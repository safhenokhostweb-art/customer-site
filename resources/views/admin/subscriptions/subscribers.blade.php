@extends('admin.layout')
@section('title', 'مشترکین')
@section('content')

<div class="subs-page">

{{-- ── هدر ─────────────────────────────────────────── --}}
<div class="subs-page__head">
    <div>
        <h1 class="subs-page__title">مشترکین</h1>
        <p class="subs-page__subtitle">لیست کامل اشتراک‌های ثبت‌شده</p>
    </div>
    <div class="subs-page__actions">
        <a href="{{ route('admin.subscriptions.dashboard') }}" class="btn btn-secondary btn-sm">داشبورد</a>
        <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-secondary btn-sm">پلن‌ها</a>
        <a href="{{ route('admin.subscribers.pending') }}" class="btn btn-sm" style="background:#fff7ed;color:#c2410c;border:1px solid #fed7aa">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            درخواست‌های pending
        </a>
        <a href="{{ route('admin.subscribers.assign.form') }}" class="btn btn-primary btn-sm">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            ثبت دستی
        </a>
        <a href="{{ route('admin.subscribers.export') }}{{ request()->getQueryString() ? '?'.request()->getQueryString() : '' }}"
           class="btn btn-secondary btn-sm">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            CSV
        </a>
    </div>
</div>


{{-- ── کارت‌های آماری ──────────────────────────────── --}}
<div class="subs-stats">
    @php
    $sc = [
        ['l'=>'کل',      'v'=>$stats['total'],     'bg'=>'#f0f4ff','c'=>'#4338ca','bc'=>'#c7d2fe'],
        ['l'=>'فعال',    'v'=>$stats['active'],    'bg'=>'#f0fdf4','c'=>'#15803d','bc'=>'#bbf7d0'],
        ['l'=>'آزمایشی','v'=>$stats['trial'],      'bg'=>'#eff6ff','c'=>'#1d4ed8','bc'=>'#bfdbfe'],
        ['l'=>'لغو شده','v'=>$stats['cancelled'],  'bg'=>'#fff1f2','c'=>'#be123c','bc'=>'#fecdd3'],
        ['l'=>'منقضی',  'v'=>$stats['expired'],    'bg'=>'#f8fafc','c'=>'#475569','bc'=>'#e2e8f0'],
    ];
    @endphp
    @foreach($sc as $s)
    <div class="subs-stat" style="background:{{ $s['bg'] }};border-color:{{ $s['bc'] }}">
        <span class="subs-stat__label">{{ $s['l'] }}</span>
        <span class="subs-stat__val" style="color:{{ $s['c'] }}">{{ \App\Helpers\NumberHelper::formatFa($s['v']) }}</span>
    </div>
    @endforeach
</div>

{{-- ── فیلتر ────────────────────────────────────────── --}}
<form method="GET" action="{{ route('admin.subscribers.index') }}" class="subs-filter">
    <div class="subs-filter__fields">
        <div class="subs-filter__field">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="subs-filter__icon"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="نام یا موبایل کاربر..." class="subs-filter__input">
        </div>
        <select name="status" class="subs-filter__select">
            <option value="">همه وضعیت‌ها</option>
            <option value="active"    {{ request('status')==='active'    ?'selected':'' }}>فعال</option>
            <option value="trial"     {{ request('status')==='trial'     ?'selected':'' }}>آزمایشی</option>
            <option value="pending"   {{ request('status')==='pending'   ?'selected':'' }}>در انتظار</option>
            <option value="cancelled" {{ request('status')==='cancelled' ?'selected':'' }}>لغو شده</option>
            <option value="expired"   {{ request('status')==='expired'   ?'selected':'' }}>منقضی</option>
        </select>
        <select name="plan_id" class="subs-filter__select">
            <option value="">همه پلن‌ها</option>
            @foreach($plans as $plan)
            <option value="{{ $plan->id }}" {{ request('plan_id')==$plan->id?'selected':'' }}>{{ $plan->name }}</option>
            @endforeach
        </select>
        <div class="subs-filter__btns">
            <button type="submit" class="btn btn-primary btn-sm">اعمال</button>
            @if(request()->hasAny(['search','status','plan_id']))
            <a href="{{ route('admin.subscribers.index') }}" class="btn btn-secondary btn-sm">پاک کردن</a>
            @endif
        </div>
    </div>
</form>

{{-- ── جدول ─────────────────────────────────────────── --}}
<div class="subs-table-wrap">
    <table class="subs-table">
        <thead>
            <tr>
                <th>کاربر</th>
                <th>پلن</th>
                <th>وضعیت</th>
                <th>تاریخ شروع</th>
                <th>تاریخ پایان</th>
                <th>روز مانده</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($subscriptions as $sub)
            <tr>
                <td>
                    <div class="subs-table__user">
                        <div class="subs-table__avatar">{{ mb_substr($sub->user->name ?? $sub->user->mobile, 0, 1) }}</div>
                        <div>
                            <div class="subs-table__uname">{{ $sub->user->name ?? '—' }}</div>
                            <div class="subs-table__umobile" dir="ltr">{{ $sub->user->mobile ?? '' }}</div>
                        </div>
                    </div>
                </td>
                <td>
                    @if($sub->plan)
                    <span class="subs-table__plan">
                        <span class="subs-table__plan-dot" style="background:{{ $sub->plan->color ?? '#4f46e5' }}"></span>
                        {{ $sub->plan->name }}
                    </span>
                    @else <span class="subs-table__muted">—</span>
                    @endif
                </td>
                <td><span class="sub-badge sub-badge--{{ $sub->status }}">{{ $sub->getStatusLabel() }}</span></td>
                <td class="subs-table__date">{{ \App\Helpers\Jalali::formatFa($sub->starts_at, 'Y/m/d') }}</td>
                <td class="subs-table__date">{{ \App\Helpers\Jalali::formatFa($sub->ends_at, 'Y/m/d') }}</td>
                <td>
                    @if($sub->isActive() || $sub->isInTrial())
                    <span class="subs-days {{ $sub->daysRemaining() <= 7 ? 'subs-days--warn' : '' }}">
                        {{ \App\Helpers\NumberHelper::formatFa($sub->daysRemaining()) }} روز
                    </span>
                    @else <span class="subs-table__muted">—</span>
                    @endif
                </td>
                <td>
                    <div style="display:flex;gap:.375rem;flex-wrap:wrap;align-items:center">
                        <a href="{{ route('admin.subscribers.show', $sub) }}" class="btn btn-secondary btn-sm">جزئیات</a>
                        <form method="POST" action="{{ route('admin.subscribers.delete', $sub) }}"
                              onsubmit="return confirm('اشتراک «{{ $sub->plan?->name }}» برای {{ $sub->user?->name ?? $sub->user?->mobile }} حذف شود؟');"
                              style="display:inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm"
                                    style="background:#fff1f2;color:#be123c;border:1px solid #fecdd3;padding:.25rem .625rem;border-radius:.375rem;font-size:.75rem;font-weight:600;cursor:pointer;font-family:Vazirmatn,Tahoma,sans-serif">
                                حذف
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="subs-table__empty">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="32" height="32" style="color:#cbd5e1;margin-bottom:.5rem"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <br>هیچ اشتراکی یافت نشد.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($subscriptions->hasPages())
<div class="admin-pagination">{{ $subscriptions->links() }}</div>
@endif

</div>

@push('head')
<style>
.subs-page, .subs-page * { font-family: Vazirmatn, Tahoma, 'Segoe UI', Arial, sans-serif; }
.subs-page { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-orient: vertical; -webkit-box-direction: normal; -ms-flex-direction: column; flex-direction: column; gap: 1.25rem; }

.subs-page__head { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; -webkit-box-pack: justify; -ms-flex-pack: justify; justify-content: space-between; -ms-flex-wrap: wrap; flex-wrap: wrap; gap: .75rem; }
.subs-page__title { font-size: 1.25rem; font-weight: 800; color: #1e293b; margin: 0; }
.subs-page__subtitle { font-size: .8rem; color: #64748b; margin: .2rem 0 0; }
.subs-page__actions { display: -webkit-box; display: -ms-flexbox; display: flex; gap: .5rem; -ms-flex-wrap: wrap; flex-wrap: wrap; }

/* Stats */
.subs-stats { display: -webkit-box; display: -ms-flexbox; display: flex; -ms-flex-wrap: wrap; flex-wrap: wrap; gap: .75rem; }
.subs-stat { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-orient: vertical; -webkit-box-direction: normal; -ms-flex-direction: column; flex-direction: column; -webkit-box-align: center; -ms-flex-align: center; align-items: center; padding: .875rem 1.375rem; border-radius: .875rem; border: 1px solid; min-width: 90px; }
.subs-stat__label { font-size: .72rem; color: #64748b; font-weight: 500; }
.subs-stat__val { font-size: 1.625rem; font-weight: 800; margin-top: .2rem; }

/* Filter */
.subs-filter { background: #fff; border: 1px solid #e2e8f0; border-radius: .875rem; padding: 1rem 1.25rem; -webkit-box-shadow: 0 1px 3px rgba(0,0,0,.05); box-shadow: 0 1px 3px rgba(0,0,0,.05); }
.subs-filter__fields { display: -webkit-box; display: -ms-flexbox; display: flex; -ms-flex-wrap: wrap; flex-wrap: wrap; gap: .625rem; -webkit-box-align: center; -ms-flex-align: center; align-items: center; }
.subs-filter__field { position: relative; -webkit-box-flex: 1; -ms-flex: 1 1 200px; flex: 1 1 200px; }
.subs-filter__icon { position: absolute; top: 50%; right: .75rem; -webkit-transform: translateY(-50%); transform: translateY(-50%); width: 1rem; height: 1rem; color: #94a3b8; pointer-events: none; }
.subs-filter__input { width: 100%; -webkit-box-sizing: border-box; box-sizing: border-box; padding: .5rem .75rem .5rem 2.25rem; border-radius: .5rem; border: 1px solid #e2e8f0; font-size: .875rem; font-family: Vazirmatn, Tahoma, sans-serif; color: #1e293b; outline: none; }
.subs-filter__input:focus { border-color: #4f46e5; -webkit-box-shadow: 0 0 0 3px rgba(79,70,229,.12); box-shadow: 0 0 0 3px rgba(79,70,229,.12); }
.subs-filter__select { padding: .5rem .75rem; border-radius: .5rem; border: 1px solid #e2e8f0; font-size: .875rem; font-family: Vazirmatn, Tahoma, sans-serif; color: #1e293b; outline: none; -webkit-box-flex: 1; -ms-flex: 1 1 150px; flex: 1 1 150px; }
.subs-filter__select:focus { border-color: #4f46e5; }
.subs-filter__btns { display: -webkit-box; display: -ms-flexbox; display: flex; gap: .375rem; }

/* Table */
.subs-table-wrap { background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem; overflow: hidden; -webkit-box-shadow: 0 1px 4px rgba(0,0,0,.05); box-shadow: 0 1px 4px rgba(0,0,0,.05); overflow-x: auto; }
.subs-table { width: 100%; border-collapse: collapse; font-size: .8625rem; min-width: 600px; }
.subs-table th { text-align: right; padding: .75rem 1rem; background: #f8fafc; font-size: .72rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .03em; border-bottom: 1px solid #e2e8f0; }
.subs-table td { padding: .75rem 1rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; color: #334155; }
.subs-table tr:last-child td { border-bottom: none; }
.subs-table tbody tr:hover td { background: #f8fafc; }

.subs-table__user { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; gap: .625rem; }
.subs-table__avatar { width: 32px; height: 32px; border-radius: 50%; background: -webkit-linear-gradient(135deg, #4f46e5, #7c3aed); background: linear-gradient(135deg, #4f46e5, #7c3aed); color: #fff; font-size: .75rem; font-weight: 700; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; -webkit-box-pack: center; -ms-flex-pack: center; justify-content: center; -ms-flex-negative: 0; flex-shrink: 0; }
.subs-table__uname { font-weight: 600; color: #1e293b; font-size: .8625rem; }
.subs-table__umobile { font-size: .75rem; color: #94a3b8; margin-top: 1px; direction: ltr; text-align: right; }
.subs-table__plan { display: -webkit-inline-box; display: -ms-inline-flexbox; display: inline-flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; gap: .4rem; }
.subs-table__plan-dot { width: 8px; height: 8px; border-radius: 50%; -ms-flex-negative: 0; flex-shrink: 0; }
.subs-table__date { color: #64748b; font-size: .8rem; }
.subs-table__muted { color: #cbd5e1; }
.subs-table__empty { text-align: center; padding: 3rem 1rem; color: #94a3b8; }

/* Badge */
.sub-badge { display: inline-block; padding: .22rem .7rem; border-radius: 9999px; font-size: .73rem; font-weight: 700; }
.sub-badge--active    { background: #dcfce7; color: #15803d; }
.sub-badge--trial     { background: #dbeafe; color: #1d4ed8; }
.sub-badge--cancelled { background: #fee2e2; color: #be123c; }
.sub-badge--expired   { background: #f1f5f9; color: #64748b; }
.sub-badge--pending   { background: #fff7ed; color: #c2410c; }

/* Days */
.subs-days { font-size: .8rem; font-weight: 700; color: #15803d; }
.subs-days--warn { color: #ea580c; }
</style>
@endpush

@endsection
