<?php

namespace App\Helpers;

use Illuminate\Support\Str;

/**
 * کمک برای تولید مقادیر متا و OG از مدل‌ها (صفحه، مطلب، محصول و غیره).
 * از تنظیمات tenant-level (PlatformUx::seo) استفاده می‌کند.
 */
class Seo
{
    protected static function service(): \App\Services\SeoService
    {
        return app(\App\Services\SeoService::class);
    }

    public static function title($model = null): string
    {
        return self::service()->title($model);
    }

    public static function description($model = null, int $limit = 160): string
    {
        return self::service()->description($model, $limit);
    }

    public static function imageUrl($model = null): ?string
    {
        return self::service()->imageUrl($model);
    }

    public static function robots(): string
    {
        return self::service()->robots();
    }

    public static function canonical(?string $url = null): string
    {
        return self::service()->canonical($url);
    }

    public static function twitterCard(): string
    {
        return self::service()->twitterCard();
    }
}
