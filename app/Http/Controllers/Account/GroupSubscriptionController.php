<?php

namespace App\Http\Controllers\Account;

use App\Helpers\SiteHelper;
use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionMember;
use App\Services\GroupSubscriptionService;
use Illuminate\Http\Request;

class GroupSubscriptionController extends Controller
{
    public function __construct(private GroupSubscriptionService $service) {}

    private function requireModule(): void
    {
        if (! SiteHelper::moduleEnabled('subscription')) abort(404);
    }

    /**
     * صفحه مدیریت اعضای گروه (برای صاحب اشتراک).
     */
    public function index(Subscription $subscription)
    {
        $this->requireModule();

        if ($subscription->user_id !== auth()->id()) abort(404);
        if (! $subscription->isActive()) {
            return redirect()->route('account.subscriptions.index')
                ->with('error', 'فقط اشتراک‌های فعال قابل مدیریت هستند.');
        }
        if (! $this->service->isGroupPlan($subscription)) {
            return redirect()->route('account.subscriptions.show', $subscription)
                ->with('error', 'این پلن قابلیت اشتراک گروهی ندارد.');
        }

        $subscription->load('plan');

        $members        = SubscriptionMember::where('subscription_id', $subscription->id)
            ->whereIn('status', [SubscriptionMember::STATUS_ACTIVE, SubscriptionMember::STATUS_PENDING])
            ->with('user')
            ->latest()
            ->get();

        $availableSeats = $this->service->availableSeats($subscription);

        return view('account.subscriptions.members', compact('subscription', 'members', 'availableSeats'));
    }

    /**
     * دعوت عضو جدید با شماره موبایل.
     */
    public function invite(Request $request, Subscription $subscription)
    {
        $this->requireModule();

        if ($subscription->user_id !== auth()->id()) abort(404);

        $request->validate([
            'mobile' => 'required|string|regex:/^09[0-9]{9}$/',
        ], [
            'mobile.required' => 'شماره موبایل الزامی است.',
            'mobile.regex'    => 'فرمت شماره موبایل معتبر نیست (مثال: 09123456789).',
        ]);

        try {
            $this->service->invite($subscription, auth()->user(), $request->mobile);
            return back()->with('success', 'دعوت‌نامه با موفقیت ارسال شد.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * حذف عضو از گروه توسط صاحب اشتراک.
     */
    public function remove(Subscription $subscription, SubscriptionMember $member)
    {
        $this->requireModule();

        if ($subscription->user_id !== auth()->id()) abort(404);
        if ($member->subscription_id !== $subscription->id) abort(404);

        try {
            $this->service->remove($member, auth()->user());
            return back()->with('success', 'عضو با موفقیت از گروه حذف شد.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * پذیرش دعوت‌نامه توسط کاربر دعوت‌شده.
     */
    public function accept(SubscriptionMember $member)
    {
        $this->requireModule();

        try {
            $this->service->accept($member, auth()->user());
            return redirect()->route('account.subscriptions.index')
                ->with('success', "به اشتراک گروهی «{$member->subscription?->plan?->name}» ملحق شدید.");
        } catch (\RuntimeException $e) {
            return redirect()->route('account.subscriptions.index')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * رد دعوت‌نامه توسط کاربر دعوت‌شده.
     */
    public function reject(SubscriptionMember $member)
    {
        $this->requireModule();

        try {
            $this->service->reject($member, auth()->user());
            return redirect()->route('account.subscriptions.index')
                ->with('success', 'دعوت‌نامه رد شد.');
        } catch (\RuntimeException $e) {
            return redirect()->route('account.subscriptions.index')
                ->with('error', $e->getMessage());
        }
    }
}
