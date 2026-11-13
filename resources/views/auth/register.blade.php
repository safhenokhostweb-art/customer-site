@extends('layouts.auth-login')

@section('title', 'ثبت‌نام')

@section('content')
<div class="auth-login-card">
    <h1>ثبت‌نام در پنل</h1>
    <p class="auth-login-lead">حساب کاربری جدید خود را ایجاد کنید.</p>

    @if ($errors->any())
        <div class="alert alert-danger text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('register', [], false) }}" class="space-y-4">
        @csrf

        <div>
            <label class="form-label" for="register-name">نام و نام خانوادگی</label>
            <input id="register-name" type="text" name="name" value="{{ old('name') }}" placeholder="مثال: علی محمدی" class="form-control" required dir="rtl" autocomplete="name">
        </div>

        <div>
            <label class="form-label" for="register-mobile">شماره موبایل</label>
            <input id="register-mobile" type="text" name="mobile" value="{{ old('mobile') }}" placeholder="مثال: ۰۹۱۲۳۴۵۶۷۸۹" class="form-control" required dir="rtl" autocomplete="tel">
        </div>

        <div>
            <label class="form-label" for="register-password">رمز عبور</label>
            <input id="register-password" type="password" name="password" placeholder="حداقل ۸ کاراکتر" class="form-control" required dir="rtl" autocomplete="new-password">
        </div>

        <div>
            <label class="form-label" for="register-password-confirm">تکرار رمز عبور</label>
            <input id="register-password-confirm" type="password" name="password_confirmation" placeholder="تکرار رمز عبور" class="form-control" required dir="rtl" autocomplete="new-password">
        </div>

        @include('partials.auth-captcha')

        <button type="submit" class="btn btn-primary w-full">ثبت‌نام و ایجاد حساب</button>
    </form>
</div>

<div class="auth-login-footer-text">
    قبلاً حساب کاربری داشته‌اید؟
    <a href="{{ route('login') }}">ورود به پنل</a>
</div>
@endsection
