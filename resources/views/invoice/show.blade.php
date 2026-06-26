@extends(\App\Helpers\SiteHelper::view('layout'))
@section('title', 'فاکتور ' . $invoice->invoice_number)
@section('content')
<main>
    <div class="main-inner" style="max-width:760px;margin:0 auto;padding:2rem 1rem;">

        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:0.75rem;">
            <h1 style="font-size:1.4rem;font-weight:700;color:#1e293b;">فاکتور شماره {{ $invoice->invoice_number }}</h1>
            <a href="{{ route('invoice.download', $invoice->invoice_number) }}"
               style="display:inline-flex;align-items:center;gap:0.4rem;padding:0.55rem 1.1rem;background:#4f46e5;color:#fff;border-radius:0.5rem;text-decoration:none;font-size:0.875rem;font-weight:600;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="flex-shrink:0;">
                    <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                    <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                </svg>
                دانلود PDF
            </a>
        </div>

        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:0.75rem;overflow:hidden;">
            <div style="background:#f8fafc;padding:1rem 1.25rem;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.5rem;">
                <span style="font-size:0.875rem;color:#64748b;">مشتری: <strong style="color:#1e293b;">{{ $invoice->customer_name }}</strong></span>
                @if($invoice->status === 'paid')
                    <span style="display:inline-block;padding:3px 10px;background:#dcfce7;color:#166534;border-radius:99px;font-size:0.75rem;font-weight:700;">پرداخت شده</span>
                @elseif($invoice->status === 'cancelled')
                    <span style="display:inline-block;padding:3px 10px;background:#fee2e2;color:#991b1b;border-radius:99px;font-size:0.75rem;font-weight:700;">لغو شده</span>
                @else
                    <span style="display:inline-block;padding:3px 10px;background:#fef9c3;color:#854d0e;border-radius:99px;font-size:0.75rem;font-weight:700;">در انتظار پرداخت</span>
                @endif
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="background:#f1f5f9;">
                            <th style="padding:0.75rem 1rem;text-align:right;font-size:0.8rem;color:#64748b;font-weight:600;">شرح</th>
                            <th style="padding:0.75rem 1rem;text-align:center;font-size:0.8rem;color:#64748b;font-weight:600;">تعداد</th>
                            <th style="padding:0.75rem 1rem;text-align:left;font-size:0.8rem;color:#64748b;font-weight:600;direction:ltr;">قیمت واحد</th>
                            <th style="padding:0.75rem 1rem;text-align:left;font-size:0.8rem;color:#64748b;font-weight:600;direction:ltr;">جمع</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->items as $item)
                        <tr style="border-top:1px solid #f1f5f9;">
                            <td style="padding:0.75rem 1rem;color:#334155;">{{ $item->title }}</td>
                            <td style="padding:0.75rem 1rem;text-align:center;color:#334155;">{{ $item->quantity }}</td>
                            <td style="padding:0.75rem 1rem;text-align:left;direction:ltr;color:#334155;">@numFa($item->price)</td>
                            <td style="padding:0.75rem 1rem;text-align:left;direction:ltr;font-weight:600;color:#1e293b;">@numFa($item->total)</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        @if($invoice->discount && $invoice->discount > 0)
                        <tr style="border-top:1px solid #e2e8f0;background:#f8fafc;">
                            <td colspan="3" style="padding:0.6rem 1rem;text-align:left;font-size:0.875rem;color:#64748b;">تخفیف</td>
                            <td style="padding:0.6rem 1rem;text-align:left;direction:ltr;color:#16a34a;font-weight:600;">- @numFa($invoice->discount)</td>
                        </tr>
                        @endif
                        <tr style="border-top:2px solid #e2e8f0;background:#ede9fe;">
                            <td colspan="3" style="padding:0.85rem 1rem;text-align:left;font-weight:700;color:#1e293b;">جمع کل</td>
                            <td style="padding:0.85rem 1rem;text-align:left;direction:ltr;font-weight:800;color:#4f46e5;font-size:1.05rem;">@numFa($invoice->total) تومان</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div style="margin-top:1.25rem;display:flex;gap:0.75rem;flex-wrap:wrap;">
            @if($invoice->status === 'pending')
                <a href="{{ route('invoice.pay', $invoice->invoice_number) }}"
                   style="display:inline-flex;align-items:center;gap:0.4rem;padding:0.6rem 1.3rem;background:#16a34a;color:#fff;border-radius:0.5rem;text-decoration:none;font-size:0.875rem;font-weight:600;">
                    پرداخت آنلاین
                </a>
            @endif
        </div>

    </div>
</main>
@endsection
