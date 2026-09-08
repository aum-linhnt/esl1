<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserBadge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LeaderboardController extends Controller
{
    /**
     * Show the ESL Champions XP Leaderboard page.
     */
    public function index(Request $request)
    {
        $currentUser = $request->user();
        $timeframe = $request->get('timeframe', 'all_time'); // 'all_time', 'weekly', 'monthly'

        // Top 20 Learners ranked by XP
        $query = User::where('role', 'student')
            ->where('status', '!=', 'blocked')
            ->withCount('badges')
            ->orderBy('xp', 'desc')
            ->orderBy('streak_count', 'desc');

        $topLearners = $query->take(20)->get();

        // Calculate current user rank
        $userRank = User::where('role', 'student')
            ->where('status', '!=', 'blocked')
            ->where('xp', '>', $currentUser->xp)
            ->count() + 1;

        // Total active learners
        $totalLearners = User::where('role', 'student')->count();

        // Top 3 Podium
        $podium = $topLearners->take(3);

        return view('leaderboard.index', [
            'currentUser' => $currentUser,
            'topLearners' => $topLearners,
            'podium' => $podium,
            'userRank' => $userRank,
            'totalLearners' => $totalLearners,
            'timeframe' => $timeframe,
        ]);
    }
}
