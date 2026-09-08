<?php

namespace App\QuestionTypes;

use App\Models\QuestionBank;
use Illuminate\Http\Request;

interface QuestionTypeInterface
{
    /**
     * Get the unique question type identifier (e.g. 'mcq', 'fill_blank').
     */
    public function getType(): string;

    /**
     * Human-readable label for this question type.
     */
    public function getLabel(): string;

    /**
     * Specific validation rules when storing/updating this question type.
     */
    public function rules(): array;

    /**
     * Format options, correct_answer, and extra metadata from request for database storage.
     *
     * @param Request $request
     * @return array{options: mixed, correct_answer: string, meta_data: array}
     */
    public function formatForStorage(Request $request): array;

    /**
     * Evaluate if the learner's submitted answer is correct.
     *
     * @param QuestionBank $question
     * @param mixed $userAnswer
     * @return bool
     */
    public function evaluate(QuestionBank $question, mixed $userAnswer): bool;
}
