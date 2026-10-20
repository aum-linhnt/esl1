<?php

namespace TDSoft\AiTutor\Core;

use Illuminate\Console\Command;
use TDSoft\AiTutor\Assessment\PhaseFourSchema;
use TDSoft\AiTutor\Billing\CreditAdminSchema;
use TDSoft\AiTutor\Knowledge\PhaseThreeSchema;
use TDSoft\AiTutor\Knowledge\SyncSchema;
use TDSoft\AiTutor\Licensing\LicenseSchemaInspector;
use TDSoft\AiTutor\Writing\WritingSchema;

final class SchemaCheckCommand extends Command
{
    protected $signature = 'ai-tutor:schema-check';

    protected $description = 'Read-only AI Tutor installation / schema preflight';

    public function handle(SchemaInspector $inspector, LicenseSchemaInspector $licenseInspector): int
    {
        $result = $inspector->inspect();
        $this->line('AI Tutor foundation: '.$result['state']);
        foreach ($result['problems'] as $problem) {
            $this->error($problem);
        }

        $license = $licenseInspector->inspect();
        $this->line('AI Tutor license: '.$license['state']);
        foreach ($license['problems'] as $problem) {
            $this->error($problem);
        }

        $phaseThree = app(PhaseThreeSchema::class)->inspect();
        $this->line('AI Tutor knowledge/conversations: '.$phaseThree['state']);
        foreach ($phaseThree['problems'] as $problem) {
            $this->error($problem);
        }

        $sync = app(SyncSchema::class)->inspect();
        $this->line('AI Tutor knowledge sync: '.$sync['state']);
        foreach ($sync['problems'] as $problem) {
            $this->error($problem);
        }

        $audit = app(CreditAdminSchema::class)->inspect();
        $this->line('AI Tutor credit admin: '.$audit['state']);
        foreach ($audit['problems'] as $problem) {
            $this->error($problem);
        }
        $assessment = app(PhaseFourSchema::class)->inspect();
        $this->line('AI Tutor English assessments: '.$assessment['state']);
        foreach ($assessment['problems'] as $problem) {
            $this->error($problem);
        }

        $writing = app(WritingSchema::class)->inspect();
        $this->line('AI Tutor Writing execution: '.$writing['state']);
        foreach ($writing['problems'] as $problem) {
            $this->error($problem);
        }

        return in_array($writing['state'], ['pending', 'installed'], true)
            && in_array($assessment['state'], ['pending', 'installed'], true)
            && in_array($audit['state'], ['pending', 'installed'], true)
            && in_array($sync['state'], ['pending', 'installed'], true)
            && in_array($phaseThree['state'], ['pending', 'installed'], true)
            && in_array($result['state'], ['fresh', 'installed'], true)
            && in_array($license['state'], ['pending', 'installed'], true) ? self::SUCCESS : self::FAILURE;
    }
}
