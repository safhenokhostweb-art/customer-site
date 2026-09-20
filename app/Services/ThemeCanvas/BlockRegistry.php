<?php

namespace App\Services\ThemeCanvas;

final class BlockRegistry
{
    private static ?array $blocks = null;

    public const CATEGORY_LAYOUT    = 'layout';
    public const CATEGORY_CONTENT   = 'content';
    public const CATEGORY_DYNAMIC   = 'dynamic';
    public const CATEGORY_ECOMMERCE = 'ecommerce';
    public const CATEGORY_MARKETING = 'marketing';
    public const CATEGORY_UTILITY   = 'utility';
    public const CATEGORY_MODULE    = 'module';

    public static function all(): array
    {
        // Phase 3F-C: only the local+canonical portion is memoized — see
        // the merge at the end of this method for why the extension
        // portion deliberately is not.
        if (self::$blocks !== null) {
            return self::$blocks + \App\Builder\Extension\CanvasBuilderAdapter::palette();
        }

        self::$blocks = [
            // ─── LAYOUT ─────────────────────────────────────────────────────
            'hero' => [
                'name'         => 'بخش قهرمان (Hero)',
                'category'     => self::CATEGORY_LAYOUT,
                'schema'       => [
                    'title'            => ['type' => 'string',  'label' => 'عنوان اصلی',   'default' => ''],
                    'subtitle'         => ['type' => 'string',  'label' => 'زیرعنوان',      'default' => ''],
                    'button_text'      => ['type' => 'string',  'label' => 'متن دکمه',       'default' => ''],
                    // TASK 3/3 Phase 6.5C (BLD-28) — see the matching
                    // shared-package registry entry for the rationale.
                    'button_url'       => ['type' => 'link',    'label' => 'لینک دکمه',      'default' => ''],
                    'background'       => ['type' => 'string',  'label' => 'پس‌زمینه',       'default' => 'primary'],
                    'background_image' => ['type' => 'image',   'label' => 'تصویر پس‌زمینه', 'default' => ''],
                    'align'            => ['type' => 'string',  'label' => 'تراز',           'default' => 'center'],
                ],
                'defaultProps' => ['title' => '', 'subtitle' => '', 'button_text' => '', 'button_url' => '', 'background' => 'primary', 'background_image' => '', 'align' => 'center'],
            ],
            'section' => [
                'name'     => 'بخش (Section)',
                'category' => self::CATEGORY_LAYOUT,
                'schema'   => [
                    'padding'          => ['type' => 'string',  'label' => 'فاصله داخلی',    'default' => 'md'],
                    'background'       => ['type' => 'string',  'label' => 'رنگ پس‌زمینه',   'default' => 'transparent'],
                    'background_image' => ['type' => 'image',   'label' => 'تصویر پس‌زمینه', 'default' => ''],
                ],
                'defaultProps' => ['padding' => 'md', 'background' => 'transparent'],
            ],
            'grid' => [
                'name'     => 'گرید',
                'category' => self::CATEGORY_LAYOUT,
                'schema'   => [
                    'columns' => ['type' => 'number', 'label' => 'تعداد ستون', 'default' => 3],
                    'gap'     => ['type' => 'string', 'label' => 'فاصله',      'default' => 'md'],
                ],
                'defaultProps' => ['columns' => 3, 'gap' => 'md'],
            ],
            'columns' => [
                'name'     => 'ستون‌ها',
                'category' => self::CATEGORY_LAYOUT,
                'schema'   => [
                    'ratios' => ['type' => 'string', 'label' => 'نسبت ستون‌ها', 'default' => '1/2,1/2'],
                    'gap'    => ['type' => 'string', 'label' => 'فاصله',        'default' => 'md'],
                ],
                'defaultProps' => ['ratios' => '1/2,1/2', 'gap' => 'md'],
            ],
            'accordion' => [
                'name'     => 'آکوردئون',
                'category' => self::CATEGORY_LAYOUT,
                'schema'   => [
                    'title' => ['type' => 'string', 'label' => 'عنوان', 'default' => ''],
                ],
                'defaultProps' => ['title' => ''],
            ],
            // Universal Multipurpose Slider Overhaul: 'transition'/'boxed'/
            // 'aspect_ratio'/'overlay' and the slide-level 'cta_text' are
            // new, additive fields matching the shared BlockRegistry
            // (packages/saas-platform/modules/theme-runtime) and
            // BlockRenderer::renderSlider() — all default to the exact
            // pre-existing visual behavior, so content authored before
            // this change renders unchanged.
            'slider' => [
                'name'     => 'اسلایدر تصویری',
                'category' => self::CATEGORY_LAYOUT,
                'schema'   => [
                    'autoplay' => ['type' => 'boolean', 'label' => 'پخش خودکار', 'default' => true],
                    'interval' => ['type' => 'number',  'label' => 'فاصله (ms)',  'default' => 5000],
                    'transition' => ['type' => 'select', 'label' => 'نوع گذار', 'options' => ['slide' => 'اسلاید', 'fade' => 'محو شدن'], 'default' => 'slide'],
                    'boxed' => ['type' => 'boolean', 'label' => 'حالت جعبه‌ای (Boxed)', 'default' => false],
                    'aspect_ratio' => ['type' => 'select', 'label' => 'نسبت تصویر', 'options' => ['16/9' => '16:9', '21/9' => '21:9', '4/3' => '4:3', '1/1' => '1:1'], 'default' => '16/9'],
                    'overlay' => ['type' => 'boolean', 'label' => 'گرادیان روی تصویر', 'default' => true],
                    // Builder integrity fix (2026-09-13): renderSlider()
                    // (ThemeCanvasRenderer) always expected slides shaped
                    // {image,title,subtitle,link} — this field was simply
                    // never declared here, so the Canvas editor had no
                    // control to author them at all. Same repeater shape
                    // as 'gallery.items' above (fields/storage=array), the
                    // Canvas's generic repeater adapter already renders it.
                    'slides' => [
                        'type' => 'repeater',
                        'label' => 'اسلایدها',
                        'storage' => 'array',
                        'default' => [],
                        'fields' => [
                            'image'    => ['type' => 'image',  'label' => 'تصویر اسلاید', 'default' => ''],
                            'title'    => ['type' => 'string', 'label' => 'عنوان',        'default' => ''],
                            'subtitle' => ['type' => 'string', 'label' => 'زیرعنوان',      'default' => ''],
                            'link'     => ['type' => 'string', 'label' => 'لینک (اختیاری)', 'default' => ''],
                            'cta_text' => ['type' => 'string', 'label' => 'متن دکمه CTA (اختیاری)', 'default' => ''],
                        ],
                    ],
                ],
                'defaultProps' => [
                    'autoplay' => true,
                    'interval' => 5000,
                    'transition' => 'slide',
                    'boxed' => false,
                    'aspect_ratio' => '16/9',
                    'overlay' => true,
                    'slides' => [
                        ['image' => '', 'title' => 'عنوان اسلاید ۱', 'subtitle' => '', 'link' => '', 'cta_text' => ''],
                        ['image' => '', 'title' => 'عنوان اسلاید ۲', 'subtitle' => '', 'link' => '', 'cta_text' => ''],
                    ],
                ],
            ],

            // ─── CONTENT ─────────────────────────────────────────────────────
            'heading' => [
                'name'     => 'عنوان',
                'category' => self::CATEGORY_CONTENT,
                'schema'   => [
                    'text'  => ['type' => 'string', 'label' => 'متن عنوان', 'default' => ''],
                    'level' => ['type' => 'select', 'label' => 'سطح (H)', 'options' => ['1' => 'H1', '2' => 'H2', '3' => 'H3', '4' => 'H4'], 'default' => '2'],
                ],
                'defaultProps' => ['text' => '', 'level' => '2'],
            ],
            'text' => [
                'name'     => 'متن',
                'category' => self::CATEGORY_CONTENT,
                'schema'   => [
                    'content' => ['type' => 'textarea', 'label' => 'محتوا', 'default' => ''],
                ],
                'defaultProps' => ['content' => ''],
            ],
            'image' => [
                'name'     => 'تصویر',
                'category' => self::CATEGORY_CONTENT,
                'schema'   => [
                    'url'     => ['type' => 'image', 'label' => 'آدرس تصویر', 'default' => ''],
                    'alt'     => ['type' => 'string', 'label' => 'توضیح تصویر (alt)', 'default' => ''],
                    'caption' => ['type' => 'string', 'label' => 'کپشن', 'default' => ''],
                    'align'   => ['type' => 'select', 'label' => 'تراز', 'options' => ['right' => 'راست', 'center' => 'وسط', 'left' => 'چپ'], 'default' => 'center'],
                ],
                'defaultProps' => ['url' => '', 'alt' => '', 'caption' => '', 'align' => 'center'],
            ],
            'gallery' => [
                'name'     => 'گالری تصاویر',
                'category' => self::CATEGORY_CONTENT,
                'schema'   => [
                    'title'   => ['type' => 'string', 'label' => 'عنوان گالری', 'default' => ''],
                    'columns' => ['type' => 'select', 'label' => 'ستون', 'options' => ['2' => '۲', '3' => '۳', '4' => '۴'], 'default' => '3'],
                    // Canonical renderer contract is items: [{url, alt}], not
                    // the old host-only `images` textarea. `storage=array`
                    // preserves that exact runtime shape while allowing the
                    // Canvas's generic repeater adapter to host the existing
                    // canonical media picker for each item.
                    'items' => [
                        'type' => 'repeater',
                        'label' => 'تصاویر گالری',
                        'storage' => 'array',
                        'default' => [],
                        'fields' => [
                            'url' => ['type' => 'image', 'label' => 'تصویر', 'default' => ''],
                            'alt' => ['type' => 'string', 'label' => 'متن جایگزین', 'default' => ''],
                        ],
                    ],
                ],
                'defaultProps' => ['title' => '', 'columns' => '3', 'items' => []],
            ],
            'video' => [
                'name'     => 'ویدیو',
                'category' => self::CATEGORY_CONTENT,
                'schema'   => [
                    // Builder integrity audit (2026-09-11): this block had a
                    // schema field but zero renderer anywhere — registered,
                    // insertable, always rendered empty. Now resolved
                    // through the same canonical customer-media path as
                    // every other media-capable prop (CustomerMediaResolver
                    // + type:'image' marker — see its isMediaControl() doc),
                    // not a new media identity/lookup of its own.
                    'url'     => ['type' => 'image', 'label' => 'فایل ویدیو', 'default' => ''],
                    'poster'  => ['type' => 'image', 'label' => 'تصویر پیش‌نمایش', 'default' => ''],
                    'caption' => ['type' => 'string', 'label' => 'کپشن', 'default' => ''],
                    'autoplay'=> ['type' => 'boolean', 'label' => 'پخش خودکار (بی‌صدا)', 'default' => false],
                    'loop'    => ['type' => 'boolean', 'label' => 'تکرار', 'default' => false],
                    'controls'=> ['type' => 'boolean', 'label' => 'نمایش کنترل‌ها', 'default' => true],
                ],
                'defaultProps' => ['url' => '', 'poster' => '', 'caption' => '', 'autoplay' => false, 'loop' => false, 'controls' => true],
            ],
            'button' => [
                'name'     => 'دکمه',
                'category' => self::CATEGORY_CONTENT,
                'schema'   => [
                    'text'  => ['type' => 'string', 'label' => 'متن دکمه', 'default' => ''],
                    'url'   => ['type' => 'link', 'label' => 'لینک',     'default' => '#'],
                    'style' => ['type' => 'select', 'label' => 'استایل', 'options' => ['primary' => 'اصلی', 'secondary' => 'ثانویه', 'outline' => 'خطی'], 'default' => 'primary'],
                ],
                'defaultProps' => ['text' => '', 'url' => '#', 'style' => 'primary'],
            ],
            'divider' => [
                'name'         => 'جداکننده',
                'category'     => self::CATEGORY_CONTENT,
                'schema'       => [],
                'defaultProps' => [],
            ],
            'counter' => [
                'name'     => 'شمارنده',
                'category' => self::CATEGORY_CONTENT,
                'schema'   => [
                    'value'  => ['type' => 'number', 'label' => 'عدد',    'default' => 0],
                    'suffix' => ['type' => 'string', 'label' => 'پسوند',  'default' => '+'],
                    'label'  => ['type' => 'string', 'label' => 'برچسب', 'default' => ''],
                ],
                'defaultProps' => ['value' => 0, 'suffix' => '+', 'label' => ''],
            ],

            // ─── DYNAMIC ─────────────────────────────────────────────────────
            // Universal Multipurpose Slider Overhaul: 'slider' is a new
            // third 'layout' option (real carousel via the shared
            // BlockRenderer::renderCarousel(), see App\Services\
            // ThemeBuilder\BlockRenderer::renderBlogPosts()); 'source'/
            // 'category_id'/'manual_ids'/'autoplay'/'interval' are new and
            // additive. Existing content with no 'source' key keeps its
            // 'latest' behavior unchanged.
            'blog_posts' => [
                'name'     => 'پست‌های وبلاگ',
                'category' => self::CATEGORY_DYNAMIC,
                'schema'   => [
                    'title'   => ['type' => 'string', 'label' => 'عنوان بخش', 'default' => 'آخرین مطالب'],
                    'limit'   => ['type' => 'number', 'label' => 'تعداد پست', 'default' => 6],
                    'layout'  => ['type' => 'select', 'label' => 'چیدمان', 'options' => ['grid' => 'گرید', 'list' => 'لیست', 'slider' => 'اسلایدر'], 'default' => 'grid'],
                    'source'  => ['type' => 'select', 'label' => 'منبع', 'options' => ['latest' => 'جدیدترین', 'category' => 'یک دسته‌بندی خاص', 'manual' => 'انتخاب دستی'], 'default' => 'latest'],
                    'category_id' => ['type' => 'number', 'label' => 'شناسه دسته‌بندی (در حالت دسته‌بندی)', 'default' => null],
                    'manual_ids' => ['type' => 'array', 'label' => 'شناسه مقالات (در حالت انتخاب دستی)', 'default' => []],
                    'autoplay' => ['type' => 'boolean', 'label' => 'پخش خودکار (در حالت اسلایدر)', 'default' => true],
                    'interval' => ['type' => 'number', 'label' => 'فاصله زمانی (میلی‌ثانیه)', 'default' => 5000],
                ],
                'defaultProps' => ['title' => 'آخرین مطالب', 'limit' => 6, 'layout' => 'grid', 'source' => 'latest', 'autoplay' => true, 'interval' => 5000],
            ],
            'testimonials' => [
                'name'     => 'نظرات مشتریان',
                'category' => self::CATEGORY_DYNAMIC,
                'schema'   => [
                    'title' => ['type' => 'string', 'label' => 'عنوان', 'default' => 'نظرات'],
                    'limit' => ['type' => 'number', 'label' => 'تعداد', 'default' => 5],
                ],
                'defaultProps' => ['title' => 'نظرات', 'limit' => 5],
            ],

            // ─── ECOMMERCE ───────────────────────────────────────────────────
            'product_grid' => [
                'name'     => 'گرید محصولات',
                'category' => self::CATEGORY_ECOMMERCE,
                'schema'   => [
                    'title'   => ['type' => 'string', 'label' => 'عنوان بخش', 'default' => 'محصولات'],
                    'columns' => ['type' => 'number', 'label' => 'ستون',      'default' => 4],
                    'limit'   => ['type' => 'number', 'label' => 'تعداد',     'default' => 8],
                ],
                'defaultProps' => ['title' => 'محصولات', 'columns' => 4, 'limit' => 8],
            ],
            // Universal Multipurpose Slider Overhaul: 'columns'/'source'/
            // 'category_id'/'manual_ids' are new and additive — matching
            // ThemeCanvasRenderer::renderProductSlider()'s real query
            // options (latest/new/category/manual) and per-breakpoint
            // column count. No 'source' key → 'latest' (unchanged prior
            // behavior).
            'product_slider' => [
                'name'         => 'اسلایدر محصولات',
                'category'     => self::CATEGORY_ECOMMERCE,
                // Builder integrity audit (2026-09-11): registered,
                // insertable, no dedicated renderer anywhere (fell to the
                // silent generic fallback). Now real — see
                // ThemeCanvasRenderer::renderProductSlider(), reusing the
                // same live Product query as renderModuleProductGrid() and
                // the shared carousel runtime.
                'schema'       => [
                    'title'    => ['type' => 'string',  'label' => 'عنوان', 'default' => 'محصولات منتخب'],
                    'limit'    => ['type' => 'number',  'label' => 'تعداد', 'default' => 6],
                    'columns'  => ['type' => 'select', 'label' => 'تعداد ستون (دسکتاپ)', 'options' => ['2' => '۲', '3' => '۳', '4' => '۴'], 'default' => '3'],
                    'source'   => ['type' => 'select', 'label' => 'منبع محصولات', 'options' => ['latest' => 'جدیدترین', 'new' => 'دارای برچسب جدید', 'category' => 'یک دسته‌بندی خاص', 'manual' => 'انتخاب دستی'], 'default' => 'latest'],
                    'category_id' => ['type' => 'number', 'label' => 'شناسه دسته‌بندی (در حالت دسته‌بندی)', 'default' => null],
                    'manual_ids' => ['type' => 'array', 'label' => 'شناسه محصولات (در حالت انتخاب دستی)', 'default' => []],
                    'autoplay' => ['type' => 'boolean', 'label' => 'پخش خودکار', 'default' => true],
                    'interval' => ['type' => 'number',  'label' => 'فاصله (ms)',  'default' => 5000],
                ],
                'defaultProps' => ['title' => 'محصولات منتخب', 'limit' => 6, 'columns' => '3', 'source' => 'latest', 'autoplay' => true, 'interval' => 5000],
            ],
            // Universal Multipurpose Slider Overhaul: new type — mirrors
            // the shared BlockRegistry entry (packages/saas-platform/
            // modules/theme-runtime); real data via ThemeCanvasRenderer::
            // renderCategorySlider() (ProductCategory + real product
            // counts).
            'category_slider' => [
                'name' => 'اسلایدر دسته‌بندی',
                'category' => self::CATEGORY_ECOMMERCE,
                'schema' => [
                    'title'    => ['type' => 'string', 'label' => 'عنوان بخش', 'default' => 'دسته‌بندی‌ها'],
                    'layout'   => ['type' => 'select', 'label' => 'چیدمان', 'options' => ['carousel' => 'اسلایدر', 'grid' => 'گرید ثابت'], 'default' => 'carousel'],
                    'columns'  => ['type' => 'select', 'label' => 'تعداد ستون', 'options' => ['2' => '۲', '3' => '۳', '4' => '۴', '6' => '۶'], 'default' => '4'],
                    'autoplay' => ['type' => 'boolean', 'label' => 'پخش خودکار', 'default' => true],
                    'interval' => ['type' => 'number', 'label' => 'فاصله زمانی (میلی‌ثانیه)', 'default' => 5000],
                    'source'   => ['type' => 'select', 'label' => 'منبع', 'options' => ['all' => 'همه دسته‌بندی‌ها', 'manual' => 'انتخاب دستی'], 'default' => 'all'],
                    'manual_ids' => ['type' => 'array', 'label' => 'شناسه دسته‌بندی‌ها (در حالت انتخاب دستی)', 'default' => []],
                ],
                'defaultProps' => ['title' => 'دسته‌بندی‌ها', 'layout' => 'carousel', 'columns' => '4', 'autoplay' => true, 'interval' => 5000, 'source' => 'all'],
            ],

            // ─── MARKETING ───────────────────────────────────────────────────
            'cta' => [
                'name'     => 'دعوت به اقدام (CTA)',
                'category' => self::CATEGORY_MARKETING,
                'schema'   => [
                    'title'       => ['type' => 'string', 'label' => 'عنوان',      'default' => ''],
                    'subtitle'    => ['type' => 'string', 'label' => 'زیرعنوان',  'default' => ''],
                    'button_text' => ['type' => 'string', 'label' => 'متن دکمه',   'default' => ''],
                    'button_url'  => ['type' => 'link',   'label' => 'لینک دکمه', 'default' => ''],
                    'background'  => ['type' => 'color',  'label' => 'رنگ',        'default' => '#4f46e5'],
                ],
                'defaultProps' => ['title' => '', 'subtitle' => '', 'button_text' => '', 'button_url' => '', 'background' => '#4f46e5'],
            ],
            'countdown' => [
                'name'     => 'شمارش معکوس',
                'category' => self::CATEGORY_MARKETING,
                'schema'   => [
                    'end_date' => ['type' => 'string', 'label' => 'تاریخ پایان (Y-m-d)', 'default' => ''],
                    'label'    => ['type' => 'string', 'label' => 'برچسب',               'default' => 'پایان پیشنهاد'],
                ],
                'defaultProps' => ['end_date' => '', 'label' => 'پایان پیشنهاد'],
            ],
            'promo_banner' => [
                'name'     => 'بنر تبلیغاتی',
                'category' => self::CATEGORY_MARKETING,
                'schema'   => [
                    'text'  => ['type' => 'string', 'label' => 'متن',   'default' => ''],
                    'link'  => ['type' => 'string', 'label' => 'لینک',  'default' => ''],
                    'style' => ['type' => 'select', 'label' => 'استایل', 'options' => ['info' => 'اطلاعاتی', 'success' => 'موفقیت', 'warning' => 'هشدار', 'danger' => 'خطر'], 'default' => 'info'],
                ],
                'defaultProps' => ['text' => '', 'link' => '', 'style' => 'info'],
            ],

            // ─── UTILITY ────────────────────────────────────────────────────
            'search_bar' => [
                'name'     => 'جستجو',
                'category' => self::CATEGORY_UTILITY,
                'schema'   => [
                    'placeholder' => ['type' => 'string', 'label' => 'راهنما', 'default' => 'جستجو...'],
                ],
                'defaultProps' => ['placeholder' => 'جستجو...'],
            ],
            'nav_menu' => [
                'name'     => 'منوی ناوبری',
                'category' => self::CATEGORY_UTILITY,
                'schema'   => [
                    'location' => ['type' => 'select', 'label' => 'موقعیت', 'options' => ['header' => 'هدر', 'footer' => 'فوتر', 'sidebar' => 'کنار'], 'default' => 'header'],
                ],
                'defaultProps' => ['location' => 'header'],
            ],
            'social_icons' => [
                'name'     => 'شبکه‌های اجتماعی',
                'category' => self::CATEGORY_UTILITY,
                'schema'   => [
                    'style' => ['type' => 'select', 'label' => 'استایل', 'options' => ['icons' => 'آیکون', 'buttons' => 'دکمه', 'text' => 'متن'], 'default' => 'icons'],
                ],
                'defaultProps' => ['style' => 'icons'],
            ],
            'login_register' => [
                'name'         => 'ورود / ثبت‌نام',
                'category'     => self::CATEGORY_UTILITY,
                'schema'       => [],
                'defaultProps' => [],
            ],

            // ─── MODULE ─────────────────────────────────────────────────────
            'module:blog_posts' => [
                'name'     => 'پست‌های وبلاگ',
                'category' => self::CATEGORY_MODULE,
                'requires' => 'blog',
                'schema'   => [
                    'title'        => ['type' => 'string',  'label' => 'عنوان بخش',              'default' => 'آخرین مطالب'],
                    'limit'        => ['type' => 'number',  'label' => 'تعداد پست',              'default' => 6],
                    'layout'       => ['type' => 'select',  'label' => 'چیدمان',                  'options' => ['grid' => 'گرید', 'list' => 'لیست', 'slider' => 'اسلایدر'], 'default' => 'grid'],
                    'show_excerpt' => ['type' => 'boolean', 'label' => 'نمایش خلاصه',            'default' => true],
                    'show_date'    => ['type' => 'boolean', 'label' => 'نمایش تاریخ',            'default' => true],
                    'category_slug'=> ['type' => 'string',  'label' => 'فیلتر دسته‌بندی',        'default' => ''],
                ],
                'defaultProps' => ['title' => 'آخرین مطالب', 'limit' => 6, 'layout' => 'grid', 'show_excerpt' => true, 'show_date' => true, 'category_slug' => ''],
            ],
            'module:shop_product_grid' => [
                'name'     => 'گرید محصولات فروشگاه',
                'category' => self::CATEGORY_MODULE,
                'requires' => 'shop',
                'schema'   => [
                    'title'         => ['type' => 'string', 'label' => 'عنوان بخش',    'default' => 'محصولات'],
                    'limit'         => ['type' => 'number', 'label' => 'تعداد محصول',  'default' => 8],
                    'columns'       => ['type' => 'select', 'label' => 'ستون',          'options' => ['2' => '۲', '3' => '۳', '4' => '۴'], 'default' => '4'],
                    // 'bestselling' removed 2026-09-11: no sales-count tracking
                    // exists on Product to back it honestly (see 'category_slug'
                    // below, which IS backed by Product::category()).
                    'source'        => ['type' => 'select', 'label' => 'منبع',          'options' => ['recent' => 'جدیدترین', 'featured' => 'ویژه'], 'default' => 'recent'],
                    'category_slug' => ['type' => 'string', 'label' => 'دسته‌بندی (slug)', 'default' => ''],
                ],
                'defaultProps' => ['title' => 'محصولات', 'limit' => 8, 'columns' => '4', 'source' => 'recent', 'category_slug' => ''],
            ],
            'module:courses_grid' => [
                'name'     => 'گرید دوره‌ها',
                'category' => self::CATEGORY_MODULE,
                'requires' => 'courses',
                'schema'   => [
                    'title'            => ['type' => 'string',  'label' => 'عنوان بخش',  'default' => 'دوره‌های آموزشی'],
                    'limit'            => ['type' => 'number',  'label' => 'تعداد دوره', 'default' => 6],
                    'columns'          => ['type' => 'select',  'label' => 'ستون',        'options' => ['2' => '۲', '3' => '۳', '4' => '۴'], 'default' => '3'],
                    'layout'           => ['type' => 'select',  'label' => 'چیدمان',      'options' => ['grid' => 'گرید', 'list' => 'لیست'], 'default' => 'grid'],
                    'show_price'       => ['type' => 'boolean', 'label' => 'نمایش قیمت', 'default' => true],
                    // 'show_instructor' removed 2026-09-11: Course has no
                    // instructor field/relation on this host — fabricating an
                    // instructor name is not an option (Constitution §11).
                ],
                'defaultProps' => ['title' => 'دوره‌های آموزشی', 'limit' => 6, 'columns' => '3', 'layout' => 'grid', 'show_price' => true],
            ],
            'module:pricing_table' => [
                'name'     => 'جدول اشتراک / تعرفه',
                'category' => self::CATEGORY_MODULE,
                'requires' => 'subscription',
                'schema'   => [
                    'title'               => ['type' => 'string',  'label' => 'عنوان بخش',           'default' => 'پلن‌های اشتراک'],
                    'subtitle'            => ['type' => 'string',  'label' => 'زیرعنوان',            'default' => 'پلنی که مناسب شماست را انتخاب کنید'],
                    // 'highlight_plan' / 'show_annual_toggle' removed
                    // 2026-09-11: real plan-highlighting/annual-billing logic
                    // is a commercial/pricing change requiring its own owner
                    // sign-off (Governance §2, Constitution §9) — out of
                    // scope for a Builder-integrity pass. This block links to
                    // the real pricing page rather than faking a toggle.
                ],
                'defaultProps' => ['title' => 'پلن‌های اشتراک', 'subtitle' => 'پلنی که مناسب شماست را انتخاب کنید'],
            ],
            'module:contact_quick' => [
                'name'     => 'فرم تماس سریع',
                'category' => self::CATEGORY_MODULE,
                'requires' => null,
                'schema'   => [
                    'title'        => ['type' => 'string',  'label' => 'عنوان',         'default' => 'تماس با ما'],
                    'show_phone'   => ['type' => 'boolean', 'label' => 'نمایش تلفن',    'default' => true],
                    'show_address' => ['type' => 'boolean', 'label' => 'نمایش آدرس',    'default' => true],
                    'show_map'     => ['type' => 'boolean', 'label' => 'نمایش نقشه',    'default' => false],
                ],
                'defaultProps' => ['title' => 'تماس با ما', 'show_phone' => true, 'show_address' => true, 'show_map' => false],
            ],
            'module:faq_dynamic' => [
                'name'     => 'سوالات متداول پویا',
                'category' => self::CATEGORY_MODULE,
                'requires' => null,
                'schema'   => [
                    'title' => ['type' => 'string', 'label' => 'عنوان', 'default' => 'سوالات متداول'],
                    // 'items' added 2026-09-11: the renderer always read
                    // $props['items'], but no schema field ever exposed it in
                    // the editor — the block could never actually be
                    // populated through the UI. Real repeater now closes
                    // that gap; 'limit'/'style' (already declared) are now
                    // actually consumed by the renderer too.
                    'items' => [
                        'type' => 'repeater',
                        'label' => 'سوالات',
                        'storage' => 'array',
                        'default' => [],
                        'fields' => [
                            'question' => ['type' => 'string', 'label' => 'سوال', 'default' => ''],
                            'answer'   => ['type' => 'textarea', 'label' => 'پاسخ', 'default' => ''],
                        ],
                    ],
                    'limit' => ['type' => 'number', 'label' => 'تعداد', 'default' => 10],
                    'style' => ['type' => 'select', 'label' => 'استایل', 'options' => ['accordion' => 'آکوردئون', 'list' => 'لیست'], 'default' => 'accordion'],
                ],
                'defaultProps' => ['title' => 'سوالات متداول', 'items' => [], 'limit' => 10, 'style' => 'accordion'],
            ],
            'module:support_button' => [
                'name'     => 'دکمه پشتیبانی',
                'category' => self::CATEGORY_MODULE,
                'requires' => null,
                'schema'   => [
                    'label'    => ['type' => 'string', 'label' => 'متن دکمه',  'default' => 'پشتیبانی آنلاین'],
                    'style'    => ['type' => 'select', 'label' => 'استایل',    'options' => ['button' => 'دکمه ثابت', 'floating' => 'شناور'], 'default' => 'floating'],
                    'position' => ['type' => 'select', 'label' => 'موقعیت',   'options' => ['bottom-right' => 'پایین راست', 'bottom-left' => 'پایین چپ'], 'default' => 'bottom-right'],
                    'bg_color' => ['type' => 'color',  'label' => 'رنگ',       'default' => '#4f46e5'],
                ],
                'defaultProps' => ['label' => 'پشتیبانی آنلاین', 'style' => 'floating', 'position' => 'bottom-right', 'bg_color' => '#4f46e5'],
            ],
            // module:gallery_widget removed — 'gallery' module has no backend
            // content type in this codebase (no model/table), so the block
            // could never render real data. Use the plain 'gallery' block
            // instead, which renders a per-instance, editor-entered image list.
            'module:reviews_grid' => [
                'name'     => 'نظرات کاربران',
                'category' => self::CATEGORY_MODULE,
                'requires' => 'reviews',
                'schema'   => [
                    'title'       => ['type' => 'string',  'label' => 'عنوان',       'default' => 'نظرات مشتریان'],
                    'limit'       => ['type' => 'number',  'label' => 'تعداد',       'default' => 6],
                    // 'carousel' removed 2026-09-11: no working carousel
                    // runtime is wired for this block (unlike the real
                    // slider JS backing testimonials_slider) — 'cards' and
                    // 'minimal' are both real, distinct layouts below.
                    'style'       => ['type' => 'select',  'label' => 'استایل',      'options' => ['cards' => 'کارت', 'minimal' => 'مینیمال'], 'default' => 'cards'],
                    'show_avatar' => ['type' => 'boolean', 'label' => 'نمایش آواتار', 'default' => true],
                ],
                'defaultProps' => ['title' => 'نظرات مشتریان', 'limit' => 6, 'style' => 'cards', 'show_avatar' => true],
            ],
            'module:referral_widget' => [
                'name'     => 'ویجت معرفی به دوستان',
                'category' => self::CATEGORY_MODULE,
                'requires' => 'referral',
                'schema'   => [
                    'title'     => ['type' => 'string', 'label' => 'عنوان',     'default' => 'دوستانت را دعوت کن'],
                    'subtitle'  => ['type' => 'string', 'label' => 'زیرعنوان', 'default' => 'با معرفی دوستان جایزه بگیر'],
                    'style'     => ['type' => 'select', 'label' => 'استایل',    'options' => ['banner' => 'بنر', 'card' => 'کارت', 'minimal' => 'مینیمال'], 'default' => 'banner'],
                    'show_link' => ['type' => 'boolean', 'label' => 'نمایش لینک معرفی', 'default' => true],
                ],
                'defaultProps' => ['title' => 'دوستانت را دعوت کن', 'subtitle' => 'با معرفی دوستان جایزه بگیر', 'style' => 'banner', 'show_link' => true],
            ],
            'module:social_links_bar' => [
                'name'     => 'نوار شبکه‌های اجتماعی',
                'category' => self::CATEGORY_MODULE,
                'requires' => 'social_links',
                'schema'   => [
                    'style' => ['type' => 'select', 'label' => 'استایل',  'options' => ['horizontal' => 'افقی', 'vertical' => 'عمودی', 'icons-only' => 'فقط آیکون'], 'default' => 'horizontal'],
                    'color' => ['type' => 'select', 'label' => 'رنگ',     'options' => ['brand' => 'رنگ برند', 'white' => 'سفید', 'dark' => 'تیره'], 'default' => 'brand'],
                    'size'  => ['type' => 'select', 'label' => 'اندازه',  'options' => ['sm' => 'کوچک', 'md' => 'متوسط', 'lg' => 'بزرگ'], 'default' => 'md'],
                ],
                'defaultProps' => ['style' => 'horizontal', 'color' => 'brand', 'size' => 'md'],
            ],
            'module:notifications_bar' => [
                'name'     => 'نوار اعلان سایت',
                'category' => self::CATEGORY_MODULE,
                'requires' => null,
                'schema'   => [
                    'style'        => ['type' => 'select',  'label' => 'استایل',      'options' => ['top-bar' => 'نوار بالا', 'floating' => 'شناور'], 'default' => 'top-bar'],
                    'show_dismiss' => ['type' => 'boolean', 'label' => 'دکمه بستن', 'default' => true],
                ],
                'defaultProps' => ['style' => 'top-bar', 'show_dismiss' => true],
            ],
            'module:whatsapp_widget' => [
                'name'     => 'ویجت واتساپ',
                'category' => self::CATEGORY_MODULE,
                'requires' => 'whatsapp',
                'schema'   => [
                    'label'    => ['type' => 'string', 'label' => 'متن',     'default' => 'پشتیبانی واتساپ'],
                    'phone'    => ['type' => 'string', 'label' => 'شماره',   'default' => ''],
                    'position' => ['type' => 'select', 'label' => 'موقعیت', 'options' => ['bottom-right' => 'پایین راست', 'bottom-left' => 'پایین چپ'], 'default' => 'bottom-right'],
                ],
                'defaultProps' => ['label' => 'پشتیبانی واتساپ', 'phone' => '', 'position' => 'bottom-right'],
            ],
            // Theme Builder <-> Motion Graphics contract: a stable reference to
            // an already-published/assigned Motion asset only — never raw
            // source, editable timeline, AI prompt, or master metadata. The
            // renderer resolves motion_asset_id through the site's own
            // existing Motion runtime component (<x-motion:graphics>);
            // nothing here talks to the Motion module directly.
            'module:motion' => [
                'name'     => 'موشن گرافیک',
                'category' => self::CATEGORY_MODULE,
                'schema'   => [
                    'motion_asset_id' => ['type' => 'motion', 'label' => 'موشن انتخاب‌شده', 'default' => ''],
                    'placement'       => ['type' => 'select', 'label' => 'جایگاه', 'options' => ['hero' => 'هیرو', 'section' => 'بخش', 'cta' => 'فراخوان به اقدام', 'background' => 'پس‌زمینه'], 'default' => 'section'],
                    'enabled'         => ['type' => 'boolean', 'label' => 'فعال', 'default' => true],
                    'fallback_image'  => ['type' => 'image', 'label' => 'تصویر جایگزین (در صورت نبود موشن)', 'default' => ''],
                ],
                'defaultProps' => ['motion_asset_id' => '', 'placement' => 'section', 'enabled' => true, 'fallback_image' => ''],
            ],
            // ─── NEXUS PRO PREMIUM BLOCKS ────────────────────────────────────
            'hero_split' => [
                'name'     => 'Hero دو ستونه (Split Hero)',
                'category' => self::CATEGORY_LAYOUT,
                'schema'   => [
                    'badge'        => ['type' => 'string',  'label' => 'نشان بالای عنوان', 'default' => ''],
                    'title'        => ['type' => 'string',  'label' => 'عنوان اصلی',        'default' => 'عنوان اصلی شما اینجاست'],
                    'subtitle'     => ['type' => 'textarea','label' => 'زیرعنوان',           'default' => 'توضیح کوتاهی درباره محصول یا خدمات شما'],
                    'button_text'  => ['type' => 'string',  'label' => 'متن دکمه اول',       'default' => 'شروع کنید'],
                    'button_url'   => ['type' => 'link',    'label' => 'لینک دکمه اول',      'default' => '#'],
                    'button2_text' => ['type' => 'string',  'label' => 'متن دکمه دوم',       'default' => 'بیشتر بدانید'],
                    'button2_url'  => ['type' => 'link',    'label' => 'لینک دکمه دوم',      'default' => '#'],
                    'image_url'    => ['type' => 'image',   'label' => 'آدرس تصویر',          'default' => ''],
                    'image_side'   => ['type' => 'select',  'label' => 'موقعیت تصویر',       'options' => ['right' => 'راست', 'left' => 'چپ'], 'default' => 'left'],
                    'background'   => ['type' => 'color',   'label' => 'رنگ پس‌زمینه',       'default' => '#ffffff'],
                ],
                'defaultProps' => ['badge' => '', 'title' => 'عنوان اصلی', 'subtitle' => 'توضیح کوتاه', 'button_text' => 'شروع کنید', 'button_url' => '#', 'button2_text' => '', 'button2_url' => '#', 'image_url' => '', 'image_side' => 'left', 'background' => '#ffffff'],
            ],
            'hero_gradient' => [
                'name'     => 'Hero گرادیان بزرگ',
                'category' => self::CATEGORY_LAYOUT,
                'schema'   => [
                    'badge'         => ['type' => 'string',  'label' => 'نشان بالای عنوان',  'default' => 'معرفی می‌کنیم'],
                    'title'         => ['type' => 'string',  'label' => 'عنوان اصلی',         'default' => 'قدرت آینده در دستان شما'],
                    'subtitle'      => ['type' => 'textarea','label' => 'زیرعنوان',            'default' => 'پلتفرم چندمنظوره‌ای برای موفقیت کسب‌وکار شما'],
                    'button_text'   => ['type' => 'string',  'label' => 'متن دکمه اول',        'default' => 'رایگان شروع کنید'],
                    'button_url'    => ['type' => 'link',    'label' => 'لینک دکمه اول',       'default' => '#'],
                    'button2_text'  => ['type' => 'string',  'label' => 'متن دکمه دوم',        'default' => 'مشاهده دمو'],
                    'button2_url'   => ['type' => 'link',    'label' => 'لینک دکمه دوم',       'default' => '#'],
                    'gradient_from' => ['type' => 'color',   'label' => 'رنگ گرادیان ابتدا',   'default' => '#4f46e5'],
                    'gradient_to'   => ['type' => 'color',   'label' => 'رنگ گرادیان انتها',   'default' => '#7c3aed'],
                    'text_color'    => ['type' => 'color',   'label' => 'رنگ متن',             'default' => '#ffffff'],
                ],
                'defaultProps' => ['badge' => 'معرفی می‌کنیم', 'title' => 'قدرت آینده در دستان شما', 'subtitle' => 'پلتفرم چندمنظوره', 'button_text' => 'شروع کنید', 'button_url' => '#', 'button2_text' => '', 'button2_url' => '#', 'gradient_from' => '#4f46e5', 'gradient_to' => '#7c3aed', 'text_color' => '#ffffff'],
            ],
            'features_grid' => [
                'name'     => 'گرید ویژگی‌ها (Features)',
                'category' => self::CATEGORY_CONTENT,
                'schema'   => [
                    'title'    => ['type' => 'string',  'label' => 'عنوان بخش',   'default' => 'چرا ما را انتخاب کنید؟'],
                    'subtitle' => ['type' => 'string',  'label' => 'زیرعنوان',     'default' => ''],
                    'columns'  => ['type' => 'select',  'label' => 'تعداد ستون',  'options' => ['2' => '۲', '3' => '۳', '4' => '۴'], 'default' => '3'],
                    'style'    => ['type' => 'select',  'label' => 'استایل',       'options' => ['cards' => 'کارت', 'bordered' => 'خطی', 'minimal' => 'مینیمال'], 'default' => 'cards'],
                    'features' => [
                        'type' => 'repeater', 'label' => 'ویژگی‌ها', 'storage' => 'array',
                        'default' => [
                            ['icon' => '⚡', 'title' => 'سریع', 'text' => 'بهینه‌سازی شده'],
                            ['icon' => '🔒', 'title' => 'امن', 'text' => 'رمزنگاری پیشرفته'],
                            ['icon' => '🎯', 'title' => 'دقیق', 'text' => 'هوشمند'],
                        ],
                        'fields' => [
                            'icon'  => ['type' => 'string', 'label' => 'آیکون', 'default' => '✨'],
                            'title' => ['type' => 'string', 'label' => 'عنوان', 'default' => ''],
                            'text'  => ['type' => 'textarea', 'label' => 'متن', 'default' => ''],
                        ],
                    ],
                ],
                'defaultProps' => ['title' => 'چرا ما؟', 'subtitle' => '', 'columns' => '3', 'style' => 'cards', 'features' => [
                    ['icon' => '⚡', 'title' => 'سریع', 'text' => 'بهینه‌سازی شده'],
                    ['icon' => '🔒', 'title' => 'امن', 'text' => 'رمزنگاری'],
                    ['icon' => '🎯', 'title' => 'دقیق', 'text' => 'هوشمند'],
                ]],
            ],
            'stats_row' => [
                'name'     => 'آمار و شمارنده (Stats Row)',
                'category' => self::CATEGORY_CONTENT,
                'schema'   => [
                    'title'      => ['type' => 'string',  'label' => 'عنوان (اختیاری)',  'default' => ''],
                    'stats'      => [
                        'type' => 'repeater', 'label' => 'آمارها', 'storage' => 'array',
                        'default' => [
                            ['value' => 5000, 'suffix' => '+', 'label' => 'کاربر فعال'],
                            ['value' => 99, 'suffix' => '%', 'label' => 'رضایت'],
                            ['value' => 150, 'suffix' => '+', 'label' => 'پروژه'],
                        ],
                        'fields' => [
                            'value'  => ['type' => 'string', 'label' => 'عدد', 'default' => '0'],
                            'suffix' => ['type' => 'string', 'label' => 'پسوند (مثلا + یا %)', 'default' => ''],
                            'label'  => ['type' => 'string', 'label' => 'برچسب', 'default' => ''],
                        ],
                    ],
                    'background' => ['type' => 'color',   'label' => 'رنگ پس‌زمینه',    'default' => '#f8fafc'],
                    'dividers'   => ['type' => 'boolean', 'label' => 'خط جداکننده',     'default' => true],
                ],
                'defaultProps' => ['title' => '', 'stats' => [
                    ['value' => 5000, 'suffix' => '+', 'label' => 'کاربر فعال'],
                    ['value' => 99, 'suffix' => '%', 'label' => 'رضایت'],
                    ['value' => 150, 'suffix' => '+', 'label' => 'پروژه'],
                ], 'background' => '#f8fafc', 'dividers' => true],
            ],
            // Universal Multipurpose Slider Overhaul: each logo item may
            // now optionally carry {image, url, alt} alongside the
            // pre-existing {name} — an existing {name}-only entry still
            // renders as a text badge, unchanged. 'mode'/'grayscale'/
            // 'speed'/'visible_count' are new and additive.
            'client_logos' => [
                'name'     => 'اسلایدر لوگوی مشتریان',
                'category' => self::CATEGORY_MARKETING,
                'schema'   => [
                    'title'  => ['type' => 'string',  'label' => 'عنوان',        'default' => 'مورد اعتماد برندهای بزرگ'],
                    'logos'  => [
                        'type' => 'repeater', 'label' => 'لوگوها', 'storage' => 'array',
                        'default' => [['name' => 'برند اول'], ['name' => 'برند دوم'], ['name' => 'برند سوم']],
                        'fields' => [
                            'name'  => ['type' => 'string', 'label' => 'نام برند', 'default' => ''],
                            'image' => ['type' => 'image', 'label' => 'تصویر لوگو (اختیاری)', 'default' => ''],
                            'url'   => ['type' => 'string', 'label' => 'لینک (اختیاری)', 'default' => ''],
                            'alt'   => ['type' => 'string', 'label' => 'متن جایگزین تصویر (اختیاری)', 'default' => ''],
                        ],
                    ],
                    'scroll' => ['type' => 'boolean', 'label' => 'حرکت خودکار (مود مرور - قدیمی)', 'default' => true],
                    'mode'   => ['type' => 'select', 'label' => 'نوع نمایش', 'options' => ['marquee' => 'نوار متحرک', 'carousel' => 'اسلایدر با کنترل', 'static' => 'ثابت (Grid)'], 'default' => 'marquee'],
                    'grayscale' => ['type' => 'boolean', 'label' => 'سیاه‌وسفید تا هاور', 'default' => true],
                    'speed' => ['type' => 'number', 'label' => 'سرعت (ثانیه)', 'default' => 25],
                    'visible_count' => ['type' => 'number', 'label' => 'تعداد نمایش هم‌زمان (حالت اسلایدر)', 'default' => 5],
                ],
                'defaultProps' => ['title' => 'مورد اعتماد برندهای بزرگ', 'logos' => [['name' => 'برند اول'], ['name' => 'برند دوم'], ['name' => 'برند سوم']], 'scroll' => true, 'mode' => 'marquee', 'grayscale' => true, 'speed' => 25, 'visible_count' => 5],
            ],
            'process_steps' => [
                'name'     => 'مراحل انجام کار',
                'category' => self::CATEGORY_CONTENT,
                'schema'   => [
                    'title'    => ['type' => 'string',  'label' => 'عنوان', 'default' => 'چطور شروع کنیم؟'],
                    'subtitle' => ['type' => 'string',  'label' => 'زیرعنوان', 'default' => ''],
                    'steps'    => [
                        'type' => 'repeater', 'label' => 'مراحل', 'storage' => 'array',
                        'default' => [
                            ['number' => '۱', 'title' => 'ثبت‌نام', 'text' => 'در چند ثانیه شروع کنید'],
                            ['number' => '۲', 'title' => 'تنظیم', 'text' => 'قالب انتخاب کنید'],
                            ['number' => '۳', 'title' => 'انتشار', 'text' => 'سایت را راه‌اندازی کنید'],
                        ],
                        'fields' => [
                            'number' => ['type' => 'string', 'label' => 'شماره', 'default' => ''],
                            'title'  => ['type' => 'string', 'label' => 'عنوان', 'default' => ''],
                            'text'   => ['type' => 'textarea', 'label' => 'متن', 'default' => ''],
                        ],
                    ],
                ],
                'defaultProps' => ['title' => 'چطور شروع کنیم؟', 'subtitle' => '', 'steps' => [
                    ['number' => '۱', 'title' => 'ثبت‌نام', 'text' => 'شروع کنید'],
                    ['number' => '۲', 'title' => 'تنظیم', 'text' => 'قالب انتخاب کنید'],
                    ['number' => '۳', 'title' => 'انتشار', 'text' => 'راه‌اندازی'],
                ]],
            ],
            'timeline' => [
                'name'     => 'خط زمانی (Timeline)',
                'category' => self::CATEGORY_CONTENT,
                'schema'   => [
                    'title'    => ['type' => 'string', 'label' => 'عنوان بخش', 'default' => 'مسیر رشد ما'],
                    'subtitle' => ['type' => 'string', 'label' => 'زیرعنوان', 'default' => ''],
                    'layout'   => ['type' => 'select', 'label' => 'چیدمان', 'options' => ['vertical' => 'عمودی', 'horizontal' => 'افقی'], 'default' => 'vertical'],
                    'items'    => [
                        'type' => 'repeater',
                        'label' => 'رویدادها',
                        // Persisted/runtime contract is a JSON string
                        // (matches the shared canonical schema's textarea
                        // convention) — the visual adapter decodes/re-encodes
                        // without changing that shape.
                        'storage' => 'json',
                        'default' => '[{"date":"۱۳۹۸","title":"تأسیس شرکت","text":"شروع فعالیت با یک تیم کوچک و ایدهٔ بزرگ.","icon":"🚀","image":"","link_label":"","link_url":""}]',
                        'fields' => [
                            'date'       => ['type' => 'string', 'label' => 'تاریخ / مرحله', 'default' => ''],
                            'title'      => ['type' => 'string', 'label' => 'عنوان رویداد', 'default' => ''],
                            'text'       => ['type' => 'textarea', 'label' => 'توضیح', 'default' => ''],
                            'icon'       => ['type' => 'string', 'label' => 'آیکون (اموجی، اختیاری)', 'default' => ''],
                            'image'      => ['type' => 'image', 'label' => 'تصویر (اختیاری، جای آیکون را می‌گیرد)', 'default' => ''],
                            'link_label' => ['type' => 'string', 'label' => 'متن دکمه (اختیاری)', 'default' => ''],
                            'link_url'   => ['type' => 'string', 'label' => 'لینک دکمه (اختیاری)', 'default' => ''],
                        ],
                    ],
                ],
                'defaultProps' => ['title' => 'مسیر رشد ما', 'subtitle' => '', 'layout' => 'vertical', 'items' => '[{"date":"۱۳۹۸","title":"تأسیس شرکت","text":"شروع فعالیت با یک تیم کوچک و ایدهٔ بزرگ.","icon":"🚀","image":"","link_label":"","link_url":""}]'],
            ],
            'team_cards' => [
                'name'     => 'کارت اعضای تیم',
                'category' => self::CATEGORY_DYNAMIC,
                'schema'   => [
                    'title'   => ['type' => 'string',  'label' => 'عنوان',         'default' => 'تیم ما'],
                    'columns' => ['type' => 'select',  'label' => 'ستون',          'options' => ['2' => '۲', '3' => '۳', '4' => '۴'], 'default' => '3'],
                    'members' => [
                        'type' => 'repeater',
                        'label' => 'اعضای تیم',
                        // Existing persisted/runtime contract is a JSON
                        // string. The visual adapter decodes and re-encodes
                        // it without changing that shape.
                        'storage' => 'json',
                        'default' => '[{"name":"نام","role":"مدیر","image":"","bio":"توضیح"}]',
                        'fields' => [
                            'name' => ['type' => 'string', 'label' => 'نام', 'default' => ''],
                            'role' => ['type' => 'string', 'label' => 'سمت', 'default' => ''],
                            'image' => ['type' => 'image', 'label' => 'تصویر عضو', 'default' => ''],
                            'bio' => ['type' => 'textarea', 'label' => 'توضیح', 'default' => ''],
                        ],
                    ],
                ],
                'defaultProps' => ['title' => 'تیم ما', 'columns' => '3', 'members' => '[{"name":"نام","role":"مدیر","image":"","bio":"توضیح"}]'],
            ],
            'skills_bars' => [
                'name'     => 'نوارهای مهارت',
                'category' => self::CATEGORY_CONTENT,
                'schema'   => [
                    'title'  => ['type' => 'string',  'label' => 'عنوان',         'default' => 'مهارت‌های من'],
                    'skills' => [
                        'type' => 'repeater', 'label' => 'مهارت‌ها', 'storage' => 'array',
                        'default' => [['name' => 'طراحی', 'percent' => 90], ['name' => 'برنامه‌نویسی', 'percent' => 85]],
                        'fields' => [
                            'name'    => ['type' => 'string', 'label' => 'نام مهارت', 'default' => ''],
                            'percent' => ['type' => 'string', 'label' => 'درصد (۰ تا ۱۰۰)', 'default' => '0'],
                        ],
                    ],
                ],
                'defaultProps' => ['title' => 'مهارت‌ها', 'skills' => [['name' => 'طراحی', 'percent' => 90], ['name' => 'برنامه‌نویسی', 'percent' => 85]]],
            ],
            // Universal Multipurpose Slider Overhaul: restores 'columns'
            // (removed 2026-09-11 when it genuinely had no effect) — the
            // shared canonical renderer (packages/saas-platform/modules/
            // theme-runtime BlockRenderer::renderTestimonialsSlider) now
            // honors it in BOTH 'static' and 'carousel' 'mode' via a real
            // itemsPerView carousel engine, closing that exact
            // schema/renderer mismatch. This host no longer needs its own
            // override for this type at all (removed from
            // ThemeCanvasRenderer — see its own doc comment) — the shared
            // renderer's behavior is now correct for every host. 'company'/
            // 'avatar' are new, additive item fields.
            'testimonials_slider' => [
                'name'     => 'اسلایدر نظرات مشتریان',
                'category' => self::CATEGORY_DYNAMIC,
                'schema'   => [
                    'title'    => ['type' => 'string',  'label' => 'عنوان',        'default' => 'نظرات مشتریان'],
                    'columns'  => ['type' => 'select', 'label' => 'تعداد ستون', 'options' => ['1' => '۱', '2' => '۲', '3' => '۳'], 'default' => '3'],
                    'mode'     => ['type' => 'select', 'label' => 'نوع نمایش', 'options' => ['carousel' => 'اسلایدر با کنترل', 'static' => 'ثابت (Grid)'], 'default' => 'carousel'],
                    'items'    => [
                        'type' => 'repeater', 'label' => 'نظرات', 'storage' => 'array',
                        'default' => [['name' => 'رضا', 'role' => 'مدیر', 'company' => '', 'text' => 'عالی بود!', 'rating' => 5]],
                        'fields' => [
                            'name'    => ['type' => 'string', 'label' => 'نام', 'default' => ''],
                            'role'    => ['type' => 'string', 'label' => 'سمت', 'default' => ''],
                            'company' => ['type' => 'string', 'label' => 'شرکت (اختیاری)', 'default' => ''],
                            'avatar'  => ['type' => 'image', 'label' => 'تصویر (اختیاری)', 'default' => ''],
                            'text'    => ['type' => 'textarea', 'label' => 'متن نظر', 'default' => ''],
                            'rating'  => ['type' => 'string', 'label' => 'امتیاز (۱ تا ۵)', 'default' => '5'],
                        ],
                    ],
                    'autoplay' => ['type' => 'boolean', 'label' => 'پخش خودکار', 'default' => true],
                    'interval' => ['type' => 'number',  'label' => 'فاصله (ms)',  'default' => 5000],
                ],
                'defaultProps' => ['title' => 'نظرات مشتریان', 'columns' => '3', 'mode' => 'carousel', 'items' => [['name' => 'رضا', 'role' => 'مدیر', 'company' => '', 'text' => 'عالی بود!', 'rating' => 5]], 'autoplay' => true, 'interval' => 5000],
            ],
            'trust_badges' => [
                'name'     => 'نشان‌های اعتماد',
                'category' => self::CATEGORY_MARKETING,
                'schema'   => [
                    'badges'     => [
                        'type' => 'repeater', 'label' => 'نشان‌ها', 'storage' => 'array',
                        'default' => [
                            ['icon' => '🚚', 'title' => 'ارسال رایگان', 'text' => 'بالای ۲۰۰ هزار'],
                            ['icon' => '🔒', 'title' => 'پرداخت امن', 'text' => 'درگاه معتبر'],
                        ],
                        'fields' => [
                            'icon'  => ['type' => 'string', 'label' => 'آیکون', 'default' => '✓'],
                            'title' => ['type' => 'string', 'label' => 'عنوان', 'default' => ''],
                            'text'  => ['type' => 'string', 'label' => 'متن', 'default' => ''],
                        ],
                    ],
                    'background' => ['type' => 'color',   'label' => 'رنگ پس‌زمینه',  'default' => '#f8fafc'],
                ],
                'defaultProps' => ['badges' => [
                    ['icon' => '🚚', 'title' => 'ارسال رایگان', 'text' => 'بالای ۲۰۰ هزار'],
                    ['icon' => '🔒', 'title' => 'پرداخت امن', 'text' => 'درگاه معتبر'],
                ], 'background' => '#f8fafc'],
            ],
            'pricing_cards' => [
                'name'     => 'کارت‌های تعرفه',
                'category' => self::CATEGORY_MARKETING,
                'schema'   => [
                    'title'    => ['type' => 'string',  'label' => 'عنوان',       'default' => 'پلن‌های ما'],
                    'subtitle' => ['type' => 'string',  'label' => 'زیرعنوان',    'default' => ''],
                    // 'highlight' (boolean) is a simple text-like field
                    // ("true" or empty) rather than a checkbox control — the
                    // Canvas nested-repeater adapter has no boolean widget at
                    // this nesting depth. 'features' below IS a real bounded
                    // nested repeater (one explicit extra level, exactly
                    // like nav_footer.columns[].links — not a generic
                    // recursive mechanism), giving customers real
                    // add/edit/remove/reorder controls with no JSON exposed.
                    'plans'    => [
                        'type' => 'repeater', 'label' => 'پلن‌ها', 'storage' => 'array',
                        'default' => [
                            ['name' => 'رایگان', 'price' => 'رایگان', 'period' => '', 'badge' => '', 'highlight' => false, 'features' => [['text' => 'ویژگی ۱']], 'cta_text' => 'شروع', 'cta_url' => '#'],
                            ['name' => 'حرفه‌ای', 'price' => '۱۵۰ هزار', 'period' => '/ ماه', 'badge' => 'محبوب', 'highlight' => true, 'features' => [['text' => 'همه ویژگی‌ها']], 'cta_text' => 'انتخاب', 'cta_url' => '#'],
                        ],
                        'fields' => [
                            'name'      => ['type' => 'string', 'label' => 'نام پلن', 'default' => ''],
                            'price'     => ['type' => 'string', 'label' => 'قیمت', 'default' => ''],
                            'period'    => ['type' => 'string', 'label' => 'دوره (مثلا / ماه)', 'default' => ''],
                            'badge'     => ['type' => 'string', 'label' => 'برچسب (اختیاری)', 'default' => ''],
                            'highlight' => ['type' => 'string', 'label' => 'پلن ویژه؟ (true یا خالی)', 'default' => ''],
                            'features'  => [
                                'type' => 'repeater', 'label' => 'ویژگی‌ها', 'storage' => 'array', 'default' => [],
                                'fields' => [
                                    'text' => ['type' => 'string', 'label' => 'متن ویژگی', 'default' => ''],
                                ],
                            ],
                            'cta_text'  => ['type' => 'string', 'label' => 'متن دکمه', 'default' => ''],
                            'cta_url'   => ['type' => 'string', 'label' => 'لینک دکمه', 'default' => '#'],
                        ],
                    ],
                ],
                'defaultProps' => ['title' => 'پلن‌های ما', 'subtitle' => '', 'plans' => [
                    ['name' => 'رایگان', 'price' => 'رایگان', 'period' => '', 'badge' => '', 'highlight' => false, 'features' => [['text' => 'ویژگی ۱']], 'cta_text' => 'شروع', 'cta_url' => '#'],
                    ['name' => 'حرفه‌ای', 'price' => '۱۵۰ هزار', 'period' => '/ ماه', 'badge' => 'محبوب', 'highlight' => true, 'features' => [['text' => 'همه ویژگی‌ها']], 'cta_text' => 'انتخاب', 'cta_url' => '#'],
                ]],
            ],
            'newsletter_form' => [
                'name'     => 'فرم خبرنامه',
                'category' => self::CATEGORY_MARKETING,
                'schema'   => [
                    'title'       => ['type' => 'string', 'label' => 'عنوان',        'default' => 'در خبرنامه عضو شوید'],
                    'subtitle'    => ['type' => 'string', 'label' => 'زیرعنوان',     'default' => ''],
                    'placeholder' => ['type' => 'string', 'label' => 'placeholder',  'default' => 'ایمیل یا موبایل'],
                    'button_text' => ['type' => 'string', 'label' => 'متن دکمه',     'default' => 'عضویت'],
                    'background'  => ['type' => 'color',  'label' => 'رنگ پس‌زمینه','default' => '#4f46e5'],
                    'text_color'  => ['type' => 'color',  'label' => 'رنگ متن',      'default' => '#ffffff'],
                ],
                'defaultProps' => ['title' => 'در خبرنامه عضو شوید', 'subtitle' => '', 'placeholder' => 'ایمیل یا موبایل', 'button_text' => 'عضویت', 'background' => '#4f46e5', 'text_color' => '#ffffff'],
            ],
            'bento_grid' => [
                'name'     => 'گرید بنتو (Bento Grid)',
                'category' => self::CATEGORY_LAYOUT,
                'schema'   => [
                    'title' => ['type' => 'string',  'label' => 'عنوان',           'default' => 'همه چیز در یک پلتفرم'],
                    'items' => [
                        'type' => 'repeater', 'label' => 'آیتم‌ها', 'storage' => 'array',
                        'default' => [
                            ['title' => 'مدیریت', 'text' => 'داشبورد جامع', 'icon' => '📊', 'size' => 'large', 'bg_color' => '#4f46e5', 'text_color' => '#fff'],
                            ['title' => 'امنیت', 'text' => 'رمزنگاری', 'icon' => '🔒', 'size' => 'small', 'bg_color' => '#f0fdf4', 'text_color' => '#14532d'],
                        ],
                        'fields' => [
                            'title'      => ['type' => 'string', 'label' => 'عنوان', 'default' => ''],
                            'text'       => ['type' => 'textarea', 'label' => 'متن', 'default' => ''],
                            'icon'       => ['type' => 'string', 'label' => 'آیکون', 'default' => ''],
                            'size'       => ['type' => 'string', 'label' => 'اندازه (large/tall/featured/small)', 'default' => 'small'],
                            'bg_color'   => ['type' => 'string', 'label' => 'رنگ پس‌زمینه', 'default' => '#f8fafc'],
                            'text_color' => ['type' => 'string', 'label' => 'رنگ متن', 'default' => '#0f172a'],
                        ],
                    ],
                ],
                'defaultProps' => ['title' => 'همه چیز در یک پلتفرم', 'items' => [
                    ['title' => 'مدیریت', 'text' => 'داشبورد جامع', 'icon' => '📊', 'size' => 'large', 'bg_color' => '#4f46e5', 'text_color' => '#fff'],
                    ['title' => 'امنیت', 'text' => 'رمزنگاری', 'icon' => '🔒', 'size' => 'small', 'bg_color' => '#f0fdf4', 'text_color' => '#14532d'],
                ]],
            ],
            'services_cards' => [
                'name'     => 'کارت‌های خدمات',
                'category' => self::CATEGORY_CONTENT,
                'schema'   => [
                    'title'    => ['type' => 'string',  'label' => 'عنوان',         'default' => 'خدمات ما'],
                    'subtitle' => ['type' => 'string',  'label' => 'زیرعنوان',      'default' => ''],
                    'columns'  => ['type' => 'select',  'label' => 'ستون',          'options' => ['2' => '۲', '3' => '۳', '4' => '۴'], 'default' => '3'],
                    'style'    => ['type' => 'select',  'label' => 'استایل',        'options' => ['bordered' => 'خطی', 'filled' => 'پر', 'minimal' => 'مینیمال'], 'default' => 'bordered'],
                    'services' => [
                        'type' => 'repeater', 'label' => 'خدمات', 'storage' => 'array',
                        'default' => [['icon' => '🎨', 'title' => 'طراحی', 'text' => 'رابط کاربری', 'link' => '#', 'link_text' => 'بیشتر']],
                        'fields' => [
                            'icon'      => ['type' => 'string', 'label' => 'آیکون', 'default' => ''],
                            'title'     => ['type' => 'string', 'label' => 'عنوان', 'default' => ''],
                            'text'      => ['type' => 'textarea', 'label' => 'متن', 'default' => ''],
                            'link'      => ['type' => 'string', 'label' => 'لینک', 'default' => '#'],
                            'link_text' => ['type' => 'string', 'label' => 'متن لینک', 'default' => 'بیشتر بدانید'],
                        ],
                    ],
                ],
                'defaultProps' => ['title' => 'خدمات ما', 'subtitle' => '', 'columns' => '3', 'style' => 'bordered', 'services' => [
                    ['icon' => '🎨', 'title' => 'طراحی', 'text' => 'رابط کاربری', 'link' => '#', 'link_text' => 'بیشتر'],
                ]],
            ],
            'contact_split' => [
                'name'     => 'تماس دو ستونه',
                'category' => self::CATEGORY_UTILITY,
                'schema'   => [
                    'title'     => ['type' => 'string',  'label' => 'عنوان',       'default' => 'با ما در ارتباط باشید'],
                    'subtitle'  => ['type' => 'string',  'label' => 'زیرعنوان',    'default' => ''],
                    'phone'     => ['type' => 'string',  'label' => 'تلفن',         'default' => ''],
                    'email'     => ['type' => 'string',  'label' => 'ایمیل',        'default' => ''],
                    'address'   => ['type' => 'string',  'label' => 'آدرس',         'default' => ''],
                    'hours'     => ['type' => 'string',  'label' => 'ساعات کاری',  'default' => 'شنبه تا پنجشنبه، ۹ تا ۱۷'],
                    'show_form' => ['type' => 'boolean', 'label' => 'نمایش فرم',   'default' => true],
                ],
                'defaultProps' => ['title' => 'با ما در ارتباط باشید', 'subtitle' => '', 'phone' => '', 'email' => '', 'address' => '', 'hours' => 'شنبه تا پنجشنبه، ۹ تا ۱۷', 'show_form' => true],
            ],

            'module:form_embed' => [
                'name'     => 'درج فرم',
                'category' => self::CATEGORY_MODULE,
                'requires' => 'forms',
                'schema'   => [
                    'form_id'    => ['type' => 'string',  'label' => 'شناسه فرم (ID)', 'default' => ''],
                    'title'      => ['type' => 'string',  'label' => 'عنوان نمایشی',  'default' => ''],
                    'show_title' => ['type' => 'boolean', 'label' => 'نمایش عنوان',   'default' => true],
                    'style'      => ['type' => 'select',  'label' => 'استایل',         'options' => ['card' => 'کارت', 'minimal' => 'مینیمال', 'full' => 'تمام عرض'], 'default' => 'card'],
                ],
                'defaultProps' => ['form_id' => '', 'title' => '', 'show_title' => true, 'style' => 'card'],
            ],
            'module:plan_cta' => [
                'name'     => 'دعوت به اشتراک',
                'category' => self::CATEGORY_MODULE,
                'requires' => 'subscription',
                'schema'   => [
                    'title'       => ['type' => 'string', 'label' => 'عنوان',     'default' => 'به کاربران ویژه بپیوند'],
                    'subtitle'    => ['type' => 'string', 'label' => 'زیرعنوان', 'default' => ''],
                    'button_text' => ['type' => 'string', 'label' => 'متن دکمه', 'default' => 'مشاهده پلن‌ها'],
                    'bg_color'    => ['type' => 'color',  'label' => 'رنگ',       'default' => '#7c3aed'],
                ],
                'defaultProps' => ['title' => 'به کاربران ویژه بپیوند', 'subtitle' => '', 'button_text' => 'مشاهده پلن‌ها', 'bg_color' => '#7c3aed'],
            ],
            'module:course_enroll_cta' => [
                'name'     => 'دعوت به ثبت‌نام دوره',
                'category' => self::CATEGORY_MODULE,
                'requires' => 'courses',
                'schema'   => [
                    'title'       => ['type' => 'string', 'label' => 'عنوان',     'default' => 'همین الان شروع کن!'],
                    'subtitle'    => ['type' => 'string', 'label' => 'زیرعنوان', 'default' => 'به هزاران دانشجو بپیوند'],
                    'button_text' => ['type' => 'string', 'label' => 'متن دکمه', 'default' => 'مشاهده دوره‌ها'],
                    'bg_color'    => ['type' => 'color',  'label' => 'رنگ',       'default' => '#4f46e5'],
                ],
                'defaultProps' => ['title' => 'همین الان شروع کن!', 'subtitle' => 'به هزاران دانشجو بپیوند', 'button_text' => 'مشاهده دوره‌ها', 'bg_color' => '#4f46e5'],
            ],
            'module:my_learning_link' => [
                'name'     => 'لینک یادگیری من',
                'category' => self::CATEGORY_MODULE,
                'requires' => 'courses',
                'schema'   => [
                    'label' => ['type' => 'string', 'label' => 'متن لینک', 'default' => 'یادگیری من'],
                    'style' => ['type' => 'select', 'label' => 'استایل',   'options' => ['button' => 'دکمه', 'link' => 'لینک ساده', 'card' => 'کارت'], 'default' => 'button'],
                ],
                'defaultProps' => ['label' => 'یادگیری من', 'style' => 'button'],
            ],

            // BLD-11..13 (Full Product Commercial Audit, TASK 2A-CONTINUATION):
            // these 7 canonical types carried genuinely incomplete array
            // authoring here (a raw, non-repeater `type: array` field with no
            // Canvas-local override — the canonical package's own definition
            // still declares plain `array`, unchanged, since it also serves
            // saas-platform's own paused Theme Builder UI). Local overrides
            // below upgrade only the Canvas authoring surface's field(s) to
            // `type: repeater`, matching the same real add/remove/reorder
            // pattern already proven for gallery/team_cards above — field
            // names verified directly against each renderer's actual read
            // path in BlockRenderer.php (SaaSPlatform\ThemeRuntime and this
            // host's own overrides), not guessed. `nav_footer.columns` is the
            // one genuinely nested case (a column has its own `links` list);
            // it uses one explicit, bounded extra repeater level — not a
            // generic recursive mechanism — matching the Alpine adapter's
            // matching bounded extension.
            'nav_header' => [
                'name'     => 'هدر سایت (Nav Header)',
                'category' => self::CATEGORY_UTILITY,
                'schema'   => [
                    'logo_text'          => ['type' => 'string', 'label' => 'متن لوگو', 'default' => ''],
                    'logo_url'           => ['type' => 'string', 'label' => 'لینک لوگو', 'default' => '/'],
                    // TASK 3/3 Phase 6.5B (BLD-22) — this Canvas-editor
                    // schema is a SEPARATE copy from the shared theme-
                    // runtime package's own BlockRegistry (this class does
                    // not extend it — see the class docblock context
                    // above), so the render-side nav_source field added
                    // there has no effect on the authoring UI unless it is
                    // also added here. Kept identical: same key, options,
                    // and default ('custom', so no existing theme/instance
                    // changes behavior) — see
                    // BlockRenderer::renderNavHeader() for the render-time
                    // branch this authors.
                    'nav_source'         => ['type' => 'select', 'label' => 'منبع لینک‌های ناوبری', 'options' => ['custom' => 'لینک‌های سفارشی این بلوک', 'menu' => 'منوی اصلی سایت (/admin/menus)'], 'default' => 'custom'],
                    // Commercial Productization fase-2 — Mega Menu, real
                    // independent column/group structure (no raw JSON, and
                    // no longer inferred by matching duplicate column-title
                    // text). The Canvas repeater UI (resources/views/admin/
                    // theme-design/canvas-editor.blade.php) previously
                    // supported exactly one bounded extra nesting level; a
                    // second explicit, equally bounded level was added
                    // specifically for this field (`nestedItems()` family →
                    // `deepNestedItems()` family) so `mega_columns[].items`
                    // is a real nested repeater, not a generic recursive
                    // mechanism. Each column is its own array entry with its
                    // own `items` — two columns can share the exact same
                    // title and remain fully independent, unlike the old
                    // flat-and-grouped shape.
                    // `mega_items` (flat, grouped-by-title) is kept ONLY so
                    // themes/instances authored before this change keep
                    // rendering — BlockRenderer::normalizeMegaMenuData()
                    // prefers `mega_columns` when present and falls back to
                    // `mega_items` otherwise. The Canvas editor itself only
                    // ever writes `mega_columns` going forward.
                    'nav_links'          => [
                        'type' => 'repeater', 'label' => 'لینک‌های منو', 'storage' => 'array', 'default' => [],
                        'fields' => [
                            'label' => ['type' => 'string', 'label' => 'متن لینک', 'default' => ''],
                            'url'   => ['type' => 'string', 'label' => 'آدرس لینک', 'default' => '#'],
                            'mega_columns' => [
                                'type' => 'repeater',
                                'label' => 'ستون‌های Mega Menu (اختیاری — برای منوی چندستونی)',
                                'fields' => [
                                    'title' => ['type' => 'string', 'label' => 'عنوان ستون', 'default' => ''],
                                    'items' => [
                                        'type' => 'repeater',
                                        'label' => 'آیتم‌های این ستون',
                                        'fields' => [
                                            'label' => ['type' => 'string', 'label' => 'متن لینک', 'default' => ''],
                                            'url'   => ['type' => 'string', 'label' => 'آدرس لینک', 'default' => '#'],
                                            'icon'  => ['type' => 'string', 'label' => 'آیکون (اموجی، اختیاری)', 'default' => ''],
                                            'image' => ['type' => 'image', 'label' => 'تصویر آیتم (اختیاری، جای آیکون را می‌گیرد)', 'default' => ''],
                                        ],
                                    ],
                                ],
                            ],
                            'mega_banner_image' => ['type' => 'image', 'label' => 'تصویر بنر Mega Menu (اختیاری)', 'default' => ''],
                            'mega_banner_url'   => ['type' => 'string', 'label' => 'لینک بنر', 'default' => '#'],
                            'mega_banner_text'  => ['type' => 'string', 'label' => 'متن روی بنر', 'default' => ''],
                            'mega_show_desktop' => ['type' => 'boolean', 'label' => 'نمایش Mega Menu در دسکتاپ', 'default' => true],
                            'mega_mobile_behavior' => ['type' => 'select', 'label' => 'رفتار در موبایل', 'options' => ['accordion' => 'آکاردئونی (پیش‌فرض)', 'flat' => 'فهرست تخت بدون ستون‌بندی', 'hidden' => 'مخفی در موبایل'], 'default' => 'accordion'],
                        ],
                    ],
                    'sticky'             => ['type' => 'boolean', 'label' => 'چسبان به بالا', 'default' => false],
                    'bg_color'           => ['type' => 'string', 'label' => 'رنگ پس‌زمینه', 'default' => '#ffffff'],
                    'text_color'         => ['type' => 'string', 'label' => 'رنگ متن', 'default' => '#111827'],
                    'phone'              => ['type' => 'string', 'label' => 'شماره تماس (اختیاری)', 'default' => ''],
                    'cta_text'           => ['type' => 'string', 'label' => 'متن دکمه CTA (اختیاری)', 'default' => ''],
                    'cta_url'            => ['type' => 'string', 'label' => 'لینک دکمه CTA', 'default' => '#'],
                    'show_search'        => ['type' => 'boolean', 'label' => 'نمایش جستجو', 'default' => false],
                    'search_placeholder' => ['type' => 'string', 'label' => 'متن جای‌گزین جستجو', 'default' => 'جستجو...'],
                    'show_cart'          => ['type' => 'boolean', 'label' => 'نمایش سبد خرید', 'default' => false],
                    'show_account'       => ['type' => 'boolean', 'label' => 'نمایش حساب کاربری', 'default' => false],
                    // Commercial Productization fase-4: real dark-mode
                    // toggle — see BlockRenderer::renderDarkModeToggle()
                    // (shared) and layouts/canvas-theme.blade.php's
                    // head-bootstrap script for the full mechanism.
                    'show_dark_toggle'   => ['type' => 'boolean', 'label' => 'نمایش تغییر حالت روشن/تاریک', 'default' => false],
                ],
                'defaultProps' => ['logo_text' => '', 'logo_url' => '/', 'nav_links' => [], 'sticky' => false, 'bg_color' => '#ffffff', 'text_color' => '#111827'],
            ],
            'nav_footer' => [
                'name'     => 'فوتر سایت (Nav Footer)',
                'category' => self::CATEGORY_UTILITY,
                'schema'   => [
                    // TASK 3/3 Phase 6.5C (BLD-26) — see the matching
                    // shared-package registry entry and BlockRenderer::renderNavFooter().
                    'nav_source' => ['type' => 'select', 'label' => 'منبع لینک‌های فوتر', 'options' => ['custom' => 'ستون‌های سفارشی این بلوک', 'menu' => 'منوی فوتر سایت (/admin/menus)'], 'default' => 'custom'],
                    'columns' => [
                        'type' => 'repeater', 'label' => 'ستون‌های لینک', 'storage' => 'array', 'default' => [],
                        'fields' => [
                            'title' => ['type' => 'string', 'label' => 'عنوان ستون', 'default' => ''],
                            'links' => [
                                'type' => 'repeater', 'label' => 'لینک‌های ستون', 'storage' => 'array', 'default' => [],
                                'fields' => [
                                    'label' => ['type' => 'string', 'label' => 'متن لینک', 'default' => ''],
                                    'url'   => ['type' => 'string', 'label' => 'آدرس لینک', 'default' => '#'],
                                ],
                            ],
                        ],
                    ],
                    'links' => [
                        'type' => 'repeater', 'label' => 'لینک‌های حقوقی (اختیاری)', 'storage' => 'array', 'default' => [],
                        'fields' => [
                            'label' => ['type' => 'string', 'label' => 'متن لینک', 'default' => ''],
                            'url'   => ['type' => 'string', 'label' => 'آدرس لینک', 'default' => '#'],
                        ],
                    ],
                    'social_links' => [
                        'type' => 'repeater', 'label' => 'شبکه‌های اجتماعی (اختیاری)', 'storage' => 'array', 'default' => [],
                        'fields' => [
                            'url'   => ['type' => 'string', 'label' => 'آدرس', 'default' => '#'],
                            'label' => ['type' => 'string', 'label' => 'عنوان', 'default' => ''],
                        ],
                    ],
                    'logo_text'  => ['type' => 'string', 'label' => 'متن لوگو', 'default' => ''],
                    'tagline'    => ['type' => 'string', 'label' => 'شعار برند (اختیاری)', 'default' => ''],
                    'copyright'  => ['type' => 'string', 'label' => 'متن کپی‌رایت', 'default' => ''],
                    'bg_color'   => ['type' => 'string', 'label' => 'رنگ پس‌زمینه', 'default' => '#111827'],
                    'text_color' => ['type' => 'string', 'label' => 'رنگ متن', 'default' => '#e5e7eb'],
                ],
                'defaultProps' => ['columns' => [], 'links' => [], 'social_links' => [], 'copyright' => '', 'bg_color' => '#111827', 'text_color' => '#e5e7eb'],
            ],
            'about_section' => [
                'name'     => 'بخش درباره ما (About Section)',
                'category' => self::CATEGORY_CONTENT,
                'schema'   => [
                    'badge'       => ['type' => 'string', 'label' => 'نشان', 'default' => ''],
                    'title'       => ['type' => 'string', 'label' => 'عنوان', 'default' => ''],
                    'description' => ['type' => 'textarea', 'label' => 'توضیحات', 'default' => ''],
                    'features'    => [
                        'type' => 'repeater', 'label' => 'ویژگی‌ها', 'storage' => 'array', 'default' => [],
                        'fields' => [
                            'label' => ['type' => 'string', 'label' => 'متن ویژگی', 'default' => ''],
                        ],
                    ],
                    'stats' => [
                        'type' => 'repeater', 'label' => 'آمار (اختیاری)', 'storage' => 'array', 'default' => [],
                        'fields' => [
                            'value' => ['type' => 'string', 'label' => 'عدد', 'default' => ''],
                            'label' => ['type' => 'string', 'label' => 'برچسب', 'default' => ''],
                        ],
                    ],
                    'btn_text' => ['type' => 'string', 'label' => 'متن دکمه', 'default' => ''],
                    'btn_url'  => ['type' => 'string', 'label' => 'لینک دکمه', 'default' => '#'],
                ],
                'defaultProps' => ['badge' => '', 'title' => '', 'description' => '', 'features' => [], 'stats' => [], 'btn_text' => '', 'btn_url' => '#'],
            ],
            'service_cards' => [
                'name'     => 'کارت‌های خدمات با لینک (Service Cards)',
                'category' => self::CATEGORY_CONTENT,
                'schema'   => [
                    'title'    => ['type' => 'string', 'label' => 'عنوان بخش', 'default' => ''],
                    'subtitle' => ['type' => 'string', 'label' => 'زیرعنوان', 'default' => ''],
                    'columns'  => ['type' => 'select', 'label' => 'تعداد ستون', 'options' => ['2' => '۲', '3' => '۳', '4' => '۴'], 'default' => '3'],
                    'cards'    => [
                        'type' => 'repeater', 'label' => 'کارت‌ها', 'storage' => 'array', 'default' => [],
                        'fields' => [
                            'icon'        => ['type' => 'string', 'label' => 'آیکون', 'default' => ''],
                            'title'       => ['type' => 'string', 'label' => 'عنوان', 'default' => ''],
                            'description' => ['type' => 'textarea', 'label' => 'توضیحات', 'default' => ''],
                            'url'         => ['type' => 'string', 'label' => 'لینک (اختیاری)', 'default' => ''],
                        ],
                    ],
                ],
                'defaultProps' => ['title' => '', 'subtitle' => '', 'columns' => '3', 'cards' => []],
            ],
            'project_cards' => [
                'name'     => 'کارت‌های نمونه‌کار (Project Cards)',
                'category' => self::CATEGORY_CONTENT,
                'schema'   => [
                    'title'    => ['type' => 'string', 'label' => 'عنوان بخش', 'default' => ''],
                    'subtitle' => ['type' => 'string', 'label' => 'زیرعنوان', 'default' => ''],
                    'columns'  => ['type' => 'select', 'label' => 'تعداد ستون', 'options' => ['2' => '۲', '3' => '۳', '4' => '۴'], 'default' => '2'],
                    'cards'    => [
                        'type' => 'repeater', 'label' => 'کارت‌ها', 'storage' => 'array', 'default' => [],
                        'fields' => [
                            'category'    => ['type' => 'string', 'label' => 'دسته‌بندی', 'default' => ''],
                            'title'       => ['type' => 'string', 'label' => 'عنوان', 'default' => ''],
                            'description' => ['type' => 'textarea', 'label' => 'توضیحات', 'default' => ''],
                            'url'         => ['type' => 'string', 'label' => 'لینک (اختیاری)', 'default' => ''],
                        ],
                    ],
                ],
                'defaultProps' => ['title' => '', 'subtitle' => '', 'columns' => '2', 'cards' => []],
            ],
            'contact_info' => [
                'name'     => 'اطلاعات تماس (Contact Info)',
                'category' => self::CATEGORY_UTILITY,
                'schema'   => [
                    'title'      => ['type' => 'string', 'label' => 'عنوان', 'default' => ''],
                    'subtitle'   => ['type' => 'string', 'label' => 'زیرعنوان', 'default' => ''],
                    'background' => ['type' => 'string', 'label' => 'رنگ پس‌زمینه (اختیاری)', 'default' => ''],
                    'items'      => [
                        'type' => 'repeater', 'label' => 'موارد', 'storage' => 'array', 'default' => [],
                        'fields' => [
                            'icon'  => ['type' => 'string', 'label' => 'آیکون', 'default' => ''],
                            'label' => ['type' => 'string', 'label' => 'برچسب', 'default' => ''],
                            'value' => ['type' => 'string', 'label' => 'مقدار', 'default' => ''],
                        ],
                    ],
                ],
                'defaultProps' => ['title' => '', 'subtitle' => '', 'background' => '', 'items' => []],
            ],
            'faq' => [
                'name'     => 'سوالات متداول',
                'category' => self::CATEGORY_DYNAMIC,
                'schema'   => [
                    'title' => ['type' => 'string', 'label' => 'عنوان بخش', 'default' => 'سوالات متداول'],
                    'items' => [
                        'type' => 'repeater', 'label' => 'سوالات', 'storage' => 'array', 'default' => [],
                        'fields' => [
                            'question' => ['type' => 'string', 'label' => 'سوال', 'default' => ''],
                            'answer'   => ['type' => 'textarea', 'label' => 'پاسخ', 'default' => ''],
                        ],
                    ],
                ],
                'defaultProps' => ['title' => 'سوالات متداول', 'items' => []],
            ],
        ];

        // Phase 2i (ADR-029): this registry is no longer disconnected from
        // the canonical one — every canonical type (hero, section, stats_row,
        // ...) is now reachable through this class too, for any type this
        // file doesn't already define locally. A type defined in BOTH keeps
        // its LOCAL definition (host wins) to guarantee zero behavior change
        // — none of the ~40 locally-defined canonical-name overlaps were
        // re-verified schema-field-by-field against the canonical source in
        // this phase (a large, real remaining audit, not done here to avoid
        // an unreviewed schema change reaching a live tenant runtime). Only
        // genuinely local-only types (module:* and customer-site-only
        // content types) are exclusively defined by this class.
        self::$blocks = self::$blocks + \SaaSPlatform\ThemeRuntime\BlockRegistry::all();

        // module:gallery_widget was deliberately removed above (no backend
        // content type/model exists for it on this host) — the merge must
        // not silently resurrect it from the canonical registry, which
        // still defines it for saas-platform's own Builder preview.
        unset(self::$blocks['module:gallery_widget']);

        // Same rule applied to two more canonical-only types with no
        // backend content model on this host (Builder integrity audit,
        // 2026-09-11): module:portfolio_grid (no Portfolio model — the
        // static, editor-entered 'gallery'/'bento_grid' blocks already
        // cover this use case) and module:services_section (no Service
        // model — 'services_cards'/'service_cards' already cover it).
        // Both remain defined canonically for saas-platform's own preview.
        unset(self::$blocks['module:portfolio_grid']);
        unset(self::$blocks['module:services_section']);

        // BLD-10 (Full Product Commercial Audit, TASK 1/3): the bare
        // `products` type (title/limit/source) is a duplicate authority for
        // exactly the same responsibility `module:shop_product_grid`/
        // `product_grid` already cover, correctly, on this host — same
        // schema shape, no distinct behavior. It has never had a renderer
        // here (falls through to the empty generic fallback), and building
        // a second real product-grid renderer would be the exact kind of
        // parallel product path the Pre-Customer Convergence Rule exists
        // to prevent. Removed from the authoring palette rather than
        // duplicated; the canonical replacement already exists and is
        // production-ready.
        unset(self::$blocks['products']);

        // Placeholder re-evaluation (Full Product Commercial Audit, TASK
        // 2A): `team` is a DYNAMIC-category type designed around a "team
        // member" data source that was never built (no Team model/CRUD
        // exists anywhere in this app) — it always renders the honest
        // canonical placeholder hint text, forever, with no way to ever
        // become real. `team_cards` already covers the exact same
        // customer need (a team-members section) via direct repeater
        // authoring, production-ready. Removed rather than left as a
        // permanently-inert duplicate choice in the palette.
        unset(self::$blocks['team']);

        // Phase 3F-C — registered Theme Builder Extension SDK definitions
        // (ext:*) are bridged in on every call, NOT folded into the
        // memoized self::$blocks above. This is deliberate, not an
        // oversight: ExtensionBlockTypeRegistry::assertAvailable() (the
        // existing 3E-B collision authority) itself calls
        // ThemeCanvasBlockRegistry::all() as one of its checks, DURING
        // extension registration — i.e. before ElementRegistry has yet
        // been told about the type being registered. If the extension
        // merge were folded into the memoized self::$blocks, that
        // in-progress registration call would permanently freeze a
        // pre-registration (extension-less) snapshot for the remainder of
        // the process — the very extension being registered would never
        // become visible here again. Keeping self::$blocks as the pure
        // local+canonical snapshot (never containing an ext:*-prefixed
        // key) also preserves, rather than merely coincidentally matches,
        // that collision check's own documented assumption. The merge
        // itself costs nothing extra to repeat: CanvasBuilderAdapter reads
        // ElementRegistry::definitions(), itself already memoized.
        return self::$blocks + \App\Builder\Extension\CanvasBuilderAdapter::palette();
    }

    public static function get(string $type): ?array
    {
        return self::all()[$type] ?? null;
    }

    /**
     * Phase 3F-C — test-only cache invalidation, mirroring
     * ElementRegistry::clearCache(). Before this phase all() was 100%
     * static/config-driven within a process, so no reset ever mattered;
     * now that it also merges CanvasBuilderAdapter::palette() (which
     * reflects test-scoped extension registration/reset cycles), a test
     * that registers or resets extensions must also reset this memoized
     * snapshot. No production caller needs this — a real request is
     * always a fresh process, exactly like ElementRegistry's own cache.
     */
    public static function reset(): void
    {
        self::$blocks = null;
    }

    /**
     * Phase 3F-B — the one container-capability answer for this Canvas
     * domain, delegating to the canonical shared authority rather than the
     * Canvas editor's own hardcoded JS array (which this replaces as the
     * decision source, not merely a duplicate of it). Deliberately queries
     * the canonical registry directly rather than reading an `is_container`
     * key off self::all()[$type]: several container-relevant types
     * (section/grid/columns) are locally redefined above with a simpler,
     * Canvas-specific schema that does not carry that flag, and this must
     * stay correct regardless of local shadowing. No host-only or
     * extension-aware layer exists here yet — that is Phase 3F-C's job
     * (Extension SDK → Canvas authoring bridge); this phase only makes the
     * canonical/shared layer consumable.
     */
    public static function isContainerType(string $type): bool
    {
        // Phase 3F-C: layered exactly like App\Services\ThemeBuilder\
        // BlockRenderer::isContainerType() — canonical/shared layer first,
        // then the host/extension layer (App\Builder\ElementRegistry),
        // covering any registered ext:* type declaring
        // HasContainerCapability. Unregistered/unavailable types (including
        // unregistered ext:*) are never containers (definition() is null).
        if (\SaaSPlatform\ThemeRuntime\BlockRegistry::isContainerType($type)) {
            return true;
        }
        $definition = \App\Builder\ElementRegistry::definition($type);
        return $definition !== null && $definition->isContainer;
    }

    /**
     * Phase 2i: this registry had no empty-output-policy concept at all —
     * delegates to the canonical authority rather than reimplementing it.
     */
    public static function emptyOutputPolicy(string $type): string
    {
        return \SaaSPlatform\ThemeRuntime\BlockRegistry::emptyOutputPolicy($type);
    }

    public static function defaultProps(string $type): array
    {
        $def = self::get($type);
        return $def['defaultProps'] ?? [];
    }

    public static function byCategory(): array
    {
        $byCat = [];
        foreach (self::all() as $type => $def) {
            $cat = $def['category'] ?? 'content';
            $byCat[$cat][$type] = $def;
        }
        return $byCat;
    }

    public static function categoryLabels(): array
    {
        return [
            self::CATEGORY_LAYOUT    => 'چیدمان',
            self::CATEGORY_CONTENT   => 'محتوای ثابت',
            self::CATEGORY_DYNAMIC   => 'محتوای پویا',
            self::CATEGORY_ECOMMERCE => 'فروشگاه',
            self::CATEGORY_MARKETING => 'بازاریابی',
            self::CATEGORY_UTILITY   => 'ابزار',
            self::CATEGORY_MODULE    => 'ماژول‌ها',
        ];
    }

    public static function categoryIcons(): array
    {
        return [
            self::CATEGORY_LAYOUT    => '📐',
            self::CATEGORY_CONTENT   => '📝',
            self::CATEGORY_DYNAMIC   => '🔄',
            self::CATEGORY_ECOMMERCE => '🛒',
            self::CATEGORY_MARKETING => '📣',
            self::CATEGORY_UTILITY   => '🔧',
            self::CATEGORY_MODULE    => '🧩',
        ];
    }
}
