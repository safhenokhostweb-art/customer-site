@push('head')
<link rel="stylesheet" href="{{ asset('vendor/persian-datepicker/persian-datepicker.min.css') }}?v={{ @filemtime(public_path('vendor/persian-datepicker/persian-datepicker.min.css')) ?: time() }}">
@endpush
@push('scripts')
<script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('vendor/persian-date/persian-date.min.js') }}"></script>
<script src="{{ asset('vendor/persian-datepicker/persian-datepicker.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var disp = document.getElementById('menu-go-live-display');
    var hidden = document.getElementById('menu-go-live-hidden');
    if (!disp || !hidden || typeof jQuery === 'undefined' || !jQuery.fn.persianDatepicker || typeof persianDate === 'undefined') return;

    function pad(n) { return n < 10 ? '0' + n : String(n); }
    function isoFromUnix(unix) {
        var g = new persianDate(unix).toDate();
        return g.getFullYear() + '-' + pad(g.getMonth() + 1) + '-' + pad(g.getDate()) + ' ' + pad(g.getHours()) + ':' + pad(g.getMinutes()) + ':00';
    }
    jQuery(disp).persianDatepicker({
        format: 'YYYY/MM/DD HH:mm',
        initialValueType: 'persian',
        persianDigit: true,
        autoClose: true,
        calendar: { persian: { locale: 'fa' } },
        timePicker: { enabled: true, meridiem: { enabled: true } },
        onSelect: function (unix) { hidden.value = isoFromUnix(unix); }
    });
    var form = disp.closest('form');
    if (form) {
        form.addEventListener('submit', function () {
            if (!String(disp.value || '').trim()) hidden.value = '';
        });
    }
});
</script>
@endpush
