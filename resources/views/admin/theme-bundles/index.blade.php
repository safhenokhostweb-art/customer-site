@extends('admin.layout')

@section('title', 'باندل‌های قالب')

@section('content')
<div class="p-6 space-y-6" dir="rtl">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                <svg class="w-7 h-7 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                باندل‌های قالب
            </h1>
            <p class="text-sm text-gray-500 mt-1">خرید مجموعه قالب‌ها با تخفیف ویژه</p>
        </div>
        <a href="{{ route('admin.theme-marketplace.index') }}"
           class="text-sm text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5z"/>
            </svg>
            فروشگاه قالب‌ها
        </a>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-800 rounded-xl px-4 py-3 text-sm">{{ session('error') }}</div>
    @endif
    @if(isset($error))
        <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl px-4 py-3 text-sm">{{ $error }}</div>
    @endif
    @if(request('purchased'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl px-4 py-3 text-sm flex items-center gap-2">
            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            باندل با موفقیت خریداری شد! لایسنس‌های قالب‌ها صادر گردید.
        </div>
    @endif

    {{-- Empty state --}}
    @if(empty($bundles))
        <div class="bg-white rounded-2xl border border-gray-200 p-16 text-center">
            <div class="w-16 h-16 bg-violet-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-violet-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
            </div>
            <p class="text-gray-500 text-lg font-medium">باندلی موجود نیست</p>
            <p class="text-gray-400 text-sm mt-1">در حال حاضر هیچ باندل فعالی در دسترس نیست.</p>
        </div>
    @else
        {{-- Featured bundle banner --}}
        @php $featured = collect($bundles)->firstWhere('is_featured', true); @endphp
        @if($featured)
            <div class="bg-linear-to-l from-violet-600 to-indigo-600 rounded-2xl p-6 text-white relative overflow-hidden">
                <div class="absolute inset-0 opacity-10">
                    <svg class="w-full h-full" viewBox="0 0 400 200" fill="none">
                        <circle cx="350" cy="100" r="150" fill="white"/>
                        <circle cx="50" cy="50" r="80" fill="white"/>
                    </svg>
                </div>
                <div class="relative flex items-center justify-between gap-4">
                    <div>
                        <span class="text-xs bg-white/20 text-white px-2 py-0.5 rounded-full mb-2 inline-block">باندل ویژه</span>
                        <h2 class="text-xl font-bold">{{ $featured['name'] }}</h2>
                        <p class="text-white/80 text-sm mt-1">{{ $featured['description'] ?? '' }}</p>
                        <div class="flex items-center gap-4 mt-3">
                            <span class="text-2xl font-bold">
                                @if($featured['is_free'] ?? false)
                                    رایگان
                                @else
                                    {{ number_format($featured['price']) }} ریال
                                @endif
                            </span>
                            @if(($featured['discount_pct'] ?? 0) > 0)
                                <span class="bg-amber-400 text-amber-900 text-xs font-bold px-2 py-0.5 rounded-full">
                                    {{ $featured['discount_pct'] }}% تخفیف
                                </span>
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('admin.theme-bundles.show', $featured['id']) }}"
                       class="shrink-0 bg-white text-violet-700 font-bold px-5 py-2.5 rounded-xl hover:bg-violet-50 text-sm">
                        مشاهده باندل
                    </a>
                </div>
            </div>
        @endif

        {{-- Bundles grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            @foreach($bundles as $bundle)
                <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden hover:shadow-md transition flex flex-col">
                    {{-- Thumbnail --}}
                    @if($bundle['thumbnail'] ?? null)
                        <div class="h-44 bg-gray-100 overflow-hidden relative">
                            <img src="{{ $bundle['thumbnail'] }}" alt="{{ $bundle['name'] }}"
                                 class="w-full h-full object-cover">
                            @if($bundle['is_featured'] ?? false)
                                <span class="absolute top-2 right-2 bg-amber-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">ویژه</span>
                            @endif
                            @if($bundle['has_purchased'] ?? false)
                                <span class="absolute top-2 left-2 bg-emerald-500 text-white text-xs font-bold px-2 py-0.5 rounded-full flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    خریداری شده
                                </span>
                            @endif
                        </div>
                    @else
                        <div class="h-44 bg-linear-to-br from-violet-100 to-indigo-100 flex items-center justify-center relative">
                            <svg class="w-14 h-14 text-violet-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                            @if($bundle['is_featured'] ?? false)
                                <span class="absolute top-2 right-2 bg-amber-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">ویژه</span>
                            @endif
                            @if($bundle['has_purchased'] ?? false)
                                <span class="absolute top-2 left-2 bg-emerald-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">خریداری شده</span>
                            @endif
                        </div>
                    @endif

                    <div class="p-5 flex flex-col flex-1 space-y-3">
                        <div>
                            <h3 class="font-bold text-gray-900 text-lg">{{ $bundle['name'] }}</h3>
                            @if($bundle['description'] ?? null)
                                <p class="text-sm text-gray-500 mt-1 line-clamp-2">{{ $bundle['description'] }}</p>
                            @endif
                        </div>

                        {{-- Themes included --}}
                        <div class="flex flex-wrap gap-1">
                            @foreach($bundle['themes'] ?? [] as $theme)
                                <span class="text-xs bg-indigo-50 text-indigo-700 border border-indigo-100 px-2 py-0.5 rounded-full">
                                    {{ $theme['name'] }}
                                </span>
                            @endforeach
                        </div>

                        {{-- Price --}}
                        <div class="flex items-end justify-between pt-2 border-t border-gray-100 mt-auto">
                            <div>
                                @if($bundle['is_free'] ?? false)
                                    <p class="text-xl font-bold text-emerald-600">رایگان</p>
                                @else
                                    <p class="text-xl font-bold text-violet-700">{{ number_format($bundle['price']) }} ریال</p>
                                    @if(($bundle['original_total'] ?? 0) > ($bundle['price'] ?? 0))
                                        <p class="text-xs text-gray-400 line-through">{{ number_format($bundle['original_total']) }} ریال</p>
                                    @endif
                                @endif
                                @if(($bundle['discount_pct'] ?? 0) > 0)
                                    <span class="text-xs bg-amber-100 text-amber-700 font-bold px-1.5 py-0.5 rounded">{{ $bundle['discount_pct'] }}% تخفیف</span>
                                @endif
                            </div>
                            <div class="text-xs text-gray-400">
                                {{ $bundle['theme_count'] }} قالب
                            </div>
                        </div>

                        {{-- CTA --}}
                        <a href="{{ route('admin.theme-bundles.show', $bundle['id']) }}"
                           class="block w-full text-center py-2.5 rounded-xl font-bold text-sm
                               {{ ($bundle['has_purchased'] ?? false) ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'bg-violet-600 text-white hover:bg-violet-700' }}">
                            {{ ($bundle['has_purchased'] ?? false) ? 'مشاهده جزئیات' : 'خرید باندل' }}
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>
@endsection
