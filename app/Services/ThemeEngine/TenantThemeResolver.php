<?php

namespace App\Services\ThemeEngine;

use App\Models\Setting;
use App\Models\ThemeSetting;
use App\Services\PlatformUxService;
use Illuminate\Support\Facades\Cache;

/**
 * Resolves the active theme for the current customer-site tenant.
 *
 * Resolution order:
 * 1. ThemeSetting.preset_slug → one of the 8 enterprise presets (full token set)
 * 2. ThemeSetting custom colors → minimal override (primary, accent, surfaces)
 * 3. No override → empty (design-tokens.css defaults apply)
 */
class TenantThemeResolver
{
    private const CACHE_TTL = 3600;

    /**
     * Resolve CSS custom properties for the given site.
     * Returns associative array: ['--ui-primary-500' => '#value', ...]
     */
    public function resolveTokens(?int $siteId = null): array
    {
        $cacheKey = 'tenant_theme_tokens_' . ($siteId ?? 'default');
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($siteId) {
            return $this->buildTokens($siteId);
        });
    }

    public function clearCache(?int $siteId = null): void
    {
        Cache::forget('tenant_theme_tokens_' . ($siteId ?? 'default'));
        Cache::forget('settings');
        Cache::forget(PlatformUxService::CACHE_KEY);
    }

    /**
     * Render a full <style id="theme-engine-tokens"> block for injection into <head>.
     */
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

    // ── Private ───────────────────────────────────────────────────────────────

    private function buildTokens(?int $siteId): array
    {
        $setting = ThemeSetting::current($siteId);

        // Priority 1: preset assigned by platform admin (via API site-config response)
        // Priority 2: local Setting key
        // Priority 3: ThemeSetting.preset_slug column
        try {
            $platformSlug = app(PlatformUxService::class)->getAssignedThemePresetSlug();
        } catch (\Throwable) {
            $platformSlug = null;
        }
        $presetSlug = $platformSlug ?? Setting::get('theme_preset_slug') ?? ($setting->preset_slug ?? null);
        if ($presetSlug) {
            $preset = $this->loadPreset($presetSlug);
            if ($preset) {
                return $preset;
            }
        }

        // Otherwise map ThemeSetting's custom colors to --ui-* / --ds-* vars
        return $this->fromThemeSetting($setting);
    }

    /**
     * Load a preset's full token set by slug.
     * Presets mirror the ThemePresetRegistry — defined here without cross-project import.
     */
    private function loadPreset(string $slug): ?array
    {
        $presets = $this->presets();
        return $presets[$slug] ?? null;
    }

    /**
     * Map ThemeSetting columns → CSS custom properties.
     * Only overrides the tokens that ThemeSetting actually stores.
     */
    private function fromThemeSetting(ThemeSetting $s): array
    {
        $defaults = ThemeSetting::defaults();
        $primary   = $s->primary_color   ?? $defaults['primary_color'];
        $secondary = $s->secondary_color ?? $defaults['secondary_color'];
        $radius    = $s->border_radius   ?? $defaults['border_radius'];

        $vars = [
            '--ui-indigo'               => $primary,
            '--ui-indigo-hover'         => $primary,
            '--ds-primary'              => $primary,
            '--ds-secondary'            => $secondary,
            '--ds-button-primary-bg'    => $primary,
            '--ds-link-color'           => $primary,
            '--ui-radius-md'            => $radius,
            '--ui-radius-lg'            => $radius,
            '--tb-primary'              => $primary,
            '--tb-secondary'            => $secondary,
            '--tb-radius'               => $radius,
        ];

        if (!empty($s->font_family_body)) {
            $vars['--ds-font-body']   = $s->font_family_body;
            $vars['--tb-font-body']   = $s->font_family_body;
        }
        if (!empty($s->font_family_heading)) {
            $vars['--ds-font-title']  = $s->font_family_heading;
            $vars['--tb-font-heading']= $s->font_family_heading;
        }
        if (!empty($s->base_font_size)) {
            $vars['--ds-font-size-base'] = $s->base_font_size;
            $vars['--tb-base-font-size'] = $s->base_font_size;
        }

        return $vars;
    }

    /**
     * Full preset token maps including --admin-sidebar-* vars used by customer-site layout.
     * Mirrors ThemePresetRegistry from saas-platform.
     */
    private function presets(): array
    {
        return [
            'enterprise-blue' => [
                '--ui-indigo' => '#4f46e5', '--ui-indigo-hover' => '#4338ca', '--ui-indigo-light' => '#eef2ff',
                '--ui-primary-500' => '#3b82f6', '--ui-primary-600' => '#2563eb',
                '--ui-bg' => '#f1f5f9', '--ui-surface' => '#ffffff', '--ui-text-primary' => '#0f172a',
                '--ds-primary' => '#4f46e5', '--ds-button-primary-bg' => '#4f46e5', '--ds-menu-bg' => '#ffffff',
                '--admin-sidebar-bg1' => '#1e293b', '--admin-sidebar-bg2' => '#0f172a',
                '--admin-sidebar-text' => '#f1f5f9', '--admin-sidebar-muted' => '#94a3b8',
                '--admin-sidebar-accent' => '#60a5fa', '--admin-sidebar-border' => '#334155',
            ],
            'modern-indigo' => [
                '--ui-indigo' => '#6366f1', '--ui-indigo-hover' => '#4f46e5', '--ui-indigo-light' => '#eef2ff',
                '--ui-primary-500' => '#6366f1', '--ui-primary-600' => '#4f46e5',
                '--ui-bg' => '#f5f4ff', '--ui-surface' => '#ffffff', '--ui-text-primary' => '#1e1b4b',
                '--ds-primary' => '#6366f1', '--ds-button-primary-bg' => '#6366f1', '--ds-menu-bg' => '#f8f7ff',
                '--admin-sidebar-bg1' => '#312e81', '--admin-sidebar-bg2' => '#1e1b4b',
                '--admin-sidebar-text' => '#e0e7ff', '--admin-sidebar-muted' => '#a5b4fc',
                '--admin-sidebar-accent' => '#818cf8', '--admin-sidebar-border' => '#4338ca',
            ],
            'emerald-pro' => [
                '--ui-indigo' => '#059669', '--ui-indigo-hover' => '#047857', '--ui-indigo-light' => '#ecfdf5',
                '--ui-primary-500' => '#22c55e', '--ui-primary-600' => '#16a34a',
                '--ui-bg' => '#f0fdf4', '--ui-surface' => '#ffffff', '--ui-text-primary' => '#052e16',
                '--ds-primary' => '#059669', '--ds-button-primary-bg' => '#059669', '--ds-menu-bg' => '#f0fdf4',
                '--admin-sidebar-bg1' => '#065f46', '--admin-sidebar-bg2' => '#022c22',
                '--admin-sidebar-text' => '#d1fae5', '--admin-sidebar-muted' => '#6ee7b7',
                '--admin-sidebar-accent' => '#34d399', '--admin-sidebar-border' => '#065f46',
            ],
            'royal-purple' => [
                '--ui-indigo' => '#7c3aed', '--ui-indigo-hover' => '#6d28d9', '--ui-indigo-light' => '#f5f3ff',
                '--ui-primary-500' => '#a855f7', '--ui-primary-600' => '#9333ea',
                '--ui-bg' => '#faf5ff', '--ui-surface' => '#ffffff', '--ui-text-primary' => '#1a0536',
                '--ds-primary' => '#7c3aed', '--ds-button-primary-bg' => '#7c3aed', '--ds-menu-bg' => '#faf5ff',
                '--admin-sidebar-bg1' => '#3b0764', '--admin-sidebar-bg2' => '#1a0036',
                '--admin-sidebar-text' => '#f3e8ff', '--admin-sidebar-muted' => '#c084fc',
                '--admin-sidebar-accent' => '#e879f9', '--admin-sidebar-border' => '#6b21a8',
            ],
            'midnight-dark' => [
                '--ui-indigo' => '#818cf8', '--ui-indigo-hover' => '#a5b4fc', '--ui-indigo-light' => '#1e1b4b',
                '--ui-primary-500' => '#60a5fa', '--ui-bg' => '#030712',
                '--ui-surface' => '#111827', '--ui-surface-soft' => '#0f172a',
                '--ui-text-primary' => '#f9fafb', '--ui-text-secondary' => '#e5e7eb', '--ui-text-muted' => '#9ca3af',
                '--ui-border' => '#374151',
                '--ds-primary' => '#818cf8', '--ds-button-primary-bg' => '#818cf8',
                '--ds-menu-bg' => '#111827', '--ds-menu-text' => '#f9fafb',
                '--admin-sidebar-bg1' => '#111827', '--admin-sidebar-bg2' => '#030712',
                '--admin-sidebar-text' => '#e5e7eb', '--admin-sidebar-muted' => '#6b7280',
                '--admin-sidebar-accent' => '#818cf8', '--admin-sidebar-border' => '#374151',
            ],
            'graphite' => [
                '--ui-indigo' => '#374151', '--ui-indigo-hover' => '#1f2937', '--ui-indigo-light' => '#f9fafb',
                '--ui-primary-500' => '#6b7280', '--ui-bg' => '#f3f4f6',
                '--ui-surface' => '#ffffff', '--ui-text-primary' => '#111827',
                '--ds-primary' => '#374151', '--ds-button-primary-bg' => '#374151', '--ds-menu-bg' => '#f9fafb',
                '--admin-sidebar-bg1' => '#1f2937', '--admin-sidebar-bg2' => '#111827',
                '--admin-sidebar-text' => '#f9fafb', '--admin-sidebar-muted' => '#9ca3af',
                '--admin-sidebar-accent' => '#9ca3af', '--admin-sidebar-border' => '#374151',
            ],
            'ocean-teal' => [
                '--ui-indigo' => '#0d9488', '--ui-indigo-hover' => '#0f766e', '--ui-indigo-light' => '#f0fdfa',
                '--ui-primary-500' => '#14b8a6', '--ui-primary-600' => '#0d9488',
                '--ui-bg' => '#f0fdfa', '--ui-surface' => '#ffffff', '--ui-text-primary' => '#042f2e',
                '--ds-primary' => '#0d9488', '--ds-button-primary-bg' => '#0d9488', '--ds-menu-bg' => '#f0fdfa',
                '--admin-sidebar-bg1' => '#134e4a', '--admin-sidebar-bg2' => '#042f2e',
                '--admin-sidebar-text' => '#ccfbf1', '--admin-sidebar-muted' => '#5eead4',
                '--admin-sidebar-accent' => '#2dd4bf', '--admin-sidebar-border' => '#0f766e',
            ],
            'executive-gold' => [
                '--ui-indigo' => '#b45309', '--ui-indigo-hover' => '#92400e', '--ui-indigo-light' => '#fffbeb',
                '--ui-primary-500' => '#f59e0b', '--ui-primary-600' => '#d97706',
                '--ui-bg' => '#fffbeb', '--ui-surface' => '#ffffff', '--ui-text-primary' => '#1c1917',
                '--ds-primary' => '#b45309', '--ds-button-primary-bg' => '#b45309', '--ds-menu-bg' => '#fffbeb',
                '--admin-sidebar-bg1' => '#292524', '--admin-sidebar-bg2' => '#1c1917',
                '--admin-sidebar-text' => '#fef3c7', '--admin-sidebar-muted' => '#fbbf24',
                '--admin-sidebar-accent' => '#fcd34d', '--admin-sidebar-border' => '#44403c',
            ],
        ];
    }
}
