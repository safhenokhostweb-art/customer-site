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
        $primary    = $gs['primary_color']   ?? $gs['colors']['primary']   ?? '#4f46e5';
        $secondary  = $gs['secondary_color'] ?? $gs['colors']['secondary'] ?? '#7c3aed';
        $bgColor    = $gs['background_color'] ?? $gs['colors']['background'] ?? '#f9fafb';
        $textColor  = $gs['text_color']      ?? $gs['colors']['text']      ?? '#1f2937';
        $fontBody   = $gs['font_family']     ?? $gs['fonts']['body']       ?? 'Vazirmatn';
        $borderRad  = $gs['border_radius']   ?? '0.75rem';
        $shadowSm   = $gs['shadow_sm']       ?? '0 1px 3px rgba(15,23,42,0.12)';
        $containerW = $gs['container_width'] ?? '80rem';
    @endphp
    <title>{{ $fullTitle }}</title>
    <meta name="description" content="{{ \App\Helpers\Seo::description() }}">
    <link rel="canonical" href="{{ \App\Helpers\Seo::canonical() }}">
    <meta property="og:title" content="{{ $fullTitle }}">
    <meta property="og:locale" content="fa_IR">

    {{-- فونت آفلاین Vazirmatn --}}
    <link rel="preload" as="font" href="{{ asset('assets/fonts/vazirmatn/Vazirmatn-Regular.woff2') }}" type="font/woff2" crossorigin>
    <link rel="preload" as="font" href="{{ asset('assets/fonts/vazirmatn/Vazirmatn-Bold.woff2') }}" type="font/woff2" crossorigin>
    <style>@include('partials.vazirmatn-font-faces-inline')</style>

    @if(file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <link rel="stylesheet" href="{{ asset('css/customer-admin-layout.css') }}?v={{ @filemtime(public_path('css/customer-admin-layout.css')) ?: time() }}">
    @endif

    {{-- CSS متغیرهای قالب canvas --}}
    <style>
        :root {
            --tb-primary:    {{ $primary }};
            --tb-secondary:  {{ $secondary }};
            --tb-background: {{ $bgColor }};
            --tb-text:       {{ $textColor }};
            --tb-text-muted: #4b5563;
            --tb-surface:    #ffffff;
            --tb-font-body:  {{ $fontBody }}, Tahoma, sans-serif;
            --tb-radius:     {{ $borderRad }};
            --tb-shadow-sm:  {{ $shadowSm }};
            --tb-container:  {{ $containerW }};
            --platform-primary: {{ $primary }};
        }

        * { box-sizing: border-box; }

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
        .tb-hero { padding: 3rem 1.5rem; text-align: center; border-radius: var(--tb-radius, 0.75rem); background: var(--tb-primary, #4f46e5); color: #fff; margin-bottom: 2.5rem; }
        .tb-hero-inner { max-width: 56rem; margin: 0 auto; }
        .tb-hero-title { font-size: clamp(1.75rem, 4vw, 2.5rem); font-weight: 800; margin: 0 0 0.75rem; line-height: 1.2; }
        .tb-hero-subtitle { font-size: 1.1rem; opacity: 0.95; max-width: 42rem; margin: 0 auto 1.25rem; line-height: 1.6; }
        .tb-btn { display: inline-block; padding: 0.75rem 1.5rem; border-radius: var(--tb-radius, 0.75rem); text-decoration: none; font-weight: 600; font-size: 1rem; transition: opacity 0.2s, transform 0.15s; cursor: pointer; }
        .tb-btn:hover { opacity: 0.9; transform: translateY(-1px); }
        .tb-btn-primary { background: var(--tb-primary, #4f46e5); color: #fff !important; }
        .tb-btn-secondary { background: transparent; color: var(--tb-primary, #4f46e5); border: 2px solid currentColor; }
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
        .tb-divider { border: none; border-top: 1px solid #e5e7eb; margin: 1.5rem 0; }
        .tb-spacer { display: block; }

        /* گرید */
        .tb-grid { display: grid; gap: 1.5rem; }
        .tb-grid.tb-grid-cols-1 { grid-template-columns: repeat(1, minmax(0, 1fr)); }
        .tb-grid.tb-grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .tb-grid.tb-grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .tb-grid.tb-grid-cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .tb-grid.tb-grid-cols-5 { grid-template-columns: repeat(5, minmax(0, 1fr)); }
        .tb-grid.tb-grid-cols-6 { grid-template-columns: repeat(6, minmax(0, 1fr)); }
        @media (max-width: 767px) {
            .tb-grid.tb-grid-cols-2,
            .tb-grid.tb-grid-cols-3,
            .tb-grid.tb-grid-cols-4,
            .tb-grid.tb-grid-cols-5,
            .tb-grid.tb-grid-cols-6 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .tb-grid.tb-grid-cols-1 { grid-template-columns: 1fr; }
        }
        @media (max-width: 480px) {
            .tb-grid.tb-grid-cols-2,
            .tb-grid.tb-grid-cols-3,
            .tb-grid.tb-grid-cols-4 { grid-template-columns: 1fr; }
        }

        /* پس‌زمینه */
        .tb-bg-background { background-color: var(--tb-background, #f9fafb) !important; }
        .tb-bg-surface    { background-color: var(--tb-surface, #fff) !important; }
        .tb-bg-primary    { background-color: var(--tb-primary, #4f46e5) !important; color: #fff !important; }
        .tb-bg-secondary  { background-color: var(--tb-secondary, #7c3aed) !important; color: #fff !important; }
        .tb-bg-text       { background-color: var(--tb-text, #111827) !important; color: #fff !important; }

        /* padding */
        .tb-padding-none { padding-top: 0; padding-bottom: 0; }
        .tb-padding-xs   { padding-top: 0.5rem; padding-bottom: 0.5rem; }
        .tb-padding-sm   { padding-top: 1.25rem; padding-bottom: 1.25rem; }
        .tb-padding-md   { padding-top: 2.5rem; padding-bottom: 2.5rem; }
        .tb-padding-lg   { padding-top: 3.5rem; padding-bottom: 3.5rem; }
        .tb-padding-xl   { padding-top: 4.5rem; padding-bottom: 4.5rem; }
        @media (min-width: 768px) {
            .tb-padding-lg { padding-top: 4rem; padding-bottom: 4rem; }
            .tb-padding-xl { padding-top: 5.5rem; padding-bottom: 5.5rem; }
        }

        /* hero align */
        .tb-hero.tb-align-left    { text-align: right; }
        .tb-hero.tb-align-right   { text-align: left; }
        .tb-hero.tb-align-center  { text-align: center; }

        /* CTA */
        .tb-cta { padding: 2.5rem 1.5rem; border-radius: var(--tb-radius, 0.75rem); text-align: center; background: var(--tb-primary, #4f46e5); color: #fff; }
        .tb-cta-title    { font-size: 1.75rem; font-weight: 800; margin: 0 0 0.5rem; }
        .tb-cta-subtitle { font-size: 1rem; opacity: 0.9; margin: 0 0 1rem; }
        .tb-cta .tb-btn  { background: #fff !important; color: var(--tb-primary, #4f46e5) !important; }

        /* کارت محصول */
        .tb-product-card { background: var(--tb-surface, #fff); border-radius: var(--tb-radius, 0.75rem); border: 1px solid #e5e7eb; padding: 0; overflow: hidden; box-shadow: var(--tb-shadow-sm); text-decoration: none; color: inherit; display: flex; flex-direction: column; transition: box-shadow 0.2s, transform 0.15s; }
        .tb-product-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,0.12); transform: translateY(-2px); }
        .tb-product-card-img { aspect-ratio: 1; background: #f3f4f6; overflow: hidden; display: flex; align-items: center; justify-content: center; }
        .tb-product-card-img img { width: 100%; height: 100%; object-fit: cover; }
        .tb-product-card-noimg { font-size: 2.5rem; color: #9ca3af; }
        .tb-product-card-inner { padding: 1rem; flex: 1; }
        .tb-product-title { font-weight: 700; font-size: 1rem; margin: 0 0 0.4rem; color: var(--tb-text, #111827); }
        .tb-product-price { font-weight: 700; color: var(--tb-primary, #4f46e5); font-size: 0.95rem; }
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
        .tb-blog-card-meta   { font-size: 0.8rem; color: #9ca3af; margin: 0 0 0.4rem; }
        .tb-blog-card-excerpt { font-size: 0.88rem; color: #6b7280; margin: 0; line-height: 1.6; }

        /* ناوبری داخل canvas */
        .tb-nav { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem 1.25rem; }
        .tb-nav a { text-decoration: none; color: var(--tb-text, #1f2937); font-weight: 500; padding: 0.3rem 0; }
        .tb-nav a:hover { color: var(--tb-primary, #4f46e5); }
        .tb-social-icons { display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; }
        .tb-social-link { text-decoration: none; color: var(--tb-text, #374151); font-weight: 500; }
        .tb-social-link:hover { color: var(--tb-primary, #4f46e5); }

        /* هدر و فوتر site */
        .tb-site-header { padding: 0 1rem; background: var(--tb-surface, #fff); border-bottom: 1px solid rgba(0,0,0,0.08); box-shadow: var(--tb-shadow-sm); }
        .tb-site-header.tb-header-sticky { position: sticky; top: 0; z-index: 100; }
        .tb-header-inner { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; max-width: var(--tb-container, 80rem); margin: 0 auto; min-height: 4rem; }
        .tb-header-brand { font-size: 1.25rem; font-weight: 700; }
        .tb-header-brand a { color: var(--tb-primary, #4f46e5); text-decoration: none; }
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
        .tb-site-footer { border-top: 1px solid #e5e7eb; }
        .tb-site-footer .tb-section { padding-top: 2rem !important; padding-bottom: 2rem !important; }
        @media (max-width: 767px) {
            .tb-header-nav-wrap { display: none; }
            .tb-menu-btn { display: block; }
        }
        @media (min-width: 768px) {
            .tb-mobile-menu { display: none !important; }
        }

        /* FAQ */
        .tb-faq-item { border: 1px solid #e5e7eb; border-radius: 0.5rem; margin-bottom: 0.5rem; overflow: hidden; }
        .tb-faq-question { padding: 1rem 1.25rem; cursor: pointer; font-weight: 600; background: #f9fafb; margin: 0; }
        .tb-faq-answer { padding: 1rem 1.25rem; border-top: 1px solid #e5e7eb; line-height: 1.7; color: #4b5563; }

        /* accordion */
        .tb-accordion-item { border: 1px solid #e5e7eb; border-radius: 0.5rem; margin-bottom: 0.5rem; overflow: hidden; }
        .tb-accordion-item summary { padding: 1rem 1.25rem; cursor: pointer; font-weight: 600; background: #f9fafb; }
        .tb-accordion-body { padding: 1rem 1.25rem; border-top: 1px solid #e5e7eb; }

        /* tabs */
        .tb-tabs { }
        .tb-tab-title { font-size: 1rem; font-weight: 700; padding: 0.75rem 0; border-bottom: 2px solid var(--tb-primary, #4f46e5); margin-bottom: 0.75rem; }
        .tb-tab-content { padding: 0.5rem 0; }

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
    </style>

    @stack('head')
</head>
<body>
    <a href="#canvas-main" class="canvas-skip-link">پرش به محتوا</a>

    {{-- ─── هدر canvas ─── --}}
    @if(!empty(trim($headerHtml)))
        <header class="canvas-site-header tb-site-header" role="banner" id="site-header">
            <div class="tb-header-inner">
                <div class="tb-header-brand">
                    <a href="{{ route('home') }}">{{ \App\Helpers\SiteHelper::siteName() }}</a>
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
                <a href="{{ route('home') }}" style="font-size:1.25rem;font-weight:700;color:#fff;text-decoration:none;">
                    {{ \App\Helpers\SiteHelper::siteName() }}
                </a>
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

    @stack('scripts')
</body>
</html>
