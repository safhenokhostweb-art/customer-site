@extends('admin.layout')
@section('title', 'داشبورد پشتیبانی کاربران')

@push('head')
@include('partials.ticket-styles')
<style>
@keyframes awaitsPulse{0%,100%{background-color:transparent}50%{background-color:rgba(99,102,241,.06)}}
.ticket-awaits-reply{animation:awaitsPulse 2.4s ease-in-out infinite}
.ticket-awaits-reply:hover{animation:none}
@keyframes badgeFade{0%,100%{opacity:1}50%{opacity:.55}}
.awaits-reply-badge{animation:badgeFade 1.8s ease-in-out infinite}
</style>
@endpush

@section('content')
<div x-data="{
    search: '',
    status: '',
    priority: '',
    userFilter: '',
    bulkSelected: [],
    get allIds() { return Array.from(document.querySelectorAll('.ticket-check')).map(el => el.value); },
    toggleAll(checked) { this.bulkSelected = checked ? this.allIds : []; },
    get bulkCount() { return this.bulkSelected.length; }
}">

    {{-- Escalation Alert --}}
    @php $openUrgent = $allTickets->where('status','open')->where('priority','high')->count(); @endphp
    @if($openUrgent > 0)
    <div class="escalation-alert flex items-center gap-4 px-5 py-3.5 mb-5">
        <div class="flex items-center justify-center w-10 h-10 rounded-full bg-red-100 shrink-0">
            <x-core-ui::icon name="alert-triangle" class="w-5 h-5 text-red-600 animate-bounce" />
        </div>
        <div class="flex-1">
            <p class="text-sm font-bold text-red-700">{{ \App\Helpers\NumberHelper::formatFa($openUrgent) }} تیکت اورژانسی باز — نیاز به توجه فوری</p>
            <p class="text-xs text-red-600 mt-0.5">این تیکت‌ها اولویت بالا دارند و هنوز پاسخ نگرفته‌اند.</p>
        </div>
        <button @click="status='open'; priority='high'" class="text-xs font-semibold text-red-600 hover:text-red-700 underline underline-offset-2 shrink-0">مشاهده همه</button>
    </div>
    @endif

    {{-- ─── Stat Cards ─── --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">

        <div class="tkt-stat col-span-2 sm:col-span-1">
            <div class="tkt-stat__accent bg-gradient-to-b from-indigo-500 to-purple-500"></div>
            <div class="flex items-start gap-3">
                <div class="tkt-stat__icon bg-gradient-to-br from-indigo-50 to-purple-50 text-indigo-600">📫</div>
                <div>
                    <div class="tkt-stat__val text-gray-900">{{ \App\Helpers\NumberHelper::formatFa($stats['total']) }}</div>
                    <div class="tkt-stat__label text-gray-500">کل تیکت‌ها</div>
                </div>
            </div>
        </div>

        <div class="tkt-stat cursor-pointer" @click="status='open'; priority=''">
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
                    @if($stats['open'] > 0)
                    <span class="tkt-stat__trend bg-yellow-100 text-yellow-700">
                        <x-core-ui::icon name="arrow-up" class="w-3 h-3" />
                        نیاز به پاسخ
                    </span>
                    @endif
                </div>
            </div>
        </div>

        <div class="tkt-stat cursor-pointer" @click="status='in_progress'; priority=''">
            <div class="tkt-stat__accent bg-gradient-to-b from-blue-400 to-indigo-500"></div>
            <div class="flex items-start gap-3">
                <div class="tkt-stat__icon bg-blue-50 text-blue-600">
                    <x-core-ui::icon name="loader" class="w-6 h-6 animate-spin" style="animation-duration:3s" />
                </div>
                <div>
                    <div class="tkt-stat__val text-blue-700">{{ \App\Helpers\NumberHelper::formatFa($stats['in_progress']) }}</div>
                    <div class="tkt-stat__label text-blue-600">در حال بررسی</div>
                </div>
            </div>
        </div>

        <div class="tkt-stat cursor-pointer" @click="status='closed'; priority=''">
            <div class="tkt-stat__accent bg-gradient-to-b from-green-400 to-teal-500"></div>
            <div class="flex items-start gap-3">
                <div class="tkt-stat__icon bg-green-50 text-green-600">
                    <x-core-ui::icon name="check-circle" class="w-6 h-6" />
                </div>
                <div>
                    <div class="tkt-stat__val text-green-700">{{ \App\Helpers\NumberHelper::formatFa($stats['closed']) }}</div>
                    <div class="tkt-stat__label text-green-600">بسته شده</div>
                </div>
            </div>
        </div>

        <div class="tkt-stat">
            <div class="tkt-stat__accent bg-gradient-to-b from-teal-400 to-cyan-500"></div>
            <div class="flex items-start gap-3">
                <div class="tkt-stat__icon bg-teal-50 text-teal-600">
                    <x-core-ui::icon name="clock" class="w-6 h-6" />
                </div>
                <div>
                    <div class="tkt-stat__val text-teal-700 text-2xl">{{ \App\Helpers\NumberHelper::formatFa($stats['avg_response_hours']) }}h</div>
                    <div class="tkt-stat__label text-teal-600">میانگین پاسخ</div>
                    <span class="tkt-stat__trend bg-teal-50 text-teal-700">سرویس سالم</span>
                </div>
            </div>
        </div>

    </div>

    {{-- ─── Queue Health + Heatmap + Agent Workload ─── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">

        {{-- Queue Health --}}
        <div class="glass-card-strong p-5">
            <h3 class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2">
                <x-core-ui::icon name="filter" class="w-4 h-4 text-indigo-500" />
                سلامت صف
            </h3>
            @php
            $total   = max(1, $stats['total']);
            $openPct = round($stats['open']        / $total * 100);
            $inPct   = round($stats['in_progress'] / $total * 100);
            $clPct   = round($stats['closed']      / $total * 100);
            $qScore  = $openPct < 30 ? 'سالم' : ($openPct < 60 ? 'متوسط' : 'بحرانی');
            $qColor  = $openPct < 30 ? 'text-green-600' : ($openPct < 60 ? 'text-amber-600' : 'text-red-600');
            @endphp
            <div class="space-y-3 text-sm">
                <div>
                    <div class="flex justify-between mb-1.5">
                        <span class="text-gray-500 text-xs">باز</span>
                        <span class="font-bold text-yellow-600 text-xs">{{ \App\Helpers\NumberHelper::formatFa($openPct) }}%</span>
                    </div>
                    <div class="queue-health-bar"><div class="queue-health-fill bg-gradient-to-r from-yellow-400 to-orange-400" style="width:{{ $openPct }}%"></div></div>
                </div>
                <div>
                    <div class="flex justify-between mb-1.5">
                        <span class="text-gray-500 text-xs">در حال بررسی</span>
                        <span class="font-bold text-blue-600 text-xs">{{ \App\Helpers\NumberHelper::formatFa($inPct) }}%</span>
                    </div>
                    <div class="queue-health-bar"><div class="queue-health-fill bg-gradient-to-r from-blue-400 to-indigo-500" style="width:{{ $inPct }}%"></div></div>
                </div>
                <div>
                    <div class="flex justify-between mb-1.5">
                        <span class="text-gray-500 text-xs">بسته</span>
                        <span class="font-bold text-green-600 text-xs">{{ \App\Helpers\NumberHelper::formatFa($clPct) }}%</span>
                    </div>
                    <div class="queue-health-bar"><div class="queue-health-fill bg-gradient-to-r from-green-400 to-teal-500" style="width:{{ $clPct }}%"></div></div>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between">
                <span class="text-xs text-gray-500">وضعیت کلی صف</span>
                <span class="text-xs font-bold {{ $qColor }}">{{ $qScore }}</span>
            </div>
        </div>

        {{-- Priority Heatmap --}}
        <div class="glass-card-strong p-5">
            <h3 class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2">
                <x-core-ui::icon name="flag" class="w-4 h-4 text-red-500" />
                نقشه حرارتی اولویت
            </h3>
            @php
            $highOpen   = $allTickets->where('priority','high')->where('status','open')->count();
            $highInProg = $allTickets->where('priority','high')->where('status','in_progress')->count();
            $normOpen   = $allTickets->where('priority','normal')->where('status','open')->count();
            $normInProg = $allTickets->where('priority','normal')->where('status','in_progress')->count();
            $lowOpen    = $allTickets->where('priority','low')->where('status','open')->count();
            $lowInProg  = $allTickets->where('priority','low')->where('status','in_progress')->count();
            $heatMax    = max(1, $highOpen, $highInProg, $normOpen, $normInProg, $lowOpen, $lowInProg);
            $heatCls = fn($v, $max, $base) => $v === 0 ? 'bg-gray-100 text-gray-400' : ($v/$max < .33 ? "bg-{$base}-100 text-{$base}-700" : ($v/$max < .66 ? "bg-{$base}-200 text-{$base}-800" : "bg-{$base}-400 text-white"));
            @endphp
            <table class="w-full text-xs text-center">
                <thead><tr class="text-gray-400 font-semibold">
                    <th class="py-1 text-right pr-1">اولویت</th>
                    <th class="py-1 px-2">باز</th>
                    <th class="py-1 px-2">در جریان</th>
                </tr></thead>
                <tbody>
                    <tr>
                        <td class="text-right text-red-600 font-bold py-1.5 pr-1">بالا</td>
                        <td class="py-1.5 px-2"><div class="heatmap-cell h-10 flex items-center justify-center font-bold {{ $heatCls($highOpen, $heatMax, 'red') }}">{{ \App\Helpers\NumberHelper::formatFa($highOpen) }}</div></td>
                        <td class="py-1.5 px-2"><div class="heatmap-cell h-10 flex items-center justify-center font-bold {{ $heatCls($highInProg, $heatMax, 'red') }}">{{ \App\Helpers\NumberHelper::formatFa($highInProg) }}</div></td>
                    </tr>
                    <tr>
                        <td class="text-right text-blue-600 font-bold py-1.5 pr-1">عادی</td>
                        <td class="py-1.5 px-2"><div class="heatmap-cell h-10 flex items-center justify-center font-bold {{ $heatCls($normOpen, $heatMax, 'blue') }}">{{ \App\Helpers\NumberHelper::formatFa($normOpen) }}</div></td>
                        <td class="py-1.5 px-2"><div class="heatmap-cell h-10 flex items-center justify-center font-bold {{ $heatCls($normInProg, $heatMax, 'blue') }}">{{ \App\Helpers\NumberHelper::formatFa($normInProg) }}</div></td>
                    </tr>
                    <tr>
                        <td class="text-right text-gray-500 font-bold py-1.5 pr-1">کم</td>
                        <td class="py-1.5 px-2"><div class="heatmap-cell h-10 flex items-center justify-center font-bold {{ $heatCls($lowOpen, $heatMax, 'gray') }}">{{ \App\Helpers\NumberHelper::formatFa($lowOpen) }}</div></td>
                        <td class="py-1.5 px-2"><div class="heatmap-cell h-10 flex items-center justify-center font-bold {{ $heatCls($lowInProg, $heatMax, 'gray') }}">{{ \App\Helpers\NumberHelper::formatFa($lowInProg) }}</div></td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Agent Workload --}}
        <div class="glass-card-strong p-5">
            <h3 class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2">
                <x-core-ui::icon name="users" class="w-4 h-4 text-purple-500" />
                بار کاری کارشناسان
            </h3>
            @php
            $agentWorkloads = $allTickets->whereNotNull('assigned_to')->groupBy('assigned_to');
            $maxAgt = max(1, $agentWorkloads->map->count()->max() ?? 1);
            @endphp
            @if($agentWorkloads->isEmpty())
            <div class="text-center py-4 text-gray-400">
                <x-core-ui::icon name="user" class="w-8 h-8 mx-auto mb-2 opacity-40" />
                <p class="text-xs">تیکتی تخصیص نیافته</p>
            </div>
            @else
            <div class="space-y-3">
                @foreach($agentWorkloads->take(4) as $agentId => $agentTickets)
                @php
                $agentName  = $agentTickets->first()?->assignee?->name ?? ('کارشناس ' . $agentId);
                $agentCount = $agentTickets->count();
                $agentPct   = round($agentCount / $maxAgt * 100);
                $barColor   = $agentPct > 75 ? 'bg-red-500' : ($agentPct > 50 ? 'bg-amber-400' : 'bg-indigo-500');
                @endphp
                <div class="agent-card">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-white text-xs font-bold shrink-0">{{ mb_substr($agentName,0,1) }}</div>
                            <span class="text-xs font-semibold text-gray-700 truncate max-w-[100px]">{{ $agentName }}</span>
                        </div>
                        <span class="text-xs font-bold {{ $agentPct > 75 ? 'text-red-600' : 'text-gray-600' }}">{{ \App\Helpers\NumberHelper::formatFa($agentCount) }}</span>
                    </div>
                    <div class="agent-workload mt-2">
                        <div class="{{ $barColor }} agent-workload-bar" style="width:{{ $agentPct }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

    </div>

    {{-- ─── Actions Bar ─── --}}
    <div class="flex items-center justify-between mb-4 gap-3 flex-wrap">
        <a href="{{ route('admin.user-tickets.create') }}" class="btn-gradient">
            <x-core-ui::icon name="plus" class="w-4 h-4 shrink-0" />
            تیکت جدید
        </a>
        <span class="text-xs text-gray-400">مجموع {{ \App\Helpers\NumberHelper::formatFa($allTickets->count()) }} تیکت ثبت شده</span>
    </div>

    {{-- ─── Bulk Toolbar ─── --}}
    <div x-show="bulkCount > 0" x-transition
         class="flex items-center gap-3 bg-indigo-50 border border-indigo-200 rounded-2xl px-5 py-3.5 mb-4">
        <span class="text-sm font-semibold text-indigo-800" x-text="bulkCount + ' تیکت انتخاب شده'"></span>
        <form method="POST" action="{{ route('admin.user-tickets.bulk') }}" class="flex items-center gap-2" id="bulk-form">
            @csrf
            <template x-for="id in bulkSelected" :key="id">
                <input type="hidden" name="ids[]" :value="id">
            </template>
            <button type="submit" name="action" value="close"
                    class="px-3.5 py-2 rounded-xl text-xs font-semibold text-gray-700 bg-white hover:bg-gray-50 border border-gray-200 transition-all">
                بستن
            </button>
            <button type="submit" name="action" value="reopen"
                    class="px-3.5 py-2 rounded-xl text-xs font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 transition-all">
                بازگشایی
            </button>
            <button type="submit" name="action" value="delete"
                    onclick="return confirm('آیا از حذف تیکت‌های انتخاب‌شده اطمینان دارید؟ این عمل قابل بازگشت نیست.')"
                    class="px-3.5 py-2 rounded-xl text-xs font-semibold text-red-700 bg-red-50 hover:bg-red-100 border border-red-200 transition-all">
                حذف انتخاب‌شده
            </button>
        </form>
        <button @click="bulkSelected = []" class="mr-auto text-xs text-gray-400 hover:text-gray-600 transition-colors">
            لغو انتخاب
        </button>
    </div>

    {{-- ─── Filter Bar ─── --}}
    <div class="glass-filter-bar mb-5">
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 flex-wrap">
            {{-- جستجوی متنی --}}
            <div class="relative flex-1 min-w-0 w-full sm:w-auto">
                <x-core-ui::icon name="search" class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" />
                <input type="text" x-model="search" placeholder="جستجوی موضوع، نام کاربر یا شماره تیکت..."
                       class="w-full pr-9 pl-4 py-2.5 rounded-xl border border-gray-200 bg-white/80 text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 transition-all">
            </div>
            {{-- فیلتر وضعیت --}}
            <select x-model="status" style="max-width:10rem" class="px-4 py-2.5 rounded-xl border border-gray-200 bg-white/80 text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 transition-all w-full sm:w-auto">
                <option value="">همه وضعیت‌ها</option>
                <option value="open">باز</option>
                <option value="in_progress">در حال بررسی</option>
                <option value="closed">بسته</option>
            </select>
            {{-- فیلتر اولویت --}}
            <select x-model="priority" style="max-width:10rem" class="px-4 py-2.5 rounded-xl border border-gray-200 bg-white/80 text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 transition-all w-full sm:w-auto">
                <option value="">همه اولویت‌ها</option>
                <option value="high">🔴 بالا</option>
                <option value="normal">🔵 عادی</option>
                <option value="low">⚪ کم</option>
            </select>
            {{-- فیلتر کاربر خاص (server-side) --}}
            <form method="GET" action="{{ route('admin.user-tickets.index') }}" class="flex items-center gap-2 w-full sm:w-auto">
                @foreach(request()->except('user_id', 'page') as $k => $v)
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endforeach
                <select name="user_id" onchange="this.form.submit()"
                        style="max-width:14rem"
                        class="px-4 py-2.5 rounded-xl border {{ request('user_id') ? 'border-indigo-400 bg-indigo-50' : 'border-gray-200 bg-white/80' }} text-sm focus:ring-2 focus:ring-indigo-200 transition-all w-full sm:w-auto">
                    <option value="">همه کاربران</option>
                    @foreach($regularUsers as $ru)
                    <option value="{{ $ru->id }}" @selected(request('user_id') == $ru->id)>
                        {{ $ru->name ?: $ru->mobile }} — {{ $ru->mobile }}
                    </option>
                    @endforeach
                </select>
            </form>
            {{-- پاک کردن --}}
            <button @click="search=''; status=''; priority=''"
                    class="flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl border border-gray-200 bg-white/80 text-sm text-gray-500 hover:bg-gray-50 transition-all w-full sm:w-auto justify-center">
                <x-core-ui::icon name="close" class="w-4 h-4" />
                پاک کردن
            </button>
        </div>
    </div>

    {{-- ─── Table ─── --}}
    <div class="glass-card-strong overflow-hidden">
    <div class="overflow-x-auto">
    <table class="w-full text-right border-collapse" style="min-width:680px">
        <thead>
            <tr class="bg-gradient-to-r from-gray-50 to-white border-b border-gray-100">
                <th class="p-3 pr-5 w-8">
                    <input type="checkbox" class="rounded text-indigo-600"
                           @change="toggleAll($event.target.checked)">
                </th>
                <th class="p-3 text-xs font-bold text-gray-500 uppercase tracking-wider">شماره</th>
                <th class="p-3 text-xs font-bold text-gray-500 uppercase tracking-wider">موضوع / کاربر</th>
                <th class="p-3 text-xs font-bold text-gray-500 uppercase tracking-wider">وضعیت</th>
                <th class="p-3 text-xs font-bold text-gray-500 uppercase tracking-wider">اولویت</th>
                <th class="p-3 text-xs font-bold text-gray-500 uppercase tracking-wider">مسئول</th>
                <th class="p-3 text-xs font-bold text-gray-500 uppercase tracking-wider">تاریخ</th>
                <th class="p-3 pl-5 text-xs font-bold text-gray-500 uppercase tracking-wider text-left">عملیات</th>
            </tr>
        </thead>
        <tbody>
        @forelse($tickets as $ticket)
        @php
            $p    = $ticket->priority ?? 'normal';
            $pBg  = $p === 'high'  ? 'bg-red-50 text-red-700 border-red-200'  : ($p === 'low' ? 'bg-gray-50 text-gray-500 border-gray-200' : 'bg-blue-50 text-blue-600 border-blue-200');
            $pDot = $p === 'high'  ? 'bg-red-500' : ($p === 'low' ? 'bg-gray-400' : 'bg-blue-500');
            $pLbl = $p === 'high'  ? 'بالا' : ($p === 'low' ? 'کم' : 'عادی');
            $needsReply = $ticket->status !== 'closed'
                && ($lastReplyAuthors ?? collect())->get($ticket->id) === 'customer';
        @endphp
        <tr class="ticket-row border-b border-gray-50 hover:bg-indigo-50/30 transition-colors group {{ $needsReply ? 'ticket-awaits-reply' : '' }}"
            style="{{ $needsReply ? 'border-right:3px solid #6366f1' : '' }}"
            data-subject="{{ $ticket->subject }}"
            data-ticket-no="{{ $ticket->ticket_number }}"
            data-customer="{{ $ticket->customer_name }}"
            data-status="{{ $ticket->status }}"
            data-priority="{{ $p }}"
            x-show="
                (!search || $el.dataset.subject?.toLowerCase().includes(search.toLowerCase()) || $el.dataset.ticketNo?.includes(search) || $el.dataset.customer?.toLowerCase().includes(search.toLowerCase())) &&
                (!status   || $el.dataset.status   === status) &&
                (!priority || $el.dataset.priority === priority)
            "
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100">

            <td class="p-3 pr-5">
                <input type="checkbox" class="ticket-check rounded text-indigo-600"
                       value="{{ $ticket->id }}"
                       x-model="bulkSelected">
            </td>
            <td class="p-3">
                <span class="font-mono font-bold text-indigo-700 text-xs bg-indigo-50 px-2 py-1 rounded-lg border border-indigo-100">{{ $ticket->ticket_number }}</span>
            </td>

            <td class="p-3">
                <a href="{{ route('admin.user-tickets.show', $ticket->id) }}" class="group/link">
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="text-sm font-semibold text-gray-800 group-hover/link:text-indigo-700 transition-colors">{{ Str::limit($ticket->subject, 42) }}</span>
                        @if($needsReply)
                        <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-indigo-100 text-indigo-700 border border-indigo-200 awaits-reply-badge shrink-0">
                            <x-core-ui::icon name="arrow-right" class="w-2.5 h-2.5" />
                            پیام کاربر — پاسخ دهید
                        </span>
                        @endif
                    </div>
                    @if($ticket->category)
                    <span class="ml-2 text-[10px] text-gray-400 bg-gray-100 px-1.5 py-0.5 rounded">{{ $ticket->category }}</span>
                    @endif
                </a>
                @if($ticket->customer_name)
                <div class="text-xs text-gray-400 mt-0.5 flex items-center gap-1">
                    <x-core-ui::icon name="user" class="w-3 h-3" />
                    {{ $ticket->customer_name }}
                </div>
                @endif
            </td>

            <td class="p-3">
                @if($ticket->status === 'open')
                    <span class="tkt-badge bg-yellow-50 text-yellow-700 border-yellow-200"><span class="pdot bg-yellow-500"></span>باز</span>
                @elseif($ticket->status === 'in_progress')
                    <span class="tkt-badge bg-blue-50 text-blue-700 border-blue-200"><span class="pdot bg-blue-500"></span>در بررسی</span>
                @else
                    <span class="tkt-badge bg-green-50 text-green-700 border-green-200"><x-core-ui::icon name="check" class="w-3 h-3" />بسته</span>
                @endif
            </td>

            <td class="p-3">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border {{ $pBg }}">
                    <span class="pdot {{ $pDot }}"></span>{{ $pLbl }}
                </span>
            </td>

            <td class="p-3">
                @if($ticket->assignee)
                <div class="flex items-center gap-1.5">
                    <div class="w-6 h-6 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-white text-[10px] font-bold shrink-0">{{ mb_substr($ticket->assignee->name,0,1) }}</div>
                    <span class="text-xs text-gray-700 truncate max-w-[80px]">{{ $ticket->assignee->name }}</span>
                </div>
                @else
                <span class="text-xs text-gray-300 italic">تخصیص نیافته</span>
                @endif
            </td>

            <td class="p-3 text-xs text-gray-500 whitespace-nowrap">
                {{ \App\Helpers\Jalali::formatFa($ticket->created_at, 'Y/m/d') }}
                <div class="text-[10px] text-gray-400">{{ \App\Helpers\Jalali::formatFa($ticket->created_at, 'H:i') }}</div>
            </td>

            <td class="p-3 pl-5">
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.user-tickets.show', $ticket->id) }}"
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 transition-all hover:shadow-sm whitespace-nowrap">
                        <x-core-ui::icon name="eye" class="w-3.5 h-3.5" />
                        مشاهده
                    </a>
                    <form method="POST" action="{{ route('admin.user-tickets.destroy', $ticket->id) }}"
                          x-data
                          @submit.prevent="if(confirm('آیا از حذف تیکت {{ $ticket->ticket_number }} اطمینان دارید؟ این عمل قابل بازگشت نیست.')) $el.submit()">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="inline-flex items-center gap-1 px-2.5 py-2 rounded-xl text-xs font-semibold text-red-600 bg-red-50 hover:bg-red-100 border border-red-200 transition-all hover:shadow-sm whitespace-nowrap">
                            <x-core-ui::icon name="trash" class="w-3.5 h-3.5" />
                            حذف
                        </button>
                    </form>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="8" class="text-center py-16">
                <div class="flex flex-col items-center gap-3">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-50 to-purple-50 flex items-center justify-center text-3xl shadow-sm">📭</div>
                    <p class="text-gray-600 font-semibold">هیچ تیکتی یافت نشد</p>
                    <p class="text-gray-400 text-sm">فیلترها را تغییر دهید یا تیکت جدید ثبت کنید.</p>
                    <a href="{{ route('admin.user-tickets.create') }}" class="btn-gradient !text-sm !py-2 !px-5 mt-2">
                        <x-core-ui::icon name="plus" class="w-4 h-4" />
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

    {{-- Pagination --}}
    @if($tickets->hasPages())
    <div class="mt-6 flex justify-center">{{ $tickets->withQueryString()->links() }}</div>
    @endif

    {{-- ─── Feature Pills ─── --}}
    <div class="section-divider"><hr><span>قابلیت‌های سیستم پشتیبانی</span><hr></div>
    @php
    $features = [
        ['icon'=>'💬','label'=>'چت آنی','desc'=>'رابط چت با حباب‌های رنگی و اسکرول خودکار'],
        ['icon'=>'📱','label'=>'اعلان پیامکی','desc'=>'SMS خودکار برای رویدادهای تیکت'],
        ['icon'=>'👤','label'=>'تخصیص کارشناس','desc'=>'مدیریت کامل تخصیص به کارشناسان'],
        ['icon'=>'📝','label'=>'یادداشت داخلی','desc'=>'یادداشت‌های محرمانه برای کارشناسان'],
        ['icon'=>'⚡','label'=>'به‌روزرسانی آنی','desc'=>'پولینگ هر ۴ ثانیه برای پیام‌های جدید'],
        ['icon'=>'📊','label'=>'داشبورد تحلیلی','desc'=>'آمار، نقشه حرارتی و بار کاری'],
        ['icon'=>'🔔','label'=>'اعلان مرورگر','desc'=>'نوتیفیکیشن دسکتاپ برای پیام‌های جدید'],
        ['icon'=>'🔒','label'=>'ایزوله از سایت‌ها','desc'=>'تیکت‌های داخلی کاملاً جدا از سایت‌ها'],
    ];
    @endphp
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3 mt-4">
        @foreach($features as $feat)
        <div class="feature-pill">
            <div class="text-2xl mb-2">{{ $feat['icon'] }}</div>
            <p class="text-xs font-bold text-gray-800">{{ $feat['label'] }}</p>
            <p class="text-[10px] text-gray-400 mt-0.5 leading-relaxed">{{ $feat['desc'] }}</p>
        </div>
        @endforeach
    </div>

</div>
@endsection
