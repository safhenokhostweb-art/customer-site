<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Module;
use App\Helpers\PlatformUx;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\BuilderLayoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SettingController extends Controller
{
    public function __construct(
        protected BuilderLayoutService $layoutService
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
        $currentLogo = Setting::get('site_logo', '');
        return view('admin.settings.index', compact(
            'siteName',
            'theme',
            'currentLogo',
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
            'themeBuilder'
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

    public function uploadFavicon(Request $request)
    {
        $request->validate([
            'favicon' => [
                'required',
                'file',
                'max:512',
                // SVG intentionally excluded: this app has no canonical SVG
                // sanitization authority (same policy as CustomerMediaService),
                // and a raw, unsanitized SVG served directly from the public
                // disk is a stored-XSS vector (embedded <script>/event
                // handlers execute if the URL is opened directly).
                'mimes:ico,png,jpg,jpeg,gif,webp',
            ],
        ], [
            'favicon.required' => 'لطفاً یک فایل تصویری انتخاب کنید.',
            'favicon.max'      => 'حجم فایل نباید بیشتر از ۵۱۲ کیلوبایت باشد.',
            'favicon.mimes'    => 'فرمت قابل‌قبول: ico, png, jpg, gif, webp',
        ]);

        $file = $request->file('favicon');
        $ext  = strtolower($file->getClientOriginalExtension()) ?: 'ico';

        // Delete old favicon if it exists
        $old = Setting::get('site_favicon', '');
        if ($old && Storage::disk('public')->exists($old)) {
            Storage::disk('public')->delete($old);
        }

        $path = $file->storeAs('favicon', 'favicon.' . $ext, 'public');
        Setting::set('site_favicon', $path);

        return back()->with('success', 'آیکن سایت با موفقیت آپلود شد.');
    }

    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => [
                'required',
                'file',
                'max:2048',
                // SVG intentionally excluded — see uploadFavicon()'s comment.
                'mimes:png,jpg,jpeg,gif,webp',
            ],
        ], [
            'logo.required' => 'لطفاً یک فایل تصویری انتخاب کنید.',
            'logo.max'      => 'حجم فایل نباید بیشتر از ۲ مگابایت باشد.',
            'logo.mimes'    => 'فرمت قابل‌قبول: png, jpg, gif, webp',
        ]);

        $file = $request->file('logo');
        $ext  = strtolower($file->getClientOriginalExtension()) ?: 'png';

        // Delete old logo if it exists
        $old = Setting::get('site_logo', '');
        if ($old && Storage::disk('public')->exists($old)) {
            Storage::disk('public')->delete($old);
        }

        $path = $file->storeAs('logo', 'logo.' . $ext, 'public');
        Setting::set('site_logo', $path);

        return back()->with('success', 'لوگوی سایت با موفقیت آپلود شد.');
    }

    public function removeLogo(Request $request)
    {
        $old = Setting::get('site_logo', '');
        if ($old && Storage::disk('public')->exists($old)) {
            Storage::disk('public')->delete($old);
        }
        Setting::set('site_logo', '');

        return back()->with('success', 'لوگو حذف شد؛ نام سایت به‌جای آن نمایش داده می‌شود.');
    }
}
