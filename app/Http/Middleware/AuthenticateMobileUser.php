<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMobileUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->extractToken($request);

        if (!$token) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user = User::where('api_token', hash('sha256', $token))->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid token.'], 401);
        }

        auth()->setUser($user);
        $request->setUserResolver(fn() => $user);

        return $next($request);
    }

    private function extractToken(Request $request): ?string
    {
        $header = $request->header('Authorization', '');
        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }
        return $request->query('api_token');
    }
}
