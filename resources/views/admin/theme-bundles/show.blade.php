@extends('admin.layout')

@section('title', $bundle['name'] ?? 'جزئیات باندل')

@section('content')
<div class="p-6 space-y-6" dir="rtl">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-gray-400">
        <a href="{{ route('admin.theme-bundles.index') }}" class="hover:text-gray-600">باندل‌ها</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
        <span class="text-gray-700 font-medium">{{ $bundle['name'] }}</span>
    </div>

    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-800 rounded-xl px-4 py-3 text-sm">{{ session('error') }}</div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        {{-- Main content --}}
        <div class="xl:col-span-2 space-y-6">

            {{-- Hero --}}
            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                @if($bundle['thumbnail'] ?? null)
                    <div class="h-56 bg-gray-100">
                        <img src="{{ $bundle['thumbnail'] }}" alt="{{ $bundle['name'] }}"
                             class="w-full h-full object-cover">
                    </div>
                @else
                    <div class="h-56 bg-linear-to-br from-violet-100 to-indigo-100 flex items-center justify-center">
                        <svg class="w-20 h-20 text-violet-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                @endif
                <div class="p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                @if($bundle['is_featured'] ?? false)
                                    <span class="bg-amber-100 text-amber-700 text-xs font-bold px-2 py-0.5 rounded-full">ویژه</span>
                                @endif
                                @if($bundle['has_purchased'] ?? false)
                                    <span class="bg-emerald-100 text-emerald-700 text-xs font-bold px-2 py-0.5 rounded-full flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        خریداری شده
                                    </span>
                                @endif
                            </div>
                            <h1 class="text-2xl font-bold text-gray-900">{{ $bundle['name'] }}</h1>
                            @if($bundle['description'] ?? null)
                                <p class="text-gray-500 mt-2">{{ $bundle['description'] }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Themes in bundle --}}
            <div class="bg-white rounded-2xl border border-gray-200 p-6 space-y-4">
                <h2 class="font-semibold text-gray-800 flex items-center gap-2">
                    <svg class="w-5 h-5 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                    </svg>
                    قالب‌های این باندل
                    <span class="text-sm text-gray-400 font-normal">({{ $bundle['theme_count'] ?? count($bundle['themes'] ?? []) }} قالب)</span>
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($bundle['themes'] ?? [] as $theme)
                        <div class="flex items-center gap-3 p-3 border border-gray-100 rounded-xl">
                            @if($theme['thumbnail'] ?? null)
                                <img src="{{ $theme['thumbnail'] }}" alt="{{ $theme['name'] }}"
                                     class="w-14 h-14 rounded-lg object-cover shrink-0">
                            @else
                                <div class="w-14 h-14 bg-indigo-50 rounded-lg flex items-center justify-center shrink-0">
                                    <svg class="w-6 h-6 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5z"/>
                                    </svg>
                                </div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-gray-800 text-sm">{{ $theme['name'] }}</p>
                                <p class="text-xs text-gray-400">{{ num_fa($theme['price'] ?? 0) }} ریال</p>
                            </div>
                            <div class="flex items-center gap-1.5 shrink-0">
                                <a href="{{ route('admin.theme-marketplace.show', $theme['id']) }}"
                                   class="text-xs text-indigo-600 hover:text-indigo-800 whitespace-nowrap">
                                    مشاهده
                                </a>
                                @if($bundle['has_purchased'] ?? false)
                                {{-- GAP-M04: Install button per theme in purchased bundle --}}
                                <form method="POST"
                                      action="{{ route('admin.theme-marketplace.install', $theme['id']) }}"
                                      onsubmit="return confirm('قالب «{{ $theme['name'] }}» نصب و فعال شود؟')">
                                    @csrf
                                    <button type="submit"
                                            class="text-xs bg-indigo-600 text-white px-2 py-1 rounded-lg hover:bg-indigo-700 whitespace-nowrap">
                                        نصب
                                    </button>
                                </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>

        {{-- Sidebar --}}
        <div class="space-y-4">

            {{-- Pricing card --}}
            <div class="bg-white rounded-2xl border border-gray-200 p-6 space-y-4 sticky top-6">
                <div class="text-center">
                    @if($bundle['is_free'] ?? false)
                        <p class="text-3xl font-bold text-emerald-600">رایگان</p>
                    @else
                        <p class="text-3xl font-bold text-violet-700">{{ num_fa($bundle['price']) }} ریال</p>
                        @if(($bundle['original_total'] ?? 0) > ($bundle['price'] ?? 0))
                            <p class="text-sm text-gray-400 line-through mt-0.5">{{ num_fa($bundle['original_total']) }} ریال</p>
                        @endif
                    @endif
                    @if(($bundle['discount_pct'] ?? 0) > 0)
                        <span class="mt-1 inline-block bg-amber-100 text-amber-700 text-sm font-bold px-3 py-0.5 rounded-full">
                            {{ $bundle['discount_pct'] }}% صرفه‌جویی
                        </span>
                    @endif
                </div>

                <div class="border-t border-gray-100 pt-4 space-y-2 text-sm text-gray-600">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        {{ $bundle['theme_count'] ?? count($bundle['themes'] ?? []) }} قالب در یک خرید
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        لایسنس دائمی برای همه قالب‌ها
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        نصب و فعال‌سازی فوری
                    </div>
                </div>

                @if($bundle['has_purchased'] ?? false)
                    <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3 text-sm text-emerald-700 text-center font-medium">
                        این باندل خریداری شده — قالب مورد نظر را از لیست نصب کنید.
                    </div>
                    @php $firstThemeId = $bundle['themes'][0]['id'] ?? null; @endphp
                    @if($firstThemeId)
                    {{-- GAP-M04: Install first theme in bundle --}}
                    <form method="POST"
                          action="{{ route('admin.theme-marketplace.install', $firstThemeId) }}"
                          onsubmit="return confirm('اولین قالب باندل ({{ $bundle['themes'][0]['name'] ?? '' }}) نصب و فعال شود؟')">
                        @csrf
                        <button type="submit"
                                class="block w-full text-center py-2.5 bg-violet-600 text-white rounded-xl font-bold text-sm hover:bg-violet-700">
                            نصب اولین قالب باندل
                        </button>
                    </form>
                    @endif
                    <a href="{{ route('admin.theme-marketplace.my-licenses') }}"
                       class="block w-full text-center py-2.5 bg-emerald-600 text-white rounded-xl font-bold text-sm hover:bg-emerald-700">
                        مشاهده لایسنس‌های من
                    </a>
                @elseif($bundle['all_licensed'] ?? false)
                    <div class="bg-indigo-50 border border-indigo-200 rounded-xl px-4 py-3 text-sm text-indigo-700 text-center">
                        همه قالب‌های این باندل لایسنس دارید.
                    </div>
                @else
                    <form action="{{ route('admin.theme-bundles.purchase', $bundle['id']) }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="w-full py-3 bg-violet-600 text-white rounded-xl font-bold text-sm hover:bg-violet-700 transition">
                            @if($bundle['is_free'] ?? false)
                                دریافت رایگان
                            @else
                                خرید باندل
                            @endif
                        </button>
                    </form>
                @endif

                <a href="{{ route('admin.theme-bundles.index') }}"
                   class="block w-full text-center py-2 text-sm text-gray-500 hover:text-gray-700">
                    بازگشت به باندل‌ها
                </a>
            </div>

        </div>
    </div>

</div>
@endsection
