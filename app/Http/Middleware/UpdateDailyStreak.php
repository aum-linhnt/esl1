<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;

class UpdateDailyStreak
{
    /**
     * Automatically update the user's daily activity/login streak whenever they access the site.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            // Check if streak needs updating for today
            $lastActive = $user->last_active_date ? Carbon::parse($user->last_active_date)->startOfDay() : null;
            $today = Carbon::today()->startOfDay();

            if (!$lastActive || !$lastActive->isSameDay($today)) {
                $user->recordDailyStudy();
            }
        }

        return $next($request);
    }
}
