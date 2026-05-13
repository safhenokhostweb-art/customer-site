@extends('admin.layout')
@section('title', 'سفارش ' . $order->number)
@section('content')
<h1>سفارش {{ $order->number }}</h1>
<p><strong>مشتری:</strong> {{ $order->customer_name }} — {{ $order->customer_email }} — {{ $order->customer_phone }}</p>
<p><strong>آدرس:</strong> {{ $order->customer_address }}</p>
<p><strong>وضعیت:</strong> {{ $order->status }}</p>

<form action="{{ route('admin.orders.update-status', $order) }}" method="post" style="margin:1rem 0;">
    @csrf
    @method('PATCH')
    <label>تغییر وضعیت</label>
    <select name="status">
        <option value="pending" {{ $order->status === 'pending' ? 'selected' : '' }}>در انتظار</option>
        <option value="paid" {{ $order->status === 'paid' ? 'selected' : '' }}>پرداخت شده</option>
        <option value="processing" {{ $order->status === 'processing' ? 'selected' : '' }}>در حال پردازش</option>
        <option value="shipped" {{ $order->status === 'shipped' ? 'selected' : '' }}>ارسال شده</option>
        <option value="completed" {{ $order->status === 'completed' ? 'selected' : '' }}>تکمیل شده</option>
        <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>لغو شده</option>
    </select>
    <label style="display:block;margin-top:0.4rem">دلیل تغییر (اختیاری)</label>
    <input type="text" name="reason" maxlength="255" placeholder="مثلاً: پرداخت توسط مشتری انجام شد" style="width:100%;max-width:520px">
    <div style="margin-top:0.4rem"><button type="submit" class="btn btn-primary">بروزرسانی</button></div>
</form>

@if($order->statusHistory && $order->statusHistory->count())
    <h2 style="margin-top:1.4rem;font-size:1.05rem">تاریخچهٔ وضعیت (شمسی)</h2>
    <table>
        <thead><tr><th>زمان</th><th>از وضعیت</th><th>به وضعیت</th><th>توسط</th><th>دلیل</th></tr></thead>
        <tbody>
        @foreach($order->statusHistory as $h)
            <tr>
                <td>{{ \App\Helpers\Jalali::formatFa($h->created_at, 'Y/m/d H:i') }}</td>
                <td>{{ $h->from_status ?? '—' }}</td>
                <td>{{ $h->to_status }}</td>
                <td>{{ $h->changedBy?->name ?? '—' }}</td>
                <td>{{ $h->reason ?? '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

<table>
    <thead><tr><th>محصول</th><th>قیمت</th><th>تعداد</th><th>جمع</th></tr></thead>
    <tbody>
        @foreach($order->items as $item)
            <tr>
                <td>{{ $item->product_title }}</td>
                <td>{{ number_format($item->price) }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ number_format($item->total) }} تومان</td>
            </tr>
        @endforeach
    </tbody>
</table>
<p><strong>جمع کل:</strong> {{ number_format($order->total) }} تومان</p>
@if($order->discount_amount > 0)
    <p><strong>جمع سبد:</strong> {{ number_format($order->subtotal) }} تومان</p>
    <p><strong>تخفیف:</strong> {{ number_format($order->discount_amount) }} تومان @if($order->coupon) (کد: {{ $order->coupon->code }}) @endif</p>
@endif
<a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">بازگشت</a>
@endsection
