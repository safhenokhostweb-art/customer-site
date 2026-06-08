<?php

namespace Tests\Feature;

use App\Helpers\Module;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\License;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use Tests\TestCase;

/**
 * Invoice PDF download feature tests.
 */
class InvoicePdfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');

        $ref  = new ReflectionClass(Module::class);
        $prop = $ref->getProperty('resolved');
        $prop->setAccessible(true);
        $prop->setValue(null, null);

        License::query()->delete();
        License::create([
            'license_key'       => 'INV-PDF-TEST-LICENSE',
            'domain'            => '',
            'expires_at'        => now()->addYear(),
            'modules'           => ['invoice'],
            'platform_valid'    => true,
            'last_validated_at' => now(),
        ]);
    }

    private function makeInvoice(string $status = 'paid'): Invoice
    {
        $invoice = Invoice::create([
            'invoice_number'  => 'INV-TEST-' . uniqid(),
            'customer_name'   => 'علی محمدی',
            'customer_mobile' => '09120000000',
            'subtotal'        => 200000,
            'total'           => 200000,
            'status'          => $status,
            'payment_link'    => null,
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'title'      => 'اشتراک ماهانه',
            'price'      => 200000,
            'quantity'   => 1,
            'total'      => 200000,
        ]);
        return $invoice;
    }

    public function test_pdf_route_returns_pdf_response(): void
    {
        $invoice = $this->makeInvoice('paid');

        $response = $this->get(route('invoice.download', $invoice->invoice_number));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString(
            "invoice-{$invoice->invoice_number}.pdf",
            $response->headers->get('Content-Disposition') ?? ''
        );
    }

    public function test_pdf_for_pending_invoice(): void
    {
        $invoice = $this->makeInvoice('pending');
        $response = $this->get(route('invoice.download', $invoice->invoice_number));
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_pdf_returns_404_for_unknown_number(): void
    {
        $response = $this->get(route('invoice.download', 'INV-NONEXISTENT-99999'));
        $response->assertNotFound();
    }

    public function test_invoice_show_page_contains_pdf_download_link(): void
    {
        $invoice  = $this->makeInvoice('paid');
        $response = $this->get(route('invoice.show', $invoice->invoice_number));
        $response->assertOk();
        $response->assertSee(route('invoice.download', $invoice->invoice_number));
    }

    public function test_pdf_with_discount(): void
    {
        $invoice = Invoice::create([
            'invoice_number'  => 'INV-DISC-' . uniqid(),
            'customer_name'   => 'رضا کریمی',
            'customer_mobile' => '09130000000',
            'subtotal'        => 300000,
            'discount'        => 50000,
            'total'           => 250000,
            'status'          => 'paid',
            'payment_link'    => null,
            'notes'           => 'تخفیف ویژه',
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'title'      => 'اشتراک سالانه',
            'price'      => 300000,
            'quantity'   => 1,
            'total'      => 300000,
        ]);

        $response = $this->get(route('invoice.download', $invoice->invoice_number));
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }
}
