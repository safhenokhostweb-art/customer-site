<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Helpers\SiteHelper;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\GroupSubscriptionService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionService $service,
        private GroupSubscriptionService $groupService,
    ) {}

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

        $active         = $this->service->getActive($user);
        $pendingInvites = $this->groupService->pendingInvites($user);

        return view('account.subscriptions.index', compact('subscriptions', 'active', 'pendingInvites'));
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
        $plans  = SubscriptionPlan::where('is_active', true)->orderBy('order')->with('products')->get();
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

        $request->validate([
            'plan_id'     => 'required|exists:subscription_plans,id',
            'coupon_code' => 'nullable|string|max:50',
        ]);

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

        // بررسی کوپن تخفیف
        $coupon         = null;
        $discountAmount = 0;
        $couponCode     = trim($request->coupon_code ?? '');

        if ($couponCode) {
            $coupon = \App\Models\Coupon::where('code', $couponCode)
                ->where('is_active', true)
                ->first();

            if (! $coupon) {
                return back()->with('error', 'کد تخفیف «' . $couponCode . '» معتبر نیست.');
            }

            $basePrice = (int) $plan->getEffectivePrice();
            if (! $coupon->isValidForAmount($basePrice, [], $user->id)) {
                return back()->with('error', 'این کد تخفیف برای اشتراک قابل استفاده نیست (منقضی، حداقل مبلغ یا محدودیت).');
            }

            $discountAmount = $coupon->calculateDiscount($basePrice);
        }

        // پلن پولی: ساخت فاکتور + subscription pending + ریدایرکت به صفحه پرداخت
        $invoice = $this->service->createInvoice($user, $plan, $discountAmount, $coupon);
        $this->service->createPendingSubscription($user, $plan, $invoice->id);

        // افزایش تعداد استفاده از کوپن
        if ($coupon) {
            $coupon->increment('used_count');
        }

        session(['pending_subscription_plan' => $plan->id]);

        return redirect()->route('subscriptions.pay', $invoice->invoice_number);
    }

    public function upgradePlans(Subscription $subscription)
    {
        $this->requireModule();

        if ($subscription->user_id !== auth()->id()) abort(404);

        if (! $subscription->isActive()) {
            return redirect()->route('account.subscriptions.plans')
                ->with('error', 'فقط اشتراک‌های فعال قابل ارتقاء / تغییر هستند.');
        }

        $subscription->load('plan');

        $plans = SubscriptionPlan::where('is_active', true)
            ->where('id', '!=', $subscription->subscription_plan_id)
            ->orderBy('order')
            ->get();

        $previews = $plans->map(fn ($plan) => [
            'plan'    => $plan,
            'preview' => $this->service->upgradePreview($subscription, $plan),
        ]);

        return view('account.subscriptions.upgrade-plans', compact('subscription', 'previews'));
    }

    public function upgrade(Request $request, Subscription $subscription)
    {
        $this->requireModule();

        if ($subscription->user_id !== auth()->id()) abort(404);

        if (! $subscription->isActive()) {
            return back()->with('error', 'فقط اشتراک‌های فعال قابل تغییر هستند.');
        }

        $request->validate(['plan_id' => 'required|exists:subscription_plans,id']);

        $newPlan = SubscriptionPlan::findOrFail($request->plan_id);

        if (! $newPlan->is_active) {
            return back()->with('error', 'این پلن در دسترس نیست.');
        }
        if ($newPlan->id === $subscription->subscription_plan_id) {
            return back()->with('error', 'شما در حال حاضر از این پلن استفاده می‌کنید.');
        }

        $user    = auth()->user();
        $preview = $this->service->upgradePreview($subscription, $newPlan);
        $credit  = $preview['credit'];

        // بدون نیاز به پرداخت: پلن رایگان یا اعتبار کافی
        if ($preview['is_direct'] || $preview['is_free']) {
            $this->service->upgrade($user, $subscription, $newPlan, null, $credit);

            $msg = "پلن شما با موفقیت به «{$newPlan->name}» تغییر یافت.";
            if ($preview['leftover_points'] > 0) {
                $pts = \App\Helpers\NumberHelper::formatFa($preview['leftover_points']);
                $msg .= " {$pts} امتیاز از اعتبار باقیمانده به حساب شما اضافه شد.";
            }

            return redirect()->route('account.subscriptions.index')->with('success', $msg);
        }

        // نیاز به پرداخت: ساخت فاکتور + subscription pending
        $invoice = $this->service->createUpgradeInvoice($user, $newPlan, $subscription, (int) $credit);

        Subscription::create([
            'user_id'                      => $user->id,
            'subscription_plan_id'         => $newPlan->id,
            'status'                       => Subscription::STATUS_PENDING,
            'starts_at'                    => now(),
            'ends_at'                      => now()->addDays($newPlan->getDurationInDays()),
            'invoice_id'                   => $invoice->id,
            'upgrade_from_subscription_id' => $subscription->id,
            'credit_amount'                => (int) $credit,
            'pending_reason'               => "در انتظار پرداخت — تغییر پلن از «{$subscription->plan->name}»",
        ]);

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

    public function toggleAutoRenew(Subscription $subscription)
    {
        $this->requireModule();

        if ($subscription->user_id !== auth()->id()) abort(404);

        if (! $subscription->isActive() && ! $subscription->isInTrial()) {
            return back()->with('error', 'فقط اشتراک‌های فعال قابل تنظیم هستند.');
        }

        $subscription->update(['auto_renew' => ! $subscription->auto_renew]);

        $msg = $subscription->fresh()->auto_renew
            ? 'تمدید خودکار فعال شد.'
            : 'تمدید خودکار غیرفعال شد.';

        return back()->with('success', $msg);
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
