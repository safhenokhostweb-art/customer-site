@extends('admin.layout')
@section('title', $warehouse ? 'ویرایش انبار' : 'انبار جدید')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-6">

    <nav class="flex items-center gap-2 text-sm text-slate-500 mb-5">
        <a href="{{ route('admin.inventory.index') }}" class="hover:text-indigo-600">انبار</a>
        <span>/</span>
        <a href="{{ route('admin.inventory.warehouses.index') }}" class="hover:text-indigo-600">انبارها</a>
        <span>/</span>
        <span class="text-slate-800">{{ $warehouse ? 'ویرایش' : 'جدید' }}</span>
    </nav>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <h1 class="text-xl font-bold text-slate-800 mb-6">{{ $warehouse ? 'ویرایش انبار' : 'انبار جدید' }}</h1>

        <form method="POST" action="{{ $warehouse ? route('admin.inventory.warehouses.update', $warehouse) : route('admin.inventory.warehouses.store') }}" class="space-y-4">
            @csrf
            @if($warehouse) @method('PUT') @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">نام انبار <span class="text-red-500">*</span></label>
                    <input name="name" value="{{ old('name', $warehouse?->name) }}" required class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none">
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">کد انبار <span class="text-red-500">*</span></label>
                    <input name="code" value="{{ old('code', $warehouse?->code) }}" required class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none" placeholder="WH-01">
                    @error('code') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">شهر</label>
                    <input name="city" value="{{ old('city', $warehouse?->city) }}" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">آدرس</label>
                    <input name="address" value="{{ old('address', $warehouse?->address) }}" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">نام مسئول</label>
                    <input name="manager_name" value="{{ old('manager_name', $warehouse?->manager_name) }}" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">موبایل مسئول</label>
                    <input name="manager_mobile" value="{{ old('manager_mobile', $warehouse?->manager_mobile) }}" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">یادداشت</label>
                <textarea name="notes" rows="2" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none resize-none">{{ old('notes', $warehouse?->notes) }}</textarea>
            </div>

            <div class="flex items-center gap-6">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_default" value="1" {{ old('is_default', $warehouse?->is_default) ? 'checked' : '' }} class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm text-slate-700">انبار پیش‌فرض</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $warehouse?->is_active ?? true) ? 'checked' : '' }} class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm text-slate-700">فعال</span>
                </label>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="px-5 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">
                    {{ $warehouse ? 'ذخیره تغییرات' : 'ایجاد انبار' }}
                </button>
                <a href="{{ route('admin.inventory.warehouses.index') }}" class="px-4 py-2 rounded-lg border border-slate-200 text-slate-600 text-sm hover:bg-slate-50 transition">انصراف</a>
                @if($warehouse)
                <form method="POST" action="{{ route('admin.inventory.warehouses.destroy', $warehouse) }}" class="mr-auto" onsubmit="return confirm('آیا مطمئنید؟')">
                    @csrf @method('DELETE')
                    <button type="submit" class="px-4 py-2 rounded-lg border border-red-200 text-red-600 text-sm hover:bg-red-50 transition">حذف</button>
                </form>
                @endif
            </div>
        </form>
    </div>
</div>
@endsection
