<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::orderBy('created_at', 'desc')->paginate(15);
        return view('admin.invoices.index', compact('invoices'));
    }

    public function create()
    {
        return view('admin.invoices.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email',
            'customer_phone' => 'nullable|string|max:50',
            'items' => 'required|array|min:1',
            'items.*.title' => 'required|string|max:255',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
        ]);
        $invoiceNumber = 'INV-' . now()->format('Ymd') . '-' . str_pad((string) (Invoice::whereDate('created_at', today())->count() + 1), 4, '0', STR_PAD_LEFT);
        $subtotal = 0;
        foreach ($data['items'] as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
        $invoice = Invoice::create([
            'invoice_number' => $invoiceNumber,
            'customer_name' => $data['customer_name'],
            'customer_email' => $data['customer_email'],
            'customer_phone' => $data['customer_phone'] ?? null,
            'subtotal' => $subtotal,
            'total' => $subtotal,
            'status' => 'pending',
            'payment_link' => route('invoice.pay', $invoiceNumber),
        ]);
        foreach ($data['items'] as $item) {
            $total = $item['price'] * $item['quantity'];
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'title' => $item['title'],
                'price' => $item['price'],
                'quantity' => $item['quantity'],
                'total' => $total,
            ]);
        }
        return redirect()->route('admin.invoices.index')->with('success', 'فاکتور ایجاد شد.');
    }

    public function show(Invoice $invoice)
    {
        $invoice->load('items');
        return view('admin.invoices.show', compact('invoice'));
    }

    public function destroy(Invoice $invoice)
    {
        $invoice->delete();
        return redirect()->route('admin.invoices.index')->with('success', 'فاکتور حذف شد.');
    }
}
