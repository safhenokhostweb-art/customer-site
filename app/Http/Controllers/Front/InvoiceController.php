<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function show(string $invoiceNumber)
    {
        $invoice = Invoice::where('invoice_number', $invoiceNumber)->with('items')->firstOrFail();
        return view('invoice.show', compact('invoice'));
    }

    public function pay(string $invoiceNumber)
    {
        $invoice = Invoice::where('invoice_number', $invoiceNumber)->with('items')->firstOrFail();
        if ($invoice->status === 'paid') {
            return redirect()->route('invoice.show', $invoiceNumber)->with('info', 'این فاکتور قبلاً پرداخت شده است.');
        }
        return view('invoice.pay', compact('invoice'));
    }

    public function download(string $invoiceNumber)
    {
        $invoice = Invoice::where('invoice_number', $invoiceNumber)->with('items')->firstOrFail();
        $pdf = Pdf::loadView('invoice.pdf', compact('invoice'))
            ->setPaper('a4', 'portrait');
        return $pdf->download("invoice-{$invoice->invoice_number}.pdf");
    }
}
