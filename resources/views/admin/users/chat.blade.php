@extends('admin.layout')
@section('title', 'چت با ' . $user->name)

@push('head')
@include('partials.ticket-styles')
<style>
.chat-panel {
    display: flex;
    gap: 1rem;
    height: calc(100vh - 10rem);
    min-height: 560px;
}
.chat-sidebar {
    width: 280px;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    gap: .75rem;
    overflow-y: auto;
}
.chat-main {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 1rem;
    overflow: hidden;
}
.convo-item {
    display: flex;
    align-items: flex-start;
    gap: .625rem;
    padding: .625rem .75rem;
    border-radius: .75rem;
    cursor: pointer;
    transition: all .15s;
    border: 1px solid transparent;
    text-decoration: none;
}
.convo-item:hover { background: #f5f3ff; border-color: #e0e7ff; }
.convo-item.active { background: #eef2ff; border-color: #c7d2fe; }
.convo-dot { width: .5rem; height: .5rem; border-radius: 50%; flex-shrink: 0; margin-top: .3rem; }
@media (max-width: 768px) {
    .chat-panel { flex-direction: column; height: auto; }
    .chat-sidebar { width: 100%; max-height: 260px; }
    .chat-main { min-height: 400px; }
}
</style>
@endpush

@section('content')

{{-- Breadcrumb --}}
<div class="flex items-center gap-2 text-sm mb-4">
    <a href="{{ route('admin.users.index') }}" class="text-slate-500 hover:text-indigo-600 transition-colors flex items-center gap-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        پنل کاربران
    </a>
    <span class="text-slate-300">/</span>
    <span class="text-slate-700 font-medium">{{ $user->name }}</span>
</div>

{{-- Flash --}}
@if(session('success'))
<div class="flex items-center gap-3 bg-green-50 border border-green-200 rounded-2xl px-5 py-3 mb-4"
     x-data="{ show: true }" x-show="show" x-transition.duration.300ms>
    <svg class="w-4 h-4 text-green-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <span class="text-sm text-green-700">{{ session('success') }}</span>
    <button @click="show=false" class="mr-auto text-green-400"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
</div>
@endif

@php
    $avatarColors = ['#4f46e5','#0891b2','#059669','#d97706','#dc2626','#7c3aed','#db2777'];
    $uColor = $avatarColors[crc32($user->name ?? $user->email ?? '') % count($avatarColors)];
    $uInitial = mb_strtoupper(mb_substr($user->name ?? $user->email ?? '؟', 0, 1));
@endphp

{{-- Main Layout --}}
<div class="chat-panel"
     @if($ticket)
     data-poll-url="{{ route('admin.user-tickets.poll', $ticket->id) }}"
     data-poll-last-id="{{ $ticket->replies->last()?->id ?? 0 }}"
     @endif
     x-data="{
        noteMode: false,
        message: '',
        noteText: '',
        isSending: false,
        showScrollBtn: false,
        pollLastId: 0,
        seenIds: {},
        pollTimer: null,
        showToast: false,
        toastText: '',
        hasTicket: {{ $ticket ? 'true' : 'false' }},
        scrollDown() {
            let el = this.$refs.chatMessages;
            if(el) { el.scrollTop = el.scrollHeight; this.showScrollBtn = false; }
        },
        handleScroll() {
            let el = this.$refs.chatMessages;
            if(!el) return;
            this.showScrollBtn = el.scrollHeight - el.scrollTop - el.clientHeight > 80;
        },
        init() {
            if (!this.hasTicket) return;
            this.$nextTick(() => this.scrollDown());
            this.pollLastId = parseInt((this.$el.dataset.pollLastId || '0'), 10);
            this.pollTimer = setInterval(() => this.doPoll(), 4000);
            window.addEventListener('beforeunload', () => clearInterval(this.pollTimer));
        },
        async doPoll() {
            const url = this.$el.dataset.pollUrl;
            if (!url) return;
            try {
                const res = await fetch(url + '?last_id=' + this.pollLastId, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' }
                });
                if (!res.ok) return;
                const data = await res.json();
                if (data.replies && data.replies.length > 0) {
                    data.replies.forEach(r => this.appendReply(r));
                    const hasCustomer = data.replies.some(r => r.author_type === 'customer');
                    if (window.CS && hasCustomer) window.CS.play('receive');
                }
                if (data.last_id && data.last_id > this.pollLastId) this.pollLastId = data.last_id;
            } catch(e) {}
        },
        async sendReply(form) {
            if (this.isSending) return;
            const text = this.noteMode ? this.noteText.trim() : this.message.trim();
            if (!text) return;
            this.isSending = true;
            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
                    body: new FormData(form)
                });
                if (!res.ok) { const e = await res.json().catch(()=>({})); alert(e.error || e.message || 'خطا در ارسال'); return; }
                const data = await res.json();
                if (data.reply) {
                    this.appendReply(data.reply);
                    if (data.reply.id > this.pollLastId) this.pollLastId = data.reply.id;
                    if (window.CS && !data.reply.is_internal) window.CS.play('send');
                }
                if (this.noteMode) { this.noteText = ''; } else { this.message = ''; }
            } catch(e) { console.error(e); }
            finally { this.isSending = false; }
        },
        appendReply(r) {
            if (r.id && this.seenIds[r.id]) return;
            if (r.id) this.seenIds[r.id] = true;
            const container = this.$refs.chatMessages;
            if (!container) return;
            const isStaff    = r.author_type === 'staff';
            const isInternal = !!r.is_internal;
            const safeName = r.author_name || (isStaff ? 'پشتیبان' : 'کاربر');
            const initial = safeName.charAt(0);
            const d = new Date(r.created_at);
            const time = d.toLocaleTimeString('fa-IR', {hour:'2-digit', minute:'2-digit'});
            const bubbleCls = isInternal ? 'bubble-internal' : (isStaff ? 'bubble-staff' : 'bubble-customer');
            const color = isInternal ? 'var(--ui-warning)' : (isStaff ? 'var(--ui-success)' : 'var(--ui-indigo)');
            const wrapper = document.createElement('div');
            wrapper.className = 'flex items-end gap-2 max-w-[80%] mb-4' + ((isStaff||isInternal) ? ' flex-row-reverse' : '');
            wrapper.style.cssText = 'align-self:' + ((isStaff||isInternal) ? 'flex-end' : 'flex-start') + ';opacity:0;transform:translateY(8px)';
            const av = document.createElement('div');
            av.className = 'w-7 h-7 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0 shadow-sm';
            av.style.backgroundColor = color;
            av.textContent = initial;
            const bw = document.createElement('div');
            const bl = document.createElement('div');
            bl.className = bubbleCls + ' px-3.5 py-2 shadow-sm';
            const np = document.createElement('p');
            np.className = 'text-xs font-bold mb-0.5 opacity-70';
            np.textContent = safeName;
            if (isInternal) {
                const sp = document.createElement('span');
                sp.className = 'text-[9px] bg-amber-200/60 px-1.5 py-0.5 rounded-full mr-1';
                sp.textContent = 'یادداشت';
                np.appendChild(sp);
            } else if (isStaff) {
                const sp = document.createElement('span');
                sp.className = 'text-[9px] bg-green-200/60 px-1.5 py-0.5 rounded-full mr-1';
                sp.textContent = 'پشتیبان';
                np.appendChild(sp);
            }
            const mp = document.createElement('p');
            mp.className = 'text-sm leading-relaxed whitespace-pre-wrap';
            mp.textContent = r.message || '';
            bl.appendChild(np); bl.appendChild(mp);
            const ts = document.createElement('span');
            ts.className = 'text-[10px] text-gray-400 mt-0.5 px-1 block';
            ts.textContent = time;
            bw.appendChild(bl); bw.appendChild(ts);
            wrapper.appendChild(av); wrapper.appendChild(bw);
            container.appendChild(wrapper);
            requestAnimationFrame(() => { wrapper.style.transition = 'opacity .3s,transform .3s'; wrapper.style.opacity = '1'; wrapper.style.transform = 'translateY(0)'; });
            container.scrollTop = container.scrollHeight;
            if (r.author_type === 'customer') {
                this.toastText = 'پیام جدید از ' + safeName;
                this.showToast = true;
                clearTimeout(this._toastTimer);
                this._toastTimer = setTimeout(() => { this.showToast = false; }, 5000);
            }
        }
     }">

    {{-- ── LEFT SIDEBAR ── --}}
    <div class="chat-sidebar">

        {{-- User Profile Card --}}
        <div class="glass-card-strong p-4">
            <div class="flex items-center gap-3 mb-3">
                @if($user->avatar)
                <img src="{{ $user->avatar_url }}" class="w-12 h-12 rounded-full object-cover ring-2 ring-indigo-100" alt="{{ $user->name }}">
                @else
                <div class="w-12 h-12 rounded-full flex items-center justify-center text-white text-lg font-bold ring-2 ring-indigo-100" style="background:{{ $uColor }}">{{ $uInitial }}</div>
                @endif
                <div class="flex-1 min-w-0">
                    <h2 class="font-bold text-sm text-gray-900 truncate">{{ $user->name }}</h2>
                    <p class="text-xs text-gray-500 truncate">{{ $user->email }}</p>
                </div>
            </div>
            @if($user->mobile)
            <div class="flex items-center gap-2 text-xs text-gray-600 mb-1.5">
                <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                {{ $user->mobile }}
            </div>
            @endif
            <div class="flex items-center gap-2 text-xs text-gray-400">
                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                عضویت {{ $user->created_at->diffForHumans() }}
            </div>
        </div>

        {{-- Start New Chat --}}
        <form action="{{ route('admin.users.startChat', $user->id) }}" method="POST">
            @csrf
            <button type="submit" class="w-full btn-gradient text-xs justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                گفتگوی جدید
            </button>
        </form>

        {{-- Conversations List --}}
        <div class="glass-card-strong p-3 flex-1 overflow-y-auto">
            <div class="text-xs font-bold text-gray-500 mb-2 px-1 flex items-center gap-2">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/></svg>
                مکالمات ({{ $allTickets->count() }})
            </div>
            @forelse($allTickets as $t)
            @php
                $isActive = $ticket && $ticket->id === $t->id;
                $dotColor = match($t->status) {
                    'open'        => '#10b981',
                    'in_progress' => '#f59e0b',
                    'closed'      => '#9ca3af',
                    default       => '#9ca3af',
                };
                $statusLabel = match($t->status) {
                    'open'        => 'باز',
                    'in_progress' => 'در حال بررسی',
                    'closed'      => 'بسته',
                    default       => $t->status,
                };
            @endphp
            <a href="{{ route('admin.users.chat', ['id' => $user->id, 'ticket' => $t->id]) }}"
               class="convo-item {{ $isActive ? 'active' : '' }}">
                <span class="convo-dot mt-1.5" style="background:{{ $dotColor }}"></span>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold text-gray-800 truncate leading-tight">{{ $t->subject }}</p>
                    <p class="text-[10px] text-gray-400 mt-0.5 flex items-center gap-1.5">
                        <span>{{ $t->ticket_number }}</span>
                        <span class="text-gray-300">•</span>
                        <span>{{ $statusLabel }}</span>
                    </p>
                    <p class="text-[10px] text-gray-300 mt-0.5">{{ $t->updated_at->diffForHumans() }}</p>
                </div>
            </a>
            @empty
            <p class="text-center text-xs text-gray-400 py-4">هیچ مکالمه‌ای وجود ندارد</p>
            @endforelse
        </div>
    </div>

    {{-- ── RIGHT CHAT MAIN ── --}}
    <div class="chat-main">
        @if($ticket)

        {{-- Chat Header --}}
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 bg-gray-50/60 shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-bold shrink-0" style="background:{{ $uColor }}">{{ $uInitial }}</div>
                <div>
                    <p class="text-sm font-semibold text-gray-900">{{ $user->name }}</p>
                    <p class="text-xs text-gray-400">{{ $ticket->ticket_number }} · {{ $ticket->subject }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                {{-- Status badge --}}
                @php
                    $sBadge = match($ticket->status) {
                        'open'        => ['text-emerald-700','bg-emerald-50','border-emerald-200','#10b981','باز'],
                        'in_progress' => ['text-amber-700','bg-amber-50','border-amber-200','#f59e0b','در حال بررسی'],
                        'closed'      => ['text-gray-500','bg-gray-50','border-gray-200','#9ca3af','بسته شده'],
                        default       => ['text-gray-500','bg-gray-50','border-gray-200','#9ca3af',$ticket->status],
                    };
                @endphp
                <span class="tkt-badge {{ $sBadge[0] }} {{ $sBadge[1] }} border-{{ $sBadge[2] }}"
                      style="border-color:{{ $sBadge[2] }}; background:{{ $sBadge[1] }}; color:{{ $sBadge[0] }}">
                    <span class="pdot" style="background:{{ $sBadge[3] }}"></span>
                    {{ $sBadge[4] }}
                </span>

                <a href="{{ route('admin.user-tickets.show', $ticket->id) }}"
                   class="btn-ghost text-xs py-1.5 px-3" title="مشاهده در پنل تیکت‌ها">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>

                {{-- Quick status change --}}
                <form action="{{ route('admin.user-tickets.status', $ticket->id) }}" method="POST" class="inline">
                    @csrf @method('PATCH')
                    @if($ticket->status !== 'closed')
                    <button type="submit" name="status" value="closed"
                            onclick="return confirm('این مکالمه بسته شود؟')"
                            class="btn-ghost text-xs py-1.5 px-3 text-red-600 hover:bg-red-50">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        بستن
                    </button>
                    @else
                    <button type="submit" name="status" value="open"
                            class="btn-ghost text-xs py-1.5 px-3 text-emerald-600 hover:bg-emerald-50">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        بازگشایی
                    </button>
                    @endif
                </form>
            </div>
        </div>

        {{-- Toast --}}
        <div x-show="showToast" x-transition
             class="mx-4 mt-3 flex items-center gap-2 bg-indigo-600 text-white px-4 py-2.5 rounded-xl text-xs font-medium shadow-lg"
             style="display:none">
            <svg class="w-4 h-4 shrink-0 animate-bounce" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            <span x-text="toastText"></span>
            <button @click="showToast=false" class="mr-auto opacity-70 hover:opacity-100"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>

        {{-- Messages Area --}}
        <div class="flex-1 overflow-y-auto p-4 chat-bg flex flex-col gap-1 relative"
             x-ref="chatMessages"
             @scroll="handleScroll()">

            {{-- Description (first message) --}}
            @if($ticket->description)
            <div class="flex items-end gap-2 max-w-[80%] mb-4" style="align-self:flex-start">
                <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0" style="background:{{ $uColor }}">{{ $uInitial }}</div>
                <div>
                    <div class="bubble-customer px-3.5 py-2 shadow-sm">
                        <p class="text-xs font-bold mb-0.5 opacity-70">{{ $ticket->customer_name ?? $user->name }}</p>
                        <p class="text-sm leading-relaxed whitespace-pre-wrap">{{ $ticket->description }}</p>
                    </div>
                    <span class="text-[10px] text-gray-400 mt-0.5 px-1 block">{{ $ticket->created_at->format('H:i') }}</span>
                </div>
            </div>
            @endif

            {{-- Replies --}}
            @foreach($ticket->replies as $reply)
            @php
                $rIsStaff    = $reply->author_type === 'staff';
                $rIsInternal = (bool)$reply->is_internal;
                $rName = $rIsStaff ? ($reply->user?->name ?? 'پشتیبان') : ($ticket->customer_name ?? $user->name);
                $rInitial = mb_strtoupper(mb_substr($rName, 0, 1));
                $rColor = $rIsInternal ? 'var(--ui-warning)' : ($rIsStaff ? 'var(--ui-success)' : $uColor);
                $rBubble = $rIsInternal ? 'bubble-internal' : ($rIsStaff ? 'bubble-staff' : 'bubble-customer');
                $rAlign = ($rIsStaff || $rIsInternal) ? 'flex-end' : 'flex-start';
                $rReverse = ($rIsStaff || $rIsInternal) ? 'flex-row-reverse' : '';
            @endphp
            <div class="flex items-end gap-2 max-w-[80%] mb-4 {{ $rReverse }}" style="align-self:{{ $rAlign }}">
                <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0 shadow-sm"
                     style="background:{{ $rColor }}">{{ $rInitial }}</div>
                <div>
                    <div class="{{ $rBubble }} px-3.5 py-2 shadow-sm">
                        <p class="text-xs font-bold mb-0.5 opacity-70">
                            {{ $rName }}
                            @if($rIsInternal)
                            <span class="text-[9px] bg-amber-200/60 px-1.5 py-0.5 rounded-full mr-1">یادداشت</span>
                            @elseif($rIsStaff)
                            <span class="text-[9px] bg-green-200/60 px-1.5 py-0.5 rounded-full mr-1">پشتیبان</span>
                            @endif
                        </p>
                        <p class="text-sm leading-relaxed whitespace-pre-wrap">{{ $reply->message }}</p>
                    </div>
                    <span class="text-[10px] text-gray-400 mt-0.5 px-1 block">{{ $reply->created_at->format('H:i') }}</span>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Scroll to bottom btn --}}
        <div x-show="showScrollBtn" x-transition
             class="absolute bottom-20 left-1/2 -translate-x-1/2 z-10"
             style="display:none; position:sticky; bottom:5rem">
            <button @click="scrollDown()"
                    class="flex items-center gap-1.5 bg-indigo-600 text-white text-xs font-medium px-3 py-1.5 rounded-full shadow-lg hover:bg-indigo-700 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                پیام جدید
            </button>
        </div>

        {{-- Reply Form --}}
        @if($ticket->status !== 'closed')
        <div class="border-t border-gray-100 p-3 bg-white shrink-0">
            {{-- Mode Toggle --}}
            <div class="flex items-center gap-2 mb-2">
                <button type="button" @click="noteMode=false"
                        :class="!noteMode ? 'bg-indigo-100 text-indigo-700 border-indigo-200' : 'text-gray-500 hover:bg-gray-100'"
                        class="text-xs font-semibold px-3 py-1 rounded-lg border border-transparent transition-all">پاسخ</button>
                <button type="button" @click="noteMode=true"
                        :class="noteMode ? 'bg-amber-100 text-amber-700 border-amber-200' : 'text-gray-500 hover:bg-gray-100'"
                        class="text-xs font-semibold px-3 py-1 rounded-lg border border-transparent transition-all">یادداشت داخلی</button>
            </div>

            <form action="{{ route('admin.user-tickets.reply', $ticket->id) }}" method="POST"
                  @submit.prevent="sendReply($event.target)">
                @csrf
                <input type="hidden" name="is_internal" :value="noteMode ? '1' : '0'">
                <div class="flex gap-2 items-end">
                    <textarea
                        :name="noteMode ? 'message' : 'message'"
                        x-model="noteMode ? noteText : message"
                        :placeholder="noteMode ? 'یادداشت داخلی (فقط برای کارشناسان)...' : 'پیام خود را بنویسید...'"
                        :class="noteMode ? 'note-area' : ''"
                        class="tkt-form-input flex-1 resize-none text-sm"
                        rows="2"
                        @keydown.enter.prevent="if(!$event.shiftKey) sendReply($event.target.closest('form'))"
                    ></textarea>
                    <button type="submit"
                            :disabled="isSending"
                            :class="noteMode ? 'bg-amber-500 hover:bg-amber-600 disabled:bg-amber-300' : 'bg-gradient-to-r from-indigo-500 to-violet-500 hover:from-indigo-600 hover:to-violet-600 disabled:opacity-50'"
                            class="shrink-0 text-white px-4 py-2 rounded-xl text-xs font-semibold transition-all flex items-center gap-1.5 h-[4rem]">
                        <span x-show="!isSending">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        </span>
                        <span x-show="isSending">
                            <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        </span>
                    </button>
                </div>
                <p class="text-[10px] text-gray-400 mt-1">Enter = ارسال · Shift+Enter = خط جدید</p>
            </form>
        </div>
        @else
        <div class="border-t border-gray-100 p-4 bg-gray-50/60 text-center shrink-0">
            <p class="text-sm text-gray-400 mb-2">این مکالمه بسته شده است</p>
            <form action="{{ route('admin.user-tickets.status', $ticket->id) }}" method="POST" class="inline">
                @csrf @method('PATCH')
                <button type="submit" name="status" value="open"
                        class="btn-ghost text-xs text-emerald-600 hover:bg-emerald-50">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    بازگشایی مکالمه
                </button>
            </form>
        </div>
        @endif

        @else
        {{-- Empty State —— No active ticket --}}
        <div class="flex-1 flex flex-col items-center justify-center gap-4 p-8 text-center">
            <div class="w-16 h-16 rounded-full bg-indigo-50 flex items-center justify-center text-3xl">💬</div>
            <div>
                <h3 class="font-bold text-gray-700 mb-1">هنوز مکالمه‌ای با {{ $user->name }} آغاز نشده</h3>
                <p class="text-sm text-gray-400">با کلیک روی «گفتگوی جدید» اولین پیام را ارسال کنید.</p>
            </div>
            <form action="{{ route('admin.users.startChat', $user->id) }}" method="POST">
                @csrf
                <button type="submit" class="btn-gradient">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    شروع گفتگو با {{ $user->name }}
                </button>
            </form>
        </div>
        @endif
    </div>

</div>
@endsection
