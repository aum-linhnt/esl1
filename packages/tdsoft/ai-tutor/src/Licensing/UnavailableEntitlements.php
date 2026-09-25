<?php

namespace TDSoft\AiTutor\Licensing;

use TDSoft\AiTutor\Contracts\Entitlements;

final class UnavailableEntitlements implements Entitlements
{
    public function allows(string $module): bool
    {
        // Phase 2 replaces this with offline signed-license verification.
        return false;
    }
}
