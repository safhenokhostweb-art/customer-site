@extends('admin.layout')
@section('title', 'حذف ۴ صفر پول ملی')
@section('header', 'حذف ۴ صفر پول ملی')
@section('content')
<div class="admin-page">
    @if(!$available)
        <div class="admin-flash admin-flash--warning">
            <svg fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
            <span>ماژول حذف ۴ صفر پول ملی برای این سایت در دسترس نیست. از پنل پلتفرم دسترسی این ماژول را فعال و سپس لایسنس را بروزرسانی کنید.</span>
        </div>
    @else
        <div class="card">
            <div class="card-header">وضعیت نمایش قیمت‌ها</div>
            <div class="card-body">
                <ul class="admin-section-lead" style="padding-right:1rem; margin:0 0 1rem; list-style:disc;">
                    <li>تا وقتی تیک زیر را نزنید، قیمت‌ها مطابق قبل (بدون حذف ۴ صفر) نمایش داده می‌شوند.</li>
                    <li>با غیرفعال کردن، محصولات قبلی با قیمت بدون حذف ۴ صفر نمایش داده می‌شوند.</li>
                </ul>
                <form method="post" action="{{ route('admin.currency-reform.update') }}" class="admin-form-stack">
                    @csrf
                    <label class="admin-form-inline-check">
                        <input type="checkbox" name="currency_reform_enabled" value="1" class="form-check-input" {{ $toggleEnabled ? 'checked' : '' }}>
                        <span>فعال‌سازی نمایش با حذف ۴ صفر (۴ صفر انتهایی کم‌رنگ)</span>
                    </label>
                    <div>
                        <button type="submit" class="btn btn-primary">ذخیره</button>
                    </div>
                </form>
            </div>
        </div>

        @if($enabled)
            <div class="admin-flash admin-flash--success">
                <svg fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                <span>نمایش با حذف ۴ صفر فعال است و در محاسبات نیز مقدار تقسیم‌شده استفاده می‌شود.</span>
            </div>
        @endif

        <div class="card">
            <div class="card-header">راهنما</div>
            <div class="card-body">
                <ul class="admin-section-lead" style="padding-right:1rem; margin:0; list-style:disc;">
                    <li>نمایش: مثلاً ۵۰۰٬۰۰۰ تومان به صورت <strong>۵۰</strong><span style="opacity:0.4;">۰۰۰۰</span> تومان نمایش داده می‌شود.</li>
                    <li>محاسبات: از مقدار تقسیم‌شده (۵۰) استفاده می‌شود.</li>
                    <li>در فرم محصول: قیمت را به واحد قدیم وارد کنید (مثلاً ۵۰۰۰۰۰) تا نمایش خودکار اعمال شود.</li>
                </ul>
            </div>
        </div>
    @endif
</div>
@endsection
@extends('admin.layout')
@section('title', 'حذف ۴ صفر پول ملی')
@section('header', 'حذف ۴ صفر پول ملی')
@section('content')
<div class="admin-page">
    @if(!$available)
        <div class="admin-flash admin-flash--warning">
            <svg fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
            <span>ماژول حذف ۴ صفر پول ملی برای این سایت در دسترس نیست. از پنل پلتفرم دسترسی این ماژول را فعال و سپس لایسنس را بروزرسانی کنید.</span>
        </div>
    @else
        <div class="card">
            <div class="card-header">وضعیت نمایش قیمت‌ها</div>
            <div class="card-body">
                <ul class="admin-section-lead" style="padding-right:1rem; margin:0 0 1rem; list-style:disc;">
                    <li>تا وقتی تیک زیر را نزنید، قیمت‌ها مطابق قبل (بدون حذف ۴ صفر) نمایش داده می‌شوند.</li>
                    <li>با غیرفعال کردن، محصولات قبلی با قیمت بدون حذف ۴ صفر نمایش داده می‌شوند.</li>
                </ul>
                <form method="post" action="{{ route('admin.currency-reform.update') }}" class="admin-form-stack">
                    @csrf
                    <label class="admin-form-inline-check">
                        <input type="checkbox" name="currency_reform_enabled" value="1" class="form-check-input" {{ $toggleEnabled ? 'checked' : '' }}>
                        <span>فعال‌سازی نمایش با حذف ۴ صفر (۴ صفر انتهایی کم‌رنگ)</span>
                    </label>
                    <div>
                        <button type="submit" class="btn btn-primary">ذخیره</button>
                    </div>
                </form>
            </div>
        </div>

        @if($enabled)
            <div class="admin-flash admin-flash--success">
                <svg fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                <span>نمایش با حذف ۴ صفر فعال است و در محاسبات نیز مقدار تقسیم‌شده استفاده می‌شود.</span>
            </div>
        @endif

        <div class="card">
            <div class="card-header">راهنما</div>
            <div class="card-body">
                <ul class="admin-section-lead" style="padding-right:1rem; margin:0; list-style:disc;">
                    <li>نمایش: مثلاً ۵۰۰٬۰۰۰ تومان به صورت <strong>۵۰</strong><span style="opacity:0.4;">۰۰۰۰</span> تومان نمایش داده می‌شود.</li>
                    <li>محاسبات: از مقدار تقسیم‌شده (۵۰) استفاده می‌شود.</li>
                    <li>در فرم محصول: قیمت را به واحد قدیم وارد کنید (مثلاً ۵۰۰۰۰۰) تا نمایش خودکار اعمال شود.</li>
                </ul>
            </div>
        </div>
    @endif
</div>
@endsection
