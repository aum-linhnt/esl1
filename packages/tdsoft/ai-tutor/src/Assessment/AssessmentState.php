<?php

namespace TDSoft\AiTutor\Assessment;

use TDSoft\AiTutor\Core\AiException;

final class AssessmentState
{
    private const TRANSITIONS = [
        'draft' => ['audio_ready', 'queued'],
        'audio_ready' => ['queued'],
        'queued' => ['processing', 'failed'],
        'processing' => ['completed', 'failed', 'reconciliation_required'],
        'reconciliation_required' => ['completed', 'failed'],
        'completed' => [],
        'failed' => [],
    ];

    public static function assertTransition(string $from, string $to): void
    {
        if (! in_array($to, self::TRANSITIONS[$from] ?? [], true)) {
            throw new AiException('AI_ASSESSMENT_STATE_INVALID');
        }
    }
}
