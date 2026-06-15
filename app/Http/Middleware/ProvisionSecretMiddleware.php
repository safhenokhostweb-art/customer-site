<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProvisionSecretMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('app.provision_secret', env('CUSTOMER_PROVISION_SECRET', ''));

        // If no secret is configured, reject all provision calls
        if (empty($secret)) {
            return response()->json(['error' => 'Provisioning not configured'], 403);
        }

        if ($request->header('X-Provision-Secret') !== $secret) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return $next($request);
    }
}
