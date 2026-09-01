@extends('admin.layout')

@section('title', 'پیش‌نمایش ارتقای قالب')

@section('content')
<div class="space-y-6 max-w-3xl" dir="rtl">

  <div class="flex items-center gap-2 text-sm text-gray-500">
    <a href="{{ route('admin.theme-marketplace.index') }}" class="hover:text-indigo-600">فروشگاه قالب‌ها</a>
    <span>/</span>
    <span class="text-gray-700">پیش‌نمایش ارتقا</span>
  </div>

  <h1 class="text-xl font-bold text-gray-900">پیش‌نمایش ارتقای قالب</h1>

  @if($preview->status === \App\Services\ThemeCanvas\ThemeUpgradePreviewResult::STATUS_NON_RECONCILABLE)
    <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-lg p-4 text-sm">
      امکان ارتقای ایمن این نصب وجود ندارد (منشأ نسخه فعلی قابل اثبات نیست).
      برای این حالت فقط جایگزینی کامل (مخرب) از صفحه قالب در دسترس است.
    </div>
  @elseif($preview->status === \App\Services\ThemeCanvas\ThemeUpgradePreviewResult::STATUS_DIFFERENT_LINEAGE)
    <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-lg p-4 text-sm">
      این قالب با قالب نصب‌شده فعلی متفاوت است — این یک ارتقا نیست، بلکه تعویض کامل قالب است.
    </div>
  @elseif($preview->status === \App\Services\ThemeCanvas\ThemeUpgradePreviewResult::STATUS_DOWNLOAD_FAILED)
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4 text-sm">
      دریافت اطلاعات نسخه جدید از پلتفرم ناموفق بود. لطفاً دوباره تلاش کنید.
    </div>
  @elseif($preview->status === \App\Services\ThemeCanvas\ThemeUpgradePreviewResult::STATUS_REMOTE_NOT_TRACKABLE)
    <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-lg p-4 text-sm">
      نسخه مقصد فاقد شناسه انتشار معتبر است و نمی‌تواند به‌صورت ایمن پیگیری شود.
    </div>
  @else
    @php($r = $preview->reconciliation)

    <div class="bg-white border border-gray-200 rounded-lg p-4 grid grid-cols-2 gap-4 text-sm">
      <div>
        <div class="text-gray-500">نسخه فعلی</div>
        <div class="font-medium text-gray-900">{{ $preview->currentRelease['release_version'] ?? '—' }}</div>
      </div>
      <div>
        <div class="text-gray-500">نسخه مقصد</div>
        <div class="font-medium text-gray-900">{{ $preview->targetRelease['release_version'] ?? '—' }} — {{ $preview->targetRelease['theme_name'] ?? '' }}</div>
      </div>
    </div>

    @if(!$r->canApply())
      <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4 text-sm font-medium">
        این ارتقا به دلیل وابستگی‌های مسدودکننده قابل اعمال نیست.
      </div>
    @endif

    @php($blocking = array_values(array_filter($r->conflicts, fn ($c) => $c->isBlocking())))
    @php($nonBlocking = array_values(array_filter($r->conflicts, fn ($c) => !$c->isBlocking())))

    @if(count($blocking) > 0)
      <div class="bg-white border border-red-200 rounded-lg p-4">
        <h2 class="text-sm font-semibold text-red-700 mb-2">موانع مسدودکننده ({{ count($blocking) }})</h2>
        <ul class="text-xs text-gray-700 space-y-1">
          @foreach($blocking as $c)
            <li>{{ $c->conflictType }} — {{ $c->metadata['dependency_identifier'] ?? $c->blockId ?? '' }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <div class="bg-white border border-gray-200 rounded-lg p-4">
      <h2 class="text-sm font-semibold text-gray-800 mb-2">خلاصه تغییرات</h2>
      <ul class="text-xs text-gray-600 space-y-1">
        <li>شخصی‌سازی‌های حفظ‌شده بدون تغییر: {{ count($r->preservedCustomizations) }}</li>
        <li>محتوای جدید از نسخه مقصد: {{ count($r->remoteAdditions) }}</li>
        <li>موارد حل‌شده به‌صورت خودکار (غیرمسدودکننده): {{ count($nonBlocking) }}</li>
        <li>بلوک‌های جداشده و حفظ‌شده: {{ count($r->detachedBlocks) }}</li>
        <li>جابه‌جایی‌های خودکار ساختاری: {{ count($r->structuralFallbacks) }}</li>
        <li>هشدارها: {{ count($r->warnings) }}</li>
      </ul>
    </div>

    <form method="POST" action="{{ route('admin.theme-marketplace.apply-upgrade', $themeId) }}"
          onsubmit="return confirm('ارتقا اعمال شود؟');">
      @csrf
      <input type="hidden" name="release_id" value="{{ $preview->targetRelease['release_id'] }}">
      <input type="hidden" name="fingerprint" value="{{ $r->fingerprint }}">
      <button type="submit" @if(!$r->canApply()) disabled @endif
        class="w-full text-white text-sm font-semibold py-3 rounded-lg transition
               {{ $r->canApply() ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-300 cursor-not-allowed' }}">
        اعمال ارتقا
      </button>
    </form>
  @endif

  <a href="{{ route('admin.theme-marketplace.show', $themeId) }}"
     class="block text-center text-sm text-gray-500 hover:text-gray-700 py-1">بازگشت به صفحه قالب</a>
</div>
@endsection
