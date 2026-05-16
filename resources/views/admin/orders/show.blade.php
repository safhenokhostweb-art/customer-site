@extends('admin.layout')
@section('title', 'سفارشات')
@section('header', 'سفارشات')
@section('content')

@include('admin.partials.breadcrumb', ['crumbs' => [
    ['label' => 'داشبورد', 'url' => route('admin.dashboard')],
    ['label' => 'سفارشات', 'url' => null],
]])

@include('admin.orders.guide')

<div class="admin-page">
    <div class="admin-page-head">
        <h1 class="admin-page-title">سفارشات</h1>
    </div>
    <div class="card">
        <div class="table-responsive">
            <table>
                <thead><tr><th>شماره</th><th>مشتری</th><th>مبلغ</th><th>وضعیت</th><th>تاریخ</th><th></th></tr></thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td>{{ $order->number }}</td>
                            <td>{{ $order->customer_name }}</td>

                            <td>{{ \App\Helpers\NumberHelper::formatFa($order->total) }} تومان</td>
                            <td>{{ $order->status }}</td>
                            <td>{{ \App\Helpers\Jalali::formatFa($order->created_at, 'Y/m/d H:i') }}</td>
                            <td><a href="{{ route('admin.orders.show', $order) }}" class="btn btn-primary btn-sm">مشاهده</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="admin-empty">سفارشی یافت نشد.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if(method_exists($orders, 'links'))
        <div class="admin-pagination">{{ $orders->links() }}</div>
    @endif
</div>
@endsection
