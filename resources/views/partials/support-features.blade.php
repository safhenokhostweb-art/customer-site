<div class="mt-14 mb-2">
    <div class="flex items-center gap-3 mb-6 mt-8">
        <div class="flex-1 h-px bg-slate-200"></div>
        <p class="text-sm font-bold text-slate-400 tracking-wide px-2">قابلیت‌های ماژول پشتیبانی</p>
        <div class="flex-1 h-px bg-slate-200"></div>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">

        @php
        $features = [
            ['icon' => '💬', 'title' => 'چت آنی', 'desc' => 'رابط چت با حباب‌های رنگی و اسکرول خودکار'],
            ['icon' => '📱', 'title' => 'اعلان پیامکی', 'desc' => 'SMS خودکار برای رویدادهای تیکت'],
            ['icon' => '⚡', 'title' => 'SLA هوشمند', 'desc' => 'ردیابی و هشدار نقض توافق سطح خدمت'],
            ['icon' => '👤', 'title' => 'تخصیص کارشناس', 'desc' => 'مدیریت کامل تخصیص و سابقه تغییرات'],
            ['icon' => '🔗', 'title' => 'چند سایته', 'desc' => 'تخصیص تیکت به سایت دلخواه'],
            ['icon' => '📝', 'title' => 'یادداشت داخلی', 'desc' => 'یادداشت‌های محرمانه برای کارشناسان'],
            ['icon' => '🤖', 'title' => 'API پروکسی', 'desc' => 'اتصال سایت مشتری از طریق API'],
            ['icon' => '🔔', 'title' => 'ناظران تیکت', 'desc' => 'سیستم CC و ناظران خودکار'],
            ['icon' => '📊', 'title' => 'داشبورد تحلیلی', 'desc' => 'آمار، نقشه حرارتی و بار کاری'],
            ['icon' => '♾️', 'title' => 'حاکمیت یکپارچه', 'desc' => 'یک منبع معتبر برای تمام پروژه‌ها'],
        ];
        @endphp

        @foreach($features as $f)
        <div class="flex flex-col items-center text-center p-4 rounded-2xl border border-slate-100 bg-white/70 hover:bg-white hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
            <span class="text-3xl mb-2 leading-none">{{ $f['icon'] }}</span>
            <span class="text-xs font-bold text-slate-700 mb-1">{{ $f['title'] }}</span>
            <span class="text-[10px] text-slate-400 leading-relaxed">{{ $f['desc'] }}</span>
        </div>
        @endforeach

    </div>
</div>
