<?php

namespace Tests\Feature\ThemeCanvas;

use App\Services\ThemeCanvas\BlockRegistry;
use App\Services\ThemeCanvas\ThemePresentationCapabilityRegistry;
use Tests\TestCase;

class FoodMartCanvasCompletionTest extends TestCase
{
    public function test_every_foodmart_type_has_editable_schema_or_an_explicit_architectural_reason(): void
    {
        $types = array_filter(
            BlockRegistry::all(),
            static fn (array $definition, string $type): bool => str_starts_with($type, 'foodmart_'),
            ARRAY_FILTER_USE_BOTH,
        );

        self::assertCount(19, $types);
        foreach ($types as $type => $definition) {
            $schema = $definition['schema'] ?? [];
            $reason = trim((string) ($definition['schemaAuditReason'] ?? ''));
            self::assertTrue($schema !== [] || $reason !== '', "{$type} has neither meaningful controls nor an audit reason");
        }
    }

    public function test_hero_repeater_default_is_structurally_complete_and_safe(): void
    {
        $fields = BlockRegistry::all()['foodmart_hero']['schema']['slides']['fields'];

        foreach (['label', 'title', 'description', 'cta_label', 'cta_url', 'image', 'image_alt'] as $key) {
            self::assertArrayHasKey($key, $fields);
            self::assertNotSame('', $fields[$key]['default']);
        }
        self::assertSame('#', $fields['cta_url']['default']);
        self::assertSame('images/product-thumb-1.png', $fields['image']['default']);
    }

    public function test_active_theme_capability_contract_only_exposes_presentations_it_uses(): void
    {
        $theme = ['pages' => ['home' => ['blocks' => [
            ['id' => 'products', 'type' => 'foodmart_trending_products', 'props' => []],
            ['id' => 'categories', 'type' => 'foodmart_categories', 'props' => []],
        ]]]];

        $available = app(ThemePresentationCapabilityRegistry::class)->availableFor($theme);

        self::assertSame(['product_categories.collection', 'products.collection'], array_keys($available));
        self::assertSame('foodmart_categories', $available['product_categories.collection']['presentations'][0]['type']);
        self::assertSame('foodmart_trending_products', $available['products.collection']['presentations'][0]['type']);
        self::assertArrayNotHasKey('blog.collection', $available);
        self::assertArrayNotHasKey('forms.embed', $available);
    }
}
