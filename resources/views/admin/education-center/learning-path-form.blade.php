@extends('admin.layout')

@section('title', isset($path) ? 'ویرایش مسیر یادگیری' : 'مسیر یادگیری جدید')

@section('content')
<div dir="rtl" class="p-6 max-w-3xl mx-auto">

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.education-center.manage', ['tab' => 'learning-paths']) }}"
           class="text-gray-400 hover:text-gray-600 p-1 rounded hover:bg-gray-100 transition">
            <x-core-ui::icon name="chevron-right" class="w-5 h-5" />
        </a>
        <h1 class="text-xl font-bold text-gray-800">
            {{ isset($path) ? 'ویرایش مسیر یادگیری' : 'مسیر یادگیری جدید' }}
        </h1>
    </div>

    @if($errors->any())
    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 rounded-lg px-4 py-3 text-sm">
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form method="POST"
          action="{{ isset($path) ? route('admin.education-center.learning-paths.update', $path['id']) : route('admin.education-center.learning-paths.store') }}"
          class="space-y-5">
        @csrf
        @if(isset($path)) @method('PUT') @endif

        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-5">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">عنوان <span class="text-red-500">*</span></label>
                <input type="text" name="title" value="{{ old('title', $path['title'] ?? '') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-400"
                       required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">توضیحات</label>
                <textarea name="description" rows="3"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-400 resize-none">{{ old('description', $path['description'] ?? '') }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">نوع مسیر</label>
                    <select name="path_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-400">
                        @foreach(['onboarding' => 'آشنایی اولیه', 'feature' => 'ویژگی', 'role' => 'نقش کاربری', 'custom' => 'دلخواه'] as $val => $label)
                        <option value="{{ $val }}" {{ old('path_type', $path['path_type'] ?? 'custom') === $val ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">زمان تخمینی (دقیقه)</label>
                    <input type="number" name="estimated_minutes" value="{{ old('estimated_minutes', $path['estimated_minutes'] ?? '') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-400"
                           min="1" placeholder="مثال: 30">
                </div>
                <div class="flex items-end pb-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1"
                               {{ old('is_active', $path['is_active'] ?? true) ? 'checked' : '' }}
                               class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                        <span class="text-sm font-medium text-gray-700">فعال</span>
                    </label>
                </div>
            </div>

        </div>

        <div class="flex items-center gap-3 justify-end">
            <a href="{{ route('admin.education-center.manage', ['tab' => 'learning-paths']) }}"
               class="px-4 py-2 rounded-lg border border-gray-300 text-sm text-gray-600 hover:bg-gray-50 transition">
                انصراف
            </a>
            <button type="submit"
                    class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition flex items-center gap-2">
                <x-core-ui::icon name="save" class="w-4 h-4" />
                {{ isset($path) ? 'ذخیره تغییرات' : 'ایجاد مسیر' }}
            </button>
        </div>
    </form>
</div>
@endsection
