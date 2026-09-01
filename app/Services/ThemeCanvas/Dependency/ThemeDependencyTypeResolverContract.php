<?php

namespace App\Services\ThemeCanvas\Dependency;

/**
 * Phase 3E-A — the generic registration point for a per-dependency-type
 * availability adapter. Shared Theme Runtime/reconciliation code knows
 * nothing about any specific product (Motion, Icons, Forms, ...) — it only
 * ever talks to this contract. A future product (Motion, per Phase 3E's
 * scope note) registers its own implementation; this phase defines the
 * contract and registers exactly one real adapter (`module`, via the
 * already-existing, product-neutral TenantContext::hasModule()).
 */
interface ThemeDependencyTypeResolverContract
{
    /** Whether this adapter is the authority for a given dependency_type. */
    public function supports(string $dependencyType): bool;

    /**
     * Reports only the raw fact this adapter can actually prove — presence,
     * and an installed version if (and only if) this dependency type
     * genuinely tracks one. Never guesses a version; never treats "a
     * record exists somewhere" as proof of runtime availability unless it
     * actually is.
     */
    public function resolve(string $dependencyIdentifier): ThemeDependencyPresence;
}
