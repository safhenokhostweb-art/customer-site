@extends('admin.layout')
@section('title', 'تیکت ' . $ticket->ticket_number)

@push('head')
@include('partials.ticket-styles')
@endpush

@section('content')
<div class="max-w-7xl mx-auto"
     data-poll-url="{{ route('admin.user-tickets.poll', $ticket->id) }}"
     data-poll-last-id="{{ $ticket->replies->last()?->id ?? 0 }}"
     x-data="{
        activeTab: 'chat',
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
        selectedFiles: [],
        _rawFiles: [],
        notifPerm: ('Notification' in window) ? Notification.permission : 'unsupported',
        scrollDown() {
            let el = this.$refs.chatMessages;
            if(el) { el.scrollTop = el.scrollHeight; this.showScrollBtn = false; }
        },
        handleScroll() {
            let el = this.$refs.chatMessages;
            if(!el) return;
            this.showScrollBtn = el.scrollHeight - el.scrollTop - el.clientHeight > 80;
        },
        enableNotifications() {
            if ('Notification' in window) Notification.requestPermission().then(p => { this.notifPerm = p; });
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
                    const hasCustomer = data.replies.some(r => r.author_type === 'customer');
                    if (window.CS && hasCustomer) window.CS.play('receive');
                }
                if (data.last_id && data.last_id > this.pollLastId) this.pollLastId = data.last_id;
            } catch(e) {}
        },
        async sendReply(form) {
            if (this.isSending) return;
            const text     = this.noteMode ? this.noteText.trim() : this.message.trim();
            const hasFiles = this._rawFiles && this._rawFiles.length > 0;
            if (!text && !hasFiles) return;
            this.isSending = true;
            try {
                const fd = new FormData(form);
                if (hasFiles) {
                    const inp = this.$refs.fileInput;
                    if (inp && (!inp.files || inp.files.length === 0)) {
                        this._rawFiles.forEach(f => fd.append('attachments[]', f, f.name));
                    }
                }
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
                    body: fd
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
            wrapper.className = 'flex items-end gap-2 max-w-[85%] sm:max-w-[75%] mb-4' + ((isStaff||isInternal) ? ' flex-row-reverse' : '');
            wrapper.style.cssText = 'align-self:' + ((isStaff||isInternal) ? 'flex-end' : 'flex-start') + ';opacity:0;transform:translateY(8px)';
            const av = document.createElement('div');
            av.className = 'w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0 shadow-sm';
            av.style.backgroundColor = color;
            av.textContent = initial;
            const bw = document.createElement('div');
            const bl = document.createElement('div');
            bl.className = bubbleCls + ' px-4 py-2.5 shadow-sm';
            const np = document.createElement('p');
            np.className = 'text-xs font-bold mb-1 opacity-70';
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
            bl.appendChild(np);
            bl.appendChild(mp);
            if (r.attachments && r.attachments.length > 0) {
                const isImgAtt = att => /^image\//i.test(att.mime||'') || /\.(jpg|jpeg|png|gif|webp|bmp)$/i.test(att.name||'');
                const attDiv = document.createElement('div');
                attDiv.className = 'flex flex-wrap gap-1.5 mt-2';
                r.attachments.filter(att => att.url || att.path).forEach(att => {
                    const aUrl = att.url || '#';
                    if (isImgAtt(att)) {
                        const a = document.createElement('a');
                        a.href = aUrl; a.target = '_blank'; a.className = 'block mt-1.5';
                        const img = document.createElement('img');
                        img.src = aUrl; img.loading = 'lazy'; img.alt = att.name || 'تصویر';
                        img.className = 'rounded-xl max-h-52 max-w-[220px] object-cover hover:opacity-90 transition-opacity shadow-sm border border-white/60 cursor-pointer';
                        a.appendChild(img); attDiv.appendChild(a);
                    } else {
                        const a = document.createElement('a');
                        a.href = aUrl; a.target = '_blank';
                        a.className = 'inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[10px] bg-white/60 hover:bg-white border border-gray-200 transition-all';
                        a.textContent = att.name || 'فایل';
                        attDiv.appendChild(a);
                    }
                });
                if (attDiv.hasChildNodes()) bl.appendChild(attDiv);
            }
            const ts = document.createElement('span');
            ts.className = 'text-[10px] text-gray-400 mt-1 px-1';
            ts.textContent = time;
            bw.appendChild(bl);
            bw.appendChild(ts);
            wrapper.appendChild(av);
            wrapper.appendChild(bw);
            container.appendChild(wrapper);
            requestAnimationFrame(() => { wrapper.style.transition = 'opacity .3s,transform .3s'; wrapper.style.opacity = '1'; wrapper.style.transform = 'translateY(0)'; });
            container.scrollTop = container.scrollHeight;
            if (r.author_type === 'customer') { this.notify(r.author_name || 'کاربر', r.message); }
        },
        notify(from, body) {
            this.toastText = 'پیام جدید از ' + from;
            this.showToast = true;
            clearTimeout(this._toastTimer);
            this._toastTimer = setTimeout(() => { this.showToast = false; }, 6000);
            if ('Notification' in window && Notification.permission === 'granted' && document.hidden) {
                new Notification('پشتیبانی — پیام جدید', { body: (body||'').substring(0,120), icon:'/favicon.ico', dir:'rtl' });
            }
        }
     }">

    {{-- Real-time toast --}}
    <div x-show="showToast" x-transition
         class="fixed top-5 left-1/2 -translate-x-1/2 z-50 flex items-center gap-3 bg-indigo-600 text-white px-5 py-3 rounded-2xl shadow-xl text-sm font-medium"
         style="display:none">
        <svg class="w-4 h-4 shrink-0 animate-bounce" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        <span x-text="toastText"></span>
        <button @click="showToast=false" class="mr-2 opacity-70 hover:opacity-100">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    {{-- ── Page Header ── --}}
    <div class="flex items-center gap-4 mb-6 flex-wrap">
        <a href="{{ route('admin.user-tickets.index') }}"
           class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-medium text-gray-600 bg-white hover:bg-gray-50 border border-gray-200 transition-all hover:shadow-sm shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            بازگشت به لیست
        </a>
        <div class="flex-1 min-w-0">
            <h2 class="text-xl font-bold text-gray-900 truncate">{{ $ticket->subject }}</h2>
            <div class="flex items-center gap-3 mt-1 flex-wrap">
                <span class="text-xs text-gray-500 font-mono">{{ $ticket->ticket_number }}</span>
                <span class="text-gray-300">·</span>
                @if($ticket->status === 'open')
                    <span class="tkt-badge bg-yellow-50 text-yellow-700 border-yellow-200"><span class="w-2 h-2 rounded-full bg-yellow-500 animate-pulse"></span>باز</span>
                @elseif($ticket->status === 'in_progress')
                    <span class="tkt-badge bg-blue-50 text-blue-700 border-blue-200"><span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>در حال بررسی</span>
                @else
                    <span class="tkt-badge bg-green-50 text-green-700 border-green-200"><svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>بسته</span>
                @endif
                @if($ticket->priority === 'high')
                    <span class="tkt-badge bg-red-50 text-red-600 border-red-200"><svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>اولویت بالا</span>
                @elseif($ticket->priority === 'low')
                    <span class="tkt-badge bg-gray-50 text-gray-500 border-gray-200">اولویت کم</span>
                @else
                    <span class="tkt-badge bg-blue-50 text-blue-600 border-blue-200">اولویت عادی</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Flash Messages --}}
    @foreach(['success' => 'green', 'error' => 'red'] as $flashKey => $fc)
    @if(session($flashKey))
    <div class="glass-card mb-4 p-4 border-r-4 border-r-{{ $fc }}-400 flex items-center gap-3"
         x-data="{ show: true }" x-show="show" x-transition>
        <svg class="w-5 h-5 text-{{ $fc }}-600 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span class="text-sm text-{{ $fc }}-800 font-medium flex-1">{{ session($flashKey) }}</span>
        <button @click="show=false" class="text-{{ $fc }}-400 hover:text-{{ $fc }}-600 shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    @endif
    @endforeach

    {{-- ── Main Grid ── --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

        {{-- ═══ LEFT: Chat + Notes ════════════════════════════ --}}
        <div class="xl:col-span-2 flex flex-col">

            {{-- Tabs --}}
            <div class="flex items-end gap-1 border-b border-gray-200 overflow-x-auto pb-0">
                <button class="tkt-tab" :class="activeTab==='chat' && 'active'" @click="activeTab='chat'">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    گفتگو
                    <span class="tkt-tab-badge">{{ $ticket->replies->where('is_internal',false)->count() + 1 }}</span>
                </button>
                <button class="tkt-tab" :class="activeTab==='notes' && 'active'" @click="activeTab='notes'">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
                    یادداشت داخلی
                    @if($ticket->replies->where('is_internal',true)->count())
                    <span class="tkt-tab-badge bg-amber-500">{{ $ticket->replies->where('is_internal',true)->count() }}</span>
                    @endif
                </button>
                <button class="tkt-tab" :class="activeTab==='timeline' && 'active'" @click="activeTab='timeline'">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    تاریخچه
                </button>
            </div>

            {{-- TAB: Chat ── --}}
            <div x-show="activeTab==='chat'" class="flex-1 flex flex-col" style="min-height:520px">

                {{-- Chat Header --}}
                <div class="bg-white border border-gray-200 border-b-0 p-4">
                    <div class="flex items-center gap-3 flex-wrap">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-white text-xs font-bold shrink-0">
                                {{ mb_substr($ticket->customer_name ?? $ticket->customer_mobile ?? 'ک', 0, 1) }}
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">{{ $ticket->customer_name ?? 'کاربر' }}</p>
                                @if($ticket->customer_mobile)
                                <p class="text-xs text-gray-400" dir="ltr">{{ $ticket->customer_mobile }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="mr-auto flex items-center gap-2 flex-wrap">
                            <div class="flex items-center gap-1.5 text-xs text-gray-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse"></span>
                                <span>به‌روزرسانی خودکار هر ۴ ثانیه</span>
                            </div>
                            <button x-show="notifPerm === 'default'" @click="enableNotifications()"
                                    class="text-[10px] text-indigo-600 hover:text-indigo-800 border border-indigo-200 rounded-lg px-2 py-1 transition-all">
                                🔔 فعال‌سازی اعلان
                            </button>
                            <span x-show="notifPerm === 'granted'" class="text-[10px] text-green-600">🔔 اعلان فعال</span>
                        </div>
                    </div>
                </div>

                {{-- Messages --}}
                <div class="bg-white border border-gray-200 border-t-0 flex-1 flex flex-col">
                    <div x-ref="chatMessages"
                         @scroll="handleScroll"
                         class="flex-1 overflow-y-auto p-4 space-y-1 chat-bg flex flex-col"
                         style="max-height:460px; scroll-behavior:smooth">

                        @php $custInitial = mb_substr($ticket->customer_name ?? $ticket->customer_mobile ?? 'ک', 0, 1); @endphp

                        {{-- Initial message --}}
                        <div class="flex items-end gap-2 max-w-[85%] sm:max-w-[75%] mb-4" style="align-self:flex-start">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0 shadow-sm" style="background-color:var(--ui-indigo)">{{ $custInitial }}</div>
                            <div>
                                <div class="bubble-customer px-4 py-2.5 shadow-sm">
                                    <p class="text-xs font-bold mb-1 opacity-80">{{ $ticket->customer_name ?? $ticket->customer_mobile ?? 'کاربر' }}</p>
                                    <p class="text-sm leading-relaxed whitespace-pre-wrap">{{ $ticket->description }}</p>
                                </div>
                                <span class="text-[10px] text-gray-400 mt-1 px-1 block">{{ \App\Helpers\Jalali::formatFa($ticket->created_at, 'Y/m/d H:i') }}</span>
                            </div>
                        </div>

                        @php $lastDate = null; @endphp
                        @foreach($ticket->replies->where('is_internal', false) as $reply)
                            @php
                                $replyDate  = $reply->created_at->toDateString();
                                $isStaff    = $reply->author_type === 'staff';
                                $authorName = $isStaff ? ($reply->user?->name ?? 'پشتیبان') : ($reply->customer_name ?? $ticket->customer_name ?? 'کاربر');
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

                            <div class="flex items-end gap-2 max-w-[85%] sm:max-w-[75%] mb-4 {{ $isStaff ? 'flex-row-reverse' : '' }}"
                                 style="align-self:{{ $isStaff ? 'flex-end' : 'flex-start' }}">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0 shadow-sm"
                                     style="background-color:{{ $isStaff ? 'var(--ui-success)' : 'var(--ui-indigo)' }}">
                                    {{ mb_substr($authorName, 0, 1) }}
                                </div>
                                <div>
                                    <div class="{{ $isStaff ? 'bubble-staff' : 'bubble-customer' }} px-4 py-2.5 shadow-sm">
                                        <p class="text-xs font-bold mb-1 opacity-70">
                                            {{ $authorName }}
                                            @if($isStaff)
                                            <span class="text-[9px] bg-green-200/60 px-1.5 py-0.5 rounded-full mr-1">پشتیبان</span>
                                            @endif
                                        </p>
                                        <p class="text-sm leading-relaxed whitespace-pre-wrap">{{ $reply->message }}</p>
                                        @if($reply->attachments && is_array($reply->attachments))
                                        <div class="flex flex-wrap gap-1.5 mt-2">
                                            @foreach($reply->attachments as $file)
                                            @if(isset($file['url']) || isset($file['path']))
                                            @php
                                                $attUrl  = $file['url'] ?? asset('storage/' . $file['path']);
                                                $attName = $file['name'] ?? 'فایل';
                                                $attIsImg = preg_match('/^image\//i', $file['mime'] ?? '') || preg_match('/\.(jpg|jpeg|png|gif|webp|bmp)$/i', $attName);
                                            @endphp
                                            @if($attIsImg)
                                            <a href="{{ $attUrl }}" target="_blank" class="block mt-1">
                                                <img src="{{ $attUrl }}" loading="lazy" alt="{{ $attName }}"
                                                     class="rounded-xl max-h-52 max-w-[220px] object-cover hover:opacity-90 transition-opacity shadow-sm border border-white/60 cursor-pointer">
                                            </a>
                                            @else
                                            <a href="{{ $attUrl }}" target="_blank"
                                               class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[10px] bg-white/60 hover:bg-white border border-gray-200 transition-all">
                                                <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13"/></svg>
                                                <span class="truncate max-w-[120px]">{{ $attName }}</span>
                                            </a>
                                            @endif
                                            @endif
                                            @endforeach
                                        </div>
                                        @endif
                                    </div>
                                    <span class="text-[10px] text-gray-400 mt-1 px-1 {{ $isStaff ? 'text-left block' : '' }}">
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

                    {{-- Reply form --}}
                    @if($ticket->status !== 'closed')
                    <div class="border-t border-gray-200 bg-white/90 p-3">
                        <div class="flex items-center gap-2 mb-2.5">
                            <button @click="noteMode=false"
                                    :class="!noteMode ? 'bg-indigo-100 text-indigo-700 border-indigo-300' : 'bg-white text-gray-500 border-gray-200'"
                                    class="flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg border transition-all">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                پاسخ به کاربر
                            </button>
                            <button @click="noteMode=true"
                                    :class="noteMode ? 'bg-amber-100 text-amber-700 border-amber-300' : 'bg-white text-gray-500 border-gray-200'"
                                    class="flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg border transition-all">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
                                یادداشت داخلی
                            </button>
                            <span x-show="noteMode" class="text-[10px] text-amber-600 font-medium">فقط کارشناسان می‌بینند</span>
                        </div>

                        <form method="POST"
                              action="{{ route('admin.user-tickets.reply', $ticket->id) }}"
                              @submit.prevent="sendReply($event.target)"
                              enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="is_internal" :value="noteMode ? '1' : '0'">
                            @if($smsEnabled && $endUser?->mobile)
                            <input type="hidden" name="send_sms" x-bind:value="noteMode ? '0' : '1'">
                            @endif
                            <div class="flex items-end gap-2">
                                <div class="flex-1">
                                    <textarea name="message"
                                              x-model="noteMode ? noteText : message"
                                              :class="noteMode ? 'note-area' : ''"
                                              class="w-full rounded-2xl border border-gray-200 resize-none py-3 px-4 text-sm transition-all bg-white/85 focus:outline-none focus:ring-2 focus:ring-indigo-200 focus:border-indigo-300"
                                              rows="2" required maxlength="5000"
                                              :placeholder="noteMode ? 'یادداشت داخلی (فقط کارشناسان می‌بینند)...' : 'پاسخ خود را بنویسید...'"
                                              @keydown.enter.prevent="if(!$event.shiftKey) sendReply($el.closest('form'))"></textarea>
                                    <small class="text-[10px] text-gray-400 mt-1 block mr-2">اینتر = ارسال | Shift+Enter = خط جدید</small>
                                    <input type="file" name="attachments[]" multiple
                                           class="mt-2 text-xs text-gray-500 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700 file:px-3 file:py-1.5 file:text-xs file:font-medium hover:file:bg-indigo-100"
                                           accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.zip">
                                </div>
                                <button type="submit"
                                        :disabled="isSending || (!noteMode && !message.trim()) || (noteMode && !noteText.trim())"
                                        class="flex items-center justify-center rounded-full p-3 min-w-[48px] h-[48px] transition-all shrink-0"
                                        :class="(isSending||(!noteMode&&!message.trim())||(noteMode&&!noteText.trim())) ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : (noteMode ? 'bg-amber-400 text-white shadow-md hover:bg-amber-500' : 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-md hover:shadow-lg')">
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
                    <div class="border-t border-gray-200 bg-gray-50/80 p-4 text-center">
                        <span class="text-sm text-gray-500">این تیکت بسته شده است.</span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- TAB: Internal Notes ── --}}
            <div x-show="activeTab==='notes'" class="flex-1" style="min-height:520px">
                <div class="glass-card p-5 h-full flex flex-col gap-4 mt-2">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-xl bg-amber-100 flex items-center justify-center text-amber-600 shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-800">یادداشت‌های داخلی</h3>
                            <p class="text-xs text-gray-400">فقط کارشناسان این یادداشت‌ها را می‌بینند</p>
                        </div>
                    </div>
                    @php $internalNotes = $ticket->replies->where('is_internal', true); @endphp
                    @if($internalNotes->isEmpty())
                    <div class="flex-1 flex flex-col items-center justify-center text-gray-400 py-8">
                        <svg class="w-10 h-10 mb-3 opacity-30" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
                        <p class="text-sm">هیچ یادداشت داخلی ثبت نشده</p>
                        <button @click="activeTab='chat'; noteMode=true"
                                class="mt-3 text-xs text-amber-600 hover:text-amber-700 font-medium underline underline-offset-2">
                            + افزودن یادداشت داخلی
                        </button>
                    </div>
                    @else
                    <div class="flex-1 space-y-3 overflow-y-auto">
                        @foreach($internalNotes as $note)
                        <div class="bubble-internal px-4 py-3">
                            <div class="flex items-center justify-between mb-1.5">
                                <p class="text-xs font-bold text-amber-800">{{ $note->user?->name ?? 'کارشناس' }}</p>
                                <span class="text-[10px] text-amber-600">{{ \App\Helpers\Jalali::formatFa($note->created_at, 'Y/m/d H:i') }}</span>
                            </div>
                            <p class="text-sm leading-relaxed whitespace-pre-wrap text-amber-900">{{ $note->message }}</p>
                        </div>
                        @endforeach
                    </div>
                    <button @click="activeTab='chat'; noteMode=true"
                            class="text-xs text-amber-600 hover:text-amber-700 font-medium text-center underline underline-offset-2">
                        + افزودن یادداشت داخلی
                    </button>
                    @endif
                </div>
            </div>

            {{-- TAB: Timeline ── --}}
            <div x-show="activeTab==='timeline'" class="flex-1" style="min-height:520px">
                <div class="glass-card p-5 h-full mt-2">
                    <h3 class="text-sm font-bold text-gray-800 flex items-center gap-2 mb-5">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        تاریخچه فعالیت
                    </h3>
                    <div class="space-y-2">
                        <div class="timeline-item">
                            <div class="timeline-dot bg-indigo-500"></div>
                            <div class="pr-3">
                                <p class="text-xs font-semibold text-gray-700">تیکت ایجاد شد</p>
                                <p class="text-[10px] text-gray-400 mt-0.5">{{ \App\Helpers\Jalali::formatFa($ticket->created_at, 'Y/m/d H:i') }}</p>
                                <p class="text-[10px] text-gray-500 mt-0.5">توسط: {{ $ticket->customer_name ?? $ticket->customer_mobile ?? 'کاربر' }}</p>
                            </div>
                        </div>
                        @if($ticket->assignee)
                        <div class="timeline-item">
                            <div class="timeline-dot bg-purple-500"></div>
                            <div class="pr-3">
                                <p class="text-xs font-semibold text-gray-700">تخصیص به کارشناس</p>
                                <p class="text-[10px] text-gray-400 mt-0.5">{{ $ticket->assignee->name }}</p>
                            </div>
                        </div>
                        @endif
                        @foreach($ticket->replies as $reply)
                        <div class="timeline-item">
                            <div class="timeline-dot {{ $reply->is_internal ? 'bg-amber-400' : ($reply->author_type === 'staff' ? 'bg-green-500' : 'bg-blue-400') }}"></div>
                            <div class="pr-3">
                                <p class="text-xs font-semibold text-gray-700">
                                    @if($reply->is_internal) یادداشت داخلی
                                    @elseif($reply->author_type === 'staff') پاسخ پشتیبانی
                                    @else پاسخ کاربر
                                    @endif
                                </p>
                                <p class="text-[10px] text-gray-400 mt-0.5">{{ \App\Helpers\Jalali::formatFa($reply->created_at, 'Y/m/d H:i') }} — {{ $reply->user?->name ?? $reply->customer_name ?? 'کاربر' }}</p>
                            </div>
                        </div>
                        @endforeach
                        @if($ticket->status === 'closed')
                        <div class="timeline-item">
                            <div class="timeline-dot bg-emerald-500"></div>
                            <div class="pr-3">
                                <p class="text-xs font-semibold text-gray-700">تیکت بسته شد</p>
                                <p class="text-[10px] text-gray-400 mt-0.5">{{ \App\Helpers\Jalali::formatFa($ticket->updated_at, 'Y/m/d H:i') }}</p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>

        {{-- ═══ RIGHT: Sidebar ════════════════════════════════ --}}
        <div class="space-y-4">

            {{-- Ticket Info --}}
            <div class="glass-card sidebar-section">
                <h3 class="sidebar-title">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg>
                    اطلاعات تیکت
                </h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-2">
                        <dt class="text-gray-400 text-xs">شماره</dt>
                        <dd class="font-mono font-bold text-xs text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded">{{ $ticket->ticket_number }}</dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt class="text-gray-400 text-xs">مسئول</dt>
                        <dd class="font-semibold text-xs text-gray-700">{{ $ticket->assignee?->name ?? 'تخصیص نیافته' }}</dd>
                    </div>
                    @if($ticket->customer_mobile)
                    <div class="flex justify-between gap-2">
                        <dt class="text-gray-400 text-xs">موبایل</dt>
                        <dd class="font-semibold text-xs text-gray-700 truncate max-w-[150px]" dir="ltr">{{ $ticket->customer_mobile }}</dd>
                    </div>
                    @endif
                    @if($ticket->category)
                    <div class="flex justify-between gap-2">
                        <dt class="text-gray-400 text-xs">دسته‌بندی</dt>
                        <dd class="font-semibold text-xs text-gray-700">{{ ['technical'=>'فنی','billing'=>'مالی','product'=>'محصول','other'=>'سایر'][$ticket->category] ?? $ticket->category }}</dd>
                    </div>
                    @endif
                    <div class="flex justify-between gap-2">
                        <dt class="text-gray-400 text-xs">تاریخ ایجاد</dt>
                        <dd class="font-semibold text-xs text-gray-700">{{ \App\Helpers\Jalali::formatFa($ticket->created_at, 'Y/m/d') }}</dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt class="text-gray-400 text-xs">پیام‌ها</dt>
                        <dd class="font-bold text-xs text-indigo-700">{{ $ticket->replies->count() + 1 }}</dd>
                    </div>
                </dl>
            </div>

            {{-- User Info --}}
            @if($endUser)
            <div class="glass-card sidebar-section">
                <h3 class="sidebar-title">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                    اطلاعات کاربر
                </h3>
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-white font-bold text-sm shrink-0">
                        {{ mb_substr($endUser->name ?? $endUser->mobile ?? 'ک', 0, 1) }}
                    </div>
                    <div class="min-w-0">
                        <div class="font-semibold text-sm text-gray-800 truncate">{{ $endUser->name ?? '—' }}</div>
                        <div class="text-xs text-gray-400 truncate" dir="ltr">{{ $endUser->email ?? '' }}</div>
                    </div>
                </div>
                @if($endUser->mobile)
                <div class="flex items-center gap-2 text-xs text-gray-600 bg-gray-50 rounded-lg px-3 py-2">
                    <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    <span dir="ltr">{{ $endUser->mobile }}</span>
                </div>
                @endif
            </div>
            @endif

            {{-- Status Control --}}
            <div class="glass-card sidebar-section">
                <h3 class="sidebar-title">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    وضعیت تیکت
                </h3>
                <div class="space-y-2">
                    @if($ticket->status === 'open')
                    <form method="POST" action="{{ route('admin.user-tickets.status', $ticket->id) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="in_progress">
                        <button class="w-full flex items-center justify-center gap-2 bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs py-2.5 rounded-xl transition font-semibold border border-blue-200">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3l14 9-14 9V3z"/></svg>
                            شروع بررسی
                        </button>
                    </form>
                    @endif
                    @if($ticket->status !== 'closed')
                    <form method="POST" action="{{ route('admin.user-tickets.status', $ticket->id) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="closed">
                        <button class="w-full flex items-center justify-center gap-2 bg-gray-50 hover:bg-gray-100 text-gray-700 text-xs py-2.5 rounded-xl transition font-semibold border border-gray-200">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            بستن تیکت
                        </button>
                    </form>
                    @else
                    <form method="POST" action="{{ route('admin.user-tickets.status', $ticket->id) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="open">
                        <button class="w-full flex items-center justify-center gap-2 bg-yellow-50 hover:bg-yellow-100 text-yellow-700 text-xs py-2.5 rounded-xl transition font-semibold border border-yellow-200">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                            بازگشایی مجدد
                        </button>
                    </form>
                    @endif
                </div>
            </div>

            {{-- Priority Control --}}
            <div class="glass-card sidebar-section">
                <h3 class="sidebar-title">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4"/></svg>
                    اولویت
                </h3>
                <form method="POST" action="{{ route('admin.user-tickets.priority', $ticket->id) }}">
                    @csrf @method('PATCH')
                    <select name="priority" class="tkt-form-input mb-2">
                        <option value="high"   @selected(($ticket->priority ?? '') === 'high')>🔴 بالا</option>
                        <option value="normal" @selected(($ticket->priority ?? 'normal') === 'normal')>🔵 عادی</option>
                        <option value="low"    @selected(($ticket->priority ?? '') === 'low')>⚪ کم</option>
                    </select>
                    <button class="w-full text-xs py-2 rounded-xl bg-gray-50 hover:bg-gray-100 text-gray-700 border border-gray-200 font-semibold transition">
                        ثبت اولویت
                    </button>
                </form>
            </div>

            {{-- Agent Assignment --}}
            <div class="glass-card sidebar-section">
                <h3 class="sidebar-title">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                    تخصیص کارشناس
                </h3>
                @if($ticket->assignee)
                <div class="flex items-center gap-2 mb-3 p-2 bg-indigo-50 rounded-xl border border-indigo-100">
                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-white text-xs font-bold shrink-0">
                        {{ mb_substr($ticket->assignee->name, 0, 1) }}
                    </div>
                    <span class="text-xs font-semibold text-indigo-700 flex-1">{{ $ticket->assignee->name }}</span>
                    <span class="text-[10px] text-indigo-400">مسئول فعلی</span>
                </div>
                @endif
                <form method="POST" action="{{ route('admin.user-tickets.assign', $ticket->id) }}">
                    @csrf @method('PATCH')
                    <select name="assigned_to" class="tkt-form-input mb-2">
                        <option value="">بدون مسئول</option>
                        @foreach($agentUsers as $agent)
                            <option value="{{ $agent->id }}" {{ $ticket->assigned_to == $agent->id ? 'selected' : '' }}>
                                {{ $agent->name }}
                            </option>
                        @endforeach
                    </select>
                    <button class="w-full text-xs py-2 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 font-semibold transition">
                        ثبت تخصیص
                    </button>
                </form>
            </div>

            {{-- SMS Panel --}}
            @if($smsEnabled && $endUser?->mobile)
            <div class="glass-card sidebar-section"
                 x-data="{ open: false, phone: '{{ $endUser->mobile }}', smsMsg: '', loading: false }">
                <button @click="open=true"
                        class="w-full flex items-center justify-center gap-2 bg-purple-50 hover:bg-purple-100 text-purple-700 text-xs py-2.5 rounded-xl transition font-semibold border border-purple-200">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                    ارسال پیامک به کاربر
                </button>
                <div x-show="open" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @keydown.escape.window="open=false">
                    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6" @click.stop>
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="font-bold text-gray-900 flex items-center gap-2">
                                <div class="w-8 h-8 rounded-xl bg-purple-100 flex items-center justify-center text-purple-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                                </div>
                                ارسال پیامک
                            </h4>
                            <button @click="open=false" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100 transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="flex flex-wrap gap-1.5 mb-3">
                            <button @click="smsMsg='تیکت شما #{{ $ticket->ticket_number }} پاسخ دریافت کرد. لطفاً وارد پنل کاربری شوید.'"
                                    class="text-xs px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg border border-indigo-200 transition font-medium">
                                پاسخ جدید
                            </button>
                            <button @click="smsMsg='تیکت شما {{ $ticket->ticket_number }} بسته شد. در صورت نیاز تیکت جدید ثبت کنید.'"
                                    class="text-xs px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg border border-indigo-200 transition font-medium">
                                بسته شدن تیکت
                            </button>
                        </div>
                        <form method="POST" action="{{ route('admin.user-tickets.reply', $ticket->id) }}">
                            @csrf
                            <input type="hidden" name="message" x-bind:value="'[پیامک] ' + smsMsg">
                            <input type="hidden" name="send_sms" value="1">
                            <div class="mb-3">
                                <label class="block text-xs font-semibold text-gray-700 mb-1.5">شماره موبایل</label>
                                <input type="text" x-model="phone" readonly
                                       class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50" dir="ltr">
                            </div>
                            <div class="mb-4">
                                <label class="block text-xs font-semibold text-gray-700 mb-1.5">متن پیامک</label>
                                <textarea x-model="smsMsg" required maxlength="500" rows="3"
                                          class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm resize-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition"
                                          placeholder="متن پیامک..."></textarea>
                                <p class="text-[10px] text-gray-400 mt-1" x-text="`${smsMsg.length}/500 کاراکتر`"></p>
                            </div>
                            <div class="flex gap-2">
                                <button type="submit"
                                        :disabled="!smsMsg.trim()"
                                        class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-white transition"
                                        :class="!smsMsg.trim() ? 'bg-gray-300 cursor-not-allowed' : 'bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 shadow-md'">
                                    ارسال
                                </button>
                                <button type="button" @click="open=false"
                                        class="px-4 py-2.5 rounded-xl text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition">
                                    انصراف
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endif

            {{-- Delete --}}
            <form method="POST" action="{{ route('admin.user-tickets.destroy', $ticket->id) }}"
                  x-data
                  @submit.prevent="if(confirm('آیا از حذف تیکت {{ $ticket->ticket_number }} اطمینان دارید؟\nاین عمل قابل بازگشت نیست.')) $el.submit()"
                  class="mt-2">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold text-red-600 bg-red-50 hover:bg-red-100 border border-red-200 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                    حذف این تیکت
                </button>
            </form>

            {{-- Back --}}
            <a href="{{ route('admin.user-tickets.index') }}" class="btn-ghost w-full !justify-center !text-xs !py-2.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                بازگشت به لیست
            </a>

        </div>
    </div>

</div>
@endsection
