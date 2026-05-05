<?php

namespace App\Http\Middleware;

use App\Services\AuthorizationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function __construct(
        protected AuthorizationService $auth
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            abort(403, 'دسترسی غیرمجاز.');
        }
        if (!$this->auth->canAccessAdmin($user)) {
            abort(403, 'دسترسی غیرمجاز.');
        }
        return $next($request);
    }
}
