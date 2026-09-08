<?php

namespace App\QuestionTypes\Handlers;

use App\Models\QuestionBank;
use App\QuestionTypes\QuestionTypeInterface;
use Illuminate\Http\Request;

class MultipleSelectHandler implements QuestionTypeInterface
{
    public function getType(): string
    {
        return 'multiple_select';
    }

    public function getLabel(): string
    {
        return 'Trắc nghiệm chọn nhiều đáp án (Multiple Select)';
    }

    public function rules(): array
    {
        return [
            'options' => 'required|string',
            'correct_answers_multi' => 'required|string',
        ];
    }

    public function formatForStorage(Request $request): array
    {
        $options = null;
        if ($request->filled('options')) {
            $raw = $request->input('options');
            $options = is_array($raw)
                ? array_values(array_filter(array_map('trim', $raw)))
                : array_values(array_filter(array_map('trim', explode("\n", (string) $raw))));
        }

        $correctAnswer = '[]';
        if ($request->filled('correct_answers_multi')) {
            $rawMulti = $request->input('correct_answers_multi');
            $multiAnswers = is_array($rawMulti)
                ? array_values(array_filter(array_map('trim', $rawMulti)))
                : array_values(array_filter(array_map('trim', explode("\n", (string) $rawMulti))));
            $correctAnswer = json_encode($multiAnswers, JSON_UNESCAPED_UNICODE);
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

        $userArr = is_array($userAnswer) ? $userAnswer : (json_decode((string)$userAnswer, true) ?: [(string)$userAnswer]);
        $correctArr = is_array($question->correct_answer) ? $question->correct_answer : (json_decode((string)$question->correct_answer, true) ?: [(string)$question->correct_answer]);

        $uClean = array_values(array_filter(array_map(fn($v) => strtolower(trim((string)$v)), $userArr)));
        $cClean = array_values(array_filter(array_map(fn($v) => strtolower(trim((string)$v)), $correctArr)));

        sort($uClean);
        sort($cClean);

        return $uClean === $cClean;
    }
}
