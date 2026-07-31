<?php

namespace App\Services;

use App\Helpers\PlatformUx;
use App\Helpers\SiteHelper;
use Illuminate\Support\Str;
use SaaSPlatform\CoreUtils\Contracts\Services\SeoOverrideProviderInterface;

/**
 * سرویس مرکزی SEO: متا، canonical، robots با پشتیبانی از تنظیمات tenant-level.
 *
 * Phase 18-B: قبل از منطق فعلی (model → PlatformUx → default)، یک لایهٔ
 * اختیاری اضافه شد — SeoOverrideProviderInterface. پیش‌فرض آن یک Null
 * Object است (هیچ Override‌ای)، پس رفتار این کلاس برای هر سایتی که هیچ
 * Module سئویی نصب نکرده کاملاً بدون تغییر می‌ماند. فقط وقتی یک Module
 * (مثل seo-advanced) این Interface را Bind کند و مقداری برای یک فیلد
 * برگرداند، همان مقدار به‌جای منطق فعلی استفاده می‌شود — model-level
 * override (meta_title و غیره روی خودِ صفحه) همچنان بالاترین اولویت را
 * دارد، چون دقیقاً به همان محتوای همان صفحه مربوط است.
 */
class SeoService
{
    public function __construct(
        protected SeoOverrideProviderInterface $overrideProvider,
    ) {}

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
        $override = $this->override('title');
        if ($override !== null) {
            return $override;
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
        $override = $this->override('description');
        if ($override !== null) {
            return Str::limit($override, $limit);
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
        $override = $this->override('ogImage');
        if ($override !== null) {
            return $override;
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
        $override = $this->override('robots');
        if ($override !== null) {
            return $override;
        }
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
        $override = $this->override('canonical');
        if ($override !== null) {
            return $override;
        }
        return url()->current();
    }

    /**
     * Twitter card type.
     */
    public function twitterCard(): string
    {
        $override = $this->override('twitterCard');
        if ($override !== null) {
            return $override;
        }
        $seo = PlatformUx::seo();
        $c = trim($seo['seo_twitter_card'] ?? '');
        return $c !== '' ? $c : config('seo.default_twitter_card', 'summary');
    }

    /**
     * Phase 18-B: reads one field from the bound SeoOverrideProviderInterface.
     * Defensive by design — a provider throwing or returning malformed data
     * must never break page rendering; it just falls through to the
     * existing default as if nothing were bound.
     */
    protected function override(string $field): ?string
    {
        try {
            $data = $this->overrideProvider->overrideFor();
        } catch (\Throwable $e) {
            return null;
        }

        if (!is_array($data) || !array_key_exists($field, $data)) {
            return null;
        }

        $value = $data[$field];
        return ($value === null || $value === '') ? null : (string) $value;
    }
}
