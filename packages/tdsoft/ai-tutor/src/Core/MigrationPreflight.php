<?php

namespace TDSoft\AiTutor\Core;

use Illuminate\Database\Events\MigrationsStarted;
use TDSoft\AiTutor\Licensing\LicenseSchemaInspector;

final class MigrationPreflight
{
    public function __construct(private SchemaInspector $foundation, private LicenseSchemaInspector $license) {}

    public function handle(MigrationsStarted $event): void
    {
        if ($event->method !== 'up') {
            return;
        }
        foreach ([$this->foundation->inspect(), $this->license->inspect()] as $result) {
            if (! in_array($result['state'], ['fresh', 'pending', 'installed'], true)) {
                throw new \RuntimeException('AI Tutor preflight: '.$result['state'].'; '.implode(', ', $result['problems']));
            }
        }
    }
}
