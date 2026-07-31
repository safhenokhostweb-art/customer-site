<?php

namespace Tests\Unit;

use App\Services\SeoService;
use Illuminate\Support\Facades\View;
use SaaSPlatform\CoreUtils\Contracts\Services\SeoOverrideProviderInterface;
use Tests\TestCase;

/**
 * Phase 18-B — Part 5: SeoService + theme-injection scenarios.
 *
 * "چند Theme مختلف" is exercised via a throwaway fixture theme
 * (themes/_phase18b_fixture/layout.blade.php) rather than a hypothetical
 * second real theme — this repo only ships one ("default") today, but the
 * composer is registered on the wildcard pattern 'themes.*.layout', so
 * this proves it genuinely generalizes rather than being hardcoded to
 * 'default'.
 */
class SeoServiceOverrideTest extends TestCase
{
    private string $fixtureThemePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixtureThemePath = resource_path('views/themes/_phase18b_fixture');
        if (!is_dir($this->fixtureThemePath)) {
            mkdir($this->fixtureThemePath, 0755, true);
        }
        file_put_contents($this->fixtureThemePath . '/layout.blade.php', "<html><head>@stack('json-ld')</head><body>fixture</body></html>\n");
    }

    protected function tearDown(): void
    {
        @unlink($this->fixtureThemePath . '/layout.blade.php');
        @rmdir($this->fixtureThemePath);
        parent::tearDown();
    }

    // ─── Scenario: no SEO module installed → no error, existing behavior ──

    public function test_seo_service_works_unchanged_when_no_module_is_bound(): void
    {
        $service = app(SeoService::class);

        // Must not throw, and must return the same kind of defaults it
        // always has — proves the Null Object default is safe.
        $this->assertIsString($service->title());
        $this->assertIsString($service->description());
        $this->assertIsString($service->robots());
        $this->assertIsString($service->canonical());
        $this->assertIsString($service->twitterCard());
    }

    // ─── Scenario: meta renders from a bound override provider ────────────

    public function test_seo_service_uses_bound_override_for_every_field(): void
    {
        $this->app->bind(SeoOverrideProviderInterface::class, function () {
            return new class implements SeoOverrideProviderInterface {
                public function overrideFor(): ?array
                {
                    return [
                        'title' => 'عنوان از ماژول', 'description' => 'توضیح از ماژول',
                        'robots' => 'noindex,nofollow', 'canonical' => 'https://example.com/override',
                        'ogImage' => 'https://example.com/override.png', 'twitterCard' => 'summary_large_image',
                    ];
                }
                public function extraJsonLd(): array { return []; }
            };
        });

        $service = app(SeoService::class);

        $this->assertSame('عنوان از ماژول', $service->title());
        $this->assertStringContainsString('توضیح از ماژول', $service->description());
        $this->assertSame('noindex,nofollow', $service->robots());
        $this->assertSame('https://example.com/override', $service->canonical());
        $this->assertSame('https://example.com/override.png', $service->imageUrl());
        $this->assertSame('summary_large_image', $service->twitterCard());
    }

    public function test_model_level_meta_still_wins_over_module_override(): void
    {
        $this->app->bind(SeoOverrideProviderInterface::class, function () {
            return new class implements SeoOverrideProviderInterface {
                public function overrideFor(): ?array { return ['title' => 'از ماژول']; }
                public function extraJsonLd(): array { return []; }
            };
        });

        $model = (object) ['meta_title' => 'عنوان خودِ صفحه'];

        $this->assertSame('عنوان خودِ صفحه', app(SeoService::class)->title($model));
    }

    // ─── Scenario: no error when the provider itself misbehaves ────────────

    public function test_no_error_when_override_provider_throws(): void
    {
        $this->app->bind(SeoOverrideProviderInterface::class, function () {
            return new class implements SeoOverrideProviderInterface {
                public function overrideFor(): ?array { throw new \RuntimeException('boom'); }
                public function extraJsonLd(): array { throw new \RuntimeException('boom'); }
            };
        });

        $service = app(SeoService::class);

        $this->assertIsString($service->title());
        $this->assertIsString($service->robots());
    }

    // ─── Scenario: extra JSON-LD renders into the existing stack, any theme ──

    public function test_extra_json_ld_is_pushed_into_the_stack_for_any_theme_matching_the_wildcard(): void
    {
        $this->app->bind(SeoOverrideProviderInterface::class, function () {
            return new class implements SeoOverrideProviderInterface {
                public function overrideFor(): ?array { return null; }
                public function extraJsonLd(): array
                {
                    return [['@context' => 'https://schema.org', '@type' => 'LocalBusiness', 'name' => 'فروشگاه تست']];
                }
            };
        });

        $html = View::make('themes._phase18b_fixture.layout')->render();

        $this->assertStringContainsString('application/ld+json', $html);
        $this->assertStringContainsString('LocalBusiness', $html);
        $this->assertStringContainsString('فروشگاه تست', $html);
    }

    public function test_nothing_is_pushed_when_no_module_contributes_extra_json_ld(): void
    {
        $html = View::make('themes._phase18b_fixture.layout')->render();

        $this->assertStringNotContainsString('application/ld+json', $html);
    }

    // ─── Scenario: no impact on other modules ──────────────────────────────

    public function test_forms_module_service_still_resolves_normally(): void
    {
        // Binding/rebinding SeoOverrideProviderInterface must not disturb
        // unrelated container bindings from other modules.
        $this->app->bind(SeoOverrideProviderInterface::class, function () {
            return new class implements SeoOverrideProviderInterface {
                public function overrideFor(): ?array { return ['title' => 'x']; }
                public function extraJsonLd(): array { return []; }
            };
        });

        $this->assertInstanceOf(
            \SaaSPlatform\Modules\Forms\Contracts\FormServiceInterface::class,
            app(\SaaSPlatform\Modules\Forms\Contracts\FormServiceInterface::class)
        );
    }
}
