@extends('admin.layout')
@section('title', 'پیش‌نمایش: ' . $form->title)
@section('header', 'پیش‌نمایش فرم: ' . $form->title)
@section('content')

<div class="max-w-2xl mx-auto">
    <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl p-4 mb-6">
        این پیش‌نمایش فرم نهایی است. ارسال غیرفعال است.
    </div>

    <div class="card">
        <div class="card-body">
            <h4 class="font-bold mb-4 text-xl">{{ $form->title }}</h4>

            <form action="#" method="POST" onsubmit="event.preventDefault(); alert('این پیش‌نمایش است.');">
                @csrf

                @if(!empty($captcha['enabled']))
                    <div class="mb-3">
                        <label class="form-label">کپچا</label>
                        @if(($captcha['type'] ?? '') === 'persian_number' && !empty($captcha['persian_code']))
                            <div class="flex items-center gap-2">
                                <span class="text-2xl font-bold bg-gray-100 px-3 py-2 rounded">{{ $captcha['persian_code'] }}</span>
                                <input type="text" class="form-input w-32" placeholder="عدد بالا را وارد کنید" disabled>
                            </div>
                        @elseif(($captcha['type'] ?? '') === 'normal' && !empty($captcha['normal_text']))
                            <div class="flex items-center gap-2">
                                <span class="text-xl font-bold bg-gray-100 px-3 py-2 rounded">{{ $captcha['normal_text'] }}</span>
                                <input type="text" class="form-input w-24" placeholder="جواب" disabled>
                            </div>
                        @endif
                    </div>
                @endif

                @forelse($form->fields as $field)
                    <div class="mb-3">
                        <label class="form-label">
                            {{ $field->label }}
                            @if($field->is_required)<span class="text-red-500">*</span>@endif
                        </label>
                        @if(in_array($field->type, ['text','email','number']))
                            <input type="{{ $field->type }}" name="field_{{ $field->id }}" class="form-input" placeholder="{{ $field->placeholder }}">
                        @elseif($field->type === 'textarea')
                            <textarea name="field_{{ $field->id }}" class="form-input" placeholder="{{ $field->placeholder }}"></textarea>
                        @elseif($field->type === 'date')
                            <x-persian-date-input name="field_{{ $field->id }}" value="" placeholder="۱۴۰۳/۰۷/۱۵" class="form-input persian-date-input" />
                        @elseif(in_array($field->type, ['select','radio','checkbox']))
                            @foreach($field->options ?? [] as $opt)
                                <div class="flex items-center gap-2 mb-1">
                                    <input type="{{ $field->type === 'select' ? 'radio' : $field->type }}" name="field_{{ $field->id }}" value="{{ $opt }}">
                                    <label>{{ $opt }}</label>
                                </div>
                            @endforeach
                        @endif
                    </div>
                @empty
                    <p class="text-gray-500">هنوز فیلدی به این فرم اضافه نشده است.</p>
                @endforelse

                <button type="submit" class="btn btn-primary w-full mt-4">ارسال فرم (غیرفعال در پیش‌نمایش)</button>
            </form>
        </div>
    </div>

    <p class="mt-4 text-center">
        <a href="{{ route('admin.forms.edit', $form) }}" class="text-blue-600 hover:underline">بازگشت به ویرایش</a>
        <span class="mx-2">|</span>
        <a href="{{ route('forms.show', $form->slug) }}" class="text-blue-600 hover:underline" target="_blank">مشاهده در سایت</a>
    </p>
</div>

@if($form->fields->contains('type', 'date'))
@push('head')
<link rel="stylesheet" href="https://unpkg.com/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">
@endpush
@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://unpkg.com/persian-date@1.1.0/dist/persian-date.min.js"></script>
<script src="https://unpkg.com/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.persian-date-input') && typeof jQuery !== 'undefined' && jQuery.fn.persianDatepicker) {
        jQuery('.persian-date-input').persianDatepicker({ format: 'YYYY/MM/DD', persianDigit: true, initialValueType: 'persian' });
    }
});
</script>
@endpush
@endif
@endsection
