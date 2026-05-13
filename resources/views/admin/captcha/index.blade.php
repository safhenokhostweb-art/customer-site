@extends('admin.layout')
@section('title', 'تنظیمات کپچا')
@section('header', 'تنظیمات کپچا')
@section('content')
<div class="admin-page max-w-3xl space-y-6">
    <div class="admin-page-head">
        <div>
            <h1 class="admin-page-title">کپچا و ضد ربات <span class="text-xs text-slate-500 font-normal">(آخرین بروزرسانی: ۱۴۰۵/۰۲/۲۲)</span></h1>
            <p class="admin-section-lead text-sm" style="margin-top:0.35rem">کپچای عدد فارسی با نمایش رنگی، چرخش و نویز سبک برای فرم‌ساز، فرم‌های نوشتنی فروشگاه و سایر فرم‌های محافظت‌شده. در کنار کپچا، نقاط نوشتنی فروشگاه با rate limiter (محدودیت نرخ) هم محافظت می‌شوند.</p>
        </div>
    </div>

    <div class="card border-indigo-100 shadow-sm">
        <div class="card-header flex items-center gap-2">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700 text-sm font-bold" aria-hidden="true">?</span>
            پیش‌نمایش ظاهر (نمونه ثابت)
        </div>
        <div class="card-body text-sm text-slate-600 space-y-4">
            <p>در هر بار باز کردن صفحهٔ فرم، کد تازه در نشست ساخته می‌شود. تصویر زیر فقط نمای بصری است.</p>
            <div class="flex flex-wrap gap-10 items-end p-4 rounded-xl bg-slate-50 border border-slate-100">
                <div>
                    <p class="text-xs font-medium text-slate-500 mb-2">کپچای عدد فارسی</p>
                    <x-persian-captcha-challenge code="۷۳۹۲" />
                </div>
                <div>
                    <p class="text-xs font-medium text-slate-500 mb-2">کپچای محاسباتی (فارسی)</p>
                    <x-persian-captcha-math text="۸ + ۴ = ?" />
                </div>
            </div>
            <ul class="list-disc pr-5 text-xs text-slate-500 space-y-1 m-0">
                <li>کاربر می‌تواند پاسخ را با ارقام فارسی یا انگلیسی وارد کند.</li>
                <li>برای امنیت بیشتر در فرم‌های حساس، گزینهٔ hCaptcha را از پایین فعال کنید.</li>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header">تنظیمات</div>
        <div class="card-body">
        <form method="POST" action="{{ route('admin.captcha.update') }}" class="space-y-4">
            @csrf
            <div class="form-group">
                <label class="admin-form-inline-check cursor-pointer">
                    <input type="checkbox" name="captcha_enabled" value="1" class="rounded border-gray-300" {{ $captchaEnabled ? 'checked' : '' }}>
                    <span>فعال‌سازی کپچا برای فرم‌ها (فرم‌ساز و سایر مسیرهای محافظت‌شده)</span>
                </label>
            </div>
            <div class="form-group">
                <label class="admin-form-inline-check cursor-pointer">
                    <input type="checkbox" name="auth_captcha_enabled" value="1" class="rounded border-gray-300" {{ $authCaptchaEnabled ?? false ? 'checked' : '' }}>
                    <span>فعال‌سازی کپچا برای ورود و ثبت‌نام (در صورت پشتیبانی تم)</span>
                </label>
            </div>
            {{-- Phase 5 / Wave 5.3 / P1-5: toggle جدید برای فرم‌های نوشتنی فروشگاه. --}}
            <div class="form-group">
                <label class="admin-form-inline-check cursor-pointer @if(!($shopModuleEnabled ?? false)) opacity-50 cursor-not-allowed @endif">
                    <input type="checkbox" name="shop_captcha_enabled" value="1" class="rounded border-gray-300"
                        @checked($shopCaptchaEnabled ?? false)
                        @disabled(!($shopModuleEnabled ?? false))>
                    <span>
                        فعال‌سازی کپچا برای فرم‌های نوشتنی فروشگاه
                        <span class="text-xs text-slate-500 block mt-0.5">شامل: ثبت سفارش (checkout)، ثبت نظر محصول، درخواست اطلاع‌رسانی موجودی. فقط برای کاربر مهمان اعمال می‌شود؛ کاربر وارد شده bypass می‌شود.</span>
                    </span>
                </label>
                @if(!($shopModuleEnabled ?? false))
                    <p class="text-xs text-amber-700 mt-1">ماژول فروشگاه برای این سایت فعال نیست؛ این گزینه تا فعال شدن ماژول بی‌اثر است.</p>
                @endif
            </div>
            <div class="form-group">
                <label class="form-label" for="captcha-type">نوع کپچا</label>
                <select id="captcha-type" name="captcha_type" class="w-full max-w-md px-3 py-2 border border-slate-300 rounded-lg">
                    <option value="persian_number" {{ $captchaType === 'persian_number' ? 'selected' : '' }}>عدد فارسی (رنگی و آشفتهٔ بصری)</option>
                    <option value="normal" {{ $captchaType === 'normal' ? 'selected' : '' }}>محاسبهٔ ساده با ارقام فارسی</option>
                    <option value="hcaptcha" {{ $captchaType === 'hcaptcha' ? 'selected' : '' }}>hCaptcha</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="hcaptcha-site">کلید سایت hCaptcha (اختیاری)</label>
                <input id="hcaptcha-site" type="text" name="hcaptcha_site_key" class="w-full max-w-md px-3 py-2 border border-slate-300 rounded-lg font-mono text-sm" dir="ltr" value="{{ old('hcaptcha_site_key', $hcaptchaSiteKey) }}" placeholder="site key">
            </div>
            <div class="form-group">
                <label class="form-label" for="hcaptcha-secret">کلید مخفی hCaptcha (اختیاری)</label>
                <input id="hcaptcha-secret" type="password" name="hcaptcha_secret_key" class="w-full max-w-md px-3 py-2 border border-slate-300 rounded-lg font-mono text-sm" dir="ltr" value="{{ old('hcaptcha_secret_key', $hcaptchaSecretKey) }}" placeholder="secret">
            </div>
            @if($captchaType === 'hcaptcha')
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-3 text-sm text-blue-900">
                <strong>نکته:</strong> دامنهٔ سایت (<code dir="ltr">{{ request()->getHost() }}</code>) باید در پنل hCaptcha در «Hostnames» ثبت شود.
            </div>
            @endif
            <div class="admin-form-actions">
                <button type="submit" class="btn btn-primary">ذخیره</button>
            </div>
        </form>
        </div>
    </div>
</div>
@endsection
