@php
    $summary = \App\Models\Setting::get('site_resource_summary', null);
    $data = $summary ? json_decode($summary, true) : null;
    if (!$data || !isset($data['storage_pct'])) return;

    $pct   = (float) $data['storage_pct'];
    $used  = (int) ($data['storage_bytes'] ?? 0);
    $limit = (int) ($data['storage_limit'] ?? 1073741824);
    $db    = (int) ($data['db_bytes'] ?? 0);

    if ($pct < 60) return; // Only show if using >60% of quota

    $level = $pct >= 95 ? 'critical' : ($pct >= 80 ? 'warning' : 'info');
@endphp

<div class="rounded-xl border p-4 mb-4
    {{ $level === 'critical' ? 'bg-red-50 border-red-200' : ($level === 'warning' ? 'bg-yellow-50 border-yellow-200' : 'bg-blue-50 border-blue-200') }}"
    dir="rtl">
  <div class="flex items-start gap-3">
    <div class="mt-0.5">
      @if($level === 'critical')
        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      @elseif($level === 'warning')
        <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      @else
        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      @endif
    </div>
    <div class="flex-1">
      <p class="text-sm font-semibold {{ $level === 'critical' ? 'text-red-800' : ($level === 'warning' ? 'text-yellow-800' : 'text-blue-800') }}">
        @if($level === 'critical')
          فضای ذخیره‌سازی در آستانه پر شدن!
        @elseif($level === 'warning')
          هشدار فضا: ظرفیت سایت در حال اتمام
        @else
          مصرف فضا
        @endif
      </p>

      {{-- Progress bar --}}
      <div class="mt-2">
        <div class="flex justify-between text-xs mb-1 {{ $level === 'critical' ? 'text-red-700' : ($level === 'warning' ? 'text-yellow-700' : 'text-blue-700') }}">
          <span>{{ $pct }}٪ مصرف شده</span>
          <span>{{ \App\Helpers\NumberHelper::formatBytes($used) }} از {{ \App\Helpers\NumberHelper::formatBytes($limit) }}</span>
        </div>
        <div class="w-full h-2 bg-white bg-opacity-60 rounded-full overflow-hidden">
          <div class="h-full rounded-full {{ $level === 'critical' ? 'bg-red-500' : ($level === 'warning' ? 'bg-yellow-500' : 'bg-blue-500') }}"
               style="width: {{ min(100, $pct) }}%"></div>
        </div>
      </div>

      @if($db > 0)
        <p class="text-xs mt-2 {{ $level === 'critical' ? 'text-red-600' : ($level === 'warning' ? 'text-yellow-600' : 'text-blue-600') }}">
          دیتابیس: {{ \App\Helpers\NumberHelper::formatBytes($db) }}
        </p>
      @endif

      @if($level === 'critical')
        <p class="text-xs mt-2 text-red-700">لطفاً برای افزایش فضا با پشتیبانی تماس بگیرید یا فایل‌های غیرضروری را حذف کنید.</p>
      @elseif($level === 'warning')
        <p class="text-xs mt-2 text-yellow-700">در صورت نیاز می‌توانید فایل‌های قدیمی را حذف یا فضا را افزایش دهید.</p>
      @endif
    </div>
  </div>
</div>
