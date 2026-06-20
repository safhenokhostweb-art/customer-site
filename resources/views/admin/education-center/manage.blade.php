@extends('admin.layout')

@section('title', 'مدیریت محتوای آموزشی')

@push('head')
<style>
.edu-tab { border-bottom: 2px solid transparent; padding: .5rem 1rem; font-size: .875rem; color: #6b7280; cursor: pointer; transition: color .15s, border-color .15s; white-space: nowrap; }
.edu-tab.active { border-bottom-color: #2563eb; color: #2563eb; font-weight: 600; }
.edu-tab:hover:not(.active) { color: #1d4ed8; }
.edu-status-badge { display: inline-flex; align-items: center; font-size: .7rem; border-radius: 9999px; padding: .15rem .65rem; font-weight: 500; }
.edu-status-draft     { background: #fef9c3; color: #854d0e; }
.edu-status-published { background: #dcfce7; color: #166534; }
.edu-status-archived  { background: #f3f4f6; color: #6b7280; }
.edu-status-active    { background: #dcfce7; color: #166534; }
.edu-status-inactive  { background: #fee2e2; color: #991b1b; }
.edu-row { transition: background .1s; }
.edu-row:hover { background: #f8fafc; }
</style>
@endpush

@section('content')
<div dir="rtl" class="p-6 max-w-7xl mx-auto">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <x-core-ui::icon name="book-open" class="w-6 h-6 text-blue-600" />
                مدیریت محتوای آموزشی
            </h1>
            <p class="text-sm text-gray-500 mt-1">ایجاد، ویرایش و حذف مقالات، راهنماها و مسیرهای یادگیری</p>
        </div>
        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('admin.education-center.index') }}"
               class="inline-flex items-center gap-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition">
                <x-core-ui::icon name="eye" class="w-4 h-4" />
                نمایش عمومی
            </a>
            @if($tab === 'articles' || !$tab)
            <a href="{{ route('admin.education-center.articles.create') }}"
               class="inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                <x-core-ui::icon name="plus" class="w-4 h-4" />
                مقاله جدید
            </a>
            @elseif($tab === 'walkthroughs')
            <a href="{{ route('admin.education-center.walkthroughs.create') }}"
               class="inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                <x-core-ui::icon name="plus" class="w-4 h-4" />
                راهنمای جدید
            </a>
            @elseif($tab === 'learning-paths')
            <a href="{{ route('admin.education-center.learning-paths.create') }}"
               class="inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                <x-core-ui::icon name="plus" class="w-4 h-4" />
                مسیر یادگیری جدید
            </a>
            @endif
        </div>
    </div>

    {{-- Flash --}}
    @if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm flex items-center gap-2">
        <x-core-ui::icon name="check" class="w-4 h-4 text-green-500 shrink-0" />
        {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 rounded-lg px-4 py-3 text-sm">
        {{ $errors->first() }}
    </div>
    @endif

    {{-- Tabs --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="flex border-b border-gray-200 overflow-x-auto">
            <a href="{{ route('admin.education-center.manage', ['tab' => 'articles']) }}"
               class="edu-tab {{ $tab === 'articles' || !$tab ? 'active' : '' }}">
                <span class="flex items-center gap-1.5">
                    <x-core-ui::icon name="file-text" class="w-4 h-4" />
                    مقالات
                    @if(!empty($articles))
                    <span class="bg-blue-100 text-blue-700 rounded-full text-xs px-1.5">{{ count($articles) }}</span>
                    @endif
                </span>
            </a>
            <a href="{{ route('admin.education-center.manage', ['tab' => 'walkthroughs']) }}"
               class="edu-tab {{ $tab === 'walkthroughs' ? 'active' : '' }}">
                <span class="flex items-center gap-1.5">
                    <x-core-ui::icon name="walkthrough" class="w-4 h-4" />
                    راهنماها
                    @if(!empty($walkthroughs))
                    <span class="bg-blue-100 text-blue-700 rounded-full text-xs px-1.5">{{ count($walkthroughs) }}</span>
                    @endif
                </span>
            </a>
            <a href="{{ route('admin.education-center.manage', ['tab' => 'learning-paths']) }}"
               class="edu-tab {{ $tab === 'learning-paths' ? 'active' : '' }}">
                <span class="flex items-center gap-1.5">
                    <x-core-ui::icon name="learning-path" class="w-4 h-4" />
                    مسیرهای یادگیری
                    @if(!empty($learningPaths))
                    <span class="bg-blue-100 text-blue-700 rounded-full text-xs px-1.5">{{ count($learningPaths) }}</span>
                    @endif
                </span>
            </a>
        </div>

        {{-- ── Articles Tab ── --}}
        @if($tab === 'articles' || !$tab)
        <div class="p-4">
            {{-- Filter bar --}}
            <form method="GET" action="{{ route('admin.education-center.manage') }}" class="flex gap-2 mb-4 flex-wrap">
                <input type="hidden" name="tab" value="articles">
                <select name="status" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-blue-400">
                    <option value="">همه وضعیت‌ها</option>
                    <option value="draft"     {{ $status === 'draft'     ? 'selected' : '' }}>پیش‌نویس</option>
                    <option value="published" {{ $status === 'published' ? 'selected' : '' }}>منتشر شده</option>
                    <option value="archived"  {{ $status === 'archived'  ? 'selected' : '' }}>بایگانی</option>
                </select>
                <input type="text" name="q" value="{{ $q }}" placeholder="جستجو..." class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-blue-400 flex-1 min-w-[150px]">
                <button type="submit" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-sm">فیلتر</button>
            </form>

            @if(empty($articles))
            <div class="text-center py-12 text-gray-400">
                <x-core-ui::icon name="file-text" class="w-12 h-12 mx-auto mb-3 text-gray-400" />
                <p>مقاله‌ای وجود ندارد.</p>
                <a href="{{ route('admin.education-center.articles.create') }}" class="text-blue-600 text-sm mt-2 inline-block">+ ایجاد اولین مقاله</a>
            </div>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-right text-xs text-gray-500 border-b border-gray-100">
                            <th class="pb-2 pr-2 font-medium">عنوان</th>
                            <th class="pb-2 px-2 font-medium">ماژول</th>
                            <th class="pb-2 px-2 font-medium">نوع</th>
                            <th class="pb-2 px-2 font-medium">وضعیت</th>
                            <th class="pb-2 px-2 font-medium">آپدیت</th>
                            <th class="pb-2 pl-2 font-medium">عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($articles as $article)
                        <tr class="edu-row">
                            <td class="py-2.5 pr-2">
                                <span class="font-medium text-gray-800 line-clamp-1">{{ $article['title'] ?? '—' }}</span>
                                @if(!empty($article['excerpt']))
                                <span class="text-xs text-gray-400 block line-clamp-1 mt-0.5">{{ strip_tags($article['excerpt']) }}</span>
                                @endif
                            </td>
                            <td class="py-2.5 px-2">
                                <span class="bg-blue-50 text-blue-700 text-xs px-2 py-0.5 rounded-full">{{ $article['module_slug'] ?? '—' }}</span>
                            </td>
                            <td class="py-2.5 px-2 text-gray-500 text-xs">{{ $article['content_type'] ?? 'article' }}</td>
                            <td class="py-2.5 px-2">
                                @php $st = $article['status'] ?? 'draft'; @endphp
                                <span class="edu-status-badge edu-status-{{ $st }}">
                                    {{ $st === 'draft' ? 'پیش‌نویس' : ($st === 'published' ? 'منتشر' : 'بایگانی') }}
                                </span>
                            </td>
                            <td class="py-2.5 px-2 text-gray-400 text-xs">
                                {{ isset($article['updated_at']) ? \App\Helpers\Jalali::formatFa(\Carbon\Carbon::parse($article['updated_at']), 'Y/m/d') : '—' }}
                            </td>
                            <td class="py-2.5 pl-2">
                                <div class="flex items-center gap-1 justify-end">
                                    @if(($article['status'] ?? '') === 'draft')
                                    <form method="POST" action="{{ route('admin.education-center.articles.publish', $article['id']) }}" class="inline">
                                        @csrf
                                        <button type="submit" title="انتشار" class="text-green-600 hover:text-green-800 p-1 rounded hover:bg-green-50 transition">
                                            <x-core-ui::icon name="check-circle" class="w-4 h-4" />
                                        </button>
                                    </form>
                                    @endif
                                    <a href="{{ route('admin.education-center.articles.edit', $article['id']) }}" title="ویرایش"
                                       class="text-blue-600 hover:text-blue-800 p-1 rounded hover:bg-blue-50 transition">
                                        <x-core-ui::icon name="edit" class="w-4 h-4" />
                                    </a>
                                    <form method="POST" action="{{ route('admin.education-center.articles.destroy', $article['id']) }}" class="inline"
                                          onsubmit="return confirm('این مقاله حذف شود؟')">
                                        @csrf @method('DELETE')
                                        <button type="submit" title="حذف" class="text-red-500 hover:text-red-700 p-1 rounded hover:bg-red-50 transition">
                                            <x-core-ui::icon name="trash" class="w-4 h-4" />
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
        @endif

        {{-- ── Walkthroughs Tab ── --}}
        @if($tab === 'walkthroughs')
        <div class="p-4">
            @if(empty($walkthroughs))
            <div class="text-center py-12 text-gray-400">
                <x-core-ui::icon name="walkthrough" class="w-12 h-12 mx-auto mb-3 text-gray-400" />
                <p>راهنمایی وجود ندارد.</p>
                <a href="{{ route('admin.education-center.walkthroughs.create') }}" class="text-blue-600 text-sm mt-2 inline-block">+ ایجاد اولین راهنما</a>
            </div>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-right text-xs text-gray-500 border-b border-gray-100">
                            <th class="pb-2 pr-2 font-medium">عنوان</th>
                            <th class="pb-2 px-2 font-medium">ماژول</th>
                            <th class="pb-2 px-2 font-medium">مراحل</th>
                            <th class="pb-2 px-2 font-medium">وضعیت</th>
                            <th class="pb-2 pl-2 font-medium">عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($walkthroughs as $wt)
                        <tr class="edu-row">
                            <td class="py-2.5 pr-2 font-medium text-gray-800">{{ $wt['title'] ?? '—' }}</td>
                            <td class="py-2.5 px-2">
                                <span class="bg-purple-50 text-purple-700 text-xs px-2 py-0.5 rounded-full">{{ $wt['module_slug'] ?? '—' }}</span>
                            </td>
                            <td class="py-2.5 px-2 text-gray-500">{{ count($wt['steps'] ?? []) }} مرحله</td>
                            <td class="py-2.5 px-2">
                                <span class="edu-status-badge {{ ($wt['is_active'] ?? false) ? 'edu-status-active' : 'edu-status-inactive' }}">
                                    {{ ($wt['is_active'] ?? false) ? 'فعال' : 'غیرفعال' }}
                                </span>
                            </td>
                            <td class="py-2.5 pl-2">
                                <div class="flex items-center gap-1 justify-end">
                                    <a href="{{ route('admin.education-center.walkthroughs.edit', $wt['id']) }}" title="ویرایش"
                                       class="text-blue-600 hover:text-blue-800 p-1 rounded hover:bg-blue-50 transition">
                                        <x-core-ui::icon name="edit" class="w-4 h-4" />
                                    </a>
                                    <form method="POST" action="{{ route('admin.education-center.walkthroughs.destroy', $wt['id']) }}" class="inline"
                                          onsubmit="return confirm('این راهنما حذف شود؟')">
                                        @csrf @method('DELETE')
                                        <button type="submit" title="حذف" class="text-red-500 hover:text-red-700 p-1 rounded hover:bg-red-50 transition">
                                            <x-core-ui::icon name="trash" class="w-4 h-4" />
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
        @endif

        {{-- ── Learning Paths Tab ── --}}
        @if($tab === 'learning-paths')
        <div class="p-4">
            @if(empty($learningPaths))
            <div class="text-center py-12 text-gray-400">
                <x-core-ui::icon name="learning-path" class="w-12 h-12 mx-auto mb-3 text-gray-400" />
                <p>مسیر یادگیری وجود ندارد.</p>
                <a href="{{ route('admin.education-center.learning-paths.create') }}" class="text-blue-600 text-sm mt-2 inline-block">+ ایجاد اولین مسیر</a>
            </div>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-right text-xs text-gray-500 border-b border-gray-100">
                            <th class="pb-2 pr-2 font-medium">عنوان</th>
                            <th class="pb-2 px-2 font-medium">نوع</th>
                            <th class="pb-2 px-2 font-medium">زمان تخمینی</th>
                            <th class="pb-2 px-2 font-medium">وضعیت</th>
                            <th class="pb-2 pl-2 font-medium">عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($learningPaths as $path)
                        <tr class="edu-row">
                            <td class="py-2.5 pr-2">
                                <span class="font-medium text-gray-800">{{ $path['title'] ?? '—' }}</span>
                                @if(!empty($path['description']))
                                <span class="text-xs text-gray-400 block line-clamp-1 mt-0.5">{{ $path['description'] }}</span>
                                @endif
                            </td>
                            <td class="py-2.5 px-2">
                                <span class="bg-orange-50 text-orange-700 text-xs px-2 py-0.5 rounded-full">{{ $path['path_type'] ?? 'custom' }}</span>
                            </td>
                            <td class="py-2.5 px-2 text-gray-500 text-xs">
                                {{ $path['estimated_minutes'] ? $path['estimated_minutes'].' دقیقه' : '—' }}
                            </td>
                            <td class="py-2.5 px-2">
                                <span class="edu-status-badge {{ ($path['is_active'] ?? false) ? 'edu-status-active' : 'edu-status-inactive' }}">
                                    {{ ($path['is_active'] ?? false) ? 'فعال' : 'غیرفعال' }}
                                </span>
                            </td>
                            <td class="py-2.5 pl-2">
                                <div class="flex items-center gap-1 justify-end">
                                    <a href="{{ route('admin.education-center.learning-paths.edit', $path['id']) }}" title="ویرایش"
                                       class="text-blue-600 hover:text-blue-800 p-1 rounded hover:bg-blue-50 transition">
                                        <x-core-ui::icon name="edit" class="w-4 h-4" />
                                    </a>
                                    <form method="POST" action="{{ route('admin.education-center.learning-paths.destroy', $path['id']) }}" class="inline"
                                          onsubmit="return confirm('این مسیر یادگیری حذف شود؟')">
                                        @csrf @method('DELETE')
                                        <button type="submit" title="حذف" class="text-red-500 hover:text-red-700 p-1 rounded hover:bg-red-50 transition">
                                            <x-core-ui::icon name="trash" class="w-4 h-4" />
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
        @endif

    </div>
</div>
@endsection
