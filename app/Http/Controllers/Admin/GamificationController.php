<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserBadge;
use Illuminate\Http\Request;

class GamificationController extends Controller
{
    public function index(Request $request)
    {
        $topCoinHolders = User::orderByDesc('coins')->take(10)->get();
        $topStreakHolders = User::orderByDesc('streak_count')->take(10)->get();
        $recentBadges = UserBadge::with('user')->latest()->take(20)->get();

        $allUsers = User::orderBy('name')->get();

        $badgeOptions = [
            'first_step' => ['name' => 'Bước Đầu Tiên', 'icon' => '🌱', 'desc' => 'Hoàn thành bài học đầu tiên'],
            'streak_3' => ['name' => 'Chăm Chỉ 3 Ngày', 'icon' => '🔥', 'desc' => 'Duy trì chuỗi học liên tục 3 ngày'],
            'streak_7' => ['name' => 'Chiến Binh 7 Ngày', 'icon' => '⚡', 'desc' => 'Duy trì chuỗi học liên tục 7 ngày'],
            'quiz_master' => ['name' => 'Bậc Thầy Đề Thi', 'icon' => '🎯', 'desc' => 'Đạt độ chính xác 100%'],
            'coin_collector' => ['name' => 'Triệu Phú Coins', 'icon' => '💰', 'desc' => 'Tích lũy từ 50 Coins trở lên'],
            'ai_scholar' => ['name' => 'Học Giả AI', 'icon' => '🤖', 'desc' => 'Luyện tập AI Writing & Speaking thành thạo'],
        ];

        return view('admin.gamification.index', compact(
            'topCoinHolders',
            'topStreakHolders',
            'recentBadges',
            'allUsers',
            'badgeOptions'
        ));
    }

    public function awardBadge(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'badge_key' => 'required|string',
            'badge_name' => 'required|string',
            'badge_icon' => 'required|string',
            'description' => 'nullable|string',
        ]);

        $badge = UserBadge::updateOrCreate(
            ['user_id' => $request->user_id, 'badge_key' => $request->badge_key],
            [
                'badge_name' => $request->badge_name,
                'badge_icon' => $request->badge_icon,
                'description' => $request->description,
                'unlocked_at' => now(),
            ]
        );

        $user = User::find($request->user_id);

        return redirect()->route('admin.gamification.index')
            ->with('success', "Đã cấp huy hiệu {$badge->badge_name} cho học viên {$user->name}.");
    }

    public function deleteBadge($id)
    {
        $badge = UserBadge::findOrFail($id);
        $badge->delete();

        return redirect()->route('admin.gamification.index')
            ->with('success', 'Đã thu hồi huy hiệu.');
    }
}
