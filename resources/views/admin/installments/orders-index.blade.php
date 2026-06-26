@extends('admin.layout')
@section('title', 'سفارشات قسطی')

@section('content')
<div class="admin-page-container">

    {{-- Header --}}
    <div class="admin-page-header flex items-center justify-between mb-6">
        <div>
            <h1 class="admin-page-title">سفارشات قسطی</h1>
            <p class="admin-page-subtitle text-sm text-slate-500 mt-1">مدیریت و پیگیری خریدهای قسطی کاربران</p>
        </div>
        <div style="display:flex;gap:.625rem;flex-wrap:wrap;align-items:center">
            <form method="POST" action="{{ route('admin.installments.mark-overdue') }}"
                  onsubmit="return confirm('اقساط سررسیدشده به وضعیت معوقه تغییر کنند؟')">
                @csrf
                <button type="submit" class="btn btn-secondary gap-2">
                    <x-core-ui::icon name="alert-triangle" class="w-4 h-4 text-amber-500" />
                    علامت‌گذاری معوقه‌ها
                </button>
            </form>
            <a href="{{ route('admin.installments.plans.index') }}" class="btn btn-primary gap-2">
                <x-core-ui::icon name="cog" class="w-4 h-4" />
                مدیریت طرح‌ها
            </a>
        </div>
    </div>

    {{-- Stats --}}
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:.875rem;margin-bottom:1.5rem">
        @foreach([
            ['label' => 'کل سفارشات', 'val' => $stats['total'],     'color' => '#6366f1', 'bg' => '#ede9fe'],
            ['label' => 'فعال',        'val' => $stats['active'],    'color' => '#1d4ed8', 'bg' => '#dbeafe'],
            ['label' => 'انتظار پیش‌پرداخت', 'val' => $stats['pending'],  'color' => '#92400e', 'bg' => '#fef3c7'],
            ['label' => 'معوقه',       'val' => $stats['defaulted'], 'color' => '#be123c', 'bg' => '#fee2e2'],
        ] as $s)
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:.875rem;padding:.875rem 1.125rem;box-shadow:0 1px 3px rgba(0,0,0,.05)">
            <div style="display:flex;align-items:center;justify-content:space-between">
                <span style="font-size:.72rem;color:#94a3b8;font-weight:700;text-transform:uppercase">{{ $s['label'] }}</span>
                <span style="background:{{ $s['bg'] }};color:{{ $s['color'] }};padding:.15rem .5rem;border-radius:9999px;font-size:.75rem;font-weight:700">
                    {{ \App\Helpers\NumberHelper::formatFa($s['val']) }}
                </span>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <form method="GET" class="admin-card mb-4 p-4" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end">
        <div style="flex:1;min-width:180px">
            <label class="form-label text-xs">جستجو (موبایل / نام)</label>
            <input type="text" name="search" value="{{ request('search') }}" class="form-input" placeholder="شماره موبایل یا نام...">
        </div>
        <div style="min-width:160px">
            <label class="form-label text-xs">وضعیت</label>
            <select name="status" class="form-input">
                <option value="">همه وضعیت‌ها</option>
                @foreach(['pending' => 'انتظار پرداخت', 'active' => 'فعال', 'completed' => 'تسویه‌شده', 'defaulted' => 'معوقه', 'cancelled' => 'لغوشده'] as $v => $l)
                <option value="{{ $v }}" {{ request('status') === $v ? 'selected' : '' }}>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">
            <x-core-ui::icon name="search" class="w-4 h-4 ml-1" />
            جستجو
        </button>
        @if(request()->hasAny(['search','status']))
        <a href="{{ route('admin.installments.orders.index') }}" class="btn btn-secondary btn-sm">پاک‌کردن</a>
        @endif
    </form>

    {{-- Table --}}
    <div class="admin-card overflow-hidden">
        @if($orders->isEmpty())
        <div class="admin-empty-state">
            <x-core-ui::icon name="inbox" class="w-12 h-12 text-slate-300 mx-auto mb-3" />
            <p class="text-slate-500">هیچ سفارش قسطی‌ای یافت نشد</p>
        </div>
        @else
        <div style="overflow-x:auto">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>کاربر</th>
                        <th>طرح</th>
                        <th>مبلغ کل</th>
                        <th>قسط ماهانه</th>
                        <th>پیشرفت</th>
                        <th>سررسید بعدی</th>
                        <th>وضعیت</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $order)
                    @php
                        $progress = $order->installments_total > 0
                            ? round($order->installments_paid / $order->installments_total * 100) : 0;
                        $stMap = [
                            'pending'   => ['text' => 'انتظار', 'cls' => 'badge-warning'],
                            'active'    => ['text' => 'فعال',   'cls' => 'badge-info'],
                            'completed' => ['text' => 'تسویه',  'cls' => 'badge-success'],
                            'defaulted' => ['text' => 'معوقه',  'cls' => 'badge-danger'],
                            'cancelled' => ['text' => 'لغو',    'cls' => 'badge-secondary'],
                        ];
                        $st = $stMap[$order->status] ?? ['text' => $order->status, 'cls' => 'badge-secondary'];
                        $nextDue = $order->nextPayment();
                    @endphp
                    <tr>
                        <td class="text-slate-400 text-xs">#{{ \App\Helpers\NumberHelper::formatFa($order->id) }}</td>
                        <td>
                            <div class="font-medium text-slate-800 text-sm">{{ $order->user?->name ?? '—' }}</div>
                            <div class="text-xs text-slate-400">{{ $order->user?->mobile ?? '' }}</div>
                        </td>
                        <td class="text-sm">
                            <div class="font-medium text-slate-700">{{ $order->plan?->name ?? '—' }}</div>
                            <div class="text-xs text-slate-400">{{ \App\Helpers\NumberHelper::formatFa($order->plan?->period_months ?? 0) }} ماهه</div>
                        </td>
                        <td class="text-sm font-semibold whitespace-nowrap">
                            @numFa($order->total_amount)
                            <small class="text-slate-400 font-normal">ت</small>
                        </td>
                        <td class="text-sm whitespace-nowrap">
                            @numFa($order->monthly_installment)
                            <small class="text-slate-400">ت</small>
                        </td>
                        <td style="min-width:120px">
                            <div style="display:flex;align-items:center;gap:.5rem">
                                <div style="flex:1;height:5px;background:#e2e8f0;border-radius:9999px;overflow:hidden">
                                    <div style="height:100%;width:{{ $progress }}%;background:{{ $progress>=100?'#22c55e':'#6366f1' }};border-radius:9999px"></div>
                                </div>
                                <span class="text-xs text-slate-500 whitespace-nowrap">
                                    {{ \App\Helpers\NumberHelper::formatFa($order->installments_paid) }}/{{ \App\Helpers\NumberHelper::formatFa($order->installments_total) }}
                                </span>
                            </div>
                        </td>
                        <td class="text-sm whitespace-nowrap">
                            @if($nextDue)
                                <span class="{{ $nextDue->isOverdue() ? 'text-red-600 font-semibold' : 'text-slate-600' }}">
                                    {{ \App\Helpers\Jalali::formatFa($nextDue->due_date, 'Y/m/d') }}
                                </span>
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $st['cls'] }}">{{ $st['text'] }}</span>
                        </td>
                        <td>
                            <a href="{{ route('admin.installments.orders.show', $order) }}" class="btn btn-sm btn-secondary">
                                <x-core-ui::icon name="eye" class="w-3.5 h-3.5 ml-1" />
                                جزئیات
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())
        <div class="p-4 border-t border-slate-100">{{ $orders->links() }}</div>
        @endif
        @endif
    </div>
</div>
@endsection
