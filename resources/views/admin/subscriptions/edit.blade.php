@extends('admin.layout')
@section('title', 'ویرایش پلن اشتراک')
@section('header', 'ویرایش پلن اشتراک')
@section('content')
<div class="admin-page max-w-2xl">
    <div class="card">
        <div class="card-header">ویرایش پلن {{ $subscription->name }}</div>
        <div class="card-body">
            <form action="{{ route('admin.subscriptions.update', $subscription) }}" method="post">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label class="form-label">نام پلن *</label>
                    <input type="text" name="name" value="{{ old('name', $subscription->name) }}" required>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">قیمت (تومان) *</label>
                        <input type="number" name="price" value="{{ old('price', $subscription->price) }}" required min="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">مدت *</label>
                        <select name="duration" required>
                            <option value="month" {{ old('duration', $subscription->duration) === 'month' ? 'selected' : '' }}>ماهانه</option>
                            <option value="year" {{ old('duration', $subscription->duration) === 'year' ? 'selected' : '' }}>سالانه</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">تعداد واحد مدت *</label>
                    <input type="number" name="duration_count" value="{{ old('duration_count', $subscription->duration_count) }}" required min="1">
                </div>
                <div class="form-group">
                    <label class="form-label">توضیحات</label>
                    <textarea id="subscription-description-editor" name="description" class="w-full min-h-[160px]">{{ old('description', $subscription->description) }}</textarea>
                </div>
                <div class="form-group">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $subscription->is_active) ? 'checked' : '' }}>
                        <span>پلن فعال است</span>
                    </label>
                </div>
                <div class="admin-page-tools mt-6">
                    <button type="submit" class="btn btn-primary">ذخیره تغییرات</button>
                    <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-secondary">انصراف</a>
                </div>
            </form>
        </div>
    </div>
</div>
@include('admin.partials.tinymce-field-init', ['selector' => '#subscription-description-editor', 'uploadContext' => 'featured', 'height' => 300])
@endsection
