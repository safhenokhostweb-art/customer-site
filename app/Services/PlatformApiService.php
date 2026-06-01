<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * سرویس ارتباط با API پلتفرم اصلی.
 * توکن ادمین و آدرس پلتفرم از env خوانده می‌شود.
 */
class PlatformApiService
{
    private string $baseUrl;
    private string $token;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('platform.api_url', env('PLATFORM_API_URL', '')), '/');
        $this->token   = config('platform.admin_token', env('PLATFORM_ADMIN_TOKEN', ''));
    }

    // ── مجوز ادغام مکالمات ─────────────────────────────────────────────────

    public function listMergePermissions(): array
    {
        $resp = $this->get('/api/mobile/admin/merge-permissions');
        return $resp->ok() ? ($resp->json('data') ?? []) : [];
    }

    public function grantMergePermission(string $userId): bool
    {
        $resp = $this->post('/api/mobile/admin/merge-permissions', ['user_id' => $userId]);
        return $resp->ok() || $resp->status() === 201;
    }

    public function revokeMergePermission(string $userId): bool
    {
        $resp = $this->delete("/api/mobile/admin/merge-permissions/{$userId}");
        return $resp->ok();
    }

    public function checkMergePermission(string $userId): bool
    {
        $resp = $this->get("/api/mobile/admin/merge-permissions/check/{$userId}");
        return $resp->ok() && (bool) $resp->json('data.can_merge');
    }

    // ── HTTP helpers ─────────────────────────────────────────────────────────

    private function get(string $path, array $query = []): Response
    {
        return Http::withToken($this->token)
            ->acceptJson()
            ->get($this->baseUrl . $path, $query);
    }

    private function post(string $path, array $data = []): Response
    {
        return Http::withToken($this->token)
            ->acceptJson()
            ->post($this->baseUrl . $path, $data);
    }

    private function delete(string $path): Response
    {
        return Http::withToken($this->token)
            ->acceptJson()
            ->delete($this->baseUrl . $path);
    }

    public function isConfigured(): bool
    {
        return ! empty($this->baseUrl) && ! empty($this->token);
    }
}
