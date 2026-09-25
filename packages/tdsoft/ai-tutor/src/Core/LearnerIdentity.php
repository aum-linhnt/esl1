<?php

namespace TDSoft\AiTutor\Core;

final readonly class LearnerIdentity
{
    public function __construct(public string $id, public string $level = '', public array $roles = [])
    {
        if ($id === '' || strlen($id) > 191) {
            throw new AiException('AI_ACTOR_INVALID');
        }
    }
}
