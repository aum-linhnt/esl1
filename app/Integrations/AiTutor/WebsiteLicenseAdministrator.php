<?php

namespace App\Integrations\AiTutor;

use Illuminate\Support\Facades\Auth;
use TDSoft\AiTutor\Contracts\LicenseAdministrator;

final class WebsiteLicenseAdministrator implements LicenseAdministrator
{
    public function allows(): bool
    {
        $user = Auth::user();

        return $user && $user->isAdmin() && $user->isActive();
    }

    public function actorId(): ?string
    {
        return $this->allows() ? (string) Auth::id() : null;
    }
}
