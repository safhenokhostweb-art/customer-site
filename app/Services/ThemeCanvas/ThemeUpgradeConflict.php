<?php

namespace App\Services\ThemeCanvas;

/**
 * Phase 3D-D/3D-E — one minimal typed representation for every kind of
 * conflict ThemeUpgradeReconciler can produce. Deliberately a single flat
 * shape, not a class hierarchy — every conflict type uses the same fields,
 * leaving whichever don't apply null.
 *
 * The constructor is private: every conflict is built through one of the
 * named factory methods below, each hardcoding its own conflict_type +
 * severity + canonical chosen_resolution. This is the Phase 3D-E hardening
 * requirement — the reconciler can no longer typo a conflict-type string
 * or pair a conflict type with the wrong severity/resolution, because it
 * never constructs one directly.
 */
final class ThemeUpgradeConflict
{
    // ── Canonical conflict types (3D-D behavior, unchanged) ───────────────
    public const PROPERTY_CONFLICT = 'PROPERTY_CONFLICT';
    public const STRUCTURAL_MOVE_CONFLICT = 'STRUCTURAL_MOVE_CONFLICT';
    public const MASTER_DELETED_LOCAL_MODIFIED = 'MASTER_DELETED_LOCAL_MODIFIED';
    public const LOCAL_DELETED_REMOTE_MODIFIED = 'LOCAL_DELETED_REMOTE_MODIFIED';
    public const TYPE_CHANGE_WITH_LOCAL_CUSTOMIZATION = 'TYPE_CHANGE_WITH_LOCAL_CUSTOMIZATION';
    public const STRUCTURAL_PARENT_REMOVED = 'STRUCTURAL_PARENT_REMOVED';
    public const PAGE_REMOVAL_CONFLICT = 'PAGE_REMOVAL_CONFLICT';

    // ── Reserved future BLOCKING types (Phase 3D-E contract only — not yet
    //    produced anywhere in ThemeUpgradeReconciler; wired up once Phase
    //    3E's dependency-availability resolver exists). Declared now so the
    //    severity/resolution contract never needs a breaking change later. ──
    public const DEPENDENCY_UNAVAILABLE = 'DEPENDENCY_UNAVAILABLE';
    public const INCOMPATIBLE_DEPENDENCY = 'INCOMPATIBLE_DEPENDENCY';

    public const SEVERITY_BLOCKING = 'BLOCKING';
    public const SEVERITY_NON_BLOCKING = 'NON_BLOCKING';

    // ── Canonical resolution vocabulary — chosen_resolution is always one
    //    of these, never arbitrary free text. ─────────────────────────────
    public const RESOLUTION_KEEP_LOCAL = 'KEEP_LOCAL';
    public const RESOLUTION_KEEP_REMOTE = 'KEEP_REMOTE';
    public const RESOLUTION_KEEP_DELETED = 'KEEP_DELETED';
    public const RESOLUTION_PRESERVE_DETACHED = 'PRESERVE_DETACHED';
    public const RESOLUTION_PRESERVE_BOTH = 'PRESERVE_BOTH';
    public const RESOLUTION_REPARENT = 'REPARENT';
    public const RESOLUTION_KEEP_PAGE = 'KEEP_PAGE';

    private function __construct(
        public readonly string $conflictType,
        public readonly string $severity,
        public readonly ?string $blockId = null,
        public readonly ?string $fieldPath = null,
        public readonly mixed $baseValue = null,
        public readonly mixed $localValue = null,
        public readonly mixed $remoteValue = null,
        public readonly ?string $chosenResolution = null,
        public readonly array $metadata = [],
    ) {
    }

    public function isBlocking(): bool
    {
        return $this->severity === self::SEVERITY_BLOCKING;
    }

    /** Deterministic, JSON-safe, plain-array representation — no objects. */
    public function toArray(): array
    {
        return [
            'conflict_type' => $this->conflictType,
            'severity' => $this->severity,
            'block_id' => $this->blockId,
            'field_path' => $this->fieldPath,
            'base_value' => $this->baseValue,
            'local_value' => $this->localValue,
            'remote_value' => $this->remoteValue,
            'chosen_resolution' => $this->chosenResolution,
            'metadata' => $this->metadata,
        ];
    }

    // ── Canonical factories — every one NON_BLOCKING (auto-resolved with
    //    the approved commercial-safe default) per Phase 3D-D. ───────────

    public static function propertyConflict(?string $blockId, string $fieldPath, mixed $base, mixed $local, mixed $remote): self
    {
        return new self(
            conflictType: self::PROPERTY_CONFLICT,
            severity: self::SEVERITY_NON_BLOCKING,
            blockId: $blockId,
            fieldPath: $fieldPath,
            baseValue: $base,
            localValue: $local,
            remoteValue: $remote,
            chosenResolution: self::RESOLUTION_KEEP_LOCAL,
        );
    }

    public static function structuralMoveConflict(string $blockId, array $base, array $local, array $remote): self
    {
        return new self(
            conflictType: self::STRUCTURAL_MOVE_CONFLICT,
            severity: self::SEVERITY_NON_BLOCKING,
            blockId: $blockId,
            baseValue: $base,
            localValue: $local,
            remoteValue: $remote,
            chosenResolution: self::RESOLUTION_KEEP_LOCAL,
        );
    }

    public static function masterDeletedLocalModified(string $blockId, array $base, array $local, string $detachedId, array $originLocation): self
    {
        return new self(
            conflictType: self::MASTER_DELETED_LOCAL_MODIFIED,
            severity: self::SEVERITY_NON_BLOCKING,
            blockId: $blockId,
            baseValue: $base,
            localValue: $local,
            chosenResolution: self::RESOLUTION_PRESERVE_DETACHED,
            metadata: ['detached_id' => $detachedId, 'origin_location' => $originLocation],
        );
    }

    public static function localDeletedRemoteModified(string $blockId, array $base, array $remote): self
    {
        return new self(
            conflictType: self::LOCAL_DELETED_REMOTE_MODIFIED,
            severity: self::SEVERITY_NON_BLOCKING,
            blockId: $blockId,
            baseValue: $base,
            remoteValue: $remote,
            chosenResolution: self::RESOLUTION_KEEP_DELETED,
        );
    }

    public static function typeChangeWithLocalCustomization(
        string $blockId,
        mixed $baseType,
        mixed $localType,
        mixed $remoteType,
        string $detachedId,
        bool $bothSidesChangedType
    ): self {
        return new self(
            conflictType: self::TYPE_CHANGE_WITH_LOCAL_CUSTOMIZATION,
            severity: self::SEVERITY_NON_BLOCKING,
            blockId: $blockId,
            baseValue: $baseType,
            localValue: $localType,
            remoteValue: $remoteType,
            chosenResolution: self::RESOLUTION_PRESERVE_BOTH,
            metadata: ['detached_id' => $detachedId, 'both_sides_changed_type' => $bothSidesChangedType],
        );
    }

    public static function structuralParentRemoved(string $blockId, array $removedParentChain, ?string $newParentId): self
    {
        return new self(
            conflictType: self::STRUCTURAL_PARENT_REMOVED,
            severity: self::SEVERITY_NON_BLOCKING,
            blockId: $blockId,
            chosenResolution: self::RESOLUTION_REPARENT,
            metadata: ['removed_parent_chain' => $removedParentChain, 'new_parent_id' => $newParentId],
        );
    }

    public static function pageRemovalConflict(string $slug, array $base, array $local): self
    {
        return new self(
            conflictType: self::PAGE_REMOVAL_CONFLICT,
            severity: self::SEVERITY_NON_BLOCKING,
            fieldPath: "pages.{$slug}",
            baseValue: $base,
            localValue: $local,
            chosenResolution: self::RESOLUTION_KEEP_PAGE,
            metadata: ['slug' => $slug],
        );
    }

    // ── Reserved factories — not produced by ThemeUpgradeReconciler today;
    //    exist only so the severity/canApply contract is already correct
    //    once Phase 3E's dependency resolver starts emitting these. ───────

    /**
     * @param ?string $blockId A dependency requirement is a whole-release
     *   concern, not inherently tied to one block — null unless a caller
     *   can genuinely attribute it to a specific block.
     * @param string $availabilityStatus The exact ThemeDependencyAvailabilityResult
     *   status that triggered this (MISSING or UNVERIFIABLE) — both map to
     *   this one conflict type, but the report must still distinguish them.
     */
    public static function dependencyUnavailable(?string $blockId, string $dependencyType, string $dependencyIdentifier, string $availabilityStatus): self
    {
        return new self(
            conflictType: self::DEPENDENCY_UNAVAILABLE,
            severity: self::SEVERITY_BLOCKING,
            blockId: $blockId,
            metadata: [
                'dependency_type' => $dependencyType,
                'dependency_identifier' => $dependencyIdentifier,
                'availability_status' => $availabilityStatus,
            ],
        );
    }

    public static function incompatibleDependency(
        ?string $blockId,
        string $dependencyType,
        string $dependencyIdentifier,
        string $requiredConstraint,
        ?string $availableVersion
    ): self {
        return new self(
            conflictType: self::INCOMPATIBLE_DEPENDENCY,
            severity: self::SEVERITY_BLOCKING,
            blockId: $blockId,
            metadata: [
                'dependency_type' => $dependencyType,
                'dependency_identifier' => $dependencyIdentifier,
                'required_constraint' => $requiredConstraint,
                'available_version' => $availableVersion,
            ],
        );
    }
}
