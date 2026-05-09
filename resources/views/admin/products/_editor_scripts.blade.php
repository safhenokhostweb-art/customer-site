@push('head')
<link rel="stylesheet" href="{{ asset('vendor/persian-datepicker/persian-datepicker.min.css') }}?v={{ @filemtime(public_path('vendor/persian-datepicker/persian-datepicker.min.css')) ?: time() }}">
@endpush

@push('scripts')
@include('admin.partials.tinymce-professional-base')
<script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('vendor/persian-date/persian-date.min.js') }}"></script>
<script src="{{ asset('vendor/persian-datepicker/persian-datepicker.min.js') }}"></script>
<script src="{{ asset('vendor/tinymce/tinymce.min.js') }}?v={{ @filemtime(public_path('vendor/tinymce/tinymce.min.js')) ?: time() }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var tinymceBase = @json(rtrim(asset('vendor/tinymce'), '/'));

    if (typeof tinymce !== 'undefined' && window.AdminTinyMCE) {
        var staticSel = [
            '#product-description',
            '#product-shipping',
            '#product-returns',
            '#product-warranty',
            '#product-guarantee',
            '#product-sizechart-html'
        ].join(', ');

        if (document.querySelector(staticSel)) {
            tinymce.init(Object.assign({}, window.AdminTinyMCE.baseOptions('product', 320), {
                selector: staticSel,
                base_url: tinymceBase,
                suffix: '.min'
            }));
        }
    }

    var faqRoot = document.getElementById('faq-items-root');
    var faqAdd = document.getElementById('faq-add-row');
    if (faqRoot && typeof tinymce !== 'undefined' && window.AdminTinyMCE) {
        function initFaqEditor(textarea) {
            if (!textarea) return;
            if (textarea.id && tinymce.get(textarea.id)) return;
            tinymce.init(Object.assign({}, window.AdminTinyMCE.baseOptions('product', 260), {
                target: textarea,
                base_url: tinymceBase,
                suffix: '.min'
            }));
        }

        faqRoot.querySelectorAll('.faq-answer-editor').forEach(function (ta) {
            initFaqEditor(ta);
        });

        if (faqAdd) {
            faqAdd.addEventListener('click', function () {
                var rows = faqRoot.querySelectorAll('[data-faq-row]');
                var idx = rows.length;
                var wrap = document.createElement('div');
                wrap.className = 'faq-row card mb-3 p-3';
                wrap.setAttribute('data-faq-row', '1');
                wrap.innerHTML =
                    '<div class="flex justify-between items-center mb-2 gap-2">' +
                        '<span class="font-semibold text-sm">سؤال متداول</span>' +
                        '<button type="button" class="btn btn-danger btn-sm faq-remove-row">حذف</button>' +
                    '</div>' +
                    '<div class="form-group mb-2">' +
                        '<label class="form-label">سؤال</label>' +
                        '<input type="text" class="w-full" name="faq_items[' + idx + '][q]" value="" autocomplete="off">' +
                    '</div>' +
                    '<div class="form-group">' +
                        '<label class="form-label">پاسخ</label>' +
                        '<textarea id="faq_answer_' + idx + '" class="faq-answer-editor w-full" name="faq_items[' + idx + '][a]" rows="6"></textarea>' +
                    '</div>';
                faqRoot.appendChild(wrap);
                initFaqEditor(wrap.querySelector('.faq-answer-editor'));
            });
        }

        faqRoot.addEventListener('click', function (e) {
            var btn = e.target.closest('.faq-remove-row');
            if (!btn) return;
            var row = btn.closest('[data-faq-row]');
            if (!row) return;
            var ta = row.querySelector('.faq-answer-editor');
            if (ta && ta.id && tinymce.get(ta.id)) {
                tinymce.remove('#' + ta.id);
            }
            row.remove();
        });
    }

    var saleDisp = document.getElementById('product-sale-ends-display');
    var saleHidden = document.getElementById('product-sale-ends-hidden');
    if (!saleDisp || !saleHidden || typeof jQuery === 'undefined' || !jQuery.fn.persianDatepicker || typeof persianDate === 'undefined') {
        return;
    }

    function pad(n) {
        return n < 10 ? '0' + n : String(n);
    }

    function isoFromUnix(unix) {
        var g = new persianDate(unix).toDate();
        return g.getFullYear() + '-' + pad(g.getMonth() + 1) + '-' + pad(g.getDate()) + ' ' + pad(g.getHours()) + ':' + pad(g.getMinutes()) + ':00';
    }

    jQuery(saleDisp).persianDatepicker({
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
            saleHidden.value = isoFromUnix(unix);
        }
    });

    var form = saleDisp.closest('form');
    if (form) {
        form.addEventListener('submit', function () {
            if (!String(saleDisp.value || '').trim()) {
                saleHidden.value = '';
            }
        });
    }
});
</script>
@endpush
