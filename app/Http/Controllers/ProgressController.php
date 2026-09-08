<?php

namespace App\Http\Controllers;

use App\Models\UserProgress;
use App\Models\Course;
use App\Models\LearnerSkill;
use App\Models\AssessmentSubmission;
use Illuminate\Http\Request;

class ProgressController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        // 1. Fetch 4-Skill Diagnostic Matrix (Listening, Speaking, Reading, Writing)
        $defaultSkills = [
            'listening' => ['title' => 'Nghe hiểu (Listening)', 'icon' => '🎧', 'color' => '#f59e0b', 'score' => 0, 'level' => 'A1'],
            'speaking' => ['title' => 'Nói (Speaking)', 'icon' => '🎙️', 'color' => '#ec4899', 'score' => 0, 'level' => 'A1'],
            'reading' => ['title' => 'Đọc hiểu (Reading)', 'icon' => '📖', 'color' => '#14b8a6', 'score' => 0, 'level' => 'A1'],
            'writing' => ['title' => 'Viết (Writing)', 'icon' => '✍️', 'color' => '#3b82f6', 'score' => 0, 'level' => 'A1'],
        ];

        $learnerSkills = LearnerSkill::where('user_id', $user->id)->get()->keyBy('skill_type');

        foreach ($defaultSkills as $key => &$skill) {
            if (isset($learnerSkills[$key])) {
                $skill['score'] = $learnerSkills[$key]->mastery_score;
                $skill['level'] = $learnerSkills[$key]->assessed_level;
                $skill['updated_at'] = $learnerSkills[$key]->last_assessed_at ? $learnerSkills[$key]->last_assessed_at->diffForHumans() : 'Chưa đánh giá';
            } else {
                $skill['updated_at'] = 'Chưa đánh giá';
            }
        }

        $overallMastery = count($defaultSkills) > 0 ? round(array_sum(array_column($defaultSkills, 'score')) / count($defaultSkills)) : 0;

        // 2. Course Progress List (Calculated by Activities & Enrollments)
        $courses = Course::where('is_published', true)->with('lessons.activities')->orderBy('order')->get();

        $courseProgress = $courses->map(function ($course) use ($user) {
            $enrollment = $user->getEnrollment($course->id);
            $allActivityIds = $course->lessons->flatMap->activities->where('is_visible', true)->pluck('id');
            $totalActivities = $allActivityIds->count();

            $completedActivities = 0;
            if ($totalActivities > 0) {
                $completedActivities = \App\Models\ActivityCompletion::where('user_id', $user->id)
                    ->whereIn('activity_id', $allActivityIds)
                    ->count();
            }

            $percentage = $enrollment
                ? (int) $enrollment->progress_percentage
                : ($totalActivities > 0 ? min(100, (int) round(($completedActivities / $totalActivities) * 100)) : 0);

            return [
                'course' => $course,
                'total' => $totalActivities,
                'completed' => $completedActivities,
                'percentage' => $percentage,
                'is_enrolled' => (bool) ($enrollment && $enrollment->hasValidAccess()),
            ];
        });

        // 3. Recent Assessment Submissions
        $recentAssessments = AssessmentSubmission::where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        return view('progress.index', [
            'user' => $user,
            'skills' => $defaultSkills,
            'overallMastery' => $overallMastery,
            'courseProgress' => $courseProgress,
            'recentAssessments' => $recentAssessments,
            'totalCoins' => $user->coins,
            'currentLevel' => $user->current_level,
        ]);
    }
}
