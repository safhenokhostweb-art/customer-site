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
        if ($manifest === null || $assetId === '') {
            return null;
        }

        $cleanId = str_starts_with($assetId, 'motion:') ? substr($assetId, 7) : $assetId;

        foreach ($manifest['assets'] ?? [] as $asset) {
            $currentId = $asset['asset_id'] ?? null;
            if ($currentId === null) {
                continue;
            }
            $cleanCurrent = str_starts_with($currentId, 'motion:') ? substr($currentId, 7) : $currentId;
            if ($currentId === $assetId || $cleanCurrent === $cleanId || $cleanCurrent === $assetId || $currentId === $cleanId) {
                return $asset;
            }
            if (($asset['slug'] ?? null) === $cleanId || ($asset['uuid'] ?? null) === $cleanId) {
                return $asset;
            }
        }

        return null;
    }
}
