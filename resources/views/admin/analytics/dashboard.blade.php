@extends('admin.layout')
@section('title', 'داشبورد آمار و تحلیل')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">داشبورد آمار</h1>
            <p class="text-slate-500 text-sm mt-0.5">وضعیت کسب‌وکار در یک نگاه — آمار ۳۰ روزه</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs text-slate-400 bg-slate-100 px-2.5 py-1 rounded-full">بروزرسانی خودکار</span>
            <button onclick="location.reload()" class="flex items-center gap-1.5 px-3 py-2 rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 text-sm font-medium transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                بروزرسانی
            </button>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">

        {{-- Users --}}
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <span class="text-xs px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 font-medium">+{{ num_fa($kpis['new_users_month']) }} این ماه</span>
            </div>
            <div class="text-2xl font-bold text-slate-800">@numFa($kpis['total_users'])</div>
            <div class="text-xs text-slate-500 mt-0.5">کل کاربران</div>
        </div>

        {{-- Revenue --}}
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                @php
                    $pct = $kpis['revenue_change_pct'];
                    $up  = $pct >= 0;
                @endphp
                <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $up ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                    {{ $up ? '▲' : '▼' }} {{ abs($pct) }}%
                </span>
            </div>
            <div class="text-2xl font-bold text-slate-800">@numFa($kpis['revenue_month']) <span class="text-sm font-normal text-slate-400">ت</span></div>
            <div class="text-xs text-slate-500 mt-0.5">درآمد این ماه</div>
        </div>

        {{-- Subscriptions --}}
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-violet-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                </div>
                <span class="text-xs px-2 py-0.5 rounded-full bg-violet-50 text-violet-700 font-medium">فعال</span>
            </div>
            <div class="text-2xl font-bold text-slate-800">@numFa($kpis['active_subscriptions'])</div>
            <div class="text-xs text-slate-500 mt-0.5">اشتراک‌های فعال</div>
        </div>

        {{-- Page Views --}}
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </div>
                <span class="text-xs px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 font-medium">امروز: {{ num_fa($kpis['page_views_today']) }}</span>
            </div>
            <div class="text-2xl font-bold text-slate-800">@numFa($kpis['page_views_month'])</div>
            <div class="text-xs text-slate-500 mt-0.5">بازدید این ماه</div>
        </div>
    </div>

    {{-- Row 2: Revenue chart + User Registrations --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">

        {{-- Revenue Chart --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-800">درآمد و سفارشات — ۳۰ روز اخیر</h3>
                    <p class="text-xs text-slate-400 mt-0.5">درآمد تجمیع‌شده از سفارشات تکمیل‌شده</p>
                </div>
                <div class="flex items-center gap-3 text-xs text-slate-500">
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-indigo-500 inline-block"></span> درآمد</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-emerald-400 inline-block"></span> سفارش</span>
                </div>
            </div>
            <canvas id="revenueChart" height="180"></canvas>
        </div>

        {{-- Ticket Status --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <h3 class="text-sm font-bold text-slate-800 mb-4">وضعیت تیکت‌ها</h3>
            <canvas id="ticketChart" height="180"></canvas>
            <div class="mt-4 grid grid-cols-2 gap-2 text-center">
                <div class="bg-amber-50 rounded-lg p-2">
                    <div class="text-lg font-bold text-amber-700">{{ $tickets['open'] }}</div>
                    <div class="text-[10px] text-amber-600">باز</div>
                </div>
                <div class="bg-blue-50 rounded-lg p-2">
                    <div class="text-lg font-bold text-blue-700">{{ $tickets['pending'] }}</div>
                    <div class="text-[10px] text-blue-600">در انتظار</div>
                </div>
                <div class="bg-emerald-50 rounded-lg p-2">
                    <div class="text-lg font-bold text-emerald-700">{{ $tickets['resolved'] }}</div>
                    <div class="text-[10px] text-emerald-600">حل‌شده</div>
                </div>
                <div class="bg-slate-50 rounded-lg p-2">
                    <div class="text-lg font-bold text-slate-700">{{ $tickets['closed'] }}</div>
                    <div class="text-[10px] text-slate-500">بسته</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Row 3: Page Views + Users chart --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">

        {{-- Page Views Chart --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-bold text-slate-800">بازدید صفحات — ۱۴ روز</h3>
                <div class="flex items-center gap-3 text-xs text-slate-500">
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-teal-500 inline-block"></span> بازدید</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-sky-400 inline-block"></span> سشن</span>
                </div>
            </div>
            <canvas id="pvChart" height="160"></canvas>
        </div>

        {{-- User Registrations --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-bold text-slate-800">ثبت‌نام کاربران — ۳۰ روز</h3>
                <span class="text-xs text-slate-400">{{ num_fa($kpis['new_users_month']) }} کاربر این ماه</span>
            </div>
            <canvas id="usersChart" height="160"></canvas>
        </div>
    </div>

    {{-- Row 4: Top Pages + Top Products + Device Split --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- Top Pages --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <h3 class="text-sm font-bold text-slate-800 mb-4">صفحات پربازدید (۳۰ روزه)</h3>
            @if(empty($topPages))
                <div class="text-center py-8 text-slate-400 text-sm">هنوز داده‌ای ثبت نشده</div>
            @else
                <div class="space-y-2">
                    @php $maxViews = max(array_column($topPages, 'views')); @endphp
                    @foreach($topPages as $page)
                        <div class="flex items-center gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-medium text-slate-700 truncate">{{ '/' . ltrim($page['path'], '/') }}</div>
                                <div class="mt-1 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full rounded-full bg-indigo-400 transition-all"
                                         style="width: {{ $maxViews > 0 ? round($page['views'] / $maxViews * 100) : 0 }}%"></div>
                                </div>
                            </div>
                            <div class="shrink-0 text-right">
                                <div class="text-sm font-bold text-slate-800">@numFa($page['views'])</div>
                                <div class="text-[10px] text-slate-400">{{ $page['sessions'] }} سشن</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Device Split + Top Products --}}
        <div class="flex flex-col gap-4">

            {{-- Device Split --}}
            <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                <h3 class="text-sm font-bold text-slate-800 mb-3">دستگاه‌ها (۳۰ روزه)</h3>
                @if(empty($deviceSplit))
                    <div class="text-center py-4 text-slate-400 text-xs">داده‌ای موجود نیست</div>
                @else
                    @php $totalDevices = array_sum(array_column($deviceSplit, 'count')); @endphp
                    @foreach($deviceSplit as $d)
                        @php
                            $pct = $totalDevices > 0 ? round($d['count'] / $totalDevices * 100) : 0;
                            $icon = match($d['device']) { 'mobile' => '📱', 'tablet' => '📟', default => '🖥️' };
                            $colors = ['mobile' => 'bg-blue-400', 'tablet' => 'bg-purple-400', 'desktop' => 'bg-teal-400'];
                            $color  = $colors[$d['device']] ?? 'bg-slate-400';
                        @endphp
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-base">{{ $icon }}</span>
                            <div class="flex-1">
                                <div class="flex justify-between text-xs mb-0.5">
                                    <span class="text-slate-600">{{ match($d['device']) { 'mobile' => 'موبایل', 'tablet' => 'تبلت', default => 'دسکتاپ' } }}</span>
                                    <span class="font-medium text-slate-700">{{ $pct }}%</span>
                                </div>
                                <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full rounded-full {{ $color }}" style="width:{{ $pct }}%"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            {{-- Top Products --}}
            <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex-1">
                <h3 class="text-sm font-bold text-slate-800 mb-3">محصولات پرفروش</h3>
                @if(empty($topProducts))
                    <div class="text-center py-4 text-slate-400 text-xs">داده‌ای موجود نیست</div>
                @else
                    <div class="space-y-2">
                        @foreach($topProducts as $i => $p)
                            <div class="flex items-start gap-2">
                                <div class="w-5 h-5 rounded-full bg-slate-100 flex items-center justify-center text-[10px] font-bold text-slate-500 shrink-0 mt-0.5">{{ $i+1 }}</div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs font-medium text-slate-700 truncate">{{ $p->title ?? ($p['title'] ?? '') }}</div>
                                    <div class="text-[10px] text-slate-400">{{ num_fa($p->qty ?? ($p['qty'] ?? 0)) }} فروش</div>
                                </div>
                                <div class="text-xs font-bold text-emerald-700 shrink-0">{{ num_fa($p->revenue ?? ($p['revenue'] ?? 0)) }}ت</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
const REVENUE_DATA  = @json($revenueChart);
const USERS_DATA    = @json($usersChart);
const PV_DATA       = @json($pvChart);
const TICKET_DATA   = @json($tickets);

const chartDefaults = {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
        x: { grid: { display: false }, ticks: { font: { size: 10 }, color: '#94a3b8' } },
        y: { grid: { color: '#f1f5f9' }, ticks: { font: { size: 10 }, color: '#94a3b8' } }
    }
};

// Revenue + Orders (dual axis)
new Chart(document.getElementById('revenueChart'), {
    type: 'bar',
    data: {
        labels: REVENUE_DATA.labels,
        datasets: [
            {
                label: 'درآمد',
                data: REVENUE_DATA.revenue,
                backgroundColor: 'rgba(99,102,241,0.15)',
                borderColor: '#6366f1',
                borderWidth: 2,
                borderRadius: 4,
                yAxisID: 'y',
            },
            {
                label: 'سفارش',
                data: REVENUE_DATA.orders,
                type: 'line',
                borderColor: '#34d399',
                backgroundColor: 'rgba(52,211,153,0.1)',
                borderWidth: 2,
                pointRadius: 3,
                tension: 0.3,
                fill: true,
                yAxisID: 'y1',
            }
        ]
    },
    options: {
        ...chartDefaults,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 10 }, color: '#94a3b8' } },
            y:  { position: 'right', grid: { color: '#f1f5f9' }, ticks: { font: { size: 10 }, color: '#6366f1' } },
            y1: { position: 'left',  grid: { display: false }, ticks: { font: { size: 10 }, color: '#34d399' } }
        }
    }
});

// Page Views
new Chart(document.getElementById('pvChart'), {
    type: 'line',
    data: {
        labels: PV_DATA.labels,
        datasets: [
            { label: 'بازدید', data: PV_DATA.views,    borderColor: '#14b8a6', backgroundColor: 'rgba(20,184,166,0.1)', borderWidth: 2, pointRadius: 3, tension: 0.3, fill: true },
            { label: 'سشن',   data: PV_DATA.sessions, borderColor: '#38bdf8', backgroundColor: 'transparent', borderWidth: 1.5, borderDash: [4,3], pointRadius: 2, tension: 0.3 }
        ]
    },
    options: chartDefaults
});

// User Registrations
new Chart(document.getElementById('usersChart'), {
    type: 'bar',
    data: {
        labels: USERS_DATA.labels,
        datasets: [{ data: USERS_DATA.counts, backgroundColor: 'rgba(139,92,246,0.25)', borderColor: '#8b5cf6', borderWidth: 2, borderRadius: 4 }]
    },
    options: chartDefaults
});

// Ticket doughnut
new Chart(document.getElementById('ticketChart'), {
    type: 'doughnut',
    data: {
        labels: ['باز', 'در انتظار', 'حل‌شده', 'بسته'],
        datasets: [{
            data: [TICKET_DATA.open, TICKET_DATA.pending, TICKET_DATA.resolved, TICKET_DATA.closed],
            backgroundColor: ['#fbbf24', '#60a5fa', '#34d399', '#94a3b8'],
            borderWidth: 0,
            hoverOffset: 6
        }]
    },
    options: {
        responsive: true,
        cutout: '65%',
        plugins: {
            legend: { display: true, position: 'bottom', labels: { font: { size: 10 }, boxWidth: 10, padding: 8 } }
        }
    }
});
</script>
@endpush
