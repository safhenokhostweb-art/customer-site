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
     * The single active license for this installation (latest record).
     */
    public static function current(): ?self
    {
        return self::latest()->first();
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
