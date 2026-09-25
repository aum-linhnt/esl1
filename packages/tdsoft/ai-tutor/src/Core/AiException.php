<?php

namespace TDSoft\AiTutor\Core;

use RuntimeException;

final class AiException extends RuntimeException
{
    public function __construct(public readonly string $errorCode)
    {
        // Never include provider payloads, URLs, credentials or previous exceptions.
        parent::__construct($errorCode);
    }
}
