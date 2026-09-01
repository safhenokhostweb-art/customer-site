<?php

namespace Tests\Unit\ThemeCanvas\Dependency;

use App\Services\ThemeCanvas\Dependency\ThemeDependencyAvailabilityResolver;
use App\Services\ThemeCanvas\Dependency\ThemeDependencyAvailabilityResult;
use App\Services\ThemeCanvas\Dependency\ThemeDependencyPresence;
use PHPUnit\Framework\TestCase;

class ThemeDependencyAvailabilityResolverTest extends TestCase
{
    private function resolverWith(array $adapters): ThemeDependencyAvailabilityResolver
    {
        return new ThemeDependencyAvailabilityResolver($adapters);
    }

    public function test_present_with_no_constraint_is_available(): void
    {
        $adapter = new FakeDependencyResolver('module', ['forms' => ThemeDependencyPresence::present()]);
        $result = $this->resolverWith([$adapter])->check('module', 'forms', null);

        $this->assertSame(ThemeDependencyAvailabilityResult::AVAILABLE, $result->status);
    }

    public function test_absent_is_missing(): void
    {
        $adapter = new FakeDependencyResolver('module', []);
        $result = $this->resolverWith([$adapter])->check('module', 'forms', null);

        $this->assertSame(ThemeDependencyAvailabilityResult::MISSING, $result->status);
    }

    public function test_present_with_satisfied_version_constraint_is_available(): void
    {
        $adapter = new FakeDependencyResolver('motion_asset', ['hero' => ThemeDependencyPresence::present('2.5.0')]);
        $result = $this->resolverWith([$adapter])->check('motion_asset', 'hero', '>=2.0');

        $this->assertSame(ThemeDependencyAvailabilityResult::AVAILABLE, $result->status);
        $this->assertSame('2.5.0', $result->installedVersion);
    }

    public function test_present_with_unsatisfied_version_constraint_is_version_incompatible(): void
    {
        $adapter = new FakeDependencyResolver('motion_asset', ['hero' => ThemeDependencyPresence::present('1.0.0')]);
        $result = $this->resolverWith([$adapter])->check('motion_asset', 'hero', '>=2.0');

        $this->assertSame(ThemeDependencyAvailabilityResult::VERSION_INCOMPATIBLE, $result->status);
        $this->assertSame('1.0.0', $result->installedVersion);
    }

    public function test_present_but_untracked_version_with_a_real_constraint_is_unverifiable(): void
    {
        // Exactly the honest `module` limitation: present, but this type
        // has no installed version to check the constraint against.
        $adapter = new FakeDependencyResolver('module', ['forms' => ThemeDependencyPresence::present(null)]);
        $result = $this->resolverWith([$adapter])->check('module', 'forms', '>=2.0');

        $this->assertSame(ThemeDependencyAvailabilityResult::UNVERIFIABLE, $result->status);
    }

    public function test_unregistered_type_is_unverifiable_never_available(): void
    {
        $result = $this->resolverWith([])->check('icon_pack', 'anything', null);

        $this->assertSame(ThemeDependencyAvailabilityResult::UNVERIFIABLE, $result->status);
    }

    public function test_invalid_constraint_syntax_is_unverifiable(): void
    {
        $adapter = new FakeDependencyResolver('module', ['forms' => ThemeDependencyPresence::present()]);
        $result = $this->resolverWith([$adapter])->check('module', 'forms', '~invalid~');

        $this->assertSame(ThemeDependencyAvailabilityResult::UNVERIFIABLE, $result->status);
    }
}
