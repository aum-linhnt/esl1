<?php

namespace TDSoft\AiTutor\Writing;

use TDSoft\AiTutor\Core\AiException;

final class WritingIssues
{
    /** Invalid spans remain visible as feedback but never authorize a replacement. */
    public static function validate(mixed $issues, string $original): array
    {
        if (! is_array($issues) || ! array_is_list($issues) || count($issues) > 100) {
            throw new AiException('AI_ASSESSMENT_RESULT_INVALID');
        }
        $encoded = mb_convert_encoding($original, 'UTF-16LE', 'UTF-8');
        $valid = [];
        foreach ($issues as $issue) {
            if (! is_array($issue) || ! in_array($issue['category'] ?? null, ['grammar', 'vocabulary', 'coherence', 'task_response', 'style'], true)) {
                throw new AiException('AI_ASSESSMENT_RESULT_INVALID');
            }
            foreach (['original', 'replacement', 'explanation'] as $field) {
                if (! is_string($issue[$field] ?? null) || strlen($issue[$field]) > 5000 || ! mb_check_encoding($issue[$field], 'UTF-8')) {
                    throw new AiException('AI_ASSESSMENT_RESULT_INVALID');
                }
            }
            $start = $issue['start_utf16'] ?? null;
            $end = $issue['end_utf16'] ?? null;
            $apply = is_int($start) && is_int($end) && $start >= 0 && $end > $start && $end <= strlen($encoded) / 2
                && substr($encoded, $start * 2, ($end - $start) * 2) === mb_convert_encoding($issue['original'], 'UTF-16LE', 'UTF-8');
            $valid[] = ['category' => $issue['category'], 'original' => $issue['original'], 'replacement' => $issue['replacement'],
                'explanation' => $issue['explanation'], 'start_utf16' => $apply ? $start : null,
                'end_utf16' => $apply ? $end : null, 'applicable' => $apply];
        }

        return $valid;
    }
}
