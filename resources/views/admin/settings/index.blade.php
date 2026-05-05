@extends('admin.layout')
@section('title', 'تنظیمات')
@section('content')
<h1>تنظیمات سایت</h1>
@if(session('success'))
    <p style="color: green; margin-bottom: 1rem;">{{ session('success') }}</p>
@endif
<form action="{{ route('admin.settings.update') }}" method="post">
    @csrf
    <div class="form-group"><label>نام سایت</label><input type="text" name="site_name" value="{{ old('site_name', $siteName) }}" required></div>
    <div class="form-group"><label>قالب</label><select name="theme"><option value="default" {{ ($theme ?? 'default') === 'default' ? 'selected' : '' }}>پیش‌فرض</option><option value="minimal" {{ ($theme ?? '') === 'minimal' ? 'selected' : '' }}>مینیمال</option><option value="dark" {{ ($theme ?? '') === 'dark' ? 'selected' : '' }}>تیره</option></select></div>
    <hr style="margin: 1rem 0;">
    <h3 style="font-size: 1rem;">هدر و فوتر سایت (سازنده بصری)</h3>
    <div class="form-group">
        <label>قالب هدر</label>
        <select name="builder_header_template">
            @foreach($headerOptions ?? [] as $value => $label)
                <option value="{{ $value }}" {{ old('builder_header_template', $builderHeaderTemplate ?? '') == $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label>قالب فوتر</label>
        <select name="builder_footer_template">
            @foreach($footerOptions ?? [] as $value => $label)
                <option value="{{ $value }}" {{ old('builder_footer_template', $builderFooterTemplate ?? '') == $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label><input type="checkbox" name="builder_sticky_header" value="1" {{ ($builderStickyHeader ?? true) ? 'checked' : '' }}> هدر چسبان (Sticky)</label>
    </div>
    <hr style="margin: 1rem 0;">
    <h3 style="font-size: 1rem;">بلوک درباره ما (صفحهٔ خانه)</h3>
    <div class="form-group"><label><input type="checkbox" name="about_us_enabled" value="1" {{ ($aboutUsEnabled ?? false) ? 'checked' : '' }}> نمایش بلوک درباره ما</label></div>
    <div class="form-group"><label>عنوان</label><input type="text" name="about_us_title" value="{{ old('about_us_title', $aboutUsTitle ?? '') }}" placeholder="درباره ما"></div>
    <div class="form-group"><label>متن</label><textarea name="about_us_text" rows="4">{{ old('about_us_text', $aboutUsText ?? '') }}</textarea></div>
    <hr style="margin: 1rem 0;">
    <h3 style="font-size: 1rem;">اعلان‌ها (حساب کاربری)</h3>
    <div class="form-group">
        <label>موقعیت زنگولهٔ اعلان در پروفایل کاربر</label>
        <select name="notification_bell_position">
            <option value="bottom-right" {{ ($notificationBellPosition ?? 'bottom-right') === 'bottom-right' ? 'selected' : '' }}>پایین راست</option>
            <option value="bottom-left" {{ ($notificationBellPosition ?? '') === 'bottom-left' ? 'selected' : '' }}>پایین چپ</option>
            <option value="top-right" {{ ($notificationBellPosition ?? '') === 'top-right' ? 'selected' : '' }}>بالا راست</option>
            <option value="top-left" {{ ($notificationBellPosition ?? '') === 'top-left' ? 'selected' : '' }}>بالا چپ</option>
        </select>
    </div>
    <button type="submit" class="btn btn-primary">ذخیره</button>
</form>
@php
    $tm = $themeMeta ?? [];
@endphp
<div style="margin-top:1.5rem; padding:1rem; border-radius:0.75rem; border:1px solid #e5e7eb; background:#f9fafb;">
    <h2 style="font-size:1rem; margin-bottom:0.75rem;">اطلاعات تم فعال (Theme Builder)</h2>
    @if(!empty($tm))
        <ul style="list-style:none; padding:0; margin:0; font-size:.9rem; color:#374151;">
            <li><strong>نام تم:</strong> {{ $tm['name'] ?? '—' }}</li>
            <li><strong>شناسه تم:</strong> <code>{{ $tm['id'] ?? '—' }}</code></li>
            <li><strong>نسخه نصب‌شده:</strong> {{ $tm['version'] ?? '1.0.0' }}</li>
            @if(!empty($tm['release_date']))
                <li><strong>تاریخ انتشار (نمایش):</strong>
                    {{ \App\Helpers\Jalali::formatFa(\Carbon\Carbon::parse($tm['release_date']), 'Y/m/d') }}
                </li>
            @endif
            @if(!empty($tm['required_platform_version']))
                <li><strong>حداقل نسخه پلتفرم:</strong> {{ $tm['required_platform_version'] }}</li>
            @endif
            @if(!empty($tm['supported_modules']) && is_array($tm['supported_modules']))
                <li><strong>ماژول‌های پشتیبانی‌شده:</strong> {{ implode('، ', $tm['supported_modules']) }}</li>
            @endif
        </ul>
        @if(!empty($tm['changelog']) && is_array($tm['changelog']))
            <details style="margin-top:.75rem; font-size:.9rem;">
                <summary style="cursor:pointer;">تاریخچه بروزرسانی تم</summary>
                <ul style="margin-top:.5rem; padding-right:1.25rem;">
                    @foreach($tm['changelog'] as $version => $changes)
                        <li><strong>نسخه {{ is_string($version) ? $version : ($tm['version'] ?? '1.0.0') }}:</strong>
                            @if(is_array($changes))
                                <ul style="margin-top:.25rem; padding-right:1rem;">
                                    @foreach($changes as $c)
                                        <li>{{ $c }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <span>{{ $changes }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </details>
        @endif
        <p style="margin-top:.75rem; font-size:.8rem; color:#6b7280;">
            برای بررسی آخرین نسخه و بروزرسانی امن، از بخش «لایسنس → بررسی بروزرسانی» استفاده کنید.
        </p>
    @else
        <p style="font-size:.9rem; color:#6b7280;">اطلاعات تم فعال از پلتفرم در دسترس نیست (لایسنس یا ارتباط با پلتفرم را بررسی کنید).</p>
    @endif
</div>

<h2 style="margin-top:1.5rem; font-size:1.1rem;">ماژول‌ها (فعال‌سازی از طریق تنظیمات)</h2>
<p style="color:#64748b; font-size:.9rem; margin:.5rem 0;">فعال یا غیرفعال کردن هر ماژول از طریق فایل <code>.env</code> انجام می‌شود. پس از تغییر، کش را پاک کنید یا سرور را یک‌بار ریستارت کنید.</p>
<ul style="list-style:none;">
    @foreach($modules as $m)
        <li style="padding:.5rem 0;">
            <strong>{{ $m['name'] }}</strong> ({{ $m['key'] }})
            — {{ $m['enabled'] ? 'فعال' : 'غیرفعال' }}
            <span style="color:#64748b; font-size:.85rem;"> — env: {{ config('modules.modules.'.$m['key'].'.env', 'MODULE_'.strtoupper($m['key']).'_ENABLED') }}</span>
        </li>
    @endforeach
</ul>
@endsection
