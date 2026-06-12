@extends('account.layout')
@section('title', 'دعوت دوستان')
@section('header', 'دعوت دوستان')

@section('content')
<div class="ref-page" style="font-family:Vazirmatn,Tahoma,'Segoe UI',Arial,sans-serif;direction:rtl">

{{-- ═══ Hero Banner ═══════════════════════════════════════════════════════ --}}
<div style="background:linear-gradient(135deg,#4f46e5 0%,#7c3aed 100%);border-radius:1.25rem;padding:2rem 2rem 1.75rem;color:#fff;margin-bottom:1.5rem;position:relative;overflow:hidden">
    <div style="position:absolute;left:-40px;top:-40px;width:180px;height:180px;background:rgba(255,255,255,.06);border-radius:50%"></div>
    <div style="position:absolute;left:80px;bottom:-60px;width:240px;height:240px;background:rgba(255,255,255,.04);border-radius:50%"></div>
    <div style="position:relative">
        <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.75rem">
            <div style="background:rgba(255,255,255,.15);border-radius:.75rem;padding:.6rem">
                <x-core-ui::icon name="users" class="w-6 h-6 text-white" />
            </div>
            <div>
                <h1 style="font-size:1.35rem;font-weight:700;margin:0">دعوت دوستان</h1>
                <p style="margin:0;font-size:.85rem;opacity:.85">هر نفری که دعوت کنید به هر دوی شما پاداش می‌رسد</p>
            </div>
        </div>
        <div style="display:flex;gap:1.5rem;margin-top:1.25rem">
            <div style="text-align:center">
                <div style="font-size:1.6rem;font-weight:800">{{ \App\Helpers\NumberHelper::formatFa(300) }}</div>
                <div style="font-size:.75rem;opacity:.8">امتیاز برای شما</div>
            </div>
            <div style="width:1px;background:rgba(255,255,255,.25)"></div>
            <div style="text-align:center">
                <div style="font-size:1.6rem;font-weight:800">{{ \App\Helpers\NumberHelper::formatFa(100) }}</div>
                <div style="font-size:.75rem;opacity:.8">امتیاز برای دوستتان</div>
            </div>
            <div style="width:1px;background:rgba(255,255,255,.25)"></div>
            <div style="text-align:center">
                <div style="font-size:1.35rem;font-weight:800">پس از اشتراک اول</div>
                <div style="font-size:.75rem;opacity:.8">شرط دریافت پاداش</div>
            </div>
        </div>
    </div>
</div>

{{-- ═══ آمار ═══════════════════════════════════════════════════════════════ --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.5rem">
    @php
    $statCards = [
        ['label'=>'مجموع دعوت‌ها',  'value'=>$stats['total'],    'color'=>'#4f46e5','bg'=>'#eef2ff','icon'=>'referral'],
        ['label'=>'پاداش داده شده', 'value'=>$stats['rewarded'], 'color'=>'#059669','bg'=>'#d1fae5','icon'=>'check-circle'],
        ['label'=>'در انتظار',       'value'=>$stats['pending'],  'color'=>'#d97706','bg'=>'#fef3c7','icon'=>'clock'],
    ];
    @endphp
    @foreach($statCards as $sc)
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:1rem;padding:1.25rem;text-align:center">
        <div style="width:2.75rem;height:2.75rem;background:{{ $sc['bg'] }};border-radius:.75rem;display:flex;align-items:center;justify-content:center;margin:0 auto .75rem;color:{{ $sc['color'] }}">
            <x-core-ui::icon :name="$sc['icon']" class="w-5 h-5" />
        </div>
        <div style="font-size:1.5rem;font-weight:800;color:#111827">{{ \App\Helpers\NumberHelper::formatFa($sc['value']) }}</div>
        <div style="font-size:.78rem;color:#6b7280;margin-top:.25rem">{{ $sc['label'] }}</div>
    </div>
    @endforeach
</div>

{{-- ═══ لینک دعوت + کپی ════════════════════════════════════════════════════ --}}
<div style="background:#fff;border:1px solid #e5e7eb;border-radius:1.25rem;padding:1.5rem;margin-bottom:1.5rem" x-data="{ copied: false }">
    <div style="display:flex;align-items:center;gap:.625rem;margin-bottom:1rem">
        <x-core-ui::icon name="link" class="w-5 h-5 text-indigo-600" />
        <span style="font-weight:600;color:#111827">لینک دعوت شما</span>
    </div>
    <div style="display:flex;gap:.75rem;align-items:center">
        <input id="ref-link" type="text" readonly value="{{ $stats['link'] }}"
               style="flex:1;background:#f9fafb;border:1px solid #e5e7eb;border-radius:.625rem;padding:.65rem 1rem;font-size:.82rem;color:#374151;direction:ltr;text-align:left;outline:none">
        <button x-on:click="navigator.clipboard.writeText('{{ $stats['link'] }}'); copied = true; setTimeout(()=>copied=false,2500)"
                style="flex-shrink:0;background:#4f46e5;color:#fff;border:none;border-radius:.625rem;padding:.65rem 1.25rem;cursor:pointer;font-family:inherit;font-size:.83rem;display:flex;align-items:center;gap:.4rem;transition:background .15s"
                x-bind:style="copied ? 'background:#059669' : ''">
            <x-core-ui::icon name="copy" class="w-4 h-4" x-show="!copied" />
            <x-core-ui::icon name="check" class="w-4 h-4" x-show="copied" />
            <span x-text="copied ? 'کپی شد!' : 'کپی'"></span>
        </button>
    </div>

    {{-- کد دعوت --}}
    <div style="margin-top:1rem;padding:.75rem 1rem;background:#f0f0ff;border-radius:.75rem;display:flex;align-items:center;justify-content:space-between">
        <div style="font-size:.82rem;color:#6b7280">کد دعوت شما:</div>
        <div style="font-size:1.05rem;font-weight:700;color:#4f46e5;letter-spacing:.1em;direction:ltr">{{ $code->code }}</div>
    </div>
</div>

{{-- ═══ QR Code ════════════════════════════════════════════════════════════ --}}
<div style="background:#fff;border:1px solid #e5e7eb;border-radius:1.25rem;padding:1.5rem;margin-bottom:1.5rem;text-align:center">
    <div style="display:flex;align-items:center;gap:.625rem;margin-bottom:1.25rem;justify-content:center">
        <x-core-ui::icon name="share" class="w-5 h-5 text-indigo-600" />
        <span style="font-weight:600;color:#111827">QR Code لینک دعوت</span>
    </div>
    {{-- QR از google charts API (client-side, بدون ارسال اطلاعات حساس) --}}
    <img src="https://chart.googleapis.com/chart?cht=qr&chs=200x200&chl={{ urlencode($stats['link']) }}&choe=UTF-8"
         alt="QR Code لینک دعوت"
         style="width:200px;height:200px;border-radius:.75rem;border:1px solid #e5e7eb"
         onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
    <div style="display:none;padding:1rem;color:#6b7280;font-size:.82rem">QR Code در دسترس نیست</div>
    <p style="margin-top:.75rem;font-size:.78rem;color:#9ca3af">اسکرین‌شات بگیرید یا QR را برای دوستانتان بفرستید</p>
</div>

{{-- ═══ راهنما ══════════════════════════════════════════════════════════════ --}}
<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:1.25rem;padding:1.5rem">
    <h3 style="font-size:.95rem;font-weight:700;color:#111827;margin:0 0 1rem">چطور کار می‌کند؟</h3>
    <div style="display:flex;flex-direction:column;gap:.875rem">
        @php
        $steps = [
            ['n'=>'۱','title'=>'لینک را به اشتراک بگذارید','desc'=>'لینک یا کد دعوت را برای دوستان و آشنایان ارسال کنید.','c'=>'#4f46e5','bg'=>'#eef2ff'],
            ['n'=>'۲','title'=>'دوستتان ثبت‌نام می‌کند','desc'=>'دوست شما از طریق لینک شما ثبت‌نام کرده و حساب می‌سازد.','c'=>'#7c3aed','bg'=>'#f5f3ff'],
            ['n'=>'۳','title'=>'اولین اشتراک را می‌گیرد','desc'=>'پس از خرید اولین پلن اشتراک، پاداش برای هر دوی شما صادر می‌شود.','c'=>'#059669','bg'=>'#d1fae5'],
            ['n'=>'۴','title'=>'هر دو پاداش می‌گیرید','desc'=>'شما ۳۰۰ امتیاز و دوستتان ۱۰۰ امتیاز دریافت می‌کنید.','c'=>'#d97706','bg'=>'#fef3c7'],
        ];
        @endphp
        @foreach($steps as $step)
        <div style="display:flex;align-items:flex-start;gap:.875rem">
            <div style="width:2rem;height:2rem;flex-shrink:0;background:{{ $step['bg'] }};border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem;color:{{ $step['c'] }}">{{ $step['n'] }}</div>
            <div>
                <div style="font-weight:600;font-size:.88rem;color:#1f2937">{{ $step['title'] }}</div>
                <div style="font-size:.8rem;color:#6b7280;margin-top:.2rem">{{ $step['desc'] }}</div>
            </div>
        </div>
        @endforeach
    </div>
</div>

</div>
@endsection
