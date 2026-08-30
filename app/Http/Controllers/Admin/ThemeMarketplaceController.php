<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\ThemeInstanceRevision;
use App\Services\PlatformApiService;
use App\Services\ThemeCanvas\ThemeInstanceRevisionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ThemeMarketplaceController extends Controller
{
    public function __construct(
        protected PlatformApiService $platform,
        protected ThemeInstanceRevisionService $revisions,
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

        return view('admin.theme-marketplace.show', compact('theme', 'hasActiveTheme', 'hasUnsavedDraft'));
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

        // ذخیره داده قالب در تنظیمات سایت
        Setting::set('active_canvas_theme_id', $themeId, 'theme');
        Setting::set('active_canvas_theme_name', $themeData['theme_name'], 'theme');
        Setting::set('active_canvas_theme_slug', $themeData['theme_slug'], 'theme');

        // Permanent version history: creates an immutable revision and sets
        // active_canvas_theme_data + active_theme_revision_id. A first-ever
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

    public function rollback(Request $request)
    {
        $backupData = Setting::get('canvas_theme_backup_data');
        if (!$backupData) {
            return back()->with('error', 'هیچ نسخه پشتیبانی برای بازگردانی وجود ندارد.');
        }

        $backupName = Setting::get('canvas_theme_backup_name', 'قالب قبلی');
        $backupAt   = Setting::get('canvas_theme_backup_at', '');

        // Permanent version history: this legacy single-slot restore doesn't
        // track which theme lineage the backup belongs to (the slug/id are
        // deleted below, as before), so the resulting rollback_snapshot is
        // recorded under whatever lineage was active going into this call.
        $decoded = json_decode($backupData, true);
        if (is_array($decoded)) {
            $this->revisions->record($decoded, ThemeInstanceRevision::TYPE_ROLLBACK_SNAPSHOT, [
                'theme_identifier' => $this->revisions->currentIdentifier(),
                'metadata' => ['legacy_backup_restore' => true, 'restored_theme_name' => $backupName],
            ]);
        }

        // بازگردانی قالب پشتیبان
        Setting::set('active_canvas_theme_data', $backupData, 'theme');
        Setting::set('active_canvas_theme_name', $backupName, 'theme');
        Setting::where('key', 'active_canvas_theme_id')->delete();
        Setting::where('key', 'active_canvas_theme_slug')->delete();

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
