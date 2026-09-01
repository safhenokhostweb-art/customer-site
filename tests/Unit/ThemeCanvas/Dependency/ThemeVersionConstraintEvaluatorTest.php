<?php

namespace Tests\Unit\ThemeCanvas\Dependency;

use App\Services\ThemeCanvas\Dependency\ThemeVersionConstraintEvaluator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Phase 3E-A — the canonical evaluator for the exact constraint grammar
 * ThemeReleaseService::assertValidVersionConstraintSyntax() already
 * accepts (space-separated AND clauses, operators >=, <=, >, <, =, ^).
 */
class ThemeVersionConstraintEvaluatorTest extends TestCase
{
    private function evaluator(): ThemeVersionConstraintEvaluator
    {
        return new ThemeVersionConstraintEvaluator();
    }

    // ── J. >= constraint ──────────────────────────────────────────────────

    public function test_j_greater_than_or_equal(): void
    {
        $e = $this->evaluator();
        $this->assertTrue($e->satisfies('2.0.0', '>=2.0'));
        $this->assertTrue($e->satisfies('3.0.0', '>=2.0'));
        $this->assertFalse($e->satisfies('1.9.0', '>=2.0'));
    }

    // ── K. <= constraint ──────────────────────────────────────────────────

    public function test_k_less_than_or_equal(): void
    {
        $e = $this->evaluator();
        $this->assertTrue($e->satisfies('3.0.0', '<=3.0'));
        $this->assertFalse($e->satisfies('3.0.1', '<=3.0'));
    }

    // ── L. exact = constraint ────────────────────────────────────────────

    public function test_l_exact_equality(): void
    {
        $e = $this->evaluator();
        $this->assertTrue($e->satisfies('1.4.2', '=1.4.2'));
        $this->assertFalse($e->satisfies('1.4.3', '=1.4.2'));
    }

    // ── M. combined >=1.5 <3.0 constraint ────────────────────────────────

    public function test_m_combined_and_clauses(): void
    {
        $e = $this->evaluator();
        $this->assertTrue($e->satisfies('2.0.0', '>=1.5 <3.0'));
        $this->assertTrue($e->satisfies('1.5.0', '>=1.5 <3.0'));
        $this->assertFalse($e->satisfies('1.4.9', '>=1.5 <3.0'));
        $this->assertFalse($e->satisfies('3.0.0', '>=1.5 <3.0'));
    }

    // ── N/O. ^2.0 caret (compatible-with) constraint ─────────────────────

    public function test_n_caret_compatible_version(): void
    {
        $e = $this->evaluator();
        $this->assertTrue($e->satisfies('2.0.0', '^2.0'));
        $this->assertTrue($e->satisfies('2.5.3', '^2.0'));
    }

    public function test_o_caret_incompatible_major(): void
    {
        $e = $this->evaluator();
        $this->assertFalse($e->satisfies('3.0.0', '^2.0'));
        $this->assertFalse($e->satisfies('1.9.9', '^2.0'));
    }

    // ── P. invalid constraint fails closed ───────────────────────────────

    public function test_p_invalid_constraint_throws_never_silently_passes(): void
    {
        $e = $this->evaluator();
        $this->assertFalse($e->isValidSyntax('~2.0'));
        $this->assertFalse($e->isValidSyntax(''));
        $this->assertFalse($e->isValidSyntax('>=abc'));
        $this->assertFalse($e->isValidSyntax('>= 2.0 extra garbage !!'));

        $this->expectException(InvalidArgumentException::class);
        $e->satisfies('2.0.0', '~2.0');
    }

    public function test_valid_syntax_examples_from_the_canonical_grammar(): void
    {
        $e = $this->evaluator();
        foreach (['>=1.5 <3.0', '^2.0', '=1.4.2', '>=2.0', '>1', '<=5.5.5'] as $constraint) {
            $this->assertTrue($e->isValidSyntax($constraint), "\"{$constraint}\" should be valid syntax");
        }
    }
}
