<?php

namespace TDSoft\AiTutor\Billing;

final readonly class GatewayRequest
{
    public const VERSION = 1;

    public function __construct(
        public string $installationId,
        public string $requestId,
        public string $idempotencyKey,
        public string $feature,
        public array $payload,
    ) {}
}
