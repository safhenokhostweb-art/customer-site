{{--
    Shared SEO head-tag renderer, used by both the legacy theme layout and the
    Template Builder's canvas-theme layout so public pages get identical SEO
    coverage regardless of which layout rendered them.

    Reuses App\Helpers\Seo (SeoService) and App\Helpers\JsonLd — no SEO logic
    is duplicated here, this component only emits the tags.

    Props (all optional; every one falls back to the same SeoService authority
    this component has always used, so a caller that passes nothing renders
    exactly what it rendered before):
    - title (string)                         — fully composed page title
    - description (string)                   — per-page override, falls back to Seo::description()
    - image (string)                         — per-page OG/Twitter image override, falls back to Seo::imageUrl()
    - imageAlt (string)                      — og:image:alt / twitter:image:alt, falls back to the title
    - canonical (string)                     — per-page canonical override, falls back to Seo::canonical()
    - robots (string)                        — per-page robots override, falls back to Seo::robots()
    - ogType (string, default 'website')
    - locale (string, default 'fa_IR')       — og:locale, so an English document is not
                                               advertised as fa_IR
    - schema (array, default [])             — extra JSON-LD graphs to emit alongside the
                                               site-wide Organization/WebSite pair
    - includeOrganizationJsonLd (bool, default true)

    The description is guaranteed NON-EMPTY: an empty (or site-wide identical)
    <meta name="description"> is a weaker signal than one that at least names the
    site, so the last-resort fallback is the site identity itself.
--}}
@php
    $seoTitle       = trim((string) ($title ?? '')) ?: \App\Helpers\SiteHelper::siteName();
    $seoDescription = trim((string) ($description ?? '')) ?: \App\Helpers\Seo::description();
    $seoDescription = trim($seoDescription) !== '' ? $seoDescription : trim(\App\Helpers\SiteHelper::siteName());
    $seoImage       = trim((string) ($image ?? '')) ?: \App\Helpers\Seo::imageUrl();
    $seoImageAlt    = trim((string) ($imageAlt ?? '')) ?: $seoTitle;
    $seoCanonical   = \App\Helpers\Seo::canonical(trim((string) ($canonical ?? '')));
    $seoRobots      = trim((string) ($robots ?? '')) ?: \App\Helpers\Seo::robots();
    $seoOgType      = $ogType ?? 'website';
    $seoLocale      = $locale ?? 'fa_IR';
    $seoSiteName    = \App\Helpers\SiteHelper::siteName();
    $seoSchema      = is_array($schema ?? null) ? $schema : [];
@endphp
<meta name="description" content="{{ e($seoDescription) }}">
<meta name="robots" content="{{ e($seoRobots) }}">
<link rel="canonical" href="{{ e($seoCanonical) }}">
<meta property="og:type" content="{{ e($seoOgType) }}">
<meta property="og:title" content="{{ e($seoTitle) }}">
<meta property="og:url" content="{{ e($seoCanonical) }}">
<meta property="og:site_name" content="{{ e($seoSiteName) }}">
<meta property="og:locale" content="{{ e($seoLocale) }}">
<meta property="og:description" content="{{ e($seoDescription) }}">
<meta name="twitter:card" content="{{ e(\App\Helpers\Seo::twitterCard()) }}">
<meta name="twitter:title" content="{{ e($seoTitle) }}">
<meta name="twitter:description" content="{{ e($seoDescription) }}">
@if($seoImage)
<meta property="og:image" content="{{ e($seoImage) }}">
<meta property="og:image:alt" content="{{ e($seoImageAlt) }}">
<meta name="twitter:image" content="{{ e($seoImage) }}">
<meta name="twitter:image:alt" content="{{ e($seoImageAlt) }}">
@endif
@if($includeOrganizationJsonLd ?? true)
<script type="application/ld+json">@json(\App\Helpers\JsonLd::organization())</script>
<script type="application/ld+json">@json(\App\Helpers\JsonLd::webSite(\App\Helpers\SiteHelper::moduleEnabled('advanced_search') ? route('search.index') : null))</script>
@endif
@foreach($seoSchema as $seoSchemaItem)
@if(is_array($seoSchemaItem) && $seoSchemaItem !== [])
<script type="application/ld+json">@json($seoSchemaItem)</script>
@endif
@endforeach
@stack('json-ld')
