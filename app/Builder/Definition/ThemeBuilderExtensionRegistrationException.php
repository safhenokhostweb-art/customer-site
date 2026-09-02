<?php

namespace App\Builder\Definition;

use RuntimeException;

/**
 * Phase 3E-G — thrown while validating/registering an extension PACKAGE
 * (a ThemeBuilderExtensionDefinition providing one or more ext:* blocks).
 * Distinct from ExtensionIdentityRegistrationException (3E-B), which is
 * scoped purely to one block identity string — this covers package-level
 * concerns: SDK contract support, vendor/block-vendor consistency,
 * intra-package duplicate block names, and renderer-contract shape.
 *
 * Every case fails closed at PREFLIGHT time, before any registry is
 * mutated — see ThemeBuilderExtensionRegistrar.
 */
final class ThemeBuilderExtensionRegistrationException extends RuntimeException
{
    public static function unsupportedSdkContract(int $requested, array $supported): self
    {
        $list = implode(', ', $supported);
        return new self("Unsupported SDK contract version {$requested} — this installation supports: {$list}.");
    }

    public static function vendorMismatch(string $blockType, string $productVendor): self
    {
        return new self(
            "Block \"{$blockType}\" does not belong to vendor \"{$productVendor}\" — "
            . 'a package may only register ext: blocks under its own vendor namespace.'
        );
    }

    public static function duplicateBlockInPackage(string $blockType): self
    {
        return new self("Block \"{$blockType}\" is declared more than once within the same package.");
    }

    public static function invalidRendererClass(string $blockType, string $class): self
    {
        return new self("Block \"{$blockType}\"'s renderer class \"{$class}\" must implement App\\Builder\\Contracts\\ElementInterface.");
    }

    public static function blockNotClaimable(string $blockType): self
    {
        return new self("Block \"{$blockType}\" cannot be claimed — it collides with an existing canonical, host, or extension registration.");
    }

    public static function invalidProvider(string $providerClass): self
    {
        return new self("Extension provider \"{$providerClass}\" must implement App\\Builder\\Extension\\ThemeBuilderExtensionProviderContract.");
    }
}
