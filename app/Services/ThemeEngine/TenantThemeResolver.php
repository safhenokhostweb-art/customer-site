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

    /** Render <style> block for injection into <head> (site + dashboard tokens) */
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

    /** Render <style> block with ONLY dashboard tokens (--admin-sidebar-*) for admin layout */
    public function renderDashboardCssBlock(?int $siteId = null): string
    {
        $allVars = $this->resolveTokens($siteId);
        $vars = array_filter($allVars, fn($k) => str_starts_with($k, '--admin-sidebar-'), ARRAY_FILTER_USE_KEY);
        if (empty($vars)) {
            return '';
        }
        $lines = [];
        foreach ($vars as $key => $value) {
            $lines[] = "  {$key}: {$value};";
        }
        return "<style id=\"dashboard-theme-tokens\" data-source=\"tenant-theme-resolver\">\n:root {\n" . implode("\n", $lines) . "\n}\n</style>";
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
        $tokens = array_filter(
            $preset,
            fn($k) => !str_starts_with($k, '--admin-sidebar-'),
            ARRAY_FILTER_USE_KEY
        );

        // Always expose --platform-primary so default layout CSS rules work
        if (!isset($tokens['--platform-primary']) && isset($tokens['--ds-primary'])) {
            $tokens['--platform-primary'] = $tokens['--ds-primary'];
        }

        return $tokens;
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
        $d         = ThemeSetting::defaults();
        $primary   = $s->primary_color    ?? $d['primary_color'];
        $secondary = $s->secondary_color  ?? $d['secondary_color'];
        $accent    = $s->accent_color     ?? $d['accent_color']     ?? '#7c3aed';
        $bg        = $s->background_color ?? $d['background_color'] ?? '#f9fafb';
        $surface   = $s->surface_color    ?? $d['surface_color']    ?? '#ffffff';
        $textColor = $s->text_color       ?? $d['text_color']       ?? '#111827';
        $muted     = $s->muted_color      ?? $d['muted_color']      ?? '#6b7280';
        $border    = $s->border_color     ?? $d['border_color']     ?? '#e5e7eb';
        $link      = $s->link_color       ?? $primary;   // always inherit primary, never hardcoded blue
        $headerBg  = $s->header_bg        ?? $d['header_bg']        ?? '#1e293b';
        $radius    = $s->border_radius    ?? $d['border_radius'];
        $container = $s->container_width  ?? $d['container_width']  ?? '1200px';

        $vars = [
            '--ui-indigo'               => $primary,
            '--ui-indigo-hover'         => $accent,
            '--ui-indigo-light'         => $bg,
            '--ui-primary-500'          => $primary,
            '--ui-primary-600'          => $accent,
            '--ui-bg'                   => $bg,
            '--ui-surface'              => $surface,
            '--ui-surface-soft'         => $surface,
            '--ui-text-primary'         => $textColor,
            '--ui-text-secondary'       => $muted,
            '--ui-text-muted'           => $muted,
            '--ui-border'               => $border,
            '--ui-border-strong'        => $border,
            '--ui-radius-md'            => $radius,
            '--ui-radius-lg'            => $radius,
            '--ds-primary'              => $primary,
            '--ds-secondary'            => $secondary,
            '--ds-button-primary-bg'    => $primary,
            '--ds-button-primary-text'  => '#ffffff',
            '--ds-link-color'           => $link,
            '--ds-link-hover'           => $accent,
            '--ds-menu-bg'              => $headerBg,
            '--ds-menu-text'            => '#f1f5f9',
            '--tb-primary'              => $primary,
            '--tb-secondary'            => $secondary,
            '--tb-background'           => $bg,
            '--tb-surface'              => $surface,
            '--tb-text'                 => $textColor,
            '--tb-text-muted'           => $muted,
            '--tb-radius'               => $radius,
            '--tb-container'            => $container,
            '--platform-primary'        => $primary,
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
            $vars['--ds-font-body']      = $s->font_family_body;
            $vars['--tb-font-body']      = $s->font_family_body;
        }
        if (!empty($s->font_family_heading)) {
            $vars['--ds-font-title']     = $s->font_family_heading;
            $vars['--tb-font-heading']   = $s->font_family_heading;
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
            'coral-sunset', 'cherry-blossom', 'forest-green', 'arctic-blue',
            'lavender-dream', 'ruby-red', 'sage-green', 'terracotta',
            'rose-gold', 'deep-space', 'amber-glow', 'steel-blue',
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
            // ── 12 پریست جدید ────────────────────────────────────────────────
            'coral-sunset' => [
                '--ui-indigo'               => '#f97316',
                '--ui-indigo-hover'         => '#ea580c',
                '--ui-indigo-light'         => '#ffedd5',
                '--ui-indigo-ring'          => 'rgb(249 115 22 / 0.25)',
                '--ui-focus-ring'           => '0 0 0 3px rgb(249 115 22 / 0.25)',
                '--ui-hover-overlay'        => 'rgba(249,115,22,0.04)',
                '--ui-hover-bg'             => 'rgba(249,115,22,0.06)',
                '--ui-active-overlay'       => 'rgba(249,115,22,0.08)',
                '--ui-active-bg'            => 'rgba(249,115,22,0.10)',
                '--ui-selected-bg'          => 'rgba(249,115,22,0.08)',
                '--ui-primary-500'          => '#f97316',
                '--ui-primary-600'          => '#ea580c',
                '--ui-bg'                   => '#fff7ed',
                '--ui-surface'              => '#ffffff',
                '--ui-surface-soft'         => '#fffaf5',
                '--ui-text-primary'         => '#431407',
                '--ui-text-secondary'       => '#7c2d12',
                '--ui-text-muted'           => '#9a3412',
                '--ui-border'               => '#fed7aa',
                '--ui-border-strong'        => '#fdba74',
                '--ds-primary'              => '#f97316',
                '--ds-button-primary-bg'    => '#f97316',
                '--ds-button-primary-text'  => '#ffffff',
                '--ds-link-color'           => '#ea580c',
                '--ds-menu-bg'              => '#fff7ed',
                '--admin-sidebar-bg1'       => '#7c2d12',
                '--admin-sidebar-bg2'       => '#431407',
                '--admin-sidebar-text'      => '#ffedd5',
                '--admin-sidebar-muted'     => '#fdba74',
                '--admin-sidebar-accent'    => '#fb923c',
                '--admin-sidebar-border'    => '#9a3412',
            ],
            'cherry-blossom' => [
                '--ui-indigo'               => '#ec4899',
                '--ui-indigo-hover'         => '#db2777',
                '--ui-indigo-light'         => '#fce7f3',
                '--ui-indigo-ring'          => 'rgb(236 72 153 / 0.25)',
                '--ui-focus-ring'           => '0 0 0 3px rgb(236 72 153 / 0.25)',
                '--ui-hover-overlay'        => 'rgba(236,72,153,0.04)',
                '--ui-hover-bg'             => 'rgba(236,72,153,0.06)',
                '--ui-active-overlay'       => 'rgba(236,72,153,0.08)',
                '--ui-active-bg'            => 'rgba(236,72,153,0.10)',
                '--ui-selected-bg'          => 'rgba(236,72,153,0.08)',
                '--ui-primary-500'          => '#ec4899',
                '--ui-primary-600'          => '#db2777',
                '--ui-bg'                   => '#fdf2f8',
                '--ui-surface'              => '#ffffff',
                '--ui-surface-soft'         => '#fef6fb',
                '--ui-text-primary'         => '#500724',
                '--ui-text-secondary'       => '#831843',
                '--ui-text-muted'           => '#9d174d',
                '--ui-border'               => '#fbcfe8',
                '--ui-border-strong'        => '#f9a8d4',
                '--ds-primary'              => '#ec4899',
                '--ds-button-primary-bg'    => '#ec4899',
                '--ds-button-primary-text'  => '#ffffff',
                '--ds-link-color'           => '#db2777',
                '--ds-menu-bg'              => '#fdf2f8',
                '--admin-sidebar-bg1'       => '#831843',
                '--admin-sidebar-bg2'       => '#500724',
                '--admin-sidebar-text'      => '#fce7f3',
                '--admin-sidebar-muted'     => '#f9a8d4',
                '--admin-sidebar-accent'    => '#f472b6',
                '--admin-sidebar-border'    => '#9d174d',
            ],
            'forest-green' => [
                '--ui-indigo'               => '#16a34a',
                '--ui-indigo-hover'         => '#15803d',
                '--ui-indigo-light'         => '#dcfce7',
                '--ui-indigo-ring'          => 'rgb(22 163 74 / 0.25)',
                '--ui-focus-ring'           => '0 0 0 3px rgb(22 163 74 / 0.25)',
                '--ui-hover-overlay'        => 'rgba(22,163,74,0.04)',
                '--ui-hover-bg'             => 'rgba(22,163,74,0.06)',
                '--ui-active-overlay'       => 'rgba(22,163,74,0.08)',
                '--ui-active-bg'            => 'rgba(22,163,74,0.10)',
                '--ui-selected-bg'          => 'rgba(22,163,74,0.08)',
                '--ui-primary-500'          => '#16a34a',
                '--ui-primary-600'          => '#15803d',
                '--ui-bg'                   => '#f0fdf4',
                '--ui-surface'              => '#ffffff',
                '--ui-surface-soft'         => '#f7fdf9',
                '--ui-text-primary'         => '#052e16',
                '--ui-text-secondary'       => '#14532d',
                '--ui-text-muted'           => '#166534',
                '--ui-border'               => '#bbf7d0',
                '--ui-border-strong'        => '#86efac',
                '--ds-primary'              => '#16a34a',
                '--ds-button-primary-bg'    => '#16a34a',
                '--ds-button-primary-text'  => '#ffffff',
                '--ds-link-color'           => '#15803d',
                '--ds-menu-bg'              => '#f0fdf4',
                '--admin-sidebar-bg1'       => '#14532d',
                '--admin-sidebar-bg2'       => '#052e16',
                '--admin-sidebar-text'      => '#dcfce7',
                '--admin-sidebar-muted'     => '#86efac',
                '--admin-sidebar-accent'    => '#4ade80',
                '--admin-sidebar-border'    => '#166534',
            ],
            'arctic-blue' => [
                '--ui-indigo'               => '#0284c7',
                '--ui-indigo-hover'         => '#0369a1',
                '--ui-indigo-light'         => '#e0f2fe',
                '--ui-indigo-ring'          => 'rgb(2 132 199 / 0.25)',
                '--ui-focus-ring'           => '0 0 0 3px rgb(2 132 199 / 0.25)',
                '--ui-hover-overlay'        => 'rgba(2,132,199,0.04)',
                '--ui-hover-bg'             => 'rgba(2,132,199,0.06)',
                '--ui-active-overlay'       => 'rgba(2,132,199,0.08)',
                '--ui-active-bg'            => 'rgba(2,132,199,0.10)',
                '--ui-selected-bg'          => 'rgba(2,132,199,0.08)',
                '--ui-primary-500'          => '#38bdf8',
                '--ui-primary-600'          => '#0284c7',
                '--ui-bg'                   => '#f0f9ff',
                '--ui-surface'              => '#ffffff',
                '--ui-surface-soft'         => '#f5fbff',
                '--ui-text-primary'         => '#082f49',
                '--ui-text-secondary'       => '#0c4a6e',
                '--ui-text-muted'           => '#075985',
                '--ui-border'               => '#bae6fd',
                '--ui-border-strong'        => '#7dd3fc',
                '--ds-primary'              => '#0284c7',
                '--ds-button-primary-bg'    => '#0284c7',
                '--ds-button-primary-text'  => '#ffffff',
                '--ds-link-color'           => '#0284c7',
                '--ds-menu-bg'              => '#f0f9ff',
                '--admin-sidebar-bg1'       => '#0c4a6e',
                '--admin-sidebar-bg2'       => '#082f49',
                '--admin-sidebar-text'      => '#e0f2fe',
                '--admin-sidebar-muted'     => '#7dd3fc',
                '--admin-sidebar-accent'    => '#38bdf8',
                '--admin-sidebar-border'    => '#075985',
            ],
            'lavender-dream' => [
                '--ui-indigo'               => '#8b5cf6',
                '--ui-indigo-hover'         => '#7c3aed',
                '--ui-indigo-light'         => '#ede9fe',
                '--ui-indigo-ring'          => 'rgb(139 92 246 / 0.25)',
                '--ui-focus-ring'           => '0 0 0 3px rgb(139 92 246 / 0.25)',
                '--ui-hover-overlay'        => 'rgba(139,92,246,0.04)',
                '--ui-hover-bg'             => 'rgba(139,92,246,0.06)',
                '--ui-active-overlay'       => 'rgba(139,92,246,0.08)',
                '--ui-active-bg'            => 'rgba(139,92,246,0.10)',
                '--ui-selected-bg'          => 'rgba(139,92,246,0.08)',
                '--ui-primary-500'          => '#8b5cf6',
                '--ui-primary-600'          => '#7c3aed',
                '--ui-bg'                   => '#faf5ff',
                '--ui-surface'              => '#ffffff',
                '--ui-surface-soft'         => '#fdf9ff',
                '--ui-text-primary'         => '#2e1065',
                '--ui-text-secondary'       => '#4c1d95',
                '--ui-text-muted'           => '#5b21b6',
                '--ui-border'               => '#ddd6fe',
                '--ui-border-strong'        => '#c4b5fd',
                '--ds-primary'              => '#8b5cf6',
                '--ds-button-primary-bg'    => '#8b5cf6',
                '--ds-button-primary-text'  => '#ffffff',
                '--ds-link-color'           => '#7c3aed',
                '--ds-menu-bg'              => '#faf5ff',
                '--admin-sidebar-bg1'       => '#4c1d95',
                '--admin-sidebar-bg2'       => '#2e1065',
                '--admin-sidebar-text'      => '#ede9fe',
                '--admin-sidebar-muted'     => '#c4b5fd',
                '--admin-sidebar-accent'    => '#a78bfa',
                '--admin-sidebar-border'    => '#5b21b6',
            ],
            'ruby-red' => [
                '--ui-indigo'               => '#dc2626',
                '--ui-indigo-hover'         => '#b91c1c',
                '--ui-indigo-light'         => '#fee2e2',
                '--ui-indigo-ring'          => 'rgb(220 38 38 / 0.25)',
                '--ui-focus-ring'           => '0 0 0 3px rgb(220 38 38 / 0.25)',
                '--ui-hover-overlay'        => 'rgba(220,38,38,0.04)',
                '--ui-hover-bg'             => 'rgba(220,38,38,0.06)',
                '--ui-active-overlay'       => 'rgba(220,38,38,0.08)',
                '--ui-active-bg'            => 'rgba(220,38,38,0.10)',
                '--ui-selected-bg'          => 'rgba(220,38,38,0.08)',
                '--ui-primary-500'          => '#dc2626',
                '--ui-primary-600'          => '#b91c1c',
                '--ui-bg'                   => '#fef2f2',
                '--ui-surface'              => '#ffffff',
                '--ui-surface-soft'         => '#fff5f5',
                '--ui-text-primary'         => '#450a0a',
                '--ui-text-secondary'       => '#7f1d1d',
                '--ui-text-muted'           => '#991b1b',
                '--ui-border'               => '#fecaca',
                '--ui-border-strong'        => '#fca5a5',
                '--ds-primary'              => '#dc2626',
                '--ds-button-primary-bg'    => '#dc2626',
                '--ds-button-primary-text'  => '#ffffff',
                '--ds-link-color'           => '#b91c1c',
                '--ds-menu-bg'              => '#fef2f2',
                '--admin-sidebar-bg1'       => '#7f1d1d',
                '--admin-sidebar-bg2'       => '#450a0a',
                '--admin-sidebar-text'      => '#fee2e2',
                '--admin-sidebar-muted'     => '#fca5a5',
                '--admin-sidebar-accent'    => '#f87171',
                '--admin-sidebar-border'    => '#991b1b',
            ],
            'sage-green' => [
                '--ui-indigo'               => '#65a30d',
                '--ui-indigo-hover'         => '#4d7c0f',
                '--ui-indigo-light'         => '#ecfccb',
                '--ui-indigo-ring'          => 'rgb(101 163 13 / 0.25)',
                '--ui-focus-ring'           => '0 0 0 3px rgb(101 163 13 / 0.25)',
                '--ui-hover-overlay'        => 'rgba(101,163,13,0.04)',
                '--ui-hover-bg'             => 'rgba(101,163,13,0.06)',
                '--ui-active-overlay'       => 'rgba(101,163,13,0.08)',
                '--ui-active-bg'            => 'rgba(101,163,13,0.10)',
                '--ui-selected-bg'          => 'rgba(101,163,13,0.08)',
                '--ui-primary-500'          => '#65a30d',
                '--ui-primary-600'          => '#4d7c0f',
                '--ui-bg'                   => '#f7fee7',
                '--ui-surface'              => '#ffffff',
                '--ui-surface-soft'         => '#fbfef0',
                '--ui-text-primary'         => '#1a2e05',
                '--ui-text-secondary'       => '#365314',
                '--ui-text-muted'           => '#3f6212',
                '--ui-border'               => '#d9f99d',
                '--ui-border-strong'        => '#bef264',
                '--ds-primary'              => '#65a30d',
                '--ds-button-primary-bg'    => '#65a30d',
                '--ds-button-primary-text'  => '#ffffff',
                '--ds-link-color'           => '#4d7c0f',
                '--ds-menu-bg'              => '#f7fee7',
                '--admin-sidebar-bg1'       => '#365314',
                '--admin-sidebar-bg2'       => '#1a2e05',
                '--admin-sidebar-text'      => '#ecfccb',
                '--admin-sidebar-muted'     => '#bef264',
                '--admin-sidebar-accent'    => '#a3e635',
                '--admin-sidebar-border'    => '#3f6212',
            ],
            'terracotta' => [
                '--ui-indigo'               => '#c2410c',
                '--ui-indigo-hover'         => '#9a3412',
                '--ui-indigo-light'         => '#ffedd5',
                '--ui-indigo-ring'          => 'rgb(194 65 12 / 0.25)',
                '--ui-focus-ring'           => '0 0 0 3px rgb(194 65 12 / 0.25)',
                '--ui-hover-overlay'        => 'rgba(194,65,12,0.04)',
                '--ui-hover-bg'             => 'rgba(194,65,12,0.06)',
                '--ui-active-overlay'       => 'rgba(194,65,12,0.08)',
                '--ui-active-bg'            => 'rgba(194,65,12,0.10)',
                '--ui-selected-bg'          => 'rgba(194,65,12,0.08)',
                '--ui-primary-500'          => '#c2410c',
                '--ui-primary-600'          => '#9a3412',
                '--ui-bg'                   => '#fef3eb',
                '--ui-surface'              => '#ffffff',
                '--ui-surface-soft'         => '#fff8f3',
                '--ui-text-primary'         => '#431407',
                '--ui-text-secondary'       => '#7c2d12',
                '--ui-text-muted'           => '#9a3412',
                '--ui-border'               => '#fed7aa',
                '--ui-border-strong'        => '#fb923c',
                '--ds-primary'              => '#c2410c',
                '--ds-button-primary-bg'    => '#c2410c',
                '--ds-button-primary-text'  => '#ffffff',
                '--ds-link-color'           => '#9a3412',
                '--ds-menu-bg'              => '#fef3eb',
                '--admin-sidebar-bg1'       => '#7c2d12',
                '--admin-sidebar-bg2'       => '#431407',
                '--admin-sidebar-text'      => '#ffedd5',
                '--admin-sidebar-muted'     => '#fdba74',
                '--admin-sidebar-accent'    => '#fb923c',
                '--admin-sidebar-border'    => '#9a3412',
            ],
            'rose-gold' => [
                '--ui-indigo'               => '#e11d48',
                '--ui-indigo-hover'         => '#be123c',
                '--ui-indigo-light'         => '#ffe4e6',
                '--ui-indigo-ring'          => 'rgb(225 29 72 / 0.25)',
                '--ui-focus-ring'           => '0 0 0 3px rgb(225 29 72 / 0.25)',
                '--ui-hover-overlay'        => 'rgba(225,29,72,0.04)',
                '--ui-hover-bg'             => 'rgba(225,29,72,0.06)',
                '--ui-active-overlay'       => 'rgba(225,29,72,0.08)',
                '--ui-active-bg'            => 'rgba(225,29,72,0.10)',
                '--ui-selected-bg'          => 'rgba(225,29,72,0.08)',
                '--ui-primary-500'          => '#e11d48',
                '--ui-primary-600'          => '#be123c',
                '--ui-bg'                   => '#fff1f2',
                '--ui-surface'              => '#ffffff',
                '--ui-surface-soft'         => '#fff5f6',
                '--ui-text-primary'         => '#4c0519',
                '--ui-text-secondary'       => '#881337',
                '--ui-text-muted'           => '#9f1239',
                '--ui-border'               => '#fecdd3',
                '--ui-border-strong'        => '#fda4af',
                '--ds-primary'              => '#e11d48',
                '--ds-button-primary-bg'    => '#e11d48',
                '--ds-button-primary-text'  => '#ffffff',
                '--ds-link-color'           => '#be123c',
                '--ds-menu-bg'              => '#fff1f2',
                '--admin-sidebar-bg1'       => '#881337',
                '--admin-sidebar-bg2'       => '#4c0519',
                '--admin-sidebar-text'      => '#ffe4e6',
                '--admin-sidebar-muted'     => '#fda4af',
                '--admin-sidebar-accent'    => '#fb7185',
                '--admin-sidebar-border'    => '#9f1239',
            ],
            'deep-space' => [
                '--ui-indigo'               => '#4338ca',
                '--ui-indigo-hover'         => '#3730a3',
                '--ui-indigo-light'         => '#e0e7ff',
                '--ui-indigo-ring'          => 'rgb(67 56 202 / 0.25)',
                '--ui-focus-ring'           => '0 0 0 3px rgb(67 56 202 / 0.25)',
                '--ui-hover-overlay'        => 'rgba(67,56,202,0.04)',
                '--ui-hover-bg'             => 'rgba(67,56,202,0.06)',
                '--ui-active-overlay'       => 'rgba(67,56,202,0.08)',
                '--ui-active-bg'            => 'rgba(67,56,202,0.10)',
                '--ui-selected-bg'          => 'rgba(67,56,202,0.08)',
                '--ui-primary-500'          => '#4338ca',
                '--ui-primary-600'          => '#3730a3',
                '--ui-bg'                   => '#eef2ff',
                '--ui-surface'              => '#ffffff',
                '--ui-surface-soft'         => '#f5f7ff',
                '--ui-text-primary'         => '#0d0b2c',
                '--ui-text-secondary'       => '#1e1b4b',
                '--ui-text-muted'           => '#312e81',
                '--ui-border'               => '#c7d2fe',
                '--ui-border-strong'        => '#a5b4fc',
                '--ds-primary'              => '#4338ca',
                '--ds-button-primary-bg'    => '#4338ca',
                '--ds-button-primary-text'  => '#ffffff',
                '--ds-link-color'           => '#3730a3',
                '--ds-menu-bg'              => '#eef2ff',
                '--admin-sidebar-bg1'       => '#1e1b4b',
                '--admin-sidebar-bg2'       => '#0d0b2c',
                '--admin-sidebar-text'      => '#e0e7ff',
                '--admin-sidebar-muted'     => '#a5b4fc',
                '--admin-sidebar-accent'    => '#818cf8',
                '--admin-sidebar-border'    => '#312e81',
            ],
            'amber-glow' => [
                '--ui-indigo'               => '#d97706',
                '--ui-indigo-hover'         => '#b45309',
                '--ui-indigo-light'         => '#fef3c7',
                '--ui-indigo-ring'          => 'rgb(217 119 6 / 0.25)',
                '--ui-focus-ring'           => '0 0 0 3px rgb(217 119 6 / 0.25)',
                '--ui-hover-overlay'        => 'rgba(217,119,6,0.04)',
                '--ui-hover-bg'             => 'rgba(217,119,6,0.06)',
                '--ui-active-overlay'       => 'rgba(217,119,6,0.08)',
                '--ui-active-bg'            => 'rgba(217,119,6,0.10)',
                '--ui-selected-bg'          => 'rgba(217,119,6,0.08)',
                '--ui-primary-500'          => '#d97706',
                '--ui-primary-600'          => '#b45309',
                '--ui-bg'                   => '#fffbeb',
                '--ui-surface'              => '#ffffff',
                '--ui-surface-soft'         => '#fffdf5',
                '--ui-text-primary'         => '#451a03',
                '--ui-text-secondary'       => '#78350f',
                '--ui-text-muted'           => '#92400e',
                '--ui-border'               => '#fde68a',
                '--ui-border-strong'        => '#fcd34d',
                '--ds-primary'              => '#d97706',
                '--ds-button-primary-bg'    => '#d97706',
                '--ds-button-primary-text'  => '#ffffff',
                '--ds-link-color'           => '#b45309',
                '--ds-menu-bg'              => '#fffbeb',
                '--admin-sidebar-bg1'       => '#78350f',
                '--admin-sidebar-bg2'       => '#451a03',
                '--admin-sidebar-text'      => '#fef3c7',
                '--admin-sidebar-muted'     => '#fcd34d',
                '--admin-sidebar-accent'    => '#fbbf24',
                '--admin-sidebar-border'    => '#92400e',
            ],
            'steel-blue' => [
                '--ui-indigo'               => '#2563eb',
                '--ui-indigo-hover'         => '#1d4ed8',
                '--ui-indigo-light'         => '#dbeafe',
                '--ui-indigo-ring'          => 'rgb(37 99 235 / 0.25)',
                '--ui-focus-ring'           => '0 0 0 3px rgb(37 99 235 / 0.25)',
                '--ui-hover-overlay'        => 'rgba(37,99,235,0.04)',
                '--ui-hover-bg'             => 'rgba(37,99,235,0.06)',
                '--ui-active-overlay'       => 'rgba(37,99,235,0.08)',
                '--ui-active-bg'            => 'rgba(37,99,235,0.10)',
                '--ui-selected-bg'          => 'rgba(37,99,235,0.08)',
                '--ui-primary-500'          => '#2563eb',
                '--ui-primary-600'          => '#1d4ed8',
                '--ui-bg'                   => '#eff6ff',
                '--ui-surface'              => '#ffffff',
                '--ui-surface-soft'         => '#f5f9ff',
                '--ui-text-primary'         => '#0f2035',
                '--ui-text-secondary'       => '#1e3a5f',
                '--ui-text-muted'           => '#1e40af',
                '--ui-border'               => '#bfdbfe',
                '--ui-border-strong'        => '#93c5fd',
                '--ds-primary'              => '#2563eb',
                '--ds-button-primary-bg'    => '#2563eb',
                '--ds-button-primary-text'  => '#ffffff',
                '--ds-link-color'           => '#1d4ed8',
                '--ds-menu-bg'              => '#eff6ff',
                '--admin-sidebar-bg1'       => '#1e3a5f',
                '--admin-sidebar-bg2'       => '#0f2035',
                '--admin-sidebar-text'      => '#dbeafe',
                '--admin-sidebar-muted'     => '#93c5fd',
                '--admin-sidebar-accent'    => '#60a5fa',
                '--admin-sidebar-border'    => '#1e40af',
            ],
        ];
    }
}
