<?php

namespace Tests\Feature;

use App\Models\License;
use App\Models\User;
use App\Services\PlatformApiService;
use App\Services\ThemeMarketplacePresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression coverage for the Production incident: my-licenses returned a
 * real HTTP 500 ("Undefined array key installed_theme_version") for every
 * theme, because ThemeMarketplacePresenter accessed a legacy field the
 * Platform API's real contract (ThemeMarketplaceApiController::myLicenses())
 * has fully retired and never sends. These tests go through the real
 * HTTP/auth/middleware stack — not a direct controller call — so a Blade
 * compile/render error surfaces exactly as it would in Production, and use
 * the exact shape that controller emits today: only `installed_version` /
 * `current_version`, present but possibly null, legacy keys entirely
 * absent — plus deliberately legacy/partial shapes to prove the fix is
 * general, not a one-theme patch.
 */
class ThemeMarketplaceMyLicensesContractTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        License::create([
            'license_key' => 'MLC-' . uniqid(),
            'domain' => request()->getHost(),
            'platform_valid' => true,
            'expires_at' => now()->addYear(),
            'last_validated_at' => now(),
            'modules' => [],
        ]);

        return User::factory()->create(['is_admin' => true]);
    }

    private function fetchMyLicenses(array $licenses)
    {
        $platform = $this->mock(PlatformApiService::class);
        $platform->shouldReceive('isConfigured')->once()->andReturnTrue();
        $platform->shouldReceive('marketplaceMyLicenses')->once()->andReturn($licenses);

        return $this->actingAs($this->admin())->get(route('admin.theme-marketplace.my-licenses'));
    }

    /** Exact shape ThemeMarketplaceApiController::myLicenses() sends for a theme that was never installed. */
    private function realUninstalledLicense(int $themeId): array
    {
        return [
            'license_key'           => 'LIC-UNINSTALLED-' . $themeId,
            'purchased_at'          => '2026-09-01',
            'is_permanent'          => true,
            'expires_at'            => null,
            'installed_version'     => null,
            'is_outdated_by_ledger' => false,
            'theme' => [
                'id' => $themeId, 'name' => 'Theme ' . $themeId, 'slug' => 'theme-' . $themeId,
                'thumbnail' => null, 'category' => 'landing', 'version_notes' => null,
                'current_version' => '1.4.0',
            ],
        ];
    }

    /** Exact shape for a theme that IS installed. */
    private function realInstalledLicense(int $themeId, string $installedVersion, string $currentVersion): array
    {
        return [
            'license_key'           => 'LIC-INSTALLED-' . $themeId,
            'purchased_at'          => '2026-08-01',
            'is_permanent'          => true,
            'expires_at'            => null,
            'installed_version'     => $installedVersion,
            'is_outdated_by_ledger' => $installedVersion !== $currentVersion,
            'theme' => [
                'id' => $themeId, 'name' => 'Theme ' . $themeId, 'slug' => 'theme-' . $themeId,
                'thumbnail' => null, 'category' => 'landing', 'version_notes' => null,
                'current_version' => $currentVersion,
            ],
        ];
    }

    public function test_page_renders_without_a_500_for_two_valid_licenses(): void
    {
        $response = $this->fetchMyLicenses([
            $this->realUninstalledLicense(1),
            $this->realInstalledLicense(2, '2.0.0', '2.0.0'),
        ]);

        $response->assertOk();
        $response->assertSee('Theme 1');
        $response->assertSee('Theme 2');
    }

    public function test_uninstalled_theme_with_no_legacy_key_does_not_crash_and_shows_current_version(): void
    {
        $this->assertNull(ThemeMarketplacePresenter::installedVersion($this->realUninstalledLicense(1)));

        $response = $this->fetchMyLicenses([$this->realUninstalledLicense(1)]);

        $response->assertOk();
        $response->assertSee('نسخه 1.4.0');
    }

    public function test_installed_theme_shows_its_real_installed_version(): void
    {
        $response = $this->fetchMyLicenses([$this->realInstalledLicense(5, '1.0.0', '2.0.0')]);

        $response->assertOk();
        $response->assertSee('نصب‌شده 1.0.0');
        $response->assertSee('جدید 2.0.0');
    }

    public function test_legacy_only_api_response_without_the_new_field_still_resolves_a_version(): void
    {
        // A not-yet-upgraded Platform response: only the retired field is present.
        $license = $this->realUninstalledLicense(9);
        unset($license['installed_version']);
        $license['installed_theme_version'] = 3;

        $this->assertSame('3', ThemeMarketplacePresenter::installedVersion($license));

        $response = $this->fetchMyLicenses([$license]);
        $response->assertOk();
        $response->assertSee('Theme 9');
    }

    public function test_incomplete_api_response_missing_optional_version_fields_does_not_crash(): void
    {
        // Realistic "incomplete" shape: the fields this bug is about are
        // missing entirely, while the fields the API always guarantees
        // (license_key, is_permanent, theme.*) are still present — an
        // Undefined-array-key crash on any of THOSE would be a separate,
        // pre-existing bug outside this fix's scope.
        $response = $this->fetchMyLicenses([[
            'license_key'  => 'LIC-MINIMAL',
            'purchased_at' => null,
            'is_permanent' => true,
            'expires_at'   => null,
            'theme'        => ['id' => 42, 'name' => 'Bare Theme', 'slug' => 'bare-theme'],
        ]]);

        $response->assertOk();
        $response->assertSee('Bare Theme');
    }

    public function test_multiple_themes_with_mixed_install_states_render_together(): void
    {
        $response = $this->fetchMyLicenses([
            $this->realUninstalledLicense(1),
            $this->realInstalledLicense(2, '2.0.0', '2.0.0'),
            $this->realInstalledLicense(3, '1.0.0', '1.5.0'),
        ]);

        $response->assertOk();
        $response->assertSee('Theme 1');
        $response->assertSee('Theme 2');
        $response->assertSee('Theme 3');
        $response->assertSee('نصب‌شده 1.0.0');
        $response->assertSee('جدید 1.5.0');
    }

    public function test_presenter_never_throws_for_totally_empty_arrays(): void
    {
        $this->assertNull(ThemeMarketplacePresenter::installedVersion([]));
        $this->assertNull(ThemeMarketplacePresenter::currentVersion([]));
        $this->assertNull(ThemeMarketplacePresenter::currentVersion(null));
    }
}
