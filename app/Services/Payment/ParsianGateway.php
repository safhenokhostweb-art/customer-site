<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use SoapClient;
use SoapFault;

/**
 * درگاه بانک پارسیان — PEC (پک)
 * SOAP — ServiceReference
 * WSDL: https://pec.shaparak.ir/NewIPGServices/Sale/ServiceReference.asmx?wsdl
 */
class ParsianGateway implements PaymentGatewayInterface
{
    private const WSDL    = 'https://pec.shaparak.ir/NewIPGServices/Sale/ServiceReference.asmx?wsdl';
    private const PAY_URL = 'https://pec.shaparak.ir/NewIPGServices/CA/GW/Payment';

    public function __construct(private readonly array $config = []) {}

    public function name(): string { return 'parsian'; }

    private function cfg(string $key, mixed $default = ''): mixed
    {
        return $this->config[$key] ?? $default;
    }

    public function request(Invoice $invoice, User $user, string $callbackUrl): string
    {
        $pin = $this->cfg('pin');

        if (empty($pin)) {
            throw new GatewayException('PIN ترمینال بانک پارسیان تنظیم نشده است.');
        }

        $amount = (int) ($invoice->total_amount * 10); // تومان → ریال

        try {
            $soap   = new SoapClient(self::WSDL, ['exceptions' => true, 'connection_timeout' => 15]);
            $result = $soap->SalePaymentRequest([
                'LoginAccount'   => $pin,
                'OrderId'        => $invoice->id,
                'Amount'         => $amount,
                'CallBackUrl'    => $callbackUrl,
                'AdditionalData' => "فاکتور {$invoice->invoice_number}",
            ]);
        } catch (SoapFault $e) {
            Log::error('Parsian SOAP SalePaymentRequest failed', ['error' => $e->getMessage(), 'invoice' => $invoice->invoice_number]);
            throw new GatewayException('خطا در اتصال به درگاه بانک پارسیان: ' . $e->getMessage());
        }

        $status = $result->SalePaymentRequestResult->Status  ?? -1;
        $token  = $result->SalePaymentRequestResult->Token   ?? 0;

        if ($status !== 0 || empty($token)) {
            Log::warning('Parsian SalePaymentRequest failed', ['status' => $status, 'invoice' => $invoice->invoice_number]);
            throw new GatewayException($this->translateStatus((int) $status), (int) $status);
        }

        return self::PAY_URL . '?Token=' . $token;
    }

    public function validateCallback(array $params, array $headers, string $rawBody): void
    {
        $token = $params['Token'] ?? $params['token'] ?? '';
        if (empty($token)) {
            throw new GatewayException('پاسخ درگاه بانک پارسیان فاقد Token است.');
        }
    }

    public function verify(Invoice $invoice, array $params): PaymentResult
    {
        $status = (int) ($params['status'] ?? $params['Status'] ?? -1);
        $token  = $params['Token'] ?? $params['token'] ?? '';

        if ($status !== 0 || empty($token)) {
            return PaymentResult::fail($this->translateStatus($status));
        }

        $pin = $this->cfg('pin');

        try {
            $soap   = new SoapClient(self::WSDL, ['exceptions' => true, 'connection_timeout' => 15]);
            $result = $soap->ConfirmPayment([
                'LoginAccount' => $pin,
                'Token'        => (int) $token,
            ]);
        } catch (SoapFault $e) {
            Log::error('Parsian SOAP ConfirmPayment failed', ['error' => $e->getMessage()]);
            throw new GatewayException('خطا در تأیید پرداخت بانک پارسیان: ' . $e->getMessage());
        }

        $confirmStatus = $result->ConfirmPaymentResult->Status ?? -1;
        $rrn           = $result->ConfirmPaymentResult->RRN    ?? '';
        $cardNumber    = $result->ConfirmPaymentResult->CardNumberMasked ?? '';

        if ((int) $confirmStatus !== 0) {
            return PaymentResult::fail($this->translateStatus((int) $confirmStatus));
        }

        return PaymentResult::ok((string) $rrn, (string) $cardNumber);
    }

    private function translateStatus(int $code): string
    {
        return match ($code) {
            0    => 'عملیات موفق',
            -1   => 'دریافت نشدن صحیح اطلاعات از درگاه',
            -2   => 'Login Account نامعتبر است',
            -3   => 'Token نامعتبر است',
            -4   => 'Merchant نامعتبر است',
            -6   => 'پارامترهای ارسالی اشتباه است',
            -7   => 'IP نامعتبر است',
            -8   => 'URL نامعتبر است',
            -9   => 'مبلغ نامعتبر است',
            -10  => 'خطا در پردازش',
            -11  => 'کاربر از پرداخت منصرف شد',
            -12  => 'Token منقضی شده است',
            -1551 => 'تراکنش قبلاً تأیید شده است',
            -1552 => 'شناسه سفارش تکراری است',
            default => "خطای درگاه بانک پارسیان: {$code}",
        };
    }
}
