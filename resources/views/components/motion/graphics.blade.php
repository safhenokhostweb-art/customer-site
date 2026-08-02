@props(['name'])
@inject('motionReader', 'App\Services\MotionGraphics\MotionRuntimeManifestReader')
@inject('motionResolver', 'App\Services\MotionGraphics\MotionRuntimeAssetResolver')
@inject('motionRenderer', 'App\Services\MotionGraphics\MotionRuntimeRenderer')
@php
    $manifest = $motionReader->read();
    $asset = $motionResolver->resolve($manifest, $name);
    $html = $asset ? $motionRenderer->render($asset) : null;
@endphp
@if($html)
    @once($asset['asset_id'])
        <div {{ $attributes }}>{!! $html !!}</div>
    @endonce
@endif
