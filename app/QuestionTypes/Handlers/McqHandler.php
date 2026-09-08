<?php

namespace App\QuestionTypes\Handlers;

use App\Models\QuestionBank;
use App\QuestionTypes\QuestionTypeInterface;
use Illuminate\Http\Request;

class McqHandler implements QuestionTypeInterface
{
    public function getType(): string
    {
        return 'mcq';
    }

    public function getLabel(): string
    {
        return 'Trắc nghiệm 1 đáp án (Single Choice)';
    }

    public function rules(): array
    {
        return [
            'options' => 'nullable|string',
            'correct_answer' => 'required|string|max:255',
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

        $correctAnswer = trim((string) $request->input('correct_answer', ''));


        return [
            'options' => $options,
            'correct_answer' => $correctAnswer,
            'meta_data' => [],
        ];
    }

    public function evaluate(QuestionBank $question, mixed $userAnswer): bool
    {
        if ($userAnswer === null || $userAnswer === '') {
            return false;
        }

        $u = strtolower(trim((string)$userAnswer));
        $c = strtolower(trim((string)$question->correct_answer));

        if ($u === $c) {
            return true;
        }

        // Match stripped option contents (e.g. 'A. Solar energy' vs 'Solar energy')
        $uCore = preg_replace('/^[a-z]\.\s*/i', '', $u);
        $cCore = preg_replace('/^[a-z]\.\s*/i', '', $c);
        if ($uCore !== '' && $uCore === $cCore) {
            return true;
        }

        // Direct letter matching (e.g. 'a' vs 'A. Option 1')
        if (strlen($u) === 1 && str_starts_with($c, $u . '.')) {
            return true;
        }
        if (strlen($c) === 1 && str_starts_with($u, $c . '.')) {
            return true;
        }

        // Option index letter matching against options array
        if (is_array($question->options)) {
            foreach ($question->options as $idx => $opt) {
                $optClean = strtolower(trim((string)$opt));
                $letter = strtolower(chr(65 + $idx));
                if (($u === $optClean || $u === $letter) && ($c === $optClean || $c === $letter || str_starts_with($optClean, $c))) {
                    return true;
                }
            }
        }

        return false;
    }
}
