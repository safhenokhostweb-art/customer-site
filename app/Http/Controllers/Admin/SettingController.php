<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\AdminSidebarTheme;
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
        $adminSidebarTheme = AdminSidebarTheme::all();
        $adminSidebarThemeLabels = AdminSidebarTheme::labels();

        return view('admin.settings.index', compact(
            'siteName',
            'theme',
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
            'adminSidebarTheme',
            'adminSidebarThemeLabels'
        ));
    }

    public function update(Request $request)
    {
        $sidebarRules = [];
        foreach (array_keys(AdminSidebarTheme::KEYS) as $k) {
            $sidebarRules[$k] = ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'];
        }
        $request->validate(array_merge($sidebarRules, [
            'site_name' => 'required|string|max:255',
            'theme' => 'required|string|in:default,minimal,dark',
            'about_us_title' => 'nullable|string|max:255',
            'about_us_text' => 'nullable|string|max:2000',
            'notification_bell_position' => 'nullable|string|in:bottom-right,bottom-left,top-right,top-left',
            'builder_header_template' => 'nullable|string|max:64',
            'builder_footer_template' => 'nullable|string|max:64',
            'builder_sticky_header' => 'nullable|boolean',
        ]));
        Setting::set('site_name', $request->site_name);
        Setting::set('theme', $request->theme);
        Setting::set('about_us_enabled', $request->boolean('about_us_enabled') ? '1' : '0');
        Setting::set('about_us_title', $request->about_us_title ?? '');
        Setting::set('about_us_text', $request->about_us_text ?? '');
        Setting::set('notification_bell_position', $request->input('notification_bell_position', 'bottom-right'), 'general');
        Setting::set(BuilderLayoutService::SETTING_HEADER_TEMPLATE, $request->input('builder_header_template', ''));
        Setting::set(BuilderLayoutService::SETTING_FOOTER_TEMPLATE, $request->input('builder_footer_template', ''));
        Setting::set(BuilderLayoutService::SETTING_STICKY_HEADER, $request->boolean('builder_sticky_header', true) ? '1' : '0');
        foreach (array_keys(AdminSidebarTheme::KEYS) as $k) {
            Setting::set($k, $request->input($k));
        }
        return back()->with('success', 'تنظیمات ذخیره شد.');
    }

    public function uploadFavicon(Request $request)
    {
        $request->validate([
            'favicon' => [
                'required',
                'file',
                'max:512',
                'mimes:ico,png,jpg,jpeg,gif,webp,svg+xml,svg',
            ],
        ], [
            'favicon.required' => 'لطفاً یک فایل تصویری انتخاب کنید.',
            'favicon.max'      => 'حجم فایل نباید بیشتر از ۵۱۲ کیلوبایت باشد.',
            'favicon.mimes'    => 'فرمت قابل‌قبول: ico, png, jpg, gif, webp, svg',
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
}
