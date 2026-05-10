{{-- باید داخل <head> لود شود؛ @push('head') از داخل <main> بعد از @stack('head') اجرا می‌شود و CSS به صفحه نمی‌رسد. --}}
<link rel="stylesheet" href="{{ asset('vendor/persian-datepicker/persian-datepicker.min.css') }}?v={{ @filemtime(public_path('vendor/persian-datepicker/persian-datepicker.min.css')) ?: time() }}">
<style id="admin-persian-datepicker-layout-guard">
/* بلوک‌های مخفی با hidden / .hidden حتماً جمع شوند (اگر utility تیلویند در بیلد نباشد). */
#recipient-date-range[hidden],
#recipient-date-range.hidden,
#recipient-selected[hidden],
#recipient-selected.hidden {
    display: none !important;
}
/* اگر استایل vendor حذف/بلاک شود، بدون این قانون تقویم بسته در انتهای body به‌صورت بلوک دیده می‌شود. */
.datepicker-container.pwt-hide,
.datepicker-container-inline.pwt-hide {
    display: none !important;
    visibility: hidden !important;
    pointer-events: none !important;
}
.datepicker-container:not(.pwt-hide) {
    position: absolute !important;
    z-index: 100000 !important;
}
</style>
