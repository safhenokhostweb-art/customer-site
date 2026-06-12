@php
/**
 * بنر فاکتور پرداخت‌نشده برای ماژول اشتراک.
 * تا زمانی که ماژول حسابداری توسعه نیافته، مستقیماً از جدول invoices بررسی می‌شود.
 * هنگامی که ماژول accounting توسعه یابد، این بخش می‌تواند از AccountingService استفاده کند.
 */
$__pendingInvoice = null;
if (auth()->check() && \App\Helpers\SiteHelper::moduleEnabled('subscription')) {
    $__pendingInvoice = \App\Models\Invoice::where('user_id', auth()->id())
        ->whereIn('status', ['pending', 'pending_confirmation'])
        ->whereHas('items', function ($q) {
            $q->where('title', 'like', '%اشتراک%');
        })
        ->with('items')
        ->latest()
        ->first();
}
@endphp

@if($__pendingInvoice)
<div class="pib-banner" role="alert" style="font-family:Vazirmatn,Tahoma,'Segoe UI',Arial,sans-serif">
    <div class="pib-banner__icon">
        <x-core-ui::icon name="alert-triangle" style="width:20px;height:20px" />
    </div>
    <div class="pib-banner__body">
        <p class="pib-banner__title">
            یک فاکتور پرداخت‌نشده دارید
        </p>
        <p class="pib-banner__desc">
            بابت «{{ $__pendingInvoice->items->first()?->title ?? 'اشتراک' }}» به مبلغ
            <strong>{{ \App\Helpers\NumberHelper::formatFa($__pendingInvoice->total) }} تومان</strong>
            (شماره: {{ $__pendingInvoice->invoice_number }})
            — تا پرداخت یا لغو این درخواست، اشتراک فعال نمی‌شود.
        </p>
    </div>
    <div class="pib-banner__actions">
        <a href="{{ route('subscriptions.pay', $__pendingInvoice->invoice_number) }}"
           class="pib-banner__btn pib-banner__btn--pay">
            پرداخت الان
        </a>
        <form method="POST"
              action="{{ route('account.subscriptions.cancel-pending') }}"
              onsubmit="return confirm('درخواست اشتراک لغو شود؟');"
              style="display:inline">
            @csrf
            <input type="hidden" name="invoice_id" value="{{ $__pendingInvoice->id }}">
            <button type="submit" class="pib-banner__btn pib-banner__btn--cancel">
                لغو درخواست
            </button>
        </form>
    </div>
</div>

<style>
.pib-banner { display:-webkit-box; display:-ms-flexbox; display:flex; -webkit-box-align:center; -ms-flex-align:center; align-items:center; gap:.875rem; background:#fffbeb; border:1.5px solid #fcd34d; border-radius:.875rem; padding:.875rem 1.125rem; margin-bottom:1.125rem; -ms-flex-wrap:wrap; flex-wrap:wrap; }
.pib-banner__icon { width:36px; height:36px; background:#fef3c7; border-radius:50%; display:-webkit-box; display:-ms-flexbox; display:flex; -webkit-box-align:center; -ms-flex-align:center; align-items:center; -webkit-box-pack:center; -ms-flex-pack:center; justify-content:center; color:#d97706; -ms-flex-negative:0; flex-shrink:0; }
.pib-banner__body { -webkit-box-flex:1; -ms-flex:1; flex:1; min-width:200px; }
.pib-banner__title { font-size:.875rem; font-weight:700; color:#92400e; margin:0 0 .2rem; }
.pib-banner__desc { font-size:.8rem; color:#a16207; margin:0; line-height:1.5; }
.pib-banner__actions { display:-webkit-box; display:-ms-flexbox; display:flex; gap:.5rem; -ms-flex-wrap:wrap; flex-wrap:wrap; }
.pib-banner__btn { display:inline-block; padding:.4rem .875rem; border-radius:.5rem; font-size:.8rem; font-weight:700; cursor:pointer; border:1px solid transparent; font-family:Vazirmatn,Tahoma,sans-serif; text-decoration:none; }
.pib-banner__btn--pay { background:#d97706; color:#fff; border-color:#d97706; }
.pib-banner__btn--pay:hover { background:#b45309; }
.pib-banner__btn--cancel { background:#fff; color:#92400e; border-color:#fcd34d; }
.pib-banner__btn--cancel:hover { background:#fef3c7; }
</style>
@endif


