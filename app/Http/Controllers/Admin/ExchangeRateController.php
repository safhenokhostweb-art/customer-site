<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CurrencyExchangeRate;
use App\Modules\SolarCalculator\Formula\Expression;
use App\Services\ExchangeRates\ExchangeRateException;
use App\Services\ExchangeRates\ExchangeRateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin page for the site's exchange rates: manual rates per currency, and
 * the optional online JSON provider with an on-demand sync.
 */
class ExchangeRateController extends Controller
{
    public function __construct(private readonly ExchangeRateService $rates)
    {
    }

    public function index(): View
    {
        return view('admin.exchange-rates.index', [
            'rows' => $this->rates->all(),
            'provider' => $this->rates->providerSettings(),
        ]);
    }

    public function updateRates(Request $request): RedirectResponse
    {
        $request->merge(['rates' => array_map(
            static fn ($v) => str_replace([',', '٬', ' '], '', Expression::normalizeDigits((string) $v)),
            (array) $request->input('rates', []),
        )]);
        $data = $request->validate([
            'rates' => ['required', 'array'],
            'rates.*' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'active' => ['nullable', 'array'],
        ], ['rates.*.*' => 'نرخ باید عددی معتبر (تومان) باشد.']);

        foreach (CurrencyExchangeRate::query()->get() as $row) {
            $active = isset($data['active'][$row->code]);
            if ($row->is_active !== $active) {
                $row->update(['is_active' => $active]);
            }
            if (!array_key_exists($row->code, $data['rates']) || $data['rates'][$row->code] === null || $data['rates'][$row->code] === '') {
                continue;
            }
            $new = (float) $data['rates'][$row->code];
            if (abs($new - $row->rate_toman) > 0.00001) {
                $this->rates->setManual($row->code, $new);
            }
        }

        return back()->with('success', 'نرخ‌های ارز ذخیره شد.');
    }

    public function updateProvider(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'url' => ['nullable', 'url:https', 'max:500'],
            'unit' => ['required', 'in:toman,rial'],
            'paths' => ['nullable', 'array'],
            'paths.*' => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9_.\-]*$/'],
        ], [
            'url.url' => 'آدرس سرویس باید با https:// شروع شود.',
            'paths.*.regex' => 'مسیر JSON فقط حروف انگلیسی، عدد، نقطه، - و _ است (مثلاً data.usd.sell).',
        ]);
        $enabled = $request->boolean('enabled');
        if ($enabled && empty($data['url'])) {
            return back()->withErrors(['url' => 'برای فعال‌سازی، آدرس سرویس را وارد کنید.'])->withInput();
        }

        $this->rates->saveProviderSettings([
            'enabled' => $enabled,
            'url' => (string) ($data['url'] ?? ''),
            'unit' => $data['unit'],
            'paths' => array_filter(array_map('strval', $data['paths'] ?? []), static fn ($p) => $p !== ''),
        ]);

        return back()->with('success', 'تنظیمات سرویس آنلاین ذخیره شد.');
    }

    public function sync(): RedirectResponse
    {
        try {
            $updated = $this->rates->syncOnline();
        } catch (ExchangeRateException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'نرخ‌ها به‌روزرسانی شد: ' . implode('، ', array_keys($updated)));
    }
}
