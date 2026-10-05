<?php

namespace TDSoft\AiTutor\Http;

use Closure;
use Illuminate\Http\Request;
use TDSoft\AiTutor\Contracts\CreditAdministrator;

final class RequireCreditAdministrator
{
    public function __construct(private CreditAdministrator $administrator) {}

    public function handle(Request $request, Closure $next): mixed
    {
        abort_unless($this->administrator->actorId() !== null, 403);

        return $next($request);
    }
}
