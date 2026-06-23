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

        $page = $this->findHomePage($themeData['pages'] ?? []);

        return $this->renderPage(
            themeData: $themeData,
            blocks: $page['blocks'] ?? [],
            title: $page['title'] ?? SiteHelper::siteName(),
        );
    }

    // ── Inner pages ( GET /page/{slug} ) ────────────────────────────────────

    public function show(string $slug)
    {
        $themeData = $this->loadThemeData();

        if (!$themeData) {
            return app(PageController::class)->show($slug);
        }

        $page = $this->findPage($themeData['pages'] ?? [], $slug);

        if (!$page) {
            // Canvas theme installed but this slug is a DB page — delegate
            return app(PageController::class)->show($slug);
        }

        return $this->renderPage(
            themeData: $themeData,
            blocks: $page['blocks'] ?? [],
            title: $page['title'] ?? ucfirst($slug),
        );
    }

    // ── Render ───────────────────────────────────────────────────────────────

    private function renderPage(array $themeData, array $blocks, string $title): \Illuminate\View\View
    {
        $this->prepareRenderer($themeData);

        $headerHtml  = $this->renderer->renderBlocks($themeData['header_blocks'] ?? []);
        $footerHtml  = $this->renderer->renderBlocks($themeData['footer_blocks'] ?? []);
        $contentHtml = $this->renderer->renderBlocks($blocks);

        return view('layouts.canvas-theme', [
            'headerHtml'  => $headerHtml,
            'footerHtml'  => $footerHtml,
            'contentHtml' => $contentHtml,
            'themeData'   => $themeData,
            'pageTitle'   => $title,
            'themeName'   => Setting::get('active_canvas_theme_name', ''),
            'globalStyles'=> $themeData['global_styles'] ?? [],
        ]);
    }

    private function prepareRenderer(array $themeData): void
    {
        $this->renderer->setThemeData($themeData);
        $this->renderer->setContext([
            'menu_html'   => $this->buildMenuHtml(),
            'site_name'   => SiteHelper::siteName(),
        ]);
    }

    private function buildMenuHtml(): string
    {
        $primaryMenu = $this->menuService->getMenuForLocation('primary');

        if ($primaryMenu) {
            return view('partials.site-menu', [
                'menu'    => $primaryMenu,
                'variant' => 'nav',
                'items'   => $this->menuService->buildMenuTree($primaryMenu),
            ])->render();
        }

        return view('partials.platform-menu', [
            'location' => 'header',
            'variant'  => 'nav',
        ])->render();
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
     */
    private function findHomePage(array $pages): array
    {
        foreach ($pages as $key => $page) {
            if (!is_array($page)) {
                continue;
            }
            // Explicit is_home flag
            if (!empty($page['is_home'])) {
                return $page;
            }
            // Slug-based: empty string or 'home'
            $slug = $page['slug'] ?? (is_string($key) ? $key : '');
            if ($slug === '' || $slug === 'home') {
                return $page;
            }
        }

        // Return first page if nothing matched
        $first = is_array($pages) ? reset($pages) : [];
        return is_array($first) ? $first : [];
    }

    /**
     * Find a specific page by slug.
     */
    private function findPage(array $pages, string $slug): ?array
    {
        foreach ($pages as $key => $page) {
            if (!is_array($page)) {
                continue;
            }
            $pageSlug = $page['slug'] ?? (is_string($key) ? $key : '');
            if ($pageSlug === $slug) {
                return $page;
            }
        }

        return null;
    }
}
