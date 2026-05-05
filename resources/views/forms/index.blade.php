@extends('themes.default.layout')
@section('title', 'فرم‌ها')
@section('content')
<div class="container mx-auto py-8 px-4 max-w-2xl">
    <h1 class="font-bold text-2xl mb-6">فرم‌های سایت</h1>

    <ul class="divide-y divide-gray-200 border border-gray-200 rounded-xl bg-white overflow-hidden">
        @forelse($forms as $form)
            <li class="flex justify-between items-center px-4 py-3 hover:bg-gray-50">
                <span>{{ $form->title }}</span>
                <a class="btn btn-primary text-sm px-3 py-1.5" href="{{ route('forms.show', $form->slug) }}">
                    مشاهده فرم
                </a>
            </li>
        @empty
            <li class="px-4 py-8 text-gray-500 text-center">
                فرمی برای این سایت ثبت نشده است
            </li>
        @endforelse
    </ul>
</div>
@endsection
