<?php

namespace App\Modules\SolarCalculator\Services;

use App\Helpers\SiteHelper;
use App\Models\Setting;
use App\Modules\SolarCalculator\Models\SolarCalculatorConfig;
use App\Modules\SolarCalculator\Support\SolarCalculatorDefaults;
use App\Services\CustomerMediaService;
use Illuminate\Support\Facades\Schema;

/**
 * Reads and writes the module settings (solar_calculator_configs), merged
 * over SolarCalculatorDefaults::config(). Keyed sections (access, company,
 * ui, …) merge key by key and drop unknown keys; list sections (regions,
 * result_cards, quote_lines) replace the default list as a whole.
 */
class SolarCalculatorSettings
{
    public const LIST_SECTIONS = ['regions', 'result_cards', 'quote_lines'];

    /** @var array<string, mixed>|null */
    private ?array $cache = null;

    /** @return array<string, mixed> */
    public function all(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $stored = Schema::hasTable('solar_calculator_configs')
            ? SolarCalculatorConfig::query()->pluck('value', 'key')->all()
            : [];

        $merged = [];
        foreach (SolarCalculatorDefaults::config() as $key => $default) {
            $value = $stored[$key] ?? null;
            if (is_string($value)) {
                $value = json_decode($value, true);
            }
            if (in_array($key, self::LIST_SECTIONS, true)) {
                $merged[$key] = is_array($value) && array_is_list($value) && $value !== [] ? $value : $default;
            } else {
                $merged[$key] = is_array($value) ? array_replace($default, array_intersect_key($value, $default)) : $default;
            }
        }

        return $this->cache = $merged;
    }

    /** @return array<mixed> */
    public function section(string $key): array
    {
        return $this->all()[$key] ?? [];
    }

    public function save(string $key, array $value): void
    {
        SolarCalculatorConfig::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        $this->cache = null;
    }

    public function accessMode(): string
    {
        $mode = (string) ($this->section('access')['mode'] ?? 'open');

        return array_key_exists($mode, SolarCalculatorDefaults::ACCESS_MODES) ? $mode : 'open';
    }

    /**
     * UI texts and images: module settings, then any non-empty canvas
     * block prop on top (a block prop left empty inherits the setting).
     *
     * @param array<string, mixed> $overrides
     * @return array<string, string>
     */
    public function ui(array $overrides = []): array
    {
        $ui = $this->section('ui');
        foreach (array_keys(SolarCalculatorDefaults::UI_FIELDS) as $key) {
            $value = $overrides[$key] ?? null;
            if (is_string($value) && trim($value) !== '') {
                $ui[$key] = $value;
            }
            $ui[$key] = (string) ($ui[$key] ?? '');
        }

        return $ui;
    }

    /** Company branding with the site's own name/logo as fallbacks. */
    public function company(): array
    {
        $company = $this->section('company');
        if (trim((string) $company['name']) === '') {
            $company['name'] = SiteHelper::siteName();
        }
        if (trim((string) $company['logo']) === '') {
            $company['logo'] = (string) (app(CustomerMediaService::class)->resolveSiteAssetUrl((string) Setting::get('site_logo', '')) ?? '');
        }
        $company['validity_days'] = max(1, (int) $company['validity_days']);

        return $company;
    }

    /** @return list<array{key:string,label:string,psh:float}> */
    public function regions(): array
    {
        return array_values(array_filter(
            $this->section('regions'),
            static fn ($r) => is_array($r) && ($r['key'] ?? '') !== '' && (float) ($r['psh'] ?? 0) > 0,
        ));
    }

    public function flush(): void
    {
        $this->cache = null;
    }
}
