<?php

namespace App\Http\Middleware;

use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * تنظیم TenantContext از License فعلی.
 * در customer-site هر نصب یک tenant است؛ شناسایی از domain و License.
 */
class SetTenantContext
{
    public function __construct(
        protected TenantContext $tenantContext
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->tenantContext->clear();

        if (file_exists(storage_path('installed.lock')) && \Illuminate\Support\Facades\Schema::hasTable('licenses')) {
            $license = \App\Models\License::current();
            if ($license && $license->isValid($request->getHost())) {
                $this->tenantContext->set($license);
            }
        }

        $response = $next($request);

        $this->tenantContext->clear();

        return $response;
    }
}
