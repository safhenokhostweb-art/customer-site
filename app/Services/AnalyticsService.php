<?php

namespace App\Services;

use App\Models\AnalyticsPageView;
use App\Models\User;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\SupportConversation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    public function kpis(): array
    {
        $today      = now()->startOfDay();
        $thisMonth  = now()->startOfMonth();
        $lastMonth  = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();

        $totalUsers    = User::count();
        $newUsersMonth = User::where('created_at', '>=', $thisMonth)->count();

        $revenueMonth = Order::where('status', 'completed')
            ->where('created_at', '>=', $thisMonth)
            ->sum('total');

        $revenueLastMonth = Order::where('status', 'completed')
            ->whereBetween('created_at', [$lastMonth, $lastMonthEnd])
            ->sum('total');

        $totalOrders = Order::where('created_at', '>=', $thisMonth)->count();

        $activeSubscriptions = Subscription::where('status', 'active')->count();

        $openTickets = SupportConversation::whereIn('status', ['open', 'assigned'])->count();

        $pageViewsToday = AnalyticsPageView::where('visited_at', '>=', $today)->count();
        $pageViewsMonth = AnalyticsPageView::where('visited_at', '>=', $thisMonth)->count();

        return [
            'total_users'          => $totalUsers,
            'new_users_month'      => $newUsersMonth,
            'revenue_month'        => $revenueMonth,
            'revenue_last_month'   => $revenueLastMonth,
            'revenue_change_pct'   => $revenueLastMonth > 0
                ? round((($revenueMonth - $revenueLastMonth) / $revenueLastMonth) * 100, 1)
                : ($revenueMonth > 0 ? 100 : 0),
            'total_orders_month'   => $totalOrders,
            'active_subscriptions' => $activeSubscriptions,
            'open_tickets'         => $openTickets,
            'page_views_today'     => $pageViewsToday,
            'page_views_month'     => $pageViewsMonth,
        ];
    }

    /** Revenue per day for the last N days */
    public function revenueChart(int $days = 30): array
    {
        $rows = Order::where('status', 'completed')
            ->where('created_at', '>=', now()->subDays($days - 1)->startOfDay())
            ->selectRaw('DATE(created_at) as day, SUM(total) as revenue, COUNT(*) as orders')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $labels  = [];
        $revenue = [];
        $orders  = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $labels[]  = now()->subDays($i)->format('m/d');
            $revenue[] = $rows[$date]->revenue ?? 0;
            $orders[]  = $rows[$date]->orders  ?? 0;
        }

        return compact('labels', 'revenue', 'orders');
    }

    /** User registrations per day for last N days */
    public function userRegistrationsChart(int $days = 30): array
    {
        $rows = User::where('created_at', '>=', now()->subDays($days - 1)->startOfDay())
            ->selectRaw('DATE(created_at) as day, COUNT(*) as count')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $labels = [];
        $counts = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date     = now()->subDays($i)->toDateString();
            $labels[] = now()->subDays($i)->format('m/d');
            $counts[] = $rows[$date]->count ?? 0;
        }

        return ['labels' => $labels, 'counts' => $counts];
    }

    /** Page views per day for last N days */
    public function pageViewsChart(int $days = 14): array
    {
        $rows = AnalyticsPageView::where('visited_at', '>=', now()->subDays($days - 1)->startOfDay())
            ->selectRaw('DATE(visited_at) as day, COUNT(*) as views, COUNT(DISTINCT session_id) as sessions')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $labels   = [];
        $views    = [];
        $sessions = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date       = now()->subDays($i)->toDateString();
            $labels[]   = now()->subDays($i)->format('m/d');
            $views[]    = $rows[$date]->views    ?? 0;
            $sessions[] = $rows[$date]->sessions ?? 0;
        }

        return compact('labels', 'views', 'sessions');
    }

    /** Top pages by view count */
    public function topPages(int $limit = 10): array
    {
        return AnalyticsPageView::where('visited_at', '>=', now()->subDays(30))
            ->selectRaw('path, COUNT(*) as views, COUNT(DISTINCT session_id) as sessions')
            ->groupBy('path')
            ->orderByDesc('views')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /** Device split for last 30 days */
    public function deviceSplit(): array
    {
        return AnalyticsPageView::where('visited_at', '>=', now()->subDays(30))
            ->selectRaw('device, COUNT(*) as count')
            ->groupBy('device')
            ->orderByDesc('count')
            ->get()
            ->toArray();
    }

    /** Top products by order count */
    public function topProducts(int $limit = 5): array
    {
        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.status', 'completed')
            ->where('orders.created_at', '>=', now()->subDays(30))
            ->selectRaw('products.title, products.id, SUM(order_items.quantity) as qty, SUM(order_items.total) as revenue')
            ->groupBy('products.id', 'products.title')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /** Recent support tickets summary */
    public function ticketSummary(): array
    {
        $statuses = SupportConversation::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'open'     => $statuses['open']     ?? 0,
            'pending'  => $statuses['assigned'] ?? 0,
            'resolved' => $statuses['archived'] ?? 0,
            'closed'   => $statuses['closed']   ?? 0,
        ];
    }
}
