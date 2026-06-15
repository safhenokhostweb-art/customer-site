<?php

namespace App\Http\Middleware;

use App\Models\License;
use App\Services\TenantContext;
use App\Support\Installation;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

/**
 * تنظیم TenantContext از License فعلی.
 * در customer-site هر نصب یک tenant است؛ شناسایی از domain و License.
 */
class SetTenantContext
{
    private const LICENSE_CACHE_KEY = 'license.current';
    private const LICENSE_CACHE_TTL = 300;
    private const SCHEMA_CACHE_KEY  = 'schema.has_table.licenses';

    public function __construct(
        protected TenantContext $tenantContext
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->tenantContext->clear();

        if (Installation::isLockPresent() && $this->licensesTableExists()) {
            $license = $this->currentLicense();
            if ($license && $license->isValid($request->getHost())) {
                $this->tenantContext->set($license);
            }
        }

        $response = $next($request);

        $this->tenantContext->clear();

        return $response;
    }

    private function licensesTableExists(): bool
    {
        return Cache::remember(self::SCHEMA_CACHE_KEY, 3600, fn() => Schema::hasTable('licenses'));
    }

    private function currentLicense(): ?License
    {
        if (Cache::has(self::LICENSE_CACHE_KEY)) {
            $hit = Cache::get(self::LICENSE_CACHE_KEY);
            return $hit instanceof License ? $hit : null;
        }

        $license = License::current();
        Cache::put(self::LICENSE_CACHE_KEY, $license ?? false, self::LICENSE_CACHE_TTL);

        return $license;
    }

    public static function forgetLicenseCache(): void
    {
        Cache::forget(self::LICENSE_CACHE_KEY);
    }
}
