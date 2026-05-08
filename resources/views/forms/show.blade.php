@extends('themes.default.layout')
@section('title', $form->title)
@section('content')
<div class="container py-8 px-4 max-w-2xl">

    <h1 class="font-bold text-2xl mb-6">{{ $form->title }}</h1>

    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-4 rounded-lg mb-6">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('forms.submit', $form->slug) }}" class="space-y-4">
        @csrf

        @foreach($form->fields as $field)
            <div>
                <label class="form-label">
                    {{ $field->label }}
                    @if($field->is_required)
                        <span class="text-red-500">*</span>
                    @endif
                </label>

                @if(in_array($field->type, ['text','email','number']))
                    <input type="{{ $field->type }}" name="field_{{ $field->id }}" class="form-input" value="{{ old('field_'.$field->id) }}" placeholder="{{ $field->placeholder }}">
                @elseif($field->type === 'textarea')
                    <textarea name="field_{{ $field->id }}" class="form-input" placeholder="{{ $field->placeholder }}">{{ old('field_'.$field->id) }}</textarea>
                @elseif($field->type === 'date')
                    <x-persian-date-input name="field_{{ $field->id }}" value="{{ old('field_'.$field->id) }}" placeholder="۱۴۰۳/۰۷/۱۵" class="form-input persian-date-input" />
                @elseif(in_array($field->type, ['select','radio','checkbox']))
                    @foreach($field->options ?? [] as $opt)
                        <div class="flex items-center gap-2 mb-1">
                            <input type="{{ $field->type === 'select' ? 'radio' : $field->type }}" name="field_{{ $field->id }}" value="{{ $opt }}" {{ old('field_'.$field->id) == $opt ? 'checked' : '' }}>
                            <label>{{ $opt }}</label>
                        </div>
                    @endforeach
                @endif

                @error('field_'.$field->id)
                    <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                @enderror
            </div>
        @endforeach

        @if(!empty($captcha['enabled'] ?? false))
            <div>
                <label class="form-label">کپچا</label>
                @if($captcha['type'] === 'persian_number' && !empty($captcha['persian_code']))
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-2xl font-bold tracking-widest text-gray-700 bg-gray-100 px-3 py-2 rounded select-none">{{ $captcha['persian_code'] }}</span>
                        <input type="text" name="captcha_answer" class="form-input w-32" placeholder="عدد بالا را وارد کنید" maxlength="6" required autocomplete="off">
                    </div>
                @elseif($captcha['type'] === 'normal' && !empty($captcha['normal_text']))
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-xl font-bold text-gray-700 bg-gray-100 px-3 py-2 rounded select-none">{{ $captcha['normal_text'] }}</span>
                        <input type="text" name="captcha_answer" class="form-input w-24" placeholder="جواب" maxlength="4" required autocomplete="off">
                    </div>
                @elseif($captcha['type'] === 'hcaptcha' && !empty($captcha['hcaptcha_site_key']))
                    <div class="h-captcha" data-sitekey="{{ $captcha['hcaptcha_site_key'] }}" data-hl="fa"></div>
                @endif
                @error('captcha')
                    <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                @enderror
            </div>
        @endif

        <button type="submit" class="btn btn-primary w-full">ارسال فرم</button>
    </form>
</div>

@if(!empty($captcha['enabled']) && ($captcha['type'] ?? '') === 'hcaptcha' && !empty($captcha['hcaptcha_site_key']))
@push('scripts')
<script src="https://js.hcaptcha.com/1/api.js?hl=fa" async defer></script>
@endpush
@endif

@if($form->fields->contains('type', 'date'))
@push('head')
<link rel="stylesheet" href="{{ asset('vendor/persian-datepicker/persian-datepicker.min.css') }}">
@endpush
@push('scripts')
<script src="{{ asset('vendor/jquery/jquery.min.js') }}" defer></script>
<script src="{{ asset('vendor/persian-date/persian-date.min.js') }}" defer></script>
<script src="{{ asset('vendor/persian-datepicker/persian-datepicker.min.js') }}" defer></script>
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
