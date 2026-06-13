@extends('admin.layout')
@section('title', 'طرح‌های اقساطی')

@section('content')
<div class="admin-page-container">

    {{-- Header --}}
    <div class="admin-page-header flex items-center justify-between mb-6">
        <div>
            <h1 class="admin-page-title">طرح‌های اقساطی</h1>
            <p class="admin-page-subtitle text-sm text-slate-500 mt-1">تعریف و مدیریت طرح‌های خرید قسطی</p>
        </div>
        <div style="display:flex;gap:.625rem;flex-wrap:wrap;align-items:center">
            <a href="{{ route('admin.installments.orders.index') }}" class="btn btn-secondary gap-2">
                <x-core-ui::icon name="list" class="w-4 h-4" />
                سفارشات قسطی
            </a>
            <a href="{{ route('admin.installments.plans.create') }}" class="btn btn-primary gap-2">
                <x-core-ui::icon name="plus" class="w-4 h-4" />
                افزودن طرح
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger mb-4">{{ session('error') }}</div>
    @endif

    @if($plans->isEmpty())
    <div class="admin-empty-state">
        <svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 mx-auto mb-4 text-indigo-200">
            <rect width="64" height="64" rx="16" fill="#ede9fe"/>
            <rect x="14" y="22" width="36" height="24" rx="4" stroke="#7c3aed" stroke-width="2"/>
            <path d="M14 30h36" stroke="#7c3aed" stroke-width="2"/>
            <path d="M22 38h8M22 43h5" stroke="#7c3aed" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        <p class="text-slate-500 text-lg font-medium mb-1">هنوز طرح اقساطی تعریف نشده</p>
        <a href="{{ route('admin.installments.plans.create') }}" class="btn btn-primary btn-sm mt-3">ایجاد اولین طرح</a>
    </div>
    @else
    <div class="admin-card overflow-hidden">
        <div style="overflow-x:auto">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>نام طرح</th>
                        <th>مدت (ماه)</th>
                        <th>پیش‌پرداخت</th>
                        <th>بهره ماهانه</th>
                        <th>حداقل مبلغ</th>
                        <th>حداکثر مبلغ</th>
                        <th>وضعیت</th>
                        <th>سفارشات</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($plans as $plan)
                    <tr>
                        <td class="font-semibold text-slate-800">{{ $plan->name }}</td>
                        <td class="text-center font-bold text-indigo-600">
                            {{ \App\Helpers\NumberHelper::formatFa($plan->period_months) }}
                        </td>
                        <td class="text-center">
                            <span class="badge badge-info">{{ \App\Helpers\NumberHelper::formatFa($plan->down_payment_percent) }}٪</span>
                        </td>
                        <td class="text-center">
                            @if($plan->monthly_interest_rate > 0)
                                <span class="badge badge-warning">{{ \App\Helpers\NumberHelper::formatFa($plan->monthly_interest_rate) }}٪</span>
                            @else
                                <span class="badge badge-success">بدون سود</span>
                            @endif
                        </td>
                        <td class="text-sm text-slate-600">
                            @if($plan->min_amount > 0)
                                {{ \App\Helpers\NumberHelper::formatFa(number_format($plan->min_amount)) }} <small class="text-slate-400">ت</small>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="text-sm text-slate-600">
                            @if($plan->max_amount > 0)
                                {{ \App\Helpers\NumberHelper::formatFa(number_format($plan->max_amount)) }} <small class="text-slate-400">ت</small>
                            @else
                                <span class="text-slate-400">نامحدود</span>
                            @endif
                        </td>
                        <td>
                            @if($plan->is_active)
                                <span class="badge badge-success">فعال</span>
                            @else
                                <span class="badge badge-secondary">غیرفعال</span>
                            @endif
                        </td>
                        <td class="text-center text-sm font-semibold text-slate-700">
                            {{ \App\Helpers\NumberHelper::formatFa($plan->orders()->count()) }}
                        </td>
                        <td>
                            <div style="display:flex;gap:.375rem">
                                <a href="{{ route('admin.installments.plans.edit', $plan) }}" class="btn btn-sm btn-secondary">
                                    <x-core-ui::icon name="pencil" class="w-3.5 h-3.5" />
                                </a>
                                <form method="POST" action="{{ route('admin.installments.plans.destroy', $plan) }}"
                                      onsubmit="return confirm('این طرح حذف شود؟')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <x-core-ui::icon name="trash" class="w-3.5 h-3.5" />
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
