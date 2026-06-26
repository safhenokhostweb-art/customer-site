<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ThemeSetting extends Model
{
    protected $fillable = [
        'site_id',
        'primary_color',
        'secondary_color',
        'accent_color',
        'background_color',
        'surface_color',
        'text_color',
        'muted_color',
        'border_color',
        'link_color',
        'header_bg',
        'footer_bg',
        'font_family_heading',
        'font_family_body',
        'base_font_size',
        'container_width',
        'border_radius',
        'button_style',
        'section_spacing',
    ];

    public const BUTTON_SOLID = 'solid';
    public const BUTTON_OUTLINE = 'outline';
    public const BUTTON_ROUNDED = 'rounded';

    public static function buttonStyles(): array
    {
        return [
            self::BUTTON_SOLID   => 'توپر (Solid)',
            self::BUTTON_OUTLINE => 'حاشیه‌دار (Outline)',
            self::BUTTON_ROUNDED => 'گرد (Rounded)',
        ];
    }

    public static function current(?int $siteId = null): self
    {
        $key = 'theme_settings_' . ($siteId ?? 'default');
        return Cache::remember($key, 3600, function () use ($siteId) {
            $q = self::query();
            if ($siteId !== null) {
                $q->where('site_id', $siteId);
            } else {
                $q->whereNull('site_id');
            }
            $settings = $q->first();
            return $settings ?? new self(array_merge(
                ['site_id' => $siteId],
                self::defaults()
            ));
        });
    }

    public static function defaults(): array
    {
        return [
            'primary_color'      => '#4f46e5',
            'secondary_color'    => '#64748b',
            'accent_color'       => '#7c3aed',
            'background_color'   => '#f9fafb',
            'surface_color'      => '#ffffff',
            'text_color'         => '#111827',
            'muted_color'        => '#6b7280',
            'border_color'       => '#e5e7eb',
            'link_color'         => '#4f46e5',
            'header_bg'          => '#1e293b',
            'footer_bg'          => '#1e293b',
            'font_family_heading'=> 'Vazirmatn, Tahoma, sans-serif',
            'font_family_body'   => 'Vazirmatn, Tahoma, sans-serif',
            'base_font_size'     => '16px',
            'container_width'    => '1200px',
            'border_radius'      => '8px',
            'button_style'       => self::BUTTON_SOLID,
            'section_spacing'    => 'md',
        ];
    }

    public function toCssVariables(): array
    {
        $d = self::defaults();

        $primary    = $this->primary_color    ?? $d['primary_color'];
        $secondary  = $this->secondary_color  ?? $d['secondary_color'];
        $accent     = $this->accent_color     ?? $d['accent_color'];
        $bg         = $this->background_color ?? $d['background_color'];
        $surface    = $this->surface_color    ?? $d['surface_color'];
        $textColor  = $this->text_color       ?? $d['text_color'];
        $muted      = $this->muted_color      ?? $d['muted_color'];
        $border     = $this->border_color     ?? $d['border_color'];
        $link       = $this->link_color       ?? $d['link_color'];
        $headerBg   = $this->header_bg        ?? $d['header_bg'];
        $radius     = $this->border_radius    ?? $d['border_radius'];
        $fontBody   = $this->font_family_body    ?? $d['font_family_body'];
        $fontHead   = $this->font_family_heading ?? $d['font_family_heading'];
        $fontSize   = $this->base_font_size   ?? $d['base_font_size'];
        $container  = $this->container_width  ?? $d['container_width'];

        $out = [
            '--tb-primary'           => $primary,
            '--tb-secondary'         => $secondary,
            '--tb-background'        => $bg,
            '--tb-surface'           => $surface,
            '--tb-text'              => $textColor,
            '--tb-text-muted'        => $muted,
            '--tb-radius'            => $radius,
            '--tb-container'         => $container,
            '--tb-font-body'         => $fontBody . ', Tahoma, sans-serif',
            '--tb-font-heading'      => $fontHead . ', Tahoma, sans-serif',
            '--tb-base-font-size'    => $fontSize,
            '--ds-primary'           => $primary,
            '--ds-secondary'         => $secondary,
            '--ds-button-primary-bg' => $primary,
            '--ds-button-primary-text'=> '#ffffff',
            '--ds-link-color'        => $link,
            '--ds-link-hover'        => $accent,
            '--ds-menu-bg'           => $headerBg,
            '--ds-menu-text'         => '#f1f5f9',
            '--ds-font-body'         => $fontBody,
            '--ds-font-title'        => $fontHead,
            '--ds-font-size-base'    => $fontSize,
            '--ui-indigo'            => $primary,
            '--ui-indigo-hover'      => $accent,
            '--ui-bg'                => $bg,
            '--ui-surface'           => $surface,
            '--ui-surface-soft'      => $surface,
            '--ui-text-primary'      => $textColor,
            '--ui-text-secondary'    => $muted,
            '--ui-text-muted'        => $muted,
            '--ui-border'            => $border,
            '--ui-border-strong'     => $border,
            '--ui-radius-md'         => $radius,
            '--ui-radius-lg'         => $radius,
            '--platform-primary'     => $primary,
            '--tb-button-style'      => $this->button_style ?? self::BUTTON_SOLID,
            '--tb-section-spacing'   => $this->section_spacing ?? 'md',
        ];

        if (preg_match('/^#([0-9a-f]{6})$/i', $primary, $m)) {
            [$r, $g, $b] = array_map('hexdec', str_split($m[1], 2));
            $out['--ui-hover-overlay']  = "rgba($r,$g,$b,0.04)";
            $out['--ui-hover-bg']       = "rgba($r,$g,$b,0.06)";
            $out['--ui-active-overlay'] = "rgba($r,$g,$b,0.08)";
            $out['--ui-active-bg']      = "rgba($r,$g,$b,0.10)";
            $out['--ui-selected-bg']    = "rgba($r,$g,$b,0.08)";
            $out['--ui-indigo-ring']    = "rgb($r $g $b / 0.25)";
            $out['--ui-focus-ring']     = "0 0 0 3px rgb($r $g $b / 0.25)";
        }

        return $out;
    }

    public static function clearCache(?int $siteId = null): void
    {
        Cache::forget('theme_settings_' . ($siteId ?? 'default'));
    }
}
