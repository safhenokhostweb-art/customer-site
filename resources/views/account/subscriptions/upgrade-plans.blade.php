@extends('account.layout')
@section('title', 'تغییر / ارتقاء پلن')
@section('header', 'تغییر پلن اشتراک')

@section('content')
<div class="upg-pg" style="font-family:Vazirmatn,Tahoma,'Segoe UI',Arial,sans-serif">

{{-- کارت اشتراک فعلی --}}
<div class="upg-pg__current-bar">
    <div class="upg-pg__current-bar__icon">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
        </svg>
    </div>
    <div class="upg-pg__current-bar__info">
        <strong>پلن فعلی: {{ $subscription->plan->name }}</strong>
        <span>
            {{ \App\Helpers\Jalali::formatFa($subscription->ends_at, 'Y/m/d') }} تا
            · {{ \App\Helpers\NumberHelper::formatFa($subscription->daysRemaining()) }} روز مانده
            @php $credit = app(\App\Services\SubscriptionService::class)->calculateCredit($subscription); @endphp
            @if($credit > 0)
            · اعتبار باقیمانده: <strong>{{ \App\Helpers\NumberHelper::formatFa($credit) }} تومان</strong>
            @endif
        </span>
    </div>
    <a href="{{ route('account.subscriptions.index') }}" class="upg-pg__back-link">بازگشت</a>
</div>

{{-- پیام‌های flash --}}
@if(session('error'))
<div class="upg-pg__alert upg-pg__alert--error">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    {{ session('error') }}
</div>
@endif

{{-- عنوان --}}
<div class="upg-pg__intro">
    <h2 class="upg-pg__intro-title">پلن مورد نظر را انتخاب کنید</h2>
    <p class="upg-pg__intro-desc">اعتبار روزهای مانده از پلن فعلی شما به‌صورت تخفیف اعمال می‌شود</p>
</div>

@if($previews->isEmpty())
<div class="upg-pg__empty">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="48" height="48"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
    <p>پلن دیگری برای تغییر وجود ندارد.</p>
</div>
@else
<div class="upg-grid">
    @foreach($previews as $item)
    @php
        /** @var \App\Models\SubscriptionPlan $plan */
        $plan    = $item['plan'];
        $preview = $item['preview'];
        $color   = $plan->color ?? '#4f46e5';
        $isUp    = $preview['direction'] === 'upgrade';
        $isDirect = $preview['is_direct'] || $preview['is_free'];
    @endphp

    <div class="upg-card {{ $plan->highlighted ? 'upg-card--featured' : '' }} {{ $isUp ? 'upg-card--upgrade' : 'upg-card--downgrade' }}" style="--card-color:{{ $color }}">

        {{-- نوار بالا --}}
        <div class="upg-card__header" style="background:linear-gradient(135deg,{{ $color }}22,{{ $color }}08);border-bottom:3px solid {{ $color }}">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem">
                <h3 class="upg-card__name">{{ $plan->name }}</h3>
                <div style="display:flex;flex-direction:column;align-items:flex-end;gap:.3rem">
                    @if($plan->badge_text || $plan->highlighted)
                    <span class="upg-card__badge" style="background:{{ $color }}">{{ $plan->badge_text ?: 'پیشنهادی' }}</span>
                    @endif
                    <span class="upg-card__dir-badge upg-card__dir-badge--{{ $isUp ? 'up' : 'down' }}">
                        @if($isUp)
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="11" height="11"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                        ارتقاء
                        @else
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="11" height="11"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                        تنزل
                        @endif
                    </span>
                </div>
            </div>

            {{-- قیمت اصلی --}}
            <div class="upg-card__orig-price">
                قیمت پلن:
                <strong style="color:{{ $color }}">
                    @if($plan->isFree()) رایگان
                    @else {{ \App\Helpers\NumberHelper::formatFa($plan->getEffectivePrice()) }} تومان
                    @endif
                </strong>
                @if(! $plan->isFree())
                / {{ $plan->getDurationLabel() }}
                @endif
            </div>
        </div>

        {{-- محاسبه هزینه --}}
        <div class="upg-card__calc">
            <div class="upg-card__calc-title">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="13" height="13"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                محاسبه هزینه
            </div>

            <div class="upg-card__calc-rows">
                <div class="upg-card__calc-row">
                    <span>قیمت پلن جدید</span>
                    <span class="upg-card__calc-val">
                        @if($plan->isFree()) رایگان
                        @else {{ \App\Helpers\NumberHelper::formatFa($preview['new_price']) }} تومان
                        @endif
                    </span>
                </div>
                @if($preview['credit'] > 0)
                <div class="upg-card__calc-row upg-card__calc-row--credit">
                    <span>اعتبار باقیمانده شما</span>
                    <span class="upg-card__calc-val upg-card__calc-val--green">
                        − {{ \App\Helpers\NumberHelper::formatFa($preview['credit']) }} تومان
                    </span>
                </div>
                @endif
                <div class="upg-card__calc-row upg-card__calc-row--total">
                    <span><strong>مبلغ پرداختی</strong></span>
                    <span class="upg-card__calc-total" style="color:{{ $color }}">
                        @if($isDirect) <strong>رایگان</strong>
                        @else <strong>{{ \App\Helpers\NumberHelper::formatFa($preview['amount_to_pay']) }} تومان</strong>
                        @endif
                    </span>
                </div>
            </div>

            @if($preview['leftover_points'] > 0)
            <div class="upg-card__leftover">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="13" height="13"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/></svg>
                {{ \App\Helpers\NumberHelper::formatFa($preview['leftover_points']) }} امتیاز به حساب شما اضافه می‌شود
            </div>
            @endif
        </div>

        {{-- مزایای ویژه --}}
        @php $badges = $plan->getBenefitBadges(); @endphp
        @if(count($badges))
        <div class="upg-card__benefits">
            <div class="upg-card__benefits-title">مزایای این پلن</div>
            <div class="upg-card__benefits-list">
                @foreach($badges as $badge)
                <div class="upg-card__benefit-item">
                    <span class="upg-card__benefit-icon" style="color:{{ $color }};background:{{ $color }}18">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="13" height="13">
                            @if($badge['icon'] === 'tag')
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            @elseif($badge['icon'] === 'truck')
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10m10 0H3m10 0h4l3-3V10l-3-3h-4v9z"/>
                            @elseif($badge['icon'] === 'headset')
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 18v-6a9 9 0 0118 0v6"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 19a2 2 0 01-2 2h-1a2 2 0 01-2-2v-3a2 2 0 012-2h3zM3 19a2 2 0 002 2h1a2 2 0 002-2v-3a2 2 0 00-2-2H3z"/>
                            @elseif($badge['icon'] === 'gift')
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/>
                            @else
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                            @endif
                        </svg>
                    </span>
                    <span>{{ $badge['label'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- دکمه --}}
        <div class="upg-card__footer">
            <form method="POST" action="{{ route('account.subscriptions.upgrade', $subscription) }}">
                @csrf
                <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                <button type="submit"
                        class="upg-card__btn"
                        style="background:linear-gradient(135deg,{{ $color }},{{ $color }}cc)"
                        onclick="return confirm('پلن شما به «{{ $plan->name }}» تغییر خواهد یافت. ادامه می‌دهید؟')">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                        @if($isDirect)
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        @else
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        @endif
                    </svg>
                    @if($isDirect)
                        تغییر فوری (رایگان)
                    @else
                        پرداخت {{ \App\Helpers\NumberHelper::formatFa($preview['amount_to_pay']) }} تومان
                    @endif
                </button>
            </form>
        </div>

    </div>
    @endforeach
</div>
@endif

<div class="upg-pg__bottom-note">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    اعتبار باقیمانده بر اساس نسبت روزهای مانده به کل مدت پلن محاسبه می‌شود.
</div>

</div>

@push('head')
<style>
.upg-pg,.upg-pg *{font-family:Vazirmatn,Tahoma,'Segoe UI',Arial,sans-serif}

/* Current bar */
.upg-pg__current-bar{display:flex;align-items:center;gap:.875rem;background:#eff6ff;border:1.5px solid #bfdbfe;border-radius:.875rem;padding:.875rem 1.25rem;margin-bottom:1.5rem;flex-wrap:wrap}
.upg-pg__current-bar__icon{width:40px;height:40px;background:#dbeafe;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#2563eb;flex-shrink:0}
.upg-pg__current-bar__info{flex:1;display:flex;flex-direction:column;gap:.2rem}
.upg-pg__current-bar__info strong{font-size:.9rem;font-weight:700;color:#1e40af}
.upg-pg__current-bar__info span{font-size:.8rem;color:#3b82f6}
.upg-pg__back-link{font-size:.8rem;color:#2563eb;font-weight:600;text-decoration:none;border:1px solid #93c5fd;padding:.35rem .875rem;border-radius:.5rem;flex-shrink:0;white-space:nowrap}
.upg-pg__back-link:hover{background:#dbeafe}

/* Alert */
.upg-pg__alert{display:flex;align-items:center;gap:.625rem;padding:.75rem 1rem;border-radius:.75rem;margin-bottom:1rem;font-size:.85rem;font-weight:600}
.upg-pg__alert--error{background:#fff1f2;color:#be123c;border:1px solid #fecdd3}

/* Intro */
.upg-pg__intro{text-align:center;margin-bottom:2rem}
.upg-pg__intro-title{font-size:1.4rem;font-weight:800;color:#1e293b;margin:0 0 .35rem}
.upg-pg__intro-desc{font-size:.875rem;color:#64748b;margin:0}

/* Empty */
.upg-pg__empty{text-align:center;background:#f8fafc;border:2px dashed #e2e8f0;border-radius:1rem;padding:3rem;color:#94a3b8}

/* Grid */
.upg-grid{display:grid;grid-template-columns:1fr;gap:1.5rem}
@media(min-width:640px){.upg-grid{grid-template-columns:1fr 1fr}}
@media(min-width:1024px){.upg-grid{grid-template-columns:repeat(3,1fr)}}

/* Card */
.upg-card{background:#fff;border:1.5px solid #e2e8f0;border-radius:1.375rem;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 4px 16px rgba(0,0,0,.06);transition:box-shadow .25s,transform .25s}
.upg-card:hover{box-shadow:0 12px 40px rgba(0,0,0,.13);transform:translateY(-4px)}
.upg-card--featured{border-color:var(--card-color);box-shadow:0 6px 24px rgba(79,70,229,.15)}
.upg-card--upgrade{border-top:3px solid var(--card-color)}
.upg-card--downgrade{border-top:3px solid #94a3b8}

/* Card Header */
.upg-card__header{padding:1.125rem 1.25rem .875rem}
.upg-card__name{font-size:1.1rem;font-weight:800;color:#1e293b;margin:0}
.upg-card__badge{display:inline-block;font-size:.68rem;font-weight:800;color:#fff;padding:.2rem .5rem;border-radius:9999px}
.upg-card__dir-badge{display:inline-flex;align-items:center;gap:.25rem;font-size:.7rem;font-weight:700;padding:.2rem .5rem;border-radius:9999px}
.upg-card__dir-badge--up{background:#f0fdf4;color:#16a34a;border:1px solid #86efac}
.upg-card__dir-badge--down{background:#f8fafc;color:#64748b;border:1px solid #e2e8f0}
.upg-card__orig-price{font-size:.8rem;color:#64748b;margin-top:.625rem}

/* Calc section */
.upg-card__calc{padding:.875rem 1.25rem;background:#f8fafc;border-top:1px solid #f1f5f9}
.upg-card__calc-title{display:flex;align-items:center;gap:.375rem;font-size:.72rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.04em;margin-bottom:.625rem}
.upg-card__calc-rows{display:flex;flex-direction:column;gap:.4rem}
.upg-card__calc-row{display:flex;justify-content:space-between;align-items:center;font-size:.82rem;color:#475569}
.upg-card__calc-row--credit{color:#16a34a}
.upg-card__calc-row--total{padding-top:.5rem;border-top:1px dashed #e2e8f0;font-size:.875rem}
.upg-card__calc-val{font-weight:600}
.upg-card__calc-val--green{color:#16a34a}
.upg-card__calc-total{font-size:1.125rem;font-weight:800}

/* Leftover note */
.upg-card__leftover{display:flex;align-items:center;gap:.375rem;margin-top:.625rem;background:#fef3c7;border:1px solid #fde68a;border-radius:.5rem;padding:.375rem .625rem;font-size:.75rem;color:#92400e;font-weight:600}

/* Benefits */
.upg-card__benefits{padding:.75rem 1.25rem;border-top:1px solid #f1f5f9}
.upg-card__benefits-title{font-size:.72rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.04em;margin-bottom:.5rem}
.upg-card__benefits-list{display:flex;flex-direction:column;gap:.375rem}
.upg-card__benefit-item{display:flex;align-items:center;gap:.5rem;font-size:.8rem;color:#334155;font-weight:600}
.upg-card__benefit-icon{width:22px;height:22px;border-radius:.35rem;display:flex;align-items:center;justify-content:center;flex-shrink:0}

/* Footer */
.upg-card__footer{padding:1rem 1.25rem 1.25rem;margin-top:auto}
.upg-card__btn{width:100%;padding:.8rem;border:none;border-radius:.875rem;color:#fff;font-size:.9rem;font-weight:700;cursor:pointer;font-family:Vazirmatn,Tahoma,sans-serif;transition:opacity .15s,transform .15s;display:flex;align-items:center;justify-content:center;gap:.5rem;box-shadow:0 4px 14px rgba(79,70,229,.3)}
.upg-card__btn:hover{opacity:.92;transform:translateY(-1px)}

/* Bottom note */
.upg-pg__bottom-note{display:flex;align-items:center;justify-content:center;gap:.375rem;margin-top:2rem;font-size:.78rem;color:#94a3b8;text-align:center}
</style>
@endpush

@endsection
