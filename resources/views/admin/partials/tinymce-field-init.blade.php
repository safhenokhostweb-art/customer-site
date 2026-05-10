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
    var any = parts.some(function (s) { return document.querySelector(s); });
    if (!any) return;
    var tinymceBase = @json(rtrim(asset('vendor/tinymce'), '/'));
    tinymce.init(Object.assign({}, window.AdminTinyMCE.baseOptions(@json($uploadContext), {{ $height }}), {
        selector: sel,
        base_url: tinymceBase,
        suffix: '.min'
    }));
});
</script>
@endpush
