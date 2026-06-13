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
        if (self::$blocks !== null) {
            return self::$blocks;
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
                    'button_url'       => ['type' => 'string',  'label' => 'لینک دکمه',      'default' => ''],
                    'background'       => ['type' => 'string',  'label' => 'پس‌زمینه',       'default' => 'primary'],
                    'background_image' => ['type' => 'string',  'label' => 'تصویر پس‌زمینه', 'default' => ''],
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
                    'background_image' => ['type' => 'string',  'label' => 'تصویر پس‌زمینه', 'default' => ''],
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
            'slider' => [
                'name'     => 'اسلایدر',
                'category' => self::CATEGORY_LAYOUT,
                'schema'   => [
                    'autoplay' => ['type' => 'boolean', 'label' => 'پخش خودکار', 'default' => true],
                    'interval' => ['type' => 'number',  'label' => 'فاصله (ms)',  'default' => 5000],
                ],
                'defaultProps' => ['autoplay' => true, 'interval' => 5000],
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
                    'url'     => ['type' => 'string', 'label' => 'آدرس تصویر', 'default' => ''],
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
                    'images'  => ['type' => 'textarea', 'label' => 'آدرس تصاویر (هر خط یک آدرس)', 'default' => ''],
                ],
                'defaultProps' => ['title' => '', 'columns' => '3', 'images' => ''],
            ],
            'video' => [
                'name'     => 'ویدیو',
                'category' => self::CATEGORY_CONTENT,
                'schema'   => [
                    'url'     => ['type' => 'string', 'label' => 'آدرس ویدیو', 'default' => ''],
                    'caption' => ['type' => 'string', 'label' => 'کپشن', 'default' => ''],
                ],
                'defaultProps' => ['url' => '', 'caption' => ''],
            ],
            'button' => [
                'name'     => 'دکمه',
                'category' => self::CATEGORY_CONTENT,
                'schema'   => [
                    'text'  => ['type' => 'string', 'label' => 'متن دکمه', 'default' => ''],
                    'url'   => ['type' => 'string', 'label' => 'لینک',     'default' => '#'],
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
            'blog_posts' => [
                'name'     => 'پست‌های وبلاگ',
                'category' => self::CATEGORY_DYNAMIC,
                'schema'   => [
                    'title'   => ['type' => 'string', 'label' => 'عنوان بخش', 'default' => 'آخرین مطالب'],
                    'limit'   => ['type' => 'number', 'label' => 'تعداد پست', 'default' => 6],
                    'layout'  => ['type' => 'select', 'label' => 'چیدمان', 'options' => ['grid' => 'گرید', 'list' => 'لیست'], 'default' => 'grid'],
                ],
                'defaultProps' => ['title' => 'آخرین مطالب', 'limit' => 6, 'layout' => 'grid'],
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
            'product_slider' => [
                'name'         => 'اسلایدر محصولات',
                'category'     => self::CATEGORY_ECOMMERCE,
                'schema'       => ['limit' => ['type' => 'number', 'label' => 'تعداد', 'default' => 6]],
                'defaultProps' => ['limit' => 6],
            ],

            // ─── MARKETING ───────────────────────────────────────────────────
            'cta' => [
                'name'     => 'دعوت به اقدام (CTA)',
                'category' => self::CATEGORY_MARKETING,
                'schema'   => [
                    'title'       => ['type' => 'string', 'label' => 'عنوان',      'default' => ''],
                    'subtitle'    => ['type' => 'string', 'label' => 'زیرعنوان',  'default' => ''],
                    'button_text' => ['type' => 'string', 'label' => 'متن دکمه',   'default' => ''],
                    'button_url'  => ['type' => 'string', 'label' => 'لینک دکمه', 'default' => ''],
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
                    'source'        => ['type' => 'select', 'label' => 'منبع',          'options' => ['recent' => 'جدیدترین', 'featured' => 'ویژه', 'bestselling' => 'پرفروش'], 'default' => 'recent'],
                    'category_slug' => ['type' => 'string', 'label' => 'دسته‌بندی',     'default' => ''],
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
                    'show_price'       => ['type' => 'boolean', 'label' => 'نمایش قیمت', 'default' => true],
                    'show_instructor'  => ['type' => 'boolean', 'label' => 'نمایش مدرس', 'default' => true],
                ],
                'defaultProps' => ['title' => 'دوره‌های آموزشی', 'limit' => 6, 'columns' => '3', 'show_price' => true, 'show_instructor' => true],
            ],
            'module:pricing_table' => [
                'name'     => 'جدول اشتراک / تعرفه',
                'category' => self::CATEGORY_MODULE,
                'requires' => 'subscription',
                'schema'   => [
                    'title'               => ['type' => 'string',  'label' => 'عنوان بخش',           'default' => 'پلن‌های اشتراک'],
                    'subtitle'            => ['type' => 'string',  'label' => 'زیرعنوان',            'default' => ''],
                    'highlight_plan'      => ['type' => 'string',  'label' => 'پلن برجسته (slug)',   'default' => ''],
                    'show_annual_toggle'  => ['type' => 'boolean', 'label' => 'کلید ماهانه/سالانه', 'default' => false],
                ],
                'defaultProps' => ['title' => 'پلن‌های اشتراک', 'subtitle' => '', 'highlight_plan' => '', 'show_annual_toggle' => false],
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
                    'limit' => ['type' => 'number', 'label' => 'تعداد', 'default' => 10],
                    'style' => ['type' => 'select', 'label' => 'استایل', 'options' => ['accordion' => 'آکوردئون', 'list' => 'لیست'], 'default' => 'accordion'],
                ],
                'defaultProps' => ['title' => 'سوالات متداول', 'limit' => 10, 'style' => 'accordion'],
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
            'module:gallery_widget' => [
                'name'     => 'گالری تصاویر',
                'category' => self::CATEGORY_MODULE,
                'requires' => 'gallery',
                'schema'   => [
                    'title'   => ['type' => 'string', 'label' => 'عنوان',  'default' => 'گالری'],
                    'style'   => ['type' => 'select', 'label' => 'استایل', 'options' => ['masonry' => 'ماسونری', 'grid' => 'گرید', 'carousel' => 'چرخشی'], 'default' => 'grid'],
                    'columns' => ['type' => 'select', 'label' => 'ستون',   'options' => ['2' => '۲', '3' => '۳', '4' => '۴'], 'default' => '3'],
                ],
                'defaultProps' => ['title' => 'گالری', 'style' => 'grid', 'columns' => '3'],
            ],
            'module:reviews_grid' => [
                'name'     => 'نظرات کاربران',
                'category' => self::CATEGORY_MODULE,
                'requires' => 'reviews',
                'schema'   => [
                    'title'       => ['type' => 'string',  'label' => 'عنوان',       'default' => 'نظرات مشتریان'],
                    'limit'       => ['type' => 'number',  'label' => 'تعداد',       'default' => 6],
                    'style'       => ['type' => 'select',  'label' => 'استایل',      'options' => ['cards' => 'کارت', 'minimal' => 'مینیمال', 'carousel' => 'چرخشی'], 'default' => 'cards'],
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
        ];

        return self::$blocks;
    }

    public static function get(string $type): ?array
    {
        return self::all()[$type] ?? null;
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
