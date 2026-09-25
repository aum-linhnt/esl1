<?php

namespace TDSoft\AiTutor\Licensing;

use TDSoft\AiTutor\Contracts\LicenseAdministrator;

final class DenyLicenseAdministrator implements LicenseAdministrator
{
    public function allows(): bool
    {
        return false;
    }

    public function actorId(): ?string
    {
        return null;
    }
}
