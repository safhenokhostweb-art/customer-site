@extends('admin.layout')
@section('title', $ticket->ticket_number . ' — صندوق پشتیبانی')

@push('head')
@include('partials.ticket-styles')
<style>
/* ═══ Messenger Frame ═══════════════════════════════════════════════ */
.msgr-frame {
    display: flex;
    height: calc(100vh - 5rem);
    min-height: 600px;
    border-radius: 1.25rem;
    overflow: hidden;
    border: 1px solid rgba(226,232,240,.6);
    background: #fff;
    box-shadow: 0 4px 32px rgba(15,23,42,.07);
}

/* ── Left: conversation list ─────────────────────────────────────── */
.msgr-convs {
    width: 252px; min-width: 220px; flex-shrink: 0;
    display: flex; flex-direction: column;
    border-left: 1px solid #e2e8f0;
    background: #f8fafc;
}
.msgr-convs-head {
    padding: .875rem 1rem; border-bottom: 1px solid #e2e8f0;
    background: #fff; flex-shrink: 0;
}
.msgr-conv-search {
    width: 100%; padding: .4rem .75rem .4rem 2.1rem;
    border: 1px solid #e2e8f0; border-radius: .625rem;
    font-size: .8rem; background: #f8fafc; outline: none; transition: all .2s;
}
.msgr-conv-search:focus { border-color: #a5b4fc; box-shadow: 0 0 0 3px rgba(99,102,241,.1); background: #fff; }
.msgr-convs-list { flex: 1; overflow-y: auto; scrollbar-width: thin; scrollbar-color: #c7d2fe transparent; }
.msgr-conv-item {
    display: flex; align-items: flex-start; gap: .625rem;
    padding: .7rem .875rem; border-bottom: 1px solid rgba(226,232,240,.4);
    cursor: pointer; text-decoration: none; color: inherit; transition: background .15s; position: relative;
}
.msgr-conv-item:hover { background: rgba(99,102,241,.04); }
.msgr-conv-item.active { background: rgba(99,102,241,.1); border-right: 3px solid #6366f1; }
.msgr-conv-av {
    width: 2.125rem; height: 2.125rem; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: .8rem; color: #fff; flex-shrink: 0;
}
.msgr-conv-body { flex: 1; min-width: 0; }
.msgr-conv-subj {
    font-size: .78rem; font-weight: 600; color: #1e293b;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block;
}
.msgr-conv-item.active .msgr-conv-subj { color: #4f46e5; }
.msgr-conv-meta { display: flex; align-items: center; gap: .3rem; margin-top: .15rem; font-size: .68rem; color: #94a3b8; }
.msgr-conv-sdot { width: .35rem; height: .35rem; border-radius: 50%; flex-shrink: 0; }

/* ── Middle: chat ────────────────────────────────────────────────── */
.msgr-chat { flex: 1; min-width: 0; display: flex; flex-direction: column; background: #fff; position: relative; }
.msgr-chat-head {
    padding: .75rem 1.125rem; border-bottom: 1px solid #e2e8f0;
    background: rgba(255,255,255,.97); display: flex; align-items: center;
    gap: .75rem; flex-shrink: 0;
}
.msgr-chat-msgs {
    flex: 1; overflow-y: auto; padding: 1.25rem;
    display: flex; flex-direction: column;
    scrollbar-width: thin; scrollbar-color: #c7d2fe transparent;
}
.msgr-chat-foot { border-top: 1px solid #e2e8f0; background: rgba(255,255,255,.97); flex-shrink: 0; }

/* ── Right: info panel ───────────────────────────────────────────── */
.msgr-info {
    width: 224px; min-width: 196px; flex-shrink: 0;
    display: flex; flex-direction: column;
    border-right: 1px solid #e2e8f0; background: #f8fafc;
    overflow-y: auto; scrollbar-width: thin; scrollbar-color: #c7d2fe transparent;
}
.msgr-info-sec { padding: .875rem 1rem; border-bottom: 1px solid #e2e8f0; }
.msgr-info-label { font-size: .7rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .04em; margin-bottom: .5rem; }

/* ── Scroll pill ─────────────────────────────────────────────────── */
.scroll-pill {
    position: absolute; bottom: .875rem; left: 50%; transform: translateX(-50%);
    display: inline-flex; align-items: center; gap: .35rem;
    padding: .35rem .875rem; border-radius: 9999px;
    background: #6366f1; color: #fff; font-size: .72rem; font-weight: 600;
    cursor: pointer; box-shadow: 0 4px 14px rgba(99,102,241,.35);
    z-index: 20; white-space: nowrap; transition: all .2s;
}
.scroll-pill:hover { background: #4f46e5; }

/* ── Mode toggle chips ───────────────────────────────────────────── */
.mode-chip {
    display: inline-flex; align-items: center; gap: .3rem;
    padding: .35rem .75rem; border-radius: .625rem;
    font-size: .75rem; font-weight: 600; cursor: pointer;
    border: 1.5px solid transparent; transition: all .2s;
}

/* ── Attachment mosaic ────────────────────────────────────────────── */
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

/* ── Responsive ──────────────────────────────────────────────────── */
@media (max-width: 1024px) {
    .msgr-info { display: none; }
}
@media (max-width: 768px) {
    .msgr-convs { display: none; }
    .msgr-frame  { border-radius: 0; height: 100dvh; min-height: unset; }
}
</style>
@endpush

@section('content')
<div style="margin:-1rem"
     data-poll-url="{{ route('admin.inbox.poll', ['channel' => $channel, 'id' => $id]) }}"
     data-poll-last-id="{{ $ticket->replies->last()?->id ?? 0 }}"
     x-data="{
        message: '',
        noteText: '',
        noteMode: false,
        isSending: false,
        showScrollBtn: false,
        pollLastId: 0,
        seenIds: {},
        pollTimer: null,
        convSearch: '',
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
            const inp = this.$refs.fileInput;
            if (inp) {
                const dt = new DataTransfer();
                this.selectedFiles.forEach(f => { if (this._rawFiles[f._pos]) dt.items.add(this._rawFiles[f._pos]); });
                inp.files = dt.files;
            }
        },

        scrollDown() {
            const el = this.$refs.chatMsgs;
            if (el) { el.scrollTop = el.scrollHeight; this.showScrollBtn = false; }
        },
        handleScroll() {
            const el = this.$refs.chatMsgs;
            if (!el) return;
            this.showScrollBtn = el.scrollHeight - el.scrollTop - el.clientHeight > 100;
        },
        init() {
            this.$nextTick(() => this.scrollDown());
            this.pollLastId = parseInt(this.$el.dataset.pollLastId || '0', 10);
            this.pollTimer  = setInterval(() => this.doPoll(), 4000);
            window.addEventListener('beforeunload', () => clearInterval(this.pollTimer));
        },
        async doPoll() {
            const url = this.$el.dataset.pollUrl;
            if (!url) return;
            try {
                const res  = await fetch(url + '?last_id=' + this.pollLastId, { headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' }});
                if (!res.ok) return;
                const data = await res.json();
                if (data.replies?.length) {
                    data.replies.forEach(r => this.appendReply(r));
                    if (window.CS && data.replies.some(r => r.author_type !== 'staff')) window.CS.play('receive');
                }
                if (data.last_id > this.pollLastId) this.pollLastId = data.last_id;
            } catch (e) {}
        },
        async sendReply(form) {
            if (this.isSending) return;
            const text = (this.noteMode ? this.noteText : this.message).trim();
            if (!text) return;
            this.isSending = true;
            try {
                const fd  = new FormData(form);
                const res = await fetch(form.action, { method:'POST', headers:{ 'X-Requested-With':'XMLHttpRequest', Accept:'application/json'}, body: fd });
                if (!res.ok) { const e = await res.json().catch(()=>({})); alert(e.message || 'خطا در ارسال'); return; }
                const data = await res.json();
                if (data.reply) {
                    this.appendReply(data.reply);
                    if (data.reply.id > this.pollLastId) this.pollLastId = data.reply.id;
                    if (window.CS && !data.reply.is_internal) window.CS.play('send');
                }
                this.noteMode ? (this.noteText = '') : (this.message = '');
                this.selectedFiles = [];
                if (this.$refs.fileInput) this.$refs.fileInput.value = '';
            } catch (e) { console.error(e); }
            finally { this.isSending = false; }
        },
        appendReply(r) {
            if (r.id && this.seenIds[r.id]) return;
            if (r.id) this.seenIds[r.id] = true;
            const c = this.$refs.chatMsgs;
            if (!c) return;
            const isStaff    = r.author_type === 'staff';
            const isInternal = !!r.is_internal;
            const onRight    = isStaff || isInternal;
            const name  = r.author_name || (isStaff ? 'پشتیبان' : 'کاربر');
            const d = new Date(r.created_at);
            const time = d.toLocaleTimeString('fa-IR',{hour:'2-digit',minute:'2-digit'});
            const color = isInternal ? '#f59e0b' : (isStaff ? '#6366f1' : '#0d9488');
            const bubbleCls = isInternal ? 'bubble-internal' : (isStaff ? 'bubble-staff' : 'bubble-customer');

            const w = document.createElement('div');
            w.className = 'flex items-end gap-2 max-w-[78%] mb-4' + (onRight ? ' flex-row-reverse' : '');
            w.style.cssText = 'align-self:' + (onRight ? 'flex-end' : 'flex-start') + ';opacity:0;transform:translateY(6px);transition:all .25s';

            const av = document.createElement('div');
            av.className = 'w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0 shadow-sm';
            av.style.backgroundColor = color;
            av.textContent = name.charAt(0);

            const bw = document.createElement('div');
            bw.style.cssText = 'display:flex;flex-direction:column;align-items:' + (onRight ? 'flex-end' : 'flex-start');

            const bl = document.createElement('div');
            bl.className = bubbleCls + ' px-4 py-2.5 shadow-sm';

            const np = document.createElement('p');
            np.className = 'text-xs font-bold mb-1 opacity-70';
            np.textContent = name;
            if (isInternal) {
                const tg = document.createElement('span');
                tg.className = 'text-[9px] bg-amber-200/60 px-1.5 py-0.5 rounded-full mr-1';
                tg.textContent = 'یادداشت';
                np.appendChild(tg);
            }
            const mp = document.createElement('p');
            mp.className = 'text-sm leading-relaxed whitespace-pre-wrap';
            mp.textContent = r.message || '';
            bl.appendChild(np); bl.appendChild(mp);

            if (r.attachments?.length) {
                const isImg = a => /^image\//i.test(a.mime||'')||/\.(jpg|jpeg|png|gif|webp|bmp)$/i.test(a.name||'');
                const imgs = r.attachments.filter(a=>a.url&&isImg(a));
                const docs = r.attachments.filter(a=>a.url&&!isImg(a));
                if (imgs.length > 0) {
                    const n=imgs.length, mc=n===1?'n1':n===2?'n2':n===3?'n3':'n4';
                    const mosaic=document.createElement('div'); mosaic.className='att-mosaic '+mc;
                    imgs.slice(0,Math.min(n,4)).forEach((att,i)=>{
                        const cell=document.createElement('div'); cell.className='att-cell';
                        const a=document.createElement('a'); a.href=att.url; a.target='_blank';
                        const img=document.createElement('img'); img.src=att.url; img.loading='lazy'; img.alt=att.name||'تصویر';
                        a.appendChild(img); cell.appendChild(a);
                        if(i===3&&n>4){const m=document.createElement('div');m.className='att-more';m.textContent='+'+(n-3);cell.appendChild(m);}
                        mosaic.appendChild(cell);
                    });
                    bl.appendChild(mosaic);
                }
                docs.forEach(att => {
                    const a=document.createElement('a'); a.href=att.url; a.target='_blank';
                    a.className='inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[10px] bg-white/60 hover:bg-white border border-gray-200 mt-1 mr-1';
                    a.textContent=att.name||'فایل'; bl.appendChild(a);
                });
            }

            const ts = document.createElement('span');
            ts.className = 'text-[10px] text-gray-400 mt-1 px-1';
            ts.textContent = time;
            bw.appendChild(bl); bw.appendChild(ts);
            w.appendChild(av); w.appendChild(bw);
            c.appendChild(w);
            requestAnimationFrame(() => { w.style.opacity='1'; w.style.transform='translateY(0)'; });
            this.scrollDown();
        }
     }">

    <div class="msgr-frame">

        {{-- ══ LEFT: Conversations sidebar ═════════════════════════════════════ --}}
        <div class="msgr-convs">
            <div class="msgr-convs-head">
                <div class="flex items-center justify-between mb-2.5">
                    <span class="text-xs font-bold text-slate-700">گفتگوها</span>
                    <a href="{{ route('admin.inbox.create') }}"
                       class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-700 transition-all text-[11px] font-semibold"
                       style="color:#fff !important;text-decoration:none">
                        <svg class="w-3 h-3" fill="none" stroke="#fff" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        <span style="color:#fff">جدید</span>
                    </a>
                </div>
                <div class="relative">
                    <svg class="absolute right-2 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" x-model="convSearch" placeholder="جستجو..." class="msgr-conv-search" style="padding-right:1.875rem">
                </div>
            </div>
            <div class="msgr-convs-list">
                @forelse($sidebarTickets as $st)
                @php
                    $stActive  = $st->id === $ticket->id && $channel === 'user';
                    $stColor   = match($st->status) { 'open' => '#f59e0b', 'in_progress' => '#3b82f6', default => '#10b981' };
                    $stGrad    = match($st->status) { 'open' => 'linear-gradient(135deg,#f59e0b,#f97316)', 'in_progress' => 'linear-gradient(135deg,#6366f1,#3b82f6)', default => 'linear-gradient(135deg,#10b981,#059669)' };
                    $stLabel   = match($st->status) { 'open' => 'باز', 'in_progress' => 'در بررسی', default => 'بسته' };
                    $stInitial = mb_substr($st->subject ?? '#', 0, 1);
                    $stTime    = \App\Helpers\Jalali::formatFa($st->updated_at, 'H:i');
                @endphp
                <a href="{{ route('admin.inbox.show', ['channel' => 'user', 'id' => $st->id]) }}"
                   class="msgr-conv-item {{ $stActive ? 'active' : '' }}"
                   x-show="!convSearch || '{{ addslashes($st->subject) }}'.includes(convSearch) || '{{ addslashes($st->customer_name ?? '') }}'.includes(convSearch) || '{{ $st->ticket_number }}'.includes(convSearch)">
                    <div class="msgr-conv-av" style="background:{{ $stGrad }}">{{ $stInitial }}</div>
                    <div class="msgr-conv-body">
                        <span class="msgr-conv-subj">{{ $st->subject }}</span>
                        <div class="msgr-conv-meta">
                            <span class="msgr-conv-sdot" style="background:{{ $stColor }}"></span>
                            <span>{{ $stLabel }}</span>
                            @if($st->priority === 'high')
                            <span class="bg-red-100 text-red-600 px-1 rounded text-[9px] font-bold">فوری</span>
                            @endif
                            <span class="mr-auto">{{ $stTime }}</span>
                        </div>
                        @if($st->customer_name)
                        <div class="text-[10px] text-slate-400 truncate mt-0.5">{{ $st->customer_name }}</div>
                        @endif
                    </div>
                </a>
                @empty
                <div class="p-6 text-center text-slate-400 text-xs">هیچ گفتگویی نیست</div>
                @endforelse
            </div>
        </div>

        {{-- ══ MIDDLE: Chat ═════════════════════════════════════════════════════ --}}
        <div class="msgr-chat">

            {{-- Header --}}
            <div class="msgr-chat-head">
                <a href="{{ route('admin.inbox.index') }}"
                   class="shrink-0 w-8 h-8 flex items-center justify-center rounded-lg hover:bg-slate-100 transition-all text-slate-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                </a>
                <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-sm font-bold shrink-0"
                     style="background:linear-gradient(135deg,#6366f1,#a855f7)">{{ mb_substr($ticket->subject, 0, 1) }}</div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-slate-800 truncate">{{ $ticket->subject }}</p>
                    <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                        <span class="font-mono bg-slate-100 text-slate-500 text-[10px] px-1.5 py-0.5 rounded">{{ $ticket->ticket_number }}</span>
                        @if($channel === 'platform')
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold text-white" style="background:linear-gradient(135deg,#8b5cf6,#6d28d9)">🏢 پلتفرم</span>
                        @else
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold text-white" style="background:linear-gradient(135deg,#0d9488,#0891b2)">👤 کاربر</span>
                        @endif
                        @if($ticket->status === 'open')
                        <span class="tkt-badge bg-yellow-50 text-yellow-700 border-yellow-200 !text-[10px] !py-0"><span class="pdot bg-yellow-400"></span>باز</span>
                        @elseif($ticket->status === 'in_progress')
                        <span class="tkt-badge bg-blue-50 text-blue-700 border-blue-200 !text-[10px] !py-0"><span class="pdot bg-blue-400"></span>در بررسی</span>
                        @else
                        <span class="tkt-badge bg-green-50 text-green-700 border-green-200 !text-[10px] !py-0">بسته</span>
                        @endif
                        @if($ticket->customer_name)
                        <span class="text-[10px] text-slate-500">{{ $ticket->customer_name }}</span>
                        @endif
                    </div>
                </div>
                <span class="text-xs text-slate-400 shrink-0 flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    زنده
                </span>
            </div>

            {{-- Messages --}}
            <div x-ref="chatMsgs" @scroll="handleScroll()"
                 class="msgr-chat-msgs chat-bg">

                {{-- Description as first message --}}
                @if($ticket->description)
                <div class="flex items-end gap-2 max-w-[78%] mb-4" style="align-self:flex-start">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0"
                         style="background:#0d9488">{{ mb_substr($ticket->customer_name ?? 'ک', 0, 1) }}</div>
                    <div>
                        <div class="bubble-customer px-4 py-2.5 shadow-sm">
                            <p class="text-xs font-bold mb-1 opacity-70">{{ $ticket->customer_name ?? 'کاربر' }}
                                <span class="text-[9px] bg-teal-100 text-teal-600 px-1.5 py-0.5 rounded-full mr-1">متن اولیه</span>
                            </p>
                            <p class="text-sm leading-relaxed whitespace-pre-wrap">{{ $ticket->description }}</p>
                        </div>
                        <span class="text-[10px] text-gray-400 mt-1 px-1 block">{{ \App\Helpers\Jalali::formatFa($ticket->created_at, 'Y/m/d H:i') }}</span>
                    </div>
                </div>
                @endif

                @php $lastDate = null; @endphp
                @foreach($ticket->replies as $reply)
                @php
                    $rDate     = $reply->created_at->toDateString();
                    $isStaff   = $reply->author_type === 'staff';
                    $isInt     = $reply->is_internal ?? false;
                    $onRight   = $isStaff || $isInt;
                    $rName     = $reply->user?->name ?? ($isStaff ? 'پشتیبان' : ($reply->customer_name ?? 'کاربر'));
                    $rColor    = $isInt ? '#f59e0b' : ($isStaff ? '#6366f1' : '#0d9488');
                    $rBubble   = $isInt ? 'bubble-internal' : ($isStaff ? 'bubble-staff' : 'bubble-customer');
                @endphp

                @if($rDate !== $lastDate)
                    @php $lastDate = $rDate; @endphp
                    <div class="flex justify-center my-3 w-full">
                        <span class="text-[10px] text-gray-400 bg-white/80 px-3 py-1 rounded-full shadow-sm">
                            @if($reply->created_at->isToday()) امروز
                            @elseif($reply->created_at->isYesterday()) دیروز
                            @else {{ \App\Helpers\Jalali::formatFa($reply->created_at, 'Y/m/d') }}
                            @endif
                        </span>
                    </div>
                @endif

                <div class="flex items-end gap-2 max-w-[78%] mb-4 {{ $onRight ? 'flex-row-reverse' : '' }}"
                     style="align-self: {{ $onRight ? 'flex-end' : 'flex-start' }}">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0 shadow-sm"
                         style="background-color: {{ $rColor }}">{{ mb_substr($rName, 0, 1) }}</div>
                    <div class="flex flex-col items-{{ $onRight ? 'end' : 'start' }}">
                        <div class="{{ $rBubble }} px-4 py-2.5 shadow-sm">
                            <p class="text-xs font-bold mb-1 opacity-70">
                                {{ $rName }}
                                @if($isInt)<span class="text-[9px] bg-amber-200/60 px-1.5 py-0.5 rounded-full mr-1">یادداشت</span>@endif
                                @if($isStaff && !$isInt)<span class="text-[9px] bg-indigo-100 text-indigo-600 px-1.5 py-0.5 rounded-full mr-1">پشتیبانی</span>@endif
                            </p>
                            <p class="text-sm leading-relaxed whitespace-pre-wrap">{{ $reply->message }}</p>
                            @if(!empty($reply->attachments) && is_array($reply->attachments))
                            @php
                                $rImgs = array_values(array_filter($reply->attachments, fn($a)=>!empty($a['url'])&&(preg_match('/^image\//i',$a['mime']??'')||preg_match('/\.(jpg|jpeg|png|gif|webp|bmp)$/i',$a['name']??''))));
                                $rDocs = array_values(array_filter($reply->attachments, fn($a)=>!empty($a['url'])&&!(preg_match('/^image\//i',$a['mime']??'')||preg_match('/\.(jpg|jpeg|png|gif|webp|bmp)$/i',$a['name']??''))));
                                $rIc   = count($rImgs);
                                $rMc   = $rIc===1?'n1':($rIc===2?'n2':($rIc===3?'n3':'n4'));
                            @endphp
                            @if($rImgs)
                            <div class="att-mosaic {{ $rMc }}">
                                @foreach(array_slice($rImgs,0,4) as $ri=>$rImg)
                                <div class="att-cell">
                                    <a href="{{ $rImg['url'] }}" target="_blank"><img src="{{ $rImg['url'] }}" loading="lazy" alt="{{ $rImg['name']??'تصویر' }}"></a>
                                    @if($ri===3&&$rIc>4)<div class="att-more">+{{ $rIc-3 }}</div>@endif
                                </div>
                                @endforeach
                            </div>
                            @endif
                            @foreach($rDocs as $rDoc)
                            <a href="{{ $rDoc['url'] }}" target="_blank" class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[10px] bg-white/60 hover:bg-white border border-gray-200 mt-1 mr-1">
                                <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372"/></svg>
                                {{ $rDoc['name']??'فایل' }}
                            </a>
                            @endforeach
                            @endif
                        </div>
                        <span class="text-[10px] text-gray-400 mt-1 px-1">{{ \App\Helpers\Jalali::formatFa($reply->created_at, 'H:i') }}</span>
                    </div>
                </div>
                @endforeach

                {{-- Compact new-message pill (absolutely positioned) --}}
                <div x-show="showScrollBtn" x-transition
                     @click="scrollDown()"
                     class="scroll-pill">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    پیام‌های جدید
                </div>
            </div>

            {{-- Reply form --}}
            @if($ticket->status !== 'closed')
            <div class="msgr-chat-foot p-3">

                {{-- Channel: user → show mode toggle --}}
                @if($channel === 'user')
                <div class="flex items-center gap-2 mb-2.5">
                    <span class="text-[11px] text-slate-500 font-medium">نوع پیام:</span>
                    <button type="button" @click="noteMode=false"
                            :class="!noteMode ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-500 border-slate-200'"
                            class="mode-chip border">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        پاسخ به کاربر
                    </button>
                    <button type="button" @click="noteMode=true"
                            :class="noteMode ? 'bg-amber-500 text-white border-amber-500' : 'bg-white text-slate-500 border-slate-200'"
                            class="mode-chip border">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                        یادداشت داخلی
                    </button>
                </div>
                @endif

                <form @submit.prevent="sendReply($el)"
                      action="{{ route('admin.inbox.reply', ['channel' => $channel, 'id' => $id]) }}"
                      method="POST" enctype="multipart/form-data">
                    @csrf
                    @if($channel === 'user')
                    <input type="hidden" name="is_internal" :value="noteMode ? '1' : '0'">
                    @endif

                    <div class="flex items-end gap-2">
                        <div class="flex-1">
                            {{-- Main textarea --}}
                            <div x-show="!noteMode">
                                <textarea name="message" x-model="message" rows="2" required
                                          placeholder="پیام خود را بنویسید... (Enter = ارسال، Shift+Enter = خط جدید)"
                                          @keydown.enter.prevent="if(!$event.shiftKey) sendReply($el.closest('form'))"
                                          :class="!noteMode ? 'border-slate-200 focus:ring-indigo-200 focus:border-indigo-300' : ''"
                                          class="w-full px-3.5 py-2.5 rounded-xl border text-sm resize-none bg-white transition-all focus:outline-none focus:ring-2"></textarea>
                            </div>
                            <div x-show="noteMode" x-cloak>
                                <textarea name="note_message" x-model="noteText" rows="2"
                                          placeholder="یادداشت داخلی — فقط پشتیبانان می‌بینند..."
                                          @keydown.enter.prevent="if(!$event.shiftKey) sendReply($el.closest('form'))"
                                          class="w-full px-3.5 py-2.5 rounded-xl border border-amber-300 bg-amber-50/60 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-amber-200 focus:border-amber-400 transition-all"></textarea>
                            </div>
                            <template x-if="noteMode">
                                <input type="hidden" name="message" :value="noteText">
                            </template>

                            {{-- Attachments + SMS row --}}
                            <div class="flex items-center gap-3 mt-1.5">
                                <label class="inline-flex items-center gap-1.5 cursor-pointer text-slate-500 hover:text-indigo-600 transition-colors text-[11px]">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13"/></svg>
                                    پیوست
                                    <input type="file" name="attachments[]" multiple x-ref="fileInput"
                                           accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.zip,.txt"
                                           class="hidden" @change="handleFileChange($event)">
                                </label>
                                @if($channel === 'user' && $smsEnabled && $endUser?->mobile)
                                <label class="inline-flex items-center gap-1.5 text-[11px] text-slate-500 cursor-pointer" x-show="!noteMode">
                                    <input type="checkbox" name="send_sms" value="1" class="rounded text-indigo-600">
                                    <svg class="w-3.5 h-3.5 text-green-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3"/></svg>
                                    ارسال پیامک
                                </label>
                                @endif
                            </div>
                            {{-- File preview --}}
                            <div x-show="selectedFiles.length > 0" x-transition class="mt-2 space-y-1.5">
                                {{-- Image mosaic preview --}}
                                <div x-show="imgFiles.length > 0" class="att-mosaic" :class="mosaicCls">
                                    <template x-for="(f,i) in imgFiles.slice(0,4)" :key="f._pos">
                                        <div class="att-cell" style="position:relative">
                                            <template x-if="f.preview">
                                                <a :href="f.preview" target="_blank"><img :src="f.preview" :alt="f.name"></a>
                                            </template>
                                            <template x-if="!f.preview">
                                                <div style="width:100%;height:100%;min-height:60px;background:#f1f5f9;display:flex;align-items:center;justify-content:center">
                                                    <span style="font-size:.6rem;color:#94a3b8">در حال بارگذاری...</span>
                                                </div>
                                            </template>
                                            <div x-show="i===3&&imgFiles.length>4" class="att-more" x-text="'+'+(imgFiles.length-3)"></div>
                                            <button type="button" @click.stop="removeFile(f._pos)"
                                                    style="position:absolute;top:4px;left:4px;width:18px;height:18px;border-radius:9999px;background:rgba(0,0,0,.55);color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;cursor:pointer;border:none;z-index:5">×</button>
                                        </div>
                                    </template>
                                </div>
                                {{-- Doc files --}}
                                <div x-show="docFiles.length > 0" class="flex flex-wrap gap-1">
                                    <template x-for="f in docFiles" :key="f._pos">
                                        <div class="flex items-center gap-1 bg-white border border-emerald-200 rounded-lg px-2 py-1" style="max-width:160px">
                                            <svg style="width:13px;height:13px;color:#94a3b8;flex-shrink:0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364L18.375 12.739"/></svg>
                                            <div style="min-width:0;flex:1"><div x-text="f.name" style="font-size:.65rem;font-weight:600;color:#334155;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"></div><div x-text="f.size" style="font-size:.6rem;color:#94a3b8"></div></div>
                                            <button type="button" @click.stop="removeFile(f._pos)" style="width:14px;height:14px;border-radius:9999px;background:#fee2e2;color:#ef4444;display:flex;align-items:center;justify-content:center;font-size:9px;cursor:pointer;border:none;flex-shrink:0">×</button>
                                        </div>
                                    </template>
                                </div>
                                <div class="flex items-center gap-1" style="font-size:.65rem;color:#059669;font-weight:600">
                                    <svg style="width:11px;height:11px" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span x-text="selectedFiles.length + ' فایل آماده ارسال'"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Send button --}}
                        <button type="submit" :disabled="isSending"
                                :class="noteMode ? 'from-amber-500 to-orange-500' : 'from-indigo-600 to-purple-600'"
                                class="flex items-center justify-center rounded-full w-10 h-10 bg-gradient-to-br text-white shadow-md hover:shadow-lg transition-all shrink-0 disabled:opacity-60">
                            <template x-if="!isSending">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                            </template>
                            <template x-if="isSending">
                                <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            </template>
                        </button>
                    </div>
                </form>
            </div>
            @else
            <div class="msgr-chat-foot p-4 text-center">
                <div class="inline-flex items-center gap-2 text-sm text-slate-500 bg-slate-50 px-4 py-2 rounded-xl border border-slate-200">
                    <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                    تیکت بسته شده است
                </div>
            </div>
            @endif
        </div>

        {{-- ══ RIGHT: Info panel ════════════════════════════════════════════════ --}}
        <div class="msgr-info">

            {{-- Ticket details --}}
            <div class="msgr-info-sec">
                <div class="msgr-info-label">جزئیات تیکت</div>
                <dl class="space-y-2 text-xs">
                    @if($ticket->customer_name)
                    <div class="flex justify-between items-center">
                        <dt class="text-slate-400">مخاطب</dt>
                        <dd class="font-semibold text-slate-700 truncate max-w-[120px]">{{ $ticket->customer_name }}</dd>
                    </div>
                    @endif
                    @if($endUser?->mobile)
                    <div class="flex justify-between items-center">
                        <dt class="text-slate-400">موبایل</dt>
                        <dd class="font-mono text-slate-600">{{ $endUser->mobile }}</dd>
                    </div>
                    @endif
                    <div class="flex justify-between items-center">
                        <dt class="text-slate-400">وضعیت</dt>
                        <dd>
                            @if($ticket->status === 'open') <span class="tkt-badge bg-yellow-50 text-yellow-700 border-yellow-200 !text-[10px]">باز</span>
                            @elseif($ticket->status === 'in_progress') <span class="tkt-badge bg-blue-50 text-blue-700 border-blue-200 !text-[10px]">در بررسی</span>
                            @else <span class="tkt-badge bg-green-50 text-green-700 border-green-200 !text-[10px]">بسته</span>
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between items-center">
                        <dt class="text-slate-400">اولویت</dt>
                        <dd class="font-semibold text-xs {{ $ticket->priority === 'high' ? 'text-red-600' : ($ticket->priority === 'low' ? 'text-slate-400' : 'text-blue-600') }}">
                            {{ $ticket->priority === 'high' ? '🔴 بالا' : ($ticket->priority === 'low' ? '⚪ کم' : '🔵 عادی') }}
                        </dd>
                    </div>
                    @if($ticket->assignee?->name)
                    <div class="flex justify-between items-center">
                        <dt class="text-slate-400">کارشناس</dt>
                        <dd class="font-semibold text-indigo-600 text-xs">{{ $ticket->assignee->name }}</dd>
                    </div>
                    @endif
                    <div class="flex justify-between items-center">
                        <dt class="text-slate-400">تاریخ</dt>
                        <dd class="text-slate-500">{{ \App\Helpers\Jalali::formatFa($ticket->created_at, 'Y/m/d') }}</dd>
                    </div>
                </dl>
            </div>

            @if($channel === 'user')

            {{-- Change status --}}
            <div class="msgr-info-sec">
                <div class="msgr-info-label">وضعیت</div>
                <form action="{{ route('admin.inbox.user.status', $id) }}" method="POST" class="flex gap-1.5">
                    @csrf @method('PATCH')
                    <select name="status" class="flex-1 px-2 py-1.5 rounded-lg border border-slate-200 text-xs bg-white focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                        <option value="open"        {{ $ticket->status==='open'        ? 'selected':'' }}>باز</option>
                        <option value="in_progress" {{ $ticket->status==='in_progress' ? 'selected':'' }}>در بررسی</option>
                        <option value="closed"      {{ $ticket->status==='closed'      ? 'selected':'' }}>بسته</option>
                    </select>
                    <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-indigo-600 text-white text-[11px] font-semibold hover:bg-indigo-700 transition-all">ثبت</button>
                </form>
            </div>

            {{-- Change priority --}}
            <div class="msgr-info-sec">
                <div class="msgr-info-label">اولویت</div>
                <form action="{{ route('admin.inbox.user.priority', $id) }}" method="POST" class="flex gap-1.5">
                    @csrf @method('PATCH')
                    <select name="priority" class="flex-1 px-2 py-1.5 rounded-lg border border-slate-200 text-xs bg-white focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                        <option value="high"   {{ $ticket->priority==='high'   ? 'selected':'' }}>🔴 بالا</option>
                        <option value="normal" {{ $ticket->priority==='normal' ? 'selected':'' }}>🔵 عادی</option>
                        <option value="low"    {{ $ticket->priority==='low'    ? 'selected':'' }}>⚪ کم</option>
                    </select>
                    <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-indigo-600 text-white text-[11px] font-semibold hover:bg-indigo-700 transition-all">ثبت</button>
                </form>
            </div>

            {{-- Assign agent --}}
            <div class="msgr-info-sec">
                <div class="msgr-info-label">تخصیص کارشناس</div>
                <form action="{{ route('admin.inbox.user.assign', $id) }}" method="POST" class="flex gap-1.5">
                    @csrf @method('PATCH')
                    <select name="assigned_to" class="flex-1 px-2 py-1.5 rounded-lg border border-slate-200 text-xs bg-white focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                        <option value="">— انتخاب —</option>
                        @foreach($agentUsers as $agent)
                        <option value="{{ $agent->id }}" {{ ($ticket->assigned_to ?? null) == $agent->id ? 'selected' : '' }}>{{ $agent->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-indigo-600 text-white text-[11px] font-semibold hover:bg-indigo-700 transition-all">ثبت</button>
                </form>
            </div>

            @endif

            {{-- Platform info --}}
            @if($channel === 'platform')
            <div class="msgr-info-sec">
                <div class="p-3 bg-purple-50 border border-purple-200 rounded-xl text-xs text-purple-700">
                    <div class="font-bold mb-1">🏢 تیکت پلتفرم</div>
                    <p class="text-purple-600 leading-relaxed">این تیکت به ادمین اصلی پلتفرم ارسال شده و از طریق API پاسخ می‌گیرد.</p>
                </div>
            </div>
            @endif

            {{-- Quick replies count --}}
            <div class="msgr-info-sec">
                <div class="msgr-info-label">آمار</div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-400">تعداد پیام‌ها</span>
                    <span class="font-bold text-indigo-600">{{ $ticket->replies->count() }}</span>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection
