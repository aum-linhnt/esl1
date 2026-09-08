<?php

namespace App\Http\Controllers;

use App\Models\QuestionBank;
use App\Models\AdaptiveTestSession;
use App\Models\Course;
use App\Services\Assessment\AdaptiveTestingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdaptiveTestController extends Controller
{
    protected AdaptiveTestingService $adaptiveTestingService;

    public function __construct(
        AdaptiveTestingService $adaptiveTestingService
    ) {
        $this->adaptiveTestingService = $adaptiveTestingService;
    }

    /**
     * Start an adaptive test session and open the interactive Activity Player.
     */
    public function startAdaptive(Request $request)
    {
        $user = $request->user();
        $startLevel = $request->input('start_level', $user->current_level ?: 'A1');
        
        $session = $this->adaptiveTestingService->initializeSession($user->id, $startLevel);

        return redirect()->route('practice.player', ['session' => $session->id]);
    }

    /**
     * Activity / Adaptive Quiz Player view.
     */
    public function player(Request $request, int $sessionId)
    {
        $user = $request->user();
        $session = AdaptiveTestSession::where('id', $sessionId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // If finished, load full review details & recommended courses
        $rawHistory = $session->answers_history ?? [];
        $history = $this->hydrateAnswerHistory($rawHistory);
        $skillMatrix = $this->adaptiveTestingService->calculateSkillMatrix($history);
        $finalLevel = $session->final_level ?: $this->adaptiveTestingService->calculateFinalLevel($history);

        $recommendedCourses = Course::where('is_published', true)
            ->where(function($q) use ($finalLevel) {
                $q->where('level', $finalLevel)->orWhere('level', 'All Levels');
            })
            ->take(3)
            ->get();

        if ($recommendedCourses->isEmpty()) {
            $recommendedCourses = Course::where('is_published', true)->take(3)->get();
        }

        return view('practice.player', [
            'session' => $session,
            'user' => $user,
            'history' => $history,
            'skillMatrix' => $skillMatrix,
            'finalLevel' => $finalLevel,
            'recommendedCourses' => $recommendedCourses,
        ]);
    }

    /**
     * Start adaptive test by specific exam set key.
     */
    public function startAdaptiveByKey(Request $request, ?string $testKey = null)
    {
        $user = $request->user();
        $startLevel = match($testKey) {
            'adaptive_vstep_4skills' => 'B1',
            'adaptive_ielts_fasttrack' => 'B2',
            'adaptive_business_pro' => 'B1',
            default => ($user->current_level ?: 'A1'),
        };

        $session = $this->adaptiveTestingService->initializeSession($user->id, $startLevel);

        return redirect()->route('practice.player', ['session' => $session->id]);
    }

    /**
     * Dedicated full-featured Adaptive Test Scorecard view.
     */
    public function adaptiveScorecard(Request $request, int $sessionId)
    {
        $user = $request->user();
        $session = AdaptiveTestSession::where('id', $sessionId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $rawHistory = $session->answers_history ?? [];
        $history = $this->hydrateAnswerHistory($rawHistory);
        $skillMatrix = $this->adaptiveTestingService->calculateSkillMatrix($history);
        $finalLevel = $session->final_level ?: $this->adaptiveTestingService->calculateFinalLevel($history);

        $descriptors = [
            'A1' => 'Căn bản (Breakthrough): Hiểu và sử dụng các biểu đạt quen thuộc hàng ngày, câu đơn giản nhằm đáp ứng nhu cầu cụ thể.',
            'A2' => 'Sơ cấp (Waystage): Hiểu các câu và cấu trúc thông dụng về các lĩnh vực cá nhân, gia đình, mua sắm, việc làm.',
            'B1' => 'Trung cấp (Threshold): Hiểu các ý chính của ngôn ngữ chuẩn về các chủ đề quen thuộc trong công việc, trường học, giải trí.',
            'B2' => 'Trung cao cấp (Vantage): Hiểu ý chính của văn bản phức tạp về cả chủ đề cụ thể lẫn trừu tượng, giao tiếp tự tin, trôi chảy.',
            'C1' => 'Cao cấp (Effective Operational): Sử dụng ngôn ngữ linh hoạt, hiệu quả cho các mục đích xã hội, học thuật và chuyên môn.',
        ];

        $recommendedCourses = Course::where('is_published', true)
            ->where(function($q) use ($finalLevel) {
                $q->where('level', $finalLevel)->orWhere('level', 'All Levels');
            })
            ->take(3)
            ->get();

        if ($recommendedCourses->isEmpty()) {
            $recommendedCourses = Course::where('is_published', true)->take(3)->get();
        }

        return view('practice.adaptive_scorecard', [
            'session' => $session,
            'history' => $history,
            'skillMatrix' => $skillMatrix,
            'finalLevel' => $finalLevel,
            'descriptor' => $descriptors[$finalLevel] ?? $descriptors['B1'],
            'recommendedCourses' => $recommendedCourses,
            'user' => $user,
        ]);
    }

    /**
     * JSON API: Fetch next question for the adaptive player.
     */
    public function fetchNextQuestion(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|exists:adaptive_test_sessions,id',
        ]);

        $session = AdaptiveTestSession::where('id', $request->session_id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if ($session->status === 'finished' || $session->total_questions_answered >= AdaptiveTestingService::TOTAL_TEST_QUESTIONS) {
            return response()->json([
                'finished' => true,
                'message' => 'Bài kiểm tra đã hoàn thành.',
            ]);
        }

        $question = $this->adaptiveTestingService->getNextQuestion($session->id);

        if (!$question) {
            return response()->json([
                'finished' => true,
                'message' => 'Không còn câu hỏi phù hợp.',
            ]);
        }

        // Safely normalize options to array of strings
        $rawOptions = $question->options;
        $formattedOptions = [];
        if (is_array($rawOptions)) {
            if (isset($rawOptions['left']) || isset($rawOptions['right'])) {
                foreach ((array)($rawOptions['left'] ?? []) as $v) {
                    $formattedOptions[] = is_string($v) ? $v : json_encode($v, JSON_UNESCAPED_UNICODE);
                }
                foreach ((array)($rawOptions['right'] ?? []) as $v) {
                    $formattedOptions[] = is_string($v) ? $v : json_encode($v, JSON_UNESCAPED_UNICODE);
                }
            } else {
                foreach ($rawOptions as $opt) {
                    if (is_string($opt)) {
                        $formattedOptions[] = $opt;
                    } elseif (is_array($opt)) {
                        $formattedOptions[] = $opt['text'] ?? $opt['label'] ?? $opt['value'] ?? json_encode($opt, JSON_UNESCAPED_UNICODE);
                    } else {
                        $formattedOptions[] = strval($opt);
                    }
                }
            }
        }

        $skill = $question->skill ?: 'listening';
        $skillMeta = [
            'key' => $skill,
            'name' => match($skill) {
                'listening' => 'Kỹ năng Nghe (Listening)',
                'reading' => 'Kỹ năng Đọc (Reading)',
                'writing' => 'Kỹ năng Viết (Writing)',
                'speaking' => 'Kỹ năng Nói (Speaking)',
                default => 'Tổng hợp'
            },
            'short_name' => match($skill) {
                'listening' => 'Nghe',
                'reading' => 'Đọc',
                'writing' => 'Viết',
                'speaking' => 'Nói',
                default => 'Tổng hợp'
            },
            'emoji' => match($skill) {
                'listening' => '🎧',
                'reading' => '📖',
                'writing' => '✍️',
                'speaking' => '🎙️',
                default => '⚡'
            },
            'badge_color' => match($skill) {
                'listening' => 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30',
                'reading' => 'bg-purple-500/15 text-purple-300 border-purple-500/30',
                'writing' => 'bg-cyan-500/15 text-cyan-300 border-cyan-500/30',
                'speaking' => 'bg-amber-500/15 text-amber-300 border-amber-500/30',
                default => 'bg-blue-500/15 text-blue-300 border-blue-500/30'
            },
            'step_range' => match($skill) {
                'listening' => 'Câu 1 – 12',
                'reading' => 'Câu 13 – 26',
                'writing' => 'Câu 27 – 33',
                'speaking' => 'Câu 34 – 40',
                default => 'Câu 1 – 40'
            },
        ];

        $meta = $question->meta_data ?? [];
        $passageContent = $meta['passage_content'] 
            ?? $meta['passage'] 
            ?? $meta['reading_passage'] 
            ?? null;
        $passageTitle = $meta['passage_title'] 
            ?? $meta['title'] 
            ?? ($question->skill === 'reading' ? 'Đoạn văn đọc hiểu (' . $question->difficulty . ')' : null);
        
        $audioUrl = $question->audio_url 
            ?: ($question->skill === 'listening' ? 'https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3' : null);

        return response()->json([
            'finished' => false,
            'question' => [
                'id' => $question->id,
                'skill' => $question->skill,
                'skill_meta' => $skillMeta,
                'difficulty' => $question->difficulty,
                'question_type' => $question->question_type,
                'question_text' => $question->question_text,
                'options' => $formattedOptions,
                'audio_url' => $audioUrl,
                'passage' => $passageContent,
                'passage_title' => $passageTitle,
                'part_name' => $meta['part_name'] ?? null,
                'min_words' => $meta['min_words'] ?? ($question->skill === 'writing' ? 40 : null),
                'task_type' => $meta['task_type'] ?? null,
                'prep_time' => $meta['prep_time'] ?? null,
                'duration' => $meta['duration'] ?? $meta['speak_time'] ?? null,
                'testlet_index' => $meta['testlet_index'] ?? null,
                'testlet_total' => $meta['testlet_total'] ?? null,
                'testlet_start_step' => $meta['testlet_start_step'] ?? null,
                'testlet_end_step' => $meta['testlet_end_step'] ?? null,
                'testlet_size' => $meta['testlet_size'] ?? null,
                'testlet_question_num' => $meta['testlet_question_num'] ?? null,
                'testlet_badge' => $meta['testlet_badge'] ?? null,
                'testlet_info' => $meta['testlet_info'] ?? null,
            ],
            'session' => [
                'current_step' => $session->total_questions_answered + 1,
                'total_steps' => AdaptiveTestingService::TOTAL_TEST_QUESTIONS,
                'current_difficulty' => $session->current_difficulty,
                'score' => $session->score,
                'correct_count' => $session->correct_count ?? 0,
            ],
        ]);
    }

    /**
     * JSON API: Submit answer to adaptive test and process branching.
     */
    public function submitAdaptiveAnswer(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|exists:adaptive_test_sessions,id',
            'question_id' => 'required|exists:question_banks,id',
            'answer' => 'required|string',
        ]);

        $session = AdaptiveTestSession::where('id', $request->session_id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $result = $this->adaptiveTestingService->processAnswer(
            $session->id,
            $request->question_id,
            $request->answer
        );

        $result['current_coins'] = $request->user()->fresh()->coins;

        return response()->json($result);
    }

    /**
     * Helper to hydrate question details (passages, audio, options, explanations) into answers history.
     */
    private function hydrateAnswerHistory(array $history): array
    {
        if (empty($history)) {
            return [];
        }

        $questionIds = collect($history)->pluck('question_id')->filter()->unique();
        $questions = QuestionBank::whereIn('id', $questionIds)->get()->keyBy('id');

        $hydrated = [];
        foreach ($history as $item) {
            $q = $questions->get($item['question_id'] ?? 0);
            if ($q) {
                $meta = $q->meta_data ?? [];
                $item['question_text'] = $item['question_text'] ?? $q->question_text;
                $item['skill'] = $item['skill'] ?? $q->skill;
                $item['difficulty'] = $item['difficulty'] ?? $q->difficulty;
                $item['question_type'] = $item['question_type'] ?? $q->question_type;
                $item['options'] = $item['options'] ?? $q->options;
                $item['correct_answer'] = $item['correct_answer'] ?? $q->correct_answer;
                $item['passage'] = $item['passage'] ?? ($meta['passage_content'] ?? ($meta['passage'] ?? ($meta['reading_passage'] ?? null)));
                $item['passage_title'] = $item['passage_title'] ?? ($meta['passage_title'] ?? ($meta['title'] ?? null));
                $item['audio_url'] = $item['audio_url'] ?? ($q->audio_url ?: ($q->skill === 'listening' ? 'https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3' : null));
                $item['explanation'] = $item['explanation'] ?? ($meta['explanation'] ?? ($q->explanation ?? null));
                $item['task_type'] = $item['task_type'] ?? ($meta['task_type'] ?? null);
                $item['min_words'] = $item['min_words'] ?? ($meta['min_words'] ?? 30);
            }
            $hydrated[] = $item;
        }

        return $hydrated;
    }
}
