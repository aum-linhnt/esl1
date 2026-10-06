<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Activity;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $filters = $request->validate(['level' => 'nullable|string|in:A1,A2,B1,B2,C1,C2']);
        $courses = Course::where('is_published', true)
            ->when($filters['level'] ?? null, fn ($query, $level) => $query->where('level', $level))
            ->orderBy('order')
            ->withCount('lessons')
            ->get();

        return view('courses.index', [
            'courses' => $courses,
            'userLevel' => $user->current_level,
        ]);
    }

    public function show(Request $request, $courseId)
    {
        $user = $request->user();
        $course = Course::with('lessons.activities')->findOrFail($courseId);
        $enrollment = $user->getEnrollment($courseId);
        $canPreviewAsStaff = $course->canPreviewFor($user, $enrollment);
        abort_unless($canPreviewAsStaff || $course->is_published, 404);

        $allActivityIds = $course->lessons->flatMap->activities->where('is_visible', true)->pluck('id');
        $completedActivityIds = \App\Models\ActivityCompletion::where('user_id', $user->id)
            ->whereIn('activity_id', $allActivityIds)
            ->pluck('activity_id')
            ->flip()
            ->all();

        $isEnrolled = $enrollment && $enrollment->hasValidAccess();

        $lessons = $canPreviewAsStaff ? $course->lessons : $course->lessons->where('is_visible', true);
        $lessonsWithStatus = $lessons->map(function ($lesson) use ($user, $completedActivityIds, $isEnrolled, $canPreviewAsStaff) {
            $visibleActs = $lesson->activities->where('is_visible', true);
            $totalActs = $visibleActs->count();
            $completedActs = $visibleActs->filter(fn($a) => isset($completedActivityIds[$a->id]))->count();
            $isCompleted = $totalActs > 0 ? ($completedActs >= $totalActs) : true;

            $trialActsCount = $visibleActs->where('is_free_trial', true)->count();
            $hasTrialActs = $trialActsCount > 0;
            $isTrialLesson = (bool) ($hasTrialActs || $lesson->is_free_trial);
            $canAccess = $canPreviewAsStaff || ($isEnrolled ? $lesson->isUnlockedFor($user) : $isTrialLesson);

            return [
                'lesson' => $lesson,
                'unlocked' => $canAccess,
                'is_trial' => $isTrialLesson,
                'total_activities' => $totalActs,
                'trial_activities' => $trialActsCount,
                'completed_activities' => $completedActs,
                'completed' => $isEnrolled ? $isCompleted : false,
                'score' => 0,
            ];
        });

        // Enter the learning workspace only through a visible, available lesson.
        $layoutCandidates = $lessonsWithStatus->filter(function ($item) use ($enrollment, $isEnrolled, $canPreviewAsStaff) {
            return ($canPreviewAsStaff || !$enrollment || $isEnrolled)
                && $item['unlocked']
                && ($canPreviewAsStaff || $item['lesson']->is_visible)
                && $item['lesson']->activities->contains(fn ($activity) => $canPreviewAsStaff
                    || ($activity->is_visible && $activity->isAvailable() && ($isEnrolled || $activity->is_free_trial)));
        });
        $layoutEntry = $layoutCandidates->first(fn ($item) => !$item['completed']) ?? $layoutCandidates->first();

        return view('courses.show', [
            'layoutLesson' => $layoutEntry['lesson'] ?? null,
            'course' => $course,
            'lessonsWithStatus' => $lessonsWithStatus,
        ]);
    }
}
