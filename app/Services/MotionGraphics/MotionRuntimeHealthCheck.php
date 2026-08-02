<?php

namespace App\Services\MotionGraphics;

/**
 * Phase 8 (docs/motion-customer-runtime-phase8/HEALTH_CHECK_RESULTS.md):
 * the customer-site half of the health-check pair (the platform side is
 * MotionCustomerActivationService::healthCheck()). Diagnostic only — no
 * admin editing surface, nothing this class does can mutate any state.
 *
 * Two independent, uncached reads are compared so a transient fetch
 * glitch or a genuinely unstable manifest is distinguished from "not
 * configured" — either read failing, or the two disagreeing, is
 * unhealthy.
 */
final class MotionRuntimeHealthCheck
{
    public function __construct(
        private readonly MotionRuntimeManifestReader $reader,
    ) {}

    /**
     * @return array{healthy: bool, manifest_checksum: ?string, asset_count: int, reason: ?string}
     */
    public function check(): array
    {
        $first = $this->reader->readFresh();
        if ($first === null) {
            return ['healthy' => false, 'manifest_checksum' => null, 'asset_count' => 0, 'reason' => 'manifest_unavailable'];
        }

        $second = $this->reader->readFresh();
        if ($second === null || ($second['manifest_checksum'] ?? null) !== ($first['manifest_checksum'] ?? null)) {
            return ['healthy' => false, 'manifest_checksum' => $first['manifest_checksum'] ?? null, 'asset_count' => count($first['assets'] ?? []), 'reason' => 'checksum_unstable'];
        }

        if (empty($first['assets'])) {
            return ['healthy' => false, 'manifest_checksum' => $first['manifest_checksum'], 'asset_count' => 0, 'reason' => 'empty_manifest'];
        }

        return ['healthy' => true, 'manifest_checksum' => $first['manifest_checksum'], 'asset_count' => count($first['assets']), 'reason' => null];
    }
}
