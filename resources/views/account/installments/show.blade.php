@extends('account.layout')
@section('title', 'جزئیات قسطی #' . $order->id)
@section('header', 'جزئیات خرید قسطی')

@section('content')
@php
    $statusMap = [
        'pending'   => ['text' => 'انتظار پرداخت پیش‌قسط', 'cls' => 'amber'],
        'active'    => ['text' => 'فعال',                   'cls' => 'blue'],
        'completed' => ['text' => 'تسویه‌شده',              'cls' => 'green'],
        'defaulted' => ['text' => 'معوقه',                  'cls' => 'red'],
        'cancelled' => ['text' => 'لغو‌شده',                'cls' => 'gray'],
    ];
    $stInfo   = $statusMap[$order->status] ?? ['text' => $order->status, 'cls' => 'gray'];
    $progress = $order->installments_total > 0
        ? round($order->installments_paid / $order->installments_total * 100) : 0;
    $totalPaid = $order->payments->where('status','paid')->sum('amount');
    $remaining = $order->total_amount - $order->down_payment - $totalPaid;
@endphp

<div style="font-family:Vazirmatn,Tahoma,'Segoe UI',Arial,sans-serif">

{{-- Back --}}
<a href="{{ route('account.installments') }}" style="display:inline-flex;align-items:center;gap:.375rem;color:#6366f1;font-size:.875rem;font-weight:600;text-decoration:none;margin-bottom:1.25rem">
    <x-core-ui::icon name="arrow-right" class="w-4 h-4" />
    بازگشت به لیست اقساط
</a>

{{-- Header card --}}
<div class="inst-show__header">
    <div style="flex:1;min-width:200px">
        <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap">
            <h2 style="font-size:1.15rem;font-weight:800;color:#fff;margin:0">
                {{ $order->plan?->name ?? 'طرح اقساطی' }}
                <span style="font-size:.8rem;opacity:.7;font-weight:500"> — #{{ \App\Helpers\NumberHelper::formatFa($order->id) }}</span>
            </h2>
            <span class="inst-show__badge inst-show__badge--{{ $stInfo['cls'] }}">{{ $stInfo['text'] }}</span>
        </div>
        @if($order->plan)
        <p style="font-size:.8rem;color:rgba(255,255,255,.75);margin:.5rem 0 0">
            طرح {{ \App\Helpers\NumberHelper::formatFa($order->plan->period_months) }}-ماهه
            @if($order->plan->annual_interest_rate > 0)
                · سود {{ \App\Helpers\NumberHelper::formatFa($order->plan->annual_interest_rate) }}٪ سالانه
            @else
                · بدون سود
            @endif
        </p>
        @endif
    </div>

    {{-- Progress ring --}}
    <div style="text-align:center;flex-shrink:0">
        <svg viewBox="0 0 56 56" style="width:56px;height:56px;transform:rotate(-90deg)">
            <circle cx="28" cy="28" r="24" fill="none" stroke="rgba(255,255,255,.2)" stroke-width="5"/>
            <circle cx="28" cy="28" r="24" fill="none" stroke="#fff" stroke-width="5"
                stroke-dasharray="{{ round(2 * 3.14159 * 24, 1) }}"
                stroke-dashoffset="{{ round(2 * 3.14159 * 24 * (1 - $progress/100), 1) }}"
                stroke-linecap="round"/>
        </svg>
        <div style="margin-top:.25rem;font-size:.78rem;color:rgba(255,255,255,.9);font-weight:700">
            {{ \App\Helpers\NumberHelper::formatFa($progress) }}٪
        </div>
    </div>
</div>

{{-- Financial summary --}}
<div class="inst-show__stats">
    <div class="inst-show__stat">
        <span class="inst-show__stat-lbl">مبلغ کل خرید</span>
        <span class="inst-show__stat-num">{{ \App\Helpers\NumberHelper::formatFa(number_format($order->total_amount)) }} <small>تومان</small></span>
    </div>
    <div class="inst-show__stat">
        <span class="inst-show__stat-lbl">پیش‌پرداخت</span>
        <span class="inst-show__stat-num">{{ \App\Helpers\NumberHelper::formatFa(number_format($order->down_payment)) }} <small>تومان</small></span>
    </div>
    <div class="inst-show__stat">
        <span class="inst-show__stat-lbl">قسط ماهانه</span>
        <span class="inst-show__stat-num">{{ \App\Helpers\NumberHelper::formatFa(number_format($order->monthly_installment)) }} <small>تومان</small></span>
    </div>
    <div class="inst-show__stat">
        <span class="inst-show__stat-lbl">مانده پرداخت‌نشده</span>
        <span class="inst-show__stat-num" style="color:{{ $remaining > 0 ? '#c2410c' : '#15803d' }}">
            {{ \App\Helpers\NumberHelper::formatFa(number_format(max(0, $remaining))) }} <small>تومان</small>
        </span>
    </div>
</div>

{{-- Progress bar --}}
@if($order->status === 'active' || $order->status === 'completed')
<div class="inst-show__card" style="margin-top:1.25rem;padding:1.25rem 1.5rem">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem">
        <span style="font-size:.875rem;font-weight:700;color:#374151">پیشرفت پرداخت اقساط</span>
        <span style="font-size:.8rem;color:#6366f1;font-weight:700">{{ \App\Helpers\NumberHelper::formatFa($order->installments_paid) }} از {{ \App\Helpers\NumberHelper::formatFa($order->installments_total) }} قسط</span>
    </div>
    <div style="height:10px;background:#e2e8f0;border-radius:9999px;overflow:hidden">
        <div style="height:100%;width:{{ $progress }}%;background:{{ $progress >= 100 ? 'linear-gradient(90deg,#22c55e,#16a34a)' : 'linear-gradient(90deg,#6366f1,#7c3aed)' }};border-radius:9999px;transition:width .4s"></div>
    </div>
    @if($order->next_due_date && $order->status === 'active')
    <p style="font-size:.78rem;color:#94a3b8;margin:.6rem 0 0;text-align:left">
        سر‌رسید قسط بعدی: <strong style="color:#374151">{{ \App\Helpers\Jalali::formatFa($order->next_due_date, 'Y/m/d') }}</strong>
    </p>
    @endif
</div>
@endif

{{-- Payment schedule --}}
@if($order->payments->isNotEmpty())
<div class="inst-show__card" style="margin-top:1.25rem">
    <div class="inst-show__card-head">
        <x-core-ui::icon name="calendar" class="w-4 h-4 ml-1 text-indigo-500 inline-block" />
        جدول پرداخت اقساط
    </div>
    <div style="overflow-x:auto">
        <table class="inst-show__table">
            <thead>
                <tr>
                    <th>قسط</th>
                    <th>سر‌رسید</th>
                    <th>مبلغ</th>
                    <th>وضعیت</th>
                    <th>تاریخ پرداخت</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->payments as $payment)
                @php
                    $pmapCls = [
                        'pending'   => 'amber',
                        'paid'      => 'green',
                        'overdue'   => 'red',
                        'cancelled' => 'gray',
                    ];
                    $pmapTxt = [
                        'pending'   => 'در انتظار',
                        'paid'      => 'پرداخت‌شده',
                        'overdue'   => 'معوق',
                        'cancelled' => 'لغو',
                    ];
                    $pCls = $pmapCls[$payment->status] ?? 'gray';
                    $pTxt = $pmapTxt[$payment->status] ?? $payment->status;
                    $canPay = in_array($payment->status, ['pending','overdue']) && $order->status === 'active';
                @endphp
                <tr style="{{ $payment->status === 'overdue' ? 'background:#fff1f2;' : '' }}">
                    <td style="font-weight:700;color:#6366f1;text-align:center">
                        {{ \App\Helpers\NumberHelper::formatFa($payment->installment_number) }}
                    </td>
                    <td style="white-space:nowrap;font-size:.875rem">
                        {{ \App\Helpers\Jalali::formatFa($payment->due_date, 'Y/m/d') }}
                    </td>
                    <td style="white-space:nowrap;font-weight:600">
                        {{ \App\Helpers\NumberHelper::formatFa(number_format($payment->amount)) }}
                        <span style="font-size:.72rem;color:#94a3b8">تومان</span>
                    </td>
                    <td>
                        <span class="inst-show__badge inst-show__badge--{{ $pCls }}">{{ $pTxt }}</span>
                    </td>
                    <td style="font-size:.8rem;color:#64748b;white-space:nowrap">
                        {{ $payment->paid_at ? \App\Helpers\Jalali::formatFa($payment->paid_at, 'Y/m/d') : '—' }}
                    </td>
                    <td>
                        @if($canPay)
                        <form method="POST" action="{{ route('installment.pay', [$order->id, $payment->id]) }}" style="display:inline">
                            @csrf
                            <button type="submit" class="inst-show__btn-pay">
                                <x-core-ui::icon name="credit-card" class="w-3.5 h-3.5 ml-1" />
                                پرداخت
                            </button>
                        </form>
                        @elseif($payment->status === 'paid')
                        <span style="font-size:.78rem;color:#22c55e;font-weight:600;display:inline-flex;align-items:center;gap:.25rem">
                            <x-core-ui::icon name="check-circle" class="w-3.5 h-3.5" />
                            تسویه
                        </span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@elseif($order->status === 'pending')
<div class="inst-show__card" style="margin-top:1.25rem;padding:1.5rem;text-align:center;background:#fffbeb;border-color:#fde68a">
    <x-core-ui::icon name="alert-triangle" class="w-8 h-8 text-amber-500 inline-block mb-2" />
    <p style="color:#92400e;font-weight:700;margin:.5rem 0 .25rem">منتظر پیش‌پرداخت</p>
    <p style="font-size:.8rem;color:#b45309;margin:0">پس از پرداخت پیش‌قسط، جدول اقساط تولید می‌شود.</p>
</div>
@endif

</div>

@push('head')
<style>
/* header gradient */
.inst-show__header { background:-webkit-linear-gradient(135deg,#6366f1,#7c3aed); background:linear-gradient(135deg,#6366f1,#7c3aed); border-radius:1rem; padding:1.375rem 1.5rem; display:-webkit-box; display:-ms-flexbox; display:flex; -webkit-box-align:center; -ms-flex-align:center; align-items:center; gap:1.25rem; -ms-flex-wrap:wrap; flex-wrap:wrap; -webkit-box-shadow:0 4px 18px rgba(99,102,241,.3); box-shadow:0 4px 18px rgba(99,102,241,.3); }

/* stats row */
.inst-show__stats { display:-webkit-box; display:-ms-flexbox; display:flex; gap:.75rem; -ms-flex-wrap:wrap; flex-wrap:wrap; margin-top:1.25rem; }
.inst-show__stat { background:#fff; border:1px solid #e2e8f0; border-radius:.875rem; padding:.875rem 1.125rem; -webkit-box-flex:1; -ms-flex:1 1 140px; flex:1 1 140px; -webkit-box-shadow:0 1px 3px rgba(0,0,0,.05); box-shadow:0 1px 3px rgba(0,0,0,.05); }
.inst-show__stat-lbl { display:block; font-size:.72rem; color:#94a3b8; margin-bottom:.25rem; }
.inst-show__stat-num { display:block; font-size:1.1rem; font-weight:800; color:#1e293b; }
.inst-show__stat-num small { font-size:.7rem; color:#94a3b8; font-weight:400; }

/* card */
.inst-show__card { background:#fff; border:1px solid #e2e8f0; border-radius:1rem; overflow:hidden; -webkit-box-shadow:0 1px 4px rgba(0,0,0,.05); box-shadow:0 1px 4px rgba(0,0,0,.05); }
.inst-show__card-head { padding:.875rem 1.25rem; background:#f8fafc; border-bottom:1px solid #e2e8f0; font-size:.875rem; font-weight:700; color:#374151; }

/* table */
.inst-show__table { width:100%; border-collapse:collapse; font-size:.875rem; min-width:500px; }
.inst-show__table th { text-align:right; padding:.625rem 1rem; background:#f8fafc; font-size:.72rem; font-weight:700; color:#64748b; text-transform:uppercase; border-bottom:1px solid #e2e8f0; }
.inst-show__table td { padding:.75rem 1rem; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.inst-show__table tr:last-child td { border-bottom:none; }

/* badges */
.inst-show__badge { display:inline-block; padding:.22rem .65rem; border-radius:9999px; font-size:.73rem; font-weight:700; }
.inst-show__badge--blue   { background:#dbeafe; color:#1d4ed8; }
.inst-show__badge--green  { background:#dcfce7; color:#15803d; }
.inst-show__badge--amber  { background:#fef3c7; color:#92400e; }
.inst-show__badge--red    { background:#fee2e2; color:#be123c; }
.inst-show__badge--gray   { background:#f1f5f9; color:#64748b; }

/* header badge */
.inst-show__badge.inst-show__badge--blue   { background:rgba(219,234,254,.85); }
.inst-show__badge.inst-show__badge--green  { background:rgba(220,252,231,.85); }
.inst-show__badge.inst-show__badge--amber  { background:rgba(254,243,199,.85); }

/* pay button */
.inst-show__btn-pay { display:inline-flex; align-items:center; padding:.3rem .875rem; border-radius:.5rem; font-size:.78rem; font-weight:700; background:#4f46e5; color:#fff; border:none; cursor:pointer; transition:background .15s; font-family:Vazirmatn,Tahoma,sans-serif; }
.inst-show__btn-pay:hover { background:#4338ca; color:#fff; }
</style>
@endpush

@endsection
