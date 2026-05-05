<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Helpers\Module as ConfigModule;
use App\Helpers\SiteHelper;
use App\Models\License;
use App\Models\Setting;
use App\Services\UpdateCheckService;
use Illuminate\Http\Request;

/**
 * نمایش ماژول‌های خریداری‌شده و وضعیت بروزرسانی + تنظیمات ماژول‌های ویژه در پنل ادمین سایت.
 */
class ModuleController extends Controller
{
    public function index(UpdateCheckService $updateCheck)
    {
        $license = License::current();
        $modulesFromPlatform = $license && is_array($license->modules) ? $license->modules : [];

        $localModulesRaw = ConfigModule::all();
        $localModules = [];
        foreach ($localModulesRaw as $key => $info) {
            $localModules[$key] = array_merge($info, ['enabled' => SiteHelper::moduleEnabled($key)]);
        }

        foreach ($modulesFromPlatform as $slug) {
            if (!is_string($slug) || isset($localModules[$slug])) {
                continue;
            }
            $localModules[$slug] = [
                'key' => $slug,
                'name' => self::humanizeModuleSlug($slug),
                'enabled' => SiteHelper::moduleEnabled($slug),
            ];
        }

        $moduleUpdates = [];
        if ($license && $license->license_key && $license->isValid()) {
            $result = $updateCheck->checkModuleUpdates();
            if (!empty($result['success']) && !empty($result['modules'])) {
                $moduleUpdates = $result['modules'];
            }
        }

        return view('admin.modules.index', compact(
            'license',
            'modulesFromPlatform',
            'localModules',
            'moduleUpdates'
        ));
    }

    /**
     * صفحهٔ تنظیمات/اطلاعات یک ماژول (برای ماژول‌هایی که صفحهٔ اختصاصی ادمین ندارند: منو، جستجوی پیشرفته، درگاه بانکی و ...).
     */
    public function show(string $slug)
    {
        $license = License::current();
        $modulesFromPlatform = $license && is_array($license->modules) ? $license->modules : [];

        $fromLicense = in_array($slug, $modulesFromPlatform, true)
            || ($slug === 'whatsapp' && in_array('messaging', $modulesFromPlatform, true));

        $isConfigModule = array_key_exists($slug, config('modules.modules', []));

        if (! $isConfigModule && ! $fromLicense) {
            abort(404);
        }

        $enabled = SiteHelper::moduleEnabled($slug);
        $title = self::humanizeModuleSlug($slug);

        $advancedSearchConfig = null;
        $menuConfig = null;
        $bankGatewayConfig = null;

        if ($slug === 'advanced_search') {
            $advancedSearchConfig = [
                'include_products' => (bool) Setting::get('advanced_search_include_products', true),
                'include_posts' => (bool) Setting::get('advanced_search_include_posts', true),
                'results_per_page' => (int) (Setting::get('advanced_search_results_per_page', 12)),
            ];
        } elseif ($slug === 'menu') {
            $rawMenus = Setting::get('menu_custom_menus', null);
            $decoded = $rawMenus ? json_decode($rawMenus, true) : null;
            if (! is_array($decoded)) {
                $decoded = [];
            }
            $menuConfig = [
                'mode' => (string) (Setting::get('menu_mode', 'platform') ?: 'platform'),
                'menus' => [
                    'header' => [
                        'items' => $decoded['header']['items'] ?? [],
                    ],
                    'footer' => [
                        'items' => $decoded['footer']['items'] ?? [],
                    ],
                ],
            ];
        } elseif ($slug === 'bank_gateway') {
            $bankGatewayConfig = [
                'callback_url' => route('payment.callback'),
            ];
        }

        return view('admin.modules.show', compact(
            'slug',
            'title',
            'enabled',
            'advancedSearchConfig',
            'menuConfig',
            'bankGatewayConfig'
        ));
    }

    /**
     * ذخیره‌سازی تنظیمات ماژول‌های قابل پیکربندی از داخل پنل سایت.
     */
    public function update(Request $request, string $slug)
    {
        if ($slug === 'advanced_search') {
            $data = $request->validate([
                'include_products' => 'sometimes|boolean',
                'include_posts' => 'sometimes|boolean',
                'results_per_page' => 'required|integer|min:1|max:60',
            ]);

            Setting::set('advanced_search_include_products', $request->boolean('include_products'));
            Setting::set('advanced_search_include_posts', $request->boolean('include_posts'));
            Setting::set('advanced_search_results_per_page', $data['results_per_page']);

            return redirect()
                ->route('admin.modules.show', $slug)
                ->with('success', 'تنظیمات جستجوی پیشرفته ذخیره شد.');
        }

        if ($slug === 'menu') {
            $data = $request->validate([
                'menu_mode' => 'required|string|in:platform,local',
                'header_title' => 'array',
                'header_title.*' => 'nullable|string|max:255',
                'header_url' => 'array',
                'header_url.*' => 'nullable|string|max:1024',
                'header_target' => 'array',
                'header_target.*' => 'nullable|string|in:_self,_blank',
                'footer_title' => 'array',
                'footer_title.*' => 'nullable|string|max:255',
                'footer_url' => 'array',
                'footer_url.*' => 'nullable|string|max:1024',
                'footer_target' => 'array',
                'footer_target.*' => 'nullable|string|in:_self,_blank',
            ]);

            $buildItems = static function (array $titles, array $urls, array $targets): array {
                $items = [];
                $count = max(count($titles), count($urls), count($targets));

                for ($i = 0; $i < $count; $i++) {
                    $title = trim($titles[$i] ?? '');
                    $url = trim($urls[$i] ?? '');
                    $target = $targets[$i] ?? '_self';

                    if ($title === '' || $url === '') {
                        continue;
                    }

                    $items[] = [
                        'title' => $title,
                        'url' => $url,
                        'target' => in_array($target, ['_self', '_blank'], true) ? $target : '_self',
                    ];
                }

                return $items;
            };

            $headerItems = $buildItems(
                $request->input('header_title', []),
                $request->input('header_url', []),
                $request->input('header_target', [])
            );

            $footerItems = $buildItems(
                $request->input('footer_title', []),
                $request->input('footer_url', []),
                $request->input('footer_target', [])
            );

            $menus = [
                'header' => ['items' => $headerItems],
                'footer' => ['items' => $footerItems],
            ];

            Setting::set('menu_mode', $data['menu_mode'], 'menus');
            Setting::set('menu_custom_menus', $menus, 'menus');

            app(\App\Services\PlatformUxService::class)->clearCache();

            return redirect()
                ->route('admin.modules.show', $slug)
                ->with('success', 'تنظیمات منو ذخیره شد.');
        }

        if ($slug === 'bank_gateway') {
            return redirect()
                ->route('admin.modules.show', $slug)
                ->with('info', 'تنظیمات اصلی درگاه بانکی از طریق پلتفرم مدیریت می‌شود.');
        }

        abort(404);
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
            'bank_gateway' => 'درگاه بانکی',
            'accounting' => 'حسابداری',
        ];

        return $labels[$slug] ?? str_replace('_', ' ', $slug);
    }
}
