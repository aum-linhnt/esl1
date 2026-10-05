<?php

namespace TDSoft\AiTutor\Providers;

use TDSoft\AiTutor\Core\AiException;

final class CredentialResolver
{
    public function resolve(string $provider): string
    {
        $key = config('ai-tutor.credentials.'.$provider);
        if (! is_string($key) || trim($key) === '') {
            throw new AiException('AI_PROVIDER_NOT_CONFIGURED');
        }

        return $key;
    }
}
