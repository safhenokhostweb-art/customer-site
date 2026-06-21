<?php
namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces customer site lifecycle state.
 *
 * - ACTIVE / GRACE_PERIOD: full access
 * - SUSPENDED: public visitors see suspended page; authenticated admins still access the admin panel
 * - ARCHIVED / DELETED: all public access blocked
 *
 * Status is pushed from saas-platform into the local `settings` table
 * on every lifecycle transition (site_lifecycle_status key).
 */
class CheckSiteLifecycle
{
    private const ADMIN_PATHS = [
        '/admin',
        '/login',
        '/logout',
        '/api',
        '/site-suspended',
        // C04 — allow self-service restore even when suspended
        '/account/lifecycle',
        '/account/subscriptions/plans',
        '/account/subscriptions/subscribe',
        '/subscription/pay',
        '/subscription/callback',
        // C01 — direct lifecycle payment link (admin sends to customer)
        '/lifecycle-pay',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $status = Setting::get('site_lifecycle_status', 'active');

        if ($status === 'active' || $status === 'grace_period') {
            return $next($request);
        }

        // Admin paths always accessible (login, admin panel, invoices, API)
        if ($this->isAdminPath($request)) {
            return $next($request);
        }

        // Authenticated admins can still access admin panel paths
        if ($request->user()?->is_admin && $this->isAdminPath($request)) {
            return $next($request);
        }

        // All other public routes → suspended/archived page
        $siteName = Setting::get('site_name', 'این سایت');

        return response()->view('front.suspended', [
            'siteName'  => $siteName,
            'status'    => $status,
        ], 503);
    }

    private function isAdminPath(Request $request): bool
    {
        $path = '/' . ltrim($request->getPathInfo(), '/');

        foreach (self::ADMIN_PATHS as $adminPath) {
            if (str_starts_with($path, $adminPath)) {
                return true;
            }
        }

        return false;
    }
}
