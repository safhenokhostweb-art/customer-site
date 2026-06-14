<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * درگاه بانک تجارت — آقای‌پرداخت (AqayePardakht)
 * REST API v2
 */
class TejaratGateway implements PaymentGatewayInterface
{
    private const CREATE_URL = 'https://aqayepardakht.ir/api/v2/create';
    private const VERIFY_URL = 'https://aqayepardakht.ir/api/v2/verify';
    private const PAY_URL    = 'https://aqayepardakht.ir/api/v2/gateway/';

    public function __construct(private readonly array $config = []) {}

    public function name(): string { return 'tejarat'; }

    private function cfg(string $key, mixed $default = ''): mixed
    {
        return $this->config[$key] ?? $default;
    }

    public function request(Invoice $invoice, User $user, string $callbackUrl): string
    {
        $merchantId = $this->cfg('merchant_id');

        if (empty($merchantId)) {
            throw new GatewayException('کد پذیرنده بانک تجارت تنظیم نشده است.');
        }

        $sandbox = (bool) $this->cfg('sandbox', false);

        $payload = [
            'pin'         => $merchantId,
            'amount'      => (int) ($invoice->total_amount * 10), // تومان → ریال
            'callback'    => $callbackUrl,
            'invoice_id'  => $invoice->invoice_number,
            'mobile'      => $user->mobile ?? '',
            'description' => "پرداخت فاکتور {$invoice->invoice_number}",
        ];

        if ($sandbox) {
            $payload['sandbox'] = '1';
        }

        $response = Http::timeout(15)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post(self::CREATE_URL, $payload);

        if (! $response->successful()) {
            Log::error('Tejarat (AqayePardakht) create failed', [
                'status'  => $response->status(),
                'invoice' => $invoice->invoice_number,
            ]);
            throw new GatewayException('خطا در اتصال به درگاه بانک تجارت.');
        }

        $data   = $response->json();
        $status = $data['status']  ?? 0;
        $transId = $data['transid'] ?? '';

        if ($status != 1 || empty($transId)) {
            $msg = $data['message'] ?? "خطای درگاه بانک تجارت: کد {$status}";
            Log::warning('Tejarat create error', ['data' => $data, 'invoice' => $invoice->invoice_number]);
            throw new GatewayException($msg, (int) $status);
        }

        return self::PAY_URL . $transId;
    }

    public function validateCallback(array $params, array $headers, string $rawBody): void
    {
        $transId = $params['transid'] ?? '';
        if (empty($transId)) {
            throw new GatewayException('پاسخ درگاه بانک تجارت فاقد transid است.');
        }
    }

    public function verify(Invoice $invoice, array $params): PaymentResult
    {
        $status  = (string) ($params['status']  ?? '');
        $transId = $params['transid'] ?? '';

        if ($status !== '1') {
            return PaymentResult::fail('پرداخت توسط کاربر لغو شد یا ناموفق بود.');
        }

        $merchantId = $this->cfg('merchant_id');
        $sandbox    = (bool) $this->cfg('sandbox', false);

        $payload = [
            'pin'     => $merchantId,
            'transid' => $transId,
            'amount'  => (int) ($invoice->total_amount * 10),
        ];

        if ($sandbox) {
            $payload['sandbox'] = '1';
        }

        $response = Http::timeout(15)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post(self::VERIFY_URL, $payload);

        if (! $response->successful()) {
            throw new GatewayException('خطا در تأیید پرداخت بانک تجارت.');
        }

        $data       = $response->json();
        $verStatus  = $data['status']  ?? 0;
        $cardNumber = $data['card_number'] ?? '';

        if ($verStatus != 1) {
            $msg = $data['message'] ?? 'تأیید پرداخت بانک تجارت ناموفق بود.';
            return PaymentResult::fail($msg);
        }

        return PaymentResult::ok($transId, $cardNumber);
    }
}
