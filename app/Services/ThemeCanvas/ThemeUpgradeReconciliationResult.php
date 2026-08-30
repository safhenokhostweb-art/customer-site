<?php

namespace App\Services\ThemeCanvas;

/**
 * Output of ThemeUpgradeReconciler::reconcile() — pure data, no persistence.
 * `reconciledTheme` is a complete, valid Theme-snapshot-shaped array
 * (header_blocks/footer_blocks/pages/global_styles/settings) — every other
 * field is reporting/bookkeeping about how it was produced, never data the
 * canonical runtime needs to render correctly.
 */
final class ThemeUpgradeReconciliationResult
{
    /**
     * @param array $reconciledTheme Complete merged snapshot, valid current
     *   Theme schema.
     * @param ThemeUpgradeConflict[] $conflicts Every conflict encountered,
     *   in deterministic order.
     * @param array $preservedCustomizations LOCAL changes that survived
     *   into the result untouched by any REMOTE change (block ids and, for
     *   global_styles/settings, field paths) — distinct from a conflict
     *   that happened to resolve in the customer's favor (§6): a preserved
     *   customization never appears in `conflicts` at all, because nothing
     *   competed with it.
     * @param array $remoteAdditions New master content introduced by
     *   REMOTE (new blocks, new pages, new global_styles/settings fields)
     *   that had no LOCAL counterpart — never an ordinary REMOTE edit to
     *   something that already existed.
     * @param array $detachedBlocks Blocks preserved under a fresh
     *   deterministic id because their original id's REMOTE fate
     *   (deletion or incompatible type change) could not simply absorb
     *   the customer's own changes. Each entry: id, origin_id, reason,
     *   origin_location, final_location, conflict_index (position in
     *   `conflicts` of the conflict that caused the detachment).
     * @param array $structuralFallbacks Every case where an automatic
     *   relocation had to happen — a removed ordering anchor, a removed
     *   parent/container — never a hidden repositioning.
     * @param array $warnings Structured, non-blocking, informational notes
     *   distinct from conflicts (§10) — e.g. a page kept alive only
     *   because removing it would have stranded customer content. Plain
     *   data, never pre-formatted human-readable text (§5) — the future
     *   preview UI renders the message.
     * @param string $fingerprint Deterministic identity of exactly this
     *   (BASE, LOCAL, REMOTE) triple — see ThemeUpgradeReconciler::
     *   fingerprint(). Lets a later apply step prove it is applying the
     *   exact reconciliation that was previewed, not a stale one.
     */
    public function __construct(
        public readonly array $reconciledTheme,
        public readonly array $conflicts,
        public readonly array $preservedCustomizations,
        public readonly array $remoteAdditions,
        public readonly array $detachedBlocks,
        public readonly array $structuralFallbacks,
        public readonly array $warnings,
        public readonly string $fingerprint,
    ) {
    }

    public function hasConflicts(): bool
    {
        return $this->conflicts !== [];
    }

    public function hasBlockingConflicts(): bool
    {
        foreach ($this->conflicts as $conflict) {
            if ($conflict->isBlocking()) {
                return true;
            }
        }
        return false;
    }

    /**
     * False only for genuinely blocking conditions — never merely because
     * ordinary LOCAL/REMOTE conflicts exist (those are, by construction,
     * already auto-resolved with a commercial-safe default). A structurally
     * invalid snapshot never reaches this class at all: ThemeCustomizationDiffException
     * is thrown before a Result can be built, so it is never represented
     * as canApply()=false — it is a hard failure, not a reconciliation
     * outcome (Phase 3D-E §13).
     */
    public function canApply(): bool
    {
        return !$this->hasBlockingConflicts();
    }

    /** Deterministic, JSON-safe, plain-array representation — no objects. */
    public function toArray(): array
    {
        return [
            'reconciled_theme' => $this->reconciledTheme,
            'conflicts' => array_map(fn (ThemeUpgradeConflict $c) => $c->toArray(), $this->conflicts),
            'preserved_customizations' => $this->preservedCustomizations,
            'remote_additions' => $this->remoteAdditions,
            'detached_blocks' => $this->detachedBlocks,
            'structural_fallbacks' => $this->structuralFallbacks,
            'warnings' => $this->warnings,
            'has_conflicts' => $this->hasConflicts(),
            'has_blocking_conflicts' => $this->hasBlockingConflicts(),
            'can_apply' => $this->canApply(),
            'fingerprint' => $this->fingerprint,
        ];
    }
}
