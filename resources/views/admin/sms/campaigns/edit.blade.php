@extends('admin.layouts.app')
@section('title', 'ویرایش کمپین')

@section('content')
<div class="max-w-3xl mx-auto space-y-6" x-data="{
    audienceType: '{{ $campaign->audience_type }}',
    message: @js($campaign->message),
    get charCount() { return this.message.length; },
    get smsCount() { return this.message.length === 0 ? 0 : Math.ceil(this.message.length / 70); }
}">

    <div class="flex items-center gap-3">
        <a href="{{ route('admin.sms.campaigns.show', $campaign) }}" class="p-2 text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">ویرایش کمپین</h1>
            <p class="text-sm text-gray-500">{{ $campaign->name }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.sms.campaigns.update', $campaign) }}" class="space-y-5">
        @csrf @method('PUT')

        <div class="bg-white rounded-xl border border-gray-100 p-5 space-y-4">
            <h2 class="font-semibold text-gray-800">اطلاعات کمپین</h2>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">نام کمپین <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $campaign->name) }}" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">متن پیامک <span class="text-red-500">*</span></label>
                <textarea name="message" x-model="message" rows="4" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 resize-none">{{ old('message', $campaign->message) }}</textarea>
                <div class="flex items-center justify-between mt-1">
                    <span class="text-xs text-gray-400" x-text="`${charCount} کاراکتر`"></span>
                    <span class="text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded" x-text="`${smsCount} پیامک`"></span>
                </div>
                @error('message')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 p-5 space-y-4">
            <h2 class="font-semibold text-gray-800">مخاطبین</h2>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                @foreach([
                    'all'    => ['label'=>'همه مشتریان', 'desc'=>'از سفارشات'],
                    'users'  => ['label'=>'کاربران سایت', 'desc'=>'ثبت‌نام‌شده'],
                    'crm'    => ['label'=>'مخاطبین CRM', 'desc'=>'با فیلتر'],
                    'manual' => ['label'=>'شماره‌های دستی', 'desc'=>'وارد کردن دستی'],
                ] as $type => $opt)
                <label class="relative cursor-pointer">
                    <input type="radio" name="audience_type" value="{{ $type }}" x-model="audienceType" class="sr-only peer">
                    <div class="p-3 border-2 rounded-xl text-center transition-all peer-checked:border-indigo-500 peer-checked:bg-indigo-50 border-gray-200 hover:border-indigo-300">
                        <p class="text-xs font-medium text-gray-700">{{ $opt['label'] }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $opt['desc'] }}</p>
                    </div>
                </label>
                @endforeach
            </div>

            <div x-show="audienceType === 'crm'" x-transition>
                <label class="block text-sm font-medium text-gray-700 mb-1">فیلتر وضعیت CRM</label>
                <select name="audience_filter[crm_status]" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                    <option value="">همه مخاطبین CRM</option>
                    <option value="lead" @selected(($campaign->audience_filter['crm_status'] ?? '') === 'lead')>سرنخ</option>
                    <option value="prospect" @selected(($campaign->audience_filter['crm_status'] ?? '') === 'prospect')>بالقوه</option>
                    <option value="customer" @selected(($campaign->audience_filter['crm_status'] ?? '') === 'customer')>مشتری</option>
                    <option value="churned" @selected(($campaign->audience_filter['crm_status'] ?? '') === 'churned')>از دست رفته</option>
                </select>
            </div>

            <div x-show="audienceType === 'manual'" x-transition>
                <label class="block text-sm font-medium text-gray-700 mb-1">شماره موبایل‌ها</label>
                <textarea name="audience_filter[mobiles]" rows="5" placeholder="09123456789"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono focus:ring-2 focus:ring-indigo-500 resize-none">{{ $campaign->audience_filter['mobiles'] ?? '' }}</textarea>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('admin.sms.campaigns.show', $campaign) }}" class="px-5 py-2 text-sm text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">انصراف</a>
            <button type="submit" class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                ذخیره تغییرات
            </button>
        </div>
    </form>

</div>
@endsection
