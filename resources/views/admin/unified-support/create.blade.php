@extends('admin.layout')
@section('title', 'تیکت جدید — صندوق پشتیبانی')

@push('head')
@include('partials.ticket-styles')
@endpush

@section('content')
@php
    $frNotice = \App\Models\Setting::get('tickets.fr_customer_notice',
        'این درخواست صرفاً به عنوان پیشنهاد دریافت می‌گردد و پس از امکان‌سنجی، بررسی فنی و تأیید نهایی، نتیجه از طریق همین تیکت یا سایر کانال‌های اطلاع‌رسانی به شما اعلام خواهد شد.'
    );
@endphp
<div class="max-w-2xl mx-auto" x-data="{
    channel: 'user',
    ticketType: 'support',
    userSearch: '',
    selectedUser: null,
    get canSubmitPlatform() { return @js($hasLicense ? 'true' : 'false') === 'true'; }
}">


    {{-- ─── Breadcrumb --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.inbox.index') }}" class="text-sm text-indigo-600 hover:underline flex items-center gap-1">
            <x-core-ui::icon name="arrow-right" class="w-4 h-4" />
            بازگشت به صندوق
        </a>
        <span class="text-gray-400">/</span>
        <span class="text-gray-700 font-semibold text-sm">تیکت جدید</span>
    </div>

    {{-- ─── Channel Selector ─────────────────────────────────────────────── --}}
    <div class="glass-card-strong p-6 mb-5">
        <h2 class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2">
            <x-core-ui::icon name="message" class="w-4 h-4 text-indigo-500" />
            مقصد تیکت را انتخاب کنید
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

            {{-- User channel --}}
            <label class="relative cursor-pointer">
                <input type="radio" name="_channel_sel" value="user" x-model="channel" class="sr-only">
                <div :class="channel === 'user' ? 'ring-2 ring-teal-500 border-teal-300 bg-teal-50' : 'border-gray-200 bg-white hover:border-teal-200'"
                     class="rounded-2xl border p-4 transition-all">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-teal-100 flex items-center justify-center text-teal-600 text-xl shrink-0">👤</div>
                        <div>
                            <div class="font-bold text-sm text-gray-800">به کاربر سایت</div>
                            <div class="text-xs text-gray-500 mt-0.5">ارسال تیکت به یک کاربر نهایی سایت</div>
                        </div>
                        <div x-show="channel === 'user'" class="mr-auto">
                            <div class="w-5 h-5 rounded-full bg-teal-500 flex items-center justify-center">
                                <x-core-ui::icon name="check" class="w-3 h-3 text-white" />
                            </div>
                        </div>
                    </div>
                </div>
            </label>

            {{-- Platform channel --}}
            <label class="relative cursor-pointer" :class="!canSubmitPlatform ? 'opacity-60 cursor-not-allowed' : ''">
                <input type="radio" name="_channel_sel" value="platform" x-model="channel" :disabled="!canSubmitPlatform" class="sr-only">
                <div :class="channel === 'platform' ? 'ring-2 ring-purple-500 border-purple-300 bg-purple-50' : 'border-gray-200 bg-white hover:border-purple-200'"
                     class="rounded-2xl border p-4 transition-all">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 text-xl shrink-0">🏢</div>
                        <div>
                            <div class="font-bold text-sm text-gray-800">به پلتفرم اصلی</div>
                            <div class="text-xs text-gray-500 mt-0.5">پشتیبانی یا درخواست ویژگی به تیم SaaS</div>
                        </div>
                        <div x-show="channel === 'platform'" class="mr-auto">
                            <div class="w-5 h-5 rounded-full bg-purple-500 flex items-center justify-center">
                                <x-core-ui::icon name="check" class="w-3 h-3 text-white" />
                            </div>
                        </div>
                    </div>
                    @if(!$hasLicense)
                    <div class="mt-2 text-xs text-red-500 flex items-center gap-1">
                        <x-core-ui::icon name="alert-triangle" class="w-3 h-3" />
                        لایسنس فعالی یافت نشد
                    </div>
                    @endif
                </div>
            </label>

        </div>
    </div>

    {{-- ─── Form ────────────────────────────────────────────────────────────── --}}
    <form action="{{ route('admin.inbox.store') }}" method="POST" enctype="multipart/form-data"
          class="glass-card-strong p-6 flex flex-col gap-5">
        @csrf

        {{-- Pass chosen channel to controller --}}
        <input type="hidden" name="channel" :value="channel">

        {{-- Pass ticket type (only relevant for platform channel) --}}
        <input type="hidden" name="type" :value="channel === 'platform' ? ticketType : 'support'">

        {{-- ─── نوع تیکت — فقط برای کانال پلتفرم ──────────────────────────── --}}
        <div x-show="channel === 'platform'" x-transition>
            <label class="block text-sm font-semibold text-gray-700 mb-3">نوع درخواست</label>
            <div class="grid grid-cols-2 gap-3">

                {{-- پشتیبانی --}}
                <label class="cursor-pointer">
                    <input type="radio" name="_type_sel" value="support" x-model="ticketType" class="sr-only">
                    <div :class="ticketType === 'support' ? 'ring-2 ring-indigo-500 border-indigo-300 bg-indigo-50' : 'border-gray-200 bg-white hover:border-indigo-200'"
                         class="rounded-2xl border p-4 transition-all">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 text-lg shrink-0">💬</div>
                            <div class="flex-1 min-w-0">
                                <div class="font-bold text-sm text-gray-800">پشتیبانی</div>
                                <div class="text-xs text-gray-500 mt-0.5">مشکل یا سوال فنی</div>
                            </div>
                            <div x-show="ticketType === 'support'" class="shrink-0">
                                <div class="w-5 h-5 rounded-full bg-indigo-500 flex items-center justify-center">
                                    <x-core-ui::icon name="check" class="w-3 h-3 text-white" />
                                </div>
                            </div>
                        </div>
                    </div>
                </label>

                {{-- درخواست ویژگی --}}
                <label class="cursor-pointer">
                    <input type="radio" name="_type_sel" value="feature_request" x-model="ticketType" class="sr-only">
                    <div :class="ticketType === 'feature_request' ? 'ring-2 ring-purple-500 border-purple-300 bg-purple-50' : 'border-gray-200 bg-white hover:border-purple-200'"
                         class="rounded-2xl border p-4 transition-all">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 text-lg shrink-0">💡</div>
                            <div class="flex-1 min-w-0">
                                <div class="font-bold text-sm text-gray-800">درخواست ویژگی</div>
                                <div class="text-xs text-gray-500 mt-0.5">پیشنهاد بهبود محصول</div>
                            </div>
                            <div x-show="ticketType === 'feature_request'" class="shrink-0">
                                <div class="w-5 h-5 rounded-full bg-purple-500 flex items-center justify-center">
                                    <x-core-ui::icon name="check" class="w-3 h-3 text-white" />
                                </div>
                            </div>
                        </div>
                    </div>
                </label>

            </div>

            {{-- Section M: Customer notice --}}
            <div x-show="ticketType === 'feature_request'" x-transition
                 class="mt-3 bg-amber-50 border border-amber-200 rounded-xl p-3"
                 style="display:none">
                <div class="flex items-start gap-2">
                    <x-core-ui::icon name="info-circle" class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" />
                    <p class="text-xs text-amber-800 leading-relaxed">{{ $frNotice }}</p>
                </div>
            </div>
        </div>

        {{-- User selector (only for 'user' channel) --}}
        <div x-show="channel === 'user'" x-transition>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                کاربر مخاطب
                <span class="text-gray-400 font-normal text-xs mr-1">(اختیاری)</span>
            </label>
            <select name="for_user_id"
                    class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm focus:ring-2 focus:ring-teal-200 focus:border-teal-400 transition-all">
                <option value="">— بدون کاربر مشخص (از طرف ادمین) —</option>
                @foreach($users as $user)
                <option value="{{ $user->id }}">{{ $user->name }} @if($user->mobile) — {{ $user->mobile }} @endif</option>
                @endforeach
            </select>
            <p class="text-xs text-gray-400 mt-1.5">اگر انتخاب نشود، تیکت بدون نام کاربر ثبت می‌شود.</p>
        </div>

        {{-- Subject --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">موضوع <span class="text-red-500">*</span></label>
            <input type="text" name="subject" value="{{ old('subject') }}" required
                   placeholder="موضوع تیکت را بنویسید..."
                   class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 transition-all">
            @error('subject')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        {{-- Message --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                <span x-text="channel === 'platform' && ticketType === 'feature_request' ? 'شرح درخواست' : (channel === 'platform' ? 'شرح مشکل' : 'متن تیکت')"></span>
                <span class="text-red-500">*</span>
            </label>
            <textarea name="message" required rows="5"
                      placeholder="توضیحات کامل را بنویسید..."
                      class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 transition-all resize-none">{{ old('message') }}</textarea>
            @error('message')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        {{-- Priority + Category --}}
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">اولویت</label>
                <select name="priority" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 transition-all">
                    <option value="normal" {{ old('priority') === 'normal' || !old('priority') ? 'selected' : '' }}>🔵 عادی</option>
                    <option value="high"   {{ old('priority') === 'high'   ? 'selected' : '' }}>🔴 بالا</option>
                    <option value="low"    {{ old('priority') === 'low'    ? 'selected' : '' }}>⚪ کم</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">دسته‌بندی <span class="text-gray-400 font-normal text-xs">(اختیاری)</span></label>
                <input type="text" name="category" value="{{ old('category') }}"
                       placeholder="مثلاً: فنی، مالی، سفارش..."
                       class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 transition-all">
            </div>
        </div>

        {{-- SMS option (user channel only) --}}
        <div x-show="channel === 'user'" x-transition class="flex items-center gap-2">
            <input type="checkbox" name="send_sms" value="1" id="send_sms" class="rounded text-teal-600">
            <label for="send_sms" class="text-sm text-gray-600 cursor-pointer flex items-center gap-1.5">
                <x-core-ui::icon name="sms" class="w-4 h-4 text-green-500" />
                ارسال پیامک اطلاع‌رسانی به کاربر
            </label>
        </div>

        {{-- Submit --}}
        <div class="flex items-center gap-3 pt-2 border-t border-gray-100">
            <button type="submit"
                    :class="channel === 'platform' ? 'bg-purple-600 hover:bg-purple-700' : 'bg-teal-600 hover:bg-teal-700'"
                    class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl text-sm font-semibold text-white transition-all shadow-sm">
                <x-core-ui::icon name="arrow-up" class="w-4 h-4" />
                <span x-text="channel === 'platform' && ticketType === 'feature_request' ? 'ارسال درخواست ویژگی' : (channel === 'platform' ? 'ارسال به پلتفرم' : 'ثبت تیکت کاربری')"></span>
            </button>
            <a href="{{ route('admin.inbox.index') }}" class="text-sm text-gray-500 hover:text-gray-700 transition-colors">انصراف</a>
        </div>

    </form>

</div>
@endsection
