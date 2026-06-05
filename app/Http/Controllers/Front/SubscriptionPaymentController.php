<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Helpers\SiteHelper;
use App\Models\Invoice;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;

class SubscriptionPaymentController extends Controller
{
    public function __construct(private SubscriptionService $service) {}

    /**
     * صفحه پرداخت فاکتور اشتراک.
     */
    public function pay(string $invoiceNumber)
    {
        if (! SiteHelper::moduleEnabled('subscription')) abort(404);

        $invoice = Invoice::where('invoice_number', $invoiceNumber)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if ($invoice->status === 'paid') {
            return redirect()->route('account.subscriptions.index')
                ->with('success', 'این فاکتور قبلاً پرداخت شده است.');
        }

        return view('front.subscriptions.pay', compact('invoice'));
    }

    /**
     * تأیید پرداخت (callback از درگاه یا تأیید دستی).
     * در این نسخه: تأیید دستی توسط ادمین در نظر گرفته می‌شود.
     */
    public function confirm(Request $request, string $invoiceNumber)
    {
        if (! SiteHelper::moduleEnabled('subscription')) abort(404);

        $invoice = Invoice::where('invoice_number', $invoiceNumber)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if ($invoice->status === 'paid') {
            return redirect()->route('account.subscriptions.index')
                ->with('info', 'این فاکتور قبلاً پردازش شده است.');
        }

        $planId = session('pending_subscription_plan');
        if (! $planId) {
            return redirect()->route('account.subscriptions.plans')
                ->with('error', 'اطلاعات پلن یافت نشد. لطفاً مجدداً اقدام کنید.');
        }

        $plan = SubscriptionPlan::find($planId);
        if (! $plan) {
            return redirect()->route('account.subscriptions.plans')
                ->with('error', 'پلن انتخابی یافت نشد.');
        }

        // در اینجا می‌توان به درگاه پرداخت متصل شد.
        // فعلاً: ایجاد subscription با وضعیت pending + در انتظار تأیید ادمین
        $invoice->update(['status' => 'pending_confirmation']);

        // ایجاد یا به‌روزرسانی subscription pending
        $existingPending = \App\Models\Subscription::where('invoice_id', $invoice->id)
            ->where('user_id', auth()->id())
            ->first();

        if (! $existingPending) {
            $this->service->createPendingSubscription(auth()->user(), $plan, $invoice->id);
        }

        session()->forget('pending_subscription_plan');

        return redirect()->route('account.subscriptions.index')
            ->with('info', 'درخواست پرداخت شما ثبت شد و پس از تأیید توسط مدیریت فعال می‌شود.');
    }

    /**
     * فعال‌سازی اشتراک پس از تأیید پرداخت (توسط ادمین).
     */
    public function activate(Request $request, Invoice $invoice)
    {
        abort_if(! auth()->user()?->is_admin, 403);

        $planId = $request->validate(['plan_id' => 'required|exists:subscription_plans,id'])['plan_id'];
        $plan   = SubscriptionPlan::findOrFail($planId);
        $user   = $invoice->user;

        $invoice->update(['status' => 'paid', 'paid_at' => now()]);

        $subscription = $this->service->subscribe($user, $plan, $invoice->id);

        return response()->json(['success' => true, 'subscription_id' => $subscription->id]);
    }
}
