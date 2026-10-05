<?php

namespace TDSoft\AiTutor\Licensing;

final readonly class LicenseStatus
{
    public function __construct(
        public string $state,
        public ?string $errorCode = null,
        public ?array $document = null,
        public ?string $offlineUntil = null,
        public bool $warning = false,
    ) {}

    public function usable(): bool
    {
        return in_array($this->state, ['active', 'refresh_due', 'grace'], true);
    }
}
