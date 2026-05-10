@push('scripts')
@include('admin.partials.tinymce-professional-base')
<script src="{{ asset('vendor/tinymce/tinymce.min.js') }}?v={{ @filemtime(public_path('vendor/tinymce/tinymce.min.js')) ?: time() }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var tinymceBase = @json(rtrim(asset('vendor/tinymce'), '/'));

    if (typeof tinymce !== 'undefined' && window.AdminTinyMCE) {
        var sel = '#post-body-editor';
        if (document.querySelector(sel)) {
            tinymce.init(Object.assign({}, window.AdminTinyMCE.baseOptions('featured', 480), {
                selector: sel,
                base_url: tinymceBase,
                suffix: '.min'
            }));
        }
    }

    var disp = document.getElementById('post-published-at-display');
    var hidden = document.getElementById('post-published-at');
    if (disp && hidden && typeof jQuery !== 'undefined' && jQuery.fn.persianDatepicker && typeof persianDate !== 'undefined') {
        function pad(n) {
            return n < 10 ? '0' + n : String(n);
        }

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
            timePicker: {
                enabled: true,
                meridiem: { enabled: true }
            },
            onSelect: function (unix) {
                hidden.value = isoFromUnix(unix);
            }
        });

        var form = disp.closest('form');
        if (form) {
            form.addEventListener('submit', function () {
                if (!String(disp.value || '').trim()) {
                    hidden.value = '';
                }
            });
        }
    }
});
</script>
@endpush
