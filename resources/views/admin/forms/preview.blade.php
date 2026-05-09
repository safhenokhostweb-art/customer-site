@extends('admin.layout')
@section('title', 'پیش‌نمایش: ' . $form->title)
@section('header', 'پیش‌نمایش فرم: ' . $form->title)
@section('content')

<div class="max-w-2xl mx-auto">
    <div class="bg-amber-50 border border-amber-200 text-amber-900 rounded-xl p-4 mb-6 text-sm">
        این پیش‌نمایش ظاهر فرم است؛ ارسال واقعی غیرفعال است.
    </div>

    <div class="card">
        <div class="card-body">
            <h4 class="font-bold mb-2 text-xl">{{ $form->title }}</h4>
            @if($form->intro_text)
                <p class="text-slate-600 text-sm mb-4 border-r-4 border-amber-200 pr-3">{!! nl2br(e($form->intro_text)) !!}</p>
            @endif

            <form action="#" method="POST" onsubmit="event.preventDefault(); alert('این پیش‌نمایش است.');">
                @csrf

                @if(!empty($captcha['enabled']))
                    <div class="mb-3">
                        <label class="form-label">کپچا</label>
                        @if(($captcha['type'] ?? '') === 'persian_number' && !empty($captcha['persian_code']))
                            <div class="flex items-center gap-3 flex-wrap">
                                <x-persian-captcha-challenge :code="$captcha['persian_code']" />
                                <input type="text" class="form-input w-36" placeholder="پاسخ (غیرفعال)" disabled>
                            </div>
                        @elseif(($captcha['type'] ?? '') === 'normal' && !empty($captcha['normal_text']))
                            <div class="flex items-center gap-3 flex-wrap">
                                <x-persian-captcha-math :text="$captcha['normal_text']" />
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
                        @if(in_array($field->type, ['text','email','number'], true))
                            <input type="{{ $field->type }}" name="field_{{ $field->id }}" class="form-input" placeholder="{{ $field->placeholder }}" disabled>
                        @elseif($field->type === 'textarea')
                            <textarea name="field_{{ $field->id }}" class="form-input" placeholder="{{ $field->placeholder }}" disabled></textarea>
                        @elseif($field->type === 'date')
                            <x-persian-date-input name="field_{{ $field->id }}" value="" placeholder="۱۴۰۴/۰۱/۱۵" class="form-input persian-date-input" />
                        @elseif($field->type === 'select')
                            <select name="field_{{ $field->id }}" class="form-input" disabled>
                                <option>—</option>
                                @foreach($field->options ?? [] as $opt)
                                    <option>{{ $opt }}</option>
                                @endforeach
                            </select>
                        @elseif($field->type === 'radio')
                            @foreach($field->options ?? [] as $opt)
                                <label class="flex items-center gap-2 mb-1">
                                    <input type="radio" name="field_{{ $field->id }}" value="{{ $opt }}" disabled>
                                    <span>{{ $opt }}</span>
                                </label>
                            @endforeach
                        @elseif($field->type === 'checkbox')
                            @foreach($field->options ?? [] as $opt)
                                <label class="flex items-center gap-2 mb-1">
                                    <input type="checkbox" name="field_{{ $field->id }}[]" value="{{ $opt }}" disabled>
                                    <span>{{ $opt }}</span>
                                </label>
                            @endforeach
                        @endif
                        @if($field->help_text)
                            <p class="text-xs text-slate-500 mt-1">{{ $field->help_text }}</p>
                        @endif
                    </div>
                @empty
                    <p class="text-gray-500">هنوز فیلدی به این فرم اضافه نشده است.</p>
                @endforelse

                <button type="submit" class="btn btn-primary w-full mt-4">ارسال فرم (غیرفعال در پیش‌نمایش)</button>
            </form>
        </div>
    </div>

    <p class="mt-4 text-center text-sm">
        <a href="{{ route('admin.forms.edit', $form) }}" class="text-blue-600 hover:underline">بازگشت به ویرایش</a>
        <span class="mx-2">|</span>
        <a href="{{ route('forms.show', $form->slug) }}" class="text-blue-600 hover:underline" target="_blank" rel="noopener">مشاهده در سایت</a>
    </p>
</div>

@if($form->fields->contains('type', 'date'))
@push('head')
<link rel="stylesheet" href="{{ asset('vendor/persian-datepicker/persian-datepicker.min.css') }}?v={{ @filemtime(public_path('vendor/persian-datepicker/persian-datepicker.min.css')) ?: time() }}">
@endpush
@push('scripts')
<script src="{{ asset('vendor/jquery/jquery.min.js') }}" defer></script>
<script src="{{ asset('vendor/persian-date/persian-date.min.js') }}" defer></script>
<script src="{{ asset('vendor/persian-datepicker/persian-datepicker.min.js') }}" defer></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.persian-date-input') && typeof jQuery !== 'undefined' && jQuery.fn.persianDatepicker) {
        jQuery('.persian-date-input').persianDatepicker({ format: 'YYYY/MM/DD', persianDigit: true, initialValueType: 'persian', autoClose: true });
    }
});
</script>
@endpush
@endif
@endsection
