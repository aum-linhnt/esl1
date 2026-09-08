<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Activity;
use App\Models\QuestionBank;
use App\Models\AssessmentSubmission;
use App\Models\UserBadge;
use App\Models\UserActivityLog;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. User Stats
        $totalUsers = User::count();
        $studentsCount = User::where('role', 'student')->count();
        $teachersCount = User::where('role', 'teacher')->count();
        $adminsCount = User::where('role', 'admin')->count();
        $activeUsersCount = User::where('status', 'active')->count();
        $trialExpiredCount = User::where('status', 'trial_expired')->count();

        // 2. Learning Content Stats
        $totalCourses = Course::count();
        $totalLessons = Lesson::count();
        $totalActivities = Activity::count();
        $totalQuestions = QuestionBank::count();

        // 3. Submissions & Assessment Stats
        $totalSubmissions = AssessmentSubmission::count();
        $passedSubmissionsCount = AssessmentSubmission::where('is_passed', true)->count();
        $avgAccuracy = (float) (AssessmentSubmission::avg('accuracy_rate') ?? 0);

        // 4. Gamification Stats
        $totalCoins = User::sum('coins');
        $activeStreaksCount = User::where('streak_count', '>=', 3)->count();
        $totalBadgesUnlocked = UserBadge::count();

        // 5. CEFR Distribution Breakdown
        $cefrDistribution = [
            'A1' => User::where('current_level', 'A1')->count(),
            'A2' => User::where('current_level', 'A2')->count(),
            'B1' => User::where('current_level', 'B1')->count(),
            'B2' => User::where('current_level', 'B2')->count(),
        ];

        // 6. Recent Submissions
        $recentSubmissions = AssessmentSubmission::with('user')
            ->latest()
            ->take(6)
            ->get();

        // 7. Recent Students
        $recentUsers = User::latest()
            ->take(6)
            ->get();

        // 8. Recent Telemetry Activity Logs
        $recentActivityLogs = UserActivityLog::with('user', 'activity')
            ->latest()
            ->take(6)
            ->get();

        return view('admin.dashboard', compact(
            'totalUsers',
            'studentsCount',
            'teachersCount',
            'adminsCount',
            'activeUsersCount',
            'trialExpiredCount',
            'totalCourses',
            'totalLessons',
            'totalActivities',
            'totalQuestions',
            'totalSubmissions',
            'passedSubmissionsCount',
            'avgAccuracy',
            'totalCoins',
            'activeStreaksCount',
            'totalBadgesUnlocked',
            'cefrDistribution',
            'recentSubmissions',
            'recentUsers',
            'recentActivityLogs'
        ));
    }
}
