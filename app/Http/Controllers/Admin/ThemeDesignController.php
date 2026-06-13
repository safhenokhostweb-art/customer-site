<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\ThemeSetting;
use App\Services\ThemeEngine\TenantThemeResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
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
        $canvasActive = !empty(Setting::get('active_canvas_theme_data'));
        $canvasName   = Setting::get('active_canvas_theme_name', '');

        return view('admin.theme-design.index', compact('settings', 'buttonStyles', 'activePreset', 'presets', 'canvasActive', 'canvasName'));
    }

    // ── Canvas Theme Management ───────────────────────────────────────────────

    public function canvasTheme(): View
    {
        $themeData = null;
        $raw = Setting::get('active_canvas_theme_data');
        if ($raw) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $themeData = $decoded;
            }
        }

        return view('admin.theme-design.canvas', [
            'themeData' => $themeData,
            'themeName' => Setting::get('active_canvas_theme_name', ''),
            'themeSlug' => Setting::get('active_canvas_theme_slug', ''),
            'themeId'   => Setting::get('active_canvas_theme_id', ''),
        ]);
    }

    public function canvasDeactivate(): RedirectResponse
    {
        Setting::set('active_canvas_theme_data',  '', 'theme');
        Setting::set('active_canvas_theme_name',  '', 'theme');
        Setting::set('active_canvas_theme_slug',  '', 'theme');
        Setting::set('active_canvas_theme_id',    '', 'theme');

        Cache::forget('canvas_theme_data');
        $this->resolver->clearCache();

        return redirect()->route('admin.theme-design.canvas')
            ->with('success', 'قالب Canvas غیرفعال شد. سایت به حالت پیش‌فرض بازگشت.');
    }

    public function canvasExport()
    {
        $raw = Setting::get('active_canvas_theme_data');
        if (empty($raw)) {
            return back()->with('error', 'هیچ قالب Canvas فعالی برای صادر کردن وجود ندارد.');
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return back()->with('error', 'داده قالب Canvas معتبر نیست.');
        }

        $data['_export_meta'] = [
            'exported_at'  => now()->toIso8601String(),
            'theme_name'   => Setting::get('active_canvas_theme_name', ''),
            'theme_slug'   => Setting::get('active_canvas_theme_slug', ''),
            'theme_id'     => Setting::get('active_canvas_theme_id', ''),
            'exported_by'  => 'canvas-admin',
        ];

        $slug     = Str::slug(Setting::get('active_canvas_theme_name', 'canvas-theme'));
        $filename = $slug . '-' . now()->format('Ymd') . '.json';

        return response(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function canvasImport(Request $request): RedirectResponse
    {
        $request->validate([
            'theme_file' => 'required|file|mimes:json|max:2048',
        ], [
            'theme_file.required' => 'فایل قالب الزامی است.',
            'theme_file.mimes'    => 'فایل باید از نوع JSON باشد.',
            'theme_file.max'      => 'حداکثر حجم فایل ۲ مگابایت است.',
        ]);

        $contents = file_get_contents($request->file('theme_file')->getRealPath());
        $data     = json_decode($contents, true);

        if (!is_array($data)) {
            return back()->with('error', 'فایل JSON معتبر نیست.');
        }

        $hasStructure = isset($data['pages']) || isset($data['blocks'])
            || isset($data['header_blocks']) || isset($data['footer_blocks'])
            || isset($data['global_styles']);

        if (!$hasStructure) {
            return back()->with('error', 'فایل JSON ساختار قالب Canvas را ندارد. مطمئن شوید فایل از همین سیستم صادر شده باشد.');
        }

        $meta      = $data['_export_meta'] ?? [];
        $themeName = $meta['theme_name'] ?? $request->file('theme_file')->getClientOriginalName();
        $themeSlug = $meta['theme_slug'] ?? Str::slug($themeName);
        $themeId   = $meta['theme_id']   ?? 'imported-' . now()->timestamp;

        unset($data['_export_meta']);

        Setting::set('active_canvas_theme_data', json_encode($data), 'theme');
        Setting::set('active_canvas_theme_name', $themeName,         'theme');
        Setting::set('active_canvas_theme_slug', $themeSlug,         'theme');
        Setting::set('active_canvas_theme_id',   $themeId,           'theme');

        Cache::forget('canvas_theme_data');
        $this->resolver->clearCache();

        return redirect()->route('admin.theme-design.canvas')
            ->with('success', 'قالب «' . $themeName . '» با موفقیت وارد و فعال شد.');
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
