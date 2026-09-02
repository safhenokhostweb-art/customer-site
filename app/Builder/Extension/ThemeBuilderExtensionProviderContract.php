<?php

namespace App\Builder\Extension;

use App\Builder\Definition\ThemeBuilderExtensionDefinition;

/**
 * Phase 3E-G — the smallest possible V1 loading contract: one class,
 * resolved from the container, returning one immutable package manifest.
 *
 * A provider declares runtime capability ONLY. It must never own
 * entitlement, checkout, Marketplace listing, Theme upgrade gating, or
 * reconciliation — those remain the exclusive concerns of
 * ExtensionDependencyResolver (3E-E) and ThemeUpgradeDependencyGate,
 * completely untouched by this contract.
 */
interface ThemeBuilderExtensionProviderContract
{
    public function definition(): ThemeBuilderExtensionDefinition;
}
