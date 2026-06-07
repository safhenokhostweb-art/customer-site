<?php

namespace App\Services\Payment;

class PaymentGatewayFactory
{
    public static function make(): PaymentGatewayInterface
    {
        $gateway = strtolower(config('payment.gateway', ''));

        return match ($gateway) {
            'zarinpal' => new ZarinpalGateway(),
            'idpay'    => new IdpayGateway(),
            default    => throw new GatewayException(
                "درگاه پرداخت «{$gateway}» پشتیبانی نمی‌شود. مقدار PAYMENT_GATEWAY را zarinpal یا idpay قرار دهید."
            ),
        };
    }

    public static function isConfigured(): bool
    {
        $gateway = strtolower(config('payment.gateway', ''));
        if (! in_array($gateway, ['zarinpal', 'idpay'], true)) {
            return false;
        }

        return match ($gateway) {
            'zarinpal' => ! empty(config('payment.zarinpal.merchant_id')),
            'idpay'    => ! empty(config('payment.idpay.api_key')),
            default    => false,
        };
    }
}
