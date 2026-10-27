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
            font-size: var(--tb-body-size, 16px);
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

        {{-- Phase 0.3 (ADR-063): every block's visual rules now live in the
             shared package stylesheet, consolidated from this file — the
             platform Canvas preview includes the same one, so the editor
             preview and the live page can no longer drift. Only this host's
             own chrome stays here (above and below). --}}
        @include('theme-runtime::css.blocks')

        /* skip link */
        .canvas-skip-link { position: fixed; top: -5rem; right: 1rem; z-index: 999; padding: 0.5rem 1rem; background: #1f2937; color: #fff; border-radius: 0.5rem; text-decoration: none; }
        .canvas-skip-link:focus { top: 1rem; }
        *:focus-visible { outline: 2px solid var(--tb-primary, #4f46e5); outline-offset: 2px; }

        /* اعلان‌ها */
        .tb-notifications-bar { margin: 0; }

        /* ─── NEXUS PRO: Skills bar animation on scroll ─────────────── */
        {{-- Host-only: paired with this host's IntersectionObserver script
             in layouts/canvas-theme.blade.php. Without that script the bars
             would stay at width 0, so the shared stylesheet must not carry it. --}}
        .tb-skill-bar-fill[data-width] { width:0 !important; }
        .tb-skill-bar-fill.animated { width:var(--skill-w) !important; }

        {{-- SEO Phase 4.2 — the visually-hidden H1 fallback.
             layouts/canvas-theme.blade.php emits <h1 class="visually-hidden">
             when Canvas content carries no <h1> of its own, so every public
             Canvas page has exactly one H1 without forcing the author to
             change their design.

             This rule MUST live in this shared contract (not inline on the
             element) for two reasons: the editor's live-preview iframe
             includes this same partial, and an inline-style H1 silently
             becomes VISIBLE the moment the class is referenced without a
             matching rule anywhere. Kept in the Bootstrap/Tailwind sr-only
             idiom, plus clip-path and white-space for browsers that ignore
             `clip`. --}}
        .visually-hidden {
            position:absolute !important;
            width:1px !important;
            height:1px !important;
            padding:0 !important;
            margin:-1px !important;
            overflow:hidden !important;
            clip:rect(0,0,0,0) !important;
            clip-path:inset(50%) !important;
            white-space:nowrap !important;
            border:0 !important;
        }
