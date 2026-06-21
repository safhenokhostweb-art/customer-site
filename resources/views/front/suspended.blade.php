<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سایت موقتاً غیرفعال</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Vazirmatn', 'Tahoma', 'Arial', sans-serif;
            background: linear-gradient(135deg, #f0f4ff 0%, #e8f0fe 50%, #f5f0ff 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            direction: rtl;
        }

        @import url('https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700&display=swap');

        .card {
            background: #ffffff;
            border-radius: 24px;
            padding: 3rem 2.5rem;
            max-width: 520px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(99, 102, 241, 0.12), 0 4px 16px rgba(0,0,0,0.06);
        }

        .icon-wrap {
            width: 88px;
            height: 88px;
            background: linear-gradient(135deg, #eef2ff, #e0e7ff);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.75rem;
        }

        .icon-wrap svg {
            width: 40px;
            height: 40px;
            color: #6366f1;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #fef3c7;
            color: #92400e;
            padding: 6px 14px;
            border-radius: 100px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 1.5rem;
            border: 1px solid #fde68a;
        }

        .badge svg { width: 14px; height: 14px; }

        h1 {
            font-size: 1.6rem;
            font-weight: 700;
            color: #1e1b4b;
            margin-bottom: 1rem;
            line-height: 1.4;
        }

        h1 span.site-name {
            color: #6366f1;
        }

        p {
            color: #64748b;
            font-size: 0.95rem;
            line-height: 1.8;
            margin-bottom: 2rem;
        }

        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, #e2e8f0, transparent);
            margin: 1.5rem 0;
        }

        .actions {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-primary {
            background: linear-gradient(135deg, #6366f1, #7c3aed);
            color: white;
            border: none;
            padding: 0.75rem 1.75rem;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(99,102,241,0.35);
        }

        .btn-secondary {
            background: transparent;
            color: #6366f1;
            border: 1.5px solid #c7d2fe;
            padding: 0.75rem 1.75rem;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .btn-secondary:hover {
            background: #eef2ff;
        }

        .footer-note {
            margin-top: 2rem;
            font-size: 0.8rem;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="card">

        <div class="icon-wrap">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" color="#6366f1">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
            </svg>
        </div>

        <div class="badge">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            موقتاً غیرفعال
        </div>

        <h1>
            سایت <span class="site-name">{{ $siteName }}</span><br>
            موقتاً غیرفعال شده است.
        </h1>

        <p>
            این سایت به دلیل انقضای اشتراک موقتاً از دسترس خارج شده است.<br>
            اگر مالک این سایت هستید، از طریق دکمه‌های زیر سایت خود را فعال‌سازی مجدد کنید.
        </p>

        <div class="divider"></div>

        @auth
        {{-- C04: اگر کاربر لاگین است، دکمه فعال‌سازی مستقیم نمایش داده می‌شود --}}
        <div class="actions" style="flex-direction:column; gap:1rem; align-items:center;">
            <form method="POST" action="/account/lifecycle/restore" style="width:100%;">
                @csrf
                <button type="submit" class="btn-primary" style="width:100%;">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    فعال‌سازی مجدد سایت
                </button>
            </form>

            <a href="/account/subscriptions/plans" class="btn-secondary" style="width:100%;justify-content:center;">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                تمدید اشتراک
            </a>
        </div>
        @else
        {{-- C04: اگر کاربر لاگین نیست، لینک ورود نمایش داده می‌شود --}}
        <div class="actions">
            <a href="/login?redirect=lifecycle-restore" class="btn-primary">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                ورود و فعال‌سازی مجدد
            </a>
            <a href="/login" class="btn-secondary">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                ورود به پنل کاربری
            </a>
        </div>
        @endauth

        <p class="footer-note">
            اگر این پیام به اشتباه نمایش داده می‌شود، با پشتیبانی تماس بگیرید.
        </p>
    </div>
</body>
</html>
