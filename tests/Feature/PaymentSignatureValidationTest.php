<?php

namespace Tests\Feature;

use App\Services\Payment\GatewayException;
use App\Services\Payment\IdpayGateway;
use App\Services\Payment\ZarinpalGateway;
use Tests\TestCase;

/**
 * Webhook Signature Validation — unit tests for validateCallback().
 *
 * Covers:
 *   1. Zarinpal: missing Authority → GatewayException
 *   2. Zarinpal: Authority with invalid format → GatewayException
 *   3. Zarinpal: valid Authority → passes
 *   4. IDPay production: missing X-IDPAY-SIGNATURE → GatewayException
 *   5. IDPay production: wrong signature → GatewayException
 *   6. IDPay production: correct HMAC-SHA256 → passes
 *   7. IDPay sandbox: missing signature is allowed → passes
 */
class PaymentSignatureValidationTest extends TestCase
{
    // ── Helpers ──────────────────────────────────────────────────────────────

    private function zarinpal(): ZarinpalGateway
    {
        config(['payment.sandbox' => false, 'payment.zarinpal.merchant_id' => 'test-mid']);
        return new ZarinpalGateway();
    }

    private function idpay(bool $sandbox = false): IdpayGateway
    {
        config([
            'payment.sandbox'        => $sandbox,
            'payment.idpay.api_key'  => 'test-api-key',
            'payment.idpay.request_url' => 'https://api.idpay.ir/v1.1/payment',
            'payment.idpay.verify_url'  => 'https://api.idpay.ir/v1.1/payment/verify',
        ]);
        return new IdpayGateway();
    }

    private function sign(string $body, string $key = 'test-api-key'): string
    {
        return hash_hmac('sha256', $body, $key);
    }

    // ── Zarinpal ─────────────────────────────────────────────────────────────

    public function test_zarinpal_missing_authority_throws(): void
    {
        $this->expectException(GatewayException::class);
        $this->expectExceptionMessageMatches('/Authority/i');

        $this->zarinpal()->validateCallback([], [], '');
    }

    public function test_zarinpal_invalid_authority_format_throws(): void
    {
        $this->expectException(GatewayException::class);
        $this->expectExceptionMessageMatches('/فرمت Authority/');

        // Does not start with 'A'
        $this->zarinpal()->validateCallback(['Authority' => '1234567890INVALID'], [], '');
    }

    public function test_zarinpal_valid_authority_passes(): void
    {
        // Real Zarinpal Authority format: A + 35 alphanumeric chars
        $authority = 'A' . str_repeat('0', 35);

        $this->zarinpal()->validateCallback(['Authority' => $authority, 'Status' => 'OK'], [], '');

        $this->assertTrue(true); // no exception = pass
    }

    // ── IDPay ─────────────────────────────────────────────────────────────────

    public function test_idpay_missing_signature_header_throws_in_production(): void
    {
        $this->expectException(GatewayException::class);
        $this->expectExceptionMessageMatches('/X-IDPAY-SIGNATURE/i');

        $body = json_encode(['id' => 'txn-123', 'order_id' => 'SUB-001', 'status' => 100]);
        $this->idpay(sandbox: false)->validateCallback([], [], $body);
    }

    public function test_idpay_wrong_signature_throws(): void
    {
        $this->expectException(GatewayException::class);
        $this->expectExceptionMessageMatches('/امضا.*نامعتبر/');

        $body    = json_encode(['id' => 'txn-123', 'order_id' => 'SUB-001', 'status' => 100]);
        $headers = ['x-idpay-signature' => 'deadbeef00000000000000000000000000000000000000000000000000000000'];

        $this->idpay(sandbox: false)->validateCallback([], $headers, $body);
    }

    public function test_idpay_correct_hmac_passes(): void
    {
        $body      = json_encode(['id' => 'txn-123', 'order_id' => 'SUB-001', 'status' => 100]);
        $signature = $this->sign($body);
        $headers   = ['x-idpay-signature' => $signature];

        $this->idpay(sandbox: false)->validateCallback([], $headers, $body);

        $this->assertTrue(true); // no exception = pass
    }

    public function test_idpay_sandbox_skips_signature_check(): void
    {
        // In sandbox mode, missing or wrong signature must be accepted
        $body = json_encode(['id' => 'txn-sandbox', 'status' => 100]);

        $this->idpay(sandbox: true)->validateCallback([], [], $body);

        $this->assertTrue(true); // no exception = pass
    }
}
