<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PlatformApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ThemeMarketplaceController extends Controller
{
    public function __construct(protected PlatformApiService $platform) {}

    public function index(Request $request)
    {
        if (!$this->platform->isConfigured()) {
            return view('admin.theme-marketplace.index', [
                'themes' => [],
                'error'  => 'اتصال به پلتفرم پیکربندی نشده است.',
            ]);
        }

        $themes    = Cache::remember('theme_marketplace_list', 300, fn () => $this->platform->marketplaceThemes());
        $purchased = session('purchased_theme', false);
        $failed    = $request->query('failed', false);
        $msg       = $request->query('msg', '');

        session()->forget('purchased_theme');

        return view('admin.theme-marketplace.index', compact('themes', 'purchased', 'failed', 'msg'));
    }

    public function purchase(Request $request, int $themeId)
    {
        if (!$this->platform->isConfigured()) {
            return back()->with('error', 'سرویس مارکت‌پلیس در دسترس نیست.');
        }

        $redirectBack = route('admin.theme-marketplace.my-licenses') . '?purchased=1';

        $result = $this->platform->marketplaceInitiatePurchase($themeId, $redirectBack);

        if (empty($result['success'])) {
            $err = match ($result['error'] ?? '') {
                'already_purchased' => 'این قالب قبلاً خریداری شده است.',
                'theme_not_available' => 'قالب در دسترس نیست.',
                default => 'خطا در راه‌اندازی پرداخت. لطفاً دوباره تلاش کنید.',
            };
            return back()->with('error', $err);
        }

        if (!empty($result['free'])) {
            Cache::forget('theme_marketplace_list');
            return redirect()->route('admin.theme-marketplace.my-licenses')
                ->with('success', 'قالب رایگان با موفقیت دریافت شد!');
        }

        return redirect()->away($result['redirect_url']);
    }

    public function myLicenses(Request $request)
    {
        if (!$this->platform->isConfigured()) {
            return view('admin.theme-marketplace.my-licenses', [
                'licenses' => [],
                'error'    => 'اتصال به پلتفرم پیکربندی نشده است.',
            ]);
        }

        $licenses  = $this->platform->marketplaceMyLicenses();
        $purchased = $request->query('purchased') == '1';
        $msg       = $request->query('msg', '');

        return view('admin.theme-marketplace.my-licenses', compact('licenses', 'purchased', 'msg'));
    }

    public function install(Request $request, int $themeId)
    {
        if (!$this->platform->isConfigured()) {
            return back()->with('error', 'سرویس در دسترس نیست.');
        }

        $themeData = $this->platform->marketplaceDownloadTheme($themeId);

        if (!$themeData || empty($themeData['success'])) {
            return back()->with('error', 'دانلود قالب ناموفق بود. لطفاً مطمئن شوید لایسنس معتبر است.');
        }

        // ذخیره داده قالب در تنظیمات سایت
        \App\Models\Setting::set('active_canvas_theme_id', $themeId, 'theme');
        \App\Models\Setting::set('active_canvas_theme_name', $themeData['theme_name'], 'theme');
        \App\Models\Setting::set('active_canvas_theme_slug', $themeData['theme_slug'], 'theme');
        \App\Models\Setting::set('active_canvas_theme_data', json_encode([
            'header_blocks' => $themeData['header_blocks'] ?? [],
            'footer_blocks' => $themeData['footer_blocks'] ?? [],
            'pages'         => $themeData['pages'] ?? [],
            'global_styles' => $themeData['global_styles'] ?? [],
            'settings'      => $themeData['settings'] ?? [],
        ]), 'theme');

        // پاک‌سازی کش تم
        if (class_exists(\App\Services\TenantThemeResolver::class)) {
            app(\App\Services\TenantThemeResolver::class)->clearCache();
        }

        return back()->with('success', "قالب «{$themeData['theme_name']}» نصب و فعال شد.");
    }
}
