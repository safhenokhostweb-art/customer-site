@if(!empty($captcha['enabled'] ?? false))
<div class="auth-captcha-block">
    <label class="form-label">کپچا</label>
    <p class="auth-captcha-hint">در صورت خطا صفحه را یک‌بار بروز کنید و دوباره تلاش کنید.</p>
    @if(($captcha['type'] ?? '') === 'persian_number' && !empty($captcha['persian_code']))
        <div class="auth-captcha-row">
            <span class="auth-captcha-code" aria-hidden="true">{{ $captcha['persian_code'] }}</span>
            <input type="text" name="captcha_answer" class="form-control auth-captcha-input" placeholder="عدد بالا را وارد کنید" maxlength="6" required autocomplete="off" inputmode="numeric">
        </div>
    @elseif(($captcha['type'] ?? '') === 'normal' && !empty($captcha['normal_text']))
        <div class="auth-captcha-row">
            <span class="auth-captcha-code auth-captcha-code--expr" aria-hidden="true">{{ $captcha['normal_text'] }}</span>
            <input type="text" name="captcha_answer" class="form-control auth-captcha-input" placeholder="جواب" maxlength="4" required autocomplete="off" inputmode="numeric">
        </div>
    @elseif(($captcha['type'] ?? '') === 'hcaptcha' && !empty($captcha['hcaptcha_site_key']))
        <div class="h-captcha" data-sitekey="{{ $captcha['hcaptcha_site_key'] }}" data-hl="fa"></div>
    @endif
    @error('captcha')
        <div class="auth-captcha-error">{{ $message }}</div>
    @enderror
</div>
<style>
.auth-captcha-block { margin-top: 0.25rem; }
.auth-captcha-hint { font-size: 0.75rem; color: #64748b; margin: 0 0 0.5rem; line-height: 1.4; }
.auth-captcha-row { display: flex; flex-wrap: wrap; align-items: center; gap: 0.65rem; }
.auth-captcha-code {
    display: inline-flex;
    align-items: center;
    font-size: 1.35rem;
    font-weight: 800;
    letter-spacing: 0.15em;
    color: #0f172a;
    background: linear-gradient(180deg, #f1f5f9, #e2e8f0);
    border: 1px solid #cbd5e1;
    border-radius: 0.65rem;
    padding: 0.5rem 0.85rem;
    user-select: none;
    font-variant-numeric: tabular-nums;
}
.auth-captcha-code--expr { font-size: 1.1rem; letter-spacing: 0.02em; }
.auth-captcha-input { max-width: 11rem; flex: 1; min-width: 7rem; }
.auth-captcha-error { color: #b91c1c; font-size: 0.8125rem; margin-top: 0.35rem; }
</style>
@endif
