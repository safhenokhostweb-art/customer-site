@php
    $summary = \App\Models\Setting::get('site_resource_summary', null);
    $data = $summary ? json_decode($summary, true) : null;
    if (!$data || !isset($data['storage_pct'])) return;

    $pct       = (float) $data['storage_pct'];
    $used      = (int) ($data['storage_bytes'] ?? 0);
    $limit     = (int) ($data['storage_limit'] ?? 1073741824);
    $softLimit = (int) ($data['disk_soft_limit_bytes'] ?? ($limit * 0.70));
    $db        = (int) ($data['db_bytes'] ?? 0);
    $ramMb     = (int) ($data['memory_limit_mb'] ?? 1024);
    $cpuCores  = (float) ($data['cpu_cores_limit'] ?? 1.0);

    $isSoftExceeded = $softLimit > 0 && $used >= $softLimit;

    // Per user governance: Alert banner displays at >= 80% or when soft quota exceeded
    if ($pct < 80 && !$isSoftExceeded) return;

    $level = $pct >= 95 ? 'critical' : 'warning';
@endphp

<div class="rounded-xl border p-4 mb-4
    {{ $level === 'critical' ? 'bg-red-50 border-red-200' : 'bg-yellow-50 border-yellow-200' }}"
    dir="rtl">
  <div class="flex items-start gap-3">
    <div class="mt-0.5">
      @if($level === 'critical')
        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      @else
        <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      @endif
    </div>
    <div class="flex-1">
      <div class="flex items-center justify-between flex-wrap gap-2">
        <p class="text-sm font-bold {{ $level === 'critical' ? 'text-red-800' : 'text-yellow-800' }}">
          @if($level === 'critical')
            🚨 بحران فضا: ظرفیت دیسک سایت شما به ۹۵٪ رسید! اقدام فوری لازم است.
          @elseif($pct >= 80)
            ⚠️ هشدار فضا: مصرف دیسک سایت به ۸۰٪ ظرفیت مجاز رسیده است.
          @else
            ⚠️ هشدار سهمیه نرم (Soft Quota): مصرف فایل‌ها به آستانه ۷۰٪ رسید (مهلت ۷ روزه برای مدیریت فضا فعال است).
          @endif
        </p>
        <div class="flex gap-2 items-center text-xs">
          <span class="bg-white/80 px-2 py-0.5 rounded border border-gray-200 text-gray-700">RAM: {{ $ramMb }} MB</span>
          <span class="bg-white/80 px-2 py-0.5 rounded border border-gray-200 text-gray-700">CPU: {{ $cpuCores }} هسته</span>
        </div>
      </div>

      {{-- Progress bar --}}
      <div class="mt-2">
        <div class="flex justify-between text-xs mb-1 {{ $level === 'critical' ? 'text-red-700' : 'text-yellow-700' }}">
          <span>{{ $pct }}٪ از سقف سخت مصرف شده</span>
          <span>{{ \App\Helpers\NumberHelper::formatBytes($used) }} از {{ \App\Helpers\NumberHelper::formatBytes($limit) }} (دیسک نرم: {{ \App\Helpers\NumberHelper::formatBytes($softLimit) }})</span>
        </div>
        <div class="w-full h-2.5 bg-white bg-opacity-70 rounded-full overflow-hidden">
          <div class="h-full rounded-full {{ $level === 'critical' ? 'bg-red-500' : 'bg-yellow-500' }}"
               style="width: {{ min(100, $pct) }}%"></div>
        </div>
      </div>

      <div class="flex items-center justify-between mt-2.5 flex-wrap gap-2">
        <p class="text-xs {{ $level === 'critical' ? 'text-red-700' : 'text-yellow-700' }}">
          سایت شما بدون قطعی به فعالیت ادامه می‌دهد؛ جهت حفظ پایداری و ارتقای فضا می‌توانید به صفحه منابع مراجعه کنید.
        </p>
        <a href="/account/resources" class="text-xs font-bold underline {{ $level === 'critical' ? 'text-red-800' : 'text-yellow-900' }}">
          مشاهده جزئیات منابع و ارتقا ←
        </a>
      </div>
    </div>
  </div>
</div>
