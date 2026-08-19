<?php

namespace App\Helpers;

use App\Models\License;
use App\Models\Setting;

class SiteHelper
{
    /**
     * Phase 5 / P0-7:
     * نگاشت دوطرفهٔ alias بین کلیدهای قدیمی و جدید ماژول‌ها.
     * هر کلید به آرایه‌ای از نام‌های هم‌معنا متصل است؛ اگر هر یک از آن‌ها در لیست
     * ماژول‌های لایسنس وجود داشته باشد، ماژول فعال شمرده می‌شود.
     *
     * این نگاشت فقط access را گسترش می‌دهد و هیچ‌گاه entitlement موجود را
     * نمی‌گیرد (سازگار با Global Rules §4 — Immutable Customer Entitlement Protection).
     */
    private const MODULE_ALIASES = [
        'whatsapp'  => ['messaging'],
        'messaging' => ['whatsapp'],
    ];

    public static function theme(): string
    {
        return Setting::get('theme', config('site.theme', 'default'));
    }

    /**
     * فعال بودن ماژول فروشگاه: اول از لایسنس/پلتفرم، در غیر صورت از config و .env.
     */
    public static function shopEnabled(): bool
    {
        return self::moduleEnabled('shop');
    }

    /**
     * فعال بودن ماژول وبلاگ: اول از لایسنس/پلتفرم، در غیر صورت از config و .env.
     */
    public static function blogEnabled(): bool
    {
        return self::moduleEnabled('blog');
    }

    public static function coursesEnabled(): bool
    {
        return self::moduleEnabled('courses');
    }

    /**
     * فعال بودن ماژول با کلید مشخص: اول از لایسنس/پلتفرم، در غیر صورت از config و .env.
     */
    public static function moduleEnabled(string $key): bool
    {
        return self::moduleEnabledFromLicenseOrConfig($key);
    }

    /**
     * منبع حقیقت برای ماژول‌ها پلتفرم است؛ در صورت لایسنس معتبر از modules لایسنس استفاده می‌شود، وگرنه fallback به config + .env.
     * ماژول‌های blog و shop فقط از لایسنس فعال می‌شوند؛ بدون لایسنس معتبر همیشه غیرفعال‌اند.
     * سایر ماژول‌ها (اعلان، فرم و ...) در صورت نبود لایسنس از .env استفاده می‌کنند.
     */
    protected static function moduleEnabledFromLicenseOrConfig(string $key): bool
    {
        // Customer installations rely on the platform-issued cached
        // projection. Local module configuration is not an entitlement source.
        if (! self::isOwnerSite()) {
            return self::moduleEnabledFromProjection($key);
        }

        return self::moduleEnabledFromProjection($key) || Module::enabled($key);
    }

    /**
     * An explicit platform rejection clears platform_valid and modules in the
     * sync service, so a known revocation always overrides an older cache.
     */
    protected static function moduleEnabledFromProjection(string $key): bool
    {
        // blog و shop: فقط از لایسنس؛ بدون لایسنس معتبر همیشه غیرفعال
        if (in_array($key, ['blog', 'shop'], true)) {
            $license = License::current();
            if (!$license || !$license->isValid()) {
                return false;
            }
            $modules = $license->modules ?? [];
            return is_array($modules) && in_array($key, $modules, true);
        }

        // سایر ماژول‌ها: لایسنس اول، سپس fallback به .env
        $license = License::current();
        if ($license && $license->isValid()) {
            $modules = $license->modules ?? [];
            if (is_array($modules)) {
                if (in_array($key, $modules, true)) {
                    return true;
                }
                // alias دوطرفه: اگر هر هم‌معنایی از کلید درخواست‌شده در لایسنس باشد، فعال است.
                foreach (self::MODULE_ALIASES[$key] ?? [] as $alias) {
                    if (in_array($alias, $modules, true)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * آیا این نصب به‌عنوان سایت مالک (سایت فروش شما) تنظیم شده است؟
     * در این حالت لایسنس اختیاری است و ماژول‌ها از .env خوانده می‌شوند.
     */
    public static function isOwnerSite(): bool
    {
        return (bool) config('site.owner_site', false);
    }

    public static function siteName(): string
    {
        return Setting::get('site_name', config('site.name', 'سایت شرکتی'));
    }

    /** View path for current theme: e.g. themes.default. Fallback to default if theme view missing. */
    public static function view(string $name): string
    {
        $theme = self::theme();
        $path = 'themes.' . $theme . '.' . $name;
        return \Illuminate\Support\Facades\View::exists($path) ? $path : 'themes.default.' . $name;
    }
}
