@include('canvas.control-states')
@include('canvas.control-devices')

@php
    $controlsScriptPath = base_path('vendor/saas-platform/modules-theme-runtime/resources/js/canvas-controls.js');
@endphp

@if(file_exists($controlsScriptPath))
<script>
{!! file_get_contents($controlsScriptPath) !!}
</script>
@endif
