<?php

namespace TDSoft\AiTutor\Writing;

use TDSoft\AiTutor\Core\AiException;

final class WritingRequirements
{
    public static function validate(mixed $items, string $original): array
    {
        if (! is_array($items) || ! array_is_list($items) || count($items) > 6) {
            throw new AiException('AI_ASSESSMENT_RESULT_INVALID');
        }
        $result = [];
        foreach ($items as $item) {
            if (! is_array($item) || ! in_array($item['status'] ?? null, ['met', 'partial', 'not_met', 'not_available'], true)) {
                throw new AiException('AI_ASSESSMENT_RESULT_INVALID');
            }
            foreach (['requirement', 'comment'] as $field) {
                if (! is_string($item[$field] ?? null) || trim($item[$field]) === '' || strlen($item[$field]) > 2000) {
                    throw new AiException('AI_ASSESSMENT_RESULT_INVALID');
                }
            }
            $quotes = $item['evidence'] ?? null;
            if (! is_array($quotes) || ! array_is_list($quotes) || count($quotes) > 5) {
                throw new AiException('AI_ASSESSMENT_RESULT_INVALID');
            }
            foreach ($quotes as $quote) {
                if (! is_string($quote) || trim($quote) === '' || strlen($quote) > 2000) {
                    throw new AiException('AI_ASSESSMENT_RESULT_INVALID');
                }
            }
            $verified = array_values(array_unique(array_filter($quotes, fn ($quote) => str_contains($original, $quote))));
            $status = $item['status'];
            $comment = trim($item['comment']);
            if (in_array($status, ['met', 'partial'], true) && $verified === []) {
                $status = 'not_available';
                $comment = 'Chưa có dẫn chứng khớp bài viết để xác minh yêu cầu này.';
            }
            $result[] = ['requirement' => trim($item['requirement']), 'status' => $status,
                'comment' => $comment, 'evidence' => $status === 'not_available' ? [] : $verified];
        }

        return $result;
    }
}
