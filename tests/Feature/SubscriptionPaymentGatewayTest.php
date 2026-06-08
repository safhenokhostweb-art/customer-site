<?php

namespace Tests\Feature;

use App\Helpers\Module;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Payment\GatewayException;
use App\Services\Payment\PaymentGatewayFactory;
use App\Services\Payment\PaymentResult;
use App\Services\Payment\ZarinpalGateway;
use App\Services\Payment\IdpayGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use ReflectionClass;
use Tests\TestCase;

/**
 * Payment Gateway Layer — P1 tests.
 *
 * تمام HTTP calls به درگاه‌های خارجی mock می‌شوند — هیچ call واقعی ارسال نمی‌شود.
 *
 * Covers:
 *   - PaymentGatewayFactory::isConfigured — false when env empty
 *   - PaymentGatewayFactory::isConfigured — true when merchant_id set
 *   - PaymentGatewayFactory::make — throws for unknown gateway
 *   - ZarinpalGateway::request — returns redirect URL on success (mocked)
 *   - ZarinpalGateway::request — throws GatewayException on API error
 *   - ZarinpalGateway::verify — PaymentResult::ok on code 100
 *   - ZarinpalGateway::verify — PaymentResult::fail when Status != OK
 *   - ZarinpalGateway::verify — PaymentResult::fail on error code
 *   - IdpayGateway::request — returns link on success (mocked)
 *   - IdpayGateway::verify — PaymentResult::ok on status 100
 *   - IdpayGateway::verify — PaymentResult::fail on status 2
 *   - pay() endpoint — redirects to gateway when configured
 *   - pay() endpoint — shows manual page when gateway not configured
 *   - callback() endpoint — activates subscription on success
 *   - callback() endpoint — redirects with error on payment failure
 *   - confirm() endpoint — works when gateway not configured
 *   - confirm() endpoint — blocked when gateway configured
 */
class SubscriptionPaymentGatewayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
        config(['site.owner_site' => true]);
        // reset Module cache so env reads fresh
        $ref = new ReflectionClass(Module::class);
        $prop = $ref->getProperty('resolved');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('installed.lock'));
        parent::tearDown();
    }

    // ─── Factory ──────────────────────────────────────────────────────────

    public function test_factory_not_configured_when_gateway_empty(): void
    {
        config(['payment.gateway' => '']);
        $this->assertFalse(PaymentGatewayFactory::isConfigured());
    }

    public function test_factory_configured_when_zarinpal_merchant_set(): void
    {
        config(['payment.gateway' => 'zarinpal', 'payment.zarinpal.merchant_id' => 'test-mid']);
        $this->assertTrue(PaymentGatewayFactory::isConfigured());
    }

    public function test_factory_configured_when_idpay_key_set(): void
    {
        config(['payment.gateway' => 'idpay', 'payment.idpay.api_key' => 'test-key']);
        $this->assertTrue(PaymentGatewayFactory::isConfigured());
    }

    public function test_factory_throws_for_unknown_gateway(): void
    {
        config(['payment.gateway' => 'bogus']);
        $this->expectException(GatewayException::class);
        PaymentGatewayFactory::make();
    }

    // ─── ZarinpalGateway ──────────────────────────────────────────────────

    public function test_zarinpal_request_returns_redirect_url(): void
    {
        config([
            'payment.gateway'                => 'zarinpal',
            'payment.sandbox'                => false,
            'payment.zarinpal.merchant_id'   => 'TESTMID',
            'payment.zarinpal.request_url'   => 'https://api.zarinpal.com/pg/v4/payment/request.json',
            'payment.zarinpal.start_url'     => 'https://www.zarinpal.com/pg/StartPay/',
        ]);

        Http::fake([
            'api.zarinpal.com/*' => Http::response([
                'data' => ['code' => 100, 'authority' => 'A00000000000000000000000000999999999'],
                'errors' => [],
            ], 200),
        ]);

        $invoice = $this->makeInvoice(50000);
        $user    = User::factory()->create();

        $gateway = new ZarinpalGateway();
        $url     = $gateway->request($invoice, $user, 'https://site.test/callback');

        $this->assertStringContainsString('zarinpal.com/pg/StartPay/', $url);
        $this->assertStringContainsString('A000000000', $url);
    }

    public function test_zarinpal_request_throws_on_api_error(): void
    {
        config([
            'payment.gateway'              => 'zarinpal',
            'payment.sandbox'              => false,
            'payment.zarinpal.merchant_id' => 'TESTMID',
            'payment.zarinpal.request_url' => 'https://api.zarinpal.com/pg/v4/payment/request.json',
        ]);

        Http::fake([
            'api.zarinpal.com/*' => Http::response([
                'data'   => [],
                'errors' => ['code' => -11, 'message' => 'Merchant inactive'],
            ], 200),
        ]);

        $invoice = $this->makeInvoice(50000);
        $user    = User::factory()->create();

        $this->expectException(GatewayException::class);
        (new ZarinpalGateway())->request($invoice, $user, 'https://site.test/callback');
    }

    public function test_zarinpal_verify_returns_ok_on_code_100(): void
    {
        config([
            'payment.sandbox'              => false,
            'payment.zarinpal.merchant_id' => 'TESTMID',
            'payment.zarinpal.verify_url'  => 'https://api.zarinpal.com/pg/v4/payment/verify.json',
        ]);

        Http::fake([
            'api.zarinpal.com/*' => Http::response([
                'data' => ['code' => 100, 'ref_id' => '12345678', 'card_pan' => '603799******1234'],
                'errors' => [],
            ], 200),
        ]);

        $invoice = $this->makeInvoice(50000);
        $result  = (new ZarinpalGateway())->verify($invoice, [
            'Status'    => 'OK',
            'Authority' => 'A00000000000000000000000000999999999',
        ]);

        $this->assertTrue($result->success);
        $this->assertEquals('12345678', $result->refId);
    }

    public function test_zarinpal_verify_fails_when_status_not_ok(): void
    {
        config(['payment.sandbox' => false, 'payment.zarinpal.merchant_id' => 'TESTMID']);
        Http::fake(); // نباید call بزند

        $invoice = $this->makeInvoice(50000);
        $result  = (new ZarinpalGateway())->verify($invoice, ['Status' => 'NOK', 'Authority' => 'x']);

        $this->assertFalse($result->success);
    }

    public function test_zarinpal_verify_fails_on_error_code(): void
    {
        config([
            'payment.sandbox'              => false,
            'payment.zarinpal.merchant_id' => 'TESTMID',
            'payment.zarinpal.verify_url'  => 'https://api.zarinpal.com/pg/v4/payment/verify.json',
        ]);

        Http::fake([
            'api.zarinpal.com/*' => Http::response([
                'data' => ['code' => 51], 'errors' => [],
            ], 200),
        ]);

        $invoice = $this->makeInvoice(50000);
        $result  = (new ZarinpalGateway())->verify($invoice, ['Status' => 'OK', 'Authority' => 'AXXX']);

        $this->assertFalse($result->success);
    }

    // ─── IdpayGateway ──────────────────────────────────────────────────────

    public function test_idpay_request_returns_link(): void
    {
        config([
            'payment.gateway'            => 'idpay',
            'payment.sandbox'            => false,
            'payment.idpay.api_key'      => 'test-key',
            'payment.idpay.request_url'  => 'https://api.idpay.ir/v1.1/payment',
        ]);

        Http::fake([
            'api.idpay.ir/*' => Http::response([
                'id'   => 'txn123',
                'link' => 'https://idpay.ir/p/ws/txn123',
            ], 201),
        ]);

        $invoice = $this->makeInvoice(50000);
        $user    = User::factory()->create();
        $link    = (new IdpayGateway())->request($invoice, $user, 'https://site.test/callback');

        $this->assertStringContainsString('idpay.ir', $link);
    }

    public function test_idpay_verify_returns_ok_on_status_100(): void
    {
        config([
            'payment.sandbox'           => false,
            'payment.idpay.api_key'     => 'test-key',
            'payment.idpay.verify_url'  => 'https://api.idpay.ir/v1.1/payment/verify',
        ]);

        Http::fake([
            'api.idpay.ir/*' => Http::response([
                'status'   => 100,
                'track_id' => '99887766',
                'payment'  => ['card_no' => '6037-99**-**11-1234'],
            ], 200),
        ]);

        $invoice = $this->makeInvoice(50000);
        $result  = (new IdpayGateway())->verify($invoice, ['status' => 100, 'id' => 'txn123', 'order_id' => $invoice->invoice_number]);

        $this->assertTrue($result->success);
        $this->assertEquals('99887766', $result->refId);
    }

    public function test_idpay_verify_fails_on_status_2(): void
    {
        config(['payment.sandbox' => false, 'payment.idpay.api_key' => 'test-key']);
        Http::fake();

        $invoice = $this->makeInvoice(50000);
        $result  = (new IdpayGateway())->verify($invoice, ['status' => 2, 'id' => 'x', 'order_id' => $invoice->invoice_number]);

        $this->assertFalse($result->success);
    }

    // ─── HTTP Endpoints ───────────────────────────────────────────────────

    public function test_pay_redirects_to_gateway_when_configured(): void
    {
        config([
            'payment.gateway'              => 'zarinpal',
            'payment.sandbox'              => false,
            'payment.zarinpal.merchant_id' => 'TESTMID',
            'payment.zarinpal.request_url' => 'https://api.zarinpal.com/pg/v4/payment/request.json',
            'payment.zarinpal.start_url'   => 'https://www.zarinpal.com/pg/StartPay/',
        ]);

        Http::fake([
            'api.zarinpal.com/*' => Http::response([
                'data' => ['code' => 100, 'authority' => 'A00000000000000000000000000TEST123'],
                'errors' => [],
            ], 200),
        ]);

        [$user, $invoice] = $this->makeUserAndInvoice();

        $response = $this->actingAs($user)->get(route('subscriptions.pay', $invoice->invoice_number));

        $response->assertRedirect();
        $this->assertStringContainsString('zarinpal.com', $response->headers->get('Location'));
    }

    public function test_pay_shows_manual_page_when_gateway_not_configured(): void
    {
        config(['payment.gateway' => '']);

        [$user, $invoice] = $this->makeUserAndInvoice();

        $response = $this->actingAs($user)->get(route('subscriptions.pay', $invoice->invoice_number));

        $response->assertStatus(200);
        $response->assertSee('تأیید و ثبت درخواست پرداخت');
    }

    public function test_callback_activates_subscription_on_success(): void
    {
        config([
            'payment.gateway'              => 'zarinpal',
            'payment.sandbox'              => false,
            'payment.zarinpal.merchant_id' => 'TESTMID',
            'payment.zarinpal.verify_url'  => 'https://api.zarinpal.com/pg/v4/payment/verify.json',
        ]);

        Http::fake([
            'api.zarinpal.com/*' => Http::response([
                'data' => ['code' => 100, 'ref_id' => '11223344', 'card_pan' => ''],
                'errors' => [],
            ], 200),
        ]);

        [$user, $invoice, $plan] = $this->makeUserAndInvoice(true);

        $callbackUrl = route('subscriptions.callback', $invoice->invoice_number)
            . '?Status=OK&Authority=A' . str_repeat('0', 35); // valid Zarinpal Authority format
        $response = $this->actingAs($user)->get($callbackUrl);

        $response->assertRedirect(route('account.subscriptions.index'));
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'paid']);
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'status'  => Subscription::STATUS_ACTIVE,
        ]);
    }

    public function test_callback_redirects_with_error_on_payment_failure(): void
    {
        config([
            'payment.gateway'              => 'zarinpal',
            'payment.sandbox'              => false,
            'payment.zarinpal.merchant_id' => 'TESTMID',
        ]);

        [$user, $invoice] = $this->makeUserAndInvoice();

        $callbackUrl = route('subscriptions.callback', $invoice->invoice_number)
            . '?Status=NOK&Authority=A' . str_repeat('0', 35); // valid format, but Status=NOK → verify returns fail
        $response = $this->actingAs($user)->get($callbackUrl);

        $response->assertRedirect(route('account.subscriptions.index'));
        $response->assertSessionHas('error');
    }

    public function test_confirm_works_when_gateway_not_configured(): void
    {
        config(['payment.gateway' => '']);

        [$user, $invoice, $plan] = $this->makeUserAndInvoice(true);

        $response = $this->actingAs($user)->post(route('subscriptions.confirm', $invoice->invoice_number));

        $response->assertRedirect(route('account.subscriptions.index'));
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'paid']);
    }

    public function test_confirm_blocked_when_gateway_configured(): void
    {
        config(['payment.gateway' => 'zarinpal', 'payment.zarinpal.merchant_id' => 'TESTMID']);

        [$user, $invoice] = $this->makeUserAndInvoice();

        $response = $this->actingAs($user)->post(route('subscriptions.confirm', $invoice->invoice_number));

        // ریدایرکت به pay — نه فعال‌سازی
        $response->assertRedirect(route('subscriptions.pay', $invoice->invoice_number));
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'pending']);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────

    private function makeInvoice(int $total = 50000): Invoice
    {
        $user = User::factory()->create();
        return Invoice::create([
            'invoice_number'  => 'INV-TEST-' . uniqid(),
            'user_id'         => $user->id,
            'customer_name'   => $user->name,
            'customer_mobile' => $user->mobile,
            'subtotal'        => $total,
            'total'           => $total,
            'status'          => 'pending',
        ]);
    }

    private function makeUserAndInvoice(bool $withPendingSub = false): array
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::create([
            'name'           => 'پلن تست',
            'slug'           => 'test-' . uniqid(),
            'price'          => 50000,
            'duration'       => 'month',
            'duration_count' => 1,
            'is_active'      => true,
            'order'          => 0,
            'trial_days'     => 0,
        ]);

        $invoice = Invoice::create([
            'invoice_number'  => 'INV-' . uniqid(),
            'user_id'         => $user->id,
            'customer_name'   => $user->name,
            'customer_mobile' => $user->mobile,
            'subtotal'        => 50000,
            'total'           => 50000,
            'status'          => 'pending',
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'title'      => 'اشتراک ' . $plan->name,
            'price'      => 50000,
            'quantity'   => 1,
            'total'      => 50000,
        ]);

        if ($withPendingSub) {
            Subscription::create([
                'user_id'              => $user->id,
                'subscription_plan_id' => $plan->id,
                'invoice_id'           => $invoice->id,
                'status'               => Subscription::STATUS_PENDING,
                'starts_at'            => now(),
                'ends_at'              => now()->addMonth(),
            ]);
        } else {
            // session fallback
            session(['pending_subscription_plan' => $plan->id]);
        }

        return [$user, $invoice, $plan];
    }
}
