@extends('admin.layout')
@section('title', 'ظاهر و تم')
@section('content')
<h1>ظاهر و تم (Theme Design)</h1>
<p style="color: #6b7280; margin-bottom: 1rem;">رنگ‌ها، فونت‌ها و استایل‌های کلی سایت. این مقادیر به صورت متغیرهای CSS در تمام صفحات اعمال می‌شوند.</p>
@if(session('success'))
    <p style="color: green; margin-bottom: 1rem;">{{ session('success') }}</p>
@endif
<form action="{{ route('admin.theme-design.update') }}" method="post" style="max-width: 520px;">
    @csrf
    @method('PUT')
    <div class="form-group">
        <label>رنگ اصلی (Primary)</label>
        <input type="text" name="primary_color" value="{{ old('primary_color', $settings->primary_color ?? '#4f46e5') }}" placeholder="#4f46e5">
    </div>
    <div class="form-group">
        <label>رنگ ثانویه (Secondary)</label>
        <input type="text" name="secondary_color" value="{{ old('secondary_color', $settings->secondary_color ?? '#64748b') }}" placeholder="#64748b">
    </div>
    <div class="form-group">
        <label>فونت عناوین</label>
        <input type="text" name="font_family_heading" value="{{ old('font_family_heading', $settings->font_family_heading ?? 'Vazirmatn, Tahoma, sans-serif') }}" placeholder="Vazirmatn, Tahoma, sans-serif">
    </div>
    <div class="form-group">
        <label>فونت متن</label>
        <input type="text" name="font_family_body" value="{{ old('font_family_body', $settings->font_family_body ?? 'Vazirmatn, Tahoma, sans-serif') }}" placeholder="Vazirmatn, Tahoma, sans-serif">
    </div>
    <div class="form-group">
        <label>اندازه پایه فونت</label>
        <input type="text" name="base_font_size" value="{{ old('base_font_size', $settings->base_font_size ?? '16px') }}" placeholder="16px یا 1rem">
    </div>
    <div class="form-group">
        <label>عرض کانتینر (پیکسل یا rem)</label>
        <input type="text" name="container_width" value="{{ old('container_width', $settings->container_width ?? '1200px') }}" placeholder="1200px">
    </div>
    <div class="form-group">
        <label>گردی گوشه‌ها (Border radius)</label>
        <input type="text" name="border_radius" value="{{ old('border_radius', $settings->border_radius ?? '8px') }}" placeholder="8px">
    </div>
    <div class="form-group">
        <label>استایل دکمه</label>
        <select name="button_style">
            @foreach($buttonStyles as $value => $label)
                <option value="{{ $value }}" {{ old('button_style', $settings->button_style ?? 'solid') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label>فاصله سکشن‌ها</label>
        <select name="section_spacing">
            <option value="xs" {{ old('section_spacing', $settings->section_spacing ?? 'md') === 'xs' ? 'selected' : '' }}>خیلی کم</option>
            <option value="sm" {{ old('section_spacing', $settings->section_spacing ?? 'md') === 'sm' ? 'selected' : '' }}>کم</option>
            <option value="md" {{ old('section_spacing', $settings->section_spacing ?? 'md') === 'md' ? 'selected' : '' }}>متوسط</option>
            <option value="lg" {{ old('section_spacing', $settings->section_spacing ?? 'md') === 'lg' ? 'selected' : '' }}>زیاد</option>
            <option value="xl" {{ old('section_spacing', $settings->section_spacing ?? 'md') === 'xl' ? 'selected' : '' }}>خیلی زیاد</option>
        </select>
    </div>
    <button type="submit" class="btn btn-primary">ذخیره</button>
</form>
@endsection
