@extends('admin.layout')
@section('title', 'مجموعه جدید')
@section('header', 'مجموعه جدید')
@section('content')
<div class="admin-page">
    <div class="admin-page-head">
        <h1 class="admin-page-title">مجموعه جدید</h1>
    </div>
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.post-series.store') }}" method="post">
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
                    <label class="form-label">توضیحات</label>
                    <textarea id="post-series-description-editor" name="description" class="w-full min-h-[160px]">{{ old('description') }}</textarea>
                </div>
                <div class="admin-page-tools">
                    <button type="submit" class="btn btn-primary">ذخیره</button>
                    <a href="{{ route('admin.post-series.index') }}" class="btn btn-secondary">انصراف</a>
                </div>
            </form>
        </div>
    </div>
</div>
@include('admin.partials.tinymce-field-init', ['selector' => '#post-series-description-editor', 'uploadContext' => 'featured', 'height' => 320])
@endsection
