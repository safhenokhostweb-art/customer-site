<?php

namespace App\Services;

use App\Helpers\PlatformUx;
use App\Helpers\SiteHelper;
use Illuminate\Support\Str;

/**
 * سرویس مرکزی SEO: متا، canonical، robots با پشتیبانی از تنظیمات tenant-level.
 */
class SeoService
{
    /**
     * عنوان صفحه (با fallback به تنظیمات tenant و نام سایت).
     */
    public function title(?object $model = null): string
    {
        if ($model !== null) {
            $t = $model->meta_title ?? $model->title ?? null;
            if ($t !== null && (string) $t !== '') {
                return (string) $t;
            }
        }
        $seo = PlatformUx::seo();
        $default = trim($seo['seo_default_title'] ?? '');
        return $default !== '' ? $default : SiteHelper::siteName();
    }

    /**
     * توضیحات متا (با fallback به محتوای مدل).
     */
    public function description(?object $model = null, int $limit = 160): string
    {
        if ($model !== null) {
            $desc = $model->meta_description ?? null;
            if ($desc !== null && (string) $desc !== '') {
                return Str::limit(strip_tags((string) $desc), $limit);
            }
            $body = $model->body ?? $model->content ?? $model->description ?? null;
            if ($body !== null) {
                return Str::limit(strip_tags((string) $body), $limit);
            }
        }
        $seo = PlatformUx::seo();
        $default = trim($seo['seo_default_description'] ?? '');
        return Str::limit($default, $limit);
    }

    /**
     * URL تصویر برای OG/Twitter.
     */
    public function imageUrl(?object $model = null): ?string
    {
        if ($model !== null) {
            $image = $model->og_image ?? $model->featured_image ?? $model->image ?? $model->meta_image ?? null;
            if (!empty($image)) {
                return \App\Helpers\ImageHelper::url($image) ?? $image;
            }
        }
        $seo = PlatformUx::seo();
        $img = trim($seo['seo_og_image'] ?? '');
        return $img !== '' ? $img : null;
    }

    /**
     * مقدار robots (index, follow | noindex, nofollow و غیره).
     */
    public function robots(): string
    {
        $seo = PlatformUx::seo();
        $r = trim($seo['seo_robots'] ?? '');
        return $r !== '' ? $r : config('seo.default_robots', 'index, follow');
    }

    /**
     * Canonical URL مطلق برای صفحه.
     */
    public function canonical(?string $url = null): string
    {
        $url = trim((string) $url);
        if ($url !== '') {
            return str_starts_with($url, 'http') ? $url : url($url);
        }
        return url()->current();
    }

    /**
     * Twitter card type.
     */
    public function twitterCard(): string
    {
        $seo = PlatformUx::seo();
        $c = trim($seo['seo_twitter_card'] ?? '');
        return $c !== '' ? $c : config('seo.default_twitter_card', 'summary');
    }
}
