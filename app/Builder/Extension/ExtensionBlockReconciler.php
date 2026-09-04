<?php

namespace App\Builder\Extension;

/**
 * Phase 3F-C — extracted (byte-identical logic, moved, not rewritten) from
 * `PageBuilderController::reconcileExtensionBlocks()` (3E-H-C), so the same
 * fail-closed trust boundary for `ext:*` blocks whose extension is not
 * currently registered (unavailable — provider not loaded, or never
 * legitimately registered at all) can be reused by any save/update endpoint
 * that persists a block tree, not only the Production Builder's.
 *
 * `ThemeDesignController::canvasSaveBlocks()` (Canvas domain) is the second
 * caller this phase adds. No new algorithm, no extension-specific
 * persistence branch beyond what already existed: every other block type
 * (any non-`ext:`-prefixed type, and any currently-registered ext:* type)
 * passes through completely unchanged.
 *
 * For an unregistered ext:* block, the incoming (client-submitted) copy is
 * NEVER trusted as-is: reconciled against the caller's own previously-
 * persisted content.
 *   - If a block with the same id AND type already exists in the previous
 *     content, the server's own last-known-good copy is kept (so props/
 *     children the client can't validate can't be silently rewritten
 *     either).
 *   - Otherwise it is a brand-new client-asserted unavailable extension
 *     block (or a wholly unregistered ext:* identity, e.g. `ext:evil:x`),
 *     which is dropped — every other block in the same request still
 *     saves normally.
 */
final class ExtensionBlockReconciler
{
    /**
     * @param array $incoming Blocks from this request.
     * @param array $previous Blocks currently persisted (pre-overwrite), any tree shape.
     */
    public static function reconcile(array $incoming, array $previous): array
    {
        $previousById = [];
        self::indexBlocksById($previous, $previousById);

        return self::reconcileLevel($incoming, $previousById);
    }

    /** Recursively flattens a block tree (any nesting key: blocks/children) into id => block. */
    private static function indexBlocksById(array $blocks, array &$index): void
    {
        foreach ($blocks as $block) {
            if (!is_array($block)) {
                continue;
            }
            if (isset($block['id'])) {
                $index[$block['id']] = $block;
            }
            $children = $block['blocks'] ?? $block['children'] ?? [];
            if (is_array($children)) {
                self::indexBlocksById($children, $index);
            }
        }
    }

    /** @param array<string, array> $previousById */
    private static function reconcileLevel(array $incoming, array $previousById): array
    {
        $result = [];
        foreach ($incoming as $block) {
            if (!is_array($block)) {
                continue;
            }
            $type = $block['type'] ?? '';

            if (str_starts_with($type, 'ext:') && !\App\Builder\ElementRegistry::has($type)) {
                $existing = $previousById[$block['id'] ?? null] ?? null;
                if ($existing === null || ($existing['type'] ?? null) !== $type) {
                    continue;
                }
                $result[] = $existing;
                continue;
            }

            if (isset($block['blocks']) && is_array($block['blocks'])) {
                $block['blocks'] = self::reconcileLevel($block['blocks'], $previousById);
            }
            if (isset($block['children']) && is_array($block['children'])) {
                $block['children'] = self::reconcileLevel($block['children'], $previousById);
            }
            $result[] = $block;
        }

        return $result;
    }
}
