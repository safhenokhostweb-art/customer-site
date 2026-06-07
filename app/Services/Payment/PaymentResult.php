<?php

namespace App\Services\Payment;

final class PaymentResult
{
    public function __construct(
        public readonly bool   $success,
        public readonly string $refId,        // شماره پیگیری از درگاه
        public readonly string $cardPan = '',  // شماره کارت (در صورت ارائه توسط درگاه)
        public readonly string $message = '',
    ) {}

    public static function ok(string $refId, string $cardPan = '', string $message = ''): self
    {
        return new self(true, $refId, $cardPan, $message);
    }

    public static function fail(string $message, string $refId = ''): self
    {
        return new self(false, $refId, '', $message);
    }
}
