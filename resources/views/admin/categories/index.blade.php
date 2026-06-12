@extends('admin.layout')
@section('title', 'دسته‌بندی‌های وبلاگ')
@section('header', 'دسته‌بندی‌های وبلاگ')
@push('head')
<style>
.cat-card{display:flex;align-items:center;gap:1rem;padding:1rem 1.25rem;border-radius:.875rem;background:#fff;border:1px solid #e2e8f0;box-shadow:0 1px 3px rgba(15,23,42,.05);transition:all .2s;margin-bottom:.625rem}
.cat-card:hover{box-shadow:0 4px 16px rgba(99,102,241,.08);border-color:#c7d2fe;transform:translateY(-1px)}
.cat-icon{display:flex;align-items:center;justify-content:center;width:2.75rem;height:2.75rem;border-radius:.75rem;background:linear-gradient(135deg,#6366f1,#4f46e5);flex-shrink:0}
.cat-name{font-size:.95rem;font-weight:700;color:#1e293b}
.cat-slug{font-size:.75rem;color:#94a3b8;font-family:monospace}
.cat-count{display:inline-flex;align-items:center;gap:.25rem;padding:.2rem .65rem;border-radius:9999px;font-size:.72rem;font-weight:700;background:#ede9fe;color:#6d28d9}
.cat-actions{display:flex;gap:.375rem;margin-right:auto}
</style>
@endpush
@section('content')

@include('admin.partials.breadcrumb', ['crumbs' => [
    ['label' => 'داشبورد', 'url' => route('admin.dashboard')],
    ['label' => 'دسته‌بندی‌های وبلاگ', 'url' => null],
]])

@if(session('success'))
    <div class="alert alert-success mb-4">{{ session('success') }}</div>
@endif

<div class="admin-page">
    <div class="admin-page-head">
        <div>
            <h1 class="admin-page-title">دسته‌بندی‌های وبلاگ</h1>
            <p class="admin-section-lead" style="margin-top:.3rem">مدیریت دسته‌بندی مطالب و تعداد پست‌های هر دسته.</p>
        </div>
        <div class="admin-page-tools">
            <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">
                <x-core-ui::icon name="plus" class="w-4 h-4" />
                دسته جدید
            </a>
        </div>
    </div>

    {{-- KPIs --}}
    <div style="display:flex;gap:1rem;margin-bottom:1.25rem;flex-wrap:wrap">
        <div class="card" style="padding:.875rem 1.25rem;display:flex;align-items:center;gap:.75rem;min-width:160px">
            <div style="width:2.25rem;height:2.25rem;border-radius:.625rem;background:#ede9fe;display:flex;align-items:center;justify-content:center">
                <x-core-ui::icon name="folder" class="w-5 h-5 text-violet-600" />
            </div>
            <div>
                <div style="font-size:.72rem;color:#64748b;font-weight:600">تعداد دسته‌ها</div>
                <div style="font-size:1.4rem;font-weight:800;color:#1e293b">{{ \App\Helpers\NumberHelper::formatFa($categories->count()) }}</div>
            </div>
        </div>
        <div class="card" style="padding:.875rem 1.25rem;display:flex;align-items:center;gap:.75rem;min-width:160px">
            <div style="width:2.25rem;height:2.25rem;border-radius:.625rem;background:#dbeafe;display:flex;align-items:center;justify-content:center">
                <x-core-ui::icon name="file-text" class="w-5 h-5 text-blue-600" />
            </div>
            <div>
                <div style="font-size:.72rem;color:#64748b;font-weight:600">مجموع مطالب</div>
                <div style="font-size:1.4rem;font-weight:800;color:#1e293b">{{ \App\Helpers\NumberHelper::formatFa($categories->sum('posts_count')) }}</div>
            </div>
        </div>
    </div>

    {{-- Categories List --}}
    @forelse($categories as $cat)
        <div class="cat-card">
            <div class="cat-icon">
                <x-core-ui::icon name="tag" class="w-5 h-5 text-white" />
            </div>
            <div style="flex:1;min-width:0">
                <div class="cat-name">{{ $cat->name }}</div>
                <div class="cat-slug">/{{ $cat->slug }}</div>
            </div>
            <span class="cat-count">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                {{ \App\Helpers\NumberHelper::formatFa($cat->posts_count) }} مطلب
            </span>
            <div class="cat-actions">
                <a href="{{ route('admin.categories.edit', $cat) }}" class="btn btn-primary btn-sm">ویرایش</a>
                <form method="post" action="{{ route('admin.categories.destroy', $cat) }}" style="display:inline;"
                      onsubmit="return confirm('دسته‌بندی «{{ $cat->name }}» حذف شود؟ مطالب آن بدون دسته می‌مانند.');">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                </form>
            </div>
        </div>
    @empty
        <div class="admin-empty-state" style="margin-top:2rem">
            <svg class="admin-empty-state__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
            <p class="admin-empty-state__title">هنوز دسته‌بندی ثبت نشده</p>
            <p class="admin-empty-state__text">با دکمهٔ «دسته جدید» اولین دسته‌بندی وبلاگ خود را بسازید.</p>
            <a href="{{ route('admin.categories.create') }}" class="btn btn-primary mt-3">ساخت اولین دسته</a>
        </div>
    @endforelse
</div>
@endsection
