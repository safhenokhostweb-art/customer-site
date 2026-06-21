@extends('admin.layouts.app')
@section('title', 'کمپین پیامکی جدید')

@section('content')
<div class="max-w-3xl mx-auto space-y-6" x-data="{
    audienceType: 'all',
    message: '',
    get charCount() { return this.message.length; },
    get smsCount() { return this.message.length === 0 ? 0 : Math.ceil(this.message.length / 70); }
}">

    <div class="flex items-center gap-3">
        <a href="{{ route('admin.sms.campaigns.index') }}" class="p-2 text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">کمپین پیامکی جدید</h1>
            <p class="text-sm text-gray-500">ارسال پیامک گروهی به مخاطبین</p>
        </div>
    </div>

    @if(!$smsConfigured)
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-800">
        ⚠️ تنظیمات پیامک تکمیل نشده. <a href="{{ route('admin.sms.index') }}" class="underline font-medium">تنظیمات پیامک</a> را ابتدا تکمیل کنید.
    </div>
    @endif

    <form method="POST" action="{{ route('admin.sms.campaigns.store') }}" class="space-y-5">
        @csrf

        {{-- Campaign name --}}
        <div class="bg-white rounded-xl border border-gray-100 p-5 space-y-4">
            <h2 class="font-semibold text-gray-800 flex items-center gap-2">
                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                اطلاعات کمپین
            </h2>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">نام کمپین <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" placeholder="مثال: تخفیف عید نوروز ۱۴۰۴" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    متن پیامک <span class="text-red-500">*</span>
                    <span class="text-gray-400 font-normal mr-1">(حداکثر ۱۰۰۰ کاراکتر)</span>
                </label>
                <textarea name="message" x-model="message" rows="4" placeholder="متن پیامک خود را اینجا بنویسید..." required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none">{{ old('message') }}</textarea>
                <div class="flex items-center justify-between mt-1">
                    <span class="text-xs text-gray-400" x-text="`${charCount} کاراکتر`"></span>
                    <span class="text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded" x-text="`${smsCount} پیامک`"></span>
                </div>
                @error('message')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- Audience --}}
        <div class="bg-white rounded-xl border border-gray-100 p-5 space-y-4">
            <h2 class="font-semibold text-gray-800 flex items-center gap-2">
                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                مخاطبین
            </h2>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                @foreach([
                    'all'    => ['label'=>'همه مشتریان', 'icon'=>'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z', 'desc'=>'مشتریانی که سفارش داشتند'],
                    'users'  => ['label'=>'کاربران سایت', 'icon'=>'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', 'desc'=>'کاربران ثبت‌نام‌شده'],
                    'crm'    => ['label'=>'مخاطبین CRM', 'icon'=>'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10', 'desc'=>'مخاطبین CRM با فیلتر'],
                    'manual' => ['label'=>'شماره‌های دستی', 'icon'=>'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z', 'desc'=>'وارد کردن دستی شماره'],
                ] as $type => $opt)
                <label class="relative cursor-pointer">
                    <input type="radio" name="audience_type" value="{{ $type }}" x-model="audienceType" class="sr-only peer" @checked(old('audience_type','all') === $type)>
                    <div class="p-3 border-2 rounded-xl text-center transition-all peer-checked:border-indigo-500 peer-checked:bg-indigo-50 border-gray-200 hover:border-indigo-300">
                        <svg class="w-5 h-5 mx-auto mb-1 text-gray-400 peer-checked:text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $opt['icon'] }}"/></svg>
                        <p class="text-xs font-medium text-gray-700">{{ $opt['label'] }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $opt['desc'] }}</p>
                    </div>
                </label>
                @endforeach
            </div>

            {{-- CRM filter --}}
            <div x-show="audienceType === 'crm'" x-transition>
                <label class="block text-sm font-medium text-gray-700 mb-1">فیلتر بر اساس وضعیت CRM</label>
                <select name="audience_filter[crm_status]" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                    <option value="">همه مخاطبین CRM</option>
                    <option value="lead">سرنخ (Lead)</option>
                    <option value="prospect">بالقوه (Prospect)</option>
                    <option value="customer">مشتری (Customer)</option>
                    <option value="churned">از دست رفته (Churned)</option>
                </select>
            </div>

            {{-- Manual numbers --}}
            <div x-show="audienceType === 'manual'" x-transition>
                <label class="block text-sm font-medium text-gray-700 mb-1">شماره موبایل‌ها</label>
                <textarea name="audience_filter[mobiles]" rows="5" placeholder="09123456789&#10;09198765432&#10;(هر شماره در یک خط)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono focus:ring-2 focus:ring-indigo-500 resize-none">{{ old('audience_filter.mobiles') }}</textarea>
                <p class="text-xs text-gray-400 mt-1">هر شماره در یک خط جداگانه — فرمت: ۰۹۱۲۳۴۵۶۷۸۹</p>
            </div>
        </div>

        {{-- Schedule (optional) --}}
        <div class="bg-white rounded-xl border border-gray-100 p-5 space-y-3" x-data="{schedule: false}">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-gray-800 flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    زمان‌بندی (اختیاری)
                </h2>
                <button type="button" x-on:click="schedule = !schedule" class="text-sm text-indigo-600 hover:text-indigo-700">
                    <span x-text="schedule ? 'لغو' : 'تنظیم زمان'"></span>
                </button>
            </div>
            <div x-show="schedule" x-transition>
                <label class="block text-sm font-medium text-gray-700 mb-1">تاریخ و ساعت ارسال</label>
                <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}"
                    class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                <p class="text-xs text-gray-400 mt-1">اگر خالی بگذارید، کمپین به‌صورت پیش‌نویس ذخیره می‌شود</p>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('admin.sms.campaigns.index') }}" class="px-5 py-2 text-sm text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">انصراف</a>
            <button type="submit" class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                ذخیره کمپین
            </button>
        </div>
    </form>

</div>
@endsection
