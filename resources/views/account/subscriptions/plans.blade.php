@extends('account.layout')
@section('title', 'پلن‌های اشتراک')
@section('header', 'انتخاب پلن اشتراک')

@section('content')
<div class="plans-pg" style="font-family:Vazirmatn,Tahoma,'Segoe UI',Arial,sans-serif">

{{-- اشتراک فعال --}}
@if($active)
<div class="plans-pg__active-bar">
    <div class="plans-pg__active-bar__icon">
        <x-core-ui::icon name="check-circle" style="width:18px;height:18px" />
    </div>
    <div class="plans-pg__active-bar__info">
        <strong>اشتراک فعال: {{ $active->plan->name }}</strong>
        <span>تا {{ \App\Helpers\Jalali::formatFa($active->ends_at, 'Y/m/d') }} · {{ \App\Helpers\NumberHelper::formatFa($active->daysRemaining()) }} روز مانده</span>
    </div>
    <a href="{{ route('account.subscriptions.index') }}" class="plans-pg__active-bar__link">مدیریت اشتراک</a>
</div>
@endif

{{-- بنر بلاک --}}
@if($blocked && !$active)
<div class="plans-pg__blocked-bar">
    <div class="plans-pg__blocked-bar__icon">
        <x-core-ui::icon name="alert-triangle" style="width:18px;height:18px" />
    </div>
    <div class="plans-pg__blocked-bar__info">
        <strong>یک درخواست اشتراک در انتظار تأیید دارید</strong>
        <span>پلن «{{ $blocked->plan?->name }}» — تا پیش از تأیید یا حذف این درخواست، امکان ثبت اشتراک جدید وجود ندارد.</span>
    </div>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap">
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
    <p class="plans-pg__intro-desc">دسترسی کامل به امکانات ویژه — بدون تعهد بلندمدت</p>
</div>

@if($plans->isEmpty())
<div class="plans-pg__empty">
    <x-core-ui::icon name="plan" style="width:48px;height:48px;color:#c7d2fe;margin:0 auto 1rem;display:block" />
    <p>در حال حاضر پلنی برای عضویت وجود ندارد.</p>
</div>
@else
<div class="plans-grid">
    @foreach($plans as $plan)
    @php
        $isActive       = $active && $active->subscription_plan_id === $plan->id;
        $color          = $plan->color ?? '#4f46e5';
        $products       = $plan->products ?? collect();
        $discountSaving = $plan->hasSpecialPrice()
            ? round((1 - $plan->special_price / $plan->price) * 100)
            : 0;
    @endphp

    <div class="plan-card {{ $plan->highlighted ? 'plan-card--featured' : '' }}" style="--plan-color:{{ $color }}">

        {{-- تصویر --}}
        @if($plan->image)
        <div class="plan-card__img-wrap">
            <img src="{{ asset('storage/' . $plan->image) }}" alt="{{ $plan->name }}" class="plan-card__img-full" loading="lazy">
            @if($discountSaving > 0)
            <div class="plan-card__discount-ribbon">{{ \App\Helpers\NumberHelper::formatFa($discountSaving) }}٪ تخفیف</div>
            @endif
        </div>
        @endif

        {{-- نوار رنگی + بج --}}
        <div class="plan-card__header" style="background:linear-gradient(135deg,{{ $color }}22,{{ $color }}08);border-bottom:3px solid {{ $color }}">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem">
                <h3 class="plan-card__name">{{ $plan->name }}</h3>
                @if($plan->badge_text || $plan->highlighted)
                <span class="plan-card__badge" style="background:{{ $color }}">{{ $plan->badge_text ?: 'پیشنهادی' }}</span>
                @endif
            </div>

            {{-- قیمت --}}
            <div class="plan-card__price-block">
                @if($plan->isFree())
                <span class="plan-card__price-free">رایگان</span>
                @else
                <div class="plan-card__price-row">
                    @if($plan->hasSpecialPrice())
                    <div style="display:flex;flex-direction:column;align-items:flex-start;gap:.1rem">
                        <div style="display:flex;align-items:center;gap:.375rem">
                            <span class="plan-card__price-main" style="color:{{ $color }}">{{ \App\Helpers\NumberHelper::formatFa($plan->special_price) }}</span>
                            <span class="plan-card__price-currency">تومان</span>
                        </div>
                        <div style="display:flex;align-items:center;gap:.5rem">
                            <span class="plan-card__price-old">{{ \App\Helpers\NumberHelper::formatFa($plan->price) }} تومان</span>
                            @if($discountSaving > 0 && !$plan->image)
                            <span class="plan-card__discount-badge">{{ \App\Helpers\NumberHelper::formatFa($discountSaving) }}٪ تخفیف</span>
                            @endif
                        </div>
                    </div>
                    @else
                    <span class="plan-card__price-main" style="color:{{ $color }}">{{ \App\Helpers\NumberHelper::formatFa($plan->price) }}</span>
                    <span class="plan-card__price-currency">تومان</span>
                    @endif
                </div>
                <span class="plan-card__price-per">/ {{ $plan->getDurationLabel() }}</span>
                @endif
            </div>

            {{-- دوره آزمایشی --}}
            @if($plan->hasTrial())
            <div class="plan-card__trial">
                <x-core-ui::icon name="clock" style="width:13px;height:13px" />
                {{ \App\Helpers\NumberHelper::formatFa($plan->trial_days) }} روز آزمایشی رایگان
            </div>
            @endif
        </div>

        {{-- توضیح --}}
        @if($plan->description)
        <p class="plan-card__desc">{{ \Illuminate\Support\Str::limit(strip_tags($plan->description), 120) }}</p>
        @endif

        {{-- مزایای ویژه --}}
        @php $badges = $plan->getBenefitBadges(); @endphp
        @if(count($badges))
        <div class="plan-card__benefits-section">
            <div class="plan-card__section-title">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                مزایای ویژه
            </div>
            <div class="plan-card__benefits-list">
                @foreach($badges as $badge)
                @php
                $bIcons = [
                    'tag'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>',
                    'truck'   => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10m10 0H3m10 0h4l3-3V10l-3-3h-4v9z"/>',
                    'headset' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 18v-6a9 9 0 0118 0v6"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 19a2 2 0 01-2 2h-1a2 2 0 01-2-2v-3a2 2 0 012-2h3zM3 19a2 2 0 002 2h1a2 2 0 002-2v-3a2 2 0 00-2-2H3z"/>',
                    'star'    => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>',
                    'gift'    => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/>',
                ];
                @endphp
                <div class="plan-card__benefit-item">
                    <span class="plan-card__benefit-icon" style="color:{{ $color }};background:{{ $color }}18">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14">{!! $bIcons[$badge['icon']] ?? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>' !!}</svg>
                    </span>
                    <span>{{ $badge['label'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- امکانات --}}
        @if($plan->getFeaturesArray())
        <div class="plan-card__features-section">
            <div class="plan-card__section-title">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                امکانات شامل
            </div>
            <ul class="plan-card__feat-list">
                @foreach($plan->getFeaturesArray() as $feat)
                <li>
                    <span class="plan-card__feat-check" style="color:{{ $color }}">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    </span>
                    {{ $feat }}
                </li>
                @endforeach
            </ul>
        </div>
        @endif

        {{-- محصولات شامل این اشتراک --}}
        @if($products->isNotEmpty())
        <div class="plan-card__products-section">
            <div class="plan-card__section-title">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                محصولات مشمول اشتراک
            </div>
            <div class="plan-card__products-list">
                @foreach($products->take(4) as $prod)
                <div class="plan-card__product-item">
                    <span class="plan-card__product-dot" style="background:{{ $color }}"></span>
                    <span class="plan-card__product-name">{{ $prod->title }}</span>
                    @if($prod->pivot->discount_percent > 0)
                    <span class="plan-card__product-disc" style="background:{{ $color }}22;color:{{ $color }}">
                        {{ \App\Helpers\NumberHelper::formatFa($prod->pivot->discount_percent) }}٪ تخفیف
                    </span>
                    @elseif($plan->product_discount_percent > 0)
                    <span class="plan-card__product-disc" style="background:{{ $color }}22;color:{{ $color }}">
                        {{ \App\Helpers\NumberHelper::formatFa($plan->product_discount_percent) }}٪ تخفیف
                    </span>
                    @endif
                </div>
                @endforeach
                @if($products->count() > 4)
                <div class="plan-card__products-more">+{{ \App\Helpers\NumberHelper::formatFa($products->count() - 4) }} محصول دیگر</div>
                @endif
            </div>
        </div>
        @endif

        {{-- فوتر / دکمه --}}
        <div class="plan-card__footer">
            @if($isActive)
            <div class="plan-card__current">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="17" height="17"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
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
                @if(! $plan->isFree())
                <div class="plan-card__coupon">
                    <div class="plan-card__coupon-wrap">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14" class="plan-card__coupon-icon"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                        <input type="text" name="coupon_code" placeholder="کد تخفیف (اختیاری)"
                               class="plan-card__coupon-input" dir="ltr" maxlength="50" autocomplete="off">
                    </div>
                </div>
                @endif
                <button type="submit" class="plan-card__buy-btn" style="background:linear-gradient(135deg,{{ $color }},{{ $color }}cc)">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                        @if($plan->isFree())
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        @else
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        @endif
                    </svg>
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
    <a href="{{ route('account.subscriptions.index') }}">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="13" height="13" style="vertical-align:middle;margin-left:.25rem"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        تاریخچه اشتراک‌های من
    </a>
</div>

</div>

@push('head')
<style>
.plans-pg,.plans-pg *{font-family:Vazirmatn,Tahoma,'Segoe UI',Arial,sans-serif}

/* ── Active bar ── */
.plans-pg__active-bar{display:flex;align-items:center;gap:.875rem;background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:.875rem;padding:.875rem 1.25rem;margin-bottom:1.5rem;flex-wrap:wrap}
.plans-pg__active-bar__icon{width:36px;height:36px;background:#dcfce7;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#16a34a;flex-shrink:0}
.plans-pg__active-bar__info{flex:1;display:flex;flex-direction:column;gap:.15rem}
.plans-pg__active-bar__info strong{font-size:.9rem;font-weight:700;color:#166534}
.plans-pg__active-bar__info span{font-size:.78rem;color:#16a34a}
.plans-pg__active-bar__link{font-size:.8rem;color:#16a34a;font-weight:600;text-decoration:none;border:1px solid #86efac;padding:.35rem .875rem;border-radius:.5rem;flex-shrink:0}
.plans-pg__active-bar__link:hover{background:#dcfce7}

/* ── Blocked bar ── */
.plans-pg__blocked-bar{display:flex;align-items:center;gap:.875rem;background:#fff7ed;border:1.5px solid #fed7aa;border-radius:.875rem;padding:.875rem 1.25rem;margin-bottom:1.5rem;flex-wrap:wrap}
.plans-pg__blocked-bar__icon{width:36px;height:36px;background:#ffedd5;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#c2410c;flex-shrink:0}
.plans-pg__blocked-bar__info{flex:1;display:flex;flex-direction:column;gap:.2rem;min-width:180px}
.plans-pg__blocked-bar__info strong{font-size:.875rem;font-weight:700;color:#9a3412}
.plans-pg__blocked-bar__info span{font-size:.78rem;color:#c2410c}
.plans-pg__blocked-btn{display:inline-block;padding:.35rem .875rem;border-radius:.5rem;font-size:.78rem;font-weight:700;cursor:pointer;border:1px solid transparent;font-family:Vazirmatn,Tahoma,sans-serif;text-decoration:none;white-space:nowrap}
.plans-pg__blocked-btn--view{background:#fff;color:#9a3412;border-color:#fed7aa}.plans-pg__blocked-btn--view:hover{background:#fff7ed}
.plans-pg__blocked-btn--del{background:#fff1f2;color:#be123c;border-color:#fecdd3}.plans-pg__blocked-btn--del:hover{background:#ffe4e6}

/* ── Intro ── */
.plans-pg__intro{text-align:center;margin-bottom:2rem}
.plans-pg__intro-title{font-size:1.5rem;font-weight:800;color:#1e293b;margin:0 0 .375rem}
.plans-pg__intro-desc{font-size:.9rem;color:#64748b;margin:0}

/* ── Empty ── */
.plans-pg__empty{text-align:center;background:#f8fafc;border:2px dashed #e2e8f0;border-radius:1rem;padding:3rem 1.5rem;color:#94a3b8}

/* ── Grid ── */
.plans-grid{display:grid;grid-template-columns:1fr;gap:1.5rem}
@media(min-width:640px){.plans-grid{grid-template-columns:1fr 1fr}}
@media(min-width:1024px){.plans-grid{grid-template-columns:repeat(3,1fr)}}

/* ── Plan Card ── */
.plan-card{position:relative;background:#fff;border:1.5px solid #e2e8f0;border-radius:1.375rem;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 4px 16px rgba(0,0,0,.06);transition:box-shadow .25s,transform .25s}
.plan-card:hover{box-shadow:0 12px 40px rgba(0,0,0,.13);transform:translateY(-4px)}
.plan-card--featured{border-color:var(--plan-color);box-shadow:0 6px 24px color-mix(in srgb,var(--plan-color) 25%,transparent)}

/* Image */
.plan-card__img-wrap{position:relative;overflow:hidden;height:180px}
.plan-card__img-full{width:100%;height:100%;object-fit:cover;display:block;transition:transform .4s}
.plan-card:hover .plan-card__img-full{transform:scale(1.04)}
.plan-card__discount-ribbon{position:absolute;top:.75rem;left:.75rem;background:#ef4444;color:#fff;font-size:.7rem;font-weight:800;padding:.25rem .625rem;border-radius:9999px;box-shadow:0 2px 8px rgba(239,68,68,.4)}

/* Header */
.plan-card__header{padding:1.125rem 1.375rem .875rem}
.plan-card__name{font-size:1.1875rem;font-weight:800;color:#1e293b;margin:0 0 .875rem;line-height:1.3}
.plan-card__badge{display:inline-block;font-size:.68rem;font-weight:800;color:#fff;padding:.2rem .625rem;border-radius:9999px;white-space:nowrap}

/* Price */
.plan-card__price-block{margin-bottom:.25rem}
.plan-card__price-row{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap}
.plan-card__price-main{font-size:2.125rem;font-weight:900;line-height:1;letter-spacing:-.03em}
.plan-card__price-currency{font-size:.9rem;font-weight:600;color:#64748b;padding-bottom:.25rem}
.plan-card__price-old{font-size:.8rem;color:#94a3b8;text-decoration:line-through}
.plan-card__price-per{font-size:.78rem;color:#94a3b8;margin-top:.2rem;display:block}
.plan-card__price-free{font-size:2rem;font-weight:900;color:#16a34a}
.plan-card__discount-badge{display:inline-block;background:#fef2f2;color:#dc2626;font-size:.68rem;font-weight:800;padding:.15rem .5rem;border-radius:9999px;border:1px solid #fecaca}
.plan-card__trial{display:inline-flex;align-items:center;gap:.3rem;font-size:.75rem;color:#2563eb;margin-top:.625rem;background:#eff6ff;padding:.2rem .625rem;border-radius:9999px;font-weight:600}

/* Description */
.plan-card__desc{font-size:.8125rem;color:#64748b;padding:.125rem 1.375rem .75rem;margin:0;line-height:1.65;border-top:1px solid #f8fafc}

/* Section title */
.plan-card__section-title{display:flex;align-items:center;gap:.375rem;font-size:.72rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.04em;margin-bottom:.5rem}

/* Benefits */
.plan-card__benefits-section{padding:.75rem 1.375rem;border-top:1px solid #f1f5f9}
.plan-card__benefits-list{display:flex;flex-direction:column;gap:.4rem}
.plan-card__benefit-item{display:flex;align-items:center;gap:.5rem;font-size:.8rem;color:#334155;font-weight:600}
.plan-card__benefit-icon{width:24px;height:24px;border-radius:.375rem;display:flex;align-items:center;justify-content:center;flex-shrink:0}

/* Features */
.plan-card__features-section{padding:.75rem 1.375rem;border-top:1px solid #f1f5f9;flex:1}
.plan-card__feat-list{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:.45rem}
.plan-card__feat-list li{display:flex;align-items:flex-start;gap:.5rem;font-size:.8375rem;color:#475569;line-height:1.45}
.plan-card__feat-check{width:18px;height:18px;flex-shrink:0;margin-top:.05rem;display:flex;align-items:center;justify-content:center}

/* Products */
.plan-card__products-section{padding:.75rem 1.375rem;border-top:1px solid #f1f5f9;background:#fafafa}
.plan-card__products-list{display:flex;flex-direction:column;gap:.35rem}
.plan-card__product-item{display:flex;align-items:center;gap:.4rem;font-size:.78rem;color:#334155}
.plan-card__product-dot{width:6px;height:6px;border-radius:50%;flex-shrink:0}
.plan-card__product-name{flex:1;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.plan-card__product-disc{font-size:.68rem;font-weight:700;padding:.1rem .4rem;border-radius:9999px;white-space:nowrap}
.plan-card__products-more{font-size:.75rem;color:#94a3b8;padding-right:.375rem}

/* Footer */
.plan-card__footer{padding:1rem 1.375rem 1.375rem;margin-top:auto}
.plan-card__coupon{margin-bottom:.625rem}
.plan-card__coupon-wrap{position:relative;display:flex;align-items:center}
.plan-card__coupon-icon{position:absolute;right:.75rem;color:#94a3b8;pointer-events:none;flex-shrink:0}
.plan-card__coupon-input{width:100%;box-sizing:border-box;padding:.5rem .75rem .5rem 2.25rem;border:1.5px solid #e2e8f0;border-radius:.625rem;font-size:.8125rem;font-family:monospace,'Courier New';color:#334155;outline:none;text-align:left}
.plan-card__coupon-input:focus{border-color:var(--plan-color,#4f46e5);box-shadow:0 0 0 3px color-mix(in srgb,var(--plan-color,#4f46e5) 15%,transparent)}
.plan-card__buy-btn{width:100%;padding:.8rem;border:none;border-radius:.875rem;color:#fff;font-size:.9375rem;font-weight:700;cursor:pointer;font-family:Vazirmatn,Tahoma,sans-serif;transition:opacity .15s,transform .15s,box-shadow .15s;display:flex;align-items:center;justify-content:center;gap:.5rem;box-shadow:0 4px 14px color-mix(in srgb,var(--plan-color,#4f46e5) 35%,transparent)}
.plan-card__buy-btn:hover{opacity:.92;transform:translateY(-1px);box-shadow:0 6px 20px color-mix(in srgb,var(--plan-color,#4f46e5) 45%,transparent)}
.plan-card__current{display:flex;align-items:center;justify-content:center;gap:.5rem;padding:.8rem;background:#f0fdf4;border:1.5px solid #86efac;border-radius:.875rem;color:#16a34a;font-weight:700;font-size:.875rem}
.plan-card__blocked{display:flex;align-items:center;justify-content:center;gap:.5rem;padding:.8rem;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:.875rem;color:#94a3b8;font-weight:600;font-size:.875rem}

/* Back */
.plans-pg__back{text-align:center;margin-top:2rem}
.plans-pg__back a{font-size:.8125rem;color:#94a3b8;text-decoration:none;display:inline-flex;align-items:center;gap:.25rem}
.plans-pg__back a:hover{color:#4f46e5}
</style>
@endpush

@endsection
