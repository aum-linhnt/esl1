<?php

namespace TDSoft\AiTutor\Integrations;

use TDSoft\AiTutor\Contracts\ActorResolver;
use TDSoft\AiTutor\Core\{AiException, LearnerIdentity};

final class MissingActorResolver implements ActorResolver
{
    public function resolve(): LearnerIdentity
    {
        throw new AiException('AI_ADAPTER_NOT_CONFIGURED');
    }
}
