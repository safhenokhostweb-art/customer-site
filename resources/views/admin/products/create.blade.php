@extends('admin.layout')
@section('title', 'محصول جدید')
@section('header', 'محصول جدید')
@section('content')
<div class="admin-page">
    <div class="admin-page-head">
        <h1 class="admin-page-title">محصول جدید</h1>
    </div>
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.products.store') }}" method="post">
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
                        <option value="">بدون دسته</option>
                        @foreach($categories ?? [] as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">قیمت ({{ \App\Helpers\CurrencyHelper::unitLabel() }})@if(\App\Helpers\CurrencyHelper::isReformEnabled()) <span class="text-sm text-gray-500">— واحد جدید</span>@endif</label>
                    <input type="number" name="price" value="{{ old('price', 0) }}" min="0" required>
                </div>
                <div class="form-group">
                    <label class="form-label">قیمت با تخفیف (اختیاری)</label>
                    <input type="number" name="sale_price" value="{{ old('sale_price') }}" min="0">
                </div>
                <div class="form-group">
                    <label class="form-label">موجودی</label>
                    <input type="number" name="stock" value="{{ old('stock', 0) }}" min="0">
                </div>
                <div class="form-group">
                    <label class="form-label">توضیح</label>
                    <textarea name="description">{{ old('description') }}</textarea>
                </div>
                <x-image-picker name="image" :value="old('image')" context="product" label="تصویر محصول" />
                <div class="form-group">
                    <label><input type="checkbox" name="is_active" value="1" checked> فعال</label>
                </div>
                <div class="admin-page-tools">
                    <button type="submit" class="btn btn-primary">ذخیره</button>
                    <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">انصراف</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
