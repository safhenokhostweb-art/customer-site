@extends('admin.layout')
@section('title', 'پیش‌نمایش منو')
@section('header', 'پیش‌نمایش منو (فقط خواندنی)')
@section('content')
<div class="admin-page">
    <p class="admin-section-lead">«{{ $menu->name }}» — این صفحه ساختار فعلی آیتم‌ها را بدون اعمال وضعیت انتشار نشان می‌دهد.</p>
    <div class="card">
        <div class="card-header">درخت پیش‌نمایش منو</div>
        <div class="card-body">
            <ul class="list-disc pr-5 space-y-1 text-sm">
                @include('admin.menus.partials.preview-tree', ['items' => $tree])
            </ul>
        </div>
    </div>
    <a href="{{ route('admin.menus.edit', $menu) }}" class="btn btn-secondary mt-4">بازگشت به ویرایش</a>
</div>
@endsection
