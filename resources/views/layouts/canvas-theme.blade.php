<!DOCTYPE html>
{{-- documentLang/documentDirection are pre-validated (allowlist: only
     'ltr'/'rtl', 'en'/'fa') by ThemeCanvasPageController before reaching
     this view — the ?? fallback here is defense-in-depth only, for any
     other future caller of this layout that might omit them. --}}
<html lang="{{ $documentLang ?? 'fa' }}" dir="{{ $documentDirection ?? 'rtl' }}">
<head>
    <meta charset="utf-8">
    {{-- Commercial Productization fase-4: real dark-mode support, not just
         backend token capability. This MUST run synchronously, this early,
         before any CSS renders — reading the user's stored preference and
         applying [data-theme] before first paint is the only way to avoid
         a flash of the wrong theme (a real, common dark-mode bug this
         phase must not ship). window.__tbTheme is the one shared API every
         BlockRenderer::renderDarkModeToggle() button instance on the page
         talks to, so multiple toggles (e.g. header + footer) stay in sync
         via the 'tb-theme-change' event. --}}
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
            var resolved = resolve(p);
            document.documentElement.setAttribute('data-theme', resolved);
            document.documentElement.setAttribute('data-theme-pref', p);
            if (resolved === 'dark') {
                document.documentElement.classList.add('dark', 'tb-dark-mode');
                if (document.body) document.body.classList.add('dark', 'tb-dark-mode');
            } else {
                document.documentElement.classList.remove('dark', 'tb-dark-mode');
                if (document.body) document.body.classList.remove('dark', 'tb-dark-mode');
            }
            try {
                localStorage.setItem('theme', resolved);
                localStorage.setItem(KEY, p);
            } catch (e) {}
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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $siteTitle  = \App\Helpers\SiteHelper::siteName();
        $fullTitle  = ($pageTitle ?? $siteTitle) . ($pageTitle ? ' — ' . $siteTitle : '');
        $gs         = $globalStyles ?? [];
        /*
         * $seoMeta is resolved ONCE, server-side, by SeoService::canvasPage()
         * from the canvas page array + its own block tree (see
         * ThemeCanvasPageController::renderPage()). It is not optional data:
         * the ?? [] keeps this layout renderable for any future caller that
         * has not adopted the service yet, in which case the component below
         * falls back to the same SeoService authority it always used.
         *
         * (A PHP comment, not a Blade {{-- --}} one: Blade comments are not
         * valid inside an @php block and compile to a syntax error.)
         */
        $seoMeta    = $seoMeta ?? [];
        $ogLocale   = ($documentLang ?? 'fa') === 'en' ? 'en_US' : 'fa_IR';
    @endphp
    <title>{{ $fullTitle }}</title>
    <x-seo-meta
        :title="$fullTitle"
        :description="($seoMeta['description'] ?? '')"
        :image="($seoMeta['image'] ?? '')"
        :imageAlt="($seoMeta['imageAlt'] ?? '')"
        :canonical="($seoMeta['canonical'] ?? '')"
        :robots="($seoMeta['robots'] ?? '')"
        :ogType="($seoMeta['ogType'] ?? 'website')"
        :locale="$ogLocale"
        :schema="($schema ?? [])"
    />

    {{-- TASK 3/3 Phase 6.5C (BLD-25) — real defect found and fixed: this
         Canvas layout (the real public render for every Canvas-active
         site, including the Universal Theme) had NO favicon handling at
         all — the Settings -> Favicon upload was completely inert for any
         Canvas-active site, even though the classic (non-Canvas) layout
         already wired it correctly. Same resolution logic as
         themes/default/layout.blade.php, via the same canonical
         CustomerMediaService. --}}
    @php
        $faviconUrl = app(\App\Services\CustomerMediaService::class)->resolveSiteAssetUrl(\App\Models\Setting::get('site_favicon', ''));
        $faviconMime = $faviconUrl ? match(strtolower(pathinfo($faviconUrl, PATHINFO_EXTENSION))) {
            'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp', default => 'image/jpeg',
        } : null;
    @endphp
    @if($faviconUrl)
    <link rel="icon" type="{{ $faviconMime }}" href="{{ $faviconUrl }}">
    <link rel="shortcut icon" href="{{ $faviconUrl }}">
    @endif

    {{-- فونت آفلاین Vazirmatn --}}
    <link rel="preload" as="font" href="{{ asset('assets/fonts/vazirmatn/Vazirmatn-Regular.woff2') }}" type="font/woff2" crossorigin>
    <link rel="preload" as="font" href="{{ asset('assets/fonts/vazirmatn/Vazirmatn-Bold.woff2') }}" type="font/woff2" crossorigin>
    <style>@include('partials.vazirmatn-font-faces-inline')</style>

    @if(file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @if($platformPresentationCss ?? true)
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            {{-- Generic opt-in isolation: presentation CSS is theme-owned;
                 platform JS remains independently loaded. --}}
            @vite('resources/js/app.js')
        @endif
    @elseif($platformPresentationCss ?? true)
        <link rel="stylesheet" href="{{ asset('css/customer-admin-layout.css') }}?v={{ @filemtime(public_path('css/customer-admin-layout.css')) ?: time() }}">
    @endif

    {{-- Site preset / custom theme base tokens --}}
    @include('partials.theme-engine-tokens')
    {{-- CSS متغیرهای قالب canvas (اولویت بالاتر از preset) --}}
    <style>
        {{-- Canvas fidelity fix (2026-09-20): extracted verbatim into
             partials/theme-canvas-style-contract.blade.php so this real
             public render AND the Theme Design Canvas editor's own live
             iframe (admin/theme-design/canvas-live-preview.blade.php —
             what the WYSIWYG editor surface actually shows) share one
             single, un-driftable CSS source. That iframe previously carried
             its own tiny, independently hand-maintained subset (no
             theme-runtime::css.contract, no nav_header/nav_footer,
             hero_split/hero_gradient, gallery, promo_banner/countdown,
             wishlist/installment CSS at all), which is why real theme
             content rendered unstyled/broken there. Do not re-add any of
             these rules locally; edit the partial. --}}
        @include('partials.theme-canvas-style-contract', ['gs' => $gs])
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

    {{-- Phase 8 Step 1 — Asset-on-Demand. Theme-global assets (themeAssets)
         are needed by every page and stay eager. The per-block assets the
         blocks on THIS page actually require (assetManifest — extension
         assets only) are injected from the manifest; null manifest ⇒ Safe
         Default: the historical eager global injection, unchanged, so a
         manifest problem can never strip a live page of its assets. --}}
    @php
        $__manifest = ($assetManifest ?? null);
        $__safeDefaultCss = array_values(array_unique(array_merge(
            (array) ($themeAssets['css'] ?? []),
            (array) ($extensionAssets['css'] ?? []),
        )));
        $__pageCss = is_array($__manifest) ? array_values((array) ($__manifest['css'] ?? [])) : [];
    @endphp

    @foreach(($themeAssets['css'] ?? []) as $__href)
    <link rel="stylesheet" href="{{ $__href }}">
    @endforeach

    @if ($__manifest === null)
    @foreach(array_diff($__safeDefaultCss, (array) ($themeAssets['css'] ?? [])) as $__href)
    <link rel="stylesheet" href="{{ $__href }}">
    @endforeach
    @else
    <script type="application/json" id="theme-asset-manifest" data-tb-asset-manifest>{!! json_encode(['css' => $__pageCss, 'js' => array_values((array) ($__manifest['js'] ?? []))], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP) !!}</script>
    @foreach($__pageCss as $__href)
    <link rel="preload" as="style" href="{{ $__href }}">
    <link rel="stylesheet" href="{{ $__href }}">
    @endforeach
    @endif
    @stack('head')
</head>
<body>
    <a href="#canvas-main" class="canvas-skip-link">پرش به محتوا</a>

    {{-- ─── هدر canvas ─── --}}
    {{-- Generic, opt-in "theme owns its own chrome" capability
         (global_styles.chrome_owner === 'theme'): the theme's own header
         markup — still rendered by the exact same BlockRenderer pipeline
         as always — is emitted bare, with neither this platform wrapper
         nor the platform-branded fallback below. Every existing theme
         (chrome_owner absent) is completely unaffected: this branch is
         new, the @else branch below is byte-for-byte the pre-existing
         behavior. Skip-link and <main> below are untouched either way —
         "theme owns chrome" means the header/footer PRESENTATION only,
         never the surrounding accessibility structure. --}}
    @unless($omitChrome ?? false)
    @if(($chromeOwner ?? 'platform') === 'theme')
        {!! $headerHtml !!}
    @elseif(!empty(trim($headerHtml)))
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
            {{-- Phase 1 (saas-platform ADR-067): when the header block carries
                 its own links (nav_header, incl. mega menus), the phone menu
                 shows those — copied in by the script below — instead of a
                 fixed list that ignored them. --}}
            @php $__blockNav = str_contains($headerHtml, 'class="tb-nav-header-link'); @endphp
            <div id="tb-mobile-menu" class="tb-mobile-menu" aria-hidden="true">
                <div class="tb-mobile-menu-inner">
                    @if($__blockNav)
                        <nav class="tb-mobile-menu-nav" data-tb-mobile-nav aria-label="منوی اصلی"></nav>
                    @else
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
                    @endif
                    @auth
                        <a href="{{ route('account.dashboard') }}">حساب من</a>
                        @if(auth()->user()->isTenantAdmin())
                            <a href="{{ route('admin.dashboard') }}">پنل مدیریت</a>
                        @endif
                    @else
                        <a href="{{ route('login') }}">ورود</a>
                    @endauth
                    {{-- Real, proven gap (khab-shimana-tablet dark-mode
                         certification, 2026-09-21): .tb-header-nav-wrap above
                         is display:none below 768px, so a real
                         .tb-theme-toggle authored via nav_header's
                         show_dark_toggle is unreachable at mobile width — a
                         real interactive Playwright click confirmed it. This
                         mirrors that toggle's own markup/script exactly (see
                         BlockRenderer::renderDarkModeToggle()), gated on
                         whether the real rendered header actually has one, so
                         no second toggle appears for a header that never
                         opted in. --}}
                    @if(str_contains($headerHtml, 'tb-theme-toggle'))
                        <button type="button" id="tb-mobile-theme-toggle" class="tb-theme-toggle" aria-label="تغییر حالت رنگی" style="margin-top:.5rem;">
                            <span class="tb-theme-toggle-icon" aria-hidden="true">{!! \SaaSPlatform\ThemeRuntime\Support\IconLibrary::svg('monitor') !!}</span>
                        </button>
                        <script>(function(){
                            var btn = document.getElementById('tb-mobile-theme-toggle');
                            if (!btn || btn.dataset.tbInit) return;
                            btn.dataset.tbInit = '1';
                            var ICONS = {!! json_encode(['light' => \SaaSPlatform\ThemeRuntime\Support\IconLibrary::svg('sun'), 'dark' => \SaaSPlatform\ThemeRuntime\Support\IconLibrary::svg('moon'), 'system' => \SaaSPlatform\ThemeRuntime\Support\IconLibrary::svg('monitor')], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES) !!};
                            var LABELS = {
                                light: 'حالت رنگی: روشن — برای تغییر به تاریک کلیک کنید',
                                dark: 'حالت رنگی: تاریک — برای تغییر به خودکار (سیستم) کلیک کنید',
                                system: 'حالت رنگی: هماهنگ با سیستم — برای تغییر به روشن کلیک کنید'
                            };
                            function render() {
                                var p = (window.__tbTheme && window.__tbTheme.get) ? window.__tbTheme.get() : 'system';
                                var icon = btn.querySelector('.tb-theme-toggle-icon');
                                if (icon) icon.innerHTML = ICONS[p] || ICONS.system;
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
    @endunless

    {{-- ─── محتوای اصلی ─── --}}
    <main id="canvas-main" class="canvas-main" tabindex="-1">
        {{-- SEO Phase 4.2 — H1 safety net.
             The flag comes from HeaderIntegrityService via the controller,
             so this decision is made by the same DOM parse that the Phase 4.1
             audit logs from — a page can never be "no violation" in the log
             and "missing H1" in the markup, because there is only one count.

             Replaces a pre-existing inline-styled fallback guarded by its own
             preg_match(). That regex was both a second, divergent source of
             truth (it matched a literal "<h1" inside a <script> or an
             attribute and so could suppress a fallback the DOM says is
             needed) and unstyleable, since .visually-hidden did not exist.

             Fires ONLY when the content has zero H1. With two or more the
             author owns the structure and 4.1 already logged it; silently
             adding another H1 there would turn a reported problem into a
             worse one. --}}
        @if($needsH1Fallback ?? false)
            <h1 class="visually-hidden">{{ $pageTitle }}</h1>
        @endif
        {!! $contentHtml !!}
    </main>

    {{-- ─── فوتر canvas ─── --}}
    {{-- Same generic capability as the header above — see that comment. --}}
    @unless($omitChrome ?? false)
    @if(($chromeOwner ?? 'platform') === 'theme')
        {!! $footerHtml !!}
    @elseif(!empty(trim($footerHtml)))
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
    @endunless

    {{-- ─── Alpine.js برای تب‌ها، آکوردئون و منوی موبایل ─── --}}
    <script>
    (function () {
        // منوی موبایل
        var btn = document.getElementById('tb-mobile-menu-btn');
        var menu = document.getElementById('tb-mobile-menu');
        // Phase 1 (ADR-067): copy the header block's own links (and mega
        // menus, which site-header.js toggles by delegation) into the phone menu.
        var mobileNav = menu && menu.querySelector('[data-tb-mobile-nav]');
        var blockNav = document.querySelector('.tb-header-nav-wrap .tb-nav-header-links');
        if (mobileNav && blockNav) {
            mobileNav.innerHTML = blockNav.innerHTML;
            mobileNav.querySelectorAll('[id]').forEach(function (el) { el.id = el.id + '-m'; });
            mobileNav.querySelectorAll('[aria-controls]').forEach(function (el) { el.setAttribute('aria-controls', el.getAttribute('aria-controls') + '-m'); });
        }
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

    @foreach(($themeAssets['js'] ?? []) as $__themeSrc)
    <script src="{{ $__themeSrc }}" defer></script>
    @endforeach
    @if (($assetManifest ?? null) !== null)
    {{-- Phase 8 hydration bridge: inject ONLY the manifest-listed scripts,
         non-blocking (async, appended at body end). Theme-global scripts
         stay eager above; this covers the per-block manifest set. If the
         island is missing or unparseable, nothing is injected — the page's
         static content never depends on these scripts to render. --}}
    <script>
    (function () {
        var island = document.querySelector('script[type="application/json"][data-tb-asset-manifest]');
        if (!island) { return; }
        var manifest;
        try { manifest = JSON.parse(island.textContent || '{}'); } catch (e) { return; }
        ((manifest && manifest.js) || []).forEach(function (src) {
            if (typeof src !== 'string' || src === '') { return; }
            var s = document.createElement('script');
            s.src = src;
            s.async = true;
            document.body.appendChild(s);
        });
    })();
    </script>

<!-- Phase 8 (1.2) — viewport-triggered asset hydration.

     The bridge above loads the manifest's scripts eagerly once, at body
     end. This engine is the SECOND, lazy tier: a block that only needs an
     asset when it actually scrolls into view. The manifest remains the
     single source of truth for "what is already on this page" — the
     engine re-reads it rather than keeping a second list, so a page served
     WITHOUT a manifest (the $assetManifest ?? null branch above) simply
     has nothing to cross-reference against and this engine stands down
     instead of guessing.

     BLOCK_ASSETS is intentionally EMPTY as shipped. No per-block asset
     files exist in this installation yet (verified: public/js and
     public/css contain only the two platform stylesheets), and naming
     files that do not exist would inject 404s that degrade real pages.
     The engine below is complete and provably correct; it becomes useful
     the moment an entry is added. Adding one is a data change only — no
     code edit below is required. -->
<script>
    (function () {
        'use strict';

        // type => { css: [...], js: [...] }. Keys are BLOCK TYPES, spelled
        // exactly as SaaSPlatform\ThemeRuntime\BlockRegistry spells them, so
        // they match the data-tb-block-type the renderer already emits.
        var BLOCK_ASSETS = Object.freeze({});

        var island = document.getElementById('theme-asset-manifest')
            || document.querySelector('script[type="application/json"][data-tb-asset-manifest]');
        if (!island) { return; }

        var manifest;
        try { manifest = JSON.parse(island.textContent || '{}'); } catch (e) { return; }
        if (!manifest || typeof manifest !== 'object') { return; }

        // Authoritative "already on this page" set, unioned with what the
        // eager bridge above may have injected and with the tags the server
        // rendered directly. Reading the live DOM as well as the manifest is
        // what makes double-loading structurally impossible: a tag we are
        // about to add is checked against every one of these sources first.
        var loaded = Object.create(null);
        var remember = function (bucket, url) {
            if (typeof url === 'string' && url !== '') { loaded[bucket + '|' + url] = true; }
        };

        (Array.isArray(manifest.css) ? manifest.css : []).forEach(function (u) { remember('css', u); });
        (Array.isArray(manifest.js) ? manifest.js : []).forEach(function (u) { remember('js', u); });

        Array.prototype.forEach.call(
            document.querySelectorAll('link[rel="stylesheet"], link[rel="preload"], script[src]'),
            function (el) {
                remember(el.tagName === 'LINK' ? 'css' : 'js', el.getAttribute('href') || el.getAttribute('src'));
            }
        );

        var has = function (bucket, url) { return loaded[bucket + '|' + url] === true; };

        var injectCss = function (url) {
            if (has('css', url)) { return; }
            remember('css', url);
            var link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = url;
            // A late-arriving stylesheet still costs a reflow; preload tells
            // the browser to fetch it in parallel with the current work.
            var pre = document.createElement('link');
            pre.rel = 'preload';
            pre.as = 'style';
            pre.href = url;
            document.head.appendChild(pre);
            document.head.appendChild(link);
        };

        var injectJs = function (url) {
            if (has('js', url)) { return; }
            remember('js', url);
            var s = document.createElement('script');
            s.src = url;
            s.async = true;
            document.body.appendChild(s);
        };

         var hydrate = function (el) {
            var type = el.getAttribute('data-tb-block-type');
            if (!type) { return; }
            var spec = BLOCK_ASSETS[type];
            if (!spec) { return; }
            (Array.isArray(spec.css) ? spec.css : []).forEach(injectCss);
            var jsUrls = Array.isArray(spec.js) ? spec.js : [];
            // Phase 8 Step 2 — Lifecycle Engine: after assets are injected,
            // schedule init(ctx) via microtask so no block's init() blocks
            // another. ThemeCanvasRuntime.initBlock() reads props from
            // data-tb-block-props and dispatches to the registered handler.
            if (window.ThemeCanvasRuntime && typeof window.ThemeCanvasRuntime.initBlock === 'function') {
                // If JS was injected, wait for it to load before init;
                // otherwise init runs immediately via microtask (non-blocking).
                var pending = jsUrls.map(function (url) { return window.ThemeCanvasRuntime.trackScript(url); });
                Promise.all(pending).then(function () {
                    window.ThemeCanvasRuntime.initBlock(el);
                }).catch(function () {
                    // Even on script load failure, attempt init — fail-open
                    // to the block's own error handling, never silent skip.
                    window.ThemeCanvasRuntime.initBlock(el);
                });
            }
        };

        // rootMargin preloads just before the block is painted, so the asset
        // is usually in hand by the time the user sees it — the point of
        // viewport-triggered loading is to hide latency, not to expose it.
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) { return; }
                hydrate(entry.target);
                // Once satisfied, stop watching: assets are never unloaded,
                // and a re-entering block must not re-run the lookup.
                observer.unobserve(entry.target);
            });
        }, { rootMargin: '200px 0px' });

        var blocks = document.querySelectorAll('[data-tb-block-type]');
        Array.prototype.forEach.call(blocks, function (el) { observer.observe(el); });
    })();
    </script>
    @else
    @foreach(($themeAssets['js'] ?? []) as $__themeSrc)
    <script src="{{ $__themeSrc }}" defer></script>
    @endforeach
    @foreach(($extensionAssets['js'] ?? []) as $__extSrc)
    <script src="{{ $__extSrc }}" defer></script>
    @endforeach
    @endif
    @includeWhen(($themeRuntimeMode ?? null) === \App\Services\ThemeCanvas\ThemeRuntimeDocument::MODE_PUBLIC, 'partials.theme-public-runtime')
    @stack('scripts')

    {{-- ─── Builder Edit Bar (for admin users only) ─── --}}
    @auth
        @if(($showBuilderEditBar ?? true) && auth()->user()->isTenantAdmin())
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
    {{-- Global Scroll-to-Top Widget (when enabled via global styles or site settings) --}}
    @php
        $__hasSttBlock = str_contains($headerHtml ?? '', 'data-tb-scroll-to-top-wrap')
            || str_contains($footerHtml ?? '', 'data-tb-scroll-to-top-wrap')
            || str_contains($contentHtml ?? '', 'data-tb-scroll-to-top-wrap');
        $__globalStt = $globalStyles['scroll_to_top'] ?? \App\Models\Setting::get('scroll_to_top', []);
        if (is_string($__globalStt)) {
            $__globalStt = json_decode($__globalStt, true) ?: [];
        }
        $__globalSttEnabled = !empty($__globalStt['enabled']) || \App\Models\Setting::get('scroll_to_top_enabled', false);
    @endphp
    @if(!$__hasSttBlock && $__globalSttEnabled)
        {!! app(\App\Services\ThemeBuilder\BlockRenderer::class)->renderBlock([
            'type' => 'scroll_to_top',
            'id' => 'global-scroll-to-top',
            'props' => is_array($__globalStt) ? $__globalStt : [],
        ]) !!}
    @endif

<x-core-ui::payment-connection-warning />
</body>
</html>
