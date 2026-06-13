@php
    $currentLocale = session('site_locale', 'fa');
    $locales = [
        'fa' => ['label' => 'فارسی',  'flag' => '🇮🇷', 'dir' => 'rtl'],
        'ar' => ['label' => 'عربي',   'flag' => '🇸🇦', 'dir' => 'rtl'],
        'en' => ['label' => 'English', 'flag' => '🇬🇧', 'dir' => 'ltr'],
    ];
    $current = $locales[$currentLocale] ?? $locales['fa'];
@endphp

<div class="relative" x-data="{ open: false }" @click.outside="open = false">
    <button type="button"
            @click="open = !open"
            class="flex items-center gap-2 px-3 py-1.5 rounded-xl text-sm font-medium border border-slate-200 bg-white hover:bg-slate-50 transition">
        <span>{{ $current['flag'] }}</span>
        <span class="hidden sm:inline">{{ $current['label'] }}</span>
        <svg class="w-3.5 h-3.5 text-slate-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <div x-show="open" x-transition
         class="absolute top-full mt-1 end-0 z-50 bg-white border border-slate-200 rounded-2xl shadow-xl overflow-hidden min-w-[140px]">
        @foreach($locales as $code => $info)
        <a href="{{ route('lang.switch', $code) }}"
           class="flex items-center gap-3 px-4 py-2.5 text-sm hover:bg-slate-50 transition
                  {{ $code === $currentLocale ? 'font-semibold text-blue-600 bg-blue-50' : 'text-slate-700' }}"
           dir="{{ $info['dir'] }}">
            <span>{{ $info['flag'] }}</span>
            <span>{{ $info['label'] }}</span>
            @if($code === $currentLocale)
            <svg class="w-4 h-4 mr-auto text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            @endif
        </a>
        @endforeach
    </div>
</div>
