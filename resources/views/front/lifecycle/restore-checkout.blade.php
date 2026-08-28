@extends('account.layout')

@section('title', 'پرداخت هزینه فعال‌سازی مجدد')
@section('header', 'فعال‌سازی مجدد سایت')

@section('content')
<div class="mx-auto max-w-xl rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
    <h1 class="text-lg font-bold text-slate-900">پرداخت هزینه فعال‌سازی مجدد</h1>
    <p class="mt-2 text-sm leading-7 text-slate-600">
        پس از انتخاب پرداخت، اتصال شما بررسی می‌شود و سپس به درگاه بانکی منتقل خواهید شد.
    </p>

    <div class="mt-5 rounded-xl bg-slate-50 p-4 text-center">
        <div class="text-sm text-slate-500">مبلغ قابل پرداخت</div>
        <div class="mt-1 text-2xl font-bold text-slate-900">
            @numFa($amount) <span class="text-sm font-medium">تومان</span>
        </div>
    </div>

    <form method="POST" action="{{ route('account.lifecycle.start-payment', $invoice->invoice_number) }}" class="mt-6" data-payment-start>
        @csrf
        <x-core-ui::button type="submit" variant="primary" class="w-full justify-center">
            ادامه به درگاه بانکی
        </x-core-ui::button>
    </form>
</div>
@endsection
