<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\ThemeSetting;
use App\Services\ThemeEngine\TenantThemeResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ThemeDesignController extends Controller
{
    public function __construct(private TenantThemeResolver $resolver) {}

    public function index(): View
    {
        $settings     = ThemeSetting::current();
        $buttonStyles = ThemeSetting::buttonStyles();
        $activePreset = Setting::get('theme_preset_slug', '');
        $presets      = $this->presetList();

        return view('admin.theme-design.index', compact('settings', 'buttonStyles', 'activePreset', 'presets'));
    }

    /** ذخیره تنظیمات سفارشی رنگ / فونت */
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'primary_color'       => 'nullable|string|max:32',
            'secondary_color'     => 'nullable|string|max:32',
            'font_family_heading' => 'nullable|string|max:128',
            'font_family_body'    => 'nullable|string|max:128',
            'base_font_size'      => 'nullable|string|max:24',
            'container_width'     => 'nullable|string|max:32',
            'border_radius'       => 'nullable|string|max:32',
            'button_style'        => 'nullable|in:solid,outline,rounded',
            'section_spacing'     => 'nullable|in:xs,sm,md,lg,xl',
        ]);

        $settings = ThemeSetting::firstOrNew(['site_id' => null]);
        $settings->fill($data);
        $settings->site_id = null;
        $settings->save();

        ThemeSetting::clearCache();
        $this->resolver->clearCache();

        return back()->with('success', 'تنظیمات ظاهر ذخیره شد.');
    }

    /** فعال‌سازی یکی از ۸ پریست Enterprise */
    public function activatePreset(Request $request): RedirectResponse
    {
        $validSlugs = array_column($this->presetList(), 'slug');
        $slug = $request->validate([
            'preset_slug' => 'required|string|in:' . implode(',', $validSlugs),
        ])['preset_slug'];

        Setting::set('theme_preset_slug', $slug, 'theme');
        $this->resolver->clearCache();

        return back()->with('success', 'پریست «' . $this->presetName($slug) . '» فعال شد.');
    }

    /** حذف پریست انتخاب‌شده — برگشت به تنظیمات سفارشی */
    public function resetPreset(): RedirectResponse
    {
        Setting::set('theme_preset_slug', '', 'theme');
        $this->resolver->clearCache();
        return back()->with('success', 'تم به تنظیمات سفارشی بازنشانی شد.');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function presetList(): array
    {
        return [
            ['slug' => 'enterprise-blue',  'name' => 'Enterprise Blue',  'primary' => '#3b82f6', 'accent' => '#4f46e5', 'sidebar' => '#1e293b', 'sidebar_end' => '#0f172a', 'desc' => 'آبی کلاسیک شرکتی — پیش‌فرض'],
            ['slug' => 'modern-indigo',    'name' => 'Modern Indigo',    'primary' => '#6366f1', 'accent' => '#6366f1', 'sidebar' => '#312e81', 'sidebar_end' => '#1e1b4b', 'desc' => 'ایندیگو مدرن و جسورانه'],
            ['slug' => 'emerald-pro',      'name' => 'Emerald Pro',      'primary' => '#22c55e', 'accent' => '#059669', 'sidebar' => '#065f46', 'sidebar_end' => '#022c22', 'desc' => 'سبز زمردی برای فین‌تک و سلامت'],
            ['slug' => 'royal-purple',     'name' => 'Royal Purple',     'primary' => '#a855f7', 'accent' => '#7c3aed', 'sidebar' => '#3b0764', 'sidebar_end' => '#1a0036', 'desc' => 'بنفش سلطنتی برای برندهای لوکس'],
            ['slug' => 'midnight-dark',    'name' => 'Midnight Dark',    'primary' => '#60a5fa', 'accent' => '#818cf8', 'sidebar' => '#111827', 'sidebar_end' => '#030712', 'desc' => 'حالت تاریک کامل'],
            ['slug' => 'graphite',         'name' => 'Graphite',         'primary' => '#6b7280', 'accent' => '#374151', 'sidebar' => '#1f2937', 'sidebar_end' => '#111827', 'desc' => 'خاکستری خنثی و زمان‌پایدار'],
            ['slug' => 'ocean-teal',       'name' => 'Ocean Teal',       'primary' => '#14b8a6', 'accent' => '#0d9488', 'sidebar' => '#134e4a', 'sidebar_end' => '#042f2e', 'desc' => 'فیروزه‌ای برای بهداشت و بهره‌وری'],
            ['slug' => 'executive-gold',   'name' => 'Executive Gold',   'primary' => '#f59e0b', 'accent' => '#b45309', 'sidebar' => '#292524', 'sidebar_end' => '#1c1917', 'desc' => 'طلایی گرم برای داشبوردهای اجرایی'],
        ];
    }

    private function presetName(string $slug): string
    {
        foreach ($this->presetList() as $p) {
            if ($p['slug'] === $slug) return $p['name'];
        }
        return $slug;
    }
}
