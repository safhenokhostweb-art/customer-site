<?php

namespace App\Services\ThemeCanvas\Dependency;

/**
 * Phase 3E-E (correction) — the narrow, config-driven authoritative source
 * of "which extension PRODUCTS this installation has code installed for",
 * keyed by ExtensionProductIdentity's "{vendor-slug}/{package-slug}" —
 * never a bare vendor slug (one vendor may publish several independent
 * products). Explicitly NOT a licensing/entitlement/Marketplace-fulfillment
 * system: it reports only the raw installed-code fact. Whether the CURRENT
 * tenant is actually entitled to a present package is a wholly separate
 * question, answered only by ExtensionEntitlementProviderContract — this
 * class must never be treated as an entitlement authority by itself.
 *
 * Deliberately reads config() fresh on every call rather than caching a
 * snapshot in a constructor-time property — "which packages are installed"
 * is safe, immutable, process-wide static data (like config itself), never
 * tenant-scoped entitlement, so this class carries no per-request state at
 * all and needs none.
 */
final class InstalledExtensionProvider
{
    public function isInstalled(string $productIdentifier): bool
    {
        return array_key_exists($productIdentifier, $this->installed());
    }

    public function installedVersion(string $productIdentifier): ?string
    {
        $entry = $this->installed()[$productIdentifier] ?? null;
        return is_array($entry) ? ($entry['version'] ?? null) : null;
    }

    private function installed(): array
    {
        $installed = config('extensions.installed', []);
        return is_array($installed) ? $installed : [];
    }
}
