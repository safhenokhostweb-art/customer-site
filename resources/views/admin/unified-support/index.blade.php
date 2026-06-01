@extends('admin.layout')
@section('title', 'صندوق پشتیبانی یکپارچه')

@push('head')
@include('partials.ticket-styles')
@endpush

@section('content')
<div x-data="{
    search: @js($q),
    status: @js($status),
    priority: '',
    channel: @js($channel),
}">

    {{-- Flash --}}
    @foreach(['success','error'] as $type)
    @if(session($type))
    <div class="flex items-center gap-3 {{ $type === 'success' ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200' }} border rounded-2xl px-5 py-3.5 mb-5"
         x-data="{ show: true }" x-show="show" x-transition.duration.300ms>
        <span class="text-sm {{ $type === 'success' ? 'text-green-800' : 'text-red-800' }} font-medium flex-1">{{ session($type) }}</span>
        <button @click="show=false">✕</button>
    </div>
    @endif
    @endforeach

    @if($platformError && $channel !== 'user')
    <div class="flex items-center gap-3 bg-amber-50 border border-amber-200 rounded-2xl px-5 py-3 mb-5">
        <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z"/></svg>
        <span class="text-xs text-amber-700">{{ $platformError }}</span>
    </div>
    @endif

    {{-- ─── Channel Tabs ─────────────────────────────────────────────────── --}}
    <div class="flex items-center gap-2 mb-6 flex-wrap">
        @php
            $tabs = [
                ['key' => 'all',      'label' => 'همه',            'icon' => '📥', 'count' => $stats['total'],          'color' => 'indigo'],
                ['key' => 'platform', 'label' => 'به پلتفرم',      'icon' => '🏢', 'count' => $stats['platform_total'], 'color' => 'purple'],
                ['key' => 'user',     'label' => 'به کاربران',     'icon' => '👤', 'count' => $stats['user_total'],     'color' => 'teal'],
            ];
        @endphp
        @foreach($tabs as $tab)
        @php
            $active = $channel === $tab['key'];
            $base   = $active
                ? 'bg-gradient-to-r from-' . $tab['color'] . '-600 to-' . $tab['color'] . '-500 text-white shadow-md border-transparent'
                : 'bg-white text-gray-600 border-gray-200 hover:border-' . $tab['color'] . '-300 hover:text-' . $tab['color'] . '-600';
        @endphp
        <a href="{{ route('admin.inbox.index', ['channel' => $tab['key'], 'status' => $status, 'q' => $q]) }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl border text-sm font-semibold transition-all {{ $base }}">
            <span>{{ $tab['icon'] }}</span>
            <span>{{ $tab['label'] }}</span>
            @if($tab['count'] > 0)
            <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $active ? 'bg-white/25 text-white' : 'bg-gray-100 text-gray-600' }}">
                {{ \App\Helpers\NumberHelper::formatFa($tab['count']) }}
            </span>
            @endif
        </a>
        @endforeach

        <div class="mr-auto flex items-center gap-2">
            @if($stats['platform_open'] > 0 && $channel !== 'user')
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-purple-50 border border-purple-200 text-xs font-semibold text-purple-700">
                <span class="w-1.5 h-1.5 rounded-full bg-purple-500 animate-ping inline-flex"></span>
                {{ \App\Helpers\NumberHelper::formatFa($stats['platform_open']) }} تیکت پلتفرم باز
            </span>
            @endif
            @if($stats['user_open'] > 0 && $channel !== 'platform')
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-teal-50 border border-teal-200 text-xs font-semibold text-teal-700">
                <span class="w-1.5 h-1.5 rounded-full bg-teal-500 animate-ping inline-flex"></span>
                {{ \App\Helpers\NumberHelper::formatFa($stats['user_open']) }} تیکت کاربری باز
            </span>
            @endif
        </div>
    </div>

    {{-- ─── Stat Cards ───────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="tkt-stat">
            <div class="tkt-stat__accent bg-gradient-to-b from-indigo-500 to-purple-500"></div>
            <div class="flex items-start gap-3">
                <div class="tkt-stat__icon bg-gradient-to-br from-indigo-50 to-purple-50 text-indigo-600">📥</div>
                <div>
                    <div class="tkt-stat__val text-gray-900">{{ \App\Helpers\NumberHelper::formatFa($stats['total']) }}</div>
                    <div class="tkt-stat__label text-gray-500">کل تیکت‌ها</div>
                </div>
            </div>
        </div>
        <div class="tkt-stat cursor-pointer" @click="status='open'">
            <div class="tkt-stat__accent bg-gradient-to-b from-yellow-400 to-orange-400"></div>
            <div class="flex items-start gap-3">
                <div class="tkt-stat__icon bg-yellow-50 text-yellow-600">
                    <span class="relative flex h-6 w-6 items-center justify-center">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-yellow-400 opacity-30"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-yellow-500"></span>
                    </span>
                </div>
                <div>
                    <div class="tkt-stat__val text-yellow-700">{{ \App\Helpers\NumberHelper::formatFa($stats['open']) }}</div>
                    <div class="tkt-stat__label text-yellow-600">باز</div>
                </div>
            </div>
        </div>
        <div class="tkt-stat cursor-pointer" @click="status='in_progress'">
            <div class="tkt-stat__accent bg-gradient-to-b from-blue-400 to-indigo-500"></div>
            <div class="flex items-start gap-3">
                <div class="tkt-stat__icon bg-blue-50 text-blue-600">
                    <svg class="w-6 h-6 animate-spin" style="animation-duration:3s" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                </div>
                <div>
                    <div class="tkt-stat__val text-blue-700">{{ \App\Helpers\NumberHelper::formatFa($stats['in_progress']) }}</div>
                    <div class="tkt-stat__label text-blue-600">در حال بررسی</div>
                </div>
            </div>
        </div>
        <div class="tkt-stat cursor-pointer" @click="status='closed'">
            <div class="tkt-stat__accent bg-gradient-to-b from-green-400 to-teal-500"></div>
            <div class="flex items-start gap-3">
                <div class="tkt-stat__icon bg-green-50 text-green-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <div class="tkt-stat__val text-green-700">{{ \App\Helpers\NumberHelper::formatFa($stats['closed']) }}</div>
                    <div class="tkt-stat__label text-green-600">بسته شده</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Action Bar — RTL: first item = visual right ────────────────── --}}
    <div class="flex items-center justify-between mb-5 gap-3 flex-wrap">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.inbox.create') }}" class="btn-gradient">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                + تیکت جدید
            </a>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('tickets.site.settings') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold text-gray-600 bg-white hover:bg-gray-50 border border-gray-200 transition-all shadow-sm">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                تنظیمات
            </a>
        </div>
    </div>

    {{-- ─── Filter Bar — identical to main admin pattern ────────────────── --}}
    <div class="glass-filter-bar mb-5">
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
            <div class="relative flex-1 min-w-0 w-full sm:w-auto">
                <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" x-model="search" placeholder="جستجوی موضوع یا شماره تیکت..."
                       class="w-full pr-9 pl-4 py-2.5 rounded-xl border border-gray-200 bg-white/80 text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 transition-all">
            </div>
            <select x-model="status" style="max-width:10rem"
                    class="px-4 py-2.5 rounded-xl border border-gray-200 bg-white/80 text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 transition-all w-full sm:w-auto">
                <option value="">همه وضعیت‌ها</option>
                <option value="open">باز</option>
                <option value="in_progress">در حال بررسی</option>
                <option value="closed">بسته</option>
            </select>
            <select x-model="priority" style="max-width:10rem"
                    class="px-4 py-2.5 rounded-xl border border-gray-200 bg-white/80 text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 transition-all w-full sm:w-auto">
                <option value="">همه اولویت‌ها</option>
                <option value="high">🔴 بالا</option>
                <option value="normal">🔵 عادی</option>
                <option value="low">⚪ کم</option>
            </select>
            <button @click="search=''; status=''; priority=''"
                    class="flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl border border-gray-200 bg-white/80 text-sm text-gray-500 hover:bg-gray-50 transition-all justify-center w-full sm:w-auto">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                پاک کردن
            </button>
        </div>
    </div>

    {{-- ─── Tickets Table ─────────────────────────────────────────────────── --}}
    <div class="glass-card-strong overflow-hidden">
    <div class="overflow-x-auto scrollbar-thin">
    <table class="w-full text-right border-collapse" style="min-width:640px">
    <thead>
        <tr class="bg-gradient-to-r from-gray-50 to-white border-b border-gray-100">
            <th class="p-3 pr-5 text-xs font-bold text-gray-500 uppercase">شماره</th>
            <th class="p-3 text-xs font-bold text-gray-500 uppercase">کانال</th>
            <th class="p-3 text-xs font-bold text-gray-500 uppercase">موضوع</th>
            <th class="p-3 text-xs font-bold text-gray-500 uppercase">مخاطب</th>
            <th class="p-3 text-xs font-bold text-gray-500 uppercase">وضعیت</th>
            <th class="p-3 text-xs font-bold text-gray-500 uppercase">اولویت</th>
            <th class="p-3 text-xs font-bold text-gray-500 uppercase">تاریخ</th>
            <th class="p-3 pl-5 text-xs font-bold text-gray-500 uppercase text-left">عملیات</th>
        </tr>
    </thead>
    <tbody>
    @forelse($all as $ticket)
    @php
        $p     = $ticket->priority ?? 'normal';
        $pBg   = $p === 'high' ? 'bg-red-50 text-red-700 border-red-200' : ($p === 'low' ? 'bg-gray-50 text-gray-500 border-gray-200' : 'bg-blue-50 text-blue-600 border-blue-200');
        $pDot  = $p === 'high' ? 'bg-red-500' : ($p === 'low' ? 'bg-gray-400' : 'bg-blue-500');
        $pLbl  = $p === 'high' ? 'بالا' : ($p === 'low' ? 'کم' : 'عادی');
        $ch    = $ticket->channel ?? 'user';
        $showUrl = route('admin.inbox.show', ['channel' => $ch, 'id' => $ticket->id]);
    @endphp
    <tr class="ticket-row border-b border-gray-50 hover:bg-gray-50/50 transition-colors"
        data-subject="{{ $ticket->subject }}"
        data-ticket-no="{{ $ticket->ticket_number }}"
        data-customer="{{ $ticket->customer_name ?? '' }}"
        data-status="{{ $ticket->status }}"
        data-channel="{{ $ch }}"
        data-priority="{{ $p }}"
        x-show="
            (!search || $el.dataset.subject.toLowerCase().includes(search.toLowerCase()) || $el.dataset.ticketNo.includes(search) || $el.dataset.customer.toLowerCase().includes(search.toLowerCase())) &&
            (!status || $el.dataset.status === status) &&
            (!priority || $el.dataset.priority === priority) &&
            (channel === 'all' || $el.dataset.channel === channel)
        "
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0">

        {{-- شماره --}}
        <td class="p-3 pr-5">
            <span class="font-mono font-bold text-xs bg-indigo-50 text-indigo-700 px-2 py-1 rounded-lg border border-indigo-100">{{ $ticket->ticket_number }}</span>
        </td>

        {{-- کانال --}}
        <td class="p-3">
            @if($ch === 'platform')
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                🏢 پلتفرم
            </span>
            @else
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-teal-50 text-teal-700 border border-teal-200">
                👤 کاربر
            </span>
            @endif
        </td>

        {{-- موضوع --}}
        <td class="p-3">
            <a href="{{ $showUrl }}" class="group">
                <span class="text-sm font-semibold text-gray-800 group-hover:text-indigo-700 transition-colors">{{ Str::limit($ticket->subject, 40) }}</span>
                @if($ticket->category)
                <span class="mr-2 text-[10px] text-gray-400 bg-gray-100 px-1.5 py-0.5 rounded">{{ $ticket->category }}</span>
                @endif
            </a>
        </td>

        {{-- مخاطب --}}
        <td class="p-3">
            @if($ticket->customer_name)
            <span class="text-xs text-gray-600">{{ $ticket->customer_name }}</span>
            @elseif($ticket->assignee?->name)
            <span class="text-xs text-gray-400 italic">→ {{ $ticket->assignee->name }}</span>
            @else
            <span class="text-xs text-gray-300">—</span>
            @endif
        </td>

        {{-- وضعیت --}}
        <td class="p-3">
            @if($ticket->status === 'open')
                <span class="tkt-badge bg-yellow-50 text-yellow-700 border-yellow-200"><span class="pdot bg-yellow-500"></span>باز</span>
            @elseif($ticket->status === 'in_progress')
                <span class="tkt-badge bg-blue-50 text-blue-700 border-blue-200"><span class="pdot bg-blue-500"></span>در حال بررسی</span>
            @else
                <span class="tkt-badge bg-green-50 text-green-700 border-green-200"><svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>بسته</span>
            @endif
        </td>

        {{-- اولویت --}}
        <td class="p-3">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border {{ $pBg }}">
                <span class="pdot {{ $pDot }}"></span>{{ $pLbl }}
            </span>
        </td>

        {{-- تاریخ --}}
        <td class="p-3 text-xs text-gray-500 whitespace-nowrap">
            {{ \App\Helpers\Jalali::formatFa($ticket->created_at, 'Y/m/d') }}
            <div class="text-[10px] text-gray-400">{{ \App\Helpers\Jalali::formatFa($ticket->created_at, 'H:i') }}</div>
        </td>

        {{-- عملیات --}}
        <td class="p-3 pl-5">
            <a href="{{ $showUrl }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 transition-all hover:shadow-sm whitespace-nowrap">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                چت
            </a>
        </td>
    </tr>
    @empty
    <tr>
        <td colspan="8" class="text-center py-16">
            <div class="flex flex-col items-center gap-3">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-50 to-purple-50 flex items-center justify-center text-3xl shadow-sm">📭</div>
                <p class="text-gray-600 font-semibold">هیچ تیکتی یافت نشد</p>
                <a href="{{ route('admin.inbox.create') }}" class="btn-gradient !text-sm !py-2 !px-5 mt-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    ثبت تیکت جدید
                </a>
            </div>
        </td>
    </tr>
    @endforelse
    </tbody>
    </table>
    </div>
    </div>

</div>{{-- end x-data --}}

@include('partials.support-features')

@endsection
