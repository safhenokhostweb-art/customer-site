{{--
    Shared site-brand renderer (header/footer "logo" slot) used by both public
    layouts. Falls back to the site name as text when no logo is uploaded, so
    every existing site keeps working exactly as before.

    Props: class, style — passed through so each call site keeps its own
    surrounding visual context (dark header, light header, etc.).
--}}
@php
    $siteBrandLogo    = \App\Models\Setting::get('site_logo', '');
    $siteBrandHasLogo = $siteBrandLogo && \Illuminate\Support\Facades\Storage::disk('public')->exists($siteBrandLogo);
@endphp
@if($siteBrandHasLogo)
    <a href="{{ route('home') }}" class="{{ $class ?? '' }}" style="{{ $style ?? '' }}display:inline-flex;align-items:center;">
        <img src="{{ \Illuminate\Support\Facades\Storage::url($siteBrandLogo) }}"
             alt="{{ \App\Helpers\SiteHelper::siteName() }}"
             style="max-height:2.75rem;width:auto;display:block;">
    </a>
@else
    <a href="{{ route('home') }}" class="{{ $class ?? '' }}" style="{{ $style ?? '' }}">{{ \App\Helpers\SiteHelper::siteName() }}</a>
@endif
