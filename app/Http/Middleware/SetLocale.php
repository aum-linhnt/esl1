<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Supported locales in the application.
     */
    public const SUPPORTED_LOCALES = ['vi', 'en'];

    /**
     * Handle an incoming request and set the active application locale.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = Session::get('locale', config('app.locale', 'vi'));

        if (!in_array($locale, self::SUPPORTED_LOCALES)) {
            $locale = 'vi';
        }

        App::setLocale($locale);

        return $next($request);
    }
}
