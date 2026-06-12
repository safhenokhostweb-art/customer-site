@extends(\App\Helpers\SiteHelper::view('layout'))
@section('title', 'پلن‌های اشتراک')

@section('content')
<main>
<div class="pricing-page" style="font-family:Vazirmatn,Tahoma,'Segoe UI',Arial,sans-serif;direction:rtl;max-width:1100px;margin:0 auto;padding:2rem 1rem 3rem">

{{-- ═══ هدر ═══════════════════════════════════════════════════════════════ --}}
<div style="text-align:center;margin-bottom:3rem">
    <div style="display:inline-flex;align-items:center;gap:.5rem;background:#eef2ff;color:#4f46e5;border-radius:2rem;padding:.4rem 1.25rem;font-size:.82rem;font-weight:600;margin-bottom:1.25rem">
        <x-core-ui::icon name="spark" style="width:16px;height:16px" />
        پلن‌های ویژه عضویت
    </div>
    <h1 style="font-size:2rem;font-weight:800;color:#111827;margin:0 0 .75rem">انتخاب پلن مناسب</h1>
    <p style="font-size:1rem;color:#6b7280;max-width:500px;margin:0 auto">با انتخاب یک پلن عضویت، از تخفیف‌های ویژه، امتیاز دوچندان، ارسال رایگان و امکانات VIP بهره‌مند شوید.</p>
</div>

{{-- ═══ گرید پلن‌ها ══════════════════════════════════════════════════════ --}}
@if($plans->isEmpty())
<div style="text-align:center;padding:4rem;color:#9ca3af">
    <x-core-ui::icon name="plan" style="width:48px;height:48px;margin:0 auto 1rem;color:#d1d5db" />
    <p>در حال حاضر پلن فعالی وجود ندارد.</p>
</div>
@else
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1.5rem">
    @foreach($plans as $plan)
    @php
        $isActive    = $activePlanId && $activePlanId === $plan->id;
        $highlighted = $plan->highlighted;
        $effective   = $plan->getEffectivePrice();
        $badges      = $plan->getBenefitBadges();
        $features    = $plan->getFeaturesArray();
        $borderColor = $highlighted ? '#4f46e5' : '#e5e7eb';
        $cardBg      = $highlighted ? 'linear-gradient(135deg,#4f46e5 0%,#7c3aed 100%)' : '#fff';
        $textColor   = $highlighted ? '#fff' : '#111827';
        $mutedColor  = $highlighted ? 'rgba(255,255,255,.75)' : '#6b7280';
    @endphp
    <div style="position:relative;background:{{ $highlighted ? $cardBg : '#fff' }};border:2px solid {{ $borderColor }};border-radius:1.5rem;padding:2rem 1.75rem;box-shadow:{{ $highlighted ? '0 20px 40px rgba(79,70,229,.25)' : '0 1px 8px rgba(0,0,0,.06)' }};display:flex;flex-direction:column">

        {{-- badge پلن برجسته --}}
        @if($highlighted)
        <div style="position:absolute;top:-13px;right:1.5rem;background:#fbbf24;color:#78350f;font-size:.72rem;font-weight:700;padding:.3rem .9rem;border-radius:2rem">
            {{ $plan->badge_text ?: 'محبوب‌ترین' }}
        </div>
        @endif

        {{-- اگر کاربر این پلن را دارد --}}
        @if($isActive)
        <div style="position:absolute;top:-13px;left:1.5rem;background:#d1fae5;color:#065f46;font-size:.72rem;font-weight:700;padding:.3rem .9rem;border-radius:2rem;display:flex;align-items:center;gap:.3rem">
            <x-core-ui::icon name="check-circle" style="width:12px;height:12px" />
            پلن فعلی شما
        </div>
        @endif

        {{-- نام و توضیح --}}
        <h2 style="font-size:1.2rem;font-weight:700;color:{{ $textColor }};margin:0 0 .5rem">{{ $plan->name }}</h2>
        @if($plan->description)
        <p style="font-size:.82rem;color:{{ $mutedColor }};margin:0 0 1.5rem;line-height:1.6">{{ $plan->description }}</p>
        @else
        <div style="margin-bottom:1.5rem"></div>
        @endif

        {{-- قیمت --}}
        <div style="margin-bottom:1.5rem">
            @if($plan->isFree())
            <div style="font-size:2rem;font-weight:800;color:{{ $textColor }}">رایگان</div>
            @else
                @if($plan->hasSpecialPrice())
                <div style="font-size:.85rem;text-decoration:line-through;color:{{ $mutedColor }}">{{ \App\Helpers\NumberHelper::formatFa($plan->price) }} تومان</div>
                @endif
            <div style="font-size:2rem;font-weight:800;color:{{ $textColor }}">
                {{ \App\Helpers\NumberHelper::formatFa($effective) }}
                <span style="font-size:.9rem;font-weight:400;color:{{ $mutedColor }}">تومان</span>
            </div>
            @endif
            <div style="font-size:.78rem;color:{{ $mutedColor }};margin-top:.25rem">{{ $plan->getDurationLabel() }}</div>
        </div>

        {{-- مزایای badge --}}
        @if(count($badges))
        <div style="display:flex;flex-wrap:wrap;gap:.5rem;margin-bottom:1.25rem">
            @foreach($badges as $badge)
            <span style="display:inline-flex;align-items:center;gap:.3rem;font-size:.73rem;background:{{ $highlighted ? 'rgba(255,255,255,.15)' : '#f3f4f6' }};color:{{ $highlighted ? '#fff' : '#374151' }};border-radius:2rem;padding:.3rem .75rem">
                @php $bim = ['tag'=>'price-tag','truck'=>'package','star'=>'star']; @endphp
                <x-core-ui::icon :name="$bim[$badge['icon']] ?? 'coupon'" style="width:12px;height:12px" />
                {{ $badge['label'] }}
            </span>
            @endforeach
        </div>
        @endif

        {{-- features --}}
        @if(count($features))
        <ul style="list-style:none;padding:0;margin:0 0 1.5rem;display:flex;flex-direction:column;gap:.625rem;flex:1">
            @foreach($features as $feature)
            <li style="display:flex;align-items:flex-start;gap:.625rem;font-size:.83rem;color:{{ $textColor }}">
                <x-core-ui::icon name="check" style="width:16px;height:16px;flex-shrink:0;margin-top:.1rem;color:{{ $highlighted ? '#a5f3fc' : '#10b981' }}" />
                {{ $feature }}
            </li>
            @endforeach
        </ul>
        @else
        <div style="flex:1"></div>
        @endif

        {{-- trial badge --}}
        @if($plan->hasTrial())
        <div style="text-align:center;font-size:.77rem;color:{{ $mutedColor }};margin-bottom:.75rem">
            {{ \App\Helpers\NumberHelper::formatFa($plan->trial_days) }} روز رایگان برای آزمایش
        </div>
        @endif

        {{-- دکمه --}}
        @if($isActive)
        <div style="text-align:center;background:{{ $highlighted ? 'rgba(255,255,255,.15)' : '#f3f4f6' }};color:{{ $highlighted ? '#fff' : '#6b7280' }};border-radius:.875rem;padding:.85rem;font-size:.88rem;font-weight:600">
            <x-core-ui::icon name="check-circle" style="display:inline;vertical-align:middle;margin-left:.3rem;width:16px;height:16px" />
            پلن فعال شما
        </div>
        @elseif(auth()->check())
        <a href="{{ route('account.subscriptions.plans') }}"
           style="display:block;text-align:center;background:{{ $highlighted ? '#fff' : '#4f46e5' }};color:{{ $highlighted ? '#4f46e5' : '#fff' }};border-radius:.875rem;padding:.85rem;font-size:.9rem;font-weight:700;text-decoration:none;transition:opacity .15s"
           onmouseover="this.style.opacity='.88'" onmouseout="this.style.opacity='1'">
            {{ $plan->isFree() ? 'انتخاب رایگان' : 'خرید اشتراک' }}
        </a>
        @else
        <a href="{{ route('login', ['redirect' => route('account.subscriptions.plans')]) }}"
           style="display:block;text-align:center;background:{{ $highlighted ? '#fff' : '#4f46e5' }};color:{{ $highlighted ? '#4f46e5' : '#fff' }};border-radius:.875rem;padding:.85rem;font-size:.9rem;font-weight:700;text-decoration:none;transition:opacity .15s"
           onmouseover="this.style.opacity='.88'" onmouseout="this.style.opacity='1'">
            {{ $plan->isFree() ? 'ثبت‌نام رایگان' : 'خرید اشتراک' }}
        </a>
        @endif

    </div>
    @endforeach
</div>
@endif

{{-- ═══ FAQ ══════════════════════════════════════════════════════════════ --}}
<div style="margin-top:4rem;border-top:1px solid #e5e7eb;padding-top:3rem" x-data="{ open: null }">
    <h2 style="text-align:center;font-size:1.35rem;font-weight:700;color:#111827;margin:0 0 2rem">سؤالات متداول</h2>
    @php
    $faqs = [
        ['q'=>'آیا می‌توانم پلن را تغییر دهم؟',         'a'=>'بله، در هر زمان می‌توانید پلن خود را ارتقا یا تغییر دهید. تغییر پلن از پنل حساب کاربری امکان‌پذیر است.'],
        ['q'=>'پرداخت به چه صورت است؟',                  'a'=>'پرداخت از طریق درگاه بانکی امن انجام می‌شود. فاکتور فوری صادر و تحویل داده می‌شود.'],
        ['q'=>'آیا دوره آزمایشی وجود دارد؟',             'a'=>'برخی پلن‌ها دارای دوره آزمایشی رایگان هستند. اطلاعات هر پلن را در کارت مربوطه مشاهده کنید.'],
        ['q'=>'لغو اشتراک چگونه انجام می‌شود؟',          'a'=>'در هر زمان از پنل حساب کاربری بخش اشتراک‌ها می‌توانید اشتراک خود را لغو کنید.'],
    ];
    @endphp
    <div style="max-width:680px;margin:0 auto;display:flex;flex-direction:column;gap:.875rem">
        @foreach($faqs as $i => $faq)
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:1rem;overflow:hidden">
            <button x-on:click="open === {{ $i }} ? open = null : open = {{ $i }}"
                    style="width:100%;text-align:right;padding:1rem 1.25rem;background:none;border:none;cursor:pointer;display:flex;align-items:center;justify-content:space-between;font-family:inherit;font-size:.9rem;font-weight:600;color:#1f2937">
                {{ $faq['q'] }}
                <x-core-ui::icon name="chevron-down"
                     x-bind:style="open === {{ $i }} ? 'transform:rotate(180deg)' : ''"
                     style="width:18px;height:18px;flex-shrink:0;transition:transform .2s;color:#6b7280" />
            </button>
            <div x-show="open === {{ $i }}" x-collapse style="padding:0 1.25rem 1rem;font-size:.85rem;color:#6b7280;line-height:1.7">
                {{ $faq['a'] }}
            </div>
        </div>
        @endforeach
    </div>
</div>

</div>
</main>
@endsection
