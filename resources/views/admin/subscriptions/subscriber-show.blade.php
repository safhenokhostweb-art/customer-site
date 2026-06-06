@extends('admin.layout')
@section('title', 'اشتراک #' . \App\Helpers\NumberHelper::formatFa($subscription->id))
@section('content')

<div class="subshow" style="font-family:Vazirmatn,Tahoma,'Segoe UI',Arial,sans-serif">

{{-- ── breadcrumb ──────────────────────────────────── --}}
<div class="subshow__bc">
    <a href="{{ route('admin.subscribers.index') }}" class="subshow__bc-link">مشترکین</a>
    <span class="subshow__bc-sep">›</span>
    <span class="subshow__bc-cur">اشتراک #{{ \App\Helpers\NumberHelper::formatFa($subscription->id) }}</span>
</div>


<div class="subshow__layout">

    {{-- ══════ ستون اصلی ══════ --}}
    <div class="subshow__main">

        {{-- کاربر --}}
        <div class="subshow__card">
            <div class="subshow__card-head">اطلاعات کاربر</div>
            <div class="subshow__card-body">
                <div class="subshow__user">
                    <div class="subshow__user-avatar">
                        {{ mb_substr(optional($subscription->user)->name ?? optional($subscription->user)->mobile ?? '?', 0, 1) }}
                    </div>
                    <div>
                        <p class="subshow__user-name">{{ optional($subscription->user)->name ?? '—' }}</p>
                        <p class="subshow__user-mobile" dir="ltr">{{ optional($subscription->user)->mobile ?? '—' }}</p>
                    </div>
                    @if($subscription->user)
                    <a href="{{ route('admin.users.index') }}?search={{ urlencode($subscription->user->mobile) }}"
                       class="btn btn-secondary btn-sm" style="margin-right:auto">مشاهده در لیست</a>
                    @endif
                </div>
            </div>
        </div>

        {{-- اطلاعات اشتراک --}}
        <div class="subshow__card">
            <div class="subshow__card-head" style="display:-webkit-box;display:-ms-flexbox;display:flex;-webkit-box-align:center;-ms-flex-align:center;align-items:center;-webkit-box-pack:justify;-ms-flex-pack:justify;justify-content:space-between">
                <span>اطلاعات اشتراک</span>
                <span class="sub-badge sub-badge--{{ $subscription->status }}">{{ $subscription->getStatusLabel() }}</span>
            </div>
            <div class="subshow__card-body">
                <div class="subshow__grid">
                    <div class="subshow__field">
                        <span class="subshow__field-label">پلن</span>
                        <span class="subshow__field-val subshow__field-val--bold">{{ $subscription->plan->name ?? '—' }}</span>
                    </div>
                    <div class="subshow__field">
                        <span class="subshow__field-label">قیمت پلن</span>
                        <span class="subshow__field-val">{{ $subscription->plan?->getFormattedPrice() ?? '—' }}</span>
                    </div>
                    <div class="subshow__field">
                        <span class="subshow__field-label">تاریخ شروع</span>
                        <span class="subshow__field-val">{{ \App\Helpers\Jalali::formatFa($subscription->starts_at, 'Y/m/d') }}</span>
                    </div>
                    <div class="subshow__field">
                        <span class="subshow__field-label">تاریخ پایان</span>
                        <span class="subshow__field-val">{{ \App\Helpers\Jalali::formatFa($subscription->ends_at, 'Y/m/d') }}</span>
                    </div>
                    @if($subscription->trial_ends_at)
                    <div class="subshow__field">
                        <span class="subshow__field-label">پایان آزمایشی</span>
                        <span class="subshow__field-val">{{ \App\Helpers\Jalali::formatFa($subscription->trial_ends_at, 'Y/m/d') }}</span>
                    </div>
                    @endif
                    @if($subscription->cancelled_at)
                    <div class="subshow__field">
                        <span class="subshow__field-label">تاریخ لغو</span>
                        <span class="subshow__field-val subshow__field-val--danger">{{ \App\Helpers\Jalali::formatFa($subscription->cancelled_at, 'Y/m/d') }}</span>
                    </div>
                    @endif
                    @if($subscription->isActive() || $subscription->isInTrial())
                    <div class="subshow__field">
                        <span class="subshow__field-label">روزهای مانده</span>
                        <span class="subshow__field-val {{ $subscription->daysRemaining() <= 7 ? 'subshow__field-val--warn' : 'subshow__field-val--good' }}">
                            {{ \App\Helpers\NumberHelper::formatFa($subscription->daysRemaining()) }} روز
                        </span>
                    </div>
                    @endif
                    @if($subscription->invoice)
                    <div class="subshow__field">
                        <span class="subshow__field-label">فاکتور</span>
                        <a href="{{ route('admin.invoices.show', $subscription->invoice) }}" class="subshow__field-val subshow__field-val--link">
                            {{ $subscription->invoice->invoice_number }}
                        </a>
                    </div>
                    @endif
                </div>
                @if($subscription->cancellation_reason)
                <div class="subshow__reason">
                    <span class="subshow__reason-label">دلیل لغو:</span>
                    {{ $subscription->cancellation_reason }}
                </div>
                @endif
            </div>
        </div>

        {{-- لاگ‌ها --}}
        <div class="subshow__card">
            <div class="subshow__card-head">تاریخچه عملیات</div>
            <div class="subshow__card-body" style="padding:0">
                @forelse($subscription->logs as $log)
                <div class="subshow__log-row">
                    <div class="subshow__log-icon subshow__log-icon--{{ $log->action }}">
                        @if($log->action === 'subscribed' || $log->action === 'reactivated')
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        @elseif($log->action === 'cancelled')
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        @elseif($log->action === 'renewed')
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        @else
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @endif
                    </div>
                    <div class="subshow__log-content">
                        <span class="subshow__log-action">{{ $log->getActionLabel() }}</span>
                        <span class="subshow__log-meta">
                            {{ \App\Helpers\Jalali::formatFa($log->created_at, 'Y/m/d H:i') }}
                            · {{ $log->performed_by }}
                        </span>
                        @if($log->details)
                        <span class="subshow__log-details">{{ json_encode($log->details, JSON_UNESCAPED_UNICODE) }}</span>
                        @endif
                    </div>
                </div>
                @empty
                <p style="padding:1rem 1.25rem;color:#94a3b8;font-size:.8rem">لاگی ثبت نشده.</p>
                @endforelse
            </div>
        </div>

    </div>{{-- /main --}}

    {{-- ══════ ستون کناری ══════ --}}
    <div class="subshow__side">

        {{-- پلن فعلی --}}
        @if($subscription->plan)
        <div class="subshow__plan-card" style="border-top: 4px solid {{ $subscription->plan->color ?? '#4f46e5' }}">
            <p class="subshow__plan-name">{{ $subscription->plan->name }}</p>
            <p class="subshow__plan-price">{{ $subscription->plan->getFormattedPrice() }}</p>
            <div class="subshow__plan-meta">
                <span>{{ $subscription->plan->getDurationLabel() }}</span>
                @if($subscription->plan->hasTrial())
                <span>آزمایشی {{ \App\Helpers\NumberHelper::formatFa($subscription->plan->trial_days) }} روز</span>
                @endif
            </div>
            @if($subscription->plan->getFeaturesArray())
            <ul class="subshow__plan-feats">
                @foreach($subscription->plan->getFeaturesArray() as $f)
                <li>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="13" height="13"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    {{ $f }}
                </li>
                @endforeach
            </ul>
            @endif
        </div>
        @endif

        @php
            $isLiveGateway  = config('subscription.live_gateway', env('PAYMENT_GATEWAY_LIVE', false));
            $hasPaidInvoice = $subscription->invoice && $subscription->invoice->status === 'paid';
            $canDelete      = ! ($isLiveGateway && $hasPaidInvoice);
        @endphp

        {{-- عملیات: لغو --}}
        @if($subscription->isActive() || $subscription->isInTrial())
        <div class="subshow__action-card subshow__action-card--danger">
            <p class="subshow__action-card__title">لغو اشتراک</p>
            <form method="POST" action="{{ route('admin.subscribers.cancel', $subscription) }}"
                  onsubmit="return confirm('اشتراک لغو شود؟');">
                @csrf
                <textarea name="reason" rows="2" placeholder="دلیل لغو (اختیاری)..."
                          class="subshow__textarea"></textarea>
                <button type="submit" class="subshow__btn-danger">لغو اشتراک</button>
            </form>
        </div>
        @endif

        {{-- عملیات: فعال‌سازی مجدد (لغو شده) --}}
        @if($subscription->isCancelled())
        <div class="subshow__action-card subshow__action-card--success">
            <p class="subshow__action-card__title">فعال‌سازی مجدد</p>
            <p class="subshow__action-card__desc">اشتراک با مدت کامل دوباره فعال می‌شود.</p>
            <form method="POST" action="{{ route('admin.subscribers.reactivate', $subscription) }}"
                  onsubmit="return confirm('اشتراک مجدداً فعال شود؟');">
                @csrf
                <button type="submit" class="subshow__btn-success">فعال‌سازی مجدد</button>
            </form>
        </div>
        @endif

        {{-- فعال‌سازی رایگان (pending/expired) --}}
        @if($subscription->isPending() || $subscription->status === 'expired')
        <div class="subshow__action-card subshow__action-card--success">
            <p class="subshow__action-card__title">فعال‌سازی رایگان</p>
            <p class="subshow__action-card__desc">بدون نیاز به پرداخت، اشتراک همین لحظه فعال می‌شود و تیکت اطلاع‌رسانی ارسال خواهد شد.</p>
            <form method="POST" action="{{ route('admin.subscribers.activate-free', $subscription) }}"
                  onsubmit="return confirm('اشتراک رایگان فعال شود؟');">
                @csrf
                <button type="submit" class="subshow__btn-success">فعال‌سازی رایگان</button>
            </form>
        </div>
        @endif

        {{-- حذف اشتراک --}}
        @if($canDelete)
        <div class="subshow__action-card" style="border:1px solid #fecdd3;background:#fff1f2">
            <p class="subshow__action-card__title" style="color:#be123c">حذف اشتراک</p>
            @if(!$isLiveGateway)
            <p class="subshow__action-card__desc" style="color:#94a3b8;font-size:.72rem">درگاه آزمایشی — حذف همیشه مجاز است</p>
            @else
            <p class="subshow__action-card__desc">این اشتراک پرداخت ندارد و قابل حذف است.</p>
            @endif
            <form method="POST" action="{{ route('admin.subscribers.delete', $subscription) }}"
                  onsubmit="return confirm('اشتراک کاملاً حذف شود؟ این عمل برگشت‌ناپذیر است.');">
                @csrf @method('DELETE')
                <button type="submit" class="subshow__btn-danger">حذف اشتراک</button>
            </form>
        </div>
        @else
        <div class="subshow__action-card" style="border:1px solid #e2e8f0;background:#f8fafc">
            <p class="subshow__action-card__title" style="color:#64748b">حذف غیرمجاز</p>
            <p class="subshow__action-card__desc">این اشتراک دارای پرداخت تأیید‌شده از درگاه واقعی است و قابل حذف نیست.</p>
        </div>
        @endif

        <a href="{{ route('admin.subscribers.index') }}" class="subshow__back">
            ← بازگشت به لیست مشترکین
        </a>

    </div>{{-- /side --}}

</div>{{-- /layout --}}
</div>{{-- /subshow --}}

@push('head')
<style>
.subshow, .subshow * { font-family: Vazirmatn, Tahoma, 'Segoe UI', Arial, sans-serif; }

/* breadcrumb */
.subshow__bc { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; gap: .375rem; font-size: .8rem; color: #94a3b8; margin-bottom: 1.125rem; }
.subshow__bc-link { color: #4f46e5; text-decoration: none; }
.subshow__bc-link:hover { text-decoration: underline; }
.subshow__bc-sep { color: #cbd5e1; }
.subshow__bc-cur { color: #64748b; }

/* layout */
.subshow__layout { display: -webkit-box; display: -ms-flexbox; display: flex; gap: 1.25rem; -webkit-box-align: start; -ms-flex-align: start; align-items: flex-start; }
.subshow__main { -webkit-box-flex: 1; -ms-flex: 1 1 0%; flex: 1 1 0%; min-width: 0; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-orient: vertical; -webkit-box-direction: normal; -ms-flex-direction: column; flex-direction: column; gap: 1.125rem; }
.subshow__side { width: 280px; -ms-flex-negative: 0; flex-shrink: 0; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-orient: vertical; -webkit-box-direction: normal; -ms-flex-direction: column; flex-direction: column; gap: 1rem; }
@media (max-width: 900px) { .subshow__layout { -webkit-box-orient: vertical; -webkit-box-direction: normal; -ms-flex-direction: column; flex-direction: column; } .subshow__side { width: 100%; } }

/* card */
.subshow__card { background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem; overflow: hidden; -webkit-box-shadow: 0 1px 4px rgba(0,0,0,.05); box-shadow: 0 1px 4px rgba(0,0,0,.05); }
.subshow__card-head { padding: .875rem 1.25rem; background: #f8fafc; border-bottom: 1px solid #e2e8f0; font-size: .875rem; font-weight: 700; color: #374151; }
.subshow__card-body { padding: 1.125rem 1.25rem; }

/* user */
.subshow__user { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; gap: .875rem; }
.subshow__user-avatar { width: 44px; height: 44px; border-radius: 50%; background: -webkit-linear-gradient(135deg,#4f46e5,#7c3aed); background: linear-gradient(135deg,#4f46e5,#7c3aed); color:#fff; font-weight:800; font-size:1.125rem; display:-webkit-box; display:-ms-flexbox; display:flex; -webkit-box-align:center; -ms-flex-align:center; align-items:center; -webkit-box-pack:center; -ms-flex-pack:center; justify-content:center; -ms-flex-negative:0; flex-shrink:0; }
.subshow__user-name { font-weight: 700; color: #1e293b; font-size: .9375rem; margin: 0; }
.subshow__user-mobile { font-size: .8rem; color: #94a3b8; margin: .15rem 0 0; }

/* fields grid */
.subshow__grid { display: -ms-grid; display: grid; -ms-grid-columns: 1fr 1fr; grid-template-columns: 1fr 1fr; gap: .875rem 1.5rem; }
@media (max-width: 500px) { .subshow__grid { grid-template-columns: 1fr; } }
.subshow__field { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-orient: vertical; -webkit-box-direction: normal; -ms-flex-direction: column; flex-direction: column; gap: .2rem; }
.subshow__field-label { font-size: .72rem; color: #94a3b8; font-weight: 500; }
.subshow__field-val { font-size: .875rem; color: #334155; font-weight: 500; }
.subshow__field-val--bold { font-weight: 700; color: #1e293b; }
.subshow__field-val--danger { color: #dc2626; }
.subshow__field-val--warn { color: #ea580c; font-weight: 700; }
.subshow__field-val--good { color: #16a34a; font-weight: 700; }
.subshow__field-val--link { color: #4f46e5; text-decoration: none; }
.subshow__field-val--link:hover { text-decoration: underline; }
.subshow__reason { margin-top: .875rem; padding: .75rem; background: #fff7ed; border-radius: .5rem; font-size: .8rem; color: #92400e; }
.subshow__reason-label { font-weight: 700; margin-left: .25rem; }

/* log */
.subshow__log-row { display: -webkit-box; display: -ms-flexbox; display: flex; gap: .875rem; -webkit-box-align: start; -ms-flex-align: start; align-items: flex-start; padding: .75rem 1.25rem; border-bottom: 1px solid #f1f5f9; }
.subshow__log-row:last-child { border-bottom: none; }
.subshow__log-icon { width: 28px; height: 28px; border-radius: 50%; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; -webkit-box-pack: center; -ms-flex-pack: center; justify-content: center; -ms-flex-negative: 0; flex-shrink: 0; margin-top: .1rem; }
.subshow__log-icon--subscribed, .subshow__log-icon--reactivated { background: #dcfce7; color: #16a34a; }
.subshow__log-icon--cancelled   { background: #fee2e2; color: #dc2626; }
.subshow__log-icon--renewed     { background: #dbeafe; color: #2563eb; }
.subshow__log-icon--trial_started { background: #ede9fe; color: #7c3aed; }
.subshow__log-icon--expired     { background: #f1f5f9; color: #64748b; }
.subshow__log-content { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-orient: vertical; -webkit-box-direction: normal; -ms-flex-direction: column; flex-direction: column; gap: .15rem; }
.subshow__log-action { font-size: .875rem; font-weight: 600; color: #1e293b; }
.subshow__log-meta { font-size: .75rem; color: #94a3b8; }
.subshow__log-details { font-size: .72rem; font-family: monospace; background: #f8fafc; color: #64748b; padding: .2rem .5rem; border-radius: .25rem; display: inline-block; }

/* plan card */
.subshow__plan-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem; padding: 1.125rem 1.25rem; -webkit-box-shadow: 0 1px 4px rgba(0,0,0,.05); box-shadow: 0 1px 4px rgba(0,0,0,.05); }
.subshow__plan-name { font-size: 1.0625rem; font-weight: 800; color: #1e293b; margin: 0 0 .25rem; }
.subshow__plan-price { font-size: 1.375rem; font-weight: 800; color: #4f46e5; margin: 0 0 .5rem; }
.subshow__plan-meta { display: -webkit-box; display: -ms-flexbox; display: flex; gap: .5rem; -ms-flex-wrap: wrap; flex-wrap: wrap; font-size: .75rem; color: #64748b; margin-bottom: .75rem; }
.subshow__plan-feats { list-style: none; padding: 0; margin: .5rem 0 0; }
.subshow__plan-feats li { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; gap: .375rem; font-size: .8rem; color: #475569; padding: .25rem 0; }
.subshow__plan-feats li svg { color: #16a34a; -ms-flex-negative: 0; flex-shrink: 0; }

/* action card */
.subshow__action-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem; padding: 1rem 1.25rem; -webkit-box-shadow: 0 1px 4px rgba(0,0,0,.05); box-shadow: 0 1px 4px rgba(0,0,0,.05); }
.subshow__action-card--danger { border-top: 3px solid #f43f5e; }
.subshow__action-card--success { border-top: 3px solid #22c55e; }
.subshow__action-card__title { font-size: .875rem; font-weight: 700; color: #1e293b; margin: 0 0 .625rem; }
.subshow__action-card__desc { font-size: .8rem; color: #64748b; margin: 0 0 .75rem; }
.subshow__textarea { width: 100%; -webkit-box-sizing: border-box; box-sizing: border-box; border: 1px solid #e2e8f0; border-radius: .5rem; padding: .5rem .75rem; font-size: .8125rem; font-family: Vazirmatn, Tahoma, sans-serif; margin-bottom: .625rem; resize: vertical; }
.subshow__btn-danger { width: 100%; padding: .625rem; background: #f43f5e; color: #fff; font-weight: 700; font-size: .875rem; border: none; border-radius: .625rem; cursor: pointer; font-family: Vazirmatn, Tahoma, sans-serif; }
.subshow__btn-danger:hover { background: #e11d48; }
.subshow__btn-success { width: 100%; padding: .625rem; background: #22c55e; color: #fff; font-weight: 700; font-size: .875rem; border: none; border-radius: .625rem; cursor: pointer; font-family: Vazirmatn, Tahoma, sans-serif; }
.subshow__btn-success:hover { background: #16a34a; }
.subshow__back { display: block; text-align: center; padding: .625rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: .75rem; font-size: .8125rem; color: #475569; text-decoration: none; font-weight: 500; }
.subshow__back:hover { background: #f1f5f9; }

/* badge */
.sub-badge { display: inline-block; padding: .22rem .7rem; border-radius: 9999px; font-size: .73rem; font-weight: 700; }
.sub-badge--active    { background: #dcfce7; color: #15803d; }
.sub-badge--trial     { background: #dbeafe; color: #1d4ed8; }
.sub-badge--cancelled { background: #fee2e2; color: #be123c; }
.sub-badge--expired   { background: #f1f5f9; color: #64748b; }
</style>
@endpush

@endsection
