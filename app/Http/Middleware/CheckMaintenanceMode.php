<?php

namespace App\Http\Middleware;

use App\Services\AuthorizationService;
use App\Services\MaintenanceModeService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * تفکیک دسترسی در حالت تعمیرات:
 * - بازدیدکنندگان عادی: دریافت صفحه تعمیرات Canvas (بدون هدر و فوتر) با وضعیت ۵۰۳
 * - ادمین لاگین‌شده مدیریت: دسترسی کامل به سایت اصلی همراه با نوار اطلاع‌رسانی بالای صفحه
 */
class CheckMaintenanceMode
{
    private const BYPASS_PREFIXES = [
        '/admin',
        '/login',
        '/logout',
        '/api',
        '/up',
        '/storage',
        '/assets',
        '/build',
        '/vendor',
        '/site-suspended',
        '/lifecycle-pay',
    ];

    public function __construct(
        private readonly MaintenanceModeService $maintenance,
        private readonly AuthorizationService $auth,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->maintenance->isEnabled()) {
            return $next($request);
        }

        if ($this->shouldBypassPath($request)) {
            return $next($request);
        }

        $user = $request->user();
        if ($user && $this->auth->canAccessAdmin($user)) {
            $response = $next($request);
            return $this->injectAdminBanner($response);
        }

        return $this->maintenance->renderStandaloneResponse();
    }

    private function shouldBypassPath(Request $request): bool
    {
        $path = '/' . ltrim($request->getPathInfo(), '/');
        foreach (self::BYPASS_PREFIXES as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return true;
            }
        }
        return false;
    }

    private function injectAdminBanner(Response $response): Response
    {
        $contentType = (string) $response->headers->get('Content-Type', '');
        if (!str_contains($contentType, 'text/html')) {
            return $response;
        }
        $html = $response->getContent();
        if (!is_string($html) || !str_contains($html, '<body')) {
            return $response;
        }

        $banner = '<div id="maintenance-admin-banner" dir="rtl" style="position:sticky;top:0;z-index:99999;background:#f59e0b;color:#111827;font-family:Vazirmatn,Tahoma,sans-serif;font-size:13px;font-weight:600;padding:8px 16px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,0.12);">'
            . 'حالت تعمیرات برای بازدیدکنندگان فعال است — شما به عنوان ادمین سایت اصلی را مشاهده می‌کنید. '
            . '<a href="' . e(route('admin.settings.index')) . '" style="text-decoration:underline;margin-right:8px;color:#111827;">مدیریت تنظیمات</a>'
            . '</div>';

        $updated = preg_replace('/(<body[^>]*>)/i', '$1' . $banner, $html, 1);
        if (is_string($updated)) {
            $response->setContent($updated);
        }

        return $response;
    }
}
