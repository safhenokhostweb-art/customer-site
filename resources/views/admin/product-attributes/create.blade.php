@extends('admin.layout')
@section('title', 'ویژگی جدید')
@section('content')
<div class="admin-page">
    <div class="admin-page-head">
        <h1 class="admin-page-title">ویژگی جدید</h1>
    </div>
    <div class="card"><div class="card-body">
        <form method="post" action="{{ route('admin.product-attributes.store') }}">
            @csrf
            <fieldset class="admin-fieldset">
                <legend class="admin-fieldset__legend">اطلاعات ویژگی</legend>
                <div class="admin-form-grid admin-form-grid--2">
                    <div class="form-group">
                        <label class="form-label" for="attribute-name">نام</label>
                        <input id="attribute-name" type="text" name="name" value="{{ old('name') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="attribute-slug">نامک</label>
                        <input id="attribute-slug" type="text" name="slug" value="{{ old('slug') }}">
                        <span class="admin-field-hint">در صورت خالی بودن، از نام ساخته می‌شود.</span>
                    </div>
                </div>
                <div class="admin-form-grid admin-form-grid--2">
                    <div class="form-group">
                        <label class="admin-form-inline-check" for="attribute-filterable">
                            <input id="attribute-filterable" type="checkbox" name="filterable" value="1" checked> قابل فیلتر در فروشگاه
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="attribute-sort-order">ترتیب</label>
                        <input id="attribute-sort-order" type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0">
                    </div>
                </div>
            </fieldset>
            <div class="admin-form-actions admin-form-actions--sticky">
                <button type="submit" class="btn btn-primary">ذخیره</button>
                <a href="{{ route('admin.product-attributes.index') }}" class="btn btn-secondary">انصراف</a>
            </div>
        </form>
    </div></div>
</div>
@endsection
