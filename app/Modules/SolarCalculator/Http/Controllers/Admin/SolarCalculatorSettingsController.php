<?php

namespace App\Modules\SolarCalculator\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\SolarCalculator\Canvas\SolarCalculatorWidget;
use App\Modules\SolarCalculator\Models\SolarCalculatorFormula;
use App\Modules\SolarCalculator\Services\SolarCalculatorSettings;
use App\Modules\SolarCalculator\Support\SolarCalculatorDefaults;
use App\Services\CustomerMediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use SaaSPlatform\ThemeRuntime\Support\IconLibrary;

class SolarCalculatorSettingsController extends Controller
{
    public const SECTIONS = ['access', 'company', 'ui', 'regions', 'results'];

    public function __construct(
        private readonly SolarCalculatorSettings $settings,
        private readonly CustomerMediaService $media,
    ) {
    }

    public function edit(Request $request): View
    {
        $tab = in_array($request->query('tab'), self::SECTIONS, true) ? $request->query('tab') : 'access';

        return view('modules.solar-calculator.admin.settings', [
            'tab' => $tab,
            'all' => $this->settings->all(),
            'uiFields' => SolarCalculatorDefaults::UI_FIELDS,
            'uiGroups' => SolarCalculatorDefaults::UI_GROUPS,
            'accessModes' => SolarCalculatorDefaults::ACCESS_MODES,
            'formulaKeys' => SolarCalculatorFormula::query()->orderBy('sort_order')->pluck('label', 'key')->all(),
            'icons' => collect(IconLibrary::catalog())->keys()->sort()->values()->all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $section = (string) $request->input('section');
        abort_unless(in_array($section, self::SECTIONS, true), 422);

        match ($section) {
            'access' => $this->saveAccess($request),
            'company' => $this->saveCompany($request),
            'ui' => $this->saveUi($request),
            'regions' => $this->saveRegions($request),
            'results' => $this->saveResults($request),
        };

        return redirect()->route('admin.solar-calculator.settings.edit', ['tab' => $section])->with('success', 'تنظیمات ذخیره شد.');
    }

    public function reset(Request $request): RedirectResponse
    {
        $section = (string) $request->input('section');
        $map = ['access' => ['access', 'calculator'], 'company' => ['company'], 'ui' => ['ui'], 'regions' => ['regions'], 'results' => ['result_cards', 'quote_lines', 'quote_totals']];
        abort_unless(isset($map[$section]), 422);
        $defaults = SolarCalculatorDefaults::config();
        foreach ($map[$section] as $key) {
            $this->settings->save($key, $defaults[$key]);
        }

        return redirect()->route('admin.solar-calculator.settings.edit', ['tab' => $section])->with('success', 'مقادیر پیش‌فرض این بخش بازگردانی شد.');
    }

    private function saveAccess(Request $request): void
    {
        $d = $request->validate([
            'access.mode' => ['required', Rule::in(array_keys(SolarCalculatorDefaults::ACCESS_MODES))],
            'calculator.default_system_type' => ['required', Rule::in(SolarCalculatorDefaults::SYSTEM_TYPES)],
            'calculator.default_backup_hours' => ['required', 'numeric', 'min:0', 'max:72'],
            'calculator.max_monthly_kwh' => ['required', 'numeric', 'min:10', 'max:10000000'],
        ]);

        $this->settings->save('access', [
            'mode' => $d['access']['mode'],
            'require_email' => $request->boolean('access.require_email'),
        ]);
        $this->settings->save('calculator', array_replace($this->settings->section('calculator'), [
            'default_system_type' => $d['calculator']['default_system_type'],
            'default_backup_hours' => (float) $d['calculator']['default_backup_hours'],
            'max_monthly_kwh' => (float) $d['calculator']['max_monthly_kwh'],
            'show_price' => $request->boolean('calculator.show_price'),
            'show_saving' => $request->boolean('calculator.show_saving'),
        ]));
    }

    private function saveCompany(Request $request): void
    {
        $d = $request->validate([
            'company.name' => ['nullable', 'string', 'max:190'],
            'company.phone' => ['nullable', 'string', 'max:64'],
            'company.email' => ['nullable', 'email', 'max:190'],
            'company.address' => ['nullable', 'string', 'max:500'],
            'company.website' => ['nullable', 'url:http,https', 'max:190'],
            'company.logo' => ['nullable', 'string', 'max:2048'],
            'company.accent_color' => ['nullable', 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'company.quote_prefix' => ['required', 'regex:/^[A-Za-z0-9]{1,10}$/'],
            'company.validity_days' => ['required', 'integer', 'min:1', 'max:365'],
            'company.terms' => ['nullable', 'string', 'max:5000'],
            'company_logo_upload' => ['nullable', 'file'],
        ], [
            'company.quote_prefix.regex' => 'پیشوند شماره فقط حروف انگلیسی و عدد (حداکثر ۱۰ نویسه) است.',
            'company.accent_color.regex' => 'رنگ باید کد هگز مانند #0f766e باشد.',
        ]);

        $company = array_map(static fn ($v) => is_string($v) ? trim($v) : $v, $d['company']);
        $company['logo'] = $this->imageValue((string) ($company['logo'] ?? ''), $request->file('company_logo_upload'), 'company_logo_upload');
        $company['validity_days'] = (int) $company['validity_days'];
        foreach (array_keys(SolarCalculatorDefaults::config()['company']) as $key) {
            $company[$key] ??= '';
        }
        $this->settings->save('company', $company);
    }

    private function saveUi(Request $request): void
    {
        $rules = [];
        foreach (SolarCalculatorDefaults::UI_FIELDS as $key => [$type]) {
            $rules["ui.$key"] = ['nullable', 'string', 'max:' . ($type === 'textarea' ? 2000 : ($type === 'image' ? 2048 : 500))];
            if ($type === 'image') {
                $rules["ui_upload.$key"] = ['nullable', 'file'];
            }
        }
        $d = $request->validate($rules);

        $ui = [];
        foreach (SolarCalculatorDefaults::UI_FIELDS as $key => [$type]) {
            $value = trim((string) ($d['ui'][$key] ?? ''));
            $ui[$key] = $type === 'image' ? $this->imageValue($value, $request->file("ui_upload.$key"), "ui_upload.$key") : $value;
        }
        $this->settings->save('ui', $ui);
    }

    private function saveRegions(Request $request): void
    {
        $d = $request->validate([
            'regions' => ['required', 'array', 'min:1', 'max:100'],
            'regions.*.key' => ['required', 'regex:/^[a-z][a-z0-9_]{0,63}$/', 'distinct'],
            'regions.*.label' => ['required', 'string', 'max:100'],
            'regions.*.psh' => ['required', 'numeric', 'min:0.5', 'max:12'],
        ], [
            'regions.*.key.*' => 'کلید هر استان باید یکتا و فقط حروف کوچک انگلیسی، عدد و _ باشد.',
            'regions.*.psh.*' => 'ساعات اوج تابش باید بین ۰٫۵ تا ۱۲ باشد.',
        ]);

        $this->settings->save('regions', array_values(array_map(static fn (array $r) => [
            'key' => $r['key'], 'label' => trim($r['label']), 'psh' => (float) $r['psh'],
        ], $d['regions'])));
    }

    private function saveResults(Request $request): void
    {
        $keys = SolarCalculatorFormula::query()->pluck('key')->all();
        $formula = Rule::in($keys);
        $d = $request->validate([
            'result_cards' => ['required', 'array', 'min:1', 'max:12'],
            'result_cards.*.key' => ['required', $formula],
            'result_cards.*.label' => ['required', 'string', 'max:100'],
            'result_cards.*.unit' => ['nullable', 'string', 'max:32'],
            'result_cards.*.decimals' => ['required', 'integer', 'min:0', 'max:3'],
            'result_cards.*.icon' => ['nullable', 'string', 'max:64'],
            'quote_lines' => ['required', 'array', 'min:1', 'max:30'],
            'quote_lines.*.label' => ['required', 'string', 'max:190'],
            'quote_lines.*.detail' => ['nullable', 'string', 'max:300'],
            'quote_lines.*.qty_key' => ['nullable', $formula],
            'quote_lines.*.qty_unit' => ['nullable', 'string', 'max:32'],
            'quote_lines.*.amount_key' => ['required', $formula],
            'quote_totals.subtotal_key' => ['nullable', $formula],
            'quote_totals.tax_key' => ['nullable', $formula],
            'quote_totals.tax_label' => ['nullable', 'string', 'max:100'],
            'quote_totals.total_key' => ['required', $formula],
            'quote_totals.saving_key' => ['nullable', $formula],
            'quote_totals.payback_key' => ['nullable', $formula],
        ], [
            '*.*.key.in' => 'کلید انتخاب‌شده در جدول فرمول‌ها وجود ندارد.',
        ]);

        $this->settings->save('result_cards', array_values(array_map(static fn (array $c) => [
            'key' => $c['key'],
            'label' => trim($c['label']),
            'unit' => trim((string) ($c['unit'] ?? '')),
            'decimals' => (int) $c['decimals'],
            'icon' => IconLibrary::has((string) ($c['icon'] ?? '')) ? (string) $c['icon'] : '',
            'hide_zero' => !empty($c['hide_zero']),
        ], $d['result_cards'])));

        $this->settings->save('quote_lines', array_values(array_map(static fn (array $l) => [
            'label' => trim($l['label']),
            'detail' => trim((string) ($l['detail'] ?? '')),
            'qty_key' => (string) ($l['qty_key'] ?? ''),
            'qty_unit' => trim((string) ($l['qty_unit'] ?? '')),
            'amount_key' => $l['amount_key'],
            'hide_zero' => !empty($l['hide_zero']),
        ], $d['quote_lines'])));

        $this->settings->save('quote_totals', array_map(static fn ($v) => trim((string) $v), $d['quote_totals'] + [
            'subtotal_key' => '', 'tax_key' => '', 'tax_label' => '', 'saving_key' => '', 'payback_key' => '',
        ]));
    }

    /** An uploaded image (validated by the site media policy) wins over the typed URL. */
    private function imageValue(string $url, ?UploadedFile $upload, string $field): string
    {
        if ($upload !== null) {
            try {
                $media = $this->media->upload($upload, ['title' => 'ماشین‌حساب خورشیدی']);
                if ($media->category !== 'image') {
                    $this->media->deleteUnused($media);
                    throw ValidationException::withMessages([$field => 'فقط فایل تصویری (JPG، PNG، WebP) مجاز است.']);
                }

                return $this->media->publicUrl($media);
            } catch (ValidationException $e) {
                throw ValidationException::withMessages([$field => $e->validator->errors()->first() ?: 'فایل تصویر معتبر نیست.']);
            } catch (\Throwable $e) {
                Log::warning('solar_calculator.image_upload_failed', ['message' => $e->getMessage()]);
                throw ValidationException::withMessages([$field => 'بارگذاری تصویر انجام نشد؛ فایل JPG، PNG یا WebP انتخاب کنید.']);
            }
        }
        if ($url === '') {
            return '';
        }
        $safe = SolarCalculatorWidget::safeImageUrl($url);
        if ($safe === '') {
            throw ValidationException::withMessages([$field => 'آدرس تصویر باید با https:// یا / شروع شود.']);
        }

        return $safe;
    }
}
