<?php

namespace TDSoft\AiTutor\Contracts;

use Closure;

interface BackgroundActor
{
    /** Reauthenticate a server-persisted job owner; never accept an HTTP actor ID. */
    public function run(string $actorId, Closure $work): mixed;
}
