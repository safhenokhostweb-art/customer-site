@extends('account.layout')
@section('title', 'پشتیبانی')
@section('header', 'پشتیبانی و تیکت‌ها')

@push('head')
@include('partials.ticket-styles')
<style>
@keyframes needsReplyPulse {
    0%,100% { background-color: transparent; }
    50%      { background-color: rgba(245,158,11,0.08); }
}
.ticket-row--needs-reply {
    animation: needsReplyPulse 2.4s ease-in-out infinite;
}
.ticket-row--needs-reply:hover { animation: none; }
@keyframes badgePulse {
    0%,100% { opacity:1; }
    50%      { opacity:.55; }
}
.needs-reply-badge { animation: badgePulse 1.8s ease-in-out infinite; }
</style>
@endpush

@section('content')

{{-- Stat Cards --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">

    <div class="account-card p-4 col-span-2 sm:col-span-1">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-50 to-purple-50 text-indigo-600 flex items-center justify-center shrink-0 text-xl">📫</div>
            <div>
                <div class="text-2xl font-bold text-slate-800">{{ \App\Helpers\NumberHelper::formatFa($stats['total']) }}</div>
                <div class="text-xs text-slate-500 mt-0.5">کل تیکت‌ها</div>
            </div>
        </div>
    </div>

    <div class="account-card p-4">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center shrink-0">
                <span class="relative flex h-5 w-5 items-center justify-center">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-40"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-amber-500"></span>
                </span>
            </div>
            <div>
                <div class="text-2xl font-bold text-amber-700">{{ \App\Helpers\NumberHelper::formatFa($stats['open']) }}</div>
                <div class="text-xs text-amber-600 mt-0.5">منتظر بررسی</div>
            </div>
        </div>
    </div>

    <div class="account-card p-4">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <x-core-ui::icon name="loader" class="w-5 h-5 animate-spin" style="animation-duration:3s" />
            </div>
            <div>
                <div class="text-2xl font-bold text-blue-700">{{ \App\Helpers\NumberHelper::formatFa($stats['in_progress']) }}</div>
                <div class="text-xs text-blue-600 mt-0.5">در حال بررسی</div>
            </div>
        </div>
    </div>

    <div class="account-card p-4">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <x-core-ui::icon name="check-circle" class="w-5 h-5" />
            </div>
            <div>
                <div class="text-2xl font-bold text-emerald-700">{{ \App\Helpers\NumberHelper::formatFa($stats['closed']) }}</div>
                <div class="text-xs text-emerald-600 mt-0.5">پاسخ داده شده</div>
            </div>
        </div>
    </div>

</div>

{{-- Filter + Table --}}
<div x-data="{
    search: '',
    status: '',
    priority: '',
    allChecked: false,
    visibleCount: {{ $tickets->count() }},
    get hasFilters() { return this.search !== '' || this.status !== '' || this.priority !== ''; }
}"
x-effect="$nextTick(() => { visibleCount = [...$el.querySelectorAll('tr.ticket-row')].filter(r => r.style.display !== 'none').length })">

    {{-- Filter Bar --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm px-4 py-3 mb-3">
        <div class="flex flex-wrap items-center gap-2.5">

            {{-- Search --}}
            <div class="relative flex-1" style="min-width:200px">
                <x-core-ui::icon name="search" class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" />
                <input type="text" x-model="search"
                       placeholder="جستجوی موضوع یا شماره تیکت..."
                       class="w-full pr-9 pl-3 py-2 rounded-xl border border-gray-200 bg-gray-50/60 text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 transition-all">
            </div>

            {{-- Status --}}
            <div class="relative">
                <select x-model="status"
                        class="appearance-none pr-4 pl-8 py-2 rounded-xl border border-gray-200 bg-gray-50/60 text-sm text-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 transition-all cursor-pointer" style="min-width:150px">
                    <option value="">همه وضعیت‌ها</option>
                    <option value="open">باز</option>
                    <option value="in_progress">در حال بررسی</option>
                    <option value="closed">بسته</option>
                </select>
                <x-core-ui::icon name="chevron-down" class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" />
            </div>

            {{-- Priority --}}
            <div class="relative">
                <select x-model="priority"
                        class="appearance-none pr-4 pl-8 py-2 rounded-xl border border-gray-200 bg-gray-50/60 text-sm text-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 transition-all cursor-pointer" style="min-width:150px">
                    <option value="">همه اولویت‌ها</option>
                    <option value="high">بالا</option>
                    <option value="normal">عادی</option>
                    <option value="low">کم</option>
                </select>
                <x-core-ui::icon name="chevron-down" class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" />
            </div>

            {{-- Clear Button --}}
            <button x-show="hasFilters"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    @click="search=''; status=''; priority=''"
                    class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-gray-200 bg-white text-sm text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition-all whitespace-nowrap">
                <x-core-ui::icon name="close" class="w-3.5 h-3.5" />
                پاک کردن
            </button>

        </div>
    </div>

    {{-- Table Card --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        @if($tickets->isEmpty())
            <div class="text-center py-16">
                <div class="w-20 h-20 mx-auto mb-4 rounded-2xl bg-slate-100 flex items-center justify-center">
                    <x-core-ui::icon name="message-circle" class="w-10 h-10 text-slate-300" />
                </div>
                <h3 class="font-semibold text-slate-600 mb-1">هنوز تیکتی ثبت نکرده‌اید</h3>
                <p class="text-sm text-slate-400 mb-5">سوال یا مشکلی دارید؟ یک تیکت پشتیبانی ثبت کنید.</p>
                <a href="{{ route('account.tickets.create') }}" class="btn btn-primary">ثبت اولین تیکت</a>
            </div>
        @else
        <div class="overflow-x-auto">
        <table class="w-full text-right" style="min-width:760px; border-collapse:collapse">
            <thead>
                <tr style="background:#f9fafb; border-bottom:1px solid #f3f4f6">
                    <th class="px-4 py-3.5 w-10">
                        <input type="checkbox" @change="allChecked = $event.target.checked"
                               class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                    </th>
                    <th class="px-3 py-3.5 text-xs font-semibold text-gray-500 text-right">شماره</th>
                    <th class="px-3 py-3.5 text-xs font-semibold text-gray-500 text-right">سایت</th>
                    <th class="px-3 py-3.5 text-xs font-semibold text-gray-500 text-right">موضوع</th>
                    <th class="px-3 py-3.5 text-xs font-semibold text-gray-500 text-right">وضعیت</th>
                    <th class="px-3 py-3.5 text-xs font-semibold text-gray-500 text-right">اولویت</th>
                    <th class="px-3 py-3.5 text-xs font-semibold text-gray-500 text-right">مسئول</th>
                    <th class="px-3 py-3.5 text-xs font-semibold text-gray-500 text-right">تاریخ</th>
                    <th class="px-4 py-3.5 text-xs font-semibold text-gray-500 text-left">عملیات</th>
                </tr>
            </thead>
            <tbody>
            @foreach($tickets as $ticket)
            @php
                $p      = $ticket->priority ?? 'normal';
                $pLabel = $p === 'high' ? 'بالا' : ($p === 'low' ? 'کم' : 'عادی');
                $pBadge = $p === 'high'
                    ? 'bg-red-50 text-red-600 border-red-200'
                    : ($p === 'low' ? 'bg-gray-50 text-gray-500 border-gray-200' : 'bg-blue-50 text-blue-600 border-blue-200');
                $pDot   = $p === 'high' ? 'bg-red-500' : ($p === 'low' ? 'bg-gray-400' : 'bg-blue-500');
                $staffCount = $ticket->staff_replies_count ?? 0;
                $needsReply = $ticket->status !== 'closed'
                    && ($ticket->last_reply_author ?? null) === 'staff';
            @endphp
            <tr class="ticket-row {{ $needsReply ? 'ticket-row--needs-reply' : '' }}"
                data-subject="{{ $ticket->subject }}"
                data-ticket-no="{{ $ticket->ticket_number }}"
                data-status="{{ $ticket->status }}"
                data-priority="{{ $p }}"
                x-show="
                    (!search || $el.dataset.subject?.toLowerCase().includes(search.toLowerCase()) || $el.dataset.ticketNo?.toLowerCase().includes(search.toLowerCase())) &&
                    (!status   || $el.dataset.status   === status) &&
                    (!priority || $el.dataset.priority === priority)
                "
                style="border-bottom:1px solid #f9fafb; transition:background 0.12s{{ $needsReply ? ';border-right:3px solid #f59e0b' : '' }}"
                onmouseenter="this.style.background='rgba(238,242,255,0.45)'"
                onmouseleave="this.style.background=''">

                {{-- Checkbox --}}
                <td class="px-4 py-3.5">
                    <input type="checkbox" :checked="allChecked"
                           class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                </td>

                {{-- Ticket Number --}}
                <td class="px-3 py-3.5">
                    <a href="{{ route('account.tickets.show', $ticket->id) }}"
                       class="font-mono font-bold text-indigo-600 text-xs bg-indigo-50 px-2 py-1 rounded-lg border border-indigo-100 hover:bg-indigo-100 transition-colors whitespace-nowrap">
                        {{ $ticket->ticket_number }}
                    </a>
                </td>

                {{-- Site --}}
                <td class="px-3 py-3.5">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-blue-500 shrink-0"></span>
                        <span class="text-xs text-gray-600 whitespace-nowrap">{{ $siteName }}</span>
                    </div>
                </td>

                {{-- Subject --}}
                <td class="px-3 py-3.5" style="max-width:280px">
                    <a href="{{ route('account.tickets.show', $ticket->id) }}" class="block">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="text-sm font-medium text-gray-800 leading-tight" style="display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical;overflow:hidden">{{ $ticket->subject }}</span>
                            @if($needsReply)
                            <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-amber-100 text-amber-700 border border-amber-200 needs-reply-badge whitespace-nowrap shrink-0">
                                <x-core-ui::icon name="arrow-right" class="w-2.5 h-2.5" />
                                پاسخ کارشناس — پاسخ دهید
                            </span>
                            @endif
                        </div>
                        @if($ticket->category)
                        <span class="inline-block mt-0.5 text-[10px] text-gray-400 bg-gray-100 px-1.5 py-px rounded font-mono leading-tight">{{ $ticket->category }}</span>
                        @endif
                    </a>
                </td>

                {{-- Status --}}
                <td class="px-3 py-3.5">
                    @if($ticket->status === 'open')
                        <span class="tkt-badge bg-yellow-50 text-yellow-700 border-yellow-200">
                            <span class="pdot bg-yellow-500"></span>باز
                        </span>
                    @elseif($ticket->status === 'in_progress')
                        <span class="tkt-badge bg-blue-50 text-blue-700 border-blue-200">
                            <span class="pdot bg-blue-500"></span>در حال بررسی
                        </span>
                    @else
                        <span class="tkt-badge bg-green-50 text-green-700 border-green-200">
                            <x-core-ui::icon name="check" class="w-3 h-3" />بسته
                        </span>
                    @endif
                </td>

                {{-- Priority + Reply Count --}}
                <td class="px-3 py-3.5">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border {{ $pBadge }}">
                            <span class="pdot {{ $pDot }}"></span>{{ $pLabel }}
                        </span>
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-indigo-500 text-white font-bold" style="font-size:10px">
                            {{ \App\Helpers\NumberHelper::formatFa($staffCount) }}
                        </span>
                    </div>
                </td>

                {{-- Assignee --}}
                <td class="px-3 py-3.5">
                    @if($ticket->assignee)
                        <div class="flex items-center gap-1.5">
                            <div class="w-6 h-6 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-white font-bold shrink-0" style="font-size:10px">{{ mb_substr($ticket->assignee->name, 0, 1) }}</div>
                            <span class="text-xs text-gray-600 truncate" style="max-width:90px">{{ $ticket->assignee->name }}</span>
                        </div>
                    @else
                        <span class="text-xs text-gray-400 italic">تخصیص نیافته</span>
                    @endif
                </td>

                {{-- Date --}}
                <td class="px-3 py-3.5 whitespace-nowrap">
                    <span class="text-xs text-gray-600">{{ \App\Helpers\Jalali::formatFa($ticket->created_at, 'Y/m/d') }}</span>
                    <div class="text-gray-400 mt-0.5" style="font-size:10px">{{ \App\Helpers\Jalali::formatFa($ticket->created_at, 'H:i') }}</div>
                </td>

                {{-- Actions --}}
                <td class="px-4 py-3.5 text-left">
                    <a href="{{ route('account.tickets.show', $ticket->id) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 transition-all whitespace-nowrap">
                        <x-core-ui::icon name="eye" class="w-3.5 h-3.5" />
                        مشاهده
                    </a>
                </td>
            </tr>
            @endforeach

            {{-- No results after filter --}}
            <tr x-show="hasFilters && visibleCount === 0">
                <td colspan="9" class="text-center py-12">
                    <p class="text-gray-400 text-sm">نتیجه‌ای با این فیلترها یافت نشد.</p>
                    <button @click="search=''; status=''; priority=''" class="mt-2 text-xs text-indigo-600 hover:underline">پاک کردن فیلترها</button>
                </td>
            </tr>
            </tbody>
        </table>
        </div>

        {{-- Create + Pagination --}}
        <div class="flex items-center justify-between px-4 py-3 border-t border-gray-100">
            <a href="{{ route('account.tickets.create') }}" class="btn-gradient">
                <x-core-ui::icon name="plus" class="w-4 h-4" />
                تیکت جدید
            </a>
            @if($tickets->hasPages())
            <div>{{ $tickets->withQueryString()->links() }}</div>
            @endif
        </div>
        @endif
    </div>

</div>

@include('partials.support-features')

@endsection
