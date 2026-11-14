@extends('admin.layout')
@section('title', 'تنظیمات ماشین‌حساب خورشیدی')
@section('header', 'ماشین‌حساب خورشیدی')
@section('content')
@include('modules.solar-calculator.admin._chrome', ['crumb' => 'تنظیمات'])

@php
    $tabs = ['access' => 'دسترسی و محاسبه', 'company' => 'شرکت و پیش‌فاکتور', 'ui' => 'متن‌ها و تصاویر', 'regions' => 'استان‌ها و تابش', 'results' => 'نتایج و ردیف‌های پیش‌فاکتور'];
    $access = $all['access']; $calc = $all['calculator']; $company = $all['company']; $ui = $all['ui'];
@endphp

<div class="admin-page">
    <div class="admin-segmented" role="tablist" style="display:flex;flex-wrap:wrap;gap:.35rem;margin-bottom:1rem">
        @foreach($tabs as $key => $label)
            <a role="tab" href="{{ route('admin.solar-calculator.settings.edit', ['tab' => $key]) }}" aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
               class="btn btn-sm {{ $tab === $key ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $label }}</a>
        @endforeach
    </div>

    <form method="POST" action="{{ route('admin.solar-calculator.settings.update') }}" enctype="multipart/form-data" class="admin-form-stack">
        @csrf @method('PUT')
        <input type="hidden" name="section" value="{{ $tab }}">

        @if($tab === 'access')
            <div class="card">
                <div class="card-header">دسترسی و دریافت اطلاعات تماس</div>
                <div class="card-body admin-form-stack">
                    <fieldset class="admin-fieldset">
                        <legend class="admin-fieldset__legend">سیاست دسترسی</legend>
                        @foreach($accessModes as $value => $label)
                            <label class="admin-form-inline-check" style="margin-bottom:.5rem">
                                <input type="radio" name="access[mode]" value="{{ $value }}" class="form-check-input" @checked(old('access.mode', $access['mode']) === $value)>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                        <p class="admin-field-hint">در همهٔ حالت‌ها پیش از صدور پیش‌فاکتور، نام و موبایل مشتری در «درخواست‌ها» و در CRM (برچسب solar_calculator) ثبت می‌شود.</p>
                    </fieldset>
                    <label class="admin-form-inline-check">
                        <input type="checkbox" name="access[require_email]" value="1" class="form-check-input" @checked($access['require_email'])>
                        <span>ایمیل در فرم پیش‌فاکتور الزامی باشد</span>
                    </label>
                </div>
            </div>
            <div class="card">
                <div class="card-header">رفتار ماشین‌حساب</div>
                <div class="card-body admin-form-stack">
                    <div class="admin-form-grid admin-form-grid--3">
                        <div class="form-group">
                            <label class="form-label" for="c-type">نوع سیستم پیش‌فرض</label>
                            <select id="c-type" name="calculator[default_system_type]" class="form-select">
                                @foreach(['on_grid' => 'متصل به شبکه', 'off_grid' => 'مستقل', 'hybrid' => 'هیبرید'] as $k => $v)
                                    <option value="{{ $k }}" @selected($calc['default_system_type'] === $k)>{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="c-backup">ساعات برق اضطراری پیش‌فرض (هیبرید)</label>
                            <input id="c-backup" name="calculator[default_backup_hours]" value="{{ $calc['default_backup_hours'] }}" class="form-control" dir="ltr">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="c-max">سقف مصرف ماهانه (کیلووات‌ساعت)</label>
                            <input id="c-max" name="calculator[max_monthly_kwh]" value="{{ $calc['max_monthly_kwh'] }}" class="form-control" dir="ltr">
                        </div>
                    </div>
                    <label class="admin-form-inline-check"><input type="checkbox" name="calculator[show_price]" value="1" class="form-check-input" @checked($calc['show_price'])><span>نمایش برآورد قیمت در نتایج (قابل تغییر برای هر ویجت)</span></label>
                    <label class="admin-form-inline-check"><input type="checkbox" name="calculator[show_saving]" value="1" class="form-check-input" @checked($calc['show_saving'])><span>نمایش صرفه‌جویی سالانه و دورهٔ بازگشت سرمایه</span></label>
                </div>
            </div>
        @endif

        @if($tab === 'company')
            <div class="card">
                <div class="card-header">برند و اطلاعات تماس روی پیش‌فاکتور</div>
                <div class="card-body admin-form-stack">
                    <div class="admin-form-grid admin-form-grid--2">
                        @foreach(['name' => 'نام شرکت (خالی = نام سایت)', 'phone' => 'تلفن', 'email' => 'ایمیل', 'website' => 'وب‌سایت (https://…)'] as $k => $label)
                            <div class="form-group">
                                <label class="form-label" for="co-{{ $k }}">{{ $label }}</label>
                                <input id="co-{{ $k }}" name="company[{{ $k }}]" value="{{ old('company.' . $k, $company[$k]) }}" class="form-control" @if(in_array($k, ['email', 'website', 'phone'], true)) dir="ltr" @endif>
                            </div>
                        @endforeach
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="co-address">نشانی</label>
                        <input id="co-address" name="company[address]" value="{{ old('company.address', $company['address']) }}" class="form-control">
                    </div>
                    <div class="admin-form-grid admin-form-grid--2">
                        <div class="form-group">
                            <label class="form-label" for="co-logo">لوگو (خالی = لوگوی سایت)</label>
                            <input id="co-logo" name="company[logo]" value="{{ old('company.logo', $company['logo']) }}" class="form-control" dir="ltr" placeholder="/storage/...">
                            <input type="file" name="company_logo_upload" accept="image/jpeg,image/png,image/webp" class="form-control" style="margin-top:.4rem">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="co-accent">رنگ برند پیش‌فاکتور و ویجت (هگز)</label>
                            <input id="co-accent" name="company[accent_color]" value="{{ old('company.accent_color', $company['accent_color']) }}" class="form-control" dir="ltr" placeholder="#0f766e">
                            <p class="admin-field-hint">خالی = رنگ اصلی قالب سایت.</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="co-prefix">پیشوند شمارهٔ پیش‌فاکتور</label>
                            <input id="co-prefix" name="company[quote_prefix]" value="{{ old('company.quote_prefix', $company['quote_prefix']) }}" class="form-control" dir="ltr">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="co-valid">مدت اعتبار (روز)</label>
                            <input id="co-valid" name="company[validity_days]" value="{{ old('company.validity_days', $company['validity_days']) }}" class="form-control" dir="ltr">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="co-terms">شرایط و توضیحات پایین پیش‌فاکتور (هر سطر یک بند)</label>
                        <textarea id="co-terms" name="company[terms]" rows="5" class="form-control">{{ old('company.terms', $company['terms']) }}</textarea>
                    </div>
                </div>
            </div>
        @endif

        @if($tab === 'ui')
            <p class="admin-section-lead">این متن‌ها پیش‌فرض همهٔ ویجت‌های ماشین‌حساب هستند. در ویرایشگر قالب، هر ویجت می‌تواند هر کدام را برای همان صفحه بازنویسی کند.</p>
            @foreach($uiGroups as $groupKey => $groupLabel)
                <div class="card">
                    <div class="card-header">{{ $groupLabel }}</div>
                    <div class="card-body">
                        <div class="admin-form-grid admin-form-grid--2">
                            @foreach($uiFields as $key => [$type, $label, $default, $group])
                                @continue($group !== $groupKey)
                                <div class="form-group" @if($type === 'textarea') style="grid-column:1/-1" @endif>
                                    <label class="form-label" for="ui-{{ $key }}">{{ $label }}</label>
                                    @if($type === 'textarea')
                                        <textarea id="ui-{{ $key }}" name="ui[{{ $key }}]" rows="2" class="form-control">{{ old('ui.' . $key, $ui[$key]) }}</textarea>
                                    @elseif($type === 'image')
                                        <div style="display:flex;gap:.6rem;align-items:flex-start">
                                            @if($ui[$key] !== '')<img src="{{ $ui[$key] }}" alt="" style="width:3.5rem;height:3.5rem;object-fit:cover;border-radius:.5rem;border:1px solid #e2e8f0">@endif
                                            <div style="flex:1;min-width:0">
                                                <input id="ui-{{ $key }}" name="ui[{{ $key }}]" value="{{ old('ui.' . $key, $ui[$key]) }}" class="form-control" dir="ltr" placeholder="/storage/... یا https://...">
                                                <input type="file" name="ui_upload[{{ $key }}]" accept="image/jpeg,image/png,image/webp" class="form-control" style="margin-top:.4rem">
                                            </div>
                                        </div>
                                    @else
                                        <input id="ui-{{ $key }}" name="ui[{{ $key }}]" value="{{ old('ui.' . $key, $ui[$key]) }}" class="form-control">
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        @endif

        @if($tab === 'regions')
            <div class="card" x-data="{ rows: @js($all['regions']) }">
                <div class="card-header">استان‌ها و ساعات اوج تابش روزانه (PSH)</div>
                <div class="card-body">
                    <p class="sc-help" style="margin-bottom:.75rem">PSH میانگین ساعات تابش معادل ۱۰۰۰ وات بر مترمربع در روز است و در فرمول‌ها با نام <span class="sc-code">psh</span> در دسترس است.</p>
                    <template x-for="(row, i) in rows" :key="i">
                        <div class="sc-repeater-row" style="grid-template-columns:minmax(0,1fr) minmax(0,1.4fr) minmax(0,.7fr) auto">
                            <div class="form-group"><label class="form-label">کلید</label><input class="form-control" dir="ltr" :name="`regions[${i}][key]`" x-model="row.key" required></div>
                            <div class="form-group"><label class="form-label">نام</label><input class="form-control" :name="`regions[${i}][label]`" x-model="row.label" required></div>
                            <div class="form-group"><label class="form-label">PSH</label><input class="form-control" dir="ltr" :name="`regions[${i}][psh]`" x-model="row.psh" required></div>
                            <button type="button" class="btn btn-danger btn-sm" @click="rows.splice(i, 1)" aria-label="حذف">✕</button>
                        </div>
                    </template>
                    <button type="button" class="btn btn-outline-primary btn-sm" @click="rows.push({ key: '', label: '', psh: 5 })">+ افزودن استان</button>
                </div>
            </div>
        @endif

        @if($tab === 'results')
            <div class="card" x-data="{ rows: @js($all['result_cards']) }">
                <div class="card-header">کارت‌های نتیجه (مرحلهٔ ۳ و پیش‌فاکتور)</div>
                <div class="card-body">
                    <template x-for="(row, i) in rows" :key="i">
                        <div class="sc-repeater-row" style="grid-template-columns:repeat(auto-fit,minmax(120px,1fr)) auto">
                            <div class="form-group"><label class="form-label">فرمول</label>
                                <select class="form-select" :name="`result_cards[${i}][key]`" x-model="row.key">
                                    @foreach($formulaKeys as $k => $l)<option value="{{ $k }}">{{ $k }} — {{ $l }}</option>@endforeach
                                </select></div>
                            <div class="form-group"><label class="form-label">عنوان</label><input class="form-control" :name="`result_cards[${i}][label]`" x-model="row.label"></div>
                            <div class="form-group"><label class="form-label">واحد</label><input class="form-control" :name="`result_cards[${i}][unit]`" x-model="row.unit"></div>
                            <div class="form-group"><label class="form-label">رقم اعشار</label><input class="form-control" dir="ltr" :name="`result_cards[${i}][decimals]`" x-model="row.decimals"></div>
                            <div class="form-group"><label class="form-label">آیکون</label>
                                <select class="form-select" :name="`result_cards[${i}][icon]`" x-model="row.icon">
                                    <option value="">بدون آیکون</option>
                                    @foreach($icons as $icon)<option value="{{ $icon }}">{{ $icon }}</option>@endforeach
                                </select></div>
                            <label class="admin-form-inline-check"><input type="checkbox" value="1" class="form-check-input" :name="`result_cards[${i}][hide_zero]`" :checked="row.hide_zero"><span>پنهان اگر صفر</span></label>
                            <button type="button" class="btn btn-danger btn-sm" @click="rows.splice(i, 1)" aria-label="حذف">✕</button>
                        </div>
                    </template>
                    <button type="button" class="btn btn-outline-primary btn-sm" @click="rows.push({ key: 'system_kw', label: '', unit: '', decimals: 0, icon: '', hide_zero: false })">+ افزودن کارت</button>
                </div>
            </div>

            <div class="card" x-data="{ rows: @js($all['quote_lines']) }">
                <div class="card-header">ردیف‌های پیش‌فاکتور</div>
                <div class="card-body">
                    <p class="sc-help" style="margin-bottom:.75rem">در «توضیح» می‌توانید مقدار هر فرمول یا ورودی را با <span class="sc-code">{panel_watt}</span> درج کنید. اگر «فرمول تعداد» خالی باشد تعداد ۱ است.</p>
                    <template x-for="(row, i) in rows" :key="i">
                        <div class="sc-repeater-row" style="grid-template-columns:repeat(auto-fit,minmax(130px,1fr)) auto">
                            <div class="form-group"><label class="form-label">شرح</label><input class="form-control" :name="`quote_lines[${i}][label]`" x-model="row.label" required></div>
                            <div class="form-group"><label class="form-label">توضیح</label><input class="form-control" :name="`quote_lines[${i}][detail]`" x-model="row.detail"></div>
                            <div class="form-group"><label class="form-label">فرمول تعداد</label>
                                <select class="form-select" :name="`quote_lines[${i}][qty_key]`" x-model="row.qty_key">
                                    <option value="">— (۱)</option>
                                    @foreach($formulaKeys as $k => $l)<option value="{{ $k }}">{{ $k }}</option>@endforeach
                                </select></div>
                            <div class="form-group"><label class="form-label">واحد تعداد</label><input class="form-control" :name="`quote_lines[${i}][qty_unit]`" x-model="row.qty_unit"></div>
                            <div class="form-group"><label class="form-label">فرمول مبلغ</label>
                                <select class="form-select" :name="`quote_lines[${i}][amount_key]`" x-model="row.amount_key">
                                    @foreach($formulaKeys as $k => $l)<option value="{{ $k }}">{{ $k }}</option>@endforeach
                                </select></div>
                            <label class="admin-form-inline-check"><input type="checkbox" value="1" class="form-check-input" :name="`quote_lines[${i}][hide_zero]`" :checked="row.hide_zero"><span>پنهان اگر صفر</span></label>
                            <button type="button" class="btn btn-danger btn-sm" @click="rows.splice(i, 1)" aria-label="حذف">✕</button>
                        </div>
                    </template>
                    <button type="button" class="btn btn-outline-primary btn-sm" @click="rows.push({ label: '', detail: '', qty_key: '', qty_unit: '', amount_key: 'subtotal', hide_zero: true })">+ افزودن ردیف</button>
                </div>
            </div>

            <div class="card">
                <div class="card-header">جمع‌ها</div>
                <div class="card-body">
                    <div class="admin-form-grid admin-form-grid--3">
                        @foreach(['subtotal_key' => 'جمع قبل از مالیات', 'tax_key' => 'مالیات', 'total_key' => 'مبلغ نهایی', 'saving_key' => 'صرفه‌جویی سالانه', 'payback_key' => 'بازگشت سرمایه (سال)'] as $k => $label)
                            <div class="form-group">
                                <label class="form-label" for="qt-{{ $k }}">{{ $label }}</label>
                                <select id="qt-{{ $k }}" name="quote_totals[{{ $k }}]" class="form-select">
                                    @if($k !== 'total_key')<option value="">— نمایش داده نشود</option>@endif
                                    @foreach($formulaKeys as $fk => $fl)<option value="{{ $fk }}" @selected($all['quote_totals'][$k] === $fk)>{{ $fk }}</option>@endforeach
                                </select>
                            </div>
                        @endforeach
                        <div class="form-group">
                            <label class="form-label" for="qt-tax-label">عنوان ردیف مالیات</label>
                            <input id="qt-tax-label" name="quote_totals[tax_label]" value="{{ $all['quote_totals']['tax_label'] }}" class="form-control">
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="admin-form-actions admin-form-actions--sticky" style="display:flex;gap:.5rem;flex-wrap:wrap">
            <button type="submit" class="btn btn-primary">ذخیرهٔ «{{ $tabs[$tab] }}»</button>
        </div>
    </form>

    <form method="POST" action="{{ route('admin.solar-calculator.settings.reset') }}" style="margin-top:.75rem" onsubmit="return confirm('مقادیر پیش‌فرض این بخش بازگردانی شود؟ تغییرات فعلی از بین می‌رود.');">
        @csrf
        <input type="hidden" name="section" value="{{ $tab }}">
        <button type="submit" class="btn btn-outline-secondary btn-sm">بازگردانی پیش‌فرض‌های «{{ $tabs[$tab] }}»</button>
    </form>
</div>
@endsection
