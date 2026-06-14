@extends('admin.layout')

@section('title', 'فروشگاه قالب‌ها')

@section('content')
<div class="space-y-6" dir="rtl">

  {{-- Header --}}
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-bold text-gray-900">فروشگاه قالب‌ها</h1>
      <p class="text-sm text-gray-500 mt-1">قالب‌های حرفه‌ای پلتفرم را مرور و خریداری کنید</p>
    </div>
    <a href="{{ route('admin.theme-marketplace.my-licenses') }}"
       class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
      <x-core-ui::icon name="shield-check" class="w-4 h-4" />
      قالب‌های من
    </a>
  </div>

  {{-- Flash --}}
  @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-3 text-sm flex items-center gap-2">
      <x-core-ui::icon name="check-circle" class="w-5 h-5 text-green-500 shrink-0" />
      {{ session('success') }}
    </div>
  @endif
  @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">{{ session('error') }}</div>
  @endif
  @if(!empty($error))
    <div class="bg-amber-50 border border-amber-200 text-amber-700 rounded-lg px-4 py-3 text-sm">{{ $error }}</div>
  @endif
  @if(!empty($failed))
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">
      پرداخت ناموفق بود. {{ $msg ? '(' . $msg . ')' : '' }}
    </div>
  @endif

  {{-- Featured --}}
  @php $featured = array_filter($themes ?? [], fn($t) => $t['is_featured'] ?? false); @endphp
  @if(count($featured))
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-2xl p-6 text-white">
      <p class="text-xs font-semibold uppercase tracking-wider opacity-80 mb-4">قالب‌های ویژه</p>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($featured as $theme)
          <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 flex flex-col gap-3">
            @if($theme['thumbnail'] ?? null)
              <img src="{{ $theme['thumbnail'] }}" class="w-full h-28 object-cover rounded-lg" alt="{{ $theme['name'] }}">
            @else
              <div class="w-full h-28 bg-white/20 rounded-lg flex items-center justify-center">
                <x-core-ui::icon name="image" class="w-10 h-10 opacity-40" />
              </div>
            @endif
            <div class="flex items-center justify-between">
              <span class="font-semibold text-sm">{{ $theme['name'] }}</span>
              @if($theme['is_free'] ?? false)
                <span class="bg-green-400 text-green-900 text-xs px-2 py-0.5 rounded-full font-bold">رایگان</span>
              @else
                <span class="text-xs font-bold">{{ number_format($theme['price_rial']) }} ریال</span>
              @endif
            </div>
            @if($theme['has_license'] ?? false)
              <a href="{{ route('admin.theme-marketplace.my-licenses') }}"
                 class="text-center text-xs bg-green-500 text-white py-1.5 rounded-lg font-medium">✓ خریداری‌شده</a>
            @else
              <form method="POST" action="{{ route('admin.theme-marketplace.purchase', $theme['id']) }}">
                @csrf
                <button type="submit" class="w-full text-center text-xs bg-white text-indigo-700 py-1.5 rounded-lg font-bold hover:bg-indigo-50 transition">
                  {{ ($theme['is_free'] ?? false) ? 'دریافت رایگان' : 'خرید قالب' }}
                </button>
              </form>
            @endif
          </div>
        @endforeach
      </div>
    </div>
  @endif

  {{-- All Themes Grid --}}
  @if(empty($themes))
    <div class="bg-white rounded-xl border border-gray-100 py-16 text-center">
      <x-core-ui::icon name="puzzle" class="w-12 h-12 text-gray-300 mx-auto mb-3" />
      <p class="text-gray-400 text-sm">هنوز قالبی در فروشگاه موجود نیست.</p>
    </div>
  @else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      @foreach($themes as $theme)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col hover:shadow-md transition group">

          {{-- Thumbnail --}}
          <div class="relative h-40 bg-gray-50 overflow-hidden">
            @if($theme['preview_image'] ?? $theme['thumbnail'] ?? null)
              <img src="{{ $theme['preview_image'] ?? $theme['thumbnail'] }}"
                   class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                   alt="{{ $theme['name'] }}">
            @else
              <div class="w-full h-full flex items-center justify-center">
                <x-core-ui::icon name="image" class="w-12 h-12 text-gray-400" />
              </div>
            @endif
            {{-- Badges --}}
            <div class="absolute top-2 right-2 flex gap-1">
              @if($theme['is_featured'] ?? false)
                <span class="bg-amber-400 text-amber-900 text-xs px-2 py-0.5 rounded-full font-semibold">⭐ ویژه</span>
              @endif
              @if($theme['is_free'] ?? false)
                <span class="bg-green-500 text-white text-xs px-2 py-0.5 rounded-full font-semibold">رایگان</span>
              @endif
              @if($theme['has_license'] ?? false)
                <span class="bg-indigo-600 text-white text-xs px-2 py-0.5 rounded-full font-semibold">✓ خریداری‌شده</span>
              @endif
            </div>
          </div>

          {{-- Content --}}
          <div class="p-5 flex flex-col flex-1">
            <h3 class="font-bold text-gray-900 text-base mb-1">{{ $theme['name'] }}</h3>
            @if($theme['description'] ?? null)
              <p class="text-sm text-gray-500 leading-relaxed line-clamp-2 mb-3">{{ $theme['description'] }}</p>
            @endif

            {{-- Requirements --}}
            @if(!empty($theme['requirements']))
              <div class="flex flex-wrap gap-1 mb-3">
                @foreach($theme['requirements'] as $req)
                  <span class="bg-gray-100 text-gray-500 text-xs px-2 py-0.5 rounded font-medium">{{ $req }}</span>
                @endforeach
              </div>
            @endif

            {{-- Sales --}}
            @if(($theme['sales_count'] ?? 0) > 0)
              <p class="text-xs text-gray-400 mb-3">{{ number_format($theme['sales_count']) }} بار فروخته شده</p>
            @endif

            <div class="mt-auto flex items-center justify-between">
              @if($theme['is_free'] ?? false)
                <span class="text-green-600 font-bold text-sm">رایگان</span>
              @else
                <span class="font-bold text-gray-900 text-base">{{ number_format($theme['price_rial']) }} <span class="text-xs font-normal text-gray-400">ریال</span></span>
              @endif

              @if($theme['has_license'] ?? false)
                <a href="{{ route('admin.theme-marketplace.my-licenses') }}"
                   class="inline-flex items-center gap-1.5 bg-green-100 text-green-700 hover:bg-green-200 text-xs font-semibold px-4 py-2 rounded-lg transition">
                  <x-core-ui::icon name="check-circle" class="w-4 h-4" />
                  مدیریت
                </a>
              @else
                <form method="POST" action="{{ route('admin.theme-marketplace.purchase', $theme['id']) }}">
                  @csrf
                  <button type="submit"
                    class="inline-flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-4 py-2 rounded-lg transition">
                    <x-core-ui::icon name="cart" class="w-4 h-4" />
                    {{ ($theme['is_free'] ?? false) ? 'دریافت' : 'خرید' }}
                  </button>
                </form>
              @endif
            </div>
          </div>
        </div>
      @endforeach
    </div>
  @endif

</div>
@endsection
