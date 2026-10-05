<?php

namespace App\Integrations\AiTutor;

use Closure;
use Illuminate\Support\Facades\Auth;
use TDSoft\AiTutor\Contracts\BackgroundActor;
use TDSoft\AiTutor\Core\AiException;

final class WebsiteBackgroundActor implements BackgroundActor
{
    public function run(string $actorId, Closure $work): mixed
    {
        $guard = Auth::guard('web');
        $previous = $guard->user();
        try {
            // onceUsingId does not write a login session; normal actor/admin checks still run.
            if (! $guard->onceUsingId($actorId)) {
                throw new AiException('AI_ACTOR_INVALID');
            }

            return $work();
        } finally {
            $guard->forgetUser();
            if ($previous) {
                $guard->setUser($previous);
            }
        }
    }
}
