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
        // Accept both naming conventions: PLATFORM_API_URL (new) or PLATFORM_URL (legacy)
        $this->baseUrl = rtrim(config('platform.api_url',
            env('PLATFORM_API_URL', env('PLATFORM_URL', ''))
        ), '/');

        // Accept both naming conventions: PLATFORM_ADMIN_TOKEN (new) or PLATFORM_API_TOKEN (legacy)
        $this->token = config('platform.admin_token',
            env('PLATFORM_ADMIN_TOKEN', env('PLATFORM_API_TOKEN', ''))
        );
    }

    // ── Theme Marketplace (TB-2) ────────────────────────────────────────────

    public function marketplaceThemes(): array
    {
        $resp = $this->tenantGet('/api/theme-marketplace');
        return $resp->ok() ? ($resp->json('themes') ?? []) : [];
    }

    public function marketplaceTheme(int $themeId): ?array
    {
        $resp = $this->tenantGet("/api/theme-marketplace/{$themeId}");
        return $resp->ok() ? $resp->json('theme') : null;
    }

    public function marketplaceMyLicenses(): array
    {
        $resp = $this->tenantGet('/api/theme-marketplace/my-licenses');
        return $resp->ok() ? ($resp->json('licenses') ?? []) : [];
    }

    public function marketplaceInitiatePurchase(int $themeId, string $redirectBackUrl): array
    {
        $resp = $this->tenantPost("/api/theme-marketplace/{$themeId}/initiate-purchase", [
            'redirect_back_url' => $redirectBackUrl,
        ]);
        return $resp->json() ?? ['success' => false, 'error' => 'no_response'];
    }

    public function marketplaceDownloadTheme(int $themeId): ?array
    {
        $resp = $this->tenantGet("/api/theme-marketplace/{$themeId}/download");
        return $resp->ok() ? $resp->json() : null;
    }

    /** GAP-H06 — Get invoice data for a theme license (for PDF generation) */
    public function getThemeLicenseInvoiceData(string $licenseKey): ?array
    {
        $resp = $this->tenantGet("/api/theme-marketplace/licenses/{$licenseKey}/invoice-data");
        return $resp->ok() ? $resp->json() : null;
    }

    // ── Theme Bundles (GAP-010) ───────────────────────────────────────────────

    public function themeBundles(): array
    {
        $resp = $this->tenantGet('/api/theme-bundles');
        return $resp->ok() ? ($resp->json('bundles') ?? []) : [];
    }

    public function themeBundle(int $bundleId): ?array
    {
        $resp = $this->tenantGet("/api/theme-bundles/{$bundleId}");
        return $resp->ok() ? $resp->json('bundle') : null;
    }

    public function purchaseBundle(int $bundleId, string $redirectBackUrl): array
    {
        $resp = $this->tenantPost("/api/theme-bundles/{$bundleId}/purchase", [
            'redirect_back_url' => $redirectBackUrl,
        ]);
        return $resp->json() ?? ['success' => false, 'error' => 'no_response'];
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

    // ── Lifecycle API (C02/C04) ──────────────────────────────────────────────

    /**
     * After subscription payment, notify platform to reactivate lifecycle.
     * Idempotent — safe to call even if site is already active.
     */
    public function requestLifecycleReactivation(): bool
    {
        try {
            $resp = $this->tenantPost('/api/lifecycle/reactivate', []);
            return $resp->ok() && ($resp->json('success') === true);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Get current lifecycle status and restore fee from platform.
     */
    public function getLifecycleStatus(): array
    {
        try {
            $resp = $this->tenantGet('/api/lifecycle/status');
            return $resp->ok() ? ($resp->json() ?? []) : [];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Request site restoration — creates SiteLifecycleInvoice on platform.
     * Returns ['success', 'invoice_number', 'amount', 'is_free', ...].
     */
    public function requestLifecycleRestore(): array
    {
        try {
            $resp = $this->tenantPost('/api/lifecycle/request-restore', []);
            return $resp->json() ?? ['success' => false, 'error' => 'no_response'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Confirm restore after payment — marks invoice paid + restores site.
     */
    public function confirmLifecycleRestore(string $invoiceNumber, string $paymentRef): bool
    {
        try {
            $resp = $this->tenantPost('/api/lifecycle/confirm-restore', [
                'invoice_number' => $invoiceNumber,
                'payment_ref'    => $paymentRef,
            ]);
            return $resp->ok() && ($resp->json('success') === true);
        } catch (\Throwable) {
            return false;
        }
    }

    // ── Finance Ledger Push (CENTRAL_FINANCE_LEDGER_MODULE) ─────────────────

    /**
     * Push a payment event to the platform finance ledger.
     * Fire-and-forget: failures are silently logged, never re-thrown.
     */
    public function pushLedgerEvent(array $data): void
    {
        try {
            $this->tenantPost('/api/ledger/push-event', $data);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('LedgerPush: failed', [
                'event_type' => $data['event_type'] ?? null,
                'error'      => $e->getMessage(),
            ]);
        }
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

    /** Calls platform API with license_key+domain (tenant.api middleware) */
    private function tenantGet(string $path, array $query = []): Response
    {
        [$licenseKey, $domain] = $this->resolveTenantCredentials();
        return Http::withToken($licenseKey)
            ->acceptJson()
            ->withHeaders(['X-Tenant-Domain' => $domain])
            ->get($this->baseUrl . $path, $query);
    }

    private function tenantPost(string $path, array $data = []): Response
    {
        [$licenseKey, $domain] = $this->resolveTenantCredentials();
        return Http::withToken($licenseKey)
            ->acceptJson()
            ->withHeaders(['X-Tenant-Domain' => $domain])
            ->post($this->baseUrl . $path, $data);
    }

    private function resolveTenantCredentials(): array
    {
        $license = \App\Models\License::current();
        $licenseKey = $license?->license_key ?? '';
        $domain     = $license?->domain ?? request()->getHost();
        return [$licenseKey, $domain];
    }

    public function isConfigured(): bool
    {
        return ! empty($this->baseUrl) && ! empty($this->token);
    }
}
