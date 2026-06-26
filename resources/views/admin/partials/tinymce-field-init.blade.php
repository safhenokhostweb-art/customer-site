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
        // Register a capture-phase mousedown guard BEFORE tinymce.init() so our
        // handler runs ahead of TinyMCE's own document mousedown listener.
        // When the user clicks a native <select>, this stops TinyMCE from calling
        // editor.getBody().blur() which would steal focus and dismiss the popup.
        if (!window.__adminSelectGuard) {
            window.__adminSelectGuard = true;
            document.addEventListener('mousedown', function (e) {
                if (e.target && e.target.tagName === 'SELECT') {
                    e.stopImmediatePropagation();
                }
            }, true);
        }
        var tinymceBase = @json(rtrim(asset('vendor/tinymce'), '/'));
        tinymce.init(Object.assign({}, window.AdminTinyMCE.baseOptions(@json($uploadContext), {{ $height }}), {
            selector: sel,
            base_url: tinymceBase,
            suffix: '.min'
        }));
    }

    // Lazy init: TinyMCE only loads when user actually focuses/clicks the textarea.
    // This prevents TinyMCE's global document mousedown listener (which it registers
    // on tinymce.init()) from interfering with native <select> dropdowns on the same page.
    parts.forEach(function (s) {
        var el = document.querySelector(s);
        if (!el) return;
        el.addEventListener('focus', initEditor, { once: true });
        el.addEventListener('mousedown', initEditor, { once: true });
    });
});
</script>
@endpush
