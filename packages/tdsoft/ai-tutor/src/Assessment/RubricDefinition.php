<?php

namespace TDSoft\AiTutor\Assessment;

use TDSoft\AiTutor\Core\AiException;

final readonly class RubricDefinition
{
    public function __construct(public array $criteria)
    {
        if ($criteria === [] || array_is_list($criteria) || count($criteria) > 20) {
            throw new AiException('AI_RUBRIC_INVALID');
        }
        $weight = 0;
        foreach ($criteria as $key => $criterion) {
            if (! is_string($key) || ! preg_match('/^[a-z][a-z_]{0,47}$/D', $key)
                || ! is_array($criterion)
                || ! is_int($criterion['weight'] ?? null) || $criterion['weight'] < 1 || $criterion['weight'] > 100
                || ! is_string($criterion['description'] ?? null) || trim($criterion['description']) === ''
                || strlen($criterion['description']) > 2000
                || ! in_array($criterion['evidence_type'] ?? null, ['text', 'timing', 'acoustic'], true)) {
                throw new AiException('AI_RUBRIC_INVALID');
            }
            $weight += $criterion['weight'];
        }
        if ($weight !== 100) {
            throw new AiException('AI_RUBRIC_INVALID');
        }
    }
}
