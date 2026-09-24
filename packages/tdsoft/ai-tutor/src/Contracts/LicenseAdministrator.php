<?php

namespace TDSoft\AiTutor\Contracts;

interface LicenseAdministrator
{
    public function allows(): bool;

    public function actorId(): ?string;
}
