<?php

namespace App\Services\LMS;

use App\Models\User;
use App\Models\Activity;
use App\Models\ActivityCompletion;
use App\Models\UserBadge;
use App\Models\AssessmentSubmission;
use App\Models\UserProgress;
use Carbon\Carbon;

class GamificationService
{
    /**
     * Record daily study activity to increment or maintain learning streak,
     * award streak bonus XP, and check for milestone badges.
     */
    public function recordDailyStudy(User $user): array
    {
        $streakInfo = $user->recordDailyStudy();
        $newBadges = $this->checkAndAwardBadges($user->id);

        return [
            'streak_count' => $streakInfo['streak_count'] ?? $user->streak_count,
            'streak_incremented' => $streakInfo['streak_incremented'] ?? false,
            'bonus_xp' => $streakInfo['bonus_xp'] ?? 0,
            'total_xp' => $user->fresh()->xp,
            'new_badges' => $newBadges,
        ];
    }

    /**
     * Award XP and Coins when an activity is completed, maintain streak, and check badges.
     *
     * @param User $user
     * @param Activity $activity
     * @param int $score Achieved score (0-100)
     * @return array
     */
    public function awardActivityCompletion(User $user, Activity $activity, int $score = 100): array
    {
        // Calculate base XP based on activity type
        $baseXp = match ($activity->type) {
            Activity::TYPE_QUIZ => 25,
            Activity::TYPE_ASSIGNMENT => 35,
            Activity::TYPE_AI_SPEAKING => 20,
            Activity::TYPE_AI_WRITING => 25,
            Activity::TYPE_VOCABULARY => 15,
            Activity::TYPE_GRAMMAR => 15,
            Activity::TYPE_AUDIO_LISTENING => 15,
            Activity::TYPE_VIDEO => 10,
            default => 10,
        };

        // Score multiplier (e.g. perfect score gets bonus)
        $scoreBonus = 0;
        if ($score >= 100) {
            $scoreBonus = 10;
        } elseif ($score >= 80) {
            $scoreBonus = 5;
        }

        $totalXp = $baseXp + $scoreBonus;
        $coins = max(1, (int) round($totalXp / 10));

        // Increment user stats
        $user->increment('xp', $totalXp);
        $user->increment('coins', $coins);

        // Record streak
        $streakData = $this->recordDailyStudy($user);

        return [
            'xp_earned' => $totalXp,
            'coins_earned' => $coins,
            'total_xp' => $user->fresh()->xp,
            'current_coins' => $user->fresh()->coins,
            'streak_count' => $streakData['streak_count'],
            'new_badges' => $streakData['new_badges'],
        ];
    }

    /**
     * Legacy streak method maintained for backward compatibility.
     */
    public function updateDailyStreak(int $userId): int
    {
        $user = User::findOrFail($userId);
        $data = $this->recordDailyStudy($user);
        return $data['streak_count'];
    }

    /**
     * Check achievement milestones and award badges if conditions are met.
     *
     * @param int $userId
     * @return array Newly awarded badges
     */
    public function checkAndAwardBadges(int $userId): array
    {
        $user = User::findOrFail($userId);
        $newBadges = [];

        $badgeDefinitions = [
            'first_step' => [
                'name' => 'Bước Đầu Tiên',
                'icon' => '🌱',
                'desc' => 'Hoàn thành bài học đầu tiên trên ESL',
                'condition' => fn() => ActivityCompletion::where('user_id', $userId)->exists()
                    || UserProgress::where('user_id', $userId)->where('completed', true)->exists(),
            ],
            'activity_10' => [
                'name' => 'Chăm Chỉ Vượt Trội',
                'icon' => '🌟',
                'desc' => 'Hoàn thành 10 hoạt động học tập',
                'condition' => fn() => ActivityCompletion::where('user_id', $userId)->count() >= 10,
            ],
            'streak_3' => [
                'name' => 'Chăm Chỉ 3 Ngày',
                'icon' => '🔥',
                'desc' => 'Duy trì chuỗi học liên tục 3 ngày',
                'condition' => fn() => $user->streak_count >= 3,
            ],
            'streak_7' => [
                'name' => 'Chiến Binh 7 Ngày',
                'icon' => '⚡',
                'desc' => 'Duy trì chuỗi học liên tục 7 ngày',
                'condition' => fn() => $user->streak_count >= 7,
            ],
            'quiz_master' => [
                'name' => 'Bậc Thầy Đề Thi',
                'icon' => '🎯',
                'desc' => 'Đạt độ chính xác 100% trong một bài đánh giá hoặc quiz',
                'condition' => fn() => AssessmentSubmission::where('user_id', $userId)->where('accuracy_rate', 100)->exists()
                    || ActivityCompletion::where('user_id', $userId)->where('score', '>=', 100)->exists(),
            ],
            'coin_collector' => [
                'name' => 'Triệu Phú Coins',
                'icon' => '💰',
                'desc' => 'Tích lũy từ 50 Coins trở lên',
                'condition' => fn() => $user->coins >= 50,
            ],
        ];

        foreach ($badgeDefinitions as $key => $def) {
            $hasBadge = UserBadge::where('user_id', $userId)->where('badge_key', $key)->exists();
            if (!$hasBadge && $def['condition']()) {
                $badge = UserBadge::create([
                    'user_id' => $userId,
                    'badge_key' => $key,
                    'badge_name' => $def['name'],
                    'badge_icon' => $def['icon'],
                    'description' => $def['desc'],
                    'unlocked_at' => Carbon::now(),
                ]);
                $newBadges[] = [
                    'id' => $badge->id,
                    'badge_key' => $badge->badge_key,
                    'name' => $badge->badge_name,
                    'icon' => $badge->badge_icon,
                    'description' => $badge->description,
                ];
            }
        }

        return $newBadges;
    }
}
