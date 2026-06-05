<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\DB;

class SubscriptionDashboardController extends Controller
{
    public function index()
    {
        // آمار کلی
        $stats = [
            'total'          => Subscription::count(),
            'active'         => Subscription::where('status', Subscription::STATUS_ACTIVE)->count(),
            'trial'          => Subscription::where('status', Subscription::STATUS_TRIAL)->count(),
            'cancelled'      => Subscription::where('status', Subscription::STATUS_CANCELLED)->count(),
            'expired'        => Subscription::where('status', Subscription::STATUS_EXPIRED)->count(),
            'expiring_soon'  => Subscription::where('status', Subscription::STATUS_ACTIVE)
                ->whereBetween('ends_at', [now(), now()->addDays(7)])
                ->count(),
        ];

        // درآمد ماه جاری
        $revenueThisMonth = Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->whereMonth('starts_at', now()->month)
            ->whereYear('starts_at', now()->year)
            ->join('subscription_plans', 'subscriptions.subscription_plan_id', '=', 'subscription_plans.id')
            ->sum('subscription_plans.price');

        // درآمد ماه قبل
        $revenueLastMonth = Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->whereMonth('starts_at', now()->subMonth()->month)
            ->whereYear('starts_at', now()->subMonth()->year)
            ->join('subscription_plans', 'subscriptions.subscription_plan_id', '=', 'subscription_plans.id')
            ->sum('subscription_plans.price');

        // اشتراک‌های ۳۰ روز اخیر (برای نمودار)
        $chartData = $this->getChartData();

        // پرفروش‌ترین پلن‌ها
        $topPlans = SubscriptionPlan::withCount(['activeSubscriptions'])
            ->orderByDesc('active_subscriptions_count')
            ->take(5)
            ->get();

        // اشتراک‌های در حال انقضا (۷ روز آینده)
        $expiringSoon = Subscription::with(['user', 'plan'])
            ->where('status', Subscription::STATUS_ACTIVE)
            ->whereBetween('ends_at', [now(), now()->addDays(7)])
            ->orderBy('ends_at')
            ->take(10)
            ->get();

        // آخرین اشتراک‌ها
        $recentSubscriptions = Subscription::with(['user', 'plan'])
            ->latest()
            ->take(8)
            ->get();

        return view('admin.subscriptions.dashboard', compact(
            'stats', 'revenueThisMonth', 'revenueLastMonth',
            'chartData', 'topPlans', 'expiringSoon', 'recentSubscriptions'
        ));
    }

    private function getChartData(): array
    {
        $days   = [];
        $counts = [];
        $revenues = [];

        for ($i = 29; $i >= 0; $i--) {
            $date   = now()->subDays($i)->startOfDay();
            $label  = \App\Helpers\Jalali::formatFa($date, 'm/d');

            $dayCount = Subscription::whereDate('starts_at', $date->toDateString())->count();
            $dayRev   = Subscription::whereDate('starts_at', $date->toDateString())
                ->where('status', '!=', Subscription::STATUS_CANCELLED)
                ->join('subscription_plans', 'subscriptions.subscription_plan_id', '=', 'subscription_plans.id')
                ->sum('subscription_plans.price');

            $days[]     = $label;
            $counts[]   = $dayCount;
            $revenues[] = (int) $dayRev;
        }

        return [
            'labels'   => $days,
            'counts'   => $counts,
            'revenues' => $revenues,
        ];
    }
}
