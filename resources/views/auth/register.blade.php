@extends('layouts.auth-login')

@section('title', 'ط«ط¨طھâ€Œظ†ط§ظ…')

@section('content')
<div class="auth-login-card">
    <h1>ط³ط§ط®طھ ط­ط³ط§ط¨ ع©ط§ط±ط¨ط±غŒ</h1>
    <p class="auth-login-lead">ط§ط·ظ„ط§ط¹ط§طھ ط²غŒط± ط±ط§ ظ¾ط± ع©ظ†غŒط¯ طھط§ ظˆط§ط±ط¯ ظ¾ظ†ظ„ ع©ط§ط±ط¨ط±غŒ ط´ظˆغŒط¯.</p>

    @if ($errors->any())
        <div class="alert alert-danger text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div>
            <label class="form-label" for="reg-name">ظ†ط§ظ… ظˆ ظ†ط§ظ…â€Œط®ط§ظ†ظˆط§ط¯ع¯غŒ <span style="color:#ef4444">*</span></label>
            <input id="reg-name" type="text" name="name" value="{{ old('name') }}"
                   placeholder="ظ…ط«ط§ظ„: ط¹ظ„غŒ ط±ط¶ط§غŒغŒ"
                   class="form-control" required autocomplete="name">
        </div>

        <div>
            <label class="form-label" for="reg-mobile">ط´ظ…ط§ط±ظ‡ ظ…ظˆط¨ط§غŒظ„ <span style="color:#ef4444">*</span></label>
            <input id="reg-mobile" type="text" name="mobile" value="{{ old('mobile') }}"
                   placeholder="ظ…ط«ط§ظ„: غ°غ¹غ±غ²غ³غ´غµغ¶غ·غ¸غ¹"
                   class="form-control" required dir="rtl" autocomplete="tel"
                   inputmode="numeric">
        </div>


        <div>
            <label class="form-label" for="reg-password">ط±ظ…ط² ط¹ط¨ظˆط± <span style="color:#ef4444">*</span></label>
            <input id="reg-password" type="password" name="password"
                   placeholder="ط­ط¯ط§ظ‚ظ„ غ¶ ع©ط§ط±ط§ع©طھط±"
                   class="form-control" required dir="rtl" autocomplete="new-password">
        </div>

        <div>
            <label class="form-label" for="reg-password-confirm">طھع©ط±ط§ط± ط±ظ…ط² ط¹ط¨ظˆط± <span style="color:#ef4444">*</span></label>
            <input id="reg-password-confirm" type="password" name="password_confirmation"
                   placeholder="ط±ظ…ط² ط¹ط¨ظˆط± ط±ط§ طھع©ط±ط§ط± ع©ظ†غŒط¯"
                   class="form-control" required dir="rtl" autocomplete="new-password">
        </div>

        {{-- ظپغŒظ„ط¯ ظ…ط®ظپغŒ ع©ط¯ ط¯ط¹ظˆطھ --}}
        <input type="hidden" name="ref_code" value="{{ old('ref_code', $refCode ?? '') }}">

        @if(!empty($refCode ?? ''))
        <div style="display:flex;align-items:center;gap:.5rem;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:.625rem;padding:.625rem 1rem;font-size:.8rem;color:#166534">
            <x-core-ui::icon name="check-circle" style="width:16px;height:16px" />
            ع©ط¯ ط¯ط¹ظˆطھ ظپط¹ط§ظ„ ط§ط³طھ â€” ظ¾ط³ ط§ط² ط§ط´طھط±ط§ع© ط§ظˆظ„طŒ ظ‡ط± ط¯ظˆ ط·ط±ظپ ظ¾ط§ط¯ط§ط´ ظ…غŒâ€Œع¯غŒط±غŒط¯!
        </div>
        @endif

        <button type="submit" class="btn btn-primary w-full" style="margin-top:1.25rem">
            ط³ط§ط®طھ ط­ط³ط§ط¨ ظˆ ظˆط±ظˆط¯
        </button>
    </form>
</div>

<div class="auth-login-footer-text">
    ظ‚ط¨ظ„ط§ظ‹ ط«ط¨طھâ€Œظ†ط§ظ… ع©ط±ط¯ظ‡â€Œط§غŒط¯طں
    <a href="{{ route('login') }}">ظˆط±ظˆط¯ ط¨ظ‡ ط­ط³ط§ط¨</a>
</div>
@endsection


