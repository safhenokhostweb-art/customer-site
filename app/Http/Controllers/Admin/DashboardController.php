<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Helpers\Module;
use App\Helpers\SiteHelper;
use App\Models\License;
use App\Models\Post;
use App\Models\Product;
use App\Models\Order;
use App\Models\Page;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'pages' => Page::count(),
            'posts' => Post::count(),
            'products' => SiteHelper::shopEnabled() ? Product::count() : 0,
            'orders' => SiteHelper::shopEnabled() ? Order::count() : 0,
        ];
        $recentOrders = SiteHelper::shopEnabled()
            ? Order::latest()->take(5)->get()
            : collect();

        $license = License::current();
        $modulesFromPlatform = $license && is_array($license->modules) ? $license->modules : [];
        $localModulesRaw = Module::all();
        // وضعیت «فعال در این سایت» با SiteHelper::moduleEnabled (لایسنس اول، بعد .env)
        $localModules = [];
        foreach ($localModulesRaw as $key => $info) {
            $localModules[$key] = array_merge($info, ['enabled' => SiteHelper::moduleEnabled($key)]);
        }
        // ماژول‌هایی که پلتفرم فرستاده ولی در config سایت مشتری تعریف نشده‌اند را هم نشان بده
        foreach ($modulesFromPlatform as $slug) {
            if (!is_string($slug)) {
                continue;
            }
            // پلتفرم ماژول را به messaging تغییر داده؛ در config سایت هنوز whatsapp هست — یک ردیف نشان بده
            if ($slug === 'messaging' && isset($localModules['whatsapp'])) {
                continue;
            }
            if (isset($localModules[$slug])) {
                continue;
            }
            $localModules[$slug] = [
                'key' => $slug,
                'name' => self::humanizeModuleSlug($slug),
                'enabled' => SiteHelper::moduleEnabled($slug),
            ];
        }

        $importantAlerts = collect();
        if (class_exists(\App\Services\PlatformUxService::class)) {
            $all = collect(app(\App\Services\PlatformUxService::class)->getPlatformNotifications())
                ->filter(fn ($n) => ($n['audience'] ?? 'public') === 'admin');
            $importantAlerts = $all->filter(fn ($n) => in_array($n['type'] ?? '', ['warning', 'danger']) || in_array($n['priority'] ?? '', ['high', 'urgent']));
        }

        $platformUpdateData = app(\App\Services\UpdateCheckService::class)->getUpdateHistory();
        $latestUpdate = $platformUpdateData['latest'] ?? null;
        $updateHistory = $platformUpdateData['history'] ?? [];

        return view('admin.dashboard', compact(
            'stats',
            'recentOrders',
            'license',
            'modulesFromPlatform',
            'localModules',
            'importantAlerts',
            'latestUpdate',
            'updateHistory'
        ));
    }

    private static function humanizeModuleSlug(string $slug): string
    {
        $labels = [
            'blog' => 'وبلاگ',
            'shop' => 'فروشگاه',
            'forms' => 'فرم‌ساز',
            'advertisements' => 'تبلیغات',
            'notifications' => 'اعلان‌ها',
            'messaging' => 'پاسخگویی شبکه‌ها',
            'menu' => 'منو و ناوبری',
            'advanced_search' => 'جستجوی پیشرفته',
            'currency_reform' => 'حذف ۴ صفر پول ملی',
            'coupon' => 'کوپن تخفیف',
            'reviews' => 'نظرات',
            'referral' => 'معرفی دوستان',
            'subscription' => 'اشتراک',
            'sms' => 'پیامک',
            'courses' => 'دوره‌ها',
            'invoice' => 'فاکتور آنلاین',
            'business_card' => 'کارت ویزیت دیجیتال',
            'social_links' => 'لینک‌های اجتماعی',
        ];
        return $labels[$slug] ?? str_replace('_', ' ', $slug);
    }
}
