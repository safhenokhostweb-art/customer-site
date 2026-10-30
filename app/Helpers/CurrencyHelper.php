<?php

namespace App\Helpers;

/**
 * کمک‌کنندهٔ واحد پول — حذف ۴ صفر از پول ملی ایران.
 * فعال‌سازی نیاز به: ۱) ماژول از پلتفرم ۲) تیک در تنظیمات ماژول.
 */
class CurrencyHelper
{
    public const DIVISOR = 10000;

    /**
     * آیا ماژول از پلتفرم/لایسنس در دسترس است؟
     */
    public static function isModuleAvailable(): bool
    {
        return SiteHelper::moduleEnabled('currency_reform');
    }

    /**
     * آیا کاربر تیک فعال‌سازی را زده است؟ (تنظیمات ماژول)
     */
    public static function isToggleEnabled(): bool
    {
        return (bool) \App\Models\Setting::get('currency_reform_enabled', false);
    }

    /**
     * آیا حذف ۴ صفر فعال است؟ (هر دو شرط: ماژول در دسترس + تیک زده شده)
     */
    public static function isReformEnabled(): bool
    {
        return self::isModuleAvailable() && self::isToggleEnabled();
    }

    /**
     * آیا قیمت‌ها قبلاً در دیتابیس تبدیل شده‌اند؟
     */
    public static function isMigrated(): bool
    {
        return (bool) \App\Models\Setting::get('currency_reform_migrated', false);
    }

    /**
     * مقدار عددی برای نمایش و محاسبات.
     * فعال + غیرمایگریشن: price/10000
     * فعال + مایگریشن: price (قبلاً تقسیم شده)
     * غیرفعال + مایگریشن: price*10000 (بازگردانی به قالب قدیم برای نمایش)
     * غیرفعال + غیرمایگریشن: price
     */
    public static function toDisplayValue(float|int|string $price): float
    {
        $p = (float) $price;
        $enabled = self::isReformEnabled();
        $migrated = self::isMigrated();

        if ($enabled) {
            return $migrated ? $p : $p / self::DIVISOR;
        }
        return $migrated ? $p * self::DIVISOR : $p;
    }

    /**
     * قیمت برای نمایش — وقتی ماژول فعال است: عدد اصلی + «۰۰۰۰» کم‌رنگ.
     * در محاسبات از toDisplayValue استفاده شود.
     */
    public static function formatPrice(float|int|string $price): string
    {
        return self::formatPriceWithContext($price, self::displayContext());
    }

    /** @return array{enabled:bool,migrated:bool} */
    public static function displayContext(): array
    {
        return ['enabled' => self::isReformEnabled(), 'migrated' => self::isMigrated()];
    }

    /** @param array{enabled:bool,migrated:bool} $context */
    public static function formatPriceWithContext(float|int|string $price, array $context): string
    {
        $value = self::displayValueWithContext($price, $context);
        // Phase 0.5 follow-up (ADR-064): the same formatter as the shared
        // theme-runtime blocks — Persian digits and the Persian thousands
        // separator ٬ (U+066C), as fa-IR toLocaleString() prints them too.
        $main = \SaaSPlatform\ThemeRuntime\Support\PersianNumber::format((int) round($value));
        return $main . self::fadedZerosHtml($context);
    }

    /**
     * The faded «۰۰۰۰» printed after every amount while the reform is on
     * ('' otherwise). The theme mini-cart appends it to the totals it
     * recomputes in the browser (theme-runtime blocks/cart.js).
     *
     * @param array{enabled:bool,migrated:bool} $context
     */
    public static function fadedZerosHtml(array $context): string
    {
        return $context['enabled']
            ? '<span class="currency-faded-zeros" style="opacity:0.4;color:inherit;font-weight:normal;">۰۰۰۰</span>'
            : '';
    }

    /**
     * The number formatPriceWithContext() prints, before rounding.
     *
     * @param array{enabled:bool,migrated:bool} $context
     */
    public static function displayValueWithContext(float|int|string $price, array $context): float
    {
        $raw = (float) $price;
        return $context['enabled']
            ? ($context['migrated'] ? $raw : $raw / self::DIVISOR)
            : ($context['migrated'] ? $raw * self::DIVISOR : $raw);
    }

    /**
     * تبدیل ورودی کاربر به مقدار ذخیره در دیتابیس.
     */
    public static function toStorageValue(float|int|string $input): float
    {
        $val = (float) $input;
        if (!self::isReformEnabled()) {
            return $val;
        }
        return self::isMigrated() ? $val : $val * self::DIVISOR;
    }

    /**
     * برچسب واحد پول برای نمایش.
     */
    public static function unitLabel(): string
    {
        return 'تومان';
    }

    /**
     * مقدار برای نمایش در فیلد ورودی (فرم ویرایش).
     */
    public static function toInputValue(float|int|string $storedPrice): float
    {
        return self::toDisplayValue($storedPrice);
    }
}
