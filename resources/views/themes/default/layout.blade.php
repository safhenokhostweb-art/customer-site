<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $seoTitle = trim($__env->yieldContent('title')) ?: \App\Helpers\Seo::title();
        $seoDesc = trim($__env->yieldContent('meta_description')) ?: \App\Helpers\Seo::description();
        $seoImage = trim($__env->yieldContent('meta_image')) ?: \App\Helpers\Seo::imageUrl();
        $seoCanonical = \App\Helpers\Seo::canonical(trim($__env->yieldContent('canonical')));
        $ogType = trim($__env->yieldContent('og_type')) ?: 'website';
        $seoRobots = trim($__env->yieldContent('meta_robots')) ?: \App\Helpers\Seo::robots();
    @endphp
    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ e($seoDesc) }}">
    <meta name="robots" content="{{ e($seoRobots) }}">
    <link rel="canonical" href="{{ e($seoCanonical) }}">
    <meta property="og:type" content="{{ e($ogType) }}">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:url" content="{{ e($seoCanonical) }}">
    <meta property="og:locale" content="fa_IR">
    @if($seoDesc)
    <meta property="og:description" content="{{ e($seoDesc) }}">
    <meta name="twitter:card" content="{{ e(\App\Helpers\Seo::twitterCard()) }}">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ e($seoDesc) }}">
    @endif
    @if($seoImage)
    <meta property="og:image" content="{{ e($seoImage) }}">
    @endif
    <script type="application/ld+json">@json(\App\Helpers\JsonLd::organization())</script>
    <script type="application/ld+json">@json(\App\Helpers\JsonLd::webSite(\App\Helpers\SiteHelper::moduleEnabled('advanced_search') ? route('search.index') : null))</script>
    @stack('json-ld')
    {{-- فونت آفلاین Vazirmatn (یکسان با پنل ادمین مشتری) --}}
    <link rel="preload" as="font" href="{{ asset('assets/fonts/vazirmatn/Vazirmatn-Regular.woff2') }}" type="font/woff2" crossorigin>
    <link rel="preload" as="font" href="{{ asset('assets/fonts/vazirmatn/Vazirmatn-Bold.woff2') }}" type="font/woff2" crossorigin>
    <style>@include('partials.vazirmatn-font-faces-inline')</style>
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    @php
        $siteFavicon = \App\Models\Setting::get('site_favicon', '');
        $faviconUrl  = ($siteFavicon && \Illuminate\Support\Facades\Storage::disk('public')->exists($siteFavicon))
            ? \Illuminate\Support\Facades\Storage::url($siteFavicon)
            : null;
        $faviconExt  = $faviconUrl ? strtolower(pathinfo($faviconUrl, PATHINFO_EXTENSION)) : '';
        $faviconMime = match($faviconExt) {
            'ico'  => 'image/x-icon',
            'svg'  => 'image/svg+xml',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };
    @endphp
    @if($faviconUrl)
    <link rel="icon" type="{{ $faviconMime }}" href="{{ $faviconUrl }}">
    <link rel="shortcut icon" href="{{ $faviconUrl }}">
    @endif
    @include('partials.platform-styles')
    @php
        $themeBuilder = \App\Helpers\PlatformUx::themeBuilder();
        $themeVars = [];
        if (\Illuminate\Support\Facades\Schema::hasTable('theme_settings')) {
            $themeVars = \App\Models\ThemeSetting::current()->toCssVariables();
        }
    @endphp
    @if(!empty($themeVars))
    <style>
        :root {
            @foreach($themeVars as $var => $val)
            {{ $var }}: {{ $val }};
            @endforeach
        }
    </style>
    @endif
    @if(!empty($themeBuilder['css_block'] ?? ''))
        <style>{!! $themeBuilder['css_block'] !!}</style>
    @endif
    @if(file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        {{-- بدون CDN: لطفاً قبل از انتشار، `npm run build` را اجرا کنید تا public/build ساخته شود --}}
        <link rel="stylesheet" href="{{ asset('css/customer-admin-layout.css') }}?v={{ @filemtime(public_path('css/customer-admin-layout.css')) ?: time() }}">
    @endif
    <style>
        * { box-sizing: border-box; }
        body { font-family: var(--tb-font-body, Vazirmatn, Tahoma, sans-serif); margin: 0; background: #f9fafb; color: #1f2937; min-height: 100vh; font-size: var(--tb-base-font-size, 16px); }
        .form-input, .form-textarea, .form-select { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 1rem; }
        .form-input:focus, .form-textarea:focus { outline: none; border-color: var(--platform-primary, #4f46e5); box-shadow: 0 0 0 2px rgba(79,70,229,0.2); }
        .form-label { display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem; }
        .form-error { font-size: 0.875rem; color: #dc2626; margin-top: 0.25rem; }
        .nav-link { color: inherit; text-decoration: none; }
        .nav-link:hover { color: var(--platform-menu-link-active, #93c5fd); }
        .site-header .nav-link.font-semibold { color: var(--platform-menu-link-active, #93c5fd); }
        #mobile-menu a { color: inherit; text-decoration: none; display: block; padding: 0.5rem 0; }
        #mobile-menu a:hover { color: var(--platform-menu-link-active, #93c5fd); }
        /* Critical layout - works without Tailwind */
        .site-header .container-inner { max-width: 72rem; margin: 0 auto; padding: 0 1rem; }
        .site-header .header-row { display: flex; align-items: center; justify-content: space-between; min-height: 4rem; }
        .site-header #main-nav { display: flex; align-items: center; gap: 1.5rem; flex-wrap: wrap; }
        .site-header #mobile-menu { display: none; border-top: 1px solid rgba(255,255,255,0.2); padding: 1rem; }
        .site-header #mobile-menu > div { display: flex; flex-direction: column; gap: 0.5rem; }
        .site-header #mobile-menu.is-open { display: block; }
        .site-header .menu-btn { display: none; padding: 0.5rem; border-radius: 0.5rem; background: transparent; border: none; cursor: pointer; color: inherit; }
        @media (max-width: 767px) {
            .site-header #main-nav { display: none; }
            .site-header .menu-btn { display: block; }
        }
        main .main-inner { max-width: 72rem; margin: 0 auto; padding: 0 1rem; padding-top: 2rem; padding-bottom: 3rem; }
        @media (min-width: 768px) { main .main-inner { padding-top: 3rem; padding-bottom: 3rem; } }
        footer .footer-inner { max-width: 72rem; margin: 0 auto; padding: 0 1rem; }
        footer .footer-row { display: flex; flex-direction: column; gap: 1rem; padding-top: 2rem; padding-bottom: 1.5rem; }
        @media (min-width: 768px) { footer .footer-row { flex-direction: row; justify-content: space-between; align-items: center; } }
        footer .footer-links { display: flex; flex-wrap: wrap; gap: 1rem; font-size: 0.875rem; }
        footer .footer-links a { color: #6b7280; text-decoration: none; }
        footer .footer-links a:hover { text-decoration: underline; }
        .card-grid { display: grid; gap: 1.5rem; }
        @media (min-width: 768px) { .card-grid-2 { grid-template-columns: repeat(2, 1fr); } .card-grid-3 { grid-template-columns: repeat(3, 1fr); } .card-grid-4 { grid-template-columns: repeat(4, 1fr); } }
        .card { padding: 1.5rem; background: #fff; border-radius: 0.75rem; border: 1px solid #f3f4f6; }
        .hero-section { text-align: center; padding: 3rem 1rem; margin-bottom: 3rem; border-radius: 1rem; }
        @media (min-width: 768px) { .hero-section { padding: 4rem 1rem; } }
        .hero-buttons { display: flex; flex-wrap: wrap; justify-content: center; gap: 1rem; margin-top: 1.5rem; }
        .hero-buttons a { display: inline-block; padding: 0.75rem 1.5rem; border-radius: 0.5rem; font-weight: 600; text-decoration: none; }
        .skip-link { position: fixed; top: -5rem; right: 1rem; z-index: 50; padding: 0.5rem 1rem; background: #1f2937; color: #fff; border-radius: 0.5rem; text-decoration: none; }
        .skip-link:focus { top: 1rem; opacity: 1; }
        *:focus-visible { outline: 2px solid var(--platform-primary, #4f46e5); outline-offset: 2px; }
        button:focus-visible, a:focus-visible, input:focus-visible, textarea:focus-visible, select:focus-visible { outline: 2px solid var(--platform-primary, #4f46e5); outline-offset: 2px; }
        nav[aria-label="مسیر"] { display: flex; flex-wrap: wrap; align-items: center; gap: 0.25rem; }
        nav[aria-label="مسیر"] a { color: #6b7280; text-decoration: none; }
        nav[aria-label="مسیر"] a:hover { text-decoration: underline; }
        nav[role="navigation"] { margin-top: 2rem; }
        nav[role="navigation"] ul { display: flex; flex-wrap: wrap; gap: 0.5rem; list-style: none; padding: 0; margin: 0; }
        nav[role="navigation"] a, nav[role="navigation"] span { padding: 0.5rem 0.75rem; border-radius: 0.375rem; }
        nav[role="navigation"] a { text-decoration: none; background: #f3f4f6; color: #374151; }
        nav[role="navigation"] a:hover { background: #e5e7eb; }
        nav[role="navigation"] .relative span { background: var(--platform-primary, #4f46e5); color: white; }
        .theme-minimal .site-header { background: #fff !important; color: #111 !important; border-bottom: 1px solid #e5e7eb; }
        .theme-minimal .hero-section { background: #f9fafb !important; color: #111 !important; }
        .theme-dark body { background: #1f2937; color: #e5e7eb; }
        .theme-dark .site-header { background: #111827 !important; color: #e5e7eb !important; }
        .theme-dark .card, .theme-dark main { background: #374151; border-color: #4b5563; color: #e5e7eb; }
        .theme-dark footer { background: #111827 !important; color: #9ca3af; border-color: #374151; }
        /* Theme Builder base styles + typography scale */
        .tb-section { padding: 2.5rem 0; }
        .tb-section-inner { width: 100%; }
        .tb-container { max-width: var(--tb-container, 80rem); margin: 0 auto; padding: 0 1rem; }
        .tb-hero { padding: 3rem 1.5rem; text-align: center; border-radius: 1rem; background: var(--tb-primary, var(--platform-primary, #4f46e5)); color: #fff; margin-bottom: 2.5rem; }
        .tb-hero-title { font-size: clamp(1.75rem, 4vw, 2.5rem); font-weight: 800; margin: 0 0 0.75rem; line-height: 1.2; }
        .tb-hero-subtitle { font-size: 1.1rem; opacity: 0.95; max-width: 42rem; margin: 0 auto 1.25rem; line-height: 1.6; }
        .tb-btn { display: inline-block; padding: 0.75rem 1.5rem; border-radius: var(--tb-radius, 0.75rem); text-decoration: none; font-weight: 600; font-size: 1rem; transition: opacity 0.2s, transform 0.15s; }
        .tb-btn:hover { opacity: 0.95; }
        .tb-btn-primary { background: var(--tb-primary, var(--platform-button-primary-bg, #4f46e5)); color: #fff; }
        .tb-btn-secondary { background: transparent; color: var(--tb-primary, #4f46e5); border: 2px solid currentColor; }
        .tb-heading { margin: 0 0 0.75rem; font-weight: 700; color: var(--tb-text, #111827); line-height: 1.3; }
        h1.tb-heading { font-size: clamp(1.75rem, 3vw, 2.25rem); }
        h2.tb-heading { font-size: clamp(1.5rem, 2.5vw, 1.875rem); }
        h3.tb-heading { font-size: 1.25rem; }
        h4.tb-heading { font-size: 1.125rem; }
        h5.tb-heading, h6.tb-heading { font-size: 1.125rem; }
        .tb-text { line-height: 1.75; color: var(--tb-text-muted, #4b5563); font-size: 1rem; }
        .tb-text.prose { max-width: 65ch; }
        .tb-text.prose p { margin: 0 0 0.75rem; }
        /* تصاویر داخل سازنده: محدودیت اندازه برای جلوگیری از پر شدن کل صفحه */
        .tb-image { display: block; max-width: 100%; margin: 0 auto 1.5rem; }
        .tb-image .tb-img { display: block; max-width: 100%; height: auto; margin: 0 auto; max-height: 480px; object-fit: contain; }
        /* Border radius + shadow helpers for builder */
        .tb-radius-sm { border-radius: 0.5rem; }
        .tb-radius-md { border-radius: 0.75rem; }
        .tb-radius-lg { border-radius: 1rem; }
        .tb-shadow-sm { box-shadow: 0 1px 3px rgba(15, 23, 42, 0.16); }
        .tb-shadow-md { box-shadow: 0 4px 12px rgba(15, 23, 42, 0.18); }
        .tb-shadow-lg { box-shadow: 0 12px 30px rgba(15, 23, 42, 0.22); }
        .tb-hover-lift { transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .tb-hover-lift:hover { transform: translateY(-4px); box-shadow: 0 10px 25px rgba(15, 23, 42, 0.22); }
        /* Placeholder when element fails validation or render (safe rendering) */
        .tb-placeholder { min-height: 2rem; padding: 0.5rem 0.75rem; border-radius: 0.5rem; font-size: 0.875rem; }
        .tb-placeholder-invalid { background: #fef3c7; border: 1px dashed #d97706; color: #92400e; }
        .tb-placeholder-error { background: #fee2e2; border: 1px dashed #dc2626; color: #991b1b; }
        .tb-placeholder-label { font-family: inherit; }
        .tb-grid { display: grid; gap: 1.5rem; }
        .tb-grid.tb-grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .tb-grid.tb-grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .tb-grid.tb-grid-cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        @media (max-width: 767px) {
            .tb-grid.tb-grid-cols-2,
            .tb-grid.tb-grid-cols-3,
            .tb-grid.tb-grid-cols-4 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        .tb-section-products .tb-grid { margin-top: 1.5rem; }
        .tb-product-card { background: #ffffff; border-radius: 0.75rem; border: 1px solid #e5e7eb; padding: 1.25rem 1rem; box-shadow: var(--tb-shadow-sm, 0 1px 2px rgb(0 0 0 / 0.05)); text-align: center; text-decoration: none; color: inherit; display: flex; flex-direction: column; gap: 0.5rem; }
        .tb-product-title { font-weight: 700; margin-bottom: 0.25rem; }
        .tb-product-price { font-weight: 700; color: var(--tb-primary, var(--platform-primary, #4f46e5)); }
        .tb-cta { margin-top: 2.5rem; padding: 2rem 1.5rem; border-radius: 1rem; text-align: center; background: var(--tb-primary, var(--platform-primary, #4f46e5)); color: #fff; }
        .tb-cta-title { font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem; }
        .tb-cta-subtitle { opacity: 0.9; margin-bottom: 1rem; }
        .tb-cta .tb-btn { background: #fff !important; color: var(--tb-primary, var(--platform-primary, #4f46e5)) !important; margin-top: 0.5rem; }
        .tb-cta .tb-btn:hover { opacity: 0.95; }
        /* کارت محصول + حالت خالی */
        .tb-product-card-img { aspect-ratio: 1; background: #f3f4f6; overflow: hidden; border-radius: 0.5rem 0.5rem 0 0; display: flex; align-items: center; justify-content: center; }
        .tb-product-card-img img { width: 100%; height: 100%; object-fit: cover; }
        .tb-product-card-noimg { font-size: 2.5rem; color: #9ca3af; }
        .tb-product-grid-empty { grid-column: 1 / -1; text-align: center; padding: 2rem; background: #f9fafb; border-radius: 0.75rem; border: 1px dashed #d1d5db; }
        .tb-product-grid-empty p { margin-bottom: 1rem; color: #6b7280; }
        /* وبلاگ */
        .tb-blog-posts { margin-top: 1.5rem; }
        .tb-section-title { margin-bottom: 1.25rem; font-size: 1.5rem; }
        .tb-blog-card { display: flex; flex-direction: column; background: #fff; border-radius: 0.75rem; overflow: hidden; border: 1px solid #e5e7eb; text-decoration: none; color: inherit; box-shadow: 0 1px 3px rgba(0,0,0,0.06); transition: box-shadow 0.2s; }
        .tb-blog-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .tb-blog-card-img { aspect-ratio: 16/10; background: #f3f4f6; overflow: hidden; }
        .tb-blog-card-img img { width: 100%; height: 100%; object-fit: cover; }
        .tb-blog-card-noimg { display: flex; align-items: center; justify-content: center; font-size: 2rem; color: #9ca3af; }
        .tb-blog-card-body { padding: 1.25rem; flex: 1; }
        .tb-blog-card-title { font-size: 1.1rem; font-weight: 700; margin: 0 0 0.5rem; line-height: 1.35; }
        .tb-blog-card-meta { font-size: 0.8rem; color: #6b7280; margin: 0 0 0.5rem; }
        .tb-blog-card-excerpt { font-size: 0.9rem; color: #4b5563; margin: 0; line-height: 1.6; }
        @media (max-width: 767px) { .tb-blog-grid.tb-grid-cols-3 { grid-template-columns: 1fr; } }
        /* نظرات / testimonials */
        .tb-testimonials-grid { display: grid; gap: 1.25rem; margin-top: 1rem; }
        @media (min-width: 768px) { .tb-testimonials-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (min-width: 1024px) { .tb-testimonials-grid { grid-template-columns: repeat(3, 1fr); } }
        .tb-testimonial-card { background: var(--tb-surface, #fff); padding: 1.5rem; border-radius: 0.75rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
        .tb-testimonial-rating { color: #f59e0b; font-size: 1rem; margin-bottom: 0.5rem; }
        .tb-testimonial-text { margin: 0 0 0.75rem; line-height: 1.7; color: var(--tb-text-muted, #4b5563); }
        .tb-testimonial-author { margin: 0; font-size: 0.9rem; font-weight: 600; color: var(--tb-text, #111827); }
        /* FAQ */
        .tb-faq-item { border: 1px solid #e5e7eb; border-radius: 0.5rem; margin-bottom: 0.5rem; overflow: hidden; }
        .tb-faq-question { padding: 1rem 1.25rem; cursor: pointer; font-weight: 600; background: var(--tb-surface, #f9fafb); margin: 0; }
        .tb-faq-answer { padding: 1rem 1.25rem; border-top: 1px solid #e5e7eb; line-height: 1.7; color: var(--tb-text-muted, #4b5563); }
        /* دسته‌بندی‌ها به صورت کارت/چیپ */
        .tb-grid .tb-text.prose { background: #f9fafb; border: 1px solid #e5e7eb; padding: 0.75rem 1rem; border-radius: 0.5rem; text-align: center; font-weight: 500; margin: 0; }
        .tb-nav.tb-nav-header a { margin-left: 1rem; text-decoration: none; color: inherit; }
        .tb-social-icons a { margin-left: 0.75rem; text-decoration: none; }
        .tb-hero.tb-align-left { text-align: right; }
        .tb-hero.tb-align-right { text-align: left; }
        .tb-hero.tb-align-center { text-align: center; }
        .tb-hero.tb-align-left .tb-hero-subtitle,
        .tb-hero.tb-align-right .tb-hero-subtitle { margin-right: 0; margin-left: 0; max-width: none; }
        .tb-promo-banner { margin: 0 0 1rem; border-radius: 0.75rem; overflow: hidden; }
        .tb-promo-banner__link { display: block; padding: 0.75rem 1.25rem; text-align: center; text-decoration: none; font-weight: 600; }
        .tb-promo-info { background: #dbeafe; }
        .tb-promo-info .tb-promo-banner__link { color: #1e40af; }
        .tb-promo-success { background: #d1fae5; }
        .tb-promo-success .tb-promo-banner__link { color: #065f46; }
        .tb-gallery { margin: 1.5rem 0; }
        .tb-gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 1rem; }
        .tb-gallery-img { width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: 0.75rem; border: 1px solid #e5e7eb; }
        .tb-gallery-empty { padding: 2rem; text-align: center; color: #6b7280; background: #f9fafb; border-radius: 0.75rem; border: 1px dashed #d1d5db; }
        .tb-countdown { padding: 1.25rem; border-radius: 1rem; background: linear-gradient(135deg, #1c1917, #292524); color: #fef3c7; text-align: center; margin: 1rem 0; }
        .tb-countdown__label { display: block; font-weight: 700; margin-bottom: 0.35rem; }
        .tb-countdown__hint { display: block; margin-top: 0.5rem; font-size: 0.85rem; opacity: 0.88; }
        .tb-custom-html { line-height: 1.75; color: var(--tb-text-muted, #4b5563); }
        /* پس‌زمینه سکشن‌های Theme Builder */
        .tb-bg-background { background-color: var(--tb-background, #f9fafb) !important; }
        .tb-bg-surface { background-color: var(--tb-surface, #ffffff) !important; }
        .tb-bg-primary { background-color: var(--tb-primary, var(--platform-primary, #4f46e5)) !important; color: #fff !important; }
        .tb-bg-primary a { color: #fff !important; opacity: 0.95; }
        .tb-bg-primary a:hover { opacity: 1; }
        .tb-bg-text { background-color: var(--tb-text, #111827) !important; color: #fff !important; }
        .tb-bg-text a { color: #e5e7eb !important; }
        /* هدر سایت (نوار بالای صفحه) */
        .tb-site-header { border-bottom: 1px solid rgba(0,0,0,0.08); box-shadow: 0 1px 3px rgba(0,0,0,0.06); padding: 0 1rem; background: var(--tb-surface, #fff); }
        .tb-site-header.tb-header-sticky { position: sticky; top: 0; z-index: 100; }
        .tb-header-inner { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; max-width: 80rem; margin: 0 auto; min-height: 4rem; }
        .tb-header-brand { font-size: 1.25rem; font-weight: 700; }
        .tb-header-brand a { color: var(--tb-primary, var(--platform-primary, #4f46e5)); text-decoration: none; }
        .tb-header-brand a:hover { text-decoration: underline; }
        .tb-site-header .tb-section { flex: 1; min-width: 0; padding-top: 0.75rem !important; padding-bottom: 0.75rem !important; }
        .tb-site-header .tb-container { display: flex; align-items: center; justify-content: flex-end; flex-wrap: wrap; gap: 1rem; }
        .tb-site-header .tb-nav { display: flex; align-items: center; flex-wrap: wrap; gap: 0.75rem 1.25rem; margin: 0; }
        .tb-site-header .tb-nav a { margin: 0; text-decoration: none; color: var(--tb-text, #1f2937); font-weight: 500; padding: 0.35rem 0; }
        .tb-site-header .tb-nav a:hover { color: var(--tb-primary, var(--platform-primary, #4f46e5)); }
        .tb-site-header .tb-search-bar-wrap { flex: 0 1 14rem; max-width: 20rem; }
        .tb-site-header .tb-search-bar-wrap input { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; font-size: 0.9rem; }
        .tb-site-header .tb-cart-link { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.5rem 0.75rem; border-radius: 0.5rem; background: var(--tb-primary, var(--platform-primary, #4f46e5)); color: #fff !important; text-decoration: none; font-weight: 600; }
        .tb-site-header .tb-cart-link:hover { opacity: 0.95; }
        .tb-menu-btn { display: none; padding: 0.5rem; border: none; background: transparent; cursor: pointer; color: inherit; border-radius: 0.5rem; }
        .tb-mobile-menu { display: none; border-top: 1px solid rgba(0,0,0,0.08); padding: 1rem; background: var(--tb-surface, #fff); }
        .tb-mobile-menu.is-open { display: block; }
        .tb-mobile-menu-inner { display: flex; flex-direction: column; gap: 0.5rem; }
        body.tb-mobile-menu-open { overflow: hidden; }
        .tb-mobile-menu-inner a { padding: 0.5rem 0; color: var(--tb-text, #1f2937); text-decoration: none; font-weight: 500; }
        .tb-mobile-menu-inner a:hover { color: var(--tb-primary, var(--platform-primary, #4f46e5)); }
        .tb-header-nav-wrap { flex: 1; min-width: 0; display: flex; align-items: center; justify-content: flex-end; }
        .tb-header-nav-wrap .tb-section { padding-top: 0 !important; padding-bottom: 0 !important; }
        .tb-header-nav-wrap .tb-container { padding: 0; max-width: none; }
        @media (max-width: 767px) {
            .tb-header-nav-wrap { display: none; }
            .tb-menu-btn { display: block; }
        }
        @media (min-width: 768px) {
            .tb-mobile-menu { display: none !important; }
        }
        /* فوتر سایت */
        .tb-site-footer { border-top: 1px solid #e5e7eb; margin-top: 3rem; box-shadow: 0 -1px 3px rgba(0,0,0,0.06); }
        .tb-site-footer .tb-section { padding-top: 2rem !important; padding-bottom: 2rem !important; }
        .tb-grid.tb-grid-cols-5 { grid-template-columns: repeat(5, minmax(0, 1fr)); }
        .tb-grid.tb-grid-cols-6 { grid-template-columns: repeat(6, minmax(0, 1fr)); }
        @media (max-width: 767px) {
            .tb-grid.tb-grid-cols-5, .tb-grid.tb-grid-cols-6 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        .tb-padding-none { padding-top: 0; padding-bottom: 0; }
        .tb-padding-xs { padding-top: 0.5rem; padding-bottom: 0.5rem; }
        .tb-padding-sm { padding-top: 1.25rem; padding-bottom: 1.25rem; }
        .tb-padding-md { padding-top: 2.5rem; padding-bottom: 2.5rem; }
        .tb-padding-lg { padding-top: 3.5rem; padding-bottom: 3.5rem; }
        .tb-padding-xl { padding-top: 4.5rem; padding-bottom: 4.5rem; }
        @media (min-width: 768px) {
            .tb-padding-lg { padding-top: 4rem; padding-bottom: 4rem; }
            .tb-padding-xl { padding-top: 5.5rem; padding-bottom: 5.5rem; }
        }
        /* Page builder: row & column */
        .tb-row { display: flex; flex-wrap: wrap; gap: 1rem; }
        .tb-row.tb-gap-none { gap: 0; }
        .tb-row.tb-gap-sm { gap: 0.5rem; }
        .tb-row.tb-gap-md { gap: 1rem; }
        .tb-row.tb-gap-lg { gap: 1.5rem; }
        .tb-row.tb-gap-xl { gap: 2rem; }
        .tb-column { min-width: 0; }
        .tb-column.tb-col-full { flex: 1 1 100%; }
        .tb-column.tb-col-1-2 { flex: 1 1 calc(50% - 0.5rem); }
        .tb-column.tb-col-1-3 { flex: 1 1 calc(33.333% - 0.67rem); }
        .tb-column.tb-col-2-3 { flex: 1 1 calc(66.666% - 0.67rem); }
        .tb-column.tb-col-1-4 { flex: 1 1 calc(25% - 0.75rem); }
        .tb-column.tb-col-3-4 { flex: 1 1 calc(75% - 0.75rem); }
        @media (max-width: 767px) {
            .tb-column.tb-col-1-2, .tb-column.tb-col-1-3, .tb-column.tb-col-2-3, .tb-column.tb-col-1-4, .tb-column.tb-col-3-4 { flex: 1 1 100%; }
        }
    </style>
    @if(\App\Helpers\SiteHelper::moduleEnabled('advertisements'))
    <style>
    @keyframes ad-blink { 0%,100%{opacity:1} 50%{opacity:0.3} }
    @keyframes ad-fade { 0%,100%{opacity:1} 50%{opacity:0.5} }
    .advertisements-header { margin-bottom: 1rem; }
    .advertisements-footer { margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e5e7eb; }
    .advertisements-content_top { margin-bottom: 1rem; }
    .advertisements-content_bottom { margin-top: 1rem; }
    .typewriter-cursor { animation: ad-blink 0.8s step-end infinite; }
    </style>
    @endif
    @stack('head')
</head>
<body class="theme-{{ \App\Helpers\SiteHelper::theme() }}">
    <a href="#main" class="skip-link">پرش به محتوا</a>

    @include('partials.advertisement-zone', ['position' => 'header'])

    @php
        $layoutService = app(\App\Services\BuilderLayoutService::class);
        $menuService = app(\App\Services\MenuService::class);
        $currentPage = $currentPage ?? null;
        $headerBlocks = $layoutService->getHeaderBlocks($currentPage);
        $footerBlocks = $layoutService->getFooterBlocks($currentPage);
        $stickyHeader = $layoutService->isStickyHeader();
        $showHeader = $layoutService->shouldShowHeader($currentPage);
        $showFooter = $layoutService->shouldShowFooter($currentPage);
        $primaryMenu = $menuService->getMenuForLocation('primary');
        $footerMenu = $menuService->getMenuForLocation('footer');
        $mobileMenuLoc = $menuService->getMenuForLocation('mobile');
        $mobileNavItems = [];
        if ($mobileMenuLoc) {
            $mobileNavItems = $menuService->buildMenuTree($mobileMenuLoc);
        } elseif ($primaryMenu) {
            $mobileNavItems = $menuService->buildMenuTree($primaryMenu);
        } elseif (\App\Helpers\SiteHelper::moduleEnabled('menu')) {
            $uxMenus = \App\Helpers\PlatformUx::menus();
            $mobileNavItems = ($uxMenus['mobile']['items'] ?? []) ?: ($uxMenus['header']['items'] ?? []);
            $mobileNavItems = is_array($mobileNavItems) ? $mobileNavItems : [];
        }
        if (! empty($mobileNavItems)) {
            $mobileNavItems = \App\Helpers\MenuActiveTrail::decorate($mobileNavItems, url()->current());
        }
        $headerMenuHtml = $primaryMenu
            ? view('partials.site-menu', ['menu' => $primaryMenu, 'variant' => 'nav', 'items' => $menuService->buildMenuTree($primaryMenu)])->render()
            : view('partials.platform-menu', ['location' => 'header', 'variant' => 'nav'])->render();
        $footerMenuHtml = $footerMenu
            ? view('partials.site-menu', ['menu' => $footerMenu, 'variant' => 'footer', 'items' => $menuService->buildMenuTree($footerMenu)])->render()
            : view('partials.platform-menu', ['location' => 'footer', 'variant' => 'footer'])->render();
    @endphp
    @if($showHeader && !empty($headerBlocks))
        <header class="tb-site-header {{ $stickyHeader ? 'tb-header-sticky' : '' }}" role="banner" id="site-header">
            <div class="tb-header-inner">
                <div class="tb-header-brand">
                    <a href="{{ route('home') }}">{{ \App\Helpers\SiteHelper::siteName() }}</a>
                </div>
                <button type="button" id="tb-mobile-menu-btn" class="tb-menu-btn" aria-label="منو" aria-expanded="false">
                    <x-core-ui::icon name="menu" style="width:24px;height:24px" />
                </button>
                <div class="tb-header-nav-wrap">
                    {!! app(\App\Services\ThemeBuilder\BlockRenderer::class)
                        ->setContext([
                            'menu_html' => $headerMenuHtml,
                            'site_name' => \App\Helpers\SiteHelper::siteName(),
                        ])
                        ->renderBlocks($headerBlocks) !!}
                </div>
            </div>
            <div id="tb-mobile-menu" class="tb-mobile-menu tb-mobile-menu-panel" aria-hidden="true" role="dialog" aria-label="منوی موبایل">
                <div class="tb-mobile-menu-inner">
                    @if(!empty($mobileNavItems))
                        @include('partials.site-menu-mobile', ['items' => $mobileNavItems])
                    @else
                        <a href="{{ route('home') }}">خانه</a>
                        @foreach($pages ?? \App\Models\Page::published()->menu()->get() as $p)
                            <a href="{{ route('page.show', $p->slug) }}">{{ $p->title }}</a>
                        @endforeach
                        @if(\App\Helpers\SiteHelper::blogEnabled())
                            <a href="{{ route('blog.index') }}">وبلاگ</a>
                        @endif
                        @if(\App\Helpers\SiteHelper::shopEnabled())
                            <a href="{{ route('shop.index') }}">فروشگاه</a>
                            <a href="{{ route('shop.cart') }}">سبد خرید</a>
                        @endif
                        <a href="{{ route('contact.show') }}">تماس</a>
                        @auth
                            <a href="{{ route('account.dashboard') }}">حساب من</a>
                            @if(auth()->user()->isTenantAdmin())
                                <a href="{{ route('admin.dashboard') }}">پنل</a>
                            @endif
                        @else
                            <a href="{{ route('login') }}">ورود</a>
                        @endauth
                    @endif
                </div>
            </div>
        </header>
    @elseif($showHeader)
    <header class="site-header" style="background: var(--platform-menu-bg, #1e293b); color: var(--platform-menu-text, #e2e8f0);">
        <div class="container-inner">
            <div class="header-row">
                <a href="{{ route('home') }}" style="font-size: 1.25rem; font-weight: 700; color: var(--platform-primary, #a5b4fc); text-decoration: none;">
                    {{ \App\Helpers\SiteHelper::siteName() }}
                </a>
                <button type="button" id="mobile-menu-btn" class="menu-btn" aria-label="منو">
                    <x-core-ui::icon name="menu" style="width:24px;height:24px" />
                </button>
                <nav id="main-nav">
                    @if(\App\Helpers\SiteHelper::moduleEnabled('menu') && !empty(\App\Helpers\PlatformUx::menus()['header']['items']))
                        @include('partials.platform-menu', ['location' => 'header', 'variant' => 'nav'])
                    @else
                        <a href="{{ route('home') }}" class="nav-link hover:opacity-90 {{ request()->routeIs('home') ? 'font-semibold' : '' }}">خانه</a>
                        @foreach($pages ?? [] as $p)
                            <a href="{{ route('page.show', $p->slug) }}" class="nav-link">{{ $p->title }}</a>
                        @endforeach
                        @if(\App\Helpers\SiteHelper::blogEnabled())
                            <a href="{{ route('blog.index') }}" class="nav-link {{ request()->routeIs('blog.*') ? 'font-semibold' : '' }}">وبلاگ</a>
                        @endif
                        @if(\App\Helpers\SiteHelper::shopEnabled())
                            <a href="{{ route('shop.index') }}" class="nav-link {{ request()->routeIs('shop.*') ? 'font-semibold' : '' }}">فروشگاه</a>
                            <a href="{{ route('shop.cart') }}" class="nav-link flex items-center gap-1">سبد خرید</a>
                        @endif
                        @if(\App\Helpers\SiteHelper::isOwnerSite())
                            <a href="{{ route('platform-products.index') }}" class="nav-link {{ request()->routeIs('platform-products.*') ? 'font-semibold' : '' }}">خرید لایسنس و ماژول</a>
                        @endif
                        @if(\App\Helpers\SiteHelper::moduleEnabled('advanced_search'))
                            <a href="{{ route('search.index') }}" class="nav-link {{ request()->routeIs('search.*') ? 'font-semibold' : '' }}" title="جستجوی پیشرفته" aria-label="جستجو">
                                <x-core-ui::icon name="search" style="width:20px;height:20px;vertical-align:middle" />
                            </a>
                        @endif
                        <a href="{{ route('contact.show') }}" class="nav-link {{ request()->routeIs('contact.*') ? 'font-semibold' : '' }}">تماس</a>
                        @auth
                            <a href="{{ route('account.dashboard') }}" class="nav-link">حساب من</a>
                            @if(auth()->user()->isTenantAdmin())
                                <a href="{{ route('admin.dashboard') }}" class="nav-link">پنل</a>
                            @endif
                        @else
                            <a href="{{ route('login') }}" class="nav-link">ورود</a>
                        @endauth
                        {{-- Language Switcher --}}
                        <x-language-switcher />
                    @endif
                </nav>
            </div>
        </div>
        <div id="mobile-menu">
            <div class="flex flex-col gap-2">
                @if(\App\Helpers\SiteHelper::moduleEnabled('menu') && !empty(\App\Helpers\PlatformUx::menus()['header']['items']))
                    @php $headerItems = \App\Helpers\PlatformUx::menus()['header']['items'] ?? []; @endphp
                    @foreach($headerItems as $item)
                        <a href="{{ $item['url'] ?? '#' }}" target="{{ $item['target'] ?? '_self' }}" class="py-2 block">{{ $item['title'] }}</a>
                        @foreach($item['children'] ?? [] as $child)
                            <a href="{{ $child['url'] ?? '#' }}" target="{{ $child['target'] ?? '_self' }}" class="py-2 block mr-4">{{ $child['title'] }}</a>
                        @endforeach
                    @endforeach
                @else
                    <a href="{{ route('home') }}" class="py-2 block">خانه</a>
                    @foreach($pages ?? [] as $p)
                        <a href="{{ route('page.show', $p->slug) }}" class="py-2 block">{{ $p->title }}</a>
                    @endforeach
                    @if(\App\Helpers\SiteHelper::blogEnabled())
                        <a href="{{ route('blog.index') }}" class="py-2 block">وبلاگ</a>
                    @endif
                    @if(\App\Helpers\SiteHelper::shopEnabled())
                        <a href="{{ route('shop.index') }}" class="py-2 block">فروشگاه</a>
                        <a href="{{ route('shop.cart') }}" class="py-2 block">سبد خرید</a>
                    @endif
                    @if(\App\Helpers\SiteHelper::moduleEnabled('advanced_search'))
                        <a href="{{ route('search.index') }}" class="py-2 block">جستجو</a>
                    @endif
                    @if(\App\Helpers\SiteHelper::isOwnerSite())
                        <a href="{{ route('platform-products.index') }}" class="py-2 block">خرید لایسنس و ماژول</a>
                    @endif
                    <a href="{{ route('contact.show') }}" class="py-2 block">تماس</a>
                    @auth
                        <a href="{{ route('account.dashboard') }}" class="py-2 block">حساب من</a>
                        @if(auth()->user()->isTenantAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="py-2 block">پنل</a>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="py-2 block">ورود</a>
                    @endauth
                @endif
            </div>
        </div>
    </header>
    @endif

    <main id="main">
        <div class="main-inner">
            @if(session('success'))
                <div style="margin-bottom: 1.5rem; padding: 1rem; border-radius: 0.5rem; background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div style="margin-bottom: 1.5rem; padding: 1rem; border-radius: 0.5rem; background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                    {{ session('error') }}
                </div>
            @endif
            @include('partials.advertisement-zone', ['position' => 'content_top'])
            @hasSection('breadcrumb')
                <nav style="margin-bottom: 1.5rem; font-size: 0.875rem; color: #6b7280;" aria-label="مسیر">
                    @yield('breadcrumb')
                </nav>
            @endif
            @yield('content')
            @include('partials.advertisement-zone', ['position' => 'content_bottom'])
        </div>
    </main>

    @include('partials.advertisement-zone', ['position' => 'footer'])

    @if($showFooter && !empty($footerBlocks))
        <footer class="tb-site-footer" role="contentinfo">
            @php
                $footerSocialLinks = [];
                if (\App\Helpers\SiteHelper::moduleEnabled('social_links') && class_exists(\App\Models\SocialLink::class)) {
                    $footerSocialLinks = \App\Models\SocialLink::where('is_active', true)->orderBy('order')->get()->map(fn ($l) => ['url' => $l->url, 'label' => \App\Models\SocialLink::platforms()[$l->platform] ?? $l->platform])->toArray();
                }
            @endphp
            {!! app(\App\Services\ThemeBuilder\BlockRenderer::class)
                ->setContext([
                    'menu_html' => $footerMenuHtml,
                    'social_links' => $footerSocialLinks,
                ])
                ->renderBlocks($footerBlocks) !!}
        </footer>
    @elseif($showFooter)
    <footer style="margin-top: 3rem; padding-top: 2rem; padding-bottom: 1.5rem; background: #f3f4f6; border-top: 1px solid #e5e7eb;">
        <div class="footer-inner">
            <div class="footer-row">
                <div style="font-weight: 600;">{{ \App\Helpers\SiteHelper::siteName() }}</div>
                <div class="footer-links">
                    @if(\App\Helpers\SiteHelper::moduleEnabled('menu') && !empty(\App\Helpers\PlatformUx::menus()['footer']['items']))
                        @include('partials.platform-menu', ['location' => 'footer', 'variant' => 'footer'])
                    @else
                        <a href="{{ route('home') }}" class="hover:underline">خانه</a>
                        @if(\App\Helpers\SiteHelper::blogEnabled())
                            <a href="{{ route('blog.index') }}" class="hover:underline">وبلاگ</a>
                        @endif
                        @if(\App\Helpers\SiteHelper::shopEnabled())
                            <a href="{{ route('shop.index') }}" class="hover:underline">فروشگاه</a>
                        @endif
                        @if(\App\Helpers\SiteHelper::isOwnerSite())
                            <a href="{{ route('platform-products.index') }}" class="hover:underline">خرید لایسنس و ماژول</a>
                        @endif
                        <a href="{{ route('contact.show') }}" class="hover:underline">تماس</a>
                    @endif
                </div>
            </div>
            @include('partials.social-links-footer')
            <p style="margin-top: 1rem; font-size: 0.875rem; color: #6b7280; text-align: center;">© {{ date('Y') }} {{ \App\Helpers\SiteHelper::siteName() }}. تمامی حقوق محفوظ است.</p>
        </div>
    </footer>
    @endif

    @include('partials.messaging-widget')
    @if(\App\Helpers\SiteHelper::moduleEnabled('advertisements'))
    <script>
    (function(){
        document.querySelectorAll('.ad-typewriter').forEach(function(el){
            var text = el.getAttribute('data-text') || '';
            var span = el.querySelector('.typewriter-text');
            if (!span || !text) return;
            var i = 0;
            function type() {
                if (i < text.length) {
                    span.textContent += text.charAt(i);
                    i++;
                    setTimeout(type, 80);
                } else {
                    setTimeout(function(){ i = 0; span.textContent = ''; setTimeout(type, 1000); }, 2000);
                }
            }
            setTimeout(type, 300);
        });
    })();
    </script>
    @endif
    <script>
        document.getElementById('mobile-menu-btn')?.addEventListener('click', function() {
            document.getElementById('mobile-menu')?.classList.toggle('is-open');
        });
        document.getElementById('tb-mobile-menu-btn')?.addEventListener('click', function() {
            var menu = document.getElementById('tb-mobile-menu');
            if (menu) {
                var open = !menu.classList.contains('is-open');
                menu.classList.toggle('is-open', open);
                menu.setAttribute('aria-hidden', open ? 'false' : 'true');
                this.setAttribute('aria-expanded', open ? 'true' : 'false');
                @if(config('menu.mobile_body_scroll_lock', true))
                document.body.classList.toggle('tb-mobile-menu-open', open);
                @endif
            }
        });
        document.querySelectorAll('form').forEach(function(f) {
            f.addEventListener('submit', function() {
                var btn = f.querySelector('button[type="submit"]');
                if (btn && !btn.disabled) {
                    btn.disabled = true;
                    var orig = btn.textContent;
                    btn.textContent = btn.dataset.loadingText || 'در حال ارسال...';
                    setTimeout(function() { btn.disabled = false; btn.textContent = orig; }, 15000);
                }
            });
        });
    </script>
    @stack('scripts')
</body>
</html>

