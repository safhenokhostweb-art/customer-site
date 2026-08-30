<?php

namespace App\Services\ThemeCanvas;

/**
 * Phase 3D-C/3D-D shared utility — extracted from ThemeCustomizationDiff so
 * both it and ThemeUpgradeReconciler build block identity from the exact
 * same recursive walk and duplicate-id validation, never two independently
 * written (and potentially divergent) tree-walkers. Pure: no DB, no
 * Settings, no side effects.
 */
final class ThemeBlockIndex
{
    public const REGION_HEADER = 'header';
    public const REGION_FOOTER = 'footer';
    public const REGION_PAGE = 'page';

    /**
     * Flat map of every block id in the snapshot to its structural facts,
     * built by one recursive walk over header_blocks, footer_blocks, and
     * every page's blocks (and their nested children, to any depth) — O(n)
     * in the total block count, with O(1) id lookups thereafter (no nested
     * search by id anywhere in this class or its callers).
     *
     * A block entry with no 'id' key is defensively skipped rather than
     * treated as an error — it cannot participate in identity-based
     * matching at all, and every real write path in this codebase (Phase
     * 3D-B) guarantees an id is always assigned before a block is ever
     * persisted, so this is a not-expected-to-occur boundary case, not the
     * "invalid input" this class is required to reject (duplicate ids).
     *
     * @throws ThemeCustomizationDiffException if the snapshot contains a
     *   duplicate block id anywhere (header/footer/any page/any nesting
     *   depth) — never silently resolved by picking one occurrence.
     * @return array<string, array{id:string,type:mixed,region:string,page_slug:?string,parent_id:?string,position:int,payload:array}>
     */
    public static function build(array $snapshot, string $snapshotLabel): array
    {
        $byId = [];

        $walk = function (array $blocks, string $region, ?string $pageSlug, ?string $parentId) use (&$walk, &$byId, $snapshotLabel): void {
            foreach ($blocks as $position => $block) {
                if (!is_array($block) || !array_key_exists('id', $block)) {
                    continue;
                }

                $id = (string) $block['id'];
                if (array_key_exists($id, $byId)) {
                    throw ThemeCustomizationDiffException::duplicateBlockId($snapshotLabel, $id);
                }

                $byId[$id] = [
                    'id' => $id,
                    'type' => $block['type'] ?? null,
                    'region' => $region,
                    'page_slug' => $pageSlug,
                    'parent_id' => $parentId,
                    'position' => $position,
                    // Everything a customer/master edit can change about
                    // this block, excluding the structural/identity keys
                    // (id, type — already captured above; children —
                    // indexed as separate, independently-identified
                    // entries, never duplicated inline here). Whatever
                    // payload keys actually exist (props, style,
                    // responsive, or a future field) are treated generically
                    // — nothing is hardcoded.
                    'payload' => array_diff_key($block, ['id' => true, 'type' => true, 'children' => true]),
                ];

                if (!empty($block['children']) && is_array($block['children'])) {
                    $walk($block['children'], $region, $pageSlug, $id);
                }
            }
        };

        $walk($snapshot['header_blocks'] ?? [], self::REGION_HEADER, null, null);
        $walk($snapshot['footer_blocks'] ?? [], self::REGION_FOOTER, null, null);

        foreach ($snapshot['pages'] ?? [] as $slug => $page) {
            if (!is_array($page)) {
                continue;
            }
            $walk($page['blocks'] ?? [], self::REGION_PAGE, (string) $slug, null);
        }

        return $byId;
    }
}
