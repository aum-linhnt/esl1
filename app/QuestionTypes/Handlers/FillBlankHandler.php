<?php

namespace App\QuestionTypes\Handlers;

use App\Models\QuestionBank;
use App\QuestionTypes\QuestionTypeInterface;
use Illuminate\Http\Request;

class FillBlankHandler implements QuestionTypeInterface
{
    public function getType(): string
    {
        return 'fill_blank';
    }

    public function getLabel(): string
    {
        return 'Điền từ vào chỗ trống (Fill in the Blank)';
    }

    public function rules(): array
    {
        return [
            'correct_answer' => 'required|string|max:255',
        ];
    }

    public function formatForStorage(Request $request): array
    {
        return [
            'options' => null,
            'correct_answer' => trim((string)$request->input('correct_answer', '')),
            'meta_data' => [],
        ];
    }

    public function evaluate(QuestionBank $question, mixed $userAnswer): bool
    {
        if ($userAnswer === null || $userAnswer === '') {
            return false;
        }

        $userStr = strtolower(trim((string)$userAnswer));
        $correct = $question->correct_answer;

        $correctOptions = is_array($correct) ? $correct : (json_decode((string)$correct, true) ?: [(string)$correct]);

        foreach ($correctOptions as $valid) {
            if ($userStr === strtolower(trim((string)$valid))) {
                return true;
            }
        }

        return false;
    }
}
