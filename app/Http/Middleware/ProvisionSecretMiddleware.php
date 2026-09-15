<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProvisionSecretMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.customer_provision_secret', '');

        // If no secret is configured, reject all provision calls
        if (empty($secret)) {
            return response()->json(['error' => 'Provisioning not configured'], 403);
        }

        // hash_equals() — a plain !== comparison is timing-unsafe and lets
        // an attacker who can measure response latency recover the shared
        // secret byte-by-byte. $secret (the known value) is the first
        // argument per hash_equals()'s own contract.
        if (! hash_equals($secret, (string) $request->header('X-Provision-Secret', ''))) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return $next($request);
    }
}
