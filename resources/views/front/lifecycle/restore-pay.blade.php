<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پرداخت هزینه فعال‌سازی مجدد</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        @import url('https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700&display=swap');
        body {
            font-family: 'Vazirmatn', 'Tahoma', sans-serif;
            background: linear-gradient(135deg, #f0f4ff 0%, #e8f0fe 50%, #f5f0ff 100%);
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            padding: 1.5rem; direction: rtl;
        }
        .card {
            background: #fff; border-radius: 24px; padding: 3rem 2.5rem;
            max-width: 520px; width: 100%; text-align: center;
            box-shadow: 0 20px 60px rgba(99,102,241,0.12), 0 4px 16px rgba(0,0,0,0.06);
        }
        .icon-wrap {
            width: 80px; height: 80px;
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            border-radius: 50%; display: flex; align-items: center;
            justify-content: center; margin: 0 auto 1.5rem;
        }
        .icon-wrap svg { width: 36px; height: 36px; color: #059669; }
        h1 { font-size: 1.4rem; font-weight: 700; color: #1e1b4b; margin-bottom: .75rem; }
        p { color: #64748b; font-size: .9rem; line-height: 1.8; margin-bottom: 1.5rem; }
        .amount-box {
            background: #f0fdf4; border: 2px solid #bbf7d0; border-radius: 16px;
            padding: 1.25rem; margin-bottom: 2rem;
        }
        .amount-label { font-size: .85rem; color: #6b7280; margin-bottom: .5rem; }
        .amount-value { font-size: 2rem; font-weight: 700; color: #065f46; }
        .amount-unit { font-size: .9rem; color: #6b7280; margin-right: .25rem; }
        .items { text-align: right; margin-bottom: 1.5rem; }
        .items li {
            display: flex; justify-content: space-between;
            padding: .5rem 0; border-bottom: 1px solid #f1f5f9;
            font-size: .85rem; color: #475569;
        }
        .items li:last-child { border-bottom: none; }
        .btn {
            background: linear-gradient(135deg, #6366f1, #7c3aed);
            color: white; border: none; padding: .85rem 2rem;
            border-radius: 12px; font-size: .95rem; font-weight: 600;
            cursor: pointer; width: 100%; transition: all .2s;
        }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 8px 24px rgba(99,102,241,.35); }
        .invoice-ref { font-size: .75rem; color: #94a3b8; margin-top: 1rem; }
    </style>
</head>
<body>
<div class="card">
    <div class="icon-wrap">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/>
        </svg>
    </div>

    <h1>پرداخت هزینه فعال‌سازی مجدد</h1>
    <p>برای فعال‌سازی سایت خود، مبلغ زیر را پرداخت کنید.</p>

    <div class="amount-box">
        <div class="amount-label">مبلغ قابل پرداخت</div>
        <div class="amount-value">
            @numFa($amount)
            <span class="amount-unit">تومان</span>
        </div>
    </div>

    @if(!empty($restoreData['line_items']))
    <ul class="items" style="list-style:none;">
        @foreach($restoreData['line_items'] as $item)
        <li>
            <span>{{ $item['label'] }}</span>
            <span>@numFa($item['amount']) تومان</span>
        </li>
        @endforeach
    </ul>
    @endif

    <form method="POST" action="{{ route('account.lifecycle.manual-confirm', $invoice->invoice_number) }}">
        @csrf
        <button type="submit" class="btn">تأیید پرداخت دستی و فعال‌سازی</button>
    </form>

    <p class="invoice-ref">شماره فاکتور: {{ $invoice->invoice_number }}</p>
</div>
</body>
</html>
