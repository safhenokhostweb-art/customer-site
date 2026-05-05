@extends('admin.layout')
@section('title', 'حذف ۴ صفر پول ملی')
@section('header', 'حذف ۴ صفر پول ملی')
@section('content')
<div class="max-w-2xl">
    <h1>حذف ۴ صفر پول ملی</h1>

    @if(!$available)
        <div class="mb-6 p-4 rounded-lg bg-amber-50 border border-amber-200 text-amber-800">
            <p class="font-medium">ماژول حذف ۴ صفر پول ملی برای این سایت در دسترس نیست.</p>
            <p class="text-sm mt-1">برای فعال‌سازی، از پنل ادمین پلتفرم (saas-platform) در بخش <strong>دسترسی و منو</strong> ماژول «حذف ۴ صفر پول ملی» را فعال کنید و آن را به این سایت اختصاص دهید. سپس تنظیمات لایسنس را بروز کنید.</p>
        </div>
    @else
        <form method="post" action="{{ route('admin.currency-reform.update') }}" class="card p-6 mb-6">
            @csrf
            <h2 class="font-bold text-lg mb-2">وضعیت نمایش قیمت‌ها</h2>
            <ul class="list-disc list-inside space-y-1 text-gray-700 text-sm mb-4">
                <li>تا وقتی تیک زیر را نزنید، قیمت‌ها مطابق قبل (بدون حذف ۴ صفر) نمایش داده می‌شوند.</li>
                <li>با غیرفعال کردن، محصولات قبلی با قیمت بدون حذف ۴ صفر نمایش داده می‌شوند.</li>
            </ul>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="currency_reform_enabled" value="1" class="form-check-input"
                       {{ $toggleEnabled ? 'checked' : '' }}>
                <span class="font-medium">فعال‌سازی نمایش با حذف ۴ صفر (قیمت‌ها با ۴ صفر کم‌رنگ)</span>
            </label>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">ذخیره</button>
            </div>
        </form>

        @if($enabled)
            <div class="mb-6 p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800">
                <p class="font-medium">نمایش با حذف ۴ صفر فعال است.</p>
                <p class="text-sm mt-1">قیمت‌ها با ۴ صفر کم‌رنگ نمایش داده می‌شوند و در محاسبات حذف می‌شوند.</p>
            </div>
        @endif

        <div class="card p-6">
            <h2 class="font-bold text-lg mb-2">راهنما</h2>
            <ul class="list-disc list-inside space-y-1 text-gray-700 text-sm">
                <li>نمایش: مثلاً ۵۰۰٬۰۰۰ تومان به صورت <strong>۵۰</strong><span style="opacity:0.4;">۰۰۰۰</span> تومان (۴ صفر کم‌رنگ)</li>
                <li>محاسبات: از مقدار تقسیم‌شده (۵۰) استفاده می‌شود.</li>
                <li>در فرم محصول: قیمت را به واحد قدیم وارد کنید (مثلاً ۵۰۰۰۰۰). نمایش خودکار اعمال می‌شود.</li>
            </ul>
        </div>
    @endif
</div>
@endsection
