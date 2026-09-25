<?php

namespace TDSoft\AiTutor\Contracts;

interface CreditAdministrator
{
    public function actorId(): ?string;

    /** Safe display DTOs: id, name. Must enforce administrative permissions. */
    public function recipients(string $search): array;

    public function recipient(string $id): array;

    /** Resolve a safe display name for an administrator already authorized by this adapter. */
    public function administratorName(string $id): string;
}
