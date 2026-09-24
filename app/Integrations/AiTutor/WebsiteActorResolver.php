<?php

namespace App\Integrations\AiTutor;

use Illuminate\Support\Facades\Auth;
use TDSoft\AiTutor\Contracts\ActorResolver;
use TDSoft\AiTutor\Core\{AiException, LearnerIdentity};

final class WebsiteActorResolver implements ActorResolver
{
    public function resolve(): LearnerIdentity
    {
        $user = Auth::user();
        if (! $user || $user->isBlocked() || $user->isTrialExpired()) {
            throw new AiException('AI_ACTOR_INVALID');
        }
        return new LearnerIdentity((string) $user->getAuthIdentifier(), (string) $user->current_level, [(string) $user->role]);
    }
}
