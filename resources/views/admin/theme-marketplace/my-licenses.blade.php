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
      <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
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
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      @foreach($licenses as $lic)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">
          <div class="p-5 flex-1">
            <div class="flex items-start justify-between mb-3">
              <div>
                <h3 class="font-bold text-gray-900 text-base">{{ $lic['theme']['name'] ?? 'قالب #' . $lic['theme']['id'] }}</h3>
                <p class="text-xs text-gray-400 mt-0.5">{{ $lic['theme']['slug'] ?? '' }}</p>
              </div>
              <span class="bg-green-100 text-green-700 text-xs px-2 py-1 rounded-lg font-semibold flex-shrink-0 ml-2">
                {{ $lic['is_permanent'] ? 'دائمی' : 'محدود' }}
              </span>
            </div>

            <div class="bg-gray-50 rounded-lg p-3 mb-4">
              <p class="text-xs text-gray-500 mb-1 font-medium">کلید لایسنس</p>
              <p class="font-mono text-xs text-gray-700 select-all break-all">{{ $lic['license_key'] }}</p>
            </div>

            <p class="text-xs text-gray-400">
              تاریخ خرید: {{ $lic['purchased_at'] ?? '—' }}
            </p>
          </div>

          <div class="px-5 pb-5">
            <form method="POST"
              action="{{ route('admin.theme-marketplace.install', $lic['theme']['id'] ?? 0) }}"
              onsubmit="return confirm('قالب «{{ $lic['theme']['name'] ?? '' }}» نصب و فعال شود؟')">
              @csrf
              <button type="submit"
                class="w-full inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium py-2.5 rounded-lg transition">
                <x-core-ui::icon name="download" class="w-4 h-4" />
                نصب و فعال‌سازی قالب
              </button>
            </form>
          </div>
        </div>
      @endforeach
    </div>
  @endif

</div>
@endsection
