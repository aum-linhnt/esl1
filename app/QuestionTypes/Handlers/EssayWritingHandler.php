<?php

namespace App\QuestionTypes\Handlers;

use App\Models\QuestionBank;
use App\QuestionTypes\QuestionTypeInterface;
use Illuminate\Http\Request;

class EssayWritingHandler implements QuestionTypeInterface
{
    public function getType(): string
    {
        return 'essay_writing';
    }

    public function getLabel(): string
    {
        return 'Viết luận / Soạn thảo bài viết (Essay Writing)';
    }

    public function rules(): array
    {
        return [
            'min_words' => 'nullable|integer|min:10|max:1000',
            'task_type' => 'nullable|string|max:50',
        ];
    }

    public function formatForStorage(Request $request): array
    {
        $metaData = [];
        if ($request->filled('min_words')) {
            $metaData['min_words'] = (int) $request->input('min_words');
        }
        if ($request->filled('task_type')) {
            $metaData['task_type'] = $request->input('task_type');
        }

        return [
            'options' => null,
            'correct_answer' => 'Sample Answer / Rubric Evaluation',
            'meta_data' => $metaData,
        ];
    }

    public function evaluate(QuestionBank $question, mixed $userAnswer): bool
    {
        if (empty($userAnswer)) {
            return false;
        }

        $minWords = $question->meta_data['min_words'] ?? 20;
        $words = str_word_count(strip_tags((string)$userAnswer));

        return $words >= $minWords;
    }
}
