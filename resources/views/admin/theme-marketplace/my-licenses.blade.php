@extends('admin.layout')

@section('title', 'قالب‌های من')

@section('content')
<div class="space-y-6" dir="rtl">

  {{-- Header --}}
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-bold text-gray-900">قالب‌های من</h1>
      <p class="text-sm text-gray-500 mt-1">قالب‌های خریداری‌شده و لایسنس‌های فعال</p>
    </div>
    <a href="{{ route('admin.theme-marketplace.index') }}"
       class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
      <x-core-ui::icon name="store" class="w-4 h-4" />
      فروشگاه قالب‌ها
    </a>
  </div>

  @if(!empty($purchased))
    <div class="bg-green-50 border border-green-200 rounded-xl p-5 flex items-start gap-4">
      <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center shrink-0">
        <x-core-ui::icon name="check-circle" class="w-5 h-5 text-green-600" />
      </div>
      <div>
        <p class="font-semibold text-green-800 text-sm">خرید موفق!</p>
        <p class="text-green-700 text-sm mt-0.5">{{ $msg ?: 'قالب با موفقیت خریداری شد. اکنون می‌توانید آن را نصب کنید.' }}</p>
      </div>
    </div>
  @endif
  @if(!empty($error))
    <div class="bg-amber-50 border border-amber-200 text-amber-700 rounded-lg px-4 py-3 text-sm">{{ $error }}</div>
  @endif
  @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-3 text-sm">{{ session('success') }}</div>
  @endif

  @if(empty($licenses))
    <div class="bg-white rounded-xl border border-gray-100 py-16 text-center">
      <div class="w-16 h-16 bg-indigo-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
        <x-core-ui::icon name="shield-check" class="w-8 h-8 text-indigo-300" />
      </div>
      <p class="text-gray-500 font-medium mb-1">هنوز قالبی خریداری نکرده‌اید</p>
      <p class="text-gray-400 text-sm mb-4">از فروشگاه قالب‌های حرفه‌ای را مرور کنید</p>
      <a href="{{ route('admin.theme-marketplace.index') }}"
         class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition">
        مشاهده فروشگاه
      </a>
    </div>
  @else

    {{-- Update available banner (Phase 1h: ledger-preferred, legacy fallback
         only when ledger state is unavailable — see ThemeMarketplacePresenter) --}}
    @php $outdatedCount = count(array_filter($licenses, fn($l) => \App\Services\ThemeMarketplacePresenter::isOutdated($l))); @endphp
    @if($outdatedCount > 0)
    <div class="bg-amber-50 border border-amber-200 rounded-xl px-5 py-4 flex items-center gap-4">
      <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
      <p class="text-amber-700 text-sm font-medium">{{ $outdatedCount }} قالب آپدیت جدید دارد — برای دریافت نسخه جدید، دوباره «نصب» کنید.</p>
    </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
      @foreach($licenses as $lic)
        @php
          $theme = $lic['theme'] ?? null;
          $isOutdated = \App\Services\ThemeMarketplacePresenter::isOutdated($lic);
          $currentVersion = \App\Services\ThemeMarketplacePresenter::currentVersion($theme);
          $installedVersion = \App\Services\ThemeMarketplacePresenter::installedVersion($lic);
          $releaseNotes = \App\Services\ThemeMarketplacePresenter::releaseNotes($theme);
        @endphp
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col {{ $isOutdated ? 'ring-2 ring-amber-400' : '' }}">

          {{-- Thumbnail --}}
          <div class="relative h-32 bg-gray-50 overflow-hidden">
            @if($theme['thumbnail'] ?? null)
              <img src="{{ $theme['thumbnail'] }}" class="w-full h-full object-cover" alt="{{ $theme['name'] ?? '' }}">
            @else
              <div class="w-full h-full flex items-center justify-center bg-linear-to-br from-indigo-50 to-violet-50">
                <x-core-ui::icon name="image" class="w-10 h-10 text-indigo-200" />
              </div>
            @endif
            {{-- Update badge --}}
            @if($isOutdated)
              <div class="absolute top-2 right-2 bg-amber-500 text-white text-xs px-2 py-0.5 rounded font-bold">⬆ آپدیت موجود</div>
            @else
              <div class="absolute top-2 right-2 bg-green-500 text-white text-xs px-2 py-0.5 rounded font-bold">✓ به‌روز</div>
            @endif
            {{-- Category chip --}}
            @if($theme['category'] ?? null)
              <div class="absolute bottom-2 right-2 bg-white/90 text-gray-600 text-xs px-2 py-0.5 rounded-lg">
                {{ \App\Services\ThemeCategoryHelper::label($theme['category']) }}
              </div>
            @endif
          </div>

          {{-- Content --}}
          <div class="p-4 flex-1 flex flex-col">
            <div class="flex items-start justify-between gap-2 mb-2">
              <div>
                <h3 class="font-bold text-gray-900 text-sm">{{ $theme['name'] ?? 'قالب #' . ($theme['id'] ?? '?') }}</h3>
                <p class="text-xs text-gray-400 mt-0.5 font-mono">{{ $theme['slug'] ?? '' }}</p>
              </div>
              <span class="shrink-0 bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded-lg font-semibold">
                {{ $lic['is_permanent'] ? 'دائمی' : 'محدود' }}
              </span>
            </div>

            {{-- Update notes (Phase 1h: release_notes preferred over version_notes) --}}
            @if($isOutdated && $releaseNotes)
              <div class="bg-amber-50 border border-amber-200 rounded-lg p-2 mb-3 text-xs text-amber-700">
                <span class="font-semibold">نوآوری نسخه جدید: </span>{{ $releaseNotes }}
              </div>
            @endif

            {{-- License key --}}
            <div class="bg-gray-50 rounded-lg p-3 mb-3">
              <p class="text-xs text-gray-500 mb-0.5 font-medium">کلید لایسنس</p>
              <p class="font-mono text-xs text-gray-700 select-all break-all">{{ $lic['license_key'] }}</p>
            </div>

            <div class="flex items-center justify-between text-xs text-gray-400 mb-1">
              <span>تاریخ خرید: {{ $lic['purchased_at'] ?? '—' }}</span>
              @if($currentVersion)
                <span class="font-mono">
                  @if($installedVersion && $installedVersion !== $currentVersion)
                    نصب‌شده {{ $installedVersion }} ← جدید {{ $currentVersion }}
                  @else
                    نسخه {{ $currentVersion }}
                  @endif
                </span>
              @endif
            </div>
            {{-- GAP-L01: License expiry date --}}
            <div class="text-xs mb-3 {{ ($lic['is_permanent'] ?? true) ? 'text-green-600' : 'text-amber-600' }}">
              @if($lic['is_permanent'] ?? true)
                <span class="flex items-center gap-1">
                  <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                  انقضا: بدون محدودیت (دائمی)
                </span>
              @elseif($lic['expires_at'] ?? null)
                <span class="flex items-center gap-1">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                  انقضا: {{ $lic['expires_at'] }}
                </span>
              @endif
            </div>

            {{-- CTA --}}
            <div class="mt-auto flex flex-col gap-2">
              <form method="POST"
                action="{{ route('admin.theme-marketplace.install', $theme['id'] ?? 0) }}"
                onsubmit="return confirm('{{ ($hasActiveTheme ?? false) || ($hasUnsavedDraft ?? false) ? 'قالب فعلی و شخصی‌سازی‌های آن جایگزین خواهد شد. ادامه می‌دهید؟' : 'قالب «'.($theme['name'] ?? '').'» نصب و فعال شود؟' }}')">
                @csrf
                @if(($hasActiveTheme ?? false) || ($hasUnsavedDraft ?? false))
                  <label class="flex items-start gap-2 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                    <input type="checkbox" name="confirm_overwrite" value="1" required class="mt-0.5">
                    <span>
                      می‌دانم که {{ $isOutdated ? 'آپدیت' : 'نصب' }} این قالب، قالب فعال فعلی{{ ($hasUnsavedDraft ?? false) ? ' و تغییرات ذخیره‌نشده در ویرایشگر بوم' : '' }} را جایگزین می‌کند.
                    </span>
                  </label>
                @endif
                <button type="submit"
                  class="w-full inline-flex items-center justify-center gap-2 {{ $isOutdated ? 'bg-amber-600 hover:bg-amber-700' : 'bg-indigo-600 hover:bg-indigo-700' }} text-white text-sm font-medium py-2.5 rounded-lg transition">
                  <x-core-ui::icon name="download" class="w-4 h-4" />
                  {{ $isOutdated ? 'آپدیت و نصب' : 'نصب و فعال‌سازی' }}
                </button>
              </form>
              {{-- GAP-H06: PDF invoice download --}}
              <a href="{{ route('admin.theme-marketplace.invoice-download', $lic['license_key']) }}"
                 target="_blank"
                 class="w-full inline-flex items-center justify-center gap-2 bg-gray-50 hover:bg-gray-100 text-gray-600 border border-gray-200 text-xs font-medium py-2 rounded-lg transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                دانلود فاکتور PDF
              </a>
            </div>
          </div>
        </div>
      @endforeach
    </div>
  @endif

  {{-- BFA-H02: Rollback to previous theme --}}
  @php $backupAt = \App\Models\Setting::get('canvas_theme_backup_at'); $backupName = \App\Models\Setting::get('canvas_theme_backup_name'); @endphp
  @if($backupAt && $backupName)
  <div class="bg-amber-50 border border-amber-200 rounded-xl px-5 py-4 flex items-center justify-between gap-4">
    <div class="flex items-center gap-3">
      <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
      </svg>
      <div>
        <p class="text-amber-800 text-sm font-semibold">نسخه پشتیبان قالب موجود است</p>
        <p class="text-amber-700 text-xs mt-0.5">قالب «{{ $backupName }}» در {{ \Carbon\Carbon::parse($backupAt)->diffForHumans() }} ذخیره شد</p>
      </div>
    </div>
    <form method="POST" action="{{ route('admin.theme-marketplace.rollback') }}"
          onsubmit="return confirm('قالب قبلی «{{ $backupName }}» بازگردانی شود؟ قالب فعلی حذف خواهد شد.')">
      @csrf
      <button type="submit"
        class="inline-flex items-center gap-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
        </svg>
        بازگردانی قالب قبلی
      </button>
    </form>
  </div>
  @endif

</div>
@endsection
