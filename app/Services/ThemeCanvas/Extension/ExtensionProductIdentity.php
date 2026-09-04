<?php

namespace App\Services\ThemeCanvas\Extension;

use SaaSPlatform\ExtensionSdk\ExtensionProductIdentity as SharedExtensionProductIdentity;

/**
 * Phase 3E-E — TRANSITIONAL namespace-compatibility adapter (Phase 3G-B-2,
 * ADR-045). The canonical Extension PRODUCT identity grammar/validation
 * authority now lives in SaaSPlatform\ExtensionSdk\ExtensionProductIdentity
 * (shared with saas-platform's commercial Extension/ExtensionRelease
 * release authority — see that package's ExtensionProductIdentity for the
 * full grammar doc comment). This class is kept, unchanged in its public
 * surface, only because existing App\Builder and App\Services\ThemeCanvas
 * call sites construct it directly (::fromParts()/::fromIdentifier()) and
 * read its public ->vendorSlug/->packageSlug/->identifier properties.
 *
 * No regex, length bound, or validation branch is duplicated below — every
 * check delegates entirely to the shared authority. The prior exception
 * type and exact message text (ExtensionIdentityRegistrationException) are
 * preserved so no existing catch site's behavior changes.
 *
 * Still deliberately distinct from the Phase 3E-B persisted block identity
 * (`ext:{vendor-slug}:{block-name}`, ExtensionBlockTypeIdentity) — never
 * merged with it, per ADR-045.
 */
final class ExtensionProductIdentity
{
    private function __construct(
        public readonly string $vendorSlug,
        public readonly string $packageSlug,
        public readonly string $identifier,
    ) {
    }

    public static function fromParts(string $vendorSlug, string $packageSlug): self
    {
        if (!SharedExtensionProductIdentity::isValidVendorSlug($vendorSlug)) {
            throw ExtensionIdentityRegistrationException::malformedVendorSlug($vendorSlug);
        }
        if (!SharedExtensionProductIdentity::isValidPackageSlug($packageSlug)) {
            throw ExtensionIdentityRegistrationException::malformedPackageSlug($packageSlug);
        }

        $shared = SharedExtensionProductIdentity::fromParts($vendorSlug, $packageSlug);

        return new self($shared->vendorSlug(), $shared->packageSlug(), $shared->toString());
    }

    public static function fromIdentifier(string $identifier): self
    {
        $parts = explode('/', $identifier);
        if (count($parts) !== 2) {
            throw ExtensionIdentityRegistrationException::malformedPackageIdentifier($identifier);
        }

        return self::fromParts($parts[0], $parts[1]);
    }

    /** Non-throwing check for fail-closed callers (e.g. a dependency resolver's resolve()). */
    public static function isValidIdentifier(string $identifier): bool
    {
        return SharedExtensionProductIdentity::isValidIdentifier($identifier);
    }

    public static function isValidPackageSlug(string $packageSlug): bool
    {
        return SharedExtensionProductIdentity::isValidPackageSlug($packageSlug);
    }
}
