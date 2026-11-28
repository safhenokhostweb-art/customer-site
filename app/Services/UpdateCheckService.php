<?php

namespace App\Services;

use App\Models\License;
use App\Models\ModuleChangeLog;
use App\Models\ModuleVersion;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * بررسی وجود بروزرسانی از طریق API پلتفرم.
 */
class UpdateCheckService
{
    /**
     * همگام‌سازی تاریخچهٔ تغییرات ماژول‌ها از پلتفرم.
     */
    public function syncModuleChangeLogs(int $limit = 100): array
    {
        $license = License::current();
        if (!$license || empty($license->license_key)) {
            return ['success' => false, 'message' => 'لایسنس فعال نیست.'];
        }

        $baseUrl = config('site.platform_url');
        if (empty($baseUrl)) {
            return ['success' => false, 'message' => 'آدرس پلتفرم تنظیم نشده است.'];
        }

        $url = $baseUrl . '/api/updates/module-history';
        try {
            $response = Http::timeout(12)->withToken($license->license_key)
                ->withHeaders(['X-Tenant-Domain' => $this->licenseRequestDomain($license)])
                ->get($url, ['limit' => $limit]);

            if (!$response->successful()) {
                return ['success' => false, 'message' => 'خطا در دریافت تاریخچه ماژول‌ها.'];
            }

            $data = $response->json();
            if (empty($data['allowed'])) {
                return ['success' => false, 'message' => $data['error'] ?? 'دسترسی مجاز نیست.'];
            }

            $history = is_array($data['history'] ?? null) ? $data['history'] : [];
            foreach ($history as $row) {
                $slug = (string) ($row['module_slug'] ?? '');
                $version = (string) ($row['version'] ?? '');
                if ($slug === '' || $version === '') {
                    continue;
                }
                ModuleChangeLog::updateOrCreate(
                    ['module_slug' => $slug, 'version' => $version],
                    [
                        'release_type' => (string) ($row['release_type'] ?? 'patch'),
                        'title' => $row['title'] ?? null,
                        'changelog' => $row['changelog'] ?? null,
                        'released_at' => !empty($row['released_at']) ? $row['released_at'] : null,
                        'synced_at' => now(),
                    ]
                );
            }

            return ['success' => true, 'count' => count($history)];
        } catch (\Throwable $e) {
            Log::error('Sync module changelogs exception', ['message' => $e->getMessage()]);
            return ['success' => false, 'message' => 'امکان ارتباط با سرور وجود ندارد.'];
        }
    }

    /**
     * گزارش نسخه‌های ماژول‌های نصب‌شده به پلتفرم.
     */
    public function reportModuleVersions(): array
    {
        $license = License::current();
        if (!$license || empty($license->license_key)) {
            return ['success' => false, 'message' => 'لایسنس فعال نیست.'];
        }

        $baseUrl = config('site.platform_url');
        if (empty($baseUrl)) {
            return ['success' => false, 'message' => 'آدرس پلتفرم تنظیم نشده است.'];
        }

        $versions = ModuleVersion::allVersions();
        if (empty($versions)) {
            return ['success' => true];
        }

        $url = $baseUrl . '/api/updates/report-versions';
        try {
            $response = Http::timeout(10)->withToken($license->license_key)
                ->withHeaders(['X-Tenant-Domain' => $this->licenseRequestDomain($license)])
                ->post($url, ['versions' => $versions]);

            if (!$response->successful()) {
                return ['success' => false, 'message' => 'خطا در گزارش نسخه‌ها.'];
            }

            $data = $response->json();
            return ['success' => !empty($data['success'])];
        } catch (\Throwable $e) {
            Log::error('Report versions exception', ['message' => $e->getMessage()]);
            return ['success' => false, 'message' => 'امکان ارتباط با سرور وجود ندارد.'];
        }
    }

    /**
     * دریافت لیست بروزرسانی‌های موجود برای ماژول‌ها.
     */
    public function checkModuleUpdates(): array
    {
        $license = License::current();
        if (!$license || empty($license->license_key)) {
            return [
                'success' => false,
                'message' => 'لایسنس فعال نیست.',
                'modules' => [],
            ];
        }

        $baseUrl = config('site.platform_url');
        if (empty($baseUrl)) {
            return ['success' => false, 'message' => 'آدرس پلتفرم تنظیم نشده است.', 'modules' => []];
        }

        $url = $baseUrl . '/api/updates/modules';
        try {
            $response = Http::timeout(10)->withToken($license->license_key)
                ->withHeaders(['X-Tenant-Domain' => $this->licenseRequestDomain($license)])
                ->get($url);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => $response->json()['error'] ?? 'خطا در ارتباط با سرور.',
                    'modules' => [],
                ];
            }

            $data = $response->json();
            if (empty($data['allowed'])) {
                return [
                    'success' => false,
                    'message' => $data['error'] ?? 'دسترسی مجاز نیست.',
                    'modules' => [],
                ];
            }

            return [
                'success' => true,
                'modules' => $data['modules'] ?? [],
            ];
        } catch (\Throwable $e) {
            Log::error('Check module updates exception', ['message' => $e->getMessage()]);
            return ['success' => false, 'message' => 'امکان ارتباط با سرور وجود ندارد.', 'modules' => []];
        }
    }

    public function check(?string $currentVersion = null): array
    {
        $license = License::current();
        if (!$license || empty($license->license_key)) {
            return [
                'success' => false,
                'message' => 'لایسنس فعال نیست. ابتدا لایسنس را وارد کنید.',
            ];
        }

        $baseUrl = config('site.platform_url');
        if (empty($baseUrl)) {
            return ['success' => false, 'message' => 'آدرس پلتفرم (PLATFORM_URL) تنظیم نشده است.'];
        }

        $url = $baseUrl . '/api/updates/check';
        $params = [
            'product' => 'site_template',
        ];
        if ($currentVersion !== null) {
            $params['current_version'] = $currentVersion;
        }

        try {
            $response = Http::timeout(10)->withToken($license->license_key)
                ->withHeaders(['X-Tenant-Domain' => $this->licenseRequestDomain($license)])
                ->get($url, $params);

            if (!$response->successful()) {
                return ['success' => false, 'message' => 'خطا در ارتباط با سرور بروزرسانی.'];
            }

            $data = $response->json();
            if (empty($data['allowed'])) {
                return [
                    'success' => false,
                    'message' => $data['error'] ?? 'دسترسی مجاز نیست.',
                ];
            }

            return [
                'success' => true,
                'update_available' => $data['update_available'] ?? false,
                'latest_version' => $data['latest_version'] ?? null,
                'title' => $data['title'] ?? null,
                'shamsi_date' => $data['shamsi_date'] ?? null,
                'changelog' => $data['changelog'] ?? null,
                'features' => $data['features'] ?? [],
                'distributed_at' => $data['distributed_at'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::error('Update check exception', ['message' => $e->getMessage()]);
            return ['success' => false, 'message' => 'امکان ارتباط با سرور بروزرسانی وجود ندارد.'];
        }
    }

    /**
     * دریافت تاریخچه بروزرسانی‌ها و امکانات جدید از سرور پلتفرم ساس.
     *
     * @return array{success: bool, message?: string, history: array, latest: ?array}
     */
    public function getUpdateHistory(int $limit = 20, bool $forceRefresh = false): array
    {
        $license = License::current();
        if (!$license || empty($license->license_key)) {
            return [
                'success' => false,
                'message' => 'لایسنس فعال نیست.',
                'history' => [],
                'latest' => null,
            ];
        }

        $baseUrl = config('site.platform_url');
        if (empty($baseUrl)) {
            return [
                'success' => false,
                'message' => 'آدرس پلتفرم تنظیم نشده است.',
                'history' => [],
                'latest' => null,
            ];
        }

        $cacheKey = 'platform_updates_history_' . md5($license->license_key);
        if (!$forceRefresh && \Illuminate\Support\Facades\Cache::has($cacheKey)) {
            return \Illuminate\Support\Facades\Cache::get($cacheKey);
        }

        $url = rtrim($baseUrl, '/') . '/api/updates/history';
        try {
            $response = Http::timeout(8)
                ->withToken($license->license_key)
                ->withHeaders([
                    'X-Tenant-Domain' => $this->licenseRequestDomain($license),
                ])
                ->get($url, ['limit' => $limit]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'خطا در برقراری ارتباط با پلتفرم.',
                    'history' => [],
                    'latest' => null,
                ];
            }

            $data = $response->json();
            if (empty($data['allowed'])) {
                return [
                    'success' => false,
                    'message' => $data['error'] ?? 'دسترسی مجاز نیست.',
                    'history' => [],
                    'latest' => null,
                ];
            }

            $history = is_array($data['history'] ?? null) ? $data['history'] : [];
            $latest = is_array($data['latest'] ?? null) ? $data['latest'] : ($history[0] ?? null);

            $result = [
                'success' => true,
                'history' => $history,
                'latest'  => $latest,
            ];

            \Illuminate\Support\Facades\Cache::put($cacheKey, $result, now()->addMinutes(10));

            return $result;
        } catch (\Throwable $e) {
            Log::error('Get update history exception', ['message' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'امکان ارتباط با سرور بروزرسانی وجود ندارد.',
                'history' => [],
                'latest'  => null,
            ];
        }
    }

    private function licenseRequestDomain(License $license): string
    {
        return License::normalizeDomain($license->domain ?: request()->getHost());
    }
}
