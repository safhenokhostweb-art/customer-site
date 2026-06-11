@extends('admin.layout')
@section('title', 'سفارش ' . $order->number)
@section('header', 'جزئیات سفارش')
@push('head')
<style>
.order-show-grid{display:grid;grid-template-columns:1fr 340px;gap:1.5rem;align-items:start}
@media(max-width:900px){.order-show-grid{grid-template-columns:1fr}}

.info-block{background:#fff;border:1px solid #e2e8f0;border-radius:1rem;overflow:hidden;margin-bottom:1.25rem}
.info-block-header{padding:.875rem 1.25rem;background:#f8fafc;border-bottom:1px solid #e2e8f0;font-weight:700;font-size:.88rem;color:#1e293b;display:flex;align-items:center;gap:.5rem}
.info-block-body{padding:1.25rem}

.order-meta-row{display:flex;align-items:flex-start;gap:.75rem;padding:.625rem 0;border-bottom:1px solid #f1f5f9}
.order-meta-row:last-child{border-bottom:none}
.order-meta-label{font-size:.75rem;color:#64748b;font-weight:600;min-width:120px;padding-top:.1rem}
.order-meta-value{font-size:.85rem;color:#1e293b;flex:1}

.order-status-badge{display:inline-flex;align-items:center;padding:.3rem .9rem;border-radius:9999px;font-size:.82rem;font-weight:700}
.order-status-badge--pending{background:#fef9c3;color:#92400e}
.order-status-badge--paid{background:#dcfce7;color:#166534}
.order-status-badge--processing{background:#e0f2fe;color:#075985}
.order-status-badge--shipped{background:#ede9fe;color:#5b21b6}
.order-status-badge--completed{background:#d1fae5;color:#064e3b}
.order-status-badge--cancelled{background:#fee2e2;color:#991b1b}

.timeline{position:relative;padding-right:1.5rem}
.timeline::before{content:'';position:absolute;right:.375rem;top:0;bottom:0;width:2px;background:#e2e8f0}
.timeline-item{position:relative;padding-bottom:1.25rem}
.timeline-item:last-child{padding-bottom:0}
.timeline-dot{position:absolute;right:-.9375rem;top:.25rem;width:.875rem;height:.875rem;border-radius:50%;border:2px solid #fff;box-shadow:0 0 0 2px #6366f1;background:#6366f1}
.timeline-dot--muted{background:#94a3b8;box-shadow:0 0 0 2px #94a3b8}
.timeline-time{font-size:.7rem;color:#94a3b8;margin-bottom:.2rem}
.timeline-text{font-size:.8rem;color:#334155}
.timeline-reason{font-size:.75rem;color:#64748b;margin-top:.2rem;font-style:italic}

.price-row{display:flex;justify-content:space-between;align-items:center;padding:.375rem 0;font-size:.85rem}
.price-row--total{border-top:2px solid #e2e8f0;margin-top:.5rem;padding-top:.75rem;font-weight:800;font-size:1rem;color:#1e293b}
</style>
@endpush
@section('content')

@include('admin.partials.breadcrumb', ['crumbs' => [
    ['label' => 'داشبورد', 'url' => route('admin.dashboard')],
    ['label' => 'سفارشات', 'url' => route('admin.orders.index')],
    ['label' => $order->number, 'url' => null],
]])

@if(session('success'))
    <div class="alert alert-success mb-4">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger mb-4">{{ session('error') }}</div>
@endif

<div class="admin-page">
    {{-- ── Header ── --}}
    <div class="admin-page-head" style="margin-bottom:1.5rem">
        <div>
            <h1 class="admin-page-title" style="display:flex;align-items:center;gap:.75rem">
                <span>سفارش {{ $order->number }}</span>
                @php
                    $sm = ['pending'=>['در انتظار','pending'],'paid'=>['پرداخت شده','paid'],'processing'=>['در حال پردازش','processing'],'shipped'=>['ارسال شده','shipped'],'completed'=>['تکمیل شده','completed'],'cancelled'=>['لغو شده','cancelled']];
                    [$lbl,$cls] = $sm[$order->status] ?? [$order->status,'pending'];
                @endphp
                <span class="order-status-badge order-status-badge--{{ $cls }}">{{ $lbl }}</span>
            </h1>
            <p class="text-slate-500 text-sm mt-1">ثبت: {{ \App\Helpers\Jalali::formatFa($order->created_at,'Y/m/d H:i') }}</p>
        </div>
        <div class="admin-page-tools">
            <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">بازگشت به فهرست</a>
        </div>
    </div>

    <div class="order-show-grid">
        {{-- ── Left Column ── --}}
        <div>
            {{-- Order Items --}}
            <div class="info-block">
                <div class="info-block-header">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    آیتم‌های سفارش
                </div>
                <div class="table-responsive" style="margin:0">
                    <table style="margin:0">
                        <thead>
                            <tr>
                                <th>محصول</th>
                                <th style="text-align:center">تعداد</th>
                                <th style="text-align:left">قیمت واحد</th>
                                <th style="text-align:left">جمع</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                                <tr>
                                    <td>
                                        <div class="font-medium text-sm">{{ $item->product_title }}</div>
                                        @if($item->variant_label)
                                            <div class="text-xs text-slate-500">{{ $item->variant_label }}</div>
                                        @endif
                                    </td>
                                    <td style="text-align:center">
                                        <span class="font-semibold">{{ \App\Helpers\NumberHelper::formatFa($item->quantity) }}</span>
                                    </td>
                                    <td style="text-align:left;white-space:nowrap">
                                        {{ \App\Helpers\NumberHelper::formatFa((int)$item->price) }}
                                        <span class="text-xs text-slate-500">تومان</span>
                                    </td>
                                    <td style="text-align:left;white-space:nowrap">
                                        <span class="font-semibold">{{ \App\Helpers\NumberHelper::formatFa((int)$item->total) }}</span>
                                        <span class="text-xs text-slate-500">تومان</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Price Summary --}}
                <div style="padding:1rem 1.25rem;border-top:1px solid #e2e8f0;background:#f8fafc">
                    <div class="price-row">
                        <span class="text-slate-600">جمع آیتم‌ها</span>
                        <span>{{ \App\Helpers\NumberHelper::formatFa((int)$order->subtotal) }} تومان</span>
                    </div>
                    @if($order->shipping_cost > 0)
                        <div class="price-row">
                            <span class="text-slate-600">هزینه ارسال</span>
                            <span>{{ \App\Helpers\NumberHelper::formatFa((int)$order->shipping_cost) }} تومان</span>
                        </div>
                    @endif
                    @if($order->discount_amount > 0)
                        <div class="price-row" style="color:#16a34a">
                            <span>تخفیف کوپن @if($order->coupon)<span class="font-mono text-xs">({{ $order->coupon->code }})</span>@endif</span>
                            <span>- {{ \App\Helpers\NumberHelper::formatFa((int)$order->discount_amount) }} تومان</span>
                        </div>
                    @endif
                    @if($order->membership_discount_amount > 0)
                        <div class="price-row" style="color:#7c3aed">
                            <span>تخفیف عضویت @if($order->membership_plan_name)<span class="text-xs">({{ $order->membership_plan_name }})</span>@endif</span>
                            <span>- {{ \App\Helpers\NumberHelper::formatFa((int)$order->membership_discount_amount) }} تومان</span>
                        </div>
                    @endif
                    @if($order->points_discount_amount > 0)
                        <div class="price-row" style="color:#0284c7">
                            <span>تخفیف امتیاز ({{ \App\Helpers\NumberHelper::formatFa($order->points_spent) }} امتیاز)</span>
                            <span>- {{ \App\Helpers\NumberHelper::formatFa((int)$order->points_discount_amount) }} تومان</span>
                        </div>
                    @endif
                    <div class="price-row price-row--total">
                        <span>مجموع نهایی</span>
                        <span style="color:#6366f1">{{ \App\Helpers\NumberHelper::formatFa((int)$order->total) }} تومان</span>
                    </div>
                    @if($order->points_earned > 0)
                        <div class="price-row" style="color:#0d9488;font-size:.78rem">
                            <span>امتیاز کسب‌شده از این خرید</span>
                            <span>+{{ \App\Helpers\NumberHelper::formatFa($order->points_earned) }} امتیاز</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Status History --}}
            @if($order->statusHistory->count())
            <div class="info-block">
                <div class="info-block-header">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0"/></svg>
                    تاریخچه وضعیت
                </div>
                <div class="info-block-body">
                    <div class="timeline">
                        @foreach($order->statusHistory as $h)
                            @php $isLatest = $loop->last; @endphp
                            <div class="timeline-item">
                                <div class="timeline-dot {{ $isLatest ? '' : 'timeline-dot--muted' }}"></div>
                                <div class="timeline-time">{{ \App\Helpers\Jalali::formatFa($h->created_at,'Y/m/d H:i') }}</div>
                                <div class="timeline-text">
                                    @php $stm = ['pending'=>'در انتظار','paid'=>'پرداخت شده','processing'=>'در حال پردازش','shipped'=>'ارسال شده','completed'=>'تکمیل شده','cancelled'=>'لغو شده']; @endphp
                                    تغییر به <strong>{{ $stm[$h->status] ?? $h->status }}</strong>
                                    @if($h->changedBy)
                                        توسط {{ $h->changedBy->name }}
                                    @endif
                                </div>
                                @if($h->reason)
                                    <div class="timeline-reason">"{{ $h->reason }}"</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- ── Right Column ── --}}
        <div>
            {{-- Change Status --}}
            @if($order->status !== 'cancelled')
            <div class="info-block" style="margin-bottom:1.25rem">
                <div class="info-block-header">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    تغییر وضعیت
                </div>
                <div class="info-block-body">
                    <form method="POST" action="{{ route('admin.orders.update-status', $order) }}">
                        @csrf
                        @method('PATCH')
                        <div class="form-group" style="margin-bottom:.75rem">
                            <label class="form-label" style="font-size:.78rem">وضعیت جدید</label>
                            <select name="status" class="form-control" style="font-size:.85rem">
                                @php $statusOpts=['pending'=>'در انتظار','paid'=>'پرداخت شده','processing'=>'در حال پردازش','shipped'=>'ارسال شده','completed'=>'تکمیل شده','cancelled'=>'لغو شده']; @endphp
                                @foreach($statusOpts as $val=>$lbl)
                                    <option value="{{ $val }}" {{ $order->status === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom:.75rem">
                            <label class="form-label" style="font-size:.78rem">دلیل (اختیاری)</label>
                            <input type="text" name="reason" class="form-control" placeholder="مثلاً: محصول ارسال شد" style="font-size:.85rem">
                        </div>
                        <button type="submit" class="btn btn-primary" style="width:100%;font-size:.85rem">ثبت تغییر وضعیت</button>
                    </form>
                </div>
            </div>
            @endif

            {{-- Customer Info --}}
            <div class="info-block">
                <div class="info-block-header">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    اطلاعات مشتری
                </div>
                <div class="info-block-body" style="padding:.75rem 1.25rem">
                    <div class="order-meta-row">
                        <span class="order-meta-label">نام</span>
                        <span class="order-meta-value font-medium">{{ $order->customer_name ?: '—' }}</span>
                    </div>
                    @if($order->customer_mobile)
                    <div class="order-meta-row">
                        <span class="order-meta-label">موبایل</span>
                        <span class="order-meta-value" dir="ltr">{{ $order->customer_mobile }}</span>
                    </div>
                    @endif
                    @if($order->customer_phone)
                    <div class="order-meta-row">
                        <span class="order-meta-label">تلفن ثابت</span>
                        <span class="order-meta-value" dir="ltr">{{ $order->customer_phone }}</span>
                    </div>
                    @endif
                    @if($order->customer_address)
                    <div class="order-meta-row">
                        <span class="order-meta-label">آدرس</span>
                        <span class="order-meta-value text-sm">{{ $order->customer_address }}</span>
                    </div>
                    @endif
                    @if($order->user)
                    <div class="order-meta-row">
                        <span class="order-meta-label">حساب کاربری</span>
                        <span class="order-meta-value">
                            <a href="{{ route('admin.users.index') }}" class="text-indigo-600 text-sm hover:underline">{{ $order->user->name }}</a>
                        </span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Order Meta --}}
            <div class="info-block">
                <div class="info-block-header">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    اطلاعات سفارش
                </div>
                <div class="info-block-body" style="padding:.75rem 1.25rem">
                    <div class="order-meta-row">
                        <span class="order-meta-label">شماره سفارش</span>
                        <span class="order-meta-value font-mono font-semibold text-indigo-700">{{ $order->number }}</span>
                    </div>
                    <div class="order-meta-row">
                        <span class="order-meta-label">تاریخ ثبت</span>
                        <span class="order-meta-value">{{ \App\Helpers\Jalali::formatFa($order->created_at,'Y/m/d H:i') }}</span>
                    </div>
                    @if($order->payment_method)
                    <div class="order-meta-row">
                        <span class="order-meta-label">روش پرداخت</span>
                        <span class="order-meta-value">{{ $order->payment_method }}</span>
                    </div>
                    @endif
                    @if($order->note)
                    <div class="order-meta-row">
                        <span class="order-meta-label">یادداشت</span>
                        <span class="order-meta-value text-sm italic">{{ $order->note }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
