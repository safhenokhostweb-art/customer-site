<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZarinpalGateway implements PaymentGatewayInterface
{
    private bool   $sandbox;
    private string $merchantId;

    public function __construct()
    {
        $this->sandbox    = (bool) config('payment.sandbox');
        $this->merchantId = config('payment.zarinpal.merchant_id', '');
    }

    public function name(): string
    {
        return 'zarinpal';
    }

    public function request(Invoice $invoice, User $user, string $callbackUrl): string
    {
        if (empty($this->merchantId)) {
            throw new GatewayException('ZARINPAL_MERCHANT_ID تنظیم نشده است.');
        }

        $url = $this->sandbox
            ? config('payment.zarinpal.sandbox_url')
            : config('payment.zarinpal.request_url');

        $amount = $this->toToman((int) $invoice->total_amount);

        $payload = [
            'merchant_id'  => $this->merchantId,
            'amount'       => $amount,
            'description'  => "پرداخت فاکتور {$invoice->invoice_number}",
            'callback_url' => $callbackUrl,
            'currency'     => config('payment.zarinpal.currency', 'IRT'),
            'metadata'     => [
                'mobile' => $user->mobile ?? '',
                'order_id' => $invoice->invoice_number,
            ],
        ];

        $response = Http::timeout(15)->post($url, $payload);

        if (! $response->successful()) {
            Log::error('Zarinpal request HTTP error', ['status' => $response->status(), 'invoice' => $invoice->invoice_number]);
            throw new GatewayException('خطا در اتصال به درگاه زرین‌پال. لطفاً دوباره تلاش کنید.');
        }

        $data = $response->json();
        $code = $data['data']['code'] ?? ($data['errors']['code'] ?? -1);

        if ($code !== 100) {
            Log::warning('Zarinpal request failed', ['code' => $code, 'invoice' => $invoice->invoice_number]);
            throw new GatewayException($this->translateCode($code), (int) $code);
        }

        $authority = $data['data']['authority'] ?? '';
        if (empty($authority)) {
            throw new GatewayException('درگاه زرین‌پال authority برنگرداند.');
        }

        $startUrl = $this->sandbox
            ? config('payment.zarinpal.sandbox_start')
            : config('payment.zarinpal.start_url');

        return $startUrl . $authority;
    }

    /**
     * اعتبارسنجی فرمت Authority دریافت‌شده در callback.
     * زرین‌پال HMAC نمی‌فرستد — محافظت اصلی از طریق verify API است.
     * اینجا فقط جلوی تزریق Authority دستکاری‌شده را می‌گیریم.
     */
    public function validateCallback(array $params, array $headers, string $rawBody): void
    {
        $authority = $params['Authority'] ?? '';

        if (empty($authority)) {
            throw new GatewayException('پارامتر Authority در callback زرین‌پال وجود ندارد.');
        }

        // Authority v4: شروع با A، فقط حروف و اعداد، حداقل ۲۰ کاراکتر
        if (! preg_match('/^A[a-zA-Z0-9]{19,}$/', $authority)) {
            throw new GatewayException('فرمت Authority زرین‌پال نامعتبر است.');
        }
    }

    public function verify(Invoice $invoice, array $params): PaymentResult
    {
        $status    = $params['Status'] ?? '';
        $authority = $params['Authority'] ?? '';

        if ($status !== 'OK') {
            return PaymentResult::fail('پرداخت توسط کاربر لغو شد یا ناموفق بود.');
        }

        if (empty($authority)) {
            return PaymentResult::fail('Authority دریافت نشد.');
        }

        $url = $this->sandbox
            ? config('payment.zarinpal.sandbox_verify')
            : config('payment.zarinpal.verify_url');

        $amount = $this->toToman((int) $invoice->total_amount);

        $response = Http::timeout(15)->post($url, [
            'merchant_id' => $this->merchantId,
            'amount'      => $amount,
            'authority'   => $authority,
        ]);

        if (! $response->successful()) {
            throw new GatewayException('خطا در تأیید پرداخت زرین‌پال.');
        }

        $data = $response->json();
        $code = $data['data']['code'] ?? -1;

        if ($code === 100 || $code === 101) {
            $refId  = (string) ($data['data']['ref_id'] ?? '');
            $cardPan = $data['data']['card_pan'] ?? '';
            return PaymentResult::ok($refId, $cardPan, $code === 101 ? 'تراکنش قبلاً تأیید شده بود.' : '');
        }

        return PaymentResult::fail($this->translateCode($code), '');
    }

    private function toToman(int $rial): int
    {
        // اگر مبلغ به تومان وارد شده باشد (config IRT) همان مقدار را برمی‌گردانیم
        return $rial;
    }

    private function translateCode(int $code): string
    {
        return match ($code) {
            -9  => 'اعتبارسنجی اطلاعات ناموفق.',
            -10 => 'IP یا Merchant Code معتبر نیست.',
            -11 => 'Merchant Code فعال نیست.',
            -12 => 'تلاش بیش از حد مجاز.',
            -15 => 'Terminal معلق است.',
            -16 => 'سطح تأیید Merchant پایین است.',
            36  => 'Amount کمتر از حد مجاز است.',
            40  => 'متد پرداخت قابل قبول نیست.',
            51  => 'موجودی ناکافی.',
            54  => 'درخواست منقضی شده.',
            100 => 'عملیات موفق.',
            101 => 'تراکنش قبلاً تأیید شده.',
            default => "خطای درگاه: کد {$code}",
        };
    }
}
