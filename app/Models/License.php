<?php

namespace App\Models;

use App\Casts\EncryptedOrPlaintext;
use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    protected $fillable = [
        'license_key',
        'domain',
        'expires_at',
        'modules',
        'support_expires_at',
        'support_starts_at',
        'support_level',
        'platform_valid',
        'last_validated_at',
    ];

    /**
     * Phase 5 / P0-4:
     * `license_key` با Cast `EncryptedOrPlaintext` ذخیره می‌شود:
     * - رکوردهای جدید/به‌روزرسانی شده با Laravel\Crypt به‌صورت encrypted در DB ذخیره می‌شوند.
     * - رکوردهای legacy (plaintext) بدون شکست خوانده می‌شوند و در اولین save بعدی
     *   به‌طور خودکار encrypted می‌شوند (lazy migration بدون مایگریشن دیتابیس).
     */
    protected function casts(): array
    {
        return [
            'license_key' => EncryptedOrPlaintext::class,
            'expires_at' => 'datetime',
            'support_expires_at' => 'datetime',
            'support_starts_at' => 'datetime',
            'modules' => 'array',
            'platform_valid' => 'boolean',
            'last_validated_at' => 'datetime',
        ];
    }

    /**
     * The active license for this installation, resolved for the REQUEST HOST.
     *
     * Domain-aware resolution (SaaS multi-tenant safe):
     *
     * An installation can legitimately hold several License rows (e.g. a
     * valid production-domain license plus a stale/invalid `127.0.0.1` row
     * from an earlier local activation). The previous implementation picked
     * `latest()` blindly, so a request served under `customer-site.test`
     * could be handed the invalid localhost license and be wrongly denied
     * by `EnsureLicenseValid` — while the matching valid license sat in the
     * table. Selection is therefore host-scoped:
     *
     *   1. Licenses whose normalized domain matches the request host are
     *      the tenant's own rows. Among them, ranked deterministically:
     *      the newest row that is currently locally ACTIVE (isValid()
     *      against this host) wins; only when NO row for the host is
     *      active does the newest host-matching row resolve, so the
     *      middleware can re-validate/record the revocation.
     *   2. Legacy installs where the row has NO domain (activated before
     *      domain was recorded, `WebhookTest`-style seeds): the newest
     *      domain-less row is a compatibility fallback ONLY — it is never
     *      returned while any host-matching row exists.
     *   3. A host that matches no row (e.g. an unknown vhost) and finds no
     *      domain-less fallback returns null; the caller (middleware)
     *      already treats that as "not activated".
     *
     * This method intentionally does NOT filter out invalid rows entirely:
     * the validity contract (platform_valid, expiry, domain match) is judged
     * by `isValid()`, and `EnsureLicenseValid` must still be able to LOAD a
     * revoked license to run its platform re-validation and record the
     * revocation. Selecting the right tenant row and judging it are two
     * different concerns.
     *
     * Deterministic ranking among a host's own rows (several historical
     * licenses for the same domain is a legitimate state — re-activations,
     * stale local rows):
     *
     *   a. the newest host-matching row that is currently locally ACTIVE
     *      (`isValid()` against this host: key present, not expired,
     *      platform_valid) wins, so a stale/revoked newer row can never
     *      mask a still-active older license for the same domain;
     *   b. otherwise (every row for the host is inactive) the newest
     *      host-matching row is returned anyway, so the middleware can
     *      re-validate it against the platform and record the revocation.
     */
    public static function current(?string $requestHost = null): ?self
    {
        $host = $requestHost ?? request()->getHost();
        $normalizedHost = self::normalizeDomain($host);

        $query = self::query()->orderByDesc('id');

        if ($normalizedHost !== '') {
            // The tenant's own rows: every license whose normalized domain
            // equals the request host. normalizeDomain() strips the
            // scheme/trailing slash so stored URLs match bare hosts.
            $hostMatches = $query->get()->filter(
                fn (self $license) => self::normalizeDomain($license->domain) === $normalizedHost
            );

            if ($hostMatches->isNotEmpty()) {
                // Rank by current local activity (see docblock a/b): newest
                // active row first; inactive newest row only as the last
                // resort so revocation stays observable/re-recordable.
                return $hostMatches->first(
                    fn (self $license) => $license->isValid($normalizedHost)
                ) ?? $hostMatches->first();
            }

            // Backward compatibility: legacy installation rows with no
            // domain at all (see WebhookTest/SubscriptionUXTest seeds) must
            // keep resolving on any host — but never outrank an explicit
            // host match, and only when the host itself is the app's own
            // configured host. An unknown foreign host must not inherit a
            // domain-less license.
            if ($normalizedHost === self::normalizeDomain(config('app.url'))) {
                return $query->get()->first(
                    fn (self $license) => self::normalizeDomain($license->domain) === ''
                );
            }

            return null;
        }

        // No request host at all (console/bootstrap edge): fall back to the
        // legacy latest-record behaviour, newest domain match first.
        return $query->get()->first(
            fn (self $license) => self::normalizeDomain($license->domain) !== ''
        ) ?? $query->first();
    }

    /**
     * همان منطق LicenseActivationService: مقایسهٔ دامنه باید بعد از نرمال‌سازی باشد (پروتکل، حروف بزرگ، اسلش انتها).
     */
    public static function normalizeDomain(?string $domain): string
    {
        if ($domain === null || $domain === '') {
            return '';
        }
        $domain = trim($domain);
        if (str_contains($domain, '://')) {
            $parsed = parse_url($domain);
            $domain = $parsed['host'] ?? $domain;
        }
        $domain = rtrim($domain, '/');

        return strtolower($domain);
    }

    /**
     * Whether the current license is valid (domain match, not expired, and not revoked on platform).
     * platform_valid is updated when we re-validate with the platform (e.g. in middleware).
     */
    public function isValid(?string $requestHost = null): bool
    {
        if (empty($this->license_key)) {
            return false;
        }
        $host = $requestHost ?? request()->getHost();
        if (! empty($this->domain) && self::normalizeDomain($this->domain) !== self::normalizeDomain($host)) {
            return false;
        }
        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }
        if (array_key_exists('platform_valid', $this->getAttributes()) && ! $this->platform_valid) {
            return false;
        }
        return true;
    }

    /**
     * Status label for admin display.
     */
    public function statusLabel(): string
    {
        if (empty($this->license_key)) {
            return 'تنظیم نشده';
        }
        if (array_key_exists('platform_valid', $this->getAttributes()) && ! $this->platform_valid) {
            return 'ابطال‌شده توسط پلتفرم';
        }
        $host = request()->getHost();
        if (! empty($this->domain) && self::normalizeDomain($this->domain) !== self::normalizeDomain($host)) {
            return 'دامنه نامطابقت';
        }
        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return 'منقضی شده';
        }
        return 'فعال';
    }

    /**
     * Mask license key for display (e.g. XXXX-XXXX-****-****).
     */
    public function maskedKey(): string
    {
        if (empty($this->license_key) || strlen($this->license_key) < 8) {
            return '—';
        }
        $len = strlen($this->license_key);
        return substr($this->license_key, 0, 4) . '-****-****-' . substr($this->license_key, -4);
    }
}
