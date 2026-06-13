<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    private const SUPPORTED = ['fa', 'ar', 'en'];
    private const DEFAULT   = 'fa';
    private const SESSION_KEY = 'site_locale';

    public function handle(Request $request, Closure $next)
    {
        $locale = session(self::SESSION_KEY, self::DEFAULT);
        if (!in_array($locale, self::SUPPORTED)) {
            $locale = self::DEFAULT;
        }
        app()->setLocale($locale);
        $request->attributes->set('locale', $locale);

        return $next($request);
    }
}
