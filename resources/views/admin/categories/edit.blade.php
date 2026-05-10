@extends('admin.layout')
@section('title', 'ویرایش دسته‌بندی')
@section('header', 'ویرایش دسته‌بندی')
@section('content')
<div class="admin-page">
    <div class="admin-page-head">
        <h1 class="admin-page-title">ویرایش دسته‌بندی</h1>
    </div>
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.categories.update', $category) }}" method="post">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label class="form-label">نام</label>
                    <input type="text" name="name" value="{{ old('name', $category->name) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">نامک</label>
                    <input type="text" name="slug" value="{{ old('slug', $category->slug) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">توضیح</label>
                    <textarea id="category-description-editor" name="description" class="w-full min-h-[160px]">{{ old('description', $category->description) }}</textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">ترتیب</label>
                    <input type="number" name="order" value="{{ old('order', $category->order) }}" min="0">
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
