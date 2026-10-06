<?php

namespace TDSoft\AiTutor\Writing;

use TDSoft\AiTutor\Core\AiException;

final class WritingStructure
{
    public static function components(string $task): array
    {
        return match ($task) {
            'ielts_task_1' => ['introduction', 'overview', 'key_features', 'comparisons', 'organisation'],
            'ielts_task_2' => ['introduction', 'position', 'arguments', 'examples', 'conclusion', 'organisation'],
            default => ['main_idea', 'supporting_details', 'linking', 'organisation'],
        };
    }

    public static function validate(mixed $items, string $original, string $task): array
    {
        if (! is_array($items) || ! array_is_list($items) || count($items) > 6) {
            throw new AiException('AI_ASSESSMENT_RESULT_INVALID');
        }
        $result = [];
        foreach ($items as $item) {
            $component = is_array($item) ? ($item['component'] ?? null) : null;
            if (! in_array($component, self::components($task), true) || isset($result[$component])
                || ! is_string($item['next_step'] ?? null) || trim($item['next_step']) === '' || strlen($item['next_step']) > 2000) {
                throw new AiException('AI_ASSESSMENT_RESULT_INVALID');
            }
            // Reuse the same exact-quote rules as task requirement analysis.
            $verified = WritingRequirements::validate([[
                'requirement' => $component, 'status' => $item['status'] ?? null,
                'comment' => $item['comment'] ?? null, 'evidence' => $item['evidence'] ?? null,
            ]], $original)[0];
            $result[$component] = ['component' => $component, 'status' => $verified['status'],
                'comment' => $verified['comment'], 'evidence' => $verified['evidence'],
                'next_step' => $verified['status'] === 'not_available' ? '' : trim($item['next_step'])];
        }

        return array_values($result);
    }
}
