<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Route;

class MenuItem extends Model
{
    protected $fillable = [
        'menu_id', 'parent_id', 'title', 'title_en', 'labels', 'type', 'reference_id', 'url',
        'utm_query', 'utm_json', 'icon', 'order', 'open_in_new_tab', 'rel_nofollow', 'css_class',
        'badge_label', 'image_url', 'mega_column', 'featured_html', 'auto_list_children', 'visible_modules',
    ];

    protected function casts(): array
    {
        return [
            'open_in_new_tab' => 'boolean',
            'rel_nofollow' => 'boolean',
            'auto_list_children' => 'boolean',
            'labels' => 'array',
            'utm_json' => 'array',
            'visible_modules' => 'array',
        ];
    }

    public const TYPE_INTERNAL_PAGE = 'internal_page';
    public const TYPE_BLOG = 'blog';
    public const TYPE_SHOP = 'shop';
    public const TYPE_CATEGORY = 'category';
    public const TYPE_CUSTOM_URL = 'custom_url';
    public const TYPE_BLOG_CATEGORY = 'blog_category';
    public const TYPE_BLOG_TAG = 'blog_tag';

    /**
     * برچسب‌های نوع آیتم برای ادمین.
     *
     * @return array<string, string>
     */
    public static function types(): array
    {
        return [
            self::TYPE_CUSTOM_URL => 'لینک دلخواه',
            self::TYPE_INTERNAL_PAGE => 'صفحه داخلی',
            self::TYPE_BLOG => 'وبلاگ (فهرست)',
            self::TYPE_BLOG_CATEGORY => 'دستهٔ وبلاگ',
            self::TYPE_BLOG_TAG => 'برچسب وبلاگ',
            self::TYPE_SHOP => 'فروشگاه',
            self::TYPE_CATEGORY => 'دسته‌بندی محصول',
        ];
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('order');
    }

    public function resolvedTitle(): string
    {
        $locale = app()->getLocale();
        if (str_starts_with($locale, 'en') && is_string($this->title_en) && $this->title_en !== '') {
            return $this->title_en;
        }
        if (is_array($this->labels) && isset($this->labels[$locale]) && $this->labels[$locale] !== '') {
            return (string) $this->labels[$locale];
        }

        return $this->title;
    }

    public function passesModuleVisibility(): bool
    {
        $mods = $this->visible_modules;
        if (! is_array($mods) || $mods === []) {
            return true;
        }
        foreach ($mods as $slug) {
            $slug = is_string($slug) ? trim($slug) : '';
            if ($slug === '') {
                continue;
            }
            if (! \App\Helpers\SiteHelper::moduleEnabled($slug)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Resolve the URL for this item based on type and reference.
     */
    public function resolveUrl(): string
    {
        if ($this->type === self::TYPE_CUSTOM_URL && $this->url) {
            return $this->url;
        }
        if ($this->type === self::TYPE_INTERNAL_PAGE && $this->reference_id) {
            $page = Page::find($this->reference_id);

            return $page ? route('page.show', $page->slug) : '#';
        }
        if ($this->type === self::TYPE_BLOG) {
            if (! Route::has('blog.index')) {
                return '#';
            }

            return route('blog.index');
        }
        if ($this->type === self::TYPE_BLOG_CATEGORY && $this->reference_id) {
            $cat = Category::find($this->reference_id);
            if (! $cat || ! Route::has('blog.category')) {
                return '#';
            }

            return route('blog.category', $cat->slug);
        }
        if ($this->type === self::TYPE_BLOG_TAG && $this->reference_id) {
            $tag = Tag::find($this->reference_id);
            if (! $tag || ! Route::has('blog.index')) {
                return '#';
            }

            return route('blog.index', ['tag' => $tag->slug]);
        }
        if ($this->type === self::TYPE_SHOP) {
            if (! Route::has('shop.index')) {
                return '#';
            }

            return route('shop.index');
        }
        if ($this->type === self::TYPE_CATEGORY && $this->reference_id) {
            $cat = ProductCategory::find($this->reference_id);
            if (! $cat || ! Route::has('shop.index')) {
                return Route::has('shop.index') ? route('shop.index') : '#';
            }

            return route('shop.index', ['category' => $cat->slug ?? $cat->id]);
        }

        return $this->url ?? '#';
    }

    /**
     * افزودن پارامترهای UTM و برچسب utm_json (فقط کلیدهای utm_*).
     */
    public function urlWithUtm(string $baseUrl): string
    {
        $params = [];
        $rawQuery = $this->utm_query;
        if (is_string($rawQuery) && trim($rawQuery) !== '') {
            $q = ltrim(trim($rawQuery), '?&');
            parse_str($q, $parsed);
            if (is_array($parsed)) {
                foreach ($parsed as $k => $v) {
                    if (self::isAllowedUtmKey((string) $k)) {
                        $params[(string) $k] = $v;
                    }
                }
            }
        }
        if (is_array($this->utm_json)) {
            foreach ($this->utm_json as $k => $v) {
                $k = (string) $k;
                if (! self::isAllowedUtmKey($k)) {
                    continue;
                }
                if (is_scalar($v) || $v === null) {
                    $params[$k] = $v;
                }
            }
        }
        if ($params === []) {
            return $baseUrl;
        }
        $sep = str_contains($baseUrl, '?') ? '&' : '?';

        return $baseUrl.$sep.http_build_query($params);
    }

    public static function isAllowedUtmKey(string $key): bool
    {
        return (bool) preg_match('/^utm_[a-z0-9_]+$/i', $key);
    }

    public function relAttributeValue(): string
    {
        $parts = [];
        if ($this->open_in_new_tab) {
            $parts[] = 'noopener';
            $parts[] = 'noreferrer';
        }
        if ($this->rel_nofollow) {
            $parts[] = 'nofollow';
        }
        $parts = array_unique(array_filter($parts));

        return implode(' ', $parts);
    }

    public function isProbablyExternal(): bool
    {
        if ($this->type !== self::TYPE_CUSTOM_URL || ! $this->url) {
            return false;
        }
        $u = trim($this->url);

        return str_starts_with($u, 'http://') || str_starts_with($u, 'https://');
    }
}
