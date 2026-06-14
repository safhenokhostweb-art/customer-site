<?php

namespace App\Services\Payment;

use App\Models\PaymentGateway as GatewayModel;

class PaymentGatewayFactory
{
    /**
     * درگاه فعال را می‌سازد.
     * اولویت: درگاه پیش‌فرض در DB → متغیر محیطی PAYMENT_GATEWAY
     */
    public static function make(): PaymentGatewayInterface
    {
        // ابتدا از جدول payment_gateways بخوان
        try {
            $model = GatewayModel::getDefault();
            if ($model) {
                return static::makeFromModel($model);
            }
        } catch (\Throwable) {
            // جدول هنوز migrate نشده یا اتصال در دسترس نیست → fallback به config
        }

        // fallback: متغیر محیطی
        $driver = strtolower(config('payment.gateway', ''));

        return match ($driver) {
            'zarinpal' => new ZarinpalGateway(),
            'idpay'    => new IdpayGateway(),
            default    => throw new GatewayException(
                "درگاه پرداخت «{$driver}» پشتیبانی نمی‌شود. یک درگاه پیش‌فرض در پنل تنظیم کنید."
            ),
        };
    }

    /** ساخت درگاه از مدل ذخیره‌شده در DB */
    public static function makeFromModel(GatewayModel $model): PaymentGatewayInterface
    {
        $cfg = $model->config ?? [];

        return match ($model->driver) {
            'zarinpal' => static::makeZarinpal($cfg),
            'idpay'    => static::makeIdpay($cfg),
            'mellat'   => new MellatGateway($cfg),
            'melli'    => new MelliGateway($cfg),
            'tejarat'  => new TejaratGateway($cfg),
            'pasargad' => new PasargadGateway($cfg),
            'parsian'  => new ParsianGateway($cfg),
            default    => throw new GatewayException("درگاه «{$model->driver}» پشتیبانی نمی‌شود."),
        };
    }

    /**
     * بررسی می‌کند آیا یک درگاه معتبر و پیکربندی‌شده وجود دارد.
     */
    public static function isConfigured(): bool
    {
        // بررسی DB
        try {
            $model = GatewayModel::getDefault();
            if ($model) {
                return static::isModelConfigured($model);
            }
        } catch (\Throwable) {
            // fallback
        }

        // بررسی env
        $driver = strtolower(config('payment.gateway', ''));
        if (! in_array($driver, ['zarinpal', 'idpay', 'mellat', 'melli', 'tejarat', 'pasargad', 'parsian'], true)) {
            return false;
        }

        return match ($driver) {
            'zarinpal' => ! empty(config('payment.zarinpal.merchant_id')),
            'idpay'    => ! empty(config('payment.idpay.api_key')),
            default    => false,
        };
    }

    private static function isModelConfigured(GatewayModel $model): bool
    {
        $cfg = $model->config ?? [];

        return match ($model->driver) {
            'zarinpal' => ! empty($cfg['merchant_id']),
            'idpay'    => ! empty($cfg['api_key']),
            'mellat'   => ! empty($cfg['terminal_id']) && ! empty($cfg['username']) && ! empty($cfg['password']),
            'melli'    => ! empty($cfg['terminal_id']) && ! empty($cfg['password']),
            'tejarat'  => ! empty($cfg['merchant_id']),
            'pasargad' => ! empty($cfg['merchant_code']) && ! empty($cfg['terminal_code']) && ! empty($cfg['private_key']),
            'parsian'  => ! empty($cfg['pin']),
            default    => false,
        };
    }

    // ─── Legacy env-based builders (backward compat) ──────────────────────────

    private static function makeZarinpal(array $cfg): ZarinpalGateway
    {
        // Override config values so ZarinpalGateway reads correct credentials
        config([
            'payment.zarinpal.merchant_id' => $cfg['merchant_id'] ?? config('payment.zarinpal.merchant_id'),
            'payment.sandbox'              => $cfg['sandbox']     ?? config('payment.sandbox'),
        ]);
        return new ZarinpalGateway();
    }

    private static function makeIdpay(array $cfg): IdpayGateway
    {
        config([
            'payment.idpay.api_key' => $cfg['api_key'] ?? config('payment.idpay.api_key'),
            'payment.sandbox'       => $cfg['sandbox']  ?? config('payment.sandbox'),
        ]);
        return new IdpayGateway();
    }
}
