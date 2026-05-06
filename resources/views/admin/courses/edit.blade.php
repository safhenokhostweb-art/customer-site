@extends('admin.layout')
@section('title', 'ویرایش دوره')
@section('header', 'ویرایش دوره')
@section('content')
<div class="admin-page max-w-2xl">
    <div class="card">
        <div class="card-header">ویرایش {{ $course->title }}</div>
        <div class="card-body">
            <form action="{{ route('admin.courses.update', $course) }}" method="post">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label class="form-label">عنوان دوره *</label>
                    <input type="text" name="title" value="{{ old('title', $course->title) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">اسلاگ (اختیاری)</label>
                    <input type="text" name="slug" value="{{ old('slug', $course->slug) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">توضیحات</label>
                    <textarea name="description" rows="4">{{ old('description', $course->description) }}</textarea>
                </div>
                <x-image-picker name="image" :value="old('image', $course->image)" context="course" label="تصویر دوره" />
                <div class="form-group">
                    <label class="form-label">قیمت (تومان) *</label>
                    <input type="number" name="price" value="{{ old('price', $course->price) }}" required min="0">
                </div>
                <div class="form-group">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $course->is_active) ? 'checked' : '' }}>
                        <span>دوره فعال است</span>
                    </label>
                </div>
                <div class="admin-page-tools mt-6">
                    <button type="submit" class="btn btn-primary">ذخیره تغییرات</button>
                    <a href="{{ route('admin.courses.index') }}" class="btn btn-secondary">انصراف</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
