<?php

namespace App\Services;

use App\Helpers\PlatformUx;
use App\Models\Page;
use App\Models\Setting;

/**
 * Resolves header and footer blocks for the site layout.
 * Priority: Page override (if exists) → Site setting → Platform themeBuilder().
 */
class BuilderLayoutService
{
    public const SETTING_HEADER_TEMPLATE = 'builder_header_template';
    public const SETTING_FOOTER_TEMPLATE = 'builder_footer_template';
    public const SETTING_STICKY_HEADER = 'builder_sticky_header';

    public const PAGE_OVERRIDE_NONE = '__none__';

    /**
     * Resolve header blocks. Pass current Page for page-level override.
     */
    public function getHeaderBlocks(?Page $page = null): array
    {
        if ($page && $page->builder_disable_header) {
            return [];
        }
        $templateKey = null;
        if ($page && $page->builder_header_template_override !== null && $page->builder_header_template_override !== '') {
            $templateKey = $page->builder_header_template_override;
        }
        if ($templateKey === null || $templateKey === '') {
            $templateKey = Setting::get(self::SETTING_HEADER_TEMPLATE, '');
        }
        if ($templateKey !== '' && $templateKey !== null) {
            $templates = config('builder-headers.templates', []);
            if (isset($templates[$templateKey]['blocks'])) {
                return $templates[$templateKey]['blocks'];
            }
        }
        $tb = PlatformUx::themeBuilder();
        return $tb['header_blocks'] ?? [];
    }

    /**
     * Resolve footer blocks. Pass current Page for page-level override.
     */
    public function getFooterBlocks(?Page $page = null): array
    {
        if ($page && $page->builder_disable_footer) {
            return [];
        }
        $templateKey = null;
        if ($page && $page->builder_footer_template_override !== null && $page->builder_footer_template_override !== '') {
            $templateKey = $page->builder_footer_template_override;
        }
        if ($templateKey === null || $templateKey === '') {
            $templateKey = Setting::get(self::SETTING_FOOTER_TEMPLATE, '');
        }
        if ($templateKey !== '' && $templateKey !== null) {
            $templates = config('builder-footers.templates', []);
            if (isset($templates[$templateKey]['blocks'])) {
                return $templates[$templateKey]['blocks'];
            }
        }
        $tb = PlatformUx::themeBuilder();
        return $tb['footer_blocks'] ?? [];
    }

    /**
     * Whether to show the header on this request (page can disable it).
     */
    public function shouldShowHeader(?Page $page = null): bool
    {
        if ($page && $page->builder_disable_header) {
            return false;
        }
        return true;
    }

    /**
     * Whether to show the footer on this request (page can disable it).
     */
    public function shouldShowFooter(?Page $page = null): bool
    {
        if ($page && $page->builder_disable_footer) {
            return false;
        }
        return true;
    }

    public function isStickyHeader(): bool
    {
        return (bool) Setting::get(self::SETTING_STICKY_HEADER, true);
    }

    /**
     * List available header template keys and names for admin dropdown (site settings).
     */
    public function getHeaderTemplateOptions(): array
    {
        $templates = config('builder-headers.templates', []);
        $out = ['' => '— استفاده از تم پلتفرم —'];
        foreach ($templates as $key => $t) {
            $out[$key] = $t['name'] ?? $key;
        }
        return $out;
    }

    /**
     * List available footer template keys and names for admin dropdown (site settings).
     */
    public function getFooterTemplateOptions(): array
    {
        $templates = config('builder-footers.templates', []);
        $out = ['' => '— استفاده از تم پلتفرم —'];
        foreach ($templates as $key => $t) {
            $out[$key] = $t['name'] ?? $key;
        }
        return $out;
    }

    /**
     * Options for page-level header override: Use Site Default, No Header, or template 1..10.
     */
    public function getHeaderOptionsForPage(): array
    {
        $templates = config('builder-headers.templates', []);
        $out = ['' => 'استفاده از پیش‌فرض سایت'];
        foreach ($templates as $key => $t) {
            $out[$key] = $t['name'] ?? $key;
        }
        $out[self::PAGE_OVERRIDE_NONE] = 'بدون هدر';
        return $out;
    }

    /**
     * Options for page-level footer override: Use Site Default, No Footer, or template 1..10.
     */
    public function getFooterOptionsForPage(): array
    {
        $templates = config('builder-footers.templates', []);
        $out = ['' => 'استفاده از پیش‌فرض سایت'];
        foreach ($templates as $key => $t) {
            $out[$key] = $t['name'] ?? $key;
        }
        $out[self::PAGE_OVERRIDE_NONE] = 'بدون فوتر';
        return $out;
    }
}
