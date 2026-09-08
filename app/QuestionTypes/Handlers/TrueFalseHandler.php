<?php

namespace App\QuestionTypes\Handlers;

use App\Models\QuestionBank;
use App\QuestionTypes\QuestionTypeInterface;
use Illuminate\Http\Request;

class TrueFalseHandler implements QuestionTypeInterface
{
    public function getType(): string
    {
        return 'true_false';
    }

    public function getLabel(): string
    {
        return 'True / False / Not Given';
    }

    public function rules(): array
    {
        return [
            'true_false_answer' => 'required|in:True,False,Not Given',
        ];
    }

    public function formatForStorage(Request $request): array
    {
        return [
            'options' => ['True', 'False', 'Not Given'],
            'correct_answer' => $request->input('true_false_answer', 'True'),
            'meta_data' => [],
        ];
    }

    public function evaluate(QuestionBank $question, mixed $userAnswer): bool
    {
        if ($userAnswer === null || $userAnswer === '') {
            return false;
        }

        $userStr = strtolower(trim((string)$userAnswer));
        $corrStr = strtolower(trim((string)$question->correct_answer));

        return $userStr === $corrStr;
    }
}
