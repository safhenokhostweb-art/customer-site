{{-- Public Canvas runtime: identity/lifecycle/interaction only. Never load
     editor-bootstrap or host structural/editor services on a public page. --}}
@php
    $__publicRuntimeCapabilities = is_array($capabilityProjection['runtime'] ?? null)
        ? $capabilityProjection['runtime']
        : [];
@endphp
@if(in_array('theme-runtime::canvas.runtime', $__publicRuntimeCapabilities, true))
    @include('theme-runtime::canvas.runtime')
@endif
@if(in_array('theme-runtime::canvas.identity', $__publicRuntimeCapabilities, true))
    @include('theme-runtime::canvas.identity')
@endif
@if(in_array('theme-runtime::interaction.runtime', $__publicRuntimeCapabilities, true))
    @includeIf('theme-runtime::interaction.runtime')
@endif
