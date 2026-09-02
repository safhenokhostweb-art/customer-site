<?php

namespace App\Services\ThemeCanvas\Dependency;

/**
 * Phase 3E-E (correction) — answers ONLY "is the current tenant/site
 * entitled to use this extension product", deliberately separate from
 * InstalledExtensionProvider's "is this package's code present at all".
 * Package presence and tenant entitlement are two independent facts;
 * ExtensionDependencyResolver requires both before reporting availability.
 *
 * Implementations must resolve the current tenant/site fresh on every
 * call — never cache entitlement in static/singleton state — so a bound
 * implementation can safely be reused across requests without leaking one
 * tenant's access into another's.
 *
 * A V1 config-driven implementation exists (ConfigExtensionEntitlementProvider)
 * because no real Marketplace/license entitlement store exists yet. This
 * contract is exactly the seam a future one plugs into without touching
 * ExtensionDependencyResolver or anything upstream of it.
 */
interface ExtensionEntitlementProviderContract
{
    /**
     * @param string $productIdentifier "{vendor-slug}/{package-slug}" (see ExtensionProductIdentity)
     */
    public function isEntitled(string $productIdentifier): bool;
}
