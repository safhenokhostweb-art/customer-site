@extends('admin.layout')

@section('title', 'مدیریت و مصرف منابع سایت')

@section('content')
<div class="space-y-6" dir="rtl">

  <div>
    <h1 class="text-2xl font-bold text-gray-900">حاکمیت و مصرف منابع سایت</h1>
    <p class="text-sm text-gray-500 mt-1">وضعیت برخط سهمیه‌های سخت‌افزاری: رم (RAM)، دیسک سخت، دیسک نرم (کش و موقت) و پردازنده</p>
  </div>

  @php
    // 2026-11-19: interpreted by App\Support\ResourceSummary (platform thresholds,
    // measured/unmeasured state, real soft-quota grace dates).
    $rs        = \App\Support\ResourceSummary::fromSettings();
    $summary   = \App\Models\Setting::get('site_resource_summary', null);
    $data      = $summary ? json_decode($summary, true) : null;
    $measured  = $rs?->isMeasured() ?? false;
    $level     = $rs?->level();
    $graceEnds = $rs?->graceEndsAt();
    $graceExpired = $rs?->graceExpired() ?? false;
    $pct       = $rs?->percent() ?? 0;
    $used      = $data ? (int)   ($data['storage_bytes'] ?? 0) : 0;
    $limit     = $data ? (int)   ($data['storage_limit'] ?? 1073741824) : 1073741824;
    $softLimit = $data ? (int)   ($data['disk_soft_limit_bytes'] ?? ($limit * 0.70)) : (int) ($limit * 0.70);
    $softPct   = $softLimit > 0 ? min(100, round(($used / $softLimit) * 100, 1)) : 0;
    $db        = $data ? (int)   ($data['db_bytes'] ?? 0) : 0;
    $archive   = $data ? (int)   ($data['archive_bytes'] ?? 0) : 0;
    $ramMb     = $data ? (int)   ($data['memory_limit_mb'] ?? 1024) : 1024;
    $cpuCores  = $data ? (float) ($data['cpu_cores_limit'] ?? 1.0) : 1.0;
    $calcAt    = $data['calculated_at'] ?? null;

    $isSoftExceeded = $rs?->softExceeded() ?? false;
  @endphp

  @if(!$data)
    <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-4 text-sm text-amber-800">
      اطلاعات منابع هنوز محاسبه نشده است. پس از اولین اجرای سرویس مانیتورینگ در دسترس قرار می‌گیرد.
    </div>
  @else

    @if(!$measured)
      <div class="bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm text-gray-700" data-resource-unmeasured>
        مصرف واقعی دیسک سایت هنوز اندازه‌گیری نشده است؛ ارقام زیر پس از اولین اندازه‌گیری به‌روز می‌شوند.
      </div>
    @elseif($rs?->isStale())
      <div class="bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm text-gray-700" data-resource-stale>
        آخرین اندازه‌گیری مصرف دیسک {{ $rs->measuredAt() ? date_fa($rs->measuredAt()) : '' }} انجام شده و ممکن است به‌روز نباشد.
      </div>
    @endif

    {{-- Alert banner --}}
    @if($level === 'critical')
      <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 flex items-center gap-3">
        <svg class="w-5 h-5 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        <p class="text-sm text-red-800"><strong>بحران فضا!</strong> ظرفیت دیسک سخت سایت شما به {{ $pct }}٪ رسیده است. جهت جلوگیری از عدم امکان آپلود سریعاً ارتقا دهید.</p>
      </div>
    @elseif($level === 'warning')
      <div class="bg-yellow-50 border border-yellow-200 rounded-xl px-4 py-3 flex items-center gap-3">
        <svg class="w-5 h-5 text-yellow-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        <p class="text-sm text-yellow-800"><strong>هشدار مصرف فضا:</strong> {{ $pct }}٪ از کل سقف دیسک سخت مصرف شده است.</p>
      </div>
    @elseif($level === 'soft' && $graceExpired)
      <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 flex items-center gap-3" data-resource-grace="expired">
        <svg class="w-5 h-5 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <p class="text-sm text-red-800"><strong>پایان مهلت سقف نرم:</strong> مهلت {{ num_fa($rs->graceDays()) }} روزه مدیریت فضا{{ $graceEnds ? ' (' . date_fa($graceEnds) . ')' : '' }} به پایان رسیده و مصرف هنوز بالای سقف نرم است. لطفاً فضا را آزاد یا ارتقا دهید.</p>
      </div>
    @elseif($level === 'soft')
      <div class="bg-blue-50 border border-blue-200 rounded-xl px-4 py-3 flex items-center gap-3" data-resource-grace="active">
        <svg class="w-5 h-5 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <p class="text-sm text-blue-800"><strong>سقف نرم (Soft Quota):</strong> مصرف فایل‌ها از سقف نرم گذشته است؛ مهلت مدیریت فضا{{ $graceEnds ? ' تا ' . date_fa($graceEnds) : '' }} فعال است (سایت بدون قطعی فعال است).</p>
      </div>
    @endif

    {{-- 4 Main Resource Cards: Hard Disk, Soft Disk, RAM, CPU --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

      {{-- 1. Hard Disk (Storage) --}}
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-center gap-3 mb-3">
          <div class="p-2.5 {{ $pct >= 95 ? 'bg-red-50 text-red-600' : ($pct >= 80 ? 'bg-yellow-50 text-yellow-600' : 'bg-indigo-50 text-indigo-600') }} rounded-xl">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/></svg>
          </div>
          <div>
            <p class="text-xs text-gray-500 font-semibold">دیسک سخت (سقف هارد)</p>
            <p class="text-lg font-bold {{ $pct >= 95 ? 'text-red-700' : ($pct >= 80 ? 'text-yellow-700' : 'text-gray-900') }}">
              {{ \App\Helpers\NumberHelper::formatBytes($used) }}
            </p>
          </div>
        </div>
        <div class="flex justify-between text-xs text-gray-500 mb-1">
          <span>{{ $pct }}٪</span>
          <span>سقف: {{ \App\Helpers\NumberHelper::formatBytes($limit) }}</span>
        </div>
        <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
          <div class="{{ $pct >= 95 ? 'bg-red-500' : ($pct >= 80 ? 'bg-yellow-500' : 'bg-indigo-500') }} h-full rounded-full"
               style="width: {{ min(100, $pct) }}%"></div>
        </div>
        <p class="text-xs text-gray-400 mt-2">{{ \App\Helpers\NumberHelper::formatBytes(max(0, $limit - $used)) }} آزاد</p>
      </div>

      {{-- 2. Soft Disk (Cache / Temporary) --}}
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-center gap-3 mb-3">
          <div class="p-2.5 {{ $isSoftExceeded ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600' }} rounded-xl">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
          </div>
          <div>
            <p class="text-xs text-gray-500 font-semibold">دیسک نرم (سهمیه ۷۰٪)</p>
            <p class="text-lg font-bold text-gray-900">
              {{ \App\Helpers\NumberHelper::formatBytes($softLimit) }}
            </p>
          </div>
        </div>
        <div class="flex justify-between text-xs text-gray-500 mb-1">
          <span>{{ $softPct }}٪ پر شده</span>
          <span>{{ $isSoftExceeded ? 'مهلت ۷ روزه' : 'وضعیت عادی' }}</span>
        </div>
        <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
          <div class="{{ $isSoftExceeded ? 'bg-amber-500' : 'bg-emerald-500' }} h-full rounded-full"
               style="width: {{ min(100, $softPct) }}%"></div>
        </div>
        <p class="text-xs text-gray-400 mt-2">فضای کش و بارگذاری با مهلت ۷ روزه</p>
      </div>

      {{-- 3. RAM (Memory) --}}
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-center gap-3 mb-3">
          <div class="p-2.5 bg-purple-50 text-purple-600 rounded-xl">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
          </div>
          <div>
            <p class="text-xs text-gray-500 font-semibold">حافظه رم (RAM)</p>
            <p class="text-lg font-bold text-gray-900">
              {{ $ramMb >= 1024 ? round($ramMb / 1024, 1) . ' GB' : $ramMb . ' MB' }}
            </p>
          </div>
        </div>
        <div class="text-xs text-gray-500 space-y-1">
          <div class="flex justify-between">
            <span>سقف تخصیصی:</span>
            <span class="font-semibold text-gray-800">{{ $ramMb }} مگابایت</span>
          </div>
          <div class="flex justify-between">
            <span>مدل اشتراک:</span>
            <span class="text-purple-700 font-medium">Shared Pool اختصاصی</span>
          </div>
        </div>
      </div>

      {{-- 4. CPU (Processor) --}}
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-center gap-3 mb-3">
          <div class="p-2.5 bg-blue-50 text-blue-600 rounded-xl">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
          </div>
          <div>
            <p class="text-xs text-gray-500 font-semibold">پردازنده (CPU)</p>
            <p class="text-lg font-bold text-gray-900">
              {{ $cpuCores }} هسته
            </p>
          </div>
        </div>
        <div class="text-xs text-gray-500 space-y-1">
          <div class="flex justify-between">
            <span>سهم پردازش:</span>
            <span class="font-semibold text-gray-800">{{ (int) ($cpuCores * 25) }}٪ از کلاستر</span>
          </div>
          <div class="flex justify-between">
            <span>وضعیت:</span>
            <span class="text-blue-700 font-medium">بهینه‌شده و پایدار</span>
          </div>
        </div>
      </div>

    </div>

    {{-- Secondary row: Database & Archive --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-center gap-3 mb-2">
          <div class="p-2 bg-blue-50 rounded-lg text-blue-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
          </div>
          <div>
            <h3 class="font-bold text-gray-800 text-sm">پایگاه داده (MySQL)</h3>
            <p class="text-xs text-gray-400">اطلاعات، پست‌ها، سفارشات و تنظیمات</p>
          </div>
          <span class="mr-auto font-bold text-gray-800 text-sm">{{ \App\Helpers\NumberHelper::formatBytes($db) }}</span>
        </div>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-center gap-3 mb-2">
          <div class="p-2 bg-amber-50 rounded-lg text-amber-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
          </div>
          <div>
            <h3 class="font-bold text-gray-800 text-sm">پشتیبان و آرشیو</h3>
            <p class="text-xs text-gray-400">بسته‌های فشرده ذخیره‌شده</p>
          </div>
          <span class="mr-auto font-bold text-gray-800 text-sm">{{ $archive > 0 ? \App\Helpers\NumberHelper::formatBytes($archive) : 'بدون آرشیو' }}</span>
        </div>
      </div>
    </div>

    <div class="text-left text-xs text-gray-400 pt-1">
      آخرین محاسبه و پایش برخط: {{ $calcAt ? \App\Helpers\JalaliHelper::toJalali($calcAt) : 'هم‌اکنون' }}
    </div>

  @endif

</div>
@endsection
