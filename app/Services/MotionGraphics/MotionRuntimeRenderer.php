<?php

namespace App\Services\MotionGraphics;

/**
 * Phase 8: renders one resolved manifest asset entry into safe HTML.
 * Never receives or touches source SVG, AI metadata, or any editable
 * schema — only the already-validated, already-optimized reference the
 * manifest carries (MotionRuntimeManifestReader guarantees the shape
 * before this class ever sees it).
 *
 * Rendering strategy per `asset_type`:
 * - `html` / `web-component` / `canvas`: sandboxed iframe or an
 *   SRI-checked `<script type="module">`, mirroring the security posture
 *   the original (dormant) `<x-motion.graphics>` component already had.
 * - `svg`: an `<img>` tag — the platform only ever ships a pre-optimized,
 *   already-trusted output reference here (never raw/customer-uploaded
 *   SVG), and `<img>` cannot execute embedded `<script>`/`onload`
 *   handlers even if the source were ever compromised, so this is safe
 *   without needing an SVG sanitizer on customer-site (which does not
 *   exist — see AUDIT_REPORT.md §1).
 * - `lottie` / `motion-json`: no player is wired in customer-site yet —
 *   renders nothing rather than fabricate a fake player (honest
 *   "not implemented" stub, matching the platform side's own
 *   ResponsiveSupportDecision pattern).
 *
 * Every call site MUST guard duplicate loads itself (Blade's `@once` is
 * used by the shipped component) — this class does not track render
 * history, since it has no natural per-request identity of its own.
 */
final class MotionRuntimeRenderer
{
    /**
     * @param array<string, mixed>|string $params the block's `motion_params`
     *   (ADR-079). Only declared, valid, non-default values are forwarded;
     *   the platform validates them again before applying.
     */
    public function render(array $asset, array|string $params = []): ?string
    {
        $ref = $asset['full_reference'] ?? null;
        if ($ref !== null && ($asset['asset_type'] ?? null) === 'svg') {
            $query = MotionRuntimeParameters::query(MotionRuntimeParameters::normalizeDefinitions($asset['parameters'] ?? []), $params);
            if ($query !== []) {
                $ref .= (str_contains($ref, '?') ? '&' : '?').http_build_query($query, '', '&', PHP_QUERY_RFC3986);
            }
            $asset['full_reference'] = $ref;
        }
        $type = $asset['asset_type'] ?? null;
        $assetId = e($asset['asset_id'] ?? '');

        return match ($type) {
            'html' => $ref === null ? null : sprintf(
                '<iframe src="%s" title="%s" loading="lazy" sandbox="allow-scripts" referrerpolicy="no-referrer"></iframe>',
                e($ref), $assetId
            ),
            'web-component', 'canvas' => $ref === null ? null : sprintf(
                '<div data-motion="%s" data-motion-version="%d"></div><script type="module" src="%s" integrity="sha256-%s" crossorigin="anonymous"></script>',
                $assetId, (int) ($asset['published_version'] ?? 0), e($ref),
                base64_encode((string) hex2bin((string) ($asset['checksum'] ?? '')))
            ),
            'svg' => $ref === null ? null : sprintf(
                '<img src="%s" alt="%s"%s loading="lazy" decoding="async" class="tb-motion-svg">',
                e($ref), e($this->label($asset)), $this->intrinsicSize($asset)
            ),
            default => null, // lottie / motion-json / unknown — no player wired yet, safe no-op
        };
    }

    /**
     * ADR-078: the manifest's display name (e.g. «موشن قهرمان سازمانی») is
     * the accessible label; the opaque asset uuid is only a last resort.
     */
    private function label(array $asset): string
    {
        $name = trim((string) ($asset['name'] ?? ''));

        return $name !== '' ? $name : (string) ($asset['asset_id'] ?? '');
    }

    /**
     * Intrinsic width/height from the manifest's placement dimensions, so the
     * browser reserves the box before the SVG arrives (no layout shift).
     * Only sane positive integers are emitted; anything else is omitted.
     */
    private function intrinsicSize(array $asset): string
    {
        $dimensions = $asset['placement_rules']['dimensions'] ?? null;
        $width = is_array($dimensions) ? filter_var($dimensions['width'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 10000]]) : false;
        $height = is_array($dimensions) ? filter_var($dimensions['height'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 10000]]) : false;

        return $width !== false && $height !== false ? sprintf(' width="%d" height="%d"', $width, $height) : '';
    }
}
