@extends('account.layout')
@section('title', 'جزئیات اشتراک')
@section('header', 'جزئیات اشتراک')

@section('content')
<div class="subdetail" style="font-family:Vazirmatn,Tahoma,'Segoe UI',Arial,sans-serif">


{{-- هدر وضعیت --}}
@php $color = $subscription->plan->color ?? '#4f46e5'; @endphp
<div class="subdetail__hero" style="border-top:5px solid {{ $color }}">
    <div class="subdetail__hero-top">
        <div>
            <p class="subdetail__hero-plan">{{ $subscription->plan->name ?? 'اشتراک' }}</p>
            <p class="subdetail__hero-price" style="color:{{ $color }}">{{ $subscription->plan?->getFormattedPrice() ?? '' }}</p>
        </div>
        <span class="subdetail__badge subdetail__badge--{{ $subscription->status }}">
            {{ $subscription->getStatusLabel() }}
        </span>
    </div>

    @if($subscription->isActive() || $subscription->isInTrial())
    <div class="subdetail__progress-wrap">
        @php
            $total = $subscription->starts_at && $subscription->ends_at
                ? $subscription->starts_at->diffInDays($subscription->ends_at)
                : 1;
            $remaining = max(0, $subscription->daysRemaining());
            $elapsed   = max(0, $total - $remaining);
            $pct       = $total > 0 ? round(($elapsed / $total) * 100) : 0;
        @endphp
        <div class="subdetail__progress-row">
            <span class="subdetail__progress-label">پیشرفت اشتراک</span>
            <span class="subdetail__progress-days {{ $remaining <= 7 ? 'subdetail__progress-days--warn' : '' }}">
                {{ \App\Helpers\NumberHelper::formatFa($remaining) }} روز مانده
            </span>
        </div>
        <div class="subdetail__progress-bar">
            <div class="subdetail__progress-fill" style="width:{{ $pct }}%;background:{{ $color }}"></div>
        </div>
        <div class="subdetail__progress-dates">
            <span>{{ \App\Helpers\Jalali::formatFa($subscription->starts_at, 'Y/m/d') }}</span>
            <span>{{ \App\Helpers\Jalali::formatFa($subscription->ends_at, 'Y/m/d') }}</span>
        </div>
    </div>
    @endif
</div>

{{-- اطلاعات جزئی --}}
<div class="subdetail__grid">
    <div class="subdetail__info-card">
        <h3 class="subdetail__info-card__title">جزئیات اشتراک</h3>
        <dl class="subdetail__dl">
            <div class="subdetail__dl-row">
                <dt>تاریخ شروع</dt>
                <dd>{{ \App\Helpers\Jalali::formatFa($subscription->starts_at, 'Y/m/d') }}</dd>
            </div>
            <div class="subdetail__dl-row">
                <dt>تاریخ پایان</dt>
                <dd>{{ \App\Helpers\Jalali::formatFa($subscription->ends_at, 'Y/m/d') }}</dd>
            </div>
            @if($subscription->trial_ends_at)
            <div class="subdetail__dl-row">
                <dt>پایان آزمایشی</dt>
                <dd>{{ \App\Helpers\Jalali::formatFa($subscription->trial_ends_at, 'Y/m/d') }}</dd>
            </div>
            @endif
            @if($subscription->cancelled_at)
            <div class="subdetail__dl-row">
                <dt>تاریخ لغو</dt>
                <dd style="color:#dc2626">{{ \App\Helpers\Jalali::formatFa($subscription->cancelled_at, 'Y/m/d') }}</dd>
            </div>
            @endif
            @if($subscription->cancellation_reason)
            <div class="subdetail__dl-row">
                <dt>دلیل لغو</dt>
                <dd style="color:#92400e">{{ $subscription->cancellation_reason }}</dd>
            </div>
            @endif
            @if($subscription->invoice)
            <div class="subdetail__dl-row">
                <dt>شماره فاکتور</dt>
                <dd><span class="subdetail__invoice-num">{{ $subscription->invoice->invoice_number }}</span></dd>
            </div>
            @endif
        </dl>
    </div>

    @if($subscription->plan && $subscription->plan->getFeaturesArray())
    <div class="subdetail__info-card">
        <h3 class="subdetail__info-card__title">امکانات این پلن</h3>
        <ul class="subdetail__feats">
            @foreach($subscription->plan->getFeaturesArray() as $f)
            <li>
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="15" height="15" style="color:{{ $color }};-ms-flex-negative:0;flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                {{ $f }}
            </li>
            @endforeach
        </ul>
    </div>
    @endif
</div>

{{-- تاریخچه --}}
@if($subscription->logs->isNotEmpty())
<div class="subdetail__log-card">
    <h3 class="subdetail__info-card__title" style="padding:1rem 1.25rem;border-bottom:1px solid #f1f5f9;margin:0">تاریخچه</h3>
    <div class="subdetail__timeline">
        @foreach($subscription->logs as $log)
        <div class="subdetail__timeline-item">
            <div class="subdetail__timeline-dot subdetail__timeline-dot--{{ $log->action }}"></div>
            <div class="subdetail__timeline-content">
                <span class="subdetail__timeline-action">{{ $log->getActionLabel() }}</span>
                <span class="subdetail__timeline-time">{{ \App\Helpers\Jalali::formatFa($log->created_at, 'Y/m/d H:i') }}</span>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- عملیات --}}
<div class="subdetail__actions">
    <a href="{{ route('account.subscriptions.index') }}" class="subdetail__btn subdetail__btn--back">← بازگشت</a>
    @if($subscription->isActive() || $subscription->isInTrial())
    <form method="POST" action="{{ route('account.subscriptions.cancel', $subscription) }}"
          onsubmit="return confirm('آیا از لغو اشتراک اطمینان دارید؟');" style="display:inline">
        @csrf
        <button type="submit" class="subdetail__btn subdetail__btn--cancel">لغو اشتراک</button>
    </form>
    @endif
    @if($subscription->isPending())
    <div class="subdetail__pending-notice">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16" style="-ms-flex-negative:0;flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>این اشتراک در انتظار تأیید و پرداخت است. پس از حذف می‌توانید اشتراک جدید ثبت کنید.</span>
    </div>
    <form method="POST" action="{{ route('account.subscriptions.delete-pending', $subscription) }}"
          onsubmit="return confirm('درخواست اشتراک حذف شود؟');" style="display:inline">
        @csrf @method('DELETE')
        <button type="submit" class="subdetail__btn subdetail__btn--cancel">حذف درخواست</button>
    </form>
    @endif
    <a href="{{ route('account.subscriptions.plans') }}" class="subdetail__btn subdetail__btn--plans">مشاهده پلن‌ها</a>
</div>

</div>

@push('head')
<style>
.subdetail, .subdetail * { font-family: Vazirmatn, Tahoma, 'Segoe UI', Arial, sans-serif; }

/* hero */
.subdetail__hero { background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem; padding: 1.5rem; -webkit-box-shadow: 0 2px 8px rgba(0,0,0,.06); box-shadow: 0 2px 8px rgba(0,0,0,.06); margin-bottom: 1.25rem; }
.subdetail__hero-top { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: start; -ms-flex-align: start; align-items: flex-start; -webkit-box-pack: justify; -ms-flex-pack: justify; justify-content: space-between; gap: 1rem; margin-bottom: 1rem; }
.subdetail__hero-plan { font-size: 1.25rem; font-weight: 800; color: #1e293b; margin: 0 0 .25rem; }
.subdetail__hero-price { font-size: 1.5rem; font-weight: 800; margin: 0; }

/* progress */
.subdetail__progress-wrap { margin-top: .5rem; }
.subdetail__progress-row { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-pack: justify; -ms-flex-pack: justify; justify-content: space-between; -webkit-box-align: center; -ms-flex-align: center; align-items: center; margin-bottom: .5rem; }
.subdetail__progress-label { font-size: .78rem; color: #64748b; font-weight: 500; }
.subdetail__progress-days { font-size: .8rem; font-weight: 700; color: #16a34a; }
.subdetail__progress-days--warn { color: #ea580c; }
.subdetail__progress-bar { height: 8px; background: #f1f5f9; border-radius: 9999px; overflow: hidden; }
.subdetail__progress-fill { height: 100%; border-radius: 9999px; -webkit-transition: width .5s ease; transition: width .5s ease; }
.subdetail__progress-dates { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-pack: justify; -ms-flex-pack: justify; justify-content: space-between; font-size: .72rem; color: #94a3b8; margin-top: .375rem; }

/* badge */
.subdetail__badge { display: inline-block; padding: .3rem .875rem; border-radius: 9999px; font-size: .78rem; font-weight: 700; -ms-flex-negative: 0; flex-shrink: 0; }
.subdetail__badge--active    { background: #dcfce7; color: #15803d; }
.subdetail__badge--trial     { background: #dbeafe; color: #1d4ed8; }
.subdetail__badge--cancelled { background: #fee2e2; color: #be123c; }
.subdetail__badge--expired   { background: #f1f5f9; color: #64748b; }

/* grid */
.subdetail__grid { display: -ms-grid; display: grid; -ms-grid-columns: 1fr 1fr; grid-template-columns: 1fr 1fr; gap: 1.125rem; margin-bottom: 1.25rem; }
@media (max-width: 600px) { .subdetail__grid { grid-template-columns: 1fr; } }

/* info card */
.subdetail__info-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem; padding: 1.125rem; -webkit-box-shadow: 0 1px 4px rgba(0,0,0,.05); box-shadow: 0 1px 4px rgba(0,0,0,.05); }
.subdetail__info-card__title { font-size: .875rem; font-weight: 700; color: #374151; margin: 0 0 .875rem; }

/* dl */
.subdetail__dl { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-orient: vertical; -webkit-box-direction: normal; -ms-flex-direction: column; flex-direction: column; gap: .5rem; }
.subdetail__dl-row { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-pack: justify; -ms-flex-pack: justify; justify-content: space-between; -webkit-box-align: center; -ms-flex-align: center; align-items: center; font-size: .8375rem; padding: .375rem 0; border-bottom: 1px solid #f8fafc; }
.subdetail__dl-row:last-child { border-bottom: none; }
.subdetail__dl-row dt { color: #94a3b8; font-weight: 500; }
.subdetail__dl-row dd { font-weight: 600; color: #334155; margin: 0; }
.subdetail__invoice-num { font-family: monospace; font-size: .8rem; background: #f8fafc; padding: .15rem .5rem; border-radius: .3rem; color: #4f46e5; }

/* feats */
.subdetail__feats { list-style: none; padding: 0; margin: 0; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-orient: vertical; -webkit-box-direction: normal; -ms-flex-direction: column; flex-direction: column; gap: .5rem; }
.subdetail__feats li { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; gap: .5rem; font-size: .8375rem; color: #475569; }

/* log */
.subdetail__log-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem; overflow: hidden; -webkit-box-shadow: 0 1px 4px rgba(0,0,0,.05); box-shadow: 0 1px 4px rgba(0,0,0,.05); margin-bottom: 1.25rem; }
.subdetail__timeline { padding: .875rem 1.25rem; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-orient: vertical; -webkit-box-direction: normal; -ms-flex-direction: column; flex-direction: column; gap: .75rem; }
.subdetail__timeline-item { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; gap: .75rem; }
.subdetail__timeline-dot { width: 10px; height: 10px; border-radius: 50%; -ms-flex-negative: 0; flex-shrink: 0; }
.subdetail__timeline-dot--subscribed, .subdetail__timeline-dot--reactivated { background: #22c55e; }
.subdetail__timeline-dot--cancelled { background: #f43f5e; }
.subdetail__timeline-dot--renewed   { background: #3b82f6; }
.subdetail__timeline-dot--expired   { background: #94a3b8; }
.subdetail__timeline-dot--trial_started { background: #a78bfa; }
.subdetail__timeline-content { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-pack: justify; -ms-flex-pack: justify; justify-content: space-between; -webkit-box-align: center; -ms-flex-align: center; align-items: center; -webkit-box-flex: 1; -ms-flex: 1; flex: 1; gap: .5rem; -ms-flex-wrap: wrap; flex-wrap: wrap; }
.subdetail__timeline-action { font-size: .8375rem; font-weight: 600; color: #334155; }
.subdetail__timeline-time   { font-size: .75rem; color: #94a3b8; }

/* actions */
.subdetail__actions { display: -webkit-box; display: -ms-flexbox; display: flex; -ms-flex-wrap: wrap; flex-wrap: wrap; gap: .625rem; }
.subdetail__btn { display: inline-block; padding: .6rem 1.25rem; border-radius: .75rem; font-size: .875rem; font-weight: 600; cursor: pointer; border: 1px solid transparent; font-family: Vazirmatn, Tahoma, sans-serif; text-decoration: none; }
.subdetail__btn--back   { background: #f1f5f9; color: #475569; border-color: #e2e8f0; }
.subdetail__btn--back:hover { background: #e2e8f0; }
.subdetail__btn--cancel { background: #fff1f2; color: #be123c; border-color: #fecdd3; }
.subdetail__btn--cancel:hover { background: #ffe4e6; }
.subdetail__btn--plans  { background: #eef2ff; color: #4338ca; border-color: #c7d2fe; }
.subdetail__btn--plans:hover { background: #e0e7ff; }
.subdetail__pending-notice { display:-webkit-box; display:-ms-flexbox; display:flex; -webkit-box-align:center; -ms-flex-align:center; align-items:center; gap:.5rem; background:#fff7ed; border:1px solid #fed7aa; border-radius:.625rem; padding:.5rem .875rem; font-size:.8rem; color:#c2410c; width:100%; }
</style>
@endpush

@endsection
