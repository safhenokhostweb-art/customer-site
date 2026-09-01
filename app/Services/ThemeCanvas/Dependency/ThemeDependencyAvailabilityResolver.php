<?php

namespace App\Services\ThemeCanvas\Dependency;

/**
 * Phase 3E-A — the generic availability check every dependency type shares:
 * find the registered adapter for this dependency_type, ask it for the raw
 * presence fact, then apply the ONE centralized version-constraint policy
 * uniformly (never duplicated per adapter, so every type is judged by the
 * same rule). An unregistered type is UNVERIFIABLE, never silently
 * AVAILABLE — this is the product-neutral boundary: this class and
 * everything upstream of it (the reconciliation gate, ThemeUpgradeReconciler)
 * never needs to know Motion, Icons, or any other specific product exists.
 */
final class ThemeDependencyAvailabilityResolver
{
    private ThemeVersionConstraintEvaluator $constraints;

    /** @param ThemeDependencyTypeResolverContract[] $adapters */
    public function __construct(
        private array $adapters,
        ?ThemeVersionConstraintEvaluator $constraints = null,
    ) {
        $this->constraints = $constraints ?? new ThemeVersionConstraintEvaluator();
    }

    public function check(string $dependencyType, string $dependencyIdentifier, ?string $versionConstraint): ThemeDependencyAvailabilityResult
    {
        if ($versionConstraint !== null && !$this->constraints->isValidSyntax($versionConstraint)) {
            return ThemeDependencyAvailabilityResult::unverifiable('invalid_constraint_syntax');
        }

        $adapter = $this->adapterFor($dependencyType);
        if ($adapter === null) {
            return ThemeDependencyAvailabilityResult::unverifiable('no_adapter_registered_for_type');
        }

        $presence = $adapter->resolve($dependencyIdentifier);

        if (!$presence->present) {
            return ThemeDependencyAvailabilityResult::missing();
        }

        if ($versionConstraint === null) {
            return ThemeDependencyAvailabilityResult::available($presence->installedVersion);
        }

        if ($presence->installedVersion === null) {
            // Present, but this dependency type doesn't track a version to
            // check the constraint against — never assumed to pass.
            return ThemeDependencyAvailabilityResult::unverifiable('installed_version_not_tracked_for_type');
        }

        return $this->constraints->satisfies($presence->installedVersion, $versionConstraint)
            ? ThemeDependencyAvailabilityResult::available($presence->installedVersion)
            : ThemeDependencyAvailabilityResult::versionIncompatible($presence->installedVersion);
    }

    private function adapterFor(string $dependencyType): ?ThemeDependencyTypeResolverContract
    {
        foreach ($this->adapters as $adapter) {
            if ($adapter->supports($dependencyType)) {
                return $adapter;
            }
        }
        return null;
    }
}
