<?php

namespace TDSoft\AiTutor\Core;

use Illuminate\Database\Events\MigrationsStarted;
use TDSoft\AiTutor\Assessment\PhaseFourSchema;
use TDSoft\AiTutor\Billing\CreditAdminSchema;
use TDSoft\AiTutor\Knowledge\PhaseThreeSchema;
use TDSoft\AiTutor\Knowledge\SyncSchema;
use TDSoft\AiTutor\Licensing\LicenseSchemaInspector;
use TDSoft\AiTutor\Writing\WritingSchema;

final class MigrationPreflight
{
    public function __construct(private SchemaInspector $foundation, private LicenseSchemaInspector $license) {}

    public function handle(MigrationsStarted $event): void
    {
        if ($event->method !== 'up') {
            return;
        }
        foreach ([$this->foundation->inspect(), $this->license->inspect(), app(PhaseThreeSchema::class)->inspect(), app(SyncSchema::class)->inspect(), app(CreditAdminSchema::class)->inspect(), app(PhaseFourSchema::class)->inspect(), app(WritingSchema::class)->inspect()] as $result) {
            if (! in_array($result['state'], ['fresh', 'pending', 'installed'], true)) {
                throw new \RuntimeException('AI Tutor preflight: '.$result['state'].'; '.implode(', ', $result['problems']));
            }
        }
    }
}
