<?php

namespace App\Integrations\AiTutor;

use Illuminate\Support\Facades\Auth;
use TDSoft\AiTutor\Contracts\KnowledgeAdministrator;

final class WebsiteKnowledgeAdministrator implements KnowledgeAdministrator
{
    public function allows(): bool
    {
        $user = Auth::user();

        return $user && $user->isAdmin() && $user->isActive();
    }
}
