@extends('account.layout')
@section('title', 'اقساط من')
@section('header', 'اقساط من')

@section('content')
<div class="inst-acct" style="font-family:Vazirmatn,Tahoma,'Segoe UI',Arial,sans-serif">

@if($orders->isEmpty())
<div class="inst-acct__cta">
    <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" style="width:48px;height:48px;display:block;margin:0 auto .875rem">
        <rect width="48" height="48" rx="12" fill="#ede9fe"/>
        <path d="M14 18h20v14a2 2 0 01-2 2H16a2 2 0 01-2-2V18z" stroke="#7c3aed" stroke-width="2" stroke-linejoin="round"/>
        <path d="M10 18h28" stroke="#7c3aed" stroke-width="2" stroke-linecap="round"/>
        <path d="M20 14h8" stroke="#7c3aed" stroke-width="2" stroke-linecap="round"/>
        <path d="M20 25h8M20 29h5" stroke="#7c3aed" stroke-width="1.5" stroke-linecap="round"/>
    </svg>
    <p class="inst-acct__cta-title">هنوز خرید قسطی ندارید</p>
    <p class="inst-acct__cta-desc">برای خرید اقساطی محصولات، در هنگام سفارش گزینه «پرداخت قسطی» را انتخاب کنید.</p>
    <a href="{{ url('/') }}" class="inst-acct__cta-btn">مشاهده محصولات</a>
</div>
@else

{{-- خلاصه آمار --}}
@php
    $totalActive    = $orders->where('status', 'active')->count();
    $totalCompleted = $orders->where('status', 'completed')->count();
    $totalPending   = $orders->where('status', 'pending')->count();
    $nextPayment    = $orders->where('status', 'active')
        ->map(fn($o) => $o->nextPayment())->filter()->sortBy('due_date')->first();
@endphp
<div class="inst-acct__stats">
    <div class="inst-acct__stat inst-acct__stat--blue">
        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="width:26px;height:26px"><rect width="24" height="24" rx="6" fill="#dbeafe"/><path d="M7 12h4m6 0h-2M7 16h10M7 8h10" stroke="#1d4ed8" stroke-width="1.5" stroke-linecap="round"/></svg>
        <div>
            <p class="inst-acct__stat-num">{{ \App\Helpers\NumberHelper::formatFa($totalActive) }}</p>
            <p class="inst-acct__stat-lbl">قسطی فعال</p>
        </div>
    </div>
    <div class="inst-acct__stat inst-acct__stat--green">
        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="width:26px;height:26px"><rect width="24" height="24" rx="6" fill="#dcfce7"/><path d="M7 12l3 3 7-7" stroke="#15803d" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <div>
            <p class="inst-acct__stat-num">{{ \App\Helpers\NumberHelper::formatFa($totalCompleted) }}</p>
            <p class="inst-acct__stat-lbl">تسویه‌شده</p>
        </div>
    </div>
    <div class="inst-acct__stat inst-acct__stat--amber">
        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="width:26px;height:26px"><rect width="24" height="24" rx="6" fill="#fef3c7"/><path d="M12 8v4l2.5 2.5" stroke="#92400e" stroke-width="1.8" stroke-linecap="round"/><circle cx="12" cy="12" r="7" stroke="#92400e" stroke-width="1.5"/></svg>
        <div>
            <p class="inst-acct__stat-num">{{ \App\Helpers\NumberHelper::formatFa($totalPending) }}</p>
            <p class="inst-acct__stat-lbl">در انتظار پرداخت پیش‌قسط</p>
        </div>
    </div>
    @if($nextPayment)
    <div class="inst-acct__stat inst-acct__stat--purple">
        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="width:26px;height:26px"><rect width="24" height="24" rx="6" fill="#ede9fe"/><rect x="5" y="7" width="14" height="12" rx="2" stroke="#7c3aed" stroke-width="1.5"/><path d="M5 11h14M9 7V5M15 7V5" stroke="#7c3aed" stroke-width="1.5" stroke-linecap="round"/></svg>
        <div>
            <p class="inst-acct__stat-num" style="font-size:.85rem">{{ \App\Helpers\Jalali::formatFa($nextPayment->due_date, 'Y/m/d') }}</p>
            <p class="inst-acct__stat-lbl">سر‌رسید قسط بعدی</p>
        </div>
    </div>
    @endif
</div>

{{-- لیست سفارشات قسطی --}}
<div class="inst-acct__card" style="margin-top:1.5rem">
    <div class="inst-acct__card-head">
        <x-core-ui::icon name="list" class="w-4 h-4 inline-block ml-1 text-slate-500" />
        لیست خریدهای قسطی
    </div>
    <div style="overflow-x:auto">
        <table class="inst-acct__table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>طرح اقساطی</th>
                    <th>مبلغ کل</th>
                    <th>قسط ماهانه</th>
                    <th>پیشرفت</th>
                    <th>سر‌رسید بعدی</th>
                    <th>وضعیت</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $order)
                @php
                    $nextDue   = $order->nextPayment();
                    $progress  = $order->installments_total > 0
                        ? round($order->installments_paid / $order->installments_total * 100)
                        : 0;
                    $statusMap = [
                        'pending'   => ['text' => 'انتظار پرداخت', 'cls' => 'amber'],
                        'active'    => ['text' => 'فعال',          'cls' => 'blue'],
                        'completed' => ['text' => 'تسویه‌شده',     'cls' => 'green'],
                        'defaulted' => ['text' => 'معوقه',         'cls' => 'red'],
                        'cancelled' => ['text' => 'لغو‌شده',       'cls' => 'gray'],
                    ];
                    $stInfo = $statusMap[$order->status] ?? ['text' => $order->status, 'cls' => 'gray'];
                @endphp
                <tr>
                    <td style="color:#94a3b8;font-size:.78rem">#{{ \App\Helpers\NumberHelper::formatFa($order->id) }}</td>
                    <td>
                        <div style="font-weight:600;color:#1e293b;font-size:.875rem">{{ $order->plan?->name ?? '—' }}</div>
                        <div style="font-size:.72rem;color:#94a3b8;margin-top:.15rem">{{ \App\Helpers\NumberHelper::formatFa($order->plan?->period_months ?? 0) }} ماهه</div>
                    </td>
                    <td style="font-weight:600;color:#334155;white-space:nowrap">
                        @numFa($order->total_amount)
                        <span style="font-size:.72rem;color:#94a3b8">تومان</span>
                    </td>
                    <td style="white-space:nowrap">
                        @numFa($order->monthly_installment)
                        <span style="font-size:.72rem;color:#94a3b8">تومان</span>
                    </td>
                    <td style="min-width:120px">
                        <div style="display:flex;align-items:center;gap:.5rem">
                            <div style="flex:1;height:6px;background:#e2e8f0;border-radius:9999px;overflow:hidden">
                                <div style="height:100%;width:{{ $progress }}%;background:{{ $progress >= 100 ? '#22c55e' : '#6366f1' }};border-radius:9999px;transition:width .3s"></div>
                            </div>
                            <span style="font-size:.72rem;color:#64748b;white-space:nowrap">{{ \App\Helpers\NumberHelper::formatFa($order->installments_paid) }}/{{ \App\Helpers\NumberHelper::formatFa($order->installments_total) }}</span>
                        </div>
                    </td>
                    <td style="font-size:.8rem;color:#475569;white-space:nowrap">
                        @if($nextDue)
                            {{ \App\Helpers\Jalali::formatFa($nextDue->due_date, 'Y/m/d') }}
                            @if($nextDue->isOverdue())
                                <span class="inst-acct__badge inst-acct__badge--red" style="margin-right:.25rem">معوق</span>
                            @endif
                        @elseif($order->status === 'completed')
                            <span style="color:#22c55e">—</span>
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        <span class="inst-acct__badge inst-acct__badge--{{ $stInfo['cls'] }}">{{ $stInfo['text'] }}</span>
                    </td>
                    <td>
                        <a href="{{ route('account.installments.show', $order->id) }}" class="inst-acct__btn-sec">
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
    <div style="padding:.875rem 1.25rem;border-top:1px solid #f1f5f9">{{ $orders->links() }}</div>
    @endif
</div>

@endif
</div>

@push('head')
<style>
.inst-acct * { font-family: Vazirmatn, Tahoma, 'Segoe UI', Arial, sans-serif; }

/* cta */
.inst-acct__cta { background:#f8fafc; border:2px dashed #c4b5fd; border-radius:1rem; padding:2.5rem 1.5rem; text-align:center; }
.inst-acct__cta-title { font-size:1.0625rem; font-weight:700; color:#334155; margin:0 0 .375rem; }
.inst-acct__cta-desc { font-size:.875rem; color:#64748b; margin:0 0 1.125rem; }
.inst-acct__cta-btn { display:inline-block; background:-webkit-linear-gradient(135deg,#7c3aed,#4f46e5); background:linear-gradient(135deg,#7c3aed,#4f46e5); color:#fff; padding:.625rem 1.5rem; border-radius:.75rem; font-weight:700; text-decoration:none; font-size:.875rem; -webkit-box-shadow:0 3px 10px rgba(124,58,237,.3); box-shadow:0 3px 10px rgba(124,58,237,.3); }

/* stats */
.inst-acct__stats { display:-webkit-box; display:-ms-flexbox; display:flex; gap:.875rem; -ms-flex-wrap:wrap; flex-wrap:wrap; }
.inst-acct__stat { display:-webkit-box; display:-ms-flexbox; display:flex; -webkit-box-align:center; -ms-flex-align:center; align-items:center; gap:.875rem; background:#fff; border:1px solid #e2e8f0; border-radius:.875rem; padding:.875rem 1.125rem; -webkit-box-flex:1; -ms-flex:1 1 160px; flex:1 1 160px; -webkit-box-shadow:0 1px 3px rgba(0,0,0,.05); box-shadow:0 1px 3px rgba(0,0,0,.05); }
.inst-acct__stat-num { font-size:1.25rem; font-weight:800; color:#1e293b; margin:0; line-height:1; }
.inst-acct__stat-lbl { font-size:.72rem; color:#94a3b8; margin:.2rem 0 0; }

/* card */
.inst-acct__card { background:#fff; border:1px solid #e2e8f0; border-radius:1rem; overflow:hidden; -webkit-box-shadow:0 1px 4px rgba(0,0,0,.05); box-shadow:0 1px 4px rgba(0,0,0,.05); }
.inst-acct__card-head { padding:.875rem 1.25rem; background:#f8fafc; border-bottom:1px solid #e2e8f0; font-size:.875rem; font-weight:700; color:#374151; display:flex; align-items:center; gap:.5rem; }

/* table */
.inst-acct__table { width:100%; border-collapse:collapse; font-size:.875rem; min-width:680px; }
.inst-acct__table th { text-align:right; padding:.625rem 1rem; background:#f8fafc; font-size:.72rem; font-weight:700; color:#64748b; text-transform:uppercase; border-bottom:1px solid #e2e8f0; }
.inst-acct__table td { padding:.75rem 1rem; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.inst-acct__table tr:last-child td { border-bottom:none; }
.inst-acct__table tbody tr:hover td { background:#fafafa; }

/* badges */
.inst-acct__badge { display:inline-block; padding:.22rem .65rem; border-radius:9999px; font-size:.73rem; font-weight:700; }
.inst-acct__badge--blue   { background:#dbeafe; color:#1d4ed8; }
.inst-acct__badge--green  { background:#dcfce7; color:#15803d; }
.inst-acct__badge--amber  { background:#fef3c7; color:#92400e; }
.inst-acct__badge--red    { background:#fee2e2; color:#be123c; }
.inst-acct__badge--gray   { background:#f1f5f9; color:#64748b; }
.inst-acct__badge--purple { background:#ede9fe; color:#6d28d9; }

/* buttons */
.inst-acct__btn-sec { display:inline-flex; align-items:center; padding:.28rem .75rem; border-radius:.5rem; font-size:.78rem; font-weight:600; background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; text-decoration:none; transition:background .15s; }
.inst-acct__btn-sec:hover { background:#e2e8f0; }
.inst-acct__btn-pay { display:inline-flex; align-items:center; padding:.28rem .75rem; border-radius:.5rem; font-size:.78rem; font-weight:600; background:#4f46e5; color:#fff; border:none; text-decoration:none; cursor:pointer; transition:background .15s; font-family:Vazirmatn,Tahoma,sans-serif; }
.inst-acct__btn-pay:hover { background:#4338ca; }
</style>
@endpush

@endsection
