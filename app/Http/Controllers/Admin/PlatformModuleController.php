<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Helpers\SiteHelper;
use App\Models\License;

/**
 * صفحهٔ عمومی برای ماژول‌های پلتفرمی پولی/لایسنس‌دار که فقط
 * راهنما و وضعیت ماژول را در پنل ادمین سایت مشتری نمایش می‌دهد.
 * برای ماژول‌هایی استفاده می‌شود که هنوز UI کامل اختصاصی ندارند.
 */
class PlatformModuleController extends Controller
{
    /**
     * توضیحات راهنما برای هر ماژول پلتفرمی.
     */
    private const MODULE_GUIDES = [
        // 'analytics' removed: superseded by the real AnalyticsDashboardController
        // route (admin.analytics.index, gated by module:analytics in routes/web.php)
        // — see AdminMenu::MENU_ROUTES['analytics']. The old generic guide page
        // here was a second, unguarded-by-mistake authority for the same route
        // name; same pattern as the 'crm' removal below.
        'announcements' => [
            'title' => 'اعلان‌ها و اخبار',
            'description' => 'مدیریت اعلان‌ها و اخبار ارسال‌شده از پلتفرم برای نمایش در سایت یا پنل کاربری.',
        ],
        'backup' => [
            'title' => 'پشتیبان‌گیری',
            'description' => 'نمایش وضعیت بکاپ‌ها و هماهنگی با سرویس‌های پشتیبان‌گیری در پلتفرم.',
        ],
        'brand' => [
            'title' => 'برند',
            'description' => 'تنظیمات پیشرفتهٔ هویت بصری برند (لوگوهای چندگانه، رنگ‌های برند، حالت‌های برندینگ).',
        ],
        'cloud' => [
            'title' => 'ادغام ابر',
            'description' => 'اتصال سایت به سرویس‌های ابری (فضای ذخیره‌سازی، CDN، سرویس‌های ابری دیگر).',
        ],
        'contract' => [
            'title' => 'قرارداد',
            'description' => 'مدیریت قراردادهای خدمات و مستندات مرتبط با مشتریان.',
        ],
        // 'crm' removed (Phase X): superseded by the real crm-suite package's
        // own tenant route (crm.site.dashboard) — see AdminMenu::MENU_ROUTES['crm'].
        // The old generic guide page here was gated on the dead placeholder
        // Module id=46, not the real crm-suite (id=58) module.
        'discount_advanced' => [
            'title' => 'تخفیف پیشرفته',
            'description' => 'قوانین تخفیف پیچیده و ترکیبی فراتر از کوپن‌های معمولی.',
        ],
        'gallery' => [
            'title' => 'گالری',
            'description' => 'گالری‌های تصویر و آلبوم‌ها برای نمایش حرفه‌ای نمونه‌کار و تصاویر.',
        ],
        'i18n' => [
            'title' => 'زبان و چندگانه',
            'description' => 'پشتیبانی سایت چندزبانه و مدیریت زبان‌های فعال و پیش‌فرض.',
        ],
        'inventory' => [
            'title' => 'انبار و موجودی',
            'description' => 'مدیریت موجودی انبار و گزارش کمبود موجودی محصولات.',
        ],
        'marketing_automation' => [
            'title' => 'اتوماسیون بازاریابی',
            'description' => 'سناریوهای خودکار بازاریابی (مثل ایمیل/پیامک خوش‌آمدگویی، سبد رها شده و …).',
        ],
        'marketplace_sync' => [
            'title' => 'همگام‌سازی مارکت‌پلیس',
            'description' => 'هماهنگ‌سازی محصولات و سفارش‌ها با مارکت‌پلیس‌های خارجی.',
        ],
        'multi_currency' => [
            'title' => 'چندارزی پویا',
            'description' => 'نمایش قیمت‌ها به چند ارز و مدیریت نرخ تبدیل پویا.',
        ],
        'omnichannel' => [
            'title' => 'پاسخ همه‌کاناله',
            'description' => 'یکپارچه‌سازی کانال‌های مختلف ارتباطی (سایت، پیام‌رسان‌ها، شبکه‌های اجتماعی).',
        ],
        'podcast' => [
            'title' => 'پادکست',
            'description' => 'مدیریت اپیزودهای پادکست و فید RSS مربوط به آن‌ها.',
        ],
        'portfolio' => [
            'title' => 'پورتفولیو',
            'description' => 'نمایش و مدیریت نمونه‌کارها و پروژه‌های انجام‌شده.',
        ],
        'product_bundling' => [
            'title' => 'بسته‌بندی محصول',
            'description' => 'ساخت پکیج‌ها و باندل‌های محصولی با قیمت‌گذاری ترکیبی.',
        ],
        'projects' => [
            'title' => 'پروژه‌ها',
            'description' => 'مدیریت پروژه‌های مشتریان و پیگیری وضعیت آن‌ها.',
        ],
        'qa' => [
            'title' => 'پرسش و پاسخ',
            'description' => 'بخش سوالات متداول و پرسش و پاسخ کاربران.',
        ],
        'recruitment' => [
            'title' => 'استخدام',
            'description' => 'مدیریت آگهی‌های شغلی و رزومه‌های دریافتی.',
        ],
        'services' => [
            'title' => 'خدمات',
            'description' => 'مدیریت لیست خدمات و صفحات مربوط به معرفی سرویس‌ها.',
        ],
        'shipping' => [
            'title' => 'حمل و ارسال',
            'description' => 'تعریف و مدیریت روش‌های ارسال، هزینه‌ها و مناطق پوشش.',
        ],
        'tasks' => [
            'title' => 'وظیفه و کار',
            'description' => 'پیگیری کارها و تسک‌های داخلی تیم (در صورت فعال بودن در پلتفرم).',
        ],
        'video' => [
            'title' => 'ویدیو و ولاگ',
            'description' => 'مدیریت محتوای ویدیویی و ولاگ‌های سایت.',
        ],
        'wallet' => [
            'title' => 'کیف پول',
            'description' => 'امکان شارژ کیف پول کاربران و استفاده از آن در پرداخت‌ها.',
        ],
    ];

    public function show(string $slug)
    {
        if (! isset(self::MODULE_GUIDES[$slug])) {
            abort(404);
        }

        $license = License::current();
        $enabled = SiteHelper::moduleEnabled($slug);
        $guide = self::MODULE_GUIDES[$slug];

        return view('admin.platform-modules.show', compact('slug', 'license', 'enabled', 'guide'));
    }
}

