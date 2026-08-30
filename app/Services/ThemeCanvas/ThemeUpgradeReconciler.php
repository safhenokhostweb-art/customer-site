<?php

namespace App\Services\ThemeCanvas;

/**
 * Phase 3D-D/3D-E — pure, deterministic three-way (BASE/LOCAL/REMOTE) Theme
 * upgrade reconciler. Built entirely on ThemeBlockIndex (identity) and
 * ThemeCustomizationDiff (change facts) — this class never independently
 * re-matches or re-classifies blocks; it only decides, for facts the diff
 * engine already established, which side wins and records why. No DB, no
 * Settings, no HTTP, no rendering, no ThemeInstanceRevision writes, no
 * side effects — reconcile() is a pure function of its three arguments.
 *
 * Every conflict is built through ThemeUpgradeConflict's named factories —
 * this class never constructs one directly, so a conflict type can never
 * be paired with the wrong severity or an arbitrary chosen_resolution
 * string (Phase 3D-E hardening).
 */
final class ThemeUpgradeReconciler
{
    private const SIDE_LOCAL = 'local';
    private const SIDE_REMOTE = 'remote';

    /**
     * @throws ThemeCustomizationDiffException if BASE, LOCAL, or REMOTE
     *   contains a duplicate block id — propagated unchanged from
     *   ThemeBlockIndex/ThemeCustomizationDiff. A structurally invalid
     *   snapshot is never reconciled and never reaches a Result object —
     *   this is a hard failure, not a reconciliation outcome with
     *   canApply()=false (Phase 3D-E §13).
     */
    public function reconcile(array $base, array $local, array $remote): ThemeUpgradeReconciliationResult
    {
        $baseIndex = ThemeBlockIndex::build($base, 'BASE');
        $localIndex = ThemeBlockIndex::build($local, 'LOCAL');
        $remoteIndex = ThemeBlockIndex::build($remote, 'REMOTE');

        $diff = new ThemeCustomizationDiff();
        $localBlockChanges = $this->keyByBlockId($diff->diff($base, $local)->blocks);
        $remoteBlockChanges = $this->keyByBlockId($diff->diff($base, $remote)->blocks);

        $conflicts = [];
        $preserved = [];
        $remoteAdditions = [];
        $detached = [];
        $fallbacks = [];
        $warnings = [];

        $resolved = $this->resolveBlocks(
            $baseIndex,
            $localIndex,
            $remoteIndex,
            $localBlockChanges,
            $remoteBlockChanges,
            $conflicts,
            $preserved,
            $remoteAdditions,
            $detached
        );

        // Flatten any detached-block side-payloads (type-change, recorded
        // under a reserved '__also' key on their canonical id's entry — see
        // reconcileExistingBlock()) into the flat resolved map before
        // anything downstream needs to see every surviving id.
        $resolved = $this->flattenDetached($resolved);

        // §11 — a block whose parent no longer survives (the container was
        // removed but this descendant did, whether it's an ordinary
        // surviving child or a freshly detached one) must never be
        // silently orphaned into an unreachable bucket: reparent it to the
        // nearest surviving ancestor, or the region/page root if none
        // survive at all.
        $resolved = $this->reparentOrphans($resolved, $baseIndex, $localIndex, $remoteIndex, $conflicts, $fallbacks);

        $resolvedPages = $this->resolvePages(
            $base['pages'] ?? [],
            $local['pages'] ?? [],
            $remote['pages'] ?? [],
            $resolved,
            $conflicts,
            $preserved,
            $remoteAdditions,
            $warnings
        );

        $tree = $this->assembleTree($resolved, $resolvedPages, $baseIndex, $localIndex, $remoteIndex, $fallbacks);

        // Phase 3D-E — enrich every detached-block report with where it
        // actually ended up (after orphan reparenting + ordering) and which
        // conflict record explains it, so a future preview never needs to
        // re-run reconciliation or inspect raw snapshots to answer "where
        // is this now, and why".
        $detached = $this->enrichDetached($detached, $resolved);

        [$reconciledGlobalStyles] = $this->reconcileNested(
            $base['global_styles'] ?? [],
            $local['global_styles'] ?? [],
            $remote['global_styles'] ?? [],
            'global_styles',
            $conflicts,
            $preserved,
            $remoteAdditions
        );
        [$reconciledSettings] = $this->reconcileNested(
            $base['settings'] ?? [],
            $local['settings'] ?? [],
            $remote['settings'] ?? [],
            'settings',
            $conflicts,
            $preserved,
            $remoteAdditions
        );

        $reconciledTheme = [
            'header_blocks' => $tree['header_blocks'],
            'footer_blocks' => $tree['footer_blocks'],
            'pages' => $tree['pages'],
            'global_styles' => $reconciledGlobalStyles,
            'settings' => $reconciledSettings,
        ];

        return new ThemeUpgradeReconciliationResult(
            reconciledTheme: $reconciledTheme,
            conflicts: $conflicts,
            preservedCustomizations: $preserved,
            remoteAdditions: $remoteAdditions,
            detachedBlocks: $detached,
            structuralFallbacks: $fallbacks,
            warnings: $warnings,
            fingerprint: self::fingerprint($base, $local, $remote),
        );
    }

    // ── Phase 3D-E: deterministic fingerprint ────────────────────────────

    /**
     * A pure, deterministic identity for exactly this (BASE, LOCAL, REMOTE)
     * triple — no timestamps, no randomness, no dependence on key/array
     * iteration order beyond what is already semantically meaningful
     * (list/array ORDER is preserved verbatim, since block order is real
     * data; only associative-array KEY order is normalized). Because
     * reconcile() is a pure function of its three arguments (proven by
     * ThemeUpgradeReconcilerTest's determinism test), this fingerprint of
     * the INPUTS is sufficient to prove two reconciliations are the exact
     * same computation — a later apply step can recompute this over
     * whatever LOCAL/REMOTE it is about to act on and compare it against a
     * fingerprint captured at preview time, refusing to apply if they
     * differ (Phase 3D-F concern; this method is only the pure primitive).
     */
    public static function fingerprint(array $base, array $local, array $remote): string
    {
        return hash('sha256', self::canonicalize($base) . "\0" . self::canonicalize($local) . "\0" . self::canonicalize($remote));
    }

    private static function canonicalize(array $data): string
    {
        return json_encode(self::deepKeySort($data), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private static function deepKeySort(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        $isAssoc = $value !== [] && array_keys($value) !== range(0, count($value) - 1);

        $out = [];
        foreach ($value as $k => $v) {
            $out[$k] = self::deepKeySort($v);
        }

        if ($isAssoc) {
            ksort($out);
        }

        return $out;
    }

    private function keyByBlockId(array $blockDiffEntries): array
    {
        $byId = [];
        foreach ($blockDiffEntries as $entry) {
            $byId[$entry['id']] = $entry;
        }
        return $byId;
    }

    // ── Phase 1: per-block existence + property + type + placement resolution ─

    /**
     * @return array<string, array{type:mixed, payload:array, placement: array{region:string,page_slug:?string,parent_id:?string}, anchor_source: ?string}>
     *   Keyed by the block's FINAL id (a detached block appears under its
     *   fresh id, never its original one).
     */
    private function resolveBlocks(
        array $baseIndex,
        array $localIndex,
        array $remoteIndex,
        array $localChanges,
        array $remoteChanges,
        array &$conflicts,
        array &$preserved,
        array &$remoteAdditions,
        array &$detached
    ): array {
        $ids = array_keys($baseIndex + $localIndex + $remoteIndex);
        sort($ids, SORT_STRING);

        $resolved = [];
        $allKnownIds = array_fill_keys($ids, true);

        foreach ($ids as $id) {
            $inBase = array_key_exists($id, $baseIndex);
            $inLocal = array_key_exists($id, $localIndex);
            $inRemote = array_key_exists($id, $remoteIndex);
            $lc = $localChanges[$id] ?? null; // null = LOCAL identical to BASE for this id
            $rc = $remoteChanges[$id] ?? null;

            // ── Pure additions / mutual deletions ───────────────────────
            if (!$inBase && $inLocal && !$inRemote) {
                $resolved[$id] = $this->fromIndex($localIndex[$id], self::SIDE_LOCAL);
                $preserved[] = ['type' => 'block_added', 'block_id' => $id, 'side' => 'local'];
                continue;
            }
            if (!$inBase && !$inLocal && $inRemote) {
                $resolved[$id] = $this->fromIndex($remoteIndex[$id], self::SIDE_REMOTE);
                $remoteAdditions[] = ['type' => 'block_added', 'block_id' => $id];
                continue;
            }
            if (!$inBase && $inLocal && $inRemote) {
                // Both sides independently introduced the same id — treat
                // as a normal three-way reconciliation with an empty BASE
                // (every property is either agreed or a genuine conflict).
                $resolved[$id] = $this->reconcileExistingBlock(
                    $id,
                    ['type' => null, 'payload' => []],
                    $localIndex[$id],
                    $remoteIndex[$id],
                    null,
                    null,
                    $conflicts,
                    $preserved,
                    $detached,
                    $allKnownIds
                );
                continue;
            }
            if ($inBase && !$inLocal && !$inRemote) {
                continue; // agreed deletion — simply absent from the result.
            }

            // ── One side deleted, the other kept/changed it ─────────────
            if ($inBase && !$inLocal && $inRemote) {
                if ($rc === null) {
                    // The customer's deletion is honored because REMOTE
                    // never touched this block either — a genuine
                    // customization that survived without any conflict.
                    $preserved[] = ['type' => 'block_deleted', 'block_id' => $id, 'side' => 'local'];
                    continue;
                }
                $conflicts[] = ThemeUpgradeConflict::localDeletedRemoteModified(
                    $id,
                    $baseIndex[$id]['payload'],
                    $remoteIndex[$id]['payload']
                );
                continue; // customer's deletion is intentional — never silently revived.
            }

            if ($inBase && $inLocal && !$inRemote) {
                // Whether LOCAL made any REAL content customization —
                // deliberately NOT using the diff's raw 'MOVED' flag here,
                // because that flag also fires from a mere array-position
                // shift caused by unrelated sibling deletions/insertions
                // elsewhere on the same page (ThemeCustomizationDiff
                // compares raw position, by design — see its own docs).
                // Container placement is checked independently, directly
                // against region/page_slug/parent_id only, so a block that
                // never actually changed container is never mistaken for
                // "the customer moved it" just because a neighbor vanished.
                $hasContentCustomization = $lc !== null
                    && (in_array('MODIFIED', $lc['classification'], true) || in_array('TYPE_CHANGED', $lc['classification'], true));
                $trulyRelocated = $localIndex[$id]['region'] !== $baseIndex[$id]['region']
                    || $localIndex[$id]['page_slug'] !== $baseIndex[$id]['page_slug']
                    || $localIndex[$id]['parent_id'] !== $baseIndex[$id]['parent_id'];

                if (!$hasContentCustomization) {
                    // Either the customer never touched this block in any
                    // way REMOTE's deletion should be second-guessed for (a
                    // plain remote deletion, no conflict), or they had
                    // already relocated it to a genuinely different
                    // container before the upgrade — REMOTE's deletion
                    // targeted the OLD spot, not the content the customer
                    // already moved; it survives at its new home.
                    if ($trulyRelocated) {
                        $resolved[$id] = $this->fromIndex($localIndex[$id], self::SIDE_LOCAL);
                        $preserved[] = ['type' => 'block_moved', 'block_id' => $id, 'side' => 'local'];
                    }
                    continue;
                }

                // Genuine customer customization (property and/or type
                // change) on a block REMOTE deleted entirely — never
                // silently discarded: preserved under a fresh id.
                $newId = $this->deriveDetachedId($id, ThemeUpgradeConflict::MASTER_DELETED_LOCAL_MODIFIED, $allKnownIds);
                $allKnownIds[$newId] = true;
                $originLocation = [
                    'region' => $localIndex[$id]['region'],
                    'page_slug' => $localIndex[$id]['page_slug'],
                    'parent_id' => $localIndex[$id]['parent_id'],
                ];
                $resolved[$newId] = $this->fromIndex($localIndex[$id], self::SIDE_LOCAL);
                $conflicts[] = ThemeUpgradeConflict::masterDeletedLocalModified(
                    $id,
                    $baseIndex[$id]['payload'],
                    $localIndex[$id]['payload'],
                    $newId,
                    $originLocation
                );
                $detached[] = [
                    'id' => $newId,
                    'origin_id' => $id,
                    'reason' => ThemeUpgradeConflict::MASTER_DELETED_LOCAL_MODIFIED,
                    'origin_location' => $originLocation,
                    'conflict_index' => count($conflicts) - 1,
                ];
                continue;
            }

            // ── Present in all three — reconcile the same logical block ──
            $resolved[$id] = $this->reconcileExistingBlock(
                $id,
                $baseIndex[$id],
                $localIndex[$id],
                $remoteIndex[$id],
                $lc,
                $rc,
                $conflicts,
                $preserved,
                $detached,
                $allKnownIds
            );
        }

        return $resolved;
    }

    private function fromIndex(array $indexed, ?string $anchorSource): array
    {
        return [
            'type' => $indexed['type'],
            'payload' => $indexed['payload'],
            'placement' => [
                'region' => $indexed['region'],
                'page_slug' => $indexed['page_slug'],
                'parent_id' => $indexed['parent_id'],
            ],
            'anchor_source' => $anchorSource,
        ];
    }

    /**
     * Reconciles one block id present (or, for the empty-BASE mutual-add
     * case, effectively present) in all three sides: type, payload
     * properties, and placement — each independently, per the approved
     * three-way rule, so e.g. a property change and a placement move on
     * the same block never mask each other.
     */
    private function reconcileExistingBlock(
        string $id,
        array $base,
        array $local,
        array $remote,
        ?array $lc,
        ?array $rc,
        array &$conflicts,
        array &$preserved,
        array &$detached,
        array &$allKnownIds
    ): array {
        $baseType = $base['type'] ?? null;
        $localType = $local['type'];
        $remoteType = $remote['type'];

        $localTypeChanged = $localType !== $baseType;
        $remoteTypeChanged = $remoteType !== $baseType;
        $localHasCustomization = $lc !== null; // ANY difference from BASE (props and/or move and/or type)

        // Phase 3D-E §14: covers BOTH the original single-sided case (REMOTE
        // changed the type, LOCAL didn't) AND the audited both-sides case
        // (REMOTE and LOCAL each changed the type, but to DIFFERENT values)
        // — in either case, merging payload properties across mismatched
        // type schemas is unsafe, so any time REMOTE's type genuinely
        // disagrees with the type this id will end up needing, the merge
        // is skipped in favor of the same clean-upgrade-or-detach handling.
        // (Both sides changing to the SAME new type is deliberately NOT
        // routed here — $localType === $remoteType falls through to the
        // ordinary merge below, since there is nothing to detach: LOCAL and
        // REMOTE already agree on the one type the block should have.)
        if ($remoteTypeChanged && $localType !== $remoteType) {
            if (!$localHasCustomization) {
                // Clean upgrade: take REMOTE's new type and content wholesale.
                $preserved[] = ['type' => 'noop_remote_type_upgrade', 'block_id' => $id];
                return $this->fromIndex($remote, self::SIDE_REMOTE);
            }

            // REMOTE changed the type; LOCAL customized the block under a
            // different (old-base, or its own independently-changed) type.
            // Never migrate props across types — REMOTE's new type becomes
            // the canonical node at this id, and the customer's version
            // survives, detached, under a fresh id.
            $newId = $this->deriveDetachedId($id, ThemeUpgradeConflict::TYPE_CHANGE_WITH_LOCAL_CUSTOMIZATION, $allKnownIds);
            $allKnownIds[$newId] = true;
            $originLocation = [
                'region' => $local['region'],
                'page_slug' => $local['page_slug'],
                'parent_id' => $local['parent_id'],
            ];
            $conflicts[] = ThemeUpgradeConflict::typeChangeWithLocalCustomization(
                $id,
                $baseType,
                $localType,
                $remoteType,
                $newId,
                bothSidesChangedType: $localTypeChanged
            );
            $detached[] = [
                'id' => $newId,
                'origin_id' => $id,
                'reason' => ThemeUpgradeConflict::TYPE_CHANGE_WITH_LOCAL_CUSTOMIZATION,
                'origin_location' => $originLocation,
                'conflict_index' => count($conflicts) - 1,
            ];

            // The detached copy is resolved as an ordinary standalone block
            // (its own payload reconciliation doesn't apply — nothing to
            // three-way-merge against once it's split off).
            $detachedResolved = $this->fromIndex($local, self::SIDE_LOCAL);

            // Stash the detached block via a side-channel: the caller loop
            // only expects one return per call, so we fold it into the
            // canonical return using a reserved key convention consumed by
            // resolveBlocks()/flattenDetached().
            return [
                '__also' => [$newId => $detachedResolved],
            ] + $this->fromIndex($remote, self::SIDE_REMOTE);
        }

        // Every other type combination (unchanged on both, local-only
        // changed, or both changed identically) falls through to the
        // ordinary property/placement three-way merge below, using
        // whichever type value the following resolution settles on.
        $resolvedType = $this->resolveScalarThreeWay($baseType, $localType, $remoteType, $id, 'type', $conflicts);

        // Block payload fields have no "addition" reporting category in
        // the approved contract (§7 only names new blocks/pages/global-
        // style/settings fields) — the reference param is required by
        // reconcileNested()'s shared signature but discarded here.
        $discardedRemoteAdditions = [];
        $payload = $this->reconcileBlockPayload($id, $base['payload'] ?? [], $local['payload'], $remote['payload'], $conflicts, $preserved, $discardedRemoteAdditions);
        $placement = $this->reconcilePlacement($id, $base, $local, $remote, $conflicts, $preserved);

        return [
            'type' => $resolvedType,
            'payload' => $payload,
            'placement' => $placement['placement'],
            'anchor_source' => $placement['anchor_source'],
        ];
    }

    /**
     * Property-path-level three-way merge of one block's payload — the
     * exact rule from the approved design, applied per path:
     * A) neither side changed a path → BASE's value.
     * B) only LOCAL changed it → LOCAL's value.
     * C) only REMOTE changed it → REMOTE's value.
     * D) both changed it identically → that value.
     * E) both changed it differently → LOCAL's value + PROPERTY_CONFLICT.
     */
    private function reconcileBlockPayload(string $blockId, array $base, array $local, array $remote, array &$conflicts, array &$preserved, array &$remoteAdditions): array
    {
        [$merged] = $this->reconcileNested($base, $local, $remote, null, $conflicts, $preserved, $remoteAdditions, $blockId);
        return $merged;
    }

    private function reconcilePlacement(string $id, array $base, array $local, array $remote, array &$conflicts, array &$preserved): array
    {
        $baseTuple = [$base['region'] ?? null, $base['page_slug'] ?? null, $base['parent_id'] ?? null];
        $localTuple = [$local['region'], $local['page_slug'], $local['parent_id']];
        $remoteTuple = [$remote['region'], $remote['page_slug'], $remote['parent_id']];

        $localMoved = $localTuple !== $baseTuple;
        $remoteMoved = $remoteTuple !== $baseTuple;

        if (!$localMoved && !$remoteMoved) {
            return ['placement' => ['region' => $base['region'], 'page_slug' => $base['page_slug'], 'parent_id' => $base['parent_id']], 'anchor_source' => null];
        }
        if ($localMoved && !$remoteMoved) {
            $preserved[] = ['type' => 'block_moved', 'block_id' => $id, 'side' => 'local'];
            return ['placement' => ['region' => $local['region'], 'page_slug' => $local['page_slug'], 'parent_id' => $local['parent_id']], 'anchor_source' => self::SIDE_LOCAL];
        }
        if (!$localMoved && $remoteMoved) {
            return ['placement' => ['region' => $remote['region'], 'page_slug' => $remote['page_slug'], 'parent_id' => $remote['parent_id']], 'anchor_source' => self::SIDE_REMOTE];
        }
        if ($localTuple === $remoteTuple) {
            return ['placement' => ['region' => $local['region'], 'page_slug' => $local['page_slug'], 'parent_id' => $local['parent_id']], 'anchor_source' => self::SIDE_LOCAL];
        }

        $conflicts[] = ThemeUpgradeConflict::structuralMoveConflict($id, $baseTuple, $localTuple, $remoteTuple);
        return ['placement' => ['region' => $local['region'], 'page_slug' => $local['page_slug'], 'parent_id' => $local['parent_id']], 'anchor_source' => self::SIDE_LOCAL];
    }

    private function resolveScalarThreeWay(mixed $base, mixed $local, mixed $remote, string $blockId, string $fieldPath, array &$conflicts): mixed
    {
        $localChanged = $local !== $base;
        $remoteChanged = $remote !== $base;

        if (!$localChanged && !$remoteChanged) {
            return $base;
        }
        if ($localChanged && !$remoteChanged) {
            return $local;
        }
        if (!$localChanged && $remoteChanged) {
            return $remote;
        }
        if ($local === $remote) {
            return $local;
        }

        $conflicts[] = ThemeUpgradeConflict::propertyConflict($blockId, $fieldPath, $base, $local, $remote);
        return $local;
    }

    // ── Generic nested three-way merge (block payload, global_styles, settings) ─

    private const MISSING = "\0__missing__\0";

    /**
     * @param array &$remoteAdditions Only ever appended to for a section-
     *   level (global_styles/settings — $blockId === null) field that is
     *   genuinely new (absent from both BASE and LOCAL) — an ordinary
     *   REMOTE edit to an already-existing field is never misreported as
     *   an "addition" (Phase 3D-E §7). Block payload fields are never
     *   reported here (no such category was named in the approved
     *   contract) — pass an empty throwaway array from block-payload call
     *   sites.
     * @return array{0: array, 1: null} merged value (second slot unused,
     *   kept for call-site symmetry with earlier drafts).
     */
    private function reconcileNested(array $base, array $local, array $remote, ?string $sectionLabel, array &$conflicts, array &$preserved, array &$remoteAdditions, ?string $blockId = null, string $pathPrefix = ''): array
    {
        $keys = array_keys($base + $local + $remote);
        sort($keys, SORT_STRING);

        $merged = [];

        foreach ($keys as $key) {
            $path = $pathPrefix === '' ? (string) $key : $pathPrefix . '.' . $key;
            $hasBase = array_key_exists($key, $base);
            $hasLocal = array_key_exists($key, $local);
            $hasRemote = array_key_exists($key, $remote);

            $baseValue = $hasBase ? $base[$key] : self::MISSING;
            $localValue = $hasLocal ? $local[$key] : self::MISSING;
            $remoteValue = $hasRemote ? $remote[$key] : self::MISSING;

            if ($hasBase && $hasLocal && $hasRemote
                && is_array($baseValue) && is_array($localValue) && is_array($remoteValue)
                && $this->isAssoc($baseValue) && $this->isAssoc($localValue) && $this->isAssoc($remoteValue)
            ) {
                [$nested] = $this->reconcileNested($baseValue, $localValue, $remoteValue, $sectionLabel, $conflicts, $preserved, $remoteAdditions, $blockId, $path);
                if ($nested !== []) {
                    $merged[$key] = $nested;
                }
                continue;
            }

            $localChanged = $localValue !== $baseValue;
            $remoteChanged = $remoteValue !== $baseValue;

            if (!$localChanged && !$remoteChanged) {
                if ($hasBase) {
                    $merged[$key] = $baseValue;
                }
                continue;
            }

            if ($localChanged && !$remoteChanged) {
                if ($hasLocal) {
                    $merged[$key] = $localValue;
                }
                $this->recordPreserved($preserved, $sectionLabel, $blockId, $path);
                continue;
            }

            if (!$localChanged && $remoteChanged) {
                if ($hasRemote) {
                    $merged[$key] = $remoteValue;
                }
                if ($blockId === null && $sectionLabel !== null && !$hasBase && !$hasLocal) {
                    $remoteAdditions[] = ['type' => 'field_added', 'section' => $sectionLabel, 'field_path' => $path];
                }
                continue;
            }

            if ($localValue === $remoteValue) {
                if ($hasLocal) {
                    $merged[$key] = $localValue;
                }
                continue;
            }

            // Both changed, differently — LOCAL wins by default, conflict recorded.
            $conflicts[] = ThemeUpgradeConflict::propertyConflict(
                $blockId,
                $path,
                $hasBase ? $baseValue : null,
                $hasLocal ? $localValue : null,
                $hasRemote ? $remoteValue : null
            );
            if ($hasLocal) {
                $merged[$key] = $localValue;
            }
        }

        return [$merged, null];
    }

    private function recordPreserved(array &$preserved, ?string $sectionLabel, ?string $blockId, string $path): void
    {
        $preserved[] = $blockId !== null
            ? ['type' => 'property', 'block_id' => $blockId, 'field_path' => $path]
            : ['type' => 'global_field', 'section' => $sectionLabel, 'field_path' => $path];
    }

    private function isAssoc(array $value): bool
    {
        return $value !== [] && array_keys($value) !== range(0, count($value) - 1);
    }

    // ── Deterministic detached-id derivation ─────────────────────────────

    private function deriveDetachedId(string $sourceId, string $conflictType, array $existingIds): string
    {
        $base = 'cust_reconcile_' . substr(hash('sha256', $conflictType . '|' . $sourceId), 0, 16);
        $candidate = $base;
        $suffix = 0;
        while (isset($existingIds[$candidate])) {
            $suffix++;
            $candidate = $base . '_' . $suffix;
        }
        return $candidate;
    }

    /**
     * Unwraps the reserved '__also' side-payload a type-change detachment
     * (reconcileExistingBlock()) attaches to its canonical id's entry,
     * producing a fully flat id => entry map before anything downstream
     * needs to see every surviving id (page assembly, orphan reparenting).
     */
    private function flattenDetached(array $resolved): array
    {
        $flat = [];
        foreach ($resolved as $id => $entry) {
            if (isset($entry['__also'])) {
                foreach ($entry['__also'] as $alsoId => $alsoEntry) {
                    $flat[$alsoId] = $alsoEntry;
                }
                unset($entry['__also']);
            }
            $flat[$id] = $entry;
        }
        return $flat;
    }

    /**
     * §11 — a resolved block whose parent_id no longer exists among the
     * surviving blocks (the container itself was deleted/never carried
     * forward) is walked up its ORIGINAL parent chain — using whichever
     * source snapshot(s) it can be found in — until a still-surviving
     * ancestor is found, or the chain is exhausted (promoted to that
     * block's own region/page root). Every reparent is recorded BOTH as a
     * STRUCTURAL_PARENT_REMOVED conflict (a decision needing visibility)
     * and a structural fallback entry (§9 — exactly which auto-relocation
     * mechanism fired) — the two convey genuinely different information,
     * never a duplicate of the same fact.
     */
    private function reparentOrphans(array $resolved, array $baseIndex, array $localIndex, array $remoteIndex, array &$conflicts, array &$fallbacks): array
    {
        $survivingIds = array_fill_keys(array_keys($resolved), true);

        foreach ($resolved as $id => $entry) {
            $parentId = $entry['placement']['parent_id'];
            if ($parentId === null || isset($survivingIds[$parentId])) {
                continue;
            }

            $removedChain = [];
            $walkId = $parentId;
            $survivingAncestor = null;
            $guard = 0;

            while ($walkId !== null && $guard < 1000) {
                $guard++;
                if (isset($survivingIds[$walkId])) {
                    $survivingAncestor = $walkId;
                    break;
                }
                $removedChain[] = $walkId;
                $ancestor = $localIndex[$walkId] ?? $remoteIndex[$walkId] ?? $baseIndex[$walkId] ?? null;
                $walkId = $ancestor['parent_id'] ?? null;
            }

            $resolved[$id]['placement']['parent_id'] = $survivingAncestor;

            $conflicts[] = ThemeUpgradeConflict::structuralParentRemoved($id, $removedChain, $survivingAncestor);
            $fallbacks[] = [
                'block_id' => $id,
                'reason' => 'parent_removed',
                'fallback' => $survivingAncestor !== null ? 'nearest_surviving_ancestor' : 'root_promotion',
                'removed_parent_chain' => $removedChain,
                'new_parent_id' => $survivingAncestor,
            ];
        }

        return $resolved;
    }

    /**
     * Enriches each detached-block report (§8) with its actual final
     * placement (only known after orphan reparenting has run) and a direct
     * index into `conflicts` for the conflict that explains it — so a
     * future preview can show the full base/local/remote values without
     * re-deriving them.
     */
    private function enrichDetached(array $detached, array $resolved): array
    {
        foreach ($detached as &$entry) {
            $entry['final_location'] = $resolved[$entry['id']]['placement'] ?? null;
        }
        unset($entry);

        return $detached;
    }

    // ── Phase 2: page-level existence + metadata ─────────────────────────

    /**
     * @return array<string, array> slug => merged page metadata (title etc,
     *   never 'blocks' — that's assembled separately from resolved blocks).
     */
    private function resolvePages(
        array $basePages,
        array $localPages,
        array $remotePages,
        array $resolvedBlocks,
        array &$conflicts,
        array &$preserved,
        array &$remoteAdditions,
        array &$warnings
    ): array {
        $slugs = array_keys($basePages + $localPages + $remotePages);
        sort($slugs, SORT_STRING);

        $result = [];

        foreach ($slugs as $slug) {
            $inBase = array_key_exists($slug, $basePages);
            $inLocal = array_key_exists($slug, $localPages);
            $inRemote = array_key_exists($slug, $remotePages);

            if (!$inBase && $inLocal && !$inRemote) {
                $result[$slug] = $this->pageMeta($localPages[$slug]);
                $preserved[] = ['type' => 'page_added', 'slug' => $slug, 'side' => 'local'];
                continue;
            }
            if (!$inBase && !$inLocal && $inRemote) {
                $result[$slug] = $this->pageMeta($remotePages[$slug]);
                $remoteAdditions[] = ['type' => 'page_added', 'slug' => $slug];
                continue;
            }
            if ($inBase && !$inLocal && !$inRemote) {
                continue; // agreed removal.
            }
            if ($inBase && !$inLocal && $inRemote) {
                continue; // customer removed the page; remote never revives it.
            }
            if ($inBase && $inLocal && !$inRemote) {
                // REMOTE removed this page. If the customer left it exactly
                // as authored, honor the removal. If the customer put
                // anything of their own here (content changes, or any
                // surviving block still resolved onto this page), never
                // silently discard it — keep the page and flag a conflict.
                $hasCustomerContentHere = $this->pageHasCustomerContent($slug, $resolvedBlocks)
                    || $this->pageMeta($localPages[$slug]) !== $this->pageMeta($basePages[$slug]);

                if (!$hasCustomerContentHere) {
                    continue;
                }

                $result[$slug] = $this->pageMeta($localPages[$slug]);
                $conflicts[] = ThemeUpgradeConflict::pageRemovalConflict(
                    $slug,
                    $this->pageMeta($basePages[$slug]),
                    $this->pageMeta($localPages[$slug])
                );
                // Structured, not pre-formatted text (§5/§10) — the future
                // preview UI renders the human-readable message from this.
                $warnings[] = [
                    'type' => 'page_kept_alive',
                    'slug' => $slug,
                    'reason' => 'removed_upstream_but_has_customer_content',
                ];
                continue;
            }

            // Present in all three.
            [$merged] = $this->reconcileNested(
                $this->pageMeta($basePages[$slug]),
                $this->pageMeta($localPages[$slug]),
                $this->pageMeta($remotePages[$slug]),
                'pages.' . $slug,
                $conflicts,
                $preserved,
                $remoteAdditions
            );
            $result[$slug] = $merged;
        }

        return $result;
    }

    private function pageHasCustomerContent(string $slug, array $resolvedBlocks): bool
    {
        foreach ($resolvedBlocks as $block) {
            if (($block['placement']['page_slug'] ?? null) === $slug) {
                return true;
            }
        }
        return false;
    }

    private function pageMeta(mixed $page): array
    {
        return is_array($page) ? array_diff_key($page, ['blocks' => true]) : [];
    }

    // ── Phase 3: tree assembly (bucket ordering + nesting) ───────────────

    private function assembleTree(
        array $resolved,
        array $resolvedPages,
        array $baseIndex,
        array $localIndex,
        array $remoteIndex,
        array &$fallbacks
    ): array {
        // $resolved is already flat (flattenDetached()) and orphan-safe
        // (reparentOrphans()) by the time it reaches here.

        // Group by bucket (region|page_slug|parent_id).
        $buckets = [];
        foreach ($resolved as $id => $entry) {
            $key = $this->bucketKey($entry['placement']);
            $buckets[$key][] = $id;
        }

        $orderedByBucket = [];
        foreach ($buckets as $key => $memberIds) {
            $orderedByBucket[$key] = $this->orderBucket($key, $memberIds, $resolved, $baseIndex, $localIndex, $remoteIndex, $fallbacks);
        }

        $buildChildren = function (string $region, ?string $pageSlug, ?string $parentId) use (&$buildChildren, $orderedByBucket, $resolved): array {
            $key = $this->bucketKeyRaw($region, $pageSlug, $parentId);
            $ids = $orderedByBucket[$key] ?? [];
            $nodes = [];
            foreach ($ids as $id) {
                $entry = $resolved[$id];
                $node = ['id' => $id, 'type' => $entry['type']] + $entry['payload'];
                $children = $buildChildren($region, $pageSlug, $id);
                if ($children !== []) {
                    $node['children'] = $children;
                }
                $nodes[] = $node;
            }
            return $nodes;
        };

        $headerBlocks = $buildChildren(ThemeBlockIndex::REGION_HEADER, null, null);
        $footerBlocks = $buildChildren(ThemeBlockIndex::REGION_FOOTER, null, null);

        $pages = [];
        foreach ($resolvedPages as $slug => $meta) {
            $pages[$slug] = $meta + ['blocks' => $buildChildren(ThemeBlockIndex::REGION_PAGE, $slug, null)];
        }

        return [
            'header_blocks' => $headerBlocks,
            'footer_blocks' => $footerBlocks,
            'pages' => $pages,
        ];
    }

    private function bucketKey(array $placement): string
    {
        return $this->bucketKeyRaw($placement['region'], $placement['page_slug'], $placement['parent_id']);
    }

    private function bucketKeyRaw(string $region, ?string $pageSlug, ?string $parentId): string
    {
        return $region . '|' . ($pageSlug ?? '') . '|' . ($parentId ?? '');
    }

    /**
     * Orders one bucket's members: a "backbone" of blocks whose placement
     * is unchanged from BASE across all three sides (ordered via the same
     * three-way rule applied to their relative sequence, so pure sibling
     * insertions elsewhere never falsely register as a reorder), then every
     * other member (new, or relocated into this bucket) anchor-interleaved
     * relative to whichever side decided its placement.
     */
    private function orderBucket(string $bucketKey, array $memberIds, array $resolved, array $baseIndex, array $localIndex, array $remoteIndex, array &$fallbacks): array
    {
        $memberSet = array_fill_keys($memberIds, true);

        $backboneIds = [];
        $toInterleave = [];
        foreach ($memberIds as $id) {
            $inBase = array_key_exists($id, $baseIndex);
            $anchorSource = $resolved[$id]['anchor_source'] ?? null;
            if ($inBase && $anchorSource === null) {
                $backboneIds[] = $id;
            } else {
                $toInterleave[] = $id;
            }
        }

        $backboneOrder = $this->resolveBackboneOrder($backboneIds, $baseIndex, $localIndex, $remoteIndex, $memberSet);

        // Deterministic processing order for interleaving: by the id's
        // position within its own anchor-source array (so an anchor is
        // always processed, and therefore already placed, before anything
        // that anchors onto it), ties broken by id.
        usort($toInterleave, function ($a, $b) use ($resolved, $localIndex, $remoteIndex) {
            $posA = $this->sourcePosition($a, $resolved[$a]['anchor_source'] ?? self::SIDE_LOCAL, $localIndex, $remoteIndex);
            $posB = $this->sourcePosition($b, $resolved[$b]['anchor_source'] ?? self::SIDE_LOCAL, $localIndex, $remoteIndex);
            return $posA <=> $posB ?: $a <=> $b;
        });

        $order = $backboneOrder;
        foreach ($toInterleave as $id) {
            $order = $this->insertWithAnchor($id, $order, $resolved, $baseIndex, $localIndex, $remoteIndex, $memberSet, $fallbacks);
        }

        return $order;
    }

    private function sourcePosition(string $id, ?string $side, array $localIndex, array $remoteIndex): int
    {
        $index = $side === self::SIDE_REMOTE ? $remoteIndex : $localIndex;
        return $index[$id]['position'] ?? PHP_INT_MAX;
    }

    /**
     * @return string[] ordered ids
     */
    private function resolveBackboneOrder(array $backboneIds, array $baseIndex, array $localIndex, array $remoteIndex, array $memberSet): array
    {
        if ($backboneIds === []) {
            return [];
        }

        $baseOrder = $backboneIds;
        usort($baseOrder, fn ($a, $b) => $baseIndex[$a]['position'] <=> $baseIndex[$b]['position']);

        $localOrder = $this->orderWithin($backboneIds, $localIndex);
        $remoteOrder = $this->orderWithin($backboneIds, $remoteIndex);

        $localChanged = $localOrder !== $baseOrder;
        $remoteChanged = $remoteOrder !== $baseOrder;

        if (!$localChanged && !$remoteChanged) {
            return $baseOrder;
        }
        if ($localChanged && !$remoteChanged) {
            return $localOrder;
        }
        if (!$localChanged && $remoteChanged) {
            return $remoteOrder;
        }
        if ($localOrder === $remoteOrder) {
            return $localOrder;
        }

        // Conflicting reorder among never-reparented siblings — LOCAL wins
        // by default, deterministically, same as every other conflict rule
        // in this class. (Recorded as a STRUCTURAL_MOVE_CONFLICT only when
        // a block's bucket itself changed — see reconcilePlacement(); a
        // pure reorder within an unchanged bucket has no per-block
        // base/local/remote placement tuple to attach a conflict object
        // to, since every member of the backbone individually reports
        // "unmoved". The result itself is still never silently REMOTE's.)
        return $localOrder;
    }

    private function orderWithin(array $ids, array $index): array
    {
        $present = array_values(array_filter($ids, fn ($id) => array_key_exists($id, $index)));
        usort($present, fn ($a, $b) => $index[$a]['position'] <=> $index[$b]['position']);
        return $present;
    }

    /**
     * §10 anchor-removal fallback, applied uniformly whether the anchor
     * simply never existed in the final bucket or existed but was itself
     * excluded (deleted/detached/relocated elsewhere).
     */
    private function insertWithAnchor(string $id, array $order, array $resolved, array $baseIndex, array $localIndex, array $remoteIndex, array $memberSet, array &$fallbacks): array
    {
        $side = $resolved[$id]['anchor_source'] ?? self::SIDE_LOCAL;
        $sourceIndex = $side === self::SIDE_REMOTE ? $remoteIndex : $localIndex;

        if (!isset($sourceIndex[$id])) {
            $order[] = $id;
            return $order;
        }

        $siblingIdsBySourcePosition = array_keys($sourceIndex);
        usort($siblingIdsBySourcePosition, fn ($a, $b) => $sourceIndex[$a]['position'] <=> $sourceIndex[$b]['position']);
        $siblingIdsBySourcePosition = array_values(array_filter(
            $siblingIdsBySourcePosition,
            fn ($sid) => $sourceIndex[$sid]['region'] === $sourceIndex[$id]['region']
                && $sourceIndex[$sid]['page_slug'] === $sourceIndex[$id]['page_slug']
                && $sourceIndex[$sid]['parent_id'] === $sourceIndex[$id]['parent_id']
        ));
        $selfPos = array_search($id, $siblingIdsBySourcePosition, true);

        // Nearest preceding sibling (in source order) that is already
        // placed in this bucket's order.
        for ($i = $selfPos - 1; $i >= 0; $i--) {
            $candidate = $siblingIdsBySourcePosition[$i];
            $pos = array_search($candidate, $order, true);
            if ($pos !== false) {
                array_splice($order, $pos + 1, 0, [$id]);
                return $order;
            }
        }

        // Nearest following sibling already placed — insert immediately before it.
        for ($i = $selfPos + 1; $i < count($siblingIdsBySourcePosition); $i++) {
            $candidate = $siblingIdsBySourcePosition[$i];
            $pos = array_search($candidate, $order, true);
            if ($pos !== false) {
                array_splice($order, $pos, 0, [$id]);
                $fallbacks[] = [
                    'block_id' => $id,
                    'reason' => 'anchor_removed',
                    'fallback' => 'nearest_following_sibling',
                    'anchor_side' => $side,
                ];
                return $order;
            }
        }

        // No surviving sibling evidence at all — append within the bucket.
        $order[] = $id;
        $fallbacks[] = [
            'block_id' => $id,
            'reason' => 'anchor_removed',
            'fallback' => 'appended_to_region',
            'anchor_side' => $side,
        ];
        return $order;
    }
}
