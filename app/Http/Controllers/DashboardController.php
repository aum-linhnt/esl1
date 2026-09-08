<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\User;
use App\Models\AssessmentSubmission;
use App\Models\UserProgress;
use App\Models\LearnerSkill;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $courses = Course::where('is_published', true)
            ->withCount('lessons')
            ->orderBy('order')
            ->take(6)
            ->get();

        // ─── 1. 7-DAY STREAK CALENDAR MATRIX ───
        $startOfWeek = Carbon::now()->startOfWeek();
        $today = Carbon::today()->startOfDay();
        $weeklyStreakDays = [];
        $weeklyActiveCount = 0;

        $streakCount = (int) ($user->streak_count ?? 1);
        $lastActive = $user->last_active_date ? Carbon::parse($user->last_active_date)->startOfDay() : null;

        // Determine which dates fall within the current streak window
        $streakStartDate = null;
        if ($lastActive && ($lastActive->isSameDay($today) || $lastActive->copy()->addDay()->isSameDay($today))) {
            $streakStartDate = $lastActive->copy()->subDays(max(0, $streakCount - 1));
        }

        $dayNames = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];
        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i)->startOfDay();
            $isPast = $date->lt($today);
            $isToday = $date->isSameDay($today);
            $isFuture = $date->gt($today);

            // Date is active if it falls within current continuous streak and is not future
            $isActive = false;
            if ($streakStartDate && $date->gte($streakStartDate) && $date->lte($lastActive) && !$isFuture) {
                $isActive = true;
                $weeklyActiveCount++;
            }

            $weeklyStreakDays[] = [
                'day_name' => $dayNames[$i],
                'date_num' => $date->format('d'),
                'is_today' => $isToday,
                'is_past' => $isPast,
                'is_future' => $isFuture,
                'is_active' => $isActive,
            ];
        }

        // ─── 2. DAILY QUESTS & CHALLENGES ───
        $todaySubmissions = AssessmentSubmission::where('user_id', $user->id)
            ->whereDate('created_at', $today)
            ->get();
        
        $todayCorrectAnswers = $todaySubmissions->sum('total_score') / 10;
        $todayCompletedLessons = UserProgress::where('user_id', $user->id)
            ->whereDate('updated_at', $today)
            ->where('completed', true)
            ->count();

        $dailyQuests = [
            [
                'id' => 1,
                'title' => 'Chuyên Cần Hàng Ngày',
                'description' => 'Duy trì chuỗi Streak và hoàn thành 1 hoạt động học bất kỳ',
                'icon' => '🔥',
                'current' => min($user->streak_count > 0 ? 1 : 0, 1),
                'target' => 1,
                'reward_xp' => 25,
                'is_completed' => $user->streak_count > 0,
            ],
            [
                'id' => 2,
                'title' => 'Chinh Phục Câu Hỏi',
                'description' => 'Trả lời đúng ít nhất 5 câu hỏi trong bài học hoặc đề thi',
                'icon' => '🎯',
                'current' => min((int)$todayCorrectAnswers, 5),
                'target' => 5,
                'reward_xp' => 40,
                'is_completed' => $todayCorrectAnswers >= 5,
            ],
            [
                'id' => 3,
                'title' => 'Bậc Thầy Kiến Thức',
                'description' => 'Hoàn thành trọn vẹn 1 bài học khóa học trong ngày',
                'icon' => '📖',
                'current' => min($todayCompletedLessons, 1),
                'target' => 1,
                'reward_xp' => 50,
                'is_completed' => $todayCompletedLessons >= 1,
            ],
        ];

        // ─── 3. MINI LEADERBOARD (TOP 5) ───
        $topLeaderboard = User::where('role', 'student')
            ->where('status', '!=', 'blocked')
            ->orderBy('xp', 'desc')
            ->take(5)
            ->get();

        $userRank = User::where('role', 'student')
            ->where('status', '!=', 'blocked')
            ->where('xp', '>', $user->xp)
            ->count() + 1;

        // ─── 4. SKILLS OVERVIEW ───
        $learnerSkills = LearnerSkill::where('user_id', $user->id)->get()->keyBy('skill_type');

        return view('dashboard', [
            'user' => $user,
            'isTrialExpired' => $user->isTrialExpired(),
            'coins' => $user->coins,
            'xp' => $user->xp,
            'streakCount' => $user->streak_count ?? 1,
            'courses' => $courses,
            'weeklyStreakDays' => $weeklyStreakDays,
            'weeklyActiveCount' => $weeklyActiveCount,
            'dailyQuests' => $dailyQuests,
            'topLeaderboard' => $topLeaderboard,
            'userRank' => $userRank,
            'learnerSkills' => $learnerSkills,
        ]);
    }

    public function renew()
    {
        return view('renew');
    }
}
