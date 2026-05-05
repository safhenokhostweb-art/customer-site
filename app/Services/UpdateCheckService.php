<?php

namespace App\Services;

use App\Models\License;
use App\Models\ModuleVersion;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * بررسی وجود بروزرسانی از طریق API پلتفرم.
 */
class UpdateCheckService
{
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
            $response = Http::timeout(10)->post($url, [
                'license_key' => $license->license_key,
                'domain' => $license->domain ?: request()->getHost(),
                'versions' => $versions,
            ]);

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
            $response = Http::timeout(10)->get($url, [
                'license_key' => $license->license_key,
                'domain' => $license->domain ?: request()->getHost(),
            ]);

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
            'license_key' => $license->license_key,
            'domain' => $license->domain ?: request()->getHost(),
        ];
        if ($currentVersion !== null) {
            $params['current_version'] = $currentVersion;
        }

        try {
            $response = Http::timeout(10)->get($url, $params);

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
                'changelog' => $data['changelog'] ?? null,
                'distributed_at' => $data['distributed_at'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::error('Update check exception', ['message' => $e->getMessage()]);
            return ['success' => false, 'message' => 'امکان ارتباط با سرور بروزرسانی وجود ندارد.'];
        }
    }
}
