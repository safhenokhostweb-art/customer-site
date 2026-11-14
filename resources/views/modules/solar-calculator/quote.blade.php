{{--
    The printable quotation. A standalone document (not the site layout) so
    it prints cleanly on A4 with the locally served Vazirmatn font; «دانلود
    PDF» opens it with ?print=1, which calls the browser's print dialog
    (Save as PDF keeps Persian shaping exact, unlike dompdf).
    Everything shown is the snapshot stored on the lead at issue time.
--}}
@use('SaaSPlatform\ThemeRuntime\Support\PersianNumber')
@use('App\Helpers\Jalali')
@php
    $q = $lead->quote ?? [];
    $company = $q['company'] ?? [];
    $data = $q['data'] ?? null;
    $cards = $lead->results['cards'] ?? [];
    $issued = isset($q['issued_at']) ? \Illuminate\Support\Carbon::parse($q['issued_at']) : $lead->created_at;
    $valid = isset($q['valid_until']) ? \Illuminate\Support\Carbon::parse($q['valid_until']) : null;
    $jalali = static fn ($d) => $d ? PersianNumber::digits(Jalali::format($d, 'Y/m/d')) : '—';
    $types = ['on_grid' => 'متصل به شبکه (On-Grid)', 'off_grid' => 'مستقل از شبکه (Off-Grid)', 'hybrid' => 'هیبرید (Hybrid)'];
    $accent = \App\Modules\SolarCalculator\Canvas\SolarCalculatorWidget::safeColor((string) ($company['accent_color'] ?? '')) ?: '#0f766e';
    $logo = \App\Modules\SolarCalculator\Canvas\SolarCalculatorWidget::safeImageUrl((string) ($company['logo'] ?? ''));
    $usage = ($lead->inputs['consumption_mode'] ?? 'kwh') === 'bill'
        ? PersianNumber::format($lead->inputs['monthly_bill'] ?? 0) . ' تومان (مبلغ قبض ماهانه)'
        : PersianNumber::format($lead->inputs['monthly_kwh'] ?? 0) . ' کیلووات‌ساعت در ماه';
    $rates = collect($lead->exchange_rates ?? [])->filter(fn ($r) => ($r['rate'] ?? 0) > 0);
@endphp
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>پیش‌فاکتور {{ $lead->quote_number }} — {{ $company['name'] ?? '' }}</title>
    <style>@include('partials.vazirmatn-font-faces-inline')</style>
    <style>
        :root{--accent:{{ $accent }};--text:#0f172a;--muted:#64748b;--line:#e2e8f0;--soft:color-mix(in srgb,var(--accent) 8%,#fff)}
        *{box-sizing:border-box}
        body{margin:0;background:#f1f5f9;color:var(--text);font-family:Vazirmatn,Tahoma,sans-serif;font-size:14px;line-height:1.8}
        .toolbar{position:sticky;top:0;z-index:5;display:flex;flex-wrap:wrap;gap:.5rem;justify-content:center;padding:.75rem 1rem;background:#fff;border-bottom:1px solid var(--line)}
        .toolbar button,.toolbar a{display:inline-flex;align-items:center;gap:.4rem;font:inherit;font-weight:700;padding:.55rem 1.1rem;border-radius:.65rem;border:1.5px solid var(--accent);background:var(--accent);color:#fff;cursor:pointer;text-decoration:none}
        .toolbar .ghost{background:#fff;color:var(--accent)}
        .sheet{width:min(210mm,100% - 2rem);margin:1.5rem auto;background:#fff;border-radius:1rem;box-shadow:0 20px 50px -24px rgb(15 23 42/.35);padding:clamp(1.25rem,4vw,2.75rem);position:relative;overflow:hidden}
        .sheet::before{content:"";position:absolute;inset-inline:0;top:0;height:6px;background:var(--accent)}
        header.top{display:flex;flex-wrap:wrap;justify-content:space-between;gap:1rem;align-items:flex-start;padding-bottom:1.25rem;border-bottom:1px solid var(--line)}
        .brand{display:flex;gap:.85rem;align-items:center}
        .brand img{max-height:56px;max-width:160px;object-fit:contain}
        .brand h1{margin:0;font-size:1.25rem;font-weight:900}
        .brand p{margin:0;color:var(--muted);font-size:.8rem}
        .doc{text-align:left}
        .doc .title{font-size:1.35rem;font-weight:900;color:var(--accent);text-align:right}
        .doc dl{display:grid;grid-template-columns:auto auto;gap:.1rem .75rem;margin:.35rem 0 0;font-size:.82rem;text-align:right}
        .doc dt{color:var(--muted)}.doc dd{margin:0;font-weight:700}
        h2{font-size:1rem;font-weight:900;margin:1.5rem 0 .75rem;display:flex;align-items:center;gap:.5rem}
        h2::before{content:"";width:.35rem;height:1.1rem;border-radius:1rem;background:var(--accent)}
        .grid2{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.5rem 1.5rem}
        .kv{display:flex;justify-content:space-between;gap:1rem;border-bottom:1px dashed var(--line);padding:.35rem 0}
        .kv span{color:var(--muted)}.kv b{font-weight:800}
        .specs{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.6rem}
        .spec{border:1px solid var(--line);border-radius:.75rem;padding:.65rem .8rem;background:var(--soft)}
        .spec span{display:block;color:var(--muted);font-size:.75rem}
        .spec b{font-size:1.1rem;font-weight:900}.spec small{color:var(--muted);margin-inline-start:.25rem}
        table{width:100%;border-collapse:collapse;font-size:.85rem}
        th{background:var(--soft);color:var(--text);font-weight:800;text-align:right;padding:.6rem .65rem;border-bottom:2px solid var(--accent)}
        td{padding:.6rem .65rem;border-bottom:1px solid var(--line);vertical-align:top}
        td.num,th.num{text-align:left;white-space:nowrap}
        td small{display:block;color:var(--muted);font-size:.75rem}
        tfoot td{border:0;padding:.4rem .65rem}
        tfoot tr.total td{font-size:1.05rem;font-weight:900;background:var(--soft);border-top:2px solid var(--accent)}
        .unavailable{padding:1rem;border-radius:.75rem;background:#fff7ed;color:#9a3412;font-weight:700}
        .terms{white-space:pre-line;color:var(--muted);font-size:.8rem;margin:0}
        .rates{color:var(--muted);font-size:.75rem;margin-top:.5rem}
        footer.foot{margin-top:2rem;padding-top:1rem;border-top:1px solid var(--line);display:flex;flex-wrap:wrap;justify-content:space-between;gap:1rem;color:var(--muted);font-size:.78rem}
        .sign{min-width:12rem;text-align:center;border-top:1px solid var(--line);padding-top:.5rem;margin-top:2.5rem}
        .currency-faded-zeros{opacity:.4}
        @media (max-width:640px){.grid2,.specs{grid-template-columns:1fr 1fr}.doc{text-align:right}table{font-size:.78rem}}
        @page{size:A4;margin:12mm}
        @media print{body{background:#fff;font-size:12px}.toolbar{display:none}.sheet{width:auto;margin:0;box-shadow:none;border-radius:0;padding:0}.sheet::before{display:none}th,.spec,tfoot tr.total td{-webkit-print-color-adjust:exact;print-color-adjust:exact}tr{break-inside:avoid}}
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">دانلود PDF / چاپ</button>
        @if($admin)
            <a class="ghost" href="{{ route('admin.solar-calculator.leads.show', $lead) }}">بازگشت به درخواست</a>
        @else
            <a class="ghost" href="{{ url('/') }}">بازگشت به سایت</a>
        @endif
    </div>

    <main class="sheet">
        <header class="top">
            <div class="brand">
                @if($logo !== '')<img src="{{ $logo }}" alt="{{ $company['name'] ?? '' }}">@endif
                <div>
                    <h1>{{ $company['name'] ?? '' }}</h1>
                    @if(!empty($company['website']))<p dir="ltr">{{ $company['website'] }}</p>@endif
                </div>
            </div>
            <div class="doc">
                <div class="title">پیش‌فاکتور سیستم خورشیدی</div>
                <dl>
                    <dt>شمارهٔ پیش‌فاکتور</dt><dd dir="ltr">{{ $lead->quote_number }}</dd>
                    <dt>تاریخ صدور</dt><dd>{{ $jalali($issued) }}</dd>
                    @if($valid)<dt>معتبر تا</dt><dd>{{ $jalali($valid) }}</dd>@endif
                </dl>
            </div>
        </header>

        <h2>مشخصات درخواست‌دهنده</h2>
        <div class="grid2">
            <div class="kv"><span>نام</span><b>{{ $lead->name }}</b></div>
            <div class="kv"><span>موبایل</span><b dir="ltr">{{ PersianNumber::digits($lead->mobile) }}</b></div>
            <div class="kv"><span>استان محل نصب</span><b>{{ $regionLabel }}</b></div>
            <div class="kv"><span>نوع سیستم</span><b>{{ $types[$lead->system_type] ?? $lead->system_type }}</b></div>
            <div class="kv"><span>مصرف اعلام‌شده</span><b>{{ $usage }}</b></div>
            @if(!empty($lead->email))<div class="kv"><span>ایمیل</span><b dir="ltr">{{ $lead->email }}</b></div>@endif
        </div>

        @if($cards !== [])
            <h2>مشخصات فنی سیستم پیشنهادی</h2>
            <div class="specs">
                @foreach($cards as $card)
                    <div class="spec"><span>{{ $card['label'] }}</span><b>{{ $card['display'] }}</b><small>{{ $card['unit'] }}</small></div>
                @endforeach
            </div>
        @endif

        <h2>لیست تجهیزات و برآورد هزینه</h2>
        @if($data)
            <table>
                <thead>
                    <tr><th style="width:2.5rem">#</th><th>شرح</th><th class="num">تعداد</th><th class="num">مبلغ واحد ({{ \App\Helpers\CurrencyHelper::unitLabel() }})</th><th class="num">مبلغ کل ({{ \App\Helpers\CurrencyHelper::unitLabel() }})</th></tr>
                </thead>
                <tbody>
                    @foreach($data['lines'] as $i => $line)
                        <tr>
                            <td>{{ PersianNumber::digits($i + 1) }}</td>
                            <td>{{ $line['label'] }}@if($line['detail'] !== '')<small>{{ $line['detail'] }}</small>@endif</td>
                            <td class="num">{{ $line['qty_display'] }} {{ $line['qty_unit'] }}</td>
                            <td class="num">{!! $line['unit_price_html'] !!}</td>
                            <td class="num">{!! $line['amount_html'] !!}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    @if(!empty($data['subtotal_html']))<tr><td colspan="4">جمع کل</td><td class="num">{!! $data['subtotal_html'] !!}</td></tr>@endif
                    @if(!empty($data['tax_html']))<tr><td colspan="4">{{ $data['tax_label'] }}</td><td class="num">{!! $data['tax_html'] !!}</td></tr>@endif
                    <tr class="total"><td colspan="4">مبلغ قابل پرداخت</td><td class="num">{!! $data['total_html'] !!} {{ \App\Helpers\CurrencyHelper::unitLabel() }}</td></tr>
                </tfoot>
            </table>
            @if($rates->isNotEmpty())
                <p class="rates">نرخ ارز مبنای محاسبه:
                    @foreach($rates as $code => $r){{ $code }} = {{ PersianNumber::format($r['rate']) }} تومان@if(!$loop->last)، @endif @endforeach
                </p>
            @endif
        @else
            <p class="unavailable">قیمت به‌روز تجهیزات پس از تأیید نرخ ارز توسط کارشناسان ما اعلام می‌شود.</p>
        @endif

        @if(!empty($company['terms']))
            <h2>شرایط و توضیحات</h2>
            <p class="terms">{{ $company['terms'] }}</p>
        @endif

        <footer class="foot">
            <div>
                @if(!empty($company['address']))<div>{{ $company['address'] }}</div>@endif
                @if(!empty($company['phone']))<div>تلفن: <span dir="ltr">{{ PersianNumber::digits($company['phone']) }}</span></div>@endif
                @if(!empty($company['email']))<div dir="ltr">{{ $company['email'] }}</div>@endif
            </div>
            <div class="sign">مهر و امضای {{ $company['name'] ?? '' }}</div>
        </footer>
    </main>

    @if($autoPrint)
        <script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });</script>
    @endif
</body>
</html>
