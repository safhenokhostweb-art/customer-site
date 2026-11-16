<?php

namespace App\Http\Controllers\Front;

use App\Helpers\SiteHelper;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\Seo\HeaderIntegrityService;
use App\Services\SeoService;
use App\Services\ThemeCanvas\CanvasStructuredData;
use App\Services\ThemeCanvas\ThemeRuntimeDocument;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

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
        protected ThemeRuntimeDocument $runtimeDocument,
        protected SeoService $seo,
        protected CanvasStructuredData $structuredData,
        protected HeaderIntegrityService $headerIntegrity,
    ) {}

    // ── Home page ( GET / ) ──────────────────────────────────────────────────

    public function index()
    {
        $themeData = $this->loadThemeData();

        if (! $themeData) {
            return app(HomeController::class)->index();
        }

        [$pageKey, $page] = $this->findHomePage($themeData['pages'] ?? []);

        return $this->renderPage(
            themeData: $themeData,
            page: $page,
            pageKey: $pageKey,
            fallbackTitle: SiteHelper::siteName(),
        );
    }

    // ── Inner pages ( GET /page/{slug} ) ────────────────────────────────────

    public function show(string $slug)
    {
        $themeData = $this->loadThemeData();

        if (! $themeData) {
            return app(PageController::class)->show($slug);
        }

        [$pageKey, $page] = $this->findPage($themeData['pages'] ?? [], $slug);
        // The DB page whose content is rendered below, if any: its own
        // header/footer overrides and disable flags must still apply, as they
        // did when such a page was delegated to PageController.
        $layoutPage = null;

        if (! $page || empty($page['blocks'])) {
            $dbPage = \App\Models\Page::where('slug', $slug)->published()->first();
            if ($dbPage) {
                $blocks = $dbPage->builder_content_v2['blocks']
                    ?? $dbPage->builder_content_v2['sections']
                    ?? $dbPage->builder_content['blocks']
                    ?? $dbPage->builder_content['sections']
                    ?? [];
                if (empty($blocks) && trim((string) $dbPage->body) !== '') {
                    $blocks = [[
                        'id' => 'db_page_' . $dbPage->id,
                        'type' => 'text',
                        'props' => ['content' => $dbPage->body],
                    ]];
                }
                if (!empty($blocks)) {
                    $page = [
                        'title' => $dbPage->title,
                        'slug' => $dbPage->slug,
                        'blocks' => $blocks,
                    ];
                    $pageKey = $slug;
                    $layoutPage = $dbPage;
                }
            }
        }

        if (! $page || empty($page['blocks'])) {
            $dbPage = \App\Models\Page::where('slug', $slug)->published()->first();
            if ($dbPage) {
                $blocks = $dbPage->builder_content_v2['blocks']
                    ?? $dbPage->builder_content_v2['sections']
                    ?? $dbPage->builder_content['blocks']
                    ?? $dbPage->builder_content['sections']
                    ?? [];
                if (empty($blocks) && trim((string) $dbPage->body) !== '') {
                    $blocks = [[
                        'id' => 'db_page_' . $dbPage->id,
                        'type' => 'text',
                        'props' => ['content' => $dbPage->body],
                    ]];
                }
                if (!empty($blocks)) {
                    $page = [
                        'title' => $dbPage->title,
                        'slug' => $dbPage->slug,
                        'blocks' => $blocks,
                    ];
                    $pageKey = $slug;
                }
            }
        }

        if (! $page) {
            // Canvas theme installed but this slug is a DB page without builder — delegate
            return app(PageController::class)->show($slug);
        }

        return $this->renderPage(
            themeData: $themeData,
            page: $page,
            pageKey: $pageKey,
            fallbackTitle: ucfirst($slug),
            layoutPage: $layoutPage,
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
     *
     * The WHOLE canvas page array is taken (not just its blocks/title) so
     * SeoService::canvasPage() can read the page's own SEO overrides and
     * derive a per-page description/image — the Canvas page has no
     * Eloquent model, so this array is the only per-page SEO input that
     * exists for it.
     */
    private function renderPage(array $themeData, array $page, string $pageKey, string $fallbackTitle = '', ?\App\Models\Page $layoutPage = null): View
    {
        $blocks = $page['blocks'] ?? [];
        $blocks = is_array($blocks) ? $blocks : [];
        $title  = $page['title'] ?? $fallbackTitle;
        $title  = is_string($title) ? $title : $fallbackTitle;

        $document = $this->runtimeDocument
            ->forMode(ThemeRuntimeDocument::MODE_PUBLIC)
            ->build(
            $themeData,
            $blocks,
            $title,
            $pageKey,
            layoutPage: $layoutPage,
            );

        $seoMeta = $this->seo->canvasPage($page, $blocks, $fallbackTitle);

        // SEO audit Phase 4.1 (detection) + 4.2 (H1 safety net): ONE DOM
        // parse of the public Canvas render drives both the log entry and the
        // fallback flag, so the audit and the markup can never disagree.
        // The editor's preview/iframe path deliberately does not come through
        // here, so authoring never logs or blocks on a half-finished page.
        $h1 = $this->auditHeaderIntegrity($document);

        return view('layouts.canvas-theme', $document + [
            'themeName' => Setting::get('active_canvas_theme_name', ''),
            'seoMeta'   => $seoMeta,
            // SEO audit C-1: page-level structured data for the public Canvas
            // render only. App\Helpers\JsonLd remains the single schema
            // authority; this only decides WHICH page-level graphs the page's
            // own real data supports. An empty list renders exactly as
            // before (the <x-seo-meta> schema loop emits nothing).
            'schema'    => $this->structuredData->for(
                $blocks,
                $seoMeta,
                $document['documentLang'] ?? null,
            ),
            // Phase 4.2: inject the hidden H1 only when the page has none at
            // all. Two or more is a logged authoring problem, not something
            // to paper over.
            'needsH1Fallback' => $h1['h1_count'] === 0,
        ]);

        /*

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
        $layoutService = app(BuilderLayoutService::class);
        $headerBlocks = $layoutService->getHeaderBlocks(null, $themeData);
        $footerBlocks = $layoutService->getFooterBlocks(null, $themeData);

        // TASK 3/3 Phase 6.5E (BLD-32) — one bulk media query for the whole
        // page (header + footer + content) instead of one query per
        // media-bearing block as renderBlocks() below discovers them.
        $this->renderer->preloadMediaFor($headerBlocks, $footerBlocks, $blocks);

        $headerHtml = $this->renderer->renderBlocks($headerBlocks);
        $footerHtml = $this->renderer->renderBlocks($footerBlocks);
        $contentHtml = $this->renderer->renderBlocks($blocks);

        // Phase 3F-C — HasAssets bridge for the real public Canvas render,
        // same pattern as themes/default/page.blade.php/home.blade.php
        // (ADR-041 §2): the renderer instance accumulates CSS/JS from every
        // extension block rendered across header+content+footer above,
        // deduplicated, request-scoped.
        $extensionAssets = $this->renderer->getCollectedAssets();

        $runtime = $this->resolveThemeRuntimeCapabilities($themeData);
        $themeAssets = $this->assetLocalizer->runtimeAssets($themeData['theme_assets'] ?? []);

        // Phase 3: theme markup may reference declared manifest file assets by
        // their manifest-relative path (e.g. src="images/logo.png", inline
        // url('images/x.png')). Resolve ONLY those exact declared paths to
        // their customer-local storage URLs. This is not a general URL
        // rewriter: a candidate must exactly equal a manifest-declared path,
        // and the map only ever contains integrity-verified, customer-local
        // entries. Undeclared references stay untouched (fail-safe, never
        // invented).
        if (($themeAssets['files'] ?? []) !== []) {
            $rewrite = function (string $html) use ($themeAssets): string {
                $files = $themeAssets['files'];
                $resolve = function (string $ref) use ($files): ?string {
                    $candidate = trim(strip_tags($ref));
                    return $files[$candidate] ?? null;
                };
                $html = preg_replace_callback(
                    '/src="([^"]+)"/',
                    fn (array $m) => ($url = $resolve($m[1])) !== null ? 'src="'.$url.'"' : $m[0],
                    $html
                ) ?? $html;
                $html = preg_replace_callback(
                    '/url\(([\'"]?)([^\'")]+)\1\)/',
                    fn (array $m) => ($url = $resolve($m[2])) !== null ? 'url('.$m[1].$url.$m[1].')' : $m[0],
                    $html
                ) ?? $html;

                return $html;
            };
            $headerHtml = $rewrite($headerHtml);
            $footerHtml = $rewrite($footerHtml);
            $contentHtml = $rewrite($contentHtml);
        }

        return view('layouts.canvas-theme', [
            'headerHtml' => $headerHtml,
            'footerHtml' => $footerHtml,
            'contentHtml' => $contentHtml,
            'themeData' => $themeData,
            'pageTitle' => $title,
            'themeName' => Setting::get('active_canvas_theme_name', ''),
            'globalStyles' => $themeData['global_styles'] ?? [],
            'extensionAssets' => $extensionAssets,
            'themeAssets' => $themeAssets,
            'canvasPageKey' => $pageKey,
            'chromeOwner' => $runtime['chromeOwner'],
            'documentDirection' => $runtime['documentDirection'],
            'documentLang' => $runtime['documentLang'],
            'platformPresentationCss' => $runtime['platformPresentationCss'],
        ]);
        */
    }

    /**
     * Generic, opt-in Theme runtime capabilities — reusable by any
     * installed theme, never named after a specific one. Absent/invalid
     * values always resolve to the platform's current, pre-existing
     * behavior, so no existing theme (Universal Multipurpose, Khab
     * Shimana, or any other already-installed theme) changes unless it
     * explicitly opts in via its own `global_styles`.
     *
     * Both keys deliberately live under `global_styles`, not `settings`:
     * the real install boundary (PageBuilderController::installTheme()'s
     * `$request->validate([...])`) only whitelists a fixed set of
     * top-level keys — `global_styles` is one of them, `settings` is not
     * (any `settings` sent to the real install endpoint is silently
     * stripped before it ever reaches ThemeInstanceRevisionService or
     * `active_canvas_theme_data`, confirmed by direct testing against the
     * real endpoint). `global_styles` is therefore the only currently
     * data-flow-verified place a theme's own install payload can actually
     * carry either capability through to this controller. Widening the
     * install whitelist to also accept `settings` would be a third
     * production file (PageBuilderController.php) beyond the two
     * authorized for this phase, so that is intentionally NOT done here.
     *
     * - document direction ('global_styles.direction') is an ALREADY
     *   real, already-populated field — every theme-import adapter and
     *   AI generator on the saas-platform side already writes
     *   'rtl' into it (confirmed: ThemeAiGeneratorService,
     *   ReverseThemeReconstructorService, every JsonImport/Adapters/*),
     *   but nothing on the customer-site render path ever read it until
     *   now. Reusing it (rather than inventing a second, competing
     *   "direction" concept) is a strict allowlist: only the literal
     *   strings 'ltr' or 'rtl' are ever accepted; anything else
     *   (missing, malformed, or an attempted attribute-injection payload)
     *   safely falls back to 'rtl', the platform's current default,
     *   before the value ever reaches the Blade view — never a
     *   Blade-escaping-only defense.
     *
     * - chrome ownership ('global_styles.chrome_owner', new, following the
     *   same flat-key-under-a-JSON-bucket shape as 'direction' right next
     *   to it): 'theme' means the theme's own rendered header/footer HTML
     *   (still $headerHtml/$footerHtml, rendered through the SAME
     *   BlockRenderer pipeline as always — no second rendering engine) is
     *   emitted bare, with neither the platform's wrapper chrome nor its
     *   fallback chrome. Any value other than the literal string 'theme'
     *   — including absence — means 'platform', today's exact behavior.
     */
    private function resolveThemeRuntimeCapabilities(array $themeData): array
    {
        $globalStyles = $themeData['global_styles'] ?? [];
        $globalStyles = is_array($globalStyles) ? $globalStyles : [];

        $chromeOwner = ($globalStyles['chrome_owner'] ?? null) === 'theme' ? 'theme' : 'platform';
        $platformPresentationCss = ($globalStyles['presentation_css_owner'] ?? null) === 'theme' ? false : true;

        $rawDirection = $globalStyles['direction'] ?? null;
        $documentDirection = in_array($rawDirection, ['ltr', 'rtl'], true) ? $rawDirection : 'rtl';
        $documentLang = $documentDirection === 'ltr' ? 'en' : 'fa';

        return [
            'chromeOwner' => $chromeOwner,
            'documentDirection' => $documentDirection,
            'documentLang' => $documentLang,
            'platformPresentationCss' => $platformPresentationCss,
        ];
    }

    private function prepareRenderer(array $themeData): void
    {
        $this->renderer->setThemeData($themeData);
        $this->renderer->setContext([
            'menu_html' => $this->menuService->renderMenuHtml('primary', admin: false),
            'footer_menu_html' => $this->menuService->renderMenuHtml('footer', admin: false),
            'site_name' => SiteHelper::siteName(),
            // TASK 3/3 Phase 6.5C (BLD-25) — see BlockRenderer::renderNavHeader()'s
            // matching comment: the same <x-site-brand> logic every other
            // real render path already uses, not a second logo-resolution
            // implementation.
            'site_logo_html' => view('components.site-brand', ['class' => 'tb-nav-header-logo'])->render(),
        ]);
    }

    // ── Theme data ────────────────────────────────────────────────────────────

    private function loadThemeData(): ?array
    {
        // Read active_canvas_theme_data directly from DB (bypasses the 1-hour settings
        // cache) so that a platform-admin install becomes visible within 60 seconds.
        $raw = Cache::remember('canvas_theme_data', 60, function () {
            return DB::table('settings')
                ->where('key', 'active_canvas_theme_data')
                ->value('value') ?? '';
        });

        if (! $raw) {
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
     *                                    canvas theme's own array key for this page (e.g. 'home'), needed so
     *                                    the admin edit toolbar can deep-link to the exact page being viewed.
     */
    private function findHomePage(array $pages): array
    {
        foreach ($pages as $key => $page) {
            if (! is_array($page)) {
                continue;
            }
            $stringKey = is_string($key) ? $key : (string) $key;
            // Explicit is_home flag
            if (! empty($page['is_home'])) {
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
     * SEO audit Phase 4.1 — log an H1 integrity violation without ever
     * changing the page — and return the report for Phase 4.2's fallback.
     *
     * Operates on the document's `contentHtml`, i.e. what the AUTHOR actually
     * placed on the page. That is deliberately not the final document: the
     * Canvas layout injects its own visually-hidden `<h1>` as an
     * accessibility fallback when content has none, so auditing the final
     * markup would report "0 H1" as a permanent non-issue and hide the real
     * authoring problem. The fallback keeps the page accessible; this reports
     * the truth about the content.
     *
     * Never throws: a detection audit must not be able to take a page down.
     *
     * @return array{has_h1: bool, h1_count: int, is_valid: bool, violation: string}
     */
    private function auditHeaderIntegrity(array $document): array
    {
        try {
            $report = $this->headerIntegrity->inspect((string) ($document['contentHtml'] ?? ''));
        } catch (\Throwable $e) {
            Log::warning('Canvas H1 integrity audit failed', [
                'page_id'  => $document['canvasPageKey'] ?? null,
                'message'  => $e->getMessage(),
            ]);

            // Treated as "no H1" so the safety net still fires, which is what
            // the pre-4.2 inline fallback did when its own check saw no
            // literal "<h1". Never let a detection failure cost a page its H1.
            return ['has_h1' => false, 'h1_count' => 0, 'is_valid' => false, 'violation' => HeaderIntegrityService::VIOLATION_MISSING];
        }

        if (! $report['is_valid']) {
            Log::warning($report['violation'] === HeaderIntegrityService::VIOLATION_MULTIPLE
                ? 'Canvas H1 integrity violation: multiple H1 tags detected'
                : 'Canvas H1 integrity violation: missing H1 tag', [
                    'page_id'  => $document['canvasPageKey'] ?? null,
                    'h1_count' => $report['h1_count'],
                    'violation'=> $report['violation'],
                ]);
        }

        return $report;
    }

    /**
     * Find a specific page by slug.
     *
     * @return array{0: string, 1: ?array} [pageKey, page] — page is null
     *                                     when no canvas-owned page matches this slug (caller delegates to
     *                                     PageController).
     */
    private function findPage(array $pages, string $slug): array
    {
        foreach ($pages as $key => $page) {
            if (! is_array($page)) {
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
