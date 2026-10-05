<?php

namespace App\Services\LMS;

use App\Integrations\AiTutor\WebsiteLmsAdapter;
use App\Models\Activity;
use App\Models\QuestionBank;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class QuizAttemptContextService
{
    public function assertAccess(User $user, Activity $activity): void
    {
        abort_unless($activity->type === Activity::TYPE_QUIZ && $activity->is_visible && $activity->isAvailable(), 403);
        abort_unless(app(WebsiteLmsAdapter::class)->canAccessLesson((string) $user->id, (string) $activity->lesson_id), 403);
        $enrollment = $user->getEnrollment($activity->lesson->course_id);
        $fullAccess = $activity->lesson->course->canPreviewFor($user, $enrollment)
            || ($enrollment && $enrollment->hasValidAccess());
        abort_unless($fullAccess || $activity->is_free_trial, 403);
    }

    public function questions(Activity $activity): array
    {
        $content = $activity->content;
        $mode = $content['source_mode'] ?? 'inline';
        if ($mode === 'inline') {
            return array_values(array_map(function (array $question, int $index) {
                // Stable, unique IDs even when legacy inline questions have no ID or duplicate IDs.
                $question['id'] = 'inline:'.$index;

                return $question;
            }, array_values($content['questions'] ?? []), array_keys(array_values($content['questions'] ?? []))));
        }
        $query = QuestionBank::query()->where(function ($query) use ($activity) {
            $query->where('course_id', $activity->lesson->course_id)->orWhereNull('course_id');
        });
        if ($mode === 'bank_manual') {
            $ids = array_values(array_unique($content['question_ids'] ?? []));
            $questions = $query->whereIn('id', $ids)->get()->keyBy('id');

            return collect($ids)->map(fn ($id) => $questions->get($id)?->toQuizFormat())->filter()->values()->all();
        }
        abort_unless($mode === 'bank_random', 422);
        foreach (['skill_filter' => 'skill', 'difficulty_filter' => 'difficulty'] as $key => $column) {
            if (! empty($content[$key]) && $content[$key] !== 'all') {
                $query->where($column, $content[$key]);
            }
        }

        return $query->inRandomOrder()->limit(max(1, min(100, (int) ($content['random_count'] ?? 10))))
            ->get()->map(fn ($question) => $question->toQuizFormat())->all();
    }

    public function start(User $user, Activity $activity): QuizAttempt
    {
        $this->assertAccess($user, $activity);

        return DB::transaction(function () use ($user, $activity) {
            // Serialize starts/submissions for one learner, including multiple tabs.
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            abort_unless($activity->canUserAttempt($user->id), 422, 'Bạn đã hết số lần làm bài.');
            $questions = $this->questions($activity);
            abort_if(! $questions, 422, 'Quiz chưa có câu hỏi.');
            QuizAttempt::where('activity_id', $activity->id)->where('user_id', $user->id)
                ->where('status', QuizAttempt::STATUS_IN_PROGRESS)->update(['status' => QuizAttempt::STATUS_ABANDONED]);

            return QuizAttempt::create([
                'activity_id' => $activity->id, 'user_id' => $user->id,
                'attempt_number' => (QuizAttempt::where('activity_id', $activity->id)->where('user_id', $user->id)->max('attempt_number') ?? 0) + 1,
                'status' => QuizAttempt::STATUS_IN_PROGRESS, 'question_snapshot' => $questions, 'started_at' => now(),
            ]);
        });
    }

    public function active(User $user, Activity $activity, string $attemptId): QuizAttempt
    {
        $this->assertAccess($user, $activity);
        $attempt = QuizAttempt::whereKey($attemptId)->where('user_id', $user->id)->where('activity_id', $activity->id)->first();
        abort_unless($attempt && $attempt->status === QuizAttempt::STATUS_IN_PROGRESS && is_array($attempt->question_snapshot)
            && $attempt->question_snapshot && $attempt->started_at, 403);
        abort_if($activity->time_limit_minutes > 0 && now()->greaterThanOrEqualTo($attempt->started_at->copy()->addMinutes($activity->time_limit_minutes)), 403);

        return $attempt;
    }
}
