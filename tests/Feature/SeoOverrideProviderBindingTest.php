<?php

namespace Tests\Feature;

use App\Services\Seo\NullSeoOverrideProvider;
use SaaSPlatform\CoreUtils\Contracts\Services\SeoOverrideProviderInterface;
use SaaSPlatform\Modules\SeoAdvanced\Adapters\SeoOverrideProviderAdapter;
use Tests\TestCase;

/**
 * The generic SEO extension point must resolve to a module's adapter when one
 * is installed, and to the Null Object otherwise.
 *
 * AppServiceProvider used a plain `bind()` for the Null Object default. Laravel
 * splices package-discovered providers at index 1 of the provider list — before
 * the app's own providers — so that `bind()` ran LAST and overwrote whatever an
 * installed module had bound. SEO Advanced was installed, its provider was
 * loaded, its adapter was registered, and none of it ever reached a page: every
 * render resolved the Null Object.
 */
class SeoOverrideProviderBindingTest extends TestCase
{
    public function test_the_contract_is_always_bound(): void
    {
        $this->assertTrue(
            app()->bound(SeoOverrideProviderInterface::class),
            'SeoOverrideProviderInterface must always be bound, by a module or by the host default.'
        );
    }

    public function test_an_installed_seo_module_wins_over_the_host_default(): void
    {
        $bound = get_class(app(SeoOverrideProviderInterface::class));

        if (! class_exists(SeoOverrideProviderAdapter::class)) {
            $this->assertSame(NullSeoOverrideProvider::class, $bound);
            $this->markTestSkipped('SEO Advanced is not installed in this environment; the Null Object is correct.');
        }

        $this->assertInstanceOf(
            SeoOverrideProviderAdapter::class,
            app(SeoOverrideProviderInterface::class),
            'An installed SEO Advanced package must win the contract; the host '
            . 'default must only fill in when nothing else has bound it.'
        );
    }

    public function test_the_null_object_default_remains_the_fallback(): void
    {
        // A runtime with no SEO module must still get a working, inert provider.
        $provider = app()->makeWith(NullSeoOverrideProvider::class, []);

        $this->assertNull($provider->overrideFor());
        $this->assertSame([], $provider->extraJsonLd());
    }
}
