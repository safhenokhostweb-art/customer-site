@extends('admin.layout')
@section('title', 'ویژگی جدید')
@section('content')
<div class="admin-page">
    <div class="admin-page-head"><h1 class="admin-page-title">ویژگی جدید</h1></div>
    <div class="card"><div class="card-body">
        <form method="post" action="{{ route('admin.product-attributes.store') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">نام</label>
                <input type="text" name="name" value="{{ old('name') }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">نامک</label>
                <input type="text" name="slug" value="{{ old('slug') }}">
            </div>
            <div class="form-group">
                <label><input type="checkbox" name="filterable" value="1" checked> قابل فیلتر در فروشگاه</label>
            </div>
            <div class="form-group">
                <label class="form-label">ترتیب</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0">
            </div>
            <button type="submit" class="btn btn-primary">ذخیره</button>
            <a href="{{ route('admin.product-attributes.index') }}" class="btn btn-secondary">انصراف</a>
        </form>
    </div></div>
</div>
@endsection
