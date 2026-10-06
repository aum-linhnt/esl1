<?php

namespace App\Services\Learning;

use App\Models\Activity;
use App\Models\ActivityCompletion;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\QuizAttempt;
use App\Models\UserProgress;
use Illuminate\Support\Facades\DB;

final class TeacherLearnerSupport
{
    public function profile(Course $course, Enrollment $enrollment, array $report): array
    {
        abort_unless((string) $enrollment->course_id === (string) $course->id && $enrollment->course_role === 'student'
            && in_array($enrollment->status, ['active', 'completed'], true) && $enrollment->user, 404);
        $learner = $enrollment->user;
        $lessons = $course->lessons()->with('activities')->get();
        $lessonIds = $lessons->pluck('id');
        $activities = $lessons->flatMap(fn ($lesson) => $lesson->activities);
        $activityIds = $activities->pluck('id');
        $progress = UserProgress::where('user_id', $learner->id)->whereIn('lesson_id', $lessonIds)->get()->keyBy('lesson_id');
        $completions = ActivityCompletion::where('user_id', $learner->id)->whereIn('activity_id', $activityIds)
            ->whereNotNull('completed_at')->where('completed_at', '<=', now())->get()->keyBy('activity_id');
        $quizzes = QuizAttempt::where('user_id', $learner->id)->whereIn('activity_id', $activityIds)
            ->where('status', 'completed')->whereNotNull('completed_at')->where('completed_at', '<=', now())
            ->select('id', 'activity_id', 'score', 'max_score', 'completed_at')->orderByDesc('completed_at')->orderByDesc('id')->get()->groupBy('activity_id');
        $assignments = AssignmentSubmission::where('user_id', $learner->id)->whereIn('activity_id', $activityIds)
            ->whereIn('status', ['submitted', 'graded', 'returned'])->whereNotNull('submitted_at')->where('submitted_at', '<=', now())
            ->select('id', 'activity_id', 'status', 'grade', 'submitted_at', 'graded_at', 'attempt_number')
            ->orderByDesc('attempt_number')->orderByDesc('id')->get()->groupBy('activity_id');
        $chatCounts = collect();
        if ($report['ready']) {
            $chatCounts = DB::table('tutor_ai_conversation_messages as m')->join('tutor_ai_conversations as c', 'c.id', '=', 'm.conversation_id')
                ->where('c.course_id', (string) $course->id)->where('c.user_id', (string) $learner->id)
                ->whereBetween('m.created_at', [$report['start']->copy()->timezone(config('app.timezone'))->format('Y-m-d H:i:s'), $report['end']->copy()->timezone(config('app.timezone'))->format('Y-m-d H:i:s')])
                ->select('c.lesson_id')->selectRaw('COUNT(*) as total')->groupBy('c.lesson_id')->pluck('total', 'lesson_id');
        }
        $weak = collect();
        $rows = $lessons->map(function ($lesson) use ($course, $progress, $completions, $quizzes, $assignments, $chatCounts, $weak) {
            $items = $lesson->activities->map(function ($activity) use ($course, $completions, $quizzes, $assignments, $lesson, $weak) {
                $score = null; $source = null; $assessedAt = null; $pending = false;
                if ($activity->type === Activity::TYPE_QUIZ) {
                    $attempt = $quizzes->get($activity->id)?->first();
                    if ($attempt && $attempt->max_score > 0 && $attempt->score >= 0 && $attempt->score <= $attempt->max_score) {
                        $score = round(100 * $attempt->score / $attempt->max_score, 1);
                        $source = 'Quiz hoàn thành gần nhất'; $assessedAt = $attempt->completed_at;
                    }
                } elseif ($activity->type === Activity::TYPE_ASSIGNMENT) {
                    $submission = $assignments->get($activity->id)?->first();
                    $pending = $submission?->status === 'submitted';
                    if ($submission?->status === 'graded' && $submission->graded_at && $submission->graded_at->lte(now())
                        && $submission->grade !== null && $submission->grade >= 0 && $submission->grade <= 100) {
                        $score = (float) $submission->grade; $source = 'Bài tập lần nộp mới nhất đã chấm'; $assessedAt = $submission->graded_at;
                    }
                }
                $threshold = max(75, (float) ($activity->passing_grade ?: $course->passing_grade ?: 50));
                $needsReview = $score !== null && $score < $threshold;
                $item = ['activity' => $activity, 'lesson' => $lesson, 'score' => $score, 'source' => $source,
                    'assessed_at' => $assessedAt, 'pending' => $pending, 'needs_review' => $needsReview,
                    'threshold' => $threshold, 'completed' => $completions->has($activity->id)];
                if ($needsReview) $weak->push($item);
                return $item;
            });
            return ['lesson' => $lesson, 'completed' => (bool) $progress->get($lesson->id)?->completed,
                'completed_activities' => $items->where('completed', true)->count(), 'total_activities' => $items->count(),
                'questions' => (int) $chatCounts->get($lesson->id, 0), 'activities' => $items];
        });
        return ['learner' => $learner, 'enrollment' => $enrollment, 'lessons' => $rows,
            'weak' => $weak->sortBy('score')->values(), 'completed_lessons' => $rows->where('completed', true)->count(),
            'pending_assignments' => $rows->flatMap(fn ($row) => $row['activities'])->where('pending', true)->count(),
            'questions' => (int) $chatCounts->sum()];
    }
}
