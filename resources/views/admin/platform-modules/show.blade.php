@extends('admin.layout')
@section('title', $guide['title'] ?? 'ماژول پلتفرمی')
@section('header', $guide['title'] ?? 'ماژول پلتفرمی')

@section('content')

{{-- Wave 7.0c — Coming Soon banner. این صفحه برای ماژول‌های پلتفرمی است که
     هنوز UI کامل اختصاصی در سایت ندارند و فقط راهنما/وضعیت نمایش می‌دهند. --}}
<div class="admin-coming-soon-banner" role="status">
    <div class="admin-coming-soon-banner__icon" aria-hidden="true">
        <x-core-ui::icon name="clock" class="w-6 h-6" />
    </div>
    <div class="admin-coming-soon-banner__body">
        <p class="admin-coming-soon-banner__title">
            <span class="admin-coming-soon-badge">به‌زودی</span>
            UI اختصاصی ماژول «{{ $guide['title'] ?? $slug }}» در دست توسعه است.
        </p>
        <p class="admin-coming-soon-banner__sub">
            در نسخه‌های آینده این صفحه با فرم‌ها، فهرست‌ها و گزارش‌های مخصوص این ماژول جایگزین می‌شود.
            تا آن زمان، راهنما و وضعیت فعال‌سازی این ماژول را در پایین می‌بینید.
        </p>
    </div>
</div>
<style>
    .admin-coming-soon-banner { display: flex; gap: 0.85rem; align-items: flex-start; padding: 0.95rem 1.05rem; margin: 0 0 1.25rem; border-radius: 0.75rem; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border: 1px solid #fbbf24; color: #78350f; }
    .admin-coming-soon-banner__icon { color: #b45309; flex-shrink: 0; padding-top: 0.1rem; }
    .admin-coming-soon-banner__title { margin: 0; font-weight: 600; font-size: 0.95rem; line-height: 1.5; }
    .admin-coming-soon-banner__sub { margin: 0.35rem 0 0; font-size: 0.82rem; line-height: 1.7; color: #92400e; }
    .admin-coming-soon-badge { display: inline-block; padding: 0.15rem 0.55rem; margin-left: 0.35rem; border-radius: 9999px; background: #b45309; color: #fef3c7; font-size: 0.72rem; font-weight: 700; letter-spacing: 0.02em; }
</style>

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



