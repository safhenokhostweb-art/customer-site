@extends('admin.layout')

@section('title', isset($article) ? 'ویرایش مقاله' : 'مقاله جدید')

@section('content')
<div dir="rtl" class="p-6 max-w-4xl mx-auto">

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.education-center.manage') }}"
           class="text-gray-400 hover:text-gray-600 p-1 rounded hover:bg-gray-100 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-gray-800">
                {{ isset($article) ? 'ویرایش مقاله' : 'مقاله جدید' }}
            </h1>
            @if(isset($article))
            <p class="text-xs text-gray-400 mt-0.5">{{ $article['title'] ?? '' }}</p>
            @endif
        </div>
    </div>

    @if($errors->any())
    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 rounded-lg px-4 py-3 text-sm">
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST"
          action="{{ isset($article) ? route('admin.education-center.articles.update', $article['id']) : route('admin.education-center.articles.store') }}"
          class="space-y-5">
        @csrf
        @if(isset($article)) @method('PUT') @endif

        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-5">

            {{-- عنوان --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    عنوان مقاله <span class="text-red-500">*</span>
                </label>
                <input type="text" name="title" value="{{ old('title', $article['title'] ?? '') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-200"
                       placeholder="عنوان مقاله را وارد کنید..." required>
            </div>

            {{-- خلاصه --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">خلاصه</label>
                <textarea name="excerpt" rows="2"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-200 resize-none"
                          placeholder="خلاصه‌ای کوتاه از محتوا...">{{ old('excerpt', $article['excerpt'] ?? '') }}</textarea>
            </div>

            {{-- ماژول + نوع + وضعیت --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        ماژول <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="module_slug" value="{{ old('module_slug', $article['module_slug'] ?? '') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-400"
                           placeholder="مثال: shop, support" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">نوع محتوا</label>
                    <select name="content_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-400">
                        @foreach(['article' => 'مقاله', 'guide' => 'راهنما', 'tutorial' => 'آموزش', 'faq' => 'سوالات متداول'] as $val => $label)
                        <option value="{{ $val }}" {{ old('content_type', $article['content_type'] ?? 'article') === $val ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">وضعیت</label>
                    <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-400">
                        <option value="draft"     {{ old('status', $article['status'] ?? 'draft') === 'draft'     ? 'selected' : '' }}>پیش‌نویس</option>
                        <option value="published" {{ old('status', $article['status'] ?? 'draft') === 'published' ? 'selected' : '' }}>منتشر شده</option>
                        @if(isset($article))
                        <option value="archived"  {{ ($article['status'] ?? '') === 'archived' ? 'selected' : '' }}>بایگانی</option>
                        @endif
                    </select>
                </div>
            </div>

            {{-- متن اصلی --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    متن مقاله <span class="text-red-500">*</span>
                </label>
                <textarea name="body" rows="14"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-200 resize-y"
                          placeholder="محتوای کامل مقاله را اینجا بنویسید..." required>{{ old('body', $article['body'] ?? '') }}</textarea>
                <p class="text-xs text-gray-400 mt-1">HTML پشتیبانی می‌شود.</p>
            </div>

        </div>

        {{-- دکمه‌ها --}}
        <div class="flex items-center gap-3 justify-end">
            <a href="{{ route('admin.education-center.manage') }}"
               class="px-4 py-2 rounded-lg border border-gray-300 text-sm text-gray-600 hover:bg-gray-50 transition">
                انصراف
            </a>
            <button type="submit"
                    class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                {{ isset($article) ? 'ذخیره تغییرات' : 'ایجاد مقاله' }}
            </button>
        </div>
    </form>
</div>
@endsection
