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
        'posts' => 'مطالب وبلاگ',
        'products' => 'محصولات',
        'product_categories' => 'دسته‌بندی محصولات',
        'orders' => 'سفارشات',
        'coupon' => 'کوپن‌ها',
        'reviews' => 'نظرات و امتیازدهی',
        'referral' => 'معرفی دوستان',
        'subscription' => 'پلن‌های اشتراک',
        'whatsapp' => 'پاسخگویی شبکه‌ها',
        'sms' => 'پیامک',
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
        'builder_health' => 'سلامت المان‌های سازنده',
        'settings' => 'تنظیمات',
        'currency_reform' => 'حذف ۴ صفر پول',
        'license' => 'لایسنس',
        'modules' => 'ماژول‌ها',
        'module_store' => 'فروشگاه ماژول',
        'support' => 'پشتیبانی',
        'advanced_search' => 'جستجوی پیشرفته',
        'menu' => 'منو و ناوبری',
        'bank_gateway' => 'درگاه بانکی',
        'accounting' => 'حسابداری',
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

    public const MENU_ROUTES = [
        'dashboard' => 'admin.dashboard',
        'pages' => 'admin.pages.index',
        'categories' => 'admin.categories.index',
        'posts' => 'admin.posts.index',
        'products' => 'admin.products.index',
        'product_categories' => 'admin.product-categories.index',
        'orders' => 'admin.orders.index',
        'coupon' => 'admin.coupons.index',
        'reviews' => 'admin.reviews.index',
        'referral' => 'admin.referrals.index',
        'subscription' => 'admin.subscriptions.index',
        'whatsapp' => 'admin.whatsapp.index',
        'sms' => 'admin.sms.index',
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
        'builder_health' => 'admin.builder-health.index',
        'settings' => 'admin.settings.index',
        'currency_reform' => 'admin.currency-reform.index',
        'license' => 'admin.license.index',
        'modules' => 'admin.modules.index',
        'module_store' => 'admin.module-store.index',
        'support' => 'admin.support.index',
        'advanced_search' => 'admin.modules.show',
        'menu' => 'admin.menus.index',
        'bank_gateway' => 'admin.modules.show',
        'accounting' => 'admin.accounting.index',
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
        'menu' => 'admin.menus.index',
        'bank_gateway' => 'embedded',

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
        'posts' => 'admin.posts.*',
        'products' => 'admin.products.*',
        'product_categories' => 'admin.product-categories.*',
        'orders' => 'admin.orders.*',
        'coupon' => 'admin.coupons.*',
        'reviews' => 'admin.reviews.*',
        'referral' => 'admin.referrals.*',
        'subscription' => 'admin.subscriptions.*',
        'whatsapp' => 'admin.whatsapp.*',
        'sms' => 'admin.sms.*',
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
        'builder_health' => 'admin.builder-health.*',
        'settings' => 'admin.settings.*',
        'currency_reform' => 'admin.currency-reform.*',
        'license' => 'admin.license.*',
        'modules' => 'admin.modules.*',
        'module_store' => 'admin.module-store.index',
        'support' => 'admin.support.*',
        'advanced_search' => 'admin.modules.show',
        'menu' => 'admin.menus.index',
        'bank_gateway' => 'admin.modules.show',
        'accounting' => 'admin.accounting.*',
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
            'اصلی' => ['dashboard'],
            'محتوا' => ['pages', 'categories', 'posts', 'advanced_search', 'menu'],
            'فروشگاه' => ['product_categories', 'products', 'orders', 'coupon'],
            'تعامل و اعلان' => ['reviews', 'referral', 'whatsapp', 'sms', 'notifications'],
            'اشتراک و دوره' => ['subscription', 'courses'],
            'مالی و تماس' => ['invoice', 'business_card', 'social_links', 'advertisements'],
            'فرم و محتوا' => ['forms', 'forms_submissions'],
            'سیستم' => ['captcha', 'theme_design', 'builder_health', 'settings', 'currency_reform', 'license', 'modules', 'module_store', 'support', 'bank_gateway'],
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
            // اگر قبلاً در گروه‌های ثابت آمده یا ماژول headless/embedded است، در گروه «ماژول‌های از پلتفرم» نیاید.
            if (isset($staticKeys[$slug])) {
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
        $dynamicKeys = self::getDynamicPlatformMenuKeys();
        if (count($dynamicKeys) > 0) {
            $groups['ماژول‌های از پلتفرم'] = $dynamicKeys;
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
        if ($key === 'dashboard' || $key === 'pages' || $key === 'captcha' || $key === 'theme_design' || $key === 'builder_health' || $key === 'settings' || $key === 'license' || $key === 'modules' || $key === 'support') {
            return true;
        }
        if ($key === 'module_store') {
            return rtrim((string) config('site.platform_url', ''), '/') !== '';
        }
        if ($key === 'categories' || $key === 'posts') {
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
        if ($key === 'products' || $key === 'product_categories' || $key === 'orders') {
            return SiteHelper::shopEnabled();
        }
        if ($key === 'advanced_search') {
            return SiteHelper::moduleEnabled('advanced_search');
        }
        if ($key === 'menu' || $key === 'bank_gateway') {
            return SiteHelper::moduleEnabled($key);
        }
        $moduleMap = [
            'coupon' => 'coupon',
            'reviews' => 'reviews',
            'referral' => 'referral',
            'subscription' => 'subscription',
            'whatsapp' => 'whatsapp',
            'sms' => 'sms',
            'courses' => 'courses',
            'invoice' => 'invoice',
            'business_card' => 'business_card',
            'social_links' => 'social_links',
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
        return self::MENU_KEYS[$key]
            ?? self::PLATFORM_MODULE_LABELS[$key]
            ?? str_replace('_', ' ', $key);
    }
}
