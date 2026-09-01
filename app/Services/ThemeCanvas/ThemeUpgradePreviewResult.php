<?php

namespace App\Services\ThemeCanvas;

/**
 * Phase 3D-F — output of ThemeUpgradePreviewService::preview(). Pure data;
 * the controller/view decide how to render it, this class only ever
 * carries facts. `reconciliation` (a ThemeUpgradeReconciliationResult) is
 * only present when status is READY.
 */
final class ThemeUpgradePreviewResult
{
    /** Reconciliation ran; see `reconciliation` for the full report. */
    public const STATUS_READY = 'READY';
    /** No provable BASE release for the current instance — never guessed. */
    public const STATUS_NON_RECONCILABLE = 'NON_RECONCILABLE';
    /** The target theme is not the same lineage as the current instance —
     *  this would be a theme SWITCH, not an upgrade; out of this flow's scope. */
    public const STATUS_DIFFERENT_LINEAGE = 'DIFFERENT_LINEAGE';
    /** The Marketplace download call failed or returned no license. */
    public const STATUS_DOWNLOAD_FAILED = 'DOWNLOAD_FAILED';
    /** REMOTE has no release identity (release_id null) — cannot become a
     *  trustworthy BASE for the next upgrade, so it is never reconciled
     *  against as if it were one. */
    public const STATUS_REMOTE_NOT_TRACKABLE = 'REMOTE_NOT_TRACKABLE';

    private function __construct(
        public readonly string $status,
        public readonly ?array $currentRelease = null,
        public readonly ?array $targetRelease = null,
        public readonly ?ThemeUpgradeReconciliationResult $reconciliation = null,
    ) {
    }

    public static function ready(array $currentRelease, array $targetRelease, ThemeUpgradeReconciliationResult $reconciliation): self
    {
        return new self(self::STATUS_READY, $currentRelease, $targetRelease, $reconciliation);
    }

    public static function nonReconcilable(): self
    {
        return new self(self::STATUS_NON_RECONCILABLE);
    }

    public static function differentLineage(?array $currentRelease, ?array $targetRelease): self
    {
        return new self(self::STATUS_DIFFERENT_LINEAGE, $currentRelease, $targetRelease);
    }

    public static function downloadFailed(): self
    {
        return new self(self::STATUS_DOWNLOAD_FAILED);
    }

    public static function remoteNotTrackable(?array $currentRelease, ?array $targetRelease): self
    {
        return new self(self::STATUS_REMOTE_NOT_TRACKABLE, $currentRelease, $targetRelease);
    }

    public function isReady(): bool
    {
        return $this->status === self::STATUS_READY;
    }

    /** Deterministic, JSON-safe, plain-array representation — no secrets, no DB handles. */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'current_release' => $this->currentRelease,
            'target_release' => $this->targetRelease,
            'reconciliation' => $this->reconciliation?->toArray(),
        ];
    }
}
