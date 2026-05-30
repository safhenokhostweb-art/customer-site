@extends('admin.layout')
@section('title', $ticket->ticket_number . ' — صندوق پشتیبانی')

@push('head')
@include('partials.ticket-styles')
<style>
.channel-badge-platform { background: linear-gradient(135deg,#8b5cf6,#6d28d9); }
.channel-badge-user      { background: linear-gradient(135deg,#0d9488,#0891b2); }
#chat-messages { display: flex; flex-direction: column; gap: 0; }
</style>
@endpush

@section('content')
<div class="max-w-6xl mx-auto"
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

        scrollDown() {
            const el = this.$refs.chatMessages;
            if (el) { el.scrollTop = el.scrollHeight; this.showScrollBtn = false; }
        },
        handleScroll() {
            const el = this.$refs.chatMessages;
            if (!el) return;
            this.showScrollBtn = el.scrollHeight - el.scrollTop - el.clientHeight > 80;
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
                    const hasOther = data.replies.some(r => r.author_type !== 'staff');
                    if (window.CS && hasOther) window.CS.play('receive');
                }
                if (data.last_id > this.pollLastId) this.pollLastId = data.last_id;
            } catch (e) {}
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
                if (!res.ok) { const e = await res.json().catch(() => ({})); alert(e.message || 'خطا در ارسال'); return; }
                const data = await res.json();
                if (data.reply) {
                    this.appendReply(data.reply);
                    if (data.reply.id > this.pollLastId) this.pollLastId = data.reply.id;
                    if (window.CS && !data.reply.is_internal) window.CS.play('send');
                }
                this.noteMode ? (this.noteText = '') : (this.message = '');
            } catch (e) { console.error(e); }
            finally { this.isSending = false; }
        },
        appendReply(r) {
            if (r.id && this.seenIds[r.id]) return;
            if (r.id) this.seenIds[r.id] = true;
            const container = this.$refs.chatMessages;
            if (!container) return;
            const isStaff    = r.author_type === 'staff';
            const isInternal = !!r.is_internal;
            const name = r.author_name || (isStaff ? 'پشتیبان' : 'کاربر');
            const d    = new Date(r.created_at);
            const time = d.toLocaleTimeString('fa-IR', { hour: '2-digit', minute: '2-digit' });
            const bubbleCls = isInternal ? 'bubble-internal' : (isStaff ? 'bubble-staff' : 'bubble-customer');
            const color = isInternal ? 'var(--ui-warning)' : (isStaff ? '#6366f1' : 'var(--ui-success)');
            const wrapper = document.createElement('div');
            wrapper.className = 'flex items-end gap-2 max-w-[80%] mb-4' + ((isStaff || isInternal) ? ' flex-row-reverse' : '');
            wrapper.style.cssText = 'align-self:' + ((isStaff || isInternal) ? 'flex-end' : 'flex-start') + ';opacity:0;transform:translateY(6px);transition:all .25s';
            const av = document.createElement('div');
            av.className = 'w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0 shadow-sm';
            av.style.backgroundColor = color;
            av.textContent = name.charAt(0);
            const bwrap = document.createElement('div');
            bwrap.style.cssText = 'display:flex;flex-direction:column;align-items:' + ((isStaff || isInternal) ? 'flex-end' : 'flex-start');
            const bubble = document.createElement('div');
            bubble.className = bubbleCls + ' px-4 py-2.5 shadow-sm';
            const nameLine = document.createElement('p');
            nameLine.className = 'text-xs font-bold mb-1 opacity-70';
            nameLine.textContent = name;
            if (isInternal) {
                const tag = document.createElement('span');
                tag.className = 'text-[9px] bg-amber-200/60 px-1.5 py-0.5 rounded-full mr-1';
                tag.textContent = 'یادداشت';
                nameLine.appendChild(tag);
            }
            const msgLine = document.createElement('p');
            msgLine.className = 'text-sm leading-relaxed whitespace-pre-wrap';
            msgLine.textContent = r.message || '';
            bubble.appendChild(nameLine);
            bubble.appendChild(msgLine);
            if (r.attachments?.length) {
                const isImg = att => /^image\//i.test(att.mime||'') || /\.(jpg|jpeg|png|gif|webp|bmp)$/i.test(att.name||'');
                const attDiv = document.createElement('div');
                attDiv.className = 'flex flex-wrap gap-1.5 mt-2';
                r.attachments.filter(att => att.url).forEach(att => {
                    if (isImg(att)) {
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
                if (attDiv.hasChildNodes()) bubble.appendChild(attDiv);
            }
            const ts = document.createElement('span');
            ts.className = 'text-[10px] text-gray-400 mt-1 px-1';
            ts.textContent = time;
            bwrap.appendChild(bubble);
            bwrap.appendChild(ts);
            wrapper.appendChild(av);
            wrapper.appendChild(bwrap);
            container.appendChild(wrapper);
            requestAnimationFrame(() => { wrapper.style.opacity='1'; wrapper.style.transform='translateY(0)'; });
            this.scrollDown();
        }
    }">

    {{-- ─── Flash ──────────────────────────────────────────────────────────── --}}
    @foreach(['success','error'] as $type)
    @if(session($type))
    <div class="flex items-center gap-3 {{ $type === 'success' ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200' }} border rounded-2xl px-5 py-3.5 mb-4"
         x-data="{ show: true }" x-show="show" x-transition.duration.300ms>
        <span class="text-sm {{ $type === 'success' ? 'text-green-800' : 'text-red-800' }} font-medium flex-1">{{ session($type) }}</span>
        <button @click="show=false">✕</button>
    </div>
    @endif
    @endforeach

    {{-- ─── Breadcrumb + Channel Badge ─────────────────────────────────────── --}}
    <div class="flex items-center gap-3 mb-5 flex-wrap">
        <a href="{{ route('admin.inbox.index') }}" class="text-sm text-indigo-600 hover:underline flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            بازگشت به صندوق
        </a>
        <span class="text-gray-300">/</span>
        <span class="font-mono text-xs font-bold text-indigo-700 bg-indigo-50 px-2 py-1 rounded-lg border border-indigo-100">{{ $ticket->ticket_number }}</span>
        @if($channel === 'platform')
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold text-white channel-badge-platform">🏢 کانال: پلتفرم</span>
        @else
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold text-white channel-badge-user">👤 کانال: کاربر</span>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- ─── Chat Column ─────────────────────────────────────────────────── --}}
        <div class="lg:col-span-2 flex flex-col gap-4">

            {{-- Ticket header --}}
            <div class="glass-card-strong p-5">
                <h1 class="text-lg font-bold text-gray-900 mb-1">{{ $ticket->subject }}</h1>
                <div class="flex flex-wrap gap-2 text-xs text-gray-500">
                    <span>{{ \App\Helpers\Jalali::formatFa($ticket->created_at, 'Y/m/d H:i') }}</span>
                    @if($ticket->category)
                    <span class="bg-gray-100 px-2 py-0.5 rounded">{{ $ticket->category }}</span>
                    @endif
                    @if($ticket->customer_name)
                    <span class="text-gray-600 font-medium">مخاطب: {{ $ticket->customer_name }}</span>
                    @endif
                </div>
                @if($ticket->description)
                <div class="mt-3 pt-3 border-t border-gray-100 text-sm text-gray-700 leading-relaxed whitespace-pre-wrap">{{ $ticket->description }}</div>
                @endif
            </div>

            {{-- Chat messages --}}
            <div class="glass-card-strong flex flex-col" style="min-height:400px;max-height:520px">
                <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100">
                    <span class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        مکالمه
                    </span>
                    <span class="text-xs text-gray-400">{{ $ticket->replies->count() }} پیام</span>
                </div>
                <div id="chat-messages" x-ref="chatMessages"
                     class="flex-1 overflow-y-auto p-5 space-y-2"
                     style="display:flex;flex-direction:column"
                     @scroll="handleScroll()">
                    @foreach($ticket->replies as $reply)
                    @php
                        $isStaff    = $reply->author_type === 'staff';
                        $isInternal = $reply->is_internal ?? false;
                        $name       = $reply->user?->name ?? ($isStaff ? 'پشتیبان' : ($reply->customer_name ?? 'کاربر'));
                    @endphp
                    <div class="flex items-end gap-2 max-w-[80%] mb-4 {{ $isStaff || $isInternal ? 'flex-row-reverse' : '' }}"
                         style="align-self: {{ $isStaff || $isInternal ? 'flex-end' : 'flex-start' }}">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0 shadow-sm"
                             style="background-color: {{ $isInternal ? 'var(--ui-warning)' : ($isStaff ? '#6366f1' : 'var(--ui-success)') }}">
                            {{ mb_substr($name, 0, 1) }}
                        </div>
                        <div class="flex flex-col items-{{ $isStaff || $isInternal ? 'end' : 'start' }}">
                            <div class="{{ $isInternal ? 'bubble-internal' : ($isStaff ? 'bubble-staff' : 'bubble-customer') }} px-4 py-2.5 shadow-sm">
                                <p class="text-xs font-bold mb-1 opacity-70">
                                    {{ $name }}
                                    @if($isInternal)
                                    <span class="text-[9px] bg-amber-200/60 px-1.5 py-0.5 rounded-full mr-1">یادداشت</span>
                                    @endif
                                </p>
                                <p class="text-sm leading-relaxed whitespace-pre-wrap">{{ $reply->message }}</p>
                                @if(!empty($reply->attachments))
                                <div class="flex flex-wrap gap-1.5 mt-2">
                                    @foreach($reply->attachments as $att)
                                    @if(!empty($att['url']))
                                    @php
                                        $attName  = $att['name'] ?? 'فایل';
                                        $attIsImg = preg_match('/^image\//i', $att['mime'] ?? '') || preg_match('/\.(jpg|jpeg|png|gif|webp|bmp)$/i', $attName);
                                    @endphp
                                    @if($attIsImg)
                                    <a href="{{ $att['url'] }}" target="_blank" class="block mt-1">
                                        <img src="{{ $att['url'] }}" loading="lazy" alt="{{ $attName }}"
                                             class="rounded-xl max-h-52 max-w-[220px] object-cover hover:opacity-90 transition-opacity shadow-sm border border-white/60 cursor-pointer">
                                    </a>
                                    @else
                                    <a href="{{ $att['url'] }}" target="_blank"
                                       class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[10px] bg-white/60 hover:bg-white border border-gray-200 transition-all">
                                        <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13"/></svg>
                                        {{ $attName }}
                                    </a>
                                    @endif
                                    @endif
                                    @endforeach
                                </div>
                                @endif
                            </div>
                            <span class="text-[10px] text-gray-400 mt-1 px-1">
                                {{ \App\Helpers\Jalali::formatFa($reply->created_at, 'H:i') }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
                {{-- scroll-to-bottom button --}}
                <div x-show="showScrollBtn" @click="scrollDown()"
                     class="absolute bottom-40 left-1/2 -translate-x-1/2 bg-indigo-600 text-white text-xs px-3 py-1.5 rounded-full shadow-lg cursor-pointer z-10 flex items-center gap-1.5"
                     x-transition style="position:sticky">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    پیام جدید
                </div>
            </div>

            {{-- ─── Reply Form ──────────────────────────────────────────────── --}}
            @if($ticket->status !== 'closed')
            <div class="glass-card-strong p-5">
                {{-- Mode toggle (only for user channel) --}}
                @if($channel === 'user')
                <div class="flex items-center gap-3 mb-4 p-3 bg-gray-50 rounded-xl">
                    <span class="text-xs font-semibold text-gray-600">نوع پیام:</span>
                    <button type="button"
                            @click="noteMode=false"
                            :class="!noteMode ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white text-gray-500 border border-gray-200'"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all">
                        💬 پاسخ به کاربر
                    </button>
                    <button type="button"
                            @click="noteMode=true"
                            :class="noteMode ? 'bg-amber-500 text-white shadow-sm' : 'bg-white text-gray-500 border border-gray-200'"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all">
                        📝 یادداشت داخلی
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

                    <div x-show="!noteMode">
                        <textarea name="message" x-model="message" rows="3"
                                  placeholder="پیام خود را بنویسید..."
                                  class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-white text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 transition-all resize-none"></textarea>
                    </div>
                    <div x-show="noteMode" x-cloak>
                        <textarea name="note_message" x-model="noteText" rows="3"
                                  placeholder="یادداشت داخلی (فقط برای کارشناسان قابل مشاهده است)..."
                                  class="w-full px-4 py-3 rounded-xl border border-amber-300 bg-amber-50/60 text-sm focus:ring-2 focus:ring-amber-200 focus:border-amber-400 transition-all resize-none"></textarea>
                    </div>

                    {{-- Hidden textarea to carry the right value --}}
                    <template x-if="noteMode">
                        <input type="hidden" name="message" :value="noteText">
                    </template>

                    <div class="mt-2">
                        <input type="file" name="attachments[]" multiple
                               accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.zip,.txt"
                               class="text-xs text-gray-500 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700 file:px-3 file:py-1.5 file:text-xs file:font-medium hover:file:bg-indigo-100">
                    </div>
                    <div class="flex items-center justify-between mt-3 gap-3 flex-wrap">
                        <div class="flex items-center gap-3">
                            @if($channel === 'user' && $smsEnabled && $endUser?->mobile)
                            <label class="flex items-center gap-1.5 text-xs text-gray-500 cursor-pointer" x-show="!noteMode">
                                <input type="checkbox" name="send_sms" value="1" class="rounded text-indigo-600 text-sm">
                                <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 8.25h3m-3 3h3m-3 3h3"/></svg>
                                ارسال پیامک
                            </label>
                            @endif
                        </div>
                        <button type="submit" :disabled="isSending"
                                :class="noteMode ? 'bg-amber-500 hover:bg-amber-600' : 'bg-indigo-600 hover:bg-indigo-700'"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white transition-all disabled:opacity-60 disabled:cursor-not-allowed">
                            <svg x-show="isSending" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                            <svg x-show="!isSending" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>
                            <span x-text="isSending ? 'در حال ارسال...' : (noteMode ? 'ثبت یادداشت' : 'ارسال پاسخ')"></span>
                        </button>
                    </div>
                </form>
            </div>
            @else
            <div class="glass-card-strong p-5 text-center text-sm text-gray-500">
                <svg class="w-6 h-6 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                تیکت بسته شده است.
            </div>
            @endif

        </div>

        {{-- ─── Sidebar ──────────────────────────────────────────────────────── --}}
        <div class="flex flex-col gap-4">

            {{-- Ticket meta --}}
            <div class="glass-card-strong p-5">
                <h3 class="text-sm font-bold text-gray-700 mb-4">جزئیات تیکت</h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-400">وضعیت</dt>
                        <dd>
                            @if($ticket->status === 'open')
                            <span class="tkt-badge bg-yellow-50 text-yellow-700 border-yellow-200"><span class="pdot bg-yellow-500"></span>باز</span>
                            @elseif($ticket->status === 'in_progress')
                            <span class="tkt-badge bg-blue-50 text-blue-700 border-blue-200"><span class="pdot bg-blue-500"></span>در حال بررسی</span>
                            @else
                            <span class="tkt-badge bg-green-50 text-green-700 border-green-200">بسته</span>
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-400">اولویت</dt>
                        <dd class="font-semibold {{ $ticket->priority === 'high' ? 'text-red-600' : ($ticket->priority === 'low' ? 'text-gray-500' : 'text-blue-600') }}">
                            {{ $ticket->priority === 'high' ? '🔴 بالا' : ($ticket->priority === 'low' ? '⚪ کم' : '🔵 عادی') }}
                        </dd>
                    </div>
                    @if($ticket->assignee?->name)
                    <div class="flex justify-between">
                        <dt class="text-gray-400">کارشناس</dt>
                        <dd class="font-semibold text-indigo-600">{{ $ticket->assignee->name }}</dd>
                    </div>
                    @endif
                    @if($ticket->customer_name)
                    <div class="flex justify-between">
                        <dt class="text-gray-400">مخاطب</dt>
                        <dd class="font-semibold text-gray-700">{{ $ticket->customer_name }}</dd>
                    </div>
                    @endif
                    @if($endUser?->mobile)
                    <div class="flex justify-between">
                        <dt class="text-gray-400">موبایل</dt>
                        <dd class="font-mono text-gray-700 text-xs">{{ $endUser->mobile }}</dd>
                    </div>
                    @endif
                </dl>
            </div>

            {{-- User-ticket controls (only for user channel) --}}
            @if($channel === 'user')

            {{-- Status --}}
            <div class="glass-card-strong p-5">
                <h3 class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    تغییر وضعیت
                </h3>
                <form action="{{ route('admin.inbox.user.status', $id) }}" method="POST" class="flex gap-2">
                    @csrf @method('PATCH')
                    <select name="status" class="flex-1 px-3 py-2 rounded-xl border border-gray-200 text-sm bg-white focus:ring-2 focus:ring-indigo-200">
                        <option value="open"        {{ $ticket->status === 'open'        ? 'selected' : '' }}>باز</option>
                        <option value="in_progress" {{ $ticket->status === 'in_progress' ? 'selected' : '' }}>در حال بررسی</option>
                        <option value="closed"      {{ $ticket->status === 'closed'      ? 'selected' : '' }}>بسته</option>
                    </select>
                    <button type="submit" class="px-3 py-2 rounded-xl bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700 transition-all">ثبت</button>
                </form>
            </div>

            {{-- Priority --}}
            <div class="glass-card-strong p-5">
                <h3 class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4.5h14.25M3 9h9.75M3 13.5h9.75m4.5-4.5v12m0 0l-3.75-3.75M17.25 21L21 17.25"/></svg>
                    تغییر اولویت
                </h3>
                <form action="{{ route('admin.inbox.user.priority', $id) }}" method="POST" class="flex gap-2">
                    @csrf @method('PATCH')
                    <select name="priority" class="flex-1 px-3 py-2 rounded-xl border border-gray-200 text-sm bg-white focus:ring-2 focus:ring-indigo-200">
                        <option value="high"   {{ $ticket->priority === 'high'   ? 'selected' : '' }}>🔴 بالا</option>
                        <option value="normal" {{ $ticket->priority === 'normal' ? 'selected' : '' }}>🔵 عادی</option>
                        <option value="low"    {{ $ticket->priority === 'low'    ? 'selected' : '' }}>⚪ کم</option>
                    </select>
                    <button type="submit" class="px-3 py-2 rounded-xl bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700 transition-all">ثبت</button>
                </form>
            </div>

            {{-- Assign --}}
            <div class="glass-card-strong p-5">
                <h3 class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                    تخصیص کارشناس
                </h3>
                <form action="{{ route('admin.inbox.user.assign', $id) }}" method="POST" class="flex gap-2">
                    @csrf @method('PATCH')
                    <select name="assigned_to" class="flex-1 px-3 py-2 rounded-xl border border-gray-200 text-sm bg-white focus:ring-2 focus:ring-indigo-200">
                        <option value="">— انتخاب کنید —</option>
                        @foreach($agentUsers as $agent)
                        <option value="{{ $agent->id }}" {{ ($ticket->assignee?->name === $agent->name) ? 'selected' : '' }}>{{ $agent->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="px-3 py-2 rounded-xl bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700 transition-all">ثبت</button>
                </form>
            </div>

            @endif

            {{-- Platform info banner --}}
            @if($channel === 'platform')
            <div class="rounded-2xl p-4 bg-purple-50 border border-purple-200">
                <div class="flex items-center gap-2 text-purple-700 font-semibold text-sm mb-1">🏢 تیکت به پلتفرم</div>
                <p class="text-xs text-purple-600">این تیکت به ادمین اصلی پلتفرم ارسال شده و از طریق API پاسخ می‌گیرد.</p>
            </div>
            @endif

        </div>
    </div>

</div>
@endsection
