@extends('admin.layout')
@section('title', 'دوره جدید')
@section('header', 'دوره جدید')
@section('content')
<div class="admin-page max-w-2xl">
    <div class="card">
        <div class="card-header">اطلاعات دوره</div>
        <div class="card-body">
            <form action="{{ route('admin.courses.store') }}" method="post">
                @csrf
                <div class="form-group">
                    <label class="form-label">عنوان دوره *</label>
                    <input type="text" name="title" value="{{ old('title') }}" required placeholder="مثلاً دوره برنامه‌نویسی">
                </div>
                <div class="form-group">
                    <label class="form-label">اسلاگ (اختیاری)</label>
                    <input type="text" name="slug" value="{{ old('slug') }}" placeholder="خالی = از عنوان">
                </div>
                <div class="form-group">
                    <label class="form-label">توضیحات</label>
                    <textarea id="course-description-editor" name="description" class="w-full min-h-[180px]" placeholder="شرح دوره...">{{ old('description') }}</textarea>
                </div>
                <x-smart-image-picker name="image" :value="old('image')" context="course" label="تصویر دوره" />
                <div class="form-group">
                    <label class="form-label">قیمت (تومان) *</label>
                    <input type="number" name="price" value="{{ old('price') }}" required min="0" placeholder="0">
                </div>
                <div class="form-group">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                        <span>دوره فعال است</span>
                    </label>
                </div>
                <div class="admin-page-tools mt-6">
                    <button type="submit" class="btn btn-primary">ذخیره دوره</button>
                    <a href="{{ route('admin.courses.index') }}" class="btn btn-secondary">انصراف</a>
                </div>
            </form>
        </div>
    </div>
</div>
@include('admin.partials.tinymce-field-init', ['selector' => '#course-description-editor', 'uploadContext' => 'course', 'height' => 340])
@endsection
