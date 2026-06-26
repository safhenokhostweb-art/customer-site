@extends(\App\Helpers\SiteHelper::view('layout'))
@section('title', 'پرداخت فاکتور ' . $invoice->invoice_number)
@section('content')
<main>
    <div class="main-inner">
        <h1>پرداخت فاکتور {{ $invoice->invoice_number }}</h1>
        <p>مبلغ قابل پرداخت: <strong>@numFa($invoice->total) تومان</strong></p>
        <p>درگاه پرداخت اینجا یکپارچه می‌شود.</p>
    </div>
</main>
@endsection
