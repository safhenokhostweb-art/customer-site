@extends('admin.layout')
@section('title', 'ویرایش درگاه: ' . $paymentGateway->name)
@section('header', 'ویرایش درگاه')
@section('content')

@include('admin.partials.breadcrumb', ['crumbs' => [
    ['label' => 'داشبورد',          'url' => route('admin.dashboard')],
    ['label' => 'درگاه‌های پرداخت', 'url' => route('admin.payment-gateways.index')],
    ['label' => $paymentGateway->name, 'url' => null],
]])

<div class="admin-page" style="max-width:760px">

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem">
        <div>
            <h1 style="font-size:1.125rem;font-weight:800;color:#1e293b;margin:0">ویرایش درگاه</h1>
            <p style="font-size:.8rem;color:#64748b;margin:.15rem 0 0">{{ $paymentGateway->driver_label }}</p>
        </div>
        <a href="{{ route('admin.payment-gateways.index') }}"
           style="font-size:.82rem;color:#6366f1;text-decoration:none;display:flex;align-items:center;gap:.3rem">
            ← بازگشت به لیست
        </a>
    </div>

    @if($paymentGateway->is_default)
    <div style="background:#ede9fe;border:1px solid #c4b5fd;border-radius:.75rem;padding:.75rem 1rem;margin-bottom:1.25rem;font-size:.82rem;color:#5b21b6;display:flex;gap:.5rem;align-items:center">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
        این درگاه در حال حاضر به‌عنوان <strong>پیش‌فرض</strong> انتخاب شده است.
    </div>
    @endif

    <form method="POST" action="{{ route('admin.payment-gateways.update', $paymentGateway) }}">
        @csrf
        @method('PUT')

        @include('admin.payment-gateways._form', [
            'gateway'      => $paymentGateway,
            'driverFields' => $driverFields,
        ])

        <div style="display:flex;gap:.75rem;justify-content:flex-end;padding-top:.5rem">
            <a href="{{ route('admin.payment-gateways.index') }}"
               style="padding:.6rem 1.25rem;border:1px solid #e2e8f0;border-radius:.625rem;font-size:.875rem;color:#475569;text-decoration:none;font-weight:600">
                انصراف
            </a>
            <button type="submit"
                    style="padding:.6rem 1.5rem;background:#6366f1;color:#fff;border:none;border-radius:.625rem;font-size:.875rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:.4rem">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                ذخیره تغییرات
            </button>
        </div>
    </form>

</div>
@endsection
