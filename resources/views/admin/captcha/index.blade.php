@extends('admin.layout')
@section('title', 'تنظیمات کپچا')
@section('header', 'تنظیمات کپچا')
@section('content')
<div class="max-w-2xl space-y-6">
    <div class="bg-white rounded-2xl shadow p-6">
        <h2 class="font-bold text-lg mb-2">تنظیمات کپچا</h2>
        <p class="text-gray-500 text-sm mb-4">کپچا برای جلوگیری از ارسال ربات‌ها در فرم‌های تماس، فرم‌ساز و ورود/ثبت‌نام استفاده می‌شود.</p>

        <form method="POST" action="{{ route('admin.captcha.update') }}" class="space-y-4">
            @csrf
            <div class="form-group">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="captcha_enabled" value="1" class="rounded border-gray-300" {{ $captchaEnabled ? 'checked' : '' }}>
                    <span>فعال‌سازی کپچا برای فرم‌ها (تماس، فرم‌ساز و غیره)</span>
                </label>
            </div>
            <div class="form-group">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="auth_captcha_enabled" value="1" class="rounded border-gray-300" {{ $authCaptchaEnabled ?? false ? 'checked' : '' }}>
                    <span>فعال‌سازی کپچا برای فرم ورود و ثبت‌نام</span>
                </label>
            </div>
            <div class="form-group">
                <label class="form-label">نوع کپچا</label>
                <select name="captcha_type" class="w-full max-w-xs px-3 py-2 border border-slate-300 rounded-lg">
                    <option value="persian_number" {{ $captchaType === 'persian_number' ? 'selected' : '' }}>کپچای عدد فارسی</option>
                    <option value="normal" {{ $captchaType === 'normal' ? 'selected' : '' }}>کپچای معمولی (ریاضی فارسی)</option>
                    <option value="hcaptcha" {{ $captchaType === 'hcaptcha' ? 'selected' : '' }}>hCaptcha</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">کلید سایت hCaptcha (اختیاری)</label>
                <input type="text" name="hcaptcha_site_key" class="w-full max-w-md px-3 py-2 border border-slate-300 rounded-lg" value="{{ old('hcaptcha_site_key', $hcaptchaSiteKey) }}" placeholder="برای نوع hCaptcha وارد کنید">
            </div>
            <div class="form-group">
                <label class="form-label">کلید مخفی hCaptcha (اختیاری)</label>
                <input type="password" name="hcaptcha_secret_key" class="w-full max-w-md px-3 py-2 border border-slate-300 rounded-lg" value="{{ old('hcaptcha_secret_key', $hcaptchaSecretKey) }}" placeholder="برای تأیید در سرور لازم است">
            </div>
            @if($captchaType === 'hcaptcha')
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-3 text-sm text-blue-800">
                <strong>نکته:</strong> دامنهٔ سایت شما (مثلاً <code>{{ request()->getHost() }}</code>) باید در پنل hCaptcha در بخش «Passed Hostnames» اضافه شود.
            </div>
            @endif
            <button type="submit" class="btn btn-primary">ذخیره</button>
        </form>
    </div>
</div>
@endsection
