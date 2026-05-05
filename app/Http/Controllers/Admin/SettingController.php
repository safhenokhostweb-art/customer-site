<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Module;
use App\Helpers\PlatformUx;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\BuilderLayoutService;
use Illuminate\Http\Request;

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
}
