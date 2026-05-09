@extends('install.layout')
@section('title', 'حساب مدیر')

<h1>ایجاد حساب مدیر</h1>

<form method="POST" action="{{ route('install.store-admin', [], false) }}">
    @csrf
    <label>نام</label>
    <input type="text" name="name" value="{{ old('name') }}" required>
    <label>شماره موبایل (برای ورود)</label>
    <input type="text" name="mobile" value="{{ old('mobile') }}" placeholder="09123456789" required dir="ltr">
    <label>ایمیل</label>
    <input type="email" name="email" value="{{ old('email') }}" required>
    <label>رمز عبور</label>
    <input type="password" name="password" required>
    <label>تکرار رمز عبور</label>
    <input type="password" name="password_confirmation" required>
    @if($errors->any())
        <p class="error">{{ $errors->first() }}</p>
    @endif
    <div class="mt-2">
        <button type="submit">مرحله بعد</button>
    </div>
</form>
