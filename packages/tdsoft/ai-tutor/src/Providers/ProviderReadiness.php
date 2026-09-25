<?php

namespace TDSoft\AiTutor\Providers;

use TDSoft\AiTutor\Contracts\EmbeddingProviderInterface;

final class ProviderReadiness
{
    public function embeddingError(): ?string
    {
        if (! config('ai-tutor.enabled', false)) {
            return 'AI_DISABLED';
        }
        if (config('ai-tutor.billing_mode') !== 'customer_key') {
            return 'BILLING_MODE_NOT_SUPPORTED';
        }

        $provider = config('ai-tutor.provider');
        $class = is_string($provider) ? config('ai-tutor.providers.'.$provider) : null;
        if ($class === MockProvider::class && app()->environment('testing')) {
            return null;
        }
        if (! is_string($class) || ! is_a($class, EmbeddingProviderInterface::class, true)) {
            return 'AI_PROVIDER_NOT_CONFIGURED';
        }
        if (! is_string(config('ai-tutor.embedding_model')) || trim(config('ai-tutor.embedding_model')) === '') {
            return 'AI_PROVIDER_NOT_CONFIGURED';
        }

        $credential = is_string($provider) ? config('ai-tutor.credentials.'.$provider) : null;

        return is_string($credential) && trim($credential) !== '' ? null : 'AI_PROVIDER_NOT_CONFIGURED';
    }
}
