<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Helpers\SiteHelper;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\SubscriptionLog;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        // یافتن پلن: اول از pending subscription مرتبط با فاکتور، سپس از session
        $pendingSub = Subscription::where('invoice_id', $invoice->id)
            ->where('user_id', auth()->id())
            ->first();

        $planId = $pendingSub?->subscription_plan_id
            ?? session('pending_subscription_plan');

        if (! $planId) {
            return redirect()->route('account.subscriptions.plans')
                ->with('error', 'اطلاعات پلن یافت نشد. لطفاً دوباره پلن انتخاب کنید.');
        }

        $plan = SubscriptionPlan::find($planId);
        if (! $plan) {
            return redirect()->route('account.subscriptions.plans')
                ->with('error', 'پلن انتخابی یافت نشد.');
        }

        session()->forget('pending_subscription_plan');

        // ─── فعال‌سازی فوری — بدون نیاز به تأیید ادمین ───────────────────
        // NOTE: وقتی ماژول bank_gateway اضافه شود، اینجا به ریدایرکت به درگاه تبدیل می‌شود.
        $user = auth()->user();

        DB::transaction(function () use ($invoice, $pendingSub, $plan, $user) {
            $now = now();

            $invoice->update(['status' => 'paid', 'paid_at' => $now]);

            if ($pendingSub) {
                $pendingSub->update([
                    'status'    => Subscription::STATUS_ACTIVE,
                    'starts_at' => $now,
                    'ends_at'   => $now->copy()->addDays($plan->getDurationInDays()),
                ]);
                SubscriptionLog::create([
                    'subscription_id' => $pendingSub->id,
                    'user_id'         => $user->id,
                    'action'          => SubscriptionLog::ACTION_SUBSCRIBED,
                    'performed_by'    => 'user',
                    'details'         => ['plan' => $plan->name, 'auto_activated' => true],
                ]);
                $this->service->notifyActivation($user, $plan, $pendingSub->fresh());
            } else {
                $sub = Subscription::create([
                    'user_id'              => $user->id,
                    'subscription_plan_id' => $plan->id,
                    'status'               => Subscription::STATUS_ACTIVE,
                    'starts_at'            => $now,
                    'ends_at'              => $now->copy()->addDays($plan->getDurationInDays()),
                    'invoice_id'           => $invoice->id,
                ]);
                $this->service->notifyActivation($user, $plan, $sub);
            }
        });

        return redirect()->route('account.subscriptions.index')
            ->with('success', "اشتراک «{$plan->name}» با موفقیت فعال شد.");
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
