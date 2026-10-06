<?php

namespace App\Services\Learning;

use App\Models\LearnerSkillSnapshot;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class SkillSnapshots
{
    public function ready(): bool
    {
        return Schema::hasTable('tutor_ai_learner_skill_snapshots');
    }

    public function writing(string $id): void
    {
        if (! $this->ready() || ! Schema::hasTable('tutor_ai_writing_submissions')) return;
        $submission = DB::table('tutor_ai_writing_submissions')->where('id', $id)->where('status', 'completed')->first();
        if (! $submission || ! User::whereKey($submission->actor_id)->exists()) return;
        $result = json_decode($submission->result ?? '', true);
        $score = $result['overall_score'] ?? null;
        $scale = $result['score_scale'] ?? 'practice_0_100';
        if (! $this->validScore($score, $scale)) return;
        $criteria = $result['criteria'] ?? [];
        if (! is_array($criteria) || $criteria === []) return;
        foreach ($criteria as $criterion) {
            if (($criterion['status'] ?? null) !== 'assessed' || empty($criterion['evidence'])) return;
        }
        $issues = collect($result['issues'] ?? [])->filter(fn ($issue) => ($issue['applicable'] ?? false)
            && is_string($issue['category'] ?? null) && is_string($issue['original'] ?? null))
            ->map(fn ($issue) => ['category' => $issue['category'], 'original' => mb_strtolower(trim($issue['original']))])->unique()->values()->all();
        LearnerSkillSnapshot::firstOrCreate(['user_id' => $submission->actor_id, 'source' => 'ai_tutor_writing', 'assessment_id' => $id], [
            'skill' => 'writing', 'score' => $score, 'score_scale' => $scale, 'rubric_version' => $submission->rubric_version_id,
            'criteria' => $criteria, 'issues' => $issues, 'assessed_at' => $submission->completed_at ?? $submission->updated_at, 'created_at' => now(),
        ]);
    }

    public function speaking(User $user, array $result, bool $audio): void
    {
        if (! $audio || ! $this->ready() || ($result['assessment_verified'] ?? false) !== true
            || ! $this->validScore($result['score'] ?? null, 'practice_0_100')) return;
        LearnerSkillSnapshot::create([
            'user_id' => $user->id, 'source' => 'speech_audio', 'assessment_id' => (string) Str::uuid(), 'skill' => 'speaking',
            'score' => $result['score'], 'score_scale' => 'practice_0_100', 'rubric_version' => 'speech-provider-score-v1',
            'criteria' => ['pronunciation' => ['score' => $result['score']]],
            'issues' => collect($result['mispronounced_words'] ?? [])->filter(fn ($word) => is_string($word['word'] ?? null))
                ->map(fn ($word) => ['category' => 'pronunciation', 'original' => mb_strtolower(trim($word['word']))])->unique()->values()->all(),
            'assessed_at' => now(), 'created_at' => now(),
        ]);
    }

    private function validScore(mixed $score, string $scale): bool
    {
        return in_array($scale, ['practice_0_100', 'ielts_band_0_9'], true)
            && (is_int($score) || is_float($score)) && is_finite((float) $score)
            && $score >= 0 && $score <= ($scale === 'ielts_band_0_9' ? 9 : 100);
    }
}
