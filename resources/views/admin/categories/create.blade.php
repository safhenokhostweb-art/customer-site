@extends('admin.layout')
@section('title', 'دسته‌بندی جدید')
@section('header', 'دسته‌بندی جدید')
@section('content')
<div class="admin-page">
    <div class="admin-page-head">
        <h1 class="admin-page-title">دسته‌بندی جدید</h1>
    </div>
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.categories.store') }}" method="post">
                @csrf
                <div class="form-group">
                    <label class="form-label">نام</label>
                    <input type="text" name="name" value="{{ old('name') }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">نامک (اختیاری)</label>
                    <input type="text" name="slug" value="{{ old('slug') }}">
                </div>
                <div class="form-group">
                    <label class="form-label">توضیح</label>
                    <textarea id="category-description-editor" name="description" class="w-full min-h-[160px]">{{ old('description') }}</textarea>
                </div>
                <div class="admin-page-tools">
                    <button type="submit" class="btn btn-primary">ذخیره</button>
                    <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">انصراف</a>
                </div>
            </form>
        </div>
    </div>
</div>
@include('admin.partials.tinymce-field-init', ['selector' => '#category-description-editor', 'uploadContext' => 'featured', 'height' => 300])
@endsection
