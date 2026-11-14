@extends('admin.layout')
@section('title', 'نرخ ارز')
@section('header', 'نرخ ارز')
@section('content')
@include('modules.solar-calculator.admin._chrome', ['crumb' => 'نرخ ارز'])

@php $N = \SaaSPlatform\ThemeRuntime\Support\PersianNumber::class; @endphp

<div class="admin-page">
    <p class="admin-section-lead">قیمت پایهٔ تجهیزات به ارز خارجی با این نرخ‌ها به تومان تبدیل می‌شود (در فرمول‌ها: <span class="sc-code">rate_usd</span>، <span class="sc-code">rate_eur</span>، …). تا نرخ ارزی ثبت نشده، قیمت‌های وابسته به آن به‌جای صفر «اعلام نشده» نمایش داده می‌شوند.</p>

    <div class="sc-grid-2">
        <form method="POST" action="{{ route('admin.exchange-rates.rates.update') }}" class="card admin-table-wrap">
            @csrf @method('PUT')
            <div class="card-header">نرخ‌ها (تومان به ازای یک واحد)</div>
            <div class="table-responsive">
                <table>
                    <thead><tr><th>ارز</th><th>نرخ فعلی</th><th>نرخ جدید (تومان)</th><th>منبع / زمان</th><th>فعال</th></tr></thead>
                    <tbody>
                        @foreach($rows as $row)
                            <tr>
                                <td><span class="sc-code">{{ $row->code }}</span> <span class="text-sm">{{ $row->name }}</span></td>
                                <td class="text-sm font-semibold">{{ $row->isSet() ? $N::format($row->rate_toman) : 'ثبت نشده' }}</td>
                                <td><input name="rates[{{ $row->code }}]" class="form-control" dir="ltr" inputmode="decimal" style="max-width:11rem" placeholder="{{ $row->isSet() ? (int) $row->rate_toman : 'مثلاً 92000' }}"></td>
                                <td class="text-xs text-slate-600">{{ $row->source === 'online' ? 'آنلاین' : 'دستی' }}<br>{{ $row->fetched_at ? \App\Helpers\Jalali::formatFa($row->fetched_at, 'Y/m/d H:i') : '—' }}</td>
                                <td><input type="checkbox" name="active[{{ $row->code }}]" value="1" class="form-check-input" @checked($row->is_active) aria-label="فعال بودن {{ $row->code }}"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-body"><button type="submit" class="btn btn-primary">ذخیرهٔ نرخ‌ها</button>
                <p class="admin-field-hint" style="margin-top:.5rem">فیلد خالی یعنی بدون تغییر. پیش‌فاکتورهای صادرشده با نرخ همان لحظه ثابت می‌مانند.</p></div>
        </form>

        <div class="card">
            <div class="card-header">به‌روزرسانی خودکار آنلاین</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.exchange-rates.provider.update') }}" class="admin-form-stack">
                    @csrf @method('PUT')
                    <label class="admin-form-inline-check"><input type="checkbox" name="enabled" value="1" class="form-check-input" @checked(old('enabled', $provider['enabled']))><span>به‌روزرسانی ساعتی فعال باشد</span></label>
                    <div class="form-group">
                        <label class="form-label" for="p-url">آدرس سرویس JSON (https)</label>
                        <input id="p-url" name="url" value="{{ old('url', $provider['url']) }}" class="form-control" dir="ltr" placeholder="https://api.example.com/rates.json">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="p-unit">واحد مقادیر سرویس</label>
                        <select id="p-unit" name="unit" class="form-select">
                            <option value="toman" @selected($provider['unit'] === 'toman')>تومان</option>
                            <option value="rial" @selected($provider['unit'] === 'rial')>ریال</option>
                        </select>
                    </div>
                    @foreach($rows as $row)
                        <div class="form-group">
                            <label class="form-label" for="p-path-{{ $row->code }}">مسیر JSON نرخ {{ $row->code }}</label>
                            <input id="p-path-{{ $row->code }}" name="paths[{{ $row->code }}]" value="{{ old('paths.' . $row->code, $provider['paths'][$row->code] ?? '') }}" class="form-control" dir="ltr" placeholder="data.{{ strtolower($row->code) }}.sell">
                        </div>
                    @endforeach
                    <p class="sc-help">فقط آدرس‌های https عمومی مجاز است. مقدار نامعتبر یا خارج از محدوده نادیده گرفته می‌شود و نرخ قبلی حفظ می‌شود.</p>
                    <div><button type="submit" class="btn btn-primary">ذخیرهٔ تنظیمات سرویس</button></div>
                </form>
                @if($provider['enabled'])
                    <form method="POST" action="{{ route('admin.exchange-rates.sync') }}" style="margin-top:.75rem">
                        @csrf
                        <button type="submit" class="btn btn-secondary btn-sm">دریافت همین حالا</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
