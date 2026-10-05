<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use TDSoft\AiTutor\Assessment\RubricRepository;
use TDSoft\AiTutor\Contracts\Entitlements;
use TDSoft\AiTutor\SubjectEnglish\PracticeRubrics;
use TDSoft\AiTutor\Writing\ProcessWritingSubmission;
use Tests\TestCase;

final class WritingApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => 'student', 'status' => 'active']));
        config(['ai-tutor.enabled' => true, 'queue.default' => 'database', 'queue.connections.database.retry_after' => 90]);
        $this->app->instance(Entitlements::class, new class implements Entitlements
        {
            public function allows(string $module): bool
            {
                return true;
            }
        });
        (new PracticeRubrics)->provision(new RubricRepository);
        Queue::fake();
    }

    private function draft(): array
    {
        return $this->postJson('/ai-tutor/api/v1/writing/drafts', ['framework' => 'cefr', 'target' => 'B1',
            'task' => 'cefr_writing', 'topic' => 'My hobby', 'content' => 'I like reading books.'])->assertCreated()->json();
    }

    public function test_writing_pages_render_editor_without_embedding_learner_content_as_html(): void
    {
        $this->withoutVite();
        $this->get('/ai-tutor/writing')->assertOk()->assertSee('Writing Studio')->assertSee('data-writing-editor', false);
        $draft = $this->draft();
        $this->get('/ai-tutor/writing/'.$draft['id'])->assertOk()->assertSee('data-draft="'.$draft['id'].'"', false)
            ->assertDontSee('I like reading books.', false);
        $this->actingAs(User::factory()->create(['role' => 'student', 'status' => 'active']));
        $this->get('/ai-tutor/writing/'.$draft['id'])->assertForbidden();
    }

    public function test_history_paginates_only_owned_drafts_and_lookup_is_read_only(): void
    {
        $draft = $this->draft();
        for ($i = 0; $i < 20; $i++) {
            $this->draft();
        }
        $this->getJson('/ai-tutor/api/v1/writing/drafts')->assertOk()->assertJsonCount(20, 'data')->assertJsonPath('next_page', 2);
        $this->getJson('/ai-tutor/api/v1/writing/drafts?page=2')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('next_page', null);
        $submission = $this->postJson('/ai-tutor/api/v1/writing/drafts/'.$draft['id'].'/submit', ['revision' => 1,
            'request_id' => (string) Str::uuid(), 'idempotency_key' => 'lookup-submission', 'confirm_cost' => true])->assertStatus(202)->json();
        $this->getJson('/ai-tutor/api/v1/writing/drafts/'.$draft['id'].'/submissions?request_id='.$submission['request_id'])
            ->assertOk()->assertJsonPath('data.0.id', $submission['id'])->assertJsonMissingPath('data.0.encrypted_payload');
        $this->assertSame(0, DB::table('tutor_ai_requests')->count());
        $this->actingAs(User::factory()->create(['role' => 'student', 'status' => 'active']));
        $this->getJson('/ai-tutor/api/v1/writing/drafts')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/ai-tutor/api/v1/writing/drafts/'.$draft['id'].'/submissions?request_id='.$submission['request_id'])->assertForbidden();
    }

    public function test_autosave_submit_and_poll_are_session_authenticated_and_do_not_call_provider(): void
    {
        $draft = $this->draft();
        $this->patchJson('/ai-tutor/api/v1/writing/drafts/'.$draft['id'], ['revision' => 1, 'content' => 'I enjoy reading books.'])
            ->assertOk()->assertJsonPath('revision', 2);
        $this->patchJson('/ai-tutor/api/v1/writing/drafts/'.$draft['id'], ['revision' => 1, 'content' => 'Stale essay'])
            ->assertStatus(409)->assertJsonPath('error.code', 'AI_WRITING_REVISION_CONFLICT');
        $data = ['revision' => 2, 'request_id' => (string) Str::uuid(), 'idempotency_key' => 'api-submit', 'confirm_cost' => true];
        $path = '/ai-tutor/api/v1/writing/drafts/'.$draft['id'].'/submit';
        $submission = $this->postJson($path, $data)->assertStatus(202)->assertJsonMissingPath('encrypted_payload')->json();
        $this->postJson($path, $data)->assertStatus(202)->assertJsonPath('id', $submission['id']);
        $this->getJson('/ai-tutor/api/v1/writing/submissions/'.$submission['id'])->assertOk()->assertJsonPath('status', 'queued');
        Queue::assertPushed(ProcessWritingSubmission::class, fn ($job) => $job->submissionId === $submission['id']);
        $this->assertSame(1, DB::table('tutor_ai_writing_submissions')->count());
        $this->assertSame(0, DB::table('tutor_ai_requests')->count());
    }

    public function test_owner_and_entitlement_guards_apply_to_draft_and_submission_reads(): void
    {
        $draft = $this->draft();
        $this->actingAs(User::factory()->create(['role' => 'student', 'status' => 'active']));
        $this->getJson('/ai-tutor/api/v1/writing/drafts/'.$draft['id'])->assertForbidden();
        $this->app->instance(Entitlements::class, new class implements Entitlements
        {
            public function allows(string $module): bool
            {
                return false;
            }
        });
        $this->getJson('/ai-tutor/api/v1/writing/drafts/'.$draft['id'])->assertForbidden();
    }

    public function test_browser_cannot_override_actor_model_rubric_or_result(): void
    {
        $this->postJson('/ai-tutor/api/v1/writing/drafts', ['framework' => 'cefr', 'target' => 'B1',
            'task' => 'cefr_writing', 'topic' => 'My hobby', 'content' => 'My essay.', 'model' => 'browser-model',
            'actor_id' => 'other', 'rubric_version_id' => 'browser-rubric', 'result' => ['score' => 100]])
            ->assertUnprocessable()->assertJsonValidationErrors(['model', 'actor_id', 'rubric_version_id', 'result']);
        $this->assertSame(0, DB::table('tutor_ai_writing_drafts')->count());
    }

    public function test_submit_requires_cost_confirmation_and_async_queue(): void
    {
        $draft = $this->draft();
        $path = '/ai-tutor/api/v1/writing/drafts/'.$draft['id'].'/submit';
        $data = ['revision' => 1, 'request_id' => (string) Str::uuid(), 'idempotency_key' => 'bad-queue'];
        $this->postJson($path, $data)->assertUnprocessable()->assertJsonValidationErrors('confirm_cost');
        config(['queue.default' => 'sync']);
        $this->postJson($path, [...$data, 'confirm_cost' => true])->assertStatus(409)->assertJsonPath('error.code', 'AI_WRITING_QUEUE_INVALID');
        Queue::assertNothingPushed();
        $this->assertSame(0, DB::table('tutor_ai_writing_submissions')->count());
    }
}
