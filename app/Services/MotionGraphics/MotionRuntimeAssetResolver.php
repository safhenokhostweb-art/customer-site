<?php

namespace App\Services\MotionGraphics;

/**
 * Phase 8: asset lookup by canonical id within an already-fetched,
 * already-validated manifest array (MotionRuntimeManifestReader's
 * output). Pure, no I/O — kept separate from the reader so rendering
 * code can be unit tested against a plain array fixture without an HTTP
 * mock.
 */
final class MotionRuntimeAssetResolver
{
    /**
     * @param array{assets?: list<array<string,mixed>>}|null $manifest
     */
    public function resolve(?array $manifest, string $assetId): ?array
    {
        if ($manifest === null) {
            return null;
        }

        foreach ($manifest['assets'] ?? [] as $asset) {
            if (($asset['asset_id'] ?? null) === $assetId) {
                return $asset;
            }
        }

        return null;
    }
}
