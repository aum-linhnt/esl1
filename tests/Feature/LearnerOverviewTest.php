<?php

namespace Tests\Feature;

use App\Models\LearnerSkillSnapshot;
use App\Models\User;
use App\Services\AI\AiSpeakingService;
use App\Services\Learning\LearnerOverview;
use App\Services\Learning\SkillSnapshots;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use TDSoft\AiTutor\Events\WritingAssessmentCompleted;
use Tests\TestCase;

class LearnerOverviewTest extends TestCase
{
    use RefreshDatabase;

    private function completedWriting(User $user, array $result, string $status = 'completed'): string
    {
        $id = (string) Str::uuid();
        $draft = (string) Str::uuid();
        $rubric = (string) Str::uuid();
        $version = (string) Str::uuid();
        DB::table('tutor_ai_assessment_rubrics')->insert(['id' => $rubric, 'key' => $rubric, 'skill' => 'writing', 'task' => 'cefr_writing', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('tutor_ai_assessment_rubric_versions')->insert(['id' => $version, 'rubric_id' => $rubric, 'version' => 1, 'prompt_version' => 'v1', 'criteria' => '{}', 'fingerprint' => str_repeat('a', 64), 'created_at' => now()]);
        DB::table('tutor_ai_writing_drafts')->insert(['id' => $draft, 'actor_id' => (string) $user->id, 'profile' => '{}', 'task' => 'cefr_writing', 'topic' => 'Hobbies', 'revision' => 1, 'content' => 'I likes reading books.', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('tutor_ai_writing_submissions')->insert(['id' => $id, 'draft_id' => $draft, 'actor_id' => (string) $user->id,
            'revision' => 1, 'original' => 'I likes reading books.', 'profile' => '{}', 'task' => 'cefr_writing', 'topic' => 'Hobbies',
            'rubric_version_id' => $version, 'rubric_snapshot' => '{}', 'encrypted_payload' => 'unused', 'feature' => 'writing_assessment',
            'request_id' => (string) Str::uuid(), 'idempotency_key' => (string) Str::uuid(), 'fingerprint' => str_repeat('b', 64),
            'status' => $status, 'result' => json_encode($result), 'completed_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        event(new WritingAssessmentCompleted($id, $id, (string) $user->id, $draft, [], $version));
        return $id;
    }

    private function writingResult(float $score = 58, string $scale = 'practice_0_100'): array
    {
        return ['overall_score' => $score, 'score_scale' => $scale,
            'criteria' => ['grammar' => ['status' => 'assessed', 'score' => $score, 'evidence' => ['I likes']]],
            'issues' => [['applicable' => true, 'category' => 'grammar', 'original' => 'likes']]];
    }

    public function test_writing_event_is_idempotent_and_band_scores_keep_native_scale(): void
    {
        $user = User::factory()->create();
        $id = $this->completedWriting($user, $this->writingResult());
        app(SkillSnapshots::class)->writing($id);
        $this->assertDatabaseCount('tutor_ai_learner_skill_snapshots', 1);
        $this->actingAs($user)->get('/dashboard-v2')->assertOk()->assertSee('58');
        $this->completedWriting($user, $this->writingResult(6.5, 'ielts_band_0_9'));
        $skills = app(LearnerOverview::class)->skills($user);
        $this->assertSame(9, $skills['writing']->score_max);
        $this->assertSame(6.5, $skills['writing']->mastery_score);
        $this->get('/dashboard-v2/skills')->assertOk()->assertSee('IELTS Writing')->assertSee('6.5/9');
    }

    public function test_failed_and_unavailable_writing_are_not_added_and_backfill_is_repeatable(): void
    {
        $user = User::factory()->create();
        $this->completedWriting($user, $this->writingResult(), 'failed');
        $result = $this->writingResult(); $result['overall_score'] = null;
        $this->completedWriting($user, $result);
        $this->assertDatabaseCount('tutor_ai_learner_skill_snapshots', 0);
        $id = $this->completedWriting($user, $this->writingResult());
        LearnerSkillSnapshot::query()->delete();
        $this->artisan('ai-tutor:backfill-skills')->assertSuccessful();
        $this->artisan('ai-tutor:backfill-skills')->assertSuccessful();
        $this->assertDatabaseCount('tutor_ai_learner_skill_snapshots', 1);
    }

    public function test_audio_scores_are_saved_but_fallback_and_transcript_are_excluded(): void
    {
        $user = User::factory()->create();
        Http::fake(['*' => Http::response(['score' => 88.5, 'success' => true], 200)]);
        $this->actingAs($user)->postJson('/ai/speaking/evaluate', ['text' => 'Hello world', 'audio_base64' => 'audio'])->assertOk();
        $this->assertDatabaseHas('tutor_ai_learner_skill_snapshots', ['user_id' => $user->id, 'skill' => 'speaking', 'score' => 88.5]);
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['*' => Http::response([], 503)]);
        $this->postJson('/ai/speaking/evaluate', ['text' => 'Hello world', 'audio_base64' => 'audio'])->assertOk();
        app(SkillSnapshots::class)->speaking($user, ['score' => 95, 'assessment_verified' => true], false);
        $this->assertDatabaseCount('tutor_ai_learner_skill_snapshots', 1);
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['*' => Http::response(['success' => false, 'score' => 99], 200)]);
        $result = app(AiSpeakingService::class)->assessBase64('audio', 'Hello');
        app(SkillSnapshots::class)->speaking($user, $result, true);
        $this->assertDatabaseCount('tutor_ai_learner_skill_snapshots', 1);
    }

    public function test_repeated_errors_and_weak_skills_link_to_practice_and_history_is_private(): void
    {
        $user = User::factory()->create(['last_active_date' => today()]);
        $other = User::factory()->create();
        $this->completedWriting($user, $this->writingResult());
        $this->completedWriting($user, $this->writingResult());
        $this->completedWriting($other, $this->writingResult(91));
        $overview = app(LearnerOverview::class);
        $items = $overview->recommendations($user, $overview->skills($user), null);
        $this->assertSame('repeated_writing', $items[0]['rule']);
        $this->assertSame(route('ai-tutor.writing.index'), $items[0]['url']);
        $this->actingAs($user)->get('/dashboard-v2/skills')->assertOk()->assertDontSee('91/100');
        $this->actingAs($other)->get('/dashboard-v2')->assertOk()->assertDontSee('Sửa lỗi Writing lặp lại');
    }
}
