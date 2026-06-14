@extends('admin.layout')

@section('title', 'مرکز آموزش')

@push('head')
<style>
.edu-card { transition: box-shadow .15s, transform .15s; }
.edu-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.1); transform: translateY(-1px); }
.edu-module-pill { display:inline-flex; align-items:center; font-size:.7rem; border-radius:9999px; padding:.15rem .6rem; }
.edu-update-badge { font-size:.7rem; color:#6b7280; background:#f3f4f6; border-radius:4px; padding:.1rem .5rem; }
.edu-search-box { max-width:360px; }
.edu-path-card { border-right:4px solid; }
.edu-empty { text-align:center; padding:3rem 1rem; color:#6b7280; }
</style>
@endpush

@section('content')
<div dir="rtl" class="p-6 max-w-7xl mx-auto">

    {{-- ── Header ── --}}
    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">مرکز آموزش</h1>
            <p class="text-sm text-gray-500 mt-1">مستندات، راهنماهای گام‌به‌گام و مسیرهای یادگیری برای ویژگی‌های فعال سایت شما</p>
        </div>

        {{-- Search --}}
        <form method="GET" action="{{ route('admin.education-center.index') }}" class="edu-search-box flex gap-2">
            @if(request('module'))
                <input type="hidden" name="module" value="{{ request('module') }}">
            @endif
            <input type="text"
                   name="q"
                   value="{{ $search }}"
                   placeholder="جستجو در مستندات..."
                   class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-full focus:outline-none focus:border-blue-400">
            <button type="submit" class="bg-blue-600 text-white rounded-lg px-4 py-2 text-sm hover:bg-blue-700">جستجو</button>
        </form>
    </div>

    {{-- ── Module filter tabs ── --}}
    @if(!empty($byModule))
    <div class="flex gap-2 flex-wrap mb-6">
        <a href="{{ route('admin.education-center.index') }}"
           class="edu-module-pill text-sm px-3 py-1 rounded-full border {{ !$activeModule ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-300 hover:border-blue-400' }}">
            همه
        </a>
        @foreach(array_keys($byModule) as $mod)
        <a href="{{ route('admin.education-center.index', ['module' => $mod]) }}"
           class="edu-module-pill text-sm px-3 py-1 rounded-full border {{ $activeModule === $mod ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-300 hover:border-blue-400' }}">
            {{ $mod }}
        </a>
        @endforeach
    </div>
    @endif

    {{-- ── Learning Paths ── --}}
    @if(!empty($learningPaths) && !$search && !$activeModule)
    <section class="mb-8">
        <h2 class="text-lg font-semibold text-gray-700 mb-3">
            <span class="inline-block w-1 h-5 bg-blue-500 rounded ml-2 align-middle"></span>
            مسیرهای یادگیری
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach($learningPaths as $path)
            <div class="edu-path-card bg-white rounded-xl border border-gray-200 p-4"
                 style="border-right-color: #3b82f6;">
                <h3 class="font-semibold text-gray-800 text-sm mb-1">{{ $path['title'] ?? '' }}</h3>
                <p class="text-xs text-gray-500 mb-2">{{ $path['description'] ?? '' }}</p>
                <div class="flex items-center justify-between">
                    @if(!empty($path['target_roles']))
                    <span class="edu-module-pill bg-purple-50 text-purple-700 border border-purple-200">
                        {{ implode('، ', $path['target_roles']) }}
                    </span>
                    @endif
                    @if(!empty($path['estimated_minutes']))
                    <span class="text-xs text-gray-400">{{ $path['estimated_minutes'] }} دقیقه</span>
                    @endif
                </div>
                @if(!empty($path['steps']))
                <div class="mt-3 space-y-1">
                    @foreach(array_slice($path['steps'], 0, 3) as $step)
                    <div class="flex items-center gap-2 text-xs text-gray-600">
                        <span class="w-5 h-5 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-xs shrink-0">
                            {{ $step['step_number'] ?? '' }}
                        </span>
                        {{ $step['title'] ?? '' }}
                    </div>
                    @endforeach
                    @if(count($path['steps']) > 3)
                    <div class="text-xs text-gray-400 pr-7">و {{ count($path['steps']) - 3 }} مرحله دیگر...</div>
                    @endif
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- ── Articles ── --}}
    <section>
        <h2 class="text-lg font-semibold text-gray-700 mb-3">
            <span class="inline-block w-1 h-5 bg-green-500 rounded ml-2 align-middle"></span>
            مستندات و راهنماها
            @if(!empty($articles))
            <span class="text-sm font-normal text-gray-400">({{ count($articles) }} مقاله)</span>
            @endif
        </h2>

        @if(empty($articles))
            <div class="edu-empty bg-white rounded-xl border border-dashed border-gray-300">
                <x-core-ui::icon name="file-text" class="w-12 h-12 mx-auto text-gray-400 mb-3" />
                @if($search)
                    <p class="text-gray-500">نتیجه‌ای برای «{{ $search }}» یافت نشد.</p>
                    <a href="{{ route('admin.education-center.index') }}" class="text-blue-500 text-sm mt-1 inline-block">پاک کردن جستجو</a>
                @else
                    <p class="text-gray-500">در حال حاضر مستندی برای نمایش وجود ندارد.</p>
                    <p class="text-xs text-gray-400 mt-1">مستندات بر اساس ویژگی‌های فعال سایت شما به‌طور خودکار به‌روز می‌شوند.</p>
                @endif
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach($articles as $article)
                <a href="{{ route('admin.education-center.show', $article['slug'] ?? '') }}"
                   class="edu-card bg-white rounded-xl border border-gray-200 p-4 block hover:no-underline">
                    {{-- Module badge --}}
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <span class="edu-module-pill bg-blue-50 text-blue-600 border border-blue-200 text-xs">
                            {{ $article['module_slug'] ?? '' }}
                        </span>
                        {{-- Rule 10: Last Updated badge --}}
                        <span class="edu-update-badge">
                            {{ $article['last_updated_label'] ?? ('آخرین بروزرسانی: ' . (isset($article['updated_at']) ? \Carbon\Carbon::parse($article['updated_at'])->format('Y-m-d') : '—')) }}
                        </span>
                    </div>

                    <h3 class="font-semibold text-gray-800 text-sm leading-relaxed mb-2 line-clamp-2">
                        {{ $article['title'] ?? '' }}
                    </h3>

                    @if(!empty($article['excerpt']))
                    <p class="text-xs text-gray-500 leading-relaxed line-clamp-3">
                        {{ strip_tags($article['excerpt']) }}
                    </p>
                    @endif

                    <div class="flex items-center gap-2 mt-3 pt-3 border-t border-gray-100">
                        <x-core-ui::icon name="chevron-right" class="w-3.5 h-3.5 text-gray-400" />
                        <span class="text-xs text-blue-500">مشاهده مستند</span>
                    </div>
                </a>
                @endforeach
            </div>
        @endif
    </section>

</div>
@endsection
