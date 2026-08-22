<?php

return [

    'live_gateway' => filter_var(env('PAYMENT_GATEWAY_LIVE', false), FILTER_VALIDATE_BOOLEAN),

    /*
    | درگاه فعال (fallback وقتی جدول payment_gateways خالی است):
    | zarinpal | idpay | mellat | melli | tejarat | pasargad | parsian
    | مقدار خالی = درگاه غیرفعال
    */
    'gateway' => env('PAYMENT_GATEWAY', ''),

    /*
    | آدرس callback — اگر خالی باشد از APP_URL + route ساخته می‌شود
    */
    'callback_url' => env('PAYMENT_CALLBACK_URL', ''),

    /*
    | sandbox=true → URL آزمایشی (staging/local)
    */
    'sandbox' => filter_var(env('PAYMENT_SANDBOX', false), FILTER_VALIDATE_BOOLEAN),

    // ─── زرین‌پال ──────────────────────────────────────────────────────────────
    'zarinpal' => [
        'merchant_id'    => env('ZARINPAL_MERCHANT_ID', ''),
        'request_url'    => 'https://api.zarinpal.com/pg/v4/payment/request.json',
        'verify_url'     => 'https://api.zarinpal.com/pg/v4/payment/verify.json',
        'sandbox_url'    => 'https://sandbox.zarinpal.com/pg/v4/payment/request.json',
        'sandbox_verify' => 'https://sandbox.zarinpal.com/pg/v4/payment/verify.json',
        'start_url'      => 'https://www.zarinpal.com/pg/StartPay/',
        'sandbox_start'  => 'https://sandbox.zarinpal.com/pg/StartPay/',
        'currency'       => 'IRT',
    ],

    // ─── آیدی‌پی ───────────────────────────────────────────────────────────────
    'idpay' => [
        'api_key'     => env('IDPAY_API_KEY', ''),
        'sandbox'     => filter_var(env('PAYMENT_SANDBOX', false), FILTER_VALIDATE_BOOLEAN),
        'request_url' => 'https://api.idpay.ir/v1.1/payment',
        'verify_url'  => 'https://api.idpay.ir/v1.1/payment/verify',
    ],

    // ─── بانک ملت (به‌پرداخت) — SOAP ──────────────────────────────────────────
    'mellat' => [
        'terminal_id' => env('MELLAT_TERMINAL_ID', ''),
        'username'    => env('MELLAT_USERNAME', ''),
        'password'    => env('MELLAT_PASSWORD', ''),
        'wsdl_url'    => 'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl',
        'pay_url'     => 'https://bpm.shaparak.ir/pgwchannel/startpayment.fcp',
        'sandbox'     => filter_var(env('PAYMENT_SANDBOX', false), FILTER_VALIDATE_BOOLEAN),
    ],

    // ─── بانک ملی (ایران‌کیش) — REST ──────────────────────────────────────────
    'melli' => [
        'terminal_id' => env('MELLI_TERMINAL_ID', ''),
        'password'    => env('MELLI_PASSWORD', ''),
        'token_url'   => 'https://ikc.shaparak.ir/api/v3/tokenization/make',
        'pay_url'     => 'https://ikc.shaparak.ir/iuiv3/IPG/Index/',
        'confirm_url' => 'https://ikc.shaparak.ir/api/v3/confirmation/purchase',
        'sandbox'     => filter_var(env('PAYMENT_SANDBOX', false), FILTER_VALIDATE_BOOLEAN),
    ],

    // ─── بانک تجارت (آقای‌پرداخت) — REST ──────────────────────────────────────
    'tejarat' => [
        'merchant_id' => env('TEJARAT_MERCHANT_ID', ''),
        'create_url'  => 'https://aqayepardakht.ir/api/v2/create',
        'verify_url'  => 'https://aqayepardakht.ir/api/v2/verify',
        'pay_url'     => 'https://aqayepardakht.ir/api/v2/gateway/',
        'sandbox'     => filter_var(env('PAYMENT_SANDBOX', false), FILTER_VALIDATE_BOOLEAN),
    ],

    // ─── بانک پاسارگاد (PEP) — REST + RSA ────────────────────────────────────
    'pasargad' => [
        'merchant_code' => env('PASARGAD_MERCHANT_CODE', ''),
        'terminal_code' => env('PASARGAD_TERMINAL_CODE', ''),
        'private_key'   => env('PASARGAD_PRIVATE_KEY', ''),
        'token_url'     => 'https://pep.shaparak.ir/Api/v1/Payment/GetToken',
        'verify_url'    => 'https://pep.shaparak.ir/Api/v1/Payment/VerifyPayment',
        'pay_url'       => 'https://pep.shaparak.ir/payment.aspx',
        'sandbox'       => filter_var(env('PAYMENT_SANDBOX', false), FILTER_VALIDATE_BOOLEAN),
    ],

    // ─── بانک پارسیان (PEC) — SOAP ────────────────────────────────────────────
    'parsian' => [
        'pin'     => env('PARSIAN_PIN', ''),
        'wsdl_url'=> 'https://pec.shaparak.ir/NewIPGServices/Sale/ServiceReference.asmx?wsdl',
        'pay_url' => 'https://pec.shaparak.ir/NewIPGServices/CA/GW/Payment',
        'sandbox' => filter_var(env('PAYMENT_SANDBOX', false), FILTER_VALIDATE_BOOLEAN),
    ],

];
