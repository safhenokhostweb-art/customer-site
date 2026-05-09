@extends('admin.layout')
@section('title', 'محصول جدید')
@section('header', 'محصول جدید')
@section('content')
<div class="admin-page">
    <div class="admin-page-head">
        <h1 class="admin-page-title">محصول جدید</h1>
        <div class="admin-page-tools">
            <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary btn-sm">بازگشت به فهرست</a>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.products.store') }}" method="post">
                @csrf
                @include('admin.products.form', ['isEdit' => false])
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
