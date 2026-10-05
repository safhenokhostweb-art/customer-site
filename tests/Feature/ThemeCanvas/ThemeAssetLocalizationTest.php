<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\Setting;
use App\Models\ThemeInstanceRevision;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ThemeAssetLocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
        Storage::fake('public');
        config([
            'site.owner_site' => true,
            'site.platform_url' => 'https://platform.test',
            'services.customer_provision_secret' => 'theme-assets-test-secret',
        ]);
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('installed.lock'));
        parent::tearDown();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'theme_id' => 'asset-test-theme',
            'theme_name' => 'Asset Test Theme',
            'source_theme_id' => '81',
            'source_theme_version' => '1.0.0',
            'source_release_id' => 81,
            'header_blocks' => [],
            'footer_blocks' => [],
            'pages' => ['home' => ['title' => 'Home', 'slug' => '', 'is_home' => true, 'blocks' => []]],
            'global_styles' => [],
            'extension_assets' => [],
        ], $overrides);
    }

    private function install(array $overrides = [])
    {
        return $this->withHeaders(['X-Provision-Secret' => 'theme-assets-test-secret'])
            ->postJson(route('builder.api.theme.install'), $this->payload($overrides));
    }

    private function descriptor(string $kind, string $source, string $path, string $body): array
    {
        return compact('kind', 'source', 'path') + ['sha256' => hash('sha256', $body)];
    }

    public function test_absent_theme_assets_preserves_current_install_behavior(): void
    {
        $this->install()->assertOk();
        $this->assertSame([], ThemeInstanceRevision::firstOrFail()->theme_data['theme_assets']);
    }

    public function test_empty_theme_assets_is_a_safe_no_op(): void
    {
        $this->install(['theme_assets' => []])->assertOk();
        Http::assertNothingSent();
    }

    public function test_valid_manifest_is_localized_in_deterministic_order_without_source_urls(): void
    {
        $css = 'body{color:#123}';
        $js = 'window.themeReady=true;';
        Http::fake([
            'https://platform.test/release/theme.css' => Http::response($css, 200, ['Content-Type' => 'text/css']),
            'https://platform.test/release/theme.js' => Http::response($js, 200, ['Content-Type' => 'application/javascript']),
        ]);
        $manifest = ['version' => 1, 'assets' => [
            $this->descriptor('css', 'https://platform.test/release/theme.css', 'css/theme.css', $css),
            $this->descriptor('js', 'https://platform.test/release/theme.js', 'js/theme.js', $js),
        ]];

        $this->install(['theme_assets' => $manifest])->assertOk();
        $stored = ThemeInstanceRevision::firstOrFail()->theme_data['theme_assets'];
        $this->assertTrue($stored['localized']);
        $this->assertSame(['css', 'js'], array_column($stored['assets'], 'kind'));
        $this->assertSame(['css/theme.css', 'js/theme.js'], array_column($stored['assets'], 'path'));
        $this->assertStringNotContainsString('platform.test', json_encode($stored));
        foreach ($stored['assets'] as $asset) {
            Storage::disk('public')->assertExists($asset['storage_key']);
        }
    }

    /** @dataProvider invalidManifestProvider */
    public function test_malformed_or_unsafe_manifests_fail_closed(array $themeAssets): void
    {
        $this->install(['theme_assets' => $themeAssets])
            ->assertStatus(422)
            ->assertJson(['success' => false, 'error' => 'theme_asset_localization_failed']);
        $this->assertDatabaseCount('theme_instance_revisions', 0);
        $this->assertSame('', (string) Setting::get('active_canvas_theme_data', ''));
    }

    public static function invalidManifestProvider(): array
    {
        $hash = str_repeat('a', 64);

        return [
            'malformed descriptor' => [['version' => 1, 'assets' => [['kind' => 'css']]]],
            'javascript source' => [['version' => 1, 'assets' => [['kind' => 'js', 'source' => 'javascript:alert(1)', 'path' => 'x.js', 'sha256' => $hash]]]],
            'third party source' => [['version' => 1, 'assets' => [['kind' => 'css', 'source' => 'https://evil.test/x.css', 'path' => 'x.css', 'sha256' => $hash]]]],
            'html injection source' => [['version' => 1, 'assets' => [['kind' => 'css', 'source' => '<link href=x>', 'path' => 'x.css', 'sha256' => $hash]]]],
            'destination traversal' => [['version' => 1, 'assets' => [['kind' => 'css', 'source' => 'https://platform.test/x.css', 'path' => '../x.css', 'sha256' => $hash]]]],
            'encoded source traversal' => [['version' => 1, 'assets' => [['kind' => 'css', 'source' => 'https://platform.test/%2e%2e/x.css', 'path' => 'x.css', 'sha256' => $hash]]]],
            'invalid kind' => [['version' => 1, 'assets' => [['kind' => 'html', 'source' => 'https://platform.test/x.html', 'path' => 'x.html', 'sha256' => $hash]]]],
            'kind path mismatch' => [['version' => 1, 'assets' => [['kind' => 'js', 'source' => 'https://platform.test/x.css', 'path' => 'x.css', 'sha256' => $hash]]]],
        ];
    }

    public function test_missing_required_asset_fails_without_partial_revision_or_files(): void
    {
        $css = 'body{}';
        Http::fake([
            'https://platform.test/ok.css' => Http::response($css, 200, ['Content-Type' => 'text/css']),
            'https://platform.test/missing.js' => Http::response('', 404),
        ]);
        $manifest = ['version' => 1, 'assets' => [
            $this->descriptor('css', 'https://platform.test/ok.css', 'ok.css', $css),
            $this->descriptor('js', 'https://platform.test/missing.js', 'missing.js', 'missing'),
        ]];

        $this->install(['theme_assets' => $manifest])->assertStatus(422);
        $this->assertDatabaseCount('theme_instance_revisions', 0);
        $this->assertSame([], Storage::disk('public')->allFiles('theme-assets'));
    }

    public function test_integrity_mismatch_fails_closed(): void
    {
        Http::fake(['https://platform.test/x.css' => Http::response('actual', 200, ['Content-Type' => 'text/css'])]);
        $manifest = ['version' => 1, 'assets' => [
            $this->descriptor('css', 'https://platform.test/x.css', 'x.css', 'declared'),
        ]];
        $this->install(['theme_assets' => $manifest])->assertStatus(422);
        $this->assertDatabaseCount('theme_instance_revisions', 0);
    }

    public function test_later_revision_cannot_replace_previous_revision_assets(): void
    {
        Http::fake(function ($request) {
            $body = str_contains($request->url(), 'v2') ? 'body{color:blue}' : 'body{color:red}';

            return Http::response($body, 200, ['Content-Type' => 'text/css']);
        });
        $v1 = 'body{color:red}';
        $v2 = 'body{color:blue}';
        $this->install(['theme_assets' => ['version' => 1, 'assets' => [
            $this->descriptor('css', 'https://platform.test/v1/theme.css', 'css/theme.css', $v1),
        ]]])->assertOk();
        $first = ThemeInstanceRevision::orderBy('id')->firstOrFail();
        $firstAsset = $first->theme_data['theme_assets']['assets'][0];

        $this->install(['theme_assets' => ['version' => 1, 'assets' => [
            $this->descriptor('css', 'https://platform.test/v2/theme.css', 'css/theme.css', $v2),
        ]]])->assertOk();
        $secondAsset = ThemeInstanceRevision::orderByDesc('id')->firstOrFail()->theme_data['theme_assets']['assets'][0];

        $this->assertNotSame($firstAsset['storage_key'], $secondAsset['storage_key']);
        $this->assertSame($v1, Storage::disk('public')->get($firstAsset['storage_key']));
        $this->assertSame($v2, Storage::disk('public')->get($secondAsset['storage_key']));
    }

    public function test_assets_do_not_leak_between_themes(): void
    {
        Http::fake(fn ($request) => Http::response(str_contains($request->url(), '/a.') ? 'a{}' : 'b{}', 200, ['Content-Type' => 'text/css']));
        $this->install(['theme_id' => 'theme-a', 'theme_assets' => ['version' => 1, 'assets' => [
            $this->descriptor('css', 'https://platform.test/a.css', 'a.css', 'a{}'),
        ]]])->assertOk();
        $aKey = ThemeInstanceRevision::where('theme_identifier', 'theme-a')->firstOrFail()->theme_data['theme_assets']['assets'][0]['storage_key'];
        $this->install(['theme_id' => 'theme-b', 'theme_assets' => ['version' => 1, 'assets' => [
            $this->descriptor('css', 'https://platform.test/b.css', 'b.css', 'b{}'),
        ]]])->assertOk();
        $active = json_decode(Setting::get('active_canvas_theme_data'), true);
        $this->assertStringNotContainsString($aKey, json_encode($active));
        $this->assertStringContainsString('theme-b', $active['theme_assets']['assets'][0]['storage_key']);
    }
}
