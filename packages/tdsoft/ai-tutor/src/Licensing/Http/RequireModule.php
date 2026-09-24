<?php

namespace TDSoft\AiTutor\Licensing\Http;

use Closure;
use Illuminate\Http\Request;
use TDSoft\AiTutor\Contracts\Entitlements;
use TDSoft\AiTutor\Licensing\InstallationIdentity;

final class RequireModule
{
    public function __construct(private Entitlements $entitlements, private InstallationIdentity $identity) {}

    public function handle(Request $request, Closure $next, string $module): mixed
    {
        abort_unless(strtolower(rtrim($request->getHost(), '.')) === $this->identity->domain(), 403, 'LICENSE_DOMAIN_MISMATCH');
        abort_unless($this->entitlements->allows($module), 403, 'LICENSE_MODULE_NOT_ALLOWED');

        return $next($request);
    }
}
