<?php

namespace App\Helpers;

use App\Models\License;
use App\Models\Setting;

class SiteHelper
{
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
            if (is_array($modules) && in_array($key, $modules, true)) {
                return true;
            }
            // پلتفرم ماژول را به messaging تغییر داده؛ کد سایت ممکن است هنوز whatsapp چک کند
            if ($key === 'whatsapp' && in_array('messaging', $modules, true)) {
                return true;
            }
        }
        return Module::enabled($key);
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
