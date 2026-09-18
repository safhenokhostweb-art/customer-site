<?php

namespace App\Http\Controllers\Front;

use App\Helpers\SiteHelper;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\MenuService;
use App\Services\ThemeCanvas\ThemeCanvasRenderer;
use Illuminate\Support\Facades\Cache;

/**
 * Serves public pages rendered by the Canvas Theme Engine.
 *
 * Reads active_canvas_theme_data from Setting, resolves the correct page's
 * block list, renders header + page content + footer, and returns the
 * canvas-theme layout (no admin sidebar).
 *
 * Falls back to the standard HomeController / FrontPageController when
 * no canvas theme is installed.
 */
class ThemeCanvasPageController extends Controller
{
    public function __construct(
        protected ThemeCanvasRenderer $renderer,
        protected MenuService $menuService,
    ) {}

    // ── Home page ( GET / ) ──────────────────────────────────────────────────

    public function index()
    {
        $themeData = $this->loadThemeData();

        if (!$themeData) {
            return app(HomeController::class)->index();
        }

        [$pageKey, $page] = $this->findHomePage($themeData['pages'] ?? []);

        return $this->renderPage(
            themeData: $themeData,
            blocks: $page['blocks'] ?? [],
            title: $page['title'] ?? SiteHelper::siteName(),
            pageKey: $pageKey,
        );
    }

    // ── Inner pages ( GET /page/{slug} ) ────────────────────────────────────

    public function show(string $slug)
    {
        $themeData = $this->loadThemeData();

        if (!$themeData) {
            return app(PageController::class)->show($slug);
        }

        [$pageKey, $page] = $this->findPage($themeData['pages'] ?? [], $slug);

        if (!$page) {
            // Canvas theme installed but this slug is a DB page — delegate
            return app(PageController::class)->show($slug);
        }

        return $this->renderPage(
            themeData: $themeData,
            blocks: $page['blocks'] ?? [],
            title: $page['title'] ?? ucfirst($slug),
            pageKey: $pageKey,
        );
    }

    // ── Render ───────────────────────────────────────────────────────────────

    /**
     * $pageKey is the canvas theme's own array key for the page actually
     * being rendered (e.g. 'home', 'ext-bridge') — every call into this
     * method is, by construction, rendering canvas-owned content (never a
     * Page-model row), so the admin edit toolbar (layouts.canvas-theme)
     * uses it to route to the one authoring surface that actually controls
     * what's on screen: Theme Design Canvas, never Page Builder.
     */
    private function renderPage(array $themeData, array $blocks, string $title, string $pageKey): \Illuminate\View\View
    {
        $this->prepareRenderer($themeData);

        // TASK 3/3 Phase 4: resolve through BuilderLayoutService's real
        // variant-selection logic (site-level default header/footer choice,
        // THEME-03's selectable header_variants/footer_variants) instead of
        // always rendering the theme's flat header_blocks/footer_blocks —
        // previously the only Theme-owned variants a Canvas-installed
        // theme ever had were persisted, never actually selectable on the
        // public site. $themeData is passed through explicitly (not left
        // to BuilderLayoutService's own Setting::get() fetch) so this stays
        // on the same short-TTL, install-visible-within-60s data source
        // loadThemeData() above already uses, not the 1-hour settings cache.
        $layoutService = app(\App\Services\BuilderLayoutService::class);
        $headerBlocks = $layoutService->getHeaderBlocks(null, $themeData);
        $footerBlocks = $layoutService->getFooterBlocks(null, $themeData);

        // TASK 3/3 Phase 6.5E (BLD-32) — one bulk media query for the whole
        // page (header + footer + content) instead of one query per
        // media-bearing block as renderBlocks() below discovers them.
        $this->renderer->preloadMediaFor($headerBlocks, $footerBlocks, $blocks);

        $headerHtml  = $this->renderer->renderBlocks($headerBlocks);
        $footerHtml  = $this->renderer->renderBlocks($footerBlocks);
        $contentHtml = $this->renderer->renderBlocks($blocks);

        // Phase 3F-C — HasAssets bridge for the real public Canvas render,
        // same pattern as themes/default/page.blade.php/home.blade.php
        // (ADR-041 §2): the renderer instance accumulates CSS/JS from every
        // extension block rendered across header+content+footer above,
        // deduplicated, request-scoped.
        $extensionAssets = $this->renderer->getCollectedAssets();

        return view('layouts.canvas-theme', [
            'headerHtml'  => $headerHtml,
            'footerHtml'  => $footerHtml,
            'contentHtml' => $contentHtml,
            'themeData'   => $themeData,
            'pageTitle'   => $title,
            'themeName'   => Setting::get('active_canvas_theme_name', ''),
            'globalStyles'=> $themeData['global_styles'] ?? [],
            'extensionAssets' => $extensionAssets,
            'canvasPageKey' => $pageKey,
        ]);
    }

    private function prepareRenderer(array $themeData): void
    {
        $this->renderer->setThemeData($themeData);
        $this->renderer->setContext([
            'menu_html'        => $this->menuService->renderMenuHtml('primary', admin: false),
            'footer_menu_html' => $this->menuService->renderMenuHtml('footer', admin: false),
            'site_name'        => SiteHelper::siteName(),
            // TASK 3/3 Phase 6.5C (BLD-25) — see BlockRenderer::renderNavHeader()'s
            // matching comment: the same <x-site-brand> logic every other
            // real render path already uses, not a second logo-resolution
            // implementation.
            'site_logo_html'   => view('components.site-brand', ['class' => 'tb-nav-header-logo'])->render(),
        ]);
    }

    // ── Theme data ────────────────────────────────────────────────────────────

    private function loadThemeData(): ?array
    {
        // Read active_canvas_theme_data directly from DB (bypasses the 1-hour settings
        // cache) so that a platform-admin install becomes visible within 60 seconds.
        $raw = Cache::remember('canvas_theme_data', 60, function () {
            return \Illuminate\Support\Facades\DB::table('settings')
                ->where('key', 'active_canvas_theme_data')
                ->value('value') ?? '';
        });

        if (!$raw) {
            return null;
        }

        $data = json_decode($raw, true);

        return is_array($data) ? $data : null;
    }

    // ── Page resolution ───────────────────────────────────────────────────────

    /**
     * Find the home page from the pages array.
     * Supports both indexed array (list) and associative array (key=slug) formats.
     *
     * @return array{0: string, 1: array} [pageKey, page] — the key is the
     *   canvas theme's own array key for this page (e.g. 'home'), needed so
     *   the admin edit toolbar can deep-link to the exact page being viewed.
     */
    private function findHomePage(array $pages): array
    {
        foreach ($pages as $key => $page) {
            if (!is_array($page)) {
                continue;
            }
            $stringKey = is_string($key) ? $key : (string) $key;
            // Explicit is_home flag
            if (!empty($page['is_home'])) {
                return [$stringKey, $page];
            }
            // Slug-based: empty string or 'home'
            $slug = $page['slug'] ?? $stringKey;
            if ($slug === '' || $slug === 'home') {
                return [$stringKey, $page];
            }
        }

        // Return first page if nothing matched
        foreach ($pages as $key => $page) {
            if (is_array($page)) {
                return [is_string($key) ? $key : (string) $key, $page];
            }
        }

        return ['', []];
    }

    /**
     * Find a specific page by slug.
     *
     * @return array{0: string, 1: ?array} [pageKey, page] — page is null
     *   when no canvas-owned page matches this slug (caller delegates to
     *   PageController).
     */
    private function findPage(array $pages, string $slug): array
    {
        foreach ($pages as $key => $page) {
            if (!is_array($page)) {
                continue;
            }
            $stringKey = is_string($key) ? $key : (string) $key;
            $pageSlug = $page['slug'] ?? $stringKey;
            if ($pageSlug === $slug) {
                return [$stringKey, $page];
            }
        }

        return ['', null];
    }
}
