@extends('admin.layout')
@section('title', 'افزودن درگاه پرداخت')
@section('header', 'افزودن درگاه پرداخت')
@section('content')

@include('admin.partials.breadcrumb', ['crumbs' => [
    ['label' => 'داشبورد',          'url' => route('admin.dashboard')],
    ['label' => 'درگاه‌های پرداخت', 'url' => route('admin.payment-gateways.index')],
    ['label' => 'درگاه جدید',       'url' => null],
]])

<div class="admin-page" style="max-width:760px">

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem">
        <h1 style="font-size:1.125rem;font-weight:800;color:#1e293b;margin:0">افزودن درگاه پرداخت</h1>
        <a href="{{ route('admin.payment-gateways.index') }}"
           style="font-size:.82rem;color:#6366f1;text-decoration:none;display:flex;align-items:center;gap:.3rem">
            ← بازگشت به لیست
        </a>
    </div>

    <form method="POST" action="{{ route('admin.payment-gateways.store') }}">
        @csrf

        @include('admin.payment-gateways._form', ['driverFields' => $driverFields])

        <div style="display:flex;gap:.75rem;justify-content:flex-end;padding-top:.5rem">
            <a href="{{ route('admin.payment-gateways.index') }}"
               style="padding:.6rem 1.25rem;border:1px solid #e2e8f0;border-radius:.625rem;font-size:.875rem;color:#475569;text-decoration:none;font-weight:600">
                انصراف
            </a>
            <button type="submit"
                    style="padding:.6rem 1.5rem;background:#6366f1;color:#fff;border:none;border-radius:.625rem;font-size:.875rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:.4rem">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                ذخیره درگاه
            </button>
        </div>
    </form>

</div>
@endsection
