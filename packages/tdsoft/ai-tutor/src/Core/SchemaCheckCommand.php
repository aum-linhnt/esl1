<?php

namespace TDSoft\AiTutor\Core;

use Illuminate\Console\Command;

final class SchemaCheckCommand extends Command
{
    protected $signature = 'ai-tutor:schema-check';

    protected $description = 'Read-only AI Tutor installation / schema preflight';

    public function handle(SchemaInspector $inspector): int
    {
        $result = $inspector->inspect();
        $this->line('AI Tutor foundation: '.$result['state']);
        foreach ($result['problems'] as $problem) {
            $this->error($problem);
        }

        return in_array($result['state'], ['fresh', 'installed'], true) ? self::SUCCESS : self::FAILURE;
    }
}
