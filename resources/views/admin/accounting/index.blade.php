@extends('admin.layout')
@section('title', 'ماژول حسابداری')
@section('header', 'حسابداری (Accounting)')

@section('content')

<div class="card mb-6">
    <div class="card-header">راهنمای ماژول حسابداری</div>
    <div class="card-body space-y-3 text-sm leading-relaxed">
        <p>
            ماژول <strong>حسابداری</strong> برای جمع‌بندی مالی سایت شما طراحی شده است و بر پایهٔ سفارش‌ها و فاکتورهای ثبت‌شده کار می‌کند.
            این صفحه فقط زمانی در دسترس است که ماژول حسابداری برای این سایت از طرف پلتفرم فعال شده باشد (از طریق لایسنس یا خرید ماژول).
        </p>
        <ul class="list-disc pr-5 space-y-1">
            <li>
                <strong>فعال‌سازی از سمت پلتفرم:</strong>
                در پنل مدیر اصلی (saas-platform) وارد بخش <em>سایت‌ها → ویرایش سایت → ماژول‌ها</em> شوید و ماژول
                <code>accounting</code> را برای این سایت فعال کنید. در صورت پولی بودن ماژول، ابتدا باید برای این سایت خرید یا لایسنس ماژول ثبت شود.
            </li>
            <li>
                <strong>به‌روزرسانی ماژول‌ها در سایت مشتری:</strong>
                بعد از تغییرات در پلتفرم، در همین سایت به بخش <em>ادمین → لایسنس</em> بروید و «بروزرسانی لیست ماژول‌ها از پلتفرم» را بزنید
                تا وضعیت جدید ماژول حسابداری دریافت شود.
            </li>
            <li>
                <strong>دسترسی کامل مدیر سایت:</strong>
                وقتی این ماژول فعال باشد، مدیر سایت از طریق این صفحه به نمای کلی مالی (تعداد سفارش‌ها، فاکتورهای پرداخت‌شده و جمع مبالغ) دسترسی دارد.
                این ماژول هیچ محدودیتی روی خود داده‌ها اعمال نمی‌کند و فقط گزارش‌گیری و مشاهده است.
            </li>
            <li>
                <strong>ایمنی داده:</strong>
                اگر ماژول از سمت پلتفرم غیرفعال یا نوع آن به حالت داخلی (internal) تغییر کند، فقط این صفحه و لینک منو مخفی می‌شود؛
                هیچ‌یک از سفارش‌ها، فاکتورها یا داده‌های مالی شما حذف نخواهد شد و در صورت فعال‌سازی مجدد، آمارها دوباره در همین صفحه نمایش داده می‌شوند.
            </li>
            <li>
                <strong>گسترش در آینده:</strong>
                در نسخه‌های بعدی می‌توانید بر اساس همین صفحه، گزارش‌های جزئی‌تر (فروش روزانه/ماهانه، بدهکاران، خروجی اکسل و …) اضافه کنید
                بدون اینکه لازم باشد چیزی در پلتفرم تغییر کند؛ منبع اصلی داده، سفارش‌ها و فاکتورهای همین سایت است.
            </li>
        </ul>

        <p class="text-xs text-slate-500">
            نکته: اگر این صفحه را می‌بینید ولی در سایدبار لینک «حسابداری» نمایش داده نمی‌شود، از منوی سمت راست خارج و دوباره وارد شوید تا کش منو به‌روز شود.
        </p>
    </div>
</div>

<div class="card mb-6">
    <div class="card-header">وضعیت ماژول</div>
    <div class="card-body text-sm">
        @if($enabled)
            <p class="text-green-700">
                این ماژول برای این سایت <strong>فعال</strong> است و از طرف پلتفرم اجازهٔ استفاده دارد.
            </p>
        @else
            <p class="text-amber-700">
                در حال حاضر ماژول حسابداری برای این سایت فعال نیست. برای فعال‌سازی:
                در پلتفرم، ماژول <code>accounting</code> را برای این سایت روشن کنید و سپس در این سایت از بخش «لایسنس»
                ماژول‌ها را بروزرسانی کنید.
            </p>
        @endif
        @if($license && $license->license_key)
            <p class="mt-2 text-slate-600">
                وضعیت لایسنس: {{ $license->statusLabel() }} – دامنه: {{ $license->domain ?: request()->getHost() }}
            </p>
        @else
            <p class="mt-2 text-slate-600">
                هیچ لایسنس فعالی برای این نصب ثبت نشده است.
            </p>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-header">نمای کلی مالی</div>
    <div class="card-body">
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3 text-sm">
            <div class="p-4 rounded-lg border bg-slate-50 border-slate-200">
                <div class="text-xs text-slate-500 mb-1">تعداد کل سفارش‌ها</div>
                <div class="text-2xl font-semibold text-slate-800">{{ number_format($ordersCount) }}</div>
            </div>
            <div class="p-4 rounded-lg border bg-slate-50 border-slate-200">
                <div class="text-xs text-slate-500 mb-1">سفارش‌های پرداخت‌شده</div>
                <div class="text-2xl font-semibold text-emerald-700">{{ number_format($paidOrdersCount) }}</div>
            </div>
            <div class="p-4 rounded-lg border bg-slate-50 border-slate-200">
                <div class="text-xs text-slate-500 mb-1">جمع مبلغ سفارش‌های پرداخت‌شده</div>
                <div class="text-2xl font-semibold text-slate-800">
                    {{ number_format($totalRevenue) }}
                    <span class="text-xs text-slate-500">ریال</span>
                </div>
            </div>
            <div class="p-4 rounded-lg border bg-slate-50 border-slate-200">
                <div class="text-xs text-slate-500 mb-1">تعداد کل فاکتورها</div>
                <div class="text-2xl font-semibold text-slate-800">{{ number_format($invoicesCount) }}</div>
            </div>
            <div class="p-4 rounded-lg border bg-slate-50 border-slate-200">
                <div class="text-xs text-slate-500 mb-1">فاکتورهای پرداخت‌شده</div>
                <div class="text-2xl font-semibold text-emerald-700">{{ number_format($paidInvoicesCount) }}</div>
            </div>
            <div class="p-4 rounded-lg border bg-slate-50 border-slate-200">
                <div class="text-xs text-slate-500 mb-1">جمع مبلغ فاکتورهای پرداخت‌شده</div>
                <div class="text-2xl font-semibold text-slate-800">
                    {{ number_format($invoiceTotal) }}
                    <span class="text-xs text-slate-500">ریال</span>
                </div>
            </div>
        </div>
        <p class="mt-4 text-xs text-slate-500">
            تمام اعداد بالا فقط جهت گزارش‌گیری هستند و تغییری در سفارش‌ها یا فاکتورهای شما ایجاد نمی‌کنند.
        </p>
    </div>
</div>

@endsection

