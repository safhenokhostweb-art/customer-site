@extends('admin.layout')

@section('title', $theme['name'] . ' — فروشگاه قالب‌ها')

@section('content')
<div class="space-y-6" dir="rtl">

  {{-- Breadcrumb --}}
  <div class="flex items-center gap-2 text-sm text-gray-500">
    <a href="{{ route('admin.theme-marketplace.index') }}" class="hover:text-indigo-600">فروشگاه قالب‌ها</a>
    <svg class="w-3 h-3 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-gray-800 font-medium">{{ $theme['name'] }}</span>
  </div>

  {{-- Hero --}}
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    {{-- Cover image --}}
    <div class="relative h-56 bg-linear-to-l from-indigo-50 to-violet-50 overflow-hidden">
      @if($theme['preview_image'] ?? $theme['thumbnail'] ?? null)
        <img src="{{ $theme['preview_image'] ?? $theme['thumbnail'] }}"
             class="w-full h-full object-cover"
             alt="{{ $theme['name'] }}">
      @else
        <div class="w-full h-full flex items-center justify-center">
          <x-core-ui::icon name="image" class="w-20 h-20 text-indigo-100" />
        </div>
      @endif
      {{-- Overlay badges --}}
      <div class="absolute top-4 right-4 flex flex-wrap gap-2">
        @if($theme['is_featured'] ?? false)
          <span class="bg-amber-400 text-amber-900 text-sm px-3 py-1 rounded-full font-bold">⭐ ویژه</span>
        @endif
        @if($theme['is_bestseller'] ?? false)
          <span class="bg-orange-500 text-white text-sm px-3 py-1 rounded-full font-bold">🔥 پرفروش</span>
        @endif
        @if($theme['is_new_release'] ?? false)
          <span class="bg-green-500 text-white text-sm px-3 py-1 rounded-full font-bold">✨ جدید</span>
        @endif
        @if($theme['has_license'] ?? false)
          <span class="bg-indigo-600 text-white text-sm px-3 py-1 rounded-full font-bold">✓ خریداری‌شده</span>
        @endif
      </div>
    </div>

    {{-- Theme info + CTA --}}
    <div class="p-6 flex flex-wrap gap-6 items-start">
      <div class="flex-1 min-w-0">
        <div class="flex items-start gap-3 flex-wrap">
          <h1 class="text-2xl font-bold text-gray-900">{{ $theme['name'] }}</h1>
          @if($theme['category'] ?? null)
            <span class="bg-gray-100 text-gray-600 text-sm px-3 py-0.5 rounded-full mt-1">{{ \App\Services\ThemeCategoryHelper::label($theme['category']) }}</span>
          @endif
        </div>
        <p class="text-sm text-gray-400 font-mono mt-0.5">{{ $theme['slug'] }}</p>

        {{-- Stats --}}
        <div class="flex flex-wrap gap-4 mt-3 text-sm text-gray-500">
          @if(($theme['installs_count'] ?? 0) > 0)
            <span>⬇ @numFa($theme['installs_count']) نصب</span>
          @endif
          @if(($theme['sales_count'] ?? 0) > 0)
            <span>🛒 @numFa($theme['sales_count']) فروش</span>
          @endif
          @if(($theme['quality_score'] ?? 0) > 0)
            @php $qc = ($theme['quality_score'] >= 90) ? 'emerald' : (($theme['quality_score'] >= 75) ? 'green' : (($theme['quality_score'] >= 60) ? 'amber' : 'rose')); @endphp
            <span class="text-{{ $qc }}-600 font-semibold">★ {{ $theme['quality_score'] }}/۱۰۰</span>
          @endif
        </div>

        {{-- Description --}}
        @if($theme['description'] ?? null)
          <p class="text-gray-600 text-sm leading-7 mt-4 max-w-2xl">{{ $theme['description'] }}</p>
        @endif
      </div>

      {{-- Pricing + CTA sidebar --}}
      <div class="w-full sm:w-72 shrink-0">
        <div class="bg-gray-50 border border-gray-200 rounded-xl p-5 space-y-3">
          {{-- Pricing --}}
          @if($theme['is_free'] ?? false)
            <div class="text-center py-2">
              <p class="text-3xl font-bold text-emerald-600">رایگان</p>
            </div>
          @else
            <div class="space-y-2">
              <div class="flex items-center justify-between bg-white border border-gray-200 rounded-lg px-3 py-2">
                <span class="text-xs text-gray-500">یک‌بار خرید</span>
                <span class="font-bold text-gray-800">{{ num_fa($theme['price_rial']) }} <span class="text-xs font-normal text-gray-400">ریال</span></span>
              </div>
              @if($theme['annual_price'] ?? null)
              <div class="flex items-center justify-between bg-indigo-50 border border-indigo-100 rounded-lg px-3 py-2">
                <span class="text-xs text-indigo-500">سالانه</span>
                <span class="font-bold text-indigo-700">{{ num_fa($theme['annual_price']) }} <span class="text-xs font-normal text-indigo-400">ریال/سال</span></span>
              </div>
              @endif
              @if($theme['lifetime_price'] ?? null)
              <div class="flex items-center justify-between bg-violet-50 border border-violet-100 rounded-lg px-3 py-2">
                <span class="text-xs text-violet-500">مادام‌العمر</span>
                <span class="font-bold text-violet-700">{{ num_fa($theme['lifetime_price']) }} <span class="text-xs font-normal text-violet-400">ریال</span></span>
              </div>
              @endif
            </div>
          @endif

          {{-- Requirements warning --}}
          @if(!empty($theme['requirements']))
            <div class="text-xs text-gray-500 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2">
              <span class="font-medium text-amber-700">ماژول‌های مورد نیاز: </span>
              {{ implode('، ', $theme['requirements']) }}
            </div>
          @endif

          {{-- CTA --}}
          @if($theme['has_license'] ?? false)
            <div class="space-y-2">
              <form method="POST" action="{{ route('admin.theme-marketplace.install', $theme['id']) }}"
                onsubmit="return confirm('قالب «{{ $theme['name'] }}» نصب و فعال شود؟')">
                @csrf
                <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white text-sm font-semibold py-3 rounded-lg transition">
                  نصب و فعال‌سازی
                </button>
              </form>
              <a href="{{ route('admin.theme-marketplace.my-licenses') }}"
                 class="block text-center text-sm text-gray-500 hover:text-gray-700 py-1">مشاهده لایسنس</a>
            </div>
          @else
            <form method="POST" action="{{ route('admin.theme-marketplace.purchase', $theme['id']) }}">
              @csrf
              <button type="submit"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold py-3 rounded-lg transition">
                {{ ($theme['is_free'] ?? false) ? 'دریافت رایگان' : 'خرید این قالب' }}
              </button>
            </form>
          @endif

          @if($theme['preview_url'] ?? null)
            <a href="{{ $theme['preview_url'] }}" target="_blank"
               class="block text-center text-sm text-indigo-600 hover:text-indigo-800 font-medium py-1">
              مشاهده دمو زنده ↗
            </a>
          @endif
        </div>
      </div>
    </div>
  </div>

  {{-- Screenshots --}}
  @if(!empty($theme['screenshots']))
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
    <h2 class="text-sm font-semibold text-gray-700 mb-4">تصاویر قالب</h2>
    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
      @foreach($theme['screenshots'] as $shot)
        <a href="{{ $shot }}" target="_blank">
          <img src="{{ $shot }}" alt="" class="w-full h-36 object-cover rounded-xl border border-gray-200 hover:opacity-90 transition">
        </a>
      @endforeach
    </div>
  </div>
  @endif

  {{-- Version notes --}}
  @if($theme['version_notes'] ?? null)
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
    <h2 class="text-sm font-semibold text-gray-700 mb-2">یادداشت نسخه</h2>
    <p class="text-sm text-gray-600 leading-7">{{ $theme['version_notes'] }}</p>
  </div>
  @endif

  {{-- Back --}}
  <a href="{{ route('admin.theme-marketplace.index') }}"
     class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
    بازگشت به فروشگاه
  </a>

</div>
@endsection
