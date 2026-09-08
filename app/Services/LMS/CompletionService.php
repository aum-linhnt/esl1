<?php

namespace App\Services\LMS;

use App\Models\User;
use App\Models\Activity;
use App\Models\ActivityCompletion;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\QuizAttempt;
use App\Models\UserProgress;
use Carbon\Carbon;

class CompletionService
{
    public function __construct(
        private EnrollmentService $enrollmentService,
        private GamificationService $gamificationService
    ) {}

    /**
     * Check if a user is eligible to complete an activity under current criteria.
     *
     * @param User $user
     * @param Activity $activity
     * @param int|null $score
     * @return array
     */
    public function canComplete(User $user, Activity $activity, ?int $score = null): array
    {
        $lesson = $activity->lesson;
        $courseId = $lesson->course_id;

        // 1. Enrollment & Trial Check
        $enrollment = $user->getEnrollment($courseId);
        $isEnrolled = $enrollment && $enrollment->hasValidAccess();

        if (!$isEnrolled) {
            return [
                'allowed' => false,
                'trial_mode' => true,
                'message' => '✨ Bạn đang ở chế độ học thử. Hãy ghi danh vào khóa học để lưu tiến trình và nhận chứng chỉ!',
            ];
        }

        // 2. Max attempts check for Quiz activities
        if ($activity->type === Activity::TYPE_QUIZ) {
            $max = (int) ($activity->max_attempts ?? 0);
            if ($max > 0) {
                $completedCount = QuizAttempt::where('activity_id', $activity->id)
                    ->where('user_id', $user->id)
                    ->where('status', QuizAttempt::STATUS_COMPLETED)
                    ->count();
                if ($completedCount >= $max) {
                    return [
                        'allowed' => false,
                        'trial_mode' => false,
                        'message' => "Bạn đã sử dụng hết số lần làm bài cho phép ({$max} lần).",
                    ];
                }
            }
        }

        // 3. Passing Grade Enforcement for non-quiz AUTO_GRADE activities
        if ($activity->type !== Activity::TYPE_QUIZ && $activity->completion_type === Activity::COMPLETION_AUTO_GRADE) {
            $passingGrade = (float) ($activity->passing_grade ?? 0);
            $userScore = $score ?? 0;
            if ($userScore < $passingGrade) {
                return [
                    'allowed' => false,
                    'passed' => false,
                    'trial_mode' => false,
                    'score' => $userScore,
                    'passing_grade' => $passingGrade,
                    'message' => "Điểm số của bạn ({$userScore}%) chưa đạt ngưỡng điểm yêu cầu ({$passingGrade}%). Hãy làm lại để hoàn thành!",
                ];
            }
        }

        // 4. Submission Check for AUTO_SUBMIT activities (e.g. Assignment)
        if ($activity->completion_type === Activity::COMPLETION_AUTO_SUBMIT && $activity->type === Activity::TYPE_ASSIGNMENT) {
            $hasSubmitted = $activity->assignmentSubmissions()->where('user_id', $user->id)->exists();
            if (!$hasSubmitted) {
                return [
                    'allowed' => false,
                    'passed' => false,
                    'trial_mode' => false,
                    'message' => 'Bạn cần nộp bài tập trước khi được ghi nhận hoàn thành hoạt động này.',
                ];
            }
        }

        return [
            'allowed' => true,
            'trial_mode' => false,
        ];
    }

    /**
     * Centralized activity completion execution:
     * - Records ActivityCompletion (or QuizAttempt for Quizzes)
     * - Updates Lesson-level UserProgress
     * - Recalculates Course Enrollment progress
     * - Awards XP, Coins, Streak & Badges via GamificationService
     *
     * @param User $user
     * @param Activity $activity
     * @param array $data ['score' => int, 'max_score' => int, 'time_spent_seconds' => int, 'answers_payload' => array]
     * @return array
     */
    public function completeActivity(User $user, Activity $activity, array $data = []): array
    {
        $score = isset($data['score']) ? (float) $data['score'] : 100.0;
        $maxScore = isset($data['max_score']) ? (float) $data['max_score'] : 100.0;
        $timeSpent = isset($data['time_spent_seconds']) ? (int) $data['time_spent_seconds'] : 0;
        $answersPayload = $data['answers_payload'] ?? [];
        $startedAt = !empty($data['started_at']) ? Carbon::parse($data['started_at']) : Carbon::now()->subSeconds($timeSpent);

        // Verify eligibility
        $check = $this->canComplete($user, $activity, (int) $score);
        if (!$check['allowed']) {
            return array_merge(['success' => false, 'is_completed' => false], $check);
        }

        $lesson = $activity->lesson;
        $courseId = $lesson->course_id;
        $passingGrade = (float) ($activity->passing_grade ?? 0);

        $attempt = null;
        $effectiveScore = $score;

        // 1. If Activity is QUIZ: Create QuizAttempt and calculate aggregate score
        if ($activity->type === Activity::TYPE_QUIZ) {
            $attemptNumber = QuizAttempt::where('activity_id', $activity->id)
                ->where('user_id', $user->id)
                ->count() + 1;

            $isAttemptPassed = ($passingGrade <= 0) || ($score >= $passingGrade);

            $attempt = QuizAttempt::create([
                'activity_id' => $activity->id,
                'user_id' => $user->id,
                'attempt_number' => $attemptNumber,
                'status' => QuizAttempt::STATUS_COMPLETED,
                'score' => $score,
                'max_score' => $maxScore,
                'percentage' => $maxScore > 0 ? round(($score / $maxScore) * 100, 2) : 0,
                'is_passed' => $isAttemptPassed,
                'time_spent_seconds' => $timeSpent,
                'answers_payload' => $answersPayload,
                'started_at' => $startedAt,
                'completed_at' => Carbon::now(),
            ]);

            // Calculate aggregate score by grading method (highest, last, average, first)
            $effectiveScore = $activity->calculateGradingMethodScore($user->id);
        }

        $isPassed = ($passingGrade <= 0) || ($effectiveScore >= $passingGrade);

        // 2. Record or Update ActivityCompletion
        $completion = ActivityCompletion::updateOrCreate(
            ['user_id' => $user->id, 'activity_id' => $activity->id],
            [
                'lesson_id' => $lesson->id,
                'score' => $effectiveScore,
                'max_score' => $maxScore,
                'time_spent_seconds' => $timeSpent,
                'completed_at' => $isPassed ? Carbon::now() : null,
            ]
        );

        $reward = null;
        $lessonCompleted = false;
        $lessonPercentage = 0;
        $courseProgress = 0;

        // 3. Only advance lesson progress & award XP if passed criteria
        if ($isPassed || $activity->completion_type !== Activity::COMPLETION_AUTO_GRADE) {
            $lessonProgress = $this->updateLessonProgress($user, $lesson);
            $lessonCompleted = (bool) $lessonProgress->completed;

            $enrollment = Enrollment::where('user_id', $user->id)
                ->where('course_id', $courseId)
                ->where('status', Enrollment::STATUS_ACTIVE)
                ->first();

            if ($enrollment) {
                $this->enrollmentService->recalculateProgress($enrollment);
                $enrollment->refresh();
                $courseProgress = $enrollment->progress_percentage;
            }

            $reward = $this->gamificationService->awardActivityCompletion($user, $activity, (int) $effectiveScore);

            $totalVisibleActivities = $lesson->activities()->where('is_visible', true)->count();
            $completedVisibleActivities = ActivityCompletion::where('user_id', $user->id)
                ->where('lesson_id', $lesson->id)
                ->whereNotNull('completed_at')
                ->count();

            $lessonPercentage = $totalVisibleActivities > 0
                ? min(100, (int) round(($completedVisibleActivities / $totalVisibleActivities) * 100))
                : 100;
        }

        $allAttempts = $activity->type === Activity::TYPE_QUIZ ? $activity->getUserAttempts($user->id) : [];

        $msg = $isPassed
            ? ($activity->type === Activity::TYPE_QUIZ ? "Lần làm #{$attempt?->attempt_number} hoàn thành! Điểm tổng kết: {$effectiveScore}% (Đạt yêu cầu)" : "Hoàn thành hoạt động thành công!")
            : "Lần làm #{$attempt?->attempt_number} đã được lưu. Điểm lần này: {$score}%. Điểm tổng kết ({$activity->grading_method}): {$effectiveScore}%. Cần đạt {$passingGrade}% để hoàn thành bài.";

        return [
            'success' => true,
            'is_completed' => $isPassed,
            'trial_mode' => false,
            'completion' => $completion,
            'attempt' => $attempt,
            'attempts' => $allAttempts,
            'final_grade' => $effectiveScore,
            'grading_method' => $activity->grading_method ?: 'highest',
            'can_reattempt' => $activity->canUserAttempt($user->id),
            'remaining_attempts' => $activity->getRemainingAttempts($user->id),
            'lesson_id' => $lesson->id,
            'lesson_completed' => $lessonCompleted,
            'lesson_progress' => $lessonPercentage,
            'course_progress' => $courseProgress,
            'reward' => $reward,
            'message' => $msg,
        ];
    }

    /**
     * Update lesson-level UserProgress based on all activities in this lesson.
     */
    public function updateLessonProgress(User $user, Lesson $lesson): UserProgress
    {
        $visibleActivities = $lesson->activities()->where('is_visible', true)->get();
        $totalActivities = $visibleActivities->count();

        $completions = ActivityCompletion::where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->whereIn('activity_id', $visibleActivities->pluck('id'))
            ->get();

        $completedCount = $completions->count();
        $isFullyCompleted = $totalActivities > 0 ? ($completedCount >= $totalActivities) : true;

        $totalScore = $completions->sum('score');
        $totalMaxScore = $completions->sum('max_score');
        $avgScore = $totalMaxScore > 0 ? (int) round(($totalScore / $totalMaxScore) * 100) : 0;
        $totalTimeSpent = $completions->sum('time_spent_seconds');

        return UserProgress::updateOrCreate(
            ['user_id' => $user->id, 'lesson_id' => $lesson->id],
            [
                'score' => $avgScore,
                'completed' => $isFullyCompleted,
                'completed_at' => $isFullyCompleted ? Carbon::now() : null,
                'time_spent_seconds' => $totalTimeSpent,
            ]
        );
    }

    /**
     * Get completion status for all activities in a lesson (JSON / UI helper).
     */
    public function getLessonCompletionStatus(User $user, Lesson|int $lesson): array
    {
        $lessonModel = $lesson instanceof Lesson ? $lesson : Lesson::with('activities')->findOrFail($lesson);
        $visibleActivities = $lessonModel->activities->where('is_visible', true);

        $completions = ActivityCompletion::where('user_id', $user->id)
            ->where('lesson_id', $lessonModel->id)
            ->get()
            ->keyBy('activity_id');

        $activities = $visibleActivities->map(function ($activity) use ($completions) {
            $completion = $completions->get($activity->id);
            return [
                'activity_id' => $activity->id,
                'title' => $activity->title,
                'type' => $activity->type,
                'is_completed' => $completion !== null,
                'score' => $completion?->score ?? 0,
                'max_score' => $completion?->max_score ?? 100,
                'time_spent' => $completion?->time_spent_seconds ?? 0,
                'completed_at' => $completion?->completed_at?->toISOString(),
            ];
        })->values();

        $totalActivities = $visibleActivities->count();
        $completedCount = $completions->whereIn('activity_id', $visibleActivities->pluck('id'))->count();

        return [
            'lesson_id' => (int) $lessonModel->id,
            'total_activities' => $totalActivities,
            'completed_count' => $completedCount,
            'progress_percentage' => $totalActivities > 0
                ? (int) round(($completedCount / $totalActivities) * 100)
                : 0,
            'is_fully_completed' => $totalActivities > 0 ? ($completedCount >= $totalActivities) : true,
            'activities' => $activities,
        ];
    }
}
