<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageTemplate;
use App\Services\ThemeBuilder\BlockRenderer;
use App\Support\BuilderPageKits;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            'page'         => $page,
            'initialBlocks'=> $initialBlocks,
            'blockLabels'  => $blockLabels,
            'blockSchemas' => $blockSchemas,
            'byCategory'   => $byCategory,
            'orderedCats'  => ['layout', 'content', 'marketing', 'dynamic', 'ecommerce', 'utility'],
            'catLabels'    => config('builder-elements.categories', []),
            'catIcons'     => config('builder-elements.category_icons', []),
            'catColors'    => config('builder-elements.category_colors', []),
            'previewUrl'   => route('builder.preview'),
            'saveUrl'      => route('builder.page.update', $page),
            'exitUrl'      => route('page.show', $page->slug),
            'pageTitle'    => $page->title,
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
     */
    public function update(Request $request, Page $page): JsonResponse
    {
        $data = $request->validate([
            'blocks'   => 'required|array',
            'blocks.*.id'    => 'string',
            'blocks.*.type'  => 'string',
            'blocks.*.props' => 'sometimes|array',
        ]);
        $content = ['blocks' => $data['blocks']];
        $page->update([
            'builder_content_v2' => $content,
            'builder_type'       => Page::BUILDER_TYPE_BUILDER,
        ]);
        return response()->json(['success' => true]);
    }

    /**
     * Return HTML preview for flat blocks array.
     * Accepts {blocks:[...]} (new) or legacy {sections:[...]} wrapper.
     */
    public function preview(Request $request): JsonResponse
    {
        // Accept both flat blocks and legacy sections
        $blocks = $request->input('blocks');
        if (!is_array($blocks)) {
            $content  = $request->input('builder_content_v2') ?? $request->input('builder_content', []);
            $blocks   = $content['blocks'] ?? $content['sections'] ?? [];
        }
        $sections = is_array($blocks) ? $blocks : [];

        $testMode = $request->boolean('_test_mode') || $request->input('_test_mode') === 1;
        $start = $testMode ? (microtime(true) * 1000) : 0;

        $renderer = app(BlockRenderer::class);
        $renderer->setContext([
            'menu_html' => '', // preview doesn't need menu
        ]);
        $html = $renderer->renderBlocks($sections);

        $payload = ['html' => $html];
        if ($testMode) {
            $payload['render_time_ms'] = round((microtime(true) * 1000) - $start, 2);
            $payload['memory_peak_bytes'] = memory_get_peak_usage(true);
        }

        return response()->json($payload);
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
        $config  = config('builder-elements.elements', []);
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
        $config = config('builder-elements.elements', []);
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
