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
    // Opt-in: no site animates its logo unless it asks (block prop or setting).
    $siteLogoAnim = isset($anim) ? $anim : \App\Models\Setting::get('site_logo_animation', 'none');
    $animClass = ($siteLogoAnim && $siteLogoAnim !== 'none') ? ' tb-logo-anim--' . $siteLogoAnim : '';
    $animDurVal = isset($animDur) ? (is_numeric($animDur) ? $animDur . 's' : $animDur) : null;
    $durStyle = $animDurVal ? ';--tb-hdr-logo-anim-dur:' . $animDurVal : '';
@endphp
@if($siteBrandLogoUrl)
    <a href="{{ route('home') }}" class="{{ $class ?? '' }}" style="{{ $style ?? '' }}display:inline-flex;align-items:center;gap:0.6rem;">
        <img src="{{ $siteBrandLogoUrl }}"
             alt="{{ \App\Helpers\SiteHelper::siteName() }}"
             class="tb-nav-header-logo-img{{ $animClass }}"
             style="max-height:2.75rem;width:auto;display:block;{{ $durStyle }}">
    </a>
@else
    {{-- White-label: a site without a logo shows its own name only (no
         industry glyph, no brand colour imposed on every tenant). --}}
    <a href="{{ route('home') }}" class="{{ $class ?? '' }}" style="{{ $style ?? '' }}display:inline-flex;align-items:center;gap:0.6rem;text-decoration:none;color:inherit;font-weight:700;">
        <span>{{ \App\Helpers\SiteHelper::siteName() }}</span>
    </a>
@endif
