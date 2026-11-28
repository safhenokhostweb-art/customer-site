<?php

namespace App\Services\AssetProvisioning;

use App\Models\License;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SaaSPlatform\ThemeRuntime\Access\SiteAssetEntitlements;

/**
 * ADR-087: this site's asset entitlements as the platform provisions them
 * (GET {platform}/api/asset-entitlements, the same `tenant.api` transport
 * as the motion manifest: Bearer licence key + domain).
 *
 * The platform is authoritative and decides from its own state; this
 * class only syncs the answer:
 *   - current() serves the cached answer (config site.platform_config_cache_ttl),
 *   - refresh() asks the platform now (the editor calls it on open, so a
 *     grant or revoke shows on the next visit),
 *   - a failed or malformed answer never replaces a good one: the last good
 *     payload is kept in settings and used until the platform answers again,
 *   - a site that never received an answer holds none(): public assets only.
 *
 * The entitlement keys are unioned with the licence's own module list,
 * which is the same platform projection already synced by the licence
 * check, so a key-gated asset (ADR-085's solar calculator) keeps working
 * while the platform endpoint is unreachable.
 *
 * Never throws.
 */
class AssetEntitlementClient
{
    public const CACHE_KEY = 'asset_entitlements.v1';

    public const LAST_GOOD_SETTING = 'asset_entitlements_last_good';

    /** @var SiteAssetEntitlements|null per-request memo */
    private ?SiteAssetEntitlements $memo = null;

    public function current(bool $fresh = false): SiteAssetEntitlements
    {
        if (config('site.owner_site')) {
            return $this->memo = SiteAssetEntitlements::platformDesigner();
        }

        if ($fresh) {
            $this->forget();
            return $this->refresh();
        }
        if ($this->memo !== null) {
            return $this->memo;
        }
        $cached = Cache::get(self::CACHE_KEY);
        $fromCache = is_array($cached) ? SiteAssetEntitlements::fromPayload($cached) : null;

        return $this->memo = $fromCache !== null ? $this->withLicenceKeys($fromCache) : $this->refresh();
    }

    public function refresh(): SiteAssetEntitlements
    {
        $payload = $this->fetch();
        if ($payload !== null) {
            Cache::put(self::CACHE_KEY, $payload, max(30, (int) config('site.platform_config_cache_ttl', 300)));
            try {
                Setting::set(self::LAST_GOOD_SETTING, json_encode($payload, JSON_UNESCAPED_UNICODE), 'platform');
            } catch (\Throwable $e) {
                Log::warning('asset_entitlements.persist_failed', ['message' => $e->getMessage()]);
            }

            return $this->memo = $this->withLicenceKeys(SiteAssetEntitlements::fromPayload($payload));
        }

        $cached = Cache::get(self::CACHE_KEY);
        $fallback = (is_array($cached) ? SiteAssetEntitlements::fromPayload($cached) : null)
            ?? $this->lastGood()
            ?? SiteAssetEntitlements::none();

        return $this->memo = $this->withLicenceKeys($fallback);
    }

    public function forget(): void
    {
        $this->memo = null;
        Cache::forget(self::CACHE_KEY);
    }

    /** The validated platform payload, or null for every failure. */
    protected function fetch(): ?array
    {
        $license = License::current();
        $platform = rtrim((string) (config('site.platform_url') ?: config('platform.api_url')), '/');
        if (!$license?->license_key || $platform === '') {
            return null;
        }

        try {
            $response = Http::withToken($license->license_key)->acceptJson()->timeout(6)
                ->get($platform . '/api/asset-entitlements', ['domain' => $license->domain ?: request()->getHost()]);
        } catch (\Throwable $e) {
            Log::warning('asset_entitlements.fetch_failed', ['message' => $e->getMessage()]);

            return null;
        }
        if (!$response->successful()) {
            return null;
        }
        $json = $response->json();
        if (!is_array($json) || ($json['available'] ?? false) !== true) {
            return null;
        }
        unset($json['available']);
        $parsed = SiteAssetEntitlements::fromPayload($json);
        if ($parsed === null) {
            Log::warning('asset_entitlements.invalid_payload');

            return null;
        }

        // Store the normalised form (plain arrays, as JSON would give it back),
        // never the raw response.
        return json_decode(json_encode($parsed->toPayload()), true);
    }

    protected function lastGood(): ?SiteAssetEntitlements
    {
        try {
            $raw = Setting::get(self::LAST_GOOD_SETTING);
        } catch (\Throwable) {
            return null;
        }

        return is_string($raw) ? SiteAssetEntitlements::fromPayload(json_decode($raw, true)) : null;
    }

    protected function withLicenceKeys(SiteAssetEntitlements $site): SiteAssetEntitlements
    {
        try {
            $license = License::current();
            $modules = $license && $license->isValid() && is_array($license->modules) ? $license->modules : [];
        } catch (\Throwable) {
            $modules = [];
        }
        if ($modules === []) {
            return $site;
        }
        $payload = json_decode(json_encode($site->toPayload()), true);

        return SiteAssetEntitlements::make(
            $site->vertical,
            array_merge($site->entitlementKeys, array_values(array_filter($modules, 'is_string'))),
            $site->grantRefs(),
            is_array($payload['overrides'] ?? null) ? $payload['overrides'] : [],
            $site->issuedAt,
        );
    }
}
