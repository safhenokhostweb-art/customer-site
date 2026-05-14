<details class="mb-6 bg-white border border-slate-200 rounded-xl">
    <summary class="px-4 py-3 cursor-pointer flex items-center justify-between">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
            </svg>
            <span class="font-semibold text-sm">راهنمای ماژول لاگ فعالیت ادمین (آخرین بروزرسانی: ۱۴۰۵/۰۲/۲۴)</span>
        </div>
        <span class="text-xs text-slate-500">کلیک برای باز و بسته کردن</span>
    </summary>
    <div class="px-4 pb-4 pt-1 text-sm text-slate-600 space-y-2">
        <p>این صفحه فقط عملیات حساس انجام‌شده توسط مدیران سایت (tenant admin) را نمایش می‌دهد؛ فعالیت‌های end-user (مثل ورود، تغییر رمز، ویرایش آدرس) در «حساب من ← فعالیت‌های اخیر» قابل مشاهده‌اند.</p>
        <ul class="list-disc pr-5 space-y-1 text-slate-600">
            <li>برای هر رویداد، کلید عملیات (مثل <code>module.settings_updated</code>) و توضیح فارسی نمایش داده می‌شود.</li>
            <li>اگر تغییرات قابل ردیابی باشد (مثل تنظیمات قبل و بعد)، در ستون «جزئیات» به‌صورت JSON قابل دیدن است.</li>
            <li>تاریخ‌های فیلتر «از» و «تا» را به‌صورت شمسی وارد کنید (مثلاً <code>۱۴۰۵/۰۲/۰۱</code>).</li>
            <li>این جدول هرگز رکوردی را از UI حذف نمی‌کند؛ نگهداری خودکار با دستور <code>php artisan admin-activity-log:prune</code> (پیش‌فرض روزانه ۰۳:۰۵) و پنجرهٔ retention از <code>config/audit.php</code> یا <code>AUDIT_RETENTION_DAYS</code> انجام می‌شود. با <code>AUDIT_PRUNE_DRY_RUN=true</code> فقط گزارش شمارش می‌گیرید.</li>
            <li>انتشار یا زمان‌بندی مطالب وبلاگ با اکشن‌های <code>blog.post_published</code> و <code>blog.post_scheduled</code> در همین جدول ثبت می‌شود.</li>
            <li>برای آغاز کار، عملیاتی مثل «بازخوانی تنظیمات از پلتفرم» در صفحهٔ لایسنس را اجرا کنید تا اولین رکوردها ثبت شوند.</li>
        </ul>
    </div>
</details>
