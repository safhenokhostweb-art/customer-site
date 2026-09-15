<?php

namespace Tests\Feature\ThemeCanvas;

use App\Services\ThemeBuilder\ElementSchemaRegistry;
use App\Services\ThemeCanvas\BlockRegistry;
use App\Services\ThemeCanvas\ThemeCanvasRenderer;
use Tests\TestCase;

class ThemeCanvasPaletteCompatibilityTest extends TestCase
{
    public function test_canvas_palette_accounts_for_the_complete_canonical_inventory(): void
    {
        $registry = BlockRegistry::all();

        // Builder integrity audit (2026-09-11): module:portfolio_grid and
        // module:services_section were de-exposed alongside the existing
        // module:gallery_widget exclusion (no backend model — see
        // BlockRegistry::all()), dropping module count 34 -> 32 and total
        // 96 -> 94. Non-module count is unchanged.
        //
        // BLD-10 (Full Product Commercial Audit, TASK 1/3): bare `products`
        // (non-module) also de-exposed — a duplicate authority for exactly
        // what module:shop_product_grid/product_grid already cover
        // correctly, and had no renderer here or anywhere. Non-module
        // count 62 -> 61, total 94 -> 93. Module count unchanged.
        //
        // Placeholder re-evaluation (TASK 2A): `team` also de-exposed —
        // a permanently-inert DYNAMIC-category placeholder (no Team model
        // ever existed) duplicating `team_cards`, which already covers the
        // same need, production-ready. Non-module count 61 -> 60, total
        // 93 -> 92.
        //
        // MOD-01 (TASK 2A): `module:shop_wishlist_icon` added — a real,
        // working Builder integration for the existing ShopWishlistService/
        // session-compare backends, which had no block before. Module
        // count 32 -> 33, total 92 -> 93.
        //
        // MOD-02/MOD-03 (TASK 2A-CONTINUATION): `module:installment_badge`
        // (real InstallmentService/InstallmentPlan integration) and
        // `module:loyalty_points_badge` (real MembershipPointsService
        // integration) added — two more real Builder integrations for
        // existing backends that had no block before. Module count
        // 33 -> 35, total 93 -> 95. Non-module count unchanged.
        //
        // TASK 2A (tabs container authoring fix): `tab_panel` added to the
        // shared canonical registry as `tabs`'s real child container type
        // (see App\Services\ThemeCanvas\BlockRegistry::isContainerType() /
        // the shared SaaSPlatform\ThemeRuntime\BlockRegistry it delegates
        // to) — non-module count 60 -> 61, total 95 -> 96. Module count
        // unchanged.
        self::assertCount(96, $registry);
        self::assertCount(61, array_filter(array_keys($registry), fn (string $type) => !str_starts_with($type, 'module:') && !str_starts_with($type, 'ext:')));
        self::assertCount(35, array_filter(array_keys($registry), fn (string $type) => str_starts_with($type, 'module:')));
        self::assertArrayNotHasKey('module:gallery_widget', $registry);
        self::assertArrayNotHasKey('module:portfolio_grid', $registry);
        self::assertArrayNotHasKey('module:services_section', $registry);
        self::assertArrayNotHasKey('team', $registry);
        self::assertArrayNotHasKey('products', $registry);
    }

    public function test_every_available_non_module_canvas_block_has_a_valid_default_contract(): void
    {
        $validator = app(ElementSchemaRegistry::class);

        foreach (BlockRegistry::all() as $type => $definition) {
            if (str_starts_with($type, 'module:') || str_starts_with($type, 'ext:')) {
                continue;
            }

            $block = [
                'id' => 'compat_' . str_replace(':', '_', $type),
                'type' => $type,
                'props' => $definition['defaultProps'] ?? [],
                'children' => BlockRegistry::isContainerType($type) ? [] : [],
            ];

            self::assertTrue(
                $validator->validate($block),
                sprintf('%s default is invalid: %s', $type, implode('; ', $validator->getLastErrors()))
            );
        }
    }

    public function test_every_available_non_module_canvas_block_reaches_the_renderer_without_invalid_schema_placeholder(): void
    {
        $renderer = app(ThemeCanvasRenderer::class);

        foreach (BlockRegistry::all() as $type => $definition) {
            if (str_starts_with($type, 'module:') || str_starts_with($type, 'ext:')) {
                continue;
            }

            $html = $renderer->renderBlock([
                'id' => 'compat_' . str_replace(':', '_', $type),
                'type' => $type,
                'props' => $definition['defaultProps'] ?? [],
                'children' => [],
            ]);

            self::assertStringNotContainsString(
                'data-reason="invalid_schema"',
                $html,
                sprintf('%s reached the invalid-schema renderer fallback.', $type)
            );
        }
    }

    public function test_every_exposed_module_block_reaches_its_canvas_renderer_without_error_placeholder(): void
    {
        $renderer = app(ThemeCanvasRenderer::class);

        foreach (BlockRegistry::all() as $type => $definition) {
            if (!str_starts_with($type, 'module:')) {
                continue;
            }

            $html = $renderer->renderBlock([
                'id' => 'compat_' . str_replace(':', '_', $type),
                'type' => $type,
                'props' => $definition['defaultProps'] ?? [],
                'children' => [],
            ]);

            self::assertStringNotContainsString('data-reason="invalid_schema"', $html, "{$type} reached invalid_schema.");
            self::assertStringNotContainsString('data-reason="render_error"', $html, "{$type} raised a render error.");
        }
    }

    public function test_compound_media_schemas_preserve_their_canonical_runtime_shapes(): void
    {
        $gallery = BlockRegistry::get('gallery');
        self::assertSame('repeater', $gallery['schema']['items']['type']);
        self::assertSame('array', $gallery['schema']['items']['storage']);
        self::assertSame('image', $gallery['schema']['items']['fields']['url']['type']);
        self::assertArrayNotHasKey('images', $gallery['schema']);

        $team = BlockRegistry::get('team_cards');
        self::assertSame('repeater', $team['schema']['members']['type']);
        self::assertSame('json', $team['schema']['members']['storage']);
        self::assertSame('image', $team['schema']['members']['fields']['image']['type']);

        $galleryBlock = [
            'id' => 'compound_gallery',
            'type' => 'gallery',
            'props' => [
                'title' => 'QA',
                'columns' => '3',
                'items' => [['url' => '/storage/theme-media/qa.svg', 'alt' => 'QA', 'unknown' => 'preserved']],
            ],
            'children' => [],
        ];
        self::assertTrue(app(ElementSchemaRegistry::class)->validate($galleryBlock));
        self::assertStringContainsString('/storage/theme-media/qa.svg', app(ThemeCanvasRenderer::class)->renderBlock($galleryBlock));

        $teamBlock = [
            'id' => 'compound_team',
            'type' => 'team_cards',
            'props' => [
                'title' => 'QA',
                'columns' => '3',
                'members' => json_encode([['name' => 'QA', 'role' => 'Lead', 'image' => '/storage/theme-media/qa.svg', 'bio' => '', 'unknown' => 'preserved']]),
            ],
            'children' => [],
        ];
        self::assertTrue(app(ElementSchemaRegistry::class)->validate($teamBlock));
        self::assertStringContainsString('/storage/theme-media/qa.svg', app(ThemeCanvasRenderer::class)->renderBlock($teamBlock));
    }
}
