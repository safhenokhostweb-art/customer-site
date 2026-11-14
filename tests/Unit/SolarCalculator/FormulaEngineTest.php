<?php

namespace Tests\Unit\SolarCalculator;

use App\Modules\SolarCalculator\Formula\FormulaEngine;
use App\Modules\SolarCalculator\Formula\FormulaException;
use PHPUnit\Framework\TestCase;

class FormulaEngineTest extends TestCase
{
    public function test_evaluates_in_dependency_order_regardless_of_declaration_order(): void
    {
        $engine = new FormulaEngine([
            'total' => 'subtotal * (1 + vat / 100)',
            'subtotal' => 'kw * price_per_kw',
            'price_per_kw' => '1000',
            'vat' => '10',
        ]);

        $values = $engine->evaluate(['kw' => 3]);

        $this->assertEqualsWithDelta(3300.0, $values['total'], 1e-9);
        $this->assertSame(3000.0, $values['subtotal']);
    }

    public function test_cycle_is_reported_with_the_formula_key(): void
    {
        $engine = new FormulaEngine(['a' => 'b + 1', 'b' => 'a * 2']);

        try {
            $engine->evaluate([]);
            $this->fail('cycle not detected');
        } catch (FormulaException $e) {
            $this->assertContains($e->formulaKey, ['a', 'b']);
            $this->assertStringContainsString('چرخه', $e->getMessage());
        }
    }

    public function test_unknown_variable_names_the_formula_at_fault(): void
    {
        $engine = new FormulaEngine(['price' => 'kw * missing_rate']);

        try {
            $engine->evaluate(['kw' => 1]);
            $this->fail('unknown variable accepted');
        } catch (FormulaException $e) {
            $this->assertSame('price', $e->formulaKey);
            $this->assertStringContainsString('missing_rate', $e->getMessage());
        }
    }

    public function test_syntax_error_names_the_formula_at_construction(): void
    {
        try {
            new FormulaEngine(['ok' => '1', 'broken' => '2 * (3']);
            $this->fail('syntax error accepted');
        } catch (FormulaException $e) {
            $this->assertSame('broken', $e->formulaKey);
        }
    }

    public function test_a_formula_may_not_shadow_an_input(): void
    {
        $this->expectException(FormulaException::class);
        (new FormulaEngine(['psh' => '5']))->evaluate(['psh' => 4]);
    }

    public function test_lint_finds_unknown_names_cycles_and_shadowing_without_inputs(): void
    {
        $engine = new FormulaEngine([
            'a' => 'b + nope',
            'b' => 'c',
            'c' => 'a',
            'psh' => '1',
            'fine' => 'psh_input * 2',
        ]);

        $errors = implode("\n", $engine->lint(['psh', 'psh_input']));

        $this->assertStringContainsString('nope', $errors);
        $this->assertStringContainsString('چرخه', $errors);
        $this->assertStringContainsString('«psh»', $errors);
        $this->assertStringNotContainsString('«fine»', $errors);
    }

    public function test_reachable_inputs_follow_formula_dependencies(): void
    {
        $engine = new FormulaEngine([
            'total' => 'panels + install',
            'panels' => 'kw * usd_per_kw * rate_usd',
            'install' => 'kw * 100',
            'usd_per_kw' => '200',
        ]);

        $inputs = $engine->reachableInputs('total');
        sort($inputs);

        $this->assertSame(['kw', 'rate_usd'], $inputs);
    }
}
