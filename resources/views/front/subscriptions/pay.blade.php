@extends(\App\Helpers\SiteHelper::view('layout'))
@section('title', 'پرداخت اشتراک — ' . ($invoice->items->first()->title ?? 'فاکتور'))

@section('content')
<main>
<div class="subpay-wrap">

    <div class="subpay-card">

        {{-- ── هدر --}}
        <div class="subpay-card__header">
            <div class="subpay-card__header-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="24" height="24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
            </div>
            <div>
                <h1 class="subpay-card__title">پرداخت اشتراک</h1>
                <p class="subpay-card__subtitle">شماره فاکتور: <span dir="ltr">{{ $invoice->invoice_number }}</span></p>
            </div>
        </div>

        {{-- ── آیتم‌های فاکتور --}}
        <div class="subpay-card__items">
            @foreach($invoice->items as $item)
            <div class="subpay-item">
                <div class="subpay-item__icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <span class="subpay-item__title">{{ $item->title }}</span>
                <span class="subpay-item__price">{{ \App\Helpers\NumberHelper::formatFa($item->price) }} <small>تومان</small></span>
            </div>
            @endforeach
        </div>

        @if($invoice->discount && $invoice->discount > 0)
        <div class="subpay-card__discount">
            <span class="subpay-card__discount-label">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14" style="-ms-flex-negative:0;flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                {{ $invoice->notes ?: 'تخفیف' }}
            </span>
            <span class="subpay-card__discount-amount">− {{ \App\Helpers\NumberHelper::formatFa($invoice->discount) }} تومان</span>
        </div>
        @endif

        {{-- ── جمع کل --}}
        <div class="subpay-card__total">
            <span class="subpay-card__total-label">جمع قابل پرداخت</span>
            <span class="subpay-card__total-amount">
                {{ \App\Helpers\NumberHelper::formatFa($invoice->total) }}
                <small>تومان</small>
            </span>
        </div>

        {{-- ── راهنما --}}
        <div class="subpay-card__notice">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="17" height="17" style="-ms-flex-negative:0;flex-shrink:0;color:#d97706;margin-top:1px">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p>پس از ثبت درخواست پرداخت، اشتراک شما در اسرع وقت توسط مدیریت فعال خواهد شد و از طریق پیامک اطلاع‌رسانی می‌شود.</p>
        </div>

        {{-- ── دکمه تأیید --}}
        <div class="subpay-card__footer">
            <form method="POST" action="{{ route('subscriptions.confirm', $invoice->invoice_number) }}">
                @csrf
                <button type="submit" class="subpay-card__btn">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    تأیید و ثبت درخواست پرداخت
                </button>
            </form>
            <a href="{{ route('account.subscriptions.plans') }}" class="subpay-card__cancel">
                انصراف و بازگشت به پلن‌ها
            </a>
        </div>

    </div>

</div>
</main>

<style>
/* ── Subscription Pay Page ───────────────────────── */
*, *::before, *::after { -webkit-box-sizing: border-box; box-sizing: border-box; }
.subpay-wrap, .subpay-wrap * { font-family: Vazirmatn, Tahoma, 'Segoe UI', Arial, sans-serif; }
.subpay-wrap { min-height: 60vh; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; -webkit-box-pack: center; -ms-flex-pack: center; justify-content: center; padding: 2rem 1rem; }

.subpay-card { background: #fff; border-radius: 1.375rem; border: 1px solid #e2e8f0; max-width: 480px; width: 100%; -webkit-box-shadow: 0 8px 32px rgba(79,70,229,.12); box-shadow: 0 8px 32px rgba(79,70,229,.12); overflow: hidden; }

/* header */
.subpay-card__header { padding: 1.625rem 1.75rem 1.5rem; background: -webkit-linear-gradient(135deg,#4f46e5 0%,#7c3aed 100%); background: linear-gradient(135deg,#4f46e5 0%,#7c3aed 100%); display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; gap: 1rem; color: #fff; }
.subpay-card__header-icon { width: 48px; height: 48px; background: rgba(255,255,255,.18); border-radius: .875rem; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; -webkit-box-pack: center; -ms-flex-pack: center; justify-content: center; -ms-flex-negative: 0; flex-shrink: 0; }
.subpay-card__title { font-size: 1.125rem; font-weight: 800; margin: 0; }
.subpay-card__subtitle { font-size: .78rem; opacity: .82; margin: .2rem 0 0; }

/* items */
.subpay-card__items { padding: 1.25rem 1.75rem; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-orient: vertical; -webkit-box-direction: normal; -ms-flex-direction: column; flex-direction: column; gap: .75rem; border-bottom: 1px solid #f1f5f9; }
.subpay-item { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; gap: .75rem; }
.subpay-item__icon { width: 32px; height: 32px; background: #eef2ff; border-radius: .5rem; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; -webkit-box-pack: center; -ms-flex-pack: center; justify-content: center; color: #4f46e5; -ms-flex-negative: 0; flex-shrink: 0; }
.subpay-item__title { -webkit-box-flex: 1; -ms-flex: 1; flex: 1; font-size: .875rem; color: #374151; font-weight: 500; }
.subpay-item__price { font-size: .9375rem; font-weight: 700; color: #1e293b; -ms-flex-negative: 0; flex-shrink: 0; }
.subpay-item__price small { font-size: .72rem; font-weight: 400; color: #94a3b8; }

/* total */
.subpay-card__discount { padding: .75rem 1.75rem; display:-webkit-box; display:-ms-flexbox; display:flex; -webkit-box-pack:justify; -ms-flex-pack:justify; justify-content:space-between; -webkit-box-align:center; -ms-flex-align:center; align-items:center; background:#f0fdf4; border-bottom:1px solid #bbf7d0; }
.subpay-card__discount-label { display:-webkit-box; display:-ms-flexbox; display:flex; -webkit-box-align:center; -ms-flex-align:center; align-items:center; gap:.375rem; font-size:.8rem; color:#15803d; font-weight:600; font-family:monospace,'Courier New'; }
.subpay-card__discount-amount { font-size:.875rem; font-weight:700; color:#16a34a; }
.subpay-card__total { padding: 1.125rem 1.75rem; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-pack: justify; -ms-flex-pack: justify; justify-content: space-between; -webkit-box-align: center; -ms-flex-align: center; align-items: center; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
.subpay-card__total-label { font-size: .875rem; font-weight: 600; color: #475569; }
.subpay-card__total-amount { font-size: 1.5rem; font-weight: 800; color: #4f46e5; }
.subpay-card__total-amount small { font-size: .8rem; font-weight: 400; color: #94a3b8; margin-right: .2rem; }

/* notice */
.subpay-card__notice { padding: .875rem 1.75rem; display: -webkit-box; display: -ms-flexbox; display: flex; gap: .625rem; -webkit-box-align: start; -ms-flex-align: start; align-items: flex-start; background: #fffbeb; border-bottom: 1px solid #fde68a; }
.subpay-card__notice p { font-size: .8rem; color: #92400e; line-height: 1.65; margin: 0; }

/* footer */
.subpay-card__footer { padding: 1.5rem 1.75rem; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-orient: vertical; -webkit-box-direction: normal; -ms-flex-direction: column; flex-direction: column; gap: .75rem; }
.subpay-card__btn { width: 100%; padding: .875rem; background: -webkit-linear-gradient(135deg,#4f46e5,#7c3aed); background: linear-gradient(135deg,#4f46e5,#7c3aed); color: #fff; font-size: 1rem; font-weight: 700; border: none; border-radius: .875rem; cursor: pointer; font-family: Vazirmatn, Tahoma, sans-serif; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; -webkit-box-pack: center; -ms-flex-pack: center; justify-content: center; gap: .5rem; -webkit-box-shadow: 0 4px 14px rgba(79,70,229,.35); box-shadow: 0 4px 14px rgba(79,70,229,.35); -webkit-transition: opacity .15s, -webkit-transform .15s; transition: opacity .15s, transform .15s; }
.subpay-card__btn:hover { opacity: .93; -webkit-transform: translateY(-1px); transform: translateY(-1px); }
.subpay-card__cancel { display: block; text-align: center; font-size: .8125rem; color: #94a3b8; text-decoration: none; padding: .375rem; }
.subpay-card__cancel:hover { color: #475569; text-decoration: underline; }
</style>

@endsection
