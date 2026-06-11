@extends('admin.layout')
@section('title', 'سفارشات')
@section('header', 'سفارشات')
@push('head')
<style>
.order-stat{display:flex;flex-direction:column;gap:.25rem;padding:1rem 1.25rem;border-radius:.875rem;background:#fff;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(15,23,42,.05);transition:all .2s}
.order-stat:hover{box-shadow:0 4px 16px rgba(99,102,241,.1);border-color:#a5b4fc;transform:translateY(-1px)}
.order-stat__label{font-size:.72rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.04em}
.order-stat__value{font-size:1.6rem;font-weight:800;color:#1e293b;line-height:1.1}
.order-stat__sub{font-size:.7rem;color:#94a3b8}
.order-stat--revenue{background:linear-gradient(135deg,#6366f1,#1e1b4b);border:none}
.order-stat--revenue .order-stat__label,.order-stat--revenue .order-stat__value,.order-stat--revenue .order-stat__sub{color:#fff}
.order-stat--revenue .order-stat__label{opacity:.8}

.status-filter{display:flex;flex-wrap:wrap;gap:.375rem;margin-bottom:1rem}
.status-pill{display:inline-flex;align-items:center;gap:.25rem;padding:.3rem .8rem;border-radius:9999px;font-size:.75rem;font-weight:600;border:1.5px solid transparent;cursor:pointer;text-decoration:none;transition:all .15s}
.status-pill:hover{text-decoration:none}
.status-pill--all{background:#f1f5f9;color:#475569;border-color:#e2e8f0}.status-pill--all:hover,.status-pill--all.active{background:#6366f1;color:#fff;border-color:#6366f1}
.status-pill--pending{background:#fef9c3;color:#854d0e;border-color:#fde68a}.status-pill--pending.active{background:#d97706;color:#fff;border-color:#d97706}
.status-pill--paid{background:#dcfce7;color:#166534;border-color:#bbf7d0}.status-pill--paid.active{background:#16a34a;color:#fff;border-color:#16a34a}
.status-pill--processing{background:#e0f2fe;color:#075985;border-color:#bae6fd}.status-pill--processing.active{background:#0284c7;color:#fff;border-color:#0284c7}
.status-pill--shipped{background:#ede9fe;color:#5b21b6;border-color:#ddd6fe}.status-pill--shipped.active{background:#7c3aed;color:#fff;border-color:#7c3aed}
.status-pill--completed{background:#d1fae5;color:#064e3b;border-color:#a7f3d0}.status-pill--completed.active{background:#059669;color:#fff;border-color:#059669}
.status-pill--cancelled{background:#fee2e2;color:#991b1b;border-color:#fecaca}.status-pill--cancelled.active{background:#dc2626;color:#fff;border-color:#dc2626}

.order-badge{display:inline-flex;align-items:center;padding:.2rem .6rem;border-radius:9999px;font-size:.72rem;font-weight:700}
.order-badge--pending{background:#fef9c3;color:#92400e}
.order-badge--paid{background:#dcfce7;color:#166534}
.order-badge--processing{background:#e0f2fe;color:#075985}
.order-badge--shipped{background:#ede9fe;color:#5b21b6}
.order-badge--completed{background:#d1fae5;color:#064e3b}
.order-badge--cancelled{background:#fee2e2;color:#991b1b}
</style>
@endpush
@section('content')

@include('admin.partials.breadcrumb', ['crumbs' => [
    ['label' => 'داشبورد', 'url' => route('admin.dashboard')],
    ['label' => 'سفارشات', 'url' => null],
]])

@include('admin.orders.guide')

<div class="admin-page">
    {{-- ── KPI Stats ── --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:1rem;margin-bottom:1.5rem">
        <div class="order-stat order-stat--revenue">
            <span class="order-stat__label">درآمد کل</span>
            <span class="order-stat__value">{{ \App\Helpers\NumberHelper::formatFa((int)$stats['revenue']) }}</span>
            <span class="order-stat__sub">تومان — سفارش‌های تکمیل‌شده</span>
        </div>
        <div class="order-stat">
            <span class="order-stat__label">کل سفارشات</span>
            <span class="order-stat__value">{{ \App\Helpers\NumberHelper::formatFa($stats['total']) }}</span>
            <span class="order-stat__sub">همهٔ وضعیت‌ها</span>
        </div>
        <div class="order-stat">
            <span class="order-stat__label">در انتظار پرداخت</span>
            <span class="order-stat__value" style="color:#d97706">{{ \App\Helpers\NumberHelper::formatFa($stats['pending']) }}</span>
            <span class="order-stat__sub">نیاز به پیگیری</span>
        </div>
        <div class="order-stat">
            <span class="order-stat__label">پرداخت شده</span>
            <span class="order-stat__value" style="color:#16a34a">{{ \App\Helpers\NumberHelper::formatFa($stats['paid']) }}</span>
            <span class="order-stat__sub">در حال پردازش</span>
        </div>
        <div class="order-stat">
            <span class="order-stat__label">تکمیل‌شده</span>
            <span class="order-stat__value" style="color:#059669">{{ \App\Helpers\NumberHelper::formatFa($stats['completed']) }}</span>
            <span class="order-stat__sub">تحویل داده شده</span>
        </div>
        <div class="order-stat">
            <span class="order-stat__label">لغو شده</span>
            <span class="order-stat__value" style="color:#dc2626">{{ \App\Helpers\NumberHelper::formatFa($stats['cancelled']) }}</span>
            <span class="order-stat__sub">مسترد / لغو</span>
        </div>
    </div>

    {{-- ── Filters ── --}}
    <form method="GET" action="{{ route('admin.orders.index') }}" class="card" style="padding:1rem 1.25rem;margin-bottom:1rem">
        <div style="display:flex;flex-wrap:wrap;gap:.75rem;align-items:flex-end">
            <div style="flex:1;min-width:200px">
                <label class="form-label" style="font-size:.75rem">جستجو</label>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="شماره سفارش، نام مشتری، موبایل…"
                       class="form-control" style="height:2.25rem;font-size:.85rem">
            </div>
            <div style="min-width:130px">
                <label class="form-label" style="font-size:.75rem">از تاریخ</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control" style="height:2.25rem;font-size:.85rem">
            </div>
            <div style="min-width:130px">
                <label class="form-label" style="font-size:.75rem">تا تاریخ</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control" style="height:2.25rem;font-size:.85rem">
            </div>
            <div style="display:flex;gap:.5rem">
                <button type="submit" class="btn btn-primary" style="height:2.25rem;padding:0 1rem;font-size:.85rem">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/></svg>
                    جستجو
                </button>
                @if(request()->hasAny(['q','status','date_from','date_to']))
                    <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary" style="height:2.25rem;padding:0 .75rem;font-size:.85rem">پاک</a>
                @endif
            </div>
        </div>
    </form>

    {{-- ── Status Filter Pills ── --}}
    <div class="status-filter">
        @php
            $statusLabels = [
                ''           => ['all',        'همه', $stats['total']],
                'pending'    => ['pending',    'در انتظار پرداخت', $stats['pending']],
                'paid'       => ['paid',       'پرداخت شده', $stats['paid']],
                'processing' => ['processing', 'در حال پردازش', $stats['processing']],
                'shipped'    => ['shipped',    'ارسال شده', $stats['shipped']],
                'completed'  => ['completed',  'تکمیل شده', $stats['completed']],
                'cancelled'  => ['cancelled',  'لغو شده', $stats['cancelled']],
            ];
        @endphp
        @foreach($statusLabels as $val => [$cls, $lbl, $cnt])
            <a href="{{ route('admin.orders.index', array_merge(request()->except('status','page'), $val ? ['status'=>$val] : [])) }}"
               class="status-pill status-pill--{{ $cls }} {{ request('status', '') === $val ? 'active' : '' }}">
                {{ $lbl }}
                <span style="font-size:.68rem;background:rgba(0,0,0,.1);border-radius:9999px;padding:.05rem .4rem">{{ \App\Helpers\NumberHelper::formatFa($cnt) }}</span>
            </a>
        @endforeach
    </div>

    {{-- ── Orders Table ── --}}
    <div class="card admin-table-wrap">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>شماره سفارش</th>
                        <th>مشتری</th>
                        <th>آیتم‌ها</th>
                        <th>مبلغ کل</th>
                        <th>وضعیت</th>
                        <th>تاریخ ثبت</th>
                        <th style="width:100px">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr style="cursor:pointer" onclick="window.location='{{ route('admin.orders.show', $order) }}'">
                            <td>
                                <span class="font-mono text-sm font-semibold text-indigo-700">{{ $order->number }}</span>
                            </td>
                            <td>
                                <div class="font-medium text-sm">{{ $order->customer_name }}</div>
                                @if($order->customer_mobile)
                                    <div class="text-xs text-slate-500" dir="ltr">{{ $order->customer_mobile }}</div>
                                @endif
                            </td>
                            <td>
                                <span class="text-sm text-slate-600">{{ \App\Helpers\NumberHelper::formatFa($order->items->count()) }} آیتم</span>
                            </td>
                            <td>
                                <span class="font-semibold text-sm">{{ \App\Helpers\NumberHelper::formatFa((int)$order->total) }}</span>
                                <span class="text-xs text-slate-500">تومان</span>
                            </td>
                            <td>
                                @php
                                    $statusMap = [
                                        'pending'    => ['label'=>'در انتظار', 'cls'=>'pending'],
                                        'paid'       => ['label'=>'پرداخت شده','cls'=>'paid'],
                                        'processing' => ['label'=>'در حال پردازش','cls'=>'processing'],
                                        'shipped'    => ['label'=>'ارسال شده','cls'=>'shipped'],
                                        'completed'  => ['label'=>'تکمیل شده','cls'=>'completed'],
                                        'cancelled'  => ['label'=>'لغو شده','cls'=>'cancelled'],
                                    ];
                                    $sm = $statusMap[$order->status] ?? ['label'=>$order->status,'cls'=>'pending'];
                                @endphp
                                <span class="order-badge order-badge--{{ $sm['cls'] }}">{{ $sm['label'] }}</span>
                            </td>
                            <td class="text-xs text-slate-600">
                                {{ \App\Helpers\Jalali::formatFa($order->created_at, 'Y/m/d H:i') }}
                            </td>
                            <td onclick="event.stopPropagation()">
                                <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-primary btn-sm">مشاهده</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-0">
                                <div class="admin-empty-state">
                                    <svg class="admin-empty-state__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    <p class="admin-empty-state__title">سفارشی یافت نشد</p>
                                    <p class="admin-empty-state__text">فیلترها را تغییر دهید یا منتظر ثبت سفارش جدید باشید.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($orders->hasPages())
        <div class="admin-pagination">{{ $orders->links() }}</div>
    @endif
</div>
@endsection
