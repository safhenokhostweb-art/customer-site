<?php

namespace App\Services\ThemeCanvas\Dependency;

use App\Services\TenantContext;

/**
 * Phase 3E-A — the one currently-real dependency-type adapter: `module`,
 * backed by App\Services\TenantContext::hasModule(), the exact same
 * product-neutral module-entitlement check already used app-wide (admin
 * menus, module controllers, the page builder — see TenantContext.php and
 * its many existing callers). Nothing new was invented for this.
 *
 * Honest limitation, not silently glossed over: License.modules is a flat
 * list of enabled module slugs with no per-module installed version
 * anywhere in this codebase's data model. This adapter can therefore only
 * ever prove presence, never a version — ThemeDependencyAvailabilityResolver
 * correctly reports UNVERIFIABLE (not a guessed pass) whenever a `module`
 * dependency declares a real version_constraint, since there is nothing to
 * check it against.
 */
final class ModuleDependencyResolver implements ThemeDependencyTypeResolverContract
{
    public const TYPE = 'module';

    public function __construct(private TenantContext $tenant)
    {
    }

    public function supports(string $dependencyType): bool
    {
        return $dependencyType === self::TYPE;
    }

    public function resolve(string $dependencyIdentifier): ThemeDependencyPresence
    {
        return $this->tenant->hasModule($dependencyIdentifier)
            ? ThemeDependencyPresence::present(installedVersion: null)
            : ThemeDependencyPresence::absent();
    }
}
