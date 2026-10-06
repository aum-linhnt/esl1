<?php

namespace App\Listeners;

use App\Services\Learning\SkillSnapshots;
use TDSoft\AiTutor\Events\WritingAssessmentCompleted;

final class RecordWritingSkill
{
    public function __construct(private SkillSnapshots $snapshots) {}

    public function handle(WritingAssessmentCompleted $event): void
    {
        $this->snapshots->writing($event->assessmentId);
    }
}
