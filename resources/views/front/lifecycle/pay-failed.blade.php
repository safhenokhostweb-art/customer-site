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
        .card { background: #fff; border-radius: 20px; padding: 3rem 2rem;
                max-width: 420px; width: 100%; text-align: center;
                box-shadow: 0 10px 40px rgba(220,38,38,.12); }
        h1 { color: #dc2626; margin-bottom: 1rem; }
        p { color: #64748b; font-size: .9rem; line-height: 1.8; margin-bottom: 1.5rem; }
        a { display: inline-block; background: #6366f1; color: white;
            padding: .75rem 1.5rem; border-radius: 10px; text-decoration: none;
            font-size: .9rem; font-weight: 600; }
    </style>
</head>
<body>
<div class="card">
    <h1>پرداخت ناموفق</h1>
    <p>{{ $message ?? 'پرداخت انجام نشد. لطفاً دوباره تلاش کنید.' }}</p>
    <a href="javascript:history.back()">تلاش مجدد</a>
</div>
</body>
</html>
