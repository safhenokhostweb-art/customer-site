<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InstallmentOrder;
use App\Models\InstallmentPayment;
use App\Models\InstallmentPlan;
use App\Services\InstallmentService;
use Illuminate\Http\Request;

class InstallmentAdminController extends Controller
{
    public function __construct(private InstallmentService $service) {}

    // ── Plans ──────────────────────────────────────────────────────────────

    public function plansIndex()
    {
        $plans = InstallmentPlan::orderBy('period_months')->get();
        return view('admin.installments.plans-index', compact('plans'));
    }

    public function plansCreate()
    {
        return view('admin.installments.plans-form', ['plan' => null]);
    }

    public function plansStore(Request $request)
    {
        $data = $request->validate([
            'name'                  => 'required|string|max:100',
            'period_months'         => 'required|integer|in:3,6,12,24',
            'down_payment_percent'  => 'required|numeric|min:0|max:100',
            'monthly_interest_rate' => 'required|numeric|min:0|max:50',
            'min_amount'            => 'required|integer|min:0',
            'max_amount'            => 'required|integer|min:0',
            'is_active'             => 'boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        InstallmentPlan::create($data);

        return redirect()->route('admin.installments.plans.index')
            ->with('success', 'طرح اقساطی با موفقیت ایجاد شد.');
    }

    public function plansEdit(InstallmentPlan $plan)
    {
        return view('admin.installments.plans-form', compact('plan'));
    }

    public function plansUpdate(Request $request, InstallmentPlan $plan)
    {
        $data = $request->validate([
            'name'                  => 'required|string|max:100',
            'period_months'         => 'required|integer|in:3,6,12,24',
            'down_payment_percent'  => 'required|numeric|min:0|max:100',
            'monthly_interest_rate' => 'required|numeric|min:0|max:50',
            'min_amount'            => 'required|integer|min:0',
            'max_amount'            => 'required|integer|min:0',
            'is_active'             => 'boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $plan->update($data);

        return redirect()->route('admin.installments.plans.index')
            ->with('success', 'طرح اقساطی ویرایش شد.');
    }

    public function plansDestroy(InstallmentPlan $plan)
    {
        if ($plan->orders()->exists()) {
            return back()->with('error', 'این طرح دارای سفارش است و قابل حذف نیست.');
        }
        $plan->delete();
        return redirect()->route('admin.installments.plans.index')
            ->with('success', 'طرح حذف شد.');
    }

    // ── Orders ─────────────────────────────────────────────────────────────

    public function ordersIndex(Request $request)
    {
        $q = InstallmentOrder::with(['plan', 'user', 'payments']);

        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }
        if ($search = $request->query('search')) {
            $q->whereHas('user', fn($u) => $u->where('mobile', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%"));
        }

        $orders = $q->latest()->paginate(20)->withQueryString();
        $stats  = [
            'total'     => InstallmentOrder::count(),
            'active'    => InstallmentOrder::where('status', 'active')->count(),
            'pending'   => InstallmentOrder::where('status', 'pending')->count(),
            'defaulted' => InstallmentOrder::where('status', 'defaulted')->count(),
        ];

        return view('admin.installments.orders-index', compact('orders', 'stats'));
    }

    public function ordersShow(InstallmentOrder $order)
    {
        $order->load(['plan', 'user', 'payments']);
        return view('admin.installments.orders-show', compact('order'));
    }

    public function markOverdue()
    {
        $count = $this->service->markOverdue();
        return back()->with('success', "تعداد {$count} قسط به وضعیت معوقه تغییر کرد.");
    }
}
