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

        return in_array($result['state'], ['fresh', 'installed'], true)
            && in_array($license['state'], ['pending', 'installed'], true) ? self::SUCCESS : self::FAILURE;
    }
}
