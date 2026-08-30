<?php

namespace App\Services\ThemeCanvas;

use App\Models\Setting;
use App\Models\ThemeInstanceRevision;

/**
 * Phase 3D-B — resolves the exact immutable ThemeRelease a customer Theme
 * instance's state is based on: the BASE side of the future BASE/LOCAL/
 * REMOTE upgrade reconciliation (Phase 3D design). Read-only; creates
 * nothing, mutates nothing.
 *
 * Never infers/guesses: the only proof of provenance is a real, non-null
 * source_release_id already recorded on some revision in the lineage's own
 * immutable history (theme_instance_revisions), reached either directly or
 * by walking backward through it — never timestamps, never approximate
 * version matching, never anything supplied by an untrusted source (see
 * ThemeDesignController::canvasImport()'s verification step, which is the
 * only place a *claimed* provenance value from outside this ledger is ever
 * considered, and only after independently confirming this same
 * installation's own ledger already proves it). Anything else resolves to
 * NON_RECONCILABLE.
 */
class ThemeInstanceBaseRelease
{
    /**
     * Resolve BASE for the customer instance's CURRENT active state.
     */
    public function resolveForCurrentTheme(): ThemeBaseReleaseResolution
    {
        $activeId = (int) Setting::get('active_theme_revision_id');
        if (! $activeId) {
            return ThemeBaseReleaseResolution::nonReconcilable();
        }

        return $this->resolveForRevisionId($activeId);
    }

    /**
     * Resolve BASE by walking backward from a specific starting revision.
     *
     * Used both for the public "what release is my current state based on"
     * query, and internally by ThemeInstanceRevisionService to propagate
     * provenance forward onto new revisions that don't carry it directly:
     * a publish resolves from the revision it's about to replace (it never
     * changes which release the instance is based on); a rollback resolves
     * from the TARGET revision being restored — never from whatever was
     * active before the rollback — so restoring an old release's state can
     * never falsely inherit a newer release's provenance.
     *
     * Walk order per hop: (1) does this revision itself carry a real
     * source_release_id? If so, that's BASE. (2) Is it a rollback_snapshot
     * with a metadata.restored_from_revision_id link? Follow it. (3)
     * Otherwise (an ordinary customer edit with no direct provenance),
     * step to the immediately preceding revision_number in the same
     * lineage. A cross-lineage jump, a dangling reference, a revisit
     * (defense-in-depth; the ledger is append-only so a real cycle should
     * be structurally impossible), or exhausting history all resolve to
     * NON_RECONCILABLE rather than guessing.
     */
    public function resolveForRevisionId(int $revisionId, int $maxHops = 500): ThemeBaseReleaseResolution
    {
        $current = ThemeInstanceRevision::find($revisionId);
        if (! $current) {
            return ThemeBaseReleaseResolution::nonReconcilable();
        }

        $identifier = $current->theme_identifier;
        $visited = [];

        for ($hop = 0; $hop < $maxHops; $hop++) {
            if ($current === null || $current->theme_identifier !== $identifier || isset($visited[$current->id])) {
                return ThemeBaseReleaseResolution::nonReconcilable();
            }
            $visited[$current->id] = true;

            if ($current->source_release_id !== null) {
                return ThemeBaseReleaseResolution::resolved(
                    themeIdentifier: $current->theme_identifier,
                    sourceThemeId: $current->source_theme_id,
                    sourceThemeVersion: $current->source_theme_version,
                    sourceReleaseId: $current->source_release_id,
                    resolvedFromRevisionId: $current->id,
                    resolvedFromRevisionNumber: $current->revision_number,
                );
            }

            $restoredFromId = ($current->metadata ?? [])['restored_from_revision_id'] ?? null;
            if ($restoredFromId !== null) {
                $current = ThemeInstanceRevision::find((int) $restoredFromId);
                continue;
            }

            $current = ThemeInstanceRevision::where('theme_identifier', $identifier)
                ->where('revision_number', '<', $current->revision_number)
                ->orderByDesc('revision_number')
                ->first();
        }

        return ThemeBaseReleaseResolution::nonReconcilable();
    }
}
