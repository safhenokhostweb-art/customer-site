{{--
    Phase 5 / Wave 5.3 / P1-5: کپچا برای فرم‌های نوشتنی فروشگاه (checkout/review/stock-notify).
    این partial:
      • تنظیمات کپچا را در همان لحظه از `CaptchaService::getShopCaptchaConfig()` می‌خواند
        (نیازی به پاس‌دادن متغیر از controller نیست).
      • برای کاربر authenticated همیشه خروجی خالی می‌دهد (bypass).
      • برای کاربر مهمان اگر ادمین `shop_captcha_enabled=1` کرده باشد، یکی از سه نوع
        کپچا (persian_number / normal / hcaptcha) را با همان UI آشنای auth-captcha
        رندر می‌کند تا تجربهٔ کاربری یکپارچه بماند.
    استفاده: @include('partials.shop-captcha')
--}}
@php
    $captcha = app(\App\Services\CaptchaService::class)->getShopCaptchaConfig();
@endphp
@if(!empty($captcha['enabled'] ?? false))
<div class="shop-captcha-block" style="margin: 0.5rem 0;">
    <label class="form-label" style="display:block; font-weight:600; margin-bottom:0.35rem;">کپچا</label>
    <p style="font-size:0.75rem; color:#64748b; margin:0 0 0.5rem; line-height:1.4;">
        برای جلوگیری از ارسال خودکار، لطفاً کپچا را تکمیل کنید.
    </p>
    @if(($captcha['type'] ?? '') === 'persian_number' && !empty($captcha['persian_code']))
        <div class="shop-captcha-row" style="display:flex; flex-wrap:wrap; align-items:center; gap:0.65rem;">
            <span aria-hidden="true" style="display:inline-flex; align-items:center; font-size:1.35rem; font-weight:800; letter-spacing:0.15em; color:#0f172a; background:linear-gradient(180deg,#f1f5f9,#e2e8f0); border:1px solid #cbd5e1; border-radius:0.65rem; padding:0.5rem 0.85rem; user-select:none; font-variant-numeric:tabular-nums;">{{ $captcha['persian_code'] }}</span>
            <input type="text" name="captcha_answer" class="form-control" placeholder="عدد بالا را وارد کنید" maxlength="6" required autocomplete="off" inputmode="numeric" style="max-width:11rem; min-width:7rem;">
        </div>
    @elseif(($captcha['type'] ?? '') === 'normal' && !empty($captcha['normal_text']))
        <div class="shop-captcha-row" style="display:flex; flex-wrap:wrap; align-items:center; gap:0.65rem;">
            <span aria-hidden="true" style="display:inline-flex; align-items:center; font-size:1.1rem; font-weight:800; letter-spacing:0.02em; color:#0f172a; background:linear-gradient(180deg,#f1f5f9,#e2e8f0); border:1px solid #cbd5e1; border-radius:0.65rem; padding:0.5rem 0.85rem; user-select:none;">{{ $captcha['normal_text'] }}</span>
            <input type="text" name="captcha_answer" class="form-control" placeholder="جواب" maxlength="4" required autocomplete="off" inputmode="numeric" style="max-width:11rem; min-width:7rem;">
        </div>
    @elseif(($captcha['type'] ?? '') === 'hcaptcha' && !empty($captcha['hcaptcha_site_key']))
        <div class="h-captcha" data-sitekey="{{ $captcha['hcaptcha_site_key'] }}" data-hl="fa"></div>
        <script src="https://hcaptcha.com/1/api.js" async defer></script>
    @endif
    @error('captcha_answer')
        <div style="color:#b91c1c; font-size:0.8125rem; margin-top:0.35rem;">{{ $message }}</div>
    @enderror
</div>
@endif
