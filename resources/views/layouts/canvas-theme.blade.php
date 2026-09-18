<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $siteTitle  = \App\Helpers\SiteHelper::siteName();
        $fullTitle  = ($pageTitle ?? $siteTitle) . ($pageTitle ? ' — ' . $siteTitle : '');
        $gs         = $globalStyles ?? [];
    @endphp
    <title>{{ $fullTitle }}</title>
    <x-seo-meta :title="$fullTitle" />

    {{-- فونت آفلاین Vazirmatn --}}
    <link rel="preload" as="font" href="{{ asset('assets/fonts/vazirmatn/Vazirmatn-Regular.woff2') }}" type="font/woff2" crossorigin>
    <link rel="preload" as="font" href="{{ asset('assets/fonts/vazirmatn/Vazirmatn-Bold.woff2') }}" type="font/woff2" crossorigin>
    <style>@include('partials.vazirmatn-font-faces-inline')</style>

    @if(file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <link rel="stylesheet" href="{{ asset('css/customer-admin-layout.css') }}?v={{ @filemtime(public_path('css/customer-admin-layout.css')) ?: time() }}">
    @endif

    {{-- Site preset / custom theme base tokens --}}
    @include('partials.theme-engine-tokens')
    {{-- CSS متغیرهای قالب canvas (اولویت بالاتر از preset) --}}
    <style>
        {{-- Phase 2h (ADR-029): the same shared ThemeTokenNormalizer
             saas-platform's GlobalStyleEngine now delegates to — the same
             global_styles input produces the same --tb-* values on both
             hosts. This used to be a hand-rolled, materially incomplete
             subset (no --tb-accent, no dark mode, no custom_css, a
             different --tb-text-muted default) maintained independently
             here; see ADR-029 for the full before/after. --}}
        {!! app(\SaaSPlatform\ThemeRuntime\ThemeTokenNormalizer::class)->renderCssBlock($gs) !!}
        :root { --platform-primary: var(--tb-primary); }

        {{-- Shared structural/responsive/marquee contract (ADR-029) — the
             rules below it (.tb-container, .tb-grid, .tb-padding-*, etc.)
             are this host's own remaining, not-yet-migrated visual CSS. --}}
        @include('theme-runtime::css.contract')

        body {
            font-family: var(--tb-font-body);
            background: var(--tb-background);
            color: var(--tb-text);
            margin: 0;
            min-height: 100vh;
            font-size: 16px;
        }

        /* ─── هدر canvas ─────────────────────────────────────────────── */
        .canvas-site-header {
            background: var(--tb-surface, #fff);
            border-bottom: 1px solid rgba(0,0,0,0.08);
            box-shadow: var(--tb-shadow-sm);
        }
        .canvas-site-header .tb-site-header {
            border-bottom: none;
            box-shadow: none;
        }

        /* ─── محتوای اصلی ────────────────────────────────────────────── */
        .canvas-main {
            min-height: 60vh;
        }

        /* ─── فوتر canvas ────────────────────────────────────────────── */
        .canvas-site-footer {
            background: var(--tb-surface, #fff);
            border-top: 1px solid #e5e7eb;
        }

        /* ─── ThemeBuilder base styles ────────────────────────────────── */
        .tb-section { padding: 2.5rem 0; }
        .tb-section-inner { width: 100%; }
        .tb-container { max-width: var(--tb-container, 80rem); margin: 0 auto; padding: 0 1rem; }
        .tb-hero { padding: 3rem 1.5rem; text-align: center; border-radius: var(--tb-radius, 0.75rem); background: var(--tb-primary, #4f46e5); color: var(--tb-on-primary, #fff); margin-bottom: 2.5rem; }
        .tb-hero-inner { max-width: 56rem; margin: 0 auto; }
        .tb-hero-title { font-size: clamp(1.75rem, 4vw, 2.5rem); font-weight: 800; margin: 0 0 0.75rem; line-height: 1.2; }
        .tb-hero-subtitle { font-size: 1.1rem; opacity: 0.95; max-width: 42rem; margin: 0 auto 1.25rem; line-height: 1.6; }
        .tb-btn { display: inline-block; padding: 0.75rem 1.5rem; border-radius: var(--tb-radius, 0.75rem); text-decoration: none; font-weight: 600; font-size: 1rem; transition: opacity 0.2s, transform 0.15s; cursor: pointer; }
        .tb-btn:hover { opacity: 0.9; transform: translateY(-1px); }
        .tb-btn-primary { background: var(--tb-primary, #4f46e5); color: var(--tb-on-primary, #fff) !important; }
        .tb-btn-secondary { background: transparent; color: var(--tb-primary-text, #4f46e5); border: 2px solid currentColor; }
        .tb-heading { margin: 0 0 0.75rem; font-weight: 700; color: var(--tb-text, #111827); line-height: 1.3; display: block; }
        h1.tb-heading { font-size: clamp(1.75rem, 3vw, 2.25rem); }
        h2.tb-heading { font-size: clamp(1.5rem, 2.5vw, 1.875rem); }
        h3.tb-heading { font-size: 1.25rem; }
        h4.tb-heading, h5.tb-heading, h6.tb-heading { font-size: 1.125rem; }
        .tb-section-title { margin-bottom: 1.25rem; font-size: 1.5rem; }
        .tb-text { line-height: 1.75; color: var(--tb-text-muted, #4b5563); }
        .tb-text.prose { max-width: 65ch; }
        .tb-image { display: block; max-width: 100%; margin: 0 auto 1.5rem; }
        .tb-image .tb-img { display: block; max-width: 100%; height: auto; margin: 0 auto; max-height: 520px; object-fit: contain; }
        .tb-image-caption { text-align: center; font-size: 0.85rem; color: #6b7280; margin-top: 0.5rem; }
        {{-- .tb-divider now in the shared contract included above. --}}
        .tb-spacer { display: block; }

        {{-- .tb-grid / .tb-grid-cols-* / breakpoints now in the shared
             contract included above. --}}

        /* پس‌زمینه */
        .tb-bg-background { background-color: var(--tb-background, #f9fafb) !important; }
        .tb-bg-surface    { background-color: var(--tb-surface, #fff) !important; }
        .tb-bg-primary    { background-color: var(--tb-primary, #4f46e5) !important; color: var(--tb-on-primary, #fff) !important; }
        .tb-bg-secondary  { background-color: var(--tb-secondary, #7c3aed) !important; color: var(--tb-on-secondary, #fff) !important; }
        .tb-bg-text       { background-color: var(--tb-text, #111827) !important; color: #fff !important; }

        {{-- .tb-padding-* scale now in the shared contract included above. --}}

        /* hero align */
        .tb-hero.tb-align-left    { text-align: right; }
        .tb-hero.tb-align-right   { text-align: left; }
        .tb-hero.tb-align-center  { text-align: center; }

        /* CTA */
        .tb-cta { padding: 2.5rem 1.5rem; border-radius: var(--tb-radius, 0.75rem); text-align: center; background: var(--tb-primary, #4f46e5); color: var(--tb-on-primary, #fff); }
        .tb-cta-title    { font-size: 1.75rem; font-weight: 800; margin: 0 0 0.5rem; }
        .tb-cta-subtitle { font-size: 1rem; opacity: 0.9; margin: 0 0 1rem; }
        .tb-cta .tb-btn  { background: #fff !important; color: var(--tb-primary-text, #4f46e5) !important; }

        /* کارت محصول */
        .tb-product-card { background: var(--tb-surface, #fff); border-radius: var(--tb-radius, 0.75rem); border: 1px solid #e5e7eb; padding: 0; overflow: hidden; box-shadow: var(--tb-shadow-sm); text-decoration: none; color: inherit; display: flex; flex-direction: column; transition: box-shadow 0.2s, transform 0.15s; }
        .tb-product-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,0.12); transform: translateY(-2px); }
        .tb-product-card-img { aspect-ratio: 1; background: #f3f4f6; overflow: hidden; display: flex; align-items: center; justify-content: center; }
        .tb-product-card-img img { width: 100%; height: 100%; object-fit: cover; }
        .tb-product-card-noimg { font-size: 2.5rem; color: #9ca3af; }
        .tb-product-card-inner { padding: 1rem; flex: 1; }
        .tb-product-title { font-weight: 700; font-size: 1rem; margin: 0 0 0.4rem; color: var(--tb-text, #111827); }
        .tb-product-price { font-weight: 700; color: var(--tb-primary-text, #4f46e5); font-size: 0.95rem; }
        .tb-product-grid-empty { padding: 2rem; text-align: center; background: #f9fafb; border-radius: 0.75rem; border: 1px dashed #d1d5db; }
        .tb-product-grid-empty p { margin-bottom: 1rem; color: #6b7280; }

        /* وبلاگ */
        .tb-blog-card { display: flex; flex-direction: column; background: var(--tb-surface, #fff); border-radius: var(--tb-radius, 0.75rem); overflow: hidden; border: 1px solid #e5e7eb; text-decoration: none; color: inherit; box-shadow: var(--tb-shadow-sm); transition: box-shadow 0.2s, transform 0.15s; }
        .tb-blog-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,0.12); transform: translateY(-2px); }
        .tb-blog-card-img { aspect-ratio: 16/10; background: #f3f4f6; overflow: hidden; }
        .tb-blog-card-img img { width: 100%; height: 100%; object-fit: cover; }
        .tb-blog-card-noimg { display: flex; align-items: center; justify-content: center; font-size: 2rem; color: #9ca3af; min-height: 120px; }
        .tb-blog-card-body { padding: 1.25rem; flex: 1; }
        .tb-blog-card-title  { font-size: 1.05rem; font-weight: 700; margin: 0 0 0.4rem; line-height: 1.4; }
        {{-- TASK 3/3 Phase 6: #9ca3af on this card's #f9fafb-ish background
             measured 2.42:1 via real axe-core scan (WCAG AA needs 4.5:1) —
             the same class of failure ADR-032/ThemeTokenNormalizer already
             fixed once for --tb-text-muted's OWN default; this literal
             hardcoded gray was a separate, never-converged copy of the
             same mistake. --}}
        .tb-blog-card-meta   { font-size: 0.8rem; color: var(--tb-text-muted, #4b5563); margin: 0 0 0.4rem; }
        .tb-blog-card-excerpt { font-size: 0.88rem; color: #6b7280; margin: 0; line-height: 1.6; }

        /* ناوبری داخل canvas */
        .tb-nav { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem 1.25rem; }
        .tb-nav a { text-decoration: none; color: var(--tb-text, #1f2937); font-weight: 500; padding: 0.3rem 0; }
        .tb-nav a:hover { color: var(--tb-primary-text, #4f46e5); }
        .tb-social-icons { display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; }
        .tb-social-link { text-decoration: none; color: var(--tb-text, #374151); font-weight: 500; }
        .tb-social-link:hover { color: var(--tb-primary-text, #4f46e5); }

        /* هدر و فوتر site */
        .tb-site-header { padding: 0 1rem; background: var(--tb-surface, #fff); border-bottom: 1px solid rgba(0,0,0,0.08); box-shadow: var(--tb-shadow-sm); }
        .tb-site-header.tb-header-sticky { position: sticky; top: 0; z-index: 100; }
        .tb-header-inner { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; max-width: var(--tb-container, 80rem); margin: 0 auto; min-height: 4rem; }
        .tb-header-brand { font-size: 1.25rem; font-weight: 700; }
        .tb-header-brand a { color: var(--tb-primary-text, #4f46e5); text-decoration: none; }
        .tb-site-header .tb-section { padding-top: 0.5rem !important; padding-bottom: 0.5rem !important; flex: 1; }
        .tb-site-header .tb-container { display: flex; align-items: center; justify-content: flex-end; flex-wrap: wrap; gap: 1rem; }
        .tb-header-nav-wrap { flex: 1; display: flex; align-items: center; justify-content: flex-end; }
        .tb-header-nav-wrap .tb-section { padding: 0 !important; }
        .tb-menu-btn { display: none; padding: 0.5rem; border: none; background: transparent; cursor: pointer; color: inherit; border-radius: 0.5rem; font-size: 1.4rem; }
        .tb-mobile-menu { display: none; border-top: 1px solid rgba(0,0,0,0.08); padding: 1rem; background: var(--tb-surface, #fff); }
        .tb-mobile-menu.is-open { display: block; }
        .tb-mobile-menu-inner { display: flex; flex-direction: column; gap: 0.5rem; }
        .tb-mobile-menu-inner a { padding: 0.5rem 0; color: var(--tb-text, #1f2937); text-decoration: none; font-weight: 500; display: block; }
        .tb-cart-link { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.5rem 0.75rem; border-radius: 0.5rem; background: var(--tb-primary, #4f46e5); color: #fff !important; text-decoration: none; font-weight: 600; font-size: 0.9rem; }
        .tb-cart-link:hover { opacity: 0.9; }

        /* TASK 3/3 Phase 6 (BLD-19) — module:shop_wishlist_icon,
           module:loyalty_points_badge, module:installment_badge: real,
           working Phase-3 module integrations that had zero CSS anywhere
           (only the generic .tb-empty-state rule incidentally applied to
           the installment badge's own empty state) — rendered as bare
           unstyled text links. Mirrors the already-styled .tb-cart-link
           pattern for visual consistency across every commerce-adjacent
           header/shop control. */
        .tb-wishlist-link, .tb-loyalty-points-link { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.55rem 0.9rem; border-radius: 999px; background: var(--tb-surface, #fff); border: 1px solid #e5e7eb; color: var(--tb-text, #111827) !important; text-decoration: none; font-weight: 600; font-size: 0.85rem; transition: border-color 0.15s, background 0.15s; }
        .tb-wishlist-link:hover, .tb-loyalty-points-link:hover { border-color: var(--tb-primary, #4f46e5); background: var(--tb-surface, #f9fafb); }
        .tb-wishlist-link-icon, .tb-loyalty-points-link-icon { padding: 0.5rem; width: 2.5rem; height: 2.5rem; justify-content: center; }
        .tb-installment-badge { display: inline-flex; align-items: center; gap: 0.5rem; text-decoration: none; }
        .tb-installment-badge-compact { padding: 0.6rem 1rem; border-radius: 0.5rem; background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46 !important; font-weight: 700; font-size: 0.85rem; }
        .tb-installment-badge-icon { font-size: 1rem; }
        .tb-installment-badge.tb-empty-state { display: block; }
        .tb-installment-badge-full { display: block; padding: 1.25rem; border-radius: var(--tb-radius, 0.75rem); background: var(--tb-surface, #fff); border: 1px solid #e5e7eb; }
        .tb-installment-plans-list { display: flex; flex-direction: column; gap: 0.6rem; margin-bottom: 1rem; }
        .tb-installment-plan-row { display: flex; justify-content: space-between; gap: 1rem; padding: 0.6rem 0.75rem; border-radius: 0.5rem; background: var(--tb-background, #f9fafb); font-size: 0.85rem; }
        .tb-installment-plan-name { font-weight: 700; }
        .tb-installment-plan-detail { color: var(--tb-text-muted, #4b5563); }
        .tb-site-footer { border-top: 1px solid #e5e7eb; }
        .tb-site-footer .tb-section { padding-top: 2rem !important; padding-bottom: 2rem !important; }
        @media (max-width: 767px) {
            .tb-header-nav-wrap { display: none; }
            .tb-menu-btn { display: block; }
        }
        @media (min-width: 768px) {
            .tb-mobile-menu { display: none !important; }
        }

        /* TASK 3/3 Phase 6 (BLD-18) — nav_header / nav_footer: the primary
           header and footer component every one of the 6+6 presets uses in
           some combination had ZERO CSS anywhere in this file, rendering
           as bare unstyled <a> tags with no spacing at all. Fixed once,
           canonically, for every preset and every page at the same time —
           see BlockRenderer::renderNavHeader()/renderNavFooter(). */
        .tb-nav-header-inner { display: flex; align-items: center; gap: 1.5rem; min-height: 4.25rem; flex-wrap: wrap; padding: 0.5rem 0; }
        .tb-nav-header-logo { font-size: 1.35rem; font-weight: 800; text-decoration: none; color: inherit; letter-spacing: -0.01em; }
        .tb-nav-header-links { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem 1.5rem; margin-inline-start: auto; }
        .tb-nav-header-link { text-decoration: none; color: inherit; font-weight: 600; font-size: 0.95rem; opacity: 0.85; padding: 0.4rem 0; position: relative; transition: opacity 0.15s; }
        .tb-nav-header-link:hover { opacity: 1; }
        .tb-nav-header-link::after { content: ''; position: absolute; inset-inline: 0; bottom: -2px; height: 2px; background: currentColor; transform: scaleX(0); transition: transform 0.2s; transform-origin: center; }
        .tb-nav-header-link:hover::after { transform: scaleX(1); }
        /* TASK 3/3 Phase 6.5B — nav_source=menu renders real Menu-tree
           markup (partials/site-menu.blade.php's own .nav-link anchors),
           not this block's own .tb-nav-header-link anchors. A descendant
           rule (not a class rename in the shared partial, which other
           contexts also use) keeps both sources visually consistent. */
        .tb-nav-header-links > a { text-decoration: none; color: inherit; font-weight: 600; font-size: 0.95rem; opacity: 0.85; padding: 0.4rem 0; }
        .tb-nav-header-links > a:hover { opacity: 1; }
        .tb-nav-header-actions { display: flex; align-items: center; gap: 0.85rem; flex-wrap: wrap; }
        .tb-nav-header-search { position: relative; }
        .tb-nav-header-search-input { padding: 0.5rem 2.25rem 0.5rem 0.9rem; border: 1px solid rgba(0,0,0,0.12); border-radius: 999px; font-family: var(--tb-font-body); font-size: 0.85rem; background: rgba(0,0,0,0.03); color: inherit; width: 11rem; max-width: 40vw; }
        .tb-nav-header-search-input::placeholder { color: currentColor; opacity: 0.55; }
        .tb-nav-header-phone { text-decoration: none; color: inherit; font-weight: 700; font-size: 0.9rem; opacity: 0.9; white-space: nowrap; }
        .tb-nav-header-icon-link { display: inline-flex; align-items: center; justify-content: center; width: 2.25rem; height: 2.25rem; border-radius: 999px; background: rgba(0,0,0,0.05); text-decoration: none; font-size: 1.05rem; transition: background 0.15s; }
        .tb-nav-header-icon-link:hover { background: rgba(0,0,0,0.1); }
        .tb-nav-header-cta { padding: 0.55rem 1.25rem; font-size: 0.9rem; white-space: nowrap; }
        @media (max-width: 1023px) { .tb-nav-header-search-input { width: 8rem; } }

        .tb-nav-footer .tb-container { padding-top: 3.5rem; padding-bottom: 2rem; }
        .tb-nav-footer-brand { margin-bottom: 2rem; }
        .tb-nav-footer-logo { font-size: 1.25rem; font-weight: 800; margin: 0 0 0.5rem; }
        .tb-nav-footer-tagline { font-size: 0.9rem; opacity: 0.75; line-height: 1.7; margin: 0; max-width: 32rem; }
        .tb-nav-footer-cols { display: grid; grid-template-columns: repeat(auto-fit, minmax(9rem, 1fr)); gap: 2rem; margin-bottom: 2.5rem; }
        .tb-nav-footer-col-title { font-size: 0.85rem; font-weight: 700; margin: 0 0 1rem; opacity: 0.6; text-transform: uppercase; letter-spacing: 0.04em; }
        .tb-nav-footer-links { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 0.7rem; }
        .tb-nav-footer-links a { text-decoration: none; color: inherit; opacity: 0.8; font-size: 0.9rem; transition: opacity 0.15s; }
        .tb-nav-footer-links a:hover { opacity: 1; }
        .tb-nav-footer-bottom { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; padding-top: 1.75rem; border-top: 1px solid rgba(255,255,255,0.1); }
        .tb-nav-footer-copyright { margin: 0; font-size: 0.85rem; opacity: 0.65; }
        .tb-nav-footer-legal-link { text-decoration: none; color: inherit; opacity: 0.7; font-size: 0.85rem; margin-inline-start: 1.25rem; }
        .tb-nav-footer-legal-link:hover { opacity: 1; }
        @media (max-width: 640px) {
            .tb-nav-footer .tb-container { padding-top: 2.5rem; }
            .tb-nav-footer-bottom { flex-direction: column; align-items: flex-start; gap: 0.75rem; }
            .tb-nav-footer-legal-link { margin-inline-start: 0; margin-inline-end: 1rem; }
        }

        {{-- FAQ / accordion / tabs now in the shared contract included above. --}}

        /* placeholder (خطا) */
        .tb-placeholder { min-height: 2rem; padding: 0.5rem 0.75rem; border-radius: 0.5rem; font-size: 0.875rem; }
        .tb-placeholder-invalid { background: #fef3c7; border: 1px dashed #d97706; color: #92400e; }
        .tb-placeholder-error   { background: #fee2e2; border: 1px dashed #dc2626; color: #991b1b; }

        /* row/column */
        .tb-row { display: flex; flex-wrap: wrap; gap: 1rem; }
        .tb-column { min-width: 0; }
        .tb-col-full { flex: 1 1 100%; }
        .tb-col-1-2 { flex: 1 1 calc(50% - 0.5rem); }
        .tb-col-1-3 { flex: 1 1 calc(33.333% - 0.67rem); }
        .tb-col-2-3 { flex: 1 1 calc(66.666% - 0.67rem); }
        .tb-col-1-4 { flex: 1 1 calc(25% - 0.75rem); }
        .tb-col-3-4 { flex: 1 1 calc(75% - 0.75rem); }
        @media (max-width: 767px) {
            .tb-col-1-2, .tb-col-1-3, .tb-col-2-3, .tb-col-1-4, .tb-col-3-4 { flex: 1 1 100%; }
        }

        /* module notice */
        .tb-empty-state { padding: 2rem; text-align: center; background: #f9fafb; border-radius: 0.75rem; border: 1px dashed #d1d5db; }
        .tb-empty-state p { margin: 0 0 1rem; color: #6b7280; }

        /* skip link */
        .canvas-skip-link { position: fixed; top: -5rem; right: 1rem; z-index: 999; padding: 0.5rem 1rem; background: #1f2937; color: #fff; border-radius: 0.5rem; text-decoration: none; }
        .canvas-skip-link:focus { top: 1rem; }
        *:focus-visible { outline: 2px solid var(--tb-primary, #4f46e5); outline-offset: 2px; }

        /* اعلان‌ها */
        .tb-notifications-bar { margin: 0; }

        /* ═══════════════════════════════════════════════════════════════
           NEXUS PRO PREMIUM BLOCKS — کامپوننت‌های قالب پریمیوم
           ═══════════════════════════════════════════════════════════════ */

        /* ─── hero_split ────────────────────────────────────────────── */
        .tb-hero-split { display:flex; align-items:center; gap:3rem; flex-wrap:wrap; }
        .tb-hero-split-text { flex:1 1 300px; }
        .tb-hero-split-visual { flex:1 1 300px; text-align:center; }
        .tb-hero-split-badge { display:inline-block; background:rgba(99,102,241,0.1); color:var(--tb-primary,#6366f1); font-size:0.82rem; font-weight:700; padding:0.3rem 0.9rem; border-radius:999px; border:1px solid rgba(99,102,241,0.25); margin-bottom:1rem; letter-spacing:0.03em; }
        .tb-hero-split-title { font-size:clamp(1.75rem,4vw,3.25rem); font-weight:800; line-height:1.1; margin:0 0 1rem; letter-spacing:-0.02em; color:var(--tb-text,#0f172a); }
        .tb-hero-split-sub { font-size:1.1rem; color:var(--tb-text-muted,#4b5563); line-height:1.75; margin:0 0 1.75rem; max-width:42ch; }
        .tb-hero-split-actions { display:flex; gap:0.75rem; flex-wrap:wrap; align-items:center; }
        .tb-hero-split-img { width:100%; max-width:520px; height:380px; border-radius:1.25rem; object-fit:cover; box-shadow:0 20px 60px rgba(0,0,0,0.15); }
        .tb-hero-split-img-placeholder { width:100%; max-width:520px; height:380px; border-radius:1.25rem; background:linear-gradient(135deg,var(--tb-primary,#6366f1),var(--tb-secondary,#0ea5e9)); display:flex; align-items:center; justify-content:center; font-size:6rem; box-shadow:0 20px 60px rgba(99,102,241,0.25); }
        @media(max-width:767px) { .tb-hero-split{flex-direction:column-reverse;gap:1.5rem;} .tb-hero-split-title{font-size:1.875rem;} .tb-hero-split-img,.tb-hero-split-img-placeholder{max-width:100%;height:220px;} }

        /* ─── hero_gradient ─────────────────────────────────────────── */
        .tb-hero-gradient { text-align:center; padding:5.5rem 1.5rem; position:relative; overflow:hidden; }
        .tb-hero-gradient-inner { max-width:58rem; margin:0 auto; position:relative; z-index:1; }
        .tb-hero-gradient-badge { display:inline-block; background:rgba(255,255,255,0.18); backdrop-filter:blur(8px); -webkit-backdrop-filter:blur(8px); border:1px solid rgba(255,255,255,0.3); font-size:0.82rem; font-weight:700; padding:0.3rem 1.1rem; border-radius:999px; margin-bottom:1.25rem; }
        .tb-hero-gradient-title { font-size:clamp(2rem,5.5vw,4.5rem); font-weight:800; line-height:1.05; margin:0 0 1.25rem; letter-spacing:-0.03em; }
        .tb-hero-gradient-sub { font-size:1.15rem; opacity:0.9; line-height:1.7; margin:0 auto; max-width:50ch; }
        @media(max-width:600px) { .tb-hero-gradient{padding:3.5rem 1rem;} .tb-hero-gradient-title{font-size:2rem;} }

        /* ─── features_grid ─────────────────────────────────────────── */
        .tb-features-grid { }
        .tb-features-section-header { text-align:center; margin-bottom:2.5rem; }
        .tb-features-section-title { font-size:clamp(1.5rem,3vw,2rem); font-weight:800; margin:0 0 0.75rem; color:var(--tb-text,#0f172a); letter-spacing:-0.01em; }
        .tb-features-section-sub { color:var(--tb-text-muted,#4b5563); font-size:1rem; max-width:54ch; margin:0 auto; line-height:1.65; }
        .tb-feature-card { padding:1.75rem; border-radius:var(--tb-radius,0.75rem); transition:transform 0.2s,box-shadow 0.2s; }
        .tb-feature-card-cards { border:1px solid #e5e7eb; background:var(--tb-surface,#fff); box-shadow:var(--tb-shadow-sm); }
        .tb-feature-card-bordered { border:2px solid #e5e7eb; background:transparent; }
        .tb-feature-card-minimal { border:none; background:transparent; }
        .tb-feature-card:hover { transform:translateY(-4px); box-shadow:0 12px 32px rgba(0,0,0,0.1); }
        .tb-feature-icon { font-size:2.5rem; margin-bottom:1rem; line-height:1; }
        .tb-feature-title { font-size:1.05rem; font-weight:700; margin:0 0 0.5rem; color:var(--tb-text,#0f172a); }
        .tb-feature-text { color:var(--tb-text-muted,#4b5563); font-size:0.875rem; line-height:1.7; margin:0; }

        /* ─── stats_row ─────────────────────────────────────────────── */
        .tb-stats-row { }
        .tb-stats-grid { display:flex; flex-wrap:wrap; justify-content:center; align-items:center; }
        .tb-stat-item { text-align:center; padding:1.5rem 2rem; flex:1 1 160px; }
        .tb-stat-divider { width:1px; height:80px; background:rgba(0,0,0,0.1); align-self:center; flex-shrink:0; }
        .tb-stat-value { font-size:clamp(2.25rem,4.5vw,3.5rem); font-weight:800; color:var(--tb-primary-text,#6366f1); line-height:1; display:block; }
        .tb-stat-suffix { font-size:1.5rem; font-weight:700; color:var(--tb-primary-text,#6366f1); }
        .tb-stat-label { font-size:0.9rem; color:var(--tb-text-muted,#4b5563); margin-top:0.4rem; display:block; font-weight:500; }
        @media(max-width:600px) { .tb-stat-item{flex:1 1 130px;padding:1rem 0.75rem;} .tb-stat-divider{display:none;} }

        /* ─── client_logos ──────────────────────────────────────────── */
        .tb-client-logos { overflow:hidden; }
        .tb-client-logos-title { text-align:center; font-size:0.8rem; color:var(--tb-text-muted,#6b7280); margin-bottom:1.25rem; text-transform:uppercase; letter-spacing:0.1em; font-weight:600; }
        .tb-logos-marquee-wrap { overflow:hidden; position:relative; }
        .tb-logos-marquee-wrap::before,.tb-logos-marquee-wrap::after { content:''; position:absolute; top:0; bottom:0; width:100px; z-index:2; pointer-events:none; }
        .tb-logos-marquee-wrap::before { right:0; background:linear-gradient(to left,var(--tb-background,#fff) 0%,transparent 100%); }
        .tb-logos-marquee-wrap::after  { left:0;  background:linear-gradient(to right,var(--tb-background,#fff) 0%,transparent 100%); }
        .tb-logos-marquee { display:flex; gap:2rem; width:max-content; animation:tb-marquee 32s linear infinite; align-items:center; padding:0.5rem 0; }
        .tb-logos-marquee:hover { animation-play-state:paused; }
        {{-- Phase 2g (saas-platform ADR-028): #9ca3af (gray-400) on a white
             surface measures ~2.54:1 via real browser contrast verification,
             below WCAG AA's 4.5:1 — and was already inconsistent with
             .tb-client-logos-title's own #6b7280 fallback one line above for
             the same visual context. Aligned to the same, compliant shade
             (~4.83:1 on white). --}}
        .tb-logo-item { font-size:1rem; font-weight:700; color:var(--tb-text-muted,#6b7280); white-space:nowrap; padding:0.5rem 1.25rem; border:1px solid #e5e7eb; border-radius:0.5rem; background:var(--tb-surface,#fff); transition:color 0.2s; }
        .tb-logos-static { display:flex; flex-wrap:wrap; gap:1.5rem; justify-content:center; align-items:center; }
        @keyframes tb-marquee { from{transform:translateX(0)} to{transform:translateX(-50%)} }

        /* ─── process_steps ─────────────────────────────────────────── */
        .tb-process-steps { }
        .tb-steps-grid { display:flex; flex-wrap:wrap; gap:1.5rem; justify-content:center; }
        .tb-step-item { flex:1 1 200px; text-align:center; padding:1.5rem 1.25rem; max-width:240px; }
        .tb-step-number { width:3.25rem; height:3.25rem; border-radius:50%; background:var(--tb-primary,#6366f1); color:#fff; font-size:1.25rem; font-weight:800; display:flex; align-items:center; justify-content:center; margin:0 auto 1rem; box-shadow:0 4px 16px rgba(99,102,241,0.3); }
        .tb-step-title { font-size:1rem; font-weight:700; margin:0 0 0.5rem; color:var(--tb-text,#0f172a); }
        .tb-step-text { font-size:0.875rem; color:var(--tb-text-muted,#4b5563); line-height:1.65; margin:0; }
        @media(max-width:600px) { .tb-step-item{flex:1 1 140px;max-width:none;} }

        /* ─── team_cards ────────────────────────────────────────────── */
        .tb-team-member { background:var(--tb-surface,#fff); border-radius:var(--tb-radius,0.75rem); border:1px solid #e5e7eb; overflow:hidden; text-align:center; box-shadow:var(--tb-shadow-sm); transition:transform 0.2s,box-shadow 0.2s; padding-bottom:1.5rem; }
        .tb-team-member:hover { transform:translateY(-4px); box-shadow:0 12px 32px rgba(0,0,0,0.1); }
        .tb-team-avatar { width:84px; height:84px; border-radius:50%; object-fit:cover; margin:1.75rem auto 1rem; border:3px solid var(--tb-primary,#6366f1); display:block; }
        .tb-team-avatar-placeholder { width:84px; height:84px; border-radius:50%; background:linear-gradient(135deg,var(--tb-primary,#6366f1),var(--tb-secondary,#0ea5e9)); display:flex; align-items:center; justify-content:center; font-size:2rem; font-weight:700; color:#fff; margin:1.75rem auto 1rem; }
        .tb-team-name { font-size:1rem; font-weight:700; margin:0 0 0.25rem; color:var(--tb-text,#0f172a); }
        .tb-team-role { font-size:0.85rem; color:var(--tb-primary-text,#6366f1); font-weight:600; margin:0 0 0.75rem; }
        .tb-team-bio { font-size:0.85rem; color:var(--tb-text-muted,#4b5563); line-height:1.55; margin:0 1.25rem; }

        /* TASK 3/3 Phase 6.5 — the "gallery" block (BlockRenderer::renderGallery())
           had ZERO CSS anywhere on either host: its <img class="tb-gallery-img">
           rendered at raw intrinsic pixel size with no max-width, which the
           real browser-backed ThemeCommercialQualityGate ResponsiveReadinessCheck
           caught as catastrophic horizontal overflow (232px tablet, 610px
           mobile) once Redis was restored and the check could actually run
           instead of silently reporting "unverified". A missing reusable
           component, not a composition problem — fixed once, canonically. */
        .tb-gallery-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:1.25rem; }
        .tb-gallery-cell { border-radius:var(--tb-radius,0.75rem); overflow:hidden; box-shadow:var(--tb-shadow-sm); }
        .tb-gallery-img { display:block; width:100%; height:16rem; object-fit:cover; }
        @media(max-width:480px) { .tb-gallery-grid { grid-template-columns:1fr; } .tb-gallery-img { height:12rem; } }

        /* ─── skills_bars ───────────────────────────────────────────── */
        .tb-skills { }
        .tb-skills-list { max-width:640px; }
        .tb-skill-item { margin-bottom:1.5rem; }
        .tb-skill-header { display:flex; justify-content:space-between; margin-bottom:0.5rem; font-weight:600; font-size:0.9rem; color:var(--tb-text,#1f2937); }
        .tb-skill-bar-track { background:#e5e7eb; border-radius:999px; height:10px; overflow:hidden; }
        .tb-skill-bar-fill { height:100%; border-radius:999px; background:linear-gradient(to left,var(--tb-secondary,#0ea5e9),var(--tb-primary,#6366f1)); width:0; transition:width 1.4s cubic-bezier(0.4,0,0.2,1); }

        /* ─── testimonials_slider ───────────────────────────────────── */
        .tb-testimonials { }
        .tb-testimonials-header { text-align:center; margin-bottom:2rem; }
        .tb-testimonials-grid { display:grid; gap:1.5rem; }
        .tb-testimonials-grid.cols-1 { grid-template-columns:1fr; max-width:640px; margin:0 auto; }
        .tb-testimonials-grid.cols-2 { grid-template-columns:repeat(2,1fr); }
        .tb-testimonials-grid.cols-3 { grid-template-columns:repeat(3,1fr); }
        .tb-testimonial-card { background:var(--tb-surface,#fff); border-radius:var(--tb-radius,0.75rem); border:1px solid #e5e7eb; padding:1.75rem; box-shadow:var(--tb-shadow-sm); display:flex; flex-direction:column; }
        .tb-testimonial-stars { color:#f59e0b; font-size:0.95rem; margin-bottom:0.75rem; letter-spacing:2px; }
        .tb-testimonial-text { font-size:0.95rem; line-height:1.75; color:var(--tb-surface-text,#1f2937); margin:0 0 1.25rem; font-style:italic; flex:1; }
        .tb-testimonial-author { display:flex; align-items:center; gap:0.75rem; }
        .tb-testimonial-avatar { width:42px; height:42px; border-radius:50%; background:linear-gradient(135deg,var(--tb-primary,#6366f1),var(--tb-secondary,#0ea5e9)); display:flex; align-items:center; justify-content:center; font-size:1rem; font-weight:700; color:#fff; flex-shrink:0; }
        .tb-testimonial-name { font-weight:700; font-size:0.9rem; color:var(--tb-text,#0f172a); }
        .tb-testimonial-role { font-size:0.8rem; color:var(--tb-text-muted,#6b7280); margin-top:0.1rem; }
        @media(max-width:767px) { .tb-testimonials-grid.cols-2,.tb-testimonials-grid.cols-3{grid-template-columns:1fr;} }

        /* ─── trust_badges ──────────────────────────────────────────── */
        .tb-trust-badges { }
        .tb-trust-grid { display:flex; flex-wrap:wrap; gap:1rem; justify-content:center; }
        .tb-trust-badge { display:flex; align-items:center; gap:1rem; padding:1.1rem 1.5rem; border:1px solid #e5e7eb; border-radius:var(--tb-radius,0.75rem); background:var(--tb-surface,#fff); box-shadow:var(--tb-shadow-sm); flex:1 1 210px; }
        .tb-trust-icon { font-size:1.875rem; flex-shrink:0; }
        .tb-trust-info-title { font-weight:700; font-size:0.9rem; margin:0 0 0.15rem; color:var(--tb-text,#0f172a); }
        .tb-trust-info-text { font-size:0.8rem; color:var(--tb-text-muted,#6b7280); margin:0; }
        @media(max-width:500px) { .tb-trust-badge{flex:1 1 150px;padding:0.75rem 1rem;} }

        /* ─── pricing_cards ─────────────────────────────────────────── */
        .tb-pricing-cards { }
        .tb-pricing-header { text-align:center; margin-bottom:2.5rem; }
        .tb-pricing-grid { display:grid; gap:1.5rem; align-items:start; }
        .tb-pricing-grid.cols-1 { grid-template-columns:1fr; max-width:400px; margin:0 auto; }
        .tb-pricing-grid.cols-2 { grid-template-columns:repeat(2,1fr); }
        .tb-pricing-grid.cols-3 { grid-template-columns:repeat(3,1fr); }
        .tb-pricing-grid.cols-4 { grid-template-columns:repeat(4,1fr); }
        .tb-pricing-card { border-radius:var(--tb-radius,0.75rem); border:1px solid #e5e7eb; padding:2rem; background:var(--tb-surface,#fff); box-shadow:var(--tb-shadow-sm); display:flex; flex-direction:column; transition:transform 0.2s,box-shadow 0.2s; }
        .tb-pricing-card.highlighted { border-color:var(--tb-primary,#6366f1); border-width:2px; box-shadow:0 4px 32px rgba(99,102,241,0.18); transform:translateY(-6px); }
        .tb-plan-badge { display:inline-block; background:var(--tb-primary,#6366f1); color:#fff; font-size:0.75rem; font-weight:700; padding:0.2rem 0.85rem; border-radius:999px; margin-bottom:1rem; }
        .tb-plan-name { font-size:0.85rem; font-weight:700; color:var(--tb-text-muted,#6b7280); margin:0 0 0.75rem; text-transform:uppercase; letter-spacing:0.06em; }
        .tb-plan-price { font-size:2.75rem; font-weight:800; color:var(--tb-text,#0f172a); line-height:1; }
        .tb-plan-period { font-size:0.85rem; color:var(--tb-text-muted,#6b7280); margin:0.25rem 0 1.5rem; }
        .tb-plan-features { list-style:none; padding:0; margin:0 0 2rem; flex:1; }
        .tb-plan-features li { padding:0.55rem 0; font-size:0.9rem; color:var(--tb-text,#374151); border-bottom:1px solid #f3f4f6; display:flex; align-items:flex-start; gap:0.5rem; }
        .tb-plan-features li::before { content:'✓'; color:var(--tb-primary-text,#6366f1); font-weight:800; flex-shrink:0; }
        .tb-plan-features li:last-child { border-bottom:none; }
        @media(max-width:767px) { .tb-pricing-grid.cols-2,.tb-pricing-grid.cols-3,.tb-pricing-grid.cols-4{grid-template-columns:1fr;} .tb-pricing-card.highlighted{transform:none;} }

        /* ─── newsletter_form ───────────────────────────────────────── */
        .tb-newsletter { padding:3.5rem 2rem; border-radius:1rem; text-align:center; }
        .tb-newsletter-title { font-size:clamp(1.5rem,3vw,2.25rem); font-weight:800; margin:0 0 0.75rem; letter-spacing:-0.01em; }
        .tb-newsletter-sub { font-size:1rem; margin:0 0 1.75rem; opacity:0.88; max-width:50ch; margin-left:auto; margin-right:auto; }
        .tb-newsletter-form { display:flex; gap:0.75rem; max-width:500px; margin:0 auto; flex-wrap:wrap; }
        .tb-newsletter-input { flex:1 1 240px; padding:0.8rem 1.1rem; border-radius:var(--tb-radius,0.75rem); border:2px solid rgba(255,255,255,0.2); font-family:var(--tb-font-body); font-size:1rem; background:rgba(255,255,255,0.95); color:#0f172a; }
        .tb-newsletter-input:focus { outline:none; border-color:rgba(255,255,255,0.6); }
        .tb-newsletter-btn { padding:0.8rem 1.5rem; border-radius:var(--tb-radius,0.75rem); border:none; font-family:var(--tb-font-body); font-size:1rem; font-weight:700; cursor:pointer; white-space:nowrap; transition:opacity 0.2s; }
        .tb-newsletter-btn:hover { opacity:0.9; }
        @media(max-width:480px) { .tb-newsletter-form{flex-direction:column;} .tb-newsletter-input,.tb-newsletter-btn{width:100%;} }

        /* ─── bento_grid ────────────────────────────────────────────── */
        .tb-bento { }
        .tb-bento-header { text-align:center; margin-bottom:2rem; }
        .tb-bento-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:1rem; auto-rows:minmax(160px,auto); }
        .tb-bento-item { border-radius:1rem; padding:1.75rem; display:flex; flex-direction:column; justify-content:flex-end; overflow:hidden; transition:transform 0.2s,box-shadow 0.2s; border:1px solid rgba(0,0,0,0.06); min-height:160px; }
        .tb-bento-item:hover { transform:scale(1.02); box-shadow:0 8px 32px rgba(0,0,0,0.12); }
        .tb-bento-item.large { grid-column:span 2; }
        .tb-bento-item.tall { grid-row:span 2; }
        .tb-bento-item.featured { grid-column:span 2; grid-row:span 2; min-height:320px; }
        .tb-bento-item-icon { font-size:2.25rem; margin-bottom:0.75rem; line-height:1; }
        .tb-bento-item-title { font-size:1rem; font-weight:700; margin:0 0 0.3rem; }
        .tb-bento-item-text { font-size:0.85rem; line-height:1.55; margin:0; opacity:0.8; }
        @media(max-width:767px) { .tb-bento-grid{grid-template-columns:repeat(2,1fr);} .tb-bento-item.large,.tb-bento-item.featured{grid-column:span 2;} .tb-bento-item.tall{grid-row:span 1;} }
        @media(max-width:480px) { .tb-bento-grid{grid-template-columns:1fr;} .tb-bento-item.large,.tb-bento-item.featured,.tb-bento-item.tall{grid-column:span 1;grid-row:span 1;} }

        /* ─── services_cards ────────────────────────────────────────── */
        .tb-service-card { padding:2rem; border-radius:var(--tb-radius,0.75rem); transition:transform 0.2s,box-shadow 0.2s; }
        .tb-service-card.style-bordered { border:1px solid #e5e7eb; background:var(--tb-surface,#fff); box-shadow:var(--tb-shadow-sm); }
        .tb-service-card.style-filled { background:linear-gradient(135deg,rgba(99,102,241,0.06),rgba(14,165,233,0.06)); border:1px solid rgba(99,102,241,0.15); }
        .tb-service-card.style-minimal { border:none; background:transparent; padding-right:0; padding-left:0; }
        .tb-service-card:hover { transform:translateY(-4px); box-shadow:0 12px 32px rgba(0,0,0,0.1); }
        .tb-service-icon { font-size:2.5rem; margin-bottom:1rem; line-height:1; }
        .tb-service-title { font-size:1.1rem; font-weight:700; margin:0 0 0.5rem; color:var(--tb-text,#0f172a); }
        .tb-service-text { font-size:0.875rem; color:var(--tb-text-muted,#4b5563); line-height:1.7; margin:0 0 1.25rem; }
        .tb-service-link { font-size:0.875rem; font-weight:700; color:var(--tb-primary-text,#6366f1); text-decoration:none; }
        .tb-service-link:hover { text-decoration:underline; }

        /* ─── contact_split ─────────────────────────────────────────── */
        .tb-contact-split { display:flex; gap:3rem; align-items:flex-start; flex-wrap:wrap; }
        .tb-contact-info { flex:1 1 260px; }
        .tb-contact-form-wrap { flex:1 1 320px; }
        .tb-contact-info-item { display:flex; gap:1rem; align-items:flex-start; margin-bottom:1.5rem; }
        .tb-contact-info-icon { font-size:1.5rem; flex-shrink:0; margin-top:0.1rem; }
        .tb-contact-info-label { font-weight:700; font-size:0.875rem; margin:0 0 0.25rem; color:var(--tb-text,#0f172a); }
        .tb-contact-info-value { font-size:0.9rem; color:var(--tb-text-muted,#4b5563); margin:0; }
        .tb-contact-form-simple { background:var(--tb-surface,#fff); border-radius:var(--tb-radius,0.75rem); padding:2rem; border:1px solid #e5e7eb; box-shadow:var(--tb-shadow-sm); }
        .tb-contact-form-simple input, .tb-contact-form-simple textarea { width:100%; padding:0.7rem 1rem; border:1px solid #e5e7eb; border-radius:0.5rem; font-family:var(--tb-font-body); font-size:0.95rem; color:var(--tb-text,#1f2937); background:#fff; box-sizing:border-box; transition:border-color 0.15s; }
        .tb-contact-form-simple input:focus, .tb-contact-form-simple textarea:focus { outline:none; border-color:var(--tb-primary,#6366f1); }
        @media(max-width:767px) { .tb-contact-split{flex-direction:column;gap:1.5rem;} }

        /* ─── NEXUS PRO: Skills bar animation on scroll ─────────────── */
        .tb-skill-bar-fill[data-width] { width:0 !important; }
        .tb-skill-bar-fill.animated { width:var(--skill-w) !important; }

        /* TASK 3/3 Phase 6 — promo_banner/countdown had zero CSS anywhere
           (BlockRenderer::renderPromoBanner()/renderCountdown() render real
           markup with no visual treatment at all) — a genuine "missing
           reusable component" gap, not a composition problem: any Theme
           using these blocks got unstyled text. Fixed once, canonically,
           here rather than per-page inline styles. */
        .tb-promo-banner { text-align:center; padding:0.85rem 1.25rem; }
        .tb-promo-banner__link { display:inline-block; font-weight:700; font-size:0.95rem; text-decoration:none; letter-spacing:0.01em; }
        .tb-promo-info    { background:var(--tb-surface,#f9fafb); border-bottom:1px solid #e5e7eb; }
        .tb-promo-info .tb-promo-banner__link    { color:var(--tb-text,#111827); }
        .tb-promo-sale     { background:linear-gradient(90deg,var(--tb-primary,#4f46e5),var(--tb-secondary,#6366f1)); }
        .tb-promo-sale .tb-promo-banner__link     { color:var(--tb-on-primary,#fff); }
        .tb-promo-urgent   { background:#dc2626; }
        .tb-promo-urgent .tb-promo-banner__link   { color:#fff; }

        .tb-countdown { text-align:center; padding:2.5rem 1.5rem; background:var(--tb-surface,#f9fafb); border-radius:var(--tb-radius,0.75rem); border:1px solid #e5e7eb; }
        .tb-countdown__label { display:block; font-weight:700; font-size:1.1rem; margin-bottom:1rem; color:var(--tb-text,#111827); }
        .tb-countdown__hint { display:inline-block; font-size:1.5rem; font-weight:800; font-variant-numeric:tabular-nums; color:var(--tb-primary-text,#4f46e5); letter-spacing:0.02em; }
        @media(max-width:600px) { .tb-countdown__hint { font-size:1.15rem; } }
    </style>

    <script>
    /* NEXUS PRO: Animate skill bars on scroll */
    document.addEventListener('DOMContentLoaded', function () {
        var fills = document.querySelectorAll('.tb-skill-bar-fill[data-width]');
        if (!fills.length) return;
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    var el = entry.target;
                    el.style.setProperty('--skill-w', el.getAttribute('data-width') + '%');
                    el.classList.add('animated');
                    io.unobserve(el);
                }
            });
        }, { threshold: 0.3 });
        fills.forEach(function (el) { io.observe(el); });
    });
    </script>

    @foreach(($extensionAssets['css'] ?? []) as $__extHref)
    <link rel="stylesheet" href="{{ $__extHref }}">
    @endforeach
    @stack('head')
</head>
<body>
    <a href="#canvas-main" class="canvas-skip-link">پرش به محتوا</a>

    {{-- ─── هدر canvas ─── --}}
    @if(!empty(trim($headerHtml)))
        <header class="canvas-site-header tb-site-header" role="banner" id="site-header">
            <div class="tb-header-inner">
                <div class="tb-header-brand">
                    <x-site-brand />
                </div>
                <button type="button" id="tb-mobile-menu-btn" class="tb-menu-btn" aria-label="منو" aria-expanded="false">
                    ☰
                </button>
                <div class="tb-header-nav-wrap">
                    {!! $headerHtml !!}
                </div>
            </div>
            <div id="tb-mobile-menu" class="tb-mobile-menu" aria-hidden="true">
                <div class="tb-mobile-menu-inner">
                    <a href="{{ route('home') }}">خانه</a>
                    @if(\App\Helpers\SiteHelper::blogEnabled())
                        <a href="{{ route('blog.index') }}">وبلاگ</a>
                    @endif
                    @if(\App\Helpers\SiteHelper::shopEnabled())
                        <a href="{{ route('shop.index') }}">فروشگاه</a>
                    @endif
                    @if(\App\Helpers\SiteHelper::coursesEnabled())
                        <a href="{{ route('courses.index') }}">دوره‌ها</a>
                    @endif
                    <a href="{{ route('contact.show') }}">تماس</a>
                    @auth
                        <a href="{{ route('account.dashboard') }}">حساب من</a>
                        @if(auth()->user()->isTenantAdmin())
                            <a href="{{ route('admin.dashboard') }}">پنل مدیریت</a>
                        @endif
                    @else
                        <a href="{{ route('login') }}">ورود</a>
                    @endauth
                </div>
            </div>
        </header>
    @else
        {{-- هدر پیش‌فرض اگر canvas هدر ندارد --}}
        <header class="canvas-site-header" style="background:var(--tb-primary,#4f46e5);color:#fff;padding:0 1rem;" role="banner">
            <div style="display:flex;align-items:center;justify-content:space-between;max-width:var(--tb-container,80rem);margin:0 auto;min-height:4rem;gap:1rem;flex-wrap:wrap;">
                <x-site-brand style="font-size:1.25rem;font-weight:700;color:#fff;text-decoration:none;" />
                <nav style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap;">
                    <a href="{{ route('home') }}" style="color:#fff;text-decoration:none;opacity:0.9;">خانه</a>
                    @if(\App\Helpers\SiteHelper::blogEnabled())
                        <a href="{{ route('blog.index') }}" style="color:#fff;text-decoration:none;opacity:0.9;">وبلاگ</a>
                    @endif
                    @if(\App\Helpers\SiteHelper::shopEnabled())
                        <a href="{{ route('shop.index') }}" style="color:#fff;text-decoration:none;opacity:0.9;">فروشگاه</a>
                    @endif
                    <a href="{{ route('contact.show') }}" style="color:#fff;text-decoration:none;opacity:0.9;">تماس</a>
                    @auth
                        <a href="{{ route('account.dashboard') }}" style="color:#fff;text-decoration:none;opacity:0.9;">حساب من</a>
                    @else
                        <a href="{{ route('login') }}" style="color:#fff;text-decoration:none;opacity:0.9;">ورود</a>
                    @endauth
                </nav>
            </div>
        </header>
    @endif

    {{-- ─── محتوای اصلی ─── --}}
    <main id="canvas-main" class="canvas-main" tabindex="-1">
        {!! $contentHtml !!}
    </main>

    {{-- ─── فوتر canvas ─── --}}
    @if(!empty(trim($footerHtml)))
        <footer class="canvas-site-footer tb-site-footer" role="contentinfo" id="site-footer">
            {!! $footerHtml !!}
        </footer>
    @else
        {{-- فوتر پیش‌فرض اگر canvas فوتر ندارد --}}
        <footer class="canvas-site-footer" style="background:#1f2937;color:#9ca3af;padding:1.5rem 1rem;text-align:center;" role="contentinfo">
            <div style="max-width:var(--tb-container,80rem);margin:0 auto;">
                <p style="margin:0;font-size:0.9rem;">
                    © {{ now()->year }} {{ \App\Helpers\SiteHelper::siteName() }}. تمامی حقوق محفوظ است.
                </p>
            </div>
        </footer>
    @endif

    {{-- ─── Alpine.js برای تب‌ها، آکوردئون و منوی موبایل ─── --}}
    <script>
    (function () {
        // منوی موبایل
        var btn = document.getElementById('tb-mobile-menu-btn');
        var menu = document.getElementById('tb-mobile-menu');
        if (btn && menu) {
            btn.addEventListener('click', function () {
                var isOpen = menu.classList.toggle('is-open');
                btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                menu.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
            });
        }

        // شمارش معکوس برای tb-countdown
        document.querySelectorAll('[data-end-date]').forEach(function (el) {
            var endDate = el.getAttribute('data-end-date');
            if (!endDate) return;
            var end = new Date(endDate);
            var hint = el.querySelector('.tb-countdown__hint');

            function tick() {
                var now = new Date();
                var diff = end - now;
                if (diff <= 0) {
                    if (hint) hint.textContent = 'پیشنهاد پایان یافت.';
                    return;
                }
                var days  = Math.floor(diff / 86400000);
                var hours = Math.floor((diff % 86400000) / 3600000);
                var mins  = Math.floor((diff % 3600000) / 60000);
                var secs  = Math.floor((diff % 60000) / 1000);
                if (hint) hint.textContent = days + ' روز · ' + hours + ' ساعت · ' + mins + ' دقیقه · ' + secs + ' ثانیه';
                setTimeout(tick, 1000);
            }
            tick();
        });
    })();
    </script>

    @foreach(($extensionAssets['js'] ?? []) as $__extSrc)
    <script src="{{ $__extSrc }}" defer></script>
    @endforeach
    @stack('scripts')

    {{-- ─── Builder Edit Bar (for admin users only) ─── --}}
    @auth
        @if(auth()->user()->isTenantAdmin())
        @php
            // This layout is rendered exclusively by
            // ThemeCanvasPageController::renderPage() — every page reaching
            // it is canvas-owned by construction (a Page-model row of the
            // same slug, if one even exists, is never what's on screen).
            // The edit action must therefore always route to Theme Design
            // Canvas, deep-linked to the exact page being viewed — never to
            // Page Builder, which previously guessed a same-slug Page row
            // and silently sent admins to edit content that could never
            // control this public page.
            $builderEditUrl = isset($canvasPageKey) && $canvasPageKey !== ''
                ? route('admin.theme-design.canvas-edit') . '?page=' . urlencode($canvasPageKey)
                : route('admin.theme-design.canvas-edit');
        @endphp
        @if($builderEditUrl)
        <div id="builder-edit-bar" style="position:fixed;bottom:0;left:0;right:0;z-index:9999;background:linear-gradient(90deg,#4f46e5,#7c3aed);color:#fff;font-family:Vazirmatn,Tahoma,sans-serif;font-size:13px;direction:rtl;display:flex;align-items:center;gap:12px;padding:8px 16px;box-shadow:0 -4px 24px rgba(0,0,0,0.3)">
            <span style="font-weight:700;display:flex;align-items:center;gap:6px">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                حالت مدیریت
            </span>
            <a href="{{ $builderEditUrl }}" style="background:rgba(255,255,255,0.2);color:#fff;text-decoration:none;padding:5px 14px;border-radius:8px;font-weight:600;border:1px solid rgba(255,255,255,0.3);display:inline-flex;align-items:center;gap:6px;transition:background .15s">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 20h9M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                ویرایش این صفحه در Canvas
            </a>
            <a href="{{ route('admin.pages.index') }}" style="background:rgba(255,255,255,0.1);color:rgba(255,255,255,0.8);text-decoration:none;padding:5px 14px;border-radius:8px;font-size:12px;border:1px solid rgba(255,255,255,0.2);display:inline-flex;align-items:center;gap:6px">
                📄 مدیریت صفحات
            </a>
            <div style="flex:1"></div>
            <a href="{{ route('admin.dashboard') }}" style="color:rgba(255,255,255,0.7);text-decoration:none;font-size:12px;display:flex;align-items:center;gap:4px">
                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                پنل مدیریت
            </a>
            <button onclick="document.getElementById('builder-edit-bar').style.display='none'" style="background:none;border:none;color:rgba(255,255,255,0.5);cursor:pointer;font-size:18px;line-height:1;padding:0 4px" title="بستن">×</button>
        </div>
        <style>
            body { padding-bottom: 52px !important; }
            @media(max-width:640px){
                #builder-edit-bar { flex-wrap:wrap; gap:6px; padding:6px 10px; }
                #builder-edit-bar a { font-size:11px; padding:4px 10px; }
            }
        </style>
        @endif
        @endif
    @endauth
<x-core-ui::payment-connection-warning />
</body>
</html>
