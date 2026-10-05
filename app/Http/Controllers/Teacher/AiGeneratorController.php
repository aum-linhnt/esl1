<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Services\AI\AiExerciseGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AiGeneratorController extends Controller
{
    protected AiExerciseGeneratorService $aiExerciseGenerator;

    public function __construct(AiExerciseGeneratorService $aiExerciseGenerator)
    {
        $this->aiExerciseGenerator = $aiExerciseGenerator;
    }

    /**
     * Show AI Exercise Generator view for Teachers and Admins.
     */
    public function index()
    {
        return view('teacher.ai_generator');
    }

    /**
     * AJAX endpoint to generate candidate questions according to real exam standards.
     */
    public function generate(Request $request): JsonResponse
    {
        try {
            $topic = trim((string) $request->input('topic', ''));
            $examStandard = (string) $request->input('exam_standard', 'thpt_qg');
            $mode = (string) $request->input('mode', 'standard');
            $section = $request->input('section');

            // Clean difficulty (accepts "Level B1", "B1", etc.)
            $rawDiff = (string) $request->input('difficulty', 'B1');
            $cleanDiff = trim(str_ireplace(['level', ' '], '', $rawDiff)) ?: 'B1';
            if (!in_array($cleanDiff, ['A1', 'A2', 'B1', 'B2', 'C1', 'Mixed'])) {
                $cleanDiff = 'B1';
            }

            $count = max(1, min(50, (int) $request->input('count', 10)));
            $skill = (string) $request->input('skill', 'all');
            $qType = (string) $request->input('question_type', 'mcq');

            $questions = $this->aiExerciseGenerator->generateQuestions(
                topic: $topic,
                difficulty: $cleanDiff,
                count: $count,
                skill: $skill,
                questionTypes: (!empty($qType) && $qType !== 'all') ? [$qType] : ['mcq'],
                examStandard: $examStandard,
                mode: $mode,
                section: $section
            );

            return response()->json([
                'success' => true,
                'count' => count($questions),
                'exam_standard' => $examStandard,
                'questions' => $questions,
            ]);
        } catch (\Throwable $e) {
            Log::error('AI Generator Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi sinh đề thi: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Save generated questions to the QuestionBank table.
     */
    public function save(Request $request)
    {
        try {
            $questions = $request->input('questions');
            if (empty($questions) || !is_array($questions)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không có câu hỏi nào để lưu.',
                ], 422);
            }

            $savedCount = $this->aiExerciseGenerator->saveToQuestionBank($questions);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'count' => $savedCount,
                    'message' => "Đã lưu thành công {$savedCount} câu hỏi chuẩn hóa (đã lọc trùng) vào Ngân hàng câu hỏi!",
                ]);
            }

            return redirect()->route('admin.questions.index')
                ->with('success', "Đã lưu {$savedCount} câu hỏi chuẩn hóa do AI tạo vào Ngân hàng câu hỏi!");
        } catch (\Throwable $e) {
            Log::error('AI Save Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lưu câu hỏi: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Save generated questions directly as an ExamSet (Bài thi hoàn chỉnh).
     * Students can immediately take this exam in the CBT room at /practice/exam/{key}.
     */
    public function saveExamSet(Request $request): JsonResponse
    {
        try {
            $questions = $request->input('questions');
            if (empty($questions) || !is_array($questions)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không có câu hỏi nào để tạo đề thi.',
                ], 422);
            }

            $user = $request->user();
            $examData = [
                'title' => trim((string) $request->input('title', 'Đề Thi Tiếng Anh Chuẩn Hóa AI')),
                'key' => trim((string) $request->input('key', '')),
                'difficulty' => (string) $request->input('difficulty', 'B1'),
                'duration_minutes' => (int) $request->input('duration_minutes', 60),
                'reward_coins' => (int) $request->input('reward_coins', 30),
                'description' => (string) $request->input('description', ''),
                'is_published' => (bool) $request->input('is_published', true),
                'skill' => (string) $request->input('skill', 'full_mock'),
            ];

            $examSet = $this->aiExerciseGenerator->saveAsExamSet($questions, $examData, $user->id);

            return response()->json([
                'success' => true,
                'exam_id' => $examSet->id,
                'exam_key' => $examSet->key,
                'exam_title' => $examSet->title,
                'question_count' => $examSet->question_count,
                'exam_url' => route('practice.exam', $examSet->key),
                'manage_url' => route('admin.exams.index'),
                'message' => "Đã tạo đề thi thành công! Đề thi đã sẵn sàng trong Phòng thi trực tuyến CBT.",
            ]);
        } catch (\Throwable $e) {
            Log::error('AI Save ExamSet Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lưu đề thi: ' . $e->getMessage(),
            ], 500);
        }
    }
}
