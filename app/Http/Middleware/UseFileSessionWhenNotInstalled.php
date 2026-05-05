<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UseFileSessionWhenNotInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        // During installation DB credentials may be invalid or tables may not exist yet.
        // Force file session storage to keep installer routes accessible.
        if (! file_exists(storage_path('installed.lock'))) {
            config()->set('session.driver', 'file');
        }

        return $next($request);
    }
}
