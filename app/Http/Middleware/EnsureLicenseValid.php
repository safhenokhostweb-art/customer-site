<?php

namespace App\Http\Middleware;

use App\Models\License;
use App\Services\LicenseActivationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * اطمینان از معتبر بودن لایسنس.
 * هر چند دقیقه یک‌بار با پلتفرم re-validate می‌شود تا در صورت ابطال لایسنس از پلتفرم، سایت مشتری ماژول‌ها را مخفی کند.
 * برای سایت مالک (owner_site) این چک نادیده گرفته می‌شود.
 *
 * Phase 5 / P0-3:
 * لیست route name های مجاز به‌صورت صریح (allow-list) شمارش شده تا prefix-match فضای حمله ایجاد نکند.
 * هر route جدیدی که باید قبل از فعال‌سازی لایسنس قابل دسترسی باشد، باید عمداً به این لیست اضافه شود.
 */
class EnsureLicenseValid
{
    /**
     * Route name های مجاز پیش از validation لایسنس.
     * صرفاً صفحات لایسنس، نصب و ورود/خروج که برای ثبت کلید و راه‌اندازی اولیه ضروری‌اند.
     */
    private const PUBLIC_ROUTES = [
        'login',
        'logout',
        'admin.license.index',
        'admin.license.update',
        'admin.license.check-update',
        'admin.license.refresh-config',
        'admin.license.refresh-config.get',
        'admin.license.test-config',
        'admin.license.refresh-modules',
        // Profile page must be accessible before license is confirmed so that
        // ForcePasswordChange middleware can redirect there without creating a
        // circular redirect (license blocks profile → force-change blocks license).
        'admin.profile.index',
        'admin.profile.update',
        'install.welcome',
        'install.database',
        'install.store-database',
        'install.admin',
        'install.store-admin',
        'install.settings',
        'install.store-settings',
        'install.done',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (\App\Helpers\SiteHelper::isOwnerSite()) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        if ($routeName !== null && in_array($routeName, self::PUBLIC_ROUTES, true)) {
            return $next($request);
        }

        $license = License::current();
        if (!$license || empty($license->license_key)) {
            Log::warning('license.denied.missing', [
                'route' => $request->route()?->getName(),
                'path' => $request->path(),
                'ip' => $request->ip(),
                'host' => $request->getHost(),
            ]);
            if ($request->expectsJson()) {
                return response()->json(['error' => 'لایسنس ثبت نشده است.'], 403);
            }
            return redirect()->route('admin.license.index')
                ->with('error', 'لایسنس ثبت نشده است. ابتدا کلید را وارد کرده و فعال‌سازی کنید.');
        }

        $intervalMinutes = (int) config('license.revalidate_interval_minutes', 5);
        $shouldRevalidate = $intervalMinutes > 0
            && (\Illuminate\Support\Facades\Schema::hasColumn('licenses', 'last_validated_at'))
            && ($license->last_validated_at === null || $license->last_validated_at->copy()->addMinutes($intervalMinutes)->isPast());

        if ($shouldRevalidate) {
            $activation = app(LicenseActivationService::class);
            $result = $activation->syncWithPlatform($license, $request->getHost());
            $license->refresh();
            if (!$result['valid']) {
                Log::warning('license.denied.revalidate_failed', [
                    'route' => $request->route()?->getName(),
                    'path' => $request->path(),
                    'ip' => $request->ip(),
                    'host' => $request->getHost(),
                    'message' => $result['message'] ?? null,
                ]);
                if ($request->expectsJson()) {
                    return response()->json(['error' => $result['message'] ?? 'لایسنس در پلتفرم معتبر نیست.'], 403);
                }
                return redirect()->route('admin.license.index')
                    ->with('error', $result['message'] ?? 'لایسنس در پلتفرم معتبر نیست (ممکن است ابطال شده باشد).');
            }
        }

        if (!$license->isValid($request->getHost())) {
            Log::warning('license.denied.invalid_state', [
                'route' => $request->route()?->getName(),
                'path' => $request->path(),
                'ip' => $request->ip(),
                'host' => $request->getHost(),
                'status' => $license->statusLabel(),
            ]);
            if ($request->expectsJson()) {
                return response()->json(['error' => 'لایسنس معتبر نیست: ' . $license->statusLabel()], 403);
            }
            return redirect()->route('admin.license.index')
                ->with('error', 'لایسنس معتبر نیست: ' . $license->statusLabel());
        }

        return $next($request);
    }
}
