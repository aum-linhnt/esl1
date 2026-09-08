<?php

namespace App\Http\Controllers;

use App\Services\AI\AiSpeakingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SpeakingPracticeController extends Controller
{
    protected AiSpeakingService $aiSpeakingService;

    public function __construct(AiSpeakingService $aiSpeakingService)
    {
        $this->aiSpeakingService = $aiSpeakingService;
    }

    /**
     * Show AI Speaking practice screen.
     */
    public function index(Request $request)
    {
        $practiceSentences = [
            ['text' => 'Good morning, nice to meet you here today.', 'level' => 'A1', 'phonetic' => '/ɡʊd ˈmɔːrnɪŋ naɪs tuː miːt juː hɪər təˈdeɪ/'],
            ['text' => 'The flight to Da Nang was delayed due to bad weather.', 'level' => 'A2', 'phonetic' => '/ðə flaɪt tuː dɑː nɑːŋ wəz dɪˈleɪd djuː tuː bæd ˈweðər/'],
            ['text' => 'Regular exercise is extremely beneficial for mental health.', 'level' => 'B1', 'phonetic' => '/ˈreɡjələr ˈeksərsaɪz ɪz ɪkˈstriːmli ˌbenɪˈfɪʃl fɔːr ˈmentl helθ/'],
            ['text' => 'Renewable energy sources should replace fossil fuels rapidly.', 'level' => 'B2', 'phonetic' => '/rɪˈnuːəbl ˈenərdʒi sɔːrsɪz ʃʊd rɪˈpleɪs ˈfɑːsl fjuːəlz ˈræpɪdli/'],
            ['text' => 'Comprehensive sustainability initiatives require substantial institutional commitment.', 'level' => 'C1', 'phonetic' => '/ˌkɑːmprɪˈhensɪv səˌsteɪnəˈbɪləti ɪˈnɪʃətɪvz rɪˈkwaɪər səbˈstænʃl ˌɪnstɪˈtuːʃənl kəˈmɪtmənt/'],
        ];

        return view('ai.speaking', [
            'user' => $request->user(),
            'practiceSentences' => $practiceSentences,
        ]);
    }

    /**
     * Web endpoint for AI Speaking practice: accepts Audio File, Base64, or Transcript.
     * Route: POST /ai/speaking/evaluate
     */
    public function evaluate(Request $request): JsonResponse
    {
        $request->validate([
            'reference_sentence' => 'nullable|string|max:1500',
            'text' => 'nullable|string|max:1500',
            'audio_file' => 'nullable|file|max:20480', // max 20MB
            'audio_base64' => 'nullable|string',
            'audio_url' => 'nullable|url',
            'recognized_text' => 'nullable|string|max:1500',
        ]);

        $targetText = (string) ($request->input('reference_sentence') ?: $request->input('text', ''));
        if (empty($targetText) && empty($request->input('recognized_text'))) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng cung cấp câu mẫu mục tiêu để chấm điểm phát âm.',
            ], 422);
        }

        // 1. If audio file is uploaded (Multipart Form-Data)
        if ($request->hasFile('audio_file')) {
            $result = $this->aiSpeakingService->assessAudioFile($request->file('audio_file'), $targetText);
        } 
        // 2. If base64 audio string is sent
        elseif (!empty($request->input('audio_base64'))) {
            $format = $request->input('audio_format', 'webm');
            $result = $this->aiSpeakingService->assessBase64($request->input('audio_base64'), $targetText, $format);
        }
        // 3. If audio url is provided
        elseif (!empty($request->input('audio_url'))) {
            $result = $this->aiSpeakingService->assessAudioUrl($request->input('audio_url'), $targetText);
        }
        // 4. Fallback text transcript (e.g. Web Speech API)
        else {
            $recognizedText = (string) $request->input('recognized_text', $targetText);
            $result = $this->aiSpeakingService->evaluatePronunciation($recognizedText, $targetText);
        }

        // Gamification rewards for authenticated student
        $user = $request->user();
        if ($user) {
            $xpReward = 10;
            $user->addXp($xpReward);
            $user->increment('coins', 2);
            $streakInfo = $user->recordDailyStudy();

            $result['xp_earned'] = $xpReward;
            $result['total_xp'] = $user->fresh()->xp;
            $result['current_coins'] = $user->fresh()->coins;
            $result['streak_count'] = $streakInfo['streak_count'] ?? 1;
        }

        return response()->json(array_merge([
            'success' => true,
            'data' => $result,
        ], $result));
    }

    /**
     * API v1: Multipart Form-Data (audio_file, text)
     * Route: POST /api/v1/assess
     */
    public function assessMultipart(Request $request): JsonResponse
    {
        $request->validate([
            'audio_file' => 'required|file|max:20480',
            'text' => 'required|string|max:1500',
        ]);

        $result = $this->aiSpeakingService->assessAudioFile(
            $request->file('audio_file'),
            $request->input('text')
        );

        return response()->json($result);
    }

    /**
     * API v1: JSON Payload (audio_base64, text, audio_format)
     * Route: POST /api/v1/assess-base64
     */
    public function assessBase64(Request $request): JsonResponse
    {
        $request->validate([
            'audio_base64' => 'required|string',
            'text' => 'required|string|max:1500',
            'audio_format' => 'nullable|string|max:20',
        ]);

        $result = $this->aiSpeakingService->assessBase64(
            $request->input('audio_base64'),
            $request->input('text'),
            $request->input('audio_format', 'webm')
        );

        return response()->json($result);
    }

    /**
     * API v1: JSON Payload (audio_url, text)
     * Route: POST /api/v1/assess-url
     */
    public function assessUrl(Request $request): JsonResponse
    {
        $request->validate([
            'audio_url' => 'required|url',
            'text' => 'required|string|max:1500',
        ]);

        $result = $this->aiSpeakingService->assessAudioUrl(
            $request->input('audio_url'),
            $request->input('text')
        );

        return response()->json($result);
    }
}
