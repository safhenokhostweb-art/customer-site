<?php

namespace App\Http\Middleware;

use App\Helpers\SiteHelper;
use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * جلوگیری از دسترسی به مسیرهای ماژول غیرفعال.
 * استفاده: middleware('module:shop') یا middleware('module:blog')
 */
class EnsureModuleEnabled
{
    public function __construct(
        protected TenantContext $tenantContext
    ) {}

    public function handle(Request $request, Closure $next, string $moduleSlug): Response
    {
        if (SiteHelper::isOwnerSite()) {
            return $next($request);
        }

        if (SiteHelper::moduleEnabled($moduleSlug)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'module_disabled',
                'message' => 'این ماژول برای این سایت فعال نیست.',
            ], 403);
        }

        abort(403, 'این ماژول برای این سایت فعال نیست.');
    }
}
