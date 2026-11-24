<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\License;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * TASK 3/3 Phase 6.5 — permanent regression guard for the platform-vs-
 * customer commercial authority boundary (PROJECT_CONSTITUTION.md, ADR-020,
 * ADR-025). A full read-only audit of this repo (routes/web.php, every
 * Theme-related controller, AdminMenu.php) found ZERO customer-facing
 * surfaces that create/publish/release a master Theme product — a prior
 * parallel authority (ThemePageProvisionService::provision()) was already
 * removed in an earlier pass (see that class's own docblock and the
 * removal comment at routes/web.php around the builder.api.theme.* group).
 *
 * This test does not re-run that audit; it codifies its conclusion as a
 * permanent, automated fitness check so a future change cannot silently
 * reintroduce a parallel master-authoring path without a test failing.
 */
class NoParallelMasterThemeAuthoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_local_theme_or_theme_release_eloquent_model_exists_in_this_repo(): void
    {
        self::assertFalse(class_exists(\App\Models\Theme::class), 'customer-site must never own a local master Theme model — Theme authoring belongs to saas-platform');
        self::assertFalse(class_exists(\App\Models\ThemeRelease::class), 'customer-site must never own a local immutable ThemeRelease ledger — that belongs to saas-platform');
        self::assertFalse(class_exists(\App\Models\ThemeLicense::class), 'customer-site must never mint its own Theme licenses — licenses are granted by saas-platform and received, not created, here');
    }

    public function test_no_registered_route_accepts_a_theme_creation_or_publication_request(): void
    {
        // admin/theme-design/canvas/publish and create-blank are REQUIRED, CANONICAL_CUSTOMER_INSTANCE
        // actions (Admin\ThemeDesignController) — they manage THIS site's own
        // canvas state (publishing a draft or seeding a blank baseline canvas).
        // They never create a master Theme, ThemeRelease, or anything saas-platform's
        // own catalog would see — see Phase 6.5's audit for the traced
        // controller behavior. Explicitly allow-listed here, not excluded
        // by accident.
        $knownLegitimateCustomerInstancePublish = [
            'admin/theme-design/canvas/publish',
            'admin/theme-design/canvas/create-blank',
        ];

        $suspiciousActions = collect(Route::getRoutes())->filter(function ($route) use ($knownLegitimateCustomerInstancePublish) {
            $uri = strtolower($route->uri());
            $methods = $route->methods();

            if (in_array($route->uri(), $knownLegitimateCustomerInstancePublish, true)) {
                return false;
            }

            $looksLikeThemeCreationOrPublish = (bool) preg_match('/theme[-_]?(builder|design|marketplace|bundle)?.*(create|publish|release)/', $uri)
                || (bool) preg_match('/(create|publish|release).*theme/', $uri);

            return in_array('POST', $methods, true) && $looksLikeThemeCreationOrPublish;
        });

        self::assertCount(
            0,
            $suspiciousActions,
            'Found a POST route matching a Theme create/publish/release pattern: ' . $suspiciousActions->map(fn ($r) => $r->uri())->implode(', ')
        );
    }

    public function test_the_only_theme_install_mutation_authority_is_the_single_provision_secret_gated_endpoint(): void
    {
        $installRoutes = collect(Route::getRoutes())->filter(
            fn ($route) => str_contains($route->uri(), 'theme/install') && in_array('POST', $route->methods(), true)
        );

        self::assertCount(1, $installRoutes, 'THEME_INSTALL_MUTATION_AUTHORITIES must stay exactly 1 (see routes/web.php\'s own ADR-052 comment)');

        $route = $installRoutes->first();
        self::assertContains('provision.secret', $route->middleware(), 'the sole install authority must stay gated by the shared-secret middleware, never open to a normal authenticated admin session');
    }

    public function test_a_normal_authenticated_tenant_admin_session_cannot_call_the_install_endpoint_without_the_provision_secret(): void
    {
        config(['services.customer_provision_secret' => 'fitness-test-secret']);
        License::create([
            'license_key' => 'FITNESS-' . uniqid(),
            'domain' => request()->getHost(),
            'platform_valid' => true,
            'expires_at' => now()->addYear(),
            'last_validated_at' => now(),
            'modules' => ['shop', 'blog'],
        ]);
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->postJson('/builder/api/theme/install', [
            'theme_id' => 'attacker-theme', 'theme_name' => 'Attacker Theme', 'pages' => [],
        ]);

        $response->assertStatus(403);
        self::assertNull(Setting::where('key', 'active_canvas_theme_data')->first()?->value ?: null);
    }
}
