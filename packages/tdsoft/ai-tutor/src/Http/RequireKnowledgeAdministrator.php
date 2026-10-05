<?php

namespace TDSoft\AiTutor\Http;

use Closure;
use Illuminate\Http\Request;
use TDSoft\AiTutor\Contracts\KnowledgeAdministrator;

final class RequireKnowledgeAdministrator
{
    public function __construct(private KnowledgeAdministrator $administrator) {}

    public function handle(Request $request, Closure $next): mixed
    {
        abort_unless($this->administrator->allows(), 403);

        return $next($request);
    }
}
