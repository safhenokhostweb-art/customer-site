<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\InstallmentOrder;
use App\Models\InstallmentPayment;
use App\Models\InstallmentPlan;
use App\Services\InstallmentService;
use App\Services\Payment\ZarinpalGateway;
use App\Services\Payment\IdPayGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class InstallmentController extends Controller
{
    public function __construct(private InstallmentService $service)
    {
    }

    // ── redirect از صفحه محصول با plan و product پیش‌انتخاب‌شده ─────────────

    public function apply(Request $request)
    {
        $planId    = (int) $request->query('plan_id', 0);
        $productId = (int) $request->query('product_id', 0);

        if ($productId > 0) {
            $product = \App\Models\Product::active()->find($productId);
            if ($product) {
                $amount = (int) ($product->display_price ?? $product->price ?? 0);
                return redirect()->route('installment.plans', ['amount' => $amount, 'plan' => $planId ?: null]);
            }
        }

        return redirect()->route('installment.plans', ['amount' => 0]);
    }

    // ── صفحه انتخاب طرح اقساطی ──────────────────────────────────────────────

    public function plans(Request $request)
    {
        $amount = (int) $request->query('amount', 0);
        if ($amount <= 0) {
            return redirect()->back()->with('error', 'مبلغ نامعتبر است.');
        }

        $plans    = $this->service->eligiblePlans($amount);
        $selected = $request->query('plan');

        return view('front.installment.plans', compact('plans', 'amount', 'selected'));
    }

    // ── شروع خرید قسطی: ایجاد سفارش + redirect به درگاه برای پیش‌پرداخت ────

    public function initiate(Request $request)
    {
        $request->validate([
            'plan_id'  => 'required|exists:installment_plans,id',
            'amount'   => 'required|integer|min:1000',
            'order_id' => 'nullable|integer',
        ]);

        $plan   = InstallmentPlan::findOrFail($request->input('plan_id'));
        $amount = (int) $request->input('amount');

        if (!$plan->isEligible($amount)) {
            return back()->with('error', 'این طرح اقساطی برای مبلغ وارد‌شده مناسب نیست.');
        }

        $user  = Auth::user();
        $order = $this->service->createOrder($user, $plan, $amount, $request->input('order_id'));

        // پرداخت پیش‌پرداخت از طریق درگاه
        return $this->redirectToGateway($order);
    }

    // ── callback درگاه پیش‌پرداخت ────────────────────────────────────────────

    public function downPaymentCallback(Request $request, int $installmentOrderId)
    {
        $order = InstallmentOrder::findOrFail($installmentOrderId);

        if ($order->status !== InstallmentOrder::STATUS_PENDING) {
            return redirect()->route('account.installments')->with('info', 'این سفارش قبلاً پردازش شده است.');
        }

        // Verify payment (از طریق همان gateway که زمان ایجاد ذخیره شد)
        [$verified, $ref] = $this->verifyGatewayPayment($request);

        if (!$verified) {
            return redirect()->route('account.installments')->with('error', 'پرداخت پیش‌قسط تأیید نشد.');
        }

        try {
            $this->service->activateOrder($order, 'zarinpal', (string) $ref);
        } catch (\Throwable $e) {
            Log::error('InstallmentOrder activate failed', ['id' => $order->id, 'error' => $e->getMessage()]);
            return redirect()->route('account.installments')->with('error', 'خطا در فعال‌سازی سفارش قسطی.');
        }

        return redirect()->route('account.installments')
            ->with('success', 'خرید قسطی با موفقیت ثبت شد. اولین قسط در تاریخ مقرر باید پرداخت شود.');
    }

    // ── پرداخت یک قسط ────────────────────────────────────────────────────────

    public function payInstallment(Request $request, int $installmentOrderId, int $paymentId)
    {
        $order = InstallmentOrder::where('id', $installmentOrderId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $payment = $order->payments()->where('id', $paymentId)->firstOrFail();

        if ($payment->status === InstallmentPayment::STATUS_PAID) {
            return redirect()->route('account.installments')->with('info', 'این قسط قبلاً پرداخت شده است.');
        }

        // redirect به درگاه
        return $this->redirectToGatewayForPayment($order, $payment);
    }

    // ── callback قسط ─────────────────────────────────────────────────────────

    public function installmentCallback(Request $request, int $installmentOrderId, int $paymentId)
    {
        $order = InstallmentOrder::findOrFail($installmentOrderId);

        [$verified, $ref] = $this->verifyGatewayPayment($request);

        if (!$verified) {
            return redirect()->route('account.installments')->with('error', 'پرداخت قسط تأیید نشد.');
        }

        $this->service->recordInstallmentPayment($order, $paymentId, 'zarinpal', (string) $ref);

        return redirect()->route('account.installments')
            ->with('success', 'قسط با موفقیت پرداخت شد.');
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    private function redirectToGateway(InstallmentOrder $order)
    {
        $gateway = config('payment.default', 'zarinpal');
        $callbackUrl = route('installment.down-payment.callback', $order->id);
        $description = "پیش‌پرداخت خرید قسطی #{$order->id}";

        try {
            if ($gateway === 'idpay') {
                $gw = app(IdPayGateway::class);
            } else {
                $gw = app(ZarinpalGateway::class);
            }
            $paymentUrl = $gw->createPayment($order->down_payment, $description, $callbackUrl);
            return redirect()->away($paymentUrl);
        } catch (\Throwable $e) {
            Log::error('InstallmentController gateway error', ['error' => $e->getMessage()]);
            $order->delete();
            return back()->with('error', 'خطا در اتصال به درگاه پرداخت. لطفاً مجدداً تلاش کنید.');
        }
    }

    private function redirectToGatewayForPayment(InstallmentOrder $order, \App\Models\InstallmentPayment $payment)
    {
        $gateway = config('payment.default', 'zarinpal');
        $callbackUrl = route('installment.payment.callback', [$order->id, $payment->id]);
        $description = "قسط شماره {$payment->installment_number} سفارش #{$order->id}";

        try {
            if ($gateway === 'idpay') {
                $gw = app(IdPayGateway::class);
            } else {
                $gw = app(ZarinpalGateway::class);
            }
            $paymentUrl = $gw->createPayment($payment->amount, $description, $callbackUrl);
            return redirect()->away($paymentUrl);
        } catch (\Throwable $e) {
            return back()->with('error', 'خطا در اتصال به درگاه پرداخت.');
        }
    }

    private function verifyGatewayPayment(Request $request): array
    {
        // Zarinpal: Status=OK + Authority
        if ($request->query('Status') === 'OK') {
            return [true, $request->query('Authority')];
        }
        // IDPay: status=100 + track_id
        if ($request->query('status') === '100' && $request->query('track_id')) {
            return [true, $request->query('track_id')];
        }
        return [false, null];
    }
}
