<?php

namespace TDSoft\AiTutor\Contracts;

interface Entitlements
{
    public function allows(string $module): bool;
}
