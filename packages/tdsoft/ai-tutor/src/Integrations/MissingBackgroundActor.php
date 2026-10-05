<?php

namespace TDSoft\AiTutor\Integrations;

use Closure;
use TDSoft\AiTutor\Contracts\BackgroundActor;
use TDSoft\AiTutor\Core\AiException;

final class MissingBackgroundActor implements BackgroundActor
{
    public function run(string $actorId, Closure $work): mixed
    {
        throw new AiException('AI_BACKGROUND_ACTOR_NOT_CONFIGURED');
    }
}
