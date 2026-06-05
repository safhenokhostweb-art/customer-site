@extends('admin.layout')
@section('title', 'داشبورد اشتراک‌ها')

@section('content')
<div class="sub-dash">

{{-- ══════════════════════════════════════════════════
     هدر صفحه
══════════════════════════════════════════════════ --}}
<div class="sub-dash__head">
    <div>
        <h1 class="sub-dash__title">داشبورد اشتراک‌ها</h1>
        <p class="sub-dash__subtitle">آمار و مدیریت پلن‌های عضویت کاربران</p>
    </div>
    <div class="sub-dash__head-actions">
        <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-secondary btn-sm">
            <svg class="sub-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0l-4-4m4 4l-4 4"/></svg>
            مدیریت پلن‌ها
        </a>
        <a href="{{ route('admin.subscribers.pending') }}" class="btn btn-secondary btn-sm">
            <svg class="sub-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            در انتظار تأیید
        </a>
        <a href="{{ route('admin.subscribers.assign.form') }}" class="btn btn-secondary btn-sm">
            <svg class="sub-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            ثبت دستی
        </a>
        <a href="{{ route('admin.subscribers.index') }}" class="btn btn-primary btn-sm">
            <svg class="sub-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/></svg>
            لیست مشترکین
        </a>
    </div>
</div>


{{-- ══════════════════════════════════════════════════
     کارت‌های آماری ۶ تایی
══════════════════════════════════════════════════ --}}
<div class="sub-stats-grid">

    @php
    $statItems = [
        ['label'=>'کل اشتراک‌ها',       'value'=>$stats['total'],        'gradient'=>'sub-grad-indigo', 'icon'=>'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
        ['label'=>'فعال',                'value'=>$stats['active'],       'gradient'=>'sub-grad-emerald','icon'=>'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['label'=>'دوره آزمایشی',        'value'=>$stats['trial'],        'gradient'=>'sub-grad-sky',    'icon'=>'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['label'=>'لغو شده',             'value'=>$stats['cancelled'],    'gradient'=>'sub-grad-rose',   'icon'=>'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['label'=>'منقضی',               'value'=>$stats['expired'],      'gradient'=>'sub-grad-slate',  'icon'=>'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['label'=>'رو به انقضا (۷ روز)','value'=>$stats['expiring_soon'],'gradient'=>'sub-grad-amber',  'icon'=>'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
    ];
    @endphp

    @foreach($statItems as $item)
    <div class="sub-stat-card {{ $item['gradient'] }}">
        <div class="sub-stat-card__icon">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="22" height="22">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/>
            </svg>
        </div>
        <div class="sub-stat-card__body">
            <span class="sub-stat-card__label">{{ $item['label'] }}</span>
            <span class="sub-stat-card__value">{{ \App\Helpers\NumberHelper::formatFa($item['value']) }}</span>
        </div>
    </div>
    @endforeach

</div>

{{-- ══════════════════════════════════════════════════
     درآمد ماه جاری / قبل
══════════════════════════════════════════════════ --}}
<div class="sub-revenue-row">

    <div class="sub-revenue-card sub-revenue-card--current">
        <div class="sub-revenue-card__label">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            درآمد ماه جاری
        </div>
        <div class="sub-revenue-card__amount">
            {{ \App\Helpers\NumberHelper::formatFa($revenueThisMonth) }}
            <small>تومان</small>
        </div>
    </div>

    <div class="sub-revenue-card sub-revenue-card--last">
        <div class="sub-revenue-card__label">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            درآمد ماه قبل
        </div>
        <div class="sub-revenue-card__amount sub-revenue-card__amount--muted">
            {{ \App\Helpers\NumberHelper::formatFa($revenueLastMonth) }}
            <small>تومان</small>
        </div>
    </div>

</div>

{{-- ══════════════════════════════════════════════════
     نمودار ۳۰ روز اخیر
══════════════════════════════════════════════════ --}}
<div class="sub-chart-card">
    <div class="sub-chart-card__header">
        <h2 class="sub-chart-card__title">اشتراک‌های ۳۰ روز اخیر</h2>
    </div>
    <div class="sub-chart-card__body">
        <canvas id="subChart" style="height:220px;max-height:220px;width:100%"></canvas>
    </div>
</div>

{{-- ══════════════════════════════════════════════════
     پرفروش‌ترین پلن‌ها + رو به انقضا
══════════════════════════════════════════════════ --}}
<div class="sub-two-col">

    {{-- پرفروش‌ترین --}}
    <div class="sub-panel">
        <div class="sub-panel__header">
            <h2 class="sub-panel__title">پرفروش‌ترین پلن‌ها</h2>
        </div>
        <div class="sub-panel__body">
            @forelse($topPlans as $plan)
            <div class="sub-plan-row">
                <div class="sub-plan-row__dot" style="background:{{ $plan->color ?? '#4f46e5' }}"></div>
                <span class="sub-plan-row__name">{{ $plan->name }}</span>
                <span class="sub-plan-row__count">
                    {{ \App\Helpers\NumberHelper::formatFa($plan->active_subscriptions_count) }} مشترک
                </span>
            </div>
            @empty
            <p class="sub-empty">هنوز اشتراکی ثبت نشده.</p>
            @endforelse
        </div>
    </div>

    {{-- رو به انقضا --}}
    <div class="sub-panel">
        <div class="sub-panel__header">
            <h2 class="sub-panel__title">در حال انقضا (۷ روز آینده)</h2>
        </div>
        <div class="sub-panel__body">
            @forelse($expiringSoon as $sub)
            <div class="sub-expire-row">
                <div>
                    <p class="sub-expire-row__name">{{ $sub->user->name ?? $sub->user->mobile }}</p>
                    <p class="sub-expire-row__plan">{{ $sub->plan->name ?? '' }}</p>
                </div>
                <span class="sub-expire-badge">
                    {{ \App\Helpers\NumberHelper::formatFa($sub->daysRemaining()) }} روز مانده
                </span>
            </div>
            @empty
            <p class="sub-empty">هیچ اشتراکی در حال انقضا نیست.</p>
            @endforelse
        </div>
    </div>

</div>

{{-- ══════════════════════════════════════════════════
     آخرین اشتراک‌ها
══════════════════════════════════════════════════ --}}
<div class="sub-panel" style="margin-top:0">
    <div class="sub-panel__header" style="display:flex;align-items:center;justify-content:space-between">
        <h2 class="sub-panel__title">آخرین اشتراک‌ها</h2>
        <a href="{{ route('admin.subscribers.index') }}" class="sub-panel__more">مشاهده همه ←</a>
    </div>
    <div style="overflow-x:auto">
        <table class="sub-table">
            <thead>
                <tr>
                    <th>کاربر</th>
                    <th>پلن</th>
                    <th>وضعیت</th>
                    <th>تاریخ پایان</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentSubscriptions as $sub)
                <tr>
                    <td class="sub-table__name">{{ $sub->user->name ?? $sub->user->mobile }}</td>
                    <td>{{ $sub->plan->name ?? '—' }}</td>
                    <td>
                        <span class="sub-status sub-status--{{ $sub->status }}">
                            {{ $sub->getStatusLabel() }}
                        </span>
                    </td>
                    <td class="sub-table__date">{{ \App\Helpers\Jalali::formatFa($sub->ends_at, 'Y/m/d') }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="sub-table__empty">اشتراکی ثبت نشده.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

</div>{{-- /sub-dash --}}

@push('head')
<style>
/* ═══════════════════════════════════════════════════════════
   Subscription Dashboard — Scoped Styles
   Cross-browser: Chrome 60+, Firefox 60+, Safari 12+, Edge 79+
═══════════════════════════════════════════════════════════ */

/* فونت فارسی */
.sub-dash, .sub-dash * { font-family: Vazirmatn, Tahoma, 'Segoe UI', Arial, sans-serif; }

/* ─ Layout ─ */
.sub-dash { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-orient: vertical; -webkit-box-direction: normal; -ms-flex-direction: column; flex-direction: column; gap: 1.5rem; }
.sub-icon  { width: 1rem; height: 1rem; }

/* ─ Head ─ */
.sub-dash__head { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; -webkit-box-pack: justify; -ms-flex-pack: justify; justify-content: space-between; -ms-flex-wrap: wrap; flex-wrap: wrap; gap: .75rem; }
.sub-dash__title { font-size: 1.375rem; font-weight: 800; color: #1e293b; margin: 0; }
.sub-dash__subtitle { font-size: .8125rem; color: #64748b; margin: .25rem 0 0; }
.sub-dash__head-actions { display: -webkit-box; display: -ms-flexbox; display: flex; gap: .5rem; -ms-flex-wrap: wrap; flex-wrap: wrap; }

/* ─ Stats Grid ─ */
.sub-stats-grid {
    display: -ms-grid; display: grid;
    -ms-grid-columns: 1fr 1fr; grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}
@media (min-width: 768px) {
    .sub-stats-grid { -ms-grid-columns: 1fr 1fr 1fr; grid-template-columns: repeat(3, 1fr); }
}
@media (min-width: 1200px) {
    .sub-stats-grid { -ms-grid-columns: 1fr 1fr 1fr 1fr 1fr 1fr; grid-template-columns: repeat(6, 1fr); }
}

.sub-stat-card {
    border-radius: 1rem; padding: 1.125rem 1rem;
    display: -webkit-box; display: -ms-flexbox; display: flex;
    -webkit-box-align: center; -ms-flex-align: center; align-items: center;
    gap: .875rem; color: #fff;
    -webkit-box-shadow: 0 4px 14px rgba(0,0,0,.12); box-shadow: 0 4px 14px rgba(0,0,0,.12);
    transition: -webkit-transform .18s; transition: transform .18s;
}
.sub-stat-card:hover { -webkit-transform: translateY(-2px); transform: translateY(-2px); }
.sub-stat-card__icon { opacity: .9; -ms-flex-negative: 0; flex-shrink: 0; }
.sub-stat-card__label { display: block; font-size: .7rem; opacity: .88; font-weight: 500; }
.sub-stat-card__value { display: block; font-size: 1.75rem; font-weight: 800; line-height: 1.2; margin-top: .125rem; }

/* gradients */
.sub-grad-indigo  { background: -webkit-linear-gradient(135deg, #4f46e5, #7c3aed); background: linear-gradient(135deg, #4f46e5, #7c3aed); }
.sub-grad-emerald { background: -webkit-linear-gradient(135deg, #059669, #10b981); background: linear-gradient(135deg, #059669, #10b981); }
.sub-grad-sky     { background: -webkit-linear-gradient(135deg, #0284c7, #38bdf8); background: linear-gradient(135deg, #0284c7, #38bdf8); }
.sub-grad-rose    { background: -webkit-linear-gradient(135deg, #e11d48, #f43f5e); background: linear-gradient(135deg, #e11d48, #f43f5e); }
.sub-grad-slate   { background: -webkit-linear-gradient(135deg, #475569, #64748b); background: linear-gradient(135deg, #475569, #64748b); }
.sub-grad-amber   { background: -webkit-linear-gradient(135deg, #d97706, #f59e0b); background: linear-gradient(135deg, #d97706, #f59e0b); }

/* ─ Revenue Row ─ */
.sub-revenue-row { display: -ms-grid; display: grid; -ms-grid-columns: 1fr 1fr; grid-template-columns: 1fr 1fr; gap: 1rem; }
@media (max-width: 480px) { .sub-revenue-row { grid-template-columns: 1fr; } }
.sub-revenue-card { background: #fff; border-radius: 1rem; padding: 1.5rem; border: 1px solid #e2e8f0; -webkit-box-shadow: 0 1px 4px rgba(0,0,0,.06); box-shadow: 0 1px 4px rgba(0,0,0,.06); }
.sub-revenue-card__label { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; gap: .5rem; font-size: .8rem; color: #64748b; font-weight: 500; margin-bottom: .625rem; }
.sub-revenue-card__amount { font-size: 2rem; font-weight: 800; color: #16a34a; line-height: 1; }
.sub-revenue-card__amount--muted { color: #475569; }
.sub-revenue-card__amount small { font-size: .875rem; font-weight: 400; color: #94a3b8; margin-right: .25rem; }

/* ─ Chart Card ─ */
.sub-chart-card { background: #fff; border-radius: 1rem; border: 1px solid #e2e8f0; -webkit-box-shadow: 0 1px 4px rgba(0,0,0,.06); box-shadow: 0 1px 4px rgba(0,0,0,.06); overflow: hidden; }
.sub-chart-card__header { padding: 1.125rem 1.5rem; border-bottom: 1px solid #f1f5f9; }
.sub-chart-card__title { font-size: .9375rem; font-weight: 700; color: #1e293b; margin: 0; }
.sub-chart-card__body { padding: 1.25rem 1.5rem; }

/* ─ Two-col ─ */
.sub-two-col { display: -ms-grid; display: grid; -ms-grid-columns: 1fr 1fr; grid-template-columns: 1fr 1fr; gap: 1rem; }
@media (max-width: 768px) { .sub-two-col { grid-template-columns: 1fr; } }

/* ─ Panel ─ */
.sub-panel { background: #fff; border-radius: 1rem; border: 1px solid #e2e8f0; -webkit-box-shadow: 0 1px 4px rgba(0,0,0,.06); box-shadow: 0 1px 4px rgba(0,0,0,.06); overflow: hidden; }
.sub-panel__header { padding: 1rem 1.375rem; border-bottom: 1px solid #f1f5f9; }
.sub-panel__title { font-size: .9rem; font-weight: 700; color: #1e293b; margin: 0; }
.sub-panel__more { font-size: .8rem; color: #4f46e5; text-decoration: none; }
.sub-panel__more:hover { text-decoration: underline; }
.sub-panel__body { padding: .75rem 1.375rem; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-orient: vertical; -webkit-box-direction: normal; -ms-flex-direction: column; flex-direction: column; gap: .625rem; }

/* ─ Plan Row ─ */
.sub-plan-row { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; gap: .625rem; padding: .375rem 0; }
.sub-plan-row__dot { width: 10px; height: 10px; border-radius: 50%; -ms-flex-negative: 0; flex-shrink: 0; }
.sub-plan-row__name { font-size: .875rem; color: #374151; -webkit-box-flex: 1; -ms-flex: 1; flex: 1; }
.sub-plan-row__count { font-size: .8rem; font-weight: 700; color: #1e293b; }

/* ─ Expire Row ─ */
.sub-expire-row { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; -webkit-box-pack: justify; -ms-flex-pack: justify; justify-content: space-between; padding: .375rem 0; gap: .5rem; }
.sub-expire-row__name { font-size: .875rem; font-weight: 600; color: #1e293b; }
.sub-expire-row__plan { font-size: .75rem; color: #94a3b8; margin-top: .1rem; }
.sub-expire-badge { font-size: .75rem; font-weight: 700; background: #fff7ed; color: #c2410c; padding: .2rem .625rem; border-radius: 9999px; -ms-flex-negative: 0; flex-shrink: 0; }

.sub-empty { font-size: .8125rem; color: #94a3b8; margin: .75rem 0; }

/* ─ Table ─ */
.sub-table { width: 100%; border-collapse: collapse; font-size: .8625rem; }
.sub-table th { text-align: right; padding: .625rem 1rem; background: #f8fafc; font-size: .75rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: .02em; border-bottom: 1px solid #e2e8f0; }
.sub-table td { padding: .75rem 1rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.sub-table tr:last-child td { border-bottom: none; }
.sub-table tr:hover td { background: #f8fafc; }
.sub-table__name { font-weight: 600; color: #1e293b; }
.sub-table__date { color: #64748b; font-size: .8125rem; }
.sub-table__empty { text-align: center; padding: 2rem; color: #94a3b8; }

/* ─ Status Badges ─ */
.sub-status { display: inline-block; padding: .2rem .625rem; border-radius: 9999px; font-size: .74rem; font-weight: 700; }
.sub-status--active    { background: #dcfce7; color: #166534; }
.sub-status--trial     { background: #dbeafe; color: #1e40af; }
.sub-status--cancelled { background: #fee2e2; color: #991b1b; }
.sub-status--expired   { background: #f1f5f9; color: #475569; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
(function () {
    var labels   = @json($chartData['labels']);
    var counts   = @json($chartData['counts']);

    var ctx = document.getElementById('subChart');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'تعداد اشتراک',
                data: counts,
                backgroundColor: 'rgba(79,70,229,0.75)',
                borderRadius: 5,
                borderSkipped: false,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    rtl: true,
                    callbacks: {
                        label: function(c) {
                            return ' ' + c.formattedValue.replace(/[0-9]/g, function(d){ return '۰۱۲۳۴۵۶۷۸۹'[d]; }) + ' اشتراک';
                        }
                    }
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 10, family: 'Vazirmatn,Tahoma' } } },
                y: { beginAtZero: true, ticks: { precision: 0, font: { size: 10 } } },
            },
        },
    });
})();
</script>
@endpush
@endsection
