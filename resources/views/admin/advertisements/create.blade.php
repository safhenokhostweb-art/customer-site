@extends('admin.layout')
@section('title', 'تبلیغ جدید')
@section('header', 'تبلیغ جدید')
@section('content')
<div class="max-w-2xl">
    <div class="card">
        <div class="card-header">اطلاعات تبلیغ</div>
        <div class="card-body">
            <form action="{{ route('admin.advertisements.store') }}" method="post">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">نوع تبلیغ *</label>
                        <select name="type" required>
                            @foreach(\App\Models\Advertisement::typeLabels() as $k => $v)
                                <option value="{{ $k }}" {{ old('type', 'banner') === $k ? 'selected' : '' }}>{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">موقعیت نمایش *</label>
                        <select name="position" required>
                            @foreach(\App\Models\Advertisement::positionLabels() as $k => $v)
                                <option value="{{ $k }}" {{ old('position', 'header') === $k ? 'selected' : '' }}>{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">عنوان</label>
                    <input type="text" name="title" value="{{ old('title') }}" placeholder="عنوان تبلیغ">
                </div>
                <div class="form-group">
                    <label class="form-label">متن تبلیغ</label>
                    <textarea name="content" rows="4" placeholder="متن برای نوشتار رونده، تایپی، مارکی و...">{{ old('content') }}</textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">تصویر (برای بنر)</label>
                    @include('components.image-picker', ['name' => 'image_path', 'value' => old('image_path'), 'context' => 'advertisement', 'label' => 'تصویر'])
                </div>
                <div class="form-group">
                    <label class="form-label">لینک</label>
                    <input type="text" name="link" value="{{ old('link') }}" placeholder="https://">
                </div>
                <div class="form-group">
                    <label class="form-label">ترتیب نمایش</label>
                    <input type="number" name="order" value="{{ old('order', 0) }}" min="0">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">تاریخ شروع (شمسی)</label>
                        <input type="text" name="starts_at" value="{{ old('starts_at') }}" placeholder="۱۴۰۴/۱۲/۰۵">
                    </div>
                    <div class="form-group">
                        <label class="form-label">تاریخ انقضا (شمسی)</label>
                        <input type="text" name="expires_at" value="{{ old('expires_at') }}" placeholder="۱۴۰۴/۱۲/۳۰">
                    </div>
                </div>
                <div class="form-group">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                        <span>تبلیغ فعال است</span>
                    </label>
                </div>
                <div class="flex gap-3 mt-6">
                    <button type="submit" class="btn btn-primary">ذخیره تبلیغ</button>
                    <a href="{{ route('admin.advertisements.index') }}" class="btn btn-secondary">انصراف</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
