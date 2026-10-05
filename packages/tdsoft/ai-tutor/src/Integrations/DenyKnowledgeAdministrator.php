<?php

namespace TDSoft\AiTutor\Integrations;

use TDSoft\AiTutor\Contracts\KnowledgeAdministrator;

final class DenyKnowledgeAdministrator implements KnowledgeAdministrator
{
    public function allows(): bool
    {
        return false;
    }
}
