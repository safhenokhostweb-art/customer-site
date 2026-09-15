<?php

namespace Tests\Unit\ThemeBuilder;

use App\Services\ThemeBuilder\ElementSchemaRegistry;
use Tests\TestCase;

/**
 * BLD-14 (Full Product Commercial Audit, TASK 1/3): documents and proves
 * the schema-merge precedence contract directly, at the mechanism level —
 * not just via specific fields (client_logos.logos, heading.level, image.url)
 * that happened to expose the bug. The contract:
 *
 *   1. TYPE EXISTENCE is a union of every source — a type Canvas can create
 *      is always known here, even with no hand-authored config entry.
 *   2. PER-FIELD TYPE is won by config/builder-element-schemas.php when it
 *      declares that field; Canvas/production-palette inference only fills
 *      in fields config does not mention.
 */
class ElementSchemaMergePrecedenceTest extends TestCase
{
    private ElementSchemaRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = app(ElementSchemaRegistry::class);
    }

    public function test_a_type_known_only_to_canvas_is_still_a_known_type(): void
    {
        // hero_split/hero_gradient/bento_grid etc. are Canvas-only (no
        // config/builder-element-schemas.php entry) — pick one and confirm
        // it resolves, proving the union-of-sources rule survived the fix.
        $canvasOnlyTypes = array_diff(
            array_keys(\App\Services\ThemeCanvas\BlockRegistry::all()),
            array_keys(config('builder-element-schemas.elements', []))
        );
        $this->assertNotEmpty($canvasOnlyTypes, 'test assumption: at least one Canvas type has no config entry');

        $sample = reset($canvasOnlyTypes);
        $this->assertTrue($this->registry->has($sample), "Canvas-only type \"{$sample}\" must still be a known type");
    }

    public function test_config_declared_field_type_wins_over_canvas_inferred_type(): void
    {
        // heading.level: config declares 'integer'; Canvas's <select>-derived
        // inference would say 'mixed' (accepts anything). Config must win.
        $schema = $this->registry->getSchema('heading');
        $this->assertSame('integer', $schema['props']['level']['type'] ?? null);
    }

    public function test_field_config_does_not_mention_falls_back_to_canvas_inference(): void
    {
        // divider has no config entry with props at all (config declares it
        // with empty props); if Canvas ever adds a field here that config
        // doesn't know about, it must still surface. Use a real example:
        // 'dividers' is a Canvas-only boolean field on stats_row that config
        // never declares — confirm it survives the merge as 'boolean', not
        // silently dropped because config "won" the whole type.
        $schema = $this->registry->getSchema('stats_row');
        $this->assertArrayHasKey('dividers', $schema['props'] ?? [], 'a Canvas-only field on a config-known type must not be dropped');
        $this->assertSame('boolean', $schema['props']['dividers']['type'] ?? null);
    }

    public function test_config_wins_the_whole_field_not_just_a_default(): void
    {
        // A config-declared type/children for a type Canvas also declares
        // must come from config, not get silently overwritten by Canvas's
        // own (possibly different) container/children classification.
        $schema = $this->registry->getSchema('client_logos');
        $this->assertSame('client_logos', $schema['type']);
        $this->assertSame('none', $schema['children']);
    }
}
