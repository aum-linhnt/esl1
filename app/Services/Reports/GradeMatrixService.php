<?php

namespace App\Services\Reports;

use App\Models\ActivityCompletion;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Support\Collection;

class GradeMatrixService
{
    /**
     * Build grade matrix data for a course (Learner x Activities).
     */
    public function buildCourseGradeMatrix(Course $course, ?string $search = null): array
    {
        $enrollments = Enrollment::with('user')
            ->where('course_id', $course->id)
            ->whereIn('status', ['active', 'completed'])
            ->get();

        $enrolledUsers = $enrollments->pluck('user')->filter()->values();
        $totalEnrolled = $enrolledUsers->count();
        $courseActivities = $course->lessons->flatMap->activities;

        $matrixCompletions = ActivityCompletion::whereIn('activity_id', $courseActivities->pluck('id'))
            ->whereIn('user_id', $enrolledUsers->pluck('id'))
            ->get();

        $byUser = $matrixCompletions->groupBy('user_id');
        $byActivity = $matrixCompletions->groupBy('activity_id');

        $gradeMatrixList = [];
        $allStudentAvgScores = [];

        foreach ($enrolledUsers as $user) {
            $userComps = $byUser->get($user->id, collect())->keyBy('activity_id');
            $scoredComps = $userComps->filter(fn($c) => $c->completed_at !== null || $c->score > 0);
            $userAvgScore = $scoredComps->count() > 0 ? round($scoredComps->avg('score'), 1) : null;
            $letterGrade = $userAvgScore !== null ? $this->getLetterGrade($userAvgScore) : '-';

            if ($userAvgScore !== null) {
                $allStudentAvgScores[] = $userAvgScore;
            }

            $actScoresMap = [];
            foreach ($courseActivities as $act) {
                $c = $userComps->get($act->id);
                $hasScore = $c !== null && ($c->score > 0 || $c->completed_at !== null);
                $score = $hasScore ? $c->score : null;
                $maxScore = $c && $c->max_score > 0 ? $c->max_score : 100;
                $pct = $score !== null && $maxScore > 0 ? round(($score / $maxScore) * 100, 1) : null;
                $passingGrade = $act->passing_grade ?? 50;
                $isPassed = $score !== null && $score >= $passingGrade;

                $actScoresMap[$act->id] = [
                    'has_score'     => $hasScore,
                    'score'         => $score,
                    'max_score'     => $maxScore,
                    'percentage'    => $pct,
                    'passing_grade' => $passingGrade,
                    'is_passed'     => $isPassed,
                    'completed_at'  => $c?->completed_at,
                    'time_spent'    => $c ? $c->time_spent_seconds : 0,
                ];
            }

            $gradeMatrixList[] = [
                'user'              => $user,
                'avg_score'         => $userAvgScore,
                'letter_grade'      => $letterGrade,
                'completed_count'   => $scoredComps->count(),
                'total_activities'  => $courseActivities->count(),
                'activities_scores' => $actScoresMap,
            ];
        }

        // Search filter
        if (!empty($search)) {
            $term = strtolower(trim($search));
            $gradeMatrixList = array_filter($gradeMatrixList, function ($item) use ($term) {
                return str_contains(strtolower($item['user']->name), $term)
                    || str_contains(strtolower($item['user']->email), $term);
            });
        }

        $gradeMatrix = collect($gradeMatrixList)->sortByDesc('avg_score')->values();

        $classOverallAvgScore = count($allStudentAvgScores) > 0
            ? round(array_sum($allStudentAvgScores) / count($allStudentAvgScores), 1)
            : 0;

        // Activity Column Summaries
        $activityColumnSummaries = [];
        foreach ($courseActivities as $act) {
            $actComps = $byActivity->get($act->id, collect())->filter(fn($c) => $c->completed_at !== null || $c->score > 0);
            $count = $actComps->count();
            $avg = $count > 0 ? round($actComps->avg('score'), 1) : null;
            $max = $count > 0 ? $actComps->max('score') : null;
            $min = $count > 0 ? $actComps->min('score') : null;
            $passingGrade = $act->passing_grade ?? 50;
            $passCount = $actComps->filter(fn($c) => $c->score >= $passingGrade)->count();
            $passRate = $count > 0 ? round(($passCount / $count) * 100, 1) : null;

            $activityColumnSummaries[$act->id] = [
                'activity'    => $act,
                'count'       => $count,
                'avg_score'   => $avg,
                'max_score'   => $max,
                'min_score'   => $min,
                'pass_count'  => $passCount,
                'pass_rate'   => $passRate,
            ];
        }

        return [
            'grade_matrix' => $gradeMatrix,
            'activity_column_summaries' => $activityColumnSummaries,
            'course_activities' => $courseActivities,
            'total_enrolled' => $totalEnrolled,
            'class_avg_score' => $classOverallAvgScore,
        ];
    }

    /**
     * Convert numeric score to Letter grade (A, B, C, D, F).
     */
    public function getLetterGrade(float $score): string
    {
        if ($score >= 90) return 'A';
        if ($score >= 80) return 'B';
        if ($score >= 70) return 'C';
        if ($score >= 60) return 'D';
        return 'F';
    }
}
