@extends('admin.layout')
@section('title', $contact ? 'ویرایش مخاطب' : 'مخاطب جدید')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-6">

    <nav class="flex items-center gap-2 text-sm text-slate-500 mb-5">
        <a href="{{ route('admin.crm.index') }}" class="hover:text-indigo-600">CRM</a>
        <span>/</span>
        <a href="{{ route('admin.crm.contacts.index') }}" class="hover:text-indigo-600">مخاطبین</a>
        <span>/</span>
        <span class="text-slate-800">{{ $contact ? 'ویرایش' : 'جدید' }}</span>
    </nav>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <h1 class="text-xl font-bold text-slate-800 mb-6">{{ $contact ? 'ویرایش مخاطب' : 'مخاطب جدید' }}</h1>

        <form method="POST" action="{{ $contact ? route('admin.crm.contacts.update', $contact) : route('admin.crm.contacts.store') }}" class="space-y-4">
            @csrf
            @if($contact) @method('PUT') @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">نام <span class="text-red-500">*</span></label>
                    <input name="name" value="{{ old('name', $contact?->name) }}" required class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none" placeholder="نام کامل">
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">موبایل</label>
                    <input name="mobile" value="{{ old('mobile', $contact?->mobile) }}" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none" placeholder="09...">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">ایمیل</label>
                    <input name="email" type="email" value="{{ old('email', $contact?->email) }}" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none" placeholder="example@mail.com">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">شرکت</label>
                    <input name="company" value="{{ old('company', $contact?->company) }}" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none" placeholder="نام شرکت">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">سمت</label>
                    <input name="position" value="{{ old('position', $contact?->position) }}" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none" placeholder="مدیرعامل، مدیر فروش...">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">شهر</label>
                    <input name="city" value="{{ old('city', $contact?->city) }}" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none" placeholder="تهران، اصفهان...">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">وضعیت</label>
                    <select name="status" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none">
                        <option value="lead" {{ old('status', $contact?->status) === 'lead' ? 'selected' : '' }}>سرنخ</option>
                        <option value="prospect" {{ old('status', $contact?->status) === 'prospect' ? 'selected' : '' }}>بالقوه</option>
                        <option value="customer" {{ old('status', $contact?->status) === 'customer' ? 'selected' : '' }}>مشتری</option>
                        <option value="churned" {{ old('status', $contact?->status) === 'churned' ? 'selected' : '' }}>از دست رفته</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">منبع</label>
                    <select name="source" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none">
                        <option value="manual" {{ old('source', $contact?->source ?? 'manual') === 'manual' ? 'selected' : '' }}>دستی</option>
                        <option value="order" {{ old('source', $contact?->source) === 'order' ? 'selected' : '' }}>سفارش</option>
                        <option value="ticket" {{ old('source', $contact?->source) === 'ticket' ? 'selected' : '' }}>تیکت</option>
                        <option value="form" {{ old('source', $contact?->source) === 'form' ? 'selected' : '' }}>فرم</option>
                        <option value="import" {{ old('source', $contact?->source) === 'import' ? 'selected' : '' }}>وارد شده</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">یادداشت</label>
                <textarea name="notes" rows="3" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none resize-none" placeholder="یادداشت‌های داخلی درباره این مخاطب...">{{ old('notes', $contact?->notes) }}</textarea>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="px-5 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">
                    {{ $contact ? 'ذخیره تغییرات' : 'ایجاد مخاطب' }}
                </button>
                <a href="{{ $contact ? route('admin.crm.contacts.show', $contact) : route('admin.crm.contacts.index') }}" class="px-4 py-2 rounded-lg border border-slate-200 text-slate-600 text-sm hover:bg-slate-50 transition">انصراف</a>
                @if($contact)
                <form method="POST" action="{{ route('admin.crm.contacts.destroy', $contact) }}" class="mr-auto" onsubmit="return confirm('آیا مطمئنید؟')">
                    @csrf @method('DELETE')
                    <button type="submit" class="px-4 py-2 rounded-lg border border-red-200 text-red-600 text-sm hover:bg-red-50 transition">حذف</button>
                </form>
                @endif
            </div>
        </form>
    </div>
</div>
@endsection
