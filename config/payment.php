<?php

return [

    /*
    | درگاه فعال: zarinpal | idpay
    | مقدار خالی یا نامعتبر = درگاه غیرفعال (فعال‌سازی دستی توسط ادمین)
    */
    'gateway' => env('PAYMENT_GATEWAY', ''),

    /*
    | آدرس callback برای اشتراک پس از بازگشت از درگاه بانکی.
    | اگر خالی باشد از APP_URL + /subscription/callback/{invoice} ساخته می‌شود.
    */
    'callback_url' => env('PAYMENT_CALLBACK_URL', ''),

    /*
    | sandbox=true → URL آزمایشی درگاه استفاده می‌شود (فقط در staging/local)
    */
    'sandbox' => filter_var(env('PAYMENT_SANDBOX', false), FILTER_VALIDATE_BOOLEAN),

    // ─── زرین‌پال ──────────────────────────────────────────────────────────
    'zarinpal' => [
        'merchant_id'   => env('ZARINPAL_MERCHANT_ID', ''),
        'request_url'   => 'https://api.zarinpal.com/pg/v4/payment/request.json',
        'verify_url'    => 'https://api.zarinpal.com/pg/v4/payment/verify.json',
        'sandbox_url'   => 'https://sandbox.zarinpal.com/pg/v4/payment/request.json',
        'sandbox_verify'=> 'https://sandbox.zarinpal.com/pg/v4/payment/verify.json',
        'start_url'     => 'https://www.zarinpal.com/pg/StartPay/',
        'sandbox_start' => 'https://sandbox.zarinpal.com/pg/StartPay/',
        'currency'      => 'IRT', // تومان (IRR = ریال)
    ],

    // ─── آیدی‌پی ───────────────────────────────────────────────────────────
    'idpay' => [
        'api_key'       => env('IDPAY_API_KEY', ''),
        'sandbox'       => filter_var(env('PAYMENT_SANDBOX', false), FILTER_VALIDATE_BOOLEAN),
        'request_url'   => 'https://api.idpay.ir/v1.1/payment',
        'verify_url'    => 'https://api.idpay.ir/v1.1/payment/verify',
    ],

];
