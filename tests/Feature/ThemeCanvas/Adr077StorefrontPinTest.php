<?php

namespace Tests\Feature\ThemeCanvas;

use App\Services\ThemeBuilder\BlockRenderer;
use App\Services\ThemeCanvas\BlockRegistry;
use SaaSPlatform\ThemeRuntime\Widget\WidgetFeatureProfile;
use SaaSPlatform\ThemeRuntime\Widget\WidgetFrictionProfile;
use Tests\TestCase;

/**
 * Host proof for saas-platform 2700635c (ADR-077 amendment): the storefront
 * fixes the Sentinel UI verifier asked for reach a live tenant page through
 * the canonical package. Fails on the previous pin (4a4f03d8).
 *
 * - The header phone link is a 44 px touch target on phones (it measured
 *   22 px in production).
 * - CTA defaults point at /contact, not "#".
 * - A new footer starts with real links.
 * - The Sentinel profiles (feature and friction) exist for the host registry.
 */
class Adr077StorefrontPinTest extends TestCase
{
    public function test_the_header_phone_link_is_a_44px_target_on_phones(): void
    {
        $css = (string) file_get_contents(\SaaSPlatform\ThemeRuntime\Providers\ThemeRuntimeServiceProvider::cssBlocksPath());
        $this->assertStringContainsString('@media (max-width: 767.98px) { .tb-nav-header-phone { display: inline-flex; align-items: center; min-block-size: 2.75rem;', $css);

        $partial = (string) file_get_contents(resource_path('views/partials/theme-canvas-style-contract.blade.php'));
        $this->assertStringContainsString("@include('theme-runtime::css.blocks')", $partial, 'the storefront style contract ships the shared sheet, rule included');
    }

    public function test_cta_and_footer_defaults_lead_to_real_pages(): void
    {
        $all = BlockRegistry::all();
        $this->assertSame('/contact', $all['header_modern_v1']['defaultProps']['ctaUrl']);
        $this->assertSame('/contact', $all['nav_header']['schema']['cta_url']['default']);

        $footer = (new BlockRenderer())->setMode('public')->renderBlock(['type' => 'nav_footer', 'id' => 'f1', 'props' => $all['nav_footer']['defaultProps']]);
        $this->assertStringContainsString('<a href="/contact">تماس با ما</a>', $footer);
        $this->assertStringContainsString('data-widget-slot="columns"', $footer);
        $this->assertStringNotContainsString('href="#"', $footer);
    }

    public function test_the_sentinel_profiles_cover_the_host_registry(): void
    {
        $this->assertSame([], WidgetFeatureProfile::violations(BlockRegistry::all()));
        $friction = WidgetFrictionProfile::analyze(BlockRegistry::all());
        $this->assertArrayHasKey('product_slider', $friction);
        $this->assertSame(['drag', 'dots', 'arrows'], array_column($friction['product_slider']['missing'], 'control'));
    }
}
