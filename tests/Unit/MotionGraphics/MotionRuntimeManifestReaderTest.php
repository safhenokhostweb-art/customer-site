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
}
