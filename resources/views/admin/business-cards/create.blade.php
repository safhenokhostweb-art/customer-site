@extends('admin.layout')
@section('title', 'کارت ویزیت جدید')
@section('header', 'کارت ویزیت جدید')
@section('content')
<div class="max-w-2xl">
    <div class="card">
        <div class="card-header">اطلاعات کارت ویزیت</div>
        <div class="card-body">
            <form action="{{ route('admin.business-cards.store') }}" method="post">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">نام *</label>
                        <input type="text" name="name" value="{{ old('name') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">عنوان شغلی</label>
                        <input type="text" name="title" value="{{ old('title') }}" placeholder="مدیر فروش">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">شرکت</label>
                    <input type="text" name="company" value="{{ old('company') }}">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">تلفن</label>
                        <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="۰۹۱۲۳۴۵۶۷۸۹">
                    </div>
                    <div class="form-group">
                        <label class="form-label">ایمیل</label>
                        <input type="email" name="email" value="{{ old('email') }}">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">وب‌سایت</label>
                    <input type="url" name="website" value="{{ old('website') }}" placeholder="https://">
                </div>
                <div class="form-group">
                    <label class="form-label">آدرس</label>
                    <textarea name="address" rows="2">{{ old('address') }}</textarea>
                </div>
                <x-image-picker name="avatar" :value="old('avatar')" context="avatar" label="آواتار" />
                <div class="form-group">
                    <label class="form-label">نامک (اختیاری)</label>
                    <input type="text" name="slug" value="{{ old('slug') }}" placeholder="خالی = خودکار">
                </div>
                <div class="form-group">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                        <span>کارت فعال است</span>
                    </label>
                </div>
                <div class="flex gap-3 mt-6">
                    <button type="submit" class="btn btn-primary">ذخیره کارت</button>
                    <a href="{{ route('admin.business-cards.index') }}" class="btn btn-secondary">انصراف</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
