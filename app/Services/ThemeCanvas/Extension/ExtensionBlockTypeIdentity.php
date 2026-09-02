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

    private const VENDOR_SLUG_PATTERN = '/^[a-z0-9]+(-[a-z0-9]+)*$/';
    private const BLOCK_NAME_PATTERN = '/^[a-z0-9]+(_[a-z0-9]+)*$/';

    private const VENDOR_SLUG_MIN = 2;
    private const VENDOR_SLUG_MAX = 40;
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

    private static function assertValidVendorSlug(string $vendorSlug): void
    {
        $length = strlen($vendorSlug);
        if (
            $length < self::VENDOR_SLUG_MIN
            || $length > self::VENDOR_SLUG_MAX
            || !preg_match(self::VENDOR_SLUG_PATTERN, $vendorSlug)
        ) {
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
