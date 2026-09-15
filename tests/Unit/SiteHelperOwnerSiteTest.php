<?php

namespace Tests\Unit;

use App\Helpers\Module;
use App\Helpers\SiteHelper;
use Tests\TestCase;

class SiteHelperOwnerSiteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['site.owner_site' => false]);
        $this->resetModuleCache();
    }

    private function resetModuleCache(): void
    {
        $r = new \ReflectionClass(Module::class);
        $p = $r->getProperty('resolved');
        $p->setAccessible(true);
        $p->setValue(null);
    }

    public function test_is_owner_site_returns_true_when_config_set(): void
    {
        config(['site.owner_site' => true]);
        $this->assertTrue(SiteHelper::isOwnerSite());
    }

    public function test_is_owner_site_returns_false_when_config_not_set(): void
    {
        config(['site.owner_site' => false]);
        $this->assertFalse(SiteHelper::isOwnerSite());
    }

    // TEST-HARNESS-DB-01: the test's own name is the real invariant —
    // "blog enabled equals [config] module [state] when owner site" — but
    // the assertion hardcoded `assertFalse()`, silently assuming
    // Module::enabled('blog') defaults to false. phpunit.xml sets
    // MODULE_BLOG_ENABLED=true globally (other tests depend on that
    // default), which this test's hardcoded expectation never accounted
    // for. SiteHelper::moduleEnabledFromLicenseOrConfig() for an owner site
    // is `moduleEnabledFromProjection($key) || Module::enabled($key)`; with
    // no valid License in this test (the projection half is always false
    // for blog/shop without one — see moduleEnabledFromProjection()'s own
    // license-only rule), the result reduces to exactly Module::enabled(),
    // whatever that resolves to under the current environment — not
    // production behavior, no override needed.
    public function test_blog_enabled_equals_module_when_owner_site(): void
    {
        config(['site.owner_site' => true]);
        $this->resetModuleCache();
        $this->assertSame(Module::enabled('blog'), SiteHelper::blogEnabled());
    }

    public function test_shop_enabled_equals_module_when_owner_site(): void
    {
        config(['site.owner_site' => true]);
        $this->resetModuleCache();
        $this->assertSame(Module::enabled('shop'), SiteHelper::shopEnabled());
    }
}
