{{-- @deprecated PHASE 7.15 — Use tickets::site.tickets.show (canonical package view). Route to: tickets.site.show --}}
@php
    use App\Helpers\Jalali;
    // Use hardcoded defaults since Setting model doesn't exist in customer-site
    $chatColors = [
        'customer_bg' => '#e3f2fd',
        'customer_text' => '#1a1a2e',
        'staff_bg' => '#dcf8c6',
        'staff_text' => '#1a1a2e',
    ];
@endphp

@extends('admin.layout')

@section('title', 'جزئیات تیکت')

@push('head')
<style>
.glass-card-strong{background:rgba(255,255,255,0.82);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,0.3);border-radius:1.25rem;box-shadow:0 8px 32px rgba(15,23,42,0.06)}
.btn-gradient{display:inline-flex;align-items:center;gap:0.5rem;padding:0.7rem 1.75rem;border-radius:0.75rem;font-weight:600;font-size:0.875rem;transition:all 0.25s ease;background:linear-gradient(135deg,#6366f1,#a855f7);color:#fff;border:none;cursor:pointer;box-shadow:0 4px 14px rgba(99,102,241,0.25)}
.btn-gradient:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(99,102,241,0.35)}
.btn-ghost{display:inline-flex;align-items:center;gap:0.5rem;padding:0.7rem 1.75rem;border-radius:0.75rem;font-weight:600;font-size:0.875rem;transition:all 0.2s ease;background:transparent;color:#64748b;border:1.5px solid #e2e8f0;cursor:pointer;text-decoration:none}
.btn-ghost:hover{background:#f8fafc;border-color:#cbd5e1;color:#475569}
.error-glass{background:rgba(254,242,242,0.85);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);border:1px solid rgba(239,68,68,0.2);border-radius:1rem;padding:1rem 1.25rem}
.status-badge{display:inline-flex;align-items:center;gap:0.4rem;padding:0.3rem 0.85rem;border-radius:9999px;font-size:0.75rem;font-weight:600;white-space:nowrap;border:1px solid transparent;transition:all 0.2s ease}
.form-input{width:100%!important;padding:0.75rem 1rem!important;border:1px solid #e2e8f0!important;border-radius:0.75rem!important;font-size:0.875rem!important;transition:all 0.2s ease;background:rgba(255,255,255,0.6)}
.form-input:focus{outline:none!important;border-color:#a5b4fc!important;box-shadow:0 0 0 3px rgba(99,102,241,0.12)!important}
</style>
@endpush

@section('content')
<div class="max-w-6xl mx-auto">

{{-- Header --}}
<div class="flex items-center gap-4 mb-6">
    <a href="{{ route('admin.support.index') }}" class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-medium text-gray-600 bg-white/70 hover:bg-white border border-gray-200 transition-all hover:shadow-sm">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        بازگشت به لیست
    </a>
    <div>
        <h2 class="text-xl sm:text-2xl font-bold text-gray-900">جزئیات تیکت</h2>
        <p class="text-sm text-gray-500 mt-1">{{ $ticket['ticket_number'] ?? '' }} — {{ Str::limit($ticket['subject'] ?? '', 50) }}</p>
    </div>
</div>

@if($error)
<div class="error-glass mb-6 flex items-start gap-3">
    <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <p class="text-sm text-red-700 m-0">{{ $error }}</p>
</div>
@endif

@if(session('success'))
<div class="glass-card-strong mb-6 p-4 border-r-4 border-r-green-400 flex items-center gap-3 rounded-2xl" x-data="{ show: true }" x-show="show" x-transition>
    <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <span class="text-sm text-green-800 font-medium">{{ session('success') }}</span>
    <button @click="show = false" class="mr-auto text-green-400 hover:text-green-600 transition-colors">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
</div>
@endif

@if($ticket)
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    {{-- Chat Panel (2/3 width) --}}
    <div class="lg:col-span-2">
        {{-- Chat Header / Ticket Info Bar --}}
        <div class="bg-white rounded-t-2xl shadow-sm border border-gray-200 border-b-0 overflow-hidden">
            <div class="p-4 border-b border-gray-100">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        @if(($ticket['status'] ?? '') === 'open')
                            <span class="status-badge bg-yellow-50 text-yellow-700 border-yellow-200">
                                <span class="w-2 h-2 rounded-full bg-yellow-500 animate-pulse"></span>
                                باز
                            </span>
                        @elseif(($ticket['status'] ?? '') === 'in_progress')
                            <span class="status-badge bg-blue-50 text-blue-700 border-blue-200">
                                <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                                در حال بررسی
                            </span>
                        @else
                            <span class="status-badge bg-gray-50 text-gray-600 border-gray-200">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                بسته شده
                            </span>
                        @endif
                        @if(($ticket['priority'] ?? 'normal') === 'high')
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-600 border border-red-200">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                                اولویت بالا
                            </span>
                        @elseif(($ticket['priority'] ?? 'normal') === 'low')
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-50 text-gray-500 border border-gray-200">اولویت کم</span>
                        @else
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-600 border border-blue-200">اولویت معمولی</span>
                        @endif
                    </div>
                </div>
                <div class="mt-3">
                    <h2 class="text-lg font-bold text-gray-900">{{ $ticket['subject'] ?? '' }}</h2>
                </div>
            </div>
        </div>

        {{-- Chat Messages Area with Alpine.js --}}
        <div class="bg-white shadow-sm border border-gray-200 border-t-0"
             x-data="supportChat()"
             data-customer-bg="{{ $chatColors['customer_bg'] }}"
             data-customer-text="{{ $chatColors['customer_text'] }}"
             data-staff-bg="{{ $chatColors['staff_bg'] }}"
             data-staff-text="{{ $chatColors['staff_text'] }}"
             style="min-height: 400px; display: flex; flex-direction: column;">
            <div x-ref="chatMessages"
                 @scroll="handleScroll"
                 class="flex-1 overflow-y-auto p-4 space-y-1"
                 style="background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); max-height: 450px; scroll-behavior: smooth;">

                {{-- Initial Message (Customer) --}}
                <div class="flex items-end gap-2 max-w-[85%] sm:max-w-[75%] mb-4" style="align-self: flex-start;">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0 shadow-sm" style="background-color: #6366f1;">
                        {{ isset($ticket['customer_name']) ? mb_substr($ticket['customer_name'], 0, 1) : 'ش' }}
                    </div>
                    <div>
                        <div class="rounded-2xl rounded-br-sm px-4 py-2.5 shadow-sm"
                             style="background-color: {{ $chatColors['customer_bg'] }}; color: {{ $chatColors['customer_text'] }};">
                            <p class="text-xs font-semibold mb-1 opacity-80">{{ $ticket['customer_name'] ?? 'شما' }}</p>
                            <p class="text-sm leading-relaxed whitespace-pre-wrap">{{ $ticket['message'] ?? '' }}</p>
                            @if(!empty($ticket['attachments']))
                                <div class="flex flex-wrap gap-1.5 mt-2">
                                    @foreach($ticket['attachments'] as $file)
                                        <a href="{{ Storage::url($file['path'] ?? '') }}" target="_blank"
                                           class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[10px] bg-white/60 hover:bg-white border border-gray-200 transition-all"
                                           title="{{ $file['name'] ?? '' }}">
                                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            <span class="truncate max-w-[100px]">{{ $file['name'] ?? '' }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <span class="text-[10px] text-gray-400 mt-1 px-1">{{ Jalali::formatFa(\Carbon\Carbon::parse($ticket['created_at'] ?? now()), 'Y/m/d H:i') }}</span>
                    </div>
                </div>

                {{-- Replies Loop --}}
                @php $lastDate = null; @endphp
                @foreach(($ticket['replies'] ?? []) as $reply)
                    @php
                        $replyDate = \Carbon\Carbon::parse($reply['created_at'] ?? now())->toDateString();
                        $isStaff = ($reply['author_type'] ?? '') === 'staff';
                        $authorName = $isStaff ? ($reply['author_name'] ?? 'پشتیبان') : ($reply['customer_name'] ?? 'شما');
                    @endphp

                    @if($replyDate !== $lastDate)
                        @php $lastDate = $replyDate; @endphp
                        <div class="flex justify-center my-3 w-full">
                            <span class="text-[10px] text-gray-400 bg-white/80 px-3 py-1 rounded-full shadow-sm">
                                @if(\Carbon\Carbon::parse($reply['created_at'])->isToday())
                                    امروز
                                @elseif(\Carbon\Carbon::parse($reply['created_at'])->isYesterday())
                                    دیروز
                                @else
                                    {{ Jalali::formatFa(\Carbon\Carbon::parse($reply['created_at']), 'Y/m/d') }}
                                @endif
                            </span>
                        </div>
                    @endif

                    <div class="flex items-end gap-2 max-w-[85%] sm:max-w-[75%] mb-4 {{ $isStaff ? 'flex-row-reverse' : '' }}"
                         style="align-self: {{ $isStaff ? 'flex-end' : 'flex-start' }};">
                        @if($isStaff)
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0 shadow-sm" style="background-color: #10b981;">
                                {{ $authorName ? mb_substr($authorName, 0, 1) : 'پ' }}
                            </div>
                        @else
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0 shadow-sm" style="background-color: #6366f1;">
                                {{ $authorName ? mb_substr($authorName, 0, 1) : 'ش' }}
                            </div>
                        @endif
                        <div>
                            <div class="rounded-2xl px-4 py-2.5 shadow-sm {{ $isStaff ? 'rounded-bl-sm' : 'rounded-br-sm' }}"
                                 style="background-color: {{ $isStaff ? $chatColors['staff_bg'] : $chatColors['customer_bg'] }}; color: {{ $isStaff ? $chatColors['staff_text'] : $chatColors['customer_text'] }};">
                                <p class="text-xs font-semibold mb-1 opacity-80">
                                    {{ $authorName }}
                                    @if($isStaff)
                                        <span class="inline-flex items-center gap-0.5 text-[9px] bg-green-200/50 px-1.5 py-0.5 rounded-full mr-1">
                                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                            پشتیبان
                                        </span>
                                    @endif
                                </p>
                                <p class="text-sm leading-relaxed whitespace-pre-wrap">{{ $reply['message'] ?? '' }}</p>
                                @if(!empty($reply['attachments']))
                                    <div class="flex flex-wrap gap-1.5 mt-2">
                                        @foreach($reply['attachments'] as $file)
                                            <a href="{{ Storage::url($file['path'] ?? '') }}" target="_blank"
                                               class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[10px] bg-white/60 hover:bg-white border border-gray-200 transition-all"
                                               title="{{ $file['name'] ?? '' }}">
                                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                <span class="truncate max-w-[100px]">{{ $file['name'] ?? '' }}</span>
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <span class="text-[10px] text-gray-400 mt-1 px-1 {{ $isStaff ? 'text-left block' : '' }}">{{ Jalali::formatFa(\Carbon\Carbon::parse($reply['created_at'] ?? now()), 'H:i') }}</span>
                        </div>
                    </div>
                @endforeach

                {{-- Scroll to bottom button --}}
                <div x-show="showScrollBtn"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="sticky bottom-2 flex justify-center">
                    <button @click="scrollDown"
                            class="bg-white text-gray-600 rounded-full px-4 py-2 shadow-lg border border-gray-200 hover:bg-gray-50 transition-all text-xs font-medium flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                        پیام‌های جدید
                    </button>
                </div>
            </div>

            {{-- Reply Input with Alpine.js loading state --}}
            @if(($ticket['status'] ?? '') !== 'closed')
            <div class="border-t border-gray-200 bg-white/80 p-3">
                <form method="POST" action="{{ route('admin.support.reply', $ticket['id']) }}"
                      @submit="isSending = true; setTimeout(() => { isSending = false; }, 3000);"
                      class="flex items-end gap-2">
                    @csrf
                    <div class="flex-1 relative">
                        <textarea name="message"
                                  x-model="message"
                                  class="w-full rounded-2xl border border-gray-300 resize-none py-3 px-4 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all"
                                  rows="2" required maxlength="5000"
                                  placeholder="پاسخ خود را بنویسید..."
                                  style="background:rgba(255,255,255,0.8)"
                                  @keydown.enter.prevent="if(!$event.shiftKey) $el.closest('form').submit()"></textarea>
                        <small class="text-[10px] text-gray-400 mt-1 block mr-2">اینتر = ارسال | Shift+Enter = خط جدید</small>
                    </div>
                    <button type="submit"
                            :disabled="isSending || !message.trim()"
                            class="flex items-center justify-center rounded-full p-3 min-w-[48px] h-[48px] transition-all"
                            :class="isSending || !message.trim() ? 'bg-gray-300 text-gray-500 cursor-not-allowed' : 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-md hover:shadow-lg'">
                        <template x-if="!isSending">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        </template>
                        <template x-if="isSending">
                            <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        </template>
                    </button>
                </form>
            </div>
            @else
            <div class="border-t border-gray-200 bg-gray-50/80 p-4 text-center">
                <span class="text-sm text-gray-500">این تیکت بسته شده است.</span>
            </div>
            @endif
        </div>
    </div>

    {{-- Sidebar Panel (1/3 width) --}}
    <div class="space-y-4">
        {{-- Ticket Info --}}
        <div class="glass-card-strong p-4">
            <h3 class="font-bold text-sm mb-3 flex items-center gap-2">
                <svg class="w-5 h-5 flex-shrink-0 text-gray-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                اطلاعات تیکت
            </h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">شماره</span>
                    <span class="font-medium">{{ $ticket['ticket_number'] ?? '—' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">وضعیت</span>
                    <span class="font-medium">
                        @if(($ticket['status'] ?? '') === 'open') باز
                        @elseif(($ticket['status'] ?? '') === 'in_progress') در حال بررسی
                        @else بسته @endif
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">اولویت</span>
                    <span class="font-medium">{{ ($ticket['priority'] ?? 'normal') === 'high' ? 'بالا' : (($ticket['priority'] ?? 'normal') === 'low' ? 'کم' : 'عادی') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">موبایل</span>
                    <span class="font-medium text-left" dir="ltr">{{ $ticket['customer_mobile'] ?? '—' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">نام</span>
                    <span class="font-medium">{{ $ticket['customer_name'] ?? '—' }}</span>
                </div>
                @if(!empty($ticket['category']))
                <div class="flex justify-between">
                    <span class="text-gray-500">دسته‌بندی</span>
                    <span class="font-medium">{{ $ticket['category'] }}</span>
                </div>
                @endif
                @if(!empty($ticket['tags']))
                <div class="flex justify-between">
                    <span class="text-gray-500">برچسب‌ها</span>
                    <span class="font-medium text-left" dir="ltr">{{ $ticket['tags'] }}</span>
                </div>
                @endif
                <div class="flex justify-between">
                    <span class="text-gray-500">تاریخ ایجاد</span>
                    <span class="font-medium">{{ Jalali::formatFa(\Carbon\Carbon::parse($ticket['created_at'] ?? now()), 'Y/m/d H:i') }}</span>
                </div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="glass-card-strong p-4">
            <h3 class="font-bold text-sm mb-3 flex items-center gap-2">
                <svg class="w-5 h-5 flex-shrink-0 text-gray-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                اقدامات سریع
            </h3>
            <div class="space-y-2">
                <a href="{{ route('admin.support.index') }}" class="btn-ghost w-full !text-sm !py-2 !justify-center">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    بازگشت به لیست
                </a>
                <a href="{{ route('admin.support.history') }}" class="btn-ghost w-full !text-sm !py-2 !justify-center">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    تاریخچه کامل
                </a>
            </div>
        </div>
    </div>
</div>
@endif
</div>

@endsection
