<?php

namespace Tests\Feature\ThemeCanvas;

use SaaSPlatform\ThemeRuntime\SolarIndustryBlockTypes;
use SaaSPlatform\ThemeRuntime\VendorAssets;
use Tests\TestCase;

/**
 * The theme-runtime package serves its vendored, pinned third-party browser
 * code (VendorAssets) on the customer host too, so the solar-industry theme's
 * Lottie mode never depends on a public CDN. Skipped on an install whose
 * theme-runtime predates VendorAssets.
 */
class ThemeRuntimeVendorAssetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (! class_exists(VendorAssets::class)) {
            $this->markTestSkipped('theme-runtime without VendorAssets');
        }
    }

    public function test_the_customer_host_serves_the_pinned_lottie_player(): void
    {
        $response = $this->get(VendorAssets::url(VendorAssets::LOTTIE_PLAYER));

        $response->assertOk();
        $this->assertStringStartsWith('application/javascript', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('immutable', (string) $response->headers->get('Cache-Control'));
        $this->assertSame(VendorAssets::LOTTIE_PLAYER_UPSTREAM_SHA256, hash_file('sha256', $response->baseResponse->getFile()->getPathname()));
        $this->get('/theme-runtime/vendor/lottie-player-latest.js')->assertNotFound();
    }

    public function test_the_solar_theme_section_is_distinct_from_the_licensed_motion(): void
    {
        if (! class_exists(SolarIndustryBlockTypes::class)) {
            $this->markTestSkipped('theme-runtime without the solar-industry blocks');
        }

        $this->assertSame(['solar-energy-cycle'], array_keys(SolarIndustryBlockTypes::definitions()));
    }
}
