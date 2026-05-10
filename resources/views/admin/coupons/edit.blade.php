@extends('admin.layout')
@section('title', 'ویرایش کوپن')
@section('header', 'ویرایش کوپن')
@section('content')
<div class="admin-page max-w-2xl">
    <div class="card">
        <div class="card-header">ویرایش کوپن {{ $coupon->code }}</div>
        <div class="card-body">
            <form action="{{ route('admin.coupons.update', $coupon) }}" method="post">
                @csrf
                @method('PUT')
                @php $dt = old('discount_type', $coupon->discount_type ?? 'percent'); @endphp
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">کد کوپن *</label>
                        <input type="text" name="code" value="{{ old('code', $coupon->code) }}" required class="font-mono">
                    </div>
                    <div class="form-group">
                        <label class="form-label">نوع تخفیف *</label>
                        <select name="discount_type" id="discount_type" class="form-control">
                            <option value="percent" {{ $dt === 'percent' ? 'selected' : '' }}>درصدی</option>
                            <option value="fixed" {{ $dt === 'fixed' ? 'selected' : '' }}>مبلغ ثابت</option>
                        </select>
                    </div>
                    <div class="form-group" id="group_percent">
                        <label class="form-label">درصد تخفیف</label>
                        <input type="number" name="discount_percent" value="{{ old('discount_percent', $coupon->discount_percent) }}" min="0" max="100">
                    </div>
                    <div class="form-group {{ $dt === 'percent' ? 'hidden' : '' }}" id="group_fixed">
                        <label class="form-label">مبلغ تخفیف (تومان)</label>
                        <input type="number" name="discount_amount" value="{{ old('discount_amount', $coupon->discount_amount) }}" min="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">حداقل مبلغ سفارش (تومان)</label>
                        <input type="number" name="min_order_amount" value="{{ old('min_order_amount', $coupon->min_order_amount) }}" min="0" placeholder="خالی = بدون حداقل">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">تاریخ شروع (شمسی)</label>
                        <x-persian-date-input name="starts_at" :value="old('starts_at', $coupon->starts_at ? \App\Helpers\Jalali::formatFa($coupon->starts_at, 'Y/m/d') : '')" placeholder="۱۴۰۴/۱۲/۰۱" class="form-control" />
                    </div>
                    <div class="form-group">
                        <label class="form-label">تاریخ انقضا (شمسی)</label>
                        <x-persian-date-input name="expires_at" :value="old('expires_at', $coupon->expires_at ? \App\Helpers\Jalali::formatFa($coupon->expires_at, 'Y/m/d') : '')" placeholder="۱۴۰۴/۱۲/۰۵" class="form-control" />
                    </div>
                    <div class="form-group">
                        <label class="form-label">محدودیت استفاده</label>
                        <input type="number" name="usage_limit" value="{{ old('usage_limit', $coupon->usage_limit) }}" min="1" placeholder="خالی = نامحدود">
                    </div>
                </div>
                <div class="form-group">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="first_order_only" value="1" {{ old('first_order_only', $coupon->first_order_only) ? 'checked' : '' }}>
                        <span>فقط برای اولین خرید کاربر</span>
                    </label>
                </div>
                @if(isset($products) && $products->isNotEmpty())
                <div class="form-group">
                    <label class="form-label">محدود به محصولات (خالی = همه)</label>
                    <select name="product_ids[]" multiple class="form-control" style="min-height: 100px;">
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" {{ in_array($p->id, old('product_ids', $coupon->product_ids ?? [])) ? 'selected' : '' }}>{{ $p->title }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                @if(isset($categories) && $categories->isNotEmpty())
                <div class="form-group">
                    <label class="form-label">محدود به دسته‌ها (خالی = همه)</label>
                    <select name="category_ids[]" multiple class="form-control" style="min-height: 80px;">
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}" {{ in_array($c->id, old('category_ids', $coupon->category_ids ?? [])) ? 'selected' : '' }}>{{ $c->title }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="form-group">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $coupon->is_active) ? 'checked' : '' }}>
                        <span>کوپن فعال است</span>
                    </label>
                </div>
                <p class="text-sm text-slate-500 mt-2">تعداد استفاده شده: {{ $coupon->used_count }}</p>
                <div class="admin-page-tools mt-6">
                    <button type="submit" class="btn btn-primary">ذخیره تغییرات</button>
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
</script>
@endpush
@endsection
