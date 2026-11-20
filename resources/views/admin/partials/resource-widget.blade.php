@php
    // 2026-11-19: thresholds, measurement state and the soft-quota grace
    // period come from the platform's summary (App\Support\ResourceSummary);
    // nothing is assumed here. No banner on an unmeasured figure.
    $summary = \App\Support\ResourceSummary::fromSettings();
    $level = $summary?->level();
    if ($summary === null || $level === null) return;

    $pct       = (float) $summary->percent();
    $used      = $summary->usedBytes();
    $limit     = $summary->hardLimitBytes();
    $softLimit = $summary->softLimitBytes();
    $db        = (int) $summary->get('db_bytes', 0);
    $ramMb     = (int) $summary->get('memory_limit_mb', 1024);
    $cpuCores  = (float) $summary->get('cpu_cores_limit', 1.0);
    $graceEnds = $summary->graceEndsAt();
    $graceExpired = $summary->graceExpired();
    $isSoftExceeded = $summary->softExceeded();
    $bannerLevel = $level === 'soft' ? ($graceExpired ? 'critical' : 'warning') : $level;
@endphp

<div class="rounded-xl border p-4 mb-4
    {{ $bannerLevel === 'critical' ? 'bg-red-50 border-red-200' : 'bg-yellow-50 border-yellow-200' }}"
    dir="rtl">
  <div class="flex items-start gap-3">
    <div class="mt-0.5">
      @if($bannerLevel === 'critical')
        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      @else
        <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      @endif
    </div>
    <div class="flex-1">
      <div class="flex items-center justify-between flex-wrap gap-2">
        <p class="text-sm font-bold {{ $bannerLevel === 'critical' ? 'text-red-800' : 'text-yellow-800' }}">
          @if($level === 'critical')
            🚨 بحران فضا: مصرف دیسک سایت شما به {{ num_fa($pct, 1) }}٪ سقف مجاز رسید (آستانه بحرانی {{ num_fa($summary->criticalPercent()) }}٪). اقدام فوری لازم است.
          @elseif($level === 'warning')
            ⚠️ هشدار فضا: مصرف دیسک سایت به {{ num_fa($pct, 1) }}٪ ظرفیت مجاز رسیده است (آستانه هشدار {{ num_fa($summary->warningPercent()) }}٪).
          @elseif($graceExpired)
            🚨 مهلت {{ num_fa($summary->graceDays()) }} روزه سهمیه نرم به پایان رسیده و مصرف هنوز بالای سقف نرم است؛ لطفاً فضا را آزاد یا ارتقا دهید.
          @else
            ⚠️ مصرف فایل‌ها از سقف نرم (Soft Quota) گذشته است؛ مهلت مدیریت فضا{{ $graceEnds ? ' تا ' . date_fa($graceEnds) : '' }} فعال است.
          @endif
        </p>
        <div class="flex gap-2 items-center text-xs">
          <span class="bg-white/80 px-2 py-0.5 rounded border border-gray-200 text-gray-700">RAM: {{ $ramMb }} MB</span>
          <span class="bg-white/80 px-2 py-0.5 rounded border border-gray-200 text-gray-700">CPU: {{ $cpuCores }} هسته</span>
        </div>
      </div>

      {{-- Progress bar --}}
      <div class="mt-2">
        <div class="flex justify-between text-xs mb-1 {{ $bannerLevel === 'critical' ? 'text-red-700' : 'text-yellow-700' }}">
          <span>{{ $pct }}٪ از سقف سخت مصرف شده</span>
          <span>{{ \App\Helpers\NumberHelper::formatBytes($used) }} از {{ \App\Helpers\NumberHelper::formatBytes($limit) }} (دیسک نرم: {{ \App\Helpers\NumberHelper::formatBytes($softLimit) }})</span>
        </div>
        <div class="w-full h-2.5 bg-white bg-opacity-70 rounded-full overflow-hidden">
          <div class="h-full rounded-full {{ $bannerLevel === 'critical' ? 'bg-red-500' : 'bg-yellow-500' }}"
               style="width: {{ min(100, $pct) }}%"></div>
        </div>
      </div>

      <div class="flex items-center justify-between mt-2.5 flex-wrap gap-2">
        <p class="text-xs {{ $bannerLevel === 'critical' ? 'text-red-700' : 'text-yellow-700' }}">
          سایت شما بدون قطعی به فعالیت ادامه می‌دهد؛ جهت حفظ پایداری و ارتقای فضا می‌توانید به صفحه منابع مراجعه کنید.
        </p>
        <a href="/account/resources" class="text-xs font-bold underline {{ $bannerLevel === 'critical' ? 'text-red-800' : 'text-yellow-900' }}">
          مشاهده جزئیات منابع و ارتقا ←
        </a>
      </div>
    </div>
  </div>
</div>
