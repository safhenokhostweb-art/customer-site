<?php

namespace App\Services\ThemeCanvas\Dependency;

use App\Services\ThemeCanvas\Extension\ExtensionProductIdentity;

/**
 * Phase 3E-E (correction) — the `extension` dependency-type adapter,
 * plugging the third-party Extension SDK into the EXISTING generic Theme
 * dependency architecture (Phase 3E-A) exactly like ModuleDependencyResolver
 * already does for `module` — no second dependency system, no new
 * constraint evaluator, no new gate.
 *
 * Dependency identity is the extension PRODUCT — "{vendor-slug}/
 * {package-slug}" (ExtensionProductIdentity) — never a bare vendor slug
 * (one vendor may publish several independent products) and never a
 * persisted block type string (a release depends on the product being
 * available, not on any one of its block instances).
 *
 * Availability requires BOTH independent facts to be true, each asked of
 * its own dedicated provider, checked fresh on every call:
 *   1. the package is actually installed (InstalledExtensionProvider)
 *   2. the CURRENT tenant/site is entitled to it (ExtensionEntitlementProviderContract)
 * Neither ElementRegistry nor ExtensionBlockTypeRegistry is consulted: a
 * block type being registered proves a PHP class exists in this process,
 * not that this tenant may use the product it belongs to. And neither
 * "the package exists" nor "some tenant/license exists at all" is ever,
 * by itself, treated as entitlement to THIS specific product.
 */
final class ExtensionDependencyResolver implements ThemeDependencyTypeResolverContract
{
    public const TYPE = 'extension';

    public function __construct(
        private InstalledExtensionProvider $packages,
        private ExtensionEntitlementProviderContract $entitlement,
    ) {
    }

    public function supports(string $dependencyType): bool
    {
        return $dependencyType === self::TYPE;
    }

    public function resolve(string $dependencyIdentifier): ThemeDependencyPresence
    {
        if (!ExtensionProductIdentity::isValidIdentifier($dependencyIdentifier)) {
            return ThemeDependencyPresence::absent();
        }

        if (!$this->packages->isInstalled($dependencyIdentifier)) {
            return ThemeDependencyPresence::absent();
        }

        // Package presence alone is never entitlement — checked as a
        // genuinely separate, request-fresh fact.
        if (!$this->entitlement->isEntitled($dependencyIdentifier)) {
            return ThemeDependencyPresence::absent();
        }

        return ThemeDependencyPresence::present($this->packages->installedVersion($dependencyIdentifier));
    }
}
