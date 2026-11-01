<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\CustomerMedia;
use App\Models\Page;
use App\Services\CustomerMediaService;
use App\Services\ThemeBuilder\BlockRenderer;
use App\Services\ThemeCanvas\BlockRegistry;
use App\Services\ThemeCanvas\CustomerMediaResolver;
use App\Services\ThemeCanvas\SchemaFieldMap;
use App\Services\ThemeCanvas\ThemeCanvasRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Canvas overhaul Unit 2a/2b/2d: every `image` control of the registry, at
 * any repeater depth, resolves a Media Library pick on every host render
 * path.
 *
 * Before: CustomerMediaResolver kept hand lists that named bento_grid's
 * field `image` (the registry field is `bg_image`) and missed 17 of 53
 * image fields; and only ThemeCanvasRenderer resolved at all, so the
 * default theme layout rendered nav_header with a raw {media_id} mega
 * banner. Both crashed with "Array to string conversion" in production
 * (2026-10-30/31).
 */
class MediaFieldResolutionSweepTest extends TestCase
{
    use RefreshDatabase;

    private CustomerMedia $media;

    private string $url;

    protected function setUp(): void
    {
        parent::setUp();
        config(['site.platform_site_id' => 1]);
        $this->media = CustomerMedia::create([
            'site_id' => 1, 'uuid' => (string) Str::uuid(), 'disk' => 'public',
            'storage_key' => 'customer-media/1/sweep.webp', 'original_filename' => 'sweep.webp',
            'extension' => 'webp', 'category' => 'image', 'mime_type' => 'image/webp', 'size_bytes' => 100,
        ]);
        $this->url = app(CustomerMediaService::class)->publicUrl($this->media);
    }

    /** @return list<array{type:string, path:list<string>}> */
    private function targets(): array
    {
        $targets = [];
        foreach (array_keys(BlockRegistry::all()) as $type) {
            foreach (SchemaFieldMap::paths((string) $type, CustomerMediaResolver::CONTROL_TYPE) as $path) {
                $targets[] = ['type' => (string) $type, 'path' => $path];
            }
        }
        return $targets;
    }

    private function label(array $target): string
    {
        return $target['type'] . ':' . str_replace('.*', '[]', implode('.', $target['path']));
    }

    /** Default props with $value at the path; `*` targets the first item of each repeater. */
    private function place(array $node, array $path, mixed $value): array
    {
        $key = array_shift($path);
        if ($key === '*') {
            $items = array_values($node);
            $items[0] = $this->place(is_array($items[0] ?? null) ? $items[0] : [], $path, $value);
            return $items;
        }
        if ($path === []) {
            $node[$key] = $value;
            return $node;
        }
        $raw = $node[$key] ?? [];
        $wasJson = is_string($raw);
        $child = $wasJson ? (json_decode($raw, true) ?: []) : (is_array($raw) ? $raw : []);
        $child = $this->place($child, $path, $value);
        $node[$key] = $wasJson ? json_encode($child, JSON_UNESCAPED_UNICODE) : $child;
        return $node;
    }

    private function valueAt(mixed $node, array $path): mixed
    {
        foreach ($path as $segment) {
            if (is_string($node)) {
                $node = json_decode($node, true);
            }
            if (!is_array($node)) {
                return null;
            }
            $node = $segment === '*' ? (array_values($node)[0] ?? null) : ($node[$segment] ?? null);
        }
        return $node;
    }

    private function block(array $target, mixed $value): array
    {
        return ['id' => 'sweep', 'type' => $target['type'], 'props' => $this->place(BlockRegistry::defaultProps($target['type']), $target['path'], $value)];
    }

    public function test_the_inventory_covers_the_fields_the_old_lists_missed(): void
    {
        $labels = array_map(fn ($t) => $this->label($t), $this->targets());

        // bento_grid has two: the card picture (`image`, rendered but never
        // editable before Unit 2a) and the background (`bg_image`, which the
        // old list misnamed `image`).
        foreach (['bento_grid:items[].bg_image', 'bento_grid:items[].image', 'nav_header:nav_links[].mega_banner_image', 'nav_header:nav_links[].mega_columns[].items[].image', 'image_box:url', 'foodmart_header:logo_image', 'foodmart_blog:items[].image'] as $expected) {
            $this->assertContains($expected, $labels);
        }
    }

    public function test_every_image_field_resolves_a_media_library_pick(): void
    {
        $resolver = app(CustomerMediaResolver::class);
        $unresolved = [];
        foreach ($this->targets() as $target) {
            $block = $this->block($target, ['media_id' => $this->media->uuid]);
            $resolved = $this->valueAt($resolver->resolveBlockProps($target['type'], $block['props']), $target['path']);
            if ($resolved !== $this->url) {
                $unresolved[] = $this->label($target) . ' → ' . json_encode($resolved);
            }
        }

        $this->assertSame([], $unresolved);
    }

    public function test_no_host_render_path_breaks_on_a_resolved_or_dangling_media_value(): void
    {
        $renderers = ['ThemeCanvasRenderer' => ThemeCanvasRenderer::class, 'ThemeBuilder\\BlockRenderer' => BlockRenderer::class];
        $values = ['resolved' => ['media_id' => $this->media->uuid], 'dangling' => ['media_id' => (string) Str::uuid()]];
        $broken = [];
        foreach ($this->targets() as $target) {
            foreach ($renderers as $name => $class) {
                // A block that already placeholders with a plain URL is a
                // different defect (schema/defaults), not this sweep's.
                if (str_contains(app($class)->renderBlock($this->block($target, '/storage/plain.webp')), 'tb-placeholder')) {
                    continue;
                }
                foreach ($values as $kind => $value) {
                    if (str_contains(app($class)->renderBlock($this->block($target, $value)), 'tb-placeholder')) {
                        $broken[] = "{$name} {$kind} " . $this->label($target);
                    }
                }
            }
        }

        $this->assertSame([], $broken);
    }

    public function test_bento_card_background_renders_the_picked_image_on_both_paths(): void
    {
        $block = ['id' => 'bento', 'type' => 'bento_grid', 'props' => ['title' => 'بنتو', 'items' => [
            ['title' => 'کارت', 'size' => 'half', 'bg_image' => ['media_id' => $this->media->uuid]],
        ]]];

        foreach ([ThemeCanvasRenderer::class, BlockRenderer::class] as $class) {
            $html = app($class)->renderBlock($block);
            $this->assertStringNotContainsString('tb-placeholder', $html, $class);
            $this->assertStringContainsString($this->url, $html, $class);
        }
    }

    public function test_default_layout_renderer_resolves_the_mega_menu_banner(): void
    {
        $html = app(BlockRenderer::class)->renderBlock(['id' => 'hdr', 'type' => 'nav_header', 'props' => ['logo_text' => 'برند', 'nav_links' => [[
            'label' => 'محصولات', 'url' => '/shop', 'mega_banner_image' => ['media_id' => $this->media->uuid],
            'mega_columns' => [['title' => 'ستون', 'items' => [['label' => 'آیتم', 'url' => '/shop']]]],
        ]]]]);

        $this->assertStringNotContainsString('tb-placeholder', $html);
        $this->assertStringContainsString($this->url, $html);
    }

    public function test_internal_page_links_nested_in_repeaters_resolve_to_the_live_permalink(): void
    {
        $page = Page::create(['title' => 'درباره ما', 'slug' => 'about-us', 'content' => '', 'is_published' => true]);

        $html = app(BlockRenderer::class)->renderBlock(['id' => 'hdr', 'type' => 'nav_header', 'props' => ['logo_text' => 'برند', 'nav_links' => [
            ['label' => 'درباره', 'url' => ['type' => 'internal_page', 'page_id' => $page->id]],
        ]]]);

        $this->assertStringContainsString('href="' . route('page.show', 'about-us') . '"', $html);
    }

    public function test_the_editor_sees_why_a_block_is_invalid_and_a_public_page_does_not(): void
    {
        $block = ['id' => 'bad', 'type' => 'heading', 'props' => ['text' => 'عنوان', 'level' => ['not', 'a', 'level']]];

        $editor = app(BlockRenderer::class)->setContext(['editor_mode' => true])->renderBlock($block);
        $this->assertStringContainsString('[invalid] heading', $editor);
        $this->assertStringContainsString('tb-placeholder-detail', $editor);
        $this->assertStringContainsString('level', $editor, 'the failing prop is named');

        $public = app(BlockRenderer::class)->setMode('public')->setContext(['editor_mode' => true])->renderBlock($block);
        $this->assertStringContainsString('[invalid] heading', $public);
        $this->assertStringNotContainsString('tb-placeholder-detail', $public);
    }

    public function test_placeholder_detail_never_prints_server_paths(): void
    {
        $e = new \ErrorException('Array to string conversion in /var/www/customer-site/vendor/saas-platform/modules-theme-runtime/src/BlockRenderer.php and C:\\laragon\\www\\x\\Y.php');
        $detail = \App\Services\ThemeBuilder\PlaceholderDetail::fromThrowable($e);

        $this->assertStringContainsString('Array to string conversion', $detail);
        $this->assertStringContainsString('BlockRenderer.php', $detail);
        $this->assertStringNotContainsString('/var/www', $detail);
        $this->assertStringNotContainsString('laragon', $detail);
    }

    public function test_a_structured_link_on_a_top_level_button_is_no_longer_wiped(): void
    {
        $html = app(ThemeCanvasRenderer::class)->renderBlock(['id' => 'h', 'type' => 'hero', 'props' => [
            'title' => 'عنوان', 'button_text' => 'خرید', 'button_url' => ['url' => '/shop/sale', 'target' => '_blank'],
        ]]);

        $this->assertStringContainsString('/shop/sale', $html);
    }
}
