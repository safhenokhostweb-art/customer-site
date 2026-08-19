<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\PlatformUx;
use App\Http\Controllers\Controller;
use App\Models\License;
use App\Services\AdminActivityLogger;
use App\Services\LicenseActivationService;
use App\Services\UpdateCheckService;
use Illuminate\Http\Request;

class LicenseController extends Controller
{
    public function index()
    {
        $license = License::current();
        return view('admin.licenses.index', compact('license'));
    }

    public function update(Request $request, LicenseActivationService $activationService)
    {
        $rules = [
            'license_key' => 'nullable|string|max:255',
            'domain' => 'nullable|string|max:255',
        ];
        if (! \App\Helpers\SiteHelper::isOwnerSite()) {
            $rules['license_key'] = 'required|string|max:255';
        }
        $request->validate($rules);

        if (\App\Helpers\SiteHelper::isOwnerSite() && ! $request->filled('license_key')) {
            PlatformUx::clearCache();
            return back()->with('success', 'در حالت سایت مالک لایسنس اختیاری است. در صورت نیاز کلید را وارد کنید.');
        }

        $domainRaw = $request->filled('domain') ? $request->domain : $request->getHost();
        $domain = $this->normalizeDomain($domainRaw);

        $result = $activationService->validate($request->license_key, $domain);

        if (empty($result['valid'])) {
            return back()
                ->withInput($request->only('license_key', 'domain'))
                ->with('error', $result['message'] ?? 'لایسنس معتبر نیست.');
        }

        $license = License::current();
        $data = [
            'license_key' => $request->license_key,
            'domain' => $domain,
            'expires_at' => isset($result['expires_at']) ? $result['expires_at'] : null,
            'modules' => $result['modules'] ?? null,
            'support_expires_at' => $result['support_expires_at'] ?? null,
            'support_starts_at' => $result['support_starts_at'] ?? null,
            'support_level' => $result['support_level'] ?? 'basic',
            'platform_valid' => true,
            'last_validated_at' => now(),
        ];

        if ($license) {
            $license->update($data);
        } else {
            $license = License::create($data);
        }

        PlatformUx::clearCache();

        AdminActivityLogger::log(
            'license.activated',
            'فعال‌سازی لایسنس برای دامنهٔ ' . $domain,
            $license,
            [
                'domain' => $domain,
                'modules_count' => is_array($result['modules'] ?? null) ? count($result['modules']) : null,
            ]
        );

        return back()->with('success', 'لایسنس با موفقیت فعال شد.');
    }

    /**
     * پاک کردن کش تنظیمات پلتفرم (اعلان‌ها، UX و غیره) برای بازخوانی از API.
     */
    public function refreshConfig()
    {
        PlatformUx::clearCache();
        session()->put('platform_config_just_refreshed', true);
        AdminActivityLogger::log('license.refreshed_config', 'بازخوانی تنظیمات و اعلان‌ها از پلتفرم');
        return back()->with('success', 'تنظیمات و اعلان‌ها از سرور پلتفرم دوباره بارگذاری می‌شوند.');
    }

    /**
     * نسخهٔ GET برای لینک مستقیم (مثلاً از ویجت اعلان یا لینک «تلاش مجدد» در banner خطای موقت لایسنس).
     * در صورت وجود لایسنس، یک تلاش مجدد برای sync با پلتفرم هم انجام می‌شود تا اگر مشکل موقت
     * برطرف شده باشد، banner بلافاصله پاک شود (به‌جای انتظار تا رأس بعدی revalidate).
     */
    public function refreshConfigGet(LicenseActivationService $activationService)
    {
        $license = License::current();
        if ($license && $license->id) {
            $activationService->syncWithPlatform($license, $license->domain ?: request()->getHost());
        }

        PlatformUx::clearCache();
        session()->put('platform_config_just_refreshed', true);
        AdminActivityLogger::log('license.refreshed_config', 'بازخوانی تنظیمات و اعلان‌ها از پلتفرم (GET)');
        return redirect()->route('admin.license.index')->with('success', 'تنظیمات و اعلان‌ها بازخوانی شدند.');
    }

    /**
     * تست API و نمایش پاسخ (برای عیب‌یابی عدم نمایش اعلان).
     */
    public function testSiteConfig()
    {
        $license = License::current();
        if (!$license || empty($license->license_key)) {
            return response()->json(['error' => 'لایسنس ثبت نشده است.'], 400);
        }
        $baseUrl = rtrim(config('site.platform_url', ''), '/');
        if (empty($baseUrl)) {
            return response()->json(['error' => 'PLATFORM_URL تنظیم نشده است.'], 400);
        }
        $url = $baseUrl . '/api/site-config';
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)
                ->withToken($license->license_key)
                ->withHeaders(['X-Tenant-Domain' => $license->domain ?: request()->getHost()])
                ->get($url, ['debug' => 1]);
            $data = $response->json();
            $out = [
                'http_status' => $response->status(),
                'allowed' => $data['allowed'] ?? null,
                'notifications_count' => $data['notifications_count'] ?? (isset($data['notifications']) ? count($data['notifications']) : 0),
                'notifications' => $data['notifications'] ?? [],
                'site_title' => $data['site_title'] ?? null,
            ];
            if (!empty($data['debug'])) {
                $out['debug'] = $data['debug'];
            }
            return response()->json($out, 200, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * بروزرسانی ماژول‌ها از پلتفرم با کلید ذخیره‌شده (بدون نیاز به وارد کردن مجدد کلید).
     */
    public function refreshModules(LicenseActivationService $activationService)
    {
        $license = License::current();
        if (!$license || empty($license->license_key)) {
            return back()->with('error', 'لایسنس ثبت نشده است. ابتدا کلید را وارد کرده و فعال‌سازی کنید.');
        }

        $domain = $license->domain ?: request()->getHost();
        $domain = $this->normalizeDomain($domain);
        $result = $activationService->validate($license->license_key, $domain);

        if (empty($result['valid'])) {
            if (! $activationService->isDefinitePlatformRejection($result)) {
                return back()->with('error', $result['message'] ?? 'فعلاً ارتباط با سرور پلتفرم برقرار نشد. چند دقیقه بعد دوباره تلاش کنید.');
            }

            return back()->with('error', $result['message'] ?? 'لایسنس معتبر نیست یا دامنه مطابقت ندارد.');
        }

        $modulesBefore = is_array($license->modules ?? null) ? $license->modules : [];
        $modulesAfter = is_array($result['modules'] ?? null) ? $result['modules'] : [];

        $license->update([
            'modules' => $result['modules'] ?? null,
            'support_expires_at' => $result['support_expires_at'] ?? null,
            'support_starts_at' => $result['support_starts_at'] ?? null,
            'support_level' => $result['support_level'] ?? 'basic',
            'platform_valid' => true,
            'last_validated_at' => now(),
        ]);
        PlatformUx::clearCache();
        app(UpdateCheckService::class)->reportModuleVersions();
        app(UpdateCheckService::class)->syncModuleChangeLogs();

        AdminActivityLogger::log(
            'license.refreshed_modules',
            'بروزرسانی فهرست ماژول‌ها از پلتفرم',
            $license,
            [
                'modules_before_count' => count($modulesBefore),
                'modules_after_count' => count($modulesAfter),
                'added' => array_values(array_diff($modulesAfter, $modulesBefore)),
                'removed' => array_values(array_diff($modulesBefore, $modulesAfter)),
            ]
        );

        return back()->with('success', 'ماژول‌ها از پلتفرم بروزرسانی شدند: ' . implode('، ', $result['modules'] ?? []));
    }

    /**
     * بررسی بروزرسانی از پلتفرم
     */
    public function checkUpdate(UpdateCheckService $updateCheck)
    {
        $currentVersion = config('app.version', '1.0.0');
        $result = $updateCheck->check($currentVersion);

        if (!$result['success']) {
            return back()->with('error', $result['message'] ?? 'بررسی بروزرسانی ناموفق بود.');
        }

        if (!empty($result['update_available'])) {
            return back()->with('update_available', [
                'latest_version' => $result['latest_version'],
                'changelog' => $result['changelog'],
            ]);
        }

        return back()->with('success', 'نسخهٔ نصب‌شده به‌روز است. (فعلی: ' . $currentVersion . ')');
    }

    private function normalizeDomain(string $domain): string
    {
        return \App\Models\License::normalizeDomain($domain);
    }
}
