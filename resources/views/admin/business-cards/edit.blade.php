@extends('admin.layout')
@section('title', 'ویرایش کارت ویزیت')
@section('header', 'ویرایش کارت ویزیت')
@section('content')
<div class="max-w-2xl">
    <div class="card">
        <div class="card-header">ویرایش {{ $businessCard->name }}</div>
        <div class="card-body">
            <form action="{{ route('admin.business-cards.update', $businessCard) }}" method="post">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">نام *</label>
                        <input type="text" name="name" value="{{ old('name', $businessCard->name) }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">عنوان شغلی</label>
                        <input type="text" name="title" value="{{ old('title', $businessCard->title) }}">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">شرکت</label>
                    <input type="text" name="company" value="{{ old('company', $businessCard->company) }}">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">تلفن</label>
                        <input type="tel" name="phone" value="{{ old('phone', $businessCard->phone) }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">ایمیل</label>
                        <input type="email" name="email" value="{{ old('email', $businessCard->email) }}">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">وب‌سایت</label>
                    <input type="url" name="website" value="{{ old('website', $businessCard->website) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">آدرس</label>
                    <textarea name="address" rows="2">{{ old('address', $businessCard->address) }}</textarea>
                </div>
                <x-image-picker name="avatar" :value="old('avatar', $businessCard->avatar)" context="avatar" label="آواتار" />
                <div class="form-group">
                    <label class="form-label">نامک</label>
                    <input type="text" name="slug" value="{{ old('slug', $businessCard->slug) }}">
                </div>
                <div class="form-group">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $businessCard->is_active) ? 'checked' : '' }}>
                        <span>کارت فعال است</span>
                    </label>
                </div>
                <p class="text-sm text-slate-500 mt-2">لینک: <a href="{{ route('business-card.show', $businessCard->slug) }}" target="_blank" class="text-indigo-600">/card/{{ $businessCard->slug }}</a></p>
                <div class="flex gap-3 mt-6">
                    <button type="submit" class="btn btn-primary">ذخیره تغییرات</button>
                    <a href="{{ route('admin.business-cards.index') }}" class="btn btn-secondary">انصراف</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
