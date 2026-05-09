@extends('admin.layout')
@section('title', 'ویرایش محصول')
@section('header', 'ویرایش محصول')
@section('content')
<div class="admin-page">
    <div class="admin-page-head">
        <h1 class="admin-page-title">ویرایش محصول</h1>
    </div>
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.products.update', $product) }}" method="post">
                @csrf
                @method('PUT')
                @include('admin.products.form', ['isEdit' => true])
                <div class="admin-page-tools">
                    <button type="submit" class="btn btn-primary">ذخیره</button>
                    <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">انصراف</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
