<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پرداخت ناموفق</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Tahoma', sans-serif; background: #fef2f2;
               min-height: 100vh; display: flex; align-items: center;
               justify-content: center; padding: 1.5rem; direction: rtl; }
        .card { background: #fff; border-radius: 20px; padding: 2.5rem 2rem;
                max-width: 480px; width: 100%;
                box-shadow: 0 10px 40px rgba(220,38,38,.12); }
        .icon-wrap { width: 72px; height: 72px; background: #fef2f2; border-radius: 50%;
                     display: flex; align-items: center; justify-content: center;
                     margin: 0 auto 1.25rem; }
        h1 { color: #dc2626; text-align: center; font-size: 1.25rem; margin-bottom: .5rem; }
        .subtitle { color: #64748b; font-size: .875rem; text-align: center;
                    line-height: 1.8; margin-bottom: 1.5rem; }
        .error-box { background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px;
                     padding: .875rem 1rem; margin-bottom: 1.5rem; }
        .error-box p { color: #b91c1c; font-size: .8rem; line-height: 1.7; margin: 0; }
        .steps { background: #f8fafc; border-radius: 12px; padding: 1rem 1.25rem; margin-bottom: 1.5rem; }
        .steps-title { font-size: .8rem; font-weight: 600; color: #475569; margin-bottom: .75rem; }
        .step { display: flex; align-items: flex-start; gap: .625rem; margin-bottom: .5rem; }
        .step:last-child { margin-bottom: 0; }
        .step-num { min-width: 22px; height: 22px; background: #e0e7ff; border-radius: 50%;
                    display: flex; align-items: center; justify-content: center;
                    font-size: .7rem; font-weight: 700; color: #4f46e5; }
        .step p { font-size: .8rem; color: #475569; line-height: 1.6; }
        .actions { display: flex; flex-direction: column; gap: .75rem; }
        .btn-primary { display: flex; align-items: center; justify-content: center; gap: .5rem;
                       background: #4f46e5; color: white; padding: .75rem 1.5rem;
                       border-radius: 10px; text-decoration: none; font-size: .9rem;
                       font-weight: 600; border: none; cursor: pointer; width: 100%; }
        .btn-primary:hover { background: #4338ca; }
        .btn-secondary { display: flex; align-items: center; justify-content: center; gap: .5rem;
                         background: #f1f5f9; color: #475569; padding: .75rem 1.5rem;
                         border-radius: 10px; text-decoration: none; font-size: .875rem;
                         font-weight: 500; border: 1px solid #e2e8f0; }
        .btn-secondary:hover { background: #e2e8f0; }
        .divider { border: none; border-top: 1px solid #f1f5f9; margin: 1.25rem 0; }
        .support { text-align: center; font-size: .8rem; color: #94a3b8; }
        .support a { color: #6366f1; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
<div class="card">
    <div class="icon-wrap">
        <svg width="36" height="36" fill="none" stroke="#dc2626" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" stroke-width="2"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01"/>
        </svg>
    </div>

    <h1>پرداخت ناموفق بود</h1>
    <p class="subtitle">متأسفانه تراکنش شما تکمیل نشد. مبلغی از حساب کسر نشده است.</p>

    @if(isset($message) && $message)
    <div class="error-box">
        <p>{{ $message }}</p>
    </div>
    @endif

    {{-- GAP-L02: Actionable retry guidance --}}
    <div class="steps">
        <p class="steps-title">برای رفع مشکل این مراحل را امتحان کنید:</p>
        <div class="step">
            <div class="step-num">۱</div>
            <p>مطمئن شوید موجودی کارت کافی است و اینترنت‌بانک فعال است.</p>
        </div>
        <div class="step">
            <div class="step-num">۲</div>
            <p>VPN یا فیلترشکن را خاموش کنید و مجدداً پرداخت کنید.</p>
        </div>
        <div class="step">
            <div class="step-num">۳</div>
            <p>از مرورگر دیگری (Chrome یا Firefox) استفاده کنید.</p>
        </div>
        <div class="step">
            <div class="step-num">۴</div>
            <p>اگر مبلغ از حساب کسر شده اما پرداخت ثبت نشد، با پشتیبانی تماس بگیرید.</p>
        </div>
    </div>

    <div class="actions">
        <a href="javascript:history.back()" class="btn-primary">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            تلاش مجدد
        </a>
        <a href="/" class="btn-secondary">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            بازگشت به پنل
        </a>
    </div>

    @php
        $supportPhone    = \App\Models\Setting::get('support_phone', '');
        $supportTelegram = \App\Models\Setting::get('support_telegram', '');
    @endphp
    @if($supportPhone || $supportTelegram)
    <hr class="divider">
    <p class="support">
        نیاز به کمک دارید؟
        @if($supportPhone)
            <a href="tel:{{ $supportPhone }}">{{ $supportPhone }}</a>
        @endif
        @if($supportPhone && $supportTelegram)
            &nbsp;|&nbsp;
        @endif
        @if($supportTelegram)
            <a href="https://t.me/{{ ltrim($supportTelegram, '@') }}" target="_blank">تلگرام پشتیبانی</a>
        @endif
    </p>
    @endif
</div>
</body>
</html>
