@extends('account.layout')
@section('title', 'خلاصه')
@section('header', 'خلاصه حساب')

@push('head')
<style>
/* ─── Glass Cards ─── */
.ug-card{position:relative;background:rgba(255,255,255,0.78);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,0.32);box-shadow:0 6px 28px rgba(15,23,42,0.07);border-radius:1rem;overflow:hidden}
.ug-card:hover{box-shadow:0 10px 40px rgba(15,23,42,0.11)}

/* ─── Account Tiles ─── */
.ua-tile{position:relative;display:flex;flex-direction:column;gap:0.4rem;overflow:hidden;border-radius:1rem;padding:1.25rem 1.25rem;box-shadow:0 8px 24px rgba(15,23,42,0.1);transition:all .3s;color:#fff!important;text-decoration:none;border:1px solid rgba(255,255,255,0.15)}
.ua-tile:hover{transform:translateY(-5px) scale(1.02);box-shadow:0 18px 40px rgba(15,23,42,0.18);text-decoration:none}
.ua-tile::before{content:'';position:absolute;top:-25%;right:-15%;width:100px;height:100px;border-radius:50%;background:rgba(255,255,255,0.07);pointer-events:none}
.ua-tile__icon{font-size:1.6rem;line-height:1;opacity:0.9}
.ua-tile__label{font-size:0.75rem;font-weight:600;opacity:0.82;text-transform:uppercase;letter-spacing:0.04em}
.ua-tile__value{font-size:1.9rem;font-weight:800;line-height:1.1}
.ua-tile__hint{font-size:0.7rem;opacity:0.75}
.ua-tile--indigo{background:linear-gradient(135deg,#6366f1,#1e1b4b)}
.ua-tile--teal{background:linear-gradient(135deg,#0d9488,#042f2e)}
.ua-tile--orange{background:linear-gradient(135deg,#ea580c,#431407)}
.ua-tile--blue{background:linear-gradient(135deg,#2563eb,#1e3a5f)}
.ua-tile--violet{background:linear-gradient(135deg,#7c3aed,#2e1065)}
.ua-tile--rose{background:linear-gradient(135deg,#e11d48,#4c0519)}
.ua-tile--pink{background:linear-gradient(135deg,#db2777,#500724)}
.ua-tile--emerald{background:linear-gradient(135deg,#059669,#022c22)}

/* ─── Quick Actions ─── */
.ua-action{display:flex;align-items:center;gap:0.75rem;padding:0.7rem 1rem;border-radius:0.875rem;border:1px solid #e2e8f0;background:#fff;box-shadow:0 1px 4px rgba(15,23,42,0.05);transition:all .2s;text-decoration:none;color:#1e293b}
.ua-action:hover{border-color:#a5b4fc;box-shadow:0 4px 14px rgba(99,102,241,0.1);transform:translateY(-1px);text-decoration:none}
.ua-action__icon{display:flex;align-items:center;justify-content:center;width:2.25rem;height:2.25rem;border-radius:0.5rem;flex-shrink:0;font-size:1rem}

/* ─── pulse dot ─── */
.pulse-dot{display:inline-block;width:8px;height:8px;border-radius:50%;animation:pulse-anim 2s infinite}
@keyframes pulse-anim{0%,100%{opacity:1;transform:scale(1)}50%{opacity:0.6;transform:scale(1.2)}}
</style>
@endpush

@section('content')

@php
    $openTicket = \SaaSPlatform\Modules\Tickets\Models\Ticket::withoutTenantIsolation()
        ->whereNull('site_id')
        ->where('customer_id', auth()->id())
        ->whereIn('status', ['open', 'in_progress'])
        ->orderByDesc('updated_at')
        ->first(['id', 'ticket_number', 'subject', 'status', 'updated_at']);
@endphp

{{-- ═══ بنر خوش‌آمدید ═══ --}}
<div class="ug-card mb-7">
    <div class="absolute inset-0 bg-linear-to-l from-indigo-500/8 via-sky-400/4 to-transparent pointer-events-none"></div>
    <div class="relative z-10 p-6 sm:p-8 flex flex-col sm:flex-row sm:items-center gap-5">
        <div class="w-14 h-14 rounded-2xl flex items-center justify-center shrink-0"
             style="background:linear-gradient(135deg,#7c3aed,#2563eb)">
            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
        </div>
        <div class="flex-1">
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">
                خوش آمدید، <span class="bg-linear-to-l from-violet-600 to-indigo-600 bg-clip-text text-transparent">{{ auth()->user()->name ?? 'کاربر عزیز' }}</span> 👋
            </h2>
            <p class="text-gray-500 text-sm mt-1.5">نمای کلی حساب شما — از کاشی‌ها یا میانبرها برای دسترسی سریع استفاده کنید.</p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <span class="pulse-dot bg-green-500"></span>
            <span class="text-sm font-medium text-green-700">حساب فعال</span>
        </div>
    </div>
</div>

{{-- ═══ بنر تیکت باز ═══ --}}
@if($openTicket)
<div class="mb-6 flex items-center gap-3 p-4 rounded-xl border"
     style="background:linear-gradient(135deg,#eef2ff,#e0e7ff);border-color:#c7d2fe;">
    <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0"
         style="background:linear-gradient(135deg,#4f46e5,#7c3aed)">
        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
    </div>
    <div class="flex-1 min-w-0">
        <p class="text-sm font-semibold text-indigo-800">مکالمه فعال: {{ $openTicket->subject }}</p>
        <p class="text-xs text-indigo-500 mt-0.5">آخرین فعالیت {{ $openTicket->updated_at->locale('fa')->diffForHumans() }}</p>
    </div>
    <a href="{{ route('account.tickets.show', $openTicket->id) }}"
       style="color:#fff;flex-shrink:0;display:inline-flex;align-items:center;gap:0.375rem;background:linear-gradient(135deg,#4f46e5,#7c3aed);padding:0.45rem 1rem;border-radius:0.5rem;font-size:0.75rem;font-weight:700;text-decoration:none;box-shadow:0 2px 8px rgba(79,70,229,0.35);transition:box-shadow .2s"
       onmouseover="this.style.boxShadow='0 4px 16px rgba(79,70,229,0.5)'" onmouseout="this.style.boxShadow='0 2px 8px rgba(79,70,229,0.35)'">
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
        ادامه چت
    </a>
</div>
@else
<div class="mb-6 flex items-center gap-3 p-4 rounded-xl border border-slate-200 bg-white">
    <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 bg-slate-100">
        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
    </div>
    <div class="flex-1 min-w-0">
        <p class="text-sm font-semibold text-slate-700">سوال یا مشکلی دارید؟</p>
        <p class="text-xs text-slate-400 mt-0.5">تیم پشتیبانی در کوتاه‌ترین زمان پاسخ می‌دهد</p>
    </div>
    <a href="{{ route('account.tickets.create') }}"
       class="shrink-0 flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-4 py-2 rounded-lg transition-colors">
        شروع چت +
    </a>
</div>
@endif

{{-- ═══ کاشی‌های آمار ═══ --}}
<div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    @if(\App\Helpers\SiteHelper::shopEnabled())
        <a href="{{ route('account.orders.index') }}" class="ua-tile ua-tile--indigo">
            <span class="ua-tile__icon">🛒</span>
            <span class="ua-tile__label">سفارشات</span>
            <span class="ua-tile__value">{{ number_format($ordersCount) }}</span>
            <span class="ua-tile__hint">مشاهده همه ←</span>
        </a>
        <div class="ua-tile ua-tile--teal" style="cursor:default">
            <span class="ua-tile__icon">💰</span>
            <span class="ua-tile__label">مجموع خریدها</span>
            <span class="ua-tile__value" style="font-size:1.3rem">{{ number_format($ordersTotal) }}</span>
            <span class="ua-tile__hint">تومان</span>
        </div>
    @endif
    @if(\App\Helpers\SiteHelper::moduleEnabled('subscription'))
        <a href="{{ route('account.subscriptions.index') }}" class="ua-tile ua-tile--orange">
            <span class="ua-tile__icon">⭐</span>
            <span class="ua-tile__label">اشتراک‌های فعال</span>
            <span class="ua-tile__value">{{ number_format($activeSubscriptionsCount) }}</span>
            <span class="ua-tile__hint">مدیریت اشتراک ←</span>
        </a>
    @endif
    @if(config('account.wallet_enabled', false))
        <div class="ua-tile ua-tile--pink" style="cursor:default">
            <span class="ua-tile__icon">👛</span>
            <span class="ua-tile__label">موجودی کیف پول</span>
            <span class="ua-tile__value" style="font-size:1.3rem">{{ number_format($walletBalance) }}</span>
            <span class="ua-tile__hint">تومان</span>
        </div>
    @endif
    <a href="{{ route('account.notifications.index') }}" class="ua-tile ua-tile--blue">
        <span class="ua-tile__icon">🔔</span>
        <span class="ua-tile__label">اعلان‌ها</span>
        <span class="ua-tile__value">{{ number_format($unreadNotificationsCount) }}</span>
        <span class="ua-tile__hint">خوانده‌نشده ←</span>
    </a>
    @if(isset($pointsBalance))
    <a href="{{ route('account.points.index') }}" class="ua-tile ua-tile--violet">
        <span class="ua-tile__icon">✨</span>
        <span class="ua-tile__label">امتیاز من</span>
        <span class="ua-tile__value">{{ \App\Helpers\NumberHelper::formatFa($pointsBalance) }}</span>
        <span class="ua-tile__hint">تاریخچه و مصرف ←</span>
    </a>
    @endif
</div>

{{-- ═══ دسترسی سریع ═══ --}}
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 mb-8">
    <a href="{{ route('account.profile.index') }}" class="ua-action">
        <span class="ua-action__icon bg-indigo-50 text-indigo-600">👤</span>
        <span class="text-sm font-600 text-slate-700">پروفایل من</span>
    </a>
    <a href="{{ route('account.tickets.index') }}" class="ua-action">
        <span class="ua-action__icon bg-violet-50 text-violet-600">💬</span>
        <span class="text-sm font-600 text-slate-700">تیکت‌های پشتیبانی</span>
    </a>
    <a href="{{ route('account.notifications.index') }}" class="ua-action">
        <span class="ua-action__icon bg-blue-50 text-blue-600">🔔</span>
        <span class="text-sm font-600 text-slate-700">صندوق اعلان‌ها</span>
    </a>
    <a href="{{ route('account.security.index') }}" class="ua-action">
        <span class="ua-action__icon bg-slate-50 text-slate-600">🔒</span>
        <span class="text-sm font-600 text-slate-700">امنیت حساب</span>
    </a>
    @if(\App\Helpers\SiteHelper::shopEnabled())
    <a href="{{ route('account.orders.index') }}" class="ua-action">
        <span class="ua-action__icon bg-amber-50 text-amber-600">🛒</span>
        <span class="text-sm font-600 text-slate-700">سفارشات من</span>
    </a>
    @endif
    @if(\App\Helpers\SiteHelper::moduleEnabled('subscription'))
    <a href="{{ route('account.subscriptions.index') }}" class="ua-action">
        <span class="ua-action__icon bg-orange-50 text-orange-600">⭐</span>
        <span class="text-sm font-600 text-slate-700">اشتراک‌ها</span>
    </a>
    @endif
    <a href="{{ route('account.tickets.create') }}" class="ua-action">
        <span class="ua-action__icon bg-emerald-50 text-emerald-600">✉️</span>
        <span class="text-sm font-600 text-slate-700">تیکت جدید</span>
    </a>
    <a href="{{ url('/') }}" class="ua-action">
        <span class="ua-action__icon bg-teal-50 text-teal-600">🌐</span>
        <span class="text-sm font-600 text-slate-700">مشاهده سایت</span>
    </a>
</div>

{{-- ═══ آخرین سفارشات + فاکتورها + اعلان‌ها ═══ --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    @if(\App\Helpers\SiteHelper::shopEnabled() && $recentOrders->isNotEmpty())
        <div class="ug-card">
            <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-2">
                <span class="text-amber-500">🛒</span>
                <span class="font-semibold text-slate-800">آخرین سفارشات</span>
            </div>
            <div class="p-5">
                <div class="space-y-3">
                    @foreach($recentOrders as $order)
                        <div class="flex items-center justify-between py-2 border-b border-slate-100 last:border-0">
                            <div>
                                <a href="{{ route('account.orders.show', $order) }}" class="font-medium text-slate-800 hover:text-indigo-600">{{ $order->number }}</a>
                                <div class="text-xs text-slate-500">{{ \App\Helpers\Jalali::formatFa($order->created_at, 'Y/m/d') }}</div>
                            </div>
                            <span class="badge badge-{{ $order->status === 'completed' || $order->status === 'paid' ? 'success' : ($order->status === 'cancelled' ? 'danger' : 'warning') }}">{{ $order->status }}</span>
                            <span class="text-slate-600 text-sm">{{ number_format($order->total) }} تومان</span>
                        </div>
                    @endforeach
                </div>
                <a href="{{ route('account.orders.index') }}" class="btn btn-secondary btn-sm mt-4">همه سفارشات</a>
            </div>
        </div>
    @endif

    @if(\App\Helpers\SiteHelper::moduleEnabled('invoice') && $recentInvoices->isNotEmpty())
        <div class="ug-card">
            <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-2">
                <span class="text-teal-500">📋</span>
                <span class="font-semibold text-slate-800">آخرین فاکتورها</span>
            </div>
            <div class="p-5">
                <div class="space-y-3">
                    @foreach($recentInvoices as $inv)
                        <div class="flex items-center justify-between py-2 border-b border-slate-100 last:border-0">
                            <div>
                                <a href="{{ route('invoice.show', $inv->invoice_number) }}" class="font-medium text-slate-800 hover:text-indigo-600">{{ $inv->invoice_number }}</a>
                                <div class="text-xs text-slate-500">{{ \App\Helpers\Jalali::formatFa($inv->created_at, 'Y/m/d') }}</div>
                            </div>
                            <span class="badge badge-{{ $inv->status === 'paid' ? 'success' : ($inv->status === 'cancelled' ? 'danger' : 'warning') }}">{{ $inv->status }}</span>
                            <span class="text-slate-600 text-sm">{{ number_format($inv->total) }} تومان</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    @if($recentNotifications->isNotEmpty())
        <div class="ug-card lg:col-span-2">
            <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-2">
                <span class="text-blue-500">🔔</span>
                <span class="font-semibold text-slate-800">آخرین اعلان‌ها</span>
                <a href="{{ route('account.notifications.index') }}" class="mr-auto text-sm text-indigo-600 hover:underline">همه اعلان‌ها ←</a>
            </div>
            <div class="p-5">
                <div class="space-y-3">
                    @foreach($recentNotifications as $n)
                        <div class="flex items-center justify-between py-2 border-b border-slate-100 last:border-0 {{ $n->read_at ? 'opacity-70' : '' }}">
                            <div>
                                <span class="font-medium text-slate-800">{{ $n->title }}</span>
                                @if($n->body)
                                    <div class="text-sm text-slate-500 line-clamp-1">{{ Str::limit($n->body, 60) }}</div>
                                @endif
                                <div class="text-xs text-slate-400 mt-0.5">{{ \App\Helpers\Jalali::formatFa($n->created_at, 'Y/m/d H:i') }}</div>
                            </div>
                            @if(!$n->read_at)
                                <span class="badge badge-info">جدید</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>

@endsection
