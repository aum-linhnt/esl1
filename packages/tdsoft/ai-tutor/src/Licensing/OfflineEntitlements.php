<?php

namespace TDSoft\AiTutor\Licensing;

use TDSoft\AiTutor\Contracts\Entitlements;

final class OfflineEntitlements implements Entitlements
{
    public function __construct(private LicenseReader $reader) {}

    public function allows(string $module): bool
    {
        try {
            $mode = new LicenseMode;
            if ($mode->value() === 'source_owned') {
                return in_array($module, $mode->modules(), true);
            }
        } catch (LicenseException) {
            return false;
        }
        $status = $this->reader->status();

        return $status->usable() && in_array($module, $status->document['modules'] ?? [], true);
    }
}
