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
            self::BUTTON_SOLID => 'توپر (Solid)',
            self::BUTTON_OUTLINE => 'حاشیه‌دار (Outline)',
            self::BUTTON_ROUNDED => 'گرد (Rounded)',
        ];
    }

    /**
     * Get theme settings for current site (cached).
     */
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
            'primary_color' => '#4f46e5',
            'secondary_color' => '#64748b',
            'font_family_heading' => 'Vazirmatn, Tahoma, sans-serif',
            'font_family_body' => 'Vazirmatn, Tahoma, sans-serif',
            'base_font_size' => '16px',
            'container_width' => '1200px',
            'border_radius' => '8px',
            'button_style' => self::BUTTON_SOLID,
            'section_spacing' => 'md',
        ];
    }

    /**
     * Generate CSS custom properties for :root.
     */
    public function toCssVariables(): array
    {
        $attrs = [
            'primary_color' => '--tb-primary',
            'secondary_color' => '--tb-secondary',
            'font_family_heading' => '--tb-font-heading',
            'font_family_body' => '--tb-font-body',
            'base_font_size' => '--tb-base-font-size',
            'container_width' => '--tb-container',
            'border_radius' => '--tb-radius',
        ];
        $out = [];
        foreach ($attrs as $attr => $var) {
            $val = $this->{$attr} ?? ThemeSetting::defaults()[$attr] ?? null;
            if ($val !== null) {
                $out[$var] = $val;
            }
        }
        $out['--tb-button-style'] = $this->button_style ?? self::BUTTON_SOLID;
        $out['--tb-section-spacing'] = $this->section_spacing ?? 'md';
        return $out;
    }

    public static function clearCache(?int $siteId = null): void
    {
        Cache::forget('theme_settings_' . ($siteId ?? 'default'));
    }
}
