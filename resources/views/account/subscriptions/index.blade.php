@extends('account.layout')
@section('title', 'اشتراک‌های من')
@section('header', 'اشتراک‌های من')

@section('content')
<div class="accsub" style="font-family:Vazirmatn,Tahoma,'Segoe UI',Arial,sans-serif">


{{-- اشتراک فعال --}}
@if($active)
<div class="accsub__active">
    <div class="accsub__active-badge">فعال</div>
    <div class="accsub__active-info">
        <p class="accsub__active-plan">{{ $active->plan->name }}</p>
        <p class="accsub__active-meta">
            انقضا: {{ \App\Helpers\Jalali::formatFa($active->ends_at, 'Y/m/d') }}
            &nbsp;·&nbsp;
            <span class="{{ $active->daysRemaining() <= 7 ? 'accsub__warn' : 'accsub__good' }}">
                {{ \App\Helpers\NumberHelper::formatFa($active->daysRemaining()) }} روز مانده
            </span>
            @if($active->auto_renew)
                &nbsp;·&nbsp;
                <span class="accsub__autorenew-badge">↻ تمدید خودکار فعال</span>
            @endif
        </p>
    </div>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center">
        @if(($active->plan->max_users ?? 0) > 1)
        <a href="{{ route('account.subscriptions.members', $active) }}" class="accsub__active-btn" style="display:inline-flex;align-items:center;gap:.3rem">
            <x-core-ui::icon name="users" style="width:13px;height:13px" />
            اعضای گروه
        </a>
        @endif
        <a href="{{ route('account.subscriptions.upgrade-plans', $active) }}" class="accsub__active-btn" style="display:inline-flex;align-items:center;gap:.3rem">
            <x-core-ui::icon name="arrow-up" style="width:13px;height:13px" />
            تغییر پلن
        </a>
        <a href="{{ route('account.subscriptions.plans') }}" class="accsub__active-btn">مشاهده پلن‌ها</a>
    </div>
</div>

@else
@php
    $lastExpired = $subscriptions->first(fn($s) => $s->isExpired());
@endphp
@if($lastExpired)
{{-- ─── CTA: اشتراک منقضی شده ─────────────────────────────────────────── --}}
<div class="accsub__expired-bar">
    <div class="accsub__expired-bar__icon">
        <x-core-ui::icon name="alert-triangle" style="width:26px;height:26px;color:#f59e0b" />
    </div>
    <div style="flex:1;min-width:160px">
        <p class="accsub__expired-bar__title">اشتراک «{{ $lastExpired->plan?->name }}» شما منقضی شده است</p>
        <p class="accsub__expired-bar__sub">
            تاریخ انقضا: {{ \App\Helpers\Jalali::formatFa($lastExpired->ends_at, 'Y/m/d') }}
            &nbsp;—&nbsp; برای ادامه استفاده از امکانات، پلن جدید انتخاب کنید.
        </p>
    </div>
    <a href="{{ route('account.subscriptions.plans') }}" class="accsub__expired-bar__btn">
        <x-core-ui::icon name="refresh" style="width:14px;height:14px;vertical-align:middle;margin-left:.3rem" />
        تمدید اشتراک
    </a>
</div>
@else
<div class="accsub__cta">
    <x-core-ui::icon name="subscription" style="width:40px;height:40px;color:#c7d2fe;display:block;margin:0 auto .75rem" />
    <p class="accsub__cta-title">اشتراک فعالی ندارید</p>
    <p class="accsub__cta-desc">برای دسترسی به امکانات ویژه، یک پلن اشتراک انتخاب کنید.</p>
    <a href="{{ route('account.subscriptions.plans') }}" class="accsub__cta-btn">مشاهده پلن‌های اشتراک</a>
</div>
@endif
@endif

{{-- پیام اطلاع‌رسانی --}}
@php $pendingSub = $subscriptions->first(fn($s) => $s->isPending()); @endphp
@if($pendingSub)
<div style="display:flex;align-items:center;gap:.625rem;background:#fff7ed;border:1px solid #fed7aa;border-radius:.875rem;padding:.875rem 1.125rem;margin-top:1rem;font-size:.82rem;color:#c2410c">
    <x-core-ui::icon name="clock" style="width:18px;height:18px;flex-shrink:0" />
    <span>درخواست اشتراک «{{ $pendingSub->plan?->name }}» در انتظار بررسی ادمین است. برای لغو یا تغییر با پشتیبانی تماس بگیرید.</span>
</div>
@endif

{{-- دعوت‌نامه‌های گروهی در انتظار --}}
@if(isset($pendingInvites) && $pendingInvites->isNotEmpty())
<div class="accsub__invites" style="margin-top:1.25rem">
    <div class="accsub__card-head" style="background:#fff7ed;border-color:#fed7aa;color:#9a3412;border-radius:.875rem .875rem 0 0;border:1.5px solid #fed7aa;padding:.75rem 1.125rem;display:flex;align-items:center;gap:.5rem;font-size:.85rem;font-weight:700">
        <x-core-ui::icon name="user-plus" style="width:16px;height:16px" />
        دعوت به اشتراک گروهی ({{ \App\Helpers\NumberHelper::formatFa($pendingInvites->count()) }} دعوت‌نامه)
    </div>
    @foreach($pendingInvites as $invite)
    <div style="display:flex;align-items:center;gap:.875rem;background:#fff;border:1.5px solid #fed7aa;border-top:none;padding:.875rem 1.125rem;flex-wrap:wrap;{{ $loop->last ? 'border-radius:0 0 .875rem .875rem' : '' }}">
        <div style="width:38px;height:38px;border-radius:50%;background:#ffedd5;color:#c2410c;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1.1rem">
            <x-core-ui::icon name="users" style="width:18px;height:18px" />
        </div>
        <div style="flex:1;min-width:160px">
            <strong style="font-size:.875rem;color:#1e293b">{{ $invite->subscription?->plan?->name }}</strong>
            <p style="font-size:.78rem;color:#64748b;margin:.15rem 0 0">
                دعوت از: {{ $invite->inviter?->name ?? '—' }}
                &nbsp;·&nbsp;
                {{ \App\Helpers\Jalali::formatFa($invite->invited_at, 'Y/m/d') }}
            </p>
        </div>
        <div style="display:flex;gap:.5rem;flex-shrink:0">
            <form method="POST" action="{{ route('account.subscriptions.invites.accept', $invite) }}">
                @csrf
                <button type="submit" style="display:inline-flex;align-items:center;gap:.3rem;padding:.4rem .875rem;background:#f0fdf4;color:#166534;border:1px solid #86efac;border-radius:.5rem;font-size:.8rem;font-weight:700;cursor:pointer;font-family:Vazirmatn,Tahoma,sans-serif">
                    <x-core-ui::icon name="check" style="width:13px;height:13px" />
                    پذیرش
                </button>
            </form>
            <form method="POST" action="{{ route('account.subscriptions.invites.reject', $invite) }}">
                @csrf
                <button type="submit" style="display:inline-flex;align-items:center;gap:.3rem;padding:.4rem .875rem;background:#fff1f2;color:#be123c;border:1px solid #fecdd3;border-radius:.5rem;font-size:.8rem;font-weight:700;cursor:pointer;font-family:Vazirmatn,Tahoma,sans-serif">
                    <x-core-ui::icon name="close" style="width:13px;height:13px" />
                    رد
                </button>
            </form>
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- جدول تاریخچه --}}
@if($subscriptions->isNotEmpty())
<div class="accsub__card" style="margin-top:1.25rem">
    <div class="accsub__card-head">تاریخچه اشتراک‌ها</div>
    <div style="overflow-x:auto">
        <table class="accsub__table">
            <thead>
                <tr>
                    <th>پلن</th>
                    <th>شروع</th>
                    <th>پایان</th>
                    <th>وضعیت</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($subscriptions as $sub)
                <tr>
                    <td class="accsub__table__pname">{{ $sub->plan?->name ?? '—' }}</td>
                    <td>{{ \App\Helpers\Jalali::formatFa($sub->starts_at, 'Y/m/d') }}</td>
                    <td>{{ \App\Helpers\Jalali::formatFa($sub->ends_at, 'Y/m/d') }}</td>
                    <td>
                        <span class="accsub__badge accsub__badge--{{ $sub->status }}">{{ $sub->getStatusLabel() }}</span>
                    </td>
                    <td>
                        <div style="display:flex;gap:.375rem;flex-wrap:wrap">
                            <a href="{{ route('account.subscriptions.show', $sub) }}" class="accsub__table-btn accsub__table-btn--sec">جزئیات</a>
                            @if($sub->isExpired())
                                <a href="{{ route('account.subscriptions.plans') }}" class="accsub__table-btn accsub__table-btn--renew">تمدید</a>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($subscriptions->hasPages())
    <div style="padding:.875rem 1.25rem;border-top:1px solid #f1f5f9">{{ $subscriptions->links() }}</div>
    @endif
</div>
@endif

</div>

@push('head')
<style>
.accsub, .accsub * { font-family: Vazirmatn, Tahoma, 'Segoe UI', Arial, sans-serif; }

/* active bar */
.accsub__active { background: -webkit-linear-gradient(135deg,#4f46e5,#7c3aed); background: linear-gradient(135deg,#4f46e5,#7c3aed); border-radius: 1rem; padding: 1.25rem 1.5rem; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; gap: 1rem; -ms-flex-wrap: wrap; flex-wrap: wrap; color: #fff; -webkit-box-shadow: 0 4px 16px rgba(79,70,229,.3); box-shadow: 0 4px 16px rgba(79,70,229,.3); }
.accsub__active-badge { background: rgba(255,255,255,.2); padding: .25rem .75rem; border-radius: 9999px; font-size: .75rem; font-weight: 700; -ms-flex-negative: 0; flex-shrink: 0; }
.accsub__active-info { -webkit-box-flex: 1; -ms-flex: 1; flex: 1; min-width: 150px; }
.accsub__active-plan { font-size: 1.0625rem; font-weight: 800; margin: 0; }
.accsub__active-meta { font-size: .8rem; opacity: .88; margin: .25rem 0 0; }
.accsub__active-btn { background: rgba(255,255,255,.18); border: 1px solid rgba(255,255,255,.35); padding: .5rem 1.125rem; border-radius: .625rem; font-size: .8125rem; font-weight: 700; color: #fff; text-decoration: none; -ms-flex-negative: 0; flex-shrink: 0; -webkit-transition: background .15s; transition: background .15s; }
.accsub__active-btn:hover { background: rgba(255,255,255,.28); }
.accsub__warn { color: #fde68a; font-weight: 700; }
.accsub__good { color: #bbf7d0; font-weight: 700; }

/* cta */
.accsub__cta { background: #f8fafc; border: 2px dashed #c7d2fe; border-radius: 1rem; padding: 2.5rem 1.5rem; text-align: center; }
.accsub__cta svg { display: block; margin: 0 auto .75rem; }
.accsub__cta-title { font-size: 1.0625rem; font-weight: 700; color: #334155; margin: 0 0 .375rem; }
.accsub__cta-desc { font-size: .875rem; color: #64748b; margin: 0 0 1.125rem; }
.accsub__cta-btn { display: inline-block; background: -webkit-linear-gradient(135deg,#4f46e5,#7c3aed); background: linear-gradient(135deg,#4f46e5,#7c3aed); color: #fff; padding: .625rem 1.5rem; border-radius: .75rem; font-weight: 700; text-decoration: none; font-size: .875rem; -webkit-box-shadow: 0 3px 10px rgba(79,70,229,.3); box-shadow: 0 3px 10px rgba(79,70,229,.3); }

/* card */
.accsub__card { background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem; overflow: hidden; -webkit-box-shadow: 0 1px 4px rgba(0,0,0,.05); box-shadow: 0 1px 4px rgba(0,0,0,.05); }
.accsub__card-head { padding: .875rem 1.25rem; background: #f8fafc; border-bottom: 1px solid #e2e8f0; font-size: .875rem; font-weight: 700; color: #374151; }

/* table */
.accsub__table { width: 100%; border-collapse: collapse; font-size: .875rem; min-width: 480px; }
.accsub__table th { text-align: right; padding: .625rem 1.25rem; background: #f8fafc; font-size: .72rem; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
.accsub__table td { padding: .75rem 1.25rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; color: #334155; }
.accsub__table tr:last-child td { border-bottom: none; }
.accsub__table tbody tr:hover td { background: #f8fafc; }
.accsub__table__pname { font-weight: 600; color: #1e293b; }

/* badge */
.accsub__badge { display: inline-block; padding: .22rem .65rem; border-radius: 9999px; font-size: .73rem; font-weight: 700; }
.accsub__badge--active    { background: #dcfce7; color: #15803d; }
.accsub__badge--trial     { background: #dbeafe; color: #1d4ed8; }
.accsub__badge--cancelled { background: #fee2e2; color: #be123c; }
.accsub__badge--expired   { background: #f1f5f9; color: #64748b; }
.accsub__badge--pending   { background: #fff7ed; color: #c2410c; }

/* buttons */
.accsub__table-btn { display: inline-block; padding: .28rem .75rem; border-radius: .5rem; font-size: .78rem; font-weight: 600; cursor: pointer; border: 1px solid transparent; font-family: Vazirmatn, Tahoma, sans-serif; text-decoration: none; }
.accsub__table-btn--sec { background: #f1f5f9; color: #475569; border-color: #e2e8f0; }
.accsub__table-btn--sec:hover { background: #e2e8f0; }
.accsub__table-btn--danger { background: #fff1f2; color: #be123c; border-color: #fecdd3; }
.accsub__table-btn--danger:hover { background: #fee2e2; }
.accsub__table-btn--renew { background: #fffbeb; color: #92400e; border-color: #fde68a; }
.accsub__table-btn--renew:hover { background: #fef3c7; }

/* auto-renew badge */
.accsub__autorenew-badge { display:inline-block; background:rgba(255,255,255,.2); border-radius:9999px; padding:.1rem .5rem; font-size:.72rem; font-weight:600; }

/* expired bar */
.accsub__expired-bar { display:-webkit-box; display:-ms-flexbox; display:flex; -webkit-box-align:center; -ms-flex-align:center; align-items:center; gap:.875rem; background:#fffbeb; border:1.5px solid #fde68a; border-radius:1rem; padding:1rem 1.25rem; -ms-flex-wrap:wrap; flex-wrap:wrap; }
.accsub__expired-bar__icon { -ms-flex-negative:0; flex-shrink:0; }
.accsub__expired-bar__title { font-size:.9rem; font-weight:700; color:#92400e; margin:0 0 .2rem; }
.accsub__expired-bar__sub   { font-size:.78rem; color:#b45309; margin:0; }
.accsub__expired-bar__btn   { display:-webkit-inline-box; display:-ms-inline-flexbox; display:inline-flex; -webkit-box-align:center; -ms-flex-align:center; align-items:center; gap:.3rem; padding:.55rem 1.125rem; background:#f59e0b; color:#fff; border-radius:.75rem; font-size:.83rem; font-weight:700; text-decoration:none; -ms-flex-negative:0; flex-shrink:0; }
.accsub__expired-bar__btn:hover { background:#d97706; }
</style>
@endpush

@endsection
