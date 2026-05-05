<?php

namespace App\Http\Middleware;

use App\Services\AuthorizationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * بررسی دسترسی به permission.
 * استفاده: middleware('permission:dashboard') یا middleware('permission:pages,products')
 */
class CheckPermission
{
    public function __construct(
        protected AuthorizationService $auth
    ) {}

    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();
        if (!$user) {
            Log::warning('permission.denied.unauthenticated', [
                'route' => $request->route()?->getName(),
                'path' => $request->path(),
                'ip' => $request->ip(),
                'required_permissions' => $permissions,
            ]);
            abort(403, 'ابتدا وارد شوید');
        }

        foreach ($permissions as $perm) {
            if ($this->auth->can($user, trim($perm))) {
                return $next($request);
            }
        }

        Log::warning('permission.denied.insufficient_access', [
            'user_id' => $user->id,
            'route' => $request->route()?->getName(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'required_permissions' => $permissions,
        ]);

        abort(403, 'شما دسترسی به این بخش ندارید.');
    }
}
