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
        $elements = $this->getElementsForSite();
        $content = $page->builder_content_v2 ?? $page->builder_content ?? ['sections' => []];
        if (!isset($content['sections']) || !is_array($content['sections'])) {
            $content = ['sections' => []];
        }
        return view('builder.edit', [
            'page' => $page,
            'elementsConfig' => $elements,
            'builderContent' => $content,
            'pageKits' => BuilderPageKits::definitions(),
            'useV2' => true,
            'testMode' => false,
        ]);
    }

    /**
     * Phase 8 — Test environment: builder with fixture data and debug overlay.
     * Route: GET /builder/test (no DB page; save is disabled).
     */
    public function test()
    {
        $elements = $this->getElementsForSite();
        $content = $this->getFixtureBuilderContent();
        $page = (object) [
            'id' => 0,
            'title' => 'صفحه تست سازنده',
            'slug' => 'test',
        ];
        return view('builder.edit', [
            'page' => $page,
            'elementsConfig' => $elements,
            'builderContent' => $content,
            'pageKits' => BuilderPageKits::definitions(),
            'useV2' => true,
            'testMode' => true,
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
     * API: Save builder content for the page.
     * Accepts builder_content_v2 (preferred) or builder_content; stores v2 when provided.
     */
    public function update(Request $request, Page $page): JsonResponse
    {
        $v2 = $request->input('builder_content_v2');
        if (is_array($v2)) {
            $request->validate([
                'builder_content_v2.sections' => 'array',
                'builder_content_v2.sections.*.id' => 'string',
                'builder_content_v2.sections.*.type' => 'string',
                'builder_content_v2.sections.*.props' => 'array',
                'builder_content_v2.sections.*.blocks' => 'array',
            ]);
            $content = $v2;
            if (!isset($content['sections']) || !is_array($content['sections'])) {
                $content['sections'] = [];
            }
            $page->update([
                'builder_content_v2' => $content,
                'builder_type' => Page::BUILDER_TYPE_BUILDER,
            ]);
            return response()->json(['success' => true, 'builder_content_v2' => $page->builder_content_v2]);
        }

        $data = $request->validate([
            'builder_content' => 'required|array',
            'builder_content.sections' => 'array',
            'builder_content.sections.*.id' => 'string',
            'builder_content.sections.*.type' => 'string',
            'builder_content.sections.*.props' => 'array',
            'builder_content.sections.*.blocks' => 'array',
        ]);
        $content = $data['builder_content'];
        if (empty($content['sections'])) {
            $content['sections'] = [];
        }
        $page->update([
            'builder_content' => $content,
            'builder_type' => Page::BUILDER_TYPE_BUILDER,
        ]);
        return response()->json(['success' => true, 'builder_content' => $page->builder_content]);
    }

    /**
     * Return HTML preview for the given builder_content or builder_content_v2 (for iframe or inline preview).
     * When _test_mode=1, response includes render_time_ms and memory_peak_bytes for debug overlay.
     */
    public function preview(Request $request): JsonResponse
    {
        $content = $request->input('builder_content_v2') ?? $request->input('builder_content', ['sections' => []]);
        $sections = $content['sections'] ?? [];
        if (!is_array($sections)) {
            $sections = [];
        }

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
