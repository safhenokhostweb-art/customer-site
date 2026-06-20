@extends('admin.layout')
@section('title', 'سفارش قسطی #' . $order->id)

@section('content')
@php
    $stMap = [
        'pending'   => ['text' => 'انتظار پیش‌پرداخت', 'cls' => 'badge-warning'],
        'active'    => ['text' => 'فعال',               'cls' => 'badge-info'],
        'completed' => ['text' => 'تسویه‌شده',          'cls' => 'badge-success'],
        'defaulted' => ['text' => 'معوقه',              'cls' => 'badge-danger'],
        'cancelled' => ['text' => 'لغوشده',             'cls' => 'badge-secondary'],
    ];
    $st        = $stMap[$order->status] ?? ['text' => $order->status, 'cls' => 'badge-secondary'];
    $progress  = $order->installments_total > 0
        ? round($order->installments_paid / $order->installments_total * 100) : 0;
    $totalPaid = $order->payments->where('status','paid')->sum('amount') + $order->down_payment;
@endphp
<div class="admin-page-container">

    <div class="admin-page-header flex items-center gap-3 mb-6">
        <a href="{{ route('admin.installments.orders.index') }}" class="p-2 rounded-lg hover:bg-slate-100 text-slate-500 transition">
            <x-core-ui::icon name="arrow-right" class="w-5 h-5" />
        </a>
        <div class="flex-1">
            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="admin-page-title">سفارش قسطی #{{ \App\Helpers\NumberHelper::formatFa($order->id) }}</h1>
                <span class="badge {{ $st['cls'] }}">{{ $st['text'] }}</span>
            </div>
            <p class="admin-page-subtitle text-sm text-slate-500 mt-0.5">{{ $order->plan?->name ?? '—' }}</p>
        </div>
    </div>

    {{-- Info grid --}}
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.5rem">

        {{-- User card --}}
        <div class="admin-card p-5">
            <h3 class="text-sm font-bold text-slate-600 mb-3 flex items-center gap-2">
                <x-core-ui::icon name="user" class="w-4 h-4 text-indigo-500" />
                اطلاعات کاربر
            </h3>
            <dl style="display:grid;row-gap:.625rem">
                <div style="display:grid;grid-template-columns:100px 1fr;gap:.5rem">
                    <dt class="text-xs text-slate-400">نام</dt>
                    <dd class="text-sm font-medium text-slate-800">{{ $order->user?->name ?? '—' }}</dd>
                </div>
                <div style="display:grid;grid-template-columns:100px 1fr;gap:.5rem">
                    <dt class="text-xs text-slate-400">موبایل</dt>
                    <dd class="text-sm text-slate-700 dir-ltr">{{ $order->user?->mobile ?? '—' }}</dd>
                </div>
                @if($order->order_id)
                <div style="display:grid;grid-template-columns:100px 1fr;gap:.5rem">
                    <dt class="text-xs text-slate-400">سفارش فروشگاه</dt>
                    <dd class="text-sm text-indigo-600">#{{ \App\Helpers\NumberHelper::formatFa($order->order_id) }}</dd>
                </div>
                @endif
            </dl>
        </div>

        {{-- Plan card --}}
        <div class="admin-card p-5">
            <h3 class="text-sm font-bold text-slate-600 mb-3 flex items-center gap-2">
                <x-core-ui::icon name="credit-card" class="w-4 h-4 text-indigo-500" />
                جزئیات طرح
            </h3>
            <dl style="display:grid;row-gap:.625rem">
                <div style="display:grid;grid-template-columns:120px 1fr;gap:.5rem">
                    <dt class="text-xs text-slate-400">مبلغ کل</dt>
                    <dd class="text-sm font-semibold text-slate-800">{{ \App\Helpers\NumberHelper::formatFa(number_format($order->total_amount)) }} تومان</dd>
                </div>
                <div style="display:grid;grid-template-columns:120px 1fr;gap:.5rem">
                    <dt class="text-xs text-slate-400">پیش‌پرداخت</dt>
                    <dd class="text-sm text-slate-700">{{ \App\Helpers\NumberHelper::formatFa(number_format($order->down_payment)) }} تومان</dd>
                </div>
                <div style="display:grid;grid-template-columns:120px 1fr;gap:.5rem">
                    <dt class="text-xs text-slate-400">قسط ماهانه</dt>
                    <dd class="text-sm font-semibold text-indigo-600">{{ \App\Helpers\NumberHelper::formatFa(number_format($order->monthly_installment)) }} تومان</dd>
                </div>
                <div style="display:grid;grid-template-columns:120px 1fr;gap:.5rem">
                    <dt class="text-xs text-slate-400">پرداخت‌شده</dt>
                    <dd class="text-sm font-semibold text-green-600">{{ \App\Helpers\NumberHelper::formatFa(number_format($totalPaid)) }} تومان</dd>
                </div>
                <div style="display:grid;grid-template-columns:120px 1fr;gap:.5rem">
                    <dt class="text-xs text-slate-400">تاریخ شروع</dt>
                    <dd class="text-sm text-slate-700">{{ $order->start_date ? \App\Helpers\Jalali::formatFa($order->start_date, 'Y/m/d') : '—' }}</dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- Progress --}}
    @if($order->installments_total > 0)
    <div class="admin-card p-5 mb-4">
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-bold text-slate-600">پیشرفت اقساط</h3>
            <span class="text-sm font-bold {{ $progress>=100?'text-green-600':'text-indigo-600' }}">
                {{ \App\Helpers\NumberHelper::formatFa($order->installments_paid) }} از {{ \App\Helpers\NumberHelper::formatFa($order->installments_total) }} قسط ({{ \App\Helpers\NumberHelper::formatFa($progress) }}٪)
            </span>
        </div>
        <div style="height:10px;background:#e2e8f0;border-radius:9999px;overflow:hidden">
            <div style="height:100%;width:{{ $progress }}%;background:{{ $progress>=100?'linear-gradient(90deg,#22c55e,#16a34a)':'linear-gradient(90deg,#6366f1,#7c3aed)' }};border-radius:9999px;transition:width .4s"></div>
        </div>
    </div>
    @endif

    {{-- Payment schedule --}}
    @if($order->payments->isNotEmpty())
    <div class="admin-card overflow-hidden">
        <div style="padding:.875rem 1.25rem;background:#f8fafc;border-bottom:1px solid #e2e8f0;font-size:.875rem;font-weight:700;color:#374151;display:flex;align-items:center;gap:.5rem">
            <x-core-ui::icon name="calendar" class="w-4 h-4 text-indigo-500" />
            جدول اقساط
        </div>
        <div style="overflow-x:auto">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>قسط</th>
                        <th>سررسید</th>
                        <th>مبلغ</th>
                        <th>وضعیت</th>
                        <th>تاریخ پرداخت</th>
                        <th>مرجع</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->payments as $p)
                    @php
                        $pmCls = ['pending'=>'badge-warning','paid'=>'badge-success','overdue'=>'badge-danger','cancelled'=>'badge-secondary'];
                        $pmTxt = ['pending'=>'در انتظار','paid'=>'پرداخت‌شده','overdue'=>'معوق','cancelled'=>'لغو'];
                    @endphp
                    <tr style="{{ $p->status==='overdue'?'background:#fff1f2':'' }}">
                        <td class="text-center font-bold text-indigo-600">{{ \App\Helpers\NumberHelper::formatFa($p->installment_number) }}</td>
                        <td class="text-sm whitespace-nowrap">{{ \App\Helpers\Jalali::formatFa($p->due_date, 'Y/m/d') }}</td>
                        <td class="text-sm font-semibold whitespace-nowrap">{{ \App\Helpers\NumberHelper::formatFa(number_format($p->amount)) }} <small class="text-slate-400 font-normal">ت</small></td>
                        <td><span class="badge {{ $pmCls[$p->status] ?? 'badge-secondary' }}">{{ $pmTxt[$p->status] ?? $p->status }}</span></td>
                        <td class="text-sm text-slate-500 whitespace-nowrap">{{ $p->paid_at ? \App\Helpers\Jalali::formatFa($p->paid_at, 'Y/m/d') : '—' }}</td>
                        <td class="text-xs text-slate-400 dir-ltr">{{ $p->gateway_ref ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>
@endsection
