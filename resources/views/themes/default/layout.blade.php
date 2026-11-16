<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $seoTitle = trim($__env->yieldContent('title')) ?: \App\Helpers\Seo::title();
    @endphp
    <title>{{ $seoTitle }}</title>
    <x-seo-meta
        :title="$seoTitle"
        :description="trim($__env->yieldContent('meta_description'))"
        :image="trim($__env->yieldContent('meta_image'))"
        :canonical="trim($__env->yieldContent('canonical'))"
        :robots="trim($__env->yieldContent('meta_robots'))"
        :og-type="trim($__env->yieldContent('og_type')) ?: 'website'"
    />
    {{-- فونت آفلاین Vazirmatn (یکسان با پنل ادمین مشتری) --}}
    <link rel="preload" as="font" href="{{ asset('assets/fonts/vazirmatn/Vazirmatn-Regular.woff2') }}" type="font/woff2" crossorigin>
    <link rel="preload" as="font" href="{{ asset('assets/fonts/vazirmatn/Vazirmatn-Bold.woff2') }}" type="font/woff2" crossorigin>
    <style>@include('partials.vazirmatn-font-faces-inline')</style>
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    @php
        // TASK 3/3 Phase 6.5C (BLD-25) — resolves through CustomerMediaService.
        $faviconUrl  = app(\App\Services\CustomerMediaService::class)->resolveSiteAssetUrl(\App\Models\Setting::get('site_favicon', ''));
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
    @if(file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <link rel="stylesheet" href="{{ asset('css/customer-admin-layout.css') }}?v={{ @filemtime(public_path('css/customer-admin-layout.css')) ?: time() }}">
    @endif
    {{-- css_block از PlatformUx (design system defaults — آبی) --}}
    @if(!empty($themeBuilder['css_block'] ?? ''))
        <style>{!! $themeBuilder['css_block'] !!}</style>
    @endif
    {{-- Site preset / custom theme tokens — MUST come LAST to override css_block blue defaults --}}
    @include('partials.theme-engine-tokens')
    @php
        $tbGlobalStyles = [];
        try {
            $tbRaw = \Illuminate\Support\Facades\Cache::remember('canvas_theme_data', 60, function () {
                return \Illuminate\Support\Facades\DB::table('settings')->where('key', 'active_canvas_theme_data')->value('value') ?? '';
            });
            $tbDecoded = $tbRaw ? json_decode($tbRaw, true) : null;
            $tbGlobalStyles = is_array($tbDecoded['global_styles'] ?? null) ? $tbDecoded['global_styles'] : [];
        } catch (\Throwable $e) {
            $tbGlobalStyles = [];
        }
    @endphp
    @if(!empty($tbGlobalStyles))
        <style>{!! app(\SaaSPlatform\ThemeRuntime\ThemeTokenNormalizer::class)->renderCssBlock($tbGlobalStyles) !!}</style>
    @endif
    <style>@include('theme-runtime::css.contract')</style>
    <script>(function(){
        var KEY = 'tb-theme';
        function resolve(p) {
            if (p === 'system') {
                try { return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'; }
                catch (e) { return 'light'; }
            }
            return p === 'dark' ? 'dark' : 'light';
        }
        function apply(p) {
            document.documentElement.setAttribute('data-theme', resolve(p));
            document.documentElement.setAttribute('data-theme-pref', p);
        }
        var pref = 'system';
        try { pref = localStorage.getItem(KEY) || 'system'; } catch (e) {}
        apply(pref);
        window.__tbTheme = {
            get: function () { return pref; },
            set: function (p) {
                pref = (p === 'light' || p === 'dark' || p === 'system') ? p : 'system';
                apply(pref);
                try { localStorage.setItem(KEY, pref); } catch (e) {}
                document.dispatchEvent(new CustomEvent('tb-theme-change', { detail: { pref: pref, resolved: resolve(pref) } }));
            },
            cycle: function () {
                var order = ['light', 'dark', 'system'];
                this.set(order[(order.indexOf(pref) + 1) % order.length]);
            }
        };
        try {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function () {
                if (pref === 'system') { apply('system'); document.dispatchEvent(new CustomEvent('tb-theme-change', { detail: { pref: pref, resolved: resolve(pref) } })); }
            });
        } catch (e) {}
    })();</script>
    <style>
        * { box-sizing: border-box; }
        body { font-family: var(--tb-font-body, Vazirmatn, Tahoma, sans-serif); margin: 0; background: var(--tb-background, #f9fafb); color: var(--tb-text, #1f2937); min-height: 100vh; font-size: var(--tb-base-font-size, 16px); transition: background 0.15s, color 0.15s; }
        [data-theme="dark"] .card { background: var(--tb-surface, #374151); border-color: var(--tb-border, rgba(255,255,255,0.08)); color: var(--tb-text, #e5e7eb); }
        [data-theme="dark"] main { color: var(--tb-text, #e5e7eb); }
        [data-theme="dark"] footer .footer-links a { color: var(--tb-text-muted, #9ca3af); }
        [data-theme="dark"] .form-input,
        [data-theme="dark"] .form-textarea,
        [data-theme="dark"] .form-select {
            background: var(--tb-surface, #1f2937) !important;
            color: var(--tb-text, #e5e7eb) !important;
            border-color: var(--tb-border, #4b5563) !important;
        }
        [data-theme="dark"] .text-gray-500 { color: var(--tb-text-muted, #9ca3af) !important; }
        [data-theme="dark"] .text-gray-700 { color: var(--tb-text, #d1d5db) !important; }
        [data-theme="dark"] .border-gray-100 { border-color: var(--tb-border, #374151) !important; }
        .tb-tag-info { display:inline-block; padding:0.25rem 0.6rem; border-radius:999px; background:#dbeafe; color:#1d4ed8; font-size:0.8rem; }
        .tb-tag-warning { display:inline-block; padding:0.25rem 0.6rem; border-radius:999px; background:#fef3c7; color:#92400e; font-size:0.8rem; }
        .tb-tag-neutral { display:inline-block; padding:0.25rem 0.6rem; border-radius:999px; background:var(--tb-background, #f3f4f6); color:var(--tb-text, inherit); font-size:0.8rem; }
        [data-theme="dark"] .tb-tag-info { background:rgba(59,130,246,0.18); color:#93c5fd; }
        [data-theme="dark"] .tb-tag-warning { background:rgba(217,119,6,0.2); color:#fbbf24; }
        [data-theme="dark"] .tb-tag-neutral { background:var(--tb-surface, #374151); color:var(--tb-text-muted, #cbd5e1); }
        .tb-installment-plan-row:hover { background: var(--tb-background, #eef2ff) !important; }
        .tb-platform-menu-dropdown-link { text-decoration: none; color: inherit; }
        .tb-platform-menu-dropdown-link:hover { background: var(--tb-background, #f9fafb); }
        .tb-platform-menu-dropdown-link-active { background: var(--tb-background, #eff6ff); }
        .form-input, .form-textarea, .form-select { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 1rem; }
        .form-input:focus, .form-textarea:focus { outline: none; border-color: var(--platform-primary, #4f46e5); box-shadow: 0 0 0 2px rgba(79,70,229,0.2); }
        .form-label { display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem; }
        .form-error { font-size: 0.875rem; color: #dc2626; margin-top: 0.25rem; }
        [data-theme="dark"] .form-label { color: var(--tb-text-muted, #d1d5db); }
        [data-theme="dark"] .form-error { color: var(--tb-error-text, #f87171); }
        [data-theme="dark"] nav[aria-label="مسیر"] a { color: var(--tb-text-muted, #9ca3af); }
        .tb-flash { margin-bottom:1.5rem; padding:1rem; border-radius:0.5rem; display:flex; align-items:center; gap:0.5rem; }
        .tb-flash-success { background:#dcfce7; color:#166534; border:1px solid #bbf7d0; }
        .tb-flash-error { background:#fee2e2; color:#991b1b; border:1px solid #fecaca; }
        .tb-flash-warning { background:#fffbeb; color:#92400e; border:1px solid #fde68a; }
        .tb-flash-info { background:#eff6ff; color:#1e40af; border:1px solid #bfdbfe; }
        [data-theme="dark"] .tb-flash-success { background:rgba(22,163,74,0.18); color:#4ade80; border-color:rgba(74,222,128,0.25); }
        [data-theme="dark"] .tb-flash-error { background:rgba(220,38,38,0.18); color:#f87171; border-color:rgba(248,113,113,0.25); }
        [data-theme="dark"] .tb-flash-warning { background:rgba(217,119,6,0.18); color:#fbbf24; border-color:rgba(251,191,36,0.25); }
        [data-theme="dark"] .tb-flash-info { background:rgba(37,99,235,0.18); color:#93c5fd; border-color:rgba(147,197,253,0.25); }
        .nav-link { color: inherit; text-decoration: none; }
        .nav-link:hover { color: var(--platform-menu-link-active, #93c5fd); }
        .site-header .nav-link.font-semibold { color: var(--platform-menu-link-active, #93c5fd); }
        #mobile-menu a { color: inherit; text-decoration: none; display: block; padding: 0.5rem 0; }
        #mobile-menu a:hover { color: var(--platform-menu-link-active, #93c5fd); }
        .site-header .container-inner { max-width: 72rem; margin: 0 auto; padding: 0 1rem; }
        .site-header .header-row { display: flex; align-items: center; justify-content: space-between; min-height: 4rem; }
        .site-header #main-nav { display: flex; align-items: center; gap: 1.5rem; flex-wrap: wrap; }
        .site-header #mobile-menu { display: block; max-height: 0; opacity: 0; overflow: hidden; border-top: 0; padding: 0 1rem; transition: max-height .38s cubic-bezier(.16,1,.3,1), opacity .3s ease, padding .35s ease; }
        .site-header #mobile-menu > div { display: flex; flex-direction: column; gap: 0.5rem; }
        .site-header #mobile-menu.is-open { max-height: 80vh; opacity: 1; padding: 1rem; border-top: 1px solid rgba(255,255,255,0.2); overflow-y: auto; }
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
        nav[role="navigation"] a { text-decoration: none; background: var(--tb-surface, #f3f4f6); color: var(--tb-text, #374151); }
        nav[role="navigation"] a:hover { background: var(--tb-border, #e5e7eb); }
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
        .tb-hero { padding: 3rem 1.5rem; text-align: center; border-radius: 1rem; background: var(--tb-primary, var(--platform-primary, #4f46e5)); color: var(--tb-on-primary, #fff); margin-bottom: 2.5rem; }
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
        .tb-image { display: block; max-width: 100%; margin: 0 auto 1.5rem; }
        .tb-image .tb-img { display: block; max-width: 100%; height: auto; margin: 0 auto; max-height: 480px; object-fit: contain; }
        .tb-radius-sm { border-radius: 0.5rem; }
        .tb-radius-md { border-radius: 0.75rem; }
        .tb-radius-lg { border-radius: 1rem; }
        .tb-shadow-sm { box-shadow: 0 1px 3px rgba(15, 23, 42, 0.16); }
        .tb-shadow-md { box-shadow: 0 4px 12px rgba(15, 23, 42, 0.18); }
        .tb-shadow-lg { box-shadow: 0 12px 30px rgba(15, 23, 42, 0.22); }
        .tb-hover-lift { transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .tb-hover-lift:hover { transform: translateY(-4px); box-shadow: 0 10px 25px rgba(15, 23, 42, 0.22); }
        .tb-placeholder { min-height: 2rem; padding: 0.5rem 0.75rem; border-radius: 0.5rem; font-size: 0.875rem; }
        .tb-placeholder-invalid { background: #fef3c7; border: 1px dashed #d97706; color: #92400e; }
        .tb-placeholder-error { background: #fee2e2; border: 1px dashed #dc2626; color: #991b1b; }
        .tb-placeholder-label { font-family: inherit; }
        .tb-placeholder-detail { display: block; margin-top: 0.35rem; font-size: 0.8125rem; line-height: 1.6; }
        .tb-placeholder-detail code { display: block; margin-top: 0.15rem; font: 0.75rem/1.5 ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; overflow-wrap: anywhere; opacity: 0.85; }
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
        .tb-product-card { background: var(--tb-surface, #ffffff); border-radius: 0.75rem; border: 1px solid var(--tb-border, #e5e7eb); padding: 1.25rem 1rem; box-shadow: var(--tb-shadow-sm, 0 1px 2px rgb(0 0 0 / 0.05)); text-align: center; text-decoration: none; color: inherit; display: flex; flex-direction: column; gap: 0.5rem; }
        .tb-product-title { font-weight: 700; margin-bottom: 0.25rem; }
        .tb-product-price { font-weight: 700; color: var(--tb-primary, var(--platform-primary, #4f46e5)); }
        .tb-cta { margin-top: 2.5rem; padding: 2rem 1.5rem; border-radius: 1rem; text-align: center; background: var(--tb-primary, var(--platform-primary, #4f46e5)); color: var(--tb-on-primary, #fff); }
        .tb-cta-title { font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem; }
        .tb-cta-subtitle { opacity: 0.9; margin-bottom: 1rem; }
        .tb-cta .tb-btn { background: #fff !important; color: var(--tb-primary, var(--platform-primary, #4f46e5)) !important; margin-top: 0.5rem; }
        .tb-cta .tb-btn:hover { opacity: 0.95; }
        .tb-product-card-img { aspect-ratio: 1; background: var(--tb-background, #f3f4f6); overflow: hidden; border-radius: 0.5rem 0.5rem 0 0; display: flex; align-items: center; justify-content: center; }
        .tb-product-card-img img { width: 100%; height: 100%; object-fit: cover; }
        .tb-product-card-noimg { font-size: 2.5rem; color: var(--tb-text-muted, #9ca3af); }
        .tb-product-grid-empty { grid-column: 1 / -1; text-align: center; padding: 2rem; background: var(--tb-background, #f9fafb); border-radius: 0.75rem; border: 1px dashed var(--tb-border, #d1d5db); }
        .tb-product-grid-empty p { margin-bottom: 1rem; color: var(--tb-text-muted, #6b7280); }
        .tb-blog-posts { margin-top: 1.5rem; }
        .tb-section-title { margin-bottom: 1.25rem; font-size: 1.5rem; }
        .tb-blog-card { display: flex; flex-direction: column; background: var(--tb-surface, #fff); border-radius: 0.75rem; overflow: hidden; border: 1px solid var(--tb-border, #e5e7eb); text-decoration: none; color: inherit; box-shadow: 0 1px 3px rgba(0,0,0,0.06); transition: box-shadow 0.2s; }
        .tb-blog-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .tb-blog-card-img { aspect-ratio: 16/10; background: var(--tb-background, #f3f4f6); overflow: hidden; }
        .tb-blog-card-img img { width: 100%; height: 100%; object-fit: cover; }
        .tb-blog-card-noimg { display: flex; align-items: center; justify-content: center; font-size: 2rem; color: var(--tb-text-muted, #9ca3af); }
        .tb-blog-card-body { padding: 1.25rem; flex: 1; }
        .tb-blog-card-title { font-size: 1.1rem; font-weight: 700; margin: 0 0 0.5rem; line-height: 1.35; }
        .tb-blog-card-meta { font-size: 0.8rem; color: var(--tb-text-muted, #6b7280); margin: 0 0 0.5rem; }
        .tb-blog-card-excerpt { font-size: 0.9rem; color: var(--tb-text-muted, #4b5563); margin: 0; line-height: 1.6; }
        @media (max-width: 767px) { .tb-blog-grid.tb-grid-cols-3 { grid-template-columns: 1fr; } }
        .tb-testimonials-grid { display: grid; gap: 1.25rem; margin-top: 1rem; }
        @media (min-width: 768px) { .tb-testimonials-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (min-width: 1024px) { .tb-testimonials-grid { grid-template-columns: repeat(3, 1fr); } }
        .tb-testimonial-card { background: var(--tb-surface, #fff); padding: 1.5rem; border-radius: 0.75rem; border: 1px solid var(--tb-border, #e5e7eb); box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
        .tb-testimonial-rating { color: #f59e0b; font-size: 1rem; margin-bottom: 0.5rem; }
        .tb-testimonial-text { margin: 0 0 0.75rem; line-height: 1.7; color: var(--tb-text-muted, #4b5563); }
        .tb-testimonial-author { margin: 0; font-size: 0.9rem; font-weight: 600; color: var(--tb-text, #111827); }
        .tb-faq-item { border: 1px solid var(--tb-border, #e5e7eb); border-radius: 0.5rem; margin-bottom: 0.5rem; overflow: hidden; }
        .tb-faq-question { padding: 1rem 1.25rem; cursor: pointer; font-weight: 600; background: var(--tb-surface, #f9fafb); margin: 0; }
        .tb-faq-answer { padding: 1rem 1.25rem; border-top: 1px solid var(--tb-border, #e5e7eb); line-height: 1.7; color: var(--tb-text-muted, #4b5563); }
        .tb-grid .tb-text.prose { background: var(--tb-background, #f9fafb); border: 1px solid var(--tb-border, #e5e7eb); padding: 0.75rem 1rem; border-radius: 0.5rem; text-align: center; font-weight: 500; margin: 0; }
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
        [data-theme="dark"] .tb-promo-info { background: rgba(59,130,246,0.18); }
        [data-theme="dark"] .tb-promo-info .tb-promo-banner__link { color: #93c5fd; }
        [data-theme="dark"] .tb-promo-success { background: rgba(22,163,74,0.18); }
        [data-theme="dark"] .tb-promo-success .tb-promo-banner__link { color: #4ade80; }
        .tb-gallery { margin: 1.5rem 0; }
        .tb-gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 1rem; }
        .tb-gallery-img { width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: 0.75rem; border: 1px solid var(--tb-border, #e5e7eb); }
        .tb-gallery-empty { padding: 2rem; text-align: center; color: var(--tb-text-muted, #6b7280); background: var(--tb-background, #f9fafb); border-radius: 0.75rem; border: 1px dashed var(--tb-border, #d1d5db); }
        .tb-countdown { padding: 1.25rem; border-radius: 1rem; background: linear-gradient(135deg, #1c1917, #292524); color: #fef3c7; text-align: center; margin: 1rem 0; }
        .tb-countdown__label { display: block; font-weight: 700; margin-bottom: 0.35rem; }
        .tb-countdown__hint { display: block; margin-top: 0.5rem; font-size: 0.85rem; opacity: 0.88; }
        .tb-custom-html { line-height: 1.75; color: var(--tb-text-muted, #4b5563); }
        .tb-bg-background { background-color: var(--tb-background, #f9fafb) !important; }
        .tb-bg-surface { background-color: var(--tb-surface, #ffffff) !important; }
        .tb-bg-primary { background-color: var(--tb-primary, var(--platform-primary, #4f46e5)) !important; color: var(--tb-on-primary, #fff) !important; }
        .tb-bg-primary a { color: var(--tb-on-primary, #fff) !important; opacity: 0.95; }
        .tb-bg-primary a:hover { opacity: 1; }
        .tb-bg-text { background-color: var(--tb-text, #111827) !important; color: #fff !important; }
        .tb-bg-text a { color: #e5e7eb !important; }
        [data-theme="dark"] .tb-bg-text { color: var(--tb-background, #111827) !important; }
        [data-theme="dark"] .tb-bg-text a { color: var(--tb-background, #111827) !important; }
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
        .tb-site-header .tb-search-bar-wrap input { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--tb-border, #e5e7eb); border-radius: 0.5rem; font-size: 0.9rem; background: var(--tb-surface, #fff); color: var(--tb-text, #1f2937); }
        .tb-site-header .tb-cart-link { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.5rem 0.75rem; border-radius: 0.5rem; background: var(--tb-primary, var(--platform-primary, #4f46e5)); color: #fff !important; text-decoration: none; font-weight: 600; }
        .tb-site-header .tb-cart-link:hover { opacity: 0.95; }
        .tb-menu-btn { display: none; padding: 0.5rem; border: none; background: transparent; cursor: pointer; color: inherit; border-radius: 0.5rem; }
        .tb-mobile-menu { display: block; max-height: 0; opacity: 0; overflow: hidden; border-top: 0; padding: 0 1rem; background: var(--tb-surface, #fff); transition: max-height .38s cubic-bezier(.16,1,.3,1), opacity .3s ease, padding .35s ease; }
        .tb-mobile-menu.is-open { max-height: 80vh; opacity: 1; padding: 1rem; border-top: 1px solid rgba(0,0,0,0.08); overflow-y: auto; }
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
        .tb-site-footer { border-top: 1px solid var(--tb-border, #e5e7eb); margin-top: 3rem; box-shadow: 0 -1px 3px rgba(0,0,0,0.06); }
        .tb-site-footer .tb-section { padding-top: 2rem !important; padding-bottom: 2rem !important; }
        .tb-grid.tb-grid-cols-5 { grid-template-columns: repeat(5, minmax(0, 1fr)); }
        .tb-grid.tb-grid-cols-6 { grid-template-columns: repeat(6, minmax(0, 1fr)); }
        @media (max-width: 767px) {
            .tb-grid.tb-grid-cols-5, .tb-grid.tb-grid-cols-6 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
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
        .tb-features-section-header { text-align: center; margin-bottom: 2.5rem; }
        .tb-features-section-title { font-size: clamp(1.375rem, 2.5vw, 1.875rem); font-weight: 800; color: var(--tb-text, #111827); margin: 0 0 0.5rem; }
        .tb-features-section-sub { color: var(--tb-text-muted, #6b7280); font-size: 1rem; margin: 0; }
        .tb-stats-grid { display: flex; flex-wrap: wrap; gap: 1.5rem; }
        .tb-stat-item { flex: 1 1 8rem; text-align: center; }
        .tb-stat-value { display: block; font-size: 1.875rem; font-weight: 800; color: var(--tb-primary-text, var(--platform-primary, #4f46e5)); }
        .tb-stat-suffix { font-size: 1.25rem; }
        .tb-stat-label { display: block; font-size: 0.875rem; color: var(--tb-text-muted, #6b7280); margin-top: 0.25rem; }
        .tb-service-card { display: block; background: var(--tb-surface, #fff); border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.5rem; text-decoration: none; color: inherit; transition: box-shadow 0.2s; }
        .tb-service-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
        .tb-service-icon { font-size: 1.75rem; margin-bottom: 0.75rem; }
        .tb-service-title { font-size: 1rem; font-weight: 700; color: var(--tb-text, #111827); margin: 0 0 0.4rem; }
        .tb-service-text { font-size: 0.875rem; color: var(--tb-text-muted, #6b7280); line-height: 1.6; margin: 0; }
        .tb-services-grid, .tb-project-cards-grid { margin: 0; }
        .tb-project-card { display: block; background: var(--tb-surface, #fff); border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.5rem; text-decoration: none; color: inherit; }
        .tb-project-card-category { display: inline-block; background: var(--tb-background, #eff6ff); color: var(--tb-primary, var(--platform-primary, #4f46e5)); padding: 0.2rem 0.7rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; margin-bottom: 0.5rem; }
        .tb-project-card-title { font-size: 1.0625rem; font-weight: 700; color: var(--tb-text, #111827); margin: 0 0 0.4rem; }
        .tb-project-card-text { font-size: 0.875rem; color: var(--tb-text-muted, #6b7280); line-height: 1.5; margin: 0; }
        .tb-contact-info-grid { display: flex; flex-wrap: wrap; justify-content: center; gap: 1.5rem; }
        .tb-contact-info-item { display: flex; align-items: flex-start; gap: 0.65rem; }
        .tb-contact-info-icon { font-size: 1.25rem; }
        .tb-contact-info-label { font-size: 0.75rem; font-weight: 700; color: var(--tb-text-muted, #6b7280); text-transform: uppercase; letter-spacing: 0.04em; margin: 0; }
        .tb-contact-info-value { font-size: 0.9375rem; font-weight: 600; color: var(--tb-text, #111827); margin: 0.15rem 0 0; }
        .tb-about-section { max-width: 48rem; margin: 0 auto; text-align: center; }
        .tb-about-features { list-style: none; padding: 0; margin: 1rem 0; text-align: start; display: inline-block; }
        .tb-about-feature { padding: 0.3rem 0; }
        .tb-about-stats { justify-content: center; margin-top: 1.5rem; }
        .tb-hero-split-single { padding: 3rem 0; }
        .tb-hero-trust-inner { max-width: 42rem; margin: 0 auto; text-align: center; }
        .tb-hero-trust-meta { display: flex; flex-wrap: wrap; justify-content: center; gap: 1.25rem; margin-top: 1.25rem; font-size: 0.9rem; color: var(--tb-text-muted, #6b7280); }
        .tb-hero-trust-rating, .tb-hero-trust-phone { display: inline-flex; align-items: center; gap: 0.4rem; }
        .tb-nav-header { border-bottom: 1px solid rgba(0,0,0,0.08); }
        .tb-nav-header-inner { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; min-height: 4rem; }
        .tb-nav-header-logo { font-weight: 800; font-size: 1.15rem; text-decoration: none; color: inherit; }
        .tb-nav-header-links { display: flex; flex-wrap: wrap; gap: 1.25rem; }
        .tb-nav-header-link { text-decoration: none; color: inherit; font-weight: 500; }
        .tb-nav-header-actions { display: flex; align-items: center; gap: 1rem; }
        .tb-nav-header-search-input { padding: 0.4rem 0.75rem; border: 1px solid var(--tb-border, #e5e7eb); border-radius: 0.5rem; background: var(--tb-surface, #fff); color: var(--tb-text, #1f2937); }
        .tb-nav-header-phone, .tb-nav-header-icon-link { text-decoration: none; color: inherit; }
        .tb-nav-footer { padding: 3rem 0 1.5rem; }
        .tb-nav-footer-brand { margin-bottom: 2rem; }
        .tb-nav-footer-logo { font-weight: 800; font-size: 1.1rem; margin: 0 0 0.5rem; }
        .tb-nav-footer-tagline { opacity: 0.75; font-size: 0.875rem; margin: 0; }
        .tb-nav-footer-cols { display: flex; flex-wrap: wrap; gap: 2rem; margin-bottom: 2rem; }
        .tb-nav-footer-col-title { font-size: 0.9rem; font-weight: 700; margin: 0 0 0.75rem; }
        .tb-nav-footer-links { list-style: none; padding: 0; margin: 0; }
        .tb-nav-footer-links li { margin-bottom: 0.4rem; }
        .tb-nav-footer-links a { color: inherit; opacity: 0.7; text-decoration: none; font-size: 0.875rem; }
        .tb-nav-footer-bottom { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1.25rem; }
        .tb-nav-footer-copyright { opacity: 0.55; font-size: 0.8rem; margin: 0; }
        .tb-nav-footer-legal-link { color: inherit; opacity: 0.55; font-size: 0.8rem; text-decoration: none; margin-inline-end: 1rem; }
        .tb-breadcrumb { font-size: 0.875rem; margin-bottom: 1rem; }
        .tb-breadcrumb a { color: var(--tb-primary, var(--platform-primary, #4f46e5)); text-decoration: none; }
        .tb-login-register { display: flex; align-items: center; gap: 1rem; }
        .tb-price-box-empty { padding: 1rem; text-align: center; }
        .tb-client-logos-empty { padding: 2rem 0; text-align: center; }
        .tb-client-logos-title { font-size: 0.875rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; color: var(--tb-text-muted, #9ca3af); margin: 0 0 0.75rem; }
    </style>
    @if(\App\Helpers\SiteHelper::moduleEnabled('advertisements'))
    <style>
    @keyframes ad-blink { 0%,100%{opacity:1} 50%{opacity:0.3} }
    @keyframes ad-fade { 0%,100%{opacity:1} 50%{opacity:0.5} }
    .advertisements-header { margin-bottom: 1rem; }
    .advertisements-footer { margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--tb-border, #e5e7eb); }
    .advertisements-content_top { margin-bottom: 1rem; }
    .advertisements-content_bottom { margin-top: 1rem; }
    .typewriter-cursor { animation: ad-blink 0.8s step-end infinite; }
    </style>
    @endif
    @stack('head')
    {{-- ADR-082: nothing may follow the header sheet. Zero-gap rules live in theme-runtime-contract.css. --}}
    <style>@include('theme-runtime::css.header')</style>
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

        $renderedHeaderHtml = !empty($headerBlocks)
            ? app(\App\Services\ThemeBuilder\BlockRenderer::class)
                ->setContext([
                    'menu_html' => $headerMenuHtml,
                    'site_name' => \App\Helpers\SiteHelper::siteName(),
                ])
                ->renderBlocks($headerBlocks)
            : '';
        $hasNavHeaderBlock = str_contains($renderedHeaderHtml, 'tb-nav-header');
    @endphp
    @if($showHeader && !empty($headerBlocks))
        @if($hasNavHeaderBlock)
            {!! $renderedHeaderHtml !!}
        @else
            <header class="tb-site-header {{ $stickyHeader ? 'tb-header-sticky' : '' }}" role="banner" id="site-header">
                <div class="tb-header-inner">
                    <div class="tb-header-brand">
                        <x-site-brand />
                    </div>
                    <button type="button" id="tb-mobile-menu-btn" class="tb-menu-btn" aria-label="منو" aria-expanded="false">
                        <x-core-ui::icon name="menu" style="width:24px;height:24px" />
                    </button>
                    <div class="tb-header-nav-wrap">
                        {!! $renderedHeaderHtml !!}
                    </div>
                </div>
                @php
                    $tbHeaderWantsDarkToggle = collect($headerBlocks ?? [])
                        ->contains(fn ($b) => is_array($b) && ($b['type'] ?? null) === 'nav_header' && !empty($b['props']['show_dark_toggle']));
                @endphp
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
                        @if($tbHeaderWantsDarkToggle)
                            <button type="button" id="tb-mobile-theme-toggle" class="tb-theme-toggle" aria-label="تغییر حالت رنگی" style="margin-top:.5rem;">
                                <span class="tb-theme-toggle-icon" aria-hidden="true">🌓</span>
                            </button>
                            <script>(function(){
                                var btn = document.getElementById('tb-mobile-theme-toggle');
                                if (!btn || btn.dataset.tbInit) return;
                                btn.dataset.tbInit = '1';
                                var ICONS = {light:'☀️', dark:'🌙', system:'💻'};
                                var LABELS = {
                                    light: 'حالت رنگی: روشن — برای تغییر به تاریک کلیک کنید',
                                    dark: 'حالت رنگی: تاریک — برای تغییر به خودکار (سیستم) کلیک کنید',
                                    system: 'حالت رنگی: هماهنگ با سیستم — برای تغییر به روشن کلیک کنید'
                                };
                                function render() {
                                    var p = (window.__tbTheme && window.__tbTheme.get) ? window.__tbTheme.get() : 'system';
                                    var icon = btn.querySelector('.tb-theme-toggle-icon');
                                    if (icon) icon.textContent = ICONS[p] || ICONS.system;
                                    btn.setAttribute('aria-label', LABELS[p] || LABELS.system);
                                }
                                btn.addEventListener('click', function () {
                                    if (window.__tbTheme && window.__tbTheme.cycle) window.__tbTheme.cycle();
                                    render();
                                });
                                document.addEventListener('tb-theme-change', render);
                                render();
                            })();</script>
                        @endif
                    </div>
                </div>
            </header>
        @endif
    @elseif($showHeader)
    <header class="site-header" style="background: var(--platform-menu-bg, #1e293b); color: var(--platform-menu-text, #e2e8f0);">
        <div class="container-inner">
            <div class="header-row">
                <x-site-brand style="font-size: 1.25rem; font-weight: 700; color: var(--platform-primary, #a5b4fc); text-decoration: none;" />
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
                <div class="tb-flash tb-flash-success"
                     x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(()=>show=false,5000)">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <span style="flex:1">{{ session('success') }}</span>
                    <button @click="show=false" style="background:none;border:none;cursor:pointer;opacity:0.6;font-size:1rem;color:inherit;" aria-label="بستن">✕</button>
                </div>
            @endif
            @if(session('error'))
                <div class="tb-flash tb-flash-error"
                     x-data="{ show: true }" x-show="show" x-transition>
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                    <span style="flex:1">{{ session('error') }}</span>
                    <button @click="show=false" style="background:none;border:none;cursor:pointer;opacity:0.6;font-size:1rem;color:inherit;" aria-label="بستن">✕</button>
                </div>
            @endif
            @if(session('warning'))
                <div class="tb-flash tb-flash-warning"
                     x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(()=>show=false,7000)">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    <span style="flex:1">{{ session('warning') }}</span>
                    <button @click="show=false" style="background:none;border:none;cursor:pointer;opacity:0.6;font-size:1rem;color:inherit;" aria-label="بستن">✕</button>
                </div>
            @endif
            @if(session('info'))
                <div class="tb-flash tb-flash-info"
                     x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(()=>show=false,6000)">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                    <span style="flex:1">{{ session('info') }}</span>
                    <button @click="show=false" style="background:none;border:none;cursor:pointer;opacity:0.6;font-size:1rem;color:inherit;" aria-label="بستن">✕</button>
                </div>
            @endif
            @include('partials.advertisement-zone', ['position' => 'content_top'])
            @hasSection('breadcrumb')
                <nav style="margin-bottom: 1.5rem; font-size: 0.875rem; color: var(--tb-text-muted, #6b7280);" aria-label="مسیر">
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
    <footer style="margin-top: 3rem; padding-top: 2rem; padding-bottom: 1.5rem; background: var(--tb-background, #f3f4f6); border-top: 1px solid var(--tb-border, #e5e7eb);">
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
            <p style="margin-top: 1rem; font-size: 0.875rem; color: var(--tb-text-muted, #6b7280); text-align: center;">© {{ date('Y') }} {{ \App\Helpers\SiteHelper::siteName() }}. تمامی حقوق محفوظ است.</p>
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
    {{-- Global Scroll-to-Top Widget --}}
    @php
        $__globalStt = \App\Models\Setting::get('scroll_to_top', []);
        if (is_string($__globalStt)) {
            $__globalStt = json_decode($__globalStt, true) ?: [];
        }
        $__globalSttEnabled = !empty($__globalStt['enabled']) || \App\Models\Setting::get('scroll_to_top_enabled', false);
    @endphp
    @if($__globalSttEnabled)
        {!! app(\App\Services\ThemeBuilder\BlockRenderer::class)->renderBlock([
            'type' => 'scroll_to_top',
            'id' => 'global-scroll-to-top',
            'props' => is_array($__globalStt) ? $__globalStt : [],
        ]) !!}
    @endif

    <x-core-ui::payment-connection-warning />
    @includeWhen(($themeRuntimeMode ?? null) === \App\Services\ThemeCanvas\ThemeRuntimeDocument::MODE_PUBLIC, 'partials.theme-public-runtime')
    @stack('scripts')
</body>
</html>
