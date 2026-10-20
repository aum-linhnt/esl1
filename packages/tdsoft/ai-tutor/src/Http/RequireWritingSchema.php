<?php

namespace TDSoft\AiTutor\Http;

use Closure;
use TDSoft\AiTutor\Assessment\PhaseFourSchema;
use TDSoft\AiTutor\Core\AiException;
use TDSoft\AiTutor\Writing\WritingSchema;

final class RequireWritingSchema
{
    public function handle($request, Closure $next): mixed
    {
        if ((new PhaseFourSchema)->inspect()['state'] !== 'installed' || (new WritingSchema)->inspect()['state'] !== 'installed') {
            throw new AiException('AI_WRITING_SCHEMA_REQUIRED');
        }

        return $next($request);
    }
}
