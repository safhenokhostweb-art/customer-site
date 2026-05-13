{{-- Wave 7.0c — راهنمای ماژول کوپن‌ها (tenant admin). --}}
<details class="mb-6 bg-white border border-slate-200 rounded-xl">
    <summary class="px-4 py-3 cursor-pointer flex items-center justify-between">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
            </svg>
            <span class="font-semibold text-sm">راهنمای ماژول کوپن‌ها (آخرین بروزرسانی: ۱۴۰۵/۰۲/۲۳)</span>
        </div>
        <span class="text-xs text-slate-500">کلیک برای باز و بسته کردن</span>
    </summary>
    <div class="px-4 pb-4 pt-1 text-sm text-slate-600 space-y-3 leading-relaxed">
        <p>
            کوپن‌ها (کدهای تخفیف) ابزار اصلی کمپین‌های فروش و وفاداری مشتریان هستند. هر کوپن یک کد یکتا (case-insensitive) دارد که خریدار در سبد یا چک‌اوت وارد می‌کند.
        </p>

        <div>
            <p class="font-semibold text-slate-800 mb-1">انواع تخفیف</p>
            <ul class="list-disc pr-5 space-y-1">
                <li><strong>درصدی (<code>percent</code>):</strong> فیلد «درصد» را پر کنید (۰ تا ۱۰۰). از Wave 6.1 ستون <code>discount_percent</code> nullable است؛ برای کوپن‌های ثابت بدون مقدار اضافی ذخیره می‌شود.</li>
                <li><strong>مبلغ ثابت (<code>fixed</code>):</strong> فیلد «مبلغ تخفیف» را به تومان پر کنید (به‌صورت عدد صحیح). فیلد «درصد» در این حالت <code>NULL</code> در دیتابیس ذخیره می‌شود.</li>
            </ul>
        </div>

        <div>
            <p class="font-semibold text-slate-800 mb-1">شرایط اعمال</p>
            <ul class="list-disc pr-5 space-y-1">
                <li><strong>حداقل مبلغ سفارش:</strong> اگر مجموع سبد کمتر باشد، کوپن اعمال نمی‌شود (پیام خطای کاربرپسند نمایش داده می‌شود).</li>
                <li><strong>محدودیت تعداد استفاده:</strong> فیلد <code>usage_limit</code>؛ شمارندهٔ <code>usage_count</code> با هر سفارش موفق یک واحد افزایش می‌یابد. لغو سفارش این عدد را idempotent یک واحد کاهش می‌دهد (Wave 6.1 — replay دوم بی‌اثر است).</li>
                <li><strong>تاریخ شروع و پایان:</strong> با تقویم و ساعت شمسی تنظیم می‌شود؛ کوپن خارج از این بازه برای کاربر کار نمی‌کند.</li>
                <li><strong>فعال / غیرفعال:</strong> تیک «فعال» را بردارید تا کوپن موقتاً غیرفعال شود بدون این که حذف گردد.</li>
            </ul>
        </div>

        <div>
            <p class="font-semibold text-slate-800 mb-1">امنیت و بهترین روش</p>
            <ul class="list-disc pr-5 space-y-1">
                <li>کد کوپن همیشه با حروف بزرگ ذخیره می‌شود (case-insensitive)؛ کاربر می‌تواند با هر case وارد کند.</li>
                <li>برای کمپین‌های زمانی، بازهٔ شروع/پایان و <code>usage_limit</code> را با هم تعیین کنید تا کوپن نه پس از پایان کمپین و نه پس از سقف استفاده کار نکند.</li>
                <li>گزارش استفاده در صفحهٔ ویرایش هر کوپن قابل دیدن است.</li>
            </ul>
        </div>

        <p class="text-xs text-slate-500">
            کوپن بخشی از ماژول فروشگاه است؛ غیرفعال شدن فروشگاه از پلتفرم، مسیرهای ادمین کوپن را هم پنهان می‌کند، ولی داده‌ها در دیتابیس باقی می‌مانند.
        </p>
    </div>
</details>
