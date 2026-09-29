<?php

namespace Tests\Feature\ThemeCanvas;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Phase 2C — generic, opt-in Theme runtime capabilities: document
 * direction ('global_styles.direction', an already-existing field every
 * saas-platform theme-import adapter already writes 'rtl' into, now
 * actually wired up on the customer-site render path for the first time)
 * and chrome ownership ('global_styles.chrome_owner', new, alongside
 * 'direction' — both live under global_styles, not settings, because the
 * real install endpoint's request validation whitelists global_styles but
 * silently strips any settings key; see ThemeCanvasPageController's own
 * docblock for the full data-flow trace).
 *
 * Not named after, or specific to, any one theme — proven generically here
 * via a synthetic 'phase2c-runtime-capability-test' theme identifier, the
 * same install-round-trip pattern ThemeInstallRoundTripTest already
 * established (real POST to the real install endpoint, real
 * ThemeInstanceRevisionService write, real public GET render) — not a
 * shortcut, not a mock.
 *
 * Assertion note: theme-canvas-style-contract.blade.php's static CSS
 * (always present in <head> regardless of which header/footer branch
 * renders) contains literal text like ".tb-site-header{...}" and a code
 * comment mentioning '[dir="rtl"]' — so bare substring checks for
 * "tb-site-header" or 'dir="rtl"' alone would false-positive against that
 * CSS text even when no such element/attribute actually exists in the
 * body. Every assertion below uses a marker specific enough (a full
 * class="..." attribute string, a full opening <html ...> tag, an
 * id="..." attribute) that it cannot match CSS selector or comment text.
 */
class ThemeRuntimeCapabilitiesTest extends TestCase
{
    use RefreshDatabase;

    private const WRAPPED_HEADER_MARKER = 'class="canvas-site-header tb-site-header" role="banner" id="site-header"';

    private const WRAPPED_FOOTER_MARKER = 'class="canvas-site-footer tb-site-footer" role="contentinfo" id="site-footer"';

    private const FALLBACK_HEADER_MARKER = '<header class="canvas-site-header" style="background:var(--tb-primary';

    private const FALLBACK_FOOTER_MARKER = '<footer class="canvas-site-footer" style="background:#1f2937';

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
        config([
            'site.owner_site' => true,
            'services.customer_provision_secret' => 'phase2c-test-secret',
        ]);
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('installed.lock'));
        parent::tearDown();
    }

    private function installPayload(array $overrides = []): array
    {
        return array_merge([
            'theme_id' => 'phase2c-runtime-capability-test',
            'theme_name' => 'Phase 2C Runtime Capability Test',
            'source_theme_id' => '999',
            'source_theme_version' => '1.0.0',
            'source_release_id' => 999,
            'header_blocks' => [
                ['type' => 'nav_header', 'props' => ['logo_text' => 'Test Brand', 'nav_links' => []]],
            ],
            'footer_blocks' => [
                ['type' => 'nav_footer', 'props' => ['logo_text' => 'Test Brand', 'copyright' => '©']],
            ],
            'pages' => [
                'home' => ['title' => 'Home', 'slug' => '', 'is_home' => true, 'blocks' => [
                    ['type' => 'hero', 'props' => ['title' => 'Phase 2C Content Marker']],
                ]],
            ],
            'global_styles' => ['primary_color' => '#4f46e5'],
            'extension_assets' => [],
        ], $overrides);
    }

    private function install(array $overrides = []): TestResponse
    {
        return $this->withHeaders(['X-Provision-Secret' => 'phase2c-test-secret'])
            ->postJson(route('builder.api.theme.install'), $this->installPayload($overrides));
    }

    private function viteMarker(string $entry): string
    {
        if (file_exists(public_path('hot'))) {
            return $entry;
        }
        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);

        return $manifest[$entry]['file'];
    }

    // ── CASE A — existing/default theme, no new settings ───────────────────

    public function test_case_a_default_theme_renders_rtl_with_unchanged_platform_header_and_footer(): void
    {
        $this->install()->assertOk();

        $home = $this->get('/');
        $home->assertOk();
        $home->assertSee('<html lang="fa" dir="rtl">', false);
        $home->assertSee(self::WRAPPED_HEADER_MARKER, false);
        $home->assertSee(self::WRAPPED_FOOTER_MARKER, false);
        $home->assertDontSee(self::FALLBACK_HEADER_MARKER, false);
        $home->assertSee('Phase 2C Content Marker');
    }

    public function test_case_a_default_theme_with_empty_header_footer_blocks_shows_platform_fallback_chrome(): void
    {
        $this->install(['header_blocks' => [], 'footer_blocks' => []])->assertOk();

        $home = $this->get('/');
        $home->assertOk();
        $home->assertSee(self::FALLBACK_HEADER_MARKER, false);
        $home->assertSee(self::FALLBACK_FOOTER_MARKER, false);
        $home->assertDontSee(self::WRAPPED_HEADER_MARKER, false);
        $home->assertDontSee(self::WRAPPED_FOOTER_MARKER, false);
    }

    // ── CASE B — existing theme with header_blocks/footer_blocks, no chrome opt-in ──

    public function test_case_b_header_blocks_present_without_chrome_opt_in_renders_existing_wrapper_unchanged(): void
    {
        $this->install()->assertOk();

        $home = $this->get('/');
        $home->assertOk();
        $home->assertSee('id="site-header"', false);
        $home->assertSee('id="site-footer"', false);
        $home->assertSee('class="tb-header-inner"', false);
        $home->assertSee('id="tb-mobile-menu-btn"', false);
    }

    // ── CASE C — theme-owned chrome, explicit opt-in ────────────────────────

    public function test_case_c_theme_owned_chrome_skips_platform_wrapper_and_fallback_both(): void
    {
        $this->install(['global_styles' => ['chrome_owner' => 'theme']])->assertOk();

        $home = $this->get('/');
        $home->assertOk();
        $home->assertDontSee(self::WRAPPED_HEADER_MARKER, false);
        $home->assertDontSee(self::WRAPPED_FOOTER_MARKER, false);
        $home->assertDontSee(self::FALLBACK_HEADER_MARKER, false);
        $home->assertDontSee(self::FALLBACK_FOOTER_MARKER, false);
        $home->assertDontSee('id="site-header"', false);
        $home->assertDontSee('id="site-footer"', false);
        $home->assertDontSee('id="tb-mobile-menu-btn"', false);

        // Main content still renders.
        $home->assertSee('Phase 2C Content Marker');

        // Page-level accessibility structure is untouched by chrome
        // ownership — skip-link and <main> landmark still present.
        $home->assertSee('class="canvas-skip-link"', false);
        $home->assertSee('id="canvas-main"', false);
        $home->assertSee('class="canvas-main"', false);
    }

    public function test_case_c_theme_owned_chrome_still_emits_the_themes_own_header_footer_markup_bare(): void
    {
        $this->install(['global_styles' => ['chrome_owner' => 'theme']])->assertOk();

        $home = $this->get('/');
        $home->assertOk();
        // The theme's own nav_header/nav_footer block output (rendered by
        // the same BlockRenderer as always) is still present, just not
        // wrapped in platform chrome.
        $home->assertSee('Test Brand');
    }

    // ── CASE D / E / F / G — direction ──────────────────────────────────────

    public function test_case_d_explicit_ltr_direction_renders_ltr_document(): void
    {
        $this->install(['global_styles' => ['direction' => 'ltr']])->assertOk();

        $home = $this->get('/');
        $home->assertOk();
        $home->assertSee('<html lang="en" dir="ltr">', false);
        $home->assertDontSee('<html lang="fa" dir="rtl">', false);
    }

    public function test_case_e_explicit_rtl_direction_renders_rtl_document(): void
    {
        $this->install(['global_styles' => ['direction' => 'rtl']])->assertOk();

        $home = $this->get('/');
        $home->assertOk();
        $home->assertSee('<html lang="fa" dir="rtl">', false);
    }

    public function test_case_f_invalid_direction_value_safely_falls_back_to_rtl(): void
    {
        $this->install(['global_styles' => ['direction' => 'upside-down']])->assertOk();

        $home = $this->get('/');
        $home->assertOk();
        $home->assertSee('<html lang="fa" dir="rtl">', false);
        $home->assertDontSee('dir="upside-down"', false);
    }

    public function test_case_g_no_direction_setting_defaults_to_rtl(): void
    {
        $this->install()->assertOk(); // global_styles has no 'direction' key at all

        $home = $this->get('/');
        $home->assertOk();
        $home->assertSee('<html lang="fa" dir="rtl">', false);
    }

    // ── Security ─────────────────────────────────────────────────────────

    public function test_malicious_direction_value_cannot_inject_attributes_or_markup(): void
    {
        $payload = 'ltr" onload="alert(1)"><script>alert(2)</script';
        $this->install(['global_styles' => ['direction' => $payload]])->assertOk();

        $home = $this->get('/');
        $home->assertOk();

        $html = $home->getContent();
        // The allowlist in ThemeCanvasPageController::resolveThemeRuntimeCapabilities()
        // rejects anything not literally 'ltr' or 'rtl' BEFORE the value
        // ever reaches Blade — so none of the injected fragments may
        // appear anywhere in the response, escaped or not.
        self::assertStringNotContainsString('onload=', $html);
        self::assertStringNotContainsString('<script>alert(2)', $html);
        self::assertStringNotContainsString($payload, $html);
        $home->assertSee('<html lang="fa" dir="rtl">', false);
    }

    public function test_non_string_direction_value_does_not_error_and_falls_back_to_rtl(): void
    {
        // A theme payload could plausibly carry a malformed non-scalar
        // value here (e.g. from a bad JSON import) — must not throw a
        // TypeError against in_array(), must still safely fall back.
        $this->install(['global_styles' => ['direction' => ['not', 'a', 'string']]])->assertOk();

        $home = $this->get('/');
        $home->assertOk();
        $home->assertSee('<html lang="fa" dir="rtl">', false);
    }

    // ── Combined: theme-owned chrome + LTR (the FoodMart-shaped case, proven generically) ──

    public function test_theme_owned_chrome_and_ltr_direction_can_be_combined(): void
    {
        $this->install([
            'global_styles' => ['chrome_owner' => 'theme', 'direction' => 'ltr'],
        ])->assertOk();

        $home = $this->get('/');
        $home->assertOk();
        $home->assertSee('<html lang="en" dir="ltr">', false);
        $home->assertDontSee(self::WRAPPED_HEADER_MARKER, false);
        $home->assertDontSee(self::FALLBACK_HEADER_MARKER, false);
        $home->assertDontSee(self::WRAPPED_FOOTER_MARKER, false);
        $home->assertDontSee(self::FALLBACK_FOOTER_MARKER, false);
        $home->assertSee('Phase 2C Content Marker');
    }

    public function test_default_theme_loads_platform_css_and_platform_js(): void
    {
        $this->install()->assertOk();
        $home = $this->get('/')->assertOk();
        $home->assertSee($this->viteMarker('resources/css/app.css'), false);
        $home->assertSee($this->viteMarker('resources/js/app.js'), false);
    }

    public function test_theme_owned_presentation_omits_platform_css_but_keeps_platform_js(): void
    {
        $this->install(['global_styles' => [
            'direction' => 'ltr',
            'chrome_owner' => 'theme',
            'presentation_css_owner' => 'theme',
        ]])->assertOk();
        $home = $this->get('/')->assertOk();
        $home->assertDontSee($this->viteMarker('resources/css/app.css'), false);
        $home->assertSee($this->viteMarker('resources/js/app.js'), false);
        $home->assertSee('<html lang="en" dir="ltr">', false);
        $home->assertDontSee(self::WRAPPED_HEADER_MARKER, false);
    }

    public function test_invalid_presentation_owner_preserves_default_platform_css(): void
    {
        $this->install(['global_styles' => ['presentation_css_owner' => '<style>bad</style>']])->assertOk();
        $home = $this->get('/')->assertOk();
        $home->assertSee($this->viteMarker('resources/css/app.css'), false);
        $home->assertDontSee('<style>bad</style>', false);
    }
}
