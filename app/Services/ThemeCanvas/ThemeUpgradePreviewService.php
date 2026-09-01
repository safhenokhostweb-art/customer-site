<?php

namespace App\Services\ThemeCanvas;

use App\Models\Setting;
use App\Models\ThemeInstanceRevision;
use App\Services\PlatformApiService;
use App\Services\ThemeCanvas\Dependency\ThemeDependencyAvailabilityResolver;
use App\Services\ThemeCanvas\Dependency\ThemeUpgradeDependencyGate;

/**
 * Phase 3D-F — orchestrates a single, read-only upgrade preview:
 * resolve BASE → load BASE/LOCAL from this installation's own immutable
 * ledger → fetch REMOTE from the Marketplace → reconcile → dependency-gate.
 * No orchestration lives inside ThemeUpgradeReconciler itself — this class
 * is the only thing that knows how to assemble its three inputs from real
 * application state.
 *
 * Never writes anything. preview() is called identically at Preview time
 * and again, independently, at Apply time (Phase 3D-F's TOCTOU/staleness
 * protection) — the exact same computation, never trusted from a prior
 * call or from the client.
 */
class ThemeUpgradePreviewService
{
    public function __construct(
        private ThemeInstanceBaseRelease $baseRelease,
        private PlatformApiService $platform,
        private ThemeDependencyAvailabilityResolver $dependencyResolver,
    ) {
    }

    public function preview(int $themeId): ThemeUpgradePreviewResult
    {
        $baseResolution = $this->baseRelease->resolveForCurrentTheme();
        if (!$baseResolution->isResolved()) {
            return ThemeUpgradePreviewResult::nonReconcilable();
        }

        // Phase 3D-F §4: customer-site already possesses the exact BASE
        // snapshot locally — the immutable ledger row that first proved
        // this provenance IS byte-for-byte what was downloaded/installed
        // at that point (record() stores theme_data verbatim). No platform
        // API call is needed or used to reconstruct BASE.
        $baseRevision = ThemeInstanceRevision::find($baseResolution->resolvedFromRevisionId);

        // §5: LOCAL is the ledger row the active pointer actually names —
        // never the separate active_canvas_theme_data Setting blob taken
        // on faith. A dangling/missing pointer is a real integrity
        // failure, not something to guess past.
        $activeRevisionId = (int) Setting::get('active_theme_revision_id');
        $localRevision = ThemeInstanceRevision::find($activeRevisionId);

        if ($baseRevision === null || $localRevision === null) {
            return ThemeUpgradePreviewResult::nonReconcilable();
        }

        $remoteData = $this->platform->marketplaceDownloadTheme($themeId);
        if (!$remoteData || empty($remoteData['success'])) {
            return ThemeUpgradePreviewResult::downloadFailed();
        }

        $currentRelease = [
            'theme_identifier' => $baseResolution->themeIdentifier,
            'source_theme_id' => $baseResolution->sourceThemeId,
            'release_id' => $baseResolution->sourceReleaseId,
            'release_version' => $baseResolution->sourceThemeVersion,
        ];
        $targetRelease = [
            'theme_id' => $remoteData['theme_id'] ?? null,
            'theme_slug' => $remoteData['theme_slug'] ?? null,
            'theme_name' => $remoteData['theme_name'] ?? null,
            'release_id' => $remoteData['release_id'] ?? null,
            'release_version' => $remoteData['release_version'] ?? null,
        ];

        // §6: this is an upgrade of the SAME lineage, never a switch to a
        // different theme (that is install()'s existing, separate,
        // explicit-confirmation wholesale-replace path — never silently
        // reinterpreted as a "safe upgrade" of unrelated content).
        if (($remoteData['theme_slug'] ?? null) !== $baseResolution->themeIdentifier) {
            return ThemeUpgradePreviewResult::differentLineage($currentRelease, $targetRelease);
        }

        // A release with no real identity can never become a trustworthy
        // BASE for the NEXT upgrade (Phase 3D-B) — never reconciled against
        // as if it already were one.
        if (!isset($remoteData['release_id']) || $remoteData['release_id'] === null) {
            return ThemeUpgradePreviewResult::remoteNotTrackable($currentRelease, $targetRelease);
        }

        $remoteSnapshot = $this->extractSnapshot($remoteData);

        $reconciliation = (new ThemeUpgradeReconciler())->reconcile(
            $baseRevision->theme_data,
            $localRevision->theme_data,
            $remoteSnapshot
        );

        $gated = (new ThemeUpgradeDependencyGate($this->dependencyResolver))
            ->gate($reconciliation, $remoteData['dependencies'] ?? []);

        return ThemeUpgradePreviewResult::ready($currentRelease, $targetRelease, $gated);
    }

    private function extractSnapshot(array $remoteData): array
    {
        return [
            'header_blocks' => $remoteData['header_blocks'] ?? [],
            'footer_blocks' => $remoteData['footer_blocks'] ?? [],
            'pages' => $remoteData['pages'] ?? [],
            'global_styles' => $remoteData['global_styles'] ?? [],
            'settings' => $remoteData['settings'] ?? [],
        ];
    }
}
