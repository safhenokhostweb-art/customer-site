@props(['name', 'params' => []])
@inject('motionReader', 'App\Services\MotionGraphics\MotionRuntimeManifestReader')
@inject('motionResolver', 'App\Services\MotionGraphics\MotionRuntimeAssetResolver')
@inject('motionRenderer', 'App\Services\MotionGraphics\MotionRuntimeRenderer')
@php
    $manifest = $motionReader->read();
    $asset = $motionResolver->resolve($manifest, $name);
    $html = $asset ? $motionRenderer->render($asset, $params ?? []) : null;
@endphp
@if($html)
    {{-- ADR-079: the same motion may appear twice on a page with different
         parameters, so the once-key is the rendered markup, not the asset id. --}}
    @once($asset['asset_id'].':'.md5($html))
        <div {{ $attributes }}>{!! $html !!}</div>
    @endonce
@endif
