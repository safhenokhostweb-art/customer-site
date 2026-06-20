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

    {{-- Update available banner --}}
    @php $outdatedCount = count(array_filter($licenses, fn($l) => $l['is_outdated'] ?? false)); @endphp
    @if($outdatedCount > 0)
    <div class="bg-amber-50 border border-amber-200 rounded-xl px-5 py-4 flex items-center gap-4">
      <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
      <p class="text-amber-700 text-sm font-medium">{{ $outdatedCount }} قالب آپدیت جدید دارد — برای دریافت نسخه جدید، دوباره «نصب» کنید.</p>
    </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
      @foreach($licenses as $lic)
        @php $theme = $lic['theme'] ?? null; $isOutdated = $lic['is_outdated'] ?? false; @endphp
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

            {{-- Update notes --}}
            @if($isOutdated && ($theme['version_notes'] ?? null))
              <div class="bg-amber-50 border border-amber-200 rounded-lg p-2 mb-3 text-xs text-amber-700">
                <span class="font-semibold">نوآوری نسخه جدید: </span>{{ $theme['version_notes'] }}
              </div>
            @endif

            {{-- License key --}}
            <div class="bg-gray-50 rounded-lg p-3 mb-3">
              <p class="text-xs text-gray-500 mb-0.5 font-medium">کلید لایسنس</p>
              <p class="font-mono text-xs text-gray-700 select-all break-all">{{ $lic['license_key'] }}</p>
            </div>

            <div class="flex items-center justify-between text-xs text-gray-400 mb-3">
              <span>تاریخ خرید: {{ $lic['purchased_at'] ?? '—' }}</span>
              @if($theme['version'] ?? null)
                <span>نسخه {{ $theme['version'] ?? '—' }}</span>
              @endif
            </div>

            {{-- CTA --}}
            <div class="mt-auto">
              <form method="POST"
                action="{{ route('admin.theme-marketplace.install', $theme['id'] ?? 0) }}"
                onsubmit="return confirm('قالب «{{ $theme['name'] ?? '' }}» نصب و فعال شود؟')">
                @csrf
                <button type="submit"
                  class="w-full inline-flex items-center justify-center gap-2 {{ $isOutdated ? 'bg-amber-600 hover:bg-amber-700' : 'bg-indigo-600 hover:bg-indigo-700' }} text-white text-sm font-medium py-2.5 rounded-lg transition">
                  <x-core-ui::icon name="download" class="w-4 h-4" />
                  {{ $isOutdated ? 'آپدیت و نصب' : 'نصب و فعال‌سازی' }}
                </button>
              </form>
            </div>
          </div>
        </div>
      @endforeach
    </div>
  @endif

</div>
@endsection
