<?php

namespace TDSoft\AiTutor\Integrations;

use TDSoft\AiTutor\Contracts\CreditAdministrator;
use TDSoft\AiTutor\Core\AiException;

final class DenyCreditAdministrator implements CreditAdministrator
{
    public function actorId(): ?string
    {
        return null;
    }

    public function recipients(string $search): array
    {
        throw new AiException('AI_CREDIT_ADMIN_FORBIDDEN');
    }

    public function recipient(string $id): array
    {
        throw new AiException('AI_CREDIT_ADMIN_FORBIDDEN');
    }
}
