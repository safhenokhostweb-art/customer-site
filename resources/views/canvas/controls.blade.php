@include('canvas.control-states')
@include('canvas.control-devices')
@include('canvas.controls-runtime')

@php
    $controlsScriptPath = base_path('vendor/saas-platform/modules-theme-runtime/resources/js/canvas-controls.js');
@endphp

@if(file_exists($controlsScriptPath))
<script>
{!! file_get_contents($controlsScriptPath) !!}
</script>
@endif
