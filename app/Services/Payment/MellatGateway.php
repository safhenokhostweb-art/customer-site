<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use SoapClient;
use SoapFault;

/**
 * درگاه بانک ملت — به‌پرداخت ملت (SOAP)
 * WSDL: https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl
 */
class MellatGateway implements PaymentGatewayInterface
{
    private const WSDL    = 'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl';
    private const PAY_URL = 'https://bpm.shaparak.ir/pgwchannel/startpayment.fcp';

    public function __construct(private readonly array $config = []) {}

    public function name(): string { return 'mellat'; }

    private function cfg(string $key, mixed $default = ''): mixed
    {
        return $this->config[$key] ?? $default;
    }

    public function request(Invoice $invoice, User $user, string $callbackUrl): string
    {
        $terminalId = $this->cfg('terminal_id');
        $username   = $this->cfg('username');
        $password   = $this->cfg('password');

        if (empty($terminalId) || empty($username) || empty($password)) {
            throw new GatewayException('اطلاعات ترمینال بانک ملت کامل نیست.');
        }

        $params = [
            'terminalId'     => (int) $terminalId,
            'userName'       => $username,
            'userPassword'   => $password,
            'orderId'        => $invoice->id,
            'amount'         => (int) ($invoice->total_amount * 10), // تبدیل تومان به ریال
            'localDate'      => now()->format('Ymd'),
            'localTime'      => now()->format('His'),
            'additionalData' => "فاکتور {$invoice->invoice_number}",
            'callBackUrl'    => $callbackUrl,
            'payerId'        => 0,
        ];

        try {
            $soap   = new SoapClient(self::WSDL, ['exceptions' => true, 'connection_timeout' => 15]);
            $result = $soap->bpPayRequest($params);
        } catch (SoapFault $e) {
            Log::error('Mellat SOAP request failed', ['error' => $e->getMessage(), 'invoice' => $invoice->invoice_number]);
            throw new GatewayException('خطا در اتصال به درگاه بانک ملت: ' . $e->getMessage());
        }

        $res = explode(',', $result->return ?? '');
        $code  = $res[0] ?? '-1';
        $refId = $res[1] ?? '';

        if ($code !== '0') {
            Log::warning('Mellat payment request failed', ['code' => $code, 'invoice' => $invoice->invoice_number]);
            throw new GatewayException($this->translateCode((int) $code), (int) $code);
        }

        return self::PAY_URL . '?RefId=' . $refId;
    }

    public function validateCallback(array $params, array $headers, string $rawBody): void
    {
        $resCode = $params['ResCode'] ?? $params['res_code'] ?? null;
        if ($resCode === null) {
            throw new GatewayException('پاسخ درگاه بانک ملت نامعتبر است.');
        }
    }

    public function verify(Invoice $invoice, array $params): PaymentResult
    {
        $resCode  = $params['ResCode'] ?? '';
        $refId    = $params['RefId']   ?? '';
        $saleOrderId    = $params['SaleOrderId'] ?? '';
        $saleReferenceId = $params['SaleReferenceId'] ?? '';

        if ($resCode !== '0') {
            return PaymentResult::fail($this->translateCode((int) $resCode));
        }

        $terminalId = $this->cfg('terminal_id');
        $username   = $this->cfg('username');
        $password   = $this->cfg('password');

        try {
            $soap = new SoapClient(self::WSDL, ['exceptions' => true, 'connection_timeout' => 15]);

            // تأیید پرداخت
            $verifyResult = $soap->bpVerifyRequest([
                'terminalId'      => (int) $terminalId,
                'userName'        => $username,
                'userPassword'    => $password,
                'orderId'         => $invoice->id,
                'saleOrderId'     => (int) $saleOrderId,
                'saleReferenceId' => (int) $saleReferenceId,
            ]);

            if (($verifyResult->return ?? -1) !== 0) {
                return PaymentResult::fail('تأیید پرداخت بانک ملت ناموفق بود.');
            }

            // تسویه
            $soap->bpSettleRequest([
                'terminalId'      => (int) $terminalId,
                'userName'        => $username,
                'userPassword'    => $password,
                'orderId'         => $invoice->id,
                'saleOrderId'     => (int) $saleOrderId,
                'saleReferenceId' => (int) $saleReferenceId,
            ]);
        } catch (SoapFault $e) {
            Log::error('Mellat verify SOAP failed', ['error' => $e->getMessage()]);
            throw new GatewayException('خطا در تأیید پرداخت بانک ملت: ' . $e->getMessage());
        }

        return PaymentResult::ok((string) $saleReferenceId);
    }

    private function translateCode(int $code): string
    {
        return match ($code) {
            0   => 'عملیات موفق',
            11  => 'شماره کارت نامعتبر است',
            12  => 'موجودی حساب ناکافی است',
            13  => 'رمز نادرست است',
            14  => 'تعداد دفعات وارد کردن رمز بیش از حد مجاز است',
            15  => 'کارت نامعتبر است',
            16  => 'دفعات برداشت وجه بیش از حد مجاز است',
            17  => 'کاربر از انجام تراکنش منصرف شده است',
            18  => 'تاریخ انقضای کارت گذشته است',
            19  => 'مبلغ برداشت وجه بیش از حد مجاز است',
            111 => 'صادرکننده کارت نامعتبر است',
            112 => 'خطای سوئیچ صادرکننده کارت',
            113 => 'پاسخی از صادرکننده کارت دریافت نشد',
            114 => 'دارنده کارت مجاز به انجام این تراکنش نیست',
            21  => 'پذیرنده نامعتبر است',
            23  => 'خطای امنیتی رخ داده است',
            24  => 'اطلاعات کاربری پذیرنده نامعتبر است',
            25  => 'مبلغ نامعتبر است',
            31  => 'پاسخ نامعتبر است',
            32  => 'فرمت اطلاعات وارد شده صحیح نمی‌باشد',
            33  => 'حساب نامعتبر است',
            34  => 'خطای سیستمی',
            35  => 'تاریخ نامعتبر است',
            41  => 'شماره درخواست تکراری است',
            42  => 'تراکنش Sale یافت نشد',
            43  => 'قبلاً درخواست Verify داده شده است',
            44  => 'درخواست Verify یافت نشد',
            45  => 'تراکنش Settle شده است',
            46  => 'تراکنش Settle نشده است',
            47  => 'تراکنش Settle یافت نشد',
            48  => 'تراکنش Reverse شده است',
            49  => 'تراکنش Refund یافت نشد',
            412 => 'شناسه قبض نادرست است',
            413 => 'شناسه پرداخت نادرست است',
            414 => 'سازمان قبض نامعتبر است',
            415 => 'زمان جلسه منقضی شده است',
            416 => 'خطا در ثبت اطلاعات',
            417 => 'شناسه پرداخت‌کننده نامعتبر است',
            418 => 'اشکال در تعریف اطلاعات مشتری',
            419 => 'تعداد دفعات ورود اطلاعات از حد مجاز گذشته است',
            421 => 'IP نامعتبر است',
            51  => 'تراکنش تکراری است',
            54  => 'تراکنش مرجع موجود نیست',
            55  => 'تراکنش نامعتبر است',
            61  => 'خطا در واریز',
            default => "خطای ناشناخته بانک ملت: {$code}",
        };
    }
}
