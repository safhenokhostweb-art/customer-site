<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && !empty($user->force_password_change)) {
            $allowedRoutes = ['admin.profile.index', 'admin.profile.update', 'logout'];

            if (!$request->routeIs(...$allowedRoutes)) {
                return redirect()->route('admin.profile.index')
                    ->with('force_password_change_notice', 'برای ادامه استفاده از پنل، ابتدا رمز عبور خود را تغییر دهید.');
            }
        }

        return $next($request);
    }
}
