@extends('admin.layout')
@section('title', 'اعلان جدید')
@section('header', 'اعلان جدید')
@section('content')
<div class="admin-page max-w-2xl">
    <div class="card">
        <div class="card-header">اطلاعات اعلان</div>
        <div class="card-body">
            <form action="{{ route('admin.notifications.store') }}" method="post">
                @csrf
                <div class="form-group">
                    <label class="form-label">عنوان *</label>
                    <input type="text" name="title" value="{{ old('title') }}" required placeholder="مثلاً اطلاعیه مهم">
                </div>
                <div class="form-group">
                    <label class="form-label">متن</label>
                    <textarea id="site-notification-body-editor" name="body" class="w-full min-h-[200px]" placeholder="متن کامل اعلان...">{{ old('body') }}</textarea>
                </div>
                <input type="hidden" name="audience" value="public">
                <p class="text-slate-500 text-sm mb-3">اعلان‌های ایجادشده در این پنل فقط برای کاربران نهایی (مشتریان) در سایت فروش نمایش داده می‌شوند. برای ارسال اعلان به ادمین سایت، از پنل پلتفرم اصلی (saas-platform) استفاده کنید.</p>
                <div class="form-group">
                    <label class="form-label">نوع</label>
                    <select name="type">
                        <option value="info" {{ old('type', 'info') === 'info' ? 'selected' : '' }}>اطلاع</option>
                        <option value="success" {{ old('type') === 'success' ? 'selected' : '' }}>موفق</option>
                        <option value="warning" {{ old('type') === 'warning' ? 'selected' : '' }}>هشدار</option>
                        <option value="danger" {{ old('type') === 'danger' ? 'selected' : '' }}>مهم</option>
                    </select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">لینک</label>
                        <input type="url" name="link" value="{{ old('link') }}" placeholder="https://">
                    </div>
                    <div class="form-group">
                        <label class="form-label">متن لینک</label>
                        <input type="text" name="link_text" value="{{ old('link_text') }}" placeholder="مشاهده بیشتر">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">موقعیت تولبار</label>
                        <select name="position">
                            <option value="top" {{ old('position', 'top') === 'top' ? 'selected' : '' }}>بالا</option>
                            <option value="left" {{ old('position') === 'left' ? 'selected' : '' }}>چپ</option>
                            <option value="right" {{ old('position') === 'right' ? 'selected' : '' }}>راست</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">موقعیت تصویر</label>
                        <select name="image_position">
                            <option value="background" {{ old('image_position', 'right') === 'background' ? 'selected' : '' }}>پس‌زمینه</option>
                            <option value="left" {{ old('image_position') === 'left' ? 'selected' : '' }}>چپ</option>
                            <option value="right" {{ old('image_position') === 'right' ? 'selected' : '' }}>راست</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">تصویر (پس‌زمینه یا کنار اعلان)</label>
                    @include('components.image-picker', ['name' => 'image_path', 'value' => old('image_path'), 'context' => 'featured', 'label' => 'تصویر'])
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">تاریخ شروع (شمسی)</label>
                        <x-persian-date-input name="starts_at" :value="old('starts_at')" placeholder="۱۴۰۴/۰۱/۰۱" class="form-control" />
                    </div>
                    <div class="form-group">
                        <label class="form-label">تاریخ انقضا (شمسی)</label>
                        <x-persian-date-input name="expires_at" :value="old('expires_at')" placeholder="۱۴۰۴/۱۲/۲۹" class="form-control" />
                    </div>
                </div>
                <div class="form-group">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="show_shaking_logo" value="1" {{ old('show_shaking_logo', true) ? 'checked' : '' }}>
                        <span>نمایش لوگوی لرزان برای جلب توجه</span>
                    </label>
                </div>
                <div class="form-group">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                        <span>اعلان فعال است</span>
                    </label>
                </div>
                <div class="admin-page-tools mt-6">
                    <button type="submit" class="btn btn-primary">ذخیره اعلان</button>
                    <a href="{{ route('admin.notifications.index') }}" class="btn btn-secondary">انصراف</a>
                </div>
            </form>
        </div>
    </div>
</div>
@include('admin.partials.tinymce-field-init', ['selector' => '#site-notification-body-editor', 'uploadContext' => 'featured', 'height' => 300])
@endsection
