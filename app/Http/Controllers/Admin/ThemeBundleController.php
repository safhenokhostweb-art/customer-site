<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PlatformApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ThemeBundleController extends Controller
{
    public function __construct(protected PlatformApiService $platform) {}

    public function index(Request $request)
    {
        if (!$this->platform->isConfigured()) {
            return view('admin.theme-bundles.index', [
                'bundles' => [],
                'error'   => 'اتصال به پلتفرم پیکربندی نشده است.',
            ]);
        }

        $bundles = Cache::remember('theme_bundles_list', 300, fn () => $this->platform->themeBundles());

        return view('admin.theme-bundles.index', compact('bundles'));
    }

    public function show(Request $request, int $bundleId)
    {
        if (!$this->platform->isConfigured()) {
            return redirect()->route('admin.theme-bundles.index')
                ->with('error', 'اتصال به پلتفرم پیکربندی نشده است.');
        }

        $bundle = Cache::remember("theme_bundle_detail_{$bundleId}", 300, fn () => $this->platform->themeBundle($bundleId));

        if (!$bundle) {
            return redirect()->route('admin.theme-bundles.index')
                ->with('error', 'باندل یافت نشد.');
        }

        return view('admin.theme-bundles.show', compact('bundle'));
    }

    public function purchase(Request $request, int $bundleId)
    {
        if (!$this->platform->isConfigured()) {
            return back()->with('error', 'سرویس مارکت‌پلیس در دسترس نیست.');
        }

        $redirectBack = route('admin.theme-bundles.index') . '?purchased=1';
        $result = $this->platform->purchaseBundle($bundleId, $redirectBack);

        if (empty($result['success'])) {
            $err = match ($result['error'] ?? '') {
                'already_purchased' => 'این باندل قبلاً خریداری شده است.',
                'bundle_not_available' => 'باندل در دسترس نیست.',
                default => 'خطا در راه‌اندازی پرداخت. لطفاً دوباره تلاش کنید.',
            };
            return back()->with('error', $err);
        }

        if (!empty($result['free'])) {
            Cache::forget('theme_bundles_list');
            Cache::forget("theme_bundle_detail_{$bundleId}");
            return redirect()->route('admin.theme-bundles.index')
                ->with('success', "باندل رایگان دریافت شد. {$result['licenses_granted']} لایسنس صادر گردید.");
        }

        return redirect()->away($result['redirect_url']);
    }
}
