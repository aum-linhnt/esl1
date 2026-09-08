<?php

namespace App\Http\Controllers;

use App\Services\AI\AiTutorService;
use App\Services\LMS\GamificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class AiChatController extends Controller
{
    public const DAILY_QUESTION_LIMIT = 10;

    protected AiTutorService $aiTutorService;
    protected GamificationService $gamificationService;

    public function __construct(AiTutorService $aiTutorService, GamificationService $gamificationService)
    {
        $this->aiTutorService = $aiTutorService;
        $this->gamificationService = $gamificationService;
    }

    /**
     * Get remaining questions today for current user or guest.
     */
    public static function getRemainingQuestions(?int $userId = null, ?string $ip = null): int
    {
        $keyIdentifier = $userId ?: ($ip ?: 'guest');
        $cacheKey = "ai_chat_daily_{$keyIdentifier}_" . now()->format('Y-m-d');
        $used = (int) Cache::get($cacheKey, 0);

        return max(0, self::DAILY_QUESTION_LIMIT - $used);
    }

    /**
     * Handle incoming tutor questions from the floating chat widget.
     */
    public function message(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'context' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $keyIdentifier = $user ? $user->id : $request->ip();
        $cacheKey = "ai_chat_daily_{$keyIdentifier}_" . now()->format('Y-m-d');
        $usedCount = (int) Cache::get($cacheKey, 0);

        // Daily limit: 10 questions per day per student
        if ($usedCount >= self::DAILY_QUESTION_LIMIT) {
            return response()->json([
                'success' => false,
                'exceeded' => true,
                'reply' => "Bạn đã sử dụng hết " . self::DAILY_QUESTION_LIMIT . "/" . self::DAILY_QUESTION_LIMIT . " lượt hỏi AI hôm nay. Hãy quay lại vào ngày mai nhé! 🌟",
                'remaining' => 0,
                'max' => self::DAILY_QUESTION_LIMIT,
                'timestamp' => now()->format('H:i'),
            ]);
        }

        // Increment count and store until midnight
        $usedCount++;
        Cache::put($cacheKey, $usedCount, now()->endOfDay());
        $remaining = max(0, self::DAILY_QUESTION_LIMIT - $usedCount);

        // Update streak on interaction
        if ($user) {
            $this->gamificationService->updateDailyStreak($user->id);
        }

        $reply = $this->aiTutorService->askTutor(
            userMessage: $request->input('message'),
            lessonContext: $request->input('context', ''),
            userLevel: $user ? $user->current_level : 'A1'
        );

        return response()->json([
            'success' => true,
            'reply' => $reply,
            'remaining' => $remaining,
            'max' => self::DAILY_QUESTION_LIMIT,
            'timestamp' => now()->format('H:i'),
        ]);
    }
}
