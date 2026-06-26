<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فعال‌سازی مجدد سایت</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        @import url('https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700&display=swap');
        body {
            font-family: 'Vazirmatn', 'Tahoma', sans-serif;
            background: linear-gradient(135deg, #f0f4ff 0%, #e8f0fe 100%);
            min-height: 100vh; display: flex; align-items: center;
            justify-content: center; padding: 1.5rem; direction: rtl;
        }
        .card {
            background: #fff; border-radius: 24px; padding: 3rem 2.5rem;
            max-width: 520px; width: 100%; text-align: center;
            box-shadow: 0 20px 60px rgba(99,102,241,0.12);
        }
        .icon { width: 72px; height: 72px; background: linear-gradient(135deg,#d1fae5,#a7f3d0);
                border-radius: 50%; display: flex; align-items: center;
                justify-content: center; margin: 0 auto 1.5rem; }
        .icon svg { width: 32px; height: 32px; color: #059669; }
        h1 { font-size: 1.4rem; font-weight: 700; color: #1e1b4b; margin-bottom: .75rem; }
        p { color: #64748b; font-size: .9rem; line-height: 1.8; margin-bottom: 1.5rem; }
        .amount-box { background: #f0fdf4; border: 2px solid #bbf7d0;
                      border-radius: 16px; padding: 1.25rem; margin-bottom: 2rem; }
        .amount-label { font-size: .8rem; color: #6b7280; margin-bottom: .4rem; }
        .amount-value { font-size: 2rem; font-weight: 700; color: #065f46; }
        .amount-unit { font-size: .85rem; color: #6b7280; }
        .breakdown { text-align: right; list-style: none; margin-bottom: 1.5rem; }
        .breakdown li { display: flex; justify-content: space-between;
                        padding: .4rem 0; border-bottom: 1px solid #f1f5f9;
                        font-size: .82rem; color: #64748b; }
        .breakdown li:last-child { border-bottom: none; }
        .btn { background: linear-gradient(135deg,#6366f1,#7c3aed); color: white;
               border: none; padding: .85rem 2rem; border-radius: 12px;
               font-size: .95rem; font-weight: 600; cursor: pointer;
               width: 100%; transition: all .2s; }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 8px 24px rgba(99,102,241,.35); }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca;
                       color: #dc2626; padding: .75rem 1rem;
                       border-radius: 10px; margin-bottom: 1rem; font-size: .85rem; }
    </style>
</head>
<body>
<div class="card">
    <div class="icon">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
        </svg>
    </div>

    <h1>فعال‌سازی مجدد سایت</h1>

    @if(session('error'))
        <div class="alert-error">{{ session('error') }}</div>
    @endif

    @if($isFree)
        <p>فعال‌سازی مجدد سایت شما <strong>رایگان</strong> است. روی دکمه کلیک کنید.</p>
        <form method="POST" action="/lifecycle-pay/{{ $invoiceRef }}">
            @csrf
            <button type="submit" class="btn">فعال‌سازی رایگان</button>
        </form>
    @else
        <p>برای فعال‌سازی مجدد سایت خود، مبلغ زیر را پرداخت کنید.</p>

        <div class="amount-box">
            <div class="amount-label">مبلغ قابل پرداخت</div>
            <div class="amount-value">@numFa($fee) <span class="amount-unit">تومان</span></div>
        </div>

        @if(!empty($breakdown))
        <ul class="breakdown">
            @if($breakdown['reactivation'] > 0)
                <li><span>هزینه فعال‌سازی مجدد</span><span>@numFa($breakdown['reactivation']) تومان</span></li>
            @endif
            @if($breakdown['restore'] > 0)
                <li><span>هزینه بازگردانی آرشیو</span><span>@numFa($breakdown['restore']) تومان</span></li>
            @endif
            @if($breakdown['storage'] > 0)
                <li><span>هزینه نگهداری</span><span>@numFa($breakdown['storage']) تومان</span></li>
            @endif
        </ul>
        @endif

        <form method="POST" action="/lifecycle-pay/{{ $invoiceRef }}">
            @csrf
            <button type="submit" class="btn">پرداخت و فعال‌سازی مجدد</button>
        </form>
    @endif
</div>
</body>
</html>
