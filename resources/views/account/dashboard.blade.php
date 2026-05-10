@extends('account.layout')
@section('title', 'خلاصه')
@section('header', 'خلاصه حساب')

@section('content')
<p class="text-slate-600 text-sm mb-4">نمای کلی حساب شما به‌صورت کاشی؛ برای جزئیات روی هر کاشی کلیک کنید.</p>
<div class="account-dashboard-tiles">
    @if(\App\Helpers\SiteHelper::shopEnabled())
        <a href="{{ route('account.orders.index') }}" class="account-dashboard-tile account-dashboard-tile--indigo">
            <span class="account-dashboard-tile__label">تعداد سفارشات</span>
            <span class="account-dashboard-tile__value">{{ number_format($ordersCount) }}</span>
            <span class="account-dashboard-tile__link">مشاهده همه سفارشات ←</span>
        </a>
        <div class="account-dashboard-tile account-dashboard-tile--teal" style="cursor: default;">
            <span class="account-dashboard-tile__label">مجموع خریدها</span>
            <span class="account-dashboard-tile__value">{{ number_format($ordersTotal) }} <span style="font-size:0.75rem;font-weight:600">تومان</span></span>
        </div>
    @endif
    @if(\App\Helpers\SiteHelper::moduleEnabled('subscription'))
        <a href="{{ route('account.subscriptions.index') }}" class="account-dashboard-tile account-dashboard-tile--orange">
            <span class="account-dashboard-tile__label">اشتراک‌های فعال</span>
            <span class="account-dashboard-tile__value">{{ number_format($activeSubscriptionsCount) }}</span>
            <span class="account-dashboard-tile__link">مدیریت اشتراک ←</span>
        </a>
    @endif
    @if(config('account.wallet_enabled', false))
        <div class="account-dashboard-tile account-dashboard-tile--pink" style="cursor: default;">
            <span class="account-dashboard-tile__label">موجودی کیف پول</span>
            <span class="account-dashboard-tile__value">{{ number_format($walletBalance) }} <span style="font-size:0.75rem;font-weight:600">تومان</span></span>
        </div>
    @endif
    <a href="{{ route('account.notifications.index') }}" class="account-dashboard-tile account-dashboard-tile--blue">
        <span class="account-dashboard-tile__label">اعلان خوانده‌نشده</span>
        <span class="account-dashboard-tile__value">{{ number_format($unreadNotificationsCount) }}</span>
        <span class="account-dashboard-tile__link">صندوق اعلان‌ها ←</span>
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    @if(\App\Helpers\SiteHelper::shopEnabled() && $recentOrders->isNotEmpty())
        <div class="account-card">
            <div class="account-card-header">آخرین سفارشات</div>
            <div class="account-card-body">
                <div class="space-y-3">
                    @foreach($recentOrders as $order)
                        <div class="flex items-center justify-between py-2 border-b border-slate-100 last:border-0">
                            <div>
                                <a href="{{ route('account.orders.show', $order) }}" class="font-medium text-slate-800 hover:text-indigo-600">{{ $order->number }}</a>
                                <div class="text-sm text-slate-500">{{ \App\Helpers\Jalali::formatFa($order->created_at, 'Y/m/d') }}</div>
                            </div>
                            <span class="badge badge-{{ $order->status === 'completed' || $order->status === 'paid' ? 'success' : ($order->status === 'cancelled' ? 'danger' : 'warning') }}">{{ $order->status }}</span>
                            <span class="text-slate-600">{{ number_format($order->total) }} تومان</span>
                        </div>
                    @endforeach
                </div>
                <a href="{{ route('account.orders.index') }}" class="btn btn-secondary btn-sm mt-4">همه سفارشات</a>
            </div>
        </div>
    @endif

    @if(\App\Helpers\SiteHelper::moduleEnabled('invoice') && $recentInvoices->isNotEmpty())
        <div class="account-card">
            <div class="account-card-header">آخرین فاکتورها</div>
            <div class="account-card-body">
                <div class="space-y-3">
                    @foreach($recentInvoices as $inv)
                        <div class="flex items-center justify-between py-2 border-b border-slate-100 last:border-0">
                            <div>
                                <a href="{{ route('invoice.show', $inv->invoice_number) }}" class="font-medium text-slate-800 hover:text-indigo-600">{{ $inv->invoice_number }}</a>
                                <div class="text-sm text-slate-500">{{ \App\Helpers\Jalali::formatFa($inv->created_at, 'Y/m/d') }}</div>
                            </div>
                            <span class="badge badge-{{ $inv->status === 'paid' ? 'success' : ($inv->status === 'cancelled' ? 'danger' : 'warning') }}">{{ $inv->status }}</span>
                            <span class="text-slate-600">{{ number_format($inv->total) }} تومان</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    @if($recentNotifications->isNotEmpty())
        <div class="account-card lg:col-span-2">
            <div class="account-card-header">آخرین اعلان‌ها</div>
            <div class="account-card-body">
                <div class="space-y-3">
                    @foreach($recentNotifications as $n)
                        <div class="flex items-center justify-between py-2 border-b border-slate-100 last:border-0 {{ $n->read_at ? 'opacity-75' : '' }}">
                            <div>
                                <span class="font-medium text-slate-800">{{ $n->title }}</span>
                                @if($n->body)
                                    <div class="text-sm text-slate-500 line-clamp-1">{{ Str::limit($n->body, 60) }}</div>
                                @endif
                                <div class="text-xs text-slate-400 mt-1">{{ \App\Helpers\Jalali::formatFa($n->created_at, 'Y/m/d H:i') }}</div>
                            </div>
                            @if(!$n->read_at)
                                <span class="badge badge-info">جدید</span>
                            @endif
                        </div>
                    @endforeach
                </div>
                <a href="{{ route('account.notifications.index') }}" class="btn btn-secondary btn-sm mt-4">همه اعلان‌ها</a>
            </div>
        </div>
    @endif
</div>
@endsection
