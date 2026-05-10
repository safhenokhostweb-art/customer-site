{{-- استایل‌ها: admin.partials.persian-date-admin-styles در <head> لایوت. اینجا فقط اسکریپت‌ها به @stack('scripts') push می‌شوند. --}}
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
(function () {
    function pdpOptions() {
        return { format: 'YYYY/MM/DD', persianDigit: true, initialValueType: 'persian', autoClose: true };
    }
    /** فقط ورودی‌های قابل‌رؤیت؛ برای فیلدهای داخل display:none مقداردهی نکن (تقویم تمام‌صفحه/خراب). */
    /** ورودی‌های .persian-date-defer را به persian-date-input تبدیل کن (قبل از init). */
    window.promoteDeferredPersianInputs = function (root) {
        if (typeof jQuery === 'undefined') return;
        var $r = root ? jQuery(root) : jQuery(document.body);
        $r.find('.persian-date-defer').removeClass('persian-date-defer').addClass('persian-date-input');
    };
    window.initPersianDateInputs = function (root) {
        if (typeof jQuery === 'undefined' || !jQuery.fn.persianDatepicker) return;
        var $scope = root ? jQuery(root) : null;
        var $inputs = $scope && $scope.length
            ? $scope.find('.persian-date-input').add($scope.filter('.persian-date-input'))
            : jQuery('.persian-date-input');
        $inputs.each(function () {
            var el = jQuery(this);
            if (el.data('pdpInitialized')) return;
            if (!el.is(':visible')) return;
            el.data('pdpInitialized', true);
            el.persianDatepicker(pdpOptions());
        });
    };
    document.addEventListener('DOMContentLoaded', function () {
        window.initPersianDateInputs(document.body);
    });
})();
</script>
@endPushOnce
