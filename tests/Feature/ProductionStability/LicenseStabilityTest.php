<?php

namespace Tests\Feature\ProductionStability;

use App\Models\License;
use App\Services\LicenseActivationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LicenseStabilityTest extends TestCase
{
    use RefreshDatabase;

    private bool $installedLockExisted;
    private string|false $installedLockContents;

    protected function setUp(): void
    {
        parent::setUp();
        $this->installedLockExisted = file_exists(storage_path('installed.lock'));
        $this->installedLockContents = $this->installedLockExisted
            ? file_get_contents(storage_path('installed.lock'))
            : false;
        file_put_contents(storage_path('installed.lock'), '1');
        config([
            'site.platform_url' => 'https://platform.example',
            'license.connect_timeout_seconds' => 2,
            'license.sync_timeout_seconds' => 5,
        ]);
        Cache::flush();
    }

    protected function tearDown(): void
    {
        if ($this->installedLockExisted) {
            file_put_contents(storage_path('installed.lock'), (string) $this->installedLockContents);
        } else {
            @unlink(storage_path('installed.lock'));
        }
        parent::tearDown();
    }

    public function test_valid_cache_survives_platform_unavailable_without_rendering_warning(): void
    {
        $license = $this->validLicense();
        Http::fake(['*' => Http::response(['error' => 'server_error'], 503)]);

        $result = app(LicenseActivationService::class)->syncWithPlatform($license, 'customer-site.test');

        $this->assertTrue($result['valid']);
        $this->assertTrue($license->fresh()->platform_valid);
        $this->assertStringNotContainsString('admin-flash--warning', $this->bannerHtml());
    }

    public function test_timeout_keeps_valid_cached_license(): void
    {
        $license = $this->validLicense();
        Http::fake(fn () => throw new ConnectionException('connect timeout'));

        $result = app(LicenseActivationService::class)->syncWithPlatform($license, 'customer-site.test');

        $this->assertTrue($result['valid']);
        $this->assertTrue($license->fresh()->platform_valid);
    }

    public function test_dns_failure_keeps_valid_cached_license(): void
    {
        $license = $this->validLicense();
        Http::fake(fn () => throw new ConnectionException('Could not resolve host'));

        $result = app(LicenseActivationService::class)->syncWithPlatform($license, 'customer-site.test');

        $this->assertTrue($result['valid']);
        $this->assertTrue($license->fresh()->platform_valid);
    }

    public function test_http_401_is_transport_failure_and_request_has_bearer_and_body_fallback(): void
    {
        $license = $this->validLicense();
        Http::fake(['*' => Http::response(['error' => 'missing_credentials'], 401)]);

        $result = app(LicenseActivationService::class)->syncWithPlatform($license, 'customer-site.test');

        $this->assertTrue($result['valid']);
        $this->assertTrue($license->fresh()->platform_valid);
        $this->assertStringNotContainsString('admin-flash--warning', $this->bannerHtml());
        Http::assertSent(function (Request $request) use ($license): bool {
            return $request->hasHeader('Authorization', 'Bearer ' . $license->license_key)
                && $request['license_key'] === $license->license_key
                && $request['domain'] === 'customer-site.test';
        });
    }

    public function test_expired_platform_response_invalidates_cached_license(): void
    {
        $license = $this->validLicense();
        Http::fake(['*' => Http::response(['valid' => false, 'error' => 'expired'], 200)]);

        $result = app(LicenseActivationService::class)->syncWithPlatform($license, 'customer-site.test');

        $this->assertFalse($result['valid']);
        $this->assertFalse($license->fresh()->platform_valid);
    }

    public function test_revoked_platform_response_invalidates_cached_license(): void
    {
        $license = $this->validLicense();
        Http::fake(['*' => Http::response(['valid' => false, 'error' => 'revoked'], 200)]);

        $result = app(LicenseActivationService::class)->syncWithPlatform($license, 'customer-site.test');

        $this->assertFalse($result['valid']);
        $this->assertFalse($license->fresh()->platform_valid);
    }

    private function validLicense(): License
    {
        return License::create([
            'license_key' => 'STABILITY-KEY-XXXX-YYYY',
            'domain' => 'customer-site.test',
            'platform_valid' => true,
            'expires_at' => now()->addYear(),
            'modules' => ['blog'],
        ]);
    }

    private function bannerHtml(): string
    {
        return view('admin.partials.license-transient-banner')->render();
    }
}
