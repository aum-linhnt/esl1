<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restrict a route group to one or more user roles.
 *
 * Source of truth for roles is the `users.role` column (admin | teacher | student),
 * the same field used by User::isAdmin()/isTeacher()/isStudent() across the app.
 *
 * Usage: ->middleware('role:admin') or ->middleware('role:admin,teacher')
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401)
                : redirect()->guest(route('login'));
        }

        if (!in_array($user->role, $roles, true)) {
            abort(403, 'Bạn không có quyền truy cập khu vực này.');
        }

        return $next($request);
    }
}
