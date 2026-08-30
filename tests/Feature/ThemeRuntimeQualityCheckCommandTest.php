<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use SaaSPlatform\ThemeRuntime\Browser\PlaywrightExtractionService;
use Tests\TestCase;

/**
 * Phase 2h (ADR-029) — `theme-runtime:verify` temporarily installs a real
 * canonical Theme snapshot into this (single-tenant) instance's own
 * `active_canvas_theme_data` Setting to exercise the real rendering path,
 * then must restore whatever was there before — regardless of outcome.
 * This is the one safety property that makes "verify against the real
 * runtime" acceptable to run at all: it must never leave a dev/test
 * instance's real configured theme mutated.
 */
class ThemeRuntimeQualityCheckCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::set('active_canvas_theme_data', ['sentinel' => 'pre-existing-value']);
        Setting::set('active_canvas_theme_name', 'pre-existing-theme');
    }

    private function snapshotPath(): string
    {
        $path = storage_path('app/test-theme-runtime-snapshot.json');
        file_put_contents($path, json_encode([
            'header_blocks' => [],
            'footer_blocks' => [],
            'pages' => ['home' => ['title' => 'خانه', 'slug' => '', 'blocks' => []]],
            'global_styles' => [],
        ]));
        return $path;
    }

    public function test_the_original_setting_is_restored_after_a_successful_run(): void
    {
        $this->mock(PlaywrightExtractionService::class, function ($mock) {
            $mock->shouldReceive('isAvailable')->andReturn(true);
            $mock->shouldReceive('runQualityCheck')->andReturn([
                'success' => true,
                'viewport_dimensions' => ['width' => 1440, 'height' => 900],
                'navigation' => ['ok' => true],
                'overflow' => ['measured' => true, 'overflowPx' => 0],
                'page_errors' => ['total' => 0],
                'resource_failures' => ['entries' => []],
                'contrast' => ['failed' => 0],
            ]);
        });

        $this->artisan('theme-runtime:verify', ['snapshot' => $this->snapshotPath()])->assertExitCode(0);

        $this->assertSame('pre-existing-theme', Setting::get('active_canvas_theme_name'));
        $raw = \Illuminate\Support\Facades\DB::table('settings')->where('key', 'active_canvas_theme_data')->value('value');
        $this->assertSame(['sentinel' => 'pre-existing-value'], json_decode($raw, true));
    }

    public function test_the_original_setting_is_restored_even_when_the_check_finds_blocking_issues(): void
    {
        $this->mock(PlaywrightExtractionService::class, function ($mock) {
            $mock->shouldReceive('isAvailable')->andReturn(true);
            $mock->shouldReceive('runQualityCheck')->andReturn([
                'success' => true,
                'viewport_dimensions' => ['width' => 1440, 'height' => 900],
                'navigation' => ['ok' => false],
                'overflow' => ['measured' => false],
                'page_errors' => ['total' => 0],
                'resource_failures' => ['entries' => []],
                'contrast' => ['failed' => 0],
            ]);
        });

        $this->artisan('theme-runtime:verify', ['snapshot' => $this->snapshotPath()])->assertExitCode(1);

        $raw = \Illuminate\Support\Facades\DB::table('settings')->where('key', 'active_canvas_theme_data')->value('value');
        $this->assertSame(['sentinel' => 'pre-existing-value'], json_decode($raw, true));
    }

    public function test_the_original_setting_is_restored_even_when_the_process_itself_fails(): void
    {
        $this->mock(PlaywrightExtractionService::class, function ($mock) {
            $mock->shouldReceive('isAvailable')->andReturn(true);
            $mock->shouldReceive('runQualityCheck')->andThrow(new \RuntimeException('simulated infra failure'));
        });

        $this->artisan('theme-runtime:verify', ['snapshot' => $this->snapshotPath()])->assertExitCode(0);

        $raw = \Illuminate\Support\Facades\DB::table('settings')->where('key', 'active_canvas_theme_data')->value('value');
        $this->assertSame(['sentinel' => 'pre-existing-value'], json_decode($raw, true));
    }

    public function test_browser_infrastructure_unavailable_is_reported_not_silently_passed(): void
    {
        $this->mock(PlaywrightExtractionService::class, function ($mock) {
            $mock->shouldReceive('isAvailable')->andReturn(false);
            $mock->shouldReceive('availabilityReport')->andReturn(['ready' => false]);
        });

        $this->artisan('theme-runtime:verify', ['snapshot' => $this->snapshotPath()])->assertExitCode(2);

        // Never even attempted the swap — nothing to restore, nothing touched.
        $this->assertSame('pre-existing-theme', Setting::get('active_canvas_theme_name'));
    }
}
