@extends('admin.layout')
@section('title', 'فاکتور ' . $invoice->invoice_number)
@section('header', 'فاکتور ' . $invoice->invoice_number)
@section('content')
<div class="admin-page max-w-3xl">
    <div class="card">
        <div class="card-header flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <span>جزئیات فاکتور</span>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.invoices.download', $invoice) }}"
                   class="btn btn-secondary btn-sm flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                        <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                    </svg>
                    دانلود PDF
                </a>
                @if($invoice->status === 'pending')
                    <a href="{{ $invoice->payment_link }}" target="_blank" class="btn btn-primary btn-sm">لینک پرداخت</a>
                @endif
            </div>
        </div>
        <div class="card-body space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <span class="text-slate-500 text-sm">مشتری</span>
                    <p class="font-medium">{{ $invoice->customer_name }}</p>
                    <p class="text-sm text-slate-600">{{ $invoice->customer_mobile }}</p>
                    @if($invoice->customer_phone)
                        <p class="text-sm text-slate-600">{{ $invoice->customer_phone }}</p>
                    @endif
                </div>
                <div>
                    <span class="text-slate-500 text-sm">وضعیت</span>
                    <p>
                        @if($invoice->status === 'paid')
                            <span class="badge badge-success">پرداخت شده</span>
                        @elseif($invoice->status === 'cancelled')
                            <span class="badge badge-danger">لغو</span>
                        @else
                            <span class="badge badge-warning">در انتظار پرداخت</span>
                        @endif
                    </p>
                </div>
            </div>
            <div class="table-responsive mt-6">
                <table>
                    <thead>
                        <tr>
                            <th>عنوان</th>
                            <th>قیمت واحد</th>
                            <th>تعداد</th>
                            <th>جمع</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->items as $item)
                            <tr>
                                <td>{{ $item->title }}</td>
                                <td>{{ number_format($item->price) }} تومان</td>
                                <td>{{ $item->quantity }}</td>
                                <td class="font-medium">{{ number_format($item->total) }} تومان</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="flex justify-between items-center pt-4 border-t border-slate-200">
                <span class="font-semibold">جمع کل:</span>
                <span class="text-xl font-bold text-indigo-600">{{ number_format($invoice->total) }} تومان</span>
            </div>
        </div>
    </div>
    <div class="admin-page-tools">
        <a href="{{ route('admin.invoices.index') }}" class="btn btn-secondary">بازگشت به لیست</a>
    </div>
</div>
@endsection
