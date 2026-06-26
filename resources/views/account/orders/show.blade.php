@extends('account.layout')
@section('title', 'سفارش ' . $order->number)
@section('header', 'جزئیات سفارش ' . $order->number)

@section('content')
<div class="space-y-6">
    <div class="account-card">
        <div class="account-card-header">اطلاعات سفارش</div>
        <div class="account-card-body grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><span class="text-slate-500">شماره:</span> <strong>{{ $order->number }}</strong></div>
            <div><span class="text-slate-500">تاریخ:</span> {{ \App\Helpers\Jalali::formatFa($order->created_at, 'Y/m/d H:i') }}</div>
            <div><span class="text-slate-500">وضعیت:</span>
                @php
                    $statusLabels = ['pending' => 'در انتظار', 'paid' => 'پرداخت شده', 'processing' => 'در حال پردازش', 'shipped' => 'ارسال شده', 'completed' => 'تکمیل شده', 'cancelled' => 'لغو شده'];
                    $statusClass = in_array($order->status, ['completed', 'paid']) ? 'badge-success' : (in_array($order->status, ['cancelled']) ? 'badge-danger' : 'badge-warning');
                @endphp
                <span class="badge {{ $statusClass }}">{{ $statusLabels[$order->status] ?? $order->status }}</span>
            </div>
            <div><span class="text-slate-500">جمع کل:</span> <strong>@numFa($order->total) تومان</strong></div>
        </div>
    </div>

    <div class="account-card">
        <div class="account-card-header">اقلام سفارش</div>
        <div class="account-card-body overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr>
                        <th class="text-right py-3 px-4 bg-slate-50 font-semibold text-slate-600">محصول</th>
                        <th class="text-right py-3 px-4 bg-slate-50 font-semibold text-slate-600">قیمت</th>
                        <th class="text-right py-3 px-4 bg-slate-50 font-semibold text-slate-600">تعداد</th>
                        <th class="text-right py-3 px-4 bg-slate-50 font-semibold text-slate-600">جمع</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                        <tr class="border-b border-slate-100">
                            <td class="py-3 px-4">{{ $item->product_title }}</td>
                            <td class="py-3 px-4">@numFa($item->price) تومان</td>
                            <td class="py-3 px-4">{{ $item->quantity }}</td>
                            <td class="py-3 px-4">@numFa($item->total) تومان</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-4 flex justify-between items-center">
                <span class="text-slate-500">جمع کل:</span>
                <strong class="text-lg">@numFa($order->total) تومان</strong>
            </div>
        </div>
    </div>

    <div class="flex gap-2">
        <a href="{{ route('account.orders.index') }}" class="btn btn-secondary">بازگشت به لیست</a>
        <button type="button" onclick="window.print()" class="btn btn-primary">چاپ / ذخیره PDF</button>
    </div>
</div>
@endsection
