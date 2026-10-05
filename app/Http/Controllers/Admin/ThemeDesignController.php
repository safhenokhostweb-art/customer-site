<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\ThemeInstanceRevision;
use App\Models\ThemeSetting;
use App\Models\SiteThemeTemplate;
use App\Services\ThemeCanvas\BlockRegistry;
use App\Services\ThemeCanvas\SiteThemeTemplateService;
use App\Services\ThemeCanvas\ThemeInstanceBaseRelease;
use App\Services\ThemeCanvas\ThemeInstanceRevisionService;
use App\Services\ThemeCanvas\ThemeRuntimeDocument;
use App\Services\ThemeCanvas\ThemePresentationCapabilityRegistry;
use App\Services\ThemeEngine\TenantThemeResolver;
use App\Services\BuilderLayoutService;
use App\Services\MotionGraphics\MotionRuntimeManifestReader;
use App\Services\ThemeCanvas\DataBinding\ThemeDataBindingRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;
use SaaSPlatform\ThemeRuntime\Layout\LockedStructureGuard;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ThemeDesignController extends Controller
{
    public function __construct(
        private TenantThemeResolver $resolver,
        private SiteThemeTemplateService $templateSvc,
        private ThemeInstanceRevisionService $revisions,
        private ThemeInstanceBaseRelease $baseRelease,
        private BuilderLayoutService $layoutService,
        private ThemeRuntimeDocument $runtimeDocument,
        private ThemePresentationCapabilityRegistry $presentationCapabilities,
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

        // When a preset is active, override the settings display with that preset's colors
        // so the user can see exactly what each field of the preset is.
        if ($activeSitePreset && $activeSitePreset !== 'custom') {
            $presetColors = $this->presetToSettingsFields($activeSitePreset);
            if (!empty($presetColors)) {
                foreach ($presetColors as $field => $value) {
                    $settings->$field = $value;
                }
            }
        }

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

    /** نگاشت CSS vars یک پریست به فیلدهای ThemeSetting برای نمایش در فرم */
    private function presetToSettingsFields(string $slug): array
    {
        $presets = $this->presetList();
        $found = collect($presets)->firstWhere('slug', $slug);
        if (!$found) {
            return [];
        }

        // Map from TenantThemeResolver's full preset tokens
        $resolverPresets = app(\App\Services\ThemeEngine\TenantThemeResolver::class)->getPresetTokens($slug);
        if (empty($resolverPresets)) {
            // Fallback to simple presetList data
            return [
                'primary_color'   => $found['primary'],
                'accent_color'    => $found['accent'],
                'link_color'      => $found['primary'],
                'header_bg'       => $found['sidebar'],
                'footer_bg'       => $found['sidebar'],
            ];
        }

        return [
            'primary_color'    => $resolverPresets['--ds-primary']              ?? $found['primary'],
            'secondary_color'  => $resolverPresets['--ui-text-secondary']       ?? '#64748b',
            'accent_color'     => $resolverPresets['--ui-indigo-hover']          ?? $found['accent'],
            'background_color' => $resolverPresets['--ui-bg']                   ?? '#f9fafb',
            'surface_color'    => $resolverPresets['--ui-surface']              ?? '#ffffff',
            'text_color'       => $resolverPresets['--ui-text-primary']         ?? '#111827',
            'muted_color'      => $resolverPresets['--ui-text-muted']           ?? '#6b7280',
            'border_color'     => $resolverPresets['--ui-border']               ?? '#e5e7eb',
            'link_color'       => $resolverPresets['--ds-link-color']           ?? $found['primary'],
            'header_bg'        => $resolverPresets['--admin-sidebar-bg1']       ?? $found['sidebar'],
            'footer_bg'        => $resolverPresets['--admin-sidebar-bg1']       ?? $found['sidebar'],
        ];
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
        // Phase 3B: centralized through the ledger service, which also
        // clears the now-dangling active_theme_revision_id pointer instead
        // of leaving it pointed at a revision that is no longer active.
        $this->revisions->deactivate();

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

        // Phase 3D-B: include release provenance ONLY when this instance's
        // own ledger actually proves it (never fabricated). This makes the
        // export self-documenting and lets a later re-import into THIS same
        // installation verify and carry it forward (see canvasImport()) —
        // but the claim is never trusted on its own; canvasImport() only
        // ever accepts it after independently confirming this installation's
        // ledger already recorded the exact same (theme_identifier,
        // source_release_id) pair through the real install pipeline.
        $baseRelease = $this->baseRelease->resolveForCurrentTheme();
        if ($baseRelease->isResolved()) {
            $data['_export_meta']['source_theme_id']      = $baseRelease->sourceThemeId;
            $data['_export_meta']['source_theme_version'] = $baseRelease->sourceThemeVersion;
            $data['_export_meta']['source_release_id']    = $baseRelease->sourceReleaseId;
        }

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

        // Phase 3B: import used to write active_canvas_theme_data directly,
        // bypassing the ledger entirely — no revision was ever created for
        // an import, and active_theme_revision_id was left stale (still
        // pointing at whatever was active before). Route through the same
        // single write path install()/publish() use, so an import is fully
        // recoverable like any other lineage-changing action.
        $wasActive = (bool) Setting::get('active_canvas_theme_data');

        // A meta-provided theme_id is only ever a real platform id (numeric)
        // when the file was originally exported from this same system with
        // that metadata intact — the synthesized 'imported-<timestamp>'
        // fallback is a local display label, not a real source theme id, so
        // it must never be fabricated into the ledger's source_theme_id.
        $sourceThemeId = isset($meta['theme_id']) && is_numeric($meta['theme_id'])
            ? (int) $meta['theme_id']
            : null;

        // Phase 3D-B: an uploaded file's claimed release provenance
        // (_export_meta.source_release_id/source_theme_version) is never
        // trusted at face value — it can be hand-edited, or genuinely
        // exported from a completely different installation that happens
        // to reuse the same theme_slug. It is only accepted when THIS
        // installation's own immutable ledger independently already proves
        // the exact same (theme_identifier, source_release_id) pair was
        // installed here through the real download pipeline — never a
        // network round-trip to saas-platform, just a local, forgery-proof
        // cross-check against history nothing outside record() can write.
        // Anything else stays null: the imported lineage is NON_RECONCILABLE
        // rather than trusting supplied metadata (Phase 3D-B security
        // requirement — see ThemeInstanceBaseRelease).
        $claimedReleaseId = isset($meta['source_release_id']) && is_numeric($meta['source_release_id'])
            ? (int) $meta['source_release_id']
            : null;
        $verifiedReleaseId = null;
        $verifiedThemeVersion = null;
        if ($claimedReleaseId !== null) {
            $verified = ThemeInstanceRevision::where('theme_identifier', $themeSlug)
                ->where('source_release_id', $claimedReleaseId)
                ->exists();
            if ($verified) {
                $verifiedReleaseId = $claimedReleaseId;
                $verifiedThemeVersion = isset($meta['source_theme_version']) && is_string($meta['source_theme_version'])
                    ? $meta['source_theme_version']
                    : null;
            }
        }

        $this->revisions->record($data, $wasActive ? ThemeInstanceRevision::TYPE_UPDATE : ThemeInstanceRevision::TYPE_INSTALL, [
            'theme_identifier'     => $themeSlug,
            'theme_name'           => $themeName,
            'active_theme_id'      => $themeId,
            'source_theme_id'      => $sourceThemeId,
            'source_theme_version' => $verifiedThemeVersion,
            'source_release_id'    => $verifiedReleaseId,
            'metadata'             => ['theme_name' => $themeName, 'imported' => true],
        ]);

        // A draft from whatever theme was previously active no longer
        // applies to the newly imported lineage — starting the editor on a
        // foreign, stale draft would silently mask the import.
        Setting::where('key', 'canvas_draft_data')->delete();

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
        // TASK 3/3 Phase 6.5C (BLD-29) — must read from the SAME variant
        // BuilderLayoutService::getHeaderBlocks()/getFooterBlocks() will
        // actually render live, never the separate flat header_blocks/
        // footer_blocks field once variants exist (see that method's own
        // docblock for why those are two disconnected arrays).
        $virtualPages = [
            '__header__' => [
                'title'      => 'هدر سایت',
                'slug'       => '__header__',
                'is_home'    => false,
                'is_special' => true,
                'blocks'     => $this->activeVariantBlocks($themeData, 'header'),
            ],
            '__footer__' => [
                'title'      => 'فوتر سایت',
                'slug'       => '__footer__',
                'is_home'    => false,
                'is_special' => true,
                'blocks'     => $this->activeVariantBlocks($themeData, 'footer'),
            ],
        ];
        $pages = $pages + $virtualPages;

        $byCategory = BlockRegistry::byCategory();
        $catLabels  = BlockRegistry::categoryLabels();
        $catIcons   = BlockRegistry::categoryIcons();
        $registry   = BlockRegistry::all();
        $modulePresentations = $this->presentationCapabilities->availableFor($themeData);

        $gs = $themeData['global_styles'] ?? [];
        $globalStyles = [
            'primary_color'    => $gs['primary_color']    ?? $gs['colors']['primary']    ?? '#4f46e5',
            'secondary_color'  => $gs['secondary_color']  ?? $gs['colors']['secondary']  ?? '#7c3aed',
            'background_color' => $gs['background_color'] ?? $gs['colors']['background'] ?? '#f9fafb',
            'font_family'      => $gs['font_family']      ?? $gs['fonts']['body']        ?? 'Vazirmatn',
        ];

        return view('admin.theme-design.canvas-editor', compact(
            'themeData', 'pages', 'byCategory', 'catLabels', 'catIcons', 'registry', 'globalStyles',
            'hasDraft', 'previewToken', 'modulePresentations'
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
        // TASK 3/3 Phase 6.5C (BLD-29) — must write to the SAME variant the
        // render path (BuilderLayoutService::getHeaderBlocks()/
        // getFooterBlocks()) actually resolves as live, not always the flat
        // header_blocks/footer_blocks field — see canvasEdit()'s matching
        // comment and BuilderLayoutService::resolveActiveHeaderVariantKey()'s
        // own docblock for the defect this closes.
        if ($key === '__header__' || $key === '__footer__') {
            $region = $key === '__header__' ? 'header' : 'footer';
            $field  = $region === 'header' ? 'header_blocks' : 'footer_blocks';
            $variantsField = $region === 'header' ? 'header_variants' : 'footer_variants';

            $current = $this->activeVariantBlocks($themeData, $region);
            // Phase 3F-C — same fail-closed ext:* trust boundary the
            // Production Builder already enforces (3E-H-C), reused via the
            // extracted ExtensionBlockReconciler rather than reimplemented:
            // an incoming block whose type is ext:*-shaped but not
            // currently registered is never trusted as submitted; it is
            // reconciled against this same field's own previously-persisted
            // content instead of accepted verbatim.
            $reconciled = \App\Builder\Extension\ExtensionBlockReconciler::reconcile($validated['blocks'], $current);

            $structure = LockedStructureGuard::validate(
                $current,
                $reconciled,
                static fn (string $type): array => BlockRegistry::all()[$type]['editor'] ?? []
            );
            if (!$structure['ok']) {
                return response()->json([
                    'success' => false,
                    'error' => 'ساختار محافظت‌شده قابل حذف، جابه‌جایی یا تکثیر نیست.',
                    'structure_errors' => $structure['errors'],
                ], 422);
            }

            $variantKey = $region === 'header'
                ? $this->layoutService->resolveActiveHeaderVariantKey($themeData)
                : $this->layoutService->resolveActiveFooterVariantKey($themeData);

            if ($variantKey !== null && !empty($themeData[$variantsField])) {
                foreach ($themeData[$variantsField] as $i => $variant) {
                    if (($variant['key'] ?? null) === $variantKey) {
                        $themeData[$variantsField][$i]['blocks'] = $reconciled;
                        break;
                    }
                }
            } else {
                $themeData[$field] = $reconciled;
            }

            Setting::set('canvas_draft_data', json_encode($themeData, JSON_UNESCAPED_UNICODE), 'theme');
            return response()->json(['success' => true, 'block_count' => count($reconciled)]);
        }

        // Normalize pages to slug-keyed (handles both indexed and associative arrays)
        $themeData['pages'] = $this->normalizePagesKeyed($themeData['pages'] ?? []);

        if (!isset($themeData['pages'][$key])) {
            return response()->json(['success' => false, 'error' => 'صفحه «' . $key . '» یافت نشد.'], 404);
        }

        // Phase 3F-C — same reconciliation gate for regular pages.
        $current = $themeData['pages'][$key]['blocks'] ?? [];
        $reconciled = \App\Builder\Extension\ExtensionBlockReconciler::reconcile(
            $validated['blocks'],
            $current
        );

        $structure = LockedStructureGuard::validate(
            $current,
            $reconciled,
            static fn (string $type): array => BlockRegistry::all()[$type]['editor'] ?? []
        );
        if (!$structure['ok']) {
            return response()->json([
                'success' => false,
                'error' => 'ساختار محافظت‌شده قابل حذف، جابه‌جایی یا تکثیر نیست.',
                'structure_errors' => $structure['errors'],
            ], 422);
        }
        $themeData['pages'][$key]['blocks'] = $reconciled;

        Setting::set('canvas_draft_data', json_encode($themeData, JSON_UNESCAPED_UNICODE), 'theme');

        return response()->json(['success' => true, 'block_count' => count($themeData['pages'][$key]['blocks'])]);
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

    /**
     * TASK 3/3 Phase 6.5C (BLD-29) — the blocks of whichever header/footer
     * variant BuilderLayoutService actually resolves as live for this
     * theme data (its designated default, or the site's explicit
     * selection), falling back to the flat header_blocks/footer_blocks
     * field only for a Theme with no variants at all — the exact same
     * fallback getHeaderVariants()/getFooterVariants() themselves define.
     */
    private function activeVariantBlocks(array $themeData, string $region): array
    {
        $variantKey = $region === 'header'
            ? $this->layoutService->resolveActiveHeaderVariantKey($themeData)
            : $this->layoutService->resolveActiveFooterVariantKey($themeData);

        if ($variantKey === null) {
            return $themeData[$region === 'header' ? 'header_blocks' : 'footer_blocks'] ?? [];
        }

        $variants = $region === 'header'
            ? $this->layoutService->getHeaderVariants($themeData)
            : $this->layoutService->getFooterVariants($themeData);

        foreach ($variants as $variant) {
            if (($variant['key'] ?? null) === $variantKey) {
                return $variant['blocks'] ?? [];
            }
        }

        return [];
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

        // GAP-M02 — legacy rotating revision history: rotate before overwriting
        // active (cap at 5 revisions). Kept for the existing revisions UI.
        $currentActive = Setting::get('active_canvas_theme_data');
        if (! empty($currentActive)) {
            $this->rotateRevisions($currentActive);
        }

        // Permanent version history: creates an immutable revision and sets
        // active_canvas_theme_data + active_theme_revision_id. A publish
        // never changes which release the instance is based on, so
        // provenance is carried forward from whatever is currently active
        // (Phase 3D-B) — resolves to null if it was already unresolvable.
        $decoded = json_decode($draft, true) ?: [];
        try {
            $this->revisions->record($decoded, ThemeInstanceRevision::TYPE_PUBLISH, [
                'resolve_provenance_from' => (int) Setting::get('active_theme_revision_id'),
            ]);
        } catch (ConflictHttpException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 409);
        }

        // TASK 3/3 Phase 6.5C (BLD-24) — Appearance convergence. Canvas
        // global_styles is the ONE customer-facing appearance authority
        // once a Canvas theme is active (see index.blade.php: the legacy
        // color-picker form is display-only in that state). ThemeSetting
        // cannot be deleted outright — it remains genuinely load-bearing
        // for (a) any site with no Canvas theme installed at all
        // (themes/default/layout.blade.php reads it directly) and (b) the
        // shared core-ui `--ui-*`/`--ds-*` tokens a few components (e.g.
        // payment-connection-warning) read even on Canvas pages — so it is
        // kept in automatic sync here as a genuine read-compatibility
        // layer, not a second thing the customer edits. This is the one
        // and only place that writes ThemeSetting FROM a Canvas publish.
        $this->syncThemeSettingFromCanvasGlobalStyles($decoded['global_styles'] ?? []);

        Setting::set('canvas_draft_data', '', 'theme');

        Cache::forget('canvas_theme_data');
        $this->resolver->clearCache();

        return response()->json(['success' => true, 'message' => 'قالب با موفقیت منتشر شد.']);
    }

    /**
     * TASK 3/3 Phase 6.5C (BLD-24) — one-directional sync (Canvas -> legacy),
     * never the reverse: Canvas is the customer-facing authority, ThemeSetting
     * is a read-compatibility target for the classic theme path and shared
     * core-ui tokens. Only overwrites the fields Canvas actually authors
     * (primary/secondary/background/font) — never touches accent/surface/
     * text/muted/border/link/header_bg/footer_bg/container_width/
     * border_radius/button_style/section_spacing, which Canvas has no
     * concept of and this must not silently clobber.
     */
    private function syncThemeSettingFromCanvasGlobalStyles(array $globalStyles): void
    {
        if (empty($globalStyles)) {
            return;
        }

        $fields = [];
        if (!empty($globalStyles['primary_color'])) {
            $fields['primary_color'] = $globalStyles['primary_color'];
        }
        if (!empty($globalStyles['secondary_color'])) {
            $fields['secondary_color'] = $globalStyles['secondary_color'];
        }
        if (!empty($globalStyles['background_color'])) {
            $fields['background_color'] = $globalStyles['background_color'];
        }
        if (!empty($globalStyles['font_family'])) {
            $fields['font_family_heading'] = $globalStyles['font_family'];
            $fields['font_family_body'] = $globalStyles['font_family'];
        }

        if (empty($fields)) {
            return;
        }

        ThemeSetting::query()->firstOrNew(['site_id' => null])->fill($fields)->save();
        Setting::set('site_theme_preset_slug', 'custom', 'theme');
        ThemeSetting::clearCache();
    }

    /**
     * GAP-M02 — Rotate canvas revision history (keep last 5 published versions).
     */
    private function rotateRevisions(string $currentData): void
    {
        // Shift revisions down: 5→drop, 4→5, 3→4, 2→3, 1→2 (data + timestamp together)
        for ($i = 5; $i >= 2; $i--) {
            if ($i === 5) {
                // Slot 5 drops off
                if (! empty(Setting::get("canvas_revision_{$i}"))) {
                    Setting::where('key', "canvas_revision_{$i}")->delete();
                    Setting::where('key', "canvas_revision_{$i}_at")->delete();
                }
                continue;
            }
            $newer = Setting::get('canvas_revision_' . ($i - 1));
            if (! empty($newer)) {
                Setting::set("canvas_revision_{$i}", $newer, 'theme');
                $newerAt = Setting::get('canvas_revision_' . ($i - 1) . '_at');
                if (! empty($newerAt)) {
                    Setting::set("canvas_revision_{$i}_at", $newerAt, 'theme');
                }
            }
        }
        // Slot 1 = just-replaced active
        Setting::set('canvas_revision_1', $currentData, 'theme');
        Setting::set('canvas_revision_1_at', now()->toDateTimeString(), 'theme');
    }

    /**
     * Theme Builder Motion Picker (customer-site): the visual asset list
     * for the module:motion block's picker modal. Reuses the exact same
     * manifest the public runtime already fetches/validates/caches
     * (MotionRuntimeManifestReader) — no parallel query, no way to see
     * anything beyond this site's own already-authorized package. Every
     * field returned here already passed MotionRuntimeManifestReader's own
     * forbidden-field/shape validation before reaching this method.
     */
    public function canvasMotionAssets(MotionRuntimeManifestReader $reader): JsonResponse
    {
        $manifest = $reader->read();
        $assets = collect($manifest['assets'] ?? [])->map(fn (array $a): array => [
            'asset_id' => $a['asset_id'] ?? null,
            'name'     => $a['name'] ?? null,
            'category' => $a['category'] ?? null,
            'type'     => $a['asset_type'] ?? null,
            'thumbnail'=> $a['full_reference'] ?? null,
        ])->filter(fn (array $a) => !empty($a['asset_id']))->values();

        return response()->json(['assets' => $assets]);
    }

    /**
     * Phase 4B.5 — generic, tenant-safe entity options for a binding
     * source's manual selection control. Only allowlisted data sources
     * (per ThemeDataBindingRegistry) are ever served; nothing is writable,
     * and every option carries a human-readable label so the editor never
     * needs raw numeric IDs. Published/unpublished rules stay inside the
     * existing domain models — exactly what the public resolver uses.
     */
    public function canvasBindingEntities(string $source): JsonResponse
    {
        $allowed = array_keys(ThemeDataBindingRegistry::capabilities());
        if (!in_array($source, $allowed, true)) {
            return response()->json(['error' => 'Unknown data source.'], 404);
        }

        $entities = $source === 'products'
            ? \App\Models\Product::query()->published()->orderBy('title')->limit(200)->get(['id', 'title'])
            : \App\Models\ProductCategory::query()->orderBy('order')->orderBy('id')->limit(200)->get(['id', 'name']);

        return response()->json([
            'entities' => $entities->map(fn ($e): array => [
                'id' => (int) $e->id,
                'label' => (string) ($e->title ?? $e->name),
            ])->all(),
        ]);
    }

    /**
     * List available canvas revisions (customer-facing restore UI).
     */
    public function canvasRevisions(): JsonResponse
    {
        $revisions = [];
        for ($i = 1; $i <= 5; $i++) {
            $data = Setting::get("canvas_revision_{$i}");
            if (empty($data)) {
                continue;
            }
            $at = Setting::get("canvas_revision_{$i}_at");
            $revisions[] = [
                'slot'     => $i,
                'time_ago' => $at ? \App\Helpers\Jalali::diffForHumansFa(\Carbon\Carbon::parse($at)) : null,
                'date'     => $at ? \App\Helpers\Jalali::formatFa(\Carbon\Carbon::parse($at), 'Y/m/d H:i') : null,
            ];
        }

        return response()->json(['success' => true, 'revisions' => $revisions]);
    }

    /**
     * Restore a canvas revision. The currently active theme is rotated into
     * the revision history first, so restoring never loses what was live —
     * mirrors the existing page-builder revision restore behavior.
     */
    public function canvasRestoreRevision(int $slot): JsonResponse
    {
        if ($slot < 1 || $slot > 5) {
            return response()->json(['success' => false, 'error' => 'شماره نسخه نامعتبر است.'], 422);
        }

        $data = Setting::get("canvas_revision_{$slot}");
        if (empty($data)) {
            return response()->json(['success' => false, 'error' => 'این نسخه دیگر موجود نیست.'], 404);
        }

        $currentActive = Setting::get('active_canvas_theme_data');
        if (! empty($currentActive)) {
            $this->rotateRevisions($currentActive);
        }

        // Permanent version history: this legacy slot-restore action is also
        // captured as a durable rollback_snapshot revision.
        $decoded = json_decode($data, true);
        if (is_array($decoded)) {
            $this->revisions->record($decoded, ThemeInstanceRevision::TYPE_ROLLBACK_SNAPSHOT, [
                'metadata' => ['legacy_revision_slot' => $slot],
            ]);
        }

        Setting::set('canvas_draft_data', '', 'theme');

        Cache::forget('canvas_theme_data');
        $this->resolver->clearCache();

        return response()->json(['success' => true, 'message' => 'نسخه انتخاب‌شده با موفقیت بازیابی شد.']);
    }

    /**
     * Permanent version history (theme_instance_revisions) — customer-facing
     * list, newest first. Unlike canvasRevisions() (5-slot rotating window),
     * this never drops entries: every install/update/publish/rollback for
     * the currently active theme lineage stays listed indefinitely.
     */
    public function canvasInstanceRevisions(): JsonResponse
    {
        // listForCurrentTheme() triggers the backfill (if needed) first, so
        // active_theme_revision_id must be read only after it returns.
        $list = $this->revisions->listForCurrentTheme();
        $activeId = (int) Setting::get('active_theme_revision_id');

        $revisions = $list->map(fn (ThemeInstanceRevision $r) => [
            'id'              => $r->id,
            'revision_number' => $r->revision_number,
            'type'            => $r->type,
            'theme_name'      => $r->metadata['theme_name'] ?? null,
            'is_active'       => $r->id === $activeId,
            'time_ago'        => $r->created_at ? \App\Helpers\Jalali::diffForHumansFa($r->created_at) : null,
            'date'            => $r->created_at ? \App\Helpers\Jalali::formatFa($r->created_at, 'Y/m/d H:i') : null,
        ]);

        return response()->json(['success' => true, 'revisions' => $revisions]);
    }

    /**
     * Restore any prior permanent revision by ID. Never deletes or rewinds
     * history — creates a new rollback_snapshot revision carrying the
     * target's data forward and activates it, so a second (or Nth) rollback
     * afterward always still has something to work from.
     */
    public function canvasRollbackToRevision(int $revision): JsonResponse
    {
        try {
            $restored = $this->revisions->rollbackTo($revision);
        } catch (NotFoundHttpException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 404);
        }

        Cache::forget('canvas_theme_data');
        $this->resolver->clearCache();

        return response()->json([
            'success' => true,
            'message' => 'نسخه انتخاب‌شده با موفقیت بازیابی شد.',
            'revision_id' => $restored->id,
            'revision_number' => $restored->revision_number,
        ]);
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
        // TASK 3/3 Phase 6.5C (BLD-29) — same fix as canvasEdit()/canvasSaveBlocks().
        $editorRegion = null;
        $pageTitle = $pageKey;
        if ($pageKey === '__header__') {
            $blocks = $this->activeVariantBlocks($themeData, 'header');
            $editorRegion = 'header';
            $pageTitle = 'Header';
        } elseif ($pageKey === '__footer__') {
            $blocks = $this->activeVariantBlocks($themeData, 'footer');
            $editorRegion = 'footer';
            $pageTitle = 'Footer';
        } else {
            $pages  = $themeData['pages'] ?? [];
            $page   = $pages[$pageKey] ?? null;
            $blocks = $page['blocks'] ?? [];
            $pageTitle = $page['title'] ?? $pageKey;
        }

        $viewData = $this->runtimeDocument->build(
            $themeData,
            $blocks,
            $pageTitle,
            $pageKey,
            editorPreview: true,
            editorRegion: $editorRegion,
        ) + ['themeName' => Setting::get('active_canvas_theme_name', '')];

        return response()
            ->view('admin.theme-design.canvas-live-preview', $viewData)
            ->header('Content-Type', 'text/html; charset=UTF-8');

        /*
        // TASK 3/3 Phase 6.5E (BLD-32) — same bulk media preload as the
        // public render boundary; this endpoint renders through the same
        // CustomerMediaResolver and is hit on every editor keystroke/tab
        // switch, so an unbounded per-block query cost here is at least as
        // real a problem as on public render.
        $renderer->preloadMediaFor($blocks);

        $renderedBlocks = [];
        foreach ($blocks as $block) {
            $bid = $block['id'] ?? '';
            if ($bid) {
                $renderedBlocks[$bid] = $renderer->renderBlock($block);
            }
        }

        // Phase 3F-C — HasAssets bridge for the editor's own live iframe,
        // same pattern as the public render bridge (ADR-041 §2): assets
        // accumulate on the renderer instance across every block rendered
        // above, deduplicated, request-scoped (renderer resolved fresh per
        // request, never a singleton).
        $extensionAssets = $renderer->getCollectedAssets();

        return response()
            ->view('admin.theme-design.canvas-live-preview', compact('blocks', 'renderedBlocks', 'globalStyles', 'pageKey', 'extensionAssets'))
            ->header('Content-Type', 'text/html; charset=UTF-8');
        */
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
        $bindings = app(\App\Services\ThemeCanvas\DataBinding\ThemeDataBindingRegistry::class)
            ->resolveBlockLists([$blockData]);
        $renderer->setContext(['editor_mode' => true, 'theme_bindings' => $bindings]);

        return response()->json(['html' => $renderer->renderBlock($blockData)]);
    }

    public function canvasPreviewPublic(Request $request)
    {
        $token = $request->query('token');
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

        $pageKey = (string) $request->query('page', 'home');
        $pages = is_array($themeData['pages'] ?? null) ? $themeData['pages'] : [];
        $page = $pages[$pageKey] ?? null;
        if (!is_array($page)) {
            foreach ($pages as $candidate) {
                if (is_array($candidate) && (!empty($candidate['is_home']) || ($candidate['slug'] ?? '') === 'home')) {
                    $page = $candidate;
                    $pageKey = (string) ($candidate['slug'] ?? 'home');
                    break;
                }
            }
        }
        if (!is_array($page)) {
            $firstPage = reset($pages);
            $page = is_array($firstPage) ? $firstPage : ['title' => 'Preview', 'blocks' => []];
        }

        $viewData = $this->runtimeDocument
            ->forMode(ThemeRuntimeDocument::MODE_PUBLIC)
            ->build(
            $themeData,
            is_array($page['blocks'] ?? null) ? $page['blocks'] : [],
            (string) ($page['title'] ?? 'Preview'),
            $pageKey,
            );
        $viewData['showBuilderEditBar'] = false;

        return response()
            ->view('canvas-preview', $viewData)
            ->header('Content-Type', 'text/html; charset=UTF-8');

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

        // Seed ThemeSetting from current preset so custom mode starts from preset's colors
        if ($currentSlug && $currentSlug !== 'custom') {
            $presetDef = $this->presetList();
            $found     = collect($presetDef)->firstWhere('slug', $currentSlug);
            if ($found) {
                $ts = ThemeSetting::firstOrNew(['site_id' => null]);
                $ts->primary_color    = $found['primary'];
                $ts->accent_color     = $found['accent'];
                $ts->link_color       = $found['primary'];
                $ts->secondary_color  = '#64748b';
                $ts->background_color = '#f9fafb';
                $ts->surface_color    = '#ffffff';
                $ts->text_color       = '#111827';
                $ts->muted_color      = '#6b7280';
                $ts->border_color     = '#e5e7eb';
                $ts->header_bg        = $found['sidebar'];
                $ts->footer_bg        = $found['sidebar'];
                $ts->site_id          = null;
                $ts->save();
                ThemeSetting::clearCache();
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

    /** بازگردانی یک پریست سایت به رنگ‌های کارخانه‌ای خودش */
    public function resetSitePresetToDefault(string $slug): RedirectResponse
    {
        $presetDef = $this->presetList();
        $found = collect($presetDef)->firstWhere('slug', $slug);

        if (!$found) {
            return back()->with('error', 'پریست مورد نظر یافت نشد.');
        }

        Setting::set('site_theme_preset_slug', $slug, 'theme');
        Setting::set('theme_preset_slug', '', 'theme');
        ThemeSetting::clearCache();
        $this->resolver->clearCache();

        return back()->with('success', "تم «{$found['name']}» به رنگ‌های پیش‌فرض بازگردانی شد.");
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
