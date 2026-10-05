<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\User;
use App\Services\LMS\QuizAttemptContextService;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function show(Request $request, $lessonId)
    {
        $user = $request->user();
        $lesson = Lesson::with(['activities', 'course'])->findOrFail($lessonId);
        $courseId = $lesson->course_id;

        $enrollment = $user->getEnrollment($courseId);
        $hasActiveEnrollment = $enrollment && $enrollment->hasValidAccess();
        $isTrialMode = !$hasActiveEnrollment;
        $canPreviewAsStaff = $lesson->course->canPreviewFor($user, $enrollment);

        if ($denied = $this->checkLearningAccess($user, $lesson, $enrollment)) {
            return $denied;
        }

        // Fetch activity completion status for each activity in the current lesson
        // In trial mode, completion is strictly not recorded/calculated
        $completedActivityIds = [];
        $completedCount = 0;
        $isLessonCompleted = false;

        $visibleActivities = ($canPreviewAsStaff ? $lesson->activities : $lesson->activities->where('is_visible', true))->values();
        $totalActivities = $visibleActivities->count();
        $trialActivitiesCount = $visibleActivities->where('is_free_trial', true)->count();

        if (!$isTrialMode) {
            $completedActivityIds = \App\Models\ActivityCompletion::where('user_id', $user->id)
                ->where('lesson_id', $lesson->id)
                ->pluck('activity_id')
                ->toArray();

            $completedCount = count(array_intersect($completedActivityIds, $visibleActivities->pluck('id')->toArray()));
            $isLessonCompleted = $totalActivities > 0 ? ($completedCount >= $totalActivities) : true;
        }

        // Get previous and next lessons
        $prevLesson = Lesson::where('course_id', $lesson->course_id)
            ->when(!$canPreviewAsStaff, fn ($query) => $query->where('is_visible', true))
            ->where('order', '<', $lesson->order)
            ->orderBy('order', 'desc')
            ->first();

        $nextLesson = Lesson::where('course_id', $lesson->course_id)
            ->when(!$canPreviewAsStaff, fn ($query) => $query->where('is_visible', true))
            ->where('order', '>', $lesson->order)
            ->orderBy('order', 'asc')
            ->first();

        $activityTab = static fn ($activity) => match ($activity->type) {
            Activity::TYPE_VOCABULARY => 'vocabulary',
            Activity::TYPE_QUIZ, Activity::TYPE_ASSIGNMENT, Activity::TYPE_AI_SPEAKING, Activity::TYPE_AI_WRITING => 'practice',
            Activity::TYPE_PDF_DOCUMENT, Activity::TYPE_FILE, Activity::TYPE_URL => 'resources',
            default => 'lesson',
        };

        $requestedActivity = $request->validate(['activity' => 'nullable|integer'])['activity'] ?? null;
        $selectedActivity = $requestedActivity
            ? $visibleActivities->firstWhere('id', (int) $requestedActivity)
            : $visibleActivities->first(fn ($activity) => $canPreviewAsStaff || ((! $isTrialMode || $activity->is_free_trial) && $activity->isAvailable()));

        if ($requestedActivity && ! $selectedActivity) {
            abort(404);
        }

        $canStudySelected = $selectedActivity && ($canPreviewAsStaff || ((! $isTrialMode || $selectedActivity->is_free_trial) && $selectedActivity->isAvailable()));
        $activityGroups = $visibleActivities->groupBy($activityTab);
        $initialTab = $selectedActivity ? $activityTab($selectedActivity) : 'lesson';

        return view(\App\Support\LessonLayout::resolve($request), [
            'canPreviewAsStaff' => $canPreviewAsStaff,
            'selectedActivity' => $selectedActivity,
            'canStudySelected' => $canStudySelected,
            'activityGroups' => $activityGroups,
            'initialTab' => $initialTab,
            'lesson' => $lesson,
            'activities' => $visibleActivities,
            'course' => $lesson->course,
            'completedActivityIds' => $completedActivityIds,
            'completedCount' => $completedCount,
            'totalActivities' => $totalActivities,
            'trialActivitiesCount' => $trialActivitiesCount,
            'isLessonCompleted' => $isLessonCompleted,
            'isTrialMode' => $isTrialMode,
            'prevLesson' => $prevLesson,
            'nextLesson' => $nextLesson,
        ]);
    }

    public function showActivity(Request $request, $activityId)
    {
        $activity = Activity::with(['lesson.course', 'file'])->findOrFail($activityId);
        $user = $request->user();
        $courseId = $activity->lesson->course_id;
        $enrollment = $user->getEnrollment($courseId);
        $hasActiveEnrollment = $enrollment && $enrollment->hasValidAccess();
        $isTrialMode = !$hasActiveEnrollment;

        if ($denied = $this->checkLearningAccess($user, $activity->lesson, $enrollment, $activity)) {
            return $denied;
        }

        $mySubmissions = [];
        if ($activity->type === Activity::TYPE_ASSIGNMENT && !$isTrialMode) {
            $mySubmissions = $activity->assignmentSubmissions()
                ->where('user_id', $user->id)
                ->with(['file', 'grader'])
                ->orderBy('attempt_number', 'desc')
                ->get();
        }

        // Resolve the preview through the same course-scoped source as attempt snapshots.
        if ($activity->type === Activity::TYPE_QUIZ && is_array($activity->content)) {
            $content = $activity->content;
            $content['questions'] = app(QuizAttemptContextService::class)->questions($activity);
            $activity->content = $content;
        }

        $quizAttempts = collect();
        $canAttemptQuiz = true;
        $remainingQuizAttempts = null;
        $quizFinalGrade = 0;

        if ($activity->type === Activity::TYPE_QUIZ && !$isTrialMode) {
            $quizAttempts = $activity->getUserAttempts($user->id);
            $canAttemptQuiz = $activity->canUserAttempt($user->id);
            $remainingQuizAttempts = $activity->getRemainingAttempts($user->id);
            $quizFinalGrade = $activity->calculateGradingMethodScore($user->id);
        }

        $isActivityCompleted = $isTrialMode ? false : $activity->isCompletedBy($user->id);

        return view('activities.show', [
            'activity' => $activity,
            'lesson' => $activity->lesson,
            'course' => $activity->lesson->course,
            'mySubmissions' => $mySubmissions,
            'quizAttempts' => $quizAttempts,
            'canAttemptQuiz' => $canAttemptQuiz,
            'remainingQuizAttempts' => $remainingQuizAttempts,
            'quizFinalGrade' => $quizFinalGrade,
            'isActivityCompleted' => $isActivityCompleted,
            'isTrialMode' => $isTrialMode,
        ]);
    }

    /** Shared by the lesson page and direct/embedded activity requests. */
    protected function checkLearningAccess(User $user, Lesson $lesson, ?Enrollment $enrollment, ?Activity $activity = null): ?\Illuminate\Http\RedirectResponse
    {
        if ($lesson->course->canPreviewFor($user, $enrollment)) {
            return null;
        }

        abort_if(!$lesson->course->is_published || !$lesson->is_visible || ($activity && !$activity->is_visible), 404);

        if ($activity) {
            abort_unless($activity->isAvailable(), 403, 'Hoạt động chưa mở hoặc đã hết thời gian truy cập.');
        }

        $reason = null;

        if ($enrollment && !$enrollment->hasValidAccess()) {
            // Revoked enrollment must never fall back to free trial access.
            $reason = 'Quyền truy cập khóa học đã hết hạn, bị đình chỉ hoặc đã hủy. Vui lòng liên hệ quản trị viên.';
        } elseif ($enrollment && !$lesson->isUnlockedFor($user)) {
            $reason = 'Bài học này đang bị khóa. Bạn cần hoàn thành bài học trước đó để mở khóa.';
        } elseif (!$enrollment && $activity && !$activity->is_free_trial) {
            $reason = 'Hoạt động này yêu cầu ghi danh chính thức vào khóa học để mở khóa.';
        }

        return $reason ? redirect()->route('courses.show', $lesson->course_id)->with('error', $reason) : null;
    }

    /**
     * Legacy handler: Redirect to lesson page since completion is now per-activity.
     */
    public function complete(Request $request, $lessonId)
    {
        return redirect()->route('lessons.show', $lessonId)
            ->with('info', 'Hoàn thành bài học được ghi nhận tự động khi bạn hoàn thành từng hoạt động bên dưới.');
    }
}
