<?php

namespace App\Modules\SolarCalculator\Canvas;

use App\Helpers\SiteHelper;
use App\Modules\SolarCalculator\Services\SolarAccessPolicy;
use App\Modules\SolarCalculator\Services\SolarCalculatorSettings;
use App\Modules\SolarCalculator\Support\SolarCalculatorDefaults;
use App\Services\ThemeCanvas\BlockRegistry;
use App\Services\ThemeCanvas\CustomerMediaResolver;
use Illuminate\Support\Facades\Auth;

/**
 * The `module:solar_calculator` canvas widget.
 *
 * Host-only and entitlement-gated: it is not in the canonical theme-runtime
 * registry (so the platform Canvas and every other site never see it), it
 * joins this host's Canvas palette only when the site's license includes
 * `solar_calculator` (ThemeDesignController::canvasEdit), and it renders
 * nothing on a site without that license.
 *
 * Every text and image prop defaults to '' = "use the module setting", so
 * the admin edits defaults once in the module and overrides per block.
 */
final class SolarCalculatorWidget
{
    public const TYPE = 'module:solar_calculator';
    public const MODULE = 'solar_calculator';
    private const ASSETS_FLAG = '_solar_calculator_assets_emitted';

    public function __construct(
        private readonly SolarCalculatorSettings $settings,
        private readonly SolarAccessPolicy $access,
    ) {
    }

    /**
     * Same answer as the admin menu: the license projection (plus, on the
     * owner site only, MODULE_SOLAR_CALCULATOR_ENABLED). Owner-site status
     * alone does not offer the widget.
     */
    public static function available(): bool
    {
        return SiteHelper::moduleEnabled(self::MODULE);
    }

    /** Canvas registry definition (schema generated from UI_FIELDS). */
    public static function definition(): array
    {
        $schema = [
            'anchor_id' => ['type' => 'string', 'label' => 'شناسهٔ لنگر (برای پیوند #)', 'default' => 'calc-tool', 'group' => 'نمایش'],
            'layout' => ['type' => 'select', 'label' => 'چیدمان', 'options' => ['stacked' => 'یک‌ستونه', 'split' => 'دوستونه با تصویر کناری'], 'default' => 'stacked', 'group' => 'نمایش'],
            'default_system_type' => ['type' => 'select', 'label' => 'نوع سیستم پیش‌فرض', 'options' => ['' => 'مطابق تنظیمات ماژول', 'on_grid' => 'متصل به شبکه', 'off_grid' => 'مستقل', 'hybrid' => 'هیبرید'], 'default' => '', 'group' => 'نمایش'],
            'show_price' => ['type' => 'select', 'label' => 'نمایش برآورد قیمت', 'options' => ['' => 'مطابق تنظیمات ماژول', 'show' => 'نمایش', 'hide' => 'عدم نمایش'], 'default' => '', 'group' => 'نمایش'],
            'show_steps' => ['type' => 'boolean', 'label' => 'نمایش نوار مراحل', 'default' => true, 'group' => 'نمایش'],
            'accent_color' => ['type' => 'color', 'label' => 'رنگ تأکیدی (خالی = رنگ اصلی قالب)', 'default' => '', 'group' => 'نمایش'],
        ];

        foreach (SolarCalculatorDefaults::UI_FIELDS as $key => [$type, $label]) {
            $schema[$key] = [
                'type' => $type,
                'label' => $label,
                'default' => '',
                'description' => 'خالی بگذارید تا متن تنظیمات ماژول نمایش داده شود.',
                'group' => SolarCalculatorDefaults::UI_GROUPS[SolarCalculatorDefaults::UI_FIELDS[$key][3]],
            ];
        }

        return [
            'name' => 'ماشین‌حساب خورشیدی',
            'category' => BlockRegistry::CATEGORY_MODULE,
            'icon' => 'calculator',
            'requires' => self::MODULE,
            'required_module' => self::MODULE,
            'schema' => $schema,
            'defaultProps' => array_map(static fn (array $f) => $f['default'], $schema),
        ];
    }

    public function render(array $props, string $id, bool $editorMode = false): string
    {
        if (!self::available()) {
            return $editorMode
                ? '<div id="tb-' . e($id) . '" class="tb-module-placeholder" style="padding:1.5rem;border:1px dashed var(--tb-border,#cbd5e1);border-radius:.75rem;text-align:center;color:var(--tb-text-muted,#64748b)">ماژول ماشین‌حساب خورشیدی برای این سایت فعال نیست.</div>'
                : '';
        }

        $resolver = app(CustomerMediaResolver::class);
        foreach (SolarCalculatorDefaults::UI_FIELDS as $key => [$type]) {
            if ($type === 'image' && isset($props[$key]) && !is_string($props[$key])) {
                $props[$key] = $resolver->resolveValue($props[$key]);
            }
        }

        $ui = $this->settings->ui($props);
        foreach (SolarCalculatorDefaults::UI_FIELDS as $key => [$type]) {
            if ($type === 'image') {
                $ui[$key] = self::safeImageUrl($ui[$key]);
            }
        }

        $calc = $this->settings->section('calculator');
        $systemType = in_array($props['default_system_type'] ?? '', SolarCalculatorDefaults::SYSTEM_TYPES, true)
            ? $props['default_system_type']
            : (in_array($calc['default_system_type'], SolarCalculatorDefaults::SYSTEM_TYPES, true) ? $calc['default_system_type'] : 'on_grid');
        $showPrice = match ($props['show_price'] ?? '') {
            'show' => true,
            'hide' => false,
            default => (bool) $calc['show_price'],
        };
        $anchor = preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($props['anchor_id'] ?? 'calc-tool')) ?: 'calc-tool';
        $accent = self::safeColor((string) ($props['accent_color'] ?? '')) ?: self::safeColor((string) ($this->settings->section('company')['accent_color'] ?? ''));
        $user = Auth::user();

        return view('modules.solar-calculator.widget', [
            'id' => $id,
            'anchor' => $anchor,
            'ui' => $ui,
            'layout' => ($props['layout'] ?? 'stacked') === 'split' ? 'split' : 'stacked',
            'showSteps' => (bool) ($props['show_steps'] ?? true),
            'accent' => $accent,
            'regions' => array_map(static fn (array $r) => ['key' => $r['key'], 'label' => $r['label']], $this->settings->regions()),
            'systemType' => $systemType,
            'backupHours' => (float) $calc['default_backup_hours'],
            'showPrice' => $showPrice,
            'mode' => $this->access->mode(),
            'loggedIn' => $user !== null,
            'user' => $user,
            'loginUrl' => $this->access->loginUrl(),
            'requireEmail' => (bool) ($this->settings->section('access')['require_email'] ?? false),
            'editorMode' => $editorMode,
            'withAssets' => $this->claimAssets(),
        ])->render();
    }

    /**
     * The widget's CSS and script go out once per HTTP response. Blade's
     * @once resets after every top-level render, and each canvas block is
     * rendered on its own, so the flag lives on the request instead.
     */
    private function claimAssets(): bool
    {
        if (!app()->bound('request')) {
            return true;
        }
        $attributes = app('request')->attributes;
        if ($attributes->get(self::ASSETS_FLAG, false)) {
            return false;
        }
        $attributes->set(self::ASSETS_FLAG, true);

        return true;
    }

    /** Root-relative or http(s) only; anything else (javascript:, data:, …) is dropped. */
    public static function safeImageUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            return $url;
        }

        return preg_match('#^https?://#i', $url) ? $url : '';
    }

    public static function safeColor(string $color): string
    {
        $color = trim($color);

        return preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color) ? $color : '';
    }
}
