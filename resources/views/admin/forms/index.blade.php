@extends('admin.layout')
@section('title', 'فرم‌ساز')
@section('header', 'فرم‌ساز')
@section('content')

@if(session('success'))
    <div class="bg-green-100 text-green-700 p-3 rounded-lg mb-4">{{ session('success') }}</div>
@endif

<a href="{{ route('admin.forms.create') }}" class="btn btn-primary mb-4">ساخت فرم</a>
<a href="{{ route('admin.forms.submissions.index') }}" class="btn btn-secondary mb-4 mr-2">گزارش ارسال فرم‌ها</a>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    @foreach($forms as $form)
        <div class="card">
            <div class="card-body">
                <div class="font-bold mb-2">{{ $form->title }}</div>
                <div class="flex flex-wrap gap-2 text-sm">
                    <a href="{{ route('admin.forms.edit', $form) }}" class="text-indigo-600 hover:underline">مدیریت فیلدها</a>
                    <span>|</span>
                    <a href="{{ route('admin.forms.preview', $form) }}" class="text-indigo-600 hover:underline" target="_blank">پیش‌نمایش</a>
                    <form method="POST" action="{{ route('admin.forms.destroy', $form) }}" class="inline" onsubmit="return confirm('حذف کامل فرم؟');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:underline">حذف</button>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
</div>

@if($forms->isEmpty())
    <p class="text-slate-600 mt-4">هنوز فرمی ساخته نشده است. روی «ساخت فرم» کلیک کنید.</p>
@endif

@endsection
