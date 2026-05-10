<?php

namespace App\Support;

/**
 * قالب‌های صفحهٔ کامل برای سازندهٔ بصری (مشابه Elementor Kits).
 * هر قالب آرایه‌ای از sectionها با ساختار builder_content_v2 است.
 */
class BuilderPageKits
{
    private static function style(): array
    {
        return ['layout' => [], 'typography' => [], 'background' => [], 'effects' => []];
    }

    private static function section(string $id, array $props, array $blocks): array
    {
        return [
            'id' => $id,
            'type' => 'section',
            'props' => $props + ['container' => true],
            'style' => self::style(),
            'blocks' => $blocks,
        ];
    }

    private static function row(string $id, string $gap, array $columns): array
    {
        return [
            'id' => $id,
            'type' => 'row',
            'props' => ['gap' => $gap],
            'style' => self::style(),
            'blocks' => $columns,
        ];
    }

    private static function col(string $id, string $width, array $widgets): array
    {
        return [
            'id' => $id,
            'type' => 'column',
            'props' => ['width' => $width],
            'style' => self::style(),
            'blocks' => $widgets,
        ];
    }

    private static function w(string $id, string $type, array $props): array
    {
        return [
            'id' => $id,
            'type' => $type,
            'props' => $props,
            'style' => self::style(),
        ];
    }

    public static function definitions(): array
    {
        return [
            'corporate_pro' => [
                'id' => 'corporate_pro',
                'label' => 'شرکتی حرفه‌ای',
                'description' => 'لندینگ سازمانی: هیرو، ارقام کلیدی، خدمات، فرایند، اعتماد، سوالات متداول و دعوت به اقدام.',
                'accent' => '#0f172a',
                'sections' => self::corporateSections(),
            ],
            'personal_pro' => [
                'id' => 'personal_pro',
                'label' => 'شخصی / رزومه',
                'description' => 'برند شخصی: معرفی، درباره، مهارت‌ها، نمونه‌کار تصویری، وبلاگ و تماس.',
                'accent' => '#7c3aed',
                'sections' => self::personalSections(),
            ],
            'shop_pro' => [
                'id' => 'shop_pro',
                'label' => 'فروشگاهی',
                'description' => 'ویترین فروشگاه: بنر تخفیف، هیرو، محصولات، مزایا، شمارش معکوس پیشنهاد، سوالات و CTA.',
                'accent' => '#059669',
                'sections' => self::shopSections(),
            ],
        ];
    }

    private static function corporateSections(): array
    {
        return [
            self::section('corp_s1', ['padding' => 'xl', 'background' => 'primary', 'container' => true], [
                self::row('corp_r1', 'lg', [
                    self::col('corp_c1', '1/2', [
                        self::w('corp_h1', 'heading', ['level' => 1, 'text' => 'راهکارهای دیجیتال برای کسب‌وکار شما']),
                        self::w('corp_t1', 'text', ['content' => '<p>تیمی متمرکز بر کیفیت، امنیت و تجربهٔ کاربری؛ از مشاوره تا اجرا در کنار شما هستیم.</p>']),
                        self::w('corp_b1', 'button', ['text' => 'درخواست جلسه', 'url' => '#contact', 'style' => 'primary']),
                        self::w('corp_b2', 'button', ['text' => 'دانلود پروفایل شرکت', 'url' => '#', 'style' => 'primary']),
                    ]),
                    self::col('corp_c2', '1/2', [
                        self::w('corp_img1', 'image', ['url' => '', 'alt' => 'دفتر کار یا تیم', 'caption' => '']),
                    ]),
                ]),
            ]),
            self::section('corp_s2', ['padding' => 'lg', 'background' => 'surface', 'container' => true], [
                self::row('corp_r2', 'lg', [
                    self::col('corp_st1', '1/3', [
                        self::w('corp_n1', 'heading', ['level' => 3, 'text' => '+۱۵۰']),
                        self::w('corp_d1', 'text', ['content' => '<p><strong>پروژهٔ تحویل‌شده</strong><br>در حوزهٔ نرم‌افزار و وب</p>']),
                    ]),
                    self::col('corp_st2', '1/3', [
                        self::w('corp_n2', 'heading', ['level' => 3, 'text' => '۹۸٪']),
                        self::w('corp_d2', 'text', ['content' => '<p><strong>رضایت مشتریان</strong><br>بر اساس نظرسنجی سالانه</p>']),
                    ]),
                    self::col('corp_st3', '1/3', [
                        self::w('corp_n3', 'heading', ['level' => 3, 'text' => '۲۴/۷']),
                        self::w('corp_d3', 'text', ['content' => '<p><strong>پشتیبانی</strong><br>در قراردادهای سازمانی</p>']),
                    ]),
                ]),
            ]),
            self::section('corp_s3', ['padding' => 'xl', 'background' => 'background', 'container' => true], [
                self::w('corp_sh', 'heading', ['level' => 2, 'text' => 'خدمات اصلی']),
                self::w('corp_stx', 'text', ['content' => '<p>از استراتژی تا پیاده‌سازی؛ بسته‌های قابل‌سفارشی‌سازی برای سازمان شما.</p>']),
                self::row('corp_r3', 'lg', [
                    self::col('corp_sv1', '1/3', [
                        self::w('corp_sv1h', 'heading', ['level' => 3, 'text' => 'مشاوره و نقشه‌راه']),
                        self::w('corp_sv1t', 'text', ['content' => '<p>تحلیل نیاز، اولویت‌بندی و نقشهٔ راه فنی شفاف با خروجی قابل اندازه‌گیری.</p>']),
                    ]),
                    self::col('corp_sv2', '1/3', [
                        self::w('corp_sv2h', 'heading', ['level' => 3, 'text' => 'طراحی و توسعه']),
                        self::w('corp_sv2t', 'text', ['content' => '<p>محصولات وب و موبایل با تمرکز بر عملکرد، امنیت و مقیاس‌پذیری.</p>']),
                    ]),
                    self::col('corp_sv3', '1/3', [
                        self::w('corp_sv3h', 'heading', ['level' => 3, 'text' => 'نگهداری و رشد']),
                        self::w('corp_sv3t', 'text', ['content' => '<p>پشتیبانی SLA، بهینه‌سازی مداوم و افزودن قابلیت بر اساس داده.</p>']),
                    ]),
                ]),
            ]),
            self::section('corp_s4', ['padding' => 'xl', 'background' => 'surface', 'container' => true], [
                self::w('corp_ph', 'heading', ['level' => 2, 'text' => 'فرایند همکاری']),
                self::row('corp_r4', 'lg', [
                    self::col('corp_p1', '1/3', [
                        self::w('corp_p1h', 'heading', ['level' => 4, 'text' => '۱. کشف و تحلیل']),
                        self::w('corp_p1t', 'text', ['content' => '<p>جلسات کارشناسی، مستندسازی نیازها و تعریف موفقیت پروژه.</p>']),
                    ]),
                    self::col('corp_p2', '1/3', [
                        self::w('corp_p2h', 'heading', ['level' => 4, 'text' => '۲. طراحی و تأیید']),
                        self::w('corp_p2t', 'text', ['content' => '<p>پروتوتایپ، بازخورد ذی‌نفعان و قفل‌کردن محدودهٔ تحویل.</p>']),
                    ]),
                    self::col('corp_p3', '1/3', [
                        self::w('corp_p3h', 'heading', ['level' => 4, 'text' => '۳. اجرا و تحویل']),
                        self::w('corp_p3t', 'text', ['content' => '<p>اسپرینت‌های کوتاه، تست و آموزش تیم شما همراه با مستندات.</p>']),
                    ]),
                ]),
            ]),
            self::section('corp_s5', ['padding' => 'lg', 'background' => 'background', 'container' => true], [
                self::w('corp_trust', 'heading', ['level' => 3, 'text' => 'مورد اعتماد سازمان‌ها و استارتاپ‌ها']),
                self::row('corp_r5', 'md', [
                    self::col('corp_l1', '1/4', [self::w('corp_l1t', 'text', ['content' => '<p class="tb-text-muted">لوگوی شریک ۱</p>'])]),
                    self::col('corp_l2', '1/4', [self::w('corp_l2t', 'text', ['content' => '<p class="tb-text-muted">لوگوی شریک ۲</p>'])]),
                    self::col('corp_l3', '1/4', [self::w('corp_l3t', 'text', ['content' => '<p class="tb-text-muted">لوگوی شریک ۳</p>'])]),
                    self::col('corp_l4', '1/4', [self::w('corp_l4t', 'text', ['content' => '<p class="tb-text-muted">لوگوی شریک ۴</p>'])]),
                ]),
            ]),
            self::section('corp_s6', ['padding' => 'xl', 'background' => 'surface', 'container' => true], [
                self::w('corp_faq', 'faq', [
                    'title' => 'سوالات متداول',
                    'items' => [
                        ['question' => 'زمان شروع پروژه چقدر است؟', 'answer' => '<p>پس از امضای قرارداد و تکمیل مستندات، معمولاً ظرف ۱ تا ۲ هفته کاری اسپرینت اول آغاز می‌شود.</p>'],
                        ['question' => 'امکان همکاری حضوری/از راه دور؟', 'answer' => '<p>بله؛ جلسات آنلاین به‌صورت پیش‌فرض و در صورت نیاز، بازدید حضوری هماهنگ می‌شود.</p>'],
                        ['question' => 'پشتیبانی پس از تحویل چگونه است؟', 'answer' => '<p>بسته‌های نگهداری با SLA مشخص؛ رفع باگ، به‌روزرسانی امنیتی و توسعهٔ تدریجی.</p>'],
                    ],
                ]),
            ]),
            self::section('corp_s7', ['padding' => 'lg', 'background' => 'primary', 'container' => true], [
                self::w('corp_cta', 'cta', [
                    'title' => 'آمادهٔ گفت‌وگو هستیم',
                    'subtitle' => 'یک جلسهٔ کوتاه رایگان برای بررسی نیاز شما رزرو کنید.',
                    'button_text' => 'تماس با ما',
                    'button_url' => '#contact',
                    'background' => 'primary',
                ]),
            ]),
        ];
    }

    private static function personalSections(): array
    {
        return [
            self::section('per_s1', ['padding' => 'xl', 'background' => 'surface', 'container' => true], [
                self::w('per_hero', 'hero', [
                    'title' => 'سلام، من [نام شما] هستم',
                    'subtitle' => 'طراح محصول، توسعه‌دهنده فرانت‌اند یا مشاور — تخصص خود را اینجا بنویسید.',
                    'button_text' => 'دانلود رزومه',
                    'button_url' => '#',
                    'background' => 'primary',
                    'align' => 'center',
                ]),
                self::w('per_sp1', 'spacer', ['height' => 'md']),
                self::w('per_soc', 'social_icons', [
                    'links' => [
                        ['label' => 'لینکدین', 'url' => '#'],
                        ['label' => 'گیت‌هاب', 'url' => '#'],
                        ['label' => 'اینستاگرام', 'url' => '#'],
                    ],
                ]),
            ]),
            self::section('per_s2', ['padding' => 'xl', 'background' => 'background', 'container' => true], [
                self::row('per_r1', 'lg', [
                    self::col('per_c1', '1/2', [
                        self::w('per_abh', 'heading', ['level' => 2, 'text' => 'دربارهٔ من']),
                        self::w('per_abt', 'text', ['content' => '<p>چند پاراگراف دربارهٔ مسیر حرفه‌ای، ارزش‌های کاری و نوع پروژه‌هایی که دوست دارید انجام دهید. تجربهٔ کلیدی و دستاوردها را شفاف بنویسید.</p><p>می‌توانید لینک به نمونه‌کارها یا مقالات تخصصی اضافه کنید.</p>']),
                        self::w('per_b1', 'button', ['text' => 'مشاهده پروژه‌ها', 'url' => '#portfolio', 'style' => 'primary']),
                    ]),
                    self::col('per_c2', '1/2', [
                        self::w('per_img', 'image', ['url' => '', 'alt' => 'تصویر حرفه‌ای', 'caption' => '']),
                    ]),
                ]),
            ]),
            self::section('per_s3', ['padding' => 'xl', 'background' => 'surface', 'container' => true], [
                self::w('per_sk', 'heading', ['level' => 2, 'text' => 'مهارت‌ها و ابزارها']),
                self::row('per_r2', 'lg', [
                    self::col('per_sk1', '1/3', [
                        self::w('per_sk1h', 'heading', ['level' => 4, 'text' => 'طراحی']),
                        self::w('per_sk1t', 'text', ['content' => '<p>سیستم طراحی، پروتوتایپ، تست کاربری</p>']),
                    ]),
                    self::col('per_sk2', '1/3', [
                        self::w('per_sk2h', 'heading', ['level' => 4, 'text' => 'توسعه']),
                        self::w('per_sk2t', 'text', ['content' => '<p>فرانت‌اند مدرن، عملکرد، دسترسی‌پذیری</p>']),
                    ]),
                    self::col('per_sk3', '1/3', [
                        self::w('per_sk3h', 'heading', ['level' => 4, 'text' => 'همکاری']),
                        self::w('per_sk3t', 'text', ['content' => '<p>اسکرام، مستندسازی، انتقال دانش</p>']),
                    ]),
                ]),
            ]),
            self::section('per_s4', ['padding' => 'xl', 'background' => 'background', 'container' => true], [
                self::w('per_gal_h', 'heading', ['level' => 2, 'text' => 'نمونه‌کار']),
                self::w('per_gal', 'gallery', [
                    'title' => '',
                    'items' => [
                        ['url' => '', 'alt' => 'پروژه ۱'],
                        ['url' => '', 'alt' => 'پروژه ۲'],
                        ['url' => '', 'alt' => 'پروژه ۳'],
                    ],
                ]),
            ]),
            self::section('per_s5', ['padding' => 'xl', 'background' => 'surface', 'container' => true], [
                self::w('per_blog', 'blog_posts', ['title' => 'آخرین نوشته‌ها', 'limit' => 3]),
            ]),
            self::section('per_s6', ['padding' => 'xl', 'background' => 'primary', 'container' => true], [
                self::w('per_cta', 'cta', [
                    'title' => 'بیایید روی ایدهٔ شما کار کنیم',
                    'subtitle' => 'برای همکاری فریلنس یا پروژهٔ ثابت در تماس باشید.',
                    'button_text' => 'ارسال پیام',
                    'button_url' => '/contact',
                    'background' => 'primary',
                ]),
            ]),
        ];
    }

    private static function shopSections(): array
    {
        return [
            self::section('shop_s0', ['padding' => 'none', 'background' => 'transparent', 'container' => true], [
                self::w('shop_promo', 'promo_banner', [
                    'text' => '🎉 ارسال رایگان برای سفارش بالای ۵۰۰ هزار تومان — تا پایان هفته',
                    'link' => '/shop',
                    'style' => 'info',
                ]),
            ]),
            self::section('shop_s1', ['padding' => 'xl', 'background' => 'surface', 'container' => true], [
                self::row('shop_r1', 'lg', [
                    self::col('shop_c1', '1/2', [
                        self::w('shop_h1', 'heading', ['level' => 1, 'text' => 'فروشگاهی که به‌موقع تحویل می‌دهد']),
                        self::w('shop_t1', 'text', ['content' => '<p>محصولات منتخب با گارانتی اصالت، بسته‌بندی حرفه‌ای و پشتیبانی واقعی پس از خرید.</p>']),
                        self::w('shop_b1', 'button', ['text' => 'مشاهده محصولات', 'url' => '/shop', 'style' => 'primary']),
                        self::w('shop_b2', 'button', ['text' => 'درباره ما', 'url' => '#', 'style' => 'primary']),
                    ]),
                    self::col('shop_c2', '1/2', [
                        self::w('shop_img', 'image', ['url' => '', 'alt' => 'ویترین فروشگاه', 'caption' => '']),
                    ]),
                ]),
            ]),
            self::section('shop_s2', ['padding' => 'xl', 'background' => 'background', 'container' => true], [
                self::w('shop_ph', 'heading', ['level' => 2, 'text' => 'پرفروش‌ترین‌ها']),
                self::w('shop_pg', 'product_grid', ['columns' => 4, 'limit' => 8]),
            ]),
            self::section('shop_s3', ['padding' => 'lg', 'background' => 'surface', 'container' => true], [
                self::row('shop_r3', 'lg', [
                    self::col('shop_f1', '1/3', [
                        self::w('shop_f1h', 'heading', ['level' => 3, 'text' => 'ارسال سریع']),
                        self::w('shop_f1t', 'text', ['content' => '<p>ارسال به سراسر کشور با رهگیری مرسوله.</p>']),
                    ]),
                    self::col('shop_f2', '1/3', [
                        self::w('shop_f2h', 'heading', ['level' => 3, 'text' => 'ضمانت اصالت']),
                        self::w('shop_f2t', 'text', ['content' => '<p>۷ روز مهلت بازگشت مطابق قوانین فروشگاه.</p>']),
                    ]),
                    self::col('shop_f3', '1/3', [
                        self::w('shop_f3h', 'heading', ['level' => 3, 'text' => 'پشتیبانی']),
                        self::w('shop_f3t', 'text', ['content' => '<p>پاسخ‌گویی در ساعات کاری از طریق تیکت و تلفن.</p>']),
                    ]),
                ]),
            ]),
            self::section('shop_s4', ['padding' => 'lg', 'background' => 'primary', 'container' => true], [
                self::w('shop_cd', 'countdown', [
                    'end_date' => '',
                    'label' => 'پایان تخفیف ویژه — تاریخ پایان را در المان تنظیم کنید',
                ]),
            ]),
            self::section('shop_s5', ['padding' => 'xl', 'background' => 'background', 'container' => true], [
                self::w('shop_faq', 'faq', [
                    'title' => 'خرید مطمئن',
                    'items' => [
                        ['question' => 'نحوهٔ پرداخت چیست؟', 'answer' => '<p>پرداخت آنلاین از درگاه بانکی و در صورت فعال بودن، پرداخت در محل.</p>'],
                        ['question' => 'زمان ارسال چقدر است؟', 'answer' => '<p>بسته به شهر معمولاً ۲ تا ۵ روز کاری؛ جزئیات در صفحهٔ محصول.</p>'],
                        ['question' => 'شرایط گارانتی؟', 'answer' => '<p>طبق برگهٔ گارانتی هر محصول؛ برای کالای غیراصل، مرجوع کامل.</p>'],
                    ],
                ]),
            ]),
            self::section('shop_s6', ['padding' => 'lg', 'background' => 'surface', 'container' => true], [
                self::w('shop_cta', 'cta', [
                    'title' => 'هنوز سوالی دارید؟',
                    'subtitle' => 'تیم فروش آمادهٔ راهنمایی است.',
                    'button_text' => 'تماس با پشتیبانی',
                    'button_url' => '/contact',
                    'background' => 'primary',
                ]),
            ]),
        ];
    }
}
