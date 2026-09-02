<?php

namespace App\Services\ThemeCanvas\Extension;

use RuntimeException;

/**
 * Phase 3E-B — thrown by ExtensionBlockTypeRegistry when a proposed
 * extension block-type identity cannot be registered. Every case fails
 * closed: no partial/implicit registration ever happens, and the registry's
 * state is left completely unchanged when this is thrown.
 */
final class ExtensionIdentityRegistrationException extends RuntimeException
{
    public static function malformedVendorSlug(string $vendorSlug): self
    {
        return new self(
            "Invalid extension vendor slug \"{$vendorSlug}\" — must be lowercase ASCII "
            . 'letters, digits, and hyphens only, 2-40 characters, no leading/trailing/'
            . 'double hyphen.'
        );
    }

    public static function malformedBlockName(string $blockName): self
    {
        return new self(
            "Invalid extension block name \"{$blockName}\" — must be lowercase ASCII "
            . 'letters, digits, and underscores only, 1-60 characters, no leading/'
            . 'trailing/double underscore.'
        );
    }

    public static function reservedNamespace(string $type, string $namespace): self
    {
        return new self(
            "Cannot register \"{$type}\" — the \"{$namespace}\" namespace is reserved for "
            . 'the host/canonical runtime and is never claimable by a third-party extension.'
        );
    }

    public static function canonicalCollision(string $type): self
    {
        return new self(
            "Cannot register \"{$type}\" — it is already a canonical Theme Runtime block type."
        );
    }

    public static function hostCollision(string $type): self
    {
        return new self(
            "Cannot register \"{$type}\" — it is already claimed by this installation's host "
            . 'block registry (ThemeCanvas\\BlockRegistry) or Element SDK (Builder\\ElementRegistry).'
        );
    }

    public static function extensionCollision(string $type, string $existingOwner, string $requestedOwner): self
    {
        return new self(
            "Cannot register \"{$type}\" for extension \"{$requestedOwner}\" — it is already "
            . "registered with a different definition by extension \"{$existingOwner}\". "
            . 'Two different extension identities may never claim the same block type.'
        );
    }
}
