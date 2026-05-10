{{-- jQuery + datepicker شمسی برای .persian-date-input در تمام پنل ادمین؛ قبل از @yield('content') لود شود تا اسکریپت‌های صفحه بعد از آن اجرا شوند. --}}
@pushOnce('head', 'admin-persian-datepicker-css')
<link rel="stylesheet" href="{{ asset('vendor/persian-datepicker/persian-datepicker.min.css') }}?v={{ @filemtime(public_path('vendor/persian-datepicker/persian-datepicker.min.css')) ?: time() }}">
@endPushOnce

@pushOnce('scripts', 'admin-vendor-jquery')
<script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
@endPushOnce
@pushOnce('scripts', 'admin-vendor-persian-date')
<script src="{{ asset('vendor/persian-date/persian-date.min.js') }}"></script>
@endPushOnce
@pushOnce('scripts', 'admin-vendor-persian-datepicker')
<script src="{{ asset('vendor/persian-datepicker/persian-datepicker.min.js') }}"></script>
@endPushOnce
@pushOnce('scripts', 'admin-persian-date-input-init')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof jQuery === 'undefined' || !jQuery.fn.persianDatepicker) return;
    jQuery('.persian-date-input').each(function () {
        var el = jQuery(this);
        if (el.data('pdpInitialized')) return;
        el.data('pdpInitialized', true);
        el.persianDatepicker({ format: 'YYYY/MM/DD', persianDigit: true, initialValueType: 'persian', autoClose: true });
    });
});
</script>
@endPushOnce
