<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Helpers\SiteHelper;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(private SubscriptionService $service) {}

    private function requireModule(): void
    {
        if (! SiteHelper::moduleEnabled('subscription')) {
            abort(404);
        }
    }

    public function index()
    {
        $this->requireModule();

        $user = auth()->user();

        $subscriptions = Subscription::with('plan')
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $active = $this->service->getActive($user);

        return view('account.subscriptions.index', compact('subscriptions', 'active'));
    }

    public function show(Subscription $subscription)
    {
        $this->requireModule();

        if ($subscription->user_id !== auth()->id()) abort(404);

        $subscription->load(['plan', 'logs', 'invoice']);

        return view('account.subscriptions.show', compact('subscription'));
    }

    public function plans()
    {
        $this->requireModule();

        $user   = auth()->user();
        $plans  = SubscriptionPlan::where('is_active', true)->orderBy('order')->get();
        $active = $this->service->getActive($user);

        // اشتراک یا درخواست مسدودکننده (active/trial/pending)
        $blocked = Subscription::where('user_id', $user->id)
            ->whereIn('status', [
                Subscription::STATUS_ACTIVE,
                Subscription::STATUS_TRIAL,
                Subscription::STATUS_PENDING,
            ])
            ->with('plan')
            ->first();

        return view('account.subscriptions.plans', compact('plans', 'active', 'blocked'));
    }

    public function subscribe(Request $request)
    {
        $this->requireModule();

        $request->validate(['plan_id' => 'required|exists:subscription_plans,id']);

        $plan = SubscriptionPlan::findOrFail($request->plan_id);
        $user = auth()->user();

        if (! $plan->is_active) {
            return back()->with('error', 'این پلن در دسترس نیست.');
        }

        // بررسی: اگر کاربر هر نوع اشتراک فعال یا درخواست در انتظار دارد، نمی‌تواند درخواست جدید ثبت کند
        $blocked = Subscription::where('user_id', $user->id)
            ->whereIn('status', [
                Subscription::STATUS_ACTIVE,
                Subscription::STATUS_TRIAL,
                Subscription::STATUS_PENDING,
            ])
            ->with('plan')
            ->first();

        if ($blocked) {
            $label = $blocked->status === Subscription::STATUS_PENDING
                ? 'یک درخواست اشتراک در انتظار تأیید دارید'
                : 'یک اشتراک فعال دارید';
            $planName = $blocked->plan?->name ?? '';
            return back()->with('error',
                "{$label} (پلن: {$planName}). ابتدا آن را لغو کنید تا بتوانید اشتراک جدید ثبت نمایید."
            );
        }

        // پلن رایگان: فعال‌سازی فوری
        if ($plan->isFree()) {
            $this->service->subscribe($user, $plan);
            return redirect()->route('account.subscriptions.index')
                ->with('success', "اشتراک رایگان {$plan->name} فعال شد.");
        }

        // پلن پولی: ساخت فاکتور + subscription pending + ریدایرکت به صفحه پرداخت
        $invoice = $this->service->createInvoice($user, $plan);
        $this->service->createPendingSubscription($user, $plan, $invoice->id);

        session(['pending_subscription_plan' => $plan->id]);

        return redirect()->route('subscriptions.pay', $invoice->invoice_number);
    }

    public function cancel(Request $request, Subscription $subscription)
    {
        $this->requireModule();

        if ($subscription->user_id !== auth()->id()) abort(404);

        if (! $subscription->isActive() && ! $subscription->isInTrial()) {
            return back()->with('error', 'این اشتراک قابل لغو نیست.');
        }

        $request->validate(['reason' => 'nullable|string|max:500']);

        $this->service->cancel($subscription, $request->reason, 'user');

        return back()->with('success', 'اشتراک با موفقیت لغو شد.');
    }

    /**
     * حذف درخواست اشتراک pending از لیست (توسط کاربر نهایی).
     */
    public function deletePending(Subscription $subscription)
    {
        $this->requireModule();

        if ($subscription->user_id !== auth()->id()) abort(404);

        if (! $subscription->isPending()) {
            return back()->with('error', 'فقط درخواست‌های در انتظار قابل حذف هستند.');
        }

        // لغو فاکتور مرتبط
        if ($subscription->invoice) {
            $subscription->invoice->update(['status' => 'cancelled']);
        }

        $subscription->delete();

        session()->forget('pending_subscription_plan');

        return redirect()->route('account.subscriptions.index')
            ->with('success', 'درخواست اشتراک حذف شد. اکنون می‌توانید اشتراک جدید ثبت کنید.');
    }

    /**
     * لغو درخواست اشتراک در انتظار پرداخت (از بنر پنل کاربر).
     */
    public function cancelPending(Request $request)
    {
        $this->requireModule();

        $request->validate(['invoice_id' => 'required|integer']);

        $invoice = \App\Models\Invoice::where('id', $request->invoice_id)
            ->where('user_id', auth()->id())
            ->whereIn('status', ['pending', 'pending_confirmation'])
            ->firstOrFail();

        $invoice->update(['status' => 'cancelled']);

        // اشتراک pending مرتبط را هم حذف کن
        $sub = Subscription::where('invoice_id', $invoice->id)
            ->where('user_id', auth()->id())
            ->where('status', Subscription::STATUS_PENDING)
            ->first();

        if ($sub) {
            $sub->delete();
        }

        session()->forget('pending_subscription_plan');

        return back()->with('success', 'درخواست اشتراک لغو شد. اکنون می‌توانید اشتراک جدید ثبت کنید.');
    }
}
