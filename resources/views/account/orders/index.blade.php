@extends('account.layout')
@section('title', 'سفارشات من')
@section('header', 'سفارشات من')
@section('content')
<div class="account-card">
    <div class="account-card-body overflow-x-auto">
        @if($orders->isEmpty())
            <p class="text-slate-500 text-center py-8">هنوز سفارشی ثبت نشده است.</p>
            <a href="{{ route('shop.index') }}" class="btn btn-primary">مشاهده فروشگاه</a>
        @else
            <table class="w-full">
                <thead>
                    <tr>
                        <th class="text-right py-3 px-4 bg-slate-50 font-semibold text-slate-600">شماره</th>
                        <th class="text-right py-3 px-4 bg-slate-50 font-semibold text-slate-600">تاریخ</th>
                        <th class="text-right py-3 px-4 bg-slate-50 font-semibold text-slate-600">مبلغ</th>
                        <th class="text-right py-3 px-4 bg-slate-50 font-semibold text-slate-600">وضعیت</th>
                        <th class="text-right py-3 px-4 bg-slate-50 font-semibold text-slate-600">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($orders as $order)
                    <tr class="border-b border-slate-100 hover:bg-slate-50">
                        <td class="py-3 px-4"><a href="{{ route('account.orders.show', $order) }}" class="font-medium text-indigo-600">{{ $order->number }}</a></td>
                        <td class="py-3 px-4">{{ \App\Helpers\Jalali::formatFa($order->created_at, 'Y/m/d H:i') }}</td>
                        <td class="py-3 px-4">{{ number_format($order->total) }} تومان</td>
                        <td class="py-3 px-4"><span class="badge badge-warning">{{ $order->status }}</span></td>
                        <td class="py-3 px-4"><a href="{{ route('account.orders.show', $order) }}" class="btn btn-secondary btn-sm">مشاهده</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <div class="mt-4">{{ $orders->links() }}</div>
        @endif
    </div>
</div>
@endsection
