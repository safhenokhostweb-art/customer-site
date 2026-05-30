<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function showForm()
    {
        if (!$this->registrationEnabled()) {
            return redirect()->route('login')->with('error', 'ثبت‌نام در این سایت غیرفعال است.');
        }

        return view('auth.register');
    }

    public function register(Request $request)
    {
        if (!$this->registrationEnabled()) {
            abort(403, 'ثبت‌نام غیرفعال است.');
        }

        $data = $request->validate([
            'name'                  => ['required', 'string', 'max:100'],
            'mobile'                => ['required', 'string', 'unique:users,mobile'],
            'email'                 => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password'              => ['required', 'confirmed', Password::min(6)],
        ], [
            'name.required'         => 'نام الزامی است.',
            'mobile.required'       => 'شماره موبایل الزامی است.',
            'mobile.unique'         => 'این شماره موبایل قبلاً ثبت شده است.',
            'email.unique'          => 'این ایمیل قبلاً ثبت شده است.',
            'password.required'     => 'رمز عبور الزامی است.',
            'password.confirmed'    => 'تکرار رمز عبور مطابقت ندارد.',
            'password.min'          => 'رمز عبور باید حداقل ۶ کاراکتر باشد.',
        ]);

        $mobile = LoginController::normalizeMobile($data['mobile']);

        if (User::where('mobile', $mobile)->exists()) {
            return back()->withInput()->withErrors(['mobile' => 'این شماره موبایل قبلاً ثبت شده است.']);
        }

        $user = User::create([
            'name'     => $data['name'],
            'mobile'   => $mobile,
            'email'    => $data['email'] ?: null,
            'password' => Hash::make($data['password']),
            'is_admin' => false,
        ]);

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->route('account.dashboard')
            ->with('success', 'خوش آمدید ' . $user->name . '! حساب شما با موفقیت ساخته شد.');
    }

    private function registrationEnabled(): bool
    {
        // می‌توان از طریق تنظیمات ادمین غیرفعال کرد
        return (bool) Setting::get('auth.registration_enabled', true);
    }
}
