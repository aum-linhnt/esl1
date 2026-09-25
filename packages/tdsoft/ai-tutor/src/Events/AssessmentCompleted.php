<?php

namespace TDSoft\AiTutor\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

abstract class AssessmentCompleted implements ShouldDispatchAfterCommit
{
    public const PAYLOAD_VERSION = 1;

    public function __construct(
        public readonly string $eventId,
        public readonly string $assessmentId,
        public readonly string $actorId,
        public readonly string $contextId,
        public readonly array $referenceScores,
        public readonly string $rubricVersion,
    ) {}
}
