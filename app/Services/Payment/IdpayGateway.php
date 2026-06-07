<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IdpayGateway implements PaymentGatewayInterface
{
    private string $apiKey;
    private bool   $sandbox;

    public function __construct()
    {
        $this->apiKey  = config('payment.idpay.api_key', '');
        $this->sandbox = (bool) config('payment.sandbox');
    }

    public function name(): string
    {
        return 'idpay';
    }

    public function request(Invoice $invoice, User $user, string $callbackUrl): string
    {
        if (empty($this->apiKey)) {
            throw new GatewayException('IDPAY_API_KEY تنظیم نشده است.');
        }

        $response = Http::timeout(15)
            ->withHeaders($this->headers())
            ->post(config('payment.idpay.request_url'), [
                'order_id'    => $invoice->invoice_number,
                'amount'      => (int) $invoice->total_amount,
                'name'        => $user->name ?? '',
                'phone'       => $user->mobile ?? '',
                'callback'    => $callbackUrl,
                'desc'        => "پرداخت فاکتور {$invoice->invoice_number}",
            ]);

        if (! $response->successful()) {
            Log::error('IDPay request HTTP error', ['status' => $response->status(), 'invoice' => $invoice->invoice_number]);
            throw new GatewayException('خطا در اتصال به درگاه آیدی‌پی.');
        }

        $data = $response->json();

        if (isset($data['error_code'])) {
            throw new GatewayException($data['error_message'] ?? "خطای IDPay: {$data['error_code']}", (int) $data['error_code']);
        }

        $link = $data['link'] ?? '';
        if (empty($link)) {
            throw new GatewayException('درگاه آیدی‌پی لینک پرداخت برنگرداند.');
        }

        return $link;
    }

    public function verify(Invoice $invoice, array $params): PaymentResult
    {
        $status = (int) ($params['status'] ?? 0);
        $id     = $params['id']     ?? '';
        $orderId = $params['order_id'] ?? $invoice->invoice_number;

        // کدهای موفق IDPay: 100=موفق، 101=قبلاً تأیید
        if (! in_array($status, [100, 101], true)) {
            return PaymentResult::fail($this->translateStatus($status));
        }

        $response = Http::timeout(15)
            ->withHeaders($this->headers())
            ->post(config('payment.idpay.verify_url'), [
                'id'       => $id,
                'order_id' => $orderId,
            ]);

        if (! $response->successful()) {
            throw new GatewayException('خطا در تأیید پرداخت آیدی‌پی.');
        }

        $data = $response->json();

        if (isset($data['error_code'])) {
            return PaymentResult::fail($data['error_message'] ?? "خطای IDPay: {$data['error_code']}");
        }

        $verifyStatus = (int) ($data['status'] ?? 0);
        if (in_array($verifyStatus, [100, 101], true)) {
            $refId   = (string) ($data['track_id'] ?? '');
            $cardPan = $data['payment']['card_no'] ?? '';
            return PaymentResult::ok($refId, $cardPan);
        }

        return PaymentResult::fail($this->translateStatus($verifyStatus));
    }

    private function headers(): array
    {
        return array_filter([
            'X-API-KEY'  => $this->apiKey,
            'X-SANDBOX'  => $this->sandbox ? '1' : null,
            'Content-Type' => 'application/json',
        ]);
    }

    private function translateStatus(int $status): string
    {
        return match ($status) {
            1   => 'پرداخت انجام نشده.',
            2   => 'پرداخت ناموفق.',
            3   => 'خطا رخ داده.',
            4   => 'بلوکه شده.',
            5   => 'برگشت به پرداخت‌کننده.',
            6   => 'برگشت خورده سیستمی.',
            10  => 'در انتظار تأیید پرداخت.',
            100 => 'پرداخت تأیید شده.',
            101 => 'پرداخت قبلاً تأیید شده.',
            200 => 'به دریافت‌کننده واریز شد.',
            default => "وضعیت ناشناخته: {$status}",
        };
    }
}
