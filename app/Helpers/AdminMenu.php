<?php

namespace App\Helpers;

use App\Models\License;

/**
 * منوی پنل ادمین سایت مشتری (customer-site).
 * گروه‌بندی منو با زیرشاخه‌های مشخص؛ ماژول‌های فعال از پلتفرم به‌صورت پویا اضافه می‌شوند.
 */
class AdminMenu
{
    public const MENU_KEYS = [
        'dashboard' => 'داشبورد',
        'pages' => 'صفحات',
        'categories' => 'دسته‌بندی‌های وبلاگ',
        'post_series' => 'مجموعه‌های مطالب',
        'posts' => 'مطالب وبلاگ',
        'products' => 'محصولات',
        'product_attributes' => 'ویژگی‌های فنی محصول',
        'shop_product_reviews' => 'نظرات خریداران (فروشگاه)',
        'product_categories' => 'دسته‌بندی محصولات',
        'orders' => 'سفارشات',
        'coupon' => 'کوپن‌ها',
        'reviews' => 'نظرات و امتیازدهی',
        'referral' => 'معرفی دوستان',
        'subscription' => 'پلن‌های اشتراک',
        'whatsapp' => 'پاسخگویی شبکه‌ها',
        'sms' => 'پیامک',
        'sms_campaigns' => 'کمپین‌های پیامکی',
        'courses' => 'دوره‌ها و کلاس‌ها',
        'invoice' => 'فاکتورها',
        'business_card' => 'کارت ویزیت دیجیتال',
        'social_links' => 'شبکه‌های اجتماعی',
        'advertisements' => 'تبلیغات',
        'notifications' => 'اعلان‌ها',
        'forms' => 'فرم‌ساز',
        'forms_submissions' => 'گزارش ارسال فرم‌ها',
        'captcha' => 'تنظیمات کپچا',
        'theme_design' => 'ظاهر و تم',
        'theme_marketplace' => 'فروشگاه قالب‌ها',
        'theme_bundles' => 'باندل‌های قالب',
        'builder_health' => 'سلامت سازنده',
        'settings' => 'تنظیمات',
        'currency_reform' => 'حذف ۴ صفر پول',
        'license' => 'لایسنس',
        'modules' => 'ماژول‌ها',
        'modules_changelog' => 'تاریخچه ماژول‌ها',
        'module_store' => 'فروشگاه ماژول',
        'support'         => 'صندوق پشتیبانی',
        'support_history' => 'تاریخچه تیکت‌ها',
        'users_panel'     => 'پنل کاربران',
        'activity_log' => 'لاگ فعالیت',
        'advanced_search' => 'جستجوی پیشرفته',
        'menu' => 'منو و ناوبری',
        'bank_gateway' => 'درگاه بانکی',
        'accounting' => 'حسابداری',
        'education_center' => 'مرکز آموزش',
        'site_content'    => 'دستیار محتوا',
        'installments'    => 'خرید قسطی',
        'access_lists'    => 'لیست‌های دسترسی',
        'homepage_videos' => 'ویدیوهای صفحه اصلی',
        'translations'    => 'ترجمه‌های AI',
        'analytics_local' => 'آمار و تحلیل',
        'system_alerts'   => 'مرکز اعلان‌ها',
        // Platform modules with real dedicated admin UI (moved from dynamic to static)
        'crm'             => 'مخاطبین و CRM',
        'inventory'       => 'انبار و موجودی',
        'seo_advanced'    => 'سئو پیشرفته',
    ];

    /** عناوین ماژول‌های پلتفرم که در منو به‌صورت پویا می‌آیند (slug => عنوان فارسی) */
    public const PLATFORM_MODULE_LABELS = [
        'seo_advanced' => 'سئو پیشرفته',
        'multi_currency' => 'چندارزی پویا',
        'marketplace_sync' => 'همگام‌سازی مارکت‌پلیس',
        'video' => 'ویدیو و ولاگ',
        'projects' => 'پروژه‌ها',
        'qa' => 'پرسش و پاسخ',
        'menu' => 'منو و ناوبری',
        'announcements' => 'اعلان‌ها و اخبار',
        'backup' => 'پشتیبان‌گیری',
        'cloud' => 'ادغام ابر',
        'podcast' => 'پادکست',
        'marketing_automation' => 'اتوماسیون بازاریابی',
        'discount_advanced' => 'تخفیف پیشرفته',
        'wallet' => 'کیف پول',
        'product_bundling' => 'بسته‌بندی محصول',
        'shipping' => 'حمل و ارسال',
        'inventory' => 'انبار و موجودی',
        'accounting' => 'حسابداری',
        'gallery' => 'گالری',
        'services' => 'خدمات',
        'tasks' => 'وظیفه و کار',
        'contract' => 'قرارداد',
        'media' => 'رسانه',
        'brand' => 'برند',
        'i18n' => 'زبان و چندگانه',
        'crm' => 'مخاطبین و CRM',
        'analytics' => 'تحلیل پیشرفته',
        'recruitment' => 'استخدام',
        'portfolio' => 'پورتفولیو',
        'omnichannel' => 'پاسخ همه‌کاناله',
        'messaging' => 'پاسخگویی شبکه‌ها',
        'bank_gateway' => 'درگاه بانکی',
        'advanced_search' => 'جستجوی پیشرفته',
    ];

    /**
     * Platform modules that render a description-only "coming soon" page
     * (PlatformModuleController::show). These must NOT appear in the sidebar
     * because clicking them shows no actionable UI — only marketing text.
     * They remain accessible via admin/modules catalog page.
     */
    protected const MODULE_DESCRIPTION_ONLY = [
        'analytics',           // duplicate of analytics_local; description-only
        'announcements',
        'backup',
        'brand',
        'cloud',
        'contract',
        'discount_advanced',
        'gallery',
        'i18n',
        'marketing_automation',
        'marketplace_sync',
        'multi_currency',
        'omnichannel',
        'podcast',
        'portfolio',
        'product_bundling',
        'projects',
        'qa',
        'recruitment',
        'services',
        'shipping',
        'tasks',
        'video',              // homepage_videos is the real video management
        'wallet',
    ];

    public const MENU_ROUTES = [
        'dashboard'        => 'admin.dashboard',
        'analytics_local'  => 'admin.analytics.index',
        'system_alerts'    => 'admin.notification-center.index',
        'installments'     => 'admin.installments.plans.index',
        'access_lists'     => 'admin.access-lists.lists.index',
        'homepage_videos'  => 'admin.homepage-videos.index',
        'translations'     => 'admin.translations.index',
        'pages' => 'admin.pages.index',
        'categories' => 'admin.categories.index',
        'post_series' => 'admin.post-series.index',
        'posts' => 'admin.posts.index',
        'products' => 'admin.products.index',
        'product_attributes' => 'admin.product-attributes.index',
        'shop_product_reviews' => 'admin.shop-product-reviews.index',
        'product_categories' => 'admin.product-categories.index',
        'orders' => 'admin.orders.index',
        'coupon' => 'admin.coupons.index',
        'reviews' => 'admin.reviews.index',
        'referral' => 'admin.referrals.index',
        'subscription' => 'admin.subscriptions.index',
        'whatsapp' => 'admin.whatsapp.index',
        'sms' => 'admin.sms.index',
        'sms_campaigns' => 'admin.sms.campaigns.index',
        'courses' => 'admin.courses.index',
        'invoice' => 'admin.invoices.index',
        'business_card' => 'admin.business-cards.index',
        'social_links' => 'admin.social-links.index',
        'advertisements' => 'admin.advertisements.index',
        'notifications' => 'admin.notifications.index',
        'forms' => 'admin.forms.index',
        'forms_submissions' => 'admin.forms.submissions.index',
        'captcha' => 'admin.captcha.index',
        'theme_design' => 'admin.theme-design.index',
        'theme_marketplace' => 'admin.theme-marketplace.index',
        'theme_bundles' => 'admin.theme-bundles.index',
        'builder_health' => 'admin.builder-health.index',
        'settings' => 'admin.settings.index',
        'currency_reform' => 'admin.currency-reform.index',
        'license' => 'admin.license.index',
        'modules' => 'admin.modules.index',
        'modules_changelog' => 'admin.modules.changelog',
        'module_store' => 'admin.module-store.index',
        'support'         => 'admin.inbox.index',
        'support_history' => 'admin.support.history',
        'users_panel'     => 'admin.users.index',
        'activity_log' => 'admin.activity-log.index',
        'advanced_search' => 'admin.modules.show',
        'menu' => 'admin.menus.index',
        'bank_gateway' => 'admin.payment-gateways.index',
        'accounting' => 'admin.accounting.index',
        'education_center' => 'admin.education-center.index',
        'site_content'    => 'admin.site-content.index',
        'seo_advanced' => 'admin.seo-advanced.index',
        'analytics' => 'admin.analytics.index',
        'announcements' => 'admin.announcements.index',
        'backup' => 'admin.backup.index',
        'brand' => 'admin.brand.index',
        'cloud' => 'admin.cloud.index',
        'contract' => 'admin.contract.index',
        'crm' => 'admin.crm.index',
        'discount_advanced' => 'admin.discount-advanced.index',
        'gallery' => 'admin.gallery.index',
        'i18n' => 'admin.i18n.index',
        'inventory' => 'admin.inventory.index',
        'marketing_automation' => 'admin.marketing-automation.index',
        'marketplace_sync' => 'admin.marketplace-sync.index',
        'multi_currency' => 'admin.multi-currency.index',
        'omnichannel' => 'admin.omnichannel.index',
        'podcast' => 'admin.podcast.index',
        'portfolio' => 'admin.portfolio.index',
        'product_bundling' => 'admin.product-bundling.index',
        'projects' => 'admin.projects.index',
        'qa' => 'admin.qa.index',
        'recruitment' => 'admin.recruitment.index',
        'services' => 'admin.services.index',
        'shipping' => 'admin.shipping.index',
        'tasks' => 'admin.tasks.index',
        'video' => 'admin.video.index',
        'wallet' => 'admin.wallet.index',
    ];

    /**
     * حالت UI ماژول‌ها در پنل ادمین سایت.
     * standalone = صفحهٔ مستقل در منو، embedded = فقط در بطن ماژول‌های دیگر/فرانت استفاده می‌شود.
     */
    protected const MODULE_UI_MODE = [
        // ماژول‌های داخلی سایت
        'advanced_search' => 'standalone',
        'menu' => 'standalone',
        'bank_gateway' => 'standalone',

        // ماژول‌های پلتفرمی
        'seo_advanced' => 'standalone',
        'multi_currency' => 'standalone',
        'marketplace_sync' => 'standalone',
        'discount_advanced' => 'standalone',
        'wallet' => 'standalone',
        'product_bundling' => 'standalone',
        'shipping' => 'standalone',
        'inventory' => 'standalone',
        'accounting' => 'standalone',
        'analytics' => 'standalone',
        'omnichannel' => 'standalone',
        'media' => 'embedded',
        'crm' => 'standalone',
    ];

    public const MENU_ROUTE_PATTERNS = [
        'dashboard' => 'admin.dashboard',
        'pages' => 'admin.pages.*',
        'categories' => 'admin.categories.*',
        'post_series' => 'admin.post-series.*',
        'posts' => 'admin.posts.*',
        'products' => 'admin.products.*',
        'product_attributes' => 'admin.product-attributes.*',
        'shop_product_reviews' => 'admin.shop-product-reviews.*',
        'product_categories' => 'admin.product-categories.*',
        'orders' => 'admin.orders.*',
        'coupon' => 'admin.coupons.*',
        'reviews' => 'admin.reviews.*',
        'referral' => 'admin.referrals.*',
        'subscription' => 'admin.subscriptions.*',
        'whatsapp' => 'admin.whatsapp.*',
        'sms' => 'admin.sms.*',
        'sms_campaigns' => 'admin.sms.campaigns.*',
        'courses' => 'admin.courses.*',
        'invoice' => 'admin.invoices.*',
        'business_card' => 'admin.business-cards.*',
        'social_links' => 'admin.social-links.*',
        'advertisements' => 'admin.advertisements.*',
        'notifications' => 'admin.notifications.*',
        'forms' => 'admin.forms.*',
        'forms_submissions' => 'admin.forms.submissions.*',
        'captcha' => 'admin.captcha.*',
        'theme_design' => 'admin.theme-design.index',
        'theme_marketplace' => 'admin.theme-marketplace.*',
        'theme_bundles' => 'admin.theme-bundles.*',
        'builder_health' => 'admin.builder-health.*',
        'settings' => 'admin.settings.*',
        'currency_reform' => 'admin.currency-reform.*',
        'license' => 'admin.license.*',
        'modules' => 'admin.modules.*',
        'modules_changelog' => 'admin.modules.changelog',
        'module_store' => 'admin.module-store.index',
        'support'      => 'admin.inbox.*',
        'users_panel'  => 'admin.users.*',
        'activity_log' => 'admin.activity-log.*',
        'advanced_search' => 'admin.modules.show',
        'menu' => 'admin.menus.index',
        'bank_gateway' => 'admin.payment-gateways.*',
        'accounting' => 'admin.accounting.*',
        'education_center' => 'admin.education-center.*',
        'site_content'    => 'admin.site-content.*',
        'installments'    => 'admin.installments.*',
        'access_lists'    => 'admin.access-lists.*',
        'homepage_videos' => 'admin.homepage-videos.*',
        'translations'    => 'admin.translations.*',
        'analytics_local' => 'admin.analytics.*',
        'system_alerts'   => 'admin.notification-center.*',
        'seo_advanced' => 'admin.seo-advanced.*',
        'analytics' => 'admin.analytics.*',
        'announcements' => 'admin.announcements.*',
        'backup' => 'admin.backup.*',
        'brand' => 'admin.brand.*',
        'cloud' => 'admin.cloud.*',
        'contract' => 'admin.contract.*',
        'crm' => 'admin.crm.*',
        'discount_advanced' => 'admin.discount-advanced.*',
        'gallery' => 'admin.gallery.*',
        'i18n' => 'admin.i18n.*',
        'inventory' => 'admin.inventory.*',
        'marketing_automation' => 'admin.marketing-automation.*',
        'marketplace_sync' => 'admin.marketplace-sync.*',
        'multi_currency' => 'admin.multi-currency.*',
        'omnichannel' => 'admin.omnichannel.*',
        'podcast' => 'admin.podcast.*',
        'portfolio' => 'admin.portfolio.*',
        'product_bundling' => 'admin.product-bundling.*',
        'projects' => 'admin.projects.*',
        'qa' => 'admin.qa.*',
        'recruitment' => 'admin.recruitment.*',
        'services' => 'admin.services.*',
        'shipping' => 'admin.shipping.*',
        'tasks' => 'admin.tasks.*',
        'video' => 'admin.video.*',
        'wallet' => 'admin.wallet.*',
    ];

    /** حالت UI برای یک کلید منو/ماژول. */
    protected static function uiMode(string $key): string
    {
        return self::MODULE_UI_MODE[$key] ?? 'standalone';
    }

    /** آیا این ماژول/کلید، صفحهٔ مستقل در منو دارد؟ */
    protected static function hasStandalonePanel(string $key): bool
    {
        return self::uiMode($key) === 'standalone';
    }

    /** کلیدهایی که در گروه‌های ثابت منو هستند (غیر از گروه پویا). */
    protected static function getStaticMenuKeys(): array
    {
        $keys = [];
        foreach (self::getStaticGroups() as $groupKeys) {
            foreach ($groupKeys as $k) {
                $keys[$k] = true;
            }
        }
        return array_keys($keys);
    }

    /** گروه‌های ثابت منو (بدون گروه پویا). */
    protected static function getStaticGroups(): array
    {
        return [
            // 1 ─ داشبورد
            'داشبورد' => ['dashboard'],

            // 2 ─ ساختار سایت: صفحات، منو، محتوای ثابت، جستجو
            'وبسایت' => ['pages', 'menu', 'site_content', 'advanced_search'],

            // 3 ─ محتوای پویا: وبلاگ، دوره‌ها، ویدیو، ترجمه، مستندات
            'محتوا' => ['categories', 'post_series', 'posts', 'courses', 'homepage_videos', 'translations', 'education_center'],

            // 4 ─ ظاهر و قالب
            'ظاهر' => ['theme_design', 'theme_marketplace', 'theme_bundles'],

            // 5 ─ فروشگاه: محصولات، سفارشات، کوپن، قسطی، انبار (license-based)
            'فروشگاه' => ['product_categories', 'products', 'product_attributes', 'shop_product_reviews', 'orders', 'coupon', 'installments', 'inventory'],

            // 6 ─ مارکتینگ: فرم‌ها، کمپین‌ها، ارتباطات، تبلیغات
            'مارکتینگ' => ['forms', 'forms_submissions', 'access_lists', 'referral', 'whatsapp', 'sms', 'sms_campaigns', 'notifications', 'advertisements'],

            // 7 ─ کسب‌وکار: CRM، نظرات، آمار، سئو، شبکه‌های اجتماعی، کارت ویزیت
            'کسب‌وکار' => ['crm', 'reviews', 'analytics_local', 'seo_advanced', 'social_links', 'business_card'],

            // 8 ─ مالی: اشتراک، فاکتور، حسابداری، درگاه
            'مالی' => ['subscription', 'invoice', 'accounting', 'bank_gateway'],

            // 9 ─ تنظیمات و امنیت
            'تنظیمات' => ['captcha', 'settings', 'currency_reform'],

            // 10 ─ پشتیبانی و کاربران
            'پشتیبانی' => ['support', 'users_panel'],

            // 11 ─ سیستم: لایسنس، ماژول‌ها، لاگ، مانیتورینگ، سلامت
            'سیستم' => ['license', 'modules', 'modules_changelog', 'module_store', 'activity_log', 'system_alerts', 'builder_health'],
        ];
    }

    /**
     * ترتیب نمایش در کاتالوگ ماژول‌ها (صفحهٔ admin.modules.index): ابتدا دسته، سپس نامک.
     *
     * @param  array<int, string>  $slugs
     * @return array<int, string>
     */
    public static function sortLicensedModuleSlugsForDisplay(array $slugs): array
    {
        $slugs = array_values(array_unique(array_filter($slugs, 'is_string')));
        usort($slugs, function (string $a, string $b): int {
            $metaA = self::moduleCatalogMeta($a);
            $metaB = self::moduleCatalogMeta($b);
            if ($metaA['sort'] !== $metaB['sort']) {
                return $metaA['sort'] <=> $metaB['sort'];
            }

            return strnatcasecmp($a, $b);
        });

        return $slugs;
    }

    /** نگاشت slug لایسنس به کلید آیکن سایدبار (همان partial). */
    public static function moduleTileIconKey(string $slug): string
    {
        $slug = $slug === 'messaging' ? 'whatsapp' : $slug;
        $map = [
            'blog' => 'posts',
            'shop' => 'products',
            'forms' => 'forms',
            'advanced_search' => 'advanced_search',
            'menu' => 'menu',
            'advertisements' => 'advertisements',
            'notifications' => 'notifications',
            'coupon' => 'coupon',
            'messaging' => 'whatsapp',
        ];

        return $map[$slug] ?? $slug;
    }

    /**
     * دستهٔ نمایشی یک ماژول در گرید «ماژول‌های خریداری‌شده».
     *
     * @return array{sort: int, label: string, pill_class: string}
     */
    public static function moduleCatalogMeta(string $slug): array
    {
        $slug = $slug === 'messaging' ? 'whatsapp' : $slug;

        $defs = [
            'content' => [
                'sort' => 10,
                'label' => 'محتوا و وب‌سایت',
                'pill_class' => 'admin-module-cat-pill admin-module-cat-pill--content',
                'slugs' => [
                    'blog', 'forms', 'advanced_search', 'menu', 'gallery', 'podcast', 'video',
                    'portfolio', 'projects', 'qa', 'services', 'pages', 'media',
                ],
            ],
            'commerce' => [
                'sort' => 20,
                'label' => 'فروش و تجارت',
                'pill_class' => 'admin-module-cat-pill admin-module-cat-pill--commerce',
                'slugs' => [
                    'shop', 'coupon', 'reviews', 'referral', 'subscription', 'courses', 'invoice',
                    'business_card', 'social_links', 'wallet', 'product_bundling', 'inventory',
                    'shipping', 'multi_currency', 'marketplace_sync', 'discount_advanced',
                ],
            ],
            'marketing' => [
                'sort' => 30,
                'label' => 'مارکتینگ و ارتباط',
                'pill_class' => 'admin-module-cat-pill admin-module-cat-pill--marketing',
                'slugs' => [
                    'seo_advanced', 'analytics', 'marketing_automation', 'announcements',
                    'notifications', 'whatsapp', 'sms', 'omnichannel', 'crm', 'advertisements',
                ],
            ],
            'system' => [
                'sort' => 40,
                'label' => 'سیستم و عملیات',
                'pill_class' => 'admin-module-cat-pill admin-module-cat-pill--system',
                'slugs' => [
                    'accounting', 'backup', 'cloud', 'brand', 'i18n', 'tasks', 'contract',
                    'recruitment',
                ],
            ],
        ];

        foreach ($defs as $def) {
            if (in_array($slug, $def['slugs'], true)) {
                return [
                    'sort' => $def['sort'],
                    'label' => $def['label'],
                    'pill_class' => $def['pill_class'],
                ];
            }
        }

        return [
            'sort' => 90,
            'label' => 'سایر امکانات',
            'pill_class' => 'admin-module-cat-pill admin-module-cat-pill--other',
        ];
    }

    /** ماژول‌های فعال از پلتفرم (از لایسنس)؛ messaging به whatsapp نرمال می‌شود. */
    public static function getEnabledPlatformModuleSlugs(): array
    {
        $license = License::current();
        if (!$license || !$license->isValid() || !is_array($license->modules)) {
            return [];
        }
        $slugs = [];
        foreach ($license->modules as $slug) {
            if (!is_string($slug)) {
                continue;
            }
            $slugs[] = $slug === 'messaging' ? 'whatsapp' : $slug;
        }
        return array_values(array_unique($slugs));
    }

    /** کلیدهای منو برای گروه پویا «ماژول‌های از پلتفرم» (فقط ماژول‌هایی که در گروه ثابت نیستند). */
    protected static function getDynamicPlatformMenuKeys(): array
    {
        $staticKeys = array_fill_keys(self::getStaticMenuKeys(), true);
        $enabled = self::getEnabledPlatformModuleSlugs();
        $out = [];
        foreach ($enabled as $slug) {
            // Skip modules already placed in static groups (crm, inventory, seo_advanced, etc.)
            if (isset($staticKeys[$slug])) {
                continue;
            }
            // Skip modules that render description-only "coming soon" pages — no real UI
            if (in_array($slug, self::MODULE_DESCRIPTION_ONLY, true)) {
                continue;
            }
            if (! self::hasStandalonePanel($slug)) {
                continue;
            }
            $out[] = $slug;
        }
        return $out;
    }

    public static function getGroups(): array
    {
        $groups = self::getStaticGroups();
        foreach (self::getDynamicPlatformMenuKeys() as $key) {
            $groups[self::getTitle($key)] = [$key];
        }
        return $groups;
    }

    /**
     * منو فقط برای ماژول‌های فعال از پلتفرم/لایسنس نمایش داده می‌شود.
     * بدون لایسنس معتبر فقط dashboard و pages و settings و license و module_store (در صورت تنظیم پلتفرم) و support.
     */
    public static function isVisible(string $key): bool
    {
        // ماژول‌هایی که فقط embedded هستند، هیچ‌وقت آیتم مستقل در منو نداشته باشند
        if (isset(self::MODULE_UI_MODE[$key]) && ! self::hasStandalonePanel($key)) {
            return false;
        }

        if (in_array($key, self::getDynamicPlatformMenuKeys(), true)) {
            return true;
        }
        if (in_array($key, ['dashboard', 'pages', 'captcha', 'theme_design', 'builder_health', 'settings',
                             'license', 'modules', 'modules_changelog', 'support',
                             'activity_log', 'users_panel', 'education_center',
                             'site_content', 'analytics_local', 'system_alerts'], true)) {
            return true;
        }
        if ($key === 'installments') {
            return SiteHelper::moduleEnabled('installments');
        }
        if ($key === 'translations') {
            return SiteHelper::moduleEnabled('translations');
        }
        if ($key === 'homepage_videos') {
            return SiteHelper::moduleEnabled('homepage_videos');
        }
        if ($key === 'access_lists') {
            return SiteHelper::moduleEnabled('access_lists');
        }
        if ($key === 'module_store') {
            return rtrim((string) config('site.platform_url', ''), '/') !== '';
        }
        if ($key === 'theme_marketplace' || $key === 'theme_bundles') {
            return rtrim((string) config('site.platform_url', ''), '/') !== '';
        }
        if ($key === 'categories' || $key === 'posts' || $key === 'post_series') {
            return SiteHelper::blogEnabled();
        }
        if ($key === 'currency_reform') {
            return SiteHelper::moduleEnabled('currency_reform');
        }
        if ($key === 'advertisements') {
            return SiteHelper::moduleEnabled('advertisements');
        }
        if ($key === 'notifications') {
            return self::notificationsPanelVisible();
        }
        if ($key === 'forms' || $key === 'forms_submissions') {
            return SiteHelper::moduleEnabled('forms');
        }
        if ($key === 'products' || $key === 'product_categories' || $key === 'product_attributes' || $key === 'shop_product_reviews' || $key === 'orders') {
            return SiteHelper::shopEnabled();
        }
        if ($key === 'advanced_search') {
            return SiteHelper::moduleEnabled('advanced_search');
        }
        if ($key === 'menu') {
            return SiteHelper::moduleEnabled($key);
        }
        if ($key === 'bank_gateway') {
            return true; // درگاه پرداخت همیشه در منو نمایش داده می‌شود
        }
        $moduleMap = [
            'coupon' => 'coupon',
            'reviews' => 'reviews',
            'referral' => 'referral',
            'subscription' => 'subscription',
            'whatsapp' => 'whatsapp',
            'sms' => 'sms',
            'sms_campaigns' => 'sms', // SMS campaigns require sms module
            'courses' => 'courses',
            'invoice' => 'invoice',
            'business_card' => 'business_card',
            'social_links' => 'social_links',
            // Platform modules with real dedicated UIs, placed in static groups
            'crm'          => 'crm',
            'inventory'    => 'inventory',
            'seo_advanced' => 'seo_advanced',
        ];
        if (isset($moduleMap[$key])) {
            return SiteHelper::moduleEnabled($moduleMap[$key]);
        }
        return false;
    }

    /**
     * نمایش آیتم منوی اعلان‌ها:
     * - فقط زمانی که این ماژول در لایسنس سایت آمده یا سایت مالک باشد.
     * - در صورت منقضی شدن لایسنس، منو مخفی نمی‌شود و پنل به‌صورت read-only عمل می‌کند.
     */
    protected static function notificationsPanelVisible(): bool
    {
        if (SiteHelper::isOwnerSite()) {
            return true;
        }

        $license = License::current();
        if (! $license || ! is_array($license->modules)) {
            return false;
        }

        return in_array('notifications', $license->modules, true);
    }

    public static function getRoute(string $key): string
    {
        if (isset(self::MENU_ROUTES[$key])) {
            return self::MENU_ROUTES[$key];
        }
        return 'admin.modules.show';
    }

    /** پارامترهای مسیر برای لینک منو (مثلاً slug برای admin.modules.show). */
    public static function getRouteParams(string $key): array
    {
        $route = self::getRoute($key);
        if ($route === 'admin.modules.show') {
            return ['slug' => $key];
        }
        return [];
    }

    public static function isActive(string $key): bool
    {
        $pattern = self::MENU_ROUTE_PATTERNS[$key] ?? 'admin.modules.*';
        if (!$pattern) {
            return false;
        }
        if ($pattern === 'admin.modules.show') {
            return request()->routeIs($pattern) && request()->route('slug') === $key;
        }
        return request()->routeIs($pattern);
    }

    public static function getTitle(string $key): string
    {
        // PHASE 7.25 — TASK 5: prefer manifest label over hardcoded constant
        $manifestLabel = self::getManifestLabel($key);
        if ($manifestLabel !== null) {
            return $manifestLabel;
        }

        return self::MENU_KEYS[$key]
            ?? self::PLATFORM_MODULE_LABELS[$key]
            ?? str_replace('_', ' ', $key);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PHASE 7.25 — TASK 5: Module-manifest-driven menu resolution
    // Canonical governance: module menu items MUST derive from
    // config('module-manifest-{slug}').menu[] (PHASE 7.15 AD-2).
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Manifest key → AdminMenu key mapping.
     * Maps manifest menu item keys to the AdminMenu keys used in getStaticGroups().
     */
    protected static function getManifestKeyMap(): array
    {
        return [
            // forms manifest menu items → AdminMenu keys
            'forms'             => 'forms',
            'forms-submissions' => 'forms_submissions',
            // tickets manifest menu items → AdminMenu keys
            'tickets'           => 'support',
            'tickets-sla'       => 'support_history',
        ];
    }

    /**
     * Read a menu item label from the relevant module manifest.
     * Returns null if no manifest is loaded or the key has no manifest entry.
     */
    protected static function getManifestLabel(string $adminMenuKey): ?string
    {
        // Reverse-map: AdminMenu key → manifest item key
        $reverseMap = array_flip(self::getManifestKeyMap());
        $manifestItemKey = $reverseMap[$adminMenuKey] ?? null;
        if ($manifestItemKey === null) {
            return null;
        }

        // Determine which manifest to read from
        $manifestSlug = str_starts_with($manifestItemKey, 'forms') ? 'forms' : 'tickets';

        $manifest = config('module-manifest-' . $manifestSlug);
        if (!is_array($manifest)) {
            return null;
        }

        foreach ($manifest['menu'] ?? [] as $item) {
            if (($item['key'] ?? '') === $manifestItemKey) {
                return $item['label'] ?? null;
            }
        }

        return null;
    }

    /**
     * Read a route name from the relevant module manifest.
     * Returns null if no manifest is loaded or the key has no manifest entry.
     */
    public static function getManifestRoute(string $adminMenuKey): ?string
    {
        $reverseMap = array_flip(self::getManifestKeyMap());
        $manifestItemKey = $reverseMap[$adminMenuKey] ?? null;
        if ($manifestItemKey === null) {
            return null;
        }

        $manifestSlug = str_starts_with($manifestItemKey, 'forms') ? 'forms' : 'tickets';

        $manifest = config('module-manifest-' . $manifestSlug);
        if (!is_array($manifest)) {
            return null;
        }

        foreach ($manifest['menu'] ?? [] as $item) {
            if (($item['key'] ?? '') === $manifestItemKey) {
                return $item['route'] ?? null;
            }
        }

        return null;
    }

    /**
     * Returns all module manifest menu items across all loaded manifests.
     * Used by validators to confirm manifest-driven rendering is active.
     *
     * @return array<string, array{key: string, label: string, route: string, manifest_slug: string}>
     */
    public static function getAllManifestMenuItems(): array
    {
        $items = [];
        foreach (['forms', 'tickets'] as $slug) {
            $manifest = config('module-manifest-' . $slug);
            if (!is_array($manifest)) {
                continue;
            }
            foreach ($manifest['menu'] ?? [] as $item) {
                if (!isset($item['key'])) {
                    continue;
                }
                $items[$item['key']] = array_merge($item, ['manifest_slug' => $slug]);
            }
        }
        return $items;
    }
}
