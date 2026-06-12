{{--
  x-core-ui::icon — Local fallback for production when package not installed.
  On dev: CoreUtilsServiceProvider registers the package path first → package component is used.
  On prod: only this path is registered → LocalIconRegistry is used.
--}}
@props([
    'name'     => '',
    'size'     => null,
    'fallback' => false,
])

@php
    if (class_exists(\SaaSPlatform\CoreUtils\Icons\IconRegistry::class)) {
        $svg = \SaaSPlatform\CoreUtils\Icons\IconRegistry::get($name);
    } else {
        $svg = \App\Icons\LocalIconRegistry::get($name);
    }
    $sizeAttr = $size ? "w-{$size} h-{$size}" : '';
    $classes  = trim('inline-block shrink-0 ' . $sizeAttr);
@endphp

@if($svg)
<svg
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="none"
    aria-hidden="true"
    focusable="false"
    {{ $attributes->merge(['class' => $classes]) }}
>{!! $svg !!}</svg>
@elseif($fallback)
<svg
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="none"
    aria-hidden="true"
    {{ $attributes->merge(['class' => $classes]) }}
><rect stroke="currentColor" fill="none" stroke-width="2" stroke-linejoin="round" x="3" y="3" width="18" height="18" rx="3"/></svg>
@endif
