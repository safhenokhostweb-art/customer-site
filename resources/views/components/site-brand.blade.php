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
<<<<<<< HEAD
    // Opt-in: no site animates its logo unless it asks (block prop or setting).
    $siteLogoAnim = isset($anim) ? $anim : \App\Models\Setting::get('site_logo_animation', 'none');
=======
    $siteLogoAnim = isset($anim) ? $anim : \App\Models\Setting::get('site_logo_animation', 'rotate_pop');
>>>>>>> 71c527c (feat(customer-site): finalize uncommitted local changes and sync theme builder assets)
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
<<<<<<< HEAD
    {{-- White-label: a site without a logo shows its own name only (no
         industry glyph, no brand colour imposed on every tenant). --}}
    <a href="{{ route('home') }}" class="{{ $class ?? '' }}" style="{{ $style ?? '' }}display:inline-flex;align-items:center;gap:0.6rem;text-decoration:none;color:inherit;font-weight:700;">
=======
    <a href="{{ route('home') }}" class="{{ $class ?? '' }}" style="{{ $style ?? '' }}display:inline-flex;align-items:center;gap:0.6rem;text-decoration:none;color:inherit;font-weight:700;">
        <span class="tb-nav-header-logo-icon{{ $animClass }}" aria-hidden="true" style="color:var(--tb-primary, var(--platform-primary, #FF7900));font-size:1.4rem;display:inline-flex;align-items:center;{{ $durStyle }}">
            <svg width="26" height="26" viewBox="0 0 512 512" fill="currentColor" aria-hidden="true"><path d="M361.5 1.2c5 2.1 8.6 6.6 9.6 11.9L391 121l107.9 19.8c5.3 1 9.8 4.6 11.9 9.6s1 10.9-2.7 14.8L429.2 248l78.8 82.8c3.7 3.9 4.8 9.8 2.7 14.8s-6.6 8.6-11.9 9.6L391 375l-19.8 107.9c-1 5.3-4.6 9.8-9.6 11.9s-10.9 1-14.8-2.7L264 413.2l-82.8 78.8c-3.9 3.7-9.8 4.8-14.8 2.7s-8.6-6.6-9.6-11.9L137 375l-107.9-19.8c-5.3-1-9.8-4.6-11.9-9.6s-1-10.9 2.7-14.8L98.8 248 20 165.2c-3.7-3.9-4.8-9.8-2.7-14.8s6.6-8.6 11.9-9.6L137 121l19.8-107.9c1-5.3 4.6-9.8 9.6-11.9s10.9-1 14.8 2.7L264 82.8l82.8-78.8c3.9-3.7 9.8-4.8 14.8-2.7zM256 160c-53 0-96 43-96 96s43 96 96 96 96-43 96-96-43-96-96-96z"/></svg>
        </span>
>>>>>>> 71c527c (feat(customer-site): finalize uncommitted local changes and sync theme builder assets)
        <span>{{ \App\Helpers\SiteHelper::siteName() }}</span>
    </a>
@endif
