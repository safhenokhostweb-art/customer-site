{{--
    Shared SEO head-tag renderer, used by both the legacy theme layout and the
    Template Builder's canvas-theme layout so public pages get identical SEO
    coverage regardless of which layout rendered them.

    Reuses App\Helpers\Seo (SeoService) and App\Helpers\JsonLd — no SEO logic
    is duplicated here, this component only emits the tags.

    Props:
    - title (string, required)              — fully composed page title
    - description (string, optional)        — per-page override, falls back to Seo::description()
    - image (string, optional)               — per-page OG/Twitter image override, falls back to Seo::imageUrl()
    - canonical (string, optional)           — per-page canonical override, falls back to Seo::canonical()
    - robots (string, optional)              — per-page robots override, falls back to Seo::robots()
    - ogType (string, default 'website')
    - includeOrganizationJsonLd (bool, default true) — emit site-wide Organization + WebSite JSON-LD
--}}
@php
    $seoDescription = trim((string) ($description ?? '')) ?: \App\Helpers\Seo::description();
    $seoImage       = trim((string) ($image ?? '')) ?: \App\Helpers\Seo::imageUrl();
    $seoCanonical   = \App\Helpers\Seo::canonical(trim((string) ($canonical ?? '')));
    $seoRobots      = trim((string) ($robots ?? '')) ?: \App\Helpers\Seo::robots();
    $seoOgType      = $ogType ?? 'website';
@endphp
<meta name="description" content="{{ e($seoDescription) }}">
<meta name="robots" content="{{ e($seoRobots) }}">
<link rel="canonical" href="{{ e($seoCanonical) }}">
<meta property="og:type" content="{{ e($seoOgType) }}">
<meta property="og:title" content="{{ $title }}">
<meta property="og:url" content="{{ e($seoCanonical) }}">
<meta property="og:locale" content="fa_IR">
@if($seoDescription)
<meta property="og:description" content="{{ e($seoDescription) }}">
<meta name="twitter:card" content="{{ e(\App\Helpers\Seo::twitterCard()) }}">
<meta name="twitter:title" content="{{ $title }}">
<meta name="twitter:description" content="{{ e($seoDescription) }}">
@endif
@if($seoImage)
<meta property="og:image" content="{{ e($seoImage) }}">
<meta name="twitter:image" content="{{ e($seoImage) }}">
@endif
@if($includeOrganizationJsonLd ?? true)
<script type="application/ld+json">@json(\App\Helpers\JsonLd::organization())</script>
<script type="application/ld+json">@json(\App\Helpers\JsonLd::webSite(\App\Helpers\SiteHelper::moduleEnabled('advanced_search') ? route('search.index') : null))</script>
@endif
@stack('json-ld')
