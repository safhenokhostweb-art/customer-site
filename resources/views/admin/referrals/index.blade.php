@extends('admin.layout')
@section('title', 'برنامه معرفی دوستان')
@section('header', 'معرفی دوستان')
@push('head')
<style>
.ref-stat{padding:1rem 1.25rem;border-radius:.875rem;background:#fff;border:1px solid #e2e8f0;box-shadow:0 1px 3px rgba(15,23,42,.05)}
.ref-stat__label{font-size:.72rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:.25rem}
.ref-stat__value{font-size:1.6rem;font-weight:800;color:#1e293b;line-height:1}
.ref-stat__sub{font-size:.7rem;color:#94a3b8;margin-top:.2rem}

.ref-code-card{border-radius:.875rem;background:#fff;border:1px solid #e2e8f0;box-shadow:0 1px 3px rgba(15,23,42,.05);margin-bottom:.875rem;overflow:hidden;transition:box-shadow .2s}
.ref-code-card:hover{box-shadow:0 4px 16px rgba(99,102,241,.1)}
.ref-code-head{display:flex;align-items:center;gap:1rem;padding:.875rem 1.25rem;border-bottom:1px solid #f1f5f9}
.ref-code-body{padding:.75rem 1.25rem}
.ref-code-icon{width:2.5rem;height:2.5rem;border-radius:.625rem;background:linear-gradient(135deg,#6366f1,#4f46e5);display:flex;align-items:center;justify-content:center;flex-shrink:0}
.ref-code-text{font-size:1rem;font-weight:700;font-family:monospace;color:#4f46e5;background:#ede9fe;padding:.2rem .7rem;border-radius:.5rem;letter-spacing:.05em}
.ref-badge{display:inline-flex;align-items:center;padding:.15rem .55rem;border-radius:9999px;font-size:.68rem;font-weight:700}
.ref-badge--active{background:#dcfce7;color:#166534}
.ref-badge--inactive{background:#fee2e2;color:#991b1b}
.ref-badge--rewarded{background:#ede9fe;color:#6d28d9}
.ref-badge--pending{background:#fef9c3;color:#92400e}

.ref-referral-row{display:flex;align-items:center;gap:.75rem;padding:.4rem 0;border-bottom:1px solid #f8fafc;font-size:.8rem}
.ref-referral-row:last-child{border-bottom:none}
</style>
@endpush
@section('content')

@include('admin.partials.breadcrumb', ['crumbs' => [
    ['label' => 'داشبورد', 'url' => route('admin.dashboard')],
    ['label' => 'معرفی دوستان', 'url' => null],
]])

<div class="admin-page">
    {{-- ── KPI Stats ── --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:1rem;margin-bottom:1.5rem">
        <div class="ref-stat" style="background:linear-gradient(135deg,#6366f1,#1e1b4b);border:none">
            <div class="ref-stat__label" style="color:rgba(255,255,255,.75)">امتیاز اهدایی</div>
            <div class="ref-stat__value" style="color:#fff">{{ \App\Helpers\NumberHelper::formatFa((int)$stats['points_awarded']) }}</div>
            <div class="ref-stat__sub" style="color:rgba(255,255,255,.6)">جمع امتیازهای پرداخت‌شده</div>
        </div>
        <div class="ref-stat">
            <div class="ref-stat__label">کد معرفی</div>
            <div class="ref-stat__value">{{ \App\Helpers\NumberHelper::formatFa($stats['total_codes']) }}</div>
            <div class="ref-stat__sub">{{ \App\Helpers\NumberHelper::formatFa($stats['active_codes']) }} کد فعال</div>
        </div>
        <div class="ref-stat">
            <div class="ref-stat__label">کل معرفی‌ها</div>
            <div class="ref-stat__value">{{ \App\Helpers\NumberHelper::formatFa($stats['total_referrals']) }}</div>
            <div class="ref-stat__sub">از همه کدها</div>
        </div>
        <div class="ref-stat">
            <div class="ref-stat__label">پاداش داده شده</div>
            <div class="ref-stat__value" style="color:#6d28d9">{{ \App\Helpers\NumberHelper::formatFa($stats['rewarded']) }}</div>
            <div class="ref-stat__sub">معرفی تأیید شده</div>
        </div>
        <div class="ref-stat">
            <div class="ref-stat__label">در انتظار</div>
            <div class="ref-stat__value" style="color:#d97706">{{ \App\Helpers\NumberHelper::formatFa($stats['pending']) }}</div>
            <div class="ref-stat__sub">معرفی در صف</div>
        </div>
    </div>

    {{-- ── Referral Codes ── --}}
    @forelse($codes as $code)
        <div class="ref-code-card">
            <div class="ref-code-head">
                <div class="ref-code-icon">
                    <x-core-ui::icon name="referral" class="w-5 h-5 text-white" />
                </div>
                <div style="flex:1">
                    <div style="display:flex;align-items:center;gap:.625rem;flex-wrap:wrap">
                        <span class="ref-code-text">{{ $code->code }}</span>
                        @if($code->is_active)
                            <span class="ref-badge ref-badge--active">فعال</span>
                        @else
                            <span class="ref-badge ref-badge--inactive">غیرفعال</span>
                        @endif
                    </div>
                    @if($code->user)
                        <div style="font-size:.78rem;color:#64748b;margin-top:.25rem">
                            👤 {{ $code->user->name }}
                            @if($code->user->mobile)
                                <span dir="ltr" style="margin-right:.5rem">{{ $code->user->mobile }}</span>
                            @endif
                        </div>
                    @endif
                </div>
                <div style="text-align:left;flex-shrink:0">
                    <div style="font-size:.72rem;color:#64748b">معرفی‌ها</div>
                    <div style="font-size:1.25rem;font-weight:800;color:#1e293b">{{ \App\Helpers\NumberHelper::formatFa($code->referrals_count) }}</div>
                    @if($code->rewarded_count > 0)
                        <div style="font-size:.68rem;color:#6d28d9">{{ \App\Helpers\NumberHelper::formatFa($code->rewarded_count) }} پاداش</div>
                    @endif
                </div>
            </div>

            @if($code->referrals->count())
            <div class="ref-code-body">
                @foreach($code->referrals->take(5) as $ref)
                    <div class="ref-referral-row">
                        <x-core-ui::icon name="user" class="w-3.5 h-3.5 text-slate-400" />
                        <span style="flex:1;color:#334155">{{ $ref->referredUser?->name ?? 'کاربر ناشناس' }}</span>
                        @if($ref->status === 'rewarded')
                            <span class="ref-badge ref-badge--rewarded">
                                ✓ {{ \App\Helpers\NumberHelper::formatFa($ref->points_awarded ?? 0) }} امتیاز
                            </span>
                        @else
                            <span class="ref-badge ref-badge--pending">در انتظار</span>
                        @endif
                        <span style="color:#94a3b8;font-size:.68rem">{{ \App\Helpers\Jalali::formatFa($ref->created_at,'Y/m/d') }}</span>
                    </div>
                @endforeach
                @if($code->referrals->count() > 5)
                    <div style="font-size:.75rem;color:#94a3b8;padding:.5rem 0">
                        + {{ \App\Helpers\NumberHelper::formatFa($code->referrals->count() - 5) }} معرفی دیگر…
                    </div>
                @endif
            </div>
            @endif
        </div>
    @empty
        <div class="admin-empty-state">
            <x-core-ui::icon name="referral" class="admin-empty-state__icon" />
            <p class="admin-empty-state__title">هنوز کد معرفی ثبت نشده</p>
            <p class="admin-empty-state__text">کاربران پس از ثبت‌نام، کد معرفی دریافت می‌کنند و می‌توانند دوستان خود را دعوت کنند.</p>
        </div>
    @endforelse

    @if($codes->hasPages())
        <div class="admin-pagination">{{ $codes->links() }}</div>
    @endif
</div>
@endsection
