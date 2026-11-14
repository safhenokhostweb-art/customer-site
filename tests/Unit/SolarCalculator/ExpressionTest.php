<?php

namespace Tests\Unit\SolarCalculator;

use App\Modules\SolarCalculator\Formula\Expression;
use App\Modules\SolarCalculator\Formula\FormulaException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ExpressionTest extends TestCase
{
    private function eval(string $src, array $vars = []): float
    {
        return Expression::parse($src)->evaluate(fn (string $n) => $vars[$n] ?? throw new FormulaException("undefined {$n}"));
    }

    public static function arithmetic(): array
    {
        return [
            'precedence' => ['2 + 3 * 4', 14.0],
            'parentheses' => ['(2 + 3) * 4', 20.0],
            'power is right-associative' => ['2 ^ 3 ^ 2', 512.0],
            'unary minus binds looser than power' => ['-2 ^ 2', -4.0],
            'modulo' => ['10 % 4', 2.0],
            'decimal and exponent' => ['1.5e2 + .5', 150.5],
            'comparison yields 1/0' => ['(3 > 2) + (2 >= 3)', 1.0],
            'logic' => ['1 and 0 or not 0', 1.0],
            'symbolic logic' => ['!(1 && 0) || 0', 1.0],
            'equality tolerance' => ['0.1 + 0.2 == 0.3', 1.0],
            'booleans' => ['true + true + false', 2.0],
            'persian digits' => ['۱۲ * ۲', 24.0],
            'min/max variadic' => ['min(4, 2, 9) + max(1, 7)', 9.0],
            'round decimals' => ['round(3.14159, 2)', 3.14],
            'ceil to step' => ['ceil(4.12, 0.5)', 4.5],
            'roundup exact multiple stays' => ['roundup(4.5, 0.5)', 4.5],
            'floor to step' => ['floor(7.9, 2)', 6.0],
            'clamp' => ['clamp(15, 0, 10) + clamp(-3, 0, 10)', 10.0],
            'sqrt abs' => ['sqrt(16) + abs(-2)', 6.0],
        ];
    }

    #[DataProvider('arithmetic')]
    public function test_evaluates(string $src, float $expected): void
    {
        $this->assertEqualsWithDelta($expected, $this->eval($src), 1e-9);
    }

    public function test_variables_are_case_insensitive_and_listed(): void
    {
        $e = Expression::parse('Panel_Count * panel_watt / 1000 + panel_count');
        $this->assertSame(['panel_count', 'panel_watt'], $e->variables());
        $this->assertEqualsWithDelta(3.85 + 7, $e->evaluate(fn ($n) => ['panel_count' => 7, 'panel_watt' => 550][$n]), 1e-9);
    }

    public function test_if_is_lazy_so_the_untaken_branch_never_divides_by_zero(): void
    {
        $this->assertSame(0.0, $this->eval('if(x > 0, 10 / x, 0)', ['x' => 0]));
        $this->assertSame(5.0, $this->eval('if(x > 0, 10 / x, 0)', ['x' => 2]));
    }

    public function test_and_or_short_circuit(): void
    {
        $this->assertSame(0.0, $this->eval('0 and (1 / 0)'));
        $this->assertSame(1.0, $this->eval('1 or (1 / 0)'));
    }

    public static function invalid(): array
    {
        return [
            'empty' => [''],
            'dangling operator' => ['2 +'],
            'unbalanced' => ['(2 + 3'],
            'unknown function (no PHP function is reachable)' => ['system(1)'],
            'php call syntax' => ['exec("ls")'],
            'variable sigil' => ['$x + 1'],
            'backtick' => ['`ls`'],
            'assignment' => ['x = 1'],
            'semicolon' => ['1; 2'],
            'wrong arity' => ['if(1, 2)'],
            'reserved word as value' => ['and + 1'],
            'trailing tokens' => ['2 3'],
            'persian letters' => ['قیمت * 2'],
        ];
    }

    #[DataProvider('invalid')]
    public function test_rejects_invalid_or_unsafe_input(string $src): void
    {
        $this->expectException(FormulaException::class);
        Expression::parse($src);
    }

    public function test_runtime_errors_are_formula_exceptions(): void
    {
        foreach (['1 / 0', '5 % 0', 'sqrt(-1)', 'ceil(3, 0)', '10 ^ 400'] as $src) {
            try {
                $this->eval($src);
                $this->fail("{$src} should fail");
            } catch (FormulaException $e) {
                $this->assertNotSame('', $e->getMessage());
            }
        }
    }

    public function test_length_and_depth_are_bounded(): void
    {
        try {
            Expression::parse(str_repeat('1+', 1100) . '1');
            $this->fail('length not bounded');
        } catch (FormulaException) {
            $this->addToAssertionCount(1);
        }

        $this->expectException(FormulaException::class);
        Expression::parse(str_repeat('(', 80) . '1' . str_repeat(')', 80));
    }
}
