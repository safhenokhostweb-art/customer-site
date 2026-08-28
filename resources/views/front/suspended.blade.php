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

        @php
            $supportPhone    = \App\Models\Setting::get('support_phone', '');
            $supportTelegram = \App\Models\Setting::get('support_telegram', '');
        @endphp

        @if($supportPhone || $supportTelegram)
        <div class="divider"></div>
        <div style="text-align:right;padding:1rem 0 0;">
            <p style="font-size:0.85rem;color:#64748b;margin-bottom:0.75rem;text-align:center;">
                برای راهنمایی با پشتیبانی تماس بگیرید:
            </p>
            <div style="display:flex;gap:0.75rem;justify-content:center;flex-wrap:wrap;">
                @if($supportPhone)
                <a href="tel:{{ $supportPhone }}"
                   style="display:inline-flex;align-items:center;gap:6px;background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;padding:8px 16px;border-radius:10px;font-size:0.85rem;font-weight:600;text-decoration:none;">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                    </svg>
                    {{ $supportPhone }}
                </a>
                @endif
                @if($supportTelegram)
                <a href="https://t.me/{{ ltrim($supportTelegram, '@') }}" target="_blank"
                   style="display:inline-flex;align-items:center;gap:6px;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;padding:8px 16px;border-radius:10px;font-size:0.85rem;font-weight:600;text-decoration:none;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                    </svg>
                    {{ $supportTelegram }}
                </a>
                @endif
            </div>
        </div>
        @endif

        @php
            $suspendedAtRaw = \App\Models\Setting::get('site_lifecycle_changed_at', '');
            $archiveDays    = (int) (\App\Models\Setting::get('lifecycle_archive_after_days', 0) ?: 90);
            $archiveDate    = null;
            if ($suspendedAtRaw && $status === 'suspended') {
                try {
                    $archiveDate = \Carbon\Carbon::parse($suspendedAtRaw)->addDays($archiveDays);
                } catch (\Throwable $e) {
                    $archiveDate = null;
                }
            }
        @endphp

        @if($archiveDate)
        <div class="divider"></div>
        <div style="background:#fef9c3;border:1px solid #fde68a;border-radius:12px;padding:1rem 1.25rem;margin-top:1rem;text-align:right;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:.4rem;">
                <svg width="16" height="16" fill="none" stroke="#b45309" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span style="font-size:.8rem;font-weight:700;color:#92400e;">مهلت بازیابی سایت</span>
            </div>
            <p style="font-size:.82rem;color:#78350f;line-height:1.7;margin:0;">
                در صورت عدم پرداخت، سایت شما در تاریخ
                <strong>{{ \App\Helpers\Jalali::formatFa($archiveDate, 'Y/m/d') }}</strong>
                (@php echo \App\Helpers\NumberHelper::formatFa(max(0, (int) now()->diffInDays($archiveDate, false))) @endphp روز دیگر)
                به‌طور خودکار آرشیو خواهد شد.
                پس از آرشیو، بازیابی اطلاعات با هزینه اضافی امکان‌پذیر است.
            </p>
        </div>
        @else
        <div class="divider"></div>
        <div style="background:#fef9c3;border:1px solid #fde68a;border-radius:12px;padding:.875rem 1.25rem;margin-top:1rem;">
            <p style="font-size:.8rem;color:#92400e;margin:0;line-height:1.7;text-align:center;">
                ⚠️ در صورت عدم پرداخت، سایت شما پس از ۹۰ روز به‌طور خودکار آرشیو خواهد شد.
            </p>
        </div>
        @endif

        <p class="footer-note" style="margin-top:1rem;">
            اگر این پیام به اشتباه نمایش داده می‌شود، با پشتیبانی تماس بگیرید.
        </p>
    </div>
<x-core-ui::payment-connection-warning />
</body>
</html>
