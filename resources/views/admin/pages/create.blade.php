@extends('admin.layout')
@section('title', 'صفحه جدید')
@section('header', 'صفحه جدید')
@section('content')
<div class="admin-page">
    <div class="admin-page-head">
        <h1 class="admin-page-title">صفحه جدید</h1>
    </div>
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.pages.store') }}" method="post">
                @csrf
                <div class="form-group">
                    <label class="form-label">عنوان</label>
                    <input type="text" name="title" value="{{ old('title') }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">نامک (اختیاری)</label>
                    <input type="text" name="slug" value="{{ old('slug') }}" placeholder="about-us">
                </div>
                <div class="form-group">
                    <label class="form-label">محتوا</label>
                    <textarea id="page-body-editor" name="body" class="w-full min-h-[220px]">{{ old('body') }}</textarea>
                </div>
                <div class="form-group"><label><input type="checkbox" name="is_published" value="1" {{ old('is_published', true) ? 'checked' : '' }}> منتشر شود</label></div>
                <div class="form-group"><label><input type="checkbox" name="show_in_menu" value="1" {{ old('show_in_menu', true) ? 'checked' : '' }}> در منو نمایش داده شود</label></div>
                <div class="admin-page-tools">
                    <button type="submit" class="btn btn-primary">ذخیره</button>
                    <a href="{{ route('admin.pages.index') }}" class="btn btn-secondary">انصراف</a>
                </div>
            </form>
        </div>
    </div>
</div>
@include('admin.partials.tinymce-field-init', ['selector' => '#page-body-editor', 'uploadContext' => 'featured', 'height' => 420])
@endsection
