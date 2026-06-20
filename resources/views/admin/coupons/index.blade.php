@extends('admin.layout')
@section('title', 'کوپن‌ها')
@push('head')
<style>
.cpn-stat{padding:.875rem 1.25rem;border-radius:.875rem;background:#fff;border:1px solid #e2e8f0;box-shadow:0 1px 3px rgba(15,23,42,.05)}
.cpn-stat__label{font-size:.72rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:.25rem}
.cpn-stat__value{font-size:1.6rem;font-weight:800;color:#1e293b;line-height:1}

.cpn-code{font-family:monospace;font-weight:700;font-size:.95rem;color:#4f46e5;background:#ede9fe;padding:.2rem .65rem;border-radius:.4rem;direction:ltr;display:inline-block}
.cpn-badge{display:inline-flex;align-items:center;padding:.2rem .6rem;border-radius:9999px;font-size:.7rem;font-weight:700}
.cpn-badge--active{background:#dcfce7;color:#166534}
.cpn-badge--inactive{background:#f1f5f9;color:#475569}
.cpn-badge--expired{background:#fee2e2;color:#991b1b}
.cpn-badge--scheduled{background:#fef9c3;color:#92400e}
.cpn-badge--fop{background:#e0f2fe;color:#075985}

.cpn-progress{height:5px;background:#e2e8f0;border-radius:9999px;overflow:hidden;margin-top:.3rem}
.cpn-progress-bar{height:100%;border-radius:9999px;background:linear-gradient(90deg,#6366f1,#4f46e5);transition:width .3s}
.cpn-progress-bar--warn{background:linear-gradient(90deg,#f59e0b,#d97706)}
.cpn-progress-bar--full{background:linear-gradient(90deg,#ef4444,#dc2626)}
</style>
@endpush
@section('content')

@include('admin.coupons.guide')

<div class="admin-page">
    <div class="admin-page-head">
        <div>
            <h1 class="admin-page-title">کوپن‌های تخفیف</h1>
            <p class="admin-section-lead" style="margin-top:.3rem">مدیریت کدهای تخفیف، سقف استفاده و تاریخ انقضا.</p>
        </div>
        <div class="admin-page-tools">
            <a href="{{ route('admin.coupons.create') }}" class="btn btn-primary">
                <x-core-ui::icon name="plus" class="w-4 h-4" />
                کوپن جدید
            </a>
        </div>
    </div>

    {{-- KPIs --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:1rem;margin-bottom:1.5rem">
        <div class="cpn-stat">
            <div class="cpn-stat__label">کل کوپن‌ها</div>
            <div class="cpn-stat__value">{{ \App\Helpers\NumberHelper::formatFa($stats['total']) }}</div>
        </div>
        <div class="cpn-stat">
            <div class="cpn-stat__label">فعال</div>
            <div class="cpn-stat__value" style="color:#16a34a">{{ \App\Helpers\NumberHelper::formatFa($stats['active']) }}</div>
        </div>
        <div class="cpn-stat">
            <div class="cpn-stat__label">منقضی شده</div>
            <div class="cpn-stat__value" style="color:#dc2626">{{ \App\Helpers\NumberHelper::formatFa($stats['expired']) }}</div>
        </div>
        <div class="cpn-stat">
            <div class="cpn-stat__label">مجموع استفاده</div>
            <div class="cpn-stat__value" style="color:#6366f1">{{ \App\Helpers\NumberHelper::formatFa($stats['usages']) }}</div>
        </div>
    </div>

    {{-- Table --}}
    <div class="card admin-table-wrap">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>کد کوپن</th>
                        <th>نوع و مقدار</th>
                        <th>استفاده</th>
                        <th>انقضا</th>
                        <th>وضعیت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($coupons as $coupon)
                        @php
                            $isExpired   = $coupon->expires_at && $coupon->expires_at->isPast();
                            $isScheduled = $coupon->starts_at && $coupon->starts_at->isFuture();
                            $usagePct    = $coupon->usage_limit ? min(100, round($coupon->used_count / $coupon->usage_limit * 100)) : 0;
                            $barClass    = $usagePct >= 100 ? 'full' : ($usagePct >= 75 ? 'warn' : '');
                        @endphp
                        <tr>
                            <td>
                                <span class="cpn-code">{{ $coupon->code }}</span>
                                @if($coupon->first_order_only)
                                    <div style="margin-top:.3rem"><span class="cpn-badge cpn-badge--fop">فقط خرید اول</span></div>
                                @endif
                            </td>
                            <td>
                                @if($coupon->discount_type === 'percent')
                                    <span class="font-semibold text-indigo-700">{{ \App\Helpers\NumberHelper::formatFa($coupon->discount_percent) }}٪</span>
                                    <span class="text-xs text-slate-500">تخفیف</span>
                                @else
                                    <span class="font-semibold text-emerald-700">{{ \App\Helpers\NumberHelper::formatFa($coupon->discount_amount) }}</span>
                                    <span class="text-xs text-slate-500">تومان</span>
                                @endif
                                @if($coupon->min_order_amount)
                                    <div class="text-xs text-slate-500">حداقل: {{ \App\Helpers\NumberHelper::formatFa($coupon->min_order_amount) }} ت</div>
                                @endif
                            </td>
                            <td>
                                <div class="text-sm font-semibold text-slate-700">
                                    {{ \App\Helpers\NumberHelper::formatFa($coupon->used_count) }}
                                    @if($coupon->usage_limit)
                                        <span class="text-slate-400">/ {{ \App\Helpers\NumberHelper::formatFa($coupon->usage_limit) }}</span>
                                    @endif
                                </div>
                                @if($coupon->usage_limit)
                                    <div class="cpn-progress" style="width:80px">
                                        <div class="cpn-progress-bar cpn-progress-bar--{{ $barClass }}" style="width:{{ $usagePct }}%"></div>
                                    </div>
                                @endif
                            </td>
                            <td class="text-sm text-slate-600">
                                @if($coupon->expires_at)
                                    <span style="{{ $isExpired ? 'color:#dc2626;font-weight:600' : '' }}">
                                        {{ \App\Helpers\Jalali::formatFa($coupon->expires_at, 'Y/m/d') }}
                                    </span>
                                    @if($isExpired)
                                        <div class="text-xs" style="color:#dc2626">منقضی شده</div>
                                    @elseif($coupon->expires_at->diffInDays(now()) <= 7)
                                        <div class="text-xs" style="color:#d97706">در حال انقضا</div>
                                    @endif
                                @else
                                    <span class="text-slate-400">بدون انقضا</span>
                                @endif
                            </td>
                            <td>
                                @if(!$coupon->is_active)
                                    <span class="cpn-badge cpn-badge--inactive">غیرفعال</span>
                                @elseif($isExpired)
                                    <span class="cpn-badge cpn-badge--expired">منقضی</span>
                                @elseif($isScheduled)
                                    <span class="cpn-badge cpn-badge--scheduled">زمان‌بندی شده</span>
                                @else
                                    <span class="cpn-badge cpn-badge--active">فعال</span>
                                @endif
                            </td>
                            <td>
                                <div class="admin-table-actions">
                                    <a href="{{ route('admin.coupons.edit', $coupon) }}" class="btn btn-primary btn-sm">ویرایش</a>
                                    <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}"
                                          onsubmit="return confirm('کوپن «{{ $coupon->code }}» حذف شود؟');" style="display:inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-0">
                                <div class="admin-empty-state">
                                    <x-core-ui::icon name="price-tag" class="admin-empty-state__icon" />
                                    <p class="admin-empty-state__title">کوپنی ثبت نشده</p>
                                    <p class="admin-empty-state__text">با دکمهٔ «کوپن جدید» اولین کوپن تخفیف خود را بسازید.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($coupons->hasPages())
        <div class="admin-pagination">{{ $coupons->links() }}</div>
    @endif
</div>
@endsection



