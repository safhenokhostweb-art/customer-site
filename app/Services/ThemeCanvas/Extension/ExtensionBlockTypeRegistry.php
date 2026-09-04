<?php

namespace App\Services\ThemeCanvas\Extension;

use App\Builder\ElementRegistry;
use App\Services\ThemeCanvas\BlockRegistry as ThemeCanvasBlockRegistry;
use SaaSPlatform\ThemeRuntime\BlockRegistry as CanonicalBlockRegistry;

/**
 * Phase 3E-B (ADR-030) — the single identity/collision governance authority
 * for third-party extension block types on this installation.
 *
 * This is deliberately NOT a fourth independent registry competing with the
 * three found by the Phase 3E-A audit (canonical BlockRegistry, ThemeCanvas
 * BlockRegistry, Builder ElementRegistry) — it registers nothing about how
 * an extension type renders or edits (that is 3E-D's concern). Its only job
 * is to answer, deterministically and fail-closed: "may this identity be
 * claimed?", by consulting all three existing registries as the reserved
 * keyspace, before ever adding a type to its own small table of registered
 * extension identities.
 *
 * No rendering, dependency, editor, or reconciliation behavior is touched
 * here. An identity registered through this class has no runtime behavior
 * of its own yet.
 */
final class ExtensionBlockTypeRegistry
{
    /**
     * type => ['owner' => vendor-slug, 'definition' => array]
     */
    private static array $registered = [];

    /**
     * Registers a new extension-owned block type identity.
     *
     * Fails closed (throws ExtensionIdentityRegistrationException) on any
     * collision and never mutates state when it throws. Re-registering the
     * exact same (vendorSlug, blockName, definition) is a no-op — the same
     * idempotent-boot allowance already established for the canonical
     * registry's registerHostType() (Phase 3E-B).
     */
    public static function registerExtensionType(string $vendorSlug, string $blockName, array $definition): string
    {
        $identity = ExtensionBlockTypeIdentity::fromParts($vendorSlug, $blockName);
        $type = $identity->type;

        self::assertNotReserved($type);
        self::assertAvailable($type, $vendorSlug, $definition);

        self::$registered[$type] = ['owner' => $vendorSlug, 'definition' => $definition];

        return $type;
    }

    /**
     * Defense-in-depth reserved-namespace check against a raw type string —
     * exposed separately from registerExtensionType() so the boundary is
     * directly testable independent of the vendor/block validation rules
     * that (by construction) already prevent an extension identity from
     * ever landing in one of these namespaces.
     */
    public static function assertNotReserved(string $type): void
    {
        if (str_starts_with($type, 'module:')) {
            throw ExtensionIdentityRegistrationException::reservedNamespace($type, 'module:');
        }

        if (str_starts_with($type, 'core:')) {
            throw ExtensionIdentityRegistrationException::reservedNamespace($type, 'core:');
        }

        if (!str_starts_with($type, ExtensionBlockTypeIdentity::NAMESPACE_PREFIX)) {
            throw ExtensionIdentityRegistrationException::reservedNamespace($type, '(bare/unprefixed — reserved for canonical + host types)');
        }
    }

    private static function assertAvailable(string $type, string $vendorSlug, array $definition): void
    {
        if (CanonicalBlockRegistry::isCanonicalType($type)) {
            throw ExtensionIdentityRegistrationException::canonicalCollision($type);
        }

        if (CanonicalBlockRegistry::isHostRegisteredType($type)) {
            throw ExtensionIdentityRegistrationException::hostCollision($type);
        }

        // Phase 3E-G: checked BEFORE the Canvas-registry/ElementRegistry
        // probes below, deliberately. Once ThemeBuilderExtensionRegistrar
        // exists, a type already present in self::$registered legitimately
        // means ElementRegistry::has($type) — and, since Phase 3F-C,
        // ThemeCanvasBlockRegistry::all() — is ALSO true (its renderer
        // class was registered right after this identity was claimed):
        // that is no longer a collision to reject, it is the authoritative
        // record this exact identity is already an extension's own, and
        // owner/definition equality alone decides idempotent-vs-rejected,
        // same as always. Resolving this first means the checks below only
        // ever fire for a type they hold WITHOUT this registry's own
        // knowledge of it — a genuine inconsistency, not the normal
        // idempotent-reregistration path (Phase 3F-C: this ordering is now
        // load-bearing, not just an optimization — ThemeCanvasBlockRegistry
        // ::all() below legitimately reflects this exact identity once
        // registered, so it must never be consulted before this check).
        if (array_key_exists($type, self::$registered)) {
            $existing = self::$registered[$type];
            if ($existing['owner'] === $vendorSlug && $existing['definition'] === $definition) {
                return; // identical idempotent re-registration by the same owner — allowed
            }

            throw ExtensionIdentityRegistrationException::extensionCollision($type, $existing['owner'], $vendorSlug);
        }

        // Covers every currently-persisted host/local/module type this
        // installation defines, including the module:* namespace and the
        // ~40 pre-existing local-definition overlaps with canonical names
        // (Phase 3E-A finding) — none of those are ext:-prefixed, so this
        // can only ever match a genuine collision, never a false positive
        // against that grandfathered precedence question. Since Phase
        // 3F-C, ThemeCanvasBlockRegistry::all() also reflects OTHER
        // already-registered ext:* identities — reaching this line already
        // means $type is not one of them (the self::$registered check
        // above returned/threw for that case), so this can still only ever
        // match a genuine host/local collision, never a false positive.
        if (array_key_exists($type, ThemeCanvasBlockRegistry::all())) {
            throw ExtensionIdentityRegistrationException::hostCollision($type);
        }

        if (ElementRegistry::has($type)) {
            throw ExtensionIdentityRegistrationException::hostCollision($type);
        }
    }

    /**
     * Phase 3E-G — read-only preflight: would registerExtensionType() for
     * these exact arguments succeed right now, without mutating anything?
     * Lets a caller (ThemeBuilderExtensionRegistrar) validate an entire
     * multi-block package BEFORE committing any of it — true atomicity via
     * validate-then-mutate, never a partial registration to roll back.
     */
    public static function isClaimable(string $type, string $vendorSlug, array $definition): bool
    {
        try {
            self::assertNotReserved($type);
            self::assertAvailable($type, $vendorSlug, $definition);
            return true;
        } catch (ExtensionIdentityRegistrationException) {
            return false;
        }
    }

    /** True if `$type` is a currently-registered extension block type. */
    public static function has(string $type): bool
    {
        return array_key_exists($type, self::$registered);
    }

    /** All registered extension type identities => their definition array. */
    public static function all(): array
    {
        return array_map(static fn (array $entry) => $entry['definition'], self::$registered);
    }

    /** The vendor-slug that owns `$type`, or null if not registered. */
    public static function ownerOf(string $type): ?string
    {
        return self::$registered[$type]['owner'] ?? null;
    }

    /** Test-only: clears registered extension types between test cases. */
    public static function reset(): void
    {
        self::$registered = [];
    }
}
