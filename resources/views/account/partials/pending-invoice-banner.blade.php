@php
/**
 * ط¨ظ†ط± ظپط§ع©طھظˆط± ظ¾ط±ط¯ط§ط®طھâ€Œظ†ط´ط¯ظ‡ ط¨ط±ط§غŒ ظ…ط§عکظˆظ„ ط§ط´طھط±ط§ع©.
 * طھط§ ط²ظ…ط§ظ†غŒ ع©ظ‡ ظ…ط§عکظˆظ„ ط­ط³ط§ط¨ط¯ط§ط±غŒ طھظˆط³ط¹ظ‡ ظ†غŒط§ظپطھظ‡طŒ ظ…ط³طھظ‚غŒظ…ط§ظ‹ ط§ط² ط¬ط¯ظˆظ„ invoices ط¨ط±ط±ط³غŒ ظ…غŒâ€Œط´ظˆط¯.
 * ظ‡ظ†ع¯ط§ظ…غŒ ع©ظ‡ ظ…ط§عکظˆظ„ accounting طھظˆط³ط¹ظ‡ غŒط§ط¨ط¯طŒ ط§غŒظ† ط¨ط®ط´ ظ…غŒâ€Œطھظˆط§ظ†ط¯ ط§ط² AccountingService ط§ط³طھظپط§ط¯ظ‡ ع©ظ†ط¯.
 */
$__pendingInvoice = null;
if (auth()->check() && \App\Helpers\SiteHelper::moduleEnabled('subscription')) {
    $__pendingInvoice = \App\Models\Invoice::where('user_id', auth()->id())
        ->whereIn('status', ['pending', 'pending_confirmation'])
        ->whereHas('items', function ($q) {
            $q->where('title', 'like', '%ط§ط´طھط±ط§ع©%');
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
            غŒع© ظپط§ع©طھظˆط± ظ¾ط±ط¯ط§ط®طھâ€Œظ†ط´ط¯ظ‡ ط¯ط§ط±غŒط¯
        </p>
        <p class="pib-banner__desc">
            ط¨ط§ط¨طھ آ«{{ $__pendingInvoice->items->first()?->title ?? 'ط§ط´طھط±ط§ع©' }}آ» ط¨ظ‡ ظ…ط¨ظ„ط؛
            <strong>{{ \App\Helpers\NumberHelper::formatFa($__pendingInvoice->total) }} طھظˆظ…ط§ظ†</strong>
            (ط´ظ…ط§ط±ظ‡: {{ $__pendingInvoice->invoice_number }})
            â€” طھط§ ظ¾ط±ط¯ط§ط®طھ غŒط§ ظ„ط؛ظˆ ط§غŒظ† ط¯ط±ط®ظˆط§ط³طھطŒ ط§ط´طھط±ط§ع© ظپط¹ط§ظ„ ظ†ظ…غŒâ€Œط´ظˆط¯.
        </p>
    </div>
    <div class="pib-banner__actions">
        <a href="{{ route('subscriptions.pay', $__pendingInvoice->invoice_number) }}"
           class="pib-banner__btn pib-banner__btn--pay">
            ظ¾ط±ط¯ط§ط®طھ ط§ظ„ط§ظ†
        </a>
        <form method="POST"
              action="{{ route('account.subscriptions.cancel-pending') }}"
              onsubmit="return confirm('ط¯ط±ط®ظˆط§ط³طھ ط§ط´طھط±ط§ع© ظ„ط؛ظˆ ط´ظˆط¯طں');"
              style="display:inline">
            @csrf
            <input type="hidden" name="invoice_id" value="{{ $__pendingInvoice->id }}">
            <button type="submit" class="pib-banner__btn pib-banner__btn--cancel">
                ظ„ط؛ظˆ ط¯ط±ط®ظˆط§ط³طھ
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


