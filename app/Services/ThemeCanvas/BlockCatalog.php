<?php

namespace App\Services\ThemeCanvas;

/**
 * Block Library Catalog — Phase 11 follow-up (header-modern adoption).
 *
 * WHAT THIS IS
 *   A curated, opinionated catalog of ready-to-insert block presets for the
 *   Canvas "Add New Block" panel. Each preset bundles:
 *     - a REAL, already-registered block type (never a fake type with no
 *       renderer — see the builder-integrity audit rule: an insertable block
 *       must always render real output);
 *     - a standardized `fields` map exposing the three library-level edit
 *       kinds (`text`, `image_url`, `link`) and which real prop each maps to,
 *       so the Canvas inspector remains the single editing authority;
 *     - replaceable mock/default props the preset is inserted with.
 *
 * WHAT THIS IS NOT
 *   It is NOT a second block registry and NEVER bypasses
 *   App\Services\ThemeCanvas\BlockRegistry. Presets only re-skin existing
 *   types with better starting content; all validation, rendering, containers,
 *   and editor capabilities keep flowing through the canonical registry.
 *
 * INSERTION CONTRACT (see canvas-editor's addLibraryBlock())
 *   1. makeBlock(preset.block_type) — canonical id/type/defaults/containers;
 *   2. deep-merge preset.props over the type's own defaultProps;
 *   3. insert + select through the standard top-level path mechanism.
 */
final class BlockCatalog
{
    public const GROUP_HEADERS = 'headers';
    public const GROUP_FOOTERS = 'footers';
    public const GROUP_SECTIONS = 'sections';

    private static ?array $presets = null;

    /**
     * All catalog presets, keyed by stable preset key.
     *
     * @return array<string, array{label:string, group:string, category:string, icon:string, description:string, block_type:string, fields:array<string, array{prop:string, label:string, type:string}>, props:array}>
     */
    public static function all(): array
    {
        if (self::$presets !== null) {
            return self::$presets;
        }

        self::$presets = [
            // ─── HEADERS (3) ────────────────────────────────────────────────
            'header-minimal' => [
                'label' => 'هدر مینیمال',
                'group' => self::GROUP_HEADERS,
                'category' => 'utility',
                'icon' => '🧭',
                'description' => 'برند + منوی ساده، بدون دکمه و ابزار اضافه',
                'block_type' => 'nav_header',
                'fields' => [
                    'text' => ['prop' => 'logo_text', 'label' => 'نام برند', 'type' => 'text'],
                    'link' => ['prop' => 'logo_url', 'label' => 'لینک لوگو', 'type' => 'link'],
                ],
                'props' => [
                    'logo_text' => 'برند شما',
                    'logo_url' => '/',
                    'nav_links' => [
                        ['label' => 'خانه', 'url' => '/'],
                        ['label' => 'محصولات', 'url' => '/shop'],
                        ['label' => 'درباره ما', 'url' => '/about'],
                        ['label' => 'تماس', 'url' => '/contact'],
                    ],
                    'sticky' => false,
                    'bg_color' => '#ffffff',
                    'text_color' => '#111827',
                ],
            ],
            'header-corporate' => [
                'label' => 'هدر سازمانی',
                'group' => self::GROUP_HEADERS,
                'category' => 'utility',
                'icon' => '🏢',
                'description' => 'چسبان، با جستجو، سبد خرید و دکمه اقدام اصلی',
                'block_type' => 'nav_header',
                'fields' => [
                    'text' => ['prop' => 'logo_text', 'label' => 'نام برند', 'type' => 'text'],
                    'link' => ['prop' => 'cta_url', 'label' => 'لینک دکمه اقدام', 'type' => 'link'],
                ],
                'props' => [
                    'logo_text' => 'کسب‌وکار شما',
                    'logo_url' => '/',
                    'nav_links' => [
                        ['label' => 'خانه', 'url' => '/'],
                        ['label' => 'خدمات', 'url' => '/services'],
                        ['label' => 'تعرفه‌ها', 'url' => '/pricing'],
                        ['label' => 'وبلاگ', 'url' => '/blog'],
                        ['label' => 'تماس با ما', 'url' => '/contact'],
                    ],
                    'sticky' => true,
                    'bg_color' => '#ffffff',
                    'text_color' => '#111827',
                    'phone' => '۰۲۱-۱۲۳۴۵۶۷۸',
                    'cta_text' => 'مشاوره رایگان',
                    'cta_url' => '/contact',
                    'show_search' => true,
                    'search_placeholder' => 'جستجو در سایت...',
                    'show_cart' => true,
                    'show_account' => true,
                ],
            ],
            'header-modern' => [
                'label' => 'هدر مدرن RTL',
                'group' => self::GROUP_HEADERS,
                'category' => 'utility',
                'icon' => '✨',
                'description' => 'هدر مدرن فارسی با لوگو، منوی RTL و دکمه اقدام — بدون fast-path رنگ',
                'block_type' => 'header_modern_v1',
                'fields' => [
                    'text' => ['prop' => 'brandName', 'label' => 'نام برند', 'type' => 'text'],
                    'image_url' => ['prop' => 'logoUrl', 'label' => 'تصویر لوگو', 'type' => 'image_url'],
                    'link' => ['prop' => 'ctaUrl', 'label' => 'لینک دکمه اصلی', 'type' => 'link'],
                ],
                'props' => [
                    'bgColor' => '#ffffff',
                    'textColor' => '#172033',
                    'ctaColor' => '#4f46e5',
                    'logoUrl' => '',
                    'menuItems' => [
                        ['label' => 'خانه', 'url' => '/'],
                        ['label' => 'محصولات', 'url' => '/shop'],
                        ['label' => 'تخفیف‌ها', 'url' => '/sale'],
                        ['label' => 'تماس با ما', 'url' => '/contact'],
                    ],
                    'brandName' => 'نوین استور',
                    'homeUrl' => '/',
                    'ctaText' => 'همین حالا بخرید',
                    'ctaUrl' => '/shop',
                ],
            ],

            // ─── FOOTERS (2) ────────────────────────────────────────────────
            'footer-basic-social' => [
                'label' => 'فوتر ساده با شبکه‌های اجتماعی',
                'group' => self::GROUP_FOOTERS,
                'category' => 'utility',
                'icon' => '🦶',
                'description' => 'لوگو، شعار، حق نشر و شبکه‌های اجتماعی',
                'block_type' => 'nav_footer',
                'fields' => [
                    'text' => ['prop' => 'logo_text', 'label' => 'نام برند', 'type' => 'text'],
                    'text' => ['prop' => 'logo_text', 'label' => 'نام برند', 'type' => 'text'],
                ],
                'props' => [
                    'nav_source' => 'custom',
                    'columns' => [],
                    'links' => [],
                    'social_links' => [
                        ['label' => 'اینستاگرام', 'url' => 'https://instagram.com/'],
                        ['label' => 'تلگرام', 'url' => 'https://t.me/'],
                        ['label' => 'لینکدین', 'url' => 'https://linkedin.com/'],
                    ],
                    'logo_text' => 'برند شما',
                    'tagline' => 'همراه مطمئن کسب‌وکار آنلاین شما',
                    'copyright' => '© تمامی حقوق محفوظ است.',
                    'bg_color' => '#111827',
                    'text_color' => '#e5e7eb',
                ],
            ],
            'footer-detailed-grid' => [
                'label' => 'فوتر ستونی کامل',
                'group' => self::GROUP_FOOTERS,
                'category' => 'utility',
                'icon' => '🗂️',
                'description' => 'سه ستون لینک، لینک‌های حقوقی و شبکه‌های اجتماعی',
                'block_type' => 'nav_footer',
                'fields' => [
                    'text' => ['prop' => 'logo_text', 'label' => 'نام برند', 'type' => 'text'],
                ],
                'props' => [
                    'nav_source' => 'custom',
                    'columns' => [
                        [
                            'title' => 'فروشگاه',
                            'links' => [
                                ['label' => 'همه محصولات', 'url' => '/shop'],
                                ['label' => 'تخفیف‌ها', 'url' => '/sale'],
                                ['label' => 'پرفروش‌ترین‌ها', 'url' => '/bestsellers'],
                            ],
                        ],
                        [
                            'title' => 'پشتیبانی',
                            'links' => [
                                ['label' => 'سوالات متداول', 'url' => '/faq'],
                                ['label' => 'رویه ارسال', 'url' => '/shipping'],
                                ['label' => 'بازگشت کالا', 'url' => '/returns'],
                            ],
                        ],
                        [
                            'title' => 'درباره ما',
                            'links' => [
                                ['label' => 'داستان ما', 'url' => '/about'],
                                ['label' => 'فرصت‌های شغلی', 'url' => '/careers'],
                                ['label' => 'تماس با ما', 'url' => '/contact'],
                            ],
                        ],
                    ],
                    'links' => [
                        ['label' => 'قوانین و مقررات', 'url' => '/terms'],
                        ['label' => 'حریم خصوصی', 'url' => '/privacy'],
                    ],
                    'social_links' => [
                        ['label' => 'اینستاگرام', 'url' => 'https://instagram.com/'],
                        ['label' => 'تلگرام', 'url' => 'https://t.me/'],
                        ['label' => 'واتساپ', 'url' => 'https://wa.me/'],
                    ],
                    'logo_text' => 'برند شما',
                    'tagline' => '',
                    'copyright' => '© تمامی حقوق محفوظ است.',
                    'bg_color' => '#0f172a',
                    'text_color' => '#e2e8f0',
                ],
            ],

            // ─── BUSINESS SECTIONS (3) ─────────────────────────────────────
            'hero-with-cta' => [
                'label' => 'هیرو با دکمه اقدام',
                'group' => self::GROUP_SECTIONS,
                'category' => 'layout',
                'icon' => '🌟',
                'description' => 'عنوان، زیرعنوان، دکمه و تصویر پس‌زمینه قابل تعویض',
                'block_type' => 'hero',
                'fields' => [
                    'text' => ['prop' => 'title', 'label' => 'عنوان اصلی', 'type' => 'text'],
                    'image_url' => ['prop' => 'background_image', 'label' => 'تصویر پس‌زمینه', 'type' => 'image_url'],
                    'link' => ['prop' => 'button_url', 'label' => 'لینک دکمه', 'type' => 'link'],
                ],
                'props' => [
                    'title' => 'کسب‌وکار خود را آنلاین کنید',
                    'subtitle' => 'از ایده تا فروش، همه‌چیز در یک پلتفرم یکپارچه',
                    'button_text' => 'شروع رایگان',
                    'button_url' => '/register',
                    'background' => 'primary',
                    'background_image' => '',
                    'align' => 'center',
                ],
            ],
            'features-grid' => [
                'label' => 'گرید ویژگی‌ها',
                'group' => self::GROUP_SECTIONS,
                'category' => 'content',
                'icon' => '⚡',
                'description' => 'سه مزیت رقابتی با آیکون، عنوان و توضیح',
                'block_type' => 'features_grid',
                'fields' => [
                    'text' => ['prop' => 'title', 'label' => 'عنوان بخش', 'type' => 'text'],
                ],
                'props' => [
                    'title' => 'چرا ما را انتخاب کنید؟',
                    'subtitle' => 'سه دلیل برای اعتماد به ما',
                    'columns' => '3',
                    'style' => 'cards',
                    'features' => [
                        ['icon' => '🚀', 'title' => 'سرعت بالا', 'text' => 'تحویل سفارش‌ها در کمترین زمان ممکن'],
                        ['icon' => '🔒', 'title' => 'پرداخت امن', 'text' => 'درگاه‌های معتبر با رمزنگاری کامل'],
                        ['icon' => '🎧', 'title' => 'پشتیبانی واقعی', 'text' => 'پاسخگویی انسانی، هر روز هفته'],
                    ],
                ],
            ],
            'testimonial-slider' => [
                'label' => 'اسلایدر نظرات مشتریان',
                'group' => self::GROUP_SECTIONS,
                'category' => 'dynamic',
                'icon' => '💬',
                'description' => 'نظرات مشتریان با نام، سمت، متن و امتیاز',
                'block_type' => 'testimonials_slider',
                'fields' => [
                    'text' => ['prop' => 'title', 'label' => 'عنوان بخش', 'type' => 'text'],
                ],
                'props' => [
                    'title' => 'مشتریان ما چه می‌گویند',
                    'columns' => '3',
                    'mode' => 'carousel',
                    'autoplay' => true,
                    'interval' => 6000,
                    'items' => [
                        ['name' => 'سارا محمدی', 'role' => 'مدیر فروشگاه آنلاین', 'text' => 'فروش ما طی سه ماه دو برابر شد؛ تجربه‌ای واقعاً حرفه‌ای.', 'rating' => 5],
                        ['name' => 'امیر رضایی', 'role' => 'بنیان‌گذار استارتاپ', 'text' => 'راه‌اندازی فروشگاه فقط یک روز طول کشید!', 'rating' => 5],
                        ['name' => 'نگار کریمی', 'role' => 'مدیر بازاریابی', 'text' => 'پشتیبانی سریع و طراحی بی‌نقص؛ کاملاً پیشنهاد می‌شود.', 'rating' => 4],
                    ],
                ],
            ],
        ];

        return self::$presets;
    }

    /**
     * One preset by its stable catalog key; null when unknown.
     */
    public static function get(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }

    /**
     * Presets grouped for the palette: headers, footers, sections.
     *
     * @return array<string, array<string, array>>
     */
    public static function byGroup(): array
    {
        $groups = [
            self::GROUP_HEADERS => ['label' => 'هدرها', 'icon' => '🧭', 'items' => []],
            self::GROUP_FOOTERS => ['label' => 'فوترها', 'icon' => '🦶', 'items' => []],
            self::GROUP_SECTIONS => ['label' => 'سکشن‌های تجاری', 'icon' => '🌟', 'items' => []],
        ];

        foreach (self::all() as $key => $preset) {
            $groups[$preset['group']]['items'][$key] = $preset;
        }

        return $groups;
    }
}
