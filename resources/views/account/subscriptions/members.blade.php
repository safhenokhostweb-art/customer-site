@extends('account.layout')
@section('title', 'مدیریت اعضای گروه')
@section('header', 'اشتراک گروهی')

@section('content')
<div class="grp-pg" style="font-family:Vazirmatn,Tahoma,'Segoe UI',Arial,sans-serif">

{{-- هدر اشتراک --}}
<div class="grp-pg__hero">
    <div class="grp-pg__hero-icon">
        <x-core-ui::icon name="users" style="width:24px;height:24px" />
    </div>
    <div class="grp-pg__hero-info">
        <h2 class="grp-pg__hero-title">{{ $subscription->plan->name }}</h2>
        <p class="grp-pg__hero-meta">
            حداکثر {{ \App\Helpers\NumberHelper::formatFa($subscription->plan->max_users) }} نفر
            &nbsp;·&nbsp;
            {{ \App\Helpers\NumberHelper::formatFa($availableSeats) }} صندلی خالی
            &nbsp;·&nbsp;
            انقضا: {{ \App\Helpers\Jalali::formatFa($subscription->ends_at, 'Y/m/d') }}
        </p>
    </div>
    <a href="{{ route('account.subscriptions.show', $subscription) }}" class="grp-pg__back-btn">بازگشت</a>
</div>

<div class="grp-layout">

    {{-- فرم دعوت --}}
    @if($availableSeats > 0)
    <div class="grp-card">
        <div class="grp-card__head">
            <x-core-ui::icon name="user-plus" style="width:16px;height:16px" />
            دعوت عضو جدید
        </div>
        <form method="POST" action="{{ route('account.subscriptions.members.invite', $subscription) }}" class="grp-invite-form">
            @csrf
            <div class="grp-invite-form__field">
                <label class="grp-invite-form__label">شماره موبایل عضو</label>
                <div class="grp-invite-form__row">
                    <input type="tel"
                           name="mobile"
                           value="{{ old('mobile') }}"
                           placeholder="09xxxxxxxxx"
                           dir="ltr"
                           maxlength="11"
                           class="grp-invite-form__input {{ $errors->has('mobile') ? 'grp-invite-form__input--err' : '' }}"
                           autocomplete="off">
                    <button type="submit" class="grp-invite-form__btn">
                        <x-core-ui::icon name="sms" style="width:15px;height:15px" />
                        ارسال دعوت
                    </button>
                </div>
                @error('mobile')
                <p class="grp-invite-form__err">{{ $message }}</p>
                @enderror
            </div>
            <p class="grp-invite-form__hint">
                <x-core-ui::icon name="info-circle" style="width:12px;height:12px" />
                کاربر باید قبلاً در سیستم ثبت‌نام کرده باشد. پس از ارسال، پیامک اطلاع‌رسانی می‌شود.
            </p>
        </form>
    </div>
    @else
    <div class="grp-card grp-card--warn">
        <div class="grp-card__head">
            <x-core-ui::icon name="alert-triangle" style="width:16px;height:16px" />
            ظرفیت پر است
        </div>
        <p class="grp-card__body-text">تمام صندلی‌های اشتراک گروهی اشغال شده‌اند. برای دعوت عضو جدید باید یکی از اعضای فعلی را حذف کنید.</p>
    </div>
    @endif

    {{-- لیست اعضا --}}
    <div class="grp-card">
        <div class="grp-card__head">
            <x-core-ui::icon name="users" style="width:16px;height:16px" />
            اعضای گروه ({{ \App\Helpers\NumberHelper::formatFa($members->count()) }} نفر)
        </div>

        @if($members->isEmpty())
        <div class="grp-empty">
            <x-core-ui::icon name="users" style="width:36px;height:36px" />
            <p>هنوز عضوی دعوت نشده است.</p>
        </div>
        @else
        <div class="grp-members-list">
            @foreach($members as $m)
            <div class="grp-member">
                <div class="grp-member__avatar">
                    {{ mb_substr($m->user->name ?? $m->user->mobile, 0, 1) }}
                </div>
                <div class="grp-member__info">
                    <strong class="grp-member__name">{{ $m->user->name ?? 'بدون نام' }}</strong>
                    <span class="grp-member__mobile" dir="ltr">{{ $m->user->mobile }}</span>
                    <div class="grp-member__meta">
                        <span class="grp-member__status grp-member__status--{{ $m->status }}">
                            @if($m->status === 'active')
                            <x-core-ui::icon name="check" style="width:11px;height:11px" />
                            @elseif($m->status === 'pending')
                            <x-core-ui::icon name="clock" style="width:11px;height:11px" />
                            @endif
                            {{ $m->getStatusLabel() }}
                        </span>
                        @if($m->joined_at)
                        <span class="grp-member__date">از {{ \App\Helpers\Jalali::formatFa($m->joined_at, 'Y/m/d') }}</span>
                        @else
                        <span class="grp-member__date">دعوت: {{ \App\Helpers\Jalali::formatFa($m->invited_at, 'Y/m/d') }}</span>
                        @endif
                    </div>
                </div>
                <form method="POST"
                      action="{{ route('account.subscriptions.members.remove', [$subscription, $m]) }}"
                      onsubmit="return confirm('عضو «{{ $m->user->name ?? $m->user->mobile }}» حذف شود؟')">
                    @csrf
                    <button type="submit" class="grp-member__remove-btn" title="حذف عضو">
                        <x-core-ui::icon name="trash" style="width:15px;height:15px" />
                        حذف
                    </button>
                </form>
            </div>
            @endforeach
        </div>
        @endif
    </div>

</div>{{-- /grp-layout --}}

{{-- راهنما --}}
<div class="grp-guide">
    <div class="grp-guide__item">
        <x-core-ui::icon name="sms" style="width:14px;height:14px" />
        دعوت‌نامه از طریق پیامک به شماره موبایل وارد شده ارسال می‌شود.
    </div>
    <div class="grp-guide__item">
        <x-core-ui::icon name="check" style="width:14px;height:14px" />
        اعضای فعال از تمام مزایای این پلن بهره‌مند می‌شوند.
    </div>
    <div class="grp-guide__item">
        <x-core-ui::icon name="info-circle" style="width:14px;height:14px" />
        با انقضا یا لغو اشتراک، عضویت تمام اعضا نیز پایان می‌یابد.
    </div>
</div>

</div>

@push('head')
<style>
.grp-pg,.grp-pg *{font-family:Vazirmatn,Tahoma,'Segoe UI',Arial,sans-serif}

/* Hero */
.grp-pg__hero{display:flex;align-items:center;gap:.875rem;background:#eff6ff;border:1.5px solid #bfdbfe;border-radius:.875rem;padding:1rem 1.25rem;margin-bottom:1.5rem;flex-wrap:wrap}
.grp-pg__hero-icon{width:44px;height:44px;background:#dbeafe;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#2563eb;flex-shrink:0}
.grp-pg__hero-info{flex:1;display:flex;flex-direction:column;gap:.2rem}
.grp-pg__hero-title{font-size:1.05rem;font-weight:800;color:#1e40af;margin:0}
.grp-pg__hero-meta{font-size:.8rem;color:#3b82f6;margin:0}
.grp-pg__back-btn{font-size:.8rem;color:#2563eb;font-weight:600;text-decoration:none;border:1px solid #93c5fd;padding:.35rem .875rem;border-radius:.5rem;flex-shrink:0;white-space:nowrap}
.grp-pg__back-btn:hover{background:#dbeafe}

/* Alerts */
.grp-pg__alert{display:flex;align-items:center;gap:.5rem;padding:.75rem 1rem;border-radius:.75rem;margin-bottom:1rem;font-size:.85rem;font-weight:600}
.grp-pg__alert--ok{background:#f0fdf4;color:#166534;border:1px solid #86efac}
.grp-pg__alert--err{background:#fff1f2;color:#be123c;border:1px solid #fecdd3}

/* Layout */
.grp-layout{display:grid;gap:1.25rem}
@media(min-width:768px){.grp-layout{grid-template-columns:1fr 2fr}}

/* Card */
.grp-card{background:#fff;border:1.5px solid #e2e8f0;border-radius:1.125rem;overflow:hidden}
.grp-card--warn{border-color:#fed7aa}
.grp-card__head{display:flex;align-items:center;gap:.5rem;font-size:.82rem;font-weight:700;color:#334155;padding:.875rem 1.125rem;border-bottom:1px solid #f1f5f9;background:#f8fafc}
.grp-card--warn .grp-card__head{background:#fff7ed;color:#9a3412;border-bottom-color:#fed7aa}
.grp-card__body-text{font-size:.83rem;color:#64748b;padding:1rem 1.125rem;line-height:1.65;margin:0}

/* Invite form */
.grp-invite-form{padding:1rem 1.125rem}
.grp-invite-form__field{display:flex;flex-direction:column;gap:.5rem}
.grp-invite-form__label{font-size:.8rem;font-weight:600;color:#475569}
.grp-invite-form__row{display:flex;gap:.5rem}
.grp-invite-form__input{flex:1;padding:.6rem .875rem;border:1.5px solid #e2e8f0;border-radius:.625rem;font-size:.875rem;font-family:monospace,'Courier New';color:#334155;outline:none;min-width:0}
.grp-invite-form__input:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.15)}
.grp-invite-form__input--err{border-color:#f87171}
.grp-invite-form__btn{display:inline-flex;align-items:center;gap:.35rem;padding:.6rem 1rem;background:#2563eb;color:#fff;border:none;border-radius:.625rem;font-size:.85rem;font-weight:700;cursor:pointer;font-family:Vazirmatn,Tahoma,sans-serif;white-space:nowrap;transition:background .15s}
.grp-invite-form__btn:hover{background:#1d4ed8}
.grp-invite-form__err{font-size:.75rem;color:#dc2626;margin:0}
.grp-invite-form__hint{display:flex;align-items:flex-start;gap:.375rem;font-size:.75rem;color:#94a3b8;margin:.625rem 0 0;line-height:1.5}

/* Empty */
.grp-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.625rem;padding:2.5rem 1rem;color:#94a3b8;text-align:center}
.grp-empty p{font-size:.85rem;margin:0}

/* Members list */
.grp-members-list{display:flex;flex-direction:column}
.grp-member{display:flex;align-items:center;gap:.875rem;padding:.875rem 1.125rem;border-bottom:1px solid #f8fafc;transition:background .15s}
.grp-member:last-child{border-bottom:none}
.grp-member:hover{background:#fafafa}
.grp-member__avatar{width:38px;height:38px;border-radius:50%;background:#eff6ff;color:#2563eb;font-size:.875rem;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1.5px solid #bfdbfe}
.grp-member__info{flex:1;display:flex;flex-direction:column;gap:.15rem;min-width:0}
.grp-member__name{font-size:.875rem;font-weight:700;color:#1e293b}
.grp-member__mobile{font-size:.78rem;color:#94a3b8;font-family:monospace,'Courier New';direction:ltr;display:inline-block}
.grp-member__meta{display:flex;align-items:center;gap:.625rem;flex-wrap:wrap;margin-top:.1rem}
.grp-member__status{display:inline-flex;align-items:center;gap:.25rem;font-size:.71rem;font-weight:700;padding:.15rem .5rem;border-radius:9999px}
.grp-member__status--active{background:#f0fdf4;color:#166534;border:1px solid #86efac}
.grp-member__status--pending{background:#fff7ed;color:#c2410c;border:1px solid #fed7aa}
.grp-member__date{font-size:.72rem;color:#94a3b8}
.grp-member__remove-btn{display:inline-flex;align-items:center;gap:.3rem;padding:.35rem .75rem;background:#fff1f2;color:#be123c;border:1px solid #fecdd3;border-radius:.5rem;font-size:.75rem;font-weight:700;cursor:pointer;font-family:Vazirmatn,Tahoma,sans-serif;transition:background .15s;white-space:nowrap;flex-shrink:0}
.grp-member__remove-btn:hover{background:#ffe4e6}

/* Guide */
.grp-guide{display:flex;flex-direction:column;gap:.5rem;margin-top:1.5rem;padding:1rem 1.25rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:.875rem}
.grp-guide__item{display:flex;align-items:flex-start;gap:.5rem;font-size:.78rem;color:#64748b;line-height:1.5}
</style>
@endpush

@endsection
