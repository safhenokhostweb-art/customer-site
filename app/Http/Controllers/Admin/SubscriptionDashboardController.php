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

        $revenueThisMonth = Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->whereMonth('starts_at', now()->month)
            ->whereYear('starts_at', now()->year)
            ->join('subscription_plans', 'subscriptions.subscription_plan_id', '=', 'subscription_plans.id')
            ->sum('subscription_plans.price');

        $revenueLastMonth = Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->whereMonth('starts_at', now()->subMonth()->month)
            ->whereYear('starts_at', now()->subMonth()->year)
            ->join('subscription_plans', 'subscriptions.subscription_plan_id', '=', 'subscription_plans.id')
            ->sum('subscription_plans.price');

        $mrr = $this->computeMrr();

        $churnData = $this->computeChurn();

        $chartData = $this->getChartData();

        $topPlans = SubscriptionPlan::withCount(['activeSubscriptions'])
            ->orderByDesc('active_subscriptions_count')
            ->take(5)
            ->get();

        $expiringSoon = Subscription::with(['user', 'plan'])
            ->where('status', Subscription::STATUS_ACTIVE)
            ->whereBetween('ends_at', [now(), now()->addDays(7)])
            ->orderBy('ends_at')
            ->take(10)
            ->get();

        $recentSubscriptions = Subscription::with(['user', 'plan'])
            ->latest()
            ->take(8)
            ->get();

        return view('admin.subscriptions.dashboard', compact(
            'stats', 'revenueThisMonth', 'revenueLastMonth',
            'mrr', 'churnData',
            'chartData', 'topPlans', 'expiringSoon', 'recentSubscriptions'
        ));
    }

    /**
     * MRR — مجموع درآمد ماهانه‌سازی شده از تمام اشتراک‌های فعال.
     * درآمد هر اشتراک به ماه تبدیل می‌شود.
     */
    private function computeMrr(): float
    {
        $activeSubs = Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->with('plan')
            ->get();

        $mrr = 0.0;

        foreach ($activeSubs as $sub) {
            $plan = $sub->plan;
            if (! $plan || (float) $plan->price <= 0) {
                continue;
            }

            $daysInPlan = max(1, $plan->getDurationInDays());
            // تبدیل قیمت پلن به ماهانه (30 روز)
            $monthlyEquivalent = ((float) $plan->price / $daysInPlan) * 30;
            $mrr += $monthlyEquivalent;
        }

        return round($mrr);
    }

    /**
     * Churn rate ماه جاری:
     * (لغوشده + منقضی این ماه) / (فعال ابتدای ماه) × ۱۰۰
     */
    private function computeChurn(): array
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth   = now()->endOfMonth();

        // اشتراک‌هایی که این ماه لغو یا منقضی شدند
        $churned = Subscription::whereIn('status', [
                Subscription::STATUS_CANCELLED,
                Subscription::STATUS_EXPIRED,
            ])
            ->where('updated_at', '>=', $startOfMonth)
            ->count();

        // اشتراک‌های فعال در ابتدای ماه (شروع اشتراک قبل از ابتدای ماه و هنوز فعال)
        $activeAtStart = Subscription::where('starts_at', '<', $startOfMonth)
            ->whereIn('status', [
                Subscription::STATUS_ACTIVE,
                Subscription::STATUS_CANCELLED,
                Subscription::STATUS_EXPIRED,
            ])
            ->count();

        $rate = $activeAtStart > 0
            ? round(($churned / $activeAtStart) * 100, 1)
            : 0.0;

        // churn ماه قبل برای مقایسه
        $prevStart   = now()->subMonth()->startOfMonth();
        $prevEnd     = now()->subMonth()->endOfMonth();
        $churnedPrev = Subscription::whereIn('status', [
                Subscription::STATUS_CANCELLED,
                Subscription::STATUS_EXPIRED,
            ])
            ->where('updated_at', '>=', $prevStart)
            ->where('updated_at', '<=', $prevEnd)
            ->count();

        $activeAtStartPrev = Subscription::where('starts_at', '<', $prevStart)
            ->whereIn('status', [
                Subscription::STATUS_ACTIVE,
                Subscription::STATUS_CANCELLED,
                Subscription::STATUS_EXPIRED,
            ])
            ->count();

        $ratePrev = $activeAtStartPrev > 0
            ? round(($churnedPrev / $activeAtStartPrev) * 100, 1)
            : 0.0;

        return [
            'churned'      => $churned,
            'rate'         => $rate,
            'rate_prev'    => $ratePrev,
            'trend'        => $rate <= $ratePrev ? 'good' : 'bad',
        ];
    }

    private function getChartData(): array
    {
        $days     = [];
        $counts   = [];
        $revenues = [];

        for ($i = 29; $i >= 0; $i--) {
            $date  = now()->subDays($i)->startOfDay();
            $label = \App\Helpers\Jalali::formatFa($date, 'm/d');

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
