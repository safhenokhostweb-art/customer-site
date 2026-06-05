<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;

class SubscriberController extends Controller
{
    public function __construct(private SubscriptionService $service) {}

    public function index(Request $request)
    {
        $query = Subscription::with(['user', 'plan'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('plan_id')) {
            $query->where('subscription_plan_id', $request->plan_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        $subscriptions = $query->paginate(25)->withQueryString();
        $plans         = SubscriptionPlan::orderBy('name')->get();

        $stats = [
            'total'     => Subscription::count(),
            'active'    => Subscription::where('status', Subscription::STATUS_ACTIVE)->count(),
            'trial'     => Subscription::where('status', Subscription::STATUS_TRIAL)->count(),
            'cancelled' => Subscription::where('status', Subscription::STATUS_CANCELLED)->count(),
            'expired'   => Subscription::where('status', Subscription::STATUS_EXPIRED)->count(),
        ];

        return view('admin.subscriptions.subscribers', compact('subscriptions', 'plans', 'stats'));
    }

    public function show(Subscription $subscription)
    {
        $subscription->load(['user', 'plan', 'logs.user', 'invoice']);
        return view('admin.subscriptions.subscriber-show', compact('subscription'));
    }

    public function cancel(Request $request, Subscription $subscription)
    {
        $request->validate(['reason' => 'nullable|string|max:500']);

        if (! $subscription->isActive() && ! $subscription->isInTrial()) {
            return back()->with('error', 'این اشتراک قابل لغو نیست.');
        }

        $this->service->cancel($subscription, $request->reason, 'admin');

        return back()->with('success', 'اشتراک با موفقیت لغو شد.');
    }

    public function reactivate(Subscription $subscription)
    {
        if (! $subscription->isCancelled()) {
            return back()->with('error', 'فقط اشتراک‌های لغو شده قابل فعال‌سازی مجدد هستند.');
        }

        $plan = $subscription->plan;
        $subscription->update([
            'status'       => Subscription::STATUS_ACTIVE,
            'ends_at'      => now()->addDays($plan->getDurationInDays()),
            'cancelled_at' => null,
        ]);

        \App\Models\SubscriptionLog::create([
            'subscription_id' => $subscription->id,
            'user_id'         => auth()->id(),
            'action'          => \App\Models\SubscriptionLog::ACTION_REACTIVATED,
            'performed_by'    => 'admin',
        ]);

        return back()->with('success', 'اشتراک مجدداً فعال شد.');
    }

    public function assign(Request $request)
    {
        $data = $request->validate([
            'user_id'         => 'required|exists:users,id',
            'plan_id'         => 'required|exists:subscription_plans,id',
            'notes'           => 'nullable|string|max:500',
            'activate_now'    => 'nullable|boolean',
        ]);

        $user = User::findOrFail($data['user_id']);
        $plan = SubscriptionPlan::findOrFail($data['plan_id']);

        if ($request->boolean('activate_now')) {
            // فعال‌سازی فوری توسط ادمین
            $subscription = $this->service->subscribe($user, $plan);
        } else {
            // ایجاد فاکتور + pending subscription برای کاربر
            $invoice      = $this->service->createInvoice($user, $plan);
            $subscription = $this->service->createPendingSubscription($user, $plan, $invoice->id);
        }

        $subscription->update(['notes' => $data['notes'] ?? null]);

        return redirect()->route('admin.subscribers.index')
            ->with('success', "اشتراک «{$plan->name}» برای {$user->name} ثبت شد.");
    }

    /**
     * نمایش فرم ایجاد اشتراک برای کاربر خاص.
     */
    public function createAssign()
    {
        $plans = SubscriptionPlan::where('is_active', true)->orderBy('order')->get();
        $users = User::orderBy('name')->select('id', 'name', 'mobile')->limit(500)->get();

        return view('admin.subscriptions.assign', compact('plans', 'users'));
    }

    /**
     * لیست درخواست‌های اشتراک در انتظار تأیید.
     */
    public function pendingRequests()
    {
        $requests = Subscription::with(['user', 'plan', 'invoice'])
            ->where('status', Subscription::STATUS_PENDING)
            ->latest()
            ->paginate(25);

        return view('admin.subscriptions.pending-requests', compact('requests'));
    }

    /**
     * تأیید و فعال‌سازی درخواست pending.
     */
    public function approveRequest(Subscription $subscriber)
    {
        if (! $subscriber->isPending()) {
            return back()->with('error', 'این درخواست قابل تأیید نیست.');
        }

        $user = $subscriber->user;
        $plan = $subscriber->plan;

        $subscriber->update([
            'status'     => Subscription::STATUS_ACTIVE,
            'starts_at'  => now(),
            'ends_at'    => now()->addDays($plan->getDurationInDays()),
        ]);

        if ($subscriber->invoice) {
            $subscriber->invoice->update(['status' => 'paid', 'paid_at' => now()]);
        }

        \App\Models\SubscriptionLog::create([
            'subscription_id' => $subscriber->id,
            'user_id'         => auth()->id(),
            'action'          => \App\Models\SubscriptionLog::ACTION_SUBSCRIBED,
            'performed_by'    => 'admin',
            'details'         => ['approved_by' => auth()->user()->name],
        ]);

        // ارسال تیکت + پیامک
        $this->service->subscribe($user, $plan, $subscriber->invoice_id);

        return back()->with('success', "اشتراک «{$plan->name}» برای {$user->name} تأیید و فعال شد.");
    }

    /**
     * حذف درخواست pending توسط ادمین.
     */
    public function destroyRequest(Subscription $subscriber)
    {
        if (! $subscriber->isPending()) {
            return back()->with('error', 'فقط درخواست‌های در انتظار قابل حذف هستند.');
        }

        if ($subscriber->invoice) {
            $subscriber->invoice->update(['status' => 'cancelled']);
        }

        $subscriber->delete();

        return back()->with('success', 'درخواست اشتراک حذف شد.');
    }

    public function export(Request $request)
    {
        $query = Subscription::with(['user', 'plan'])->latest();

        if ($request->filled('status'))  $query->where('status', $request->status);
        if ($request->filled('plan_id')) $query->where('subscription_plan_id', $request->plan_id);

        $subscriptions = $query->get();

        $csv = "\xEF\xBB\xBF"; // BOM for Persian Excel
        $csv .= "نام,موبایل,پلن,وضعیت,شروع,پایان\n";

        foreach ($subscriptions as $sub) {
            $csv .= implode(',', [
                '"' . ($sub->user->name  ?? '') . '"',
                '"' . ($sub->user->mobile ?? '') . '"',
                '"' . ($sub->plan->name  ?? '') . '"',
                '"' . $sub->getStatusLabel() . '"',
                '"' . \App\Helpers\Jalali::formatFa($sub->starts_at, 'Y/m/d') . '"',
                '"' . \App\Helpers\Jalali::formatFa($sub->ends_at,   'Y/m/d') . '"',
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="subscriptions_' . now()->format('Ymd') . '.csv"',
        ]);
    }
}
