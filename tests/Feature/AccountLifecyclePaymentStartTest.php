<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AccountLifecyclePaymentStartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
        config([
            'payment.gateway' => 'zarinpal',
            'payment.sandbox' => false,
            'payment.zarinpal.merchant_id' => 'TESTMID',
            'payment.zarinpal.request_url' => 'https://api.zarinpal.test/request',
            'payment.zarinpal.start_url' => 'https://bank.test/start/',
        ]);
        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('installed.lock'));
        parent::tearDown();
    }

    public function test_repeated_paid_restore_start_reuses_the_first_gateway_link(): void
    {
        Http::fake([
            'api.zarinpal.test/*' => Http::response([
                'data' => ['code' => 100, 'authority' => 'A00000000000000000000000000123456789'],
                'errors' => [],
            ]),
        ]);

        $user = User::factory()->create();
        $invoice = Invoice::create([
            'invoice_number' => 'RESTORE-20260828-0001',
            'user_id' => $user->id,
            'customer_name' => $user->name ?? $user->mobile,
            'customer_mobile' => $user->mobile,
            'subtotal' => 50000,
            'discount' => 0,
            'total' => 50000,
            'status' => 'pending',
        ]);

        $session = ['lifecycle_restore_local_invoice' => $invoice->invoice_number];
        $url = route('account.lifecycle.start-payment', $invoice->invoice_number);

        $this->actingAs($user)->withSession($session)->post($url)
            ->assertRedirect('https://bank.test/start/A00000000000000000000000000123456789');
        $this->actingAs($user)->withSession($session)->post($url)
            ->assertRedirect('https://bank.test/start/A00000000000000000000000000123456789');

        Http::assertSentCount(1);
        $this->assertSame(
            'https://bank.test/start/A00000000000000000000000000123456789',
            $invoice->fresh()->payment_link,
        );
    }
}
