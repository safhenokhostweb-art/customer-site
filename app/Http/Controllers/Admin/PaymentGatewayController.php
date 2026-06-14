<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentGatewayController extends Controller
{
    public function index()
    {
        $gateways = PaymentGateway::orderBy('sort_order')->orderBy('id')->get();
        $drivers  = PaymentGateway::drivers();

        return view('admin.payment-gateways.index', compact('gateways', 'drivers'));
    }

    public function create()
    {
        $drivers      = PaymentGateway::drivers();
        $driverFields = PaymentGateway::driverFields();

        return view('admin.payment-gateways.create', compact('drivers', 'driverFields'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:100',
            'driver'     => ['required', Rule::in(array_keys(PaymentGateway::drivers()))],
            'is_active'  => 'boolean',
            'is_default' => 'boolean',
            'sort_order' => 'integer|min:0|max:255',
            'config'     => 'nullable|array',
        ]);

        $config = $this->extractConfig($request, $data['driver']);

        $gateway = PaymentGateway::create([
            'name'       => $data['name'],
            'driver'     => $data['driver'],
            'config'     => $config,
            'is_active'  => $request->boolean('is_active', true),
            'is_default' => false,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        if ($request->boolean('is_default')) {
            $gateway->setAsDefault();
        }

        return redirect()->route('admin.payment-gateways.index')
            ->with('success', "درگاه «{$gateway->name}» با موفقیت افزوده شد.");
    }

    public function edit(PaymentGateway $paymentGateway)
    {
        $drivers      = PaymentGateway::drivers();
        $driverFields = PaymentGateway::driverFields();

        return view('admin.payment-gateways.edit', compact('paymentGateway', 'drivers', 'driverFields'));
    }

    public function update(Request $request, PaymentGateway $paymentGateway)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:100',
            'driver'     => ['required', Rule::in(array_keys(PaymentGateway::drivers()))],
            'is_active'  => 'boolean',
            'sort_order' => 'integer|min:0|max:255',
        ]);

        $config = $this->extractConfig($request, $data['driver']);

        // اگر config خالی بود، config قبلی را نگه‌دار
        if (empty(array_filter($config))) {
            $config = $paymentGateway->config ?? [];
        }

        $paymentGateway->update([
            'name'       => $data['name'],
            'driver'     => $data['driver'],
            'config'     => $config,
            'is_active'  => $request->boolean('is_active', true),
            'sort_order' => $data['sort_order'] ?? $paymentGateway->sort_order,
        ]);

        if ($request->boolean('is_default')) {
            $paymentGateway->setAsDefault();
        }

        return redirect()->route('admin.payment-gateways.index')
            ->with('success', "درگاه «{$paymentGateway->name}» بروز شد.");
    }

    public function destroy(PaymentGateway $paymentGateway)
    {
        $name = $paymentGateway->name;
        $paymentGateway->delete();

        return redirect()->route('admin.payment-gateways.index')
            ->with('success', "درگاه «{$name}» حذف شد.");
    }

    /** تنظیم پیش‌فرض */
    public function setDefault(PaymentGateway $paymentGateway)
    {
        $paymentGateway->setAsDefault();

        return redirect()->route('admin.payment-gateways.index')
            ->with('success', "«{$paymentGateway->name}» به‌عنوان درگاه پیش‌فرض انتخاب شد.");
    }

    // ─── Private ──────────────────────────────────────────────────────────────

    private function extractConfig(Request $request, string $driver): array
    {
        $fields = PaymentGateway::driverFields()[$driver] ?? [];
        $config = [];

        foreach ($fields as $field) {
            $key   = $field['key'];
            $value = $request->input("config.{$key}");

            if ($field['type'] === 'checkbox') {
                $config[$key] = $request->boolean("config.{$key}");
            } elseif ($value !== null && $value !== '') {
                $config[$key] = $value;
            }
        }

        return $config;
    }
}
