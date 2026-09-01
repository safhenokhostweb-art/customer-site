<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\ThemeInstanceRevision;
use App\Services\PlatformApiService;
use App\Services\ThemeCanvas\ThemeInstanceBaseRelease;
use App\Services\ThemeCanvas\ThemeInstanceRevisionService;
use App\Services\ThemeCanvas\ThemeUpgradePreviewService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ThemeMarketplaceController extends Controller
{
    public function __construct(
        protected PlatformApiService $platform,
        protected ThemeInstanceRevisionService $revisions,
        protected ThemeInstanceBaseRelease $baseRelease,
    ) {}

    public function index(Request $request)
    {
        if (!$this->platform->isConfigured()) {
            return view('admin.theme-marketplace.index', [
                'themes' => [],
                'error'  => 'اتصال به پلتفرم پیکربندی نشده است.',
            ]);
        }

        $themes    = Cache::remember('theme_marketplace_list', 300, fn () => $this->platform->marketplaceThemes());
        $purchased = session('purchased_theme', false);
        $failed    = $request->query('failed', false);
        $msg       = $request->query('msg', '');

        session()->forget('purchased_theme');

        return view('admin.theme-marketplace.index', compact('themes', 'purchased', 'failed', 'msg'));
    }

    public function show(Request $request, int $themeId)
    {
        if (!$this->platform->isConfigured()) {
            return redirect()->route('admin.theme-marketplace.index')
                ->with('error', 'اتصال به پلتفرم پیکربندی نشده است.');
        }

        $theme = Cache::remember("theme_marketplace_detail_{$themeId}", 300, fn () => $this->platform->marketplaceTheme($themeId));

        if (!$theme) {
            return redirect()->route('admin.theme-marketplace.index')
                ->with('error', 'قالب یافت نشد.');
        }

        $hasActiveTheme  = (bool) Setting::get('active_canvas_theme_data');
        $hasUnsavedDraft = (bool) Setting::get('canvas_draft_data');

        // Phase 3D-F: offer the safe reconciled-upgrade path only when this
        // is genuinely an upgrade of the SAME already-installed lineage
        // with a provable BASE — never for a fresh install, and never for
        // switching to an unrelated theme (that stays the existing
        // explicit-confirmation wholesale-replace flow below).
        $baseResolution = $hasActiveTheme ? $this->baseRelease->resolveForCurrentTheme() : null;
        $canPreviewUpgrade = $hasActiveTheme
            && $baseResolution?->isResolved()
            && ($theme['slug'] ?? null) === $baseResolution->themeIdentifier;

        return view('admin.theme-marketplace.show', compact('theme', 'hasActiveTheme', 'hasUnsavedDraft', 'canPreviewUpgrade'));
    }

    public function purchase(Request $request, int $themeId)
    {
        if (!$this->platform->isConfigured()) {
            return back()->with('error', 'سرویس مارکت‌پلیس در دسترس نیست.');
        }

        $redirectBack = route('admin.theme-marketplace.my-licenses') . '?purchased=1';

        $result = $this->platform->marketplaceInitiatePurchase($themeId, $redirectBack);

        if (empty($result['success'])) {
            $err = match ($result['error'] ?? '') {
                'already_purchased' => 'این قالب قبلاً خریداری شده است.',
                'theme_not_available' => 'قالب در دسترس نیست.',
                default => 'خطا در راه‌اندازی پرداخت. لطفاً دوباره تلاش کنید.',
            };
            return back()->with('error', $err);
        }

        if (!empty($result['free'])) {
            Cache::forget('theme_marketplace_list');
            return redirect()->route('admin.theme-marketplace.my-licenses')
                ->with('success', 'قالب رایگان با موفقیت دریافت شد!');
        }

        return redirect()->away($result['redirect_url']);
    }

    public function myLicenses(Request $request)
    {
        if (!$this->platform->isConfigured()) {
            return view('admin.theme-marketplace.my-licenses', [
                'licenses' => [],
                'error'    => 'اتصال به پلتفرم پیکربندی نشده است.',
            ]);
        }

        $licenses  = $this->platform->marketplaceMyLicenses();
        $purchased = $request->query('purchased') == '1';
        $msg       = $request->query('msg', '');

        $hasActiveTheme  = (bool) Setting::get('active_canvas_theme_data');
        $hasUnsavedDraft = (bool) Setting::get('canvas_draft_data');

        return view('admin.theme-marketplace.my-licenses', compact('licenses', 'purchased', 'msg', 'hasActiveTheme', 'hasUnsavedDraft'));
    }

    public function install(Request $request, int $themeId)
    {
        if (!$this->platform->isConfigured()) {
            return back()->with('error', 'سرویس در دسترس نیست.');
        }

        // Theme Update Safety: installing over an active theme (or discarding an
        // unsaved canvas draft) is destructive — require explicit customer
        // confirmation instead of silently overwriting customizations. Re-checked
        // here against live Settings rather than trusting client-submitted state.
        $hasActiveTheme  = (bool) Setting::get('active_canvas_theme_data');
        $hasUnsavedDraft = (bool) Setting::get('canvas_draft_data');
        if (($hasActiveTheme || $hasUnsavedDraft) && !$request->boolean('confirm_overwrite')) {
            return back()->with('error', 'برای نصب قالب جدید باید تأیید کنید که قالب فعلی و شخصی‌سازی‌های ذخیره‌نشده آن جایگزین خواهند شد.');
        }

        $themeData = $this->platform->marketplaceDownloadTheme($themeId);

        if (!$themeData || empty($themeData['success'])) {
            return back()->with('error', 'دانلود قالب ناموفق بود. لطفاً مطمئن شوید لایسنس معتبر است.');
        }

        // BFA-H02: پشتیبان‌گیری از قالب فعلی قبل از نصب (legacy single-slot
        // backup — kept for the existing rollback() below; superseded as
        // the source of truth by the permanent revision recorded below).
        $currentData = Setting::get('active_canvas_theme_data');
        $isUpdate = (bool) $currentData;
        if ($currentData) {
            Setting::set('canvas_theme_backup_data', $currentData, 'theme');
            Setting::set('canvas_theme_backup_name', Setting::get('active_canvas_theme_name', 'قالب قبلی'), 'theme');
            Setting::set('canvas_theme_backup_at', now()->toDateTimeString(), 'theme');
        }

        // Permanent version history: creates an immutable revision and
        // atomically sets active_canvas_theme_data + active_theme_revision_id
        // + the identity Settings (id/name/slug) in one transaction (Phase
        // 3B) — previously the identity Settings were written directly here,
        // outside the ledger's transaction, so a failure between the two
        // could leave the installed theme's name/slug pointing at the new
        // theme while the ledger still reflected the old one. A first-ever
        // install for this site is type=install; installing over an
        // already-active theme (confirmed above) is type=update.
        $this->revisions->record([
            'header_blocks' => $themeData['header_blocks'] ?? [],
            'footer_blocks' => $themeData['footer_blocks'] ?? [],
            'pages'         => $themeData['pages'] ?? [],
            'global_styles' => $themeData['global_styles'] ?? [],
            'settings'      => $themeData['settings'] ?? [],
        ], $isUpdate ? ThemeInstanceRevision::TYPE_UPDATE : ThemeInstanceRevision::TYPE_INSTALL, [
            'theme_identifier' => $themeData['theme_slug'],
            'theme_name'       => $themeData['theme_name'],
            'active_theme_id'  => $themeId,
            'source_theme_id'  => $themeData['theme_id'] ?? $themeId,
            // ThemeRelease-authoritative lineage (Phase 1h): both come
            // straight from the download response's release identity, never
            // fabricated — null on either side simply means the theme had no
            // release yet at download time.
            'source_theme_version' => $themeData['release_version'] ?? null,
            'source_release_id'    => $themeData['release_id'] ?? null,
            'metadata'         => ['theme_name' => $themeData['theme_name']],
        ]);

        // BFA-M05: پاک‌سازی draft قدیمی تا editor با قالب جدید شروع کند
        Setting::where('key', 'canvas_draft_data')->delete();
        Cache::forget('settings');
        Cache::forget('canvas_theme_data');

        // پاک‌سازی کش تم
        if (class_exists(\App\Services\ThemeEngine\TenantThemeResolver::class)) {
            app(\App\Services\ThemeEngine\TenantThemeResolver::class)->clearCache();
        }

        return back()->with('success', "قالب «{$themeData['theme_name']}» نصب و فعال شد.");
    }

    /**
     * Phase 3D-F — read-only preview of a reconciled upgrade. Never writes
     * anything: this is exactly ThemeUpgradePreviewService::preview(), the
     * same pure computation Apply below re-runs independently.
     */
    public function previewUpgrade(int $themeId, ThemeUpgradePreviewService $previewService)
    {
        if (!$this->platform->isConfigured()) {
            return redirect()->route('admin.theme-marketplace.index')
                ->with('error', 'اتصال به پلتفرم پیکربندی نشده است.');
        }

        $preview = $previewService->preview($themeId);

        return view('admin.theme-marketplace.upgrade-preview', [
            'themeId' => $themeId,
            'preview' => $preview,
        ]);
    }

    /**
     * Phase 3D-F — the only endpoint that actually writes an upgrade. The
     * browser supplies identity/confirmation only (theme id, the target
     * release id it was shown, and the reconciliation fingerprint it was
     * shown) — never the reconciled Theme JSON, never conflict/canApply
     * booleans. Everything is recomputed here, from scratch, against
     * current real state, and compared to what was confirmed.
     */
    public function applyUpgrade(Request $request, int $themeId, ThemeUpgradePreviewService $previewService)
    {
        $data = $request->validate([
            'release_id'  => 'required|integer',
            'fingerprint' => 'required|string',
        ]);

        $preview = $previewService->preview($themeId);

        if (!$preview->isReady()) {
            return back()->with('error', 'پیش‌نمایش این ارتقا دیگر معتبر نیست. لطفاً دوباره تلاش کنید.');
        }

        // §12: exact target release identity, never "latest" — if the
        // platform's current release changed (or the theme was switched
        // out from under this identity) between Preview and this request,
        // this comparison catches it and refuses to guess which one was
        // actually confirmed.
        if ((int) ($preview->targetRelease['release_id'] ?? 0) !== $data['release_id']) {
            return back()->with('error', 'نسخهٔ مقصد از زمان پیش‌نمایش تغییر کرده است. لطفاً پیش‌نمایش را دوباره باز کنید.');
        }

        // §11: STALE_UPGRADE_PREVIEW — anything that changed BASE, LOCAL,
        // REMOTE, or dependency availability since Preview changes this
        // fingerprint (it is derived from exactly those three snapshots;
        // the dependency gate never touches it). No Theme/revision write
        // happens on a mismatch.
        if (!hash_equals($preview->reconciliation->fingerprint, $data['fingerprint'])) {
            return back()->with('error', 'وضعیت قالب از زمان پیش‌نمایش تغییر کرده است (STALE_UPGRADE_PREVIEW). لطفاً پیش‌نمایش را دوباره باز کنید.');
        }

        // §13/§20: dependency availability is a TOCTOU concern — re-checked
        // by this fresh preview() call above, never trusted from an older
        // canApply the client might submit.
        if (!$preview->reconciliation->canApply()) {
            return back()->with('error', 'به دلیل وابستگی‌های مسدودکننده، ارتقا امکان‌پذیر نیست.');
        }

        $activeRevisionId = (int) Setting::get('active_theme_revision_id');

        $newRevision = $this->revisions->recordUpgradeIfActiveRevisionUnchanged(
            $activeRevisionId,
            $preview->reconciliation->reconciledTheme,
            [
                'theme_identifier'     => $preview->targetRelease['theme_slug'],
                'theme_name'           => $preview->targetRelease['theme_name'],
                'active_theme_id'      => $preview->targetRelease['theme_id'],
                'source_theme_id'      => $preview->targetRelease['theme_id'],
                // §15: REMOTE becomes the new BASE — the old BASE's
                // provenance is never carried forward past a successful upgrade.
                'source_theme_version' => $preview->targetRelease['release_version'],
                'source_release_id'    => $preview->targetRelease['release_id'],
                'metadata' => [
                    'theme_name'                 => $preview->targetRelease['theme_name'],
                    'previous_revision_id'       => $activeRevisionId,
                    'previous_source_release_id' => $preview->currentRelease['release_id'] ?? null,
                    'target_release_id'          => $preview->targetRelease['release_id'],
                    'reconciliation_fingerprint' => $preview->reconciliation->fingerprint,
                    'conflict_count'             => count($preview->reconciliation->conflicts),
                    'blocking_conflict_count'    => 0,
                    'detached_block_count'       => count($preview->reconciliation->detachedBlocks),
                    'warning_count'              => count($preview->reconciliation->warnings),
                ],
            ]
        );

        if ($newRevision === null) {
            // §22/§23: the active revision moved between this fresh
            // preview() call and the write attempt (a concurrent Apply, or
            // an unrelated publish/rollback) — never a duplicate revision.
            return back()->with('error', 'وضعیت قالب هم‌زمان تغییر کرده است. لطفاً دوباره تلاش کنید.');
        }

        Setting::where('key', 'canvas_draft_data')->delete();
        Cache::forget('settings');
        Cache::forget('canvas_theme_data');
        if (class_exists(\App\Services\ThemeEngine\TenantThemeResolver::class)) {
            app(\App\Services\ThemeEngine\TenantThemeResolver::class)->clearCache();
        }

        return redirect()->route('admin.theme-marketplace.my-licenses')
            ->with('success', "قالب «{$preview->targetRelease['theme_name']}» با موفقیت ارتقا یافت.");
    }

    public function rollback(Request $request)
    {
        $backupData = Setting::get('canvas_theme_backup_data');
        if (!$backupData) {
            return back()->with('error', 'هیچ نسخه پشتیبانی برای بازگردانی وجود ندارد.');
        }

        $backupName = Setting::get('canvas_theme_backup_name', 'قالب قبلی');
        $backupAt   = Setting::get('canvas_theme_backup_at', '');

        // Phase 3B: this used to record the rollback_snapshot revision under
        // whichever lineage was active BEFORE the restore, then separately
        // overwrite active_canvas_theme_data/name directly and delete the
        // slug/id Settings — leaving active_theme_revision_id pointing at a
        // revision filed under a theme_identifier that currentIdentifier()
        // (now slug-less) would no longer report, breaking the ledger's own
        // lineage invariant for the very next rollback attempt. The legacy
        // single-slot backup never tracked which lineage it belonged to, so
        // the restored state is filed under 'custom' (no marketplace theme
        // confirmed installed) — the identity Settings and the revision now
        // land in one atomic call, so the pointer and currentIdentifier()
        // agree immediately afterward.
        $decoded = json_decode($backupData, true);
        if (is_array($decoded)) {
            $this->revisions->record($decoded, ThemeInstanceRevision::TYPE_ROLLBACK_SNAPSHOT, [
                'theme_identifier' => ThemeInstanceRevisionService::DEFAULT_IDENTIFIER,
                'theme_name'       => $backupName,
                'active_theme_id'  => null,
                'metadata'         => ['legacy_backup_restore' => true, 'restored_theme_name' => $backupName],
            ]);
        } else {
            // Backup payload isn't valid JSON — shouldn't happen, it was
            // written by json_encode() — fail safe without leaving the
            // revision pointer dangling.
            Setting::set('active_canvas_theme_data', $backupData, 'theme');
            Setting::set('active_canvas_theme_name', $backupName, 'theme');
            Setting::set('active_canvas_theme_slug', '', 'theme');
            Setting::where('key', 'active_canvas_theme_id')->delete();
            Setting::set('active_theme_revision_id', '', 'theme');
        }

        // حذف draft و backup پس از بازگردانی
        Setting::where('key', 'canvas_draft_data')->delete();
        Setting::where('key', 'canvas_theme_backup_data')->delete();
        Setting::where('key', 'canvas_theme_backup_name')->delete();
        Setting::where('key', 'canvas_theme_backup_at')->delete();
        Cache::forget('settings');

        if (class_exists(\App\Services\ThemeEngine\TenantThemeResolver::class)) {
            app(\App\Services\ThemeEngine\TenantThemeResolver::class)->clearCache();
        }

        return back()->with('success', "قالب «{$backupName}» با موفقیت بازگردانی شد.");
    }

    /**
     * GAP-H06 — دانلود فاکتور PDF برای خرید قالب.
     */
    public function invoiceDownload(string $licenseKey)
    {
        if (! $this->platform->isConfigured()) {
            abort(503, 'سرویس در دسترس نیست.');
        }

        $data = $this->platform->getThemeLicenseInvoiceData($licenseKey);

        if (! $data || empty($data['success'])) {
            abort(404, 'اطلاعات فاکتور یافت نشد.');
        }

        // Build a plain-object that matches what invoice/pdf.blade.php expects
        $amountToman = (int) round(($data['amount_rial'] ?? 0) / 10);
        $invoice = (object) [
            'invoice_number'   => $data['invoice_number'] ?? 'THEME',
            'customer_name'    => $data['customer_name'] ?? '',
            'customer_mobile'  => $data['customer_mobile'] ?? '',
            'customer_phone'   => '',
            'created_at'       => $data['purchased_at'] ? \Carbon\Carbon::parse($data['purchased_at']) : now(),
            'paid_at'          => $data['purchased_at'] ? \Carbon\Carbon::parse($data['purchased_at']) : null,
            'status'           => 'paid',
            'subtotal'         => $amountToman,
            'discount'         => 0,
            'total'            => $amountToman,
            'notes'            => "کلید مجوز: {$data['license_key']}" . ($data['gateway_ref'] ? "\nشماره پیگیری پرداخت: {$data['gateway_ref']}" : ''),
            'items'            => collect([
                (object) [
                    'title'    => "قالب «{$data['theme_name']}»",
                    'quantity' => 1,
                    'price'    => $amountToman,
                    'total'    => $amountToman,
                ],
            ]),
        ];

        $pdf = Pdf::loadView('invoice.pdf', compact('invoice'))
            ->setPaper('A4', 'portrait');

        $filename = 'theme-invoice-' . $data['invoice_number'] . '.pdf';

        return $pdf->download($filename);
    }
}
