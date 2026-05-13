{{-- Wave 7.0c — راهنمای ماژول محصولات (tenant admin). --}}
<details class="mb-6 bg-white border border-slate-200 rounded-xl">
    <summary class="px-4 py-3 cursor-pointer flex items-center justify-between">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            <span class="font-semibold text-sm">راهنمای ماژول محصولات (آخرین بروزرسانی: ۱۴۰۵/۰۲/۲۳)</span>
        </div>
        <span class="text-xs text-slate-500">کلیک برای باز و بسته کردن</span>
    </summary>
    <div class="px-4 pb-4 pt-1 text-sm text-slate-600 space-y-3 leading-relaxed">
        <p>
            ماژول محصولات هستهٔ فروشگاه است و انواع گوناگون محصول را پشتیبانی می‌کند: محصول ساده، محصول متغیر (variant)،
            محصول دانلودی، محصول مجازی، و محصول گروهی (bundle). برای ثبت محصول جدید روی «محصول جدید» کلیک کنید.
        </p>

        <div>
            <p class="font-semibold text-slate-800 mb-1">فیلدهای اصلی</p>
            <ul class="list-disc pr-5 space-y-1">
                <li><strong>عنوان و نامک (slug):</strong> در آدرس عمومی <code dir="ltr">/shop/&lt;نامک&gt;</code> استفاده می‌شود.</li>
                <li><strong>وضعیت انتشار:</strong> پیش‌نویس / منتشرشده / آرشیو. فقط محصولات منتشرشده در فهرست عمومی فروشگاه دیده می‌شوند.</li>
                <li><strong>قیمت اصلی و قیمت حراج:</strong> هر دو با ارقام فارسی نمایش داده می‌شوند؛ زمان پایان حراج با تقویم و ساعت شمسی تنظیم می‌شود.</li>
                <li><strong>توضیحات تفصیلی و سیاست فروش:</strong> با ویرایشگر پیشرفته (TinyMCE) — می‌توانید پاراگراف، لیست، تصویر، جدول و لینک وارد کنید.</li>
                <li><strong>دسته‌بندی و برچسب:</strong> هم به‌صورت رابطه چندبه‌چند با pivot timestamps (ستون‌های <code>created_at</code> / <code>updated_at</code> پس از Wave 6.2 افزوده شدند).</li>
                <li><strong>گالری و تصویر شاخص:</strong> آپلود تصاویر متعدد با ترتیب قابل تنظیم.</li>
            </ul>
        </div>

        <div>
            <p class="font-semibold text-slate-800 mb-1">موجودی و واریانت‌ها</p>
            <ul class="list-disc pr-5 space-y-1">
                <li>برای محصولات متغیر، هر variant موجودی مستقل دارد؛ هنگام چک‌اوت ابتدا تمام variantها با ترتیب پایدار قفل می‌شوند تا oversell رخ ندهد (P1-S1 از Wave 6.1).</li>
                <li>محصولات bundle می‌توانند چند child مشترک با bundleهای دیگر داشته باشند؛ همگی در checkout با <code>lockForUpdate</code> قفل می‌شوند.</li>
                <li>برای محصولات با <code>stock</code> برابر null، موجودی نامحدود فرض می‌شود (مثلاً محصول دانلودی).</li>
            </ul>
        </div>

        <div>
            <p class="font-semibold text-slate-800 mb-1">سئو و نمایش</p>
            <ul class="list-disc pr-5 space-y-1">
                <li>عنوان متا، توضیح متا، Canonical و Robots برای هر محصول مستقل قابل تنظیم‌اند.</li>
                <li>صفحات محصول خودکار در sitemap.xml می‌آیند؛ JSON-LD ساختاریافته نوع Product برای موتورهای جست‌وجو تولید می‌شود.</li>
                <li>تاریخ ایجاد و آخرین به‌روزرسانی هر محصول در فهرست ادمین به شمسی نمایش داده می‌شود.</li>
            </ul>
        </div>

        <p class="text-xs text-slate-500">
            ماژول فروشگاه از پلتفرم اختصاص داده می‌شود؛ غیرفعال شدن آن فقط لینک منو و مسیرهای عمومی <code dir="ltr">/shop/...</code> را پنهان می‌کند، داده‌های محصولات و دسته‌بندی‌ها حذف نمی‌شوند.
        </p>
    </div>
</details>
