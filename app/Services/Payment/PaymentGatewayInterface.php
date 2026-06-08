<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use App\Models\User;

interface PaymentGatewayInterface
{
    /**
     * ایجاد درخواست پرداخت.
     * در صورت موفقیت URL ریدایرکت به درگاه را برمی‌گرداند.
     * در صورت خطا GatewayException پرتاب می‌کند.
     */
    public function request(Invoice $invoice, User $user, string $callbackUrl): string;

    /**
     * اعتبارسنجی امضای callback قبل از تأیید پرداخت.
     *
     * @param  array  $params    پارامترهای GET/POST دریافت‌شده
     * @param  array  $headers   هدرهای HTTP (کلیدها lowercase، مقادیر string یا array)
     * @param  string $rawBody   بدنه خام درخواست (برای HMAC)
     * @throws GatewayException  اگر امضا یا فرمت نامعتبر باشد
     */
    public function validateCallback(array $params, array $headers, string $rawBody): void;

    /**
     * تأیید پرداخت پس از بازگشت از درگاه.
     * در صورت موفقیت PaymentResult برمی‌گرداند.
     * در صورت خطا GatewayException پرتاب می‌کند.
     */
    public function verify(Invoice $invoice, array $callbackParams): PaymentResult;

    /** نام درگاه برای لاگ */
    public function name(): string;
}
