<?php

namespace App\Services\Assessment;

use App\Models\QuestionBank;
use Illuminate\Database\Eloquent\Collection;

class QuestionVersioningService
{
    /**
     * Update question with automatic version tracking when content changes.
     */
    public function updateWithVersioning(
        QuestionBank $question,
        array $attributes,
        bool $createVersion = true
    ): QuestionBank {
        $options = $attributes['options'] ?? $question->options;
        $difficulty = $attributes['difficulty'] ?? $question->difficulty;
        $questionText = $attributes['question_text'] ?? $question->question_text;
        $correctAnswer = $attributes['correct_answer'] ?? $question->correct_answer;

        $hasContentChanged = ($question->question_text !== $questionText)
            || ($question->correct_answer !== $correctAnswer)
            || ($question->options != $options)
            || ($question->difficulty !== $difficulty);

        if ($createVersion && $hasContentChanged) {
            // Archive current state into a historical version record
            $archive = $question->replicate();
            $archive->parent_id = $question->parent_id ?: $question->id;
            $archive->version = $question->version ?: 1;
            $archive->deleted_at = now();
            $archive->save();

            $newVersion = ($question->version ?: 1) + 1;
        } else {
            $newVersion = $question->version ?: 1;
        }

        $attributes['version'] = $newVersion;
        $question->update($attributes);

        return $question;
    }

    /**
     * Get version history of a question.
     */
    public function getVersionHistory(int $questionId): Collection
    {
        $question = QuestionBank::withTrashed()->findOrFail($questionId);
        $rootId = $question->parent_id ?: $question->id;

        return QuestionBank::withTrashed()
            ->where(function ($q) use ($rootId) {
                $q->where('id', $rootId)
                  ->orWhere('parent_id', $rootId);
            })
            ->orderBy('version', 'desc')
            ->get([
                'id', 'version', 'parent_id', 'question_text',
                'options', 'correct_answer', 'explanation',
                'deleted_at', 'created_at', 'updated_at'
            ]);
    }

    /**
     * Synchronize updated passage content across all questions in the cluster.
     */
    public function syncPassageCluster(string $oldPassageTitle, string $newTitle, string $newContent): int
    {
        $clusterQuestions = QuestionBank::where('meta_data->passage_title', $oldPassageTitle)->get();
        $updatedCount = 0;

        foreach ($clusterQuestions as $cq) {
            $meta = $cq->meta_data ?? [];
            $meta['passage_title'] = $newTitle;
            $meta['passage_content'] = $newContent;
            $meta['part_name'] = "Phần " . ($meta['part'] ?? 1) . ": " . $newTitle;
            $cq->update(['meta_data' => $meta]);
            $updatedCount++;
        }

        return $updatedCount;
    }
}
