<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\ThemeSetting;
use App\Services\ThemeCanvas\BlockRegistry;
use App\Services\ThemeEngine\TenantThemeResolver;
use Illuminate\Http\JsonResponse;
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

    // ── Canvas Editor (drag-and-drop) ────────────────────────────────────────

    public function canvasEdit()
    {
        $raw = Setting::get('active_canvas_theme_data');
        if (empty($raw)) {
            return redirect()->route('admin.theme-design.canvas')
                ->with('error', 'ابتدا یک قالب Canvas نصب کنید.');
        }

        $themeData = json_decode($raw, true);
        if (!is_array($themeData)) {
            return redirect()->route('admin.theme-design.canvas')
                ->with('error', 'داده قالب Canvas معتبر نیست.');
        }

        // Normalize pages to keyed array
        $raw_pages = $themeData['pages'] ?? [];
        $pages = [];
        if (!empty($raw_pages)) {
            foreach ($raw_pages as $k => $page) {
                $slug = $page['slug'] ?? (is_string($k) ? $k : 'home');
                if (empty($slug)) $slug = 'home';
                $pages[$slug] = [
                    'title'   => $page['title'] ?? ($slug === 'home' ? 'صفحه اصلی' : ucfirst($slug)),
                    'slug'    => $slug,
                    'is_home' => !empty($page['is_home']) || $slug === '' || $slug === 'home',
                    'blocks'  => $page['blocks'] ?? [],
                ];
            }
        }
        if (empty($pages)) {
            $pages['home'] = ['title' => 'صفحه اصلی', 'slug' => 'home', 'is_home' => true, 'blocks' => []];
        }

        // ─── Virtual pages for header/footer block editing ───────────────────
        $virtualPages = [
            '__header__' => [
                'title'      => 'هدر سایت',
                'slug'       => '__header__',
                'is_home'    => false,
                'is_special' => true,
                'blocks'     => $themeData['header_blocks'] ?? [],
            ],
            '__footer__' => [
                'title'      => 'فوتر سایت',
                'slug'       => '__footer__',
                'is_home'    => false,
                'is_special' => true,
                'blocks'     => $themeData['footer_blocks'] ?? [],
            ],
        ];
        $pages = $virtualPages + $pages;

        $byCategory = BlockRegistry::byCategory();
        $catLabels  = BlockRegistry::categoryLabels();
        $catIcons   = BlockRegistry::categoryIcons();
        $registry   = BlockRegistry::all();

        $gs = $themeData['global_styles'] ?? [];
        $globalStyles = [
            'primary_color'    => $gs['primary_color']    ?? $gs['colors']['primary']    ?? '#4f46e5',
            'secondary_color'  => $gs['secondary_color']  ?? $gs['colors']['secondary']  ?? '#7c3aed',
            'background_color' => $gs['background_color'] ?? $gs['colors']['background'] ?? '#f9fafb',
            'font_family'      => $gs['font_family']      ?? $gs['fonts']['body']        ?? 'Vazirmatn',
        ];

        return view('admin.theme-design.canvas-editor', compact(
            'themeData', 'pages', 'byCategory', 'catLabels', 'catIcons', 'registry', 'globalStyles'
        ));
    }

    public function canvasSaveBlocks(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page_key' => 'required|string',
            'blocks'   => 'required|array',
        ]);

        $raw = Setting::get('active_canvas_theme_data');
        if (empty($raw)) {
            return response()->json(['success' => false, 'error' => 'قالب Canvas یافت نشد.'], 404);
        }

        $themeData = json_decode($raw, true);
        if (!is_array($themeData)) {
            return response()->json(['success' => false, 'error' => 'داده قالب معتبر نیست.'], 422);
        }

        $key = $validated['page_key'];

        // ─── Virtual pages: header/footer blocks ─────────────────────────────
        if ($key === '__header__' || $key === '__footer__') {
            $field = $key === '__header__' ? 'header_blocks' : 'footer_blocks';
            $themeData[$field] = $validated['blocks'];
            Setting::set('active_canvas_theme_data', json_encode($themeData, JSON_UNESCAPED_UNICODE), 'theme');
            Cache::forget('canvas_theme_data');
            $this->resolver->clearCache();
            return response()->json(['success' => true, 'block_count' => count($validated['blocks'])]);
        }

        // Normalize pages to slug-keyed (handles both indexed and associative arrays)
        $themeData['pages'] = $this->normalizePagesKeyed($themeData['pages'] ?? []);

        if (!isset($themeData['pages'][$key])) {
            return response()->json(['success' => false, 'error' => 'صفحه «' . $key . '» یافت نشد.'], 404);
        }

        $themeData['pages'][$key]['blocks'] = $validated['blocks'];

        Setting::set('active_canvas_theme_data', json_encode($themeData, JSON_UNESCAPED_UNICODE), 'theme');
        Cache::forget('canvas_theme_data');
        $this->resolver->clearCache();

        return response()->json(['success' => true, 'block_count' => count($validated['blocks'])]);
    }

    public function canvasAddPage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:128',
            'slug'  => 'nullable|string|max:128|regex:/^[a-z0-9-]*$/',
        ]);

        $raw = Setting::get('active_canvas_theme_data');
        if (empty($raw)) {
            return response()->json(['success' => false, 'error' => 'قالب Canvas یافت نشد.'], 404);
        }

        $themeData = json_decode($raw, true);
        if (!is_array($themeData)) {
            return response()->json(['success' => false, 'error' => 'داده قالب معتبر نیست.'], 422);
        }

        $themeData['pages'] = $this->normalizePagesKeyed($themeData['pages'] ?? []);

        $title = $validated['title'];
        $slug  = !empty($validated['slug']) ? $validated['slug'] : Str::slug($title, '-');
        if (empty($slug)) $slug = 'page-' . time();

        if (Str::startsWith($slug, '__')) {
            return response()->json(['success' => false, 'error' => 'نام صفحه نامعتبر است.'], 422);
        }

        if (isset($themeData['pages'][$slug])) {
            return response()->json(['success' => false, 'error' => 'صفحه‌ای با این آدرس قبلاً وجود دارد.'], 422);
        }

        $themeData['pages'][$slug] = [
            'title'   => $title,
            'slug'    => $slug,
            'is_home' => false,
            'blocks'  => [],
        ];

        Setting::set('active_canvas_theme_data', json_encode($themeData, JSON_UNESCAPED_UNICODE), 'theme');
        Cache::forget('canvas_theme_data');
        $this->resolver->clearCache();

        return response()->json([
            'success' => true,
            'key'     => $slug,
            'page'    => $themeData['pages'][$slug],
        ]);
    }

    public function canvasDeletePage(string $key): JsonResponse
    {
        if ($key === 'home') {
            return response()->json(['success' => false, 'error' => 'صفحه اصلی قابل حذف نیست.'], 422);
        }

        $raw = Setting::get('active_canvas_theme_data');
        if (empty($raw)) {
            return response()->json(['success' => false, 'error' => 'قالب Canvas یافت نشد.'], 404);
        }

        $themeData = json_decode($raw, true);
        if (!is_array($themeData)) {
            return response()->json(['success' => false, 'error' => 'داده قالب معتبر نیست.'], 422);
        }

        $themeData['pages'] = $this->normalizePagesKeyed($themeData['pages'] ?? []);
        unset($themeData['pages'][$key]);

        Setting::set('active_canvas_theme_data', json_encode($themeData, JSON_UNESCAPED_UNICODE), 'theme');
        Cache::forget('canvas_theme_data');
        $this->resolver->clearCache();

        return response()->json(['success' => true]);
    }

    public function canvasSaveGlobalStyles(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'primary_color'    => 'nullable|string|max:32',
            'secondary_color'  => 'nullable|string|max:32',
            'background_color' => 'nullable|string|max:32',
            'font_family'      => 'nullable|string|max:128',
        ]);

        $raw = Setting::get('active_canvas_theme_data');
        if (empty($raw)) {
            return response()->json(['success' => false, 'error' => 'قالب Canvas یافت نشد.'], 404);
        }

        $themeData = json_decode($raw, true);
        if (!is_array($themeData)) {
            return response()->json(['success' => false, 'error' => 'داده قالب معتبر نیست.'], 422);
        }

        if (!isset($themeData['global_styles'])) {
            $themeData['global_styles'] = [];
        }

        foreach (['primary_color', 'secondary_color', 'background_color', 'font_family'] as $field) {
            if (!empty($validated[$field])) {
                $themeData['global_styles'][$field] = $validated[$field];
            }
        }

        Setting::set('active_canvas_theme_data', json_encode($themeData, JSON_UNESCAPED_UNICODE), 'theme');
        Cache::forget('canvas_theme_data');
        $this->resolver->clearCache();

        return response()->json(['success' => true]);
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

    private function normalizePagesKeyed(array $pages): array
    {
        $keyed = [];
        foreach ($pages as $k => $page) {
            $slug = $page['slug'] ?? (is_string($k) ? $k : null);
            if (empty($slug)) $slug = 'home';
            $keyed[$slug] = array_merge($page, ['slug' => $slug]);
        }
        return $keyed ?: ['home' => ['title' => 'صفحه اصلی', 'slug' => 'home', 'is_home' => true, 'blocks' => []]];
    }

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
