<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * درگاه بانک پاسارگاد (PEP)
 * REST API v1 — امضای RSA
 */
class PasargadGateway implements PaymentGatewayInterface
{
    private const TOKEN_URL  = 'https://pep.shaparak.ir/Api/v1/Payment/GetToken';
    private const VERIFY_URL = 'https://pep.shaparak.ir/Api/v1/Payment/VerifyPayment';
    private const CHECK_URL  = 'https://pep.shaparak.ir/Api/v1/Payment/CheckTransaction';
    private const PAY_URL    = 'https://pep.shaparak.ir/payment.aspx';

    public function __construct(private readonly array $config = []) {}

    public function name(): string { return 'pasargad'; }

    private function cfg(string $key, mixed $default = ''): mixed
    {
        return $this->config[$key] ?? $default;
    }

    public function request(Invoice $invoice, User $user, string $callbackUrl): string
    {
        $merchantCode = $this->cfg('merchant_code');
        $terminalCode = $this->cfg('terminal_code');
        $privateKey   = $this->cfg('private_key');

        if (empty($merchantCode) || empty($terminalCode) || empty($privateKey)) {
            throw new GatewayException('اطلاعات درگاه بانک پاسارگاد کامل نیست.');
        }

        $invoiceNumber = $invoice->invoice_number;
        $invoiceDate   = now()->format('Y/m/d H:i:s');
        $amount        = (int) ($invoice->total_amount * 10); // تومان → ریال
        $action        = 1003; // کد عملیات خرید

        // ساخت داده برای امضا
        $data = "##{$merchantCode}#{$terminalCode}#{$invoiceNumber}#{$invoiceDate}#{$amount}#{$action}#{$callbackUrl}##";
        $signature = $this->sign($data, $privateKey);

        $payload = [
            'MerchantCode'  => $merchantCode,
            'TerminalCode'  => $terminalCode,
            'InvoiceNumber' => $invoiceNumber,
            'InvoiceDate'   => $invoiceDate,
            'Amount'        => $amount,
            'RedirectAddress' => $callbackUrl,
            'Action'        => $action,
            'Timestamp'     => now()->format('Y/m/d H:i:s'),
            'Sign'          => $signature,
        ];

        $response = Http::timeout(15)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post(self::TOKEN_URL, $payload);

        if (! $response->successful()) {
            Log::error('Pasargad GetToken failed', ['status' => $response->status(), 'invoice' => $invoiceNumber]);
            throw new GatewayException('خطا در اتصال به درگاه بانک پاسارگاد.');
        }

        $data    = $response->json();
        $isSuccess = $data['IsSuccess'] ?? false;
        $token   = $data['Token']       ?? '';

        if (! $isSuccess || empty($token)) {
            $msg = $data['Message'] ?? 'دریافت توکن از بانک پاسارگاد ناموفق بود.';
            throw new GatewayException($msg);
        }

        return self::PAY_URL . '?n=' . $token;
    }

    public function validateCallback(array $params, array $headers, string $rawBody): void
    {
        $invoiceNumber = $params['InvoiceNumber'] ?? $params['invoiceNumber'] ?? '';
        if (empty($invoiceNumber)) {
            throw new GatewayException('پاسخ درگاه بانک پاسارگاد فاقد شماره فاکتور است.');
        }
    }

    public function verify(Invoice $invoice, array $params): PaymentResult
    {
        $merchantCode  = $this->cfg('merchant_code');
        $terminalCode  = $this->cfg('terminal_code');
        $privateKey    = $this->cfg('private_key');

        $invoiceNumber = $params['InvoiceNumber'] ?? $invoice->invoice_number;
        $invoiceDate   = $params['InvoiceDate']   ?? now()->format('Y/m/d H:i:s');

        $data = "##{$merchantCode}#{$terminalCode}#{$invoiceNumber}#{$invoiceDate}##";
        $signature = $this->sign($data, $privateKey);

        $payload = [
            'MerchantCode'  => $merchantCode,
            'TerminalCode'  => $terminalCode,
            'InvoiceNumber' => $invoiceNumber,
            'InvoiceDate'   => $invoiceDate,
            'Amount'        => (int) ($invoice->total_amount * 10),
            'Timestamp'     => now()->format('Y/m/d H:i:s'),
            'Sign'          => $signature,
        ];

        $response = Http::timeout(15)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post(self::VERIFY_URL, $payload);

        if (! $response->successful()) {
            throw new GatewayException('خطا در تأیید پرداخت بانک پاسارگاد.');
        }

        $data      = $response->json();
        $isSuccess = $data['IsSuccess'] ?? false;

        if (! $isSuccess) {
            $msg = $data['Message'] ?? 'تأیید پرداخت بانک پاسارگاد ناموفق بود.';
            return PaymentResult::fail($msg);
        }

        $refId = $data['ShaparakRefNumber'] ?? ($data['TraceNumber'] ?? '');
        return PaymentResult::ok((string) $refId, $data['MaskedCardNumber'] ?? '');
    }

    private function sign(string $data, string $privateKeyPem): string
    {
        $privateKey = openssl_pkey_get_private($privateKeyPem);
        if (! $privateKey) {
            throw new GatewayException('کلید خصوصی بانک پاسارگاد نامعتبر است.');
        }

        openssl_sign($data, $signature, $privateKey, OPENSSL_ALGO_SHA1);
        return base64_encode($signature);
    }
}
