@extends('account.layout')
@section('title', 'پلن‌های اشتراک')
@section('header', 'انتخاب پلن اشتراک')

@section('content')
<div class="plans-pg" style="font-family:Vazirmatn,Tahoma,'Segoe UI',Arial,sans-serif">


{{-- اشتراک فعال --}}
@if($active)
<div class="plans-pg__active-bar">
    <div class="plans-pg__active-bar__icon">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
    <div class="plans-pg__active-bar__info">
        <strong>اشتراک فعال: {{ $active->plan->name }}</strong>
        <span>تا {{ \App\Helpers\Jalali::formatFa($active->ends_at, 'Y/m/d') }} · {{ \App\Helpers\NumberHelper::formatFa($active->daysRemaining()) }} روز مانده</span>
    </div>
    <a href="{{ route('account.subscriptions.index') }}" class="plans-pg__active-bar__link">مدیریت اشتراک</a>
</div>
@endif

{{-- بنر بلاک: کاربر نمی‌تواند اشتراک جدید ثبت کند --}}
@if($blocked && !$active)
<div class="plans-pg__blocked-bar">
    <div class="plans-pg__blocked-bar__icon">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
    </div>
    <div class="plans-pg__blocked-bar__info">
        <strong>یک درخواست اشتراک در انتظار تأیید دارید</strong>
        <span>
            پلن «{{ $blocked->plan?->name }}» — در انتظار تأیید مدیریت است.
            تا پیش از تأیید یا حذف این درخواست، امکان ثبت اشتراک جدید وجود ندارد.
        </span>
    </div>
    <div style="display:-webkit-box;display:-ms-flexbox;display:flex;gap:.5rem;-ms-flex-wrap:wrap;flex-wrap:wrap">
        <a href="{{ route('account.subscriptions.index') }}" class="plans-pg__blocked-btn plans-pg__blocked-btn--view">مشاهده درخواست</a>
        <form method="POST" action="{{ route('account.subscriptions.delete-pending', $blocked) }}"
              onsubmit="return confirm('درخواست حذف شود؟');" style="display:inline">
            @csrf @method('DELETE')
            <button type="submit" class="plans-pg__blocked-btn plans-pg__blocked-btn--del">حذف درخواست</button>
        </form>
    </div>
</div>
@endif

{{-- عنوان --}}
<div class="plans-pg__intro">
    <h2 class="plans-pg__intro-title">یک پلن مناسب انتخاب کنید</h2>
    <p class="plans-pg__intro-desc">دسترسی نامحدود به امکانات، بدون تعهد بلندمدت</p>
</div>

{{-- کارت‌های پلن --}}
@if($plans->isEmpty())
<div class="plans-pg__empty">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="48" height="48" style="color:#c7d2fe;margin-bottom:1rem"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
    <p>در حال حاضر پلنی برای عضویت وجود ندارد.</p>
</div>
@else
<div class="plans-grid">
    @foreach($plans as $plan)
    @php
        $isActive = $active && $active->subscription_plan_id === $plan->id;
        $color    = $plan->color ?? '#4f46e5';
    @endphp

    <div class="plan-card {{ $plan->highlighted ? 'plan-card--featured' : '' }}" style="--plan-color:{{ $color }}">

        @if($plan->badge_text || $plan->highlighted)
        <div class="plan-card__badge" style="background:{{ $color }}">
            {{ $plan->badge_text ?: 'پیشنهادی' }}
        </div>
        @endif

        <div class="plan-card__top" style="border-top:4px solid {{ $color }}">
            @if($plan->image)
            <img src="{{ asset('storage/' . $plan->image) }}" alt="{{ $plan->name }}"
                 class="plan-card__img" loading="lazy">
            @endif
            <h3 class="plan-card__name">{{ $plan->name }}</h3>

            <div class="plan-card__price-row">
                @if($plan->isFree())
                <span class="plan-card__price plan-card__price--free">رایگان</span>
                @else
                <span class="plan-card__price" style="color:{{ $color }}">{{ \App\Helpers\NumberHelper::formatFa($plan->price) }}</span>
                <span class="plan-card__price-unit">تومان / {{ $plan->getDurationLabel() }}</span>
                @endif
            </div>

            @if($plan->hasTrial())
            <div class="plan-card__trial">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="13" height="13"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ \App\Helpers\NumberHelper::formatFa($plan->trial_days) }} روز آزمایشی رایگان
            </div>
            @endif
        </div>

        @if($plan->description)
        <p class="plan-card__desc">{{ strip_tags($plan->description) }}</p>
        @endif

        @if($plan->getFeaturesArray())
        <ul class="plan-card__feats">
            @foreach($plan->getFeaturesArray() as $feat)
            <li>
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14" style="color:{{ $color }}"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                {{ $feat }}
            </li>
            @endforeach
        </ul>
        @else
        <div style="-webkit-box-flex:1;-ms-flex:1;flex:1"></div>
        @endif

        <div class="plan-card__footer">
            @if($isActive)
            <div class="plan-card__current">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                پلن فعلی شما
            </div>
            @elseif($blocked)
            <div class="plan-card__blocked">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="15" height="15"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                @if($blocked->isPending()) در انتظار تأیید @else اشتراک فعال دارید @endif
            </div>
            @else
            <form method="POST" action="{{ route('account.subscriptions.subscribe') }}">
                @csrf
                <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                <button type="submit" class="plan-card__buy" style="background:{{ $color }}">
                    {{ $plan->isFree() ? 'شروع رایگان' : 'خرید اشتراک' }}
                </button>
            </form>
            @endif
        </div>

    </div>
    @endforeach
</div>
@endif

<div class="plans-pg__back">
    <a href="{{ route('account.subscriptions.index') }}">← تاریخچه اشتراک‌های من</a>
</div>

</div>

@push('head')
<style>
.plans-pg, .plans-pg * { font-family: Vazirmatn, Tahoma, 'Segoe UI', Arial, sans-serif; }

/* active bar */
.plans-pg__active-bar { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; gap: .875rem; background: #f0fdf4; border: 1.5px solid #bbf7d0; border-radius: .875rem; padding: .875rem 1.25rem; margin-bottom: 1.5rem; -ms-flex-wrap: wrap; flex-wrap: wrap; }
.plans-pg__active-bar__icon { width: 36px; height: 36px; background: #dcfce7; border-radius: 50%; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; -webkit-box-pack: center; -ms-flex-pack: center; justify-content: center; color: #16a34a; -ms-flex-negative: 0; flex-shrink: 0; }
.plans-pg__active-bar__info { -webkit-box-flex: 1; -ms-flex: 1; flex: 1; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-orient: vertical; -webkit-box-direction: normal; -ms-flex-direction: column; flex-direction: column; gap: .15rem; }
.plans-pg__active-bar__info strong { font-size: .9rem; font-weight: 700; color: #166534; }
.plans-pg__active-bar__info span  { font-size: .78rem; color: #16a34a; }
.plans-pg__active-bar__link { font-size: .8rem; color: #16a34a; font-weight: 600; text-decoration: none; border: 1px solid #86efac; padding: .35rem .875rem; border-radius: .5rem; }
.plans-pg__active-bar__link:hover { background: #dcfce7; }

/* intro */
.plans-pg__intro { text-align: center; margin-bottom: 1.75rem; }
.plans-pg__intro-title { font-size: 1.375rem; font-weight: 800; color: #1e293b; margin: 0 0 .375rem; }
.plans-pg__intro-desc  { font-size: .875rem; color: #64748b; margin: 0; }

/* empty */
.plans-pg__empty { text-align: center; background: #f8fafc; border: 2px dashed #e2e8f0; border-radius: 1rem; padding: 3rem 1.5rem; color: #94a3b8; }

/* grid */
.plans-grid { display: -ms-grid; display: grid; -ms-grid-columns: 1fr; grid-template-columns: 1fr; gap: 1.25rem; }
@media (min-width: 640px)  { .plans-grid { -ms-grid-columns: 1fr 1fr; grid-template-columns: 1fr 1fr; } }
@media (min-width: 1024px) { .plans-grid { -ms-grid-columns: 1fr 1fr 1fr; grid-template-columns: repeat(3, 1fr); } }

/* plan card */
.plan-card { position: relative; background: #fff; border: 1.5px solid #e2e8f0; border-radius: 1.25rem; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-orient: vertical; -webkit-box-direction: normal; -ms-flex-direction: column; flex-direction: column; overflow: hidden; -webkit-box-shadow: 0 2px 8px rgba(0,0,0,.06); box-shadow: 0 2px 8px rgba(0,0,0,.06); -webkit-transition: -webkit-box-shadow .2s, -webkit-transform .2s; transition: box-shadow .2s, transform .2s; }
.plan-card:hover { -webkit-box-shadow: 0 8px 24px rgba(0,0,0,.1); box-shadow: 0 8px 24px rgba(0,0,0,.1); -webkit-transform: translateY(-3px); transform: translateY(-3px); }
.plan-card--featured { border-color: #4f46e5; -webkit-box-shadow: 0 4px 20px rgba(79,70,229,.2); box-shadow: 0 4px 20px rgba(79,70,229,.2); }

.plan-card__badge { position: absolute; top: .875rem; left: .875rem; font-size: .7rem; font-weight: 700; color: #fff; padding: .2rem .6rem; border-radius: 9999px; z-index: 1; }

.plan-card__top { padding: 1.5rem 1.5rem 1rem; }
.plan-card__img { width: 64px; height: 64px; border-radius: .75rem; -o-object-fit: cover; object-fit: cover; display: block; margin: 0 auto .75rem; border: 1px solid #e2e8f0; }
.plan-card__name { font-size: 1.125rem; font-weight: 800; color: #1e293b; margin: 0 0 .75rem; }
.plan-card__price-row { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: end; -ms-flex-align: end; align-items: flex-end; gap: .375rem; }
.plan-card__price { font-size: 2rem; font-weight: 800; line-height: 1; }
.plan-card__price--free { font-size: 1.75rem; color: #16a34a; }
.plan-card__price-unit { font-size: .8rem; color: #94a3b8; padding-bottom: .2rem; }
.plan-card__trial { display: -webkit-inline-box; display: -ms-inline-flexbox; display: inline-flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; gap: .3rem; font-size: .75rem; color: #2563eb; margin-top: .5rem; background: #eff6ff; padding: .2rem .625rem; border-radius: 9999px; }

.plan-card__desc { font-size: .8125rem; color: #64748b; padding: 0 1.5rem .75rem; margin: 0; line-height: 1.6; }

.plan-card__feats { list-style: none; padding: .5rem 1.5rem; margin: 0; -webkit-box-flex: 1; -ms-flex: 1; flex: 1; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-orient: vertical; -webkit-box-direction: normal; -ms-flex-direction: column; flex-direction: column; gap: .5rem; }
.plan-card__feats li { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; gap: .5rem; font-size: .8375rem; color: #475569; }

.plan-card__footer { padding: 1.125rem 1.5rem 1.375rem; margin-top: auto; }
.plan-card__buy { width: 100%; padding: .75rem; border: none; border-radius: .75rem; color: #fff; font-size: .9375rem; font-weight: 700; cursor: pointer; font-family: Vazirmatn, Tahoma, sans-serif; -webkit-transition: opacity .15s, -webkit-transform .15s; transition: opacity .15s, transform .15s; }
.plan-card__buy:hover { opacity: .9; -webkit-transform: translateY(-1px); transform: translateY(-1px); }
.plan-card__current { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; -webkit-box-pack: center; -ms-flex-pack: center; justify-content: center; gap: .5rem; padding: .75rem; background: #f0fdf4; border: 1.5px solid #86efac; border-radius: .75rem; color: #16a34a; font-weight: 700; font-size: .875rem; }

/* blocked bar */
.plans-pg__blocked-bar { display:-webkit-box; display:-ms-flexbox; display:flex; -webkit-box-align:center; -ms-flex-align:center; align-items:center; gap:.875rem; background:#fff7ed; border:1.5px solid #fed7aa; border-radius:.875rem; padding:.875rem 1.25rem; margin-bottom:1.5rem; -ms-flex-wrap:wrap; flex-wrap:wrap; }
.plans-pg__blocked-bar__icon { width:36px; height:36px; background:#ffedd5; border-radius:50%; display:-webkit-box; display:-ms-flexbox; display:flex; -webkit-box-align:center; -ms-flex-align:center; align-items:center; -webkit-box-pack:center; -ms-flex-pack:center; justify-content:center; color:#c2410c; -ms-flex-negative:0; flex-shrink:0; }
.plans-pg__blocked-bar__info { -webkit-box-flex:1; -ms-flex:1; flex:1; display:-webkit-box; display:-ms-flexbox; display:flex; -webkit-box-orient:vertical; -webkit-box-direction:normal; -ms-flex-direction:column; flex-direction:column; gap:.2rem; min-width:180px; }
.plans-pg__blocked-bar__info strong { font-size:.875rem; font-weight:700; color:#9a3412; }
.plans-pg__blocked-bar__info span { font-size:.78rem; color:#c2410c; }
.plans-pg__blocked-btn { display:inline-block; padding:.35rem .875rem; border-radius:.5rem; font-size:.78rem; font-weight:700; cursor:pointer; border:1px solid transparent; font-family:Vazirmatn,Tahoma,sans-serif; text-decoration:none; }
.plans-pg__blocked-btn--view { background:#fff; color:#9a3412; border-color:#fed7aa; }
.plans-pg__blocked-btn--view:hover { background:#fff7ed; }
.plans-pg__blocked-btn--del { background:#fff1f2; color:#be123c; border-color:#fecdd3; }
.plans-pg__blocked-btn--del:hover { background:#ffe4e6; }

/* blocked plan button */
.plan-card__blocked { display:-webkit-box; display:-ms-flexbox; display:flex; -webkit-box-align:center; -ms-flex-align:center; align-items:center; -webkit-box-pack:center; -ms-flex-pack:center; justify-content:center; gap:.5rem; padding:.75rem; background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:.75rem; color:#94a3b8; font-weight:600; font-size:.875rem; }

/* back */
.plans-pg__back { text-align: center; margin-top: 1.5rem; }
.plans-pg__back a { font-size: .8125rem; color: #94a3b8; text-decoration: none; }
.plans-pg__back a:hover { color: #4f46e5; text-decoration: underline; }
</style>
@endpush

@endsection
