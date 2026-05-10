@props([
    'name' => 'persian_date',
    'value' => '',
    'placeholder' => '۱۴۰۳/۰۷/۱۵',
    'id' => null,
    /** اگر true باشد کلاس persian-date-input نمی‌گیرد تا init سراسری ادمین روی آن نزند (مثلاً فیلد داخل بلوک مخفی). */
    'deferGlobalInit' => false,
])
@php
    $id = $id ?? 'persian-date-' . preg_replace('/[^a-z0-9]/', '-', $name);
    $baseClass = $deferGlobalInit
        ? 'form-input persian-date-defer'
        : 'form-input persian-date-input';
    $class = trim($baseClass . ' ' . ($attributes->get('class') ?? ''));
@endphp
<input type="text"
       name="{{ $name }}"
       id="{{ $id }}"
       value="{{ $value }}"
       placeholder="{{ $placeholder }}"
       autocomplete="off"
       dir="ltr"
       {{ $attributes->merge(['class' => $class]) }}>
