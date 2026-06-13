@extends('admin.layout')
@section('title', 'افزودن ویدیو')

@section('content')
<div class="admin-page-container">
    <div class="admin-page-header mb-6">
        <a href="{{ route('admin.homepage-videos.index') }}" class="text-sm text-slate-500 hover:text-slate-700 flex items-center gap-1 mb-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            بازگشت به لیست
        </a>
        <h1 class="admin-page-title">افزودن ویدیوی جدید</h1>
    </div>

    @if($errors->any())
        <div class="alert alert-danger mb-4">
            <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.homepage-videos.store') }}">
        @csrf
        @include('admin.homepage-videos._form')
        <div class="flex gap-3 mt-6">
            <button type="submit" class="btn btn-primary">ذخیره ویدیو</button>
            <a href="{{ route('admin.homepage-videos.index') }}" class="btn btn-secondary">انصراف</a>
        </div>
    </form>
</div>
@endsection
