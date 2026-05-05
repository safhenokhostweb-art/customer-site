<?php

namespace App\Helpers;

use App\Services\PlatformUxService;

/**
 * دسترسی به تنظیمات UX/UI پلتفرم (هماهنگ با saas-platform) برای استفاده در ویوها.
 */
class PlatformUx
{
    protected static function service(): PlatformUxService
    {
        return app(PlatformUxService::class);
    }

    public static function platformName(): string
    {
        return self::service()->platformName();
    }

    public static function logoUrl(): ?string
    {
        return self::service()->logoUrl();
    }

    public static function allTheme(): array
    {
        return self::service()->allTheme();
    }

    public static function allFont(): array
    {
        return self::service()->allFont();
    }

    public static function general(): array
    {
        return self::service()->general();
    }

    public static function clearCache(): void
    {
        self::service()->clearCache();
    }

    public static function guideWidget(): array
    {
        return self::service()->guideWidget();
    }

    public static function guideWidgetEnabled(): bool
    {
        return self::service()->guideWidgetEnabled();
    }

    public static function guideWidgetPositionClasses(): string
    {
        return self::service()->guideWidgetPositionClasses();
    }

    public static function adminNotifWidgetPositionClasses(): string
    {
        return self::service()->adminNotifWidgetPositionClasses();
    }

    public static function getCssVariables(): array
    {
        return self::service()->getCssVariables();
    }

    public static function getFontImports(): array
    {
        return self::service()->getFontImports();
    }

    /**
     * Theme Builder payload (header/footer/styles/css_block).
     */
    public static function themeBuilder(): array
    {
        return self::service()->getThemeBuilder();
    }

    /**
     * تنظیمات SEO سطح سایت (از پلتفرم).
     */
    public static function seo(): array
    {
        return self::service()->seo();
    }

    /**
     * منوهای پلتفرم (بر اساس موقعیت: header, footer, sidebar).
     */
    public static function menus(): array
    {
        return self::service()->getMenus();
    }
}
