<?php

namespace Tests\Unit\ThemeCanvas\Dependency;

use App\Services\ThemeCanvas\Dependency\ThemeDependencyAvailabilityResolver;
use App\Services\ThemeCanvas\Dependency\ThemeDependencyPresence;
use App\Services\ThemeCanvas\Dependency\ThemeUpgradeDependencyGate;
use App\Services\ThemeCanvas\ThemeUpgradeConflict;
use App\Services\ThemeCanvas\ThemeUpgradeReconciler;
use App\Services\ThemeCanvas\ThemeUpgradeReconciliationResult;
use PHPUnit\Framework\TestCase;

/**
 * Phase 3E-A — proves the gate is a genuinely separate, pure layer: it
 * consumes an already-computed ThemeUpgradeReconciliationResult (produced
 * by the real, unmodified ThemeUpgradeReconciler) and only ever adds
 * BLOCKING conflicts for unmet REQUIRED dependencies, or warnings for
 * unmet optional ones — never touches reconciledTheme, never mutates
 * anything, never needs ThemeUpgradeReconciler to know dependencies exist.
 */
class ThemeUpgradeDependencyGateTest extends TestCase
{
    private function cleanResult(): ThemeUpgradeReconciliationResult
    {
        $snap = ['header_blocks' => [], 'footer_blocks' => [], 'pages' => [], 'global_styles' => [], 'settings' => []];
        return (new ThemeUpgradeReconciler())->reconcile($snap, $snap, $snap);
    }

    private function gateWith(array $adapters): ThemeUpgradeDependencyGate
    {
        return new ThemeUpgradeDependencyGate(new ThemeDependencyAvailabilityResolver($adapters));
    }

    // ── A. required available dependency → apply remains allowed ────────

    public function test_a_required_available_dependency_keeps_apply_allowed(): void
    {
        $module = new FakeDependencyResolver('module', ['forms' => ThemeDependencyPresence::present()]);
        $gated = $this->gateWith([$module])->gate($this->cleanResult(), [
            ['type' => 'module', 'identifier' => 'forms', 'version_constraint' => null, 'is_required' => true],
        ]);

        $this->assertTrue($gated->canApply());
        $this->assertFalse($gated->hasBlockingConflicts());
        $this->assertSame([], $gated->conflicts);
    }

    // ── B. required missing dependency → blocking DEPENDENCY_UNAVAILABLE ─

    public function test_b_required_missing_dependency_blocks(): void
    {
        $module = new FakeDependencyResolver('module', []);
        $gated = $this->gateWith([$module])->gate($this->cleanResult(), [
            ['type' => 'module', 'identifier' => 'shop', 'version_constraint' => null, 'is_required' => true],
        ]);

        $this->assertFalse($gated->canApply());
        $this->assertTrue($gated->hasBlockingConflicts());
        $this->assertCount(1, $gated->conflicts);
        $this->assertSame(ThemeUpgradeConflict::DEPENDENCY_UNAVAILABLE, $gated->conflicts[0]->conflictType);
        $this->assertTrue($gated->conflicts[0]->isBlocking());
        $this->assertSame('MISSING', $gated->conflicts[0]->metadata['availability_status']);
    }

    // ── C. required incompatible version → blocking INCOMPATIBLE_DEPENDENCY ─

    public function test_c_required_incompatible_version_blocks(): void
    {
        $motion = new FakeDependencyResolver('motion_asset', ['hero' => ThemeDependencyPresence::present('1.0.0')]);
        $gated = $this->gateWith([$motion])->gate($this->cleanResult(), [
            ['type' => 'motion_asset', 'identifier' => 'hero', 'version_constraint' => '>=2.0', 'is_required' => true],
        ]);

        $this->assertFalse($gated->canApply());
        $this->assertSame(ThemeUpgradeConflict::INCOMPATIBLE_DEPENDENCY, $gated->conflicts[0]->conflictType);
        $this->assertSame('1.0.0', $gated->conflicts[0]->metadata['available_version']);
        $this->assertSame('>=2.0', $gated->conflicts[0]->metadata['required_constraint']);
    }

    // ── D. required unknown type → blocking ──────────────────────────────

    public function test_d_required_unknown_dependency_type_blocks(): void
    {
        $gated = $this->gateWith([])->gate($this->cleanResult(), [
            ['type' => 'icon_pack', 'identifier' => 'brand-icons', 'version_constraint' => null, 'is_required' => true],
        ]);

        $this->assertFalse($gated->canApply());
        $this->assertSame(ThemeUpgradeConflict::DEPENDENCY_UNAVAILABLE, $gated->conflicts[0]->conflictType);
        $this->assertSame('UNVERIFIABLE', $gated->conflicts[0]->metadata['availability_status']);
    }

    // ── E. required unverifiable dependency → blocking ───────────────────

    public function test_e_required_unverifiable_invalid_constraint_blocks(): void
    {
        $module = new FakeDependencyResolver('module', ['forms' => ThemeDependencyPresence::present()]);
        $gated = $this->gateWith([$module])->gate($this->cleanResult(), [
            ['type' => 'module', 'identifier' => 'forms', 'version_constraint' => '~garbage~', 'is_required' => true],
        ]);

        $this->assertFalse($gated->canApply());
        $this->assertSame(ThemeUpgradeConflict::DEPENDENCY_UNAVAILABLE, $gated->conflicts[0]->conflictType);
    }

    // ── F/G. optional unavailable → warning, never blocking ─────────────

    public function test_f_optional_missing_dependency_is_a_warning_not_blocking(): void
    {
        $module = new FakeDependencyResolver('module', []);
        $gated = $this->gateWith([$module])->gate($this->cleanResult(), [
            ['type' => 'module', 'identifier' => 'blog', 'version_constraint' => null, 'is_required' => false],
        ]);

        $this->assertTrue($gated->canApply());
        $this->assertFalse($gated->hasBlockingConflicts());
        $this->assertSame([], $gated->conflicts);
        $this->assertNotEmpty($gated->warnings);
        $this->assertSame('optional_dependency_unavailable', $gated->warnings[0]['type']);
        $this->assertSame('MISSING', $gated->warnings[0]['availability_status']);
    }

    public function test_g_optional_incompatible_dependency_is_a_warning_not_blocking(): void
    {
        $motion = new FakeDependencyResolver('motion_pack', ['seasonal' => ThemeDependencyPresence::present('1.0.0')]);
        $gated = $this->gateWith([$motion])->gate($this->cleanResult(), [
            ['type' => 'motion_pack', 'identifier' => 'seasonal', 'version_constraint' => '^2.0', 'is_required' => false],
        ]);

        $this->assertTrue($gated->canApply());
        $this->assertSame([], $gated->conflicts);
        $this->assertSame('VERSION_INCOMPATIBLE', $gated->warnings[0]['availability_status']);
    }

    // ── H. multiple required dependencies all available ──────────────────

    public function test_h_multiple_required_dependencies_all_available(): void
    {
        $module = new FakeDependencyResolver('module', [
            'forms' => ThemeDependencyPresence::present(),
            'shop' => ThemeDependencyPresence::present(),
        ]);

        $gated = $this->gateWith([$module])->gate($this->cleanResult(), [
            ['type' => 'module', 'identifier' => 'forms', 'version_constraint' => null, 'is_required' => true],
            ['type' => 'module', 'identifier' => 'shop', 'version_constraint' => null, 'is_required' => true],
        ]);

        $this->assertTrue($gated->canApply());
        $this->assertSame([], $gated->conflicts);
    }

    // ── I. one blocker among many makes canApply=false ───────────────────

    public function test_i_one_blocker_among_many_makes_can_apply_false(): void
    {
        $module = new FakeDependencyResolver('module', ['forms' => ThemeDependencyPresence::present()]);
        $gated = $this->gateWith([$module])->gate($this->cleanResult(), [
            ['type' => 'module', 'identifier' => 'forms', 'version_constraint' => null, 'is_required' => true],
            ['type' => 'module', 'identifier' => 'shop', 'version_constraint' => null, 'is_required' => true], // missing
            ['type' => 'module', 'identifier' => 'blog', 'version_constraint' => null, 'is_required' => false], // optional, missing
        ]);

        $this->assertFalse($gated->canApply());
        $this->assertCount(1, $gated->conflicts);
        $this->assertNotEmpty($gated->warnings);
    }

    // ── S. unknown future optional dependency does not crash ────────────

    public function test_s_unknown_future_optional_dependency_type_does_not_crash(): void
    {
        $gated = $this->gateWith([])->gate($this->cleanResult(), [
            ['type' => 'quantum_widget_pack', 'identifier' => 'not-invented-yet', 'version_constraint' => null, 'is_required' => false],
        ]);

        $this->assertTrue($gated->canApply());
        $this->assertSame([], $gated->conflicts);
        $this->assertSame('UNVERIFIABLE', $gated->warnings[0]['availability_status']);
    }

    public function test_malformed_dependency_row_required_fails_closed(): void
    {
        $gated = $this->gateWith([])->gate($this->cleanResult(), [
            ['is_required' => true], // no type/identifier at all
        ]);

        $this->assertFalse($gated->canApply());
    }

    // ── T. ThemeUpgradeReconciler remains pure and dependency-agnostic ───

    public function test_t_reconciler_output_is_untouched_by_gating(): void
    {
        $clean = $this->cleanResult();
        $module = new FakeDependencyResolver('module', []);

        $gated = $this->gateWith([$module])->gate($clean, [
            ['type' => 'module', 'identifier' => 'shop', 'version_constraint' => null, 'is_required' => true],
        ]);

        $this->assertSame($clean->reconciledTheme, $gated->reconciledTheme, 'the gate must never alter the reconciled Theme JSON');
        $this->assertSame($clean->preservedCustomizations, $gated->preservedCustomizations);
        $this->assertSame($clean->detachedBlocks, $gated->detachedBlocks);
        $this->assertSame($clean->fingerprint, $gated->fingerprint, 'dependency gating does not change what (BASE,LOCAL,REMOTE) triple this describes');
    }

    public function test_a_result_with_no_dependencies_at_all_is_unaffected(): void
    {
        $clean = $this->cleanResult();
        $gated = $this->gateWith([])->gate($clean, []);

        $this->assertSame($clean, $gated, 'no dependencies to check must return the exact same object, not a needless copy');
    }
}
