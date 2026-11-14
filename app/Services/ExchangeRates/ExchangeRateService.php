<?php

namespace App\Services\ExchangeRates;

use App\Models\CurrencyExchangeRate;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * The site's one authority for foreign-currency rates (Toman per unit).
 *
 * Rates are entered manually by the admin or pulled by the optional online
 * provider (settings key `exchange_rates_provider`). A rate of 0 is "not
 * set": consumers must show the price as unavailable, never as zero.
 */
class ExchangeRateService
{
    public const PROVIDER_SETTING = 'exchange_rates_provider';

    /** @var Collection<int, CurrencyExchangeRate>|null */
    private ?Collection $active = null;

    public function __construct(private readonly ?ExchangeRateProvider $provider = null)
    {
    }

    /** @return Collection<int, CurrencyExchangeRate> */
    public function all(): Collection
    {
        if (!Schema::hasTable('currency_exchange_rates')) {
            return collect();
        }

        return CurrencyExchangeRate::query()->orderBy('id')->get();
    }

    /** @return Collection<int, CurrencyExchangeRate> active currencies, memoized per instance */
    public function active(): Collection
    {
        return $this->active ??= $this->all()->where('is_active', true)->values();
    }

    /** Toman per unit, or 0 when unknown/not set. IRT and IRR are built in. */
    public function rate(string $code): float
    {
        $code = strtoupper($code);
        if ($code === 'IRT') {
            return 1.0;
        }
        if ($code === 'IRR') {
            return 0.1;
        }

        return (float) ($this->active()->firstWhere('code', $code)?->rate_toman ?? 0);
    }

    /**
     * `rate_usd`, `rate_eur`, … for every active currency (0 when not set),
     * the names the formula engine exposes.
     *
     * @return array<string, float>
     */
    public function formulaVariables(): array
    {
        $vars = [];
        foreach ($this->active() as $row) {
            $vars['rate_' . strtolower($row->code)] = (float) $row->rate_toman;
        }

        return $vars;
    }

    /**
     * What a quote stores so it never changes after issue.
     *
     * @return array<string, array{rate: float, source: string, updated_at: ?string}>
     */
    public function snapshot(): array
    {
        $snap = [];
        foreach ($this->active() as $row) {
            $snap[$row->code] = [
                'rate' => (float) $row->rate_toman,
                'source' => $row->source,
                'updated_at' => ($row->fetched_at ?? $row->updated_at)?->toIso8601String(),
            ];
        }

        return $snap;
    }

    public function setManual(string $code, float $rateToman): CurrencyExchangeRate
    {
        $row = CurrencyExchangeRate::query()->where('code', strtoupper($code))->firstOrFail();
        $row->update([
            'rate_toman' => max(0, $rateToman),
            'source' => CurrencyExchangeRate::SOURCE_MANUAL,
            'fetched_at' => now(),
        ]);
        $this->active = null;

        return $row;
    }

    // ── Online provider ──────────────────────────────────────────────────

    /** @return array{enabled: bool, url: string, unit: string, paths: array<string, string>} */
    public function providerSettings(): array
    {
        $raw = Setting::get(self::PROVIDER_SETTING);
        $data = is_string($raw) ? json_decode($raw, true) : (is_array($raw) ? $raw : null);
        $data = is_array($data) ? $data : [];
        $unit = (string) ($data['unit'] ?? 'toman');

        return [
            'enabled' => (bool) ($data['enabled'] ?? false),
            'url' => (string) ($data['url'] ?? ''),
            'unit' => in_array($unit, ['toman', 'rial'], true) ? $unit : 'toman',
            'paths' => array_map('strval', is_array($data['paths'] ?? null) ? $data['paths'] : []),
        ];
    }

    /** @param array{enabled: bool, url: string, unit: string, paths: array<string, string>} $settings */
    public function saveProviderSettings(array $settings): void
    {
        Setting::set(self::PROVIDER_SETTING, json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'exchange_rates');
    }

    /**
     * Pulls every active currency the provider maps; unmapped or invalid
     * values keep their previous rate.
     *
     * @return array<string, float> the rates that were updated
     *
     * @throws ExchangeRateException
     */
    public function syncOnline(): array
    {
        $settings = $this->providerSettings();
        if (!$settings['enabled'] || $settings['url'] === '') {
            throw new ExchangeRateException('به‌روزرسانی آنلاین نرخ ارز فعال نیست.');
        }

        $provider = $this->provider
            ?? new JsonEndpointExchangeRateProvider($settings['url'], $settings['paths'], $settings['unit']);
        $codes = $this->active()->pluck('code')->all();
        $rates = $provider->fetch($codes);
        if ($rates === []) {
            throw new ExchangeRateException('هیچ نرخ معتبری از سرویس دریافت نشد؛ مسیرهای JSON را بررسی کنید.');
        }

        foreach ($rates as $code => $rate) {
            CurrencyExchangeRate::query()->where('code', $code)->update([
                'rate_toman' => $rate,
                'source' => CurrencyExchangeRate::SOURCE_ONLINE,
                'fetched_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $this->active = null;
        Log::info('exchange_rates.synced', ['codes' => array_keys($rates)]);

        return $rates;
    }
}
