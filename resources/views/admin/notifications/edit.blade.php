@extends('admin.layout')
@section('title', 'ویرایش اعلان')
@section('header', 'ویرایش اعلان')
@section('content')
<div class="max-w-2xl">
    <div class="card">
        <div class="card-header">ویرایش {{ $site_notification->title }}</div>
        <div class="card-body">
            <form action="{{ route('admin.notifications.update', $site_notification) }}" method="post">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label class="form-label">عنوان *</label>
                    <input type="text" name="title" value="{{ old('title', $site_notification->title) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">متن</label>
                    <textarea name="body" rows="4">{{ old('body', $site_notification->body) }}</textarea>
                </div>
                <input type="hidden" name="audience" value="public">
                <div class="form-group">
                    <label class="form-label">نوع</label>
                    <select name="type">
                        <option value="info" {{ old('type', $site_notification->type) === 'info' ? 'selected' : '' }}>اطلاع</option>
                        <option value="success" {{ old('type', $site_notification->type) === 'success' ? 'selected' : '' }}>موفق</option>
                        <option value="warning" {{ old('type', $site_notification->type) === 'warning' ? 'selected' : '' }}>هشدار</option>
                        <option value="danger" {{ old('type', $site_notification->type) === 'danger' ? 'selected' : '' }}>مهم</option>
                    </select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">لینک</label>
                        <input type="url" name="link" value="{{ old('link', $site_notification->link) }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">متن لینک</label>
                        <input type="text" name="link_text" value="{{ old('link_text', $site_notification->link_text) }}">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">موقعیت تولبار</label>
                        <select name="position">
                            <option value="top" {{ old('position', $site_notification->position ?? 'top') === 'top' ? 'selected' : '' }}>بالا</option>
                            <option value="left" {{ old('position', $site_notification->position) === 'left' ? 'selected' : '' }}>چپ</option>
                            <option value="right" {{ old('position', $site_notification->position) === 'right' ? 'selected' : '' }}>راست</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">موقعیت تصویر</label>
                        <select name="image_position">
                            <option value="background" {{ old('image_position', $site_notification->image_position ?? 'right') === 'background' ? 'selected' : '' }}>پس‌زمینه</option>
                            <option value="left" {{ old('image_position', $site_notification->image_position) === 'left' ? 'selected' : '' }}>چپ</option>
                            <option value="right" {{ old('image_position', $site_notification->image_position) === 'right' ? 'selected' : '' }}>راست</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">تصویر (پس‌زمینه یا کنار اعلان)</label>
                    @include('components.image-picker', ['name' => 'image_path', 'value' => old('image_path', $site_notification->image_path ?? ''), 'context' => 'featured', 'label' => 'تصویر'])
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">تاریخ شروع (شمسی)</label>
                        <x-persian-date-input name="starts_at" :value="old('starts_at', $site_notification->starts_at ? \App\Helpers\Jalali::formatFa($site_notification->starts_at, 'Y/m/d') : '')" placeholder="۱۴۰۴/۰۱/۰۱" class="form-control" />
                    </div>
                    <div class="form-group">
                        <label class="form-label">تاریخ انقضا (شمسی)</label>
                        <x-persian-date-input name="expires_at" :value="old('expires_at', $site_notification->expires_at ? \App\Helpers\Jalali::formatFa($site_notification->expires_at, 'Y/m/d') : '')" placeholder="۱۴۰۴/۱۲/۲۹" class="form-control" />
                    </div>
                </div>
                <div class="form-group">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="show_shaking_logo" value="1" {{ old('show_shaking_logo', $site_notification->show_shaking_logo) ? 'checked' : '' }}>
                        <span>نمایش لوگوی لرزان</span>
                    </label>
                </div>
                <div class="form-group">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $site_notification->is_active) ? 'checked' : '' }}>
                        <span>اعلان فعال است</span>
                    </label>
                </div>
                <div class="flex gap-3 mt-6">
                    <button type="submit" class="btn btn-primary">ذخیره تغییرات</button>
                    <a href="{{ route('admin.notifications.index') }}" class="btn btn-secondary">انصراف</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
