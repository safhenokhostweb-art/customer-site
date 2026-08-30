<?php

namespace App\Services\ThemeCanvas;

/**
 * Phase 3D-C — pure, deterministic, read-only structural diff between two
 * Theme snapshots (each shaped like active_canvas_theme_data /
 * ThemeRelease.snapshot: header_blocks, footer_blocks, pages, global_styles,
 * settings). No DB, no Settings, no HTTP, no rendering, no side effects —
 * diff(BASE, LOCAL) and diff(BASE, REMOTE) are the same pure operation
 * called twice; Phase 3D-D will be the first thing to combine their two
 * results. This class reports FACTS only — it never decides which side
 * "wins" anything.
 *
 * Block identity is exactly `block.id`, matched globally across the entire
 * snapshot (header, footer, every page, every nesting depth) — never by
 * array position, type, content similarity, or any prefix convention.
 * 'cust_'-prefixed and legacy customer ids (Phase 3D-B) and master-origin
 * ids are all ordinary stable ids to this engine; it does not special-case
 * any id shape.
 *
 * Page identity is exactly the page's array key (its slug). A page whose
 * slug changed between snapshots is reported as one page REMOVED and one
 * page ADDED — never inferred as a rename — because nothing today proves
 * that relationship deterministically (Phase 3D-B). Real evidence that a
 * page's *content* moved is still fully captured, just at the block level:
 * every block that moved from the old slug's grouping to the new one is
 * reported as MOVED with its own base_page_slug/target_page_slug facts.
 */
final class ThemeCustomizationDiff
{
    /**
     * @throws ThemeCustomizationDiffException if either snapshot contains a
     *   duplicate block id anywhere (header/footer/any page/any nesting
     *   depth) — a snapshot with ambiguous block identity cannot be
     *   diffed; this is never silently resolved by picking one occurrence.
     */
    public function diff(array $base, array $target): ThemeCustomizationDiffResult
    {
        $baseIndex = ThemeBlockIndex::build($base, 'BASE');
        $targetIndex = ThemeBlockIndex::build($target, 'TARGET');

        return new ThemeCustomizationDiffResult(
            blocks: $this->diffBlocks($baseIndex, $targetIndex),
            pages: $this->diffPages($base['pages'] ?? [], $target['pages'] ?? []),
            globalStyles: $this->diffNested($base['global_styles'] ?? [], $target['global_styles'] ?? []),
            settings: $this->diffNested($base['settings'] ?? [], $target['settings'] ?? []),
        );
    }

    // ── Block diff ────────────────────────────────────────────────────────

    private function diffBlocks(array $baseIndex, array $targetIndex): array
    {
        $ids = array_keys($baseIndex + $targetIndex);
        sort($ids, SORT_STRING);

        $results = [];

        foreach ($ids as $id) {
            $inBase = array_key_exists($id, $baseIndex);
            $inTarget = array_key_exists($id, $targetIndex);

            if ($inBase && !$inTarget) {
                $results[] = [
                    'id' => $id,
                    'classification' => ['DELETED'],
                    'base' => $this->placement($baseIndex[$id]),
                ];
                continue;
            }

            if (!$inBase && $inTarget) {
                $results[] = [
                    'id' => $id,
                    'classification' => ['ADDED'],
                    'target' => $this->placement($targetIndex[$id]),
                ];
                continue;
            }

            $b = $baseIndex[$id];
            $t = $targetIndex[$id];
            $classification = [];
            $entry = [
                'id' => $id,
                'base' => $this->placement($b),
                'target' => $this->placement($t),
            ];

            if ($b['type'] !== $t['type']) {
                $classification[] = 'TYPE_CHANGED';
                $entry['type_change'] = ['base_type' => $b['type'], 'target_type' => $t['type']];
            }

            // Deliberately still computed even when the type also changed —
            // this engine never attempts prop migration/interpretation, it
            // only reports the raw mechanical diff; Phase 3D-D decides what
            // a type change + prop diff together mean.
            $propertyChanges = $this->diffNested($b['payload'], $t['payload']);
            if ($propertyChanges !== []) {
                $classification[] = 'MODIFIED';
                $entry['property_changes'] = $propertyChanges;
            }

            $moved = $b['region'] !== $t['region']
                || $b['page_slug'] !== $t['page_slug']
                || $b['parent_id'] !== $t['parent_id']
                || $b['position'] !== $t['position'];
            if ($moved) {
                $classification[] = 'MOVED';
                $entry['move'] = [
                    'base_parent' => $b['parent_id'],
                    'target_parent' => $t['parent_id'],
                    'base_region' => $b['region'],
                    'target_region' => $t['region'],
                    'base_page_slug' => $b['page_slug'],
                    'target_page_slug' => $t['page_slug'],
                    'base_position' => $b['position'],
                    'target_position' => $t['position'],
                ];
            }

            if ($classification === []) {
                continue; // UNCHANGED — omitted, not reported as a no-op entry.
            }

            $entry['classification'] = $classification;
            $results[] = $entry;
        }

        return $results;
    }

    private function placement(array $indexed): array
    {
        return [
            'type' => $indexed['type'],
            'region' => $indexed['region'],
            'page_slug' => $indexed['page_slug'],
            'parent_id' => $indexed['parent_id'],
            'position' => $indexed['position'],
            'payload' => $indexed['payload'],
        ];
    }

    // ── Page diff (metadata only — block content is covered by diffBlocks) ─

    private function diffPages(array $basePages, array $targetPages): array
    {
        $slugs = array_keys($basePages + $targetPages);
        sort($slugs, SORT_STRING);

        $results = [];

        foreach ($slugs as $slug) {
            $inBase = array_key_exists($slug, $basePages);
            $inTarget = array_key_exists($slug, $targetPages);

            if ($inBase && !$inTarget) {
                $results[] = [
                    'slug' => (string) $slug,
                    'classification' => ['REMOVED'],
                    'base' => $this->pageMeta($basePages[$slug]),
                ];
                continue;
            }

            if (!$inBase && $inTarget) {
                $results[] = [
                    'slug' => (string) $slug,
                    'classification' => ['ADDED'],
                    'target' => $this->pageMeta($targetPages[$slug]),
                ];
                continue;
            }

            $propertyChanges = $this->diffNested(
                $this->pageMeta($basePages[$slug]),
                $this->pageMeta($targetPages[$slug])
            );

            if ($propertyChanges !== []) {
                $results[] = [
                    'slug' => (string) $slug,
                    'classification' => ['MODIFIED'],
                    'property_changes' => $propertyChanges,
                ];
            }
        }

        return $results;
    }

    private function pageMeta(mixed $page): array
    {
        return is_array($page) ? array_diff_key($page, ['blocks' => true]) : [];
    }

    // ── Generic nested field-level diff (global_styles, settings, and every
    //    block's own payload all share this one mechanism) ────────────────

    /**
     * A sentinel distinct from PHP null — a key that does not exist at all
     * must never be conflated with a key that exists and is explicitly
     * null (both are real, distinct, tested states: "missing → null" and
     * "null → missing").
     */
    private const MISSING = "\0__missing__\0";

    private function diffNested(array $base, array $target, string $prefix = ''): array
    {
        $keys = array_keys($base + $target);
        sort($keys, SORT_STRING);

        $changes = [];

        foreach ($keys as $key) {
            $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;
            $hasBase = array_key_exists($key, $base);
            $hasTarget = array_key_exists($key, $target);
            $baseValue = $hasBase ? $base[$key] : self::MISSING;
            $targetValue = $hasTarget ? $target[$key] : self::MISSING;

            if ($hasBase && $hasTarget
                && is_array($baseValue) && is_array($targetValue)
                && $this->isAssoc($baseValue) && $this->isAssoc($targetValue)
            ) {
                // Both sides are keyed (associative) sub-structures — recurse
                // so a change deep inside e.g. colors.primary or
                // dark_mode.background gets its own precise path rather than
                // reporting the whole parent object as "changed".
                foreach ($this->diffNested($baseValue, $targetValue, $path) as $nestedPath => $nestedChange) {
                    $changes[$nestedPath] = $nestedChange;
                }
                continue;
            }

            if (!$hasBase && $hasTarget) {
                $changes[$path] = ['change_type' => 'ADDED', 'base_value' => null, 'target_value' => $targetValue];
            } elseif ($hasBase && !$hasTarget) {
                $changes[$path] = ['change_type' => 'REMOVED', 'base_value' => $baseValue, 'target_value' => null];
            } elseif ($baseValue !== $targetValue) {
                // Plain numeric-indexed lists (e.g. settings.supported_modules)
                // have no per-item identity concept in the current contract,
                // so they are compared as one atomic value here rather than
                // index-diffed — index-diffing a list with no stable item
                // identity would misrepresent an insertion/removal as a
                // cascade of "changed" entries for every later index.
                $changes[$path] = ['change_type' => 'CHANGED', 'base_value' => $baseValue, 'target_value' => $targetValue];
            }
            // else: both exist and are strictly identical — no entry.
        }

        return $changes;
    }

    private function isAssoc(array $value): bool
    {
        return $value !== [] && array_keys($value) !== range(0, count($value) - 1);
    }
}
