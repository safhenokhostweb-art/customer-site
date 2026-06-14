<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class PaymentGateway extends Model
{
    protected $fillable = [
        'name', 'driver', 'config', 'is_active', 'is_default', 'sort_order',
    ];

    protected $casts = [
        'config'     => 'encrypted:array',
        'is_active'  => 'boolean',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    // ─── Drivers ──────────────────────────────────────────────────────────────

    const DRIVER_ZARINPAL = 'zarinpal';
    const DRIVER_IDPAY    = 'idpay';
    const DRIVER_MELLAT   = 'mellat';
    const DRIVER_MELLI    = 'melli';
    const DRIVER_TEJARAT  = 'tejarat';
    const DRIVER_PASARGAD = 'pasargad';
    const DRIVER_PARSIAN  = 'parsian';

    public static function drivers(): array
    {
        return [
            self::DRIVER_ZARINPAL => 'زرین‌پال',
            self::DRIVER_IDPAY    => 'آیدی‌پی',
            self::DRIVER_MELLAT   => 'بانک ملت (به‌پرداخت)',
            self::DRIVER_MELLI    => 'بانک ملی (ایران‌کیش)',
            self::DRIVER_TEJARAT  => 'بانک تجارت (آقای‌پرداخت)',
            self::DRIVER_PASARGAD => 'بانک پاسارگاد',
            self::DRIVER_PARSIAN  => 'بانک پارسیان (پک)',
        ];
    }

    /** فیلدهای مورد نیاز برای هر driver */
    public static function driverFields(): array
    {
        return [
            self::DRIVER_ZARINPAL => [
                ['key' => 'merchant_id', 'label' => 'کد پذیرنده (Merchant ID)', 'type' => 'text', 'required' => true],
                ['key' => 'sandbox',     'label' => 'محیط آزمایشی (Sandbox)',   'type' => 'checkbox', 'required' => false],
            ],
            self::DRIVER_IDPAY => [
                ['key' => 'api_key', 'label' => 'کلید API',                   'type' => 'text', 'required' => true],
                ['key' => 'sandbox', 'label' => 'محیط آزمایشی (Sandbox)',      'type' => 'checkbox', 'required' => false],
            ],
            self::DRIVER_MELLAT => [
                ['key' => 'terminal_id', 'label' => 'شناسه ترمینال',           'type' => 'text',     'required' => true],
                ['key' => 'username',    'label' => 'نام کاربری',              'type' => 'text',     'required' => true],
                ['key' => 'password',    'label' => 'رمز عبور',               'type' => 'password', 'required' => true],
                ['key' => 'sandbox',     'label' => 'محیط آزمایشی',           'type' => 'checkbox', 'required' => false],
            ],
            self::DRIVER_MELLI => [
                ['key' => 'terminal_id', 'label' => 'شناسه ترمینال',           'type' => 'text',     'required' => true],
                ['key' => 'password',    'label' => 'رمزعبور / Passphrase',   'type' => 'password', 'required' => true],
                ['key' => 'sandbox',     'label' => 'محیط آزمایشی',           'type' => 'checkbox', 'required' => false],
            ],
            self::DRIVER_TEJARAT => [
                ['key' => 'merchant_id', 'label' => 'کد پذیرنده',             'type' => 'text',     'required' => true],
                ['key' => 'sandbox',     'label' => 'محیط آزمایشی',           'type' => 'checkbox', 'required' => false],
            ],
            self::DRIVER_PASARGAD => [
                ['key' => 'merchant_code',  'label' => 'کد مرچنت',            'type' => 'text',     'required' => true],
                ['key' => 'terminal_code',  'label' => 'کد ترمینال',          'type' => 'text',     'required' => true],
                ['key' => 'private_key',    'label' => 'کلید خصوصی RSA',      'type' => 'textarea', 'required' => true],
                ['key' => 'sandbox',        'label' => 'محیط آزمایشی',        'type' => 'checkbox', 'required' => false],
            ],
            self::DRIVER_PARSIAN => [
                ['key' => 'pin',     'label' => 'PIN ترمینال',                 'type' => 'password', 'required' => true],
                ['key' => 'sandbox', 'label' => 'محیط آزمایشی',               'type' => 'checkbox', 'required' => false],
            ],
        ];
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    // ─── Static helpers ───────────────────────────────────────────────────────

    /** درگاه پیش‌فرض فعال — اگر وجود نداشت null برمی‌گرداند */
    public static function getDefault(): ?self
    {
        return static::where('is_active', true)
            ->where('is_default', true)
            ->orderBy('sort_order')
            ->first();
    }

    /** تنظیم یک درگاه به‌عنوان پیش‌فرض (بقیه reset می‌شوند) */
    public function setAsDefault(): void
    {
        static::query()->update(['is_default' => false]);
        $this->update(['is_default' => true, 'is_active' => true]);
    }

    /** نام فارسی driver */
    public function getDriverLabelAttribute(): string
    {
        return static::drivers()[$this->driver] ?? $this->driver;
    }
}
