<?php

namespace App\Services\Reports;

use App\Models\ActivityCompletion;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Support\Collection;

class CompletionReportService
{
    /**
     * Build completion analytics, bottleneck activities, and learner completion matrix for a course.
     */
    public function buildCourseCompletionStats(Course $course, ?string $search = null): array
    {
        $enrollments = Enrollment::with('user')
            ->where('course_id', $course->id)
            ->whereIn('status', ['active', 'completed'])
            ->get();

        $enrolledUsers = $enrollments->pluck('user')->filter()->values();
        $totalEnrolled = $enrolledUsers->count();

        $courseActivities = $course->lessons->flatMap->activities;
        $totalActivities = $courseActivities->count();

        $completions = ActivityCompletion::whereIn('activity_id', $courseActivities->pluck('id'))
            ->whereIn('user_id', $enrolledUsers->pluck('id'))
            ->get();

        $byActivity = $completions->groupBy('activity_id');
        $byUser = $completions->groupBy('user_id');

        // 1. Calculate per-activity stats
        $activityStatsList = [];
        foreach ($courseActivities as $activity) {
            $compList = $byActivity->get($activity->id, collect());
            $completedRecords = $compList->whereNotNull('completed_at');
            $completedCount = $completedRecords->count();
            $rate = $totalEnrolled > 0 ? round(($completedCount / $totalEnrolled) * 100, 1) : 0;
            $avgScore = $completedRecords->count() > 0 ? round($completedRecords->avg('score'), 1) : null;
            $avgTime = $completedRecords->count() > 0 ? (int) $completedRecords->avg('time_spent_seconds') : 0;

            $activityStatsList[] = [
                'activity'         => $activity,
                'lesson'           => $activity->lesson,
                'completed_count'  => $completedCount,
                'total_enrolled'   => $totalEnrolled,
                'completion_rate'  => $rate,
                'avg_score'        => $avgScore,
                'avg_time_spent'   => $avgTime,
                'incomplete_count' => max(0, $totalEnrolled - $completedCount),
            ];
        }

        $activityStats = collect($activityStatsList);
        $topActivity = $activityStats->isNotEmpty() ? $activityStats->sortByDesc('completion_rate')->first() : null;
        $bottleneckActivity = $activityStats->isNotEmpty() ? $activityStats->sortBy('completion_rate')->first() : null;

        $totalCompletedSlots = $completions->whereNotNull('completed_at')->count();
        $totalPossibleSlots = $totalActivities * $totalEnrolled;
        $overallCourseCompletionRate = $totalPossibleSlots > 0
            ? round(($totalCompletedSlots / $totalPossibleSlots) * 100, 1)
            : 0;

        // 2. Build Learner Matrix
        $matrixList = [];
        $fullyCompletedStudents = 0;

        foreach ($enrolledUsers as $user) {
            $userComps = $byUser->get($user->id, collect())->keyBy('activity_id');
            $userCompletedCount = $userComps->whereNotNull('completed_at')->count();
            $userProgress = $totalActivities > 0 ? round(($userCompletedCount / $totalActivities) * 100) : 0;

            if ($userProgress >= 100) {
                $fullyCompletedStudents++;
            }

            $activityMap = [];
            foreach ($courseActivities as $act) {
                $c = $userComps->get($act->id);
                $activityMap[$act->id] = [
                    'is_completed' => $c && $c->completed_at !== null,
                    'score'        => $c ? $c->score : null,
                    'time_spent'   => $c ? $c->time_spent_seconds : 0,
                    'completed_at' => $c?->completed_at,
                ];
            }

            $matrixList[] = [
                'user'                => $user,
                'completed_count'     => $userCompletedCount,
                'total_activities'    => $totalActivities,
                'progress_percentage' => $userProgress,
                'activities_map'      => $activityMap,
            ];
        }

        if (!empty($search)) {
            $term = strtolower(trim($search));
            $matrixList = array_filter($matrixList, function ($item) use ($term) {
                return str_contains(strtolower($item['user']->name), $term)
                    || str_contains(strtolower($item['user']->email), $term);
            });
        }

        $learnerMatrix = collect($matrixList)->sortByDesc('progress_percentage')->values();

        return [
            'total_enrolled'                 => $totalEnrolled,
            'total_activities'               => $totalActivities,
            'overall_completion_rate'        => $overallCourseCompletionRate,
            'fully_completed_students'       => $fullyCompletedStudents,
            'top_activity'                   => $topActivity,
            'bottleneck_activity'            => $bottleneckActivity,
            'activity_stats'                 => $activityStats,
            'learner_matrix'                 => $learnerMatrix,
        ];
    }
}
