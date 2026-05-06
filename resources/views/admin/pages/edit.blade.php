@extends('admin.layout')
@section('title', 'ویرایش صفحه')
@section('header', 'ویرایش صفحه')
@section('content')
<div class="admin-page">
    <div class="admin-page-head">
        <h1 class="admin-page-title">ویرایش صفحه</h1>
    </div>
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.pages.update', $page) }}" method="post">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label class="form-label">عنوان</label>
                    <input type="text" name="title" value="{{ old('title', $page->title) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">نامک</label>
                    <input type="text" name="slug" value="{{ old('slug', $page->slug) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">محتوا</label>
                    <textarea name="body">{{ old('body', $page->body) }}</textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">ترتیب</label>
                    <input type="number" name="order" value="{{ old('order', $page->order) }}" min="0">
                </div>
                <div class="form-group"><label><input type="checkbox" name="is_published" value="1" @if(old('is_published', $page->is_published)) checked @endif> منتشر شود</label></div>
                <div class="form-group"><label><input type="checkbox" name="show_in_menu" value="1" @if(old('show_in_menu', $page->show_in_menu)) checked @endif> در منو</label></div>

                <hr style="margin: 1rem 0;">
                <h3 style="font-size: 1rem;">هدر و فوتر این صفحه</h3>
                <p style="font-size: 0.9rem; color: #6b7280; margin-bottom: 0.75rem;">در غیر صورت انتخاب «پیش‌فرض سایت»، از تنظیمات کلی سایت استفاده می‌شود.</p>
                <div class="form-group">
                    <label class="form-label">هدر</label>
                    <select name="builder_header_override">
                        @foreach($headerOptions ?? [] as $value => $label)
                            <option value="{{ $value }}" @if(old('builder_header_override', $page->builder_disable_header ? \App\Services\BuilderLayoutService::PAGE_OVERRIDE_NONE : ($page->builder_header_template_override ?? '')) == $value) selected @endif>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">فوتر</label>
                    <select name="builder_footer_override">
                        @foreach($footerOptions ?? [] as $value => $label)
                            <option value="{{ $value }}" @if(old('builder_footer_override', $page->builder_disable_footer ? \App\Services\BuilderLayoutService::PAGE_OVERRIDE_NONE : ($page->builder_footer_template_override ?? '')) == $value) selected @endif>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="admin-page-tools">
                    <button type="submit" class="btn btn-primary">ذخیره</button>
                    <a href="{{ route('admin.pages.index') }}" class="btn btn-secondary">انصراف</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
