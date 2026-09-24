<?php

namespace TDSoft\AiTutor\Core;

final readonly class AiResponse
{
    public array $usage;

    public function __construct(
        public string $content,
        public string $provider,
        public string $model,
        array $usage = [],
        public ?string $providerRequestId = null,
        public ?string $remoteRequestId = null,
    ) {
        $normalized = [];
        foreach (['input_tokens', 'output_tokens', 'cached_tokens', 'audio_seconds', 'image_count', 'document_pages'] as $field) {
            $value = $usage[$field] ?? 0;
            if (! is_int($value) || $value < 0 || $value > 1000000000) {
                throw new AiException('AI_USAGE_INVALID');
            }
            $normalized[$field] = $value;
        }
        if ($normalized['cached_tokens'] > $normalized['input_tokens']) {
            throw new AiException('AI_USAGE_INVALID');
        }
        $this->usage = $normalized;
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    public static function fromArray(array $data): self
    {
        return new self(...$data);
    }
}
