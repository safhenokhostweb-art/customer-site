<?php

namespace App\Services;

use App\Models\License;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * اعتبارسنجی لایسنس از طریق API پلتفرم.
 * با syncWithPlatform می‌توان وضعیت محلی را با پلتفرم همگام کرد (برای تشخیص ابطال لایسنس).
 */
class LicenseActivationService
{
    /**
     * همگام‌سازی لایسنس محلی با پلتفرم. در صورت ابطال/انقضا در پلتفرم، platform_valid و modules به‌روز می‌شوند.
     *
     * @return array{valid: bool, message?: string} نتیجه اعتبارسنجی و در صورت نامعتبر بودن پیام خطا
     */
    public function syncWithPlatform(License $license, ?string $domain = null): array
    {
        $domain = $domain ?? $license->domain ?? request()->getHost();
        $result = $this->validate($license->license_key, $domain);

        $data = [
            'last_validated_at' => now(),
        ];
        if (!empty($result['valid'])) {
            $data['platform_valid'] = true;
            $data['expires_at'] = isset($result['expires_at']) ? $result['expires_at'] : null;
            $data['modules'] = $result['modules'] ?? [];
            $data['support_expires_at'] = $result['support_expires_at'] ?? null;
            $data['support_starts_at'] = $result['support_starts_at'] ?? null;
            $data['support_level'] = $result['support_level'] ?? 'basic';
        } else {
            $data['platform_valid'] = false;
            $data['modules'] = [];
        }
        $license->update($data);
        return [
            'valid' => !empty($result['valid']),
            'message' => $result['message'] ?? null,
        ];
    }

    public function validate(string $licenseKey, string $domain): array
    {
        $baseUrl = config('site.platform_url');
        if (empty($baseUrl)) {
            return [
                'valid' => false,
                'error' => 'config_missing',
                'message' => 'آدرس پلتفرم (PLATFORM_URL) در تنظیمات تنظیم نشده است.',
            ];
        }

        $url = $baseUrl . '/api/license/validate';
        $domainNormalized = $this->normalizeDomain($domain);
        try {
            $response = Http::timeout(10)
                ->acceptJson()
                ->post($url, [
                    'license_key' => $licenseKey,
                    'domain' => $domainNormalized,
                ]);

            if (!$response->successful()) {
                $status = $response->status();
                $rawBody = $response->body();
                $body = $response->json();
                Log::warning('License API HTTP error', [
                    'status' => $status,
                    'url' => $url,
                    'body' => $rawBody,
                ]);
                $apiMessage = is_array($body) && isset($body['message']) && (string) $body['message'] !== '' ? (string) $body['message'] : null;
                if ($apiMessage !== null) {
                    $message = $apiMessage;
                } elseif (config('app.debug')) {
                    $message = 'خطا در ارتباط با سرور لایسنس (HTTP ' . $status . '). اگر بالا پیام خطا نیست، در customer-site فایل storage/logs/laravel.log را باز کنید و آخرین خط «License API HTTP error» را ببینید.';
                } else {
                    $message = 'خطا در ارتباط با سرور لایسنس. بعداً تلاش کنید.';
                }
                return [
                    'valid' => false,
                    'error' => 'api_error',
                    'message' => $message,
                ];
            }

            $data = $response->json();
            if (!is_array($data)) {
                return ['valid' => false, 'error' => 'invalid_response', 'message' => 'پاسخ سرور نامعتبر است.'];
            }

            if (!empty($data['valid'])) {
                return $data;
            }

            $messages = [
                'invalid_key' => 'کلید لایسنس نامعتبر است.',
                'revoked' => 'این لایسنس لغو شده است.',
                'expired' => 'تاریخ انقضای لایسنس گذشته است.',
                'domain_mismatch' => 'دامنهٔ این سایت با لایسنس مطابقت ندارد.',
                'site_inactive' => 'سایت در پلتفرم غیرفعال است.',
                'server_error' => 'خطای سرور لایسنس. بعداً تلاش کنید.',
            ];
            $message = !empty($data['message'])
                ? $data['message']
                : ($messages[$data['error'] ?? ''] ?? 'لایسنس معتبر نیست.');
            return [
                'valid' => false,
                'error' => $data['error'] ?? 'unknown',
                'message' => $message,
            ];
        } catch (\Throwable $e) {
            Log::error('License validation exception', [
                'message' => $e->getMessage(),
                'url' => $url,
                'exception' => get_class($e),
            ]);
            return [
                'valid' => false,
                'error' => 'connection_error',
                'message' => 'امکان ارتباط با سرور لایسنس وجود ندارد. اتصال اینترنت و آدرس پلتفرم (PLATFORM_URL در .env) را بررسی کنید.',
            ];
        }
    }

    /**
     * استخراج فقط host از دامنه (اگر کاربر آدرس کامل وارد کرده باشد).
     */
    private function normalizeDomain(string $domain): string
    {
        $domain = trim($domain);
        if (str_contains($domain, '://')) {
            $parsed = parse_url($domain);
            $domain = $parsed['host'] ?? $domain;
        }
        $domain = rtrim($domain, '/');
        return strtolower($domain);
    }
}
