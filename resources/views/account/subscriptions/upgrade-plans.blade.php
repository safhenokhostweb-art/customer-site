@extends('account.layout')
@section('title', 'تغییر / ارتقاء پلن')
@section('header', 'تغییر پلن اشتراک')

@section('content')
<div class="upg-pg" style="font-family:Vazirmatn,Tahoma,'Segoe UI',Arial,sans-serif">

{{-- کارت اشتراک فعلی --}}
<div class="upg-pg__current-bar">
    <div class="upg-pg__current-bar__icon">
        <x-core-ui::icon name="shield-check" style="width:20px;height:20px" />
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
    <x-core-ui::icon name="alert-triangle" style="width:16px;height:16px" />
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
    <x-core-ui::icon name="plan" style="width:48px;height:48px" />
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
                        <x-core-ui::icon name="trending-up" style="width:11px;height:11px" />
                        ارتقاء
                        @else
                        <x-core-ui::icon name="trending-down" style="width:11px;height:11px" />
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
                <x-core-ui::icon name="credit-card" style="width:13px;height:13px" />
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
                <x-core-ui::icon name="spark" style="width:13px;height:13px" />
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
                        @php $benefitIconMap = ['tag'=>'price-tag','truck'=>'package','headset'=>'phone','gift'=>'coupon']; @endphp
                        <x-core-ui::icon :name="$benefitIconMap[$badge['icon']] ?? 'star'" style="width:13px;height:13px" />
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
                    @if($isDirect)
                    <x-core-ui::icon name="zap" style="width:16px;height:16px" />
                    @else
                    <x-core-ui::icon name="credit-card" style="width:16px;height:16px" />
                    @endif
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
    <x-core-ui::icon name="info-circle" style="width:14px;height:14px" />
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
