<?php

namespace App\Http\Controllers\Front;

use App\Helpers\Jalali;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageRevision;
use App\Models\PageTemplate;
use App\Services\ThemeBuilder\BlockRenderer;
use App\Services\ThemePageProvisionService;
use App\Support\BuilderPageKits;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * API and UI for the Visual Page Builder (frontend editing).
 * Admin-only; middleware applied in routes (auth, admin).
 */
class PageBuilderController extends Controller
{
    /**
     * Show the builder UI for a page (full-screen overlay editor).
     * Uses builder_content_v2 when present, else builder_content (v1).
     */
    public function edit(Page $page)
    {
        [$blockLabels, $blockSchemas, $byCategory] = $this->buildRegistryData();

        $raw = $page->builder_content_v2 ?? $page->builder_content ?? [];
        // Support flat {blocks:[...]} format alongside legacy {sections:[...]}
        if (isset($raw['blocks']) && is_array($raw['blocks'])) {
            $initialBlocks = $raw['blocks'];
        } elseif (isset($raw['sections']) && is_array($raw['sections'])) {
            // Flatten legacy hierarchical format to top-level sections
            $initialBlocks = $raw['sections'];
        } else {
            $initialBlocks = [];
        }

        return view('builder.edit', [
            'page'             => $page,
            'initialBlocks'    => $initialBlocks,
            'blockLabels'      => $blockLabels,
            'blockSchemas'     => $blockSchemas,
            'byCategory'       => $byCategory,
            'orderedCats'      => ['layout','content','marketing','interactive','dynamic','ecommerce','utility'],
            'catLabels'        => config('builder-elements.categories', []),
            'catIcons'         => config('builder-elements.category_icons', []),
            'catColors'        => config('builder-elements.category_colors', []),
            'previewUrl'       => route('builder.preview'),
            'saveUrl'          => route('builder.page.update', $page),
            'exitUrl'          => route('page.show', $page->slug),
            'pageTitle'        => $page->title,
            'pageId'           => $page->id,
            'pageSlug'         => $page->slug,
            'pagePublished'    => (bool) $page->is_published,
            'pageShowInMenu'   => (bool) $page->show_in_menu,
            'pageMetaTitle'    => $page->meta_title ?? '',
            'pageMetaDesc'     => $page->meta_description ?? '',
            'contentType'      => 'page',
            'adminUrl'         => route('admin.pages.edit', $page),
            'listPagesUrl'     => route('builder.api.pages'),
            'createPageUrl'    => route('builder.api.pages.create'),
            'pageSettingsUrl'  => route('builder.api.page.settings', $page),
            'uploadUrl'        => route('admin.image.upload'),
        ]);
    }

    /**
     * Phase 8 — Test environment: builder with fixture data and debug overlay.
     * Route: GET /builder/test (no DB page; save is disabled).
     */
    public function test()
    {
        [$blockLabels, $blockSchemas, $byCategory] = $this->buildRegistryData();
        return view('builder.edit', [
            'pageTitle'    => 'صفحه تست سازنده',
            'initialBlocks'=> [],
            'blockLabels'  => $blockLabels,
            'blockSchemas' => $blockSchemas,
            'byCategory'   => $byCategory,
            'catLabels'    => config('builder-elements.categories', []),
            'catIcons'     => config('builder-elements.category_icons', []),
            'catColors'    => config('builder-elements.category_colors', []),
            'previewUrl'   => route('builder.preview'),
            'saveUrl'      => '#',
            'exitUrl'      => route('admin.pages.index'),
        ]);
    }

    /**
     * Fixture content for /builder/test: one section, row, two columns, heading + text + button.
     */
    protected function getFixtureBuilderContent(): array
    {
        return [
            'sections' => [
                [
                    'id' => 'fixture_section_1',
                    'type' => 'section',
                    'props' => ['padding' => 'md', 'background' => 'transparent', 'container' => true],
                    'style' => ['layout' => [], 'typography' => [], 'background' => [], 'effects' => []],
                    'blocks' => [
                        [
                            'id' => 'fixture_row_1',
                            'type' => 'row',
                            'props' => ['gap' => 'md'],
                            'blocks' => [
                                [
                                    'id' => 'fixture_col_1',
                                    'type' => 'column',
                                    'props' => ['width' => '1/2'],
                                    'blocks' => [
                                        ['id' => 'fixture_heading_1', 'type' => 'heading', 'props' => ['text' => 'عنوان تست', 'level' => 2], 'style' => []],
                                        ['id' => 'fixture_text_1', 'type' => 'text', 'props' => ['content' => 'متن دمو برای محیط تست.'], 'style' => []],
                                    ],
                                ],
                                [
                                    'id' => 'fixture_col_2',
                                    'type' => 'column',
                                    'props' => ['width' => '1/2'],
                                    'blocks' => [
                                        ['id' => 'fixture_button_1', 'type' => 'button', 'props' => ['text' => 'دکمه تست', 'url' => '#'], 'style' => []],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * API: Get page data for builder (title, slug, builder_content_v2 / builder_content, element registry).
     */
    public function show(Page $page): JsonResponse
    {
        $elements = $this->getElementsForSite();
        $content = $page->builder_content_v2 ?? $page->builder_content ?? ['sections' => []];
        return response()->json([
            'page' => [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'builder_type' => $page->builder_type ?? Page::BUILDER_TYPE_LEGACY,
                'builder_content' => $content,
                'builder_content_v2' => $page->builder_content_v2,
            ],
            'elements' => $elements,
        ]);
    }

    /**
     * API: Save builder content (flat blocks format: {blocks:[...]}).
     * Automatically creates a revision before overwriting.
     */
    public function update(Request $request, Page $page): JsonResponse
    {
        $data = $request->validate([
            'blocks'   => 'required|array',
            'blocks.*.id'    => 'nullable|string',
            'blocks.*.type'  => 'nullable|string',
            'blocks.*.props' => 'sometimes|array',
        ]);

        // Save revision of previous content before overwriting
        $previousContent = $page->builder_content_v2;
        if (!empty($previousContent['blocks'] ?? [])) {
            PageRevision::create([
                'page_id'        => $page->id,
                'blocks_snapshot'=> $previousContent['blocks'],
                'saved_by'       => Auth::user()?->name ?? Auth::user()?->mobile ?? 'system',
                'label'          => 'ذخیره خودکار',
            ]);
            // Prune revisions older than 7 days for this page
            PageRevision::where('page_id', $page->id)
                ->where('created_at', '<', now()->subDays(7))
                ->delete();
        }

        $content = ['blocks' => $this->reconcileExtensionBlocks($data['blocks'], $previousContent['blocks'] ?? [])];
        $page->update([
            'builder_content_v2' => $content,
            'builder_type'       => Page::BUILDER_TYPE_BUILDER,
        ]);
        return response()->json(['success' => true]);
    }

    /**
     * Phase 3E-H-C — fail-closed trust boundary for ext:* blocks whose
     * extension is not currently registered (unavailable — provider not
     * loaded, or never legitimately registered at all).
     *
     * Every other block type (any non-`ext:`-prefixed type, and any
     * currently-registered ext:* type) passes through completely
     * unchanged — zero behavior change for existing content.
     *
     * For an unregistered ext:* block, the incoming (client-submitted)
     * copy is NEVER trusted as-is: the server reconciles against its own
     * previously-persisted content for this page.
     *   - If a block with the same id AND type already exists in the
     *     page's own last-saved content, it is preserved using the
     *     server's own last-known-good copy (so props/children the client
     *     can't validate can't be silently rewritten either).
     *   - Otherwise it is a brand-new client-asserted unavailable
     *     extension block, which is dropped from this save — the rest of
     *     the incoming blocks still save normally.
     *
     * @param array $incoming Blocks from this request.
     * @param array $previous Blocks currently persisted for this page (pre-overwrite).
     */
    protected function reconcileExtensionBlocks(array $incoming, array $previous): array
    {
        $previousById = [];
        $this->indexBlocksById($previous, $previousById);

        return $this->reconcileExtensionBlocksLevel($incoming, $previousById);
    }

    /** Recursively flattens a block tree (any nesting key: blocks/children) into id => block. */
    protected function indexBlocksById(array $blocks, array &$index): void
    {
        foreach ($blocks as $block) {
            if (!is_array($block)) {
                continue;
            }
            if (isset($block['id'])) {
                $index[$block['id']] = $block;
            }
            $children = $block['blocks'] ?? $block['children'] ?? [];
            if (is_array($children)) {
                $this->indexBlocksById($children, $index);
            }
        }
    }

    /** @param array<string, array> $previousById */
    protected function reconcileExtensionBlocksLevel(array $incoming, array $previousById): array
    {
        $result = [];
        foreach ($incoming as $block) {
            if (!is_array($block)) {
                continue;
            }
            $type = $block['type'] ?? '';

            if (str_starts_with($type, 'ext:') && !\App\Builder\ElementRegistry::has($type)) {
                $existing = $previousById[$block['id'] ?? null] ?? null;
                if ($existing === null || ($existing['type'] ?? null) !== $type) {
                    continue;
                }
                $result[] = $existing;
                continue;
            }

            if (isset($block['blocks']) && is_array($block['blocks'])) {
                $block['blocks'] = $this->reconcileExtensionBlocksLevel($block['blocks'], $previousById);
            }
            if (isset($block['children']) && is_array($block['children'])) {
                $block['children'] = $this->reconcileExtensionBlocksLevel($block['children'], $previousById);
            }
            $result[] = $block;
        }

        return $result;
    }

    /** List page revisions for the last 7 days. */
    public function listRevisions(Page $page): JsonResponse
    {
        $revisions = PageRevision::where('page_id', $page->id)
            ->where('created_at', '>=', now()->subDays(7))
            ->orderByDesc('created_at')
            ->get(['id', 'saved_by', 'label', 'created_at']);

        return response()->json([
            'revisions' => $revisions->map(fn ($r) => [
                'id'       => $r->id,
                'saved_by' => $r->saved_by,
                'label'    => $r->label,
                'time_ago' => Jalali::diffForHumansFa($r->created_at),
                'date'     => Jalali::formatFa($r->created_at, 'Y/m/d H:i'),
            ])->values(),
        ]);
    }

    /** Restore a revision: save current as new revision, then load the old one. */
    public function restoreRevision(Request $request, PageRevision $revision): JsonResponse
    {
        $page = $revision->page;

        // Save current as a revision first
        $current = $page->builder_content_v2;
        if (!empty($current['blocks'] ?? [])) {
            PageRevision::create([
                'page_id'        => $page->id,
                'blocks_snapshot'=> $current['blocks'],
                'saved_by'       => Auth::user()?->name ?? 'system',
                'label'          => 'قبل از بازیابی',
            ]);
        }

        // Restore
        $page->update([
            'builder_content_v2' => ['blocks' => $revision->blocks_snapshot],
            'builder_type'       => Page::BUILDER_TYPE_BUILDER,
        ]);

        return response()->json([
            'success' => true,
            'blocks'  => $revision->blocks_snapshot,
        ]);
    }

    /** Provision theme pages into the local pages table (called from saas-platform). */
    public function provisionThemePages(Request $request): JsonResponse
    {
        $data = $request->validate([
            'theme_id'    => 'required|string|max:100',
            'theme_name'  => 'required|string|max:255',
            'pages'       => 'required|array',
        ]);

        $service = app(ThemePageProvisionService::class);
        $result  = $service->provision($data['theme_id'], $data['theme_name'], $data['pages']);

        return response()->json([
            'success'  => true,
            'created'  => $result['created'],
            'skipped'  => $result['skipped'],
            'pages'    => $result['pages'] ?? [],
        ]);
    }

    /** Unprotect pages of a theme (called when theme is unassigned). */
    public function unprotectThemePages(Request $request): JsonResponse
    {
        $data = $request->validate(['theme_id' => 'required|string|max:100']);
        $count = app(ThemePageProvisionService::class)->unprotect($data['theme_id']);
        return response()->json(['success' => true, 'unprotected' => $count]);
    }

    /**
     * Return HTML preview for flat blocks array.
     * mode=builder → returns {block_htmls:[...]} for interactive canvas
     * Default        → returns {html:'...'} for legacy preview
     */
    public function preview(Request $request): JsonResponse
    {
        $blocks = $request->input('blocks');
        if (!is_array($blocks)) {
            $content = $request->input('builder_content_v2') ?? $request->input('builder_content', []);
            $blocks  = $content['blocks'] ?? $content['sections'] ?? [];
        }
        $blocks = is_array($blocks) ? $blocks : [];

        $renderer = app(BlockRenderer::class);
        $renderer->setContext(['menu_html' => '']);

        // Builder mode: return per-block HTML array for live iframe canvas
        if ($request->input('mode') === 'builder') {
            $blockHtmls = [];
            foreach ($blocks as $block) {
                $blockHtmls[] = $renderer->renderBlocks([$block]);
            }
            return response()->json(['block_htmls' => $blockHtmls]);
        }

        $testMode = $request->boolean('_test_mode');
        $start    = $testMode ? (microtime(true) * 1000) : 0;
        $html     = $renderer->renderBlocks($blocks);

        $payload = ['html' => $html, 'assets' => $renderer->getCollectedAssets()];
        if ($testMode) {
            $payload['render_time_ms']    = round((microtime(true) * 1000) - $start, 2);
            $payload['memory_peak_bytes'] = memory_get_peak_usage(true);
        }
        return response()->json($payload);
    }

    // ── Pro Builder APIs ──────────────────────────────────────────────────────

    /** List all pages (for the Pages panel in builder). */
    public function listPages(): JsonResponse
    {
        $pages = Page::orderBy('order')->orderBy('title')
            ->get(['id', 'title', 'slug', 'is_published', 'show_in_menu', 'updated_at']);

        return response()->json([
            'pages' => $pages->map(fn ($p) => [
                'id'           => $p->id,
                'title'        => $p->title,
                'slug'         => $p->slug,
                'is_published' => (bool) $p->is_published,
                'show_in_menu' => (bool) $p->show_in_menu,
                'updated_at'   => Jalali::diffForHumansFa($p->updated_at),
                'edit_url'     => route('builder.page.edit', $p),
                'admin_url'    => route('admin.pages.edit', $p),
                'view_url'     => route('page.show', $p->slug),
            ])->values(),
        ]);
    }

    /** Create a new page from within the builder. */
    public function createPage(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug'  => 'nullable|string|max:255|unique:pages,slug',
        ]);

        $slug = $data['slug'] ?? Str::slug($data['title']);
        // Ensure unique
        $base = $slug;
        $i = 1;
        while (Page::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        $page = Page::create([
            'title'        => $data['title'],
            'slug'         => $slug,
            'is_published' => false,
            'builder_type' => Page::BUILDER_TYPE_BUILDER,
            'builder_content_v2' => ['blocks' => []],
        ]);

        return response()->json([
            'success'  => true,
            'page'     => [
                'id'       => $page->id,
                'title'    => $page->title,
                'slug'     => $page->slug,
                'edit_url' => route('builder.page.edit', $page),
            ],
        ]);
    }

    /** Update page settings (title, slug, SEO, published, show_in_menu). */
    public function updatePageSettings(Request $request, Page $page): JsonResponse
    {
        $data = $request->validate([
            'title'            => 'sometimes|string|max:255',
            'slug'             => 'sometimes|string|max:255|unique:pages,slug,' . $page->id,
            'is_published'     => 'sometimes|boolean',
            'show_in_menu'     => 'sometimes|boolean',
            'meta_title'       => 'sometimes|nullable|string|max:255',
            'meta_description' => 'sometimes|nullable|string|max:500',
        ]);

        $page->update($data);

        return response()->json([
            'success' => true,
            'page'    => [
                'id'           => $page->id,
                'title'        => $page->title,
                'slug'         => $page->slug,
                'is_published' => (bool) $page->is_published,
                'show_in_menu' => (bool) $page->show_in_menu,
                'view_url'     => route('page.show', $page->slug),
            ],
        ]);
    }

    /** Delete a page from within the builder. */
    public function deletePage(Page $page): JsonResponse
    {
        $page->delete();
        return response()->json(['success' => true]);
    }

    /**
     * API: List header and footer templates (10 each) + user-saved templates.
     */
    public function templates(): JsonResponse
    {
        $headers = collect(config('builder-headers.templates', []))->map(fn ($t, $key) => [
            'id' => $key,
            'name' => $t['name'],
            'blocks' => $t['blocks'] ?? [],
        ])->values()->all();
        $footers = collect(config('builder-footers.templates', []))->map(fn ($t, $key) => [
            'id' => $key,
            'name' => $t['name'],
            'blocks' => $t['blocks'] ?? [],
        ])->values()->all();
        $saved = PageTemplate::orderByDesc('updated_at')->get(['id', 'name', 'type', 'content']);
        $sectionPresets = collect(config('builder-section-presets.presets', []))->map(fn ($p, $key) => [
            'id' => $key,
            'name' => $p['name'],
            'blocks' => $p['blocks'] ?? [],
        ])->values()->all();

        return response()->json([
            'headers' => $headers,
            'footers' => $footers,
            'saved' => $saved,
            'sectionPresets' => $sectionPresets,
            'pageKits' => BuilderPageKits::definitions(),
        ]);
    }

    /**
     * API: Save current builder content as a reusable template.
     */
    public function saveTemplate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:section,page',
            'content' => 'required|array',
        ]);
        $t = PageTemplate::create($data);
        return response()->json(['success' => true, 'template' => $t]);
    }

    /**
     * Build blockLabels, blockSchemas, byCategory from builder-elements config.
     * Returns [$blockLabels, $blockSchemas, $byCategory].
     */
    protected function buildRegistryData(): array
    {
        // Phase 3E-H-B: existing production config remains sole authority
        // for every existing type — `+` keeps the LEFT operand (config) on
        // any key collision, and by construction (3E-B) an `ext:*` key can
        // never collide with a bare production key anyway. Only registered
        // Extension SDK definitions (never canonical-adapter/host-only
        // built-ins — see ProductionBuilderAdapter's own docblock) are
        // added.
        $config  = config('builder-elements.elements', []) + \App\Builder\Extension\ProductionBuilderAdapter::palette();
        $enabled = $this->enabledModuleSlugs();
        $orderedCats = ['layout', 'content', 'marketing', 'interactive', 'dynamic', 'ecommerce', 'utility'];

        $blockLabels  = [];
        $blockSchemas = [];
        $byCategory   = array_fill_keys($orderedCats, []);

        foreach ($config as $type => $def) {
            $module = $def['required_module'] ?? null;
            if ($module !== null && !in_array($module, $enabled, true)) {
                continue;
            }
            $blockLabels[$type] = $def['name'];
            $blockSchemas[$type] = [
                'schema'       => $def['schema'] ?? [],
                'defaultProps' => $def['defaultProps'] ?? [],
            ];
            $cat = $def['category'] ?? 'content';
            if (!isset($byCategory[$cat])) {
                $byCategory[$cat] = [];
            }
            $byCategory[$cat][$type] = $def;
        }
        // Remove empty categories
        $byCategory = array_filter($byCategory);
        return [$blockLabels, $blockSchemas, $byCategory];
    }

    /**
     * Element registry filtered by enabled modules.
     */
    protected function getElementsForSite(): array
    {
        // Phase 3E-H-B: same extension-only, config-wins merge as buildRegistryData().
        $config = config('builder-elements.elements', []) + \App\Builder\Extension\ProductionBuilderAdapter::palette();
        $categories = config('builder-elements.categories', []);
        $enabledModules = $this->enabledModuleSlugs();

        $byCategory = [];
        foreach ($config as $type => $def) {
            $module = $def['required_module'] ?? null;
            if ($module !== null && !in_array($module, $enabledModules, true)) {
                continue;
            }
            $cat = $def['category'] ?? 'content';
            if (!isset($byCategory[$cat])) {
                $byCategory[$cat] = [];
            }
            $byCategory[$cat][] = [
                'type' => $type,
                'name' => $def['name'],
                'icon' => $def['icon'],
                'defaultProps' => $def['defaultProps'] ?? [],
            ];
        }

        return [
            'byCategory' => $byCategory,
            'categories' => $categories,
            'flat' => array_keys($config),
        ];
    }

    protected function enabledModuleSlugs(): array
    {
        $license = \App\Models\License::current();
        if ($license && $license->isValid() && is_array($license->modules)) {
            return $license->modules;
        }
        $modules = [];
        if (\App\Helpers\SiteHelper::blogEnabled()) {
            $modules[] = 'blog';
        }
        if (\App\Helpers\SiteHelper::shopEnabled()) {
            $modules[] = 'shop';
        }
        foreach (['forms', 'reviews', 'advertisements', 'advanced_search', 'menu', 'social_links', 'messaging', 'whatsapp', 'invoice', 'business_card', 'courses', 'subscription', 'referral'] as $m) {
            if (\App\Helpers\SiteHelper::moduleEnabled($m)) {
                $modules[] = $m;
            }
        }
        return $modules;
    }
}
