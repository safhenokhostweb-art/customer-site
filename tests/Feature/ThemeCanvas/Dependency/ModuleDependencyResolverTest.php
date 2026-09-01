<?php

namespace Tests\Feature\ThemeCanvas\Dependency;

use App\Models\License;
use App\Services\ThemeCanvas\Dependency\ModuleDependencyResolver;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 3E-A — the one real, currently-supported dependency-type adapter.
 * Backed by the exact same product-neutral License.modules/TenantContext
 * mechanism already used app-wide for module gating — nothing new invented.
 */
class ModuleDependencyResolverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('installed.lock'));
        parent::tearDown();
    }

    private function licenseWithModules(array $modules): void
    {
        License::create([
            'license_key' => 'MODULE-DEP-' . uniqid(),
            'domain' => request()->getHost(),
            'platform_valid' => true,
            'expires_at' => now()->addYear(),
            'last_validated_at' => now(),
            'modules' => $modules,
        ]);
    }

    public function test_supports_only_the_module_type(): void
    {
        $resolver = app(ModuleDependencyResolver::class);
        $this->assertTrue($resolver->supports('module'));
        $this->assertFalse($resolver->supports('motion_asset'));
        $this->assertFalse($resolver->supports('icon_pack'));
    }

    public function test_resolves_present_for_an_enabled_module(): void
    {
        $this->licenseWithModules(['shop', 'blog']);
        app(TenantContext::class)->clear();

        $presence = app(ModuleDependencyResolver::class)->resolve('shop');

        $this->assertTrue($presence->present);
        $this->assertNull($presence->installedVersion, 'module entitlements carry no version — never fabricated');
    }

    public function test_resolves_absent_for_a_disabled_module(): void
    {
        $this->licenseWithModules(['shop']);
        app(TenantContext::class)->clear();

        $presence = app(ModuleDependencyResolver::class)->resolve('forms');

        $this->assertFalse($presence->present);
    }

    public function test_resolves_absent_when_there_is_no_valid_license_at_all(): void
    {
        app(TenantContext::class)->clear();

        $presence = app(ModuleDependencyResolver::class)->resolve('shop');

        $this->assertFalse($presence->present);
    }
}
