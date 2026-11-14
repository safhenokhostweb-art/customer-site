@extends('admin.layout')
@section('title', 'درخواست‌های ماشین‌حساب خورشیدی')
@section('header', 'ماشین‌حساب خورشیدی')
@section('content')
@include('modules.solar-calculator.admin._chrome', ['crumb' => 'درخواست‌ها'])

@php
    $fa = fn ($n) => \SaaSPlatform\ThemeRuntime\Support\PersianNumber::format($n);
    $types = ['on_grid' => 'متصل به شبکه', 'off_grid' => 'مستقل', 'hybrid' => 'هیبرید'];
@endphp

<div class="admin-page">
    <div class="sc-kpis">
        <a class="sc-kpi {{ request('status', '') === '' ? 'is-active' : '' }}" href="{{ route('admin.solar-calculator.leads.index', request()->except('status', 'page')) }}">
            <span>همهٔ درخواست‌ها</span><b>{{ $fa($total) }}</b>
        </a>
        @foreach($statuses as $key => $label)
            <a class="sc-kpi {{ request('status') === $key ? 'is-active' : '' }}" href="{{ route('admin.solar-calculator.leads.index', array_merge(request()->except('status', 'page'), ['status' => $key])) }}">
                <span>{{ $label }}</span><b>{{ $fa($counts[$key] ?? 0) }}</b>
            </a>
        @endforeach
    </div>

    <form method="GET" action="{{ route('admin.solar-calculator.leads.index') }}" class="card" style="padding:1rem 1.25rem;margin-bottom:1rem">
        @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
        <div style="display:flex;flex-wrap:wrap;gap:.75rem;align-items:flex-end">
            <div style="flex:1;min-width:220px">
                <label class="form-label" for="sc-q" style="font-size:.75rem">جستجو</label>
                <input id="sc-q" type="search" name="q" value="{{ request('q') }}" placeholder="نام، موبایل یا شمارهٔ پیش‌فاکتور" class="form-control" style="height:2.4rem">
            </div>
            <div style="min-width:160px">
                <label class="form-label" for="sc-type" style="font-size:.75rem">نوع سیستم</label>
                <select id="sc-type" name="system_type" class="form-select" style="height:2.4rem">
                    <option value="">همه</option>
                    @foreach($types as $k => $v)<option value="{{ $k }}" @selected(request('system_type') === $k)>{{ $v }}</option>@endforeach
                </select>
            </div>
            <div style="display:flex;gap:.5rem">
                <button type="submit" class="btn btn-primary" style="height:2.4rem"><x-core-ui::icon name="search" class="w-4 h-4" />جستجو</button>
                @if(request()->hasAny(['q', 'status', 'system_type']))
                    <a href="{{ route('admin.solar-calculator.leads.index') }}" class="btn btn-secondary" style="height:2.4rem">پاک کردن</a>
                @endif
                <a href="{{ route('admin.solar-calculator.leads.export', request()->except('page')) }}" class="btn btn-outline-primary" style="height:2.4rem"><x-core-ui::icon name="download" class="w-4 h-4" />خروجی CSV</a>
            </div>
        </div>
    </form>

    <div class="card admin-table-wrap">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>شماره</th>
                        <th>درخواست‌دهنده</th>
                        <th>سیستم</th>
                        <th>ظرفیت</th>
                        <th>مبلغ برآوردی</th>
                        <th>وضعیت</th>
                        <th>تاریخ</th>
                        <th style="width:90px">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leads as $lead)
                        <tr>
                            <td><span class="font-mono text-sm font-semibold text-indigo-700" dir="ltr">{{ $lead->quote_number }}</span></td>
                            <td>
                                <div class="font-medium text-sm">{{ $lead->name }}</div>
                                <div class="text-xs text-slate-500" dir="ltr">{{ \SaaSPlatform\ThemeRuntime\Support\PersianNumber::digits($lead->mobile) }}</div>
                            </td>
                            <td class="text-sm">
                                {{ $types[$lead->system_type] ?? $lead->system_type }}
                                <div class="text-xs text-slate-500">{{ $regions[$lead->region_key] ?? $lead->region_key }}</div>
                            </td>
                            <td class="text-sm">{{ \SaaSPlatform\ThemeRuntime\Support\PersianNumber::format($lead->results['values']['system_kw'] ?? 0, 1) }} <span class="text-xs text-slate-500">kW</span></td>
                            <td class="text-sm">
                                @if($lead->total_price !== null)
                                    <span class="font-semibold">{{ $fa($lead->total_price) }}</span> <span class="text-xs text-slate-500">تومان</span>
                                @else
                                    <span class="text-xs text-slate-500">بدون قیمت (نرخ ارز ثبت نشده)</span>
                                @endif
                            </td>
                            <td><span class="sc-badge sc-badge--{{ $lead->status }}">{{ $lead->statusLabel() }}</span></td>
                            <td class="text-xs text-slate-600">{{ \App\Helpers\Jalali::formatFa($lead->created_at, 'Y/m/d H:i') }}</td>
                            <td><a href="{{ route('admin.solar-calculator.leads.show', $lead) }}" class="btn btn-primary btn-sm">مشاهده</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-0">
                                <div class="admin-empty-state">
                                    <x-core-ui::icon name="calculator" class="admin-empty-state__icon" />
                                    <p class="admin-empty-state__title">هنوز درخواستی ثبت نشده است</p>
                                    <p class="admin-empty-state__text">ویجت «ماشین‌حساب خورشیدی» را از ویرایشگر قالب به یک صفحه اضافه کنید؛ هر پیش‌فاکتور صادرشده اینجا و در CRM ثبت می‌شود.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($leads->hasPages())
        <div class="admin-pagination">{{ $leads->links() }}</div>
    @endif
</div>
@endsection
