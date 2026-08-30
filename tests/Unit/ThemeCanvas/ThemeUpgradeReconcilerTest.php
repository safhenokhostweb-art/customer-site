<?php

namespace Tests\Unit\ThemeCanvas;

use App\Services\ThemeCanvas\ThemeCustomizationDiffException;
use App\Services\ThemeCanvas\ThemeUpgradeConflict;
use App\Services\ThemeCanvas\ThemeUpgradeReconciler;
use PHPUnit\Framework\TestCase;

/**
 * Phase 3D-D — pure ThemeUpgradeReconciler. Plain PHPUnit\Framework\
 * TestCase deliberately (see ThemeCustomizationDiffTest) — proves no
 * framework/DB dependency.
 */
class ThemeUpgradeReconcilerTest extends TestCase
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

    private function page(array $blocks, array $extra = []): array
    {
        return array_merge(['blocks' => $blocks], $extra);
    }

    private function pageBlocks(array $reconciled, string $slug = 'home'): array
    {
        return $reconciled['pages'][$slug]['blocks'] ?? [];
    }

    private function ids(array $blocks): array
    {
        return array_column($blocks, 'id');
    }

    // ── A. no changes ────────────────────────────────────────────────────

    public function test_a_no_changes(): void
    {
        $snap = $this->snapshot(['pages' => ['home' => $this->page([$this->block('b1', 'text', ['props' => ['x' => 1]])])]]);

        $result = $this->reconciler()->reconcile($snap, $snap, $snap);

        $this->assertSame(['b1'], $this->ids($this->pageBlocks($result->reconciledTheme)));
        $this->assertSame([], $result->conflicts);
    }

    // ── B. LOCAL property only ──────────────────────────────────────────

    public function test_b_local_property_only(): void
    {
        $base = $this->snapshot(['pages' => ['home' => $this->page([$this->block('b1', 'text', ['props' => ['title' => 'A']])])]]);
        $local = $this->snapshot(['pages' => ['home' => $this->page([$this->block('b1', 'text', ['props' => ['title' => 'Customer']])])]]);
        $remote = $base;

        $result = $this->reconciler()->reconcile($base, $local, $remote);

        $this->assertSame('Customer', $this->pageBlocks($result->reconciledTheme)[0]['props']['title']);
        $this->assertSame([], $result->conflicts);
    }

    // ── C. REMOTE property only ─────────────────────────────────────────

    public function test_c_remote_property_only(): void
    {
        $base = $this->snapshot(['pages' => ['home' => $this->page([$this->block('b1', 'text', ['props' => ['title' => 'A']])])]]);
        $local = $base;
        $remote = $this->snapshot(['pages' => ['home' => $this->page([$this->block('b1', 'text', ['props' => ['title' => 'New Master']])])]]);

        $result = $this->reconciler()->reconcile($base, $local, $remote);

        $this->assertSame('New Master', $this->pageBlocks($result->reconciledTheme)[0]['props']['title']);
        $this->assertSame([], $result->conflicts);
    }

    // ── D. both same property change ────────────────────────────────────

    public function test_d_both_same_change(): void
    {
        $base = $this->snapshot(['pages' => ['home' => $this->page([$this->block('b1', 'text', ['props' => ['title' => 'A']])])]]);
        $local = $this->snapshot(['pages' => ['home' => $this->page([$this->block('b1', 'text', ['props' => ['title' => 'Same']])])]]);
        $remote = $this->snapshot(['pages' => ['home' => $this->page([$this->block('b1', 'text', ['props' => ['title' => 'Same']])])]]);

        $result = $this->reconciler()->reconcile($base, $local, $remote);

        $this->assertSame('Same', $this->pageBlocks($result->reconciledTheme)[0]['props']['title']);
        $this->assertSame([], $result->conflicts);
    }

    // ── E. conflicting property changes → LOCAL + conflict ─────────────

    public function test_e_conflicting_property_changes(): void
    {
        $base = $this->snapshot(['pages' => ['home' => $this->page([$this->block('b1', 'text', ['props' => ['title' => 'A']])])]]);
        $local = $this->snapshot(['pages' => ['home' => $this->page([$this->block('b1', 'text', ['props' => ['title' => 'Customer']])])]]);
        $remote = $this->snapshot(['pages' => ['home' => $this->page([$this->block('b1', 'text', ['props' => ['title' => 'Master']])])]]);

        $result = $this->reconciler()->reconcile($base, $local, $remote);

        $this->assertSame('Customer', $this->pageBlocks($result->reconciledTheme)[0]['props']['title']);
        $this->assertCount(1, $result->conflicts);
        $conflict = $result->conflicts[0];
        $this->assertSame(ThemeUpgradeConflict::PROPERTY_CONFLICT, $conflict->conflictType);
        $this->assertSame('b1', $conflict->blockId);
        $this->assertSame('props.title', $conflict->fieldPath);
        $this->assertSame('A', $conflict->baseValue);
        $this->assertSame('Customer', $conflict->localValue);
        $this->assertSame('Master', $conflict->remoteValue);
        $this->assertSame(ThemeUpgradeConflict::RESOLUTION_KEEP_LOCAL, $conflict->chosenResolution);
        $this->assertFalse($conflict->isBlocking());
    }

    // ── F. unrelated LOCAL/REMOTE edits on same block both survive ─────

    public function test_f_unrelated_edits_on_same_block_both_survive(): void
    {
        $base = $this->snapshot(['pages' => ['home' => $this->page([
            $this->block('b1', 'hero', ['props' => ['title' => 'A'], 'style' => ['background' => 'white']]),
        ])]]);
        $local = $this->snapshot(['pages' => ['home' => $this->page([
            $this->block('b1', 'hero', ['props' => ['title' => 'Customer title'], 'style' => ['background' => 'white']]),
        ])]]);
        $remote = $this->snapshot(['pages' => ['home' => $this->page([
            $this->block('b1', 'hero', ['props' => ['title' => 'A'], 'style' => ['background' => 'black']]),
        ])]]);

        $result = $this->reconciler()->reconcile($base, $local, $remote);

        $merged = $this->pageBlocks($result->reconciledTheme)[0];
        $this->assertSame('Customer title', $merged['props']['title']);
        $this->assertSame('black', $merged['style']['background']);
        $this->assertSame([], $result->conflicts, 'unrelated non-overlapping edits must never produce a false conflict');
    }

    // ── G/H/I. add scenarios ─────────────────────────────────────────────

    public function test_g_local_add(): void
    {
        $base = $this->snapshot(['pages' => ['home' => $this->page([])]]);
        $local = $this->snapshot(['pages' => ['home' => $this->page([$this->block('cust_1', 'text')])]]);
        $remote = $base;

        $result = $this->reconciler()->reconcile($base, $local, $remote);

        $this->assertSame(['cust_1'], $this->ids($this->pageBlocks($result->reconciledTheme)));
    }

    public function test_h_remote_add(): void
    {
        $base = $this->snapshot(['pages' => ['home' => $this->page([])]]);
        $local = $base;
        $remote = $this->snapshot(['pages' => ['home' => $this->page([$this->block('new_master', 'text')])]]);

        $result = $this->reconciler()->reconcile($base, $local, $remote);

        $this->assertSame(['new_master'], $this->ids($this->pageBlocks($result->reconciledTheme)));
        $this->assertNotEmpty($result->remoteAdditions);
    }

    public function test_i_both_independently_add_different_ids(): void
    {
        $base = $this->snapshot(['pages' => ['home' => $this->page([])]]);
        $local = $this->snapshot(['pages' => ['home' => $this->page([$this->block('cust_1', 'text')])]]);
        $remote = $this->snapshot(['pages' => ['home' => $this->page([$this->block('new_master', 'text')])]]);

        $result = $this->reconciler()->reconcile($base, $local, $remote);

        $this->assertEqualsCanonicalizing(['cust_1', 'new_master'], $this->ids($this->pageBlocks($result->reconciledTheme)));
    }

    // ── J/K. delete scenarios ────────────────────────────────────────────

    public function test_j_local_delete_remote_unchanged(): void
    {
        $base = $this->snapshot(['pages' => ['home' => $this->page([$this->block('b1', 'text')])]]);
        $local = $this->snapshot(['pages' => ['home' => $this->page([])]]);
        $remote = $base;

        $result = $this->reconciler()->reconcile($base, $local, $remote);

        $this->assertSame([], $this->pageBlocks($result->reconciledTheme));
        $this->assertSame([], $result->conflicts);
    }

    public function test_k_remote_delete_local_unchanged(): void
    {
        $base = $this->snapshot(['pages' => ['home' => $this->page([$this->block('b1', 'text')])]]);
        $local = $base;
        $remote = $this->snapshot(['pages' => ['home' => $this->page([])]]);

        $result = $this->reconciler()->reconcile($base, $local, $remote);

        $this->assertSame([], $this->pageBlocks($result->reconciledTheme));
        $this->assertSame([], $result->conflicts);
    }

    // ── L. LOCAL modified + REMOTE deleted → detached ──────────────────

    public function test_l_local_modified_remote_deleted_is_detached(): void
    {
        $base = $this->snapshot(['pages' => ['home' => $this->page([$this->block('b1', 'text', ['props' => ['title' => 'A']])])]]);
        $local = $this->snapshot(['pages' => ['home' => $this->page([$this->block('b1', 'text', ['props' => ['title' => 'Customer']])])]]);
        $remote = $this->snapshot(['pages' => ['home' => $this->page([])]]);

        $result = $this->reconciler()->reconcile($base, $local, $remote);

        $resultBlocks = $this->pageBlocks($result->reconciledTheme);
        $this->assertCount(1, $resultBlocks, 'customer content must never be silently discarded');
        $this->assertNotSame('b1', $resultBlocks[0]['id'], 'a detached block must never reuse the original id');
        $this->assertStringStartsWith('cust_reconcile_', $resultBlocks[0]['id']);
        $this->assertSame('Customer', $resultBlocks[0]['props']['title']);
        $this->assertCount(1, $result->detachedBlocks);
        $this->assertSame('b1', $result->detachedBlocks[0]['origin_id']);
        $this->assertCount(1, $result->conflicts);
        $this->assertSame(ThemeUpgradeConflict::MASTER_DELETED_LOCAL_MODIFIED, $result->conflicts[0]->conflictType);
    }

    // ── M. LOCAL deleted + REMOTE modified → stays deleted + conflict ──

    public function test_m_local_deleted_remote_modified_stays_deleted(): void
    {
        $base = $this->snapshot(['pages' => ['home' => $this->page([$this->block('b1', 'text', ['props' => ['title' => 'A']])])]]);
        $local = $this->snapshot(['pages' => ['home' => $this->page([])]]);
        $remote = $this->snapshot(['pages' => ['home' => $this->page([$this->block('b1', 'text', ['props' => ['title' => 'New Master']])])]]);

        $result = $this->reconciler()->reconcile($base, $local, $remote);

        $this->assertSame([], $this->pageBlocks($result->reconciledTheme), 'a customer deletion must never be silently revived');
        $this->assertCount(1, $result->conflicts);
        $this->assertSame(ThemeUpgradeConflict::LOCAL_DELETED_REMOTE_MODIFIED, $result->conflicts[0]->conflictType);
    }

    // ── N/O. type change scenarios ───────────────────────────────────────

    public function test_n_remote_type_change_local_unchanged(): void
    {
        $base = $this->snapshot(['pages' => ['home' => $this->page([$this->block('b1', 'hero', ['props' => ['title' => 'A']])])]]);
        $local = $base;
        $remote = $this->snapshot(['pages' => ['home' => $this->page([$this->block('b1', 'slider', ['props' => ['slides' => []]])])]]);

        $result = $this->reconciler()->reconcile($base, $local, $remote);

        $resultBlocks = $this->pageBlocks($result->reconciledTheme);
        $this->assertCount(1, $resultBlocks);
        $this->assertSame('b1', $resultBlocks[0]['id']);
        $this->assertSame('slider', $resultBlocks[0]['type']);
        $this->assertSame([], $result->conflicts);
    }

    public function test_o_remote_type_change_local_customized_preserves_both(): void
    {
        $base = $this->snapshot(['pages' => ['home' => $this->page([$this->block('b1', 'hero', ['props' => ['title' => 'A']])])]]);
        $local = $this->snapshot(['pages' => ['home' => $this->page([$this->block('b1', 'hero', ['props' => ['title' => 'Customer']])])]]);
        $remote = $this->snapshot(['pages' => ['home' => $this->page([$this->block('b1', 'slider', ['props' => ['slides' => []]])])]]);

        $result = $this->reconciler()->reconcile($base, $local, $remote);

        $resultBlocks = $this->pageBlocks($result->reconciledTheme);
        $this->assertCount(2, $resultBlocks);
        $byId = collect($resultBlocks)->keyBy('id');
        $this->assertSame('slider', $byId['b1']['type'], 'the canonical id keeps the upgraded master type');
        $detachedId = collect($resultBlocks)->firstWhere('id', '!=', 'b1')['id'];
        $this->assertSame('hero', $byId[$detachedId]['type']);
        $this->assertSame('Customer', $byId[$detachedId]['props']['title']);
        $this->assertCount(1, $result->conflicts);
        $this->assertSame(ThemeUpgradeConflict::TYPE_CHANGE_WITH_LOCAL_CUSTOMIZATION, $result->conflicts[0]->conflictType);
    }

    // ── P/Q/R. reorder scenarios ──────────────────────────────────────────

    public function test_p_local_reorder_only(): void
    {
        $base = $this->snapshot(['pages' => ['home' => $this->page([$this->block('a', 'text'), $this->block('b', 'text')])]]);
        $local = $this->snapshot(['pages' => ['home' => $this->page([$this->block('b', 'text'), $this->block('a', 'text')])]]);
        $remote = $base;

        $result = $this->reconciler()->reconcile($base, $local, $remote);

        $this->assertSame(['b', 'a'], $this->ids($this->pageBlocks($result->reconciledTheme)));
        $this->assertSame([], $result->conflicts);
    }

    public function test_q_remote_reorder_only(): void
    {
        $base = $this->snapshot(['pages' => ['home' => $this->page([$this->block('a', 'text'), $this->block('b', 'text')])]]);
        $local = $base;
        $remote = $this->snapshot(['pages' => ['home' => $this->page([$this->block('b', 'text'), $this->block('a', 'text')])]]);

        $result = $this->reconciler()->reconcile($base, $local, $remote);

        $this->assertSame(['b', 'a'], $this->ids($this->pageBlocks($result->reconciledTheme)));
        $this->assertSame([], $result->conflicts);
    }

    public function test_r_conflicting_reorder(): void
    {
        $base = $this->snapshot(['pages' => ['home' => $this->page([$this->block('a', 'text'), $this->block('b', 'text'), $this->block('c', 'text')])]]);
        $local = $this->snapshot(['pages' => ['home' => $this->page([$this->block('b', 'text'), $this->block('a', 'text'), $this->block('c', 'text')])]]);
        $remote = $this->snapshot(['pages' => ['home' => $this->page([$this->block('c', 'text'), $this->block('a', 'text'), $this->block('b', 'text')])]]);

        $result = $this->reconciler()->reconcile($base, $local, $remote);

        $this->assertSame(['b', 'a', 'c'], $this->ids($this->pageBlocks($result->reconciledTheme)), 'LOCAL order wins by default');
    }

    // ── S. interleave: A X B C + A B D C → A X B D C ──────────────────────

    public function test_s_interleave_customer_and_master_insertions(): void
    {
        $base = $this->snapshot(['pages' => ['home' => $this->page([
            $this->block('a', 'text'), $this->block('b', 'text'), $this->block('c', 'text'),
        ])]]);
        $local = $this->snapshot(['pages' => ['home' => $this->page([
            $this->block('a', 'text'), $this->block('x', 'text'), $this->block('b', 'text'), $this->block('c', 'text'),
        ])]]);
        $remote = $this->snapshot(['pages' => ['home' => $this->page([
            $this->block('a', 'text'), $this->block('b', 'text'), $this->block('d', 'text'), $this->block('c', 'text'),
        ])]]);

        $result = $this->reconciler()->reconcile($base, $local, $remote);

        $this->assertSame(['a', 'x', 'b', 'd', 'c'], $this->ids($this->pageBlocks($result->reconciledTheme)));
    }

    // ── T. customer-added anchor removed ──────────────────────────────────

    public function test_t_customer_added_anchor_removed_falls_back_to_following_sibling(): void
    {
        $base = $this->snapshot(['pages' => ['home' => $this->page([$this->block('a', 'text'), $this->block('b', 'text')])]]);
        $local = $this->snapshot(['pages' => ['home' => $this->page([$this->block('a', 'text'), $this->block('x', 'text'), $this->block('b', 'text')])]]);
        $remote = $this->snapshot(['pages' => ['home' => $this->page([$this->block('b', 'text')])]]); // remote deletes A

        $result = $this->reconciler()->reconcile($base, $local, $remote);

        $this->assertSame(['x', 'b'], $this->ids($this->pageBlocks($result->reconciledTheme)));
        $this->assertNotEmpty($result->structuralFallbacks);
        $this->assertSame('x', $result->structuralFallbacks[0]['block_id']);
    }

    // ── U. parent removed with customized descendant ──────────────────────

    public function test_u_parent_removed_with_customized_descendant(): void
    {
        $base = $this->snapshot(['pages' => ['home' => $this->page([
            $this->block('parent', 'section', ['children' => [$this->block('child', 'text', ['props' => ['x' => 'A']])]]),
        ])]]);
        $local = $this->snapshot(['pages' => ['home' => $this->page([
            $this->block('parent', 'section', ['children' => [$this->block('child', 'text', ['props' => ['x' => 'Customer']])]]),
        ])]]);
        $remote = $this->snapshot(['pages' => ['home' => $this->page([])]]);

        $result = $this->reconciler()->reconcile($base, $local, $remote);

        $resultBlocks = $this->pageBlocks($result->reconciledTheme);
        $this->assertNotEmpty($resultBlocks, 'the customized descendant must survive even though its parent was removed');
        $this->assertNotContains('parent', $this->ids($resultBlocks), 'the removed, never-customized parent itself must not be resurrected');

        // The child is promoted to the page root, carrying its customization.
        $this->assertCount(1, $resultBlocks);
        $this->assertSame('Customer', $resultBlocks[0]['props']['x']);

        $conflictTypes = array_map(fn ($c) => $c->conflictType, $result->conflicts);
        $this->assertContains(ThemeUpgradeConflict::STRUCTURAL_PARENT_REMOVED, $conflictTypes);
    }

    // ── V. child moved out before parent removal ──────────────────────────

    public function test_v_child_moved_out_before_parent_removed_survives_at_new_location(): void
    {
        $base = $this->snapshot(['pages' => ['home' => $this->page([
            $this->block('parent', 'section', ['children' => [$this->block('child', 'text')]]),
        ])]]);
        $local = $this->snapshot(['pages' => ['home' => $this->page([
            $this->block('child', 'text'),
        ])]]); // customer moved child to page root, parent implicitly removed locally too
        $remote = $base; // remote never touched parent or child

        $result = $this->reconciler()->reconcile($base, $local, $remote);

        $resultBlocks = $this->pageBlocks($result->reconciledTheme);
        $this->assertSame(['child'], $this->ids($resultBlocks), 'the child must survive at its LOCAL location, no conflict, no detachment needed for a pure move');
        $this->assertSame([], $result->conflicts);
    }

    // ── W. move between pages ─────────────────────────────────────────────

    public function test_w_move_between_pages(): void
    {
        $base = $this->snapshot(['pages' => [
            'home' => $this->page([$this->block('b1', 'text')]),
            'about' => $this->page([]),
        ]]);
        $local = $this->snapshot(['pages' => [
            'home' => $this->page([]),
            'about' => $this->page([$this->block('b1', 'text')]),
        ]]);
        $remote = $base;

        $result = $this->reconciler()->reconcile($base, $local, $remote);

        $this->assertSame([], $this->pageBlocks($result->reconciledTheme, 'home'));
        $this->assertSame(['b1'], $this->ids($this->pageBlocks($result->reconciledTheme, 'about')));
    }

    // ── X. conflicting page moves ──────────────────────────────────────────

    public function test_x_conflicting_page_moves_defaults_to_local(): void
    {
        $base = $this->snapshot(['pages' => [
            'home' => $this->page([$this->block('b1', 'text')]),
            'about' => $this->page([]),
            'contact' => $this->page([]),
        ]]);
        $local = $this->snapshot(['pages' => [
            'home' => $this->page([]),
            'about' => $this->page([$this->block('b1', 'text')]),
            'contact' => $this->page([]),
        ]]);
        $remote = $this->snapshot(['pages' => [
            'home' => $this->page([]),
            'about' => $this->page([]),
            'contact' => $this->page([$this->block('b1', 'text')]),
        ]]);

        $result = $this->reconciler()->reconcile($base, $local, $remote);

        $this->assertSame(['b1'], $this->ids($this->pageBlocks($result->reconciledTheme, 'about')), 'LOCAL wins the conflicting placement by default');
        $this->assertSame([], $this->pageBlocks($result->reconciledTheme, 'contact'));
        $conflictTypes = array_map(fn ($c) => $c->conflictType, $result->conflicts);
        $this->assertContains(ThemeUpgradeConflict::STRUCTURAL_MOVE_CONFLICT, $conflictTypes);
    }

    // ── Y/Z/AA. global styles ──────────────────────────────────────────────

    public function test_y_global_style_local_only(): void
    {
        $base = $this->snapshot(['global_styles' => ['primary_color' => '#111']]);
        $local = $this->snapshot(['global_styles' => ['primary_color' => '#custom']]);
        $remote = $base;

        $result = $this->reconciler()->reconcile($base, $local, $remote);

        $this->assertSame('#custom', $result->reconciledTheme['global_styles']['primary_color']);
    }

    public function test_z_global_style_remote_only(): void
    {
        $base = $this->snapshot(['global_styles' => ['primary_color' => '#111']]);
        $local = $base;
        $remote = $this->snapshot(['global_styles' => ['primary_color' => '#new-master']]);

        $result = $this->reconciler()->reconcile($base, $local, $remote);

        $this->assertSame('#new-master', $result->reconciledTheme['global_styles']['primary_color']);
    }

    public function test_aa_global_style_conflict_local_wins_remote_surfaced(): void
    {
        $base = $this->snapshot(['global_styles' => ['primary_color' => '#111']]);
        $local = $this->snapshot(['global_styles' => ['primary_color' => '#custom-brand']]);
        $remote = $this->snapshot(['global_styles' => ['primary_color' => '#new-master']]);

        $result = $this->reconciler()->reconcile($base, $local, $remote);

        $this->assertSame('#custom-brand', $result->reconciledTheme['global_styles']['primary_color'], 'branding customization protected by default');
        $this->assertCount(1, $result->conflicts);
        $this->assertSame('#new-master', $result->conflicts[0]->remoteValue);
    }

    // ── AB. settings merge ─────────────────────────────────────────────────

    public function test_ab_settings_merge(): void
    {
        $base = $this->snapshot(['settings' => ['required_platform_version' => '1.0.0']]);
        $local = $base;
        $remote = $this->snapshot(['settings' => ['required_platform_version' => '2.0.0']]);

        $result = $this->reconciler()->reconcile($base, $local, $remote);

        $this->assertSame('2.0.0', $result->reconciledTheme['settings']['required_platform_version']);
    }

    // ── AC. missing/null distinctions survive reconciliation ──────────────

    public function test_ac_missing_null_distinctions_survive(): void
    {
        $base = $this->snapshot(['global_styles' => []]);
        $local = $this->snapshot(['global_styles' => ['x' => null]]);
        $remote = $base;

        $result = $this->reconciler()->reconcile($base, $local, $remote);

        $this->assertArrayHasKey('x', $result->reconciledTheme['global_styles']);
        $this->assertNull($result->reconciledTheme['global_styles']['x']);
    }

    public function test_ac_false_survives_as_false_not_true(): void
    {
        $base = $this->snapshot(['global_styles' => ['dark_mode' => ['enabled' => true]]]);
        $local = $this->snapshot(['global_styles' => ['dark_mode' => ['enabled' => false]]]);
        $remote = $base;

        $result = $this->reconciler()->reconcile($base, $local, $remote);

        $this->assertFalse($result->reconciledTheme['global_styles']['dark_mode']['enabled']);
    }

    // ── AD. extension/module block handled generically ─────────────────────

    public function test_ad_module_block_handled_generically_no_special_casing(): void
    {
        $base = $this->snapshot(['pages' => ['home' => $this->page([
            $this->block('m1', 'module:motion', ['props' => ['motion_asset_id' => 'intro', 'enabled' => true]]),
        ])]]);
        $local = $this->snapshot(['pages' => ['home' => $this->page([
            $this->block('m1', 'module:motion', ['props' => ['motion_asset_id' => 'custom-choice', 'enabled' => true]]),
        ])]]);
        $remote = $this->snapshot(['pages' => ['home' => $this->page([
            $this->block('m1', 'module:motion', ['props' => ['motion_asset_id' => 'intro', 'enabled' => false]]),
        ])]]);

        $result = $this->reconciler()->reconcile($base, $local, $remote);

        $merged = $this->pageBlocks($result->reconciledTheme)[0];
        $this->assertSame('custom-choice', $merged['props']['motion_asset_id']);
        $this->assertFalse($merged['props']['enabled']);
        $this->assertSame([], $result->conflicts);
    }

    // ── AE. duplicate-id input rejected ─────────────────────────────────────

    public function test_ae_duplicate_id_in_any_snapshot_is_rejected(): void
    {
        $base = $this->snapshot(['pages' => [
            'home' => $this->page([$this->block('dup', 'text')]),
            'about' => $this->page([$this->block('dup', 'heading')]),
        ]]);

        $this->expectException(ThemeCustomizationDiffException::class);
        $this->reconciler()->reconcile($base, $this->snapshot(), $this->snapshot());
    }

    // ── AF. deterministic repeated result ──────────────────────────────────

    public function test_af_deterministic_repeated_result(): void
    {
        $base = $this->snapshot(['pages' => ['home' => $this->page([$this->block('a', 'text', ['props' => ['x' => 1]])])]]);
        $local = $this->snapshot(['pages' => ['home' => $this->page([$this->block('a', 'text', ['props' => ['x' => 2]])])]]);
        $remote = $this->snapshot(['pages' => ['home' => $this->page([$this->block('a', 'text', ['props' => ['x' => 3]])])]]);

        $r1 = $this->reconciler()->reconcile($base, $local, $remote);
        $r2 = $this->reconciler()->reconcile($base, $local, $remote);

        $this->assertSame($r1->reconciledTheme, $r2->reconciledTheme);
        $this->assertCount(count($r1->conflicts), $r2->conflicts);
    }

    // ── AG. input arrays immutable ──────────────────────────────────────────

    public function test_ag_input_snapshots_are_never_mutated(): void
    {
        $base = $this->snapshot(['pages' => ['home' => $this->page([$this->block('a', 'text', ['props' => ['x' => 1]])])]]);
        $local = $this->snapshot(['pages' => ['home' => $this->page([$this->block('a', 'text', ['props' => ['x' => 2]])])]]);
        $remote = $this->snapshot(['pages' => ['home' => $this->page([$this->block('a', 'text', ['props' => ['x' => 3]])])]]);

        $baseBefore = serialize($base);
        $localBefore = serialize($local);
        $remoteBefore = serialize($remote);

        $this->reconciler()->reconcile($base, $local, $remote);

        $this->assertSame($baseBefore, serialize($base));
        $this->assertSame($localBefore, serialize($local));
        $this->assertSame($remoteBefore, serialize($remote));
    }

    // ── AH. deeply nested containers ────────────────────────────────────────

    public function test_ah_deeply_nested_containers_reconcile_correctly(): void
    {
        $base = $this->snapshot(['pages' => ['home' => $this->page([
            $this->block('l1', 'section', ['children' => [
                $this->block('l2', 'row', ['children' => [
                    $this->block('l3', 'column', ['children' => [
                        $this->block('l4', 'text', ['props' => ['x' => 'A']]),
                    ]]),
                ]]),
            ]]),
        ])]]);
        $local = $this->snapshot(['pages' => ['home' => $this->page([
            $this->block('l1', 'section', ['children' => [
                $this->block('l2', 'row', ['children' => [
                    $this->block('l3', 'column', ['children' => [
                        $this->block('l4', 'text', ['props' => ['x' => 'Customer']]),
                    ]]),
                ]]),
            ]]),
        ])]]);
        $remote = $base;

        $result = $this->reconciler()->reconcile($base, $local, $remote);

        $l1 = $this->pageBlocks($result->reconciledTheme)[0];
        $l2 = $l1['children'][0];
        $l3 = $l2['children'][0];
        $l4 = $l3['children'][0];
        $this->assertSame('l4', $l4['id']);
        $this->assertSame('Customer', $l4['props']['x']);
    }

    // ── AI. multiple customer additions around same anchor ─────────────────

    public function test_ai_multiple_customer_additions_around_same_anchor(): void
    {
        $base = $this->snapshot(['pages' => ['home' => $this->page([$this->block('a', 'text'), $this->block('b', 'text')])]]);
        $local = $this->snapshot(['pages' => ['home' => $this->page([
            $this->block('a', 'text'), $this->block('x1', 'text'), $this->block('x2', 'text'), $this->block('b', 'text'),
        ])]]);
        $remote = $base;

        $result = $this->reconciler()->reconcile($base, $local, $remote);

        $this->assertSame(['a', 'x1', 'x2', 'b'], $this->ids($this->pageBlocks($result->reconciledTheme)));
    }

    // ── AJ. empty page/header/footer cases ──────────────────────────────────

    public function test_aj_empty_everything_produces_empty_result(): void
    {
        $empty = $this->snapshot();

        $result = $this->reconciler()->reconcile($empty, $empty, $empty);

        $this->assertSame([], $result->reconciledTheme['header_blocks']);
        $this->assertSame([], $result->reconciledTheme['footer_blocks']);
        $this->assertSame([], $result->reconciledTheme['pages']);
        $this->assertSame([], $result->conflicts);
    }

    public function test_aj_empty_page_with_only_a_remote_addition(): void
    {
        $base = $this->snapshot(['pages' => ['home' => $this->page([])]]);
        $local = $base;
        $remote = $this->snapshot(['pages' => ['home' => $this->page([$this->block('m1', 'text')])]]);

        $result = $this->reconciler()->reconcile($base, $local, $remote);

        $this->assertSame(['m1'], $this->ids($this->pageBlocks($result->reconciledTheme)));
    }

    // ── AK. customer legacy non-cust_ IDs preserved ────────────────────────

    public function test_ak_legacy_non_prefixed_customer_id_preserved_through_reconciliation(): void
    {
        $base = $this->snapshot(['pages' => ['home' => $this->page([
            $this->block('element-1580000000-xy9', 'text', ['props' => ['x' => 'A']]),
        ])]]);
        $local = $this->snapshot(['pages' => ['home' => $this->page([
            $this->block('element-1580000000-xy9', 'text', ['props' => ['x' => 'Customer']]),
        ])]]);
        $remote = $base;

        $result = $this->reconciler()->reconcile($base, $local, $remote);

        $resultBlocks = $this->pageBlocks($result->reconciledTheme);
        $this->assertSame('element-1580000000-xy9', $resultBlocks[0]['id']);
        $this->assertSame('Customer', $resultBlocks[0]['props']['x']);
    }

    // ── AL. detached generated ID is deterministic and collision-safe ─────

    public function test_al_detached_id_is_deterministic_across_runs(): void
    {
        $base = $this->snapshot(['pages' => ['home' => $this->page([$this->block('b1', 'text', ['props' => ['x' => 'A']])])]]);
        $local = $this->snapshot(['pages' => ['home' => $this->page([$this->block('b1', 'text', ['props' => ['x' => 'Customer']])])]]);
        $remote = $this->snapshot(['pages' => ['home' => $this->page([])]]);

        $r1 = $this->reconciler()->reconcile($base, $local, $remote);
        $r2 = $this->reconciler()->reconcile($base, $local, $remote);

        $this->assertSame($this->pageBlocks($r1->reconciledTheme)[0]['id'], $this->pageBlocks($r2->reconciledTheme)[0]['id']);
        $this->assertStringStartsWith('cust_', $this->pageBlocks($r1->reconciledTheme)[0]['id']);
    }

    public function test_al_detached_id_never_collides_with_an_existing_id(): void
    {
        // Contrive a base/local/remote where the naturally-derived detached
        // id already exists as a real block id — the reconciler must still
        // produce a unique id, deterministically.
        $base = $this->snapshot(['pages' => ['home' => $this->page([
            $this->block('b1', 'text', ['props' => ['x' => 'A']]),
        ])]]);
        $local = $this->snapshot(['pages' => ['home' => $this->page([
            $this->block('b1', 'text', ['props' => ['x' => 'Customer']]),
            $this->block('cust_reconcile_' . substr(hash('sha256', 'MASTER_DELETED_LOCAL_MODIFIED|b1'), 0, 16), 'text'),
        ])]]);
        $remote = $this->snapshot(['pages' => ['home' => $this->page([])]]);

        $result = $this->reconciler()->reconcile($base, $local, $remote);

        $ids = $this->ids($this->pageBlocks($result->reconciledTheme));
        $this->assertSame(count($ids), count(array_unique($ids)), 'no two blocks may ever share an id in the reconciled output');
    }

    // ── §24 performance: ~500 blocks ────────────────────────────────────────

    public function test_performance_five_hundred_blocks_completes_and_is_correct(): void
    {
        $baseBlocks = [];
        $localBlocks = [];
        $remoteBlocks = [];
        for ($i = 0; $i < 500; $i++) {
            $baseBlocks[] = $this->block("blk_{$i}", 'text', ['props' => ['text' => "v{$i}"]]);
            $localBlocks[] = ($i % 11 === 0)
                ? null
                : $this->block("blk_{$i}", 'text', ['props' => ['text' => ($i % 3 === 0) ? "local-{$i}" : "v{$i}"]]);
            $remoteBlocks[] = ($i % 13 === 0)
                ? null
                : $this->block("blk_{$i}", 'text', ['props' => ['text' => ($i % 5 === 0) ? "remote-{$i}" : "v{$i}"]]);
        }
        $localBlocks = array_values(array_filter($localBlocks));
        $remoteBlocks = array_values(array_filter($remoteBlocks));
        for ($i = 0; $i < 10; $i++) {
            $localBlocks[] = $this->block("cust_new_{$i}", 'text');
        }
        for ($i = 0; $i < 10; $i++) {
            $remoteBlocks[] = $this->block("remote_new_{$i}", 'text');
        }

        $base = $this->snapshot(['pages' => ['home' => $this->page($baseBlocks)]]);
        $local = $this->snapshot(['pages' => ['home' => $this->page($localBlocks)]]);
        $remote = $this->snapshot(['pages' => ['home' => $this->page($remoteBlocks)]]);

        $start = microtime(true);
        $result = $this->reconciler()->reconcile($base, $local, $remote);
        $elapsed = microtime(true) - $start;

        $this->assertLessThan(10.0, $elapsed, 'reconciling ~500 blocks took far longer than a linear-time algorithm should');
        $resultIds = $this->ids($this->pageBlocks($result->reconciledTheme));
        $this->assertSame(count($resultIds), count(array_unique($resultIds)));
        $this->assertContains('cust_new_0', $resultIds);
        $this->assertContains('remote_new_0', $resultIds);
    }
}
