<?php

namespace App\Builder\Extension;

use App\Builder\Definition\ThemeBuilderExtensionDefinition;
use App\Builder\Definition\ThemeBuilderExtensionRegistrationException;
use App\Builder\ElementRegistry;
use App\Services\ThemeCanvas\Extension\ExtensionBlockTypeIdentity;
use App\Services\ThemeCanvas\Extension\ExtensionBlockTypeRegistry;

/**
 * Phase 3E-G — the one coordinator that registers a complete extension
 * package (ThemeBuilderExtensionDefinition) against the EXISTING
 * authorities: ExtensionBlockTypeRegistry (identity/collision) and
 * ElementRegistry (renderer-class lookup). Owns no competing type map of
 * its own — every fact it needs is asked of an existing authority, and
 * every mutation it performs is one of those authorities' own existing
 * (or narrowly added) methods.
 *
 * Atomic by validate-then-mutate, not by rollback: every block in the
 * package is preflighted (read-only) against current registry state
 * FIRST; only if every single one would succeed does this method mutate
 * anything at all. A package with N blocks where block N fails leaves
 * zero of that package's blocks newly registered — not N-1 of them.
 */
final class ThemeBuilderExtensionRegistrar
{
    public static function register(ThemeBuilderExtensionDefinition $package): void
    {
        foreach ($package->blocks as $block) {
            $identity = ExtensionBlockTypeIdentity::fromType($block->type);

            if (!ExtensionBlockTypeRegistry::isClaimable($identity->type, $identity->vendorSlug, $block->definition->toLegacySchema())) {
                throw ThemeBuilderExtensionRegistrationException::blockNotClaimable($block->type);
            }

            // A block already claimed by ElementRegistry under a DIFFERENT
            // renderer class would otherwise only be caught inside the
            // mutation loop below — checked here too so a multi-block
            // package still fails entirely before any mutation, not after
            // the first N-1 blocks already committed.
            $existingClass = ElementRegistry::get($block->type);
            if ($existingClass !== null && $existingClass !== $block->rendererClass) {
                throw ThemeBuilderExtensionRegistrationException::blockNotClaimable($block->type);
            }
        }

        foreach ($package->blocks as $block) {
            $identity = ExtensionBlockTypeIdentity::fromType($block->type);

            ExtensionBlockTypeRegistry::registerExtensionType($identity->vendorSlug, $identity->blockName, $block->definition->toLegacySchema());
            ElementRegistry::registerExtensionElement($block->type, $block->rendererClass);
        }
    }
}
