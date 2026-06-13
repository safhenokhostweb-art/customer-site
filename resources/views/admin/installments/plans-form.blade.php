@extends('admin.layout')
@section('title', $plan ? 'ویرایش طرح اقساطی' : 'افزودن طرح اقساطی')

@section('content')
<div class="admin-page-container max-w-2xl">

    <div class="admin-page-header flex items-center gap-3 mb-6">
        <a href="{{ route('admin.installments.plans.index') }}" class="p-2 rounded-lg hover:bg-slate-100 text-slate-500 hover:text-slate-700 transition">
            <x-core-ui::icon name="arrow-right" class="w-5 h-5" />
        </a>
        <div>
            <h1 class="admin-page-title">{{ $plan ? 'ویرایش طرح اقساطی' : 'افزودن طرح اقساطی' }}</h1>
            @if($plan)
            <p class="admin-page-subtitle text-sm text-slate-500 mt-0.5">{{ $plan->name }}</p>
            @endif
        </div>
    </div>

    <div class="admin-card">
        <form method="POST" action="{{ $plan ? route('admin.installments.plans.update', $plan) : route('admin.installments.plans.store') }}" class="p-6 space-y-5">
            @csrf
            @if($plan) @method('PUT') @endif

            @if($errors->any())
            <div class="alert alert-danger">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
                </ul>
            </div>
            @endif

            {{-- نام --}}
            <div class="form-group">
                <label class="form-label">نام طرح <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $plan?->name) }}"
                       class="form-input" placeholder="مثال: اقساط ۱۲ ماهه بدون سود" required>
            </div>

            {{-- مدت و پیش‌پرداخت --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                <div class="form-group">
                    <label class="form-label">مدت اقساط (ماه) <span class="text-red-500">*</span></label>
                    <select name="period_months" class="form-input" required>
                        @foreach([3, 6, 12, 24] as $m)
                        <option value="{{ $m }}" {{ old('period_months', $plan?->period_months) == $m ? 'selected' : '' }}>
                            {{ \App\Helpers\NumberHelper::formatFa($m) }} ماه
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">درصد پیش‌پرداخت <span class="text-red-500">*</span></label>
                    <div style="position:relative">
                        <input type="number" name="down_payment_percent"
                               value="{{ old('down_payment_percent', $plan?->down_payment_percent ?? 20) }}"
                               class="form-input" min="0" max="100" step="1" required>
                        <span style="position:absolute;left:.75rem;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.85rem">٪</span>
                    </div>
                </div>
            </div>

            {{-- بهره ماهانه --}}
            <div class="form-group">
                <label class="form-label">بهره ماهانه (٪)</label>
                <div style="position:relative;max-width:50%">
                    <input type="number" name="monthly_interest_rate"
                           value="{{ old('monthly_interest_rate', $plan?->monthly_interest_rate ?? 0) }}"
                           class="form-input" min="0" max="50" step="0.01">
                    <span style="position:absolute;left:.75rem;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.85rem">٪</span>
                </div>
                <p class="text-xs text-slate-400 mt-1">صفر = بدون سود (قسط‌بندی ساده)</p>
            </div>

            {{-- محدوده مبلغ --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                <div class="form-group">
                    <label class="form-label">حداقل مبلغ خرید (تومان)</label>
                    <input type="number" name="min_amount"
                           value="{{ old('min_amount', $plan?->min_amount ?? 0) }}"
                           class="form-input" min="0" step="1000">
                    <p class="text-xs text-slate-400 mt-1">۰ = بدون حداقل</p>
                </div>
                <div class="form-group">
                    <label class="form-label">حداکثر مبلغ خرید (تومان)</label>
                    <input type="number" name="max_amount"
                           value="{{ old('max_amount', $plan?->max_amount ?? 0) }}"
                           class="form-input" min="0" step="1000">
                    <p class="text-xs text-slate-400 mt-1">۰ = نامحدود</p>
                </div>
            </div>

            {{-- وضعیت --}}
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:.625rem;cursor:pointer">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1"
                           {{ old('is_active', $plan?->is_active ?? true) ? 'checked' : '' }}
                           style="width:1rem;height:1rem;accent-color:#4f46e5">
                    <span class="form-label" style="margin:0">طرح فعال باشد</span>
                </label>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn btn-primary">
                    <x-core-ui::icon name="check" class="w-4 h-4 ml-1" />
                    {{ $plan ? 'ذخیره تغییرات' : 'ایجاد طرح' }}
                </button>
                <a href="{{ route('admin.installments.plans.index') }}" class="btn btn-secondary">انصراف</a>
            </div>
        </form>
    </div>
</div>
@endsection
