@extends(\App\Helpers\SiteHelper::view('layout'))
@section('title', 'فاکتور ' . $invoice->invoice_number)
@section('content')
<main>
    <div class="main-inner">
        <h1>فاکتور {{ $invoice->invoice_number }}</h1>
        <p>مشتری: {{ $invoice->customer_name }}</p>
        <table class="card" style="margin-top:1rem;">
            <thead><tr><th>عنوان</th><th>قیمت</th><th>تعداد</th><th>جمع</th></tr></thead>
            <tbody>
                @foreach($invoice->items as $item)
                    <tr><td>{{ $item->title }}</td><td>{{ \App\Helpers\NumberHelper::formatFa($item->price) }}</td><td>{{ $item->quantity }}</td><td>{{ \App\Helpers\NumberHelper::formatFa($item->total) }}</td></tr>
                @endforeach
            </tbody>
        </table>
        <p style="margin-top:1rem;"><strong>جمع کل: {{ \App\Helpers\NumberHelper::formatFa($invoice->total) }} تومان</strong></p>
        @if($invoice->status === 'pending')
            <a href="{{ route('invoice.pay', $invoice->invoice_number) }}" class="btn btn-primary" style="display:inline-block;margin-top:1rem;padding:0.75rem 1.5rem;background:#4f46e5;color:#fff;border-radius:0.5rem;text-decoration:none;">پرداخت</a>
        @elseif($invoice->status === 'paid')
            <p style="color:#166534;">این فاکتور پرداخت شده است.</p>
        @endif
    </div>
</main>
@endsection
