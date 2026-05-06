@extends('admin.layout')
@section('title', 'فرم‌ساز')
@section('header', 'فرم‌ساز')
@section('content')

<div class="admin-page admin-forms-page">
    <div class="admin-page-head">
        <h1 class="admin-page-title">فرم‌ساز</h1>
        <div class="admin-page-tools">
            <a href="{{ route('admin.forms.create') }}" class="btn btn-primary">ساخت فرم</a>
            <a href="{{ route('admin.forms.submissions.index') }}" class="btn btn-secondary">گزارش ارسال فرم‌ها</a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @forelse($forms as $form)
            <div class="card">
                <div class="card-body">
                    <div class="font-bold mb-2">{{ $form->title }}</div>
                    <div class="flex flex-wrap gap-2 text-sm">
                        <a href="{{ route('admin.forms.edit', $form) }}" class="btn btn-secondary btn-sm">مدیریت فیلدها</a>
                        <a href="{{ route('admin.forms.preview', $form) }}" class="btn btn-secondary btn-sm" target="_blank">پیش‌نمایش</a>
                        <form method="POST" action="{{ route('admin.forms.destroy', $form) }}" class="inline" onsubmit="return confirm('حذف کامل فرم؟');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="card md:col-span-3">
                <div class="card-body">
                    <p class="admin-empty">هنوز فرمی ساخته نشده است. روی «ساخت فرم» کلیک کنید.</p>
                </div>
            </div>
        @endforelse
    </div>
</div>

@endsection
