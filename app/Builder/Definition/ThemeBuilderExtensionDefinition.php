<?php

namespace App\Builder\Definition;

use App\Services\ThemeCanvas\Extension\ExtensionBlockTypeIdentity;
use App\Services\ThemeCanvas\Extension\ExtensionProductIdentity;

/**
 * Phase 3E-G — the immutable package-level manifest one extension provider
 * returns: its PRODUCT identity (3E-E: "{vendor}/{package}", never a bare
 * vendor), package/runtime version (plain metadata — never entitlement
 * authority, never fed into the dependency evaluator by this class), an
 * integer SDK contract version, and the one-or-more ext:* blocks it
 * provides. One product may provide many blocks; a block is never itself
 * a separate product.
 *
 * Every structural rule a package must satisfy is enforced HERE, at
 * construction time — before ThemeBuilderExtensionRegistrar ever touches
 * a real registry — so a caller holding a constructed instance already
 * knows it is internally consistent (supported SDK contract, every block's
 * vendor matches the product's own vendor, no duplicate block type within
 * the package). Registry-level collisions (canonical/host/other-extension)
 * are NOT checked here — only the registrar can know current registry
 * state, and only at registration time.
 */
final class ThemeBuilderExtensionDefinition
{
    /**
     * Phase 3G-B-2: delegates to the shared SDK contract-version authority
     * (SaaSPlatform\ExtensionSdk\ExtensionSdkContract, shared with
     * saas-platform's ExtensionRelease validation) rather than defining its
     * own competing integer — no call site below needed to change, since
     * this constant still resolves to the same value (1) it always has.
     */
    public const SDK_CONTRACT_V1 = \SaaSPlatform\ExtensionSdk\ExtensionSdkContract::V1;
    private const SUPPORTED_SDK_CONTRACTS = \SaaSPlatform\ExtensionSdk\ExtensionSdkContract::SUPPORTED;

    /** @param ThemeBuilderExtensionBlock[] $blocks keyed by full ext:* type */
    private function __construct(
        public readonly ExtensionProductIdentity $product,
        public readonly string $packageVersion,
        public readonly int $sdkContract,
        public readonly array $blocks,
    ) {
    }

    /** @param ThemeBuilderExtensionBlock[] $blocks */
    public static function make(
        ExtensionProductIdentity $product,
        string $packageVersion,
        int $sdkContract,
        array $blocks,
    ): self {
        if (!in_array($sdkContract, self::SUPPORTED_SDK_CONTRACTS, true)) {
            throw ThemeBuilderExtensionRegistrationException::unsupportedSdkContract($sdkContract, self::SUPPORTED_SDK_CONTRACTS);
        }

        $keyed = [];
        foreach ($blocks as $block) {
            if (!$block instanceof ThemeBuilderExtensionBlock) {
                throw new \InvalidArgumentException('Every block must be a ThemeBuilderExtensionBlock instance.');
            }

            if (!str_starts_with($block->type, ExtensionBlockTypeIdentity::NAMESPACE_PREFIX)) {
                throw ThemeBuilderExtensionRegistrationException::vendorMismatch($block->type, $product->vendorSlug);
            }

            $identity = ExtensionBlockTypeIdentity::fromType($block->type);
            if ($identity->vendorSlug !== $product->vendorSlug) {
                throw ThemeBuilderExtensionRegistrationException::vendorMismatch($block->type, $product->vendorSlug);
            }

            if (array_key_exists($block->type, $keyed)) {
                throw ThemeBuilderExtensionRegistrationException::duplicateBlockInPackage($block->type);
            }

            $keyed[$block->type] = $block;
        }

        return new self($product, $packageVersion, $sdkContract, $keyed);
    }
}
