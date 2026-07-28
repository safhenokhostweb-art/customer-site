@props(['name'])
@inject('motionRuntime', 'App\Services\MotionGraphics\MotionRuntimeClient')
@php($manifest = $motionRuntime->published($name))

@if($manifest && count($manifest['assets']))
    @php($entry = collect($manifest['assets'])->first(fn ($asset) => in_array($asset['mime'], ['text/html', 'application/javascript', 'text/javascript'], true)))
    @if($entry && $entry['mime'] === 'text/html')
        <iframe src="{{ $entry['url'] }}" title="{{ $name }}" loading="lazy" sandbox="allow-scripts" referrerpolicy="no-referrer" {{ $attributes }}></iframe>
    @elseif($entry)
        <div data-motion="{{ $name }}" data-motion-version="{{ $manifest['version'] }}" {{ $attributes }}></div>
        @once
            <script type="module" src="{{ $entry['url'] }}" integrity="sha256-{{ base64_encode(hex2bin($entry['sha256'])) }}" crossorigin="anonymous"></script>
        @endonce
    @endif
@endif
