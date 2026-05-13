{{-- Wave 7.0b — راهنمای ماژول فرم‌ساز (tenant admin).
     این فایل توسط `admin.forms.index` (و در صورت نیاز سایر صفحات فرم) include
     می‌شود. الگوی نمایش `<details>` + `<summary>` با همان قالب
     `admin/activity-log/guide.blade.php` همگام است تا UX یکنواخت بماند. --}}
<details class="mb-6 bg-white border border-slate-200 rounded-xl">
    <summary class="px-4 py-3 cursor-pointer flex items-center justify-between">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span class="font-semibold text-sm">راهنمای ماژول فرم‌ساز (آخرین بروزرسانی: ۱۴۰۵/۰۲/۲۳)</span>
        </div>
        <span class="text-xs text-slate-500">کلیک برای باز و بسته کردن</span>
    </summary>
    <div class="px-4 pb-4 pt-1 text-sm text-slate-600 space-y-3 leading-relaxed">
        <p>
            ماژول فرم‌ساز به شما اجازه می‌دهد فرم‌های اختصاصی (تماس با ما، درخواست همکاری، نظرسنجی و …) با هر تعداد فیلد بسازید،
            آن‌ها را در آدرس عمومی <code dir="ltr">/forms/&lt;نامک&gt;</code> منتشر کنید و پاسخ‌ها را در پنل ادمین مدیریت کنید.
        </p>

        <div>
            <p class="font-semibold text-slate-800 mb-1">جریان کاری در یک نگاه</p>
            <ol class="list-decimal pr-5 space-y-1">
                <li>از همین صفحه «ایجاد فرم جدید» را بزنید؛ عنوان، نامک، متن بالای فرم و سئو را تنظیم کنید.</li>
                <li>پس از ذخیره، در صفحهٔ ویرایش فرم، فیلدها را یکی‌یکی اضافه کنید (متن، ایمیل، تاریخ شمسی، لیست کشویی، رادیو، چک‌باکس).</li>
                <li>با دکمهٔ «پیش‌نمایش» ظاهر فرم را قبل از انتشار بررسی کنید.</li>
                <li>وقتی فرم را «فعال در سایت» علامت زدید، آدرس <code dir="ltr">/forms/&lt;نامک&gt;</code> از سایت در دسترس عموم خواهد بود.</li>
                <li>پاسخ‌های دریافتی در «فرم‌ها ← پاسخ‌های دریافتی» قابل مشاهده و دانلود به CSV هستند.</li>
            </ol>
        </div>

        <div>
            <p class="font-semibold text-slate-800 mb-1">ویرایشگر «متن بالای فرم» (intro)</p>
            <ul class="list-disc pr-5 space-y-1">
                <li>فیلد «راهنما / توضیح کوتاه» با ویرایشگر پیشرفته (TinyMCE) ارائه می‌شود؛ می‌توانید پاراگراف، لیست، لینک، تصویر و جدول وارد کنید.</li>
                <li>محتوای ذخیره‌شده در سایت با یک allowlist امن نمایش داده می‌شود؛ تگ‌های ناامن مثل <code>&lt;script&gt;</code>، <code>&lt;iframe&gt;</code> و event handlerها به‌طور خودکار حذف می‌شوند.</li>
                <li>سقف طول این متن ۶۵٬۰۰۰ کاراکتر است (مطابق ستون TEXT دیتابیس).</li>
                <li>برای متن متای سئو (<em>meta description</em>) از تکست‌اریا ساده استفاده می‌شود؛ HTML در متا توضیح برخلاف استاندارد SEO است و توسط موتورهای جست‌وجو حذف می‌شود.</li>
            </ul>
        </div>

        <div>
            <p class="font-semibold text-slate-800 mb-1">امنیت و محدودسازی</p>
            <ul class="list-disc pr-5 space-y-1">
                <li><strong>کپچا:</strong> اگر ماژول «کپچا» در پلتفرم برای این سایت فعال باشد، فرم به‌طور خودکار از تنظیمات سراسری استفاده می‌کند (Persian numeric، Normal، یا hCaptcha).</li>
                <li><strong>محدودسازی نرخ:</strong> هر فرستندهٔ عمومی حداکثر ۱۰ ارسال در دقیقه (بر اساس کاربر یا IP + نامک فرم) مجاز است؛ تجاوز ⇒ پاسخ ۴۲۹ استاندارد. این محدودیت در سرور اعمال می‌شود و نیازی به تنظیم ندارد.</li>
                <li><strong>اعتبارسنجی:</strong> فیلدهای «الزامی» در سرور هم بررسی می‌شوند؛ تاریخ‌ها شمسی دریافت و خودکار به میلادی تبدیل می‌شوند.</li>
                <li><strong>IP و User-Agent</strong> هر ارسال برای کمک به ردیابی spam، در ستون مربوطه ذخیره می‌شود.</li>
            </ul>
        </div>

        <div>
            <p class="font-semibold text-slate-800 mb-1">سئو</p>
            <ul class="list-disc pr-5 space-y-1">
                <li>عنوان متا، توضیح متا، Canonical و Robots برای هر فرم مستقل قابل تنظیم‌اند.</li>
                <li>اگر فیلدها را خالی بگذارید، عنوان فرم به‌عنوان عنوان متا و ابتدای intro به‌عنوان توضیح متا استفاده می‌شود.</li>
                <li>صفحات فرم خودکار در sitemap.xml ظاهر می‌شوند (با هر تغییر فرم، کش sitemap پاک می‌شود).</li>
            </ul>
        </div>

        <p class="text-xs text-slate-500">
            ماژول فرم‌ساز از پلتفرم اختصاص داده می‌شود؛ غیرفعال شدن آن از پنل پلتفرم فقط لینک منو و مسیرهای عمومی <code dir="ltr">/forms/...</code> را پنهان می‌کند، داده‌های ذخیره‌شده (فرم‌ها، فیلدها، پاسخ‌ها) حذف نمی‌شوند و در صورت فعال‌سازی مجدد به‌طور کامل بازمی‌گردند.
        </p>
    </div>
</details>
