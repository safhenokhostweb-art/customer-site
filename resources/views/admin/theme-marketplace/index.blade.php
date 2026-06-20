@extends('admin.layout')

@section('title', 'فروشگاه قالب‌ها')

@section('content')
<div class="space-y-6" dir="rtl" x-data="themeStore()">

  {{-- Header --}}
  <div class="flex items-center justify-between flex-wrap gap-3">
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

  @if(!empty($error))
    <div class="bg-amber-50 border border-amber-200 text-amber-700 rounded-lg px-4 py-3 text-sm">{{ $error }}</div>
  @endif
  @if(!empty($failed))
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">
      پرداخت ناموفق بود. {{ $msg ? '(' . $msg . ')' : '' }}
    </div>
  @endif
  @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-3 text-sm">{{ session('success') }}</div>
  @endif

  @php
    $allThemes  = $themes ?? [];
    $featured   = array_values(array_filter($allThemes, fn($t) => $t['is_featured'] ?? false));
    $categories = array_values(array_unique(array_filter(array_column($allThemes, 'category'))));
  @endphp

  {{-- Category filter + Search --}}
  @if(count($allThemes) > 0)
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 flex flex-wrap gap-3 items-center">
    <button @click="filterCat = ''"
      :class="filterCat === '' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
      class="text-xs font-medium px-3 py-1.5 rounded-lg transition">همه</button>
    @foreach($categories as $cat)
    <button @click="filterCat = '{{ $cat }}'"
      :class="filterCat === '{{ $cat }}' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
      class="text-xs font-medium px-3 py-1.5 rounded-lg transition">{{ \App\Services\ThemeCategoryHelper::label($cat) }}</button>
    @endforeach
    <div class="mr-auto">
      <input type="text" x-model="search" placeholder="جستجوی قالب..."
        class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
    </div>
  </div>
  @endif

  {{-- Featured Banner --}}
  @if(count($featured))
    <div class="bg-linear-to-l from-indigo-600 to-violet-600 rounded-2xl p-6 text-white">
      <p class="text-xs font-semibold uppercase tracking-wider opacity-80 mb-4">⭐ قالب‌های ویژه</p>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($featured as $theme)
          <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 flex flex-col gap-3">
            @if($theme['thumbnail'] ?? null)
              <div class="relative">
                <img src="{{ $theme['thumbnail'] }}" class="w-full h-28 object-cover rounded-lg" alt="{{ $theme['name'] }}">
                @if($theme['is_bestseller'] ?? false)
                  <span class="absolute top-1 right-1 bg-orange-500 text-white text-xs px-1.5 py-0.5 rounded font-bold">🔥 پرفروش</span>
                @endif
              </div>
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
  @if(empty($allThemes))
    <div class="bg-white rounded-xl border border-gray-100 py-16 text-center">
      <x-core-ui::icon name="puzzle" class="w-12 h-12 text-gray-300 mx-auto mb-3" />
      <p class="text-gray-400 text-sm">هنوز قالبی در فروشگاه موجود نیست.</p>
    </div>
  @else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
      @foreach($allThemes as $theme)
        <div x-show="matchesFilter('{{ $theme['category'] ?? '' }}', '{{ addslashes($theme['name']) }}')"
             class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col hover:shadow-md transition group">

          {{-- Thumbnail --}}
          <div class="relative h-44 bg-gray-50 overflow-hidden">
            @if($theme['preview_image'] ?? $theme['thumbnail'] ?? null)
              <img src="{{ $theme['preview_image'] ?? $theme['thumbnail'] }}"
                   class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                   alt="{{ $theme['name'] }}">
            @else
              <div class="w-full h-full flex items-center justify-center bg-linear-to-br from-indigo-50 to-violet-50">
                <x-core-ui::icon name="image" class="w-12 h-12 text-indigo-200" />
              </div>
            @endif

            {{-- Overlay badges --}}
            <div class="absolute top-2 right-2 flex flex-wrap gap-1">
              @if($theme['is_featured'] ?? false)
                <span class="bg-amber-400 text-amber-900 text-xs px-1.5 py-0.5 rounded font-bold">⭐</span>
              @endif
              @if($theme['is_bestseller'] ?? false)
                <span class="bg-orange-500 text-white text-xs px-1.5 py-0.5 rounded font-bold">🔥</span>
              @endif
              @if($theme['is_new_release'] ?? false)
                <span class="bg-green-500 text-white text-xs px-1.5 py-0.5 rounded font-bold">✨ جدید</span>
              @endif
              @if($theme['has_license'] ?? false)
                <span class="bg-indigo-600 text-white text-xs px-1.5 py-0.5 rounded font-bold">✓</span>
              @endif
            </div>

            {{-- Quality score chip --}}
            @if(($theme['quality_score'] ?? 0) > 0)
              @php
                $qs = $theme['quality_score'];
                $qc = $qs >= 90 ? 'emerald' : ($qs >= 75 ? 'green' : ($qs >= 60 ? 'amber' : 'rose'));
              @endphp
              <div class="absolute bottom-2 left-2 bg-white/90 text-{{ $qc }}-700 text-xs font-bold px-2 py-0.5 rounded-lg shadow">
                {{ $qs }}/۱۰۰
              </div>
            @endif

            {{-- Preview link --}}
            @if($theme['preview_url'] ?? null)
              <a href="{{ $theme['preview_url'] }}" target="_blank"
                 class="absolute bottom-2 right-2 bg-white/90 text-gray-700 hover:text-indigo-600 text-xs px-2 py-0.5 rounded-lg shadow transition">
                پیش‌نمایش ↗
              </a>
            @endif
          </div>

          {{-- Content --}}
          <div class="p-4 flex flex-col flex-1">
            {{-- Name + category --}}
            <div class="flex items-start justify-between gap-2 mb-1">
              <a href="{{ route('admin.theme-marketplace.show', $theme['id']) }}"
                 class="font-bold text-gray-900 text-base leading-snug hover:text-indigo-600 transition">{{ $theme['name'] }}</a>
              @if($theme['category'] ?? null)
                <span class="shrink-0 bg-gray-100 text-gray-500 text-xs px-2 py-0.5 rounded-full mt-0.5">{{ \App\Services\ThemeCategoryHelper::label($theme['category']) }}</span>
              @endif
            </div>

            {{-- Description --}}
            @if($theme['description'] ?? null)
              <p class="text-xs text-gray-500 leading-relaxed line-clamp-2 mb-3">{{ $theme['description'] }}</p>
            @endif

            {{-- Stats row --}}
            <div class="flex items-center gap-3 text-xs text-gray-400 mb-3">
              @if(($theme['installs_count'] ?? 0) > 0)
                <span>⬇ {{ number_format($theme['installs_count']) }} نصب</span>
              @endif
              @if(($theme['sales_count'] ?? 0) > 0)
                <span>🛒 {{ number_format($theme['sales_count']) }} فروش</span>
              @endif
            </div>

            {{-- Requirements --}}
            @if(!empty($theme['requirements']))
              <div class="flex flex-wrap gap-1 mb-3">
                @foreach($theme['requirements'] as $req)
                  <span class="bg-gray-100 text-gray-500 text-xs px-1.5 py-0.5 rounded font-mono">{{ $req }}</span>
                @endforeach
              </div>
            @endif

            {{-- Pricing --}}
            <div class="mt-auto">
              @if($theme['is_free'] ?? false)
                <p class="text-green-600 font-bold text-sm mb-3">رایگان</p>
              @else
                <div class="flex flex-wrap gap-2 mb-3">
                  <div class="bg-gray-50 border border-gray-200 rounded-lg px-2.5 py-1 text-center">
                    <p class="text-xs text-gray-400">یک‌بار</p>
                    <p class="text-sm font-bold text-gray-800">{{ number_format($theme['price_rial']) }}</p>
                  </div>
                  @if($theme['annual_price'] ?? null)
                  <div class="bg-indigo-50 border border-indigo-100 rounded-lg px-2.5 py-1 text-center">
                    <p class="text-xs text-indigo-400">سالانه</p>
                    <p class="text-sm font-bold text-indigo-700">{{ number_format($theme['annual_price']) }}</p>
                  </div>
                  @endif
                  @if($theme['lifetime_price'] ?? null)
                  <div class="bg-violet-50 border border-violet-100 rounded-lg px-2.5 py-1 text-center">
                    <p class="text-xs text-violet-400">مادام‌العمر</p>
                    <p class="text-sm font-bold text-violet-700">{{ number_format($theme['lifetime_price']) }}</p>
                  </div>
                  @endif
                </div>
              @endif

              {{-- CTA --}}
              <div class="flex items-center gap-2">
                @if($theme['has_license'] ?? false)
                  <a href="{{ route('admin.theme-marketplace.my-licenses') }}"
                     class="flex-1 text-center bg-green-100 text-green-700 hover:bg-green-200 text-xs font-semibold px-3 py-2 rounded-lg transition">
                    ✓ مدیریت لایسنس
                  </a>
                @else
                  <form method="POST" action="{{ route('admin.theme-marketplace.purchase', $theme['id']) }}" class="flex-1">
                    @csrf
                    <button type="submit"
                      class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-3 py-2 rounded-lg transition">
                      {{ ($theme['is_free'] ?? false) ? 'دریافت رایگان' : 'خرید قالب' }}
                    </button>
                  </form>
                @endif
                @if($theme['preview_url'] ?? null)
                  <a href="{{ $theme['preview_url'] }}" target="_blank"
                     class="bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs font-medium px-3 py-2 rounded-lg transition shrink-0">
                    دمو
                  </a>
                @endif
              </div>
            </div>
          </div>
        </div>
      @endforeach
    </div>
  @endif

</div>

@push('scripts')
<script>
function themeStore() {
    return {
        filterCat: '',
        search: '',
        matchesFilter(cat, name) {
            const catMatch  = !this.filterCat || cat === this.filterCat;
            const nameMatch = !this.search || name.toLowerCase().includes(this.search.toLowerCase());
            return catMatch && nameMatch;
        }
    };
}
</script>
@endpush
@endsection
