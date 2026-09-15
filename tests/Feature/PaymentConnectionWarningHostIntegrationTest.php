<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PaymentConnectionWarningHostIntegrationTest extends TestCase
{
    public function test_customer_host_loads_shared_preflight_and_all_seven_gateway_providers_remain_available(): void
    {
        $this->assertTrue(Route::has('payment-connection.preflight'));
        $this->assertTrue(Route::has('account.lifecycle.start-payment'));
        $this->assertSame(
            ['zarinpal', 'idpay', 'mellat', 'melli', 'tejarat', 'pasargad', 'parsian'],
            array_keys(\App\Models\PaymentGateway::drivers()),
        );
    }

    public function test_paid_lifecycle_restore_defers_gateway_start_to_a_marked_form(): void
    {
        $view = file_get_contents(resource_path('views/front/lifecycle/restore-checkout.blade.php'));
        $suspended = file_get_contents(resource_path('views/front/suspended.blade.php'));

        $this->assertStringContainsString("route('account.lifecycle.start-payment'", $view);
        $this->assertStringContainsString('data-payment-start', $view);
        $this->assertStringNotContainsString('data-payment-start', $suspended);
    }

    public function test_untrusted_country_header_is_fail_open_in_customer_host(): void
    {
        config([
            // TEST-HARNESS-DB-01: the feature is disabled by default
            // (PAYMENT_CONNECTION_WARNING_ENABLED env, safe default — see
            // core-utils's own payment-connection-warning.php config) —
            // PaymentConnectionAdvisor::advise() short-circuits to
            // status=disabled before ever reaching the fail-open logic this
            // test exercises unless explicitly turned on here too.
            'payment-connection-warning.enabled' => true,
            'payment-connection-warning.trusted_proxy_cidr_file' => '',
            'payment-connection-warning.trusted_proxy_cidrs' => ['185.143.232.0/22'],
        ]);

        $this->postJson(route('payment-connection.preflight'), [], [
            'X-Country-Code' => 'IR',
            'X-Origin-Peer-IP' => '198.51.100.20',
        ])->assertOk()->assertJson([
            'allowed' => true,
            'requires_warning' => false,
            'status' => 'unknown_fail_open',
        ]);
    }
}
