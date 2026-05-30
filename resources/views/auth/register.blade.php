@extends('layouts.auth-login')

@section('title', 'ثبت‌نام')

@section('content')
<div class="auth-login-card">
    <h1>ساخت حساب کاربری</h1>
    <p class="auth-login-lead">اطلاعات زیر را پر کنید تا وارد پنل کاربری شوید.</p>

    @if ($errors->any())
        <div class="alert alert-danger text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div>
            <label class="form-label" for="reg-name">نام و نام‌خانوادگی <span style="color:#ef4444">*</span></label>
            <input id="reg-name" type="text" name="name" value="{{ old('name') }}"
                   placeholder="مثال: علی رضایی"
                   class="form-control" required autocomplete="name">
        </div>

        <div>
            <label class="form-label" for="reg-mobile">شماره موبایل <span style="color:#ef4444">*</span></label>
            <input id="reg-mobile" type="text" name="mobile" value="{{ old('mobile') }}"
                   placeholder="مثال: ۰۹۱۲۳۴۵۶۷۸۹"
                   class="form-control" required dir="rtl" autocomplete="tel"
                   inputmode="numeric">
        </div>


        <div>
            <label class="form-label" for="reg-password">رمز عبور <span style="color:#ef4444">*</span></label>
            <input id="reg-password" type="password" name="password"
                   placeholder="حداقل ۶ کاراکتر"
                   class="form-control" required dir="rtl" autocomplete="new-password">
        </div>

        <div>
            <label class="form-label" for="reg-password-confirm">تکرار رمز عبور <span style="color:#ef4444">*</span></label>
            <input id="reg-password-confirm" type="password" name="password_confirmation"
                   placeholder="رمز عبور را تکرار کنید"
                   class="form-control" required dir="rtl" autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-primary w-full" style="margin-top:1.25rem">
            ساخت حساب و ورود
        </button>
    </form>
</div>

<div class="auth-login-footer-text">
    قبلاً ثبت‌نام کرده‌اید؟
    <a href="{{ route('login') }}">ورود به حساب</a>
</div>
@endsection
