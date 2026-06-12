@extends('account.layout')
@section('title', 'امتیازهای من')
@section('header', 'امتیازهای من')

@push('head')
<style>
.pts-pg, .pts-pg * { font-family: Vazirmatn, Tahoma, 'Segoe UI', Arial, sans-serif; box-sizing: border-box; }

/* ── Hero balance card ── */
.pts-hero { background: linear-gradient(135deg, #7c3aed, #4f46e5); border-radius: 1.375rem; padding: 2rem 1.75rem; color: #fff; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; gap: 1.5rem; -ms-flex-wrap: wrap; flex-wrap: wrap; -webkit-box-shadow: 0 8px 32px rgba(124,58,237,.35); box-shadow: 0 8px 32px rgba(124,58,237,.35); margin-bottom: 1.75rem; position: relative; overflow: hidden; }
.pts-hero::before { content: ''; position: absolute; top: -30%; right: -5%; width: 200px; height: 200px; border-radius: 50%; background: rgba(255,255,255,.06); pointer-events: none; }
.pts-hero::after  { content: ''; position: absolute; bottom: -40%; left: 10%; width: 150px; height: 150px; border-radius: 50%; background: rgba(255,255,255,.04); pointer-events: none; }
.pts-hero__icon { width: 64px; height: 64px; background: rgba(255,255,255,.15); border-radius: 50%; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; -webkit-box-pack: center; -ms-flex-pack: center; justify-content: center; -ms-flex-negative: 0; flex-shrink: 0; }
.pts-hero__body { -webkit-box-flex: 1; -ms-flex: 1; flex: 1; }
.pts-hero__label { font-size: .875rem; opacity: .8; margin: 0 0 .25rem; }
.pts-hero__balance { font-size: 2.5rem; font-weight: 900; line-height: 1; margin: 0 0 .375rem; letter-spacing: -.03em; }
.pts-hero__hint { font-size: .8rem; opacity: .75; margin: 0; }
.pts-hero__equiv { background: rgba(255,255,255,.15); border-radius: .75rem; padding: .875rem 1.25rem; text-align: center; -ms-flex-negative: 0; flex-shrink: 0; }
.pts-hero__equiv-label { font-size: .72rem; opacity: .8; margin: 0 0 .25rem; }
.pts-hero__equiv-value { font-size: 1.25rem; font-weight: 800; margin: 0; }

/* ── Rules ── */
.pts-rules { display: -ms-grid; display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.75rem; }
@media (max-width: 600px) { .pts-rules { grid-template-columns: 1fr; } }
.pts-rule { background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem; padding: 1rem 1.125rem; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: start; -ms-flex-align: start; align-items: flex-start; gap: .75rem; -webkit-box-shadow: 0 1px 4px rgba(0,0,0,.05); box-shadow: 0 1px 4px rgba(0,0,0,.05); }
.pts-rule__icon { width: 36px; height: 36px; border-radius: .625rem; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; -webkit-box-pack: center; -ms-flex-pack: center; justify-content: center; -ms-flex-negative: 0; flex-shrink: 0; }
.pts-rule__title { font-size: .8125rem; font-weight: 700; color: #1e293b; margin: 0 0 .2rem; }
.pts-rule__desc { font-size: .75rem; color: #64748b; margin: 0; line-height: 1.5; }

/* ── History ── */
.pts-hist { background: #fff; border: 1px solid #e2e8f0; border-radius: 1.25rem; overflow: hidden; -webkit-box-shadow: 0 2px 8px rgba(0,0,0,.05); box-shadow: 0 2px 8px rgba(0,0,0,.05); }
.pts-hist__head { padding: .875rem 1.375rem; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; gap: .5rem; }
.pts-hist__title { font-size: .875rem; font-weight: 700; color: #374151; -webkit-box-flex: 1; -ms-flex: 1; flex: 1; }

/* ── Row ── */
.pts-row { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; gap: 1rem; padding: .875rem 1.375rem; border-bottom: 1px solid #f1f5f9; -webkit-transition: background .15s; transition: background .15s; }
.pts-row:last-child { border-bottom: none; }
.pts-row:hover { background: #fafbff; }
.pts-row__icon { width: 36px; height: 36px; border-radius: .625rem; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; -webkit-box-pack: center; -ms-flex-pack: center; justify-content: center; -ms-flex-negative: 0; flex-shrink: 0; }
.pts-row__icon--earn  { background: #f0fdf4; color: #16a34a; }
.pts-row__icon--spend { background: #fff1f2; color: #be123c; }
.pts-row__icon--other { background: #f8fafc; color: #64748b; }
.pts-row__body { -webkit-box-flex: 1; -ms-flex: 1; flex: 1; min-width: 0; }
.pts-row__desc { font-size: .8375rem; font-weight: 600; color: #1e293b; margin: 0 0 .15rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pts-row__meta { font-size: .75rem; color: #94a3b8; margin: 0; }
.pts-row__amount { font-size: 1rem; font-weight: 800; white-space: nowrap; -ms-flex-negative: 0; flex-shrink: 0; }
.pts-row__amount--earn  { color: #16a34a; }
.pts-row__amount--spend { color: #be123c; }
.pts-row__balance { font-size: .72rem; color: #64748b; text-align: left; white-space: nowrap; }

/* ── Empty ── */
.pts-empty { text-align: center; padding: 3rem 1.5rem; color: #94a3b8; }
.pts-empty svg { display: block; margin: 0 auto .875rem; }
</style>
@endpush

@section('content')
<div class="pts-pg">

{{-- ── Hero Balance ── --}}
<div class="pts-hero">
    <div class="pts-hero__icon">
        <x-core-ui::icon name="spark" class="w-7 h-7 text-white" />
    </div>
    <div class="pts-hero__body">
        <p class="pts-hero__label">موجودی امتیاز شما</p>
        <p class="pts-hero__balance">{{ \App\Helpers\NumberHelper::formatFa($balance) }}</p>
        <p class="pts-hero__hint">امتیاز · معادل {{ \App\Helpers\CurrencyHelper::formatPrice($balance * \App\Services\MembershipPointsService::TOMAN_PER_POINT) }} {{ \App\Helpers\CurrencyHelper::unitLabel() }}</p>
    </div>
    <div class="pts-hero__equiv">
        <p class="pts-hero__equiv-label">نرخ استفاده</p>
        <p class="pts-hero__equiv-value">هر {{ \App\Helpers\NumberHelper::formatFa(1000) }} امتیاز</p>
        <p class="pts-hero__equiv-label" style="margin-top:.25rem">= {{ \App\Helpers\CurrencyHelper::formatPrice(1000 * \App\Services\MembershipPointsService::TOMAN_PER_POINT) }} {{ \App\Helpers\CurrencyHelper::unitLabel() }}</p>
    </div>
</div>

{{-- ── Rules ── --}}
<div class="pts-rules">
    <div class="pts-rule">
        <div class="pts-rule__icon" style="background:#f0fdf4;color:#16a34a">
            <x-core-ui::icon name="cart" class="w-4.5 h-4.5" />
        </div>
        <div>
            <p class="pts-rule__title">خرید محصول</p>
            <p class="pts-rule__desc">به ازای هر ۱,۰۰۰ تومان خرید، ۱ امتیاز (ضربدر ضریب پلن شما) کسب کنید.</p>
        </div>
    </div>
    <div class="pts-rule">
        <div class="pts-rule__icon" style="background:#eff6ff;color:#2563eb">
            <x-core-ui::icon name="subscription" class="w-4 h-4" />
        </div>
        <div>
            <p class="pts-rule__title">فعال‌سازی اشتراک</p>
            <p class="pts-rule__desc">{{ \App\Helpers\NumberHelper::formatFa(\App\Services\MembershipPointsService::POINTS_ON_SUBSCRIBE) }} امتیاز هدیه پس از فعال کردن هر اشتراک.</p>
        </div>
    </div>
    <div class="pts-rule">
        <div class="pts-rule__icon" style="background:#fef3c7;color:#d97706">
            <x-core-ui::icon name="refresh" class="w-4 h-4" />
        </div>
        <div>
            <p class="pts-rule__title">تمدید اشتراک</p>
            <p class="pts-rule__desc">{{ \App\Helpers\NumberHelper::formatFa(\App\Services\MembershipPointsService::POINTS_ON_RENEW) }} امتیاز وفاداری با هر تمدید.</p>
        </div>
    </div>
    <div class="pts-rule">
        <div class="pts-rule__icon" style="background:#fdf2f8;color:#db2777">
            <x-core-ui::icon name="clock" class="w-4 h-4" />
        </div>
        <div>
            <p class="pts-rule__title">انقضای امتیاز</p>
            <p class="pts-rule__desc">امتیازها {{ \App\Helpers\NumberHelper::formatFa(\App\Services\MembershipPointsService::EXPIRY_DAYS) }} روز پس از کسب منقضی می‌شوند. به موقع استفاده کنید!</p>
        </div>
    </div>
</div>

{{-- ── History ── --}}
<div class="pts-hist">
    <div class="pts-hist__head">
        <span class="pts-hist__title">تاریخچه امتیازها</span>
        @if($history->total() > 0)
        <span style="font-size:.75rem;color:#94a3b8">{{ \App\Helpers\NumberHelper::formatFa($history->total()) }} تراکنش</span>
        @endif
    </div>

    @forelse($history as $point)
    @php
        $isEarn  = $point->amount > 0;
        $iconCls = $isEarn ? 'pts-row__icon--earn' : 'pts-row__icon--spend';
        $amtCls  = $isEarn ? 'pts-row__amount--earn' : 'pts-row__amount--spend';
        $prefix  = $isEarn ? '+' : '';
    @endphp
    <div class="pts-row">
        <div class="pts-row__icon {{ $iconCls }}">
            @if($isEarn)
            <x-core-ui::icon name="arrow-up" class="w-4 h-4" />
            @else
            <x-core-ui::icon name="arrow-down" class="w-4 h-4" />
            @endif
        </div>
        <div class="pts-row__body">
            <p class="pts-row__desc">{{ $point->description ?? $point->getSourceLabel() }}</p>
            <p class="pts-row__meta">
                {{ \App\Helpers\Jalali::formatFa($point->created_at, 'Y/m/d H:i') }}
                @if($point->expires_at && $point->amount > 0)
                · انقضا: {{ \App\Helpers\Jalali::formatFa($point->expires_at, 'Y/m/d') }}
                @endif
            </p>
        </div>
        <div>
            <div class="pts-row__amount {{ $amtCls }}">
                {{ $prefix }}{{ \App\Helpers\NumberHelper::formatFa(abs($point->amount)) }}
            </div>
            <div class="pts-row__balance">موجودی: {{ \App\Helpers\NumberHelper::formatFa($point->balance_after) }}</div>
        </div>
    </div>
    @empty
    <div class="pts-empty">
        <x-core-ui::icon name="spark" class="w-12 h-12 text-slate-200 mx-auto" />
        <p style="font-size:.9375rem;font-weight:600;color:#475569;margin:0 0 .375rem">هنوز امتیازی کسب نکرده‌اید</p>
        <p style="font-size:.8125rem;margin:0">با خرید محصول یا فعال‌سازی اشتراک امتیاز کسب کنید.</p>
    </div>
    @endforelse

    @if($history->hasPages())
    <div style="padding:.875rem 1.375rem;border-top:1px solid #f1f5f9">{{ $history->links() }}</div>
    @endif
</div>

</div>
@endsection
