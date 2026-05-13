<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

/**
 * تنظیمات کپچا برای سایت مشتری.
 * مشتری می‌تواند کپچا را برای فرم‌ها (تماس، فرم‌ساز و غیره) فعال کند.
 */
class CaptchaController extends Controller
{
    public const TYPE_PERSIAN_NUMBER = 'persian_number';
    public const TYPE_NORMAL = 'normal';
    public const TYPE_HCAPTCHA = 'hcaptcha';

    public function index()
    {
        $captchaEnabled = (string) Setting::get('captcha_enabled', '0') === '1';
        $authCaptchaEnabled = (string) Setting::get('auth_captcha_enabled', '0') === '1';
        // Phase 5 / Wave 5.3 / P1-5: toggle جدید کپچا برای فرم‌های نوشتنی فروشگاه.
        $shopCaptchaEnabled = (string) Setting::get('shop_captcha_enabled', '0') === '1';
        $shopModuleEnabled = \App\Helpers\SiteHelper::shopEnabled();
        $captchaType = (string) Setting::get('captcha_type', self::TYPE_PERSIAN_NUMBER);
        $hcaptchaSiteKey = (string) Setting::get('hcaptcha_site_key', '');
        $hcaptchaSecretKey = (string) Setting::get('hcaptcha_secret_key', '');

        return view('admin.captcha.index', compact(
            'captchaEnabled',
            'authCaptchaEnabled',
            'shopCaptchaEnabled',
            'shopModuleEnabled',
            'captchaType',
            'hcaptchaSiteKey',
            'hcaptchaSecretKey'
        ));
    }

    public function update(Request $request)
    {
        $request->validate([
            'captcha_enabled' => 'nullable|boolean',
            'auth_captcha_enabled' => 'nullable|boolean',
            'shop_captcha_enabled' => 'nullable|boolean',
            'captcha_type' => 'required|in:persian_number,normal,hcaptcha',
            'hcaptcha_site_key' => 'nullable|string|max:500',
            'hcaptcha_secret_key' => 'nullable|string|max:500',
        ]);

        Setting::set('captcha_enabled', $request->boolean('captcha_enabled') ? '1' : '0', 'captcha');
        Setting::set('auth_captcha_enabled', $request->boolean('auth_captcha_enabled') ? '1' : '0', 'captcha');
        // Phase 5 / Wave 5.3 / P1-5: ذخیرهٔ toggle جدید shop_captcha_enabled.
        // اگر ماژول shop غیرفعال شود، entitlementهای موجود حفظ می‌شوند؛ این فقط یک تنظیم UI است.
        Setting::set('shop_captcha_enabled', $request->boolean('shop_captcha_enabled') ? '1' : '0', 'captcha');
        Setting::set('captcha_type', $request->captcha_type, 'captcha');
        Setting::set('hcaptcha_site_key', $request->input('hcaptcha_site_key', ''), 'captcha');
        Setting::set('hcaptcha_secret_key', $request->input('hcaptcha_secret_key', ''), 'captcha');

        return back()->with('success', 'تنظیمات کپچا ذخیره شد.');
    }
}
