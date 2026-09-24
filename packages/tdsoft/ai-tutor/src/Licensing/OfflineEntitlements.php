<?php

namespace TDSoft\AiTutor\Licensing;

use TDSoft\AiTutor\Contracts\Entitlements;

final class OfflineEntitlements implements Entitlements
{
    public function __construct(private LicenseReader $reader) {}

    public function allows(string $module): bool
    {
        $status = $this->reader->status();

        return $status->usable() && in_array($module, $status->document['modules'] ?? [], true);
    }
}
