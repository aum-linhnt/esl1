<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Activity;
use App\Models\UserProgress;
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
        $isTrialLesson = $lesson->is_free_trial || $lesson->hasTrialActivities();
        $isTrialMode = !$hasActiveEnrollment;

        // Security gate for non-admins / non-teachers
        if (!$user->isAdmin() && !$user->isTeacher()) {
            if (!$hasActiveEnrollment) {
                // Learners without active enrollment are in trial/preview mode ($isTrialMode = true).
                // They can view the lesson activity syllabus, but non-trial activities are locked
                // and cannot be clicked (showActivity enforces $activity->is_free_trial).
            } else {
                if ($enrollment->isSuspended()) {
                    return redirect()->route('courses.show', $courseId)
                        ->with('error', '⏸️ Quyền tham gia khóa học của bạn đang bị tạm đình chỉ (Suspended). Vui lòng liên hệ quản trị viên.');
                }

                if ($enrollment->isExpired()) {
                    return redirect()->route('courses.show', $courseId)
                        ->with('error', '⏰ Thời hạn tham gia khóa học của bạn đã kết thúc vào ngày ' . $enrollment->expires_at->format('d/m/Y') . '.');
                }

                if (!$enrollment->canGradeStudents() && !$lesson->isUnlockedFor($user)) {
                    return redirect()->route('courses.show', $courseId)
                        ->with('error', '🔒 Bài học này đang bị khóa. Bạn cần hoàn thành bài học trước đó để mở khóa bài học này.');
                }
            }
        }

        // Fetch activity completion status for each activity in the current lesson
        // In trial mode, completion is strictly not recorded/calculated
        $completedActivityIds = [];
        $completedCount = 0;
        $isLessonCompleted = false;

        $visibleActivities = $lesson->activities->where('is_visible', true)->values();
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
            ->where('order', '<', $lesson->order)
            ->orderBy('order', 'desc')
            ->first();

        $nextLesson = Lesson::where('course_id', $lesson->course_id)
            ->where('order', '>', $lesson->order)
            ->orderBy('order', 'asc')
            ->first();

        return view('lessons.show', [
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
        // Activity is only available for trial if explicitly marked as is_free_trial
        $isTrialActivity = (bool) $activity->is_free_trial;
        $isTrialMode = !$hasActiveEnrollment;

        // Security gate for non-admins / non-teachers
        if (!$user->isAdmin() && !$user->isTeacher()) {
            if (!$hasActiveEnrollment) {
                if (!$isTrialActivity) {
                    return redirect()->route('courses.show', $courseId)
                        ->with('error', '🔒 Hoạt động này yêu cầu ghi danh chính thức vào khóa học để mở khóa.');
                }
            } else {
                if ($enrollment->isSuspended()) {
                    return redirect()->route('courses.show', $courseId)
                        ->with('error', '⏸️ Quyền tham gia khóa học của bạn đang bị tạm đình chỉ (Suspended).');
                }

                if ($enrollment->isExpired()) {
                    return redirect()->route('courses.show', $courseId)
                        ->with('error', '⏰ Thời hạn tham gia khóa học của bạn đã kết thúc.');
                }

                if (!$enrollment->canGradeStudents() && !$activity->lesson->isUnlockedFor($user)) {
                    return redirect()->route('courses.show', $courseId)
                        ->with('error', '🔒 Bài học này đang bị khóa. Bạn cần hoàn thành tất cả hoạt động của bài học trước đó.');
                }
            }
        }

        $mySubmissions = [];
        if ($activity->type === Activity::TYPE_ASSIGNMENT && !$isTrialMode) {
            $mySubmissions = $activity->assignmentSubmissions()
                ->where('user_id', $user->id)
                ->with(['file', 'grader'])
                ->orderBy('attempt_number', 'desc')
                ->get();
        }

        // Resolve Quiz questions if source is bank_manual or bank_random
        if ($activity->type === Activity::TYPE_QUIZ && is_array($activity->content)) {
            $content = $activity->content;
            $sourceMode = $content['source_mode'] ?? 'inline';

            if ($sourceMode === 'bank_manual' && !empty($content['question_ids'])) {
                $bankQuestions = \App\Models\QuestionBank::whereIn('id', $content['question_ids'])->get();
                $content['questions'] = $bankQuestions->map(fn($q) => $q->toQuizFormat())->values()->toArray();
                $activity->content = $content;
            } elseif ($sourceMode === 'bank_random') {
                $count = (int) ($content['random_count'] ?? 10);
                $query = \App\Models\QuestionBank::where(function ($q) use ($courseId) {
                    $q->where('course_id', $courseId)->orWhereNull('course_id');
                });

                if (!empty($content['skill_filter']) && $content['skill_filter'] !== 'all') {
                    $query->where('skill', $content['skill_filter']);
                }
                if (!empty($content['difficulty_filter']) && $content['difficulty_filter'] !== 'all') {
                    $query->where('difficulty', $content['difficulty_filter']);
                }

                $bankQuestions = $query->inRandomOrder()->take($count)->get();
                if ($bankQuestions->isNotEmpty()) {
                    $content['questions'] = $bankQuestions->map(fn($q) => $q->toQuizFormat())->values()->toArray();
                    $activity->content = $content;
                }
            }
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

    /**
     * Legacy handler: Redirect to lesson page since completion is now per-activity.
     */
    public function complete(Request $request, $lessonId)
    {
        return redirect()->route('lessons.show', $lessonId)
            ->with('info', 'Hoàn thành bài học được ghi nhận tự động khi bạn hoàn thành từng hoạt động bên dưới.');
    }
}
