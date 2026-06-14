<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * درگاه بانک ملی — ایران‌کیش (IranKish)
 * REST API v3
 */
class MelliGateway implements PaymentGatewayInterface
{
    private const TOKEN_URL   = 'https://ikc.shaparak.ir/api/v3/tokenization/make';
    private const PAY_URL     = 'https://ikc.shaparak.ir/iuiv3/IPG/Index/';
    private const CONFIRM_URL = 'https://ikc.shaparak.ir/api/v3/confirmation/purchase';

    public function __construct(private readonly array $config = []) {}

    public function name(): string { return 'melli'; }

    private function cfg(string $key, mixed $default = ''): mixed
    {
        return $this->config[$key] ?? $default;
    }

    public function request(Invoice $invoice, User $user, string $callbackUrl): string
    {
        $terminalId = $this->cfg('terminal_id');
        $password   = $this->cfg('password');

        if (empty($terminalId) || empty($password)) {
            throw new GatewayException('اطلاعات ترمینال بانک ملی کامل نیست.');
        }

        $payload = [
            'terminalId'  => $terminalId,
            'password'    => $password,
            'invoiceNo'   => (string) $invoice->id,
            'amount'      => (int) ($invoice->total_amount * 10), // تومان → ریال
            'revertURL'   => $callbackUrl,
            'description' => "فاکتور {$invoice->invoice_number}",
            'payerMobile' => $user->mobile ?? '',
        ];

        $response = Http::timeout(15)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post(self::TOKEN_URL, $payload);

        if (! $response->successful()) {
            Log::error('Melli (IranKish) token request failed', ['status' => $response->status(), 'invoice' => $invoice->invoice_number]);
            throw new GatewayException('خطا در اتصال به درگاه بانک ملی.');
        }

        $data   = $response->json();
        $status = $data['status']       ?? false;
        $token  = $data['result']       ?? ($data['token'] ?? null);
        $msg    = $data['description']  ?? '';

        if (! $status || empty($token)) {
            Log::warning('Melli token empty or status false', ['data' => $data, 'invoice' => $invoice->invoice_number]);
            throw new GatewayException("درگاه بانک ملی: {$msg}");
        }

        return self::PAY_URL . $token;
    }

    public function validateCallback(array $params, array $headers, string $rawBody): void
    {
        if (empty($params['token']) && empty($params['responseCode'])) {
            throw new GatewayException('پاسخ درگاه بانک ملی نامعتبر است.');
        }
    }

    public function verify(Invoice $invoice, array $params): PaymentResult
    {
        $responseCode  = (string) ($params['responseCode']  ?? '');
        $token         = $params['token']                   ?? '';

        if ($responseCode !== '00') {
            return PaymentResult::fail($this->translateCode($responseCode));
        }

        $terminalId = $this->cfg('terminal_id');
        $password   = $this->cfg('password');

        $response = Http::timeout(15)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post(self::CONFIRM_URL, [
                'terminalId' => $terminalId,
                'password'   => $password,
                'retrievalRefNo' => $params['retrievalRefNo'] ?? '',
                'systemTraceAuditNo' => $params['systemTraceAuditNo'] ?? '',
                'invoiceNo' => (string) $invoice->id,
            ]);

        if (! $response->successful()) {
            throw new GatewayException('خطا در تأیید پرداخت بانک ملی.');
        }

        $data   = $response->json();
        $status = $data['status'] ?? false;

        if (! $status) {
            $msg = $data['description'] ?? 'تأیید پرداخت ناموفق';
            return PaymentResult::fail($msg);
        }

        $refId = $params['retrievalRefNo'] ?? $token;
        return PaymentResult::ok((string) $refId, $params['maskedCardNumber'] ?? '');
    }

    private function translateCode(string $code): string
    {
        return match ($code) {
            '00'  => 'تراکنش موفق',
            '01'  => 'به صادرکننده کارت مراجعه شود',
            '05'  => 'تراکنش رد شد',
            '12'  => 'تراکنش نامعتبر',
            '13'  => 'مبلغ اشتباه',
            '14'  => 'شماره کارت نامعتبر',
            '51'  => 'موجودی ناکافی',
            '54'  => 'کارت منقضی شده',
            '55'  => 'رمز اشتباه',
            '57'  => 'تراکنش مجاز نیست',
            '58'  => 'تراکنش در این ترمینال مجاز نیست',
            '61'  => 'مبلغ بیش از حد مجاز',
            '62'  => 'کارت محدود شده',
            '75'  => 'تعداد خطاهای رمز بیش از حد',
            '91'  => 'صادرکننده در دسترس نیست',
            '96'  => 'خطای سیستمی',
            default => "خطای درگاه بانک ملی: {$code}",
        };
    }
}
