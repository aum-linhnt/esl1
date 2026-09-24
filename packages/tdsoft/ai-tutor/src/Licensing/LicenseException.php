<?php

namespace TDSoft\AiTutor\Licensing;

final class LicenseException extends \RuntimeException
{
    public function __construct(public readonly string $errorCode)
    {
        parent::__construct($errorCode);
    }
}
