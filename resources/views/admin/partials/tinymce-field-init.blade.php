@php
    $selector = $selector ?? '';
    $uploadContext = $uploadContext ?? 'featured';
    $height = (int) ($height ?? 380);
    $tinyMceSrc = asset('vendor/tinymce/tinymce.min.js') . '?v=' . (@filemtime(public_path('vendor/tinymce/tinymce.min.js')) ?: time());
@endphp
@pushOnce('scripts', 'admin-tinymce-base')
@include('admin.partials.tinymce-professional-base')
@endPushOnce
@push('scripts')
<script>
(function () {
    var sel = @json($selector);
    if (!sel || !window.AdminTinyMCE) return;
    var parts = sel.split(',').map(function (s) { return s.trim(); }).filter(Boolean);

    // Shared load-state so multiple tinymce-field-init instances on the
    // same page share one script download.
    if (!window._tinyState) {
        window._tinyState = { loaded: false, loading: false, queue: [] };
    }

    function initEditor() {
        var base = @json(rtrim(asset('vendor/tinymce'), '/'));
        tinymce.init(Object.assign({}, window.AdminTinyMCE.baseOptions(@json($uploadContext), {{ $height }}), {
            selector: sel,
            base_url: base,
            suffix: '.min'
        }));
    }

    function loadAndInit() {
        if (window._tinyState.loaded) { initEditor(); return; }
        window._tinyState.queue.push(initEditor);
        if (window._tinyState.loading) return;
        window._tinyState.loading = true;
        var s = document.createElement('script');
        s.src = @json($tinyMceSrc);
        s.onload = function () {
            window._tinyState.loaded = true;
            window._tinyState.queue.forEach(function (cb) { cb(); });
            window._tinyState.queue = [];
        };
        document.head.appendChild(s);
    }

    document.addEventListener('DOMContentLoaded', function () {
        parts.forEach(function (sel) {
            var el = document.querySelector(sel);
            if (!el) return;
            el.addEventListener('focus',     loadAndInit, { once: true });
            el.addEventListener('mousedown', loadAndInit, { once: true });
        });
    });
}());
</script>
@endpush
