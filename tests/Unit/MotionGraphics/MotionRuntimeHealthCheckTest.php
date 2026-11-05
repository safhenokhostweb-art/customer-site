<?php

namespace Tests\Unit\MotionGraphics;

use App\Models\License;
use App\Services\MotionGraphics\MotionRuntimeCache;
use App\Services\MotionGraphics\MotionRuntimeHealthCheck;
use App\Services\MotionGraphics\MotionRuntimeManifestReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MotionRuntimeHealthCheckTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('site.platform_url', 'https://platform.test');
        License::create(['license_key' => 'test-license-key-1234', 'domain' => 'customer.test']);
        // License::current() matches the license by the request host (since
        // 0495b2a); serve these reads from the licensed customer.test host.
        $this->app->instance('request', \Illuminate\Http\Request::create('http://customer.test/'));
    }

    private function payload(string $checksum): array
    {
        return [
            'available' => true,
            'schema_version' => '1.0',
            'site_reference' => '5',
            'template_reference' => 'template-1',
            'package_id' => 'pkg-uuid',
            'package_version' => 1,
            'generated_at' => now()->toIso8601String(),
            'assets' => [[
                'asset_id' => 'motion-1', 'published_version' => 1,
                'optimized_reference' => '/motion/motion-1/v1/output.json',
                'checksum' => str_repeat('a', 64), 'asset_type' => 'lottie',
                'theme_tokens' => [], 'placement_rules' => [], 'responsive_rules' => [],
            ]],
            'manifest_checksum' => $checksum,
        ];
    }

    private function healthCheck(): MotionRuntimeHealthCheck
    {
        return new MotionRuntimeHealthCheck(new MotionRuntimeManifestReader(new MotionRuntimeCache()));
    }

    public function test_it_reports_healthy_when_two_reads_agree(): void
    {
        $checksum = str_repeat('a', 64);
        Http::fake(['*' => Http::response($this->payload($checksum))]);

        $result = $this->healthCheck()->check();

        $this->assertTrue($result['healthy']);
        $this->assertSame($checksum, $result['manifest_checksum']);
        $this->assertSame(1, $result['asset_count']);
    }

    public function test_it_reports_unhealthy_when_the_manifest_is_unavailable(): void
    {
        Http::fake(['*' => Http::response(['available' => false])]);

        $result = $this->healthCheck()->check();

        $this->assertFalse($result['healthy']);
        $this->assertSame('manifest_unavailable', $result['reason']);
    }

    public function test_it_reports_unhealthy_when_two_reads_disagree(): void
    {
        $sequence = Http::sequence()
            ->push($this->payload(str_repeat('a', 64)))
            ->push($this->payload(str_repeat('b', 64)));
        Http::fake(['*' => $sequence]);

        $result = $this->healthCheck()->check();

        $this->assertFalse($result['healthy']);
        $this->assertSame('checksum_unstable', $result['reason']);
    }

    public function test_it_reports_unhealthy_for_an_empty_manifest(): void
    {
        $payload = $this->payload(str_repeat('a', 64));
        $payload['assets'] = [];
        Http::fake(['*' => Http::response($payload)]);

        $result = $this->healthCheck()->check();

        $this->assertFalse($result['healthy']);
        $this->assertSame('empty_manifest', $result['reason']);
    }
}
