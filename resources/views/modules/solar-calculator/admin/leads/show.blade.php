@extends('admin.layout')
@section('title', 'درخواست ' . $lead->quote_number)
@section('header', 'ماشین‌حساب خورشیدی')
@section('content')
@include('modules.solar-calculator.admin._chrome', ['crumb' => $lead->quote_number])

@php
    $N = \SaaSPlatform\ThemeRuntime\Support\PersianNumber::class;
    $types = ['on_grid' => 'متصل به شبکه (On-Grid)', 'off_grid' => 'مستقل (Off-Grid)', 'hybrid' => 'هیبرید (Hybrid)'];
    $in = $lead->inputs ?? [];
    $data = $lead->quote['data'] ?? null;
@endphp

<div class="admin-page">
    <div class="sc-row-actions" style="margin-bottom:1rem">
        <a href="{{ route('admin.solar-calculator.leads.quote', $lead) }}" target="_blank" rel="noopener" class="btn btn-primary"><x-core-ui::icon name="file-text" class="w-4 h-4" />مشاهده / چاپ پیش‌فاکتور</a>
        @if($lead->crmContact)
            <a href="{{ route('admin.crm.contacts.show', $lead->crmContact) }}" class="btn btn-outline-primary"><x-core-ui::icon name="users" class="w-4 h-4" />پروندهٔ CRM</a>
        @endif
        <a href="tel:{{ $lead->mobile }}" class="btn btn-secondary"><x-core-ui::icon name="phone" class="w-4 h-4" />تماس</a>
    </div>

    <div class="sc-grid-2">
        <div style="display:flex;flex-direction:column;gap:1rem;min-width:0">
            <div class="card">
                <div class="card-header">مشخصات فنی محاسبه‌شده</div>
                <div class="card-body">
                    <div class="sc-spec-grid">
                        @foreach($lead->results['cards'] ?? [] as $card)
                            <div class="sc-spec"><span>{{ $card['label'] }}</span><b>{{ $card['display'] }}</b> <small class="text-slate-500">{{ $card['unit'] }}</small></div>
                        @endforeach
                    </div>
                    @if(($lead->results['roof_fits'] ?? true) === false)
                        <p class="admin-flash admin-flash--warning" style="margin-top:.75rem">فضای اعلام‌شده برای این تعداد پنل کافی نیست.</p>
                    @endif
                </div>
            </div>

            <div class="card admin-table-wrap">
                <div class="card-header">اقلام پیش‌فاکتور</div>
                @if($data)
                    <div class="table-responsive">
                        <table>
                            <thead><tr><th>شرح</th><th>تعداد</th><th>مبلغ واحد</th><th>مبلغ کل (تومان)</th></tr></thead>
                            <tbody>
                                @foreach($data['lines'] as $line)
                                    <tr>
                                        <td>{{ $line['label'] }}<div class="text-xs text-slate-500">{{ $line['detail'] }}</div></td>
                                        <td class="text-sm">{{ $line['qty_display'] }} {{ $line['qty_unit'] }}</td>
                                        <td class="text-sm">{!! $line['unit_price_html'] !!}</td>
                                        <td class="text-sm font-semibold">{!! $line['amount_html'] !!}</td>
                                    </tr>
                                @endforeach
                                @if(!empty($data['tax_html']))<tr><td colspan="3">{{ $data['tax_label'] }}</td><td>{!! $data['tax_html'] !!}</td></tr>@endif
                                <tr><td colspan="3" class="font-bold">مبلغ نهایی</td><td class="font-bold">{!! $data['total_html'] !!}</td></tr>
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="card-body"><p class="sc-help">هنگام صدور، نرخ ارز ثبت نشده بود؛ این پیش‌فاکتور بدون قیمت صادر شده است. پس از ثبت نرخ، با مشتری تماس بگیرید.</p></div>
                @endif
            </div>

            <div class="card">
                <div class="card-header">ورودی‌های مشتری</div>
                <div class="card-body">
                    <dl class="sc-dl">
                        <dt>نوع سیستم</dt><dd>{{ $types[$lead->system_type] ?? $lead->system_type }}</dd>
                        <dt>استان</dt><dd>{{ $regions[$lead->region_key] ?? $lead->region_key }}</dd>
                        @if(($in['consumption_mode'] ?? 'kwh') === 'bill')
                            <dt>مبلغ قبض ماهانه</dt><dd>{{ $N::format($in['monthly_bill'] ?? 0) }} تومان</dd>
                        @else
                            <dt>مصرف ماهانه</dt><dd>{{ $N::format($in['monthly_kwh'] ?? 0) }} کیلووات‌ساعت</dd>
                        @endif
                        <dt>فضای در دسترس</dt><dd>{{ ($in['roof_area'] ?? 0) > 0 ? $N::format($in['roof_area']) . ' مترمربع' : 'اعلام نشده' }}</dd>
                        @if($lead->system_type === 'hybrid')<dt>ساعات برق اضطراری</dt><dd>{{ $N::format($in['backup_hours'] ?? 0, 1) }}</dd>@endif
                        @if($lead->customer_note)<dt>توضیحات مشتری</dt><dd style="white-space:pre-line">{{ $lead->customer_note }}</dd>@endif
                    </dl>
                </div>
            </div>
        </div>

        <div style="display:flex;flex-direction:column;gap:1rem;min-width:0">
            <div class="card">
                <div class="card-header">پیگیری</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.solar-calculator.leads.update', $lead) }}" class="admin-form-stack">
                        @csrf @method('PUT')
                        <div class="form-group">
                            <label class="form-label" for="sc-status">وضعیت</label>
                            <select id="sc-status" name="status" class="form-select">
                                @foreach($statuses as $k => $v)<option value="{{ $k }}" @selected($lead->status === $k)>{{ $v }}</option>@endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="sc-note">یادداشت داخلی</label>
                            <textarea id="sc-note" name="admin_note" rows="4" class="form-control">{{ old('admin_note', $lead->admin_note) }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">ذخیره</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">درخواست‌دهنده</div>
                <div class="card-body">
                    <dl class="sc-dl">
                        <dt>نام</dt><dd>{{ $lead->name }}</dd>
                        <dt>موبایل</dt><dd dir="ltr" style="text-align:right">{{ $N::digits($lead->mobile) }}</dd>
                        @if($lead->email)<dt>ایمیل</dt><dd dir="ltr" style="text-align:right">{{ $lead->email }}</dd>@endif
                        <dt>حساب کاربری</dt><dd>{{ $lead->user?->name ?? 'مهمان' }}</dd>
                        <dt>ثبت</dt><dd>{{ \App\Helpers\Jalali::formatFa($lead->created_at, 'Y/m/d H:i') }}</dd>
                        <dt>مشاهدهٔ پیش‌فاکتور</dt><dd>{{ $lead->quote_viewed_at ? \App\Helpers\Jalali::formatFa($lead->quote_viewed_at, 'Y/m/d H:i') : 'هنوز باز نشده' }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card-header">نرخ ارز در لحظهٔ صدور</div>
                <div class="card-body">
                    <dl class="sc-dl">
                        @forelse($lead->exchange_rates ?? [] as $code => $r)
                            <dt dir="ltr">{{ $code }}</dt><dd>{{ ($r['rate'] ?? 0) > 0 ? $N::format($r['rate']) . ' تومان' : 'ثبت نشده' }}</dd>
                        @empty
                            <dt>—</dt><dd>بدون نرخ</dd>
                        @endforelse
                    </dl>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.solar-calculator.leads.destroy', $lead) }}" onsubmit="return confirm('این درخواست حذف شود؟');">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm"><x-core-ui::icon name="trash-2" class="w-4 h-4" />حذف درخواست</button>
            </form>
        </div>
    </div>
</div>
@endsection
