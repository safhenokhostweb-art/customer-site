@extends('account.layout')
@section('title', $ticket->subject)
@section('header', 'گفتگوی پشتیبانی')

@push('head')
@include('partials.ticket-styles')
<style>
/* ── Messenger Layout ── */
.msg-wrap {
    display: flex;
    height: calc(100vh - 7rem);
    min-height: 520px;
    border-radius: 1.25rem;
    overflow: hidden;
    border: 1px solid rgba(226,232,240,0.7);
    background: rgba(255,255,255,0.92);
    box-shadow: 0 4px 32px rgba(15,23,42,0.07);
}

/* ── Ticket list panel ── */
.msg-list {
    width: 300px;
    min-width: 260px;
    border-left: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
    background: rgba(248,250,252,0.95);
    flex-shrink: 0;
}
.msg-list-header {
    padding: 1rem;
    border-bottom: 1px solid #e2e8f0;
    background: rgba(255,255,255,0.9);
}
.msg-list-search {
    width: 100%;
    padding: 0.5rem 0.75rem 0.5rem 2.25rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    font-size: 0.8125rem;
    background: rgba(248,250,252,0.8);
    transition: all 0.2s;
}
.msg-list-search:focus {
    outline: none;
    border-color: #a5b4fc;
    box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
    background: #fff;
}
.msg-list-items {
    flex: 1;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: #c7d2fe transparent;
}
.msg-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.875rem 1rem;
    border-bottom: 1px solid rgba(226,232,240,0.5);
    cursor: pointer;
    transition: background 0.15s;
    text-decoration: none;
    color: inherit;
    position: relative;
}
.msg-item:hover { background: rgba(99,102,241,0.04); }
.msg-item.active {
    background: rgba(99,102,241,0.08);
    border-right: 3px solid #6366f1;
}
.msg-item__avatar {
    width: 2.25rem; height: 2.25rem;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 0.875rem; color: #fff;
    flex-shrink: 0;
}
.msg-item__body { flex: 1; min-width: 0; }
.msg-item__subject {
    font-size: 0.8125rem; font-weight: 600; color: #1e293b;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    display: block;
}
.msg-item.active .msg-item__subject { color: #4f46e5; }
.msg-item__meta {
    display: flex; align-items: center; gap: 0.4rem;
    margin-top: 0.2rem; font-size: 0.7rem; color: #94a3b8;
}
.msg-item__dot {
    width: 0.4rem; height: 0.4rem; border-radius: 50%; flex-shrink: 0;
}
.msg-item__unread {
    position: absolute;
    left: 0.75rem; top: 50%; transform: translateY(-50%);
    min-width: 1.1rem; height: 1.1rem; border-radius: 9999px;
    background: #6366f1; color: #fff;
    font-size: 0.6rem; font-weight: 700;
    display: flex; align-items: center; justify-content: center; padding: 0 4px;
}

/* ── Chat panel ── */
.msg-chat {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-width: 0;
    background: #fff;
}
.msg-chat-header {
    padding: 0.875rem 1.25rem;
    border-bottom: 1px solid #e2e8f0;
    background: rgba(255,255,255,0.95);
    display: flex;
    align-items: center;
    gap: 0.875rem;
}
.msg-chat-header__avatar {
    width: 2.25rem; height: 2.25rem; border-radius: 50%;
    background: linear-gradient(135deg,#6366f1,#a855f7);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-weight: 700; font-size: 0.875rem; flex-shrink: 0;
}
.msg-chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 1.25rem;
    display: flex;
    flex-direction: column;
    gap: 0;
    scrollbar-width: thin;
    scrollbar-color: #c7d2fe transparent;
}
.msg-chat-footer {
    border-top: 1px solid #e2e8f0;
    background: rgba(255,255,255,0.95);
    padding: 0.75rem 1rem;
}

/* ── Mobile toggle ── */
@media (max-width: 768px) {
    .msg-wrap { flex-direction: column; height: auto; min-height: 100dvh; }
    .msg-list { width: 100%; min-width: 0; height: 240px; min-height: 240px; border-left: none; border-bottom: 1px solid #e2e8f0; }
    .msg-chat { min-height: 420px; }
}

/* ── New ticket btn ── */
.msg-new-btn {
    display: inline-flex; align-items: center; gap: 0.3rem;
    padding: 0.4rem 0.75rem; border-radius: 0.625rem;
    background: linear-gradient(135deg,#6366f1,#a855f7);
    color: #fff; font-size: 0.7rem; font-weight: 600;
    text-decoration: none; transition: opacity 0.15s;
    white-space: nowrap;
}
.msg-new-btn:hover { opacity: 0.9; }
</style>
@endpush

@section('content')
<div style="margin: -1rem"
     data-poll-url="{{ route('account.tickets.poll', $ticket->id) }}"
     data-poll-last-id="{{ $ticket->replies->last()?->id ?? 0 }}"
     x-data="{
        message: '',
        isSending: false,
        showScrollBtn: false,
        pollLastId: 0,
        seenIds: {},
        pollTimer: null,
        showToast: false,
        toastText: '',
        listSearch: '',
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
            this.$nextTick(() => this.scrollDown());
            this.pollLastId = parseInt(this.$el.dataset.pollLastId || '0', 10);
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
                    if (window.CS) window.CS.play('receive');
                }
                if (data.last_id && data.last_id > this.pollLastId) this.pollLastId = data.last_id;
            } catch(e) {}
        },
        async sendReply(form) {
            if (this.isSending || !this.message.trim()) return;
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
                    if (window.CS) window.CS.play('send');
                }
                this.message = '';
            } catch(e) { console.error(e); }
            finally { this.isSending = false; }
        },
        appendReply(r) {
            if (r.id && this.seenIds[r.id]) return;
            if (r.id) this.seenIds[r.id] = true;
            const container = this.$refs.chatMessages;
            if (!container) return;
            const isStaff = r.author_type === 'staff';
            const safeName = r.author_name || (isStaff ? 'پشتیبانی' : 'شما');
            const initial = safeName.charAt(0);
            const d = new Date(r.created_at);
            const time = d.toLocaleTimeString('fa-IR', {hour:'2-digit', minute:'2-digit'});
            const color = isStaff ? 'var(--ui-success)' : 'var(--ui-indigo)';
            const wrapper = document.createElement('div');
            wrapper.className = 'flex items-end gap-2 max-w-[85%] mb-4' + (isStaff ? '' : ' flex-row-reverse');
            wrapper.style.cssText = 'align-self:' + (isStaff ? 'flex-start' : 'flex-end') + ';opacity:0;transform:translateY(8px)';
            const av = document.createElement('div');
            av.className = 'w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0 shadow-sm';
            av.style.backgroundColor = color;
            av.textContent = initial;
            const bw = document.createElement('div');
            const bl = document.createElement('div');
            bl.className = (isStaff ? 'bubble-staff' : 'bubble-customer') + ' px-4 py-2.5 shadow-sm';
            const np = document.createElement('p');
            np.className = 'text-xs font-bold mb-1 opacity-70';
            np.textContent = safeName;
            if (isStaff) {
                const sp = document.createElement('span');
                sp.className = 'text-[9px] bg-green-200/60 px-1.5 py-0.5 rounded-full mr-1';
                sp.textContent = 'پشتیبانی';
                np.appendChild(sp);
            }
            const mp = document.createElement('p');
            mp.className = 'text-sm leading-relaxed whitespace-pre-wrap';
            mp.textContent = r.message || '';
            bl.appendChild(np); bl.appendChild(mp);
            if (r.attachments && r.attachments.length > 0) {
                const isImgAtt = att => /^image\//i.test(att.mime||'') || /\.(jpg|jpeg|png|gif|webp|bmp)$/i.test(att.name||'');
                const attDiv = document.createElement('div');
                attDiv.className = 'flex flex-wrap gap-1.5 mt-2';
                r.attachments.filter(att => att.url).forEach(att => {
                    if (isImgAtt(att)) {
                        const a = document.createElement('a');
                        a.href = att.url; a.target = '_blank'; a.className = 'block mt-1.5';
                        const img = document.createElement('img');
                        img.src = att.url; img.loading = 'lazy'; img.alt = att.name || 'تصویر';
                        img.className = 'rounded-xl max-h-52 max-w-[220px] object-cover hover:opacity-90 transition-opacity shadow-sm border border-white/60 cursor-pointer';
                        a.appendChild(img); attDiv.appendChild(a);
                    } else {
                        const a = document.createElement('a');
                        a.href = att.url; a.target = '_blank';
                        a.className = 'inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[10px] bg-white/60 hover:bg-white border border-gray-200 transition-all';
                        a.textContent = att.name || 'فایل';
                        attDiv.appendChild(a);
                    }
                });
                if (attDiv.hasChildNodes()) bl.appendChild(attDiv);
            }
            const ts = document.createElement('span');
            ts.className = 'text-[10px] text-gray-400 mt-1 px-1 block' + (isStaff ? '' : ' text-left');
            ts.textContent = time;
            bw.appendChild(bl); bw.appendChild(ts);
            wrapper.appendChild(av); wrapper.appendChild(bw);
            container.appendChild(wrapper);
            requestAnimationFrame(() => { wrapper.style.transition = 'opacity .3s,transform .3s'; wrapper.style.opacity = '1'; wrapper.style.transform = 'translateY(0)'; });
            container.scrollTop = container.scrollHeight;
            if (isStaff) { this.notify(safeName, r.message); }
        },
        notify(from, body) {
            this.toastText = 'پاسخ جدید از ' + from;
            this.showToast = true;
            clearTimeout(this._toastTimer);
            this._toastTimer = setTimeout(() => { this.showToast = false; }, 5000);
        }
     }">

    {{-- Toast --}}
    <div x-show="showToast" x-transition
         class="fixed top-5 left-1/2 -translate-x-1/2 z-50 flex items-center gap-3 bg-emerald-600 text-white px-5 py-3 rounded-2xl shadow-xl text-sm font-medium"
         style="display:none">
        <svg class="w-4 h-4 shrink-0 animate-bounce" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        <span x-text="toastText"></span>
        <button @click="showToast=false" class="mr-2 opacity-70 hover:opacity-100">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    {{-- ═══ Messenger Layout ══════════════════════════════════════════════ --}}
    <div class="msg-wrap">

        {{-- ── Left: Ticket List Panel ─────────────────────────────────── --}}
        <div class="msg-list">

            {{-- Header: search + new ticket --}}
            <div class="msg-list-header">
                <div class="flex items-center justify-between gap-2 mb-2.5">
                    <span class="text-xs font-bold text-slate-700">گفتگوها</span>
                    <a href="{{ route('account.tickets.create') }}" class="msg-new-btn">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        تیکت جدید
                    </a>
                </div>
                <div class="relative">
                    <svg class="absolute right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" x-model="listSearch" placeholder="جستجو..."
                           class="msg-list-search" style="padding-right:2rem">
                </div>
            </div>

            {{-- Ticket items --}}
            <div class="msg-list-items">
                @forelse($allTickets as $t)
                @php
                    $tIsActive   = $t->id === $ticket->id;
                    $tStatusDot  = match($t->status) { 'open' => '#f59e0b', 'in_progress' => '#3b82f6', default => '#10b981' };
                    $tBgGradient = match($t->status) { 'open' => 'linear-gradient(135deg,#f59e0b,#f97316)', 'in_progress' => 'linear-gradient(135deg,#6366f1,#3b82f6)', default => 'linear-gradient(135deg,#10b981,#059669)' };
                    $tInitial    = mb_substr($t->subject, 0, 1);
                    $tDate       = \App\Helpers\Jalali::formatFa($t->updated_at, 'Y/m/d');
                    $tStatus     = match($t->status) { 'open' => 'باز', 'in_progress' => 'در بررسی', default => 'بسته' };
                @endphp
                <a href="{{ route('account.tickets.show', $t->id) }}"
                   class="msg-item {{ $tIsActive ? 'active' : '' }}"
                   x-show="!listSearch || '{{ addslashes($t->subject) }}'.includes(listSearch) || '{{ $t->ticket_number }}'.includes(listSearch)">
                    <div class="msg-item__avatar" style="background:{{ $tBgGradient }}">{{ $tInitial }}</div>
                    <div class="msg-item__body">
                        <span class="msg-item__subject">{{ $t->subject }}</span>
                        <div class="msg-item__meta">
                            <span class="msg-item__dot" style="background:{{ $tStatusDot }}"></span>
                            <span>{{ $tStatus }}</span>
                            @if($t->priority === 'high')
                            <span class="bg-red-100 text-red-600 px-1.5 rounded text-[9px] font-bold">فوری</span>
                            @endif
                            <span class="mr-auto">{{ $tDate }}</span>
                        </div>
                    </div>
                </a>
                @empty
                <div class="p-6 text-center text-slate-400 text-xs">هیچ تیکتی نیست</div>
                @endforelse
            </div>
        </div>

        {{-- ── Right: Chat Panel ───────────────────────────────────────── --}}
        <div class="msg-chat">

            {{-- Chat header --}}
            <div class="msg-chat-header">
                <div class="msg-chat-header__avatar">{{ mb_substr($ticket->subject, 0, 1) }}</div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-slate-800 truncate">{{ $ticket->subject }}</p>
                    <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                        <span class="font-mono bg-slate-100 text-slate-500 text-[10px] px-1.5 py-0.5 rounded">{{ $ticket->ticket_number }}</span>
                        @if($ticket->status === 'open')
                            <span class="tkt-badge bg-yellow-50 text-yellow-700 border-yellow-200 !text-[10px] !py-0"><span class="pdot bg-yellow-500"></span>باز</span>
                        @elseif($ticket->status === 'in_progress')
                            <span class="tkt-badge bg-blue-50 text-blue-700 border-blue-200 !text-[10px] !py-0"><span class="pdot bg-blue-500"></span>در حال بررسی</span>
                        @else
                            <span class="tkt-badge bg-green-50 text-green-700 border-green-200 !text-[10px] !py-0"><svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>بسته</span>
                        @endif
                        @if($ticket->priority === 'high')
                            <span class="tkt-badge bg-red-50 text-red-600 border-red-200 !text-[10px] !py-0">فوری</span>
                        @endif
                        <span class="text-xs text-slate-400 mr-auto flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse"></span>
                            به‌روزرسانی خودکار
                        </span>
                    </div>
                </div>
                <a href="{{ route('account.tickets.index') }}"
                   class="shrink-0 flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 border border-slate-200 transition-all ml-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
                    همه
                </a>
            </div>

            {{-- Messages --}}
            <div x-ref="chatMessages"
                 @scroll="handleScroll"
                 class="msg-chat-messages chat-bg">

                @php
                $myName    = auth()->user()->name ?? auth()->user()->mobile ?? 'شما';
                $myInitial = mb_substr($myName, 0, 1);
                @endphp

                {{-- Initial message (right = user) --}}
                <div class="flex items-end gap-2 max-w-[85%] mb-4 flex-row-reverse" style="align-self:flex-end">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0 shadow-sm" style="background-color:var(--ui-indigo)">{{ $myInitial }}</div>
                    <div>
                        <div class="bubble-customer px-4 py-2.5 shadow-sm">
                            <p class="text-xs font-bold mb-1 opacity-80">{{ $myName }}</p>
                            <p class="text-sm leading-relaxed whitespace-pre-wrap">{{ $ticket->description }}</p>
                        </div>
                        <span class="text-[10px] text-gray-400 mt-1 px-1 block text-left">{{ \App\Helpers\Jalali::formatFa($ticket->created_at, 'Y/m/d H:i') }}</span>
                    </div>
                </div>

                @php $lastDate = null; @endphp
                @foreach($ticket->replies as $reply)
                    @php
                        $replyDate = $reply->created_at->toDateString();
                        $isStaff   = $reply->author_type === 'staff';
                        $rName     = $isStaff ? ($reply->user?->name ?? 'پشتیبانی') : ($reply->customer_name ?? $myName);
                    @endphp

                    @if($replyDate !== $lastDate)
                        @php $lastDate = $replyDate; @endphp
                        <div class="flex justify-center my-3 w-full">
                            <span class="text-[10px] text-gray-400 bg-white/80 px-3 py-1 rounded-full shadow-sm">
                                @if($reply->created_at->isToday()) امروز
                                @elseif($reply->created_at->isYesterday()) دیروز
                                @else {{ \App\Helpers\Jalali::formatFa($reply->created_at, 'Y/m/d') }}
                                @endif
                            </span>
                        </div>
                    @endif

                    <div class="flex items-end gap-2 max-w-[85%] mb-4 {{ $isStaff ? '' : 'flex-row-reverse' }}"
                         style="align-self:{{ $isStaff ? 'flex-start' : 'flex-end' }}">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0 shadow-sm"
                             style="background-color:{{ $isStaff ? 'var(--ui-success)' : 'var(--ui-indigo)' }}">
                            {{ mb_substr($rName, 0, 1) }}
                        </div>
                        <div>
                            <div class="{{ $isStaff ? 'bubble-staff' : 'bubble-customer' }} px-4 py-2.5 shadow-sm">
                                <p class="text-xs font-bold mb-1 opacity-70">
                                    {{ $rName }}
                                    @if($isStaff)
                                    <span class="text-[9px] bg-green-200/60 px-1.5 py-0.5 rounded-full mr-1">پشتیبانی</span>
                                    @endif
                                </p>
                                <p class="text-sm leading-relaxed whitespace-pre-wrap">{{ $reply->message }}</p>
                                @if($reply->attachments && is_array($reply->attachments))
                                <div class="flex flex-wrap gap-1.5 mt-2">
                                    @foreach($reply->attachments as $file)
                                    @if(!empty($file['url']))
                                    @php
                                        $attName  = $file['name'] ?? 'فایل';
                                        $attIsImg = preg_match('/^image\//i', $file['mime'] ?? '') || preg_match('/\.(jpg|jpeg|png|gif|webp|bmp)$/i', $attName);
                                    @endphp
                                    @if($attIsImg)
                                    <a href="{{ $file['url'] }}" target="_blank" class="block mt-1">
                                        <img src="{{ $file['url'] }}" loading="lazy" alt="{{ $attName }}"
                                             class="rounded-xl max-h-52 max-w-[220px] object-cover hover:opacity-90 transition-opacity shadow-sm border border-white/60 cursor-pointer">
                                    </a>
                                    @else
                                    <a href="{{ $file['url'] }}" target="_blank"
                                       class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[10px] bg-white/60 hover:bg-white border border-gray-200 transition-all">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13"/></svg>
                                        <span class="truncate max-w-[100px]">{{ $attName }}</span>
                                    </a>
                                    @endif
                                    @endif
                                    @endforeach
                                </div>
                                @endif
                            </div>
                            <span class="text-[10px] text-gray-400 mt-1 px-1 block {{ $isStaff ? '' : 'text-left' }}">
                                {{ \App\Helpers\Jalali::formatFa($reply->created_at, 'H:i') }}
                            </span>
                        </div>
                    </div>
                @endforeach

                {{-- Scroll button --}}
                <div x-show="showScrollBtn" x-transition class="sticky bottom-2 flex justify-center w-full">
                    <button @click="scrollDown"
                            class="bg-white text-gray-600 rounded-full px-4 py-2 shadow-lg border border-gray-200 hover:bg-gray-50 transition-all text-xs font-medium flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                        پیام‌های جدید
                    </button>
                </div>
            </div>

            {{-- Reply form / Closed notice --}}
            @if($ticket->status !== 'closed')
            <div class="msg-chat-footer">
                <form method="POST"
                      action="{{ route('account.tickets.reply', $ticket->id) }}"
                      enctype="multipart/form-data"
                      @submit.prevent="sendReply($event.target)">
                    @csrf
                    <div class="flex items-end gap-2">
                        <div class="flex-1">
                            <textarea name="message"
                                      x-model="message"
                                      rows="2" required maxlength="5000"
                                      class="w-full rounded-2xl border border-gray-200 resize-none py-3 px-4 text-sm transition-all bg-white focus:outline-none focus:ring-2 focus:ring-indigo-200 focus:border-indigo-300"
                                      placeholder="پیام خود را بنویسید..."
                                      @keydown.enter.prevent="if(!$event.shiftKey) sendReply($el.closest('form'))"></textarea>
                            <small class="text-[10px] text-gray-400 mt-0.5 block mr-2">اینتر = ارسال | Shift+Enter = خط جدید</small>
                            <input type="file" name="attachments[]" multiple
                                   accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.zip,.txt"
                                   class="mt-1.5 text-xs text-gray-500 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700 file:px-2.5 file:py-1 file:text-xs file:font-medium hover:file:bg-indigo-100">
                        </div>
                        <button type="submit"
                                :disabled="isSending || !message.trim()"
                                class="flex items-center justify-center rounded-full p-3 min-w-[48px] h-[48px] transition-all shrink-0 mb-4"
                                :class="(isSending || !message.trim()) ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-md hover:shadow-lg'">
                            <template x-if="!isSending">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                            </template>
                            <template x-if="isSending">
                                <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            </template>
                        </button>
                    </div>
                </form>
            </div>
            @else
            <div class="msg-chat-footer text-center">
                <div class="w-10 h-10 mx-auto mb-2 rounded-full bg-emerald-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <p class="text-sm font-medium text-slate-600 mb-1">این تیکت بسته شده است</p>
                <a href="{{ route('account.tickets.create') }}" class="btn btn-primary btn-sm mt-2">ثبت تیکت جدید</a>
            </div>
            @endif

        </div>{{-- /.msg-chat --}}
    </div>{{-- /.msg-wrap --}}
</div>
@endsection
