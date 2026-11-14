<?php

namespace App\Modules\SolarCalculator\Services;

use App\Helpers\CurrencyHelper;
use App\Modules\SolarCalculator\Formula\Expression;
use App\Modules\SolarCalculator\Formula\FormulaEngine;
use App\Modules\SolarCalculator\Models\SolarCalculatorFormula;
use App\Modules\SolarCalculator\Support\SolarCalculatorDefaults;
use App\Services\ExchangeRates\ExchangeRateService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use SaaSPlatform\ThemeRuntime\Support\PersianNumber;

/**
 * Turns a visitor's answers into the system design and the itemized
 * quote. All engineering and pricing logic lives in the formula table;
 * this class only builds the input variables, runs the engine and shapes
 * the output the widget, the quote and the admin read.
 */
class SolarCalculationService
{
    /** Variables the engine receives besides the `rate_*` exchange rates. */
    public const INPUT_NAMES = [
        'consumption_by_bill', 'input_monthly_kwh', 'input_monthly_bill',
        'psh', 'roof_area', 'backup_hours', 'is_on_grid', 'is_off_grid', 'is_hybrid',
    ];

    public const INPUT_LABELS = [
        'consumption_by_bill' => '۱ اگر کاربر مبلغ قبض را وارد کرده باشد، وگرنه ۰',
        'input_monthly_kwh' => 'مصرف ماهانهٔ واردشده (کیلووات‌ساعت)',
        'input_monthly_bill' => 'مبلغ قبض ماهانهٔ واردشده (تومان)',
        'psh' => 'ساعات اوج تابش روزانهٔ استان انتخاب‌شده',
        'roof_area' => 'فضای واردشده (مترمربع؛ ۰ یعنی نامشخص)',
        'backup_hours' => 'ساعات برق اضطراری (فقط هیبرید)',
        'is_on_grid' => '۱ برای سیستم متصل به شبکه',
        'is_off_grid' => '۱ برای سیستم مستقل',
        'is_hybrid' => '۱ برای سیستم هیبرید',
    ];

    private ?FormulaEngine $engine = null;

    public function __construct(
        private readonly SolarCalculatorSettings $settings,
        private readonly ExchangeRateService $rates,
    ) {
    }

    /** @return array<string, string> key => expression, in admin order */
    public function formulas(): array
    {
        return SolarCalculatorFormula::query()->orderBy('sort_order')->orderBy('id')->pluck('expression', 'key')->all();
    }

    public function engine(): FormulaEngine
    {
        return $this->engine ??= new FormulaEngine($this->formulas());
    }

    /** Forget the compiled formulas (after an admin edit). */
    public function refresh(): void
    {
        $this->engine = null;
    }

    /** @return list<string> */
    public function inputNames(): array
    {
        return array_merge(self::INPUT_NAMES, array_keys($this->rates->formulaVariables()));
    }

    /**
     * Validates and normalizes raw request data (Persian digits and
     * thousands separators accepted).
     *
     * @return array{system_type:string, consumption_mode:string, monthly_kwh:float, monthly_bill:float, region:string, roof_area:float, backup_hours:float}
     *
     * @throws ValidationException
     */
    public function normalizeInput(array $raw): array
    {
        $calc = $this->settings->section('calculator');
        $num = static function ($v): ?string {
            if ($v === null || $v === '') {
                return null;
            }
            $s = str_replace([',', ' ', '٬'], '', Expression::normalizeDigits((string) $v));

            return $s;
        };

        $data = [
            'system_type' => (string) ($raw['system_type'] ?? ''),
            'consumption_mode' => (string) ($raw['consumption_mode'] ?? 'kwh'),
            'monthly_kwh' => $num($raw['monthly_kwh'] ?? null),
            'monthly_bill' => $num($raw['monthly_bill'] ?? null),
            'region' => (string) ($raw['region'] ?? ''),
            'roof_area' => $num($raw['roof_area'] ?? null),
            'backup_hours' => $num($raw['backup_hours'] ?? null),
        ];

        $regionKeys = array_column($this->settings->regions(), 'key');
        $validator = Validator::make($data, [
            'system_type' => ['required', 'in:' . implode(',', SolarCalculatorDefaults::SYSTEM_TYPES)],
            'consumption_mode' => ['required', 'in:kwh,bill'],
            'monthly_kwh' => ['required_if:consumption_mode,kwh', 'nullable', 'numeric', 'min:1', 'max:' . (float) $calc['max_monthly_kwh']],
            'monthly_bill' => ['required_if:consumption_mode,bill', 'nullable', 'numeric', 'min:1000', 'max:' . (float) $calc['max_monthly_bill']],
            'region' => ['required', 'in:' . implode(',', $regionKeys)],
            'roof_area' => ['nullable', 'numeric', 'min:0', 'max:' . (float) $calc['max_roof_area']],
            'backup_hours' => ['nullable', 'numeric', 'min:0', 'max:' . (float) $calc['max_backup_hours']],
        ], [
            'system_type.*' => 'نوع سیستم را انتخاب کنید.',
            'monthly_kwh.required_if' => 'مصرف ماهانه را وارد کنید.',
            'monthly_kwh.*' => 'مصرف ماهانه باید عددی معتبر باشد.',
            'monthly_bill.required_if' => 'مبلغ قبض را وارد کنید.',
            'monthly_bill.*' => 'مبلغ قبض باید عددی معتبر (حداقل ۱٬۰۰۰ تومان) باشد.',
            'region.*' => 'استان محل نصب را انتخاب کنید.',
            'roof_area.*' => 'مساحت باید عددی معتبر باشد.',
            'backup_hours.*' => 'ساعات برق اضطراری باید عددی معتبر باشد.',
            'consumption_mode.*' => 'روش ورود مصرف نامعتبر است.',
        ]);
        $validator->validate();

        return [
            'system_type' => $data['system_type'],
            'consumption_mode' => $data['consumption_mode'],
            'monthly_kwh' => $data['consumption_mode'] === 'kwh' ? (float) $data['monthly_kwh'] : 0.0,
            'monthly_bill' => $data['consumption_mode'] === 'bill' ? (float) $data['monthly_bill'] : 0.0,
            'region' => $data['region'],
            'roof_area' => (float) ($data['roof_area'] ?? 0),
            'backup_hours' => $data['system_type'] === 'hybrid'
                ? (float) ($data['backup_hours'] ?? $calc['default_backup_hours'])
                : 0.0,
        ];
    }

    /**
     * @param array{system_type:string, consumption_mode:string, monthly_kwh:float, monthly_bill:float, region:string, roof_area:float, backup_hours:float} $input
     * @return array<string, float> engine variables
     */
    public function variables(array $input): array
    {
        $region = collect($this->settings->regions())->firstWhere('key', $input['region']);

        return [
            'consumption_by_bill' => $input['consumption_mode'] === 'bill' ? 1.0 : 0.0,
            'input_monthly_kwh' => $input['monthly_kwh'],
            'input_monthly_bill' => $input['monthly_bill'],
            'psh' => (float) ($region['psh'] ?? 5.0),
            'roof_area' => $input['roof_area'],
            'backup_hours' => $input['backup_hours'],
            'is_on_grid' => $input['system_type'] === 'on_grid' ? 1.0 : 0.0,
            'is_off_grid' => $input['system_type'] === 'off_grid' ? 1.0 : 0.0,
            'is_hybrid' => $input['system_type'] === 'hybrid' ? 1.0 : 0.0,
        ] + $this->rates->formulaVariables();
    }

    /**
     * Runs the engine and shapes the result.
     *
     * @param array{system_type:string, consumption_mode:string, monthly_kwh:float, monthly_bill:float, region:string, roof_area:float, backup_hours:float} $input
     * @return array<string, mixed>
     *
     * @throws \App\Modules\SolarCalculator\Formula\FormulaException
     */
    public function calculate(array $input, ?bool $showPrice = null): array
    {
        $vars = $this->variables($input);
        $values = $this->engine()->evaluate($vars);
        $all = $values + $vars;

        $totals = $this->settings->section('quote_totals');
        $calc = $this->settings->section('calculator');
        $totalKey = (string) $totals['total_key'];

        $missingRates = [];
        if (isset($values[$totalKey])) {
            foreach ($this->engine()->reachableInputs($totalKey) as $name) {
                if (str_starts_with($name, 'rate_') && ($all[$name] ?? 0) <= 0) {
                    $missingRates[] = strtoupper(substr($name, 5));
                }
            }
        } else {
            $missingRates[] = '—';
        }
        $missingRates = array_values(array_unique($missingRates));
        $priceAvailable = $missingRates === [] && ($values[$totalKey] ?? 0) > 0;
        $priceVisible = $showPrice ?? (bool) $calc['show_price'];

        $currency = CurrencyHelper::displayContext();
        $money = static fn (float $v): string => CurrencyHelper::formatPriceWithContext(round($v), $currency);

        $cards = [];
        foreach ($this->settings->section('result_cards') as $card) {
            $key = (string) ($card['key'] ?? '');
            if ($key === '' || !array_key_exists($key, $all)) {
                continue;
            }
            $value = (float) $all[$key];
            if (!empty($card['hide_zero']) && abs($value) < 1e-9) {
                continue;
            }
            $decimals = max(0, min(3, (int) ($card['decimals'] ?? 0)));
            $cards[] = [
                'key' => $key,
                'label' => (string) ($card['label'] ?? $key),
                'unit' => (string) ($card['unit'] ?? ''),
                'icon' => (string) ($card['icon'] ?? ''),
                'value' => round($value, $decimals),
                'display' => PersianNumber::format($value, $decimals),
            ];
        }

        $saving = (float) ($values[(string) $totals['saving_key']] ?? 0);
        $payback = (float) ($values[(string) $totals['payback_key']] ?? 0);

        return [
            'values' => $values,
            'cards' => $cards,
            'roof_fits' => (bool) round((float) ($values['roof_fits'] ?? 1)),
            'price' => [
                'visible' => $priceVisible,
                'available' => $priceAvailable,
                'missing_rates' => $missingRates,
                'total' => $priceAvailable ? round((float) $values[$totalKey]) : null,
                'total_html' => $priceAvailable ? $money((float) $values[$totalKey]) : null,
                'unit' => CurrencyHelper::unitLabel(),
                'saving_html' => $calc['show_saving'] && $saving > 0 ? $money($saving) : null,
                'payback_display' => $calc['show_saving'] && $payback > 0 ? PersianNumber::format($payback, 1) : null,
            ],
            'quote' => $priceAvailable ? $this->quote($all, $money) : null,
        ];
    }

    /**
     * @param array<string, float> $all
     * @param callable(float):string $money
     * @return array<string, mixed>
     */
    private function quote(array $all, callable $money): array
    {
        $totals = $this->settings->section('quote_totals');
        $lines = [];
        foreach ($this->settings->section('quote_lines') as $line) {
            $amountKey = (string) ($line['amount_key'] ?? '');
            if ($amountKey === '' || !array_key_exists($amountKey, $all)) {
                continue;
            }
            $amount = (float) $all[$amountKey];
            $qtyKey = (string) ($line['qty_key'] ?? '');
            $qty = $qtyKey !== '' && array_key_exists($qtyKey, $all) ? (float) $all[$qtyKey] : 1.0;
            if (!empty($line['hide_zero']) && ($amount <= 0 || $qty <= 0)) {
                continue;
            }
            $lines[] = [
                'label' => (string) ($line['label'] ?? ''),
                'detail' => $this->fillTemplate((string) ($line['detail'] ?? ''), $all),
                'qty' => $qty,
                'qty_display' => PersianNumber::format($qty, self::decimalsFor($qty)),
                'qty_unit' => (string) ($line['qty_unit'] ?? ''),
                'unit_price' => $qty > 0 ? round($amount / $qty) : round($amount),
                'unit_price_html' => $money($qty > 0 ? $amount / $qty : $amount),
                'amount' => round($amount),
                'amount_html' => $money($amount),
            ];
        }

        $get = static fn (string $key): ?float => $key !== '' && array_key_exists($key, $all) ? (float) $all[$key] : null;
        $subtotal = $get((string) $totals['subtotal_key']);
        $tax = $get((string) $totals['tax_key']);
        $total = (float) $get((string) $totals['total_key']);

        return [
            'lines' => $lines,
            'subtotal' => $subtotal !== null ? round($subtotal) : null,
            'subtotal_html' => $subtotal !== null ? $money($subtotal) : null,
            'tax_label' => (string) $totals['tax_label'],
            'tax' => $tax !== null ? round($tax) : null,
            'tax_html' => $tax !== null && $tax > 0 ? $money($tax) : null,
            'total' => round($total),
            'total_html' => $money($total),
        ];
    }

    /** `{panel_watt}` → «۵۵۰» for any computed or input value. */
    public function fillTemplate(string $template, array $all): string
    {
        return (string) preg_replace_callback('/\{([a-z_][a-z0-9_]*)\}/', static function (array $m) use ($all): string {
            if (!array_key_exists($m[1], $all)) {
                return $m[0];
            }
            $v = (float) $all[$m[1]];

            return PersianNumber::format($v, self::decimalsFor($v));
        }, $template);
    }

    private static function decimalsFor(float $v): int
    {
        if (abs($v - round($v)) < 1e-6) {
            return 0;
        }

        return abs($v * 10 - round($v * 10)) < 1e-6 ? 1 : 2;
    }

    /** Sample answers for the admin's formula test bench. */
    public function sampleInput(): array
    {
        $regions = $this->settings->regions();

        return [
            'system_type' => 'on_grid', 'consumption_mode' => 'kwh', 'monthly_kwh' => 450.0,
            'monthly_bill' => 0.0, 'region' => $regions[0]['key'] ?? 'tehran', 'roof_area' => 0.0, 'backup_hours' => 0.0,
        ];
    }
}
