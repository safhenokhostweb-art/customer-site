@extends('admin.layout')
@section('title', 'مطلب جدید')
@section('header', 'مطلب جدید')
@section('content')
<div class="admin-page">
    <div class="admin-page-head">
        <h1 class="admin-page-title">مطلب جدید</h1>
    </div>
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.posts.store') }}" method="post">
                @csrf
                <div class="form-group">
                    <label class="form-label">عنوان</label>
                    <input type="text" name="title" value="{{ old('title') }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">نامک (اختیاری)</label>
                    <input type="text" name="slug" value="{{ old('slug') }}">
                </div>
                <div class="form-group">
                    <label class="form-label">دسته‌بندی</label>
                    <select name="category_id">
                        <option value="">—</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}" {{ old('category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">خلاصه</label>
                    <textarea name="excerpt">{{ old('excerpt') }}</textarea>
                </div>
                <x-image-picker name="featured_image" :value="old('featured_image')" context="featured" label="تصویر شاخص" />
                <div class="form-group">
                    <label class="form-label">محتوا</label>
                    <textarea name="body">{{ old('body') }}</textarea>
                </div>
                <div class="form-group"><label><input type="checkbox" name="is_published" value="1" {{ old('is_published') ? 'checked' : '' }}> منتشر شود</label></div>
                <div class="admin-page-tools">
                    <button type="submit" class="btn btn-primary">ذخیره</button>
                    <a href="{{ route('admin.posts.index') }}" class="btn btn-secondary">انصراف</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
