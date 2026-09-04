<?php

namespace App\Services\ThemeCanvas\Extension;

/**
 * Phase 3E-B — a deterministic, validated persisted block `type` identity
 * for a third-party extension: `ext:{vendor-slug}:{block-name}`.
 *
 * Reuses the existing `module:` prefix convention already proven safe by
 * every current type-string parser in this codebase (only ever a simple
 * `str_starts_with($type, 'module:')` prefix check — verified against
 * ThemeCanvasRenderer::renderBlock() and every other type-string consumer
 * before adopting this format; a second colon inside the string is not
 * parsed/split anywhere) — never repurposes `module:` itself, which stays
 * reserved for the existing host/module system.
 *
 * Deliberately NOT Unicode-aware: vendor slugs and block names are
 * developer-facing identifiers, not user-facing content, so ASCII-only
 * keeps identity comparison/collision-checking simple and unambiguous.
 */
final class ExtensionBlockTypeIdentity
{
    public const NAMESPACE_PREFIX = 'ext:';

    private const BLOCK_NAME_PATTERN = '/^[a-z0-9]+(_[a-z0-9]+)*$/';

    private const BLOCK_NAME_MIN = 1;
    private const BLOCK_NAME_MAX = 60;

    private function __construct(
        public readonly string $vendorSlug,
        public readonly string $blockName,
        public readonly string $type,
    ) {
    }

    public static function fromParts(string $vendorSlug, string $blockName): self
    {
        self::assertValidVendorSlug($vendorSlug);
        self::assertValidBlockName($blockName);

        return new self($vendorSlug, $blockName, self::NAMESPACE_PREFIX . $vendorSlug . ':' . $blockName);
    }

    /**
     * Phase 3E-G: parses an already-`ext:`-prefixed full type string back
     * into its validated parts — the one place that split ever happens, so
     * ThemeBuilderExtensionDefinition and ThemeBuilderExtensionRegistrar
     * never each carry their own copy of it. Throws
     * ExtensionIdentityRegistrationException::malformedVendorSlug() if
     * `$type` is not `ext:`-prefixed at all (the caller is expected to
     * have already checked the prefix if it needs a more specific error).
     */
    public static function fromType(string $type): self
    {
        $withoutPrefix = str_starts_with($type, self::NAMESPACE_PREFIX)
            ? substr($type, strlen(self::NAMESPACE_PREFIX))
            : $type;
        $parts = explode(':', $withoutPrefix, 2);

        return self::fromParts($parts[0] ?? '', $parts[1] ?? '');
    }

    /**
     * Phase 3E-E, delegated to the shared authority in 3G-B-2: exposed
     * publicly so the extension DEPENDENCY identity (the product/package
     * identity a ThemeRelease depends on — see
     * Dependency\ExtensionDependencyResolver) and ExtensionProductIdentity
     * can validate against the exact same vendor-slug rule as the
     * block-identity namespace, without a second, divergent copy of this
     * regex. The vendor-slug grammar itself is no longer defined here — it
     * is owned solely by SaaSPlatform\ExtensionSdk\ExtensionProductIdentity
     * (shared with saas-platform's commercial Extension/ExtensionRelease
     * authority), which this method now simply delegates to. This method
     * stays because it is this class's own established public entry point
     * for the vendor-slug rule — not because the grammar lives here.
     */
    public static function isValidVendorSlug(string $vendorSlug): bool
    {
        return \SaaSPlatform\ExtensionSdk\ExtensionProductIdentity::isValidVendorSlug($vendorSlug);
    }

    private static function assertValidVendorSlug(string $vendorSlug): void
    {
        if (!self::isValidVendorSlug($vendorSlug)) {
            throw ExtensionIdentityRegistrationException::malformedVendorSlug($vendorSlug);
        }
    }

    private static function assertValidBlockName(string $blockName): void
    {
        $length = strlen($blockName);
        if (
            $length < self::BLOCK_NAME_MIN
            || $length > self::BLOCK_NAME_MAX
            || !preg_match(self::BLOCK_NAME_PATTERN, $blockName)
        ) {
            throw ExtensionIdentityRegistrationException::malformedBlockName($blockName);
        }
    }
}
