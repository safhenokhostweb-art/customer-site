@extends('admin.layout')
@section('title', 'ایجاد منو')
@section('header', 'ایجاد منو')
@section('content')
<div class="admin-page" style="max-width:48rem">
    <div class="admin-page-head">
        <h1 class="admin-page-title">ایجاد منو</h1>
        <div class="admin-page-tools">
            <a href="{{ route('admin.menus.index') }}" class="btn btn-outline-secondary btn-sm">بازگشت به فهرست</a>
        </div>
    </div>
    <p class="admin-section-lead">برای هر موقعیت فقط یک منو قابل تعریف است. اینجا ساختار اولیه منو را می‌سازید و سپس آیتم‌ها را در صفحه ویرایش مدیریت می‌کنید.</p>
    <div class="card">
        <div class="card-header">اطلاعات منو</div>
        <div class="card-body">
            <form action="{{ route('admin.menus.store') }}" method="post" class="admin-form-stack">
                @csrf
                <div class="form-group">
                    <label class="form-label" for="menu-name">نام منو</label>
                    <input id="menu-name" type="text" name="name" value="{{ old('name') }}" required placeholder="مثلاً منوی اصلی">
                </div>
                <div class="form-group">
                    <label class="form-label" for="menu-location">موقعیت</label>
                    <select id="menu-location" name="location" required>
                        @foreach($locations as $key => $label)
                            <option value="{{ $key }}" {{ old('location') === $key ? 'selected' : '' }}>{{ $label }}{{ in_array($key, $existingLocations ?? []) ? ' (قبلاً استفاده شده)' : '' }}</option>
                        @endforeach
                    </select>
                    <span class="admin-field-hint">هر موقعیت فقط یک منو می‌تواند داشته باشد.</span>
                </div>
                <div class="form-group">
                    <label class="form-label" for="menu-status">وضعیت اولیه</label>
                    <select id="menu-status" name="status">
                        <option value="published" @selected(old('status', 'published') === 'published')>منتشر شده</option>
                        <option value="draft" @selected(old('status') === 'draft')>پیش‌نویس</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="menu-go-live">زمان انتشار (اختیاری)</label>
                    <input id="menu-go-live" type="datetime-local" name="go_live_at" value="{{ old('go_live_at') }}">
                </div>
                <div class="admin-form-actions">
                    <button type="submit" class="btn btn-primary">ذخیره</button>
                    <a href="{{ route('admin.menus.index') }}" class="btn btn-outline-secondary">انصراف</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
