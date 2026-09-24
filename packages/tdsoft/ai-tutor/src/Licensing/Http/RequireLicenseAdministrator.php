<?php

namespace TDSoft\AiTutor\Licensing\Http;

use Closure;
use Illuminate\Http\Request;
use TDSoft\AiTutor\Contracts\LicenseAdministrator;

final class RequireLicenseAdministrator
{
    public function __construct(private LicenseAdministrator $administrator) {}

    public function handle(Request $request, Closure $next): mixed
    {
        abort_unless($this->administrator->allows(), 403);

        return $next($request);
    }
}
