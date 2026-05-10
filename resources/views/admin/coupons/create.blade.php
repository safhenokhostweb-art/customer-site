@extends('admin.layout')
@section('title', 'کوپن جدید')
@section('header', 'کوپن جدید')
@section('content')
<div class="admin-page max-w-2xl">
    <div class="card">
        <div class="card-header">اطلاعات کوپن</div>
        <div class="card-body">
            <form action="{{ route('admin.coupons.store') }}" method="post">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">کد کوپن *</label>
                        <input type="text" name="code" value="{{ old('code') }}" required placeholder="مثلاً WELCOME20" class="font-mono">
                    </div>
                    <div class="form-group">
                        <label class="form-label">نوع تخفیف *</label>
                        <select name="discount_type" id="discount_type" class="form-control">
                            <option value="percent" {{ old('discount_type', 'percent') === 'percent' ? 'selected' : '' }}>درصدی</option>
                            <option value="fixed" {{ old('discount_type') === 'fixed' ? 'selected' : '' }}>مبلغ ثابت</option>
                        </select>
                    </div>
                    <div class="form-group" id="group_percent">
                        <label class="form-label">درصد تخفیف</label>
                        <input type="number" name="discount_percent" value="{{ old('discount_percent', 10) }}" min="0" max="100">
                    </div>
                    <div class="form-group hidden" id="group_fixed">
                        <label class="form-label">مبلغ تخفیف (تومان)</label>
                        <input type="number" name="discount_amount" value="{{ old('discount_amount') }}" min="0" placeholder="مثلاً ۵۰۰۰۰">
                    </div>
                    <div class="form-group">
                        <label class="form-label">حداقل مبلغ سفارش (تومان)</label>
                        <input type="number" name="min_order_amount" value="{{ old('min_order_amount') }}" min="0" placeholder="خالی = بدون حداقل">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">تاریخ شروع (شمسی)</label>
                        <x-persian-date-input name="starts_at" :value="old('starts_at')" placeholder="۱۴۰۴/۱۲/۰۱" class="form-control" />
                    </div>
                    <div class="form-group">
                        <label class="form-label">تاریخ انقضا (شمسی)</label>
                        <x-persian-date-input name="expires_at" :value="old('expires_at')" placeholder="۱۴۰۴/۱۲/۰۵" class="form-control" />
                    </div>
                    <div class="form-group">
                        <label class="form-label">محدودیت استفاده</label>
                        <input type="number" name="usage_limit" value="{{ old('usage_limit') }}" min="1" placeholder="خالی = نامحدود">
                    </div>
                </div>
                <div class="form-group">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="first_order_only" value="1" {{ old('first_order_only') ? 'checked' : '' }}>
                        <span>فقط برای اولین خرید کاربر</span>
                    </label>
                </div>
                @if(isset($products) && $products->isNotEmpty())
                <div class="form-group">
                    <label class="form-label">محدود به محصولات (خالی = همه)</label>
                    <select name="product_ids[]" multiple class="form-control" style="min-height: 100px;">
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" {{ in_array($p->id, old('product_ids', [])) ? 'selected' : '' }}>{{ $p->title }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                @if(isset($categories) && $categories->isNotEmpty())
                <div class="form-group">
                    <label class="form-label">محدود به دسته‌ها (خالی = همه)</label>
                    <select name="category_ids[]" multiple class="form-control" style="min-height: 80px;">
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}" {{ in_array($c->id, old('category_ids', [])) ? 'selected' : '' }}>{{ $c->title }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="form-group">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                        <span>کوپن فعال است</span>
                    </label>
                </div>
                <div class="admin-page-tools mt-6">
                    <button type="submit" class="btn btn-primary">ذخیره کوپن</button>
                    <a href="{{ route('admin.coupons.index') }}" class="btn btn-secondary">انصراف</a>
                </div>
            </form>
        </div>
    </div>
</div>
@push('scripts')
<script>
document.getElementById('discount_type')?.addEventListener('change', function() {
    var isPercent = this.value === 'percent';
    document.getElementById('group_percent').classList.toggle('hidden', !isPercent);
    document.getElementById('group_fixed').classList.toggle('hidden', isPercent);
});
document.getElementById('discount_type')?.dispatchEvent(new Event('change'));
</script>
@endpush
@endsection
