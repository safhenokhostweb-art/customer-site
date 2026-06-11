@extends('admin.layout')
@section('title', 'حسابداری')
@section('header', 'حسابداری')
@push('head')
<style>
.acc-kpi{padding:1.125rem 1.375rem;border-radius:1rem;background:#fff;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(15,23,42,.06)}
.acc-kpi--revenue{background:linear-gradient(135deg,#6366f1,#1e1b4b);border:none;color:#fff}
.acc-kpi--revenue .acc-kpi__label,.acc-kpi--revenue .acc-kpi__value,.acc-kpi--revenue .acc-kpi__sub{color:#fff}
.acc-kpi--revenue .acc-kpi__label{opacity:.8}
.acc-kpi__label{font-size:.72rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:.35rem}
.acc-kpi__value{font-size:1.75rem;font-weight:800;color:#1e293b;line-height:1.1}
.acc-kpi__sub{font-size:.72rem;color:#94a3b8;margin-top:.3rem}

.bar-chart-wrap{display:flex;align-items:flex-end;gap:.5rem;height:120px;padding:0 .25rem}
.bar-col{display:flex;flex-direction:column;align-items:center;gap:.3rem;flex:1}
.bar-col__label{font-size:.62rem;color:#94a3b8;text-align:center;white-space:nowrap}
.bar-col__bar-wrap{flex:1;display:flex;flex-direction:column;justify-content:flex-end;width:100%}
.bar-col__bar{width:100%;border-radius:.25rem .25rem 0 0;background:linear-gradient(180deg,#6366f1,#4f46e5);min-height:4px;transition:height .5s}
.bar-col__val{font-size:.58rem;color:#475569;font-weight:600;text-align:center}
</style>
@endpush
@section('content')

@include('admin.partials.breadcrumb', ['crumbs' => [
    ['label' => 'داشبورد', 'url' => route('admin.dashboard')],
    ['label' => 'حسابداری', 'url' => null],
]])

<div class="admin-page">

    {{-- Module Status --}}
    @if(!$enabled)
        <div style="background:#fef9c3;border:1px solid #fde68a;border-radius:.875rem;padding:1rem 1.25rem;margin-bottom:1.5rem;color:#92400e;font-size:.875rem">
            <strong>ماژول حسابداری برای این سایت فعال نیست.</strong>
            در پنل پلتفرم، ماژول <code>accounting</code> را برای این سایت فعال کنید.
        </div>
    @endif

    {{-- Main KPIs --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:1rem;margin-bottom:1.5rem">
        <div class="acc-kpi acc-kpi--revenue">
            <div class="acc-kpi__label">درآمد کل</div>
            <div class="acc-kpi__value">{{ \App\Helpers\NumberHelper::formatFa((int)$totalRevenue) }}</div>
            <div class="acc-kpi__sub">تومان — سفارش‌های تکمیل‌شده</div>
        </div>
        <div class="acc-kpi">
            <div class="acc-kpi__label">کل سفارش‌ها</div>
            <div class="acc-kpi__value">{{ \App\Helpers\NumberHelper::formatFa($ordersCount) }}</div>
            <div class="acc-kpi__sub">{{ \App\Helpers\NumberHelper::formatFa($paidOrdersCount) }} پرداخت شده</div>
        </div>
        <div class="acc-kpi">
            <div class="acc-kpi__label">در انتظار پرداخت</div>
            <div class="acc-kpi__value" style="color:#d97706">{{ \App\Helpers\NumberHelper::formatFa($pendingCount) }}</div>
            <div class="acc-kpi__sub">نیاز به پیگیری</div>
        </div>
        <div class="acc-kpi">
            <div class="acc-kpi__label">لغو شده</div>
            <div class="acc-kpi__value" style="color:#dc2626">{{ \App\Helpers\NumberHelper::formatFa($cancelledCount) }}</div>
            <div class="acc-kpi__sub">از کل سفارش‌ها</div>
        </div>
        <div class="acc-kpi">
            <div class="acc-kpi__label">فاکتورها</div>
            <div class="acc-kpi__value">{{ \App\Helpers\NumberHelper::formatFa($invoicesCount) }}</div>
            <div class="acc-kpi__sub">{{ \App\Helpers\NumberHelper::formatFa($paidInvoicesCount) }} پرداخت شده</div>
        </div>
        <div class="acc-kpi">
            <div class="acc-kpi__label">درآمد فاکتور</div>
            <div class="acc-kpi__value" style="color:#059669">{{ \App\Helpers\NumberHelper::formatFa((int)$invoiceTotal) }}</div>
            <div class="acc-kpi__sub">تومان</div>
        </div>
    </div>

    {{-- Monthly Revenue Chart --}}
    @if($monthlyRevenue->count() > 1)
    <div class="card" style="padding:1.25rem;margin-bottom:1.5rem">
        <div style="font-size:.95rem;font-weight:700;color:#1e293b;margin-bottom:1.25rem;display:flex;align-items:center;gap:.5rem">
            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            درآمد ماهانه (۶ ماه اخیر)
        </div>
        @php $maxRev = $monthlyRevenue->max('revenue') ?: 1; @endphp
        <div class="bar-chart-wrap">
            @foreach($monthlyRevenue as $m)
                @php
                    $pct = round($m->revenue / $maxRev * 100);
                    $monthLabel = \Carbon\Carbon::createFromFormat('Y-m', $m->month)->format('M');
                @endphp
                <div class="bar-col">
                    <div class="bar-col__val">{{ \App\Helpers\NumberHelper::formatFa((int)($m->revenue / 1000)) }}ک</div>
                    <div class="bar-col__bar-wrap">
                        <div class="bar-col__bar" style="height:{{ $pct }}%"></div>
                    </div>
                    <div class="bar-col__label">{{ $m->month }}</div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Quick Links --}}
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <a href="{{ route('admin.orders.index') }}" class="card" style="padding:1rem 1.25rem;display:flex;align-items:center;gap:.75rem;text-decoration:none;color:#1e293b;transition:all .2s">
            <div style="width:2.5rem;height:2.5rem;border-radius:.625rem;background:#ede9fe;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            </div>
            <div>
                <div style="font-size:.88rem;font-weight:700">مدیریت سفارشات</div>
                <div style="font-size:.75rem;color:#64748b">مشاهده و تغییر وضعیت سفارش‌ها</div>
            </div>
        </a>
        <a href="{{ route('admin.invoices.index') }}" class="card" style="padding:1rem 1.25rem;display:flex;align-items:center;gap:.75rem;text-decoration:none;color:#1e293b;transition:all .2s">
            <div style="width:2.5rem;height:2.5rem;border-radius:.625rem;background:#dbeafe;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div>
                <div style="font-size:.88rem;font-weight:700">فاکتورها</div>
                <div style="font-size:.75rem;color:#64748b">مشاهده و دانلود فاکتور PDF</div>
            </div>
        </a>
    </div>

    <p class="text-xs text-slate-400 mt-4">
        داده‌های این صفحه بر اساس سفارش‌ها و فاکتورهای ثبت‌شده محاسبه می‌شوند و تغییری در آنها ایجاد نمی‌کنند.
    </p>
</div>
@endsection
