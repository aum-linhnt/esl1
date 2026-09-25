<?php

namespace TDSoft\AiTutor\Core;

use Illuminate\Console\Command;
use TDSoft\AiTutor\Licensing\LicenseSchemaInspector;

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

        $phaseThree = app(\TDSoft\AiTutor\Knowledge\PhaseThreeSchema::class)->inspect();
        $this->line('AI Tutor knowledge/conversations: '.$phaseThree['state']);
        foreach ($phaseThree['problems'] as $problem) {
            $this->error($problem);
        }

        $sync = app(\TDSoft\AiTutor\Knowledge\SyncSchema::class)->inspect();
        $this->line('AI Tutor knowledge sync: '.$sync['state']);
        foreach ($sync['problems'] as $problem) {
            $this->error($problem);
        }

        $audit = app(\TDSoft\AiTutor\Billing\CreditAdminSchema::class)->inspect();
        $this->line('AI Tutor credit admin: '.$audit['state']);
        foreach ($audit['problems'] as $problem) {
            $this->error($problem);
        }
        return in_array($audit['state'], ['pending', 'installed'], true)
            && in_array($sync['state'], ['pending', 'installed'], true)
            && in_array($phaseThree['state'], ['pending', 'installed'], true)
            && in_array($result['state'], ['fresh', 'installed'], true)
            && in_array($license['state'], ['pending', 'installed'], true) ? self::SUCCESS : self::FAILURE;
    }
}
