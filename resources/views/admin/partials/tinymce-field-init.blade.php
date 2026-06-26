@php
    $selector = $selector ?? '';
    $uploadContext = $uploadContext ?? 'featured';
    $height = (int) ($height ?? 380);
@endphp
@pushOnce('scripts', 'admin-tinymce-vendor')
@include('admin.partials.tinymce-professional-base')
<script src="{{ asset('vendor/tinymce/tinymce.min.js') }}?v={{ @filemtime(public_path('vendor/tinymce/tinymce.min.js')) ?: time() }}"></script>
@endPushOnce
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var sel = @json($selector);
    if (!sel || typeof tinymce === 'undefined' || !window.AdminTinyMCE) return;
    var parts = sel.split(',').map(function (s) { return s.trim(); }).filter(Boolean);
    var initialized = false;

    function initEditor() {
        if (initialized) return;
        initialized = true;
        var tinymceBase = @json(rtrim(asset('vendor/tinymce'), '/'));
        tinymce.init(Object.assign({}, window.AdminTinyMCE.baseOptions(@json($uploadContext), {{ $height }}), {
            selector: sel,
            base_url: tinymceBase,
            suffix: '.min'
        }));
    }

    // Lazy init: TinyMCE only activates when the user actually focuses/clicks the
    // textarea. Until then, TinyMCE registers NO global document listeners, so its
    // mousedown handler cannot interfere with native <select> dropdowns on the page.
    parts.forEach(function (s) {
        var el = document.querySelector(s);
        if (!el) return;
        el.addEventListener('focus', initEditor, { once: true });
        el.addEventListener('mousedown', initEditor, { once: true });
    });
});
</script>
@endpush
