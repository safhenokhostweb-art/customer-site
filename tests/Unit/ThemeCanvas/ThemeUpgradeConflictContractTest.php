<?php

namespace Tests\Unit\ThemeCanvas;

use App\Services\ThemeCanvas\ThemeUpgradeConflict;
use App\Services\ThemeCanvas\ThemeUpgradeReconciler;
use PHPUnit\Framework\TestCase;

/**
 * Phase 3D-E — conflict/reporting contract hardening: severity, canonical
 * resolutions, serialization, and the deterministic fingerprint. Exercises
 * the real reconciler wherever a scenario is genuinely reachable through
 * it; synthetic ThemeUpgradeConflict factory calls only for the two
 * reserved, not-yet-produced blocking types (dependency availability is
 * explicitly out of scope until Phase 3E).
 */
class ThemeUpgradeConflictContractTest extends TestCase
{
    private function reconciler(): ThemeUpgradeReconciler
    {
        return new ThemeUpgradeReconciler();
    }

    private function snapshot(array $overrides = []): array
    {
        return array_merge([
            'header_blocks' => [],
            'footer_blocks' => [],
            'pages' => [],
            'global_styles' => [],
            'settings' => [],
        ], $overrides);
    }

    private function block(string $id, string $type, array $extra = []): array
    {
        return array_merge(['id' => $id, 'type' => $type], $extra);
    }

    private function page(array $blocks): array
    {
        return ['blocks' => $blocks];
    }

    private const ALL_CANONICAL_TYPES = [
        ThemeUpgradeConflict::PROPERTY_CONFLICT,
        ThemeUpgradeConflict::STRUCTURAL_MOVE_CONFLICT,
        ThemeUpgradeConflict::MASTER_DELETED_LOCAL_MODIFIED,
        ThemeUpgradeConflict::LOCAL_DELETED_REMOTE_MODIFIED,
        ThemeUpgradeConflict::TYPE_CHANGE_WITH_LOCAL_CUSTOMIZATION,
        ThemeUpgradeConflict::STRUCTURAL_PARENT_REMOVED,
        ThemeUpgradeConflict::PAGE_REMOVAL_CONFLICT,
    ];

    /**
     * One reconciliation exercising every one of the 7 canonical conflict
     * types at once, each from exactly one distinct block/page so tests
     * can look up a specific conflict by its origin without ambiguity.
     */
    private function reconcileEveryConflictType(): \App\Services\ThemeCanvas\ThemeUpgradeReconciliationResult
    {
        $base = $this->snapshot([
            'pages' => [
                'home' => $this->page([
                    $this->block('prop', 'text', ['props' => ['title' => 'A']]),
                    $this->block('moveme', 'text'),
                    $this->block('masterdel', 'text', ['props' => ['x' => 'A']]),
                    $this->block('localdel', 'text', ['props' => ['x' => 'A']]),
                    $this->block('typechange', 'hero', ['props' => ['title' => 'A']]),
                    $this->block('parentbox', 'section', ['children' => []]),
                ]),
                'removedpage' => ['title' => 'Original Title', 'blocks' => []],
            ],
        ]);
        $local = $this->snapshot([
            'pages' => [
                'home' => $this->page([
                    $this->block('prop', 'text', ['props' => ['title' => 'Customer']]),
                    $this->block('masterdel', 'text', ['props' => ['x' => 'Customer']]),
                    $this->block('typechange', 'hero', ['props' => ['title' => 'Customer']]),
                    $this->block('parentbox', 'section', ['children' => [
                        $this->block('cust_orphan', 'text', ['props' => ['x' => 'new']]),
                    ]]),
                ]),
                'pagelocal' => $this->page([$this->block('moveme', 'text')]),
                'removedpage' => ['title' => 'Customer Renamed Title', 'blocks' => []],
                // localdel deliberately absent — the customer deleted it.
            ],
        ]);
        $remote = $this->snapshot([
            'pages' => [
                'home' => $this->page([
                    $this->block('prop', 'text', ['props' => ['title' => 'Master']]),
                    $this->block('localdel', 'text', ['props' => ['x' => 'Modified by master']]),
                    $this->block('typechange', 'slider', ['props' => ['slides' => []]]),
                ]),
                'pageremote' => $this->page([$this->block('moveme', 'text')]),
                // masterdel and parentbox (with it, cust_orphan's original
                // container) removed upstream; removedpage removed upstream too.
            ],
        ]);

        return $this->reconciler()->reconcile($base, $local, $remote);
    }

    // ── A. every conflict type has a valid canonical type ────────────────

    public function test_a_every_produced_conflict_has_a_canonical_type(): void
    {
        $result = $this->reconcileEveryConflictType();

        $this->assertNotEmpty($result->conflicts);
        foreach ($result->conflicts as $conflict) {
            $this->assertContains($conflict->conflictType, self::ALL_CANONICAL_TYPES);
        }

        $seenTypes = array_unique(array_map(fn ($c) => $c->conflictType, $result->conflicts));
        $this->assertEqualsCanonicalizing(self::ALL_CANONICAL_TYPES, $seenTypes, 'this fixture is designed to exercise every one of the 7 canonical conflict types exactly once');
        $this->assertCount(7, $result->conflicts, 'each of the 7 types is produced by exactly one distinct block/page in this fixture');
    }

    // ── B. every conflict has a canonical chosen_resolution ──────────────

    public function test_b_every_non_blocking_conflict_has_a_canonical_resolution(): void
    {
        $canonicalResolutions = [
            ThemeUpgradeConflict::RESOLUTION_KEEP_LOCAL,
            ThemeUpgradeConflict::RESOLUTION_KEEP_REMOTE,
            ThemeUpgradeConflict::RESOLUTION_KEEP_DELETED,
            ThemeUpgradeConflict::RESOLUTION_PRESERVE_DETACHED,
            ThemeUpgradeConflict::RESOLUTION_PRESERVE_BOTH,
            ThemeUpgradeConflict::RESOLUTION_REPARENT,
            ThemeUpgradeConflict::RESOLUTION_KEEP_PAGE,
        ];

        $result = $this->reconcileEveryConflictType();

        foreach ($result->conflicts as $conflict) {
            $this->assertNotNull($conflict->chosenResolution);
            $this->assertContains($conflict->chosenResolution, $canonicalResolutions, "conflict type {$conflict->conflictType} must resolve to a canonical resolution, not free text");
        }
    }

    public function test_b_conflict_type_to_resolution_mapping_is_fixed(): void
    {
        $result = $this->reconcileEveryConflictType();
        $byType = [];
        foreach ($result->conflicts as $c) {
            $byType[$c->conflictType] = $c->chosenResolution;
        }

        $this->assertSame(ThemeUpgradeConflict::RESOLUTION_KEEP_LOCAL, $byType[ThemeUpgradeConflict::PROPERTY_CONFLICT]);
        $this->assertSame(ThemeUpgradeConflict::RESOLUTION_PRESERVE_DETACHED, $byType[ThemeUpgradeConflict::MASTER_DELETED_LOCAL_MODIFIED]);
        $this->assertSame(ThemeUpgradeConflict::RESOLUTION_KEEP_DELETED, $byType[ThemeUpgradeConflict::LOCAL_DELETED_REMOTE_MODIFIED]);
        $this->assertSame(ThemeUpgradeConflict::RESOLUTION_PRESERVE_BOTH, $byType[ThemeUpgradeConflict::TYPE_CHANGE_WITH_LOCAL_CUSTOMIZATION]);
        $this->assertSame(ThemeUpgradeConflict::RESOLUTION_REPARENT, $byType[ThemeUpgradeConflict::STRUCTURAL_PARENT_REMOVED]);
        $this->assertSame(ThemeUpgradeConflict::RESOLUTION_KEEP_PAGE, $byType[ThemeUpgradeConflict::PAGE_REMOVAL_CONFLICT]);
    }

    // ── C/D/E. current auto-resolved conflicts are all NON_BLOCKING ──────

    public function test_c_property_conflict_is_non_blocking(): void
    {
        $result = $this->reconcileEveryConflictType();
        $c = current(array_filter($result->conflicts, fn ($x) => $x->conflictType === ThemeUpgradeConflict::PROPERTY_CONFLICT));
        $this->assertFalse($c->isBlocking());
        $this->assertSame(ThemeUpgradeConflict::SEVERITY_NON_BLOCKING, $c->severity);
    }

    public function test_d_structural_move_conflict_is_non_blocking(): void
    {
        $base = $this->snapshot(['pages' => ['a' => $this->page([$this->block('b1', 'text')])]]);
        $local = $this->snapshot(['pages' => ['a' => $this->page([]), 'b' => $this->page([$this->block('b1', 'text')])]]);
        $remote = $this->snapshot(['pages' => ['a' => $this->page([]), 'c' => $this->page([$this->block('b1', 'text')])]]);

        $result = $this->reconciler()->reconcile($base, $local, $remote);
        $c = current(array_filter($result->conflicts, fn ($x) => $x->conflictType === ThemeUpgradeConflict::STRUCTURAL_MOVE_CONFLICT));
        $this->assertNotFalse($c);
        $this->assertFalse($c->isBlocking());
    }

    public function test_e_detached_preservation_conflict_is_non_blocking(): void
    {
        $result = $this->reconcileEveryConflictType();
        $c = current(array_filter($result->conflicts, fn ($x) => $x->conflictType === ThemeUpgradeConflict::MASTER_DELETED_LOCAL_MODIFIED));
        $this->assertFalse($c->isBlocking());
    }

    // ── F/G/H. hasConflicts / hasBlockingConflicts / canApply ────────────

    public function test_f_has_conflicts_true_when_appropriate(): void
    {
        $result = $this->reconcileEveryConflictType();
        $this->assertTrue($result->hasConflicts());

        $clean = $this->snapshot();
        $cleanResult = $this->reconciler()->reconcile($clean, $clean, $clean);
        $this->assertFalse($cleanResult->hasConflicts());
    }

    public function test_g_has_blocking_conflicts_false_for_current_auto_resolved_conflicts(): void
    {
        $result = $this->reconcileEveryConflictType();
        $this->assertTrue($result->hasConflicts());
        $this->assertFalse($result->hasBlockingConflicts(), 'every conflict type the reconciler currently produces is auto-resolved and non-blocking');
    }

    public function test_h_can_apply_true_for_valid_auto_resolved_reconciliation(): void
    {
        $result = $this->reconcileEveryConflictType();
        $this->assertTrue($result->canApply(), 'canApply() must not be false merely because ordinary conflicts exist');
    }

    // ── I/J. synthetic blocking conflict (reserved, not yet produced) ────

    public function test_i_synthetic_blocking_conflict_makes_has_blocking_conflicts_true(): void
    {
        $blocking = ThemeUpgradeConflict::dependencyUnavailable('m1', 'motion_asset', 'medical-hero-intro');
        $this->assertTrue($blocking->isBlocking());
        $this->assertSame(ThemeUpgradeConflict::SEVERITY_BLOCKING, $blocking->severity);

        $result = $this->reconcileEveryConflictType();
        $withBlocking = new \App\Services\ThemeCanvas\ThemeUpgradeReconciliationResult(
            reconciledTheme: $result->reconciledTheme,
            conflicts: [...$result->conflicts, $blocking],
            preservedCustomizations: $result->preservedCustomizations,
            remoteAdditions: $result->remoteAdditions,
            detachedBlocks: $result->detachedBlocks,
            structuralFallbacks: $result->structuralFallbacks,
            warnings: $result->warnings,
            fingerprint: $result->fingerprint,
        );

        $this->assertTrue($withBlocking->hasBlockingConflicts());
    }

    public function test_j_synthetic_blocking_conflict_makes_can_apply_false(): void
    {
        $blocking = ThemeUpgradeConflict::incompatibleDependency('m1', 'motion_asset', 'medical-hero-intro', '>=2.0', '1.0');

        $result = $this->reconcileEveryConflictType();
        $withBlocking = new \App\Services\ThemeCanvas\ThemeUpgradeReconciliationResult(
            reconciledTheme: $result->reconciledTheme,
            conflicts: [$blocking],
            preservedCustomizations: [],
            remoteAdditions: [],
            detachedBlocks: [],
            structuralFallbacks: [],
            warnings: [],
            fingerprint: $result->fingerprint,
        );

        $this->assertFalse($withBlocking->canApply());
    }

    // ── K. invalid snapshot remains a hard failure ───────────────────────

    public function test_k_invalid_snapshot_is_a_hard_failure_never_canapply_true(): void
    {
        $base = $this->snapshot(['pages' => [
            'a' => $this->page([$this->block('dup', 'text')]),
            'b' => $this->page([$this->block('dup', 'heading')]),
        ]]);

        $this->expectException(\App\Services\ThemeCanvas\ThemeCustomizationDiffException::class);
        $this->reconciler()->reconcile($base, $this->snapshot(), $this->snapshot());
    }

    // ── L. preserved customization reported separately from conflict ────

    public function test_l_preserved_customization_is_never_also_reported_as_a_conflict(): void
    {
        $base = $this->snapshot(['pages' => ['home' => $this->page([$this->block('b1', 'text', ['props' => ['title' => 'A']])])]]);
        $local = $this->snapshot(['pages' => ['home' => $this->page([$this->block('b1', 'text', ['props' => ['title' => 'Customer']])])]]);
        $remote = $base;

        $result = $this->reconciler()->reconcile($base, $local, $remote);

        $this->assertSame([], $result->conflicts, 'a clean LOCAL-only change must never itself appear as a conflict');
        $preservedTypes = array_column($result->preservedCustomizations, 'type');
        $this->assertContains('property', $preservedTypes);
    }

    public function test_l_conflict_resolved_in_customers_favor_is_not_duplicated_into_preserved(): void
    {
        $base = $this->snapshot(['pages' => ['home' => $this->page([$this->block('b1', 'text', ['props' => ['title' => 'A']])])]]);
        $local = $this->snapshot(['pages' => ['home' => $this->page([$this->block('b1', 'text', ['props' => ['title' => 'Customer']])])]]);
        $remote = $this->snapshot(['pages' => ['home' => $this->page([$this->block('b1', 'text', ['props' => ['title' => 'Master']])])]]);

        $result = $this->reconciler()->reconcile($base, $local, $remote);

        $this->assertCount(1, $result->conflicts);
        $propertyPreserved = array_filter($result->preservedCustomizations, fn ($p) => ($p['type'] ?? null) === 'property' && ($p['block_id'] ?? null) === 'b1');
        $this->assertSame([], array_values($propertyPreserved), 'a genuine conflict must not ALSO be listed as a clean preserved customization');
    }

    // ── M. remote addition reporting is accurate ─────────────────────────

    public function test_m_remote_addition_reporting_excludes_ordinary_modifications(): void
    {
        $base = $this->snapshot(['global_styles' => ['primary_color' => '#111']]);
        $local = $base;
        $remote = $this->snapshot(['global_styles' => ['primary_color' => '#222', 'brand_new_field' => 'x']]);

        $result = $this->reconciler()->reconcile($base, $local, $remote);

        $additionPaths = array_column(array_filter($result->remoteAdditions, fn ($a) => ($a['type'] ?? null) === 'field_added'), 'field_path');
        $this->assertContains('brand_new_field', $additionPaths);
        $this->assertNotContains('primary_color', $additionPaths, 'an ordinary edit to an existing field must never be reported as an addition');
    }

    public function test_m_new_page_and_new_block_are_reported_as_remote_additions(): void
    {
        $result = $this->reconcileEveryConflictType();
        // moveme's page has no additions in this fixture; use a dedicated one.
        $base = $this->snapshot(['pages' => ['home' => $this->page([])]]);
        $local = $base;
        $remote = $this->snapshot(['pages' => [
            'home' => $this->page([$this->block('newblock', 'text')]),
            'newpage' => $this->page([]),
        ]]);

        $r = $this->reconciler()->reconcile($base, $local, $remote);
        $types = array_column($r->remoteAdditions, 'type');
        $this->assertContains('block_added', $types);
        $this->assertContains('page_added', $types);
    }

    // ── N. detached-block reporting is complete ──────────────────────────

    public function test_n_detached_block_report_is_complete(): void
    {
        $result = $this->reconcileEveryConflictType();
        $entry = current(array_filter($result->detachedBlocks, fn ($d) => $d['origin_id'] === 'masterdel'));

        $this->assertNotFalse($entry);
        $this->assertArrayHasKey('id', $entry);
        $this->assertStringStartsWith('cust_reconcile_', $entry['id']);
        $this->assertArrayHasKey('origin_id', $entry);
        $this->assertArrayHasKey('reason', $entry);
        $this->assertArrayHasKey('origin_location', $entry);
        $this->assertArrayHasKey('final_location', $entry);
        $this->assertArrayHasKey('conflict_index', $entry);

        $associatedConflict = $result->conflicts[$entry['conflict_index']];
        $this->assertSame(ThemeUpgradeConflict::MASTER_DELETED_LOCAL_MODIFIED, $associatedConflict->conflictType);
        $this->assertSame('masterdel', $associatedConflict->blockId);
    }

    // ── O. structural fallback reporting is complete ─────────────────────

    public function test_o_structural_fallback_reports_anchor_removed_and_parent_removed(): void
    {
        $base = $this->snapshot(['pages' => ['home' => $this->page([$this->block('a', 'text'), $this->block('b', 'text')])]]);
        $local = $this->snapshot(['pages' => ['home' => $this->page([$this->block('a', 'text'), $this->block('x', 'text'), $this->block('b', 'text')])]]);
        $remote = $this->snapshot(['pages' => ['home' => $this->page([$this->block('b', 'text')])]]);
        $anchorResult = $this->reconciler()->reconcile($base, $local, $remote);
        $this->assertNotEmpty($anchorResult->structuralFallbacks);
        $this->assertSame('anchor_removed', $anchorResult->structuralFallbacks[0]['reason']);

        $parentResult = $this->reconcileEveryConflictType();
        $parentFallback = current(array_filter($parentResult->structuralFallbacks, fn ($f) => $f['reason'] === 'parent_removed'));
        $this->assertNotFalse($parentFallback);
        $this->assertArrayHasKey('block_id', $parentFallback);
        $this->assertArrayHasKey('fallback', $parentFallback);
        $this->assertArrayHasKey('removed_parent_chain', $parentFallback);
        $this->assertArrayHasKey('new_parent_id', $parentFallback);
    }

    // ── P. warnings do not hide conflicts ────────────────────────────────

    public function test_p_warning_accompanies_but_never_replaces_its_conflict(): void
    {
        $result = $this->reconcileEveryConflictType();

        $pageConflict = current(array_filter($result->conflicts, fn ($c) => $c->conflictType === ThemeUpgradeConflict::PAGE_REMOVAL_CONFLICT));
        $this->assertNotFalse($pageConflict, 'the warning must never be a substitute for the blocking-eligible conflict record');

        $warning = current(array_filter($result->warnings, fn ($w) => ($w['type'] ?? null) === 'page_kept_alive'));
        $this->assertNotFalse($warning);
        $this->assertIsArray($warning, 'warnings must be structured data, never pre-formatted text');
        $this->assertIsString($warning['slug']);
        $this->assertIsString($warning['reason']);
    }

    // ── Q. toArray() is deterministic and JSON-safe ──────────────────────

    public function test_q_conflict_to_array_is_json_safe(): void
    {
        $result = $this->reconcileEveryConflictType();
        foreach ($result->conflicts as $conflict) {
            $array = $conflict->toArray();
            $encoded = json_encode($array);
            $this->assertIsString($encoded, "conflict {$conflict->conflictType} must be JSON-encodable");
            $this->assertSame($array, json_decode($encoded, true));
            $this->assertSame($array, $conflict->toArray(), 'toArray() must be deterministic across calls');
        }
    }

    public function test_q_result_to_array_is_deterministic_and_json_safe(): void
    {
        $result = $this->reconcileEveryConflictType();

        $a1 = $result->toArray();
        $a2 = $result->toArray();
        $this->assertSame($a1, $a2);

        $encoded = json_encode($a1);
        $this->assertIsString($encoded);
        $this->assertNotFalse($encoded);

        $this->assertArrayHasKey('can_apply', $a1);
        $this->assertArrayHasKey('has_blocking_conflicts', $a1);
        $this->assertArrayHasKey('fingerprint', $a1);
        $this->assertIsString($a1['fingerprint']);
    }

    // ── R/S/T/U. reconciliation fingerprint ──────────────────────────────

    public function test_r_fingerprint_identical_for_identical_inputs(): void
    {
        $base = $this->snapshot(['pages' => ['home' => $this->page([$this->block('a', 'text')])]]);
        $local = $base;
        $remote = $base;

        $f1 = ThemeUpgradeReconciler::fingerprint($base, $local, $remote);
        $f2 = ThemeUpgradeReconciler::fingerprint($base, $local, $remote);

        $this->assertSame($f1, $f2);
        $this->assertNotEmpty($f1);
    }

    public function test_s_fingerprint_changes_if_local_changes(): void
    {
        $base = $this->snapshot(['pages' => ['home' => $this->page([$this->block('a', 'text', ['props' => ['x' => 1]])])]]);
        $remote = $base;
        $local1 = $base;
        $local2 = $this->snapshot(['pages' => ['home' => $this->page([$this->block('a', 'text', ['props' => ['x' => 2]])])]]);

        $f1 = ThemeUpgradeReconciler::fingerprint($base, $local1, $remote);
        $f2 = ThemeUpgradeReconciler::fingerprint($base, $local2, $remote);

        $this->assertNotSame($f1, $f2);
    }

    public function test_t_fingerprint_changes_if_remote_changes(): void
    {
        $base = $this->snapshot(['pages' => ['home' => $this->page([$this->block('a', 'text', ['props' => ['x' => 1]])])]]);
        $local = $base;
        $remote1 = $base;
        $remote2 = $this->snapshot(['pages' => ['home' => $this->page([$this->block('a', 'text', ['props' => ['x' => 2]])])]]);

        $f1 = ThemeUpgradeReconciler::fingerprint($base, $local, $remote1);
        $f2 = ThemeUpgradeReconciler::fingerprint($base, $local, $remote2);

        $this->assertNotSame($f1, $f2);
    }

    public function test_u_repeated_reconciliation_produces_same_fingerprint(): void
    {
        $base = $this->snapshot(['pages' => ['home' => $this->page([$this->block('a', 'text', ['props' => ['x' => 1]])])]]);
        $local = $this->snapshot(['pages' => ['home' => $this->page([$this->block('a', 'text', ['props' => ['x' => 2]])])]]);
        $remote = $this->snapshot(['pages' => ['home' => $this->page([$this->block('a', 'text', ['props' => ['x' => 3]])])]]);

        $r1 = $this->reconciler()->reconcile($base, $local, $remote);
        $r2 = $this->reconciler()->reconcile($base, $local, $remote);

        $this->assertSame($r1->fingerprint, $r2->fingerprint);
        $this->assertSame($r1->fingerprint, ThemeUpgradeReconciler::fingerprint($base, $local, $remote));
    }

    public function test_fingerprint_is_insensitive_to_associative_key_order_but_not_list_order(): void
    {
        $a = ['global_styles' => ['primary' => '#111', 'secondary' => '#222'], 'header_blocks' => [], 'footer_blocks' => [], 'pages' => [], 'settings' => []];
        $b = ['footer_blocks' => [], 'global_styles' => ['secondary' => '#222', 'primary' => '#111'], 'header_blocks' => [], 'pages' => [], 'settings' => []];

        $this->assertSame(
            ThemeUpgradeReconciler::fingerprint($a, $a, $a),
            ThemeUpgradeReconciler::fingerprint($b, $b, $b),
            'associative key order must not affect the fingerprint'
        );

        $c = $this->snapshot(['pages' => ['home' => $this->page([$this->block('a', 'text'), $this->block('b', 'text')])]]);
        $d = $this->snapshot(['pages' => ['home' => $this->page([$this->block('b', 'text'), $this->block('a', 'text')])]]);
        $this->assertNotSame(
            ThemeUpgradeReconciler::fingerprint($c, $c, $c),
            ThemeUpgradeReconciler::fingerprint($d, $d, $d),
            'block ORDER is real data and must affect the fingerprint'
        );
    }

    // ── V. both-sides-different type change ──────────────────────────────

    public function test_v_both_sides_change_type_differently_produces_valid_deterministic_report(): void
    {
        $base = $this->snapshot(['pages' => ['home' => $this->page([$this->block('b1', 'hero', ['props' => ['title' => 'A']])])]]);
        $local = $this->snapshot(['pages' => ['home' => $this->page([$this->block('b1', 'gallery', ['props' => ['images' => []]])])]]);
        $remote = $this->snapshot(['pages' => ['home' => $this->page([$this->block('b1', 'slider', ['props' => ['slides' => []]])])]]);

        $result = $this->reconciler()->reconcile($base, $local, $remote);

        $blocks = $result->reconciledTheme['pages']['home']['blocks'];
        $this->assertCount(2, $blocks, 'both differently-typed versions must survive — never a cross-type prop merge into one invalid block');
        $types = array_column($blocks, 'type');
        sort($types);
        $this->assertSame(['gallery', 'slider'], $types, 'REMOTE\'s type (slider) stays canonical; LOCAL\'s (gallery) is preserved detached — no type is ever silently dropped or merged into the wrong schema');

        $this->assertCount(1, $result->conflicts);
        $this->assertSame(ThemeUpgradeConflict::TYPE_CHANGE_WITH_LOCAL_CUSTOMIZATION, $result->conflicts[0]->conflictType);
        $this->assertTrue($result->conflicts[0]->metadata['both_sides_changed_type']);

        // Determinism: repeating produces the identical two-block result.
        $result2 = $this->reconciler()->reconcile($base, $local, $remote);
        $this->assertSame($result->reconciledTheme, $result2->reconciledTheme);
        $this->assertSame($result->fingerprint, $result2->fingerprint);
    }

    public function test_v_both_sides_change_type_to_the_same_value_is_a_clean_merge_not_a_split(): void
    {
        $base = $this->snapshot(['pages' => ['home' => $this->page([$this->block('b1', 'hero', ['props' => ['title' => 'A']])])]]);
        $local = $this->snapshot(['pages' => ['home' => $this->page([$this->block('b1', 'slider', ['props' => ['title' => 'A']])])]]);
        $remote = $this->snapshot(['pages' => ['home' => $this->page([$this->block('b1', 'slider', ['props' => ['title' => 'A']])])]]);

        $result = $this->reconciler()->reconcile($base, $local, $remote);

        $blocks = $result->reconciledTheme['pages']['home']['blocks'];
        $this->assertCount(1, $blocks, 'agreeing on the same new type is not a conflict — no detachment needed');
        $this->assertSame('slider', $blocks[0]['type']);
        $this->assertSame([], $result->conflicts);
    }
}
