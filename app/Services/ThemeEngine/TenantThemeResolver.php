<?php

namespace App\Services\ThemeEngine;

use App\Models\Setting;
use App\Models\ThemeSetting;
use App\Services\PlatformUxService;
use Illuminate\Support\Facades\Cache;

/**
 * Resolves theme tokens for the current customer-site tenant.
 *
 * Two independent tracks:
 *
 * SITE TRACK (--ui-*, --ds-*, --tb-* tokens):
 *   1. PlatformUxService assigned preset (platform admin overrides)
 *   2. `site_theme_preset_slug` setting
 *   3. `theme_preset_slug` setting (backward-compat alias)
 *   4. ThemeSetting custom colors
 *
 * DASHBOARD TRACK (--admin-sidebar-* tokens only):
 *   1. `dashboard_preset_slug` setting (default: 'enterprise-blue')
 *   2. 'custom' → individual `admin_sidebar_*` settings
 */
class TenantThemeResolver
{
    private const CACHE_TTL = 3600;

    /** Resolve all CSS custom properties (site + dashboard) */
    public function resolveTokens(?int $siteId = null): array
    {
        $cacheKey = 'tenant_theme_tokens_' . ($siteId ?? 'default');
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($siteId) {
            return array_merge(
                $this->resolveSiteTokens($siteId),
                $this->resolveDashboardTokens()
            );
        });
    }

    public function clearCache(?int $siteId = null): void
    {
        Cache::forget('tenant_theme_tokens_' . ($siteId ?? 'default'));
        Cache::forget('settings');
        Cache::forget(PlatformUxService::CACHE_KEY);
    }

    /** Render <style> block for injection into <head> */
    public function renderCssBlock(?int $siteId = null): string
    {
        $vars = $this->resolveTokens($siteId);
        if (empty($vars)) {
            return '';
        }
        $lines = [];
        foreach ($vars as $key => $value) {
            $lines[] = "  {$key}: {$value};";
        }
        $body = implode("\n", $lines);
        return "<style id=\"theme-engine-tokens\" data-source=\"tenant-theme-resolver\">\n:root {\n{$body}\n}\n</style>";
    }

    // ── Site tokens (UI / DS / TB) ────────────────────────────────────────────

    private function resolveSiteTokens(?int $siteId): array
    {
        $setting = ThemeSetting::current($siteId);

        try {
            $platformSlug = app(PlatformUxService::class)->getAssignedThemePresetSlug();
        } catch (\Throwable) {
            $platformSlug = null;
        }

        $presetSlug = $platformSlug
            ?? Setting::get('site_theme_preset_slug')
            ?? Setting::get('theme_preset_slug')      // backward-compat
            ?? ($setting->preset_slug ?? null);

        if ($presetSlug && $presetSlug !== 'custom') {
            $preset = $this->loadPreset($presetSlug);
            if ($preset) {
                return $this->extractSiteTokens($preset);
            }
        }

        return $this->fromThemeSetting($setting);
    }

    // ── Dashboard tokens (--admin-sidebar-*) ─────────────────────────────────

    private function resolveDashboardTokens(): array
    {
        $presetSlug = Setting::get('dashboard_preset_slug', 'enterprise-blue');

        if ($presetSlug === 'custom') {
            return $this->dashboardTokensFromSettings();
        }

        $preset = $this->loadPreset($presetSlug ?: 'enterprise-blue');
        if ($preset) {
            return $this->extractDashboardTokens($preset);
        }

        // Hard fallback: enterprise-blue dashboard colors
        return [
            '--admin-sidebar-bg1'    => '#1e293b',
            '--admin-sidebar-bg2'    => '#0f172a',
            '--admin-sidebar-text'   => '#f1f5f9',
            '--admin-sidebar-muted'  => '#94a3b8',
            '--admin-sidebar-accent' => '#60a5fa',
            '--admin-sidebar-border' => '#334155',
        ];
    }

    private function dashboardTokensFromSettings(): array
    {
        return [
            '--admin-sidebar-bg1'    => Setting::get('admin_sidebar_bg1',    '#1e293b'),
            '--admin-sidebar-bg2'    => Setting::get('admin_sidebar_bg2',    '#0f172a'),
            '--admin-sidebar-text'   => Setting::get('admin_sidebar_text',   '#f1f5f9'),
            '--admin-sidebar-muted'  => Setting::get('admin_sidebar_muted',  '#94a3b8'),
            '--admin-sidebar-accent' => Setting::get('admin_sidebar_accent', '#60a5fa'),
            '--admin-sidebar-border' => Setting::get('admin_sidebar_border', '#334155'),
        ];
    }

    // ── Token filters ─────────────────────────────────────────────────────────

    private function extractSiteTokens(array $preset): array
    {
        return array_filter(
            $preset,
            fn($k) => !str_starts_with($k, '--admin-sidebar-'),
            ARRAY_FILTER_USE_KEY
        );
    }

    private function extractDashboardTokens(array $preset): array
    {
        return array_filter(
            $preset,
            fn($k) => str_starts_with($k, '--admin-sidebar-'),
            ARRAY_FILTER_USE_KEY
        );
    }

    // ── Custom ThemeSetting → CSS vars ────────────────────────────────────────

    private function fromThemeSetting(ThemeSetting $s): array
    {
        $defaults  = ThemeSetting::defaults();
        $primary   = $s->primary_color   ?? $defaults['primary_color'];
        $secondary = $s->secondary_color ?? $defaults['secondary_color'];
        $radius    = $s->border_radius   ?? $defaults['border_radius'];

        $vars = [
            '--ui-indigo'               => $primary,
            '--ui-indigo-hover'         => $primary,
            '--ds-primary'              => $primary,
            '--ds-secondary'            => $secondary,
            '--ds-button-primary-bg'    => $primary,
            '--ds-button-primary-text'  => '#ffffff',
            '--ds-link-color'           => $primary,
            '--ui-radius-md'            => $radius,
            '--ui-radius-lg'            => $radius,
            '--tb-primary'              => $primary,
            '--tb-secondary'            => $secondary,
            '--tb-radius'               => $radius,
        ];

        if (preg_match('/^#([0-9a-f]{6})$/i', $primary, $m)) {
            [$r, $g, $b] = array_map('hexdec', str_split($m[1], 2));
            $vars['--ui-hover-overlay']  = "rgba($r,$g,$b,0.04)";
            $vars['--ui-hover-bg']       = "rgba($r,$g,$b,0.06)";
            $vars['--ui-active-overlay'] = "rgba($r,$g,$b,0.08)";
            $vars['--ui-active-bg']      = "rgba($r,$g,$b,0.10)";
            $vars['--ui-selected-bg']    = "rgba($r,$g,$b,0.08)";
            $vars['--ui-indigo-ring']    = "rgb($r $g $b / 0.25)";
            $vars['--ui-focus-ring']     = "0 0 0 3px rgb($r $g $b / 0.25)";
        }

        if (!empty($s->font_family_body)) {
            $vars['--ds-font-body']    = $s->font_family_body;
            $vars['--tb-font-body']    = $s->font_family_body;
        }
        if (!empty($s->font_family_heading)) {
            $vars['--ds-font-title']   = $s->font_family_heading;
            $vars['--tb-font-heading'] = $s->font_family_heading;
        }
        if (!empty($s->base_font_size)) {
            $vars['--ds-font-size-base'] = $s->base_font_size;
            $vars['--tb-base-font-size'] = $s->base_font_size;
        }

        return $vars;
    }

    // ── Preset registry ───────────────────────────────────────────────────────

    private function loadPreset(string $slug): ?array
    {
        return $this->presets()[$slug] ?? null;
    }

    /** Returns slugs of all available presets (for validation) */
    public static function presetSlugs(): array
    {
        return [
            'enterprise-blue', 'modern-indigo', 'emerald-pro', 'royal-purple',
            'midnight-dark', 'graphite', 'ocean-teal', 'executive-gold',
        ];
    }

    private function presets(): array
    {
        return [
            'enterprise-blue' => [
                '--ui-indigo'               => '#4f46e5',
                '--ui-indigo-hover'         => '#4338ca',
                '--ui-indigo-light'         => '#eef2ff',
                '--ui-indigo-ring'          => 'rgb(79 70 229 / 0.25)',
                '--ui-focus-ring'           => '0 0 0 3px rgb(79 70 229 / 0.25)',
                '--ui-hover-overlay'        => 'rgba(79,70,229,0.04)',
                '--ui-hover-bg'             => 'rgba(79,70,229,0.06)',
                '--ui-active-overlay'       => 'rgba(79,70,229,0.08)',
                '--ui-active-bg'            => 'rgba(79,70,229,0.10)',
                '--ui-selected-bg'          => 'rgba(79,70,229,0.08)',
                '--ui-primary-500'          => '#3b82f6',
                '--ui-primary-600'          => '#2563eb',
                '--ui-bg'                   => '#f1f5f9',
                '--ui-surface'              => '#ffffff',
                '--ui-surface-soft'         => '#f8fafc',
                '--ui-text-primary'         => '#0f172a',
                '--ui-text-secondary'       => '#334155',
                '--ui-text-muted'           => '#64748b',
                '--ui-border'               => '#e2e8f0',
                '--ui-border-strong'        => '#cbd5e1',
                '--ds-primary'              => '#4f46e5',
                '--ds-button-primary-bg'    => '#4f46e5',
                '--ds-button-primary-text'  => '#ffffff',
                '--ds-link-color'           => '#4f46e5',
                '--ds-menu-bg'              => '#ffffff',
                '--admin-sidebar-bg1'       => '#1e293b',
                '--admin-sidebar-bg2'       => '#0f172a',
                '--admin-sidebar-text'      => '#f1f5f9',
                '--admin-sidebar-muted'     => '#94a3b8',
                '--admin-sidebar-accent'    => '#60a5fa',
                '--admin-sidebar-border'    => '#334155',
            ],
            'modern-indigo' => [
                '--ui-indigo'               => '#6366f1',
                '--ui-indigo-hover'         => '#4f46e5',
                '--ui-indigo-light'         => '#eef2ff',
                '--ui-indigo-ring'          => 'rgb(99 102 241 / 0.25)',
                '--ui-focus-ring'           => '0 0 0 3px rgb(99 102 241 / 0.25)',
                '--ui-hover-overlay'        => 'rgba(99,102,241,0.04)',
                '--ui-hover-bg'             => 'rgba(99,102,241,0.06)',
                '--ui-active-overlay'       => 'rgba(99,102,241,0.08)',
                '--ui-active-bg'            => 'rgba(99,102,241,0.10)',
                '--ui-selected-bg'          => 'rgba(99,102,241,0.08)',
                '--ui-primary-500'          => '#6366f1',
                '--ui-primary-600'          => '#4f46e5',
                '--ui-bg'                   => '#f5f4ff',
                '--ui-surface'              => '#ffffff',
                '--ui-surface-soft'         => '#f8f7ff',
                '--ui-text-primary'         => '#1e1b4b',
                '--ui-text-secondary'       => '#3730a3',
                '--ui-text-muted'           => '#6366f1',
                '--ui-border'               => '#c7d2fe',
                '--ui-border-strong'        => '#a5b4fc',
                '--ds-primary'              => '#6366f1',
                '--ds-button-primary-bg'    => '#6366f1',
                '--ds-button-primary-text'  => '#ffffff',
                '--ds-link-color'           => '#6366f1',
                '--ds-menu-bg'              => '#f8f7ff',
                '--admin-sidebar-bg1'       => '#312e81',
                '--admin-sidebar-bg2'       => '#1e1b4b',
                '--admin-sidebar-text'      => '#e0e7ff',
                '--admin-sidebar-muted'     => '#a5b4fc',
                '--admin-sidebar-accent'    => '#818cf8',
                '--admin-sidebar-border'    => '#4338ca',
            ],
            'emerald-pro' => [
                '--ui-indigo'               => '#059669',
                '--ui-indigo-hover'         => '#047857',
                '--ui-indigo-light'         => '#d1fae5',
                '--ui-indigo-ring'          => 'rgb(5 150 105 / 0.25)',
                '--ui-focus-ring'           => '0 0 0 3px rgb(5 150 105 / 0.25)',
                '--ui-hover-overlay'        => 'rgba(5,150,105,0.04)',
                '--ui-hover-bg'             => 'rgba(5,150,105,0.06)',
                '--ui-active-overlay'       => 'rgba(5,150,105,0.08)',
                '--ui-active-bg'            => 'rgba(5,150,105,0.10)',
                '--ui-selected-bg'          => 'rgba(5,150,105,0.08)',
                '--ui-primary-500'          => '#22c55e',
                '--ui-primary-600'          => '#16a34a',
                '--ui-bg'                   => '#f0fdf4',
                '--ui-surface'              => '#ffffff',
                '--ui-surface-soft'         => '#f7fef7',
                '--ui-text-primary'         => '#052e16',
                '--ui-text-secondary'       => '#065f46',
                '--ui-text-muted'           => '#10b981',
                '--ui-border'               => '#a7f3d0',
                '--ui-border-strong'        => '#6ee7b7',
                '--ds-primary'              => '#059669',
                '--ds-button-primary-bg'    => '#059669',
                '--ds-button-primary-text'  => '#ffffff',
                '--ds-link-color'           => '#059669',
                '--ds-menu-bg'              => '#f0fdf4',
                '--admin-sidebar-bg1'       => '#065f46',
                '--admin-sidebar-bg2'       => '#022c22',
                '--admin-sidebar-text'      => '#d1fae5',
                '--admin-sidebar-muted'     => '#6ee7b7',
                '--admin-sidebar-accent'    => '#34d399',
                '--admin-sidebar-border'    => '#065f46',
            ],
            'royal-purple' => [
                '--ui-indigo'               => '#7c3aed',
                '--ui-indigo-hover'         => '#6d28d9',
                '--ui-indigo-light'         => '#ede9fe',
                '--ui-indigo-ring'          => 'rgb(124 58 237 / 0.25)',
                '--ui-focus-ring'           => '0 0 0 3px rgb(124 58 237 / 0.25)',
                '--ui-hover-overlay'        => 'rgba(124,58,237,0.04)',
                '--ui-hover-bg'             => 'rgba(124,58,237,0.06)',
                '--ui-active-overlay'       => 'rgba(124,58,237,0.08)',
                '--ui-active-bg'            => 'rgba(124,58,237,0.10)',
                '--ui-selected-bg'          => 'rgba(124,58,237,0.08)',
                '--ui-primary-500'          => '#a855f7',
                '--ui-primary-600'          => '#9333ea',
                '--ui-bg'                   => '#faf5ff',
                '--ui-surface'              => '#ffffff',
                '--ui-surface-soft'         => '#fdf4ff',
                '--ui-text-primary'         => '#1a0536',
                '--ui-text-secondary'       => '#5b21b6',
                '--ui-text-muted'           => '#7c3aed',
                '--ui-border'               => '#ddd6fe',
                '--ui-border-strong'        => '#c4b5fd',
                '--ds-primary'              => '#7c3aed',
                '--ds-button-primary-bg'    => '#7c3aed',
                '--ds-button-primary-text'  => '#ffffff',
                '--ds-link-color'           => '#7c3aed',
                '--ds-menu-bg'              => '#faf5ff',
                '--admin-sidebar-bg1'       => '#3b0764',
                '--admin-sidebar-bg2'       => '#1a0036',
                '--admin-sidebar-text'      => '#f3e8ff',
                '--admin-sidebar-muted'     => '#c084fc',
                '--admin-sidebar-accent'    => '#e879f9',
                '--admin-sidebar-border'    => '#6b21a8',
            ],
            'midnight-dark' => [
                '--ui-indigo'               => '#818cf8',
                '--ui-indigo-hover'         => '#a5b4fc',
                '--ui-indigo-light'         => '#312e81',
                '--ui-indigo-ring'          => 'rgb(129 140 248 / 0.25)',
                '--ui-focus-ring'           => '0 0 0 3px rgb(129 140 248 / 0.25)',
                '--ui-hover-overlay'        => 'rgba(129,140,248,0.04)',
                '--ui-hover-bg'             => 'rgba(129,140,248,0.08)',
                '--ui-active-overlay'       => 'rgba(129,140,248,0.10)',
                '--ui-active-bg'            => 'rgba(129,140,248,0.14)',
                '--ui-selected-bg'          => 'rgba(129,140,248,0.10)',
                '--ui-primary-500'          => '#60a5fa',
                '--ui-bg'                   => '#030712',
                '--ui-surface'              => '#111827',
                '--ui-surface-soft'         => '#0f172a',
                '--ui-text-primary'         => '#f9fafb',
                '--ui-text-secondary'       => '#e5e7eb',
                '--ui-text-muted'           => '#9ca3af',
                '--ui-border'               => '#374151',
                '--ui-border-strong'        => '#4b5563',
                '--ds-primary'              => '#818cf8',
                '--ds-button-primary-bg'    => '#818cf8',
                '--ds-button-primary-text'  => '#ffffff',
                '--ds-link-color'           => '#818cf8',
                '--ds-menu-bg'              => '#111827',
                '--ds-menu-text'            => '#f9fafb',
                '--admin-sidebar-bg1'       => '#111827',
                '--admin-sidebar-bg2'       => '#030712',
                '--admin-sidebar-text'      => '#e5e7eb',
                '--admin-sidebar-muted'     => '#6b7280',
                '--admin-sidebar-accent'    => '#818cf8',
                '--admin-sidebar-border'    => '#374151',
            ],
            'graphite' => [
                '--ui-indigo'               => '#374151',
                '--ui-indigo-hover'         => '#1f2937',
                '--ui-indigo-light'         => '#e5e7eb',
                '--ui-indigo-ring'          => 'rgb(55 65 81 / 0.25)',
                '--ui-focus-ring'           => '0 0 0 3px rgb(55 65 81 / 0.25)',
                '--ui-hover-overlay'        => 'rgba(55,65,81,0.04)',
                '--ui-hover-bg'             => 'rgba(55,65,81,0.06)',
                '--ui-active-overlay'       => 'rgba(55,65,81,0.08)',
                '--ui-active-bg'            => 'rgba(55,65,81,0.10)',
                '--ui-selected-bg'          => 'rgba(55,65,81,0.08)',
                '--ui-primary-500'          => '#6b7280',
                '--ui-bg'                   => '#f3f4f6',
                '--ui-surface'              => '#ffffff',
                '--ui-surface-soft'         => '#f9fafb',
                '--ui-text-primary'         => '#111827',
                '--ui-text-secondary'       => '#1f2937',
                '--ui-text-muted'           => '#4b5563',
                '--ui-border'               => '#d1d5db',
                '--ui-border-strong'        => '#9ca3af',
                '--ds-primary'              => '#374151',
                '--ds-button-primary-bg'    => '#374151',
                '--ds-button-primary-text'  => '#ffffff',
                '--ds-link-color'           => '#374151',
                '--ds-menu-bg'              => '#f9fafb',
                '--admin-sidebar-bg1'       => '#1f2937',
                '--admin-sidebar-bg2'       => '#111827',
                '--admin-sidebar-text'      => '#f9fafb',
                '--admin-sidebar-muted'     => '#9ca3af',
                '--admin-sidebar-accent'    => '#9ca3af',
                '--admin-sidebar-border'    => '#374151',
            ],
            'ocean-teal' => [
                '--ui-indigo'               => '#0d9488',
                '--ui-indigo-hover'         => '#0f766e',
                '--ui-indigo-light'         => '#ccfbf1',
                '--ui-indigo-ring'          => 'rgb(13 148 136 / 0.25)',
                '--ui-focus-ring'           => '0 0 0 3px rgb(13 148 136 / 0.25)',
                '--ui-hover-overlay'        => 'rgba(13,148,136,0.04)',
                '--ui-hover-bg'             => 'rgba(13,148,136,0.06)',
                '--ui-active-overlay'       => 'rgba(13,148,136,0.08)',
                '--ui-active-bg'            => 'rgba(13,148,136,0.10)',
                '--ui-selected-bg'          => 'rgba(13,148,136,0.08)',
                '--ui-primary-500'          => '#14b8a6',
                '--ui-primary-600'          => '#0d9488',
                '--ui-bg'                   => '#f0fdfa',
                '--ui-surface'              => '#ffffff',
                '--ui-surface-soft'         => '#f5fffe',
                '--ui-text-primary'         => '#042f2e',
                '--ui-text-secondary'       => '#134e4a',
                '--ui-text-muted'           => '#0f766e',
                '--ui-border'               => '#5eead4',
                '--ui-border-strong'        => '#2dd4bf',
                '--ds-primary'              => '#0d9488',
                '--ds-button-primary-bg'    => '#0d9488',
                '--ds-button-primary-text'  => '#ffffff',
                '--ds-link-color'           => '#0d9488',
                '--ds-menu-bg'              => '#ccfbf1',
                '--admin-sidebar-bg1'       => '#134e4a',
                '--admin-sidebar-bg2'       => '#042f2e',
                '--admin-sidebar-text'      => '#ccfbf1',
                '--admin-sidebar-muted'     => '#5eead4',
                '--admin-sidebar-accent'    => '#2dd4bf',
                '--admin-sidebar-border'    => '#0f766e',
            ],
            'executive-gold' => [
                '--ui-indigo'               => '#b45309',
                '--ui-indigo-hover'         => '#92400e',
                '--ui-indigo-light'         => '#fde68a',
                '--ui-indigo-ring'          => 'rgb(180 83 9 / 0.25)',
                '--ui-focus-ring'           => '0 0 0 3px rgb(180 83 9 / 0.25)',
                '--ui-hover-overlay'        => 'rgba(180,83,9,0.04)',
                '--ui-hover-bg'             => 'rgba(180,83,9,0.06)',
                '--ui-active-overlay'       => 'rgba(180,83,9,0.08)',
                '--ui-active-bg'            => 'rgba(180,83,9,0.10)',
                '--ui-selected-bg'          => 'rgba(180,83,9,0.08)',
                '--ui-primary-500'          => '#f59e0b',
                '--ui-primary-600'          => '#d97706',
                '--ui-bg'                   => '#fffbeb',
                '--ui-surface'              => '#ffffff',
                '--ui-surface-soft'         => '#fffef7',
                '--ui-text-primary'         => '#1c1917',
                '--ui-text-secondary'       => '#44403c',
                '--ui-text-muted'           => '#78716c',
                '--ui-border'               => '#fcd34d',
                '--ui-border-strong'        => '#fbbf24',
                '--ds-primary'              => '#b45309',
                '--ds-button-primary-bg'    => '#b45309',
                '--ds-button-primary-text'  => '#ffffff',
                '--ds-link-color'           => '#b45309',
                '--ds-menu-bg'              => '#fde68a',
                '--admin-sidebar-bg1'       => '#292524',
                '--admin-sidebar-bg2'       => '#1c1917',
                '--admin-sidebar-text'      => '#fef3c7',
                '--admin-sidebar-muted'     => '#fbbf24',
                '--admin-sidebar-accent'    => '#fcd34d',
                '--admin-sidebar-border'    => '#44403c',
            ],
        ];
    }
}
