<?php

namespace App\Modules\SolarCalculator\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\SolarCalculator\Formula\Expression;
use App\Modules\SolarCalculator\Formula\FormulaEngine;
use App\Modules\SolarCalculator\Formula\FormulaException;
use App\Modules\SolarCalculator\Models\SolarCalculatorFormula;
use App\Modules\SolarCalculator\Services\SolarCalculationService;
use App\Modules\SolarCalculator\Services\SolarCalculatorSettings;
use App\Modules\SolarCalculator\Support\SolarCalculatorDefaults;
use App\Services\ExchangeRates\ExchangeRateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * The formula table editor. Every save is checked as a whole set before
 * it is written: the expression parses, every name it reads exists, no
 * dependency cycle appears, and a dry run with sample answers succeeds —
 * so a typo can never take the live calculator down.
 */
class SolarCalculatorFormulaController extends Controller
{
    public function __construct(
        private readonly SolarCalculationService $calculator,
        private readonly SolarCalculatorSettings $settings,
        private readonly ExchangeRateService $rates,
    ) {
    }

    public function index(Request $request): View
    {
        $formulas = SolarCalculatorFormula::query()->orderBy('sort_order')->orderBy('id')->get();

        $test = null;
        $testInput = $request->session()->get('solar_test_input', $this->calculator->sampleInput());
        try {
            $result = $this->calculator->calculate($testInput);
            $test = ['ok' => true, 'values' => $result['values'], 'price' => $result['price']];
        } catch (FormulaException $e) {
            $test = ['ok' => false, 'error' => $e->getMessage()];
        }

        return view('modules.solar-calculator.admin.formulas.index', [
            'groups' => $formulas->groupBy('group'),
            'groupLabels' => SolarCalculatorFormula::GROUPS,
            'inputs' => SolarCalculationService::INPUT_LABELS,
            'rateVars' => $this->rates->formulaVariables(),
            'functions' => array_keys(Expression::FUNCTIONS),
            'test' => $test,
            'testInput' => $testInput,
            'regions' => $this->settings->regions(),
            'used' => $this->usedKeys(),
            'defaults' => SolarCalculatorDefaults::formulas(),
        ]);
    }

    public function edit(SolarCalculatorFormula $formula): View
    {
        return view('modules.solar-calculator.admin.formulas.edit', [
            'formula' => $formula,
            'groupLabels' => SolarCalculatorFormula::GROUPS,
            'inputs' => SolarCalculationService::INPUT_LABELS,
            'rateVars' => $this->rates->formulaVariables(),
            'functions' => array_keys(Expression::FUNCTIONS),
            'others' => SolarCalculatorFormula::query()->where('id', '!=', $formula->id)->orderBy('sort_order')->get(['key', 'label']),
            'hasDefault' => isset(SolarCalculatorDefaults::formulas()[$formula->key]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'regex:/^[a-z][a-z0-9_]{1,62}$/', Rule::unique('solar_calculator_formulas', 'key')],
            'label' => ['required', 'string', 'max:190'],
            'group' => ['required', Rule::in(array_keys(SolarCalculatorFormula::GROUPS))],
            'expression' => ['required', 'string', 'max:' . Expression::MAX_LENGTH],
            'unit' => ['nullable', 'string', 'max:32'],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [
            'key.regex' => 'کلید فقط حروف کوچک انگلیسی، عدد و _ است و با حرف شروع می‌شود.',
            'key.unique' => 'این کلید قبلاً استفاده شده است.',
        ]);

        $reserved = array_merge($this->calculator->inputNames(), array_keys(Expression::FUNCTIONS), ['and', 'or', 'not', 'true', 'false']);
        if (in_array($data['key'], $reserved, true)) {
            throw ValidationException::withMessages(['key' => 'این کلید رزرو شده است (نام ورودی، تابع یا کلمهٔ کلیدی).']);
        }

        $this->assertSetIsValid([$data['key'] => $data['expression']], 'expression');

        SolarCalculatorFormula::query()->create($data + [
            'is_system' => false,
            'sort_order' => (int) SolarCalculatorFormula::query()->max('sort_order') + 10,
        ]);

        return redirect()->route('admin.solar-calculator.formulas.index')->with('success', 'فرمول «' . $data['key'] . '» افزوده شد.');
    }

    public function update(Request $request, SolarCalculatorFormula $formula): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:190'],
            'group' => ['required', Rule::in(array_keys(SolarCalculatorFormula::GROUPS))],
            'expression' => ['required', 'string', 'max:' . Expression::MAX_LENGTH],
            'unit' => ['nullable', 'string', 'max:32'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->assertSetIsValid([$formula->key => $data['expression']], 'expression');
        $formula->update($data);

        return redirect()->route('admin.solar-calculator.formulas.index')->with('success', 'فرمول «' . $formula->key . '» ذخیره شد.');
    }

    public function destroy(SolarCalculatorFormula $formula): RedirectResponse
    {
        if ($formula->is_system) {
            return back()->with('error', 'فرمول‌های سیستمی حذف‌شدنی نیستند؛ فقط عبارت آن‌ها قابل ویرایش است.');
        }
        $users = $this->referencedBy($formula->key);
        if ($users !== []) {
            return back()->with('error', 'این فرمول در «' . implode('، ', $users) . '» استفاده شده و حذف نمی‌شود.');
        }

        $formula->delete();

        return back()->with('success', 'فرمول «' . $formula->key . '» حذف شد.');
    }

    public function restore(SolarCalculatorFormula $formula): RedirectResponse
    {
        $default = SolarCalculatorDefaults::formulas()[$formula->key][2] ?? null;
        if ($default === null) {
            return back()->with('error', 'این فرمول مقدار پیش‌فرض ندارد.');
        }
        $this->assertSetIsValid([$formula->key => $default], 'expression');
        SolarCalculatorDefaults::restoreFormula($formula);

        return back()->with('success', 'عبارت پیش‌فرض «' . $formula->key . '» بازگردانی شد.');
    }

    /** Re-runs the test bench with the admin's own sample answers. */
    public function test(Request $request): RedirectResponse
    {
        $input = $this->calculator->normalizeInput($request->all());
        $request->session()->put('solar_test_input', $input);

        return redirect()->route('admin.solar-calculator.formulas.index')->withFragment('test-bench');
    }

    /**
     * @param array<string, string> $changes key => new expression
     * @throws ValidationException
     */
    private function assertSetIsValid(array $changes, string $field): void
    {
        $set = array_replace($this->calculator->formulas(), $changes);
        try {
            $engine = new FormulaEngine($set);
        } catch (FormulaException $e) {
            throw ValidationException::withMessages([$field => $e->getMessage()]);
        }

        $errors = $engine->lint($this->calculator->inputNames());
        if ($errors !== []) {
            throw ValidationException::withMessages([$field => $errors]);
        }

        try {
            $engine->evaluate($this->calculator->variables($this->calculator->sampleInput()));
        } catch (FormulaException $e) {
            throw ValidationException::withMessages([$field => 'با ورودی نمونه خطا داد: ' . $e->getMessage()]);
        }
    }

    /** @return array<string, list<string>> key => where it is used (settings) */
    private function usedKeys(): array
    {
        $used = [];
        foreach ($this->settings->section('result_cards') as $c) {
            $used[(string) ($c['key'] ?? '')][] = 'کارت نتایج';
        }
        foreach ($this->settings->section('quote_lines') as $l) {
            $used[(string) ($l['amount_key'] ?? '')][] = 'ردیف پیش‌فاکتور';
            $used[(string) ($l['qty_key'] ?? '')][] = 'ردیف پیش‌فاکتور';
        }
        foreach ($this->settings->section('quote_totals') as $k => $v) {
            if (str_ends_with($k, '_key')) {
                $used[(string) $v][] = 'جمع پیش‌فاکتور';
            }
        }
        unset($used['']);

        return array_map(static fn (array $a) => array_values(array_unique($a)), $used);
    }

    /** @return list<string> */
    private function referencedBy(string $key): array
    {
        $users = $this->usedKeys()[$key] ?? [];
        foreach ($this->calculator->formulas() as $other => $expression) {
            if ($other === $key) {
                continue;
            }
            try {
                if (in_array($key, Expression::parse($expression)->variables(), true)) {
                    $users[] = 'فرمول ' . $other;
                }
            } catch (FormulaException) {
                // An unparsable row cannot reference anything.
            }
        }

        return array_values(array_unique($users));
    }
}
