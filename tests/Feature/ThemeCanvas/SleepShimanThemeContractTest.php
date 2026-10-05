<?php

namespace Tests\Feature\ThemeCanvas;

use App\Services\ThemeCanvas\BlockRegistry;
use App\Services\ThemeCanvas\CustomerMediaResolver;
use App\Services\ThemeCanvas\DataBinding\ThemeDataBindingRegistry;
use App\Services\ThemeCanvas\ThemeCanvasRenderer;
use App\Services\ThemeCanvas\ThemePresentationCapabilityRegistry;
use Tests\TestCase;

final class SleepShimanThemeContractTest extends TestCase
{
    public function test_shared_sleep_shiman_types_are_available_to_tenant_canvas(): void
    {
        $types = array_filter(BlockRegistry::all(), fn ($definition, $type) => str_starts_with($type, 'sleepshiman_'), ARRAY_FILTER_USE_BOTH);
        self::assertCount(22, $types);
        foreach ($types as $type => $definition) {
            self::assertNotEmpty($definition['schema'], $type);
        }
    }

    public function test_module_presentations_are_discovered_from_active_theme_only(): void
    {
        $theme = ['pages' => ['home' => ['blocks' => [
            ['id' => 'products', 'type' => 'sleepshiman_mattresses'],
            ['id' => 'blog', 'type' => 'sleepshiman_articles'],
            ['id' => 'reviews', 'type' => 'sleepshiman_reviews'],
        ]]]];
        $available = app(ThemePresentationCapabilityRegistry::class)->availableFor($theme);
        self::assertSame('sleepshiman_mattresses', $available['products.collection']['presentations'][0]['type']);
        self::assertSame('sleepshiman_articles', $available['blog.posts']['presentations'][0]['type']);
        self::assertSame('sleepshiman_reviews', $available['reviews.collection']['presentations'][0]['type']);
    }

    public function test_sleep_shiman_media_fields_use_canonical_customer_media_resolver_inventory(): void
    {
        self::assertSame(['logo'], CustomerMediaResolver::MEDIA_PROPS['sleepshiman_header']);
        self::assertSame('image', CustomerMediaResolver::MEDIA_REPEATERS['sleepshiman_mattresses']['field']);
        self::assertSame('avatar', CustomerMediaResolver::MEDIA_REPEATERS['sleepshiman_reviews']['field']);
    }

    public function test_tenant_renderer_uses_shared_manual_markup_and_escapes_content(): void
    {
        $html = app(ThemeCanvasRenderer::class)->renderBlock(['id' => 'stats', 'type' => 'sleepshiman_stats', 'props' => [
            'items' => [['value' => '۱۰', 'label' => '<script>unsafe</script>']],
        ]]);
        self::assertStringContainsString('ss-stats', $html);
        self::assertStringContainsString('data-tb-logical-index="0"', $html);
        self::assertStringNotContainsString('<script>', $html);
    }

    public function test_binding_contract_allows_explicit_manual_fallback_and_rejects_cross_source_access(): void
    {
        $registry = app(ThemeDataBindingRegistry::class);
        $manual = $registry->resolveBlockLists([[
            'id' => 'fallback', 'type' => 'sleepshiman_mattresses', 'props' => [],
            'binding' => ['source' => 'products', 'query' => ['sort' => 'manual', 'selected_ids' => [999999], 'limit' => 1], 'empty' => 'manual'],
        ]]);
        $rejected = $registry->resolveBlockLists([[
            'id' => 'wrong', 'type' => 'sleepshiman_articles', 'props' => [],
            'binding' => ['source' => 'products', 'query' => ['sort' => 'latest', 'limit' => 1], 'empty' => 'manual'],
        ]]);

        self::assertSame('resolved', $manual['fallback']['status']);
        self::assertSame('manual', $manual['fallback']['empty']);
        self::assertSame('invalid', $rejected['wrong']['status']);
    }
}
