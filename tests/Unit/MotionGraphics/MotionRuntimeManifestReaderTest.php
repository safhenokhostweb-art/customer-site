<?php

namespace Tests\Unit\MotionGraphics;

use App\Models\License;
use App\Services\MotionGraphics\MotionRuntimeCache;
use App\Services\MotionGraphics\MotionRuntimeManifestReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MotionRuntimeManifestReaderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('site.platform_url', 'https://platform.test');
        Cache::flush();
        License::create(['license_key' => 'test-license-key-1234', 'domain' => 'customer.test']);
        // License::current() matches the license by the request host (since
        // 0495b2a); serve these reads from the licensed customer.test host.
        $this->app->instance('request', \Illuminate\Http\Request::create('http://customer.test/'));
    }

    private function validManifestPayload(array $overrides = []): array
    {
        return array_merge([
            'available' => true,
            'schema_version' => '1.0',
            'site_reference' => '5',
            'template_reference' => 'template-1',
            'package_id' => 'pkg-uuid',
            'package_version' => 1,
            'generated_at' => now()->toIso8601String(),
            'assets' => [[
                'asset_id' => 'motion-1',
                'published_version' => 1,
                'optimized_reference' => '/motion/motion-1/v1/output.json',
                'checksum' => str_repeat('a', 64),
                'asset_type' => 'lottie',
                'theme_tokens' => [],
                'placement_rules' => [],
                'responsive_rules' => [],
            ]],
            'manifest_checksum' => str_repeat('b', 64),
        ], $overrides);
    }

    private function reader(): MotionRuntimeManifestReader
    {
        return new MotionRuntimeManifestReader(new MotionRuntimeCache());
    }

    public function test_runtime_parameters_are_normalized_and_malformed_ones_dropped(): void
    {
        $payload = $this->validManifestPayload();
        $payload['assets'][0]['parameters'] = [
            ['key' => 'window_title', 'type' => 'text', 'label' => 'عنوان', 'default' => 'داشبورد', 'max' => 999],
            ['key' => 'Bad Key', 'type' => 'text', 'label' => 'x', 'default' => 'x'],
            ['key' => 'html', 'type' => 'raw_html', 'label' => 'x', 'default' => '<b>'],
        ];
        Http::fake(['*' => Http::response($payload)]);

        $manifest = $this->reader()->readFresh();

        $this->assertNotNull($manifest, 'a malformed parameter never rejects the whole manifest');
        $this->assertSame(['window_title'], array_column($manifest['assets'][0]['parameters'], 'key'));
        $this->assertSame(80, $manifest['assets'][0]['parameters'][0]['max'], 'max is capped');
    }

    public function test_it_accepts_a_valid_manifest_and_resolves_the_reference(): void
    {
        Http::fake(['*' => Http::response($this->validManifestPayload())]);

        $manifest = $this->reader()->read();

        $this->assertNotNull($manifest);
        $this->assertSame('https://platform.test/motion/motion-1/v1/output.json', $manifest['assets'][0]['full_reference']);
    }

    public function test_it_returns_null_when_no_license_exists(): void
    {
        License::query()->delete();
        Http::fake(['*' => Http::response($this->validManifestPayload())]);

        $this->assertNull($this->reader()->read());
    }

    public function test_it_returns_null_when_platform_reports_unavailable(): void
    {
        Http::fake(['*' => Http::response(['available' => false, 'reason' => 'motion_adapter_disabled'])]);

        $this->assertNull($this->reader()->read());
    }

    public function test_it_rejects_an_unsupported_schema_version(): void
    {
        Http::fake(['*' => Http::response($this->validManifestPayload(['schema_version' => '99.0']))]);

        $this->assertNull($this->reader()->read());
    }

    public function test_it_rejects_a_malformed_checksum(): void
    {
        $payload = $this->validManifestPayload();
        $payload['assets'][0]['checksum'] = 'not-a-checksum';
        Http::fake(['*' => Http::response($payload)]);

        $this->assertNull($this->reader()->read());
    }

    public function test_it_rejects_an_absolute_remote_optimized_reference(): void
    {
        $payload = $this->validManifestPayload();
        $payload['assets'][0]['optimized_reference'] = 'https://evil.example.com/payload.js';
        Http::fake(['*' => Http::response($payload)]);

        $this->assertNull($this->reader()->read());
    }

    public function test_it_rejects_a_protocol_relative_optimized_reference(): void
    {
        $payload = $this->validManifestPayload();
        $payload['assets'][0]['optimized_reference'] = '//evil.example.com/payload.js';
        Http::fake(['*' => Http::response($payload)]);

        $this->assertNull($this->reader()->read());
    }

    public function test_it_rejects_an_unknown_asset_type(): void
    {
        $payload = $this->validManifestPayload();
        $payload['assets'][0]['asset_type'] = 'executable';
        Http::fake(['*' => Http::response($payload)]);

        $this->assertNull($this->reader()->read());
    }

    public function test_it_rejects_a_manifest_carrying_a_forbidden_field(): void
    {
        $payload = $this->validManifestPayload();
        $payload['assets'][0]['ai_metadata'] = ['prompt' => 'draw a cat'];
        Http::fake(['*' => Http::response($payload)]);

        $this->assertNull($this->reader()->read());
    }

    public function test_it_rejects_an_invalid_asset_id_pattern(): void
    {
        $payload = $this->validManifestPayload();
        $payload['assets'][0]['asset_id'] = '<script>alert(1)</script>';
        Http::fake(['*' => Http::response($payload)]);

        $this->assertNull($this->reader()->read());
    }

    public function test_a_failed_http_response_returns_null_not_an_exception(): void
    {
        Http::fake(['*' => Http::response(['error' => 'server_error'], 500)]);

        $this->assertNull($this->reader()->read());
    }

    public function test_the_response_is_cached_across_reads(): void
    {
        $sequence = Http::sequence()
            ->push($this->validManifestPayload())
            ->push($this->validManifestPayload(['manifest_checksum' => str_repeat('c', 64)]));
        Http::fake(['*' => $sequence]);

        $first = $this->reader()->read();
        $second = $this->reader()->read();

        $this->assertSame($first['manifest_checksum'], $second['manifest_checksum']);
    }

    public function test_read_fresh_bypasses_the_cache(): void
    {
        $sequence = Http::sequence()
            ->push($this->validManifestPayload(['manifest_checksum' => str_repeat('a', 64)]))
            ->push($this->validManifestPayload(['manifest_checksum' => str_repeat('c', 64)]));
        Http::fake(['*' => $sequence]);

        $first = $this->reader()->readFresh();
        $second = $this->reader()->readFresh();

        $this->assertNotSame($first['manifest_checksum'], $second['manifest_checksum']);
    }

    // ── saas-platform ADR-086: release identifiers + instant refresh ─────

    public function test_version_label_build_id_and_category_label_pass_only_when_well_formed(): void
    {
        $payload = $this->validManifestPayload();
        $payload['assets'][0] += ['version_label' => '1.0.0', 'build_id' => 'abcdef012345', 'category_label' => 'موشن صنعت برق و انرژی'];
        $payload['assets'][] = [...$payload['assets'][0], 'asset_id' => 'motion-2',
            'version_label' => '<script>', 'build_id' => 'XYZ', 'category_label' => '<b>x</b>'];
        Http::fake(['*' => Http::response($payload)]);

        $manifest = $this->reader()->readFresh();

        $this->assertNotNull($manifest, 'a malformed display field never rejects the manifest');
        $this->assertSame('1.0.0', $manifest['assets'][0]['version_label']);
        $this->assertSame('abcdef012345', $manifest['assets'][0]['build_id']);
        $this->assertSame('موشن صنعت برق و انرژی', $manifest['assets'][0]['category_label']);
        $this->assertNull($manifest['assets'][1]['version_label']);
        $this->assertNull($manifest['assets'][1]['build_id']);
        $this->assertNull($manifest['assets'][1]['category_label']);
    }

    public function test_a_platform_that_predates_the_fields_yields_nulls(): void
    {
        Http::fake(['*' => Http::response($this->validManifestPayload())]);

        $asset = $this->reader()->readFresh()['assets'][0];

        $this->assertNull($asset['version_label']);
        $this->assertNull($asset['build_id']);
        $this->assertNull($asset['category_label']);
    }

    public function test_refresh_replaces_the_cached_manifest_with_the_platform_answer(): void
    {
        $two = $this->validManifestPayload();
        $two['assets'][] = [...$two['assets'][0], 'asset_id' => 'motion-2'];
        Http::fakeSequence()
            ->push($two)
            ->push($this->validManifestPayload());

        $this->assertCount(2, $this->reader()->read()['assets']);

        // An admin switched motion-2 off: the refresh sees it at once and the
        // cached copy (what the public runtime reads) follows.
        $this->assertCount(1, $this->reader()->refresh()['assets']);
        $this->assertCount(1, $this->reader()->read()['assets']);
    }

    public function test_refresh_clears_the_cache_when_the_platform_says_unavailable(): void
    {
        Http::fakeSequence()
            ->push($this->validManifestPayload())
            ->push(['available' => false, 'reason' => 'no_active_package']);

        $this->assertNotNull($this->reader()->read());
        $this->assertNull($this->reader()->refresh());
        $this->assertNull(Cache::get('motion.runtime.manifest'));
    }

    public function test_refresh_keeps_the_last_good_manifest_when_the_platform_is_unreachable_or_invalid(): void
    {
        Http::fakeSequence()
            ->push($this->validManifestPayload())
            ->push('upstream error', 502)
            ->push(['available' => true, 'schema_version' => '9.9']);

        $this->assertNotNull($this->reader()->read());
        $this->assertCount(1, $this->reader()->refresh()['assets'], 'a 5xx keeps the cached copy');
        $this->assertCount(1, $this->reader()->refresh()['assets'], 'an invalid answer keeps the cached copy');
    }
}
