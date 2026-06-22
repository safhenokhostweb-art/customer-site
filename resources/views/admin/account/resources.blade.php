@extends('admin.layout')

@section('title', 'مصرف منابع سایت')

@section('content')
<div class="space-y-6" dir="rtl">

  <div>
    <h1 class="text-2xl font-bold text-gray-900">مصرف منابع سایت</h1>
    <p class="text-sm text-gray-500 mt-1">وضعیت فضا، دیتابیس و ترافیک سایت شما</p>
  </div>

  @php
    $summary = \App\Models\Setting::get('site_resource_summary', null);
    $data    = $summary ? json_decode($summary, true) : null;
    $pct     = $data ? (float) ($data['storage_pct'] ?? 0) : 0;
    $used    = $data ? (int)   ($data['storage_bytes'] ?? 0) : 0;
    $limit   = $data ? (int)   ($data['storage_limit'] ?? 1073741824) : 1073741824;
    $db      = $data ? (int)   ($data['db_bytes'] ?? 0) : 0;
    $archive = $data ? (int)   ($data['archive_bytes'] ?? 0) : 0;
    $calcAt  = $data['calculated_at'] ?? null;
  @endphp

  @if(!$data)
    <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-4 text-sm text-amber-800">
      اطلاعات منابع هنوز در دسترس نیست. لطفاً با پشتیبانی تماس بگیرید.
    </div>
  @else

    {{-- Alert banner --}}
    @if($pct >= 95)
      <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 flex items-center gap-3">
        <svg class="w-5 h-5 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        <p class="text-sm text-red-800"><strong>بحران فضا!</strong> ظرفیت سایت شما به {{ $pct }}٪ رسیده. فوری اقدام کنید.</p>
      </div>
    @elseif($pct >= 80)
      <div class="bg-yellow-50 border border-yellow-200 rounded-xl px-4 py-3 flex items-center gap-3">
        <svg class="w-5 h-5 text-yellow-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        <p class="text-sm text-yellow-800"><strong>هشدار:</strong> فضای سایت به {{ $pct }}٪ رسیده است.</p>
      </div>
    @endif

    {{-- Resource Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

      {{-- Storage --}}
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-center gap-3 mb-4">
          <div class="p-2.5 {{ $pct >= 95 ? 'bg-red-50' : ($pct >= 80 ? 'bg-yellow-50' : 'bg-indigo-50') }} rounded-xl">
            <svg class="w-6 h-6 {{ $pct >= 95 ? 'text-red-600' : ($pct >= 80 ? 'text-yellow-600' : 'text-indigo-600') }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/></svg>
          </div>
          <div>
            <p class="text-xs text-gray-500">فضای فایل‌ها</p>
            <p class="text-xl font-bold {{ $pct >= 95 ? 'text-red-700' : ($pct >= 80 ? 'text-yellow-700' : 'text-gray-900') }}">
              {{ \App\Helpers\NumberHelper::formatBytes($used) }}
            </p>
          </div>
        </div>
        <div>
          <div class="flex justify-between text-xs text-gray-500 mb-1.5">
            <span>{{ $pct }}٪</span>
            <span>از {{ \App\Helpers\NumberHelper::formatBytes($limit) }}</span>
          </div>
          <div class="w-full h-2.5 bg-gray-100 rounded-full overflow-hidden">
            <div class="{{ $pct >= 95 ? 'bg-red-500' : ($pct >= 80 ? 'bg-yellow-500' : 'bg-indigo-500') }} h-full rounded-full transition-all"
                 style="width: {{ min(100, $pct) }}%"></div>
          </div>
          <p class="text-xs text-gray-400 mt-1.5">
            {{ \App\Helpers\NumberHelper::formatBytes(max(0, $limit - $used)) }} باقی‌مانده
          </p>
        </div>
      </div>

      {{-- Database --}}
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-center gap-3 mb-4">
          <div class="p-2.5 bg-blue-50 rounded-xl">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
          </div>
          <div>
            <p class="text-xs text-gray-500">دیتابیس</p>
            <p class="text-xl font-bold text-gray-900">{{ \App\Helpers\NumberHelper::formatBytes($db) }}</p>
          </div>
        </div>
        <p class="text-sm text-gray-500">اطلاعات، تنظیمات، محتوا و رکوردهای سایت</p>
      </div>

      {{-- Archive --}}
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-center gap-3 mb-4">
          <div class="p-2.5 {{ $archive > 0 ? 'bg-amber-50' : 'bg-gray-50' }} rounded-xl">
            <svg class="w-6 h-6 {{ $archive > 0 ? 'text-amber-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
          </div>
          <div>
            <p class="text-xs text-gray-500">آرشیو</p>
            <p class="text-xl font-bold {{ $archive > 0 ? 'text-amber-700' : 'text-gray-400' }}">
              {{ $archive > 0 ? \App\Helpers\NumberHelper::formatBytes($archive) : 'ندارد' }}
            </p>
          </div>
        </div>
        @if($archive > 0)
          <p class="text-sm text-amber-700">سایت شما آرشیو شده است. برای بازگردانی با پشتیبانی تماس بگیرید.</p>
        @else
          <p class="text-sm text-gray-500">سایت فعال — آرشیوی وجود ندارد</p>
        @endif
      </div>
    </div>

    {{-- Total usage summary --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5">
      <h2 class="font-semibold text-gray-800 text-sm mb-4">خلاصه مصرف</h2>
      <div class="space-y-3">
        @foreach([
          ['فایل‌های آپلودشده', $used],
          ['دیتابیس', $db],
          ['آرشیو', $archive],
        ] as [$label, $bytes])
          <div class="flex items-center gap-3">
            <span class="text-sm text-gray-600 w-36">{{ $label }}</span>
            <div class="flex-1 h-1.5 bg-gray-100 rounded-full overflow-hidden">
              @php $rowPct = $limit > 0 ? min(100, ($bytes / $limit) * 100) : 0; @endphp
              <div class="bg-indigo-400 h-full rounded-full" style="width: {{ $rowPct }}%"></div>
            </div>
            <span class="text-sm font-medium text-gray-700 w-20 text-left">{{ \App\Helpers\NumberHelper::formatBytes($bytes) }}</span>
          </div>
        @endforeach
        <div class="flex items-center gap-3 pt-2 border-t border-gray-100">
          <span class="text-sm font-semibold text-gray-800 w-36">جمع کل</span>
          <div class="flex-1"></div>
          <span class="text-sm font-bold text-gray-900 w-20 text-left">{{ \App\Helpers\NumberHelper::formatBytes($used + $db + $archive) }}</span>
        </div>
      </div>
    </div>

    @if($calcAt)
      <p class="text-xs text-gray-400 text-left">آخرین بروزرسانی: {{ $calcAt }}</p>
    @endif

  @endif

  {{-- Support CTA --}}
  <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-5">
    <h3 class="font-semibold text-indigo-900 text-sm mb-1">نیاز به فضای بیشتر دارید؟</h3>
    <p class="text-sm text-indigo-700">برای ارتقای پلن یا افزایش سقف منابع با تیم پشتیبانی تماس بگیرید.</p>
    <a href="{{ url('/contact') }}"
       class="mt-3 inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">
      تماس با پشتیبانی
    </a>
  </div>

</div>
@endsection
