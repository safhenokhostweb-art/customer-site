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
                            'id'    => 'maint-badge-1',
                            'type'  => 'badge',
                            'props' => [
                                'text'  => '● در حال به‌روزرسانی زیرساخت و ارتقای تجربه کاربری',
                                'style' => 'primary',
                            ],
                            'style' => [
                                'textAlign'     => 'center',
                                'paddingTop'    => '32px',
                                'paddingBottom' => '8px',
                            ],
                        ],
                        [
                            'id'    => 'maint-hero-1',
                            'type'  => 'hero',
                            'props' => [
                                'title'       => 'در حال ارتقای سرویس هستیم؛ به‌زودی با تجربه‌ای سریع‌تر برمی‌گردیم',
                                'subtitle'    => 'تیم فنی ما در حال انجام به‌روزرسانی‌های زیرساختی و بهبود سرعت و امنیت سامانه است. تمام اطلاعات و سفارش‌های شما کاملاً محفوظ است.',
                                'button_text' => '',
                                'button_url'  => '',
                                'background'  => 'light',
                                'align'       => 'center',
                            ],
                        ],
                        [
                            'id'    => 'maint-timeline-1',
                            'type'  => 'text',
                            'props' => [
                                'content' => '<div dir="rtl" style="max-width:680px;margin:0 auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;padding:20px 24px;box-shadow:0 4px 20px rgba(15,23,42,0.05);">'
                                    . '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;font-weight:700;color:#1e293b;font-size:14px;">'
                                    . '<span>وضعیت پیشرفت به‌روزرسانی</span><span style="color:#4f46e5;">۷۵٪ تکمیل شده</span>'
                                    . '</div>'
                                    . '<div style="width:100%;height:10px;background:#e2e8f0;border-radius:999px;overflow:hidden;margin-bottom:12px;">'
                                    . '<div style="width:75%;height:100%;background:linear-gradient(90deg,#4f46e5,#7c3aed);border-radius:999px;"></div>'
                                    . '</div>'
                                    . '<p style="margin:0;color:#64748b;font-size:13px;line-height:1.8;">زمان تقریبی بازگشت سرویس: کمتر از ۳۰ دقیقه — از شکیبایی و همراهی شما سپاسگزاریم.</p>'
                                    . '</div>',
                            ],
                            'style' => [
                                'paddingTop'    => '8px',
                                'paddingBottom' => '24px',
                            ],
                        ],
                        [
                            'id'       => 'maint-support-grid',
                            'type'     => 'container',
                            'props'    => [
                                'columns' => 3,
                                'gap'     => '16px',
                            ],
                            'style'    => [
                                'maxWidth'      => '920px',
                                'marginLeft'    => 'auto',
                                'marginRight'   => 'auto',
                                'paddingBottom' => '40px',
                            ],
                            'children' => [
                                [
                                    'id'    => 'maint-contact-phone',
                                    'type'  => 'icon_box',
                                    'props' => [
                                        'icon'  => 'phone',
                                        'title' => 'پشتیبانی تلفنی',
                                        'text'  => 'پاسخگویی سریع در ساعات کاری — شماره تماس: ۰۲۱-۰۰۰۰۰۰۰۰',
                                        'link'  => 'tel:+982100000000',
                                        'align' => 'center',
                                        'style' => 'card',
                                    ],
                                ],
                                [
                                    'id'    => 'maint-contact-email',
                                    'type'  => 'icon_box',
                                    'props' => [
                                        'icon'  => 'mail',
                                        'title' => 'پست الکترونیک',
                                        'text'  => 'ارسال پیام به تیم پشتیبانی: support@example.ir',
                                        'link'  => 'mailto:support@example.ir',
                                        'align' => 'center',
                                        'style' => 'card',
                                    ],
                                ],
                                [
                                    'id'    => 'maint-contact-social',
                                    'type'  => 'icon_box',
                                    'props' => [
                                        'icon'  => 'message-circle',
                                        'title' => 'شبکه‌های اجتماعی',
                                        'text'  => 'پیگیری آخرین وضعیت سرویس در کانال رسمی ما',
                                        'link'  => '#',
                                        'align' => 'center',
                                        'style' => 'card',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'settings' => [],
        ];
    }

    public function resetToDefault(): array
    {
        $doc = $this->defaultDocument();
        $this->saveDocument($doc);
        return $doc;
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
