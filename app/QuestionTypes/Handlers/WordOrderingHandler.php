<?php

namespace App\QuestionTypes\Handlers;

use App\Models\QuestionBank;
use App\QuestionTypes\QuestionTypeInterface;
use Illuminate\Http\Request;

class WordOrderingHandler implements QuestionTypeInterface
{
    public function getType(): string
    {
        return 'word_ordering';
    }

    public function getLabel(): string
    {
        return 'Sắp xếp từ thành câu (Word Ordering)';
    }

    public function rules(): array
    {
        return [
            'correct_answer' => 'required|string',
            'word_tiles' => 'nullable|string',
        ];
    }

    public function formatForStorage(Request $request): array
    {
        $correctAnswer = trim((string)$request->input('correct_answer', ''));
        
        if ($request->filled('word_tiles')) {
            $options = array_values(array_filter(array_map('trim', explode(',', $request->input('word_tiles')))));
        } else {
            $options = array_values(array_filter(explode(' ', $correctAnswer)));
        }

        return [
            'options' => $options,
            'correct_answer' => $correctAnswer,
            'meta_data' => [],
        ];
    }

    public function evaluate(QuestionBank $question, mixed $userAnswer): bool
    {
        if (empty($userAnswer)) {
            return false;
        }

        $userStr = is_array($userAnswer) ? implode(' ', $userAnswer) : (string)$userAnswer;

        $cleanUser = preg_replace('/\s+/', ' ', strtolower(trim($userStr)));
        $cleanCorrect = preg_replace('/\s+/', ' ', strtolower(trim((string)$question->correct_answer)));

        return $cleanUser === $cleanCorrect;
    }
}
