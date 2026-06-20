@extends('admin.layout')
@section('title', 'مدیریت محتوا')

@section('content')
<div class="p-6 space-y-6" dir="rtl">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">مدیریت محتوا</h1>
            <p class="text-sm text-gray-500 mt-1">مقاله، آموزش، فرآیند کاری، محتوای محصول و راهنما</p>
        </div>
        <div class="flex gap-2">
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium text-sm transition">
                    <x-core-ui::icon name="file-text" class="w-4 h-4" />
                    محتوای جدید
                    <x-core-ui::icon name="file-text" class="w-4 h-4" />
                </button>
                <div x-show="open" @click.outside="open=false" x-cloak
                    class="absolute left-0 mt-2 w-52 bg-white rounded-xl shadow-lg border border-gray-100 z-30 py-2">
                    @foreach(\App\Models\SiteContent::$TYPES as $key => $label)
                        @if($key !== 'help')
                        <a href="{{ route('admin.site-content.create', ['type' => $key]) }}"
                            class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-700">
                            @if($key === 'article') 📄
                            @elseif($key === 'training') 🎓
                            @elseif($key === 'workflow') 🔄
                            @elseif($key === 'procedure') 📋
                            @elseif($key === 'product_content') 🛍️
                            @endif
                            {{ $label }}
                        </a>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-100 p-4">
            <div class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</div>
            <div class="text-sm text-gray-500">کل محتوا</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-4">
            <div class="text-2xl font-bold text-green-600">{{ $stats['published'] }}</div>
            <div class="text-sm text-gray-500">منتشرشده</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-4">
            <div class="text-2xl font-bold text-yellow-500">{{ $stats['draft'] }}</div>
            <div class="text-sm text-gray-500">پیش‌نویس</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-4">
            <div class="text-2xl font-bold text-purple-600">{{ $stats['ai'] }}</div>
            <div class="text-sm text-gray-500">تولیدشده با AI</div>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="bg-white rounded-xl border border-gray-100 p-4 flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-40">
            <label class="block text-xs text-gray-500 mb-1">جستجو</label>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="عنوان محتوا..."
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-400">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">نوع</label>
            <select name="type" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-400">
                <option value="">همه</option>
                @foreach(\App\Models\SiteContent::$TYPES as $key => $label)
                    @if($key !== 'help')
                    <option value="{{ $key }}" @selected(request('type') === $key)>{{ $label }}</option>
                    @endif
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">وضعیت</label>
            <select name="status" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-400">
                <option value="">همه</option>
                <option value="draft" @selected(request('status') === 'draft')>پیش‌نویس</option>
                <option value="published" @selected(request('status') === 'published')>منتشرشده</option>
                <option value="archived" @selected(request('status') === 'archived')>آرشیو</option>
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-700">اعمال فیلتر</button>
        @if(request()->hasAny(['q','type','status']))
        <a href="{{ route('admin.site-content.index') }}" class="px-4 py-2 text-sm text-gray-500 hover:text-gray-700">پاک کردن</a>
        @endif
    </form>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
        @if($query->isEmpty())
        <div class="py-20 text-center">
            <div class="text-5xl mb-4">📝</div>
            <div class="text-gray-500">هنوز محتوایی ایجاد نشده</div>
            <a href="{{ route('admin.site-content.create') }}"
                class="mt-4 inline-block px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg">اولین محتوا را بساز</a>
        </div>
        @else
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="text-right px-4 py-3 font-medium text-gray-600">عنوان</th>
                    <th class="text-right px-4 py-3 font-medium text-gray-600">نوع</th>
                    <th class="text-right px-4 py-3 font-medium text-gray-600">وضعیت</th>
                    <th class="text-right px-4 py-3 font-medium text-gray-600">تاریخ</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($query as $item)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3">
                        <div class="font-medium text-gray-900">{{ $item->title }}</div>
                        @if($item->ai_generated)
                        <span class="inline-flex items-center gap-1 text-xs text-purple-600 mt-0.5">✨ AI</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700">
                            {{ $item->typeName() }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        @if($item->status === 'published')
                        <span class="px-2 py-1 rounded-full text-xs bg-green-50 text-green-700">منتشرشده</span>
                        @elseif($item->status === 'draft')
                        <span class="px-2 py-1 rounded-full text-xs bg-yellow-50 text-yellow-700">پیش‌نویس</span>
                        @else
                        <span class="px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-500">آرشیو</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ \App\Helpers\Jalali::formatFa($item->created_at, 'Y/m/d') }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2 justify-end">
                            @if($item->status !== 'published')
                            <form method="POST" action="{{ route('admin.site-content.publish', $item) }}">
                                @csrf
                                <button class="text-xs text-green-600 hover:text-green-800">انتشار</button>
                            </form>
                            @endif
                            <a href="{{ route('admin.site-content.edit', $item) }}" class="text-xs text-indigo-600 hover:text-indigo-800">ویرایش</a>
                            <form method="POST" action="{{ route('admin.site-content.destroy', $item) }}"
                                onsubmit="return confirm('حذف شود؟')">
                                @csrf @method('DELETE')
                                <button class="text-xs text-red-500 hover:text-red-700">حذف</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-4">{{ $query->links() }}</div>
        @endif
    </div>
</div>
@endsection

