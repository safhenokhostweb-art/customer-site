@extends('admin.layout')
@section('title', 'تیکت جدید — صندوق پشتیبانی')

@push('head')
@include('partials.ticket-styles')
@endpush

@section('content')
<div class="max-w-2xl mx-auto" x-data="{
    channel: 'user',
    userSearch: '',
    selectedUser: null,
    get canSubmitPlatform() { return @js($hasLicense ? 'true' : 'false') === 'true'; }
}">

    {{-- ─── Flash --}}
    @if(session('error'))
    <div class="flex items-center gap-3 bg-red-50 border border-red-200 rounded-2xl px-5 py-3.5 mb-5"
         x-data="{ show: true }" x-show="show" x-transition.duration.300ms>
        <span class="text-sm text-red-800 flex-1">{{ session('error') }}</span>
        <button @click="show=false">✕</button>
    </div>
    @endif

    {{-- ─── Breadcrumb --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.inbox.index') }}" class="text-sm text-indigo-600 hover:underline flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            بازگشت به صندوق
        </a>
        <span class="text-gray-300">/</span>
        <span class="text-gray-700 font-semibold text-sm">تیکت جدید</span>
    </div>

    {{-- ─── Channel Selector ─────────────────────────────────────────────── --}}
    <div class="glass-card-strong p-6 mb-5">
        <h2 class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2">
            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/></svg>
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
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
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
                            <div class="text-xs text-gray-500 mt-0.5">ارسال تیکت پشتیبانی به تیم پلتفرم</div>
                        </div>
                        <div x-show="channel === 'platform'" class="mr-auto">
                            <div class="w-5 h-5 rounded-full bg-purple-500 flex items-center justify-center">
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                        </div>
                    </div>
                    @if(!$hasLicense)
                    <div class="mt-2 text-xs text-red-500 flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z"/></svg>
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
                <span x-text="channel === 'platform' ? 'شرح مشکل' : 'متن تیکت'"></span>
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
                <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 8.25h3m-3 3h3m-3 3h3"/></svg>
                ارسال پیامک اطلاع‌رسانی به کاربر
            </label>
        </div>

        {{-- Submit --}}
        <div class="flex items-center gap-3 pt-2 border-t border-gray-100">
            <button type="submit"
                    :class="channel === 'platform' ? 'bg-purple-600 hover:bg-purple-700' : 'bg-teal-600 hover:bg-teal-700'"
                    class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl text-sm font-semibold text-white transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>
                <span x-text="channel === 'platform' ? 'ارسال به پلتفرم' : 'ثبت تیکت کاربری'"></span>
            </button>
            <a href="{{ route('admin.inbox.index') }}" class="text-sm text-gray-500 hover:text-gray-700 transition-colors">انصراف</a>
        </div>

    </form>

</div>
@endsection
