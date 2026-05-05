@extends('admin.layout')
@section('title', 'سئو پیشرفته')
@section('header', 'سئو پیشرفته (Advanced SEO)')

@section('content')

<div class="card mb-6">
    <div class="card-header">راهنمای ماژول سئو پیشرفته</div>
    <div class="card-body space-y-3 text-sm leading-relaxed">
        <p>
            ماژول <strong>سئو پیشرفته</strong> پل ارتباطی بین تنظیمات سئو در پلتفرم و محتوای همین سایت است.
            این ماژول از تنظیمات <strong>tenant-level</strong> پلتفرم (عنوان پیش‌فرض، توضیحات، تصویر OG، robots و …)
            و همچنین متاهای تعریف‌شده روی صفحات، مطالب وبلاگ و محصولات برای ساخت متا تگ‌ها، canonical و کارت‌های شبکه‌های اجتماعی استفاده می‌کند.
        </p>
        <ul class="list-disc pr-5 space-y-1">
            <li>
                <strong>فعال‌سازی از سمت پلتفرم:</strong>
                در پنل مدیر اصلی (saas-platform) وارد <em>سایت‌ها → ویرایش سایت → ماژول‌ها</em> شوید
                و ماژول <code>seo_advanced</code> را برای این سایت فعال کنید (در صورت پولی بودن، ابتدا باید لایسنس/خرید ثبت شود).
            </li>
            <li>
                <strong>به‌روزرسانی در سایت مشتری:</strong>
                پس از تغییر وضعیت ماژول یا تنظیمات سئو در پلتفرم، در این سایت به بخش
                <em>ادمین → لایسنس</em> بروید و دکمهٔ «بروزرسانی لیست ماژول‌ها از پلتفرم» را بزنید
                تا آخرین وضعیت ماژول‌ها و تنظیمات UX/SEO دریافت شود.
            </li>
            <li>
                <strong>نقش این ماژول در سایت:</strong>
                این ماژول عنوان صفحه، توضیحات متا، تصویر اشتراک‌گذاری (OG/Twitter)، robots و canonical را
                برای صفحات، مطالب وبلاگ و محصولات هماهنگ می‌کند تا خروجی سئو یکدست و قابل کنترل باشد.
                منبع اصلی منطق در کلاس‌های <code>App\Helpers\Seo</code> و <code>App\Services\SeoService</code> است.
            </li>
            <li>
                <strong>ایمنی داده:</strong>
                اگر ماژول از سمت پلتفرم غیرفعال یا نوع آن به internal تغییر کند، فقط این صفحه و منوی «سئو پیشرفته» مخفی می‌شود؛
                محتوای سایت (صفحات، مطالب، محصولات) و متاهای ذخیره‌شده دست‌نخورده باقی می‌مانند و در صورت فعال‌سازی مجدد،
                دوباره در همین صفحه و در خروجی سئو استفاده خواهند شد.
            </li>
            <li>
                <strong>گسترش‌های پیشنهادی:</strong>
                در نسخه‌های بعدی می‌توانید از همین صفحه برای افزودن ابزارهایی مثل «آنالیز سئو صفحه»،
                چک‌لیست متا برای هر URL، و لینک به sitemap و گزارش crawl استفاده کنید، بدون تغییر در سمت پلتفرم.
            </li>
        </ul>
        <p class="text-xs text-slate-500">
            نکته: این صفحه فقط تنظیمات و وضعیت را نمایش می‌دهد؛ ویرایش متاهای هر صفحه/مطلب از همان فرم ویرایش محتوا انجام می‌شود.
        </p>
    </div>
</div>

<div class="card mb-6">
    <div class="card-header">وضعیت ماژول و تنظیمات سئو</div>
    <div class="card-body text-sm space-y-2">
        @if($enabled)
            <p class="text-green-700">
                ماژول سئو پیشرفته برای این سایت <strong>فعال</strong> است و از طرف پلتفرم اجازهٔ استفاده دارد.
            </p>
        @else
            <p class="text-amber-700">
                در حال حاضر ماژول سئو پیشرفته برای این سایت فعال نیست. برای فعال‌سازی:
                در پلتفرم، ماژول <code>seo_advanced</code> را برای این سایت روشن کنید و سپس در این سایت از بخش «لایسنس»
                ماژول‌ها و تنظیمات را بروزرسانی کنید.
            </p>
        @endif

        @if($license && $license->license_key)
            <p class="text-slate-600">
                وضعیت لایسنس: {{ $license->statusLabel() }} – دامنه: {{ $license->domain ?: request()->getHost() }}
            </p>
        @else
            <p class="text-slate-600">
                هیچ لایسنس فعالی برای این نصب ثبت نشده است؛ بدون لایسنس معتبر، تنظیمات سئو از پلتفرم دریافت نمی‌شود.
            </p>
        @endif

        <div class="mt-3 grid gap-3 md:grid-cols-2">
            <div class="p-3 rounded-lg border bg-slate-50 border-slate-200">
                <div class="text-xs text-slate-500 mb-1">عنوان پیش‌فرض (seo_default_title)</div>
                <div class="text-sm text-slate-800">
                    {{ $seoConfig['seo_default_title'] ?? '—' }}
                </div>
            </div>
            <div class="p-3 rounded-lg border bg-slate-50 border-slate-200">
                <div class="text-xs text-slate-500 mb-1">توضیح پیش‌فرض (seo_default_description)</div>
                <div class="text-sm text-slate-800">
                    {{ $seoConfig['seo_default_description'] ?? '—' }}
                </div>
            </div>
            <div class="p-3 rounded-lg border bg-slate-50 border-slate-200">
                <div class="text-xs text-slate-500 mb-1">robots</div>
                <div class="text-sm text-slate-800">
                    {{ $seoConfig['seo_robots'] ?? config('seo.default_robots', 'index, follow') }}
                </div>
            </div>
            <div class="p-3 rounded-lg border bg-slate-50 border-slate-200">
                <div class="text-xs text-slate-500 mb-1">Twitter card</div>
                <div class="text-sm text-slate-800">
                    {{ $seoConfig['seo_twitter_card'] ?? config('seo.default_twitter_card', 'summary') }}
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">نمای کلی سئو محتوا</div>
    <div class="card-body text-sm">
        <div class="grid gap-4 md:grid-cols-3">
            <div class="p-4 rounded-lg border bg-slate-50 border-slate-200">
                <div class="text-xs text-slate-500 mb-1">صفحات ثابت</div>
                <div class="text-2xl font-semibold text-slate-800">{{ number_format($pagesCount) }}</div>
                <div class="text-xs text-slate-500 mt-1">
                    منتشر شده: {{ number_format($publishedPagesCount) }}
                </div>
            </div>
            <div class="p-4 rounded-lg border bg-slate-50 border-slate-200">
                <div class="text-xs text-slate-500 mb-1">مطالب وبلاگ</div>
                <div class="text-2xl font-semibold text-slate-800">{{ number_format($postsCount) }}</div>
                <div class="text-xs text-slate-500 mt-1">
                    منتشر شده: {{ number_format($publishedPostsCount) }}
                </div>
            </div>
            <div class="p-4 rounded-lg border bg-slate-50 border-slate-200">
                <div class="text-xs text-slate-500 mb-1">محصولات فروشگاه</div>
                <div class="text-2xl font-semibold text-slate-800">{{ number_format($productsCount) }}</div>
                <div class="text-xs text-slate-500 mt-1">
                    فعال: {{ number_format($activeProductsCount) }}
                </div>
            </div>
        </div>
        <p class="mt-4 text-xs text-slate-500">
            برای بهبود سئوی هر مورد، در صفحهٔ ویرایش همان صفحه/مطلب/محصول، فیلدهای <code>عنوان سئو</code> و
            <code>توضیح سئو</code> را تکمیل کنید. این مقادیر در متا تگ‌ها و پیش‌نمایش شبکه‌های اجتماعی استفاده می‌شوند.
        </p>
    </div>
</div>

@endsection

