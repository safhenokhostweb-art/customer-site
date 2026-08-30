<?php

namespace Tests\Unit\ThemeBuilder;

use App\Services\ThemeBuilder\BlockRenderer;
use App\Services\ThemeBuilder\ElementSchemaRegistry;
use Tests\TestCase;

/**
 * Phase 2f (saas-platform ADR-027) — customer-site parity for the
 * client_logos fabrication fix. This renderer used to substitute 9
 * hardcoded HVAC-brand partner names ("Carrier", "Trane", "Lennox", ...)
 * whenever no real logos were authored — real business/partner data must
 * never be invented merely to produce non-empty HTML on a live tenant site.
 */
class EmptyOutputIntegrityTest extends TestCase
{
    protected BlockRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = new BlockRenderer();
        $this->renderer->setSchemaRegistry(new ElementSchemaRegistry());
    }

    public function test_client_logos_never_fabricates_business_data_when_none_is_authored(): void
    {
        $html = $this->renderer->renderBlock(['type' => 'client_logos', 'id' => 'x1', 'props' => ['title' => 'مشتریان']]);

        foreach (['Carrier', 'Trane', 'Lennox', 'Daikin', 'Mitsubishi', 'Rheem', 'York', 'Goodman', 'Honeywell'] as $fabricatedBrand) {
            $this->assertStringNotContainsString($fabricatedBrand, $html, "leaked fabricated brand name \"{$fabricatedBrand}\"");
        }
        $this->assertStringContainsString('مشتریان', $html);
    }

    public function test_client_logos_output_is_never_truly_empty_even_with_no_props(): void
    {
        $html = $this->renderer->renderBlock(['type' => 'client_logos', 'id' => 'x1', 'props' => []]);
        $this->assertNotSame('', trim($html));
    }

    public function test_client_logos_renders_real_authored_logos_when_present(): void
    {
        $html = $this->renderer->renderBlock(['type' => 'client_logos', 'id' => 'x1', 'props' => [
            'title' => 'همکاران ما',
            'logos' => [['name' => 'شرکت الف'], ['name' => 'شرکت ب']],
        ]]);

        $this->assertStringContainsString('شرکت الف', $html);
        $this->assertStringContainsString('شرکت ب', $html);
        $this->assertStringNotContainsString('Carrier', $html);
    }
}
