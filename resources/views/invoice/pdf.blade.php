<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "DejaVu Sans", "dejavu sans", sans-serif;
            font-size: 13px;
            color: #1e293b;
            direction: rtl;
            background: #fff;
        }
        .page {
            padding: 40px 44px;
        }
        /* Header */
        .header {
            display: table;
            width: 100%;
            border-bottom: 3px solid #4f46e5;
            padding-bottom: 20px;
            margin-bottom: 28px;
        }
        .header-logo {
            display: table-cell;
            vertical-align: middle;
        }
        .header-logo .site-name {
            font-size: 22px;
            font-weight: bold;
            color: #4f46e5;
        }
        .header-logo .site-tagline {
            font-size: 11px;
            color: #64748b;
            margin-top: 3px;
        }
        .header-invoice {
            display: table-cell;
            vertical-align: middle;
            text-align: left;
            direction: ltr;
        }
        .invoice-title {
            font-size: 18px;
            font-weight: bold;
            color: #1e293b;
        }
        .invoice-number {
            font-size: 13px;
            color: #64748b;
            margin-top: 4px;
        }
        /* Status badge */
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            margin-top: 6px;
        }
        .status-paid { background: #dcfce7; color: #166534; }
        .status-pending { background: #fef9c3; color: #854d0e; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        /* Info section */
        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 24px;
        }
        .info-box {
            display: table-cell;
            width: 48%;
            vertical-align: top;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 14px 16px;
        }
        .info-box + .info-box {
            margin-right: 4%;
        }
        .info-label {
            font-size: 10px;
            color: #94a3b8;
            margin-bottom: 6px;
            text-transform: uppercase;
        }
        .info-value {
            font-size: 13px;
            color: #1e293b;
            font-weight: bold;
        }
        .info-sub {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
        }
        /* Table */
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.items thead tr {
            background: #4f46e5;
            color: #fff;
        }
        table.items thead th {
            padding: 10px 12px;
            font-size: 12px;
            font-weight: bold;
            text-align: right;
        }
        table.items tbody tr:nth-child(even) {
            background: #f8fafc;
        }
        table.items tbody tr:nth-child(odd) {
            background: #fff;
        }
        table.items tbody td {
            padding: 9px 12px;
            font-size: 12px;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
        }
        table.items tfoot tr {
            background: #f1f5f9;
        }
        table.items tfoot td {
            padding: 9px 12px;
            font-size: 12px;
        }
        /* Totals */
        .totals {
            width: 280px;
            margin-right: auto;
            margin-left: 0;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 24px;
        }
        .total-row {
            display: table;
            width: 100%;
        }
        .total-row-label {
            display: table-cell;
            padding: 9px 14px;
            font-size: 12px;
            color: #64748b;
            background: #f8fafc;
        }
        .total-row-value {
            display: table-cell;
            padding: 9px 14px;
            font-size: 12px;
            color: #1e293b;
            text-align: left;
            direction: ltr;
        }
        .total-row.grand .total-row-label {
            font-size: 13px;
            font-weight: bold;
            color: #1e293b;
            background: #ede9fe;
        }
        .total-row.grand .total-row-value {
            font-size: 15px;
            font-weight: bold;
            color: #4f46e5;
            background: #ede9fe;
        }
        /* Notes */
        .notes-box {
            background: #fafafa;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 14px;
            margin-bottom: 24px;
        }
        .notes-title {
            font-size: 11px;
            font-weight: bold;
            color: #64748b;
            margin-bottom: 6px;
        }
        .notes-text {
            font-size: 12px;
            color: #475569;
            line-height: 1.6;
        }
        /* Footer */
        .footer {
            border-top: 1px solid #e2e8f0;
            padding-top: 14px;
            text-align: center;
            font-size: 10px;
            color: #94a3b8;
        }
        .paid-stamp {
            position: absolute;
            top: 120px;
            left: 44px;
            width: 110px;
            height: 110px;
            border: 4px solid #16a34a;
            border-radius: 50%;
            text-align: center;
            padding-top: 28px;
            color: #16a34a;
            font-size: 16px;
            font-weight: bold;
            opacity: 0.25;
            transform: rotate(-20deg);
        }
    </style>
</head>
<body>
<div class="page">

    {{-- Header --}}
    <div class="header">
        <div class="header-logo">
            <div class="site-name">{{ \App\Helpers\SiteHelper::siteName() }}</div>
            <div class="site-tagline">فاکتور رسمی</div>
        </div>
        <div class="header-invoice">
            <div class="invoice-title">INVOICE</div>
            <div class="invoice-number"># {{ $invoice->invoice_number }}</div>
        </div>
    </div>

    @if($invoice->status === 'paid')
        <div class="paid-stamp">پرداخت<br>شده</div>
    @endif

    {{-- Info boxes --}}
    <div class="info-section">
        <div class="info-box">
            <div class="info-label">مشتری</div>
            <div class="info-value">{{ $invoice->customer_name }}</div>
            @if($invoice->customer_mobile)
                <div class="info-sub">{{ $invoice->customer_mobile }}</div>
            @endif
            @if($invoice->customer_phone)
                <div class="info-sub">{{ $invoice->customer_phone }}</div>
            @endif
        </div>
        <div class="info-box" style="margin-right: 4%;">
            <div class="info-label">اطلاعات فاکتور</div>
            <div class="info-sub">تاریخ صدور: {{ \App\Helpers\Jalali::formatFa($invoice->created_at, 'Y/m/d') }}</div>
            @if($invoice->paid_at)
                <div class="info-sub">تاریخ پرداخت: {{ \App\Helpers\Jalali::formatFa($invoice->paid_at, 'Y/m/d') }}</div>
            @endif
            <div style="margin-top:6px;">
                @if($invoice->status === 'paid')
                    <span class="status-badge status-paid">پرداخت شده</span>
                @elseif($invoice->status === 'cancelled')
                    <span class="status-badge status-cancelled">لغو شده</span>
                @else
                    <span class="status-badge status-pending">در انتظار پرداخت</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Items table --}}
    <table class="items">
        <thead>
            <tr>
                <th style="width:50%;">شرح خدمت / کالا</th>
                <th style="width:20%; text-align:center;">تعداد</th>
                <th style="width:15%; text-align:left; direction:ltr;">قیمت واحد</th>
                <th style="width:15%; text-align:left; direction:ltr;">جمع</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->title }}</td>
                    <td style="text-align:center;">{{ $item->quantity }}</td>
                    <td style="text-align:left; direction:ltr;">@numFa($item->price)</td>
                    <td style="text-align:left; direction:ltr;">@numFa($item->total)</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals --}}
    <div class="totals">
        @if($invoice->subtotal && $invoice->discount && $invoice->discount > 0)
            <div class="total-row">
                <div class="total-row-label">جمع جزء</div>
                <div class="total-row-value">@numFa($invoice->subtotal) تومان</div>
            </div>
            <div class="total-row">
                <div class="total-row-label">تخفیف</div>
                <div class="total-row-value" style="color: #16a34a;">- @numFa($invoice->discount) تومان</div>
            </div>
        @endif
        <div class="total-row grand">
            <div class="total-row-label">مبلغ قابل پرداخت</div>
            <div class="total-row-value">@numFa($invoice->total) تومان</div>
        </div>
    </div>

    @if($invoice->notes)
        <div class="notes-box">
            <div class="notes-title">یادداشت</div>
            <div class="notes-text">{{ $invoice->notes }}</div>
        </div>
    @endif

    {{-- Footer --}}
    <div class="footer">
        این فاکتور توسط سیستم {{ \App\Helpers\SiteHelper::siteName() }} صادر شده است.
        &nbsp;|&nbsp;
        فاکتور شماره {{ $invoice->invoice_number }}
    </div>

</div>
</body>
</html>
