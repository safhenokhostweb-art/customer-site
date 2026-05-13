{{-- Wave 7.0c — راهنمای ماژول سفارش‌ها (tenant admin).
     الگوی <details>/<summary> مطابق admin/forms/guide.blade.php و
     admin/activity-log/guide.blade.php برای UX یکنواخت. --}}
<details class="mb-6 bg-white border border-slate-200 rounded-xl">
    <summary class="px-4 py-3 cursor-pointer flex items-center justify-between">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            <span class="font-semibold text-sm">راهنمای ماژول سفارش‌ها (آخرین بروزرسانی: ۱۴۰۵/۰۲/۲۳)</span>
        </div>
        <span class="text-xs text-slate-500">کلیک برای باز و بسته کردن</span>
    </summary>
    <div class="px-4 pb-4 pt-1 text-sm text-slate-600 space-y-3 leading-relaxed">
        <p>
            این صفحه فهرست تمام سفارش‌های ثبت‌شدهٔ فروشگاه (هم کاربران لاگین‌شده، هم سفارش‌های مهمان) را نشان می‌دهد.
            هر سفارش شمارهٔ یکتای ۶ رقمی (با پدینگ صفر) دارد که در زمان ثبت سفارش به‌صورت atomic (با قفل advisory در MySQL) تولید می‌شود تا حتی در شرایط ترافیک بالا و چند ثبت همزمان، تکراری ساخته نشود.
        </p>

        <div>
            <p class="font-semibold text-slate-800 mb-1">ستون‌های جدول</p>
            <ul class="list-disc pr-5 space-y-1">
                <li><strong>شماره:</strong> شناسهٔ یکتای انسانی سفارش — برای ارجاع به مشتری و پشتیبانی.</li>
                <li><strong>مشتری:</strong> نام ثبت‌شده در فرم چک‌اوت (می‌تواند مهمان باشد).</li>
                <li><strong>مبلغ:</strong> مجموع نهایی سفارش بعد از کسر کوپن و افزودن هزینهٔ ارسال و مالیات (به تومان).</li>
                <li><strong>وضعیت:</strong> چرخهٔ pending → paid → shipped → delivered (یا cancelled). تغییر وضعیت از طریق سرویس <code>OrderStatusService</code> انجام می‌شود و در جدول <code>order_status_history</code> ثبت می‌گردد.</li>
                <li><strong>تاریخ:</strong> زمان ثبت سفارش به شمسی با ارقام فارسی.</li>
            </ul>
        </div>

        <div>
            <p class="font-semibold text-slate-800 mb-1">جریان وضعیت‌ها</p>
            <ul class="list-disc pr-5 space-y-1">
                <li>تغییر وضعیت در صفحهٔ «مشاهده» سفارش انجام می‌شود؛ دلیل اختیاری ثبت می‌شود و در لاگ دیده می‌شود.</li>
                <li>گذار از <code>cancelled</code> به هیچ وضعیت دیگری مجاز نیست؛ گذار به همان وضعیت فعلی هم رد می‌شود.</li>
                <li>لغو سفارش (<code>cancelled</code>) به‌صورت idempotent موجودی محصول و variant را بازگشت می‌دهد و usage_count کوپن را یک بار کم می‌کند (replay دوم هیچ side-effect جدیدی ندارد).</li>
                <li>هر تغییر وضعیت در «لاگ فعالیت ادمین» نیز ثبت می‌شود.</li>
            </ul>
        </div>

        <div>
            <p class="font-semibold text-slate-800 mb-1">امنیت و دسترسی</p>
            <ul class="list-disc pr-5 space-y-1">
                <li>سفارش‌های مهمان فقط توسط ادمین یا با ترکیب «شمارهٔ سفارش + ایمیل تأییدشده» در صفحهٔ عمومی قابل دسترسی‌اند.</li>
                <li>کاربر لاگین‌شده فقط سفارش‌هایی را در «حساب من» می‌بیند که <code>user_id</code> آن‌ها با شناسهٔ او مطابقت داشته باشد؛ تطبیق با ایمیل پروفایل (که از Wave 6.1 حذف شد) ممکن نیست.</li>
            </ul>
        </div>

        <p class="text-xs text-slate-500">
            ماژول فروشگاه از پلتفرم اختصاص داده می‌شود؛ غیرفعال شدن آن فقط لینک منو و مسیرهای عمومی <code dir="ltr">/shop/...</code> و <code dir="ltr">/account/orders</code> را پنهان می‌کند، داده‌های ذخیره‌شده (سفارش‌ها، تاریخچهٔ وضعیت، فاکتورها) حذف نمی‌شوند.
        </p>
    </div>
</details>
