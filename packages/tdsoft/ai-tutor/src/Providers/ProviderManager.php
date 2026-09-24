<?php

namespace TDSoft\AiTutor\Providers;

use TDSoft\AiTutor\Contracts\AiProvider;
use TDSoft\AiTutor\Core\AiException;

final class ProviderManager
{
    public function driver(): AiProvider
    {
        $name = config('ai-tutor.provider');
        $class = config('ai-tutor.providers.'.$name);
        if (! is_string($class) || ! is_a($class, AiProvider::class, true)) {
            throw new AiException('AI_PROVIDER_NOT_CONFIGURED');
        }
        if ($class === MockProvider::class && ! app()->environment('testing')) {
            throw new AiException('AI_PROVIDER_NOT_CONFIGURED');
        }
        return app($class);
    }
}
