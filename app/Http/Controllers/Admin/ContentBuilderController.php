<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Product;
use App\Services\ThemeBuilder\BlockRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Visual block builder for content items (posts, products).
 * Admin-only; middleware applied in routes.
 */
class ContentBuilderController extends Controller
{
    // ── Posts ─────────────────────────────────────────────────────────────

    public function editPost(Post $post)
    {
        [$blockLabels, $blockSchemas, $byCategory] = $this->registryData();
        $initialBlocks = $this->normalizeBlocks($post->builder_content);

        return view('builder.edit', [
            'pageTitle'      => $post->title,
            'initialBlocks'  => $initialBlocks,
            'blockLabels'    => $blockLabels,
            'blockSchemas'   => $blockSchemas,
            'byCategory'     => $byCategory,
            'orderedCats'    => ['layout','content','marketing','interactive','dynamic','ecommerce','utility'],
            'catLabels'      => config('builder-elements.categories', []),
            'catIcons'       => config('builder-elements.category_icons', []),
            'catColors'      => config('builder-elements.category_colors', []),
            'previewUrl'     => route('builder.preview'),
            'saveUrl'        => route('admin.builder.post.save', $post),
            'exitUrl'        => route('admin.posts.edit', $post),
            'contentType'    => 'post',
            'pageId'         => 0,
            'pageSlug'       => '',
            'pagePublished'  => false,
            'pageShowInMenu' => false,
            'pageMetaTitle'  => '',
            'pageMetaDesc'   => '',
            'adminUrl'       => route('admin.posts.edit', $post),
            'listPagesUrl'   => '#',
            'createPageUrl'  => '#',
            'pageSettingsUrl'=> '#',
            'uploadUrl'      => route('admin.image.upload'),
        ]);
    }

    public function savePost(Request $request, Post $post): JsonResponse
    {
        $data = $request->validate(['blocks' => 'required|array']);
        $post->update(['builder_content' => ['blocks' => $data['blocks']]]);
        return response()->json(['success' => true]);
    }

    // ── Products ──────────────────────────────────────────────────────────

    public function editProduct(Product $product)
    {
        [$blockLabels, $blockSchemas, $byCategory] = $this->registryData();
        $initialBlocks = $this->normalizeBlocks($product->builder_content);

        return view('builder.edit', [
            'pageTitle'      => $product->name,
            'initialBlocks'  => $initialBlocks,
            'blockLabels'    => $blockLabels,
            'blockSchemas'   => $blockSchemas,
            'byCategory'     => $byCategory,
            'orderedCats'    => ['layout','content','marketing','interactive','dynamic','ecommerce','utility'],
            'catLabels'      => config('builder-elements.categories', []),
            'catIcons'       => config('builder-elements.category_icons', []),
            'catColors'      => config('builder-elements.category_colors', []),
            'previewUrl'     => route('builder.preview'),
            'saveUrl'        => route('admin.builder.product.save', $product),
            'exitUrl'        => route('admin.products.edit', $product),
            'contentType'    => 'product',
            'pageId'         => 0,
            'pageSlug'       => '',
            'pagePublished'  => false,
            'pageShowInMenu' => false,
            'pageMetaTitle'  => '',
            'pageMetaDesc'   => '',
            'adminUrl'       => route('admin.products.edit', $product),
            'listPagesUrl'   => '#',
            'createPageUrl'  => '#',
            'pageSettingsUrl'=> '#',
            'uploadUrl'      => route('admin.image.upload'),
        ]);
    }

    public function saveProduct(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate(['blocks' => 'required|array']);
        $product->update(['builder_content' => ['blocks' => $data['blocks']]]);
        return response()->json(['success' => true]);
    }

    // ── Preview (shared) ──────────────────────────────────────────────────

    public function preview(Request $request): JsonResponse
    {
        $blocks = $request->input('blocks', []);
        if (!is_array($blocks)) {
            $blocks = [];
        }
        $renderer = app(BlockRenderer::class);
        // TASK 3/3 Phase 6.5B — was hardcoded to '' (empty), so a nav_menu/
        // nav_header(menu-sourced) block always previewed with no nav
        // content at all, even when a real primary Menu exists. Now uses
        // the same canonical MenuService::renderMenuHtml() the real public
        // render uses (admin: true — a preview should reflect the current
        // draft Menu, not require it to already be published).
        $menuSvc = app(\App\Services\MenuService::class);
        $renderer->setContext([
            'menu_html' => $menuSvc->renderMenuHtml('primary', admin: true),
            'footer_menu_html' => $menuSvc->renderMenuHtml('footer', admin: true),
            'site_logo_html' => view('components.site-brand', ['class' => 'tb-nav-header-logo'])->render(),
        ]);
        $html = $renderer->renderBlocks($blocks);
        return response()->json(['html' => $html]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    protected function normalizeBlocks(?array $content): array
    {
        if (empty($content)) {
            return [];
        }
        // Support {blocks:[...]} format
        if (isset($content['blocks']) && is_array($content['blocks'])) {
            return $content['blocks'];
        }
        return [];
    }

    protected function registryData(): array
    {
        $config  = config('builder-elements.elements', []);
        $orderedCats = ['layout', 'content', 'marketing', 'interactive', 'dynamic', 'ecommerce', 'utility'];
        $enabled = $this->enabledModuleSlugs();

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
        $byCategory = array_filter($byCategory);
        return [$blockLabels, $blockSchemas, $byCategory];
    }

    protected function enabledModuleSlugs(): array
    {
        $modules = ['blog', 'shop', 'reviews', 'forms', 'courses', 'subscription', 'referral'];
        $enabled = [];
        foreach ($modules as $m) {
            if (\App\Helpers\SiteHelper::moduleEnabled($m)) {
                $enabled[] = $m;
            }
        }
        return $enabled;
    }
}
