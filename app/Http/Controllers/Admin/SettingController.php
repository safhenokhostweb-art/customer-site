<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Module;
use App\Helpers\PlatformUx;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\BuilderLayoutService;
use App\Services\CustomerMediaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SettingController extends Controller
{
    public function __construct(
        protected BuilderLayoutService $layoutService,
        protected CustomerMediaService $media,
    ) {}

    public function index()
    {
        $siteName = Setting::get('site_name', '');
        $theme = Setting::get('theme', 'default');
        $aboutUsTitle = Setting::get('about_us_title', '');
        $aboutUsText = Setting::get('about_us_text', '');
        $aboutUsEnabled = (bool) Setting::get('about_us_enabled', false);
        $notificationBellPosition = Setting::get('notification_bell_position', 'bottom-right');
        $builderHeaderTemplate = Setting::get(BuilderLayoutService::SETTING_HEADER_TEMPLATE, '');
        $builderFooterTemplate = Setting::get(BuilderLayoutService::SETTING_FOOTER_TEMPLATE, '');
        $builderStickyHeader = (bool) Setting::get(BuilderLayoutService::SETTING_STICKY_HEADER, true);
        $headerOptions = $this->layoutService->getHeaderTemplateOptions();
        $footerOptions = $this->layoutService->getFooterTemplateOptions();
        $modules = Module::all();
        $themeBuilder = PlatformUx::themeBuilder();
        $themeMeta = $themeBuilder['meta'] ?? ($themeBuilder['settings']['meta'] ?? []);
        $localCanvasData = Setting::get('active_canvas_theme_data', '');
        $localCanvasSlug = (string) Setting::get('active_canvas_theme_slug', '');
        $localCanvasName = (string) Setting::get('active_canvas_theme_name', '');
        $hasLocalCanvasTheme = !empty($localCanvasData) && ($localCanvasSlug !== '' || $localCanvasName !== '');

        if ($hasLocalCanvasTheme && (empty($themeMeta) || ($themeMeta['id'] ?? '') === 'default')) {
            $activeRevisionId = Setting::get('active_theme_revision_id');
            $activeRevision = $activeRevisionId ? \App\Models\ThemeInstanceRevision::find($activeRevisionId) : null;
            $themeMeta = [
                'id' => $localCanvasSlug !== '' ? $localCanvasSlug : 'custom',
                'name' => $localCanvasName !== '' ? $localCanvasName : 'قالب سفارشی',
                'version' => $activeRevision?->source_theme_version ?? '1.0.0',
            ];
        } elseif (!$hasLocalCanvasTheme && (empty($themeMeta) || ($themeMeta['id'] ?? '') === 'default')) {
            $themeMeta = [];
        }
        $hasActiveTheme = !empty($themeMeta);

        $currentLogo = Setting::get('site_logo', '');
        $currentLogoUrl = $this->media->resolveSiteAssetUrl($currentLogo);
        $currentFavicon = Setting::get('site_favicon', '');
        $currentFaviconUrl = $this->media->resolveSiteAssetUrl($currentFavicon);
        return view('admin.settings.index', compact(
            'siteName',
            'theme',
            'currentLogo',
            'currentLogoUrl',
            'currentFavicon',
            'currentFaviconUrl',
            'aboutUsTitle',
            'aboutUsText',
            'aboutUsEnabled',
            'notificationBellPosition',
            'builderHeaderTemplate',
            'builderFooterTemplate',
            'builderStickyHeader',
            'headerOptions',
            'footerOptions',
            'modules',
            'themeMeta',
            'themeBuilder',
            'hasActiveTheme'
        ));
    }

    public function update(Request $request)
    {
        $request->validate([
            'site_name' => 'required|string|max:255',
            'theme' => 'required|string|in:default,minimal,dark',
            'about_us_title' => 'nullable|string|max:255',
            'about_us_text' => 'nullable|string|max:2000',
            'notification_bell_position' => 'nullable|string|in:bottom-right,bottom-left,top-right,top-left',
            'builder_header_template' => 'nullable|string|max:64',
            'builder_footer_template' => 'nullable|string|max:64',
            'builder_sticky_header' => 'nullable|boolean',
        ]);
        Setting::set('site_name', $request->site_name);
        Setting::set('theme', $request->theme);
        Setting::set('about_us_enabled', $request->boolean('about_us_enabled') ? '1' : '0');
        Setting::set('about_us_title', $request->about_us_title ?? '');
        Setting::set('about_us_text', $request->about_us_text ?? '');
        Setting::set('notification_bell_position', $request->input('notification_bell_position', 'bottom-right'), 'general');
        Setting::set(BuilderLayoutService::SETTING_HEADER_TEMPLATE, $request->input('builder_header_template', ''));
        Setting::set(BuilderLayoutService::SETTING_FOOTER_TEMPLATE, $request->input('builder_footer_template', ''));
        Setting::set(BuilderLayoutService::SETTING_STICKY_HEADER, $request->boolean('builder_sticky_header', true) ? '1' : '0');
        return back()->with('success', 'تنظیمات ذخیره شد.');
    }

    /**
     * TASK 3/3 Phase 6.5C (BLD-25) — favicon upload converged onto the
     * canonical CustomerMediaService ("the only write authority for
     * customer-owned public media") instead of an ad-hoc Storage call.
     * The favicon now gets a real CustomerMedia row (visible in the Media
     * Library, usage-tracked) and `site_favicon` stores its stable UUID —
     * see CustomerMediaService::resolveSiteAssetUrl() for how every reader
     * resolves either the new UUID form or a pre-existing raw-path value.
     *
     * .ico is intentionally NOT accepted here any more: CustomerMediaService's
     * own validate() policy (image/jpeg|png|gif|webp) is the one real MIME
     * allowlist for customer-owned media, and adding a second, wider
     * allowlist just for this one field would be exactly the kind of
     * duplicate authority this convergence exists to remove. png/webp
     * already cover every real favicon use case a modern browser needs.
     */
    public function uploadFavicon(Request $request)
    {
        $request->validate(['favicon' => ['required', 'file', 'max:512']], [
            'favicon.required' => 'لطفاً یک فایل تصویری انتخاب کنید.',
            'favicon.max'      => 'حجم فایل نباید بیشتر از ۵۱۲ کیلوبایت باشد.',
        ]);

        $old = Setting::get('site_favicon', '');

        try {
            $media = $this->media->upload($request->file('favicon'), ['title' => 'Site Favicon']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors(['favicon' => $e->validator->errors()->first()]);
        }

        Setting::set('site_favicon', $media->uuid);
        $this->deleteLegacySiteAsset($old);

        return back()->with('success', 'آیکن سایت با موفقیت آپلود شد.');
    }

    /** See uploadFavicon()'s docblock — same convergence, same reasoning. */
    public function uploadLogo(Request $request)
    {
        $request->validate(['logo' => ['required', 'file', 'max:2048']], [
            'logo.required' => 'لطفاً یک فایل تصویری انتخاب کنید.',
            'logo.max'      => 'حجم فایل نباید بیشتر از ۲ مگابایت باشد.',
        ]);

        $old = Setting::get('site_logo', '');

        try {
            $media = $this->media->upload($request->file('logo'), ['title' => 'Site Logo']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors(['logo' => $e->validator->errors()->first()]);
        }

        Setting::set('site_logo', $media->uuid);
        $this->deleteLegacySiteAsset($old);

        return back()->with('success', 'لوگوی سایت با موفقیت آپلود شد.');
    }

    public function removeLogo(Request $request)
    {
        $this->deleteLegacySiteAsset(Setting::get('site_logo', ''), forceDeleteMedia: true);
        Setting::set('site_logo', '');

        return back()->with('success', 'لوگو حذف شد؛ نام سایت به‌جای آن نمایش داده می‌شود.');
    }

    /**
     * Deletes whatever the OLD site_logo/site_favicon value pointed at,
     * whichever form it's in — a real CustomerMedia UUID (post-convergence)
     * or a legacy raw 'public'-disk path (pre-convergence, or a dev/test
     * fixture seeded before this phase). A CustomerMedia row is only
     * force-deleted for an explicit "remove" action (removeLogo) — a plain
     * re-upload leaves the old media row alone if canonical usage-tracking
     * says it's still referenced elsewhere, exactly like every other
     * CustomerMediaService-backed replace flow.
     */
    private function deleteLegacySiteAsset(string $value, bool $forceDeleteMedia = false): void
    {
        if ($value === '') {
            return;
        }

        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value)) {
            try {
                $old = $this->media->findForCurrentSite($value);
                if ($forceDeleteMedia || !$this->media->isUsed($old)) {
                    $this->media->deleteUnused($old);
                }
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException|\LogicException) {
                // Already gone, or genuinely in use elsewhere — either way
                // there is nothing unsafe left to do here.
            }
            return;
        }

        $mediaStorage = app(\App\Services\MediaStorage::class);
        if ($mediaStorage->exists($value, 'uploads')) {
            $mediaStorage->delete($value, 'uploads');
        } elseif ($mediaStorage->exists($value, 'legacy')) {
            $mediaStorage->delete($value, 'legacy');
        } elseif (Storage::disk('public')->exists($value)) {
            Storage::disk('public')->delete($value);
        }
    }
}
