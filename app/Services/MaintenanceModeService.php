<?php

namespace App\Services;

use App\Models\Setting;
use App\Services\ThemeCanvas\ThemeRuntimeDocument;
use Illuminate\Http\Response;

/**
 * مدیریت حالت و صفحه تعمیرات (Maintenance Mode) مستقل از قالب (Zero Theme Dependency).
 */
class MaintenanceModeService
{
    public const SETTING_ENABLED     = 'maintenance_mode_enabled';
    public const SETTING_CANVAS_DATA = 'maintenance_canvas_data';

    public function isEnabled(): bool
    {
        return (string) Setting::get(self::SETTING_ENABLED, '0') === '1';
    }

    public function setEnabled(bool $enabled): void
    {
        Setting::set(self::SETTING_ENABLED, $enabled ? '1' : '0', 'general');
    }

    /**
     * سند پیش‌فرض تمیز و مستقل از قالب (RTL، بدون هدر و فوتر) برای صفحه تعمیرات.
     */
    public function defaultDocument(): array
    {
        return [
            'meta' => [
                'id'      => 'maintenance-standalone',
                'name'    => 'صفحه تعمیرات سایت',
                'version' => '1.0.0',
            ],
            'header_blocks' => [],
            'footer_blocks' => [],
            'global_styles' => [
                'primary_color'    => '#4f46e5',
                'secondary_color'  => '#7c3aed',
                'background_color' => '#f8fafc',
                'text_color'       => '#0f172a',
                'font_family'      => 'Vazirmatn',
                'direction'        => 'rtl',
            ],
            'pages' => [
                'maintenance' => [
                    'title'  => 'سایت در حال بروزرسانی و تعمیرات است',
                    'slug'   => 'maintenance',
                    'blocks' => [
                        [
                            'id'   => 'maint-hero-1',
                            'type' => 'hero',
                            'props' => [
                                'title'       => 'در حال بروزرسانی و بهبود سرویس هستیم',
                                'subtitle'    => 'سایت موقتاً در حالت تعمیرات قرار دارد و به‌زودی با امکانات جدید در دسترس خواهد بود. از شکیبایی شما سپاسگزاریم.',
                                'button_text' => '',
                                'button_url'  => '',
                                'background'  => 'light',
                                'align'       => 'center',
                            ],
                        ],
                    ],
                ],
            ],
            'settings' => [],
        ];
    }

    public function getDocument(): array
    {
        $raw = Setting::get(self::SETTING_CANVAS_DATA, '');
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded) && isset($decoded['pages']['maintenance'])) {
                $decoded['header_blocks'] = [];
                $decoded['footer_blocks'] = [];
                return $decoded;
            }
        }

        return $this->defaultDocument();
    }

    public function saveDocument(array $document): void
    {
        $document['header_blocks'] = [];
        $document['footer_blocks'] = [];
        Setting::set(
            self::SETTING_CANVAS_DATA,
            json_encode($document, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'general'
        );
    }

    /**
     * رندر کاملاً مستقل صفحه تعمیرات بدون هدر و بدون فوتر قالب با کد وضعیت ۵۰۳.
     */
    public function renderStandaloneResponse(?ThemeRuntimeDocument $runtimeDocument = null): Response
    {
        $doc = $this->getDocument();
        $page = $doc['pages']['maintenance'] ?? $this->defaultDocument()['pages']['maintenance'];
        $blocks = is_array($page['blocks'] ?? null) ? $page['blocks'] : [];
        $title = is_string($page['title'] ?? null) && $page['title'] !== ''
            ? $page['title']
            : 'سایت در حال تعمیرات است';

        $runtime = ($runtimeDocument ?? app(ThemeRuntimeDocument::class))
            ->forMode(ThemeRuntimeDocument::MODE_PUBLIC);

        $built = $runtime->build(
            themeData: $doc,
            blocks: $blocks,
            title: $title,
            pageKey: 'maintenance',
            renderChrome: false,
        );

        return response()
            ->view('layouts.canvas-theme', $built + [
                'themeName'       => 'maintenance-standalone',
                'omitChrome'      => true,
                'seoMeta'         => ['robots' => 'noindex, nofollow', 'description' => $title],
                'schema'          => [],
                'needsH1Fallback' => false,
            ], 503)
            ->header('Retry-After', '3600');
    }
}
