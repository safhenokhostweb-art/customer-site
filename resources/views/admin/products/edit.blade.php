@extends('admin.layout')
@section('title', 'ویرایش محصول')
@section('header', 'ویرایش محصول')
@section('content')
<div class="admin-page">
    <div class="admin-page-head">
        <h1 class="admin-page-title">ویرایش محصول</h1>
        <div class="admin-page-tools">
            <a href="{{ route('admin.builder.product.edit', $product) }}"
               class="btn btn-primary btn-sm"
               style="display:inline-flex;align-items:center;gap:0.4rem;background:linear-gradient(135deg,#6366f1,#4f46e5);border-color:#4f46e5;">
                ✏️ سازنده بصری
            </a>
            <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary btn-sm">بازگشت به فهرست</a>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.products.update', $product) }}" method="post">
                @csrf
                @method('PUT')
                @include('admin.products.form', ['isEdit' => true])
                <div class="admin-form-actions admin-form-actions--sticky">
                    <button type="submit" class="btn btn-primary">ذخیره</button>
                    <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">انصراف</a>
                </div>
            </form>
        </div>
    </div>
@include('admin.products._editor_scripts')
</div>
@endsection
