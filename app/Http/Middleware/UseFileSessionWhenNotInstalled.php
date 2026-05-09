<?php

namespace App\Http\Middleware;

use App\Support\Installation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UseFileSessionWhenNotInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        // During installation DB credentials may be invalid or tables may not exist yet.
        // Force file session storage to keep installer routes accessible.
        if (! Installation::isLockPresent()) {
            config()->set('session.driver', 'file');
        }

        return $next($request);
    }
}
