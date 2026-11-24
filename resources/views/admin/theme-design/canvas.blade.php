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
        <form method="POST" action="{{ route('admin.theme-design.canvas.create-blank') }}" class="inline">
          @csrf
          <button type="submit"
             class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-5 py-2.5 rounded-xl shadow-sm transition hover:shadow-indigo-200 hover:shadow-md ring-1 ring-indigo-500">
            <x-core-ui::icon name="plus" class="w-4 h-4" />
            شروع با بوم خالی (طراحی قالب از صفر)
          </button>
        </form>
        <a href="{{ route('admin.theme-marketplace.index') }}"
           class="inline-flex items-center gap-2 bg-white hover:bg-gray-50 text-gray-700 text-sm font-medium px-5 py-2.5 rounded-xl border border-gray-200 transition">
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
    <div x-data="{ historyOpen: false }">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
      {{-- Color Bar --}}
      <div class="h-2" style="background: linear-gradient(90deg, {{ $primary }} 0%, {{ $secondary }} 100%);"></div>

      {{-- Top Header: Brand Identity & Primary Actions --}}
      <div class="p-6 flex flex-col lg:flex-row lg:items-center justify-between gap-5 border-b border-gray-100">
        {{-- Theme Identity --}}
        <div class="flex items-center gap-4 min-w-0">
          <div class="w-14 h-14 rounded-2xl flex items-center justify-center shrink-0 shadow-sm border border-gray-100"
               style="background: {{ $primary }}14;">
            <x-core-ui::icon name="layout" class="w-7 h-7" style="color: {{ $primary }};" />
          </div>
          <div class="min-w-0">
            <div class="flex items-center gap-2.5 flex-wrap mb-1">
              <h1 class="text-xl font-bold text-gray-900 truncate">
                {{ $themeName ?: 'قالب Canvas' }}
              </h1>
              <span class="inline-flex items-center gap-1.5 bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20 text-xs font-semibold px-2.5 py-0.5 rounded-full">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                قالب فعال
              </span>
            </div>
            @if($themeSlug)
              <div class="flex items-center gap-1.5 text-xs text-gray-500 font-mono">
                <span class="text-gray-400 font-sans">شناسه سیستم:</span>
                <span class="bg-gray-100 text-gray-700 px-2 py-0.5 rounded-md border border-gray-200/60 font-semibold">{{ $themeSlug }}</span>
              </div>
            @endif
          </div>
        </div>

        {{-- Primary Action Buttons --}}
        <div class="flex items-center gap-3 shrink-0 flex-wrap sm:flex-nowrap">
          <a href="{{ url('/') }}" target="_blank"
             class="inline-flex items-center justify-center gap-2 bg-white hover:bg-gray-50 text-gray-700 text-sm font-medium px-4 py-2.5 rounded-xl border border-gray-200 shadow-sm transition hover:border-gray-300">
            <x-core-ui::icon name="eye" class="w-4 h-4 text-gray-500" />
            <span>پیش‌نمایش سایت</span>
          </a>
          <a href="{{ route('admin.theme-design.canvas-edit') }}"
             class="inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-5 py-2.5 rounded-xl shadow-sm transition hover:shadow-indigo-200 hover:shadow-md ring-1 ring-indigo-500">
            <x-core-ui::icon name="pencil" class="w-4 h-4" />
            <span>ویرایش ویجت‌ها (Canvas)</span>
          </a>
        </div>
      </div>

      {{-- Middle Metrics Grid --}}
      <div class="p-6 bg-gradient-to-b from-gray-50/70 to-white border-b border-gray-100">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          {{-- Stat: Pages --}}
          <div class="bg-white rounded-xl border border-gray-200/80 p-4 shadow-[0_1px_3px_rgba(0,0,0,0.03)] flex items-center justify-between transition hover:border-indigo-200">
            <div>
              <p class="text-xs font-medium text-gray-500">صفحات طراحی شده</p>
              <p class="text-2xl font-bold text-gray-900 mt-1 font-mono">{{ $pageCount }}</p>
            </div>
            <div class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
              <x-core-ui::icon name="document" class="w-5 h-5" />
            </div>
          </div>

          {{-- Stat: Header Blocks --}}
          <div class="bg-white rounded-xl border border-gray-200/80 p-4 shadow-[0_1px_3px_rgba(0,0,0,0.03)] flex items-center justify-between transition hover:border-indigo-200">
            <div>
              <p class="text-xs font-medium text-gray-500">بلاک‌های هدر فعال</p>
              <p class="text-2xl font-bold text-gray-900 mt-1 font-mono">{{ $hCount }}</p>
            </div>
            <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
              <x-core-ui::icon name="chevron-up" class="w-5 h-5" />
            </div>
          </div>

          {{-- Stat: Footer Blocks --}}
          <div class="bg-white rounded-xl border border-gray-200/80 p-4 shadow-[0_1px_3px_rgba(0,0,0,0.03)] flex items-center justify-between transition hover:border-indigo-200">
            <div>
              <p class="text-xs font-medium text-gray-500">بلاک‌های فوتر فعال</p>
              <p class="text-2xl font-bold text-gray-900 mt-1 font-mono">{{ $fCount }}</p>
            </div>
            <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
              <x-core-ui::icon name="chevron-down" class="w-5 h-5" />
            </div>
          </div>
        </div>
      </div>

      {{-- Bottom Toolbar: Secondary Tools & Destructive Action --}}
      <div class="px-6 py-3.5 bg-gray-50/90 flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div class="flex items-center gap-2 flex-wrap">
          <button type="button" @click="historyOpen = !historyOpen"
                  :class="historyOpen ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'"
                  class="inline-flex items-center gap-2 text-xs font-semibold px-3.5 py-2 rounded-xl border shadow-xs transition">
            <x-core-ui::icon name="arrow-uturn-left" class="w-3.5 h-3.5 text-indigo-500" />
            <span>تاریخچه و بازیابی نسخه‌ها</span>
            <span class="text-[10px] font-normal text-gray-400" x-show="!historyOpen">(مشاهده)</span>
            <span class="text-[10px] font-normal text-indigo-500" x-show="historyOpen">(بستن)</span>
          </button>

          <a href="{{ route('admin.theme-design.canvas-export') }}"
             class="inline-flex items-center gap-2 bg-white hover:bg-gray-50 text-gray-700 text-xs font-semibold px-3.5 py-2 rounded-xl border border-gray-200 shadow-xs transition hover:text-emerald-700">
            <x-core-ui::icon name="download" class="w-3.5 h-3.5 text-emerald-600" />
            <span>صادر کردن JSON</span>
          </a>

          <a href="{{ route('admin.theme-marketplace.my-licenses') }}"
             class="inline-flex items-center gap-2 bg-white hover:bg-gray-50 text-gray-700 text-xs font-semibold px-3.5 py-2 rounded-xl border border-gray-200 shadow-xs transition hover:text-indigo-700">
            <x-core-ui::icon name="shield-check" class="w-3.5 h-3.5 text-indigo-600" />
            <span>لایسنس‌های من</span>
          </a>
        </div>

        <div>
          <form method="POST"
                action="{{ route('admin.theme-design.canvas-deactivate') }}"
                onsubmit="return confirm('قالب Canvas غیرفعال شود و سایت به حالت پیش‌فرض برگردد؟')">
            @csrf
            <button type="submit"
                    class="inline-flex items-center gap-1.5 text-red-600 hover:text-red-700 hover:bg-red-50 text-xs font-medium px-3 py-1.5 rounded-lg transition border border-transparent hover:border-red-200">
              <x-core-ui::icon name="x-circle" class="w-3.5 h-3.5" />
              <span>غیرفعال‌سازی قالب</span>
            </button>
          </form>
        </div>
      </div>
    </div>

    {{-- ─── Version History / Permanent Rollback (canonical, append-only) ──── --}}
    <div x-show="historyOpen" x-collapse
         x-data="{
            revisions: [],
            loading: true,
            async load() {
                this.loading = true;
                const res = await fetch('{{ route('admin.theme-design.canvas-instance-revisions') }}', { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                this.revisions = data.revisions ?? [];
                this.loading = false;
            },
            async restore(id) {
                if (!confirm('نسخه فعلی به‌طور خودکار در تاریخچه ثبت می‌شود. این نسخه انتخابی جایگزین قالب فعال شود؟')) return;
                const res = await fetch('{{ url('admin/theme-design/canvas/instance-revisions') }}/' + id + '/rollback', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (data.success) { location.reload(); } else { alert(data.error || 'خطا در بازیابی نسخه.'); }
            }
         }"
         x-init="load()"
         class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
        <x-core-ui::icon name="clock" class="w-5 h-5 text-indigo-500" />
        <h2 class="text-base font-bold text-gray-800">تاریخچه نسخه‌های قالب</h2>
        <span class="text-xs font-normal text-gray-400">— تمام نسخه‌ها برای همیشه نگه‌داری می‌شوند؛ بازیابی، نسخه جدیدی به تاریخچه اضافه می‌کند</span>
      </div>
      <div class="divide-y divide-gray-100">
        <template x-if="loading">
          <p class="px-6 py-6 text-sm text-gray-400 text-center">در حال بارگذاری...</p>
        </template>
        <template x-if="!loading && revisions.length === 0">
          <p class="px-6 py-6 text-sm text-gray-400 text-center">هنوز تاریخچه‌ای ثبت نشده است.</p>
        </template>
        <template x-for="rev in revisions" :key="rev.id">
          <div class="px-6 py-4 flex items-center justify-between gap-4 hover:bg-gray-50 transition">
            <div class="min-w-0">
              <p class="font-semibold text-gray-800 text-sm">
                نسخه #<span x-text="rev.revision_number"></span>
                <span x-show="rev.is_active" class="bg-green-100 text-green-700 text-xs font-bold px-2 py-0.5 rounded-full mr-1">فعلی</span>
              </p>
              <p class="text-xs text-gray-400 mt-0.5" x-text="rev.type + ' — ' + (rev.date || '') + (rev.time_ago ? ' (' + rev.time_ago + ')' : '')"></p>
            </div>
            <button type="button" x-show="!rev.is_active" @click="restore(rev.id)"
                    class="inline-flex items-center gap-1.5 text-indigo-600 hover:text-indigo-800 text-sm font-medium transition shrink-0">
              بازیابی این نسخه
            </button>
          </div>
        </template>
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
                <a href="{{ route('admin.theme-design.canvas-edit', ['page' => $pageSlug ?: 'home']) }}"
                   class="inline-flex items-center gap-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold px-2.5 py-1.5 rounded-lg transition">
                  <x-core-ui::icon name="pencil" class="w-3.5 h-3.5" />
                  ویرایش با Canvas
                </a>
                <a href="{{ $previewUrl }}" target="_blank"
                   class="inline-flex items-center gap-1.5 text-gray-600 hover:text-indigo-800 text-sm font-medium transition">
                  <x-core-ui::icon name="external-link" class="w-4 h-4" />
                  پیش‌نمایش
                </a>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    @endif

    {{-- ─── Site Database Pages (Pages Module) ────────────────────────── --}}
    @if(isset($sitePages) && count($sitePages) > 0)
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between gap-2">
          <div class="flex items-center gap-2">
            <x-core-ui::icon name="document-text" class="w-5 h-5 text-indigo-500" />
            <h2 class="text-base font-bold text-gray-800">صفحات ماژول صفحات (Pages)</h2>
            <span class="bg-indigo-100 text-indigo-700 text-xs font-bold px-2 py-0.5 rounded-full">{{ count($sitePages) }}</span>
          </div>
          <a href="{{ route('admin.pages.create') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition">
            + ایجاد صفحه جدید
          </a>
        </div>
        <div class="divide-y divide-gray-100">
          @foreach($sitePages as $sp)
            @php
              $spBlocks = $sp->builder_content_v2['blocks']
                  ?? $sp->builder_content_v2['sections']
                  ?? $sp->builder_content['blocks']
                  ?? $sp->builder_content['sections']
                  ?? [];
              $spBlockCount = is_array($spBlocks) ? count($spBlocks) : 0;
            @endphp
            <div class="px-6 py-4 flex items-center justify-between gap-4 hover:bg-gray-50 transition">
              <div class="flex items-center gap-3 min-w-0">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 bg-gray-100 text-gray-600">
                  <x-core-ui::icon name="document" class="w-4 h-4" />
                </div>
                <div class="min-w-0">
                  <p class="font-semibold text-gray-800 text-sm truncate">{{ $sp->title }}</p>
                  <p class="text-xs text-gray-400 mt-0.5 font-mono">
                    /page/{{ $sp->slug }}
                    @if($sp->is_published)
                      <span class="bg-emerald-50 text-emerald-700 text-[10px] px-1.5 py-0.5 rounded mr-1">منتشر شده</span>
                    @else
                      <span class="bg-gray-100 text-gray-600 text-[10px] px-1.5 py-0.5 rounded mr-1">پیش‌نویس</span>
                    @endif
                  </p>
                </div>
              </div>
              <div class="flex items-center gap-2 shrink-0">
                <span class="text-xs text-gray-400 ml-1">{{ $spBlockCount }} بلاک</span>
                <a href="{{ route('admin.theme-design.canvas-edit', ['page' => $sp->slug]) }}"
                   class="inline-flex items-center gap-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold px-2.5 py-1.5 rounded-lg transition">
                  <x-core-ui::icon name="pencil" class="w-3.5 h-3.5" />
                  ویرایش با Canvas
                </a>
                <a href="{{ route('admin.pages.edit', $sp) }}"
                   class="inline-flex items-center gap-1 bg-white hover:bg-gray-50 border border-gray-200 text-gray-700 text-xs font-medium px-2.5 py-1.5 rounded-lg transition" title="تنظیمات صفحه">
                  <x-core-ui::icon name="adjustments" class="w-3.5 h-3.5" />
                  تنظیمات
                </a>
                <a href="{{ url('/page/' . $sp->slug) }}" target="_blank"
                   class="inline-flex items-center gap-1 text-gray-400 hover:text-indigo-600 text-xs font-medium p-1 transition" title="مشاهده در سایت">
                  <x-core-ui::icon name="external-link" class="w-3.5 h-3.5" />
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
