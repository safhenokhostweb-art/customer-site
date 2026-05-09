@extends('admin.layout')
@section('title', 'دسته‌بندی محصول جدید')
@section('header', 'دسته‌بندی محصول جدید')
@section('content')
<div class="admin-page">
    <div class="admin-page-head">
        <h1 class="admin-page-title">دسته‌بندی محصول جدید</h1>
    </div>
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.product-categories.store') }}" method="post">
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
                    <label class="form-label">دستهٔ والد (سلسله‌مراتب)</label>
                    <select name="parent_id">
                        <option value="">— ریشه —</option>
                        @foreach($parents ?? [] as $par)
                            <option value="{{ $par->id }}" @selected(old('parent_id') == $par->id)>{{ $par->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">ترتیب</label>
                    <input type="number" name="order" value="{{ old('order', 0) }}" min="0">
                </div>
                <div class="admin-page-tools">
                    <button type="submit" class="btn btn-primary">ذخیره</button>
                    <a href="{{ route('admin.product-categories.index') }}" class="btn btn-secondary">انصراف</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
