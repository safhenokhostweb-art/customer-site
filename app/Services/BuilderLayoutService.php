<?php

namespace App\Services;

use App\Helpers\PlatformUx;
use App\Models\Page;
use App\Models\Setting;

/**
 * Resolves header and footer blocks for the site layout.
 *
 * THEME-03 (Full Product Commercial Audit, TASK 2A) — canonical authority
 * migration: Header/Footer content now comes exclusively from the
 * installed Theme's own selectable variants (`active_canvas_theme_data`'s
 * `header_variants`/`footer_variants`, written by `ThemeInstanceRevisionService`
 * — see `Theme::getHeaderVariants()` on the saas-platform side for the
 * matching authoring-time contract). `config/builder-headers.php` and
 * `config/builder-footers.php` (a second, independently-authored static
 * content library) are no longer read here — that was the competing
 * authority this migration closes. Those config files are NOT deleted:
 * `PageBuilderController::templates()` still legitimately uses them as a
 * one-time "insert a starter block structure" library (the same role
 * Elementor's Block Library plays), a genuinely different responsibility
 * from "what renders live as this page's header," which is what this
 * service alone decides now.
 *
 * Resolution cascade (both header and footer):
 *   Page variant reference (a stable variant `key`, stored on the Page —
 *     never literal block content)
 *   → Site-level default variant reference (`Setting`)
 *   → the Theme's own designated default variant
 *   → legacy platform-pushed config (only when no Theme is installed at
 *     all — the one pre-existing behavior this migration preserves rather
 *     than breaks for that edge case)
 *
 * A reference that doesn't match any current variant (a stale key from a
 * since-changed Theme) fails safe to the Theme's default variant — never
 * an exception, never blank chrome.
 */
class BuilderLayoutService
{
    public const SETTING_HEADER_TEMPLATE = 'builder_header_template';
    public const SETTING_FOOTER_TEMPLATE = 'builder_footer_template';
    public const SETTING_STICKY_HEADER = 'builder_sticky_header';

    public const PAGE_OVERRIDE_NONE = '__none__';

    private ?array $activeThemeData = null;

    /**
     * `active_canvas_theme_data`, decoded once per request. Empty array
     * when no Theme is currently installed (the setting is unset/blank).
     */
    private function activeThemeData(): array
    {
        if ($this->activeThemeData !== null) {
            return $this->activeThemeData;
        }
        $raw = Setting::get('active_canvas_theme_data');
        $decoded = is_string($raw) && $raw !== '' ? json_decode($raw, true) : (is_array($raw) ? $raw : null);
        return $this->activeThemeData = is_array($decoded) ? $decoded : [];
    }

    /**
     * Exposes the request-memoized active Theme document to the canonical
     * runtime-document builder. Callers must treat the returned array as
     * read-only; Setting remains the persistence authority.
     */
    public function currentThemeData(): array
    {
        return $this->activeThemeData();
    }

    /**
     * The installed Theme's selectable Header variants. Always returns at
     * least one real (never fabricated) variant when a Theme is installed:
     * either the Theme's own authored `header_variants`, or — for a Theme
     * that never authored more than one — its single `header_blocks`
     * synthesized into one implicit default variant, mirroring
     * `Theme::getHeaderVariants()` on the authoring side exactly. Empty
     * only when no Theme is installed at all.
     *
     * @param array|null $themeData Pass the caller's own already-loaded
     *   theme data (same shape as active_canvas_theme_data) to resolve
     *   against that instead of re-fetching via Setting::get() — needed by
     *   callers (e.g. ThemeCanvasPageController) that load theme data
     *   through their own short-TTL cache specifically to see a fresh
     *   install/publish sooner than the 1-hour Setting cache would.
     *   Defaults to this service's own activeThemeData() otherwise.
     * @return list<array{key:string,label:string,blocks:array,is_default:bool}>
     */
    public function getHeaderVariants(?array $themeData = null): array
    {
        $themeData = $themeData ?? $this->activeThemeData();
        $variants = $themeData['header_variants'] ?? [];
        if (!empty($variants)) {
            return $variants;
        }
        if (!empty($themeData['header_blocks'])) {
            return [['key' => 'default', 'label' => 'پیش‌فرض', 'blocks' => $themeData['header_blocks'], 'is_default' => true]];
        }
        return [];
    }

    /** @see getHeaderVariants() — identical contract, for footers. */
    public function getFooterVariants(?array $themeData = null): array
    {
        $themeData = $themeData ?? $this->activeThemeData();
        $variants = $themeData['footer_variants'] ?? [];
        if (!empty($variants)) {
            return $variants;
        }
        if (!empty($themeData['footer_blocks'])) {
            return [['key' => 'default', 'label' => 'پیش‌فرض', 'blocks' => $themeData['footer_blocks'], 'is_default' => true]];
        }
        return [];
    }

    private function defaultVariant(array $variants): ?array
    {
        foreach ($variants as $variant) {
            if (!empty($variant['is_default'])) {
                return $variant;
            }
        }
        return $variants[0] ?? null;
    }

    private function findVariant(array $variants, string $key): ?array
    {
        foreach ($variants as $variant) {
            if (($variant['key'] ?? null) === $key) {
                return $variant;
            }
        }
        return null;
    }

    /**
     * Resolve header blocks. Pass current Page for page-level override, and
     * $themeData to resolve against a caller-supplied theme data array (see
     * getHeaderVariants()) instead of this service's own Setting-cached read.
     */
    /**
     * TASK 3/3 Phase 6.5C (BLD-29) — real defect found and fixed: the
     * Canvas editor's "__header__" virtual page (ThemeDesignController)
     * read/wrote `themeData['header_blocks']` directly — a SEPARATE array
     * from whichever entry in `header_variants` this method actually
     * resolves as live below. For any Theme that ships variants (the
     * Universal Theme, and every canonical theme per THEME-03), that flat
     * field is a second, disconnected copy the render path never reads —
     * editing "the header" in Canvas silently saved to nowhere. This
     * method gives ThemeDesignController the SAME variant-key resolution
     * getHeaderBlocks() itself uses, site-level only (no $page override —
     * the Canvas editor edits the site's default header, not one page's
     * override), so both sides agree on which variant is "the header."
     * Returns null only when no Theme/variants exist at all (the flat-field
     * fallback case getHeaderVariants() itself documents).
     */
    public function resolveActiveHeaderVariantKey(?array $themeData = null): ?string
    {
        $variants = $this->getHeaderVariants($themeData);
        if (empty($variants)) {
            return null;
        }
        $key = Setting::get(self::SETTING_HEADER_TEMPLATE, '');
        if ($key !== '' && $key !== null && $this->findVariant($variants, $key) !== null) {
            return $key;
        }
        return $this->defaultVariant($variants)['key'] ?? null;
    }

    /** @see resolveActiveHeaderVariantKey() — identical contract, for footers. */
    public function resolveActiveFooterVariantKey(?array $themeData = null): ?string
    {
        $variants = $this->getFooterVariants($themeData);
        if (empty($variants)) {
            return null;
        }
        $key = Setting::get(self::SETTING_FOOTER_TEMPLATE, '');
        if ($key !== '' && $key !== null && $this->findVariant($variants, $key) !== null) {
            return $key;
        }
        return $this->defaultVariant($variants)['key'] ?? null;
    }

    public function getHeaderBlocks(?Page $page = null, ?array $themeData = null): array
    {
        if ($page && $page->builder_disable_header) {
            return [];
        }

        $variants = $this->getHeaderVariants($themeData);
        if (empty($variants)) {
            // No Theme installed at all — the one pre-existing behavior
            // this migration preserves rather than breaks.
            return PlatformUx::themeBuilder()['header_blocks'] ?? [];
        }

        $key = null;
        if ($page && $page->builder_header_template_override !== null && $page->builder_header_template_override !== '') {
            $key = $page->builder_header_template_override;
        }
        if ($key === null || $key === '') {
            $key = Setting::get(self::SETTING_HEADER_TEMPLATE, '');
        }

        if ($key !== '' && $key !== null) {
            $variant = $this->findVariant($variants, $key);
            if ($variant !== null) {
                return $variant['blocks'];
            }
            // Stale/missing reference (the Theme changed, or the variant
            // was removed since the Page/Site referenced it) — fail safe
            // to the Theme's own default variant, never an exception and
            // never blank chrome.
        }

        return $this->defaultVariant($variants)['blocks'] ?? [];
    }

    /**
     * Resolve footer blocks. Pass current Page for page-level override, and
     * $themeData to resolve against a caller-supplied theme data array (see
     * getHeaderVariants()) instead of this service's own Setting-cached read.
     */
    public function getFooterBlocks(?Page $page = null, ?array $themeData = null): array
    {
        if ($page && $page->builder_disable_footer) {
            return [];
        }

        $variants = $this->getFooterVariants($themeData);
        if (empty($variants)) {
            return PlatformUx::themeBuilder()['footer_blocks'] ?? [];
        }

        $key = null;
        if ($page && $page->builder_footer_template_override !== null && $page->builder_footer_template_override !== '') {
            $key = $page->builder_footer_template_override;
        }
        if ($key === null || $key === '') {
            $key = Setting::get(self::SETTING_FOOTER_TEMPLATE, '');
        }

        if ($key !== '' && $key !== null) {
            $variant = $this->findVariant($variants, $key);
            if ($variant !== null) {
                return $variant['blocks'];
            }
        }

        return $this->defaultVariant($variants)['blocks'] ?? [];
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
     * List available header variant keys and labels for admin dropdown
     * (site-level default settings).
     */
    public function getHeaderTemplateOptions(): array
    {
        $out = ['' => '— استفاده از پیش‌فرض تم —'];
        foreach ($this->getHeaderVariants() as $variant) {
            $out[$variant['key']] = $variant['label'] ?? $variant['key'];
        }
        return $out;
    }

    /**
     * List available footer variant keys and labels for admin dropdown
     * (site-level default settings).
     */
    public function getFooterTemplateOptions(): array
    {
        $out = ['' => '— استفاده از پیش‌فرض تم —'];
        foreach ($this->getFooterVariants() as $variant) {
            $out[$variant['key']] = $variant['label'] ?? $variant['key'];
        }
        return $out;
    }

    /**
     * Options for page-level header override: Use Site Default, No Header,
     * or one of the Theme's own selectable variants.
     */
    public function getHeaderOptionsForPage(): array
    {
        $out = ['' => 'استفاده از پیش‌فرض سایت'];
        foreach ($this->getHeaderVariants() as $variant) {
            $out[$variant['key']] = $variant['label'] ?? $variant['key'];
        }
        $out[self::PAGE_OVERRIDE_NONE] = 'بدون هدر';
        return $out;
    }

    /**
     * Options for page-level footer override: Use Site Default, No Footer,
     * or one of the Theme's own selectable variants.
     */
    public function getFooterOptionsForPage(): array
    {
        $out = ['' => 'استفاده از پیش‌فرض سایت'];
        foreach ($this->getFooterVariants() as $variant) {
            $out[$variant['key']] = $variant['label'] ?? $variant['key'];
        }
        $out[self::PAGE_OVERRIDE_NONE] = 'بدون فوتر';
        return $out;
    }
}
