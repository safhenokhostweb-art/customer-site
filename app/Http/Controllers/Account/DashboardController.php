<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Helpers\SiteHelper;
use App\Models\Order;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\UserNotification;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = auth()->user();

        $ordersCount = 0;
        $ordersTotal = 0;
        $activeSubscriptionsCount = 0;
        $walletBalance = 0;
        $unreadNotificationsCount = 0;

        if (SiteHelper::shopEnabled()) {
            $ordersQuery = Order::where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('customer_email', $user->email);
            });
            $ordersCount = $ordersQuery->count();
            $ordersTotal = (float) $ordersQuery->sum('total');
        }

        if (SiteHelper::moduleEnabled('subscription')) {
            $activeSubscriptionsCount = Subscription::where('user_id', $user->id)
                ->where('status', 'active')
                ->where('ends_at', '>', now())
                ->count();
        }

        if (config('account.wallet_enabled', false)) {
            $walletBalance = (float) ($user->wallet_balance ?? 0);
        }

        $unreadNotificationsCount = UserNotification::where('user_id', $user->id)->unread()->count();

        $recentOrders = SiteHelper::shopEnabled()
            ? Order::where(function ($q) use ($user) {
                $q->where('user_id', $user->id)->orWhere('customer_email', $user->email);
            })->latest()->take(5)->get()
            : collect();

        $recentInvoices = SiteHelper::moduleEnabled('invoice')
            ? Invoice::where(function ($q) use ($user) {
                $q->where('user_id', $user->id)->orWhere('customer_email', $user->email);
            })->latest()->take(5)->get()
            : collect();

        $recentNotifications = UserNotification::where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        return view('account.dashboard', compact(
            'ordersCount',
            'ordersTotal',
            'activeSubscriptionsCount',
            'walletBalance',
            'unreadNotificationsCount',
            'recentOrders',
            'recentInvoices',
            'recentNotifications'
        ));
    }
}
