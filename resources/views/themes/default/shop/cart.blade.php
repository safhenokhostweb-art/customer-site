@extends('themes.default.layout')
@section('title', 'سبد خرید')

@section('breadcrumb')
    <a href="{{ route('home') }}">خانه</a>
    <span style="margin:0 .4rem;color:#94a3b8">/</span>
    <a href="{{ route('shop.index') }}">فروشگاه</a>
    <span style="margin:0 .4rem;color:#94a3b8">/</span>
    <span style="color:#64748b">سبد خرید</span>
@endsection

@push('head')
<style>
/* ════════════════════════════════════════════════════════
   Cart Page — Membership Benefits Engine
   RTL | Vazirmatn | Cross-browser (Chrome/Firefox/Safari/Edge)
   ════════════════════════════════════════════════════════ */
.cart-pg,
.cart-pg * { font-family: Vazirmatn, Tahoma, 'Segoe UI', Arial, sans-serif; box-sizing: border-box; }

/* ── Layout ── */
.cart-pg { padding: 0 0 3rem; }
.cart-pg__title { font-size: 1.5rem; font-weight: 800; color: #1e293b; margin: 0 0 1.75rem; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; gap: .625rem; }
.cart-pg__title svg { color: #4f46e5; }

.cart-grid { display: -ms-grid; display: grid; gap: 1.75rem; -ms-grid-columns: 1fr; grid-template-columns: 1fr; }
@media (min-width: 1024px) { .cart-grid { -ms-grid-columns: 1fr 380px; grid-template-columns: 1fr 380px; } }

/* ── Empty state ── */
.cart-empty { text-align: center; padding: 4rem 1.5rem; background: #fff; border: 2px dashed #e2e8f0; border-radius: 1.25rem; }
.cart-empty__icon { width: 72px; height: 72px; background: #f1f5f9; border-radius: 50%; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; -webkit-box-pack: center; -ms-flex-pack: center; justify-content: center; margin: 0 auto 1.25rem; color: #94a3b8; }
.cart-empty h2 { font-size: 1.125rem; font-weight: 700; color: #334155; margin: 0 0 .375rem; }
.cart-empty p { font-size: .875rem; color: #64748b; margin: 0 0 1.5rem; }
.cart-empty__btn { display: inline-block; padding: .75rem 2rem; background: #4f46e5; color: #fff; border-radius: .875rem; font-weight: 700; text-decoration: none; font-size: .9rem; -webkit-box-shadow: 0 4px 14px rgba(79,70,229,.35); box-shadow: 0 4px 14px rgba(79,70,229,.35); -webkit-transition: opacity .15s; transition: opacity .15s; }
.cart-empty__btn:hover { opacity: .88; }

/* ── Cart items panel ── */
.cart-items-panel { background: #fff; border: 1px solid #e2e8f0; border-radius: 1.25rem; overflow: hidden; -webkit-box-shadow: 0 2px 8px rgba(0,0,0,.05); box-shadow: 0 2px 8px rgba(0,0,0,.05); }
.cart-items-head { padding: .875rem 1.375rem; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; gap: .5rem; }
.cart-items-head__title { font-size: .875rem; font-weight: 700; color: #374151; -webkit-box-flex: 1; -ms-flex: 1; flex: 1; }
.cart-items-head__count { font-size: .78rem; background: #e0e7ff; color: #4338ca; padding: .15rem .6rem; border-radius: 9999px; font-weight: 700; }

/* ── Single item card ── */
.cart-item { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: start; -ms-flex-align: start; align-items: flex-start; gap: 1rem; padding: 1.125rem 1.375rem; border-bottom: 1px solid #f1f5f9; -webkit-transition: background .15s; transition: background .15s; }
.cart-item:last-child { border-bottom: none; }
.cart-item:hover { background: #fafbff; }

.cart-item__img { width: 72px; height: 72px; border-radius: .75rem; overflow: hidden; background: #f3f4f6; -ms-flex-negative: 0; flex-shrink: 0; border: 1px solid #e5e7eb; }
.cart-item__img img { width: 100%; height: 100%; object-fit: cover; display: block; }
.cart-item__img-placeholder { width: 100%; height: 100%; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; -webkit-box-pack: center; -ms-flex-pack: center; justify-content: center; color: #cbd5e1; }

.cart-item__body { -webkit-box-flex: 1; -ms-flex: 1; flex: 1; min-width: 0; }
.cart-item__title { font-size: .9rem; font-weight: 700; color: #1e293b; margin: 0 0 .25rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.cart-item__price { font-size: .8125rem; color: #64748b; margin: 0 0 .625rem; }
.cart-item__price strong { color: #4f46e5; font-weight: 700; }

.cart-item__controls { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; gap: .5rem; -ms-flex-wrap: wrap; flex-wrap: wrap; }
.cart-item__qty-form { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; gap: .25rem; }
.cart-item__qty-input { width: 3.5rem; padding: .35rem .5rem; border: 1.5px solid #e2e8f0; border-radius: .5rem; font-size: .8125rem; text-align: center; font-family: Vazirmatn, Tahoma, sans-serif; color: #1e293b; outline: none; -webkit-transition: border-color .15s; transition: border-color .15s; }
.cart-item__qty-input:focus { border-color: #4f46e5; }
.cart-item__qty-btn { padding: .35rem .7rem; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; border-radius: .5rem; font-size: .78rem; font-weight: 600; cursor: pointer; font-family: Vazirmatn, Tahoma, sans-serif; -webkit-transition: background .15s; transition: background .15s; }
.cart-item__qty-btn:hover { background: #e2e8f0; }
.cart-item__del-btn { padding: .35rem .6rem; background: #fff1f2; color: #be123c; border: 1px solid #fecdd3; border-radius: .5rem; cursor: pointer; -webkit-transition: background .15s; transition: background .15s; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; -webkit-box-pack: center; -ms-flex-pack: center; justify-content: center; }
.cart-item__del-btn:hover { background: #fee2e2; }

.cart-item__line-total { font-size: .9375rem; font-weight: 800; color: #1e293b; white-space: nowrap; -ms-flex-negative: 0; flex-shrink: 0; }

/* ── Membership Banner ── */
.membership-banner { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; gap: .875rem; padding: 1rem 1.375rem; background: -webkit-linear-gradient(135deg, #f0f9ff, #e0f2fe); background: linear-gradient(135deg, #f0f9ff, #e0f2fe); border-top: 3px solid #0ea5e9; border-bottom: 1px solid #bae6fd; }
.membership-banner--gold { background: -webkit-linear-gradient(135deg, #fffbeb, #fef3c7); background: linear-gradient(135deg, #fffbeb, #fef3c7); border-top-color: #f59e0b; border-bottom-color: #fde68a; }
.membership-banner--violet { background: -webkit-linear-gradient(135deg, #f5f3ff, #ede9fe); background: linear-gradient(135deg, #f5f3ff, #ede9fe); border-top-color: #7c3aed; border-bottom-color: #ddd6fe; }
.membership-banner__icon { width: 40px; height: 40px; border-radius: 50%; background: rgba(255,255,255,.7); display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; -webkit-box-pack: center; -ms-flex-pack: center; justify-content: center; -ms-flex-negative: 0; flex-shrink: 0; color: #0ea5e9; -webkit-box-shadow: 0 2px 8px rgba(0,0,0,.08); box-shadow: 0 2px 8px rgba(0,0,0,.08); }
.membership-banner--gold .membership-banner__icon { color: #d97706; }
.membership-banner--violet .membership-banner__icon { color: #7c3aed; }
.membership-banner__body { -webkit-box-flex: 1; -ms-flex: 1; flex: 1; min-width: 0; }
.membership-banner__title { font-size: .875rem; font-weight: 800; color: #0c4a6e; margin: 0 0 .2rem; }
.membership-banner--gold .membership-banner__title { color: #78350f; }
.membership-banner--violet .membership-banner__title { color: #3b0764; }
.membership-banner__desc { font-size: .78rem; color: #0369a1; margin: 0; }
.membership-banner--gold .membership-banner__desc { color: #92400e; }
.membership-banner--violet .membership-banner__desc { color: #5b21b6; }
.membership-banner__saving { -ms-flex-negative: 0; flex-shrink: 0; text-align: left; }
.membership-banner__saving-label { font-size: .68rem; color: #64748b; display: block; margin-bottom: .1rem; }
.membership-banner__saving-amount { font-size: 1rem; font-weight: 900; color: #16a34a; white-space: nowrap; }

/* ── Badges in banner ── */
.membership-badges { display: -webkit-box; display: -ms-flexbox; display: flex; gap: .375rem; -ms-flex-wrap: wrap; flex-wrap: wrap; margin-top: .375rem; }
.membership-badge { display: -webkit-inline-box; display: -ms-inline-flexbox; display: inline-flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; gap: .25rem; font-size: .7rem; font-weight: 700; padding: .2rem .55rem; border-radius: 9999px; white-space: nowrap; }
.membership-badge--discount { background: #dcfce7; color: #15803d; }
.membership-badge--shipping { background: #dbeafe; color: #1d4ed8; }
.membership-badge--vip { background: #f3e8ff; color: #7e22ce; }

/* ── Summary sidebar ── */
.cart-summary { position: -webkit-sticky; position: sticky; top: 1.5rem; }
.cart-summary-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 1.25rem; overflow: hidden; -webkit-box-shadow: 0 2px 8px rgba(0,0,0,.05); box-shadow: 0 2px 8px rgba(0,0,0,.05); }
.cart-summary-head { padding: .875rem 1.375rem; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
.cart-summary-head__title { font-size: .9rem; font-weight: 700; color: #374151; margin: 0; }
.cart-summary-body { padding: 1.25rem 1.375rem; }

/* ── Price rows ── */
.price-row { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; -webkit-box-pack: justify; -ms-flex-pack: justify; justify-content: space-between; gap: .5rem; padding: .5rem 0; font-size: .875rem; color: #475569; }
.price-row:not(:last-child) { border-bottom: 1px solid #f1f5f9; }
.price-row__label { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; gap: .375rem; }
.price-row__label svg { color: #94a3b8; }
.price-row__value { font-weight: 700; color: #1e293b; }
.price-row--discount .price-row__value { color: #16a34a; }
.price-row--membership .price-row__value { color: #7c3aed; }
.price-row--total { padding: .75rem 0 0; margin-top: .25rem; border-top: 2px solid #e2e8f0 !important; border-bottom: none !important; }
.price-row--total .price-row__label { font-size: 1rem; font-weight: 800; color: #1e293b; }
.price-row--total .price-row__value { font-size: 1.25rem; font-weight: 900; color: #4f46e5; }
.price-shipping-free { font-size: .78rem; font-weight: 700; color: #16a34a; background: #dcfce7; padding: .15rem .5rem; border-radius: 9999px; }

/* ── Coupon box ── */
.coupon-box { margin: 1rem 0; padding: 1rem; background: #f8fafc; border: 1.5px dashed #cbd5e1; border-radius: .875rem; }
.coupon-box__label { font-size: .78rem; font-weight: 700; color: #64748b; margin-bottom: .5rem; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; gap: .3rem; }
.coupon-box__row { display: -webkit-box; display: -ms-flexbox; display: flex; gap: .5rem; }
.coupon-box__input { -webkit-box-flex: 1; -ms-flex: 1; flex: 1; padding: .5rem .75rem; border: 1.5px solid #e2e8f0; border-radius: .625rem; font-size: .8125rem; font-family: monospace; color: #334155; outline: none; direction: ltr; text-align: left; -webkit-transition: border-color .15s; transition: border-color .15s; }
.coupon-box__input:focus { border-color: #4f46e5; -webkit-box-shadow: 0 0 0 3px rgba(79,70,229,.12); box-shadow: 0 0 0 3px rgba(79,70,229,.12); }

/* ── Checkout form ── */
.checkout-form-section { padding: 1.25rem 1.375rem; border-top: 1px solid #e2e8f0; }
.checkout-form-section h3 { font-size: .875rem; font-weight: 700; color: #374151; margin: 0 0 1rem; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; gap: .4rem; }
.form-field { margin-bottom: .875rem; }
.form-field label { display: block; font-size: .78rem; font-weight: 700; color: #374151; margin-bottom: .3rem; }
.form-field input,
.form-field textarea { width: 100%; padding: .55rem .875rem; border: 1.5px solid #e2e8f0; border-radius: .625rem; font-size: .875rem; font-family: Vazirmatn, Tahoma, sans-serif; color: #1e293b; outline: none; background: #fff; -webkit-transition: border-color .15s, -webkit-box-shadow .15s; transition: border-color .15s, box-shadow .15s; }
.form-field input:focus,
.form-field textarea:focus { border-color: #4f46e5; -webkit-box-shadow: 0 0 0 3px rgba(79,70,229,.1); box-shadow: 0 0 0 3px rgba(79,70,229,.1); }
.form-field textarea { resize: vertical; min-height: 4.5rem; }
.form-error { font-size: .75rem; color: #dc2626; margin-top: .2rem; }

/* ── Points box ── */
.points-box { margin: 1rem 0; padding: 1rem; background: linear-gradient(135deg, #fdf8ff, #f3e8ff); border: 1.5px solid #ddd6fe; border-radius: .875rem; }
.points-box__head { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; -webkit-box-pack: justify; -ms-flex-pack: justify; justify-content: space-between; gap: .5rem; margin-bottom: .75rem; }
.points-box__title { font-size: .8125rem; font-weight: 700; color: #5b21b6; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; gap: .35rem; }
.points-box__balance { font-size: .75rem; background: #ede9fe; color: #5b21b6; padding: .2rem .6rem; border-radius: 9999px; font-weight: 700; white-space: nowrap; }
.points-box__slider-row { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; gap: .5rem; margin-bottom: .5rem; }
.points-box__slider { -webkit-box-flex: 1; -ms-flex: 1; flex: 1; accent-color: #7c3aed; cursor: pointer; }
.points-box__input { width: 5rem; padding: .35rem .5rem; border: 1.5px solid #ddd6fe; border-radius: .5rem; font-size: .8125rem; text-align: center; font-family: Vazirmatn, Tahoma, sans-serif; color: #5b21b6; font-weight: 700; outline: none; }
.points-box__input:focus { border-color: #7c3aed; }
.points-box__equiv { font-size: .78rem; color: #7c3aed; font-weight: 600; text-align: center; }
.points-box__zero { font-size: .78rem; color: #94a3b8; text-align: center; padding: .25rem 0; }

/* ── Submit button ── */
.checkout-submit-btn { width: 100%; padding: .9rem; background: -webkit-linear-gradient(135deg, #4f46e5, #7c3aed); background: linear-gradient(135deg, #4f46e5, #7c3aed); color: #fff; border: none; border-radius: .875rem; font-size: 1rem; font-weight: 800; font-family: Vazirmatn, Tahoma, sans-serif; cursor: pointer; -webkit-box-shadow: 0 4px 16px rgba(79,70,229,.35); box-shadow: 0 4px 16px rgba(79,70,229,.35); -webkit-transition: opacity .15s, -webkit-transform .15s; transition: opacity .15s, transform .15s; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; -webkit-box-pack: center; -ms-flex-pack: center; justify-content: center; gap: .5rem; }
.checkout-submit-btn:hover { opacity: .92; -webkit-transform: translateY(-1px); -ms-transform: translateY(-1px); transform: translateY(-1px); }
.checkout-submit-btn:active { -webkit-transform: translateY(0); -ms-transform: translateY(0); transform: translateY(0); }

/* ── Savings badge ── */
.savings-notice { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; gap: .5rem; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: .75rem; padding: .625rem .875rem; margin-bottom: 1rem; font-size: .8rem; color: #15803d; font-weight: 600; }
.savings-notice svg { -ms-flex-negative: 0; flex-shrink: 0; }

/* ── Alert boxes ── */
.alert-error { padding: .75rem 1rem; background: #fef2f2; border: 1px solid #fecaca; border-radius: .75rem; color: #991b1b; font-size: .875rem; margin-bottom: 1rem; }
.alert-success { padding: .75rem 1rem; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: .75rem; color: #166534; font-size: .875rem; margin-bottom: 1rem; }
</style>
@endpush

@section('content')
<div class="cart-pg">

<h1 class="cart-pg__title">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="24" height="24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
    </svg>
    سبد خرید
</h1>

@if(session('error'))
    <div class="alert-error">{{ session('error') }}</div>
@endif
@if(session('success'))
    <div class="alert-success">{{ session('success') }}</div>
@endif

@if(count($cart) > 0)

@php
    $couponEnabled = \App\Helpers\SiteHelper::moduleEnabled('coupon');
    $membershipEnabled = \App\Helpers\SiteHelper::moduleEnabled('subscription');
    $totalSavings = $membership->discountAmount;
    $cartTotal    = $subtotal - $membership->discountAmount;
    $bannerClass  = 'membership-banner';
@endphp

<div class="cart-grid">

    {{-- ── RIGHT COLUMN: Items ── --}}
    <div>

        {{-- Membership Banner --}}
        @if($membershipEnabled && $membership->hasBenefit())
        <div class="{{ $bannerClass }}" style="border-radius:1.25rem 1.25rem 0 0;margin-bottom:0">
            <div class="membership-banner__icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="22" height="22">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                </svg>
            </div>
            <div class="membership-banner__body">
                <p class="membership-banner__title">اشتراک «{{ $membership->planName }}» فعال است</p>
                <div class="membership-badges">
                    @if($membership->discountPercent > 0)
                    <span class="membership-badge membership-badge--discount">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="11" height="11">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                        {{ \App\Helpers\NumberHelper::formatFa($membership->discountPercent) }}٪ تخفیف
                    </span>
                    @endif
                    @if($membership->freeShipping)
                    <span class="membership-badge membership-badge--shipping">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="11" height="11">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10m10 0H3m10 0h4l3-3V10l-3-3h-4v9z"/>
                        </svg>
                        ارسال رایگان
                    </span>
                    @endif
                    @if($membership->vipSupport)
                    <span class="membership-badge membership-badge--vip">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="11" height="11">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                        </svg>
                        پشتیبانی VIP
                    </span>
                    @endif
                </div>
            </div>
            @if($membership->hasDiscount())
            <div class="membership-banner__saving">
                <span class="membership-banner__saving-label">صرفه‌جویی شما</span>
                <span class="membership-banner__saving-amount">
                    {!! \App\Helpers\CurrencyHelper::formatPrice($membership->discountAmount) !!}
                    {{ \App\Helpers\CurrencyHelper::unitLabel() }}
                </span>
            </div>
            @endif
        </div>
        @endif

        {{-- Cart Items --}}
        <div class="cart-items-panel" style="{{ $membershipEnabled && $membership->hasBenefit() ? 'border-radius:0 0 1.25rem 1.25rem;border-top:none' : '' }}">
            <div class="cart-items-head">
                <span class="cart-items-head__title">محصولات سبد</span>
                <span class="cart-items-head__count">{{ \App\Helpers\NumberHelper::formatFa(count($cart)) }} کالا</span>
            </div>

            @foreach($cart as $lineKey => $item)
            @php
                $lineKey  = is_string($lineKey)
                    ? $lineKey
                    : \App\Helpers\ShopCartHelper::lineKey((int)($item['product_id'] ?? $item['id']), $item['product_variant_id'] ?? null);
                $lineTotal = (int)$item['price'] * (int)$item['quantity'];
                $isInMembership = $membership->appliedToAll
                    || in_array((int)($item['product_id'] ?? 0), $membership->appliedProductIds);
            @endphp
            <div class="cart-item">

                {{-- Image --}}
                <div class="cart-item__img">
                    @if(!empty($item['image']))
                        <img src="{{ \App\Helpers\ImageHelper::url($item['image']) }}"
                             alt="{{ $item['title'] }}" loading="lazy">
                    @else
                        <div class="cart-item__img-placeholder">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="28" height="28">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                    @endif
                </div>

                {{-- Body --}}
                <div class="cart-item__body">
                    <h3 class="cart-item__title">{{ $item['title'] }}</h3>
                    <p class="cart-item__price">
                        قیمت واحد:
                        <strong>{!! \App\Helpers\CurrencyHelper::formatPrice($item['price']) !!} {{ \App\Helpers\CurrencyHelper::unitLabel() }}</strong>
                        @if($isInMembership && $membership->hasDiscount())
                            &nbsp;<span style="font-size:.7rem;background:#f3e8ff;color:#7c3aed;padding:.1rem .4rem;border-radius:9999px;font-weight:700">مشمول اشتراک</span>
                        @endif
                    </p>

                    {{-- Qty controls --}}
                    <div class="cart-item__controls">
                        <form method="POST" action="{{ route('shop.cart.update') }}" class="cart-item__qty-form">
                            @csrf
                            <input type="hidden" name="line_key" value="{{ $lineKey }}">
                            <input type="number" name="quantity" value="{{ $item['quantity'] }}"
                                   min="1" max="999" class="cart-item__qty-input" aria-label="تعداد">
                            <button type="submit" class="cart-item__qty-btn">بروزرسانی</button>
                        </form>

                        <form method="POST" action="{{ route('shop.cart.remove') }}"
                              onsubmit="return confirm('این محصول از سبد حذف شود؟');">
                            @csrf
                            <input type="hidden" name="line_key" value="{{ $lineKey }}">
                            <button type="submit" class="cart-item__del-btn" title="حذف" aria-label="حذف از سبد">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="15" height="15">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Line total --}}
                <div class="cart-item__line-total">
                    {!! \App\Helpers\CurrencyHelper::formatPrice($lineTotal) !!}
                    <span style="font-size:.75rem;font-weight:500;color:#64748b">{{ \App\Helpers\CurrencyHelper::unitLabel() }}</span>
                </div>

            </div>
            @endforeach
        </div>

        {{-- Continue shopping link --}}
        <div style="margin-top:1rem;text-align:right">
            <a href="{{ route('shop.index') }}"
               style="font-size:.8125rem;color:#64748b;text-decoration:none;display:inline-flex;align-items:center;gap:.3rem">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                ادامه خرید
            </a>
        </div>
    </div>

    {{-- ── LEFT COLUMN: Summary + Form ── --}}
    <div class="cart-summary">
        <div class="cart-summary-card">
            <div class="cart-summary-head">
                <p class="cart-summary-head__title">خلاصه سفارش</p>
            </div>
            <div class="cart-summary-body">

                {{-- Savings notice --}}
                @php
                    $totalSavingsDisplay = $membership->discountAmount;
                @endphp
                @if($totalSavingsDisplay > 0)
                <div class="savings-notice">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    با اشتراک شما {!! \App\Helpers\CurrencyHelper::formatPrice($totalSavingsDisplay) !!} {{ \App\Helpers\CurrencyHelper::unitLabel() }} صرفه‌جویی کردید!
                </div>
                @endif

                {{-- Price breakdown --}}
                <div class="price-row">
                    <span class="price-row__label">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        جمع محصولات
                    </span>
                    <span class="price-row__value">
                        {!! \App\Helpers\CurrencyHelper::formatPrice($subtotal) !!} {{ \App\Helpers\CurrencyHelper::unitLabel() }}
                    </span>
                </div>

                @if($membership->hasDiscount())
                <div class="price-row price-row--membership">
                    <span class="price-row__label" style="color:#7c3aed">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                        </svg>
                        <span style="color:#7c3aed">تخفیف اشتراک ({{ $membership->planName }})</span>
                    </span>
                    <span class="price-row__value" style="color:#7c3aed">
                        − {!! \App\Helpers\CurrencyHelper::formatPrice($membership->discountAmount) !!} {{ \App\Helpers\CurrencyHelper::unitLabel() }}
                    </span>
                </div>
                @endif

                {{-- Points Redemption Box --}}
                @if(auth()->check() && $pointsBalance > 0)
                @php $pointsToman = \App\Services\MembershipPointsService::TOMAN_PER_POINT; @endphp
                <div class="points-box" id="points-box">
                    <div class="points-box__head">
                        <span class="points-box__title">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="15" height="15">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                            </svg>
                            استفاده از امتیاز
                        </span>
                        <span class="points-box__balance">
                            {{ \App\Helpers\NumberHelper::formatFa($pointsBalance) }} امتیاز موجود
                        </span>
                    </div>
                    @if($maxSpendPoints > 0)
                    <div class="points-box__slider-row">
                        <input type="range" id="pts-slider" class="points-box__slider"
                               min="0" max="{{ $maxSpendPoints }}" step="100" value="0"
                               oninput="updatePoints(this.value)">
                        <input type="number" id="pts-input" class="points-box__input"
                               min="0" max="{{ $maxSpendPoints }}" step="100" value="0"
                               oninput="updatePoints(this.value); document.getElementById('pts-slider').value=this.value">
                    </div>
                    <div class="points-box__equiv" id="pts-equiv">
                        ۰ تومان تخفیف
                    </div>
                    <input type="hidden" name="points_to_spend" id="pts-hidden" value="0" form="checkout-form">
                    <script>
                    function updatePoints(v) {
                        v = Math.min(Math.max(0, parseInt(v)||0), {{ $maxSpendPoints }});
                        document.getElementById('pts-slider').value = v;
                        document.getElementById('pts-input').value = v;
                        document.getElementById('pts-hidden').value = v;
                        var toman = v * {{ $pointsToman }};
                        document.getElementById('pts-equiv').textContent =
                            (toman > 0 ? new Intl.NumberFormat('fa-IR').format(toman) + ' تومان تخفیف' : '۰ تومان تخفیف');
                    }
                    </script>
                    @else
                    <div class="points-box__zero">حداکثر مصرف مجاز برای این سفارش صفر است (سقف ۲۰٪ سفارش)</div>
                    @endif
                </div>
                @endif

                <div class="price-row">
                    <span class="price-row__label">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10m10 0H3m10 0h4l3-3V10l-3-3h-4v9z"/>
                        </svg>
                        هزینه ارسال
                    </span>
                    <span class="price-row__value">
                        @if($membership->freeShipping)
                            <span class="price-shipping-free">رایگان با اشتراک</span>
                        @else
                            <span style="color:#64748b;font-size:.8rem">محاسبه در تکمیل</span>
                        @endif
                    </span>
                </div>

                <div class="price-row price-row--total">
                    <span class="price-row__label">مجموع</span>
                    <span class="price-row__value">
                        {!! \App\Helpers\CurrencyHelper::formatPrice($cartTotal) !!} {{ \App\Helpers\CurrencyHelper::unitLabel() }}
                    </span>
                </div>
            </div>

            {{-- Coupon box --}}
            @if($couponEnabled)
            <div style="padding:0 1.375rem">
                <div class="coupon-box">
                    <div class="coupon-box__label">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="13" height="13">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                        کد تخفیف (در مرحله ثبت اعمال می‌شود)
                    </div>
                    <div class="coupon-box__row">
                        <input type="text" id="coupon_preview" placeholder="مثلاً WELCOME20"
                               class="coupon-box__input" dir="ltr" autocomplete="off"
                               oninput="document.getElementById('coupon_input').value=this.value">
                    </div>
                </div>
            </div>
            @endif

            {{-- Checkout Form --}}
            <div class="checkout-form-section">
                <h3>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    اطلاعات تحویل
                </h3>

                @if($errors->any())
                <div class="alert-error">
                    <ul style="margin:0;padding-right:1rem">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form method="POST" action="{{ route('shop.checkout') }}" id="checkout-form">
                    @csrf

                    @if($couponEnabled)
                    <input type="hidden" name="coupon_code" id="coupon_input" value="{{ old('coupon_code') }}">
                    @endif

                    <div class="form-field">
                        <label for="customer_name">نام و نام‌خانوادگی <span style="color:#dc2626">*</span></label>
                        <input type="text" id="customer_name" name="customer_name"
                               value="{{ old('customer_name', auth()->user()?->name) }}" required>
                        @error('customer_name')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-field">
                        <label for="customer_mobile">شماره موبایل <span style="color:#dc2626">*</span></label>
                        <input type="text" id="customer_mobile" name="customer_mobile" dir="ltr"
                               value="{{ old('customer_mobile', auth()->user()?->mobile) }}" required>
                        @error('customer_mobile')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-field">
                        <label for="customer_address">آدرس تحویل <span style="color:#dc2626">*</span></label>
                        <textarea id="customer_address" name="customer_address" rows="3" required>{{ old('customer_address') }}</textarea>
                        @error('customer_address')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-field">
                        <label for="note">یادداشت (اختیاری)</label>
                        <textarea id="note" name="note" rows="2">{{ old('note') }}</textarea>
                    </div>

                    @include('partials.shop-captcha')

                    <button type="submit" class="checkout-submit-btn">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        ثبت سفارش
                        @if($membership->hasDiscount())
                            <span style="font-size:.8rem;opacity:.85;font-weight:600">
                                (صرفه‌جویی {!! \App\Helpers\CurrencyHelper::formatPrice($membership->discountAmount) !!} ت)
                            </span>
                        @endif
                    </button>
                </form>
            </div>

        </div>
    </div>

</div>{{-- end cart-grid --}}

@else
    {{-- Empty cart --}}
    <div class="cart-empty">
        <div class="cart-empty__icon">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="36" height="36">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
        </div>
        <h2>سبد خرید خالی است</h2>
        <p>محصولات مورد نظر را از فروشگاه به سبد اضافه کنید.</p>
        <a href="{{ route('shop.index') }}" class="cart-empty__btn">مشاهده فروشگاه</a>
    </div>
@endif

</div>{{-- end cart-pg --}}
@endsection
