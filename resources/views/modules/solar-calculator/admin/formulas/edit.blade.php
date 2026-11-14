@extends('admin.layout')
@section('title', 'ویرایش فرمول ' . $formula->key)
@section('header', 'ماشین‌حساب خورشیدی')
@section('content')
@include('modules.solar-calculator.admin._chrome', ['crumb' => 'ویرایش ' . $formula->key])

<div class="admin-page">
    <div class="sc-grid-2">
        <div class="card">
            <div class="card-header">ویرایش <span class="sc-code">{{ $formula->key }}</span>@if($formula->is_system) <span class="sc-badge">سیستمی</span>@endif</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.solar-calculator.formulas.update', $formula) }}" class="admin-form-stack">
                    @csrf @method('PUT')
                    <div class="admin-form-grid admin-form-grid--2">
                        <div class="form-group">
                            <label class="form-label" for="e-label">عنوان</label>
                            <input id="e-label" name="label" value="{{ old('label', $formula->label) }}" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="e-unit">واحد</label>
                            <input id="e-unit" name="unit" value="{{ old('unit', $formula->unit) }}" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="e-group">گروه</label>
                            <select id="e-group" name="group" class="form-select">
                                @foreach($groupLabels as $k => $v)<option value="{{ $k }}" @selected(old('group', $formula->group) === $k)>{{ $v }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="e-expr">عبارت یا مقدار</label>
                        <textarea id="e-expr" name="expression" rows="3" class="form-control sc-code" style="width:100%;display:block" dir="ltr" required>{{ old('expression', $formula->expression) }}</textarea>
                        <p class="admin-field-hint">پیش از ذخیره، کل مجموعهٔ فرمول‌ها بررسی (نام‌های تعریف‌نشده، وابستگی چرخه‌ای) و با ورودی نمونه اجرا می‌شود.</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="e-desc">توضیح</label>
                        <textarea id="e-desc" name="description" rows="2" class="form-control">{{ old('description', $formula->description) }}</textarea>
                    </div>
                    <div class="sc-row-actions">
                        <button type="submit" class="btn btn-primary">ذخیره</button>
                        <a href="{{ route('admin.solar-calculator.formulas.index') }}" class="btn btn-secondary">انصراف</a>
                    </div>
                </form>
                @if($hasDefault)
                    <form method="POST" action="{{ route('admin.solar-calculator.formulas.restore', $formula) }}" style="margin-top:1rem" onsubmit="return confirm('عبارت پیش‌فرض بازگردانی شود؟');">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary btn-sm">بازگردانی عبارت پیش‌فرض</button>
                    </form>
                @endif
            </div>
        </div>

        <div style="display:flex;flex-direction:column;gap:1rem;min-width:0">
            @include('modules.solar-calculator.admin.formulas._reference')
            <div class="card">
                <div class="card-header">فرمول‌ها و ضرایب دیگر</div>
                <div class="card-body">
                    <ul style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:.3rem;max-height:22rem;overflow:auto">
                        @foreach($others as $o)<li class="text-sm"><span class="sc-code">{{ $o->key }}</span> <span class="text-slate-500">{{ $o->label }}</span></li>@endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
