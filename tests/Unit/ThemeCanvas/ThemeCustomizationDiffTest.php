<?php

namespace Tests\Unit\ThemeCanvas;

use App\Services\ThemeCanvas\ThemeCustomizationDiff;
use App\Services\ThemeCanvas\ThemeCustomizationDiffException;
use PHPUnit\Framework\TestCase;

/**
 * Phase 3D-C — pure ThemeCustomizationDiff engine. Plain PHPUnit\Framework\
 * TestCase (not Laravel's) deliberately — this proves the engine truly has
 * no framework/DB/app-container dependency, matching its "no DB reads, no
 * Settings access, no HTTP, no side effects" contract.
 */
class ThemeCustomizationDiffTest extends TestCase
{
    private function diff(): ThemeCustomizationDiff
    {
        return new ThemeCustomizationDiff();
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

    // ── A. identical BASE/TARGET → empty diff ─────────────────────────────

    public function test_a_identical_snapshots_produce_an_empty_diff(): void
    {
        $snap = $this->snapshot([
            'pages' => ['home' => ['title' => 'Home', 'slug' => 'home', 'blocks' => [
                $this->block('b1', 'heading', ['props' => ['text' => 'x']]),
            ]]],
            'global_styles' => ['primary_color' => '#fff'],
        ]);

        $result = $this->diff()->diff($snap, $snap);

        $this->assertTrue($result->isEmpty());
    }

    // ── B. block prop local change ─────────────────────────────────────────

    public function test_b_block_prop_change_is_modified(): void
    {
        $base = $this->snapshot(['pages' => ['home' => ['blocks' => [
            $this->block('b1', 'heading', ['props' => ['title' => 'Old']]),
        ]]]]);
        $target = $this->snapshot(['pages' => ['home' => ['blocks' => [
            $this->block('b1', 'heading', ['props' => ['title' => 'New']]),
        ]]]]);

        $result = $this->diff()->diff($base, $target);

        $this->assertCount(1, $result->blocks);
        $this->assertSame('b1', $result->blocks[0]['id']);
        $this->assertSame(['MODIFIED'], $result->blocks[0]['classification']);
        $this->assertSame([
            'change_type' => 'CHANGED', 'base_value' => 'Old', 'target_value' => 'New',
        ], $result->blocks[0]['property_changes']['props.title']);
    }

    // ── C. nested prop change ──────────────────────────────────────────────

    public function test_c_nested_prop_change_produces_a_precise_dot_path(): void
    {
        $base = $this->snapshot(['pages' => ['home' => ['blocks' => [
            $this->block('b1', 'image', ['props' => ['image' => ['url' => 'old.jpg', 'alt' => 'x']]]),
        ]]]]);
        $target = $this->snapshot(['pages' => ['home' => ['blocks' => [
            $this->block('b1', 'image', ['props' => ['image' => ['url' => 'new.jpg', 'alt' => 'x']]]),
        ]]]]);

        $result = $this->diff()->diff($base, $target);

        $changes = $result->blocks[0]['property_changes'];
        $this->assertArrayHasKey('props.image.url', $changes);
        $this->assertArrayNotHasKey('props.image.alt', $changes, 'an unchanged sibling field must not appear');
        $this->assertSame('old.jpg', $changes['props.image.url']['base_value']);
        $this->assertSame('new.jpg', $changes['props.image.url']['target_value']);
    }

    // ── D. responsive change ───────────────────────────────────────────────

    public function test_d_responsive_override_change(): void
    {
        $base = $this->snapshot(['pages' => ['home' => ['blocks' => [
            $this->block('b1', 'text', ['responsive' => ['mobile' => ['padding' => '8px']]]),
        ]]]]);
        $target = $this->snapshot(['pages' => ['home' => ['blocks' => [
            $this->block('b1', 'text', ['responsive' => ['mobile' => ['padding' => '16px']]]),
        ]]]]);

        $result = $this->diff()->diff($base, $target);

        $this->assertSame('8px', $result->blocks[0]['property_changes']['responsive.mobile.padding']['base_value']);
        $this->assertSame('16px', $result->blocks[0]['property_changes']['responsive.mobile.padding']['target_value']);
    }

    public function test_style_field_change(): void
    {
        $base = $this->snapshot(['pages' => ['home' => ['blocks' => [
            $this->block('b1', 'heading', ['style' => ['background' => 'red']]),
        ]]]]);
        $target = $this->snapshot(['pages' => ['home' => ['blocks' => [
            $this->block('b1', 'heading', ['style' => ['background' => 'blue']]),
        ]]]]);

        $result = $this->diff()->diff($base, $target);

        $this->assertSame('red', $result->blocks[0]['property_changes']['style.background']['base_value']);
    }

    // ── E. global token change ─────────────────────────────────────────────

    public function test_e_global_style_token_change(): void
    {
        $base = $this->snapshot(['global_styles' => ['primary_color' => '#111', 'colors' => ['accent' => '#222']]]);
        $target = $this->snapshot(['global_styles' => ['primary_color' => '#999', 'colors' => ['accent' => '#222']]]);

        $result = $this->diff()->diff($base, $target);

        $this->assertSame([
            'primary_color' => ['change_type' => 'CHANGED', 'base_value' => '#111', 'target_value' => '#999'],
        ], $result->globalStyles);
    }

    // ── F. custom_css change ───────────────────────────────────────────────

    public function test_f_custom_css_change(): void
    {
        $base = $this->snapshot(['global_styles' => ['custom_css' => '.a{}']]);
        $target = $this->snapshot(['global_styles' => ['custom_css' => '.b{}']]);

        $result = $this->diff()->diff($base, $target);

        $this->assertSame('.a{}', $result->globalStyles['custom_css']['base_value']);
        $this->assertSame('.b{}', $result->globalStyles['custom_css']['target_value']);
    }

    // ── G. block add ────────────────────────────────────────────────────────

    public function test_g_block_added(): void
    {
        $base = $this->snapshot(['pages' => ['home' => ['blocks' => []]]]);
        $target = $this->snapshot(['pages' => ['home' => ['blocks' => [
            $this->block('new1', 'heading', ['props' => ['text' => 'x']]),
        ]]]]);

        $result = $this->diff()->diff($base, $target);

        $this->assertCount(1, $result->blocks);
        $this->assertSame(['ADDED'], $result->blocks[0]['classification']);
        $this->assertArrayNotHasKey('base', $result->blocks[0]);
        $this->assertSame('heading', $result->blocks[0]['target']['type']);
        $this->assertSame('home', $result->blocks[0]['target']['page_slug']);
    }

    // ── H. block delete ─────────────────────────────────────────────────────

    public function test_h_block_deleted(): void
    {
        $base = $this->snapshot(['pages' => ['home' => ['blocks' => [
            $this->block('gone', 'text', ['props' => ['content' => 'x']]),
        ]]]]);
        $target = $this->snapshot(['pages' => ['home' => ['blocks' => []]]]);

        $result = $this->diff()->diff($base, $target);

        $this->assertSame(['DELETED'], $result->blocks[0]['classification']);
        $this->assertArrayNotHasKey('target', $result->blocks[0]);
        $this->assertSame('text', $result->blocks[0]['base']['type']);
        $this->assertSame(['content' => 'x'], $result->blocks[0]['base']['payload']['props']);
    }

    // ── I. block reorder among same siblings ──────────────────────────────

    public function test_i_block_reorder_is_moved(): void
    {
        $base = $this->snapshot(['pages' => ['home' => ['blocks' => [
            $this->block('a', 'text'), $this->block('b', 'text'),
        ]]]]);
        $target = $this->snapshot(['pages' => ['home' => ['blocks' => [
            $this->block('b', 'text'), $this->block('a', 'text'),
        ]]]]);

        $result = $this->diff()->diff($base, $target);

        $byId = collect($result->blocks)->keyBy('id');
        $this->assertSame(['MOVED'], $byId['a']['classification']);
        $this->assertSame(0, $byId['a']['move']['base_position']);
        $this->assertSame(1, $byId['a']['move']['target_position']);
        $this->assertSame(['MOVED'], $byId['b']['classification']);
    }

    // ── J. block move to another parent (same page) ───────────────────────

    public function test_j_block_move_to_another_parent(): void
    {
        $base = $this->snapshot(['pages' => ['home' => ['blocks' => [
            $this->block('container1', 'section', ['children' => [$this->block('child', 'text')]]),
            $this->block('container2', 'section', ['children' => []]),
        ]]]]);
        $target = $this->snapshot(['pages' => ['home' => ['blocks' => [
            $this->block('container1', 'section', ['children' => []]),
            $this->block('container2', 'section', ['children' => [$this->block('child', 'text')]]),
        ]]]]);

        $result = $this->diff()->diff($base, $target);

        $byId = collect($result->blocks)->keyBy('id');
        $this->assertSame('container1', $byId['child']['move']['base_parent']);
        $this->assertSame('container2', $byId['child']['move']['target_parent']);
        $this->assertContains('MOVED', $byId['child']['classification']);
        // The two containers themselves are structurally unchanged (their
        // own parent/region/position never moved) — only their children key
        // differs, which is not part of a container's OWN payload diff
        // since children are indexed and compared independently.
        $this->assertArrayNotHasKey('container1', $byId->all());
    }

    // ── K. block move to another page ──────────────────────────────────────

    public function test_k_block_move_to_another_page(): void
    {
        $base = $this->snapshot(['pages' => [
            'home' => ['blocks' => [$this->block('b1', 'text')]],
            'about' => ['blocks' => []],
        ]]);
        $target = $this->snapshot(['pages' => [
            'home' => ['blocks' => []],
            'about' => ['blocks' => [$this->block('b1', 'text')]],
        ]]);

        $result = $this->diff()->diff($base, $target);

        $entry = $result->blocks[0];
        $this->assertSame('home', $entry['move']['base_page_slug']);
        $this->assertSame('about', $entry['move']['target_page_slug']);
    }

    // ── L. page → header/footer movement ───────────────────────────────────

    public function test_l_block_move_from_page_to_header(): void
    {
        $base = $this->snapshot(['pages' => ['home' => ['blocks' => [$this->block('b1', 'text')]]]]);
        $target = $this->snapshot(['header_blocks' => [$this->block('b1', 'text')]], );
        $target['pages'] = ['home' => ['blocks' => []]];

        $result = $this->diff()->diff($base, $target);

        $entry = $result->blocks[0];
        $this->assertSame('page', $entry['move']['base_region']);
        $this->assertSame('header', $entry['move']['target_region']);
        $this->assertSame('home', $entry['move']['base_page_slug']);
        $this->assertNull($entry['move']['target_page_slug']);
    }

    // ── M. type change with same id ─────────────────────────────────────────

    public function test_m_type_change_same_id(): void
    {
        $base = $this->snapshot(['pages' => ['home' => ['blocks' => [
            $this->block('b1', 'hero', ['props' => ['title' => 'x']]),
        ]]]]);
        $target = $this->snapshot(['pages' => ['home' => ['blocks' => [
            $this->block('b1', 'slider', ['props' => ['slides' => []]]),
        ]]]]);

        $result = $this->diff()->diff($base, $target);

        $entry = $result->blocks[0];
        $this->assertContains('TYPE_CHANGED', $entry['classification']);
        $this->assertSame('hero', $entry['type_change']['base_type']);
        $this->assertSame('slider', $entry['type_change']['target_type']);
        // Raw mechanical prop diff is still present alongside the type
        // change fact — no migration/interpretation attempted.
        $this->assertContains('MODIFIED', $entry['classification']);
    }

    // ── N. parent deleted with children (genuine nested deletion) ─────────

    public function test_n_parent_deleted_with_children_both_report_deleted(): void
    {
        $base = $this->snapshot(['pages' => ['home' => ['blocks' => [
            $this->block('parent', 'section', ['children' => [
                $this->block('child', 'text'),
            ]]),
        ]]]]);
        $target = $this->snapshot(['pages' => ['home' => ['blocks' => []]]]);

        $result = $this->diff()->diff($base, $target);

        $byId = collect($result->blocks)->keyBy('id');
        $this->assertSame(['DELETED'], $byId['parent']['classification']);
        $this->assertSame(['DELETED'], $byId['child']['classification'], 'a child that truly disappears with its parent must independently report DELETED, not be silently lost');
    }

    // ── O. child moved out before parent disappears ─────────────────────────

    public function test_o_child_moved_out_before_parent_deleted_is_moved_not_deleted(): void
    {
        $base = $this->snapshot(['pages' => ['home' => ['blocks' => [
            $this->block('parent', 'section', ['children' => [$this->block('child', 'text')]]),
        ]]]]);
        $target = $this->snapshot(['pages' => ['home' => ['blocks' => [
            $this->block('child', 'text'),
        ]]]]);

        $result = $this->diff()->diff($base, $target);

        $byId = collect($result->blocks)->keyBy('id');
        $this->assertSame(['DELETED'], $byId['parent']['classification']);
        $this->assertContains('MOVED', $byId['child']['classification'], 'a child still present elsewhere in TARGET must never be reported as deleted just because its old parent vanished');
        $this->assertSame('parent', $byId['child']['move']['base_parent']);
        $this->assertNull($byId['child']['move']['target_parent']);
    }

    // ── P. duplicate ID rejection ────────────────────────────────────────────

    public function test_p_duplicate_id_in_base_throws(): void
    {
        $base = $this->snapshot(['pages' => [
            'home' => ['blocks' => [$this->block('dup', 'text')]],
            'about' => ['blocks' => [$this->block('dup', 'heading')]],
        ]]);
        $target = $this->snapshot();

        $this->expectException(ThemeCustomizationDiffException::class);
        $this->diff()->diff($base, $target);
    }

    public function test_p_duplicate_id_in_target_throws(): void
    {
        $base = $this->snapshot();
        $target = $this->snapshot(['header_blocks' => [$this->block('dup', 'text')]]);
        $target['footer_blocks'] = [$this->block('dup', 'heading')];

        $this->expectException(ThemeCustomizationDiffException::class);
        $this->diff()->diff($base, $target);
    }

    public function test_p_duplicate_id_nested_inside_children_throws(): void
    {
        $base = $this->snapshot(['pages' => ['home' => ['blocks' => [
            $this->block('outer', 'section', ['children' => [$this->block('outer', 'text')]]),
        ]]]]);

        $this->expectException(ThemeCustomizationDiffException::class);
        $this->diff()->diff($base, $this->snapshot());
    }

    // ── Q/R. cust_ ids and legacy ids are handled identically ─────────────

    public function test_q_customer_cust_id_matched_normally(): void
    {
        $base = $this->snapshot(['pages' => ['home' => ['blocks' => [
            $this->block('cust_1699999999_ab12', 'text', ['props' => ['content' => 'old']]),
        ]]]]);
        $target = $this->snapshot(['pages' => ['home' => ['blocks' => [
            $this->block('cust_1699999999_ab12', 'text', ['props' => ['content' => 'new']]),
        ]]]]);

        $result = $this->diff()->diff($base, $target);

        $this->assertCount(1, $result->blocks);
        $this->assertSame(['MODIFIED'], $result->blocks[0]['classification']);
    }

    public function test_r_legacy_non_prefixed_id_matched_normally(): void
    {
        $base = $this->snapshot(['pages' => ['home' => ['blocks' => [
            $this->block('element-1580000000-xy9', 'text', ['props' => ['content' => 'old']]),
        ]]]]);
        $target = $this->snapshot(['pages' => ['home' => ['blocks' => [
            $this->block('element-1580000000-xy9', 'text', ['props' => ['content' => 'new']]),
        ]]]]);

        $result = $this->diff()->diff($base, $target);

        $this->assertCount(1, $result->blocks);
        $this->assertSame(['MODIFIED'], $result->blocks[0]['classification']);
    }

    // ── S/T/U. page added/removed/title changed ────────────────────────────

    public function test_s_page_added(): void
    {
        $base = $this->snapshot(['pages' => []]);
        $target = $this->snapshot(['pages' => ['new-page' => ['title' => 'New', 'slug' => 'new-page', 'blocks' => []]]]);

        $result = $this->diff()->diff($base, $target);

        $this->assertSame(['slug' => 'new-page', 'classification' => ['ADDED'], 'target' => ['title' => 'New', 'slug' => 'new-page']], $result->pages[0]);
    }

    public function test_t_page_removed(): void
    {
        $base = $this->snapshot(['pages' => ['gone' => ['title' => 'Gone', 'slug' => 'gone', 'blocks' => []]]]);
        $target = $this->snapshot(['pages' => []]);

        $result = $this->diff()->diff($base, $target);

        $this->assertSame('gone', $result->pages[0]['slug']);
        $this->assertSame(['REMOVED'], $result->pages[0]['classification']);
    }

    public function test_u_page_title_changed(): void
    {
        $base = $this->snapshot(['pages' => ['home' => ['title' => 'Old Title', 'slug' => 'home', 'blocks' => []]]]);
        $target = $this->snapshot(['pages' => ['home' => ['title' => 'New Title', 'slug' => 'home', 'blocks' => []]]]);

        $result = $this->diff()->diff($base, $target);

        $this->assertSame(['MODIFIED'], $result->pages[0]['classification']);
        $this->assertSame('Old Title', $result->pages[0]['property_changes']['title']['base_value']);
        $this->assertSame('New Title', $result->pages[0]['property_changes']['title']['target_value']);
    }

    // ── V. ambiguous page rename is NEVER guessed ──────────────────────────

    public function test_v_page_slug_change_is_reported_as_remove_plus_add_never_a_rename(): void
    {
        $base = $this->snapshot(['pages' => ['old-slug' => ['title' => 'Same Title', 'slug' => 'old-slug', 'blocks' => [
            $this->block('b1', 'text'),
        ]]]]);
        $target = $this->snapshot(['pages' => ['new-slug' => ['title' => 'Same Title', 'slug' => 'new-slug', 'blocks' => [
            $this->block('b1', 'text'),
        ]]]]);

        $result = $this->diff()->diff($base, $target);

        $slugs = collect($result->pages)->pluck('classification', 'slug')->all();
        $this->assertSame(['REMOVED'], $slugs['old-slug']);
        $this->assertSame(['ADDED'], $slugs['new-slug']);
        $this->assertArrayNotHasKey('renamed_to', $result->pages[0]);

        // The real evidence that content moved is still captured — at the
        // block level, honestly, as a move fact — never fabricated as a
        // page-level rename claim.
        $this->assertSame('old-slug', $result->blocks[0]['move']['base_page_slug']);
        $this->assertSame('new-slug', $result->blocks[0]['move']['target_page_slug']);
    }

    // ── W. input arrays unchanged after diff ────────────────────────────────

    public function test_w_input_snapshots_are_never_mutated(): void
    {
        $base = $this->snapshot(['pages' => ['home' => ['blocks' => [$this->block('b1', 'text', ['props' => ['x' => 1]])]]]]);
        $target = $this->snapshot(['pages' => ['home' => ['blocks' => [$this->block('b1', 'text', ['props' => ['x' => 2]])]]]]);

        $baseBefore = serialize($base);
        $targetBefore = serialize($target);

        $this->diff()->diff($base, $target);

        $this->assertSame($baseBefore, serialize($base));
        $this->assertSame($targetBefore, serialize($target));
    }

    // ── X. repeated execution produces identical results (determinism) ────

    public function test_x_repeated_execution_is_identical(): void
    {
        $base = $this->snapshot([
            'pages' => [
                'zpage' => ['blocks' => [$this->block('z1', 'text', ['props' => ['a' => 1]])]],
                'apage' => ['blocks' => [$this->block('a1', 'text', ['props' => ['a' => 1]])]],
            ],
            'global_styles' => ['z' => 1, 'a' => 1],
        ]);
        $target = $this->snapshot([
            'pages' => [
                'zpage' => ['blocks' => [$this->block('z1', 'text', ['props' => ['a' => 2]])]],
                'apage' => ['blocks' => [$this->block('a1', 'text', ['props' => ['a' => 2]])]],
            ],
            'global_styles' => ['z' => 2, 'a' => 2],
        ]);

        $r1 = $this->diff()->diff($base, $target);
        $r2 = $this->diff()->diff($base, $target);

        $this->assertSame($r1->blocks, $r2->blocks);
        $this->assertSame($r1->pages, $r2->pages);
        $this->assertSame($r1->globalStyles, $r2->globalStyles);
        // Also proves sorted, non-hash-order-dependent output: apage/a1
        // before zpage/z1, 'a' before 'z'.
        $this->assertSame('a1', $r1->blocks[0]['id']);
        $this->assertSame('a', array_key_first($r1->globalStyles));
    }

    // ── §20 property edge cases ─────────────────────────────────────────────

    public function test_edge_missing_key_to_null_is_added_not_changed(): void
    {
        $base = $this->snapshot(['global_styles' => []]);
        $target = $this->snapshot(['global_styles' => ['x' => null]]);

        $result = $this->diff()->diff($base, $target);

        $this->assertSame(['change_type' => 'ADDED', 'base_value' => null, 'target_value' => null], $result->globalStyles['x']);
    }

    public function test_edge_null_to_missing_key_is_removed_not_changed(): void
    {
        $base = $this->snapshot(['global_styles' => ['x' => null]]);
        $target = $this->snapshot(['global_styles' => []]);

        $result = $this->diff()->diff($base, $target);

        $this->assertSame(['change_type' => 'REMOVED', 'base_value' => null, 'target_value' => null], $result->globalStyles['x']);
    }

    public function test_edge_false_to_true_is_changed(): void
    {
        $base = $this->snapshot(['global_styles' => ['dark_mode' => ['enabled' => false]]]);
        $target = $this->snapshot(['global_styles' => ['dark_mode' => ['enabled' => true]]]);

        $result = $this->diff()->diff($base, $target);

        $this->assertSame(['change_type' => 'CHANGED', 'base_value' => false, 'target_value' => true], $result->globalStyles['dark_mode.enabled']);
    }

    public function test_edge_int_zero_to_string_zero_is_changed_not_equal(): void
    {
        $base = $this->snapshot(['global_styles' => ['x' => 0]]);
        $target = $this->snapshot(['global_styles' => ['x' => '0']]);

        $result = $this->diff()->diff($base, $target);

        $this->assertSame(['change_type' => 'CHANGED', 'base_value' => 0, 'target_value' => '0'], $result->globalStyles['x']);
    }

    public function test_edge_empty_string_to_null_is_changed_not_equal(): void
    {
        $base = $this->snapshot(['global_styles' => ['x' => '']]);
        $target = $this->snapshot(['global_styles' => ['x' => null]]);

        $result = $this->diff()->diff($base, $target);

        $this->assertSame(['change_type' => 'CHANGED', 'base_value' => '', 'target_value' => null], $result->globalStyles['x']);
    }

    public function test_edge_empty_array_to_missing_key_is_removed(): void
    {
        $base = $this->snapshot(['global_styles' => ['colors' => []]]);
        $target = $this->snapshot(['global_styles' => []]);

        $result = $this->diff()->diff($base, $target);

        $this->assertSame(['change_type' => 'REMOVED', 'base_value' => [], 'target_value' => null], $result->globalStyles['colors']);
    }

    public function test_edge_nested_key_removal_reports_precise_path(): void
    {
        $base = $this->snapshot(['global_styles' => ['colors' => ['primary' => '#000', 'secondary' => '#111']]]);
        $target = $this->snapshot(['global_styles' => ['colors' => ['primary' => '#000']]]);

        $result = $this->diff()->diff($base, $target);

        $this->assertSame(['change_type' => 'REMOVED', 'base_value' => '#111', 'target_value' => null], $result->globalStyles['colors.secondary']);
        $this->assertArrayNotHasKey('colors.primary', $result->globalStyles);
    }

    public function test_edge_nested_key_addition_reports_precise_path(): void
    {
        $base = $this->snapshot(['global_styles' => ['colors' => ['primary' => '#000']]]);
        $target = $this->snapshot(['global_styles' => ['colors' => ['primary' => '#000', 'secondary' => '#111']]]);

        $result = $this->diff()->diff($base, $target);

        $this->assertSame(['change_type' => 'ADDED', 'base_value' => null, 'target_value' => '#111'], $result->globalStyles['colors.secondary']);
    }

    public function test_edge_plain_list_is_compared_atomically_not_index_diffed(): void
    {
        $base = $this->snapshot(['settings' => ['supported_modules' => ['shop', 'blog']]]);
        $target = $this->snapshot(['settings' => ['supported_modules' => ['blog', 'shop', 'forms']]]);

        $result = $this->diff()->diff($base, $target);

        $this->assertSame(['shop', 'blog'], $result->settings['supported_modules']['base_value']);
        $this->assertSame(['blog', 'shop', 'forms'], $result->settings['supported_modules']['target_value']);
    }

    // ── §21 performance: ~500 blocks, O(n+p), no nested id search ─────────

    public function test_performance_five_hundred_blocks_completes_and_is_correct(): void
    {
        $baseBlocks = [];
        $targetBlocks = [];
        for ($i = 0; $i < 500; $i++) {
            $baseBlocks[] = $this->block("blk_{$i}", 'text', ['props' => ['text' => "v{$i}"]]);
            // Every 3rd block gets a prop change; every 7th is deleted;
            // 10 brand-new blocks are added — a realistic mixed workload.
            $targetBlocks[] = ($i % 7 === 0)
                ? null
                : $this->block("blk_{$i}", 'text', ['props' => ['text' => ($i % 3 === 0) ? "v{$i}-changed" : "v{$i}"]]);
        }
        $targetBlocks = array_values(array_filter($targetBlocks));
        for ($i = 0; $i < 10; $i++) {
            $targetBlocks[] = $this->block("cust_new_{$i}", 'text', ['props' => ['text' => 'new']]);
        }

        $base = $this->snapshot(['pages' => ['home' => ['blocks' => $baseBlocks]]]);
        $target = $this->snapshot(['pages' => ['home' => ['blocks' => $targetBlocks]]]);

        $start = microtime(true);
        $result = $this->diff()->diff($base, $target);
        $elapsed = microtime(true) - $start;

        // Correctness, not a brittle microsecond threshold: a generous
        // ceiling that only fails if the implementation regressed to a
        // non-linear (e.g. nested-search-by-id) algorithm.
        $this->assertLessThan(5.0, $elapsed, 'diffing ~500 blocks took far longer than a linear id-map algorithm should');

        $classifications = collect($result->blocks)->pluck('classification', 'id');
        $deletedCount = $classifications->filter(fn ($c) => $c === ['DELETED'])->count();
        $addedCount = $classifications->filter(fn ($c) => $c === ['ADDED'])->count();
        $modifiedCount = $classifications->filter(fn ($c) => in_array('MODIFIED', $c, true))->count();

        $expectedDeleted = count(array_filter(range(0, 499), fn ($i) => $i % 7 === 0));
        $this->assertSame($expectedDeleted, $deletedCount);
        $this->assertSame(10, $addedCount);
        $this->assertGreaterThan(0, $modifiedCount);
    }
}
