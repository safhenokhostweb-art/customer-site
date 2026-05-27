<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserActivityLog;
use App\Rules\IranianMobile;
use App\Services\CaptchaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        $captcha = ['enabled' => false, 'type' => null, 'hcaptcha_site_key' => '', 'persian_code' => '', 'normal_text' => ''];
        $captchaService = app(CaptchaService::class);
        $cfg = $captchaService->getAuthCaptchaConfig();
        if ($cfg['enabled']) {
            $captcha['enabled'] = true;
            $captcha['type'] = $cfg['type'];
            $captcha['hcaptcha_site_key'] = $cfg['hcaptcha_site_key'];
            if ($cfg['type'] === CaptchaService::TYPE_PERSIAN_NUMBER) {
                $captcha['persian_code'] = $captchaService->generatePersianNumber(4);
            } elseif ($cfg['type'] === CaptchaService::TYPE_NORMAL) {
                $gen = $captchaService->generateNormal();
                $captcha['normal_text'] = $gen['text'];
            }
        }

        return view('auth.login', compact('captcha'));
    }

    public function login(Request $request)
    {
        $captchaService = app(CaptchaService::class);
        $cfg = $captchaService->getAuthCaptchaConfig();
        if ($cfg['enabled']) {
            $captchaValid = false;
            $captchaError = 'کپچا اشتباه است یا منقضی شده. لطفا دوباره تلاش کنید.';
            if ($cfg['type'] === CaptchaService::TYPE_HCAPTCHA) {
                $token = $request->input('h-captcha-response', '');
                if (trim($token) === '') {
                    $captchaError = 'لطفا کپچا را تکمیل کنید.';
                } else {
                    $captchaValid = $captchaService->verifyHcaptcha($token, $cfg['hcaptcha_secret_key']);
                    if (!$captchaValid) {
                        $captchaError = 'کپچا تأیید نشد. لطفا دوباره تلاش کنید یا صفحه را بروز کنید.';
                    }
                }
            } elseif ($cfg['type'] === CaptchaService::TYPE_PERSIAN_NUMBER) {
                $captchaValid = $captchaService->validatePersianNumber($request->input('captcha_answer', ''));
                if (!$captchaValid) {
                    $captchaError = 'کپچا اشتباه است یا منقضی شده. لطفا صفحه را بروز کنید و دوباره عدد نمایش‌داده‌شده را وارد کنید.';
                }
            } else {
                $captchaValid = $captchaService->validateNormal($request->input('captcha_answer', ''));
                if (!$captchaValid) {
                    $captchaError = 'کپچا اشتباه است یا منقضی شده. لطفا صفحه را بروز کنید و دوباره جواب را وارد کنید.';
                }
            }
            if (!$captchaValid) {
                return back()->withInput()->withErrors(['captcha' => $captchaError]);
            }
        }

        $request->validate([
            'mobile' => ['required', new IranianMobile],
            'password' => 'required',
        ]);

        $mobile = self::normalizeMobile($request->input('mobile'));

        $user = User::where('mobile', $mobile)->first();

        if ($user && Hash::check($request->input('password'), $user->password)) {
            Auth::login($user, (bool) $request->remember);
            $request->session()->regenerate();
            UserActivityLog::log('login', 'ورود به حساب', null, null, $user->id);
            $defaultRoute = $user->isTenantAdmin() ? route('admin.dashboard') : route('account.dashboard');

            return redirect()->intended($defaultRoute);
        }

        return back()->withErrors(['mobile' => 'موبایل یا رمز عبور اشتباه است.'])->onlyInput('mobile');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * نرمال‌سازی شماره موبایل (ارقام فارسی به انگلیسی، ۱۰ رقمی به ۰۹...).
     */
    public static function normalizeMobile(string $value): string
    {
        $persianDigits = ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9'];
        $clean = strtr($value, $persianDigits);
        $clean = preg_replace('/[^0-9]/', '', $clean);
        if (strlen($clean) === 10 && str_starts_with($clean, '9')) {
            $clean = '0' . $clean;
        }

        return $clean;
    }
}
