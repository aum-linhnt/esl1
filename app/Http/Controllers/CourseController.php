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
        $courses = Course::where('is_published', true)
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

        $allActivityIds = $course->lessons->flatMap->activities->where('is_visible', true)->pluck('id');
        $completedActivityIds = \App\Models\ActivityCompletion::where('user_id', $user->id)
            ->whereIn('activity_id', $allActivityIds)
            ->pluck('activity_id')
            ->flip()
            ->all();

        $enrollment = $user->getEnrollment($courseId);
        $isEnrolled = $enrollment && $enrollment->hasValidAccess();

        $lessonsWithStatus = $course->lessons->map(function ($lesson) use ($user, $completedActivityIds, $isEnrolled) {
            $visibleActs = $lesson->activities->where('is_visible', true);
            $totalActs = $visibleActs->count();
            $completedActs = $visibleActs->filter(fn($a) => isset($completedActivityIds[$a->id]))->count();
            $isCompleted = $totalActs > 0 ? ($completedActs >= $totalActs) : true;

            $trialActsCount = $visibleActs->where('is_free_trial', true)->count();
            $hasTrialActs = $trialActsCount > 0;
            $isTrialLesson = (bool) ($hasTrialActs || $lesson->is_free_trial);
            $canAccess = $isEnrolled ? $lesson->isUnlockedFor($user) : $isTrialLesson;

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

        return view('courses.show', [
            'course' => $course,
            'lessonsWithStatus' => $lessonsWithStatus,
        ]);
    }
}
