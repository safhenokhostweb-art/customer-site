{{--
    Shared site-brand renderer (header/footer "logo" slot) used by both public
    layouts. Falls back to the site name as text when no logo is uploaded, so
    every existing site keeps working exactly as before.

    Props: class, style — passed through so each call site keeps its own
    surrounding visual context (dark header, light header, etc.).
--}}
@php
    // TASK 3/3 Phase 6.5C (BLD-25) — resolves through CustomerMediaService,
    // the canonical media authority, covering both a UUID (uploaded after
    // this convergence) and a legacy raw storage path (uploaded before).
    $siteBrandLogoUrl = app(\App\Services\CustomerMediaService::class)->resolveSiteAssetUrl(\App\Models\Setting::get('site_logo', ''));
@endphp
@if($siteBrandLogoUrl)
    <a href="{{ route('home') }}" class="{{ $class ?? '' }}" style="{{ $style ?? '' }}display:inline-flex;align-items:center;">
        <img src="{{ $siteBrandLogoUrl }}"
             alt="{{ \App\Helpers\SiteHelper::siteName() }}"
             style="max-height:2.75rem;width:auto;display:block;">
    </a>
@else
    <a href="{{ route('home') }}" class="{{ $class ?? '' }}" style="{{ $style ?? '' }}">{{ \App\Helpers\SiteHelper::siteName() }}</a>
@endif
