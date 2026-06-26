<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\ThemeSetting;
use App\Models\SiteThemeTemplate;
use App\Services\ThemeCanvas\BlockRegistry;
use App\Services\ThemeCanvas\SiteThemeTemplateService;
use App\Services\ThemeEngine\TenantThemeResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ThemeDesignController extends Controller
{
    public function __construct(
        private TenantThemeResolver $resolver,
        private SiteThemeTemplateService $templateSvc,
    ) {}

    public function index(): View
    {
        $settings              = ThemeSetting::current();
        $buttonStyles          = ThemeSetting::buttonStyles();
        $activeSitePreset      = Setting::get('site_theme_preset_slug') ?? Setting::get('theme_preset_slug', '');
        $activeDashPreset      = Setting::get('dashboard_preset_slug', 'enterprise-blue');
        $presets               = $this->presetList();
        $canvasActive          = !empty(Setting::get('active_canvas_theme_data'));
        $canvasName            = Setting::get('active_canvas_theme_name', '');

        // Custom dashboard colors (for editable custom mode)
        $dashCustomColors = [
            'admin_sidebar_bg1'    => Setting::get('admin_sidebar_bg1',    '#1e293b'),
            'admin_sidebar_bg2'    => Setting::get('admin_sidebar_bg2',    '#0f172a'),
            'admin_sidebar_text'   => Setting::get('admin_sidebar_text',   '#f1f5f9'),
            'admin_sidebar_muted'  => Setting::get('admin_sidebar_muted',  '#94a3b8'),
            'admin_sidebar_accent' => Setting::get('admin_sidebar_accent', '#60a5fa'),
            'admin_sidebar_border' => Setting::get('admin_sidebar_border', '#334155'),
        ];

        return view('admin.theme-design.index', compact(
            'settings', 'buttonStyles',
            'activeSitePreset', 'activeDashPreset',
            'presets', 'canvasActive', 'canvasName',
            'dashCustomColors'
        ));
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
            'themeData'  => $themeData,
            'themeName'  => Setting::get('active_canvas_theme_name', ''),
            'themeSlug'  => Setting::get('active_canvas_theme_slug', ''),
            'themeId'    => Setting::get('active_canvas_theme_id', ''),
            'backupName' => Setting::get('canvas_theme_backup_name', ''),
            'backupAt'   => Setting::get('canvas_theme_backup_at', ''),
            'hasBackup'  => (bool) Setting::get('canvas_theme_backup_data'),
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
        $activeRaw = Setting::get('active_canvas_theme_data');
        if (empty($activeRaw)) {
            return redirect()->route('admin.theme-design.canvas')
                ->with('error', 'ابتدا یک قالب Canvas نصب کنید.');
        }

        // Draft system: load draft if exists, else seed draft from active
        $draftRaw = Setting::get('canvas_draft_data');
        if (empty($draftRaw)) {
            $draftRaw = $activeRaw;
            Setting::set('canvas_draft_data', $activeRaw, 'theme');
        }

        // Ensure preview token exists
        $previewToken = Setting::get('canvas_preview_token');
        if (empty($previewToken)) {
            $previewToken = Str::random(40);
            Setting::set('canvas_preview_token', $previewToken, 'theme');
        }

        $raw = $draftRaw;
        $hasDraft = ($draftRaw !== $activeRaw);

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
            'themeData', 'pages', 'byCategory', 'catLabels', 'catIcons', 'registry', 'globalStyles',
            'hasDraft', 'previewToken'
        ));
    }

    public function canvasSaveBlocks(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page_key' => 'required|string',
            'blocks'   => 'required|array',
        ]);

        $raw = Setting::get('canvas_draft_data') ?: Setting::get('active_canvas_theme_data');
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
            Setting::set('canvas_draft_data', json_encode($themeData, JSON_UNESCAPED_UNICODE), 'theme');
            return response()->json(['success' => true, 'block_count' => count($validated['blocks'])]);
        }

        // Normalize pages to slug-keyed (handles both indexed and associative arrays)
        $themeData['pages'] = $this->normalizePagesKeyed($themeData['pages'] ?? []);

        if (!isset($themeData['pages'][$key])) {
            return response()->json(['success' => false, 'error' => 'صفحه «' . $key . '» یافت نشد.'], 404);
        }

        $themeData['pages'][$key]['blocks'] = $validated['blocks'];

        Setting::set('canvas_draft_data', json_encode($themeData, JSON_UNESCAPED_UNICODE), 'theme');

        return response()->json(['success' => true, 'block_count' => count($validated['blocks'])]);
    }

    public function canvasAddPage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:128',
            'slug'  => 'nullable|string|max:128|regex:/^[a-z0-9-]*$/',
        ]);

        $raw = Setting::get('canvas_draft_data') ?: Setting::get('active_canvas_theme_data');
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

        Setting::set('canvas_draft_data', json_encode($themeData, JSON_UNESCAPED_UNICODE), 'theme');

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

        $raw = Setting::get('canvas_draft_data') ?: Setting::get('active_canvas_theme_data');
        if (empty($raw)) {
            return response()->json(['success' => false, 'error' => 'قالب Canvas یافت نشد.'], 404);
        }

        $themeData = json_decode($raw, true);
        if (!is_array($themeData)) {
            return response()->json(['success' => false, 'error' => 'داده قالب معتبر نیست.'], 422);
        }

        $themeData['pages'] = $this->normalizePagesKeyed($themeData['pages'] ?? []);
        unset($themeData['pages'][$key]);

        Setting::set('canvas_draft_data', json_encode($themeData, JSON_UNESCAPED_UNICODE), 'theme');

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

        $raw = Setting::get('canvas_draft_data') ?: Setting::get('active_canvas_theme_data');
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

        Setting::set('canvas_draft_data', json_encode($themeData, JSON_UNESCAPED_UNICODE), 'theme');

        return response()->json(['success' => true]);
    }

    public function canvasPublish(): JsonResponse
    {
        $draft = Setting::get('canvas_draft_data');
        if (empty($draft)) {
            return response()->json(['success' => false, 'error' => 'پیش‌نویسی برای انتشار وجود ندارد.'], 404);
        }

        // GAP-M02 — Revision history: rotate before overwriting active (cap at 5 revisions)
        $currentActive = Setting::get('active_canvas_theme_data');
        if (! empty($currentActive)) {
            $this->rotateRevisions($currentActive);
        }

        Setting::set('active_canvas_theme_data', $draft, 'theme');
        Setting::set('canvas_draft_data', '', 'theme');

        Cache::forget('canvas_theme_data');
        $this->resolver->clearCache();

        return response()->json(['success' => true, 'message' => 'قالب با موفقیت منتشر شد.']);
    }

    /**
     * GAP-M02 — Rotate canvas revision history (keep last 5 published versions).
     */
    private function rotateRevisions(string $currentData): void
    {
        // Shift revisions down: 5→drop, 4→5, 3→4, 2→3, 1→2
        for ($i = 5; $i >= 2; $i--) {
            $older = Setting::get("canvas_revision_{$i}");
            if ($i === 5) {
                // Slot 5 drops off
                if (! empty($older)) {
                    Setting::where('key', "canvas_revision_{$i}")->delete();
                }
                continue;
            }
            $newer = Setting::get('canvas_revision_' . ($i - 1));
            if (! empty($newer)) {
                Setting::set("canvas_revision_{$i}", $newer, 'theme');
            }
        }
        // Slot 1 = just-replaced active
        Setting::set('canvas_revision_1', $currentData, 'theme');
        Setting::set('canvas_revision_1_at', now()->toDateTimeString(), 'theme');
    }

    public function canvasDiscardDraft(): RedirectResponse
    {
        Setting::set('canvas_draft_data', '', 'theme');

        return redirect()->route('admin.theme-design.canvas-edit')
            ->with('success', 'تغییرات پیش‌نویس رد شد.');
    }

    /**
     * TB3-02: Live iframe canvas endpoint.
     * Renders draft page blocks into a standalone HTML page for the editor iframe.
     */
    public function canvasLivePreview(Request $request)
    {
        $pageKey = $request->input('page', 'home');

        $raw = Setting::get('canvas_draft_data') ?: Setting::get('active_canvas_theme_data');
        if (empty($raw)) {
            return response('<p>قالب Canvas یافت نشد</p>', 404);
        }

        $themeData = json_decode($raw, true);
        if (!is_array($themeData)) {
            return response('<p>داده قالب معتبر نیست</p>', 422);
        }

        // Resolve blocks for the requested page key
        if ($pageKey === '__header__') {
            $blocks = $themeData['header_blocks'] ?? [];
        } elseif ($pageKey === '__footer__') {
            $blocks = $themeData['footer_blocks'] ?? [];
        } else {
            $pages  = $themeData['pages'] ?? [];
            $page   = $pages[$pageKey] ?? null;
            $blocks = $page['blocks'] ?? [];
        }

        $globalStyles = $themeData['global_styles'] ?? [];

        /** @var \App\Services\ThemeCanvas\ThemeCanvasRenderer $renderer */
        $renderer = app(\App\Services\ThemeCanvas\ThemeCanvasRenderer::class);
        $renderer->setContext(['editor_mode' => true]);

        $renderedBlocks = [];
        foreach ($blocks as $block) {
            $bid = $block['id'] ?? '';
            if ($bid) {
                $renderedBlocks[$bid] = $renderer->renderBlock($block);
            }
        }

        return response()
            ->view('admin.theme-design.canvas-live-preview', compact('blocks', 'renderedBlocks', 'globalStyles', 'pageKey'))
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }

    /**
     * TB3-02: Per-block re-render for live prop updates.
     * POST body: { block: {id, type, props, ...} }
     * Returns: { html: "..." }
     */
    public function canvasRenderBlockAjax(Request $request): JsonResponse
    {
        $blockData = $request->input('block', []);

        if (!is_array($blockData) || empty($blockData['type'])) {
            return response()->json(['html' => '']);
        }

        // TB3-01 compat: normalize block.data → block.props
        if (isset($blockData['data']) && !isset($blockData['props'])) {
            $blockData['props'] = $blockData['data'];
            unset($blockData['data']);
        }

        /** @var \App\Services\ThemeCanvas\ThemeCanvasRenderer $renderer */
        $renderer = app(\App\Services\ThemeCanvas\ThemeCanvasRenderer::class);
        $renderer->setContext(['editor_mode' => true]);

        return response()->json(['html' => $renderer->renderBlock($blockData)]);
    }

    public function canvasPreviewPublic()
    {
        $token = request('token');
        $savedToken = Setting::get('canvas_preview_token');

        if (empty($token) || $token !== $savedToken) {
            abort(403, 'لینک پیش‌نمایش معتبر نیست.');
        }

        $raw = Setting::get('canvas_draft_data') ?: Setting::get('active_canvas_theme_data');
        if (empty($raw)) {
            abort(404, 'قالب یافت نشد.');
        }

        $themeData = json_decode($raw, true);
        if (!is_array($themeData)) {
            abort(422);
        }

        // Render the home page from draft
        $renderer = app(\App\Services\ThemeCanvas\ThemeCanvasRenderer::class);

        $pages = $themeData['pages'] ?? [];
        $homePage = null;
        foreach ($pages as $page) {
            if (!empty($page['is_home']) || ($page['slug'] ?? '') === 'home') {
                $homePage = $page;
                break;
            }
        }
        if (!$homePage && !empty($pages)) {
            $homePage = array_values($pages)[0];
        }

        $blocks       = $homePage['blocks'] ?? [];
        $headerBlocks = $themeData['header_blocks'] ?? [];
        $footerBlocks = $themeData['footer_blocks'] ?? [];
        $globalStyles = $themeData['global_styles'] ?? [];

        $renderedBlocks = collect($blocks)->map(fn($b) => $renderer->renderBlock($b))->implode('');
        $renderedHeader = collect($headerBlocks)->map(fn($b) => $renderer->renderBlock($b))->implode('');
        $renderedFooter = collect($footerBlocks)->map(fn($b) => $renderer->renderBlock($b))->implode('');

        return view('canvas-preview', compact(
            'renderedBlocks', 'renderedHeader', 'renderedFooter', 'globalStyles', 'themeData'
        ));
    }

    /** فعال‌سازی پریست تم سایت */
    public function activateSitePreset(Request $request): RedirectResponse
    {
        $validSlugs = array_column($this->presetList(), 'slug');
        $slug = $request->validate([
            'preset_slug' => 'required|string|in:' . implode(',', $validSlugs),
        ])['preset_slug'];

        Setting::set('site_theme_preset_slug', $slug, 'theme');
        Setting::set('theme_preset_slug', $slug, 'theme'); // backward-compat
        $this->resolver->clearCache();

        return back()->with('success', 'تم سایت «' . $this->presetName($slug) . '» فعال شد.');
    }

    /** بازنشانی پریست تم سایت به حالت سفارشی (رنگ‌های پریست فعلی را به ThemeSetting منتقل می‌کند) */
    public function resetSitePreset(): RedirectResponse
    {
        $currentSlug = Setting::get('site_theme_preset_slug') ?? Setting::get('theme_preset_slug');

        // If a named preset is currently active, seed ThemeSetting with its colors
        if ($currentSlug && $currentSlug !== 'custom') {
            $presetDef = $this->presetList();
            $found     = collect($presetDef)->firstWhere('slug', $currentSlug);
            if ($found) {
                $ts = ThemeSetting::firstOrNew(['site_id' => null]);
                if (!$ts->exists || !$ts->primary_color || $ts->primary_color === '#FFF') {
                    $ts->primary_color   = $found['primary'];
                    $ts->accent_color    = $found['accent'];
                    $ts->header_bg       = $found['sidebar'];
                    $ts->footer_bg       = $found['sidebar'];
                    $ts->site_id         = null;
                    $ts->save();
                    ThemeSetting::clearCache();
                }
            }
        }

        Setting::set('site_theme_preset_slug', 'custom', 'theme');
        Setting::set('theme_preset_slug', '', 'theme');
        $this->resolver->clearCache();
        return back()->with('success', 'تم سایت به حالت سفارشی تغییر یافت. رنگ‌های پریست قبلی آماده ویرایش هستند.');
    }

    /** بازنشانی رنگ‌های سفارشی به مقادیر پیش‌فرض سیستم */
    public function resetCustomColors(): RedirectResponse
    {
        $defaults = ThemeSetting::defaults();
        $ts = ThemeSetting::firstOrNew(['site_id' => null]);
        $ts->fill($defaults);
        $ts->site_id = null;
        $ts->save();

        ThemeSetting::clearCache();
        $this->resolver->clearCache();

        return back()->with('success', 'رنگ‌های سفارشی به مقادیر پیش‌فرض بازنشانی شدند.');
    }

    /** فعال‌سازی پریست داشبورد */
    public function activateDashboardPreset(Request $request): RedirectResponse
    {
        $validSlugs = array_column($this->presetList(), 'slug');
        $slug = $request->validate([
            'preset_slug' => 'required|string|in:' . implode(',', $validSlugs),
        ])['preset_slug'];

        Setting::set('dashboard_preset_slug', $slug, 'theme');
        $this->resolver->clearCache();

        return back()->with('success', 'تم داشبورد «' . $this->presetName($slug) . '» فعال شد.');
    }

    /** تنظیم داشبورد به حالت سفارشی و ذخیره رنگ‌های دستی */
    public function updateDashboardCustom(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'admin_sidebar_bg1'    => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'admin_sidebar_bg2'    => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'admin_sidebar_text'   => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'admin_sidebar_muted'  => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'admin_sidebar_accent' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'admin_sidebar_border' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        Setting::set('dashboard_preset_slug', 'custom', 'theme');
        foreach ($data as $key => $value) {
            if ($value) {
                Setting::set($key, $value, 'theme');
            }
        }
        $this->resolver->clearCache();

        return back()->with('success', 'رنگ‌های داشبورد ذخیره شد.');
    }

    /** ذخیره تنظیمات سفارشی رنگ / فونت سایت */
    public function update(Request $request): RedirectResponse
    {
        // Normalize color values: trim whitespace, expand 3-digit hex (#FFF → #FFFFFF)
        $colorFields = ['primary_color','secondary_color','accent_color','background_color',
                        'surface_color','text_color','muted_color','border_color','link_color',
                        'header_bg','footer_bg'];
        $normalized = [];
        foreach ($colorFields as $field) {
            $val = trim((string) $request->input($field, ''));
            if ($val === '') {
                $normalized[$field] = null;
            } elseif (preg_match('/^#([0-9A-Fa-f]{3})$/', $val, $m)) {
                $normalized[$field] = '#'.$m[1][0].$m[1][0].$m[1][1].$m[1][1].$m[1][2].$m[1][2];
            } else {
                $normalized[$field] = $val;
            }
        }
        $request->merge($normalized);

        $colorRule = ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/', 'max:7'];

        $data = $request->validate([
            'primary_color'       => $colorRule,
            'secondary_color'     => $colorRule,
            'accent_color'        => $colorRule,
            'background_color'    => $colorRule,
            'surface_color'       => $colorRule,
            'text_color'          => $colorRule,
            'muted_color'         => $colorRule,
            'border_color'        => $colorRule,
            'link_color'          => $colorRule,
            'header_bg'           => $colorRule,
            'footer_bg'           => $colorRule,
            'font_family_heading' => 'nullable|string|max:128',
            'font_family_body'    => 'nullable|string|max:128',
            'base_font_size'      => 'nullable|string|max:24',
            'container_width'     => 'nullable|string|max:32',
            'border_radius'       => 'nullable|string|max:32',
            'button_style'        => 'nullable|in:solid,outline,rounded',
            'section_spacing'     => 'nullable|in:xs,sm,md,lg,xl',
        ]);

        $settings = ThemeSetting::firstOrNew(['site_id' => null]);
        $settings->fill(array_filter($data, fn($v) => $v !== null));
        $settings->site_id = null;
        $settings->save();

        // Ensure site_theme_preset_slug is set to 'custom' so TenantThemeResolver
        // picks up these custom colors (not a named preset)
        Setting::set('site_theme_preset_slug', 'custom', 'theme');
        Setting::set('theme_preset_slug', '', 'theme');

        ThemeSetting::clearCache();
        $this->resolver->clearCache();

        return back()->with('success', 'تنظیمات سفارشی سایت ذخیره و اعمال شد.');
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

    // ── Template Library ─────────────────────────────────────────────────────

    public function canvasTemplates(Request $request): JsonResponse
    {
        $templates = $this->templateSvc->list(
            type:     $request->input('type'),
            category: $request->input('category'),
            term:     $request->input('search'),
        );

        return response()->json(['templates' => $templates]);
    }

    public function canvasSaveAsTemplate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'     => 'required|string|max:200',
            'type'     => 'required|in:' . implode(',', array_keys(SiteThemeTemplate::TYPES)),
            'category' => 'required|in:' . implode(',', array_keys(SiteThemeTemplate::CATEGORIES)),
            'blocks'   => 'required|array',
        ]);

        $template = $this->templateSvc->save($data);

        return response()->json(['success' => true, 'template' => ['id' => $template->id, 'name' => $template->name]]);
    }

    public function canvasInsertTemplate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'template_id' => 'required|integer|exists:site_theme_templates,id',
        ]);

        $blocks = $this->templateSvc->insertBlocks((int) $data['template_id']);

        return response()->json(['success' => true, 'blocks' => $blocks]);
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
            // ── 8 پریست اولیه ─────────────────────────────────────────────
            ['slug' => 'enterprise-blue',  'name' => 'Enterprise Blue',  'primary' => '#3b82f6', 'accent' => '#4f46e5', 'sidebar' => '#1e293b', 'sidebar_end' => '#0f172a', 'desc' => 'آبی کلاسیک شرکتی — پیش‌فرض'],
            ['slug' => 'modern-indigo',    'name' => 'Modern Indigo',    'primary' => '#6366f1', 'accent' => '#6366f1', 'sidebar' => '#312e81', 'sidebar_end' => '#1e1b4b', 'desc' => 'ایندیگو مدرن و جسورانه'],
            ['slug' => 'emerald-pro',      'name' => 'Emerald Pro',      'primary' => '#22c55e', 'accent' => '#059669', 'sidebar' => '#065f46', 'sidebar_end' => '#022c22', 'desc' => 'سبز زمردی برای فین‌تک و سلامت'],
            ['slug' => 'royal-purple',     'name' => 'Royal Purple',     'primary' => '#a855f7', 'accent' => '#7c3aed', 'sidebar' => '#3b0764', 'sidebar_end' => '#1a0036', 'desc' => 'بنفش سلطنتی برای برندهای لوکس'],
            ['slug' => 'midnight-dark',    'name' => 'Midnight Dark',    'primary' => '#60a5fa', 'accent' => '#818cf8', 'sidebar' => '#111827', 'sidebar_end' => '#030712', 'desc' => 'حالت تاریک کامل'],
            ['slug' => 'graphite',         'name' => 'Graphite',         'primary' => '#6b7280', 'accent' => '#374151', 'sidebar' => '#1f2937', 'sidebar_end' => '#111827', 'desc' => 'خاکستری خنثی و زمان‌پایدار'],
            ['slug' => 'ocean-teal',       'name' => 'Ocean Teal',       'primary' => '#14b8a6', 'accent' => '#0d9488', 'sidebar' => '#134e4a', 'sidebar_end' => '#042f2e', 'desc' => 'فیروزه‌ای برای بهداشت و بهره‌وری'],
            ['slug' => 'executive-gold',   'name' => 'Executive Gold',   'primary' => '#f59e0b', 'accent' => '#b45309', 'sidebar' => '#292524', 'sidebar_end' => '#1c1917', 'desc' => 'طلایی گرم برای داشبوردهای اجرایی'],
            // ── 12 پریست جدید ──────────────────────────────────────────────
            ['slug' => 'coral-sunset',     'name' => 'Coral Sunset',     'primary' => '#f97316', 'accent' => '#ea580c', 'sidebar' => '#7c2d12', 'sidebar_end' => '#431407', 'desc' => 'نارنجی مرجانی — انرژی و خلاقیت'],
            ['slug' => 'cherry-blossom',   'name' => 'Cherry Blossom',   'primary' => '#ec4899', 'accent' => '#db2777', 'sidebar' => '#831843', 'sidebar_end' => '#500724', 'desc' => 'صورتی شکوفه برای فشن و زیبایی'],
            ['slug' => 'forest-green',     'name' => 'Forest Green',     'primary' => '#16a34a', 'accent' => '#15803d', 'sidebar' => '#14532d', 'sidebar_end' => '#052e16', 'desc' => 'سبز جنگلی برای محیط‌زیست و کشاورزی'],
            ['slug' => 'arctic-blue',      'name' => 'Arctic Blue',      'primary' => '#38bdf8', 'accent' => '#0284c7', 'sidebar' => '#0c4a6e', 'sidebar_end' => '#082f49', 'desc' => 'آبی قطبی — خنک، شفاف، فناوری'],
            ['slug' => 'lavender-dream',   'name' => 'Lavender Dream',   'primary' => '#8b5cf6', 'accent' => '#7c3aed', 'sidebar' => '#4c1d95', 'sidebar_end' => '#2e1065', 'desc' => 'اسطوخودوس آرام‌بخش برای آموزش و رفاه'],
            ['slug' => 'ruby-red',         'name' => 'Ruby Red',         'primary' => '#dc2626', 'accent' => '#b91c1c', 'sidebar' => '#7f1d1d', 'sidebar_end' => '#450a0a', 'desc' => 'قرمز یاقوتی برای برندهای جسور'],
            ['slug' => 'sage-green',       'name' => 'Sage Green',       'primary' => '#65a30d', 'accent' => '#4d7c0f', 'sidebar' => '#365314', 'sidebar_end' => '#1a2e05', 'desc' => 'سبز مریم‌گلی — طبیعی و آرام'],
            ['slug' => 'terracotta',       'name' => 'Terracotta',       'primary' => '#c2410c', 'accent' => '#9a3412', 'sidebar' => '#7c2d12', 'sidebar_end' => '#431407', 'desc' => 'سفال قرمز — گرم، زمینی، هنری'],
            ['slug' => 'rose-gold',        'name' => 'Rose Gold',        'primary' => '#e11d48', 'accent' => '#be123c', 'sidebar' => '#881337', 'sidebar_end' => '#4c0519', 'desc' => 'رز طلایی برای لوکس و ویژه'],
            ['slug' => 'deep-space',       'name' => 'Deep Space',       'primary' => '#4338ca', 'accent' => '#3730a3', 'sidebar' => '#1e1b4b', 'sidebar_end' => '#0d0b2c', 'desc' => 'فضای عمیق — رمزآلود و فناورانه'],
            ['slug' => 'amber-glow',       'name' => 'Amber Glow',       'primary' => '#d97706', 'accent' => '#b45309', 'sidebar' => '#78350f', 'sidebar_end' => '#451a03', 'desc' => 'کهربایی درخشان برای فروشگاه و رستوران'],
            ['slug' => 'steel-blue',       'name' => 'Steel Blue',       'primary' => '#2563eb', 'accent' => '#1d4ed8', 'sidebar' => '#1e3a5f', 'sidebar_end' => '#0f2035', 'desc' => 'آبی فولادی — اعتماد و حرفه‌ای بودن'],
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
