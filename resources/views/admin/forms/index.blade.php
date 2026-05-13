@extends('admin.layout')
@section('title', 'فرم‌ساز')
@section('header', 'فرم‌ساز')
@section('content')

@include('admin.partials.breadcrumb', ['crumbs' => [
    ['label' => 'داشبورد', 'url' => route('admin.dashboard')],
    ['label' => 'فرم‌ساز', 'url' => null],
]])

@include('admin.forms.guide')

<div class="admin-page admin-forms-page">
    <div class="admin-page-head">
        <div>
            <h1 class="admin-page-title">فرم‌ساز</h1>
            <p class="admin-section-lead" style="margin-top:0.35rem">ساخت فرم، فیلدها، سئو و آدرس عمومی؛ گزارش ارسال‌ها از دکمهٔ زیر.</p>
        </div>
        <div class="admin-page-tools">
            <a href="{{ route('admin.forms.create') }}" class="btn btn-primary">ساخت فرم</a>
            <a href="{{ route('admin.forms.submissions.index') }}" class="btn btn-secondary">گزارش ارسال فرم‌ها</a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @forelse($forms as $form)
            <div class="card admin-form-card {{ $form->is_active ? '' : 'opacity-90 border-amber-200' }}">
                <div class="card-body">
                    <div class="flex justify-between items-start gap-2 mb-3">
                        <h2 class="font-bold text-base leading-snug">{{ $form->title }}</h2>
                        @if($form->is_active)
                            <span class="badge badge-published shrink-0">فعال</span>
                        @else
                            <span class="badge badge-draft shrink-0">غیرفعال</span>
                        @endif
                    </div>
                    <div class="flex flex-wrap gap-2 text-xs text-slate-600 mb-3">
                        <span class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2 py-1">{{ $form->fields_count }} فیلد فعال</span>
                        <span class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2 py-1">{{ number_format($form->submissions_count) }} ارسال</span>
                    </div>
                    <div class="rounded-lg bg-slate-50 border border-slate-100 px-3 py-2 mb-3 text-xs break-all" dir="ltr">
                        <span class="text-slate-500">آدرس عمومی:</span>
                        <a href="{{ route('forms.show', $form->slug) }}" target="_blank" rel="noopener" class="text-indigo-600 hover:underline">{{ route('forms.show', $form->slug) }}</a>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('admin.forms.edit', $form) }}" class="btn btn-primary btn-sm">تنظیمات و فیلدها</a>
                        <a href="{{ route('admin.forms.preview', $form) }}" class="btn btn-secondary btn-sm" target="_blank" rel="noopener">پیش‌نمایش</a>
                        <form method="POST" action="{{ route('admin.forms.destroy', $form) }}" class="inline" onsubmit="return confirm('حذف کامل فرم و ارسال‌ها؟');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="card md:col-span-2 xl:col-span-3">
                <div class="card-body">
                    <div class="admin-empty-state">
                        <p class="admin-empty-state__title">هنوز فرمی ساخته نشده</p>
                        <p class="admin-empty-state__text">با «ساخت فرم» اولین فرم را بسازید؛ سپس فیلدها و متا سئو را در صفحهٔ ویرایش تکمیل کنید.</p>
                        <a href="{{ route('admin.forms.create') }}" class="btn btn-primary mt-2">ساخت فرم</a>
                    </div>
                </div>
            </div>
        @endforelse
    </div>
</div>

@endsection
