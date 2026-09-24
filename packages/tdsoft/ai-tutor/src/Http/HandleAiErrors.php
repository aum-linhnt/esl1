<?php

namespace TDSoft\AiTutor\Http;

use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use TDSoft\AiTutor\Core\AiException;

final class HandleAiErrors
{
    public function handle(Request $request, Closure $next): mixed
    {
        try {
            return $next($request);
        } catch (AiException $error) {
            $code = $error->errorCode;
            $status = str_contains($code, 'NOT_FOUND') ? 404
                : ((str_contains($code, 'FORBIDDEN') || str_starts_with($code, 'LICENSE_') || $code === 'AI_DISABLED') ? 403 : 409);

            return new JsonResponse(['error' => ['code' => $code]], $status);
        } catch (QueryException) {
            // SQL exception messages can contain interpolated learner content.
            return new JsonResponse(['error' => ['code' => 'AI_STORAGE_UNAVAILABLE']], 503);
        }
    }
}
