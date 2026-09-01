<?php

namespace App\Services\ThemeCanvas\Dependency;

use App\Services\ThemeCanvas\ThemeUpgradeConflict;
use App\Services\ThemeCanvas\ThemeUpgradeReconciliationResult;

/**
 * Phase 3E-A — the separate application-level gate §8 requires: never part
 * of ThemeUpgradeReconciler (which stays pure and dependency-agnostic).
 * Consumes an already-computed ThemeUpgradeReconciliationResult plus the
 * REMOTE ThemeRelease's structured dependency rows and an availability
 * resolver, and returns a new result with BLOCKING conflicts appended for
 * every unmet REQUIRED dependency — never mutating Theme JSON, never
 * touching an existing customer block just because a dependency became
 * unavailable (§10).
 */
final class ThemeUpgradeDependencyGate
{
    public function __construct(private ThemeDependencyAvailabilityResolver $resolver)
    {
    }

    /**
     * @param array<int, array{type?:string, dependency_type?:string, identifier?:string, dependency_identifier?:string, version_constraint?:?string, is_required?:bool, metadata?:array}> $remoteDependencies
     *   Accepts either the download-API shape (type/identifier) or the
     *   structured-row shape (dependency_type/dependency_identifier) —
     *   whichever the caller has on hand — so this class never forces a
     *   reshaping step on its caller.
     */
    public function gate(ThemeUpgradeReconciliationResult $result, array $remoteDependencies): ThemeUpgradeReconciliationResult
    {
        $extraConflicts = [];
        $extraWarnings = [];

        foreach ($remoteDependencies as $dependency) {
            $type = $dependency['type'] ?? $dependency['dependency_type'] ?? null;
            $identifier = $dependency['identifier'] ?? $dependency['dependency_identifier'] ?? null;
            $constraint = $dependency['version_constraint'] ?? null;
            $isRequired = (bool) ($dependency['is_required'] ?? false);

            if ($type === null || $identifier === null) {
                // Malformed dependency row — never guessed, never silently
                // trusted as satisfied. A required one fails closed exactly
                // like any other unverifiable dependency; an optional one
                // is just noted.
                if ($isRequired) {
                    $extraConflicts[] = ThemeUpgradeConflict::dependencyUnavailable(
                        null,
                        (string) ($type ?? 'unknown'),
                        (string) ($identifier ?? 'unknown'),
                        ThemeDependencyAvailabilityResult::UNVERIFIABLE
                    );
                } else {
                    $extraWarnings[] = [
                        'type' => 'optional_dependency_unavailable',
                        'dependency_type' => $type,
                        'dependency_identifier' => $identifier,
                        'availability_status' => ThemeDependencyAvailabilityResult::UNVERIFIABLE,
                        'reason' => 'malformed_dependency_row',
                    ];
                }
                continue;
            }

            $availability = $this->resolver->check($type, $identifier, $constraint);

            if ($availability->isAvailable()) {
                continue; // nothing to report — a satisfied dependency is silent, like an unchanged property.
            }

            if (!$isRequired) {
                // §9 — optional and unavailable is a warning, never a
                // block, and never silently omitted either.
                $extraWarnings[] = [
                    'type' => 'optional_dependency_unavailable',
                    'dependency_type' => $type,
                    'dependency_identifier' => $identifier,
                    'availability_status' => $availability->status,
                    'reason' => $availability->reason,
                ];
                continue;
            }

            // Required and not available — fail closed.
            $extraConflicts[] = $availability->status === ThemeDependencyAvailabilityResult::VERSION_INCOMPATIBLE
                ? ThemeUpgradeConflict::incompatibleDependency($blockId = null, $type, $identifier, $constraint ?? '', $availability->installedVersion)
                : ThemeUpgradeConflict::dependencyUnavailable($blockId = null, $type, $identifier, $availability->status);
        }

        if ($extraConflicts === [] && $extraWarnings === []) {
            return $result;
        }

        return new ThemeUpgradeReconciliationResult(
            reconciledTheme: $result->reconciledTheme,
            conflicts: [...$result->conflicts, ...$extraConflicts],
            preservedCustomizations: $result->preservedCustomizations,
            remoteAdditions: $result->remoteAdditions,
            detachedBlocks: $result->detachedBlocks,
            structuralFallbacks: $result->structuralFallbacks,
            warnings: [...$result->warnings, ...$extraWarnings],
            // Unchanged: dependency gating is an orthogonal, later-stage
            // check layered on top of the same reconciliation — the
            // fingerprint still identifies the same (BASE, LOCAL, REMOTE)
            // triple the reconciliation itself was computed from.
            fingerprint: $result->fingerprint,
        );
    }
}
