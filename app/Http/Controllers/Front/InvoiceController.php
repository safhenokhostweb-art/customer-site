<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Invoice;

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
}
