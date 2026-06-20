@extends('admin.layout')
@section('title', 'پنل کاربران')

@push('head')
@include('partials.ticket-styles')
<style>
.user-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 1rem;
    padding: 1.25rem;
    transition: all .2s;
    position: relative;
    overflow: hidden;
}
.user-card:hover {
    box-shadow: 0 8px 24px rgba(0,0,0,.09);
    transform: translateY(-2px);
    border-color: #c7d2fe;
}
.user-card__accent {
    position: absolute; top: 0; right: 0;
    width: 4px; height: 100%;
    border-radius: 1rem 0 0 1rem;
}
.user-avatar {
    width: 3rem; height: 3rem; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.125rem; font-weight: 800; color: white;
    flex-shrink: 0;
}
.active-badge {
    display: inline-flex; align-items: center; gap: .25rem;
    padding: .2rem .6rem; border-radius: 9999px;
    font-size: .65rem; font-weight: 700;
    background: #dcfce7; color: #166534; border: 1px solid #bbf7d0;
}
.no-chat-badge {
    display: inline-flex; align-items: center; gap: .25rem;
    padding: .2rem .6rem; border-radius: 9999px;
    font-size: .65rem; font-weight: 600;
    background: #f3f4f6; color: #6b7280; border: 1px solid #e5e7eb;
}
.filter-badge {
    display: inline-flex; align-items: center; gap: .35rem;
    padding: .25rem .65rem; border-radius: 9999px; font-size: .72rem; font-weight: 500;
    border: 1px solid; cursor: pointer; transition: all .15s;
}
</style>
@endpush

@section('content')
@php
    $toFa = fn($n) => \App\Helpers\NumberHelper::formatFa($n);
    $engageRate = $totalUsers > 0 ? round(($usersWithActive / $totalUsers) * 100) : 0;
@endphp

<div x-data="{
    q: '{{ addslashes(request('q', '')) }}',
    status: '{{ request('status', '') }}',
    from_date: '{{ request('from_date', '') }}',
    to_date: '{{ request('to_date', '') }}',
    sort: '{{ request('sort', 'newest') }}',
    showFilters: {{ (request()->hasAny(['status','from_date','to_date']) && request()->anyFilled(['status','from_date','to_date'])) ? 'true' : 'false' }},
    get activeCount() {
        return [this.status, this.from_date, this.to_date].filter(Boolean).length;
    },
    doSearch() {
        const p = new URLSearchParams();
        if (this.q)         p.set('q', this.q);
        if (this.status)    p.set('status', this.status);
        if (this.from_date) p.set('from_date', this.from_date);
        if (this.to_date)   p.set('to_date', this.to_date);
        if (this.sort && this.sort !== 'newest') p.set('sort', this.sort);
        window.location.href = '{{ route('admin.users.index') }}' + (p.toString() ? '?' + p.toString() : '');
    },
    resetFilters() {
        this.q = ''; this.status = ''; this.from_date = ''; this.to_date = ''; this.sort = 'newest';
        window.location.href = '{{ route('admin.users.index') }}';
    }
}">

{{-- Flash --}}
@if(session('success'))
<div class="flex items-center gap-3 bg-green-50 border border-green-200 rounded-2xl px-5 py-3.5 mb-5"
     x-data="{ show: true }" x-show="show" x-transition.duration.300ms>
    <x-core-ui::icon name="check-circle" class="w-5 h-5 text-green-600 shrink-0" />
    <span class="text-sm text-green-700 font-medium">{{ session('success') }}</span>
    <button @click="show=false" class="mr-auto text-green-500 hover:text-green-700">
        <x-core-ui::icon name="close" class="w-4 h-4" />
    </button>
</div>
@endif

{{-- هدر --}}
<div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <div>
        <h1 class="text-xl font-bold text-gray-900">پنل کاربران</h1>
        <p class="text-sm text-gray-500 mt-0.5">مدیریت کاربران سایت و چت مستقیم با آن‌ها</p>
    </div>
    <a href="{{ route('admin.user-tickets.index') }}" class="btn-ghost text-xs">
        <x-core-ui::icon name="file-text" class="w-4 h-4" />
        صندوق تیکت‌ها
    </a>
</div>

{{-- ═══ آمار اصلی — ردیف اول ═══ --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4">

    {{-- کل کاربران --}}
    <div class="tkt-stat">
        <div class="tkt-stat__accent" style="background:linear-gradient(180deg,#6366f1,#4f46e5)"></div>
        <div class="flex items-center gap-3">
            <div class="tkt-stat__icon" style="background:#eef2ff">
                <x-core-ui::icon name="users" class="w-6 h-6 text-indigo-600" />
            </div>
            <div>
                <div class="tkt-stat__val text-indigo-600">{{ $toFa($totalUsers) }}</div>
                <div class="tkt-stat__label">کل کاربران</div>
            </div>
        </div>
    </div>

    {{-- چت فعال --}}
    <div class="tkt-stat">
        <div class="tkt-stat__accent" style="background:linear-gradient(180deg,#10b981,#059669)"></div>
        <div class="flex items-center gap-3">
            <div class="tkt-stat__icon" style="background:#ecfdf5">
                <x-core-ui::icon name="message" class="w-6 h-6 text-emerald-600" />
            </div>
            <div>
                <div class="tkt-stat__val text-emerald-600">{{ $toFa($usersWithActive) }}</div>
                <div class="tkt-stat__label">چت فعال</div>
            </div>
        </div>
    </div>

    {{-- عضو ماه جاری --}}
    <div class="tkt-stat">
        <div class="tkt-stat__accent" style="background:linear-gradient(180deg,#3b82f6,#2563eb)"></div>
        <div class="flex items-center gap-3">
            <div class="tkt-stat__icon" style="background:#eff6ff">
                <x-core-ui::icon name="calendar" class="w-6 h-6 text-blue-600" />
            </div>
            <div>
                <div class="tkt-stat__val text-blue-600">{{ $toFa($newThisMonth) }}</div>
                <div class="tkt-stat__label">عضو ماه جاری</div>
            </div>
        </div>
    </div>

    {{-- نرخ تعامل --}}
    <div class="tkt-stat">
        <div class="tkt-stat__accent" style="background:linear-gradient(180deg,#8b5cf6,#7c3aed)"></div>
        <div class="flex items-center gap-3">
            <div class="tkt-stat__icon" style="background:#f5f3ff">
                <x-core-ui::icon name="bar-chart" class="w-6 h-6 text-violet-600" />
            </div>
            <div>
                <div class="tkt-stat__val text-violet-600">{{ \App\Helpers\NumberHelper::toPersianNumbers($engageRate) }}٪</div>
                <div class="tkt-stat__label">نرخ تعامل</div>
            </div>
        </div>
    </div>
</div>

{{-- ═══ آمار ثانویه — ردیف دوم ═══ --}}
<div class="grid grid-cols-2 lg:grid-cols-3 gap-3 mb-6">

    {{-- جدید امروز --}}
    <div class="glass-card px-4 py-3 flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" style="background:#f0fdf4">
            <x-core-ui::icon name="plus" class="w-5 h-5 text-teal-600" />
        </div>
        <div>
            <div class="text-lg font-bold text-teal-700">{{ $toFa($newToday) }}</div>
            <div class="text-xs text-gray-500">جدید امروز</div>
        </div>
    </div>

    {{-- کل تیکت‌ها --}}
    <div class="glass-card px-4 py-3 flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" style="background:#fff7ed">
            <x-core-ui::icon name="file-text" class="w-5 h-5 text-amber-600" />
        </div>
        <div>
            <div class="text-lg font-bold text-amber-700">{{ $toFa($totalTickets) }}</div>
            <div class="text-xs text-gray-500">کل تیکت‌ها</div>
        </div>
    </div>

    {{-- نتیجه جستجو --}}
    <div class="glass-card px-4 py-3 flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" style="background:#f8fafc">
            <x-core-ui::icon name="search" class="w-5 h-5 text-gray-500" />
        </div>
        <div>
            <div class="text-lg font-bold text-gray-700">{{ $toFa($users->total()) }}</div>
            <div class="text-xs text-gray-500">نتیجه جستجو</div>
        </div>
    </div>
</div>

{{-- ═══ نمودار روند ثبت‌نام ═══ --}}
@if(isset($trend) && count($trend) > 0)
<div class="glass-card p-5 mb-6" x-data="{ open: true }">
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background:#eef2ff">
                <x-core-ui::icon name="bar-chart" class="w-4 h-4 text-indigo-600" />
            </div>
            <span class="font-semibold text-gray-700 text-sm">روند ثبت‌نام کاربران</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="flex bg-gray-100 rounded-lg p-0.5 gap-0.5">
                <button id="up-chart-7d" class="text-xs px-3 py-1 rounded-md font-medium transition-all">۷ روز</button>
                <button id="up-chart-30d" class="text-xs px-3 py-1 rounded-md font-medium transition-all">۳۰ روز</button>
            </div>
            <button @click="open = !open"
                    class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
                <x-core-ui::icon name="chevron-up" class="w-4 h-4 transition-transform" x-bind:class="open ? '' : 'rotate-180'" />
            </button>
        </div>
    </div>
    <div x-show="open" x-transition>
        <div class="relative h-44">
            <canvas id="upRegChart"></canvas>
        </div>
        <div class="flex justify-center gap-8 mt-3 pt-3 border-t border-gray-100">
            <div class="text-center">
                <div class="text-xs text-gray-400">مجموع دوره</div>
                <div id="up-chart-total" class="text-sm font-bold text-indigo-600">—</div>
            </div>
            <div class="text-center">
                <div class="text-xs text-gray-400">بیشترین روز</div>
                <div id="up-chart-peak" class="text-sm font-bold text-emerald-600">—</div>
            </div>
            <div class="text-center">
                <div class="text-xs text-gray-400">میانگین روزانه</div>
                <div id="up-chart-avg" class="text-sm font-bold text-gray-600">—</div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- ═══ جستجو و فیلترها ═══ --}}
<div class="glass-card p-4 mb-6 space-y-3">

    {{-- ردیف اول: جستجوی سریع --}}
    <div class="flex flex-wrap items-center gap-3">
        <div class="flex-1 min-w-48 relative">
            <x-core-ui::icon name="search" class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
            <input type="text" x-model="q"
                   @keydown.enter="doSearch()"
                   placeholder="جستجو بر اساس نام یا موبایل..."
                   class="tkt-form-input pr-9 text-sm w-full">
        </div>

        <button @click="showFilters = !showFilters"
                class="flex items-center gap-1.5 text-sm px-4 py-2 rounded-xl border transition-all"
                :class="showFilters
                    ? 'bg-indigo-50 border-indigo-200 text-indigo-700'
                    : 'bg-gray-50 border-gray-200 text-gray-600 hover:bg-gray-100'">
            <x-core-ui::icon name="filter" class="w-4 h-4" />
            فیلترها
            <span x-show="activeCount > 0" x-text="activeCount"
                  class="bg-indigo-600 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center font-bold leading-none"></span>
        </button>

        <button @click="doSearch()" class="btn-gradient text-xs px-4 py-2 flex items-center gap-1.5">
            <x-core-ui::icon name="search" class="w-3.5 h-3.5" />
            جستجو
        </button>
        <button @click="resetFilters()" class="btn-ghost text-xs flex items-center gap-1">
            <x-core-ui::icon name="close" class="w-3.5 h-3.5" />
            پاک‌سازی
        </button>
    </div>

    {{-- ردیف دوم: فیلترهای پیشرفته --}}
    <div x-show="showFilters"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         style="display:none">
        <div class="pt-3 border-t border-gray-100 grid grid-cols-2 md:grid-cols-4 gap-3">

            {{-- وضعیت چت --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">وضعیت چت</label>
                <select x-model="status" class="tkt-form-input text-sm">
                    <option value="">همه کاربران</option>
                    <option value="active_chat">چت فعال (باز)</option>
                    <option value="with_chat">دارای سابقه چت</option>
                    <option value="no_chat">بدون چت</option>
                </select>
            </div>

            {{-- مرتب‌سازی --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">مرتب‌سازی</label>
                <select x-model="sort" class="tkt-form-input text-sm">
                    <option value="newest">جدیدترین</option>
                    <option value="oldest">قدیمی‌ترین</option>
                </select>
            </div>

            {{-- از تاریخ --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">از تاریخ عضویت</label>
                <input type="date" x-model="from_date" class="tkt-form-input text-sm">
            </div>

            {{-- تا تاریخ --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">تا تاریخ عضویت</label>
                <input type="date" x-model="to_date" class="tkt-form-input text-sm">
            </div>
        </div>

        <div class="mt-3 flex gap-2">
            <button @click="doSearch()"
                    class="btn-gradient text-xs px-4 py-2 flex items-center gap-1.5">
                اعمال فیلترها
            </button>
        </div>
    </div>

    {{-- فیلترهای فعال --}}
    <div x-show="status || from_date || to_date || q" class="flex flex-wrap gap-1.5" style="display:none">
        <template x-if="q">
            <span class="filter-badge bg-indigo-50 text-indigo-700 border-indigo-200">
                جستجو: <strong x-text="q"></strong>
                <button @click="q=''; doSearch()" class="text-indigo-400 hover:text-red-500 font-bold">×</button>
            </span>
        </template>
        <template x-if="status">
            <span class="filter-badge bg-blue-50 text-blue-700 border-blue-200">
                <span x-text="status === 'active_chat' ? 'چت فعال' : status === 'with_chat' ? 'دارای چت' : 'بدون چت'"></span>
                <button @click="status=''; doSearch()" class="text-blue-400 hover:text-red-500 font-bold">×</button>
            </span>
        </template>
        <template x-if="from_date">
            <span class="filter-badge bg-gray-50 text-gray-700 border-gray-200">
                از: <strong x-text="from_date"></strong>
                <button @click="from_date=''; doSearch()" class="text-gray-400 hover:text-red-500 font-bold">×</button>
            </span>
        </template>
        <template x-if="to_date">
            <span class="filter-badge bg-gray-50 text-gray-700 border-gray-200">
                تا: <strong x-text="to_date"></strong>
                <button @click="to_date=''; doSearch()" class="text-gray-400 hover:text-red-500 font-bold">×</button>
            </span>
        </template>
    </div>
</div>

{{-- ═══ کارت‌های کاربران ═══ --}}
@if($users->isEmpty())
<div class="glass-card text-center py-20">
    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <x-core-ui::icon name="users" class="w-8 h-8 text-gray-300" />
    </div>
    <h3 class="text-base font-semibold text-gray-700 mb-1">کاربری یافت نشد</h3>
    <p class="text-sm text-gray-400">
        {{ request()->anyFilled(['q','status','from_date','to_date'])
            ? 'با این فیلترها نتیجه‌ای وجود ندارد.'
            : 'هنوز کاربری در سیستم ثبت‌نام نکرده است.' }}
    </p>
</div>

@else
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 mb-6">
    @foreach($users as $u)
    @php
        $stat        = $ticketStats[$u->id] ?? null;
        $activeCount = $stat ? (int)$stat->active_count : 0;
        $totalTkts   = $stat ? (int)$stat->total : 0;
        $lastAct     = $stat?->last_activity;
        $colors      = ['#4f46e5','#0891b2','#059669','#d97706','#dc2626','#7c3aed','#db2777'];
        $color       = $colors[crc32($u->name ?? $u->mobile ?? '') % count($colors)];
        $initial     = mb_strtoupper(mb_substr($u->name ?? '؟', 0, 1));
    @endphp
    <div class="user-card">
        <div class="user-card__accent"
             style="background: {{ $activeCount > 0
                 ? 'linear-gradient(180deg,#10b981,#059669)'
                 : 'linear-gradient(180deg,#d1d5db,#9ca3af)' }}"></div>

        {{-- هدر کارت --}}
        <div class="flex items-start gap-3 mb-3">
            @if($u->avatar)
                <img src="{{ $u->avatar_url ?? $u->avatar }}"
                     class="user-avatar object-cover" alt="{{ $u->name }}">
            @else
                <div class="user-avatar" style="background:{{ $color }}">{{ $initial }}</div>
            @endif
            <div class="flex-1 min-w-0">
                <h3 class="font-bold text-sm text-gray-900 truncate">{{ $u->name ?? '—' }}</h3>
                <p class="text-xs text-gray-500 truncate tabular-nums">
                    {{ \App\Helpers\NumberHelper::toPersianNumbers($u->mobile ?? '—') }}
                </p>
            </div>
            @if($activeCount > 0)
                <span class="active-badge shrink-0">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block animate-pulse"></span>
                    فعال
                </span>
            @else
                <span class="no-chat-badge shrink-0">بدون چت</span>
            @endif
        </div>

        {{-- آمار کارت --}}
        <div class="flex items-center justify-between text-xs text-gray-400 mb-3 pt-2.5 border-t border-gray-50">
            <span class="flex items-center gap-1">
                <x-core-ui::icon name="message" class="w-3.5 h-3.5" />
                {{ \App\Helpers\NumberHelper::toPersianNumbers($totalTkts) }} مکالمه
            </span>
            @if($activeCount > 0)
                <span class="text-emerald-600 font-semibold">
                    {{ \App\Helpers\NumberHelper::toPersianNumbers($activeCount) }} باز
                </span>
            @endif
            <span class="flex items-center gap-1" title="{{ \App\Helpers\Jalali::formatFa($u->created_at, 'Y/m/d') }}">
                <x-core-ui::icon name="calendar" class="w-3.5 h-3.5" />
                {{ \App\Helpers\Jalali::diffForHumansFa($u->created_at) }}
            </span>
        </div>

        {{-- دکمه اقدام --}}
        <a href="{{ route('admin.users.chat', $u->id) }}"
           class="w-full flex items-center justify-center gap-2 py-2 rounded-xl text-xs font-semibold transition-all
                  {{ $activeCount > 0
                      ? 'bg-gradient-to-r from-indigo-500 to-violet-500 text-white shadow-sm hover:shadow-indigo-200 hover:shadow-md'
                      : 'border border-gray-200 text-gray-600 hover:bg-gray-50 hover:border-indigo-300' }}">
            <x-core-ui::icon name="message" class="w-3.5 h-3.5" />
            {{ $activeCount > 0 ? 'ادامه چت' : 'شروع چت' }}
        </a>
    </div>
    @endforeach
</div>

{{-- صفحه‌بندی --}}
@if($users->hasPages())
<div class="flex flex-wrap items-center justify-between gap-3 mt-2">
    <span class="text-sm text-gray-500">
        نمایش
        <span class="font-medium text-gray-700">{{ \App\Helpers\NumberHelper::toPersianNumbers($users->firstItem()) }}</span>
        تا
        <span class="font-medium text-gray-700">{{ \App\Helpers\NumberHelper::toPersianNumbers($users->lastItem()) }}</span>
        از
        <span class="font-semibold text-indigo-600">{{ \App\Helpers\NumberHelper::formatFa($users->total()) }}</span>
        کاربر
    </span>
    {{ $users->links() }}
</div>
@endif
@endif

</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    const trendAll = @json($trend ?? []);
    if (!trendAll.length) return;

    const toFa = n => String(n).replace(/[0-9]/g, d => '۰۱۲۳۴۵۶۷۸۹'[d]);
    let chart, activeView = 30;

    function buildChart(days) {
        const data   = days === 7 ? trendAll.slice(-7) : trendAll;
        const labels = data.map(d => d.label);
        const counts = data.map(d => d.count);
        const total  = counts.reduce((a, b) => a + b, 0);
        const peak   = Math.max(...counts, 0);
        const avg    = counts.length ? (total / counts.length).toFixed(1) : 0;

        const el = t => document.getElementById(t);
        if (el('up-chart-total')) el('up-chart-total').textContent = toFa(total);
        if (el('up-chart-peak'))  el('up-chart-peak').textContent  = toFa(peak);
        if (el('up-chart-avg'))   el('up-chart-avg').textContent   = toFa(avg);

        const canvas = document.getElementById('upRegChart');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');

        const gradient = ctx.createLinearGradient(0, 0, 0, 180);
        gradient.addColorStop(0, 'rgba(99,102,241,.18)');
        gradient.addColorStop(1, 'rgba(99,102,241,.0)');

        if (chart) chart.destroy();
        chart = new Chart(canvas, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    data: counts,
                    borderColor: '#6366f1',
                    backgroundColor: gradient,
                    fill: true, tension: 0.4,
                    pointRadius: 3, pointHoverRadius: 6,
                    pointBackgroundColor: '#6366f1',
                    pointBorderColor: '#fff', pointBorderWidth: 2, borderWidth: 2,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        rtl: true, textDirection: 'rtl',
                        backgroundColor: '#1e1b4b', padding: 10, cornerRadius: 10,
                        callbacks: {
                            title: ([c]) => c.label,
                            label: c => ` ثبت‌نام: ${toFa(c.raw)} کاربر`,
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false }, border: { display: false },
                         ticks: { color: '#9ca3af', font: { family: 'Vazirmatn,Tahoma', size: 10 } } },
                    y: { beginAtZero: true, grid: { color: '#f8fafc' }, border: { display: false },
                         ticks: { precision: 0, callback: v => toFa(v),
                                  color: '#9ca3af', font: { family: 'Vazirmatn,Tahoma', size: 10 } } }
                }
            }
        });
    }

    function setBtn(days) {
        ['7','30'].forEach(d => {
            const btn = document.getElementById(`up-chart-${d}d`);
            if (!btn) return;
            btn.className = String(days) === d
                ? 'text-xs px-3 py-1 rounded-md font-medium transition-all bg-white shadow-sm text-indigo-600'
                : 'text-xs px-3 py-1 rounded-md font-medium transition-all text-gray-500 hover:text-gray-700';
        });
    }

    document.getElementById('up-chart-7d')?.addEventListener('click',  () => { buildChart(7);  setBtn(7);  });
    document.getElementById('up-chart-30d')?.addEventListener('click', () => { buildChart(30); setBtn(30); });

    buildChart(30); setBtn(30);
})();
</script>
@endpush

@endsection
