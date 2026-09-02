<?php

namespace App\Services\ThemeCanvas\Extension;

/**
 * Phase 3E-E (correction) — the stable identity of an extension PRODUCT/
 * package: `{vendor-slug}/{package-slug}`. Deliberately distinct from the
 * Phase 3E-B persisted block identity (`ext:{vendor-slug}:{block-name}`):
 * one product may provide multiple ext:* block types, and a Theme release
 * depends on the PRODUCT being available, never on one block instance.
 *
 * A bare vendor slug is NOT a product identity — a vendor may publish
 * several independent products (e.g. acme/gallery-pro and acme/forms-pro),
 * so the package half is required and validated exactly as strictly as the
 * vendor half. The vendor-slug rule itself is reused verbatim from
 * ExtensionBlockTypeIdentity::isValidVendorSlug() — never a second,
 * divergent copy of that grammar.
 */
final class ExtensionProductIdentity
{
    private const PACKAGE_SLUG_PATTERN = '/^[a-z0-9]+(-[a-z0-9]+)*$/';
    private const PACKAGE_SLUG_MIN = 2;
    private const PACKAGE_SLUG_MAX = 60;

    private function __construct(
        public readonly string $vendorSlug,
        public readonly string $packageSlug,
        public readonly string $identifier,
    ) {
    }

    public static function fromParts(string $vendorSlug, string $packageSlug): self
    {
        if (!ExtensionBlockTypeIdentity::isValidVendorSlug($vendorSlug)) {
            throw ExtensionIdentityRegistrationException::malformedVendorSlug($vendorSlug);
        }
        if (!self::isValidPackageSlug($packageSlug)) {
            throw ExtensionIdentityRegistrationException::malformedPackageSlug($packageSlug);
        }

        return new self($vendorSlug, $packageSlug, $vendorSlug . '/' . $packageSlug);
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
        $parts = explode('/', $identifier);
        return count($parts) === 2
            && ExtensionBlockTypeIdentity::isValidVendorSlug($parts[0])
            && self::isValidPackageSlug($parts[1]);
    }

    public static function isValidPackageSlug(string $packageSlug): bool
    {
        $length = strlen($packageSlug);
        return $length >= self::PACKAGE_SLUG_MIN
            && $length <= self::PACKAGE_SLUG_MAX
            && preg_match(self::PACKAGE_SLUG_PATTERN, $packageSlug) === 1;
    }
}
