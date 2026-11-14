@extends('admin.layout')
@section('title', 'فرمول‌های ماشین‌حساب خورشیدی')
@section('header', 'ماشین‌حساب خورشیدی')
@section('content')
@include('modules.solar-calculator.admin._chrome', ['crumb' => 'فرمول‌ها'])

@php $N = \SaaSPlatform\ThemeRuntime\Support\PersianNumber::class; @endphp

<div class="admin-page">
    <p class="admin-section-lead">
        همهٔ منطق محاسبه در این جدول است: ضرایب فنی، قیمت پایهٔ تجهیزات به ارز خارجی و فرمول‌های طراحی و قیمت‌گذاری.
        هر تغییر پیش از ذخیره با ورودی نمونه آزموده می‌شود و فوراً روی ماشین‌حساب سایت اعمال می‌شود.
    </p>

    <div class="sc-grid-2">
        <div style="display:flex;flex-direction:column;gap:1rem;min-width:0">
            @foreach($groupLabels as $groupKey => $groupLabel)
                @continue(!isset($groups[$groupKey]))
                <div class="card admin-table-wrap">
                    <div class="card-header">{{ $groupLabel }}</div>
                    <div class="table-responsive">
                        <table>
                            <thead><tr><th>کلید</th><th>عنوان</th><th>عبارت / مقدار</th><th>نمونه</th><th style="width:150px">عملیات</th></tr></thead>
                            <tbody>
                                @foreach($groups[$groupKey] as $f)
                                    @php $sample = $test['ok'] ? ($test['values'][$f->key] ?? null) : null; @endphp
                                    <tr>
                                        <td><span class="sc-code">{{ $f->key }}</span>
                                            @if($f->is_system)<div class="text-xs text-slate-500" style="margin-top:.2rem">سیستمی</div>@endif
                                            @if(!empty($used[$f->key]))<div class="text-xs text-indigo-700" style="margin-top:.2rem">{{ implode('، ', $used[$f->key]) }}</div>@endif
                                        </td>
                                        <td class="text-sm">{{ $f->label }}@if($f->unit)<div class="text-xs text-slate-500">{{ $f->unit }}</div>@endif</td>
                                        <td><span class="sc-code">{{ $f->expression }}</span>
                                            @if(isset($defaults[$f->key]) && $defaults[$f->key][2] !== $f->expression)<div class="text-xs text-amber-700" style="margin-top:.2rem">تغییر یافته از پیش‌فرض</div>@endif
                                        </td>
                                        <td class="text-sm" dir="ltr" style="text-align:right">{{ $sample !== null ? $N::format($sample, abs($sample) < 100 ? 2 : 0) : '—' }}</td>
                                        <td>
                                            <div class="sc-row-actions">
                                                <a href="{{ route('admin.solar-calculator.formulas.edit', $f) }}" class="btn btn-primary btn-sm">ویرایش</a>
                                                @unless($f->is_system)
                                                    <form method="POST" action="{{ route('admin.solar-calculator.formulas.destroy', $f) }}" onsubmit="return confirm('فرمول «{{ $f->key }}» حذف شود؟');">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                                                    </form>
                                                @endunless
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach

            <div class="card">
                <div class="card-header">افزودن ضریب یا فرمول جدید</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.solar-calculator.formulas.store') }}" class="admin-form-stack">
                        @csrf
                        <div class="admin-form-grid admin-form-grid--2">
                            <div class="form-group">
                                <label class="form-label" for="f-key">کلید (انگلیسی)</label>
                                <input id="f-key" name="key" value="{{ old('key') }}" class="form-control" dir="ltr" placeholder="cable_price_per_kw" required>
                                <p class="admin-field-hint">در فرمول‌های دیگر با همین نام استفاده می‌شود.</p>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="f-label">عنوان</label>
                                <input id="f-label" name="label" value="{{ old('label') }}" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="f-group">گروه</label>
                                <select id="f-group" name="group" class="form-select">
                                    @foreach($groupLabels as $k => $v)<option value="{{ $k }}" @selected(old('group', 'custom') === $k)>{{ $v }}</option>@endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="f-unit">واحد</label>
                                <input id="f-unit" name="unit" value="{{ old('unit') }}" class="form-control" placeholder="تومان، دلار، کیلووات…">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="f-expr">عبارت یا مقدار</label>
                            <input id="f-expr" name="expression" value="{{ old('expression') }}" class="form-control sc-code" style="width:100%" dir="ltr" placeholder="system_kw * 120 * rate_usd" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="f-desc">توضیح</label>
                            <input id="f-desc" name="description" value="{{ old('description') }}" class="form-control">
                        </div>
                        <p class="sc-help">برای اینکه فرمول جدید در قیمت نهایی اثر کند، آن را در فرمول <span class="sc-code">subtotal</span> جمع بزنید و در «تنظیمات ← نتایج و پیش‌فاکتور» یک ردیف پیش‌فاکتور برایش بسازید.</p>
                        <div><button type="submit" class="btn btn-primary">افزودن</button></div>
                    </form>
                </div>
            </div>
        </div>

        <div style="display:flex;flex-direction:column;gap:1rem;min-width:0">
            <div class="card" id="test-bench">
                <div class="card-header">آزمایش با ورودی نمونه</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.solar-calculator.formulas.test') }}" class="admin-form-stack">
                        @csrf
                        <div class="admin-form-grid admin-form-grid--2">
                            <div class="form-group">
                                <label class="form-label" for="t-type">نوع سیستم</label>
                                <select id="t-type" name="system_type" class="form-select">
                                    @foreach(['on_grid' => 'متصل به شبکه', 'off_grid' => 'مستقل', 'hybrid' => 'هیبرید'] as $k => $v)
                                        <option value="{{ $k }}" @selected($testInput['system_type'] === $k)>{{ $v }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="t-region">استان</label>
                                <select id="t-region" name="region" class="form-select">
                                    @foreach($regions as $r)<option value="{{ $r['key'] }}" @selected($testInput['region'] === $r['key'])>{{ $r['label'] }}</option>@endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="t-mode">ورود مصرف</label>
                                <select id="t-mode" name="consumption_mode" class="form-select">
                                    <option value="kwh" @selected($testInput['consumption_mode'] === 'kwh')>کیلووات‌ساعت</option>
                                    <option value="bill" @selected($testInput['consumption_mode'] === 'bill')>مبلغ قبض</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="t-kwh">مصرف ماهانه / مبلغ قبض</label>
                                <input id="t-kwh" name="{{ $testInput['consumption_mode'] === 'bill' ? 'monthly_bill' : 'monthly_kwh' }}" class="form-control" dir="ltr"
                                    value="{{ $testInput['consumption_mode'] === 'bill' ? $testInput['monthly_bill'] : $testInput['monthly_kwh'] }}"
                                    data-kwh-name="monthly_kwh" data-bill-name="monthly_bill">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="t-roof">فضا (مترمربع)</label>
                                <input id="t-roof" name="roof_area" class="form-control" dir="ltr" value="{{ $testInput['roof_area'] ?: '' }}">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="t-backup">ساعات اضطراری (هیبرید)</label>
                                <input id="t-backup" name="backup_hours" class="form-control" dir="ltr" value="{{ $testInput['backup_hours'] ?: '' }}">
                            </div>
                        </div>
                        <div><button type="submit" class="btn btn-secondary">محاسبهٔ آزمایشی</button></div>
                    </form>
                    <script>
                        (function () {
                            var mode = document.getElementById('t-mode'), input = document.getElementById('t-kwh');
                            mode.addEventListener('change', function () { input.name = mode.value === 'bill' ? input.dataset.billName : input.dataset.kwhName; });
                        })();
                    </script>

                    @if($test['ok'])
                        <div style="margin-top:1rem">
                            @if($test['price']['available'])
                                <p class="admin-flash admin-flash--success">مبلغ نهایی: {!! $test['price']['total_html'] !!} تومان</p>
                            @else
                                <p class="admin-flash admin-flash--warning">قیمت محاسبه نمی‌شود؛ نرخ این ارزها ثبت نشده: {{ implode('، ', $test['price']['missing_rates']) }}</p>
                            @endif
                            <p class="sc-help">مقدار هر فرمول در ستون «نمونه» جدول‌ها نمایش داده شده است.</p>
                        </div>
                    @else
                        <p class="admin-flash admin-flash--error" style="margin-top:1rem">{{ $test['error'] }}</p>
                    @endif
                </div>
            </div>

            @include('modules.solar-calculator.admin.formulas._reference')
        </div>
    </div>
</div>
@endsection
