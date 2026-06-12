{{-- Wave 7.0c — راهنمای ماژول مطالب وبلاگ (tenant admin). --}}
<details class="mb-6 bg-white border border-slate-200 rounded-xl">
    <summary class="px-4 py-3 cursor-pointer flex items-center justify-between">
        <div class="flex items-center gap-2">
            <x-core-ui::icon name="file-text" class="w- />
            <span class="font-semibold text-sm">راهنمای ماژول مطالب وبلاگ (آخرین بروزرسانی: ۱۴۰۵/۰۲/۲۳)</span>
        </div>
        <span class="text-xs text-slate-500">کلیک برای باز و بسته کردن</span>
    </summary>
    <div class="px-4 pb-4 pt-1 text-sm text-slate-600 space-y-3 leading-relaxed">
        <p>
            ماژول وبلاگ برای انتشار مقاله، خبر، آموزش یا هر محتوای متنیِ زمان‌دار طراحی شده است. هر مطلب می‌تواند به یک یا چند دسته و یک یا چند مجموعه (Series) تعلق داشته باشد و در زمان دلخواه شمسی منتشر شود.
        </p>

        <div>
            <p class="font-semibold text-slate-800 mb-1">جریان انتشار حرفه‌ای</p>
            <ol class="list-decimal pr-5 space-y-1">
                <li>نوشتن مطلب با ویرایشگر پیشرفته (TinyMCE) — پاراگراف، لیست، تصویر، جدول، کد، لینک.</li>
                <li>تعیین <strong>وضعیت کاری</strong>: پیش‌نویس → در انتظار بازبینی → تاییدشده → <strong>منتشرشده</strong>. فقط مطالب «منتشرشده» در آدرس عمومی <code dir="ltr">/blog/&lt;نامک&gt;</code> دیده می‌شوند (با اعمال شرط روی <code>editorial_status</code> در <code>Post::scopePublished()</code>).</li>
                <li>تعیین <strong>زمان انتشار شمسی</strong>: تاریخ + ساعت با تقویم شمسی. مطالب با تاریخ آینده در فهرست عمومی پنهان می‌مانند تا زمان انتشار فرا برسد.</li>
                <li>تنظیم متا (عنوان، توضیح، canonical، robots) و تصویر شاخص.</li>
                <li>دسته‌بندی، مجموعه (post series)، و برچسب‌ها.</li>
            </ol>
        </div>

        <div>
            <p class="font-semibold text-slate-800 mb-1">قابلیت‌های پیشرفته</p>
            <ul class="list-disc pr-5 space-y-1">
                <li><strong>نسخه‌های قبلی (Revisions):</strong> هر ذخیره یک snapshot ایجاد می‌کند؛ از صفحهٔ ویرایش مطلب با دکمهٔ «نسخه‌های پیشین» قابل بازگردانی است.</li>
                <li><strong>زمان مطالعه:</strong> به‌طور خودکار بر اساس تعداد کلمات محاسبه و در صفحهٔ عمومی نمایش داده می‌شود.</li>
                <li><strong>مطالب مرتبط:</strong> از دسته‌ها و برچسب‌های مشترک استخراج می‌شود؛ پس از Wave 7.0a رفتار <code>scopePublished</code> در این رابطه قطعی شده.</li>
                <li><strong>JSON-LD ساختاریافته:</strong> نوع <code>Article</code> با نویسنده، تاریخ انتشار، توضیح و تصویر شاخص.</li>
                <li><strong>RSS و sitemap:</strong> همه‌چیز خودکار؛ تغییر در مطلب کش sitemap را پاک می‌کند.</li>
            </ul>
        </div>

        <div>
            <p class="font-semibold text-slate-800 mb-1">سئو و عملکرد</p>
            <ul class="list-disc pr-5 space-y-1">
                <li>تاریخ ایجاد، آخرین بروزرسانی و زمان انتشار همگی در فهرست ادمین و در صفحات عمومی به شمسی با ارقام فارسی نمایش داده می‌شوند.</li>
                <li>تصاویر مطلب بر اساس viewport بهینه‌سازی می‌شوند (lazy loading + responsive srcset).</li>
                <li>کوتاه‌نوشت (excerpt) اگر دستی پر نشود، از ابتدای محتوا به‌طور خودکار استخراج می‌شود.</li>
            </ul>
        </div>

        <p class="text-xs text-slate-500">
            ماژول وبلاگ از پلتفرم اختصاص داده می‌شود؛ غیرفعال شدن آن فقط مسیرهای عمومی <code dir="ltr">/blog/...</code> و لینک منو را پنهان می‌کند، خود مطالب و دسته‌بندی‌ها در دیتابیس باقی می‌مانند و در فعال‌سازی مجدد بازمی‌گردند.
        </p>
    </div>
</details>

