<?php

namespace App\Builder\Definition;

use App\Builder\Contracts\ElementInterface;
use App\Services\ThemeCanvas\Extension\ExtensionBlockTypeIdentity;

/**
 * Phase 3E-G — one block an extension package provides: its full ext:*
 * type, declarative definition (3E-D), and the renderer class that will
 * participate in the EXISTING ElementRenderer pipeline (validation,
 * dynamic-data, asset collection, exception-safe fallback) exactly like a
 * built-in element — never a separate rendering stack.
 *
 * Validates identity FORMAT (via ExtensionBlockTypeIdentity::fromType())
 * and the renderer contract at construction time — the earliest possible
 * point, before this block is ever part of a package. Registry-level
 * collision checks (vendor-vs-product match, canonical/host/extension
 * collision) happen later, in ThemeBuilderExtensionDefinition/
 * ThemeBuilderExtensionRegistrar, which alone know package and registry
 * context.
 */
final class ThemeBuilderExtensionBlock
{
    private function __construct(
        public readonly string $type,
        public readonly ThemeBuilderElementDefinition $definition,
        public readonly string $rendererClass,
    ) {
    }

    public static function make(string $type, ThemeBuilderElementDefinition $definition, string $rendererClass): self
    {
        ExtensionBlockTypeIdentity::fromType($type); // throws on malformed vendor/block identity

        if (!is_subclass_of($rendererClass, ElementInterface::class)) {
            throw ThemeBuilderExtensionRegistrationException::invalidRendererClass($type, $rendererClass);
        }

        return new self($type, $definition, $rendererClass);
    }
}
