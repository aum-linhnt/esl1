<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreQuestionRequest;
use App\Models\Course;
use App\Models\QuestionBank;
use App\Services\Storage\FileStorageService;
use Illuminate\Http\Request;


class CourseQuestionController extends Controller
{
    /**
     * Legacy: List all global questions.
     */
    public function questions()
    {
        $questions = QuestionBank::orderBy('created_at', 'desc')->paginate(20);
        return view('admin.questions.index', ['questions' => $questions]);
    }

    /**
     * Legacy: Store a global question.
     */
    public function storeQuestion(StoreQuestionRequest $request)
    {
        $qType = $request->question_type;
        $options = null;
        $correctAnswer = $request->correct_answer ?? '';
        $skill = $request->skill === 'vocab' ? 'vocabulary' : $request->skill;

        $this->processQuestionOptions($request, $qType, $options, $correctAnswer);

        $audioUrl = $request->input('audio_url');
        if ($request->hasFile('audio_file')) {
            $audioRecord = app(FileStorageService::class)->store($request->file('audio_file'), 'questions/audio');
            $audioUrl = $audioRecord->getUrl();
        }

        $metaData = null;
        if ($request->hasFile('image_file')) {
            $imageRecord = app(FileStorageService::class)->store($request->file('image_file'), 'questions/images');
            $metaData = ['image_url' => $imageRecord->getUrl()];
        }

        QuestionBank::create([
            'skill' => $skill,
            'difficulty' => $request->difficulty,
            'question_type' => $qType,
            'question_text' => $request->question_text,
            'options' => $options,
            'audio_url' => $audioUrl,
            'correct_answer' => $correctAnswer,
            'explanation' => $request->explanation,
            'meta_data' => $metaData,
        ]);

        return redirect()->route('admin.questions.index')
            ->with('success', "Đã thêm câu hỏi dạng '{$qType}' mới vào ngân hàng thành công!");
    }

    /**
     * Legacy: Delete a global question.
     */
    public function destroyQuestion($questionId)
    {
        QuestionBank::findOrFail($questionId)->delete();
        return redirect()->route('admin.questions.index')
            ->with('success', 'Đã xóa câu hỏi.');
    }

    /**
     * Store a question into the course question bank.
     */
    public function storeCourseQuestion(StoreQuestionRequest $request, $courseId)
    {
        $course = Course::findOrFail($courseId);

        $qType = $request->question_type;
        $options = null;
        $correctAnswer = $request->correct_answer ?? '';
        $skill = $request->skill === 'vocab' ? 'vocabulary' : $request->skill;

        $this->processCourseQuestionOptions($request, $qType, $options, $correctAnswer);

        $audioUrl = $request->input('audio_url');
        if ($request->hasFile('audio_file')) {
            $audioRecord = app(FileStorageService::class)->store($request->file('audio_file'), 'questions/audio');
            $audioUrl = $audioRecord->getUrl();
        }

        $metaData = null;
        if ($request->hasFile('image_file')) {
            $imageRecord = app(FileStorageService::class)->store($request->file('image_file'), 'questions/images');
            $metaData = ['image_url' => $imageRecord->getUrl()];
        }

        $question = QuestionBank::create([
            'course_id' => $course->id,
            'skill' => $skill,
            'difficulty' => $request->difficulty,
            'question_type' => $qType,
            'question_text' => $request->question_text,
            'options' => $options,
            'audio_url' => $audioUrl,
            'correct_answer' => $correctAnswer,
            'explanation' => $request->explanation,
            'meta_data' => $metaData,
        ]);


        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Đã thêm câu hỏi dạng '{$qType}' vào ngân hàng khóa học!",
                'question' => $question,
            ]);
        }

        return back()->with('success', "Đã thêm câu hỏi dạng '{$qType}' vào ngân hàng khóa học!");
    }

    /**
     * Update a question in the course question bank.
     */
    public function updateCourseQuestion(Request $request, $courseId, $questionId)
    {
        $question = QuestionBank::where('course_id', $courseId)->findOrFail($questionId);

        $request->validate([
            'skill' => 'sometimes|in:reading,grammar,vocab,vocabulary,listening',
            'difficulty' => 'sometimes|in:A1,A2,B1,B2,Mixed',
            'question_type' => 'sometimes|string',
            'question_text' => 'sometimes|string',
            'explanation' => 'nullable|string',
        ]);

        $qType = $request->input('question_type', $question->question_type);
        $options = $question->options;
        $correctAnswer = $request->has('correct_answer') ? $request->correct_answer : $question->correct_answer;
        $audioUrl = $request->has('audio_url') ? $request->audio_url : $question->audio_url;
        if ($request->hasFile('audio_file')) {
            $audioRecord = app(FileStorageService::class)->store($request->file('audio_file'), 'questions/audio');
            $audioUrl = $audioRecord->getUrl();
        }

        $metaData = $question->meta_data ?? [];
        if ($request->hasFile('image_file')) {
            $imageRecord = app(FileStorageService::class)->store($request->file('image_file'), 'questions/images');
            $metaData['image_url'] = $imageRecord->getUrl();
        }

        $this->processUpdateQuestionOptions($request, $qType, $options, $correctAnswer);

        $data = [
            'skill' => $request->input('skill', $question->skill),
            'difficulty' => $request->input('difficulty', $question->difficulty),
            'question_type' => $qType,
            'question_text' => $request->input('question_text', $question->question_text),
            'options' => $options,
            'audio_url' => $audioUrl,
            'correct_answer' => $correctAnswer,
            'explanation' => $request->input('explanation', $question->explanation),
            'meta_data' => !empty($metaData) ? $metaData : null,
        ];

        $question->update($data);


        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã cập nhật câu hỏi!',
                'question' => $question->fresh(),
            ]);
        }

        return back()->with('success', 'Đã cập nhật câu hỏi!');
    }

    /**
     * Delete a question from the course question bank.
     */
    public function deleteCourseQuestion($courseId, $questionId)
    {
        $question = QuestionBank::where('course_id', $courseId)->findOrFail($questionId);
        $question->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã xóa câu hỏi khỏi ngân hàng khóa học!',
            ]);
        }

        return back()->with('success', 'Đã xóa câu hỏi khỏi ngân hàng khóa học!');
    }

    /**
     * Import questions from the global question bank into this course.
     */
    public function importQuestionsFromGlobal(Request $request, $courseId)
    {
        $course = Course::findOrFail($courseId);
        $questionIds = $request->input('question_ids', []);

        if (empty($questionIds) || !is_array($questionIds)) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Vui lòng chọn ít nhất một câu hỏi để nhập.'], 422);
            }
            return back()->with('error', 'Vui lòng chọn ít nhất một câu hỏi để nhập.');
        }

        $globalQuestions = QuestionBank::whereIn('id', $questionIds)
            ->whereNull('course_id')
            ->get();

        $importedCount = 0;
        $importedQuestions = [];

        foreach ($globalQuestions as $q) {
            $newQ = QuestionBank::create([
                'course_id' => $course->id,
                'skill' => $q->skill,
                'difficulty' => $q->difficulty,
                'question_type' => $q->question_type,
                'question_text' => $q->question_text,
                'options' => $q->options,
                'correct_answer' => $q->correct_answer,
                'explanation' => $q->explanation,
                'audio_url' => $q->audio_url,
                'meta_data' => $q->meta_data,
            ]);
            $importedQuestions[] = $newQ;
            $importedCount++;
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Đã nhập thành công {$importedCount} câu hỏi vào khóa học!",
                'imported_count' => $importedCount,
                'questions' => $importedQuestions,
            ]);
        }

        return back()->with('success', "Đã nhập thành công {$importedCount} câu hỏi vào khóa học!");
    }

    // ─── PRIVATE HELPERS ───

    /**
     * Process question options for legacy global question store.
     */
    private function processQuestionOptions(Request $request, string $qType, &$options, &$correctAnswer): void
    {
        switch ($qType) {
            case 'mcq':
            case 'audio_listening':
                if ($request->options) {
                    $options = array_values(array_filter(array_map('trim', explode("\n", $request->options))));
                }
                break;

            case 'multiple_select':
                if ($request->options) {
                    $options = array_values(array_filter(array_map('trim', explode("\n", $request->options))));
                }
                if ($request->correct_answers_multi) {
                    $correctAnswer = json_encode(array_values(array_filter(array_map('trim', explode("\n", $request->correct_answers_multi)))));
                }
                break;

            case 'word_ordering':
            case 'drag_drop':
                if ($request->word_tiles) {
                    $options = array_values(array_filter(array_map('trim', explode(',', $request->word_tiles))));
                } else {
                    $options = array_values(array_filter(explode(' ', $correctAnswer)));
                }
                break;

            case 'matching':
                $leftItems = array_values(array_filter(array_map('trim', explode("\n", $request->matching_left ?? ''))));
                $rightItems = array_values(array_filter(array_map('trim', explode("\n", $request->matching_right ?? ''))));
                
                $leftMap = [];
                $pairs = [];
                foreach ($leftItems as $idx => $leftVal) {
                    $rightVal = $rightItems[$idx] ?? '';
                    $leftMap[$leftVal] = $leftVal;
                    $pairs[$leftVal] = $rightVal;
                }
                $options = [
                    'left' => $leftMap,
                    'right' => $rightItems,
                ];
                $correctAnswer = json_encode($pairs);
                break;

            case 'true_false':
                $options = ['True', 'False', 'Not Given'];
                $correctAnswer = $request->input('true_false_answer', 'True');
                break;
        }
    }

    /**
     * Process question options for course-specific question store.
     */
    private function processCourseQuestionOptions(Request $request, string $qType, &$options, &$correctAnswer): void
    {
        switch ($qType) {
            case 'mcq':
            case 'audio_listening':
                if ($request->options) {
                    $options = is_array($request->options)
                        ? $request->options
                        : array_values(array_filter(array_map('trim', preg_split('/[\r\n]+/', $request->options))));
                }
                if (is_numeric($correctAnswer) && isset($options[(int) $correctAnswer])) {
                    $correctAnswer = $options[(int) $correctAnswer];
                }
                break;

            case 'multiple_select':
                if ($request->options) {
                    $options = is_array($request->options)
                        ? $request->options
                        : array_values(array_filter(array_map('trim', preg_split('/[\r\n]+/', $request->options))));
                }
                if ($request->correct_answers_multi) {
                    $multi = is_array($request->correct_answers_multi)
                        ? $request->correct_answers_multi
                        : array_values(array_filter(array_map('trim', preg_split('/[\r\n]+/', $request->correct_answers_multi))));
                    $correctAnswer = json_encode($multi);
                }
                break;

            case 'word_ordering':
            case 'drag_drop':
                if ($request->word_tiles) {
                    $options = array_values(array_filter(array_map('trim', explode(',', $request->word_tiles))));
                } else {
                    $options = array_values(array_filter(explode(' ', $correctAnswer)));
                }
                break;

            case 'matching':
                $leftItems = is_array($request->matching_left)
                    ? $request->matching_left
                    : array_values(array_filter(array_map('trim', preg_split('/[\r\n]+/', $request->matching_left ?? ''))));
                $rightItems = is_array($request->matching_right)
                    ? $request->matching_right
                    : array_values(array_filter(array_map('trim', preg_split('/[\r\n]+/', $request->matching_right ?? ''))));
                
                $leftMap = [];
                $pairs = [];
                foreach ($leftItems as $idx => $leftVal) {
                    $rightVal = $rightItems[$idx] ?? '';
                    $leftMap[$leftVal] = $leftVal;
                    $pairs[$leftVal] = $rightVal;
                }
                $options = [
                    'left' => $leftMap,
                    'right' => $rightItems,
                ];
                $correctAnswer = json_encode($pairs);
                break;

            case 'true_false':
                $options = ['True', 'False', 'Not Given'];
                $correctAnswer = $request->input('true_false_answer', $request->input('correct_answer', 'True'));
                break;
        }
    }

    /**
     * Process question options for course question update.
     */
    private function processUpdateQuestionOptions(Request $request, string $qType, &$options, &$correctAnswer): void
    {
        switch ($qType) {
            case 'mcq':
            case 'audio_listening':
                if ($request->has('options')) {
                    $options = is_array($request->options)
                        ? $request->options
                        : array_values(array_filter(array_map('trim', preg_split('/[\r\n]+/', $request->options))));
                }
                if (is_numeric($correctAnswer) && isset($options[(int) $correctAnswer])) {
                    $correctAnswer = $options[(int) $correctAnswer];
                }
                break;

            case 'multiple_select':
                if ($request->has('options')) {
                    $options = is_array($request->options)
                        ? $request->options
                        : array_values(array_filter(array_map('trim', preg_split('/[\r\n]+/', $request->options))));
                }
                if ($request->has('correct_answers_multi')) {
                    $multi = is_array($request->correct_answers_multi)
                        ? $request->correct_answers_multi
                        : array_values(array_filter(array_map('trim', preg_split('/[\r\n]+/', $request->correct_answers_multi))));
                    $correctAnswer = json_encode($multi);
                }
                break;

            case 'word_ordering':
            case 'drag_drop':
                if ($request->has('word_tiles')) {
                    $options = array_values(array_filter(array_map('trim', explode(',', $request->word_tiles))));
                } elseif ($request->has('correct_answer')) {
                    $options = array_values(array_filter(explode(' ', $correctAnswer)));
                }
                break;

            case 'matching':
                if ($request->has('matching_left') || $request->has('matching_right')) {
                    $leftItems = is_array($request->matching_left)
                        ? $request->matching_left
                        : array_values(array_filter(array_map('trim', preg_split('/[\r\n]+/', $request->matching_left ?? ''))));
                    $rightItems = is_array($request->matching_right)
                        ? $request->matching_right
                        : array_values(array_filter(array_map('trim', preg_split('/[\r\n]+/', $request->matching_right ?? ''))));
                    
                    $leftMap = [];
                    $pairs = [];
                    foreach ($leftItems as $idx => $leftVal) {
                        $rightVal = $rightItems[$idx] ?? '';
                        $leftMap[$leftVal] = $leftVal;
                        $pairs[$leftVal] = $rightVal;
                    }
                    $options = [
                        'left' => $leftMap,
                        'right' => $rightItems,
                    ];
                    $correctAnswer = json_encode($pairs);
                }
                break;

            case 'true_false':
                $options = ['True', 'False', 'Not Given'];
                if ($request->has('true_false_answer')) {
                    $correctAnswer = $request->true_false_answer;
                }
                break;
        }
    }
}
