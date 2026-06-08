<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleInvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
        config(['site.owner_site' => true]);
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('installed.lock'));
        parent::tearDown();
    }

    public function test_invoice_create_and_show(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $response = $this->post(route('admin.invoices.store'), [
            'customer_name'   => 'علی احمدی',
            'customer_mobile' => '09120000001',
            'items' => [
                ['title' => 'محصول ۱', 'price' => 10000, 'quantity' => 2],
            ],
        ]);
        $response->assertRedirect(route('admin.invoices.index'));
        $invoice = Invoice::first();
        $this->assertNotNull($invoice);
        $this->assertEquals(20000, $invoice->total);

        $response = $this->get(route('invoice.show', $invoice->invoice_number));
        $response->assertStatus(200);
        $response->assertSee('علی احمدی');
    }
}
