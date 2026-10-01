<?php

namespace Tests\Feature\ThemeCanvas;

use App\Services\ThemeCanvas\LayoutSlotBindingService;
use Tests\TestCase;

/**
 * Layout Slot Binding proof (Phase 11 follow-up, Test-Layout-Validation).
 *
 * Proves that the fixture's header/footer slots render as LAYOUT ELEMENTS —
 * injected into the <header>/<footer> chrome tags with explicit
 * data-layout-slot markers — while the hero stays an inline content block
 * and never leaks into the chrome.
 */
class LayoutSlotBindingTest extends TestCase
{
    private function fixture(): array
    {
        $path = base_path('tests/Feature/ThemeCanvas/fixtures/layout-slot-validation.json');
        $decoded = json_decode((string) file_get_contents($path), true);
        self::assertIsArray($decoded, 'layout-slot-validation fixture must decode to an array');

        return $decoded;
    }

    public function test_fixture_binds_catalog_presets_to_real_registry_types(): void
    {
        $bound = app(LayoutSlotBindingService::class)->bindFixture($this->fixture());

        self::assertSame('Test-Layout-Validation', $bound['title']);

        self::assertSame('header', $bound['slots']['header']['slot']);
        self::assertSame('header', $bound['slots']['header']['tag']);
        self::assertSame('header-minimal', $bound['slots']['header']['catalog_preset']);
        self::assertSame('nav_header', $bound['slots']['header']['block']['type']);

        self::assertSame('footer', $bound['slots']['footer']['slot']);
        self::assertSame('footer', $bound['slots']['footer']['tag']);
        self::assertSame('footer-basic-social', $bound['slots']['footer']['catalog_preset']);
        self::assertSame('nav_footer', $bound['slots']['footer']['block']['type']);

        self::assertCount(1, $bound['inline_content']);
        self::assertSame('hero', $bound['inline_content'][0]['type']);
    }

    public function test_slots_render_into_chrome_tags_with_markers_and_hero_stays_inline(): void
    {
        $report = app(LayoutSlotBindingService::class)->validate($this->fixture());

        self::assertTrue($report['binding_ok'], implode('; ', $report['failures']));

        // Header slot: rendered INTO the <header> chrome tag, marked, non-empty.
        self::assertSame('header', $report['slots']['header']['rendered_into']);
        self::assertTrue($report['slots']['header']['marker_present']);
        self::assertTrue($report['slots']['header']['has_inner_markup']);
        self::assertStringStartsWith('<header data-layout-slot="header">', $report['slots']['header']['html']);

        // Footer slot: rendered INTO the <footer> chrome tag, marked, non-empty.
        self::assertSame('footer', $report['slots']['footer']['rendered_into']);
        self::assertTrue($report['slots']['footer']['marker_present']);
        self::assertTrue($report['slots']['footer']['has_inner_markup']);
        self::assertStringStartsWith('<footer data-layout-slot="footer">', $report['slots']['footer']['html']);

        // Inline content: hero renders in the content stream — and ONLY there.
        self::assertTrue($report['inline_content']['contains_hero']);
        self::assertFalse($report['inline_content']['hero_leaked_into_slots']);
        self::assertStringContainsString('Test-Layout-Validation Hero', $report['inline_content']['html']);
    }

    public function test_slot_render_never_leaks_public_mode_into_surrounding_state(): void
    {
        $service = app(LayoutSlotBindingService::class);
        $fixture = $this->fixture();
        $bound = $service->bindFixture($fixture);
        $themeData = app(\App\Services\BuilderLayoutService::class)->currentThemeData();

        // Render a slot, then prove the renderer left no residue: a plain
        // render afterwards does not carry the slot's forced context.
        $service->renderSlot($bound['slots']['header'], $themeData);

        $after = app(\App\Services\ThemeCanvas\ThemeCanvasRenderer::class);
        $probe = $after->renderBlock($bound['slots']['footer']['block']);

        // In whatever mode the container renderer is configured, the probe
        // must render WITHOUT the slot wrapper (state restoration worked).
        self::assertStringNotContainsString('data-layout-slot', $probe);
    }
}
