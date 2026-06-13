@extends('admin.layout')

@section('title', 'مدیریت قالب Canvas')
@section('header', 'مدیریت قالب Canvas')

@section('content')
<div class="space-y-6" dir="rtl">

  {{-- ─── Breadcrumb ─────────────────────────────────────────────────────── --}}
  <nav class="flex items-center gap-2 text-sm text-gray-500 mb-1">
    <a href="{{ route('admin.theme-design.index') }}" class="hover:text-indigo-600 transition">ظاهر و تم</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">قالب Canvas</span>
  </nav>

  {{-- ─── Alerts ──────────────────────────────────────────────────────────── --}}
  @if(session('success'))
    <div class="bg-green-50 border border-green-200 rounded-xl p-4 flex items-center gap-3">
      <x-core-ui::icon name="check-circle" class="w-5 h-5 text-green-600 shrink-0" />
      <p class="text-green-800 text-sm font-medium">{{ session('success') }}</p>
    </div>
  @endif
  @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">{{ session('error') }}</div>
  @endif

  @if(!$themeData)
    {{-- ─── No Canvas Theme ───────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm py-16 text-center">
      <div class="w-20 h-20 bg-indigo-50 rounded-3xl flex items-center justify-center mx-auto mb-5">
        <x-core-ui::icon name="layout" class="w-10 h-10 text-indigo-300" />
      </div>
      <h2 class="text-xl font-bold text-gray-800 mb-2">قالب Canvas فعال نیست</h2>
      <p class="text-gray-500 text-sm mb-6 max-w-xs mx-auto leading-relaxed">
        هیچ قالب Canvas‌ای روی این سایت نصب نشده است.
        از مارکت‌پلیس یکی را خریداری یا یک فایل JSON وارد کنید.
      </p>
      <div class="flex items-center justify-center gap-3 flex-wrap">
        <a href="{{ route('admin.theme-marketplace.index') }}"
           class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition">
          <x-core-ui::icon name="store" class="w-4 h-4" />
          مارکت‌پلیس قالب‌ها
        </a>
        <a href="{{ route('admin.theme-design.index') }}"
           class="inline-flex items-center gap-2 bg-white hover:bg-gray-50 text-gray-700 text-sm font-medium px-5 py-2.5 rounded-xl border border-gray-200 transition">
          <x-core-ui::icon name="arrow-right" class="w-4 h-4" />
          بازگشت به تنظیمات تم
        </a>
      </div>
    </div>

    {{-- ─── Import Form (no active theme) ────────────────────────────────── --}}
    @include('admin.theme-design.canvas-import-panel')

  @else
    @php
      $gs      = $themeData['global_styles'] ?? [];
      $primary   = $gs['primary_color']   ?? $gs['colors']['primary']   ?? '#4f46e5';
      $secondary = $gs['secondary_color'] ?? $gs['colors']['secondary'] ?? '#7c3aed';
      $bg        = $gs['background_color'] ?? $gs['colors']['background'] ?? '#f9fafb';
      $pages     = $themeData['pages'] ?? [];
      $headerBks = $themeData['header_blocks'] ?? [];
      $footerBks = $themeData['footer_blocks'] ?? [];
      $pageCount = count($pages);
      $hCount    = count($headerBks);
      $fCount    = count($footerBks);
    @endphp

    {{-- ─── Theme Header Card ─────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      {{-- Color Bar --}}
      <div class="h-2" style="background: linear-gradient(90deg, {{ $primary }} 0%, {{ $secondary }} 100%);"></div>

      <div class="p-6 flex flex-wrap items-start justify-between gap-4">
        <div class="flex items-center gap-4">
          <div class="w-14 h-14 rounded-2xl flex items-center justify-center shrink-0"
               style="background: {{ $primary }}1a;">
            <x-core-ui::icon name="layout" class="w-7 h-7" style="color: {{ $primary }};" />
          </div>
          <div>
            <div class="flex items-center gap-2 mb-1">
              <h1 class="text-xl font-bold text-gray-900">
                {{ $themeName ?: 'قالب Canvas' }}
              </h1>
              <span class="bg-green-100 text-green-700 text-xs font-bold px-2.5 py-0.5 rounded-full">فعال</span>
            </div>
            @if($themeSlug)
              <p class="text-sm text-gray-400 font-mono">{{ $themeSlug }}</p>
            @endif
          </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
          <a href="{{ url('/') }}" target="_blank"
             class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-xl transition">
            <x-core-ui::icon name="eye" class="w-4 h-4" />
            پیش‌نمایش سایت
          </a>
          <a href="{{ route('admin.theme-design.canvas-export') }}"
             class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 py-2 rounded-xl transition">
            <x-core-ui::icon name="download" class="w-4 h-4" />
            صادر کردن JSON
          </a>
          <a href="{{ route('admin.theme-marketplace.my-licenses') }}"
             class="inline-flex items-center gap-2 bg-white hover:bg-gray-50 text-gray-600 text-sm font-medium px-4 py-2 rounded-xl border border-gray-200 transition">
            <x-core-ui::icon name="shield-check" class="w-4 h-4" />
            لایسنس‌های من
          </a>
          <form method="POST"
                action="{{ route('admin.theme-design.canvas-deactivate') }}"
                onsubmit="return confirm('قالب Canvas غیرفعال شود و سایت به حالت پیش‌فرض برگردد؟')">
            @csrf
            <button type="submit"
                    class="inline-flex items-center gap-2 bg-red-50 hover:bg-red-100 text-red-600 text-sm font-medium px-4 py-2 rounded-xl border border-red-200 transition">
              <x-core-ui::icon name="x-circle" class="w-4 h-4" />
              غیرفعال‌سازی
            </button>
          </form>
        </div>
      </div>

      {{-- Stats Row --}}
      <div class="border-t border-gray-100 px-6 py-4 grid grid-cols-3 gap-4 bg-gray-50 text-center text-sm">
        <div>
          <p class="text-2xl font-bold text-indigo-600">{{ $pageCount }}</p>
          <p class="text-gray-500 text-xs mt-0.5">صفحه</p>
        </div>
        <div>
          <p class="text-2xl font-bold text-indigo-600">{{ $hCount }}</p>
          <p class="text-gray-500 text-xs mt-0.5">بلاک هدر</p>
        </div>
        <div>
          <p class="text-2xl font-bold text-indigo-600">{{ $fCount }}</p>
          <p class="text-gray-500 text-xs mt-0.5">بلاک فوتر</p>
        </div>
      </div>
    </div>

    {{-- ─── Color Palette ────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
      <h2 class="text-base font-bold text-gray-800 mb-4 flex items-center gap-2">
        <x-core-ui::icon name="swatch" class="w-5 h-5 text-indigo-500" />
        پالت رنگ قالب
      </h2>
      <div class="flex flex-wrap gap-4">
        @foreach([
          ['label'=>'رنگ اصلی', 'color'=>$primary],
          ['label'=>'رنگ ثانویه', 'color'=>$secondary],
          ['label'=>'پس‌زمینه', 'color'=>$bg],
        ] as $swatch)
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl border border-gray-200 shadow-sm shrink-0"
                 style="background: {{ $swatch['color'] }};"></div>
            <div>
              <p class="text-xs text-gray-500">{{ $swatch['label'] }}</p>
              <p class="text-sm font-mono font-semibold text-gray-700">{{ $swatch['color'] }}</p>
            </div>
          </div>
        @endforeach

        @if(!empty($gs['font_family']) || !empty($gs['fonts']['body']))
          @php $font = $gs['font_family'] ?? $gs['fonts']['body'] ?? ''; @endphp
          <div class="flex items-center gap-3 mr-4 pr-4 border-r border-gray-200">
            <div class="w-10 h-10 rounded-xl border border-gray-200 bg-gray-50 flex items-center justify-center shrink-0">
              <span class="text-sm font-bold text-gray-500" style="font-family: {{ $font }}">Aa</span>
            </div>
            <div>
              <p class="text-xs text-gray-500">فونت</p>
              <p class="text-sm font-mono font-semibold text-gray-700">{{ Str::limit($font, 24) }}</p>
            </div>
          </div>
        @endif
      </div>
    </div>

    {{-- ─── Pages List ──────────────────────────────────────────────────── --}}
    @if($pageCount > 0)
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
          <x-core-ui::icon name="document-text" class="w-5 h-5 text-indigo-500" />
          <h2 class="text-base font-bold text-gray-800">صفحات قالب</h2>
          <span class="bg-indigo-100 text-indigo-700 text-xs font-bold px-2 py-0.5 rounded-full">{{ $pageCount }}</span>
        </div>
        <div class="divide-y divide-gray-100">
          @foreach($pages as $key => $page)
            @php
              $pageSlug  = $page['slug'] ?? (is_string($key) ? $key : '');
              $pageTitle = $page['title'] ?? ucfirst($pageSlug) ?: 'صفحه اصلی';
              $isHome    = !empty($page['is_home']) || $pageSlug === '' || $pageSlug === 'home';
              $blockCount = count($page['blocks'] ?? []);
              $previewUrl = $isHome ? url('/') : url('/page/' . $pageSlug);
            @endphp
            <div class="px-6 py-4 flex items-center justify-between gap-4 hover:bg-gray-50 transition">
              <div class="flex items-center gap-3 min-w-0">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
                     style="background: {{ $primary }}1a;">
                  @if($isHome)
                    <x-core-ui::icon name="home" class="w-4 h-4" style="color: {{ $primary }};" />
                  @else
                    <x-core-ui::icon name="document" class="w-4 h-4" style="color: {{ $primary }};" />
                  @endif
                </div>
                <div class="min-w-0">
                  <p class="font-semibold text-gray-800 text-sm truncate">{{ $pageTitle }}</p>
                  <p class="text-xs text-gray-400 mt-0.5 font-mono">
                    {{ $isHome ? '/' : '/page/' . $pageSlug }}
                    @if($isHome)
                      <span class="bg-blue-100 text-blue-600 text-xs px-1.5 py-0.5 rounded mr-1">صفحه اصلی</span>
                    @endif
                  </p>
                </div>
              </div>
              <div class="flex items-center gap-3 shrink-0">
                <span class="text-xs text-gray-400">{{ $blockCount }} بلاک</span>
                <a href="{{ $previewUrl }}" target="_blank"
                   class="inline-flex items-center gap-1.5 text-indigo-600 hover:text-indigo-800 text-sm font-medium transition">
                  <x-core-ui::icon name="external-link" class="w-4 h-4" />
                  پیش‌نمایش
                </a>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    @endif

    {{-- ─── Header / Footer Blocks ────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

      {{-- Header Blocks --}}
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <h3 class="text-sm font-bold text-gray-700 flex items-center gap-2 mb-3">
          <x-core-ui::icon name="chevron-up" class="w-4 h-4 text-indigo-400" />
          بلاک‌های هدر
          <span class="text-xs font-normal text-gray-400">({{ $hCount }} بلاک)</span>
        </h3>
        @if($hCount > 0)
          <div class="space-y-2">
            @foreach($headerBks as $hb)
              <div class="flex items-center gap-2 bg-gray-50 rounded-lg px-3 py-2">
                <span class="w-2 h-2 rounded-full bg-indigo-400 shrink-0"></span>
                <span class="text-xs text-gray-600 font-mono">{{ $hb['type'] ?? 'block' }}</span>
              </div>
            @endforeach
          </div>
        @else
          <p class="text-xs text-gray-400">بدون بلاک هدر — هدر پیش‌فرض نمایش داده می‌شود</p>
        @endif
      </div>

      {{-- Footer Blocks --}}
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <h3 class="text-sm font-bold text-gray-700 flex items-center gap-2 mb-3">
          <x-core-ui::icon name="chevron-down" class="w-4 h-4 text-indigo-400" />
          بلاک‌های فوتر
          <span class="text-xs font-normal text-gray-400">({{ $fCount }} بلاک)</span>
        </h3>
        @if($fCount > 0)
          <div class="space-y-2">
            @foreach($footerBks as $fb)
              <div class="flex items-center gap-2 bg-gray-50 rounded-lg px-3 py-2">
                <span class="w-2 h-2 rounded-full bg-purple-400 shrink-0"></span>
                <span class="text-xs text-gray-600 font-mono">{{ $fb['type'] ?? 'block' }}</span>
              </div>
            @endforeach
          </div>
        @else
          <p class="text-xs text-gray-400">بدون بلاک فوتر — فوتر پیش‌فرض نمایش داده می‌شود</p>
        @endif
      </div>
    </div>

    {{-- ─── Quick Actions ───────────────────────────────────────────────────── --}}
    <div class="bg-linear-to-l from-indigo-600 to-indigo-700 rounded-2xl p-6 text-white">
      <h3 class="font-bold text-lg mb-1">اقدامات سریع</h3>
      <p class="text-indigo-200 text-sm mb-5">سایت شما در حال حاضر با قالب Canvas فعال است.</p>
      <div class="flex flex-wrap gap-3">
        <a href="{{ url('/') }}" target="_blank"
           class="inline-flex items-center gap-2 bg-white text-indigo-700 hover:bg-indigo-50 text-sm font-semibold px-4 py-2 rounded-xl transition">
          <x-core-ui::icon name="eye" class="w-4 h-4" />
          مشاهده صفحه اصلی
        </a>
        <a href="{{ route('admin.theme-design.canvas-export') }}"
           class="inline-flex items-center gap-2 bg-indigo-500 hover:bg-indigo-400 text-white text-sm font-medium px-4 py-2 rounded-xl transition">
          <x-core-ui::icon name="download" class="w-4 h-4" />
          صادر کردن JSON
        </a>
        <a href="{{ route('admin.theme-marketplace.index') }}"
           class="inline-flex items-center gap-2 bg-indigo-500 hover:bg-indigo-400 text-white text-sm font-medium px-4 py-2 rounded-xl transition">
          <x-core-ui::icon name="store" class="w-4 h-4" />
          مارکت‌پلیس قالب‌ها
        </a>
        <a href="{{ route('admin.theme-design.index') }}"
           class="inline-flex items-center gap-2 bg-indigo-500 hover:bg-indigo-400 text-white text-sm font-medium px-4 py-2 rounded-xl transition">
          <x-core-ui::icon name="color-swatch" class="w-4 h-4" />
          تنظیمات تم
        </a>
      </div>
    </div>

    {{-- ─── Replace Theme via Import ────────────────────────────────────────── --}}
    <div x-data="{ open: false }">
      <button x-on:click="open = !open"
              class="w-full text-right flex items-center justify-between px-5 py-3 bg-white rounded-xl border border-gray-200 hover:border-gray-300 transition text-sm text-gray-600 font-medium">
        <span class="flex items-center gap-2">
          <x-core-ui::icon name="upload" class="w-4 h-4 text-gray-400" />
          جایگزین کردن قالب با فایل JSON جدید
        </span>
        <x-core-ui::icon name="chevron-down" class="w-4 h-4 text-gray-400 transition" x-bind:class="open ? 'rotate-180' : ''" />
      </button>
      <div x-show="open" x-collapse class="mt-3">
        @include('admin.theme-design.canvas-import-panel')
      </div>
    </div>

  @endif

</div>
@endsection
