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

/* ── Attachment mosaic ── */
.att-mosaic{display:flex;flex-wrap:wrap;gap:2px;border-radius:.75rem;overflow:hidden;margin-top:.5rem}
.att-mosaic .att-cell{position:relative;overflow:hidden;flex-shrink:0}
.att-mosaic .att-cell a{display:block;height:100%}
.att-mosaic .att-cell img{width:100%;height:100%;object-fit:cover;display:block;cursor:pointer;transition:opacity .18s}
.att-mosaic .att-cell img:hover{opacity:.88}
.att-more{position:absolute;inset:0;background:rgba(0,0,0,.52);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:1.1rem;pointer-events:none}
.att-mosaic.n1{max-width:230px}.att-mosaic.n1 .att-cell{width:100%}.att-mosaic.n1 .att-cell img{max-height:180px;border-radius:.75rem}
.att-mosaic.n2{max-width:280px}.att-mosaic.n2 .att-cell{width:calc(50% - 1px)}.att-mosaic.n2 .att-cell img{height:148px}
.att-mosaic.n3{max-width:280px}.att-mosaic.n3 .att-cell:first-child{width:100%}.att-mosaic.n3 .att-cell:first-child img{height:148px}.att-mosaic.n3 .att-cell:not(:first-child){width:calc(50% - 1px)}.att-mosaic.n3 .att-cell:not(:first-child) img{height:108px}
.att-mosaic.n4{max-width:280px}.att-mosaic.n4 .att-cell{width:calc(50% - 1px)}.att-mosaic.n4 .att-cell img{height:108px}

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
        selectedFiles: [],
        _rawFiles: [],

        get imgFiles() { return this.selectedFiles.filter(f => f.isImage); },
        get docFiles() { return this.selectedFiles.filter(f => !f.isImage); },
        get mosaicCls() { const n=this.imgFiles.length; return n===1?'n1':n===2?'n2':n===3?'n3':'n4'; },

        handleFileChange(e) {
            const files = Array.from(e.target.files || []);
            this._rawFiles = files;
            this.selectedFiles = files.map((f,i) => ({
                _pos: i,
                name: f.name,
                size: f.size>1048576 ? (f.size/1048576).toFixed(1)+' MB' : Math.round(f.size/1024)+' KB',
                isImage: f.type.startsWith('image/'),
                preview: null,
            }));
            files.forEach((f,i) => {
                if (f.type.startsWith('image/')) {
                    const r = new FileReader();
                    r.onload = ev => {
                        const item = this.selectedFiles.find(x => x._pos===i);
                        if (item) item.preview = ev.target.result;
                    };
                    r.readAsDataURL(f);
                }
            });
        },
        removeFile(pos) {
            this.selectedFiles = this.selectedFiles.filter(f => f._pos !== pos);
            const inp = this.$refs.fileInputUser;
            if (inp) {
                const dt = new DataTransfer();
                this.selectedFiles.forEach(f => { if (this._rawFiles[f._pos]) dt.items.add(this._rawFiles[f._pos]); });
                inp.files = dt.files;
            }
        },

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
                this.selectedFiles = []; this._rawFiles = [];
                if (this.$refs.fileInputUser) this.$refs.fileInputUser.value = '';
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
                const isImg = a => /^image\//i.test(a.mime||'')||/\.(jpg|jpeg|png|gif|webp|bmp)$/i.test(a.name||'');
                const imgs = r.attachments.filter(a => a.url && isImg(a));
                const docs = r.attachments.filter(a => a.url && !isImg(a));
                if (imgs.length > 0) {
                    const n = imgs.length, mc = n===1?'n1':n===2?'n2':n===3?'n3':'n4';
                    const mosaic = document.createElement('div'); mosaic.className = 'att-mosaic ' + mc;
                    imgs.slice(0, Math.min(n, 4)).forEach((att, i) => {
                        const cell = document.createElement('div'); cell.className = 'att-cell';
                        const a = document.createElement('a'); a.href = att.url; a.target = '_blank';
                        const img = document.createElement('img'); img.src = att.url; img.loading = 'lazy'; img.alt = att.name || 'تصویر';
                        a.appendChild(img); cell.appendChild(a);
                        if (i === 3 && n > 4) { const m = document.createElement('div'); m.className = 'att-more'; m.textContent = '+' + (n - 3); cell.appendChild(m); }
                        mosaic.appendChild(cell);
                    });
                    bl.appendChild(mosaic);
                }
                docs.forEach(att => {
                    const a = document.createElement('a'); a.href = att.url; a.target = '_blank';
                    a.className = 'inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[10px] bg-white/60 hover:bg-white border border-gray-200 mt-1 mr-1';
                    a.textContent = att.name || 'فایل'; bl.appendChild(a);
                });
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
            // صدا
            if (window.CS) window.CS.play('receive');
            // عنوان پنجره
            if (!document.hasFocus()) {
                const orig = document.title;
                document.title = '🔔 پاسخ جدید دریافت شد';
                window.addEventListener('focus', () => { document.title = orig; }, { once: true });
            }
            // مرورگر نوتیفیکیشن
            if ('Notification' in window && Notification.permission === 'granted') {
                try {
                    new Notification('پشتیبانی پاسخ داد', {
                        body: body ? body.substring(0, 100) : 'پاسخ جدید دریافت کردید',
                        icon: '/favicon.ico', dir: 'rtl', tag: 'ticket-reply'
                    });
                } catch(e) {}
            }
        }
     }">

    @include('partials.notification-permission', ['context' => 'customer'])
    </script>

    {{-- Toast --}}
    <div x-show="showToast" x-transition
         class="fixed top-5 left-1/2 -translate-x-1/2 z-50 flex items-center gap-3 bg-emerald-600 text-white px-5 py-3 rounded-2xl shadow-xl text-sm font-medium"
         style="display:none">
        <x-core-ui::icon name="bell-ring" class="w-4 h-4 shrink-0 animate-bounce" />
        <span x-text="toastText"></span>
        <button @click="showToast=false" class="mr-2 opacity-70 hover:opacity-100">
            <x-core-ui::icon name="close" class="w-4 h-4" />
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
                        <x-core-ui::icon name="plus" class="w-3 h-3" />
                        تیکت جدید
                    </a>
                </div>
                <div class="relative">
                    <x-core-ui::icon name="search" class="absolute right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" />
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
                            <span class="tkt-badge bg-green-50 text-green-700 border-green-200 !text-[10px] !py-0"><x-core-ui::icon name="check" class="w-2.5 h-2.5" />بسته</span>
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
                    <x-core-ui::icon name="list" class="w-3.5 h-3.5" />
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
                                @php
                                    $rImgs=array_values(array_filter($reply->attachments,fn($a)=>!empty($a['url'])&&(preg_match('/^image\//i',$a['mime']??'')||preg_match('/\.(jpg|jpeg|png|gif|webp|bmp)$/i',$a['name']??''))));
                                    $rDocs=array_values(array_filter($reply->attachments,fn($a)=>!empty($a['url'])&&!(preg_match('/^image\//i',$a['mime']??'')||preg_match('/\.(jpg|jpeg|png|gif|webp|bmp)$/i',$a['name']??''))));
                                    $rIc=count($rImgs);$rMc=$rIc===1?'n1':($rIc===2?'n2':($rIc===3?'n3':'n4'));
                                @endphp
                                @if($rImgs)
                                <div class="att-mosaic {{ $rMc }}">
                                    @foreach(array_slice($rImgs,0,4) as $ri=>$rImg)
                                    <div class="att-cell"><a href="{{ $rImg['url'] }}" target="_blank"><img src="{{ $rImg['url'] }}" loading="lazy" alt="{{ $rImg['name']??'تصویر' }}"></a>@if($ri===3&&$rIc>4)<div class="att-more">+{{ $rIc-3 }}</div>@endif</div>
                                    @endforeach
                                </div>
                                @endif
                                @foreach($rDocs as $rDoc)
                                <a href="{{ $rDoc['url'] }}" target="_blank" class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[10px] bg-white/60 hover:bg-white border border-gray-200 mt-1 mr-1">
                                    <x-core-ui::icon name="paperclip" class="w-3 h-3 shrink-0" />
                                    {{ $rDoc['name']??'فایل' }}
                                </a>
                                @endforeach
                                @endif
                            </div>
                            <span class="text-[10px] text-gray-400 mt-1 px-1 block {{ $isStaff ? '' : 'text-left' }}">
                                {{ \App\Helpers\Jalali::formatFa($reply->created_at, 'H:i') }}
                            </span>
                        </div>
                    </div>
                @endforeach

                {{-- Compact scroll pill --}}
                <div x-show="showScrollBtn" x-transition
                     @click="scrollDown()"
                     style="position:sticky;bottom:.75rem;display:flex;justify-content:center;pointer-events:none">
                    <button class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-indigo-600 text-white text-[11px] font-semibold shadow-lg hover:bg-indigo-700 transition-all cursor-pointer"
                            style="pointer-events:all">
                        <x-core-ui::icon name="chevron-down" class="w-3 h-3" />
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
                            <div class="flex items-center gap-3 mt-1.5 mr-1">
                                <small class="text-[10px] text-gray-400">Enter = ارسال &nbsp;|&nbsp; Shift+Enter = خط جدید</small>
                                <label class="inline-flex items-center gap-1 text-[11px] text-slate-500 hover:text-indigo-600 cursor-pointer transition-colors mr-auto">
                                    <x-core-ui::icon name="paperclip" class="w-4 h-4" />
                                    پیوست
                                    <input type="file" name="attachments[]" multiple x-ref="fileInputUser"
                                           accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.zip,.txt"
                                           class="hidden" @change="handleFileChange($event)">
                                </label>
                            </div>
                            {{-- File preview mosaic --}}
                            <div x-show="selectedFiles.length > 0" x-transition class="mt-2 space-y-1.5">
                                <div x-show="imgFiles.length > 0" class="att-mosaic" :class="mosaicCls">
                                    <template x-for="(f,i) in imgFiles.slice(0,4)" :key="f._pos">
                                        <div class="att-cell" style="position:relative">
                                            <template x-if="f.preview"><a :href="f.preview" target="_blank"><img :src="f.preview" :alt="f.name"></a></template>
                                            <template x-if="!f.preview"><div style="width:100%;height:100%;min-height:60px;background:#f1f5f9;display:flex;align-items:center;justify-content:center"><span style="font-size:.6rem;color:#94a3b8">...</span></div></template>
                                            <div x-show="i===3&&imgFiles.length>4" class="att-more" x-text="'+'+(imgFiles.length-3)"></div>
                                            <button type="button" @click.stop="removeFile(f._pos)" style="position:absolute;top:4px;left:4px;width:18px;height:18px;border-radius:9999px;background:rgba(0,0,0,.55);color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;cursor:pointer;border:none;z-index:5">×</button>
                                        </div>
                                    </template>
                                </div>
                                <div x-show="docFiles.length > 0" class="flex flex-wrap gap-1">
                                    <template x-for="f in docFiles" :key="f._pos">
                                        <div class="flex items-center gap-1 bg-white border border-emerald-200 rounded-lg px-2 py-1" style="max-width:160px">
                                            <x-core-ui::icon name="paperclip" class="w-3 h-3 shrink-0" style="color:#94a3b8" />
                                            <div style="min-width:0;flex:1"><div x-text="f.name" style="font-size:.65rem;font-weight:600;color:#334155;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"></div><div x-text="f.size" style="font-size:.6rem;color:#94a3b8"></div></div>
                                            <button type="button" @click.stop="removeFile(f._pos)" style="width:14px;height:14px;border-radius:9999px;background:#fee2e2;color:#ef4444;display:flex;align-items:center;justify-content:center;font-size:9px;cursor:pointer;border:none;flex-shrink:0">×</button>
                                        </div>
                                    </template>
                                </div>
                                <div class="flex items-center gap-1" style="font-size:.65rem;color:#059669;font-weight:600">
                                    <x-core-ui::icon name="check-circle" class="w-2.5 h-2.5" />
                                    <span x-text="selectedFiles.length + ' فایل آماده ارسال'"></span>
                                </div>
                            </div>
                        </div>
                        <button type="submit"
                                :disabled="isSending || !message.trim()"
                                class="flex items-center justify-center rounded-full w-11 h-11 transition-all shrink-0 mb-5"
                                :class="(isSending || !message.trim()) ? 'bg-gray-200 text-gray-500 cursor-not-allowed' : 'bg-gradient-to-br from-indigo-600 to-purple-600 text-white shadow-md hover:shadow-lg'">
                            <template x-if="!isSending">
                                <x-core-ui::icon name="arrow-right" class="w-5 h-5" />
                            </template>
                            <template x-if="isSending">
                                <x-core-ui::icon name="loader" class="w-5 h-5 animate-spin" />
                            </template>
                        </button>
                    </div>
                </form>
            </div>
            @else
            <div class="msg-chat-footer text-center">
                <div class="w-10 h-10 mx-auto mb-2 rounded-full bg-emerald-100 flex items-center justify-center">
                    <x-core-ui::icon name="check-circle" class="w-5 h-5 text-emerald-600" />
                </div>
                <p class="text-sm font-medium text-slate-600 mb-1">این تیکت بسته شده است</p>
                <a href="{{ route('account.tickets.create') }}" class="btn btn-primary btn-sm mt-2">ثبت تیکت جدید</a>
            </div>
            @endif

        </div>{{-- /.msg-chat --}}
    </div>{{-- /.msg-wrap --}}
</div>
@endsection
