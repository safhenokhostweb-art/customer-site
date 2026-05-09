<?php

namespace App\Services;

use App\Models\License;
use App\Models\Setting;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * دریافت و کش تنظیمات UX/UI پلتفرم (رنگ، فونت، لوگو، نام) از API سایت اصلی.
 * در صورت نبود لایسنس یا خطا، مقادیر پیش‌فرض مشابه پلتفرم استفاده می‌شود.
 */
class PlatformUxService
{
    public const CACHE_KEY = 'platform_site_config';

    /** پیش‌فرض‌های تم و فونت مطابق پلتفرم */
    protected const DEFAULT_THEME = [
        'theme_primary' => '#4f46e5',
        'theme_secondary' => '#6366f1',
        'theme_menu_bg' => '#ffffff',
        'theme_menu_text' => '#374151',
        'theme_menu_link_active' => '#2563eb',
        'theme_title_color' => '#111827',
        'theme_link_color' => '#4f46e5',
        'theme_link_hover' => '#4338ca',
        'theme_sidebar_bg' => '#ffffff',
        'theme_sidebar_text' => '#374151',
        'theme_sidebar_active_bg' => '#eff6ff',
        'theme_sidebar_active_text' => '#2563eb',
        'theme_button_primary_bg' => '#4f46e5',
        'theme_button_primary_text' => '#ffffff',
    ];

    protected const DEFAULT_FONT = [
        'font_body' => 'Vazirmatn, Tahoma, sans-serif',
        'font_title' => 'Vazirmatn, Tahoma, sans-serif',
        'font_size_base' => '16',
        'font_size_title' => '1.25',
        'font_custom_1_name' => '',
        'font_custom_1_url' => '',
        'font_custom_2_name' => '',
        'font_custom_2_url' => '',
        'font_custom_3_name' => '',
        'font_custom_3_url' => '',
        'font_custom_4_name' => '',
        'font_custom_4_url' => '',
        'font_custom_5_name' => '',
        'font_custom_5_url' => '',
    ];

    protected const DEFAULT_GENERAL = [
        'platform_name' => 'پلتفرم SaaS',
        'logo_url' => null,
        'registration_open' => true,
    ];

    protected const DEFAULT_SEO = [
        'seo_default_title' => '',
        'seo_default_description' => '',
        'seo_robots' => 'index, follow',
        'seo_og_image' => null,
        'seo_twitter_card' => 'summary',
    ];

    protected const DEFAULT_GUIDE_WIDGET = [
        'guide_widget_enabled' => '1',
        'guide_widget_position' => 'bottom-right',
        'guide_widget_btn_bg' => '#2563eb',
        'guide_widget_btn_text' => '#ffffff',
        'guide_widget_panel_bg' => '#ffffff',
        'guide_widget_panel_header_bg' => '#f9fafb',
        'guide_widget_panel_text' => '#111827',
    ];

    /** مقدار پیش‌فرض theme_builder وقتی API یا لایسنس در دسترس نیست. */
    protected const DEFAULT_THEME_BUILDER = [
        'header_blocks' => [],
        'footer_blocks' => [],
        'global_styles' => [],
        'settings' => [],
        'meta' => [],
        'css_block' => '',
    ];

    /**
     * دریافت کل پیکربندی UX از API یا کش یا پیش‌فرض.
     */
    public function getUx(): array
    {
        $config = $this->getSiteConfig();
        return $config['ux'] ?? $this->defaultUx();
    }

    /**
     * اعلان‌های ارسالی از پلتفرم برای این سایت.
     */
    public function getPlatformNotifications(): array
    {
        $config = $this->getSiteConfig();
        return $config['notifications'] ?? [];
    }

    /**
     * Theme Builder payload (header_blocks, footer_blocks, global_styles, css_block, settings).
     */
    public function getThemeBuilder(): array
    {
        $config = $this->getSiteConfig();
        $tb = $config['theme_builder'] ?? [];
        return array_merge(self::DEFAULT_THEME_BUILDER, is_array($tb) ? $tb : []);
    }

    /**
     * منوهای ارسالی از پلتفرم (بر اساس موقعیت: header, footer, sidebar).
     * هر عنصر آرایه: ['name' => ..., 'items' => [...]]
     */
    public function getMenus(): array
    {
        $config = $this->getSiteConfig();
        $menus = $config['menus'] ?? [];

        // تنظیمات محلی منو (ماژول menu) از پنل ادمین سایت + منوهای منتشرشده در دیتابیس
        $mode = (string) (Setting::get('menu_mode', 'platform') ?: 'platform');
        $rawLocal = Setting::get('menu_custom_menus', null);
        $localMenus = $this->decodeMenus($rawLocal);

        if ($mode === 'local') {
            $merged = $this->mergeLocalMenusWithDatabase($localMenus);
            if ($this->menusHaveAnyItems($merged)) {
                return $merged;
            }
        }

        return $menus;
    }

    /**
     * Decode menu payload saved in settings (json string or array).
     *
     * @return array<string, mixed>
     */
    public function decodeMenus(mixed $raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }
        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * ادغام منوی ذخیره‌شده در settings با منوهای منتشرشدهٔ جدول menus (اولویت با دیتابیس).
     *
     * @param  array<string, mixed>  $settingsMenus
     * @return array<string, array{items: list<array<string, mixed>>}>
     */
    protected function mergeLocalMenusWithDatabase(array $settingsMenus): array
    {
        $db = App::make(MenuService::class)->exportPublishedMenusForPlatformShape();
        $keys = ['header', 'footer', 'mobile', 'secondary', 'topbar'];
        $out = [];
        foreach ($keys as $key) {
            $dbItems = $db[$key]['items'] ?? [];
            $setItems = $settingsMenus[$key]['items'] ?? [];
            if (is_array($dbItems) && count($dbItems) > 0) {
                $out[$key] = ['items' => $this->enrichMenuTreeItems($dbItems)];
            } elseif (is_array($setItems) && count($setItems) > 0) {
                $out[$key] = ['items' => $this->enrichMenuTreeItems($setItems)];
            } else {
                $out[$key] = ['items' => []];
            }
        }

        return $out;
    }

    /**
     * @param  array<string, array{items?: mixed}>  $merged
     */
    protected function menusHaveAnyItems(array $merged): bool
    {
        foreach ($merged as $block) {
            $items = $block['items'] ?? [];
            if (is_array($items) && count($items) > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    public function enrichMenuTreeItems(array $items): array
    {
        $out = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $children = $item['children'] ?? [];
            $children = is_array($children) ? $this->enrichMenuTreeItems($children) : [];
            $target = $item['target'] ?? (($item['open_in_new_tab'] ?? false) ? '_blank' : '_self');
            $out[] = array_merge([
                'title' => (string) ($item['title'] ?? ''),
                'url' => (string) ($item['url'] ?? '#'),
                'target' => in_array($target, ['_self', '_blank'], true) ? $target : '_self',
                'rel' => (string) ($item['rel'] ?? ''),
                'icon' => $item['icon'] ?? null,
                'css_class' => $item['css_class'] ?? null,
                'badge' => $item['badge'] ?? null,
                'image_url' => $item['image_url'] ?? null,
                'mega_column' => $item['mega_column'] ?? null,
                'featured_html' => $item['featured_html'] ?? null,
                'mega_columns' => is_array($item['mega_columns'] ?? null) ? $item['mega_columns'] : [],
                'type' => $item['type'] ?? null,
                'sort_order' => $item['sort_order'] ?? null,
                'utm_query' => $item['utm_query'] ?? null,
                'utm_json' => $item['utm_json'] ?? null,
                'labels' => $item['labels'] ?? null,
                'title_en' => $item['title_en'] ?? null,
                'children' => $children,
            ], array_diff_key($item, array_flip([
                'title', 'url', 'target', 'rel', 'icon', 'css_class', 'badge', 'image_url',
                'mega_column', 'featured_html', 'mega_columns', 'type', 'sort_order',
                'utm_query', 'utm_json', 'labels', 'title_en', 'children',
            ])));
        }

        return $out;
    }

    /**
     * یکدست‌سازی کلیدهای منو (header/footer/…) برای خروجی API.
     *
     * @param  array<string, mixed>  $menus
     * @return array<string, mixed>
     */
    public function enrichMenuPayload(array $menus): array
    {
        $out = [];
        foreach ($menus as $key => $block) {
            if (is_array($block) && isset($block['items']) && is_array($block['items'])) {
                $out[$key] = ['items' => $this->enrichMenuTreeItems($block['items'])];
            } else {
                $out[$key] = $block;
            }
        }

        return $out;
    }

    protected function getSiteConfig(): array
    {
        if (session()->pull('platform_config_just_refreshed', false) || (request()->has('nocache') && auth()->check())) {
            Cache::forget(self::CACHE_KEY);
        }
        $ttl = config('site.platform_config_cache_ttl', 300);
        return Cache::remember(self::CACHE_KEY, $ttl, function () {
            $data = $this->fetchSiteConfig();
            if ($data && !empty($data['allowed'])) {
                return $data;
            }
            $default = $this->defaultUx();
            return ['ux' => $default, 'notifications' => [], 'seo' => $default['seo'] ?? []];
        });
    }

    /**
     * فقط تم (رنگ‌ها).
     */
    public function allTheme(): array
    {
        $ux = $this->getUx();
        return array_merge(self::DEFAULT_THEME, $ux['theme'] ?? []);
    }

    /**
     * فقط فونت‌ها و اندازه.
     */
    public function allFont(): array
    {
        $ux = $this->getUx();
        return array_merge(self::DEFAULT_FONT, $ux['font'] ?? []);
    }

    /**
     * متغیرهای CSS از design system (در صورت وجود در پاسخ API).
     *
     * @return array<string, string>
     */
    public function getCssVariables(): array
    {
        $ux = $this->getUx();
        return $ux['css_variables'] ?? [];
    }

    /**
     * URLهای فونت برای لود (در صورت وجود در پاسخ API).
     *
     * @return array<string>
     */
    public function getFontImports(): array
    {
        $ux = $this->getUx();
        return $ux['font_imports'] ?? [];
    }

    /**
     * تنظیمات عمومی (نام پلتفرم، لوگو، ثبت‌نام).
     */
    public function general(): array
    {
        $ux = $this->getUx();
        return array_merge(self::DEFAULT_GENERAL, $ux['general'] ?? []);
    }

    public function platformName(): string
    {
        return (string) ($this->general()['platform_name'] ?? self::DEFAULT_GENERAL['platform_name']);
    }

    public function logoUrl(): ?string
    {
        $url = $this->general()['logo_url'] ?? null;
        return $url ? (string) $url : null;
    }

    /**
     * پاک کردن کش (مثلاً بعد از تغییر لایسنس یا برای تست).
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * درخواست به API پلتفرم برای دریافت site-config (ux، notifications، modules).
     */
    protected function fetchSiteConfig(): ?array
    {
        if (config('site.owner_site') && !License::current()) {
            return null;
        }
        $license = License::current();
        if (!$license || empty($license->license_key)) {
            return null;
        }

        $baseUrl = config('site.platform_url');
        if (empty($baseUrl)) {
            return null;
        }

        $url = $baseUrl . '/api/site-config';
        $params = [
            'license_key' => $license->license_key,
            'domain' => $license->domain ?: request()->getHost(),
        ];

        try {
            $response = Http::timeout(10)->get($url, $params);
            if (!$response->successful()) {
                return null;
            }
            $data = $response->json();
            if (empty($data['allowed']) || empty($data['ux'])) {
                return null;
            }
            if (!empty($data['seo'])) {
                $data['ux'] = $data['ux'] ?? [];
                $data['ux']['seo'] = array_merge(self::DEFAULT_SEO, $data['seo']);
            }
            return $data;
        } catch (\Throwable $e) {
            Log::debug('PlatformUxService fetch failed', ['message' => $e->getMessage()]);
            return null;
        }
    }

    protected function defaultUx(): array
    {
        return [
            'general' => self::DEFAULT_GENERAL,
            'theme' => self::DEFAULT_THEME,
            'font' => self::DEFAULT_FONT,
            'css_variables' => $this->buildDefaultCssVariables(),
            'font_imports' => [],
            'guide_widget' => self::DEFAULT_GUIDE_WIDGET,
            'seo' => self::DEFAULT_SEO,
        ];
    }

    /**
     * تنظیمات SEO برای سایت (tenant-level).
     */
    public function seo(): array
    {
        $ux = $this->getUx();
        return array_merge(self::DEFAULT_SEO, $ux['seo'] ?? []);
    }

    protected function buildDefaultCssVariables(): array
    {
        $t = self::DEFAULT_THEME;
        $f = self::DEFAULT_FONT;
        $vars = [
            '--ds-primary' => $t['theme_primary'],
            '--ds-secondary' => $t['theme_secondary'],
            '--ds-menu-bg' => $t['theme_menu_bg'],
            '--ds-menu-text' => $t['theme_menu_text'],
            '--ds-menu-link-active' => $t['theme_menu_link_active'],
            '--ds-title-color' => $t['theme_title_color'],
            '--ds-link-color' => $t['theme_link_color'],
            '--ds-link-hover' => $t['theme_link_hover'],
            '--ds-sidebar-bg' => $t['theme_sidebar_bg'],
            '--ds-sidebar-text' => $t['theme_sidebar_text'],
            '--ds-sidebar-active-bg' => $t['theme_sidebar_active_bg'],
            '--ds-sidebar-active-text' => $t['theme_sidebar_active_text'],
            '--ds-button-primary-bg' => $t['theme_button_primary_bg'],
            '--ds-button-primary-text' => $t['theme_button_primary_text'],
            '--ds-font-body' => $f['font_body'],
            '--ds-font-title' => $f['font_title'],
            '--ds-font-size-base' => ($f['font_size_base'] ?? '16') . 'px',
            '--ds-font-size-title' => $f['font_size_title'] ?? '1.25',
        ];
        $aliases = [
            '--platform-primary' => '--ds-primary',
            '--platform-secondary' => '--ds-secondary',
            '--platform-menu-bg' => '--ds-menu-bg',
            '--platform-menu-text' => '--ds-menu-text',
            '--platform-menu-link-active' => '--ds-menu-link-active',
            '--platform-title-color' => '--ds-title-color',
            '--platform-link-color' => '--ds-link-color',
            '--platform-link-hover' => '--ds-link-hover',
            '--platform-sidebar-bg' => '--ds-sidebar-bg',
            '--platform-sidebar-text' => '--ds-sidebar-text',
            '--platform-sidebar-active-bg' => '--ds-sidebar-active-bg',
            '--platform-sidebar-active-text' => '--ds-sidebar-active-text',
            '--platform-button-primary-bg' => '--ds-button-primary-bg',
            '--platform-button-primary-text' => '--ds-button-primary-text',
            '--platform-font-body' => '--ds-font-body',
            '--platform-font-title' => '--ds-font-title',
            '--platform-font-size-base' => '--ds-font-size-base',
            '--platform-font-size-title' => '--ds-font-size-title',
        ];
        foreach ($aliases as $legacy => $dsVar) {
            if (isset($vars[$dsVar])) {
                $vars[$legacy] = $vars[$dsVar];
            }
        }
        return $vars;
    }

    /**
     * تنظیمات ویجت راهنما (فعال/غیرفعال، موقعیت، پالت رنگ).
     */
    public function guideWidget(): array
    {
        $ux = $this->getUx();
        return array_merge(self::DEFAULT_GUIDE_WIDGET, $ux['guide_widget'] ?? []);
    }

    public function guideWidgetEnabled(): bool
    {
        return ((string) ($this->guideWidget()['guide_widget_enabled'] ?? '1')) === '1';
    }

    /** کلاس‌های CSS برای موقعیت دکمهٔ ویجت (fixed). */
    public function guideWidgetPositionClasses(): string
    {
        $p = (string) ($this->guideWidget()['guide_widget_position'] ?? 'bottom-right');
        $map = [
            'bottom-right' => 'bottom-6 right-6',
            'bottom-left' => 'bottom-6 left-6',
            'top-right' => 'top-6 right-6',
            'top-left' => 'top-6 left-6',
        ];
        return $map[$p] ?? 'bottom-6 right-6';
    }

    /** کلاس‌های CSS برای موقعیت ویجت اعلان ادمین (fixed). */
    public function adminNotifWidgetPositionClasses(): string
    {
        $ux = $this->getUx();
        return (string) ($ux['admin_notif_widget']['position_classes'] ?? 'bottom-6 right-6');
    }
}
