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
     * Prop keys probed, in order, when deriving a Canvas page's description
     * from its own block tree. A block contributes at most ONE chunk (the
     * first non-empty match), so a page's description reads as prose rather
     * than a dump of every field.
     */
    private const CANVAS_TEXT_PROPS = ['description', 'subtitle', 'text', 'content', 'title'];

    /**
     * Canonical SEO resolution for a Canvas theme page.
     *
     * A Canvas page has no Eloquent model: its title, block tree and
     * (optional) SEO overrides all live in one array inside the theme JSON.
     * This is the single place that turns that array into the meta values
     * resources/views/components/seo-meta.blade.php consumes, so the public
     * layout never has to guess and never renders an empty description.
     *
     * Precedence per field, highest first:
     *   1. the page's own explicit SEO value (author intent)
     *   2. text derived from the page's own title / block content
     *   3. the tenant default (seo_default_*)
     *   4. site identity — the last resort, because a description naming the
     *      site is a weak but valid signal, whereas an EMPTY one is a
     *      site-wide duplicate-content signal repeated on every Canvas page.
     *
     * The OG image is deliberately NOT guessed from a block's image prop:
     * theme visual assets are manifest-relative references (src="images/x.jpg")
     * that only become real URLs later, in ThemeRuntimeDocument's
     * declared-asset rewrite — an OG image guessed here could 404. Only an
     * explicit, already-resolved page image is trusted; otherwise the
     * tenant's configured og image is used.
     *
     * @param  array<string,mixed>|null  $page
     * @param  array<int,mixed>  $blocks
     * @return array{title:string,description:string,image:?string,imageAlt:string,canonical:string,robots:string,ogType:string}
     */
    public function canvasPage(?array $page, array $blocks = [], ?string $fallbackTitle = null): array
    {
        $page      = is_array($page) ? $page : [];
        $blocks    = is_array($blocks) ? $blocks : [];
        $overrides = is_array($page['seo'] ?? null) ? $page['seo'] : [];

        $title = $this->firstFilled([$page['title'] ?? null, $fallbackTitle]);
        if ($title === '') {
            $title = SiteHelper::siteName();
        }

        $description = $this->firstFilled([
            $overrides['description'] ?? null,
            $page['meta_description'] ?? null,
            $page['description'] ?? null,
        ]);
        if ($description === '') {
            $description = $this->canvasPageText($blocks);
        }
        if ($description === '') {
            $description = $this->description();
        }
        if ($description === '') {
            $description = SiteHelper::siteName();
        }

        $image = $this->firstFilled([
            $overrides['image'] ?? null,
            $page['og_image'] ?? null,
            $page['meta_image'] ?? null,
        ]);
        $image = $image !== '' ? $this->absoluteUrl($image) : $this->imageUrl();

        $canonical = $this->firstFilled([$overrides['canonical'] ?? null, $page['canonical'] ?? null]);

        $robots = $this->firstFilled([$overrides['robots'] ?? null, $page['robots'] ?? null]);
        if ($robots === '' && ! empty($page['noindex'])) {
            $robots = 'noindex, follow';
        }

        return [
            'title'       => $title,
            'description' => Str::limit($description, 160),
            'image'       => $image,
            'imageAlt'    => $title,
            'canonical'   => $this->canonical($canonical !== '' ? $canonical : null),
            'robots'      => $robots !== '' ? $robots : $this->robots(),
            'ogType'      => $this->firstFilled([$overrides['og_type'] ?? null, $page['og_type'] ?? null]) ?: 'website',
        ];
    }

    /**
     * Derives a page-unique description from a Canvas block tree by walking
     * it depth-first and taking the first meaningful text prop of each block
     * (CANVAS_TEXT_PROPS order). Returns '' when the tree carries no text at
     * all, so the caller falls through to the tenant default rather than
     * emitting an empty tag.
     */
    private function canvasPageText(array $blocks, int $limit = 160): string
    {
        if ($blocks === []) {
            return '';
        }

        $chunks = [];
        $walk = function (array $node) use (&$walk, &$chunks, $limit): void {
            if (mb_strlen(implode(' ', $chunks)) >= $limit) {
                return;
            }
            $props = $node['props'] ?? $node['data'] ?? [];
            $props = is_array($props) ? $props : [];
            foreach (self::CANVAS_TEXT_PROPS as $key) {
                $value = $props[$key] ?? null;
                if (! is_string($value)) {
                    continue;
                }
                $text = trim(preg_replace('/\s+/u', ' ', strip_tags($value)) ?? '');
                if ($text !== '') {
                    $chunks[] = $text;
                    break;
                }
            }
            $children = $node['children'] ?? $node['blocks'] ?? ($props['blocks'] ?? []);
            if (is_array($children)) {
                foreach ($children as $child) {
                    if (is_array($child)) {
                        $walk($child);
                    }
                }
            }
        };

        foreach ($blocks as $block) {
            if (is_array($block)) {
                $walk($block);
            }
        }

        return trim(implode(' ', array_slice($chunks, 0, 3)));
    }

    /** First non-empty trimmed string among the candidates, else ''. */
    private function firstFilled(array $candidates): string
    {
        foreach ($candidates as $candidate) {
            if (! is_string($candidate)) {
                continue;
            }
            $value = trim($candidate);
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    /** A stored media path resolved to its real, absolute URL. */
    private function absoluteUrl(string $path): string
    {
        if (str_starts_with($path, 'http')) {
            return $path;
        }

        return \App\Helpers\ImageHelper::url($path) ?? $path;
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
