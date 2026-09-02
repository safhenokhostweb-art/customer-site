<?php

namespace App\Services\ThemeCanvas\Dependency;

use App\Services\TenantContext;

/**
 * Phase 3E-E (correction) — the V1 bridge implementation of
 * ExtensionEntitlementProviderContract. Explicitly NOT a Marketplace/
 * license entitlement store — no purchasing, orders, or fulfillment exist
 * yet. Config-driven only because nothing more authoritative exists today;
 * a future real entitlement source (Marketplace purchase record, license
 * table) replaces this behind the same contract, unchanged by callers.
 *
 * Keyed by the License's own persistent `license_key`, NOT by
 * TenantContext::domain() — domain() is ultimately derived from the
 * current request's Host header (transient request data), while
 * license_key is the License record's own stable identity. Generic tenant
 * existence (TenantContext::hasTenant()) alone is deliberately NOT
 * sufficient here: a valid license proves this installation has SOME
 * active subscription, not that its subscription includes THIS extension
 * product — those are different facts, and conflating them was exactly
 * the defect this correction fixes.
 *
 * Resolves the tenant fresh on every call (TenantContext::current()) and
 * caches nothing of its own — safe to bind transiently and reuse the same
 * provider instance across calls without any risk of leaking one tenant's
 * entitlement into another's.
 */
final class ConfigExtensionEntitlementProvider implements ExtensionEntitlementProviderContract
{
    public function __construct(private TenantContext $tenant)
    {
    }

    public function isEntitled(string $productIdentifier): bool
    {
        $license = $this->tenant->current();
        if ($license === null || empty($license->license_key)) {
            // Fail closed: no stable, resolvable tenant identity means no
            // entitlement can be established — never assumed granted.
            return false;
        }

        $entitlements = config('extensions.entitlements', []);
        $allowed = is_array($entitlements) ? ($entitlements[$license->license_key] ?? null) : null;

        return is_array($allowed) && in_array($productIdentifier, $allowed, true);
    }
}
