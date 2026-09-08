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
     * AJAX endpoint to generate candidate questions.
     */
    public function generate(Request $request): JsonResponse
    {
        try {
            $topic = trim((string) $request->input('topic', ''));
            if (empty($topic)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vui lòng nhập chủ đề bài thi (Topic).',
                ], 422);
            }

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
                questionTypes: (!empty($qType) && $qType !== 'all') ? [$qType] : ['mcq']
            );

            return response()->json([
                'success' => true,
                'count' => count($questions),
                'questions' => $questions,
            ]);
        } catch (\Throwable $e) {
            Log::error('AI Generator Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi sinh câu hỏi: ' . $e->getMessage(),
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
}
