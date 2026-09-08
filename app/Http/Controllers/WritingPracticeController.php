<?php

namespace App\Http\Controllers;

use App\Services\AI\AiWritingService;
use App\Models\AssessmentSubmission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WritingPracticeController extends Controller
{
    protected AiWritingService $aiWritingService;

    public function __construct(AiWritingService $aiWritingService)
    {
        $this->aiWritingService = $aiWritingService;
    }

    /**
     * Show AI Writing practice screen.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $sampleTopics = [
            ['title' => 'Describe your favorite hobby and why you enjoy it.', 'level' => 'A2', 'suggested_words' => 50],
            ['title' => 'Discuss the advantages and disadvantages of online learning.', 'level' => 'B1', 'suggested_words' => 120],
            ['title' => 'Should renewable energy completely replace fossil fuels in the next decade?', 'level' => 'B2', 'suggested_words' => 180],
        ];

        $pastSubmissions = AssessmentSubmission::where('user_id', $user->id)
            ->where('test_type', 'ai_writing')
            ->latest()
            ->take(5)
            ->get();

        return view('ai.writing', [
            'user' => $user,
            'sampleTopics' => $sampleTopics,
            'pastSubmissions' => $pastSubmissions,
        ]);
    }

    /**
     * AJAX endpoint to analyze an essay with Gemini AI.
     */
    public function analyze(Request $request): JsonResponse
    {
        $request->validate([
            'essay' => 'required|string|min:10|max:5000',
            'topic' => 'nullable|string|max:255',
            'level' => 'nullable|string|in:A1,A2,B1,B2,C1',
        ]);

        $user = $request->user();
        $level = $request->input('level', $user->current_level ?: 'B1');
        $topic = $request->input('topic', 'General Essay');

        $result = $this->aiWritingService->evaluateEssay(
            essay: $request->input('essay'),
            topic: $topic,
            targetLevel: $level
        );

        // Record submission
        AssessmentSubmission::create([
            'user_id' => $user->id,
            'lesson_id' => null,
            'test_type' => 'ai_writing',
            'total_score' => $result['overall_score'] ?? 80,
            'max_score' => 100,
            'accuracy_rate' => (float)($result['overall_score'] ?? 80),
            'is_passed' => ($result['overall_score'] ?? 0) >= 60,
            'answers_payload' => [
                'topic' => $topic,
                'essay' => $request->input('essay'),
                'evaluation' => $result,
            ],
        ]);

        // Award XP & Coins for practicing writing & update learning streak
        $xpReward = 15;
        $user->addXp($xpReward);
        $user->increment('coins', 3);
        $streakInfo = $user->recordDailyStudy();

        $result['xp_earned'] = $xpReward;
        $result['total_xp'] = $user->fresh()->xp;
        $result['current_coins'] = $user->fresh()->coins;
        $result['streak_count'] = $streakInfo['streak_count'];

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}
