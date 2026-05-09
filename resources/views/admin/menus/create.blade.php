@extends('admin.layout')
@section('title', 'ایجاد منو')
@section('header', 'ایجاد منو')
@section('content')
<div class="admin-page max-w-2xl">
    <p class="admin-section-lead">برای هر موقعیت فقط یک منو قابل تعریف است. اینجا ساختار اولیه منو را می‌سازید و سپس آیتم‌ها را در صفحه ویرایش مدیریت می‌کنید.</p>
    <div class="card">
        <div class="card-header">اطلاعات منو</div>
        <div class="card-body">
            <form action="{{ route('admin.menus.store') }}" method="post" class="admin-form-stack">
                @csrf
                <div class="form-group">
                    <label class="form-label">نام منو</label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="مثلاً منوی اصلی">
                </div>
                <div class="form-group">
                    <label class="form-label">موقعیت</label>
                    <select name="location" required>
                        @foreach($locations as $key => $label)
                            <option value="{{ $key }}" {{ old('location') === $key ? 'selected' : '' }}>{{ $label }}{{ in_array($key, $existingLocations ?? []) ? ' (قبلاً استفاده شده)' : '' }}</option>
                        @endforeach
                    </select>
                    <small class="admin-muted-note">هر موقعیت فقط یک منو می‌تواند داشته باشد.</small>
                </div>
                <div class="form-group">
                    <label class="form-label">وضعیت اولیه</label>
                    <select name="status">
                        <option value="published" @selected(old('status', 'published') === 'published')>منتشر شده</option>
                        <option value="draft" @selected(old('status') === 'draft')>پیش‌نویس</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">زمان انتشار (اختیاری)</label>
                    <input type="datetime-local" name="go_live_at" value="{{ old('go_live_at') }}">
                </div>
                <div class="admin-page-tools">
                    <button type="submit" class="btn btn-primary">ذخیره</button>
                    <a href="{{ route('admin.menus.index') }}" class="btn btn-secondary">انصراف</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
