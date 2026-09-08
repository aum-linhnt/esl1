<?php

namespace App\QuestionTypes\Handlers;

use App\Models\QuestionBank;
use App\QuestionTypes\QuestionTypeInterface;
use Illuminate\Http\Request;

class MatchingHandler implements QuestionTypeInterface
{
    public function getType(): string
    {
        return 'matching';
    }

    public function getLabel(): string
    {
        return 'Nối cặp từ - nghĩa (Matching Pairs)';
    }

    public function rules(): array
    {
        return [
            'matching_left' => 'required|string',
            'matching_right' => 'required|string',
        ];
    }

    public function formatForStorage(Request $request): array
    {
        $leftItems = array_values(array_filter(array_map('trim', explode("\n", $request->input('matching_left', '')))));
        $rightItems = array_values(array_filter(array_map('trim', explode("\n", $request->input('matching_right', '')))));
        
        $leftMap = [];
        $pairs = [];
        foreach ($leftItems as $idx => $leftVal) {
            $rightVal = $rightItems[$idx] ?? '';
            $leftMap[$leftVal] = $leftVal;
            $pairs[$leftVal] = $rightVal;
        }

        return [
            'options' => [
                'left' => $leftMap,
                'right' => $rightItems,
            ],
            'correct_answer' => json_encode($pairs, JSON_UNESCAPED_UNICODE),
            'meta_data' => [],
        ];
    }

    public function evaluate(QuestionBank $question, mixed $userAnswer): bool
    {
        if (empty($userAnswer)) {
            return false;
        }

        $userPairs = is_array($userAnswer) ? $userAnswer : (json_decode((string)$userAnswer, true) ?: []);
        $corrPairs = is_array($question->correct_answer) ? $question->correct_answer : (json_decode((string)$question->correct_answer, true) ?: []);

        if (empty($userPairs) || empty($corrPairs)) {
            return false;
        }

        foreach ($corrPairs as $k => $v) {
            if (!isset($userPairs[$k]) || strtolower(trim((string)$userPairs[$k])) !== strtolower(trim((string)$v))) {
                return false;
            }
        }

        return true;
    }
}
