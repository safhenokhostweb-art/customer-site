@extends('admin.layout')
@section('title', $guide['title'] ?? 'ماژول پلتفرمی')
@section('header', $guide['title'] ?? 'ماژول پلتفرمی')

@section('content')

<div class="card mb-6">
    <div class="card-header">راهنمای ماژول {{ $guide['title'] ?? $slug }}</div>
    <div class="card-body space-y-3 text-sm leading-relaxed">
        <p>
            این صفحه مربوط به ماژول <strong>{{ $guide['title'] ?? $slug }}</strong> است.
            این ماژول از طرف پلتفرم به این سایت اختصاص داده می‌شود و فقط زمانی در منوی پنل ادمین نمایش داده می‌شود
            که در لایسنس سایت فعال شده باشد.
        </p>
        @if(!empty($guide['description']))
            <p>{{ $guide['description'] }}</p>
        @endif
        <ul class="list-disc pr-5 space-y-1">
            <li>
                <strong>فعال‌سازی از سمت پلتفرم:</strong>
                در پنل مدیر اصلی (saas-platform) به بخش <em>سایت‌ها → ویرایش سایت → ماژول‌ها</em> بروید
                و ماژول <code>{{ $slug }}</code> را برای این سایت فعال کنید. در صورت پولی بودن، ابتدا باید
                برای این سایت خرید/لایسنس ماژول ثبت شود.
            </li>
            <li>
                <strong>به‌روزرسانی وضعیت در این سایت:</strong>
                پس از هر تغییری در پلتفرم، در همین سایت به بخش <em>ادمین → لایسنس</em> بروید و دکمه
                «بروزرسانی لیست ماژول‌ها از پلتفرم» را بزنید تا وضعیت جدید این ماژول دریافت شود.
            </li>
            <li>
                <strong>ایمنی داده:</strong>
                غیرفعال شدن این ماژول فقط باعث مخفی‌شدن لینک منو و صفحهٔ فعلی می‌شود؛
                هیچ‌یک از داده‌های مربوط به این ماژول (سفارش‌ها، مخاطبین، تنظیمات و …) در دیتابیس سایت حذف نخواهد شد
                و در صورت فعال‌سازی مجدد، دوباره در دسترس خواهند بود.
            </li>
            <li>
                <strong>گسترش در آینده:</strong>
                این صفحه می‌تواند در نسخه‌های بعدی با گزارش‌ها و تنظیمات جزئی‌تر مخصوص ماژول
                <strong>{{ $guide['title'] ?? $slug }}</strong> تکمیل شود، بدون این که نیاز به تغییر در پلتفرم باشد.
            </li>
        </ul>
    </div>
</div>

<div class="card mb-6">
    <div class="card-header">وضعیت ماژول</div>
    <div class="card-body text-sm space-y-2">
        @if($enabled)
            <p class="text-green-700">
                ماژول <strong>{{ $guide['title'] ?? $slug }}</strong> برای این سایت <strong>فعال</strong> است
                و از طرف پلتفرم اجازهٔ استفاده دارد.
            </p>
        @else
            <p class="text-amber-700">
                در حال حاضر ماژول <strong>{{ $guide['title'] ?? $slug }}</strong> برای این سایت فعال نیست.
                برای فعال‌سازی، ماژول <code>{{ $slug }}</code> را در پلتفرم برای این سایت روشن کنید
                و سپس در این سایت از بخش «لایسنس» لیست ماژول‌ها را بروزرسانی کنید.
            </p>
        @endif

        @if($license && $license->license_key)
            <p class="text-slate-600">
                وضعیت لایسنس: {{ $license->statusLabel() }} – دامنه: {{ $license->domain ?: request()->getHost() }}
            </p>
        @else
            <p class="text-slate-600">
                هیچ لایسنس فعالی برای این نصب ثبت نشده است؛ بدون لایسنس معتبر، این ماژول از پلتفرم فعال نخواهد شد.
            </p>
        @endif
    </div>
</div>

@endsection

