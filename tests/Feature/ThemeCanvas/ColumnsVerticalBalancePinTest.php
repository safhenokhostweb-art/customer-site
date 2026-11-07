<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\License;
use App\Services\ThemeCanvas\ThemeCanvasRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use SaaSPlatform\ThemeRuntime\Providers\ThemeRuntimeServiceProvider;
use Tests\TestCase;

/**
 * Host proof for the saas-platform pin of WU-COLUMNS-VERTICAL-BALANCE: in a
 * `columns` row on the storefront, a module:motion beside a taller widget
 * (e.g. a slider) no longer sits at the top of its cell with a gap beneath
 * it. The motion keeps the full cell height and centres its artwork, and
 * heading/text/rich text/image centre in the cell. The fix reaches this host
 * only through the vendor mirror of the shared contract sheet, so this fails
 * on the previous pin (4f5cf382).
 *
 * The rules target direct children of `.tb-columns`. They hold only because
 * this host renders the motion root (`.tb-module-motion`) and the heading as
 * the grid cells themselves, which is checked here too. The slider fix of the
 * previous pin (SliderInColumnsHeightPinTest) is unchanged.
 */
class ColumnsVerticalBalancePinTest extends TestCase
{
    use RefreshDatabase;

    private function contract(): string
    {
        return str_replace("\r\n", "\n", (string) file_get_contents(ThemeRuntimeServiceProvider::cssContractPath()));
    }

    public function test_a_motion_in_a_columns_cell_centres_its_artwork(): void
    {
        $this->assertStringContainsString(
            '.tb-columns > :is(.tb-module-motion, .tb-module-motion-fallback) { display: flex; flex-direction: column; justify-content: center; }',
            $this->contract()
        );
    }

    public function test_text_type_widgets_centre_in_the_cell_and_cards_keep_stretching(): void
    {
        $contract = $this->contract();

        $this->assertStringContainsString(
            '.tb-columns > :is(.tb-heading, .tb-text, .tb-rich-text, .tb-image) { align-self: center; }',
            $contract
        );
        $this->assertDoesNotMatchRegularExpression('/\.tb-columns\s*\{[^}]*align-items/', $contract);
    }

    public function test_the_storefront_motion_and_heading_roots_are_the_columns_cells(): void
    {
        Cache::forget('motion.runtime.manifest');
        config(['site.platform_url' => 'https://platform.example']);
        License::create([
            'license_key' => 'COLS-BALANCE-KEY',
            'domain' => null,
            'platform_valid' => true,
            'expires_at' => now()->addYear(),
            'last_validated_at' => now(),
            'modules' => [],
        ]);
        Http::fake([
            'platform.example/api/motion-runtime/manifest*' => Http::response([
                'available' => true,
                'schema_version' => '1.0',
                'site_reference' => 'site-1',
                'manifest_checksum' => str_repeat('a', 64),
                'assets' => [[
                    'asset_id' => 'hero-motion',
                    'published_version' => 1,
                    'checksum' => str_repeat('b', 64),
                    'asset_type' => 'svg',
                    'optimized_reference' => '/motion/hero-motion/v1/animation.svg',
                ]],
            ], 200),
        ]);

        $html = (new ThemeCanvasRenderer())->renderBlock([
            'id' => 'cols',
            'type' => 'columns',
            'props' => ['ratios' => '1/2,1/2'],
            'children' => [
                ['id' => 'txt', 'type' => 'heading', 'props' => ['text' => 'عنوان', 'level' => 2]],
                ['id' => 'mot', 'type' => 'module:motion', 'props' => ['motion_asset_id' => 'hero-motion', 'placement' => 'section']],
            ],
        ]);

        $this->assertMatchesRegularExpression(
            '/class="tb-columns"[^>]*>\s*<h2\b[^>]*\bclass="tb-heading"[^>]*>عنوان<\/h2>\s*<div\b[^>]*\bid="tb-mot"[^>]*\bclass="tb-module-motion"/u',
            $html
        );
    }
}
