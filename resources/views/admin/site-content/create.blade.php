@extends('admin.layout')
@section('title', 'محتوای جدید')

@section('content')
@php
$types = \App\Models\SiteContent::$TYPES;
$hasSteps = in_array($type, ['training','workflow','procedure']);
@endphp

<div class="p-6" dir="rtl" x-data="contentEditor('{{ $type }}', {{ json_encode($hasSteps) }})">

    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.site-content.index') }}" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 class="text-xl font-bold text-gray-900">محتوای جدید — <span class="text-indigo-600">{{ $types[$type] ?? $type }}</span></h1>
    </div>

    {{-- AI Assistant Panel --}}
    <div class="bg-gradient-to-l from-purple-50 to-indigo-50 border border-indigo-100 rounded-2xl p-5 mb-6">
        <div class="flex items-center gap-2 mb-3">
            <span class="text-xl">✨</span>
            <span class="font-semibold text-indigo-800">دستیار هوشمند AI</span>
            <span class="text-xs text-indigo-500">(DeepSeek + Claude)</span>
        </div>
        <div class="flex gap-3">
            <input x-model="aiPrompt" type="text"
                placeholder="موضوع یا درخواست خود را بنویسید... مثلاً: آموزش ثبت سفارش برای مشتریان"
                class="flex-1 border border-indigo-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-indigo-400 bg-white"
                @keydown.enter.prevent="generateAi()">
            <button @click="generateAi()" :disabled="aiLoading"
                class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-xl text-sm font-medium flex items-center gap-2 transition">
                <svg x-show="aiLoading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span x-show="!aiLoading">تولید محتوا</span>
                <span x-show="aiLoading">در حال تولید...</span>
            </button>
        </div>
        <div x-show="aiError" x-text="aiError" class="mt-2 text-sm text-red-600"></div>

        {{-- نمایش وضعیت صف --}}
        <div x-show="aiQueued" class="mt-2 flex items-center gap-2 text-sm text-amber-600">
            <svg class="w-4 h-4 animate-spin shrink-0" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <span x-text="aiQueueMsg"></span>
        </div>

        <div x-show="aiProvider" class="mt-2 text-xs text-indigo-400">تولیدشده با: <span x-text="aiProvider"></span></div>
    </div>

    {{-- Form --}}
    <form method="POST" action="{{ route('admin.site-content.store') }}">
        @csrf
        <input type="hidden" name="type" value="{{ $type }}">
        <input type="hidden" name="ai_generated" x-bind:value="aiUsed ? '1' : '0'">
        <input type="hidden" name="ai_prompt" x-bind:value="aiPrompt">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Main Content --}}
            <div class="lg:col-span-2 space-y-5">

                <div class="bg-white rounded-2xl border border-gray-100 p-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">عنوان <span class="text-red-500">*</span></label>
                        <input type="text" name="title" x-model="title" required
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-indigo-400"
                            placeholder="عنوان محتوا...">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">خلاصه</label>
                        <textarea name="excerpt" x-model="excerpt" rows="2"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-indigo-400 resize-none"
                            placeholder="خلاصه کوتاه..."></textarea>
                    </div>

                    @if(!$hasSteps)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">متن اصلی</label>
                        <textarea name="body" x-model="body" rows="15"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-indigo-400 font-mono"
                            placeholder="متن محتوا را اینجا بنویسید یا از دستیار AI استفاده کنید..."></textarea>
                    </div>
                    @endif
                </div>

                {{-- Steps (for training/workflow/procedure) --}}
                @if($hasSteps)
                <div class="bg-white rounded-2xl border border-gray-100 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-800">مراحل</h3>
                        <button type="button" @click="addStep()"
                            class="text-sm text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            افزودن مرحله
                        </button>
                    </div>

                    <div class="space-y-3">
                        <template x-for="(step, i) in steps" :key="i">
                            <div class="border border-gray-100 rounded-xl p-4 bg-gray-50">
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="w-6 h-6 bg-indigo-600 text-white text-xs rounded-full flex items-center justify-center font-bold" x-text="i+1"></span>
                                    <input type="text" x-bind:name="'steps['+i+'][title]'" x-model="step.title"
                                        class="flex-1 border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-indigo-400"
                                        placeholder="عنوان مرحله...">
                                    <button type="button" @click="steps.splice(i,1)" class="text-red-400 hover:text-red-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                                <textarea x-bind:name="'steps['+i+'][body]'" x-model="step.body" rows="2"
                                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-400 resize-none"
                                    placeholder="توضیح مرحله..."></textarea>
                            </div>
                        </template>
                        <template x-if="steps.length === 0">
                            <div class="text-center py-8 text-gray-400 text-sm">
                                مرحله‌ای اضافه کنید یا از دستیار AI استفاده کنید
                            </div>
                        </template>
                    </div>
                </div>
                @endif

            </div>

            {{-- Sidebar --}}
            <div class="space-y-4">

                {{-- Publish --}}
                <div class="bg-white rounded-2xl border border-gray-100 p-5 space-y-3">
                    <h3 class="font-semibold text-gray-800">انتشار</h3>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">وضعیت</label>
                        <select name="status" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-400">
                            <option value="draft">پیش‌نویس</option>
                            <option value="published">منتشرشده</option>
                        </select>
                    </div>
                    <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium transition">
                        ذخیره محتوا
                    </button>
                    <a href="{{ route('admin.site-content.index') }}" class="block text-center text-sm text-gray-400 hover:text-gray-600">انصراف</a>
                </div>

                {{-- Category & Tags --}}
                <div class="bg-white rounded-2xl border border-gray-100 p-5 space-y-3">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">دسته‌بندی</label>
                        <select name="category_id" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-400">
                            <option value="">بدون دسته</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">برچسب‌ها (با کاما جدا کنید)</label>
                        <input type="text" name="tags" placeholder="مثلاً: آموزش، راهنما، فروشگاه"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-400">
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function contentEditor(type, hasSteps) {
    return {
        type,
        hasSteps,
        title: '',
        excerpt: '',
        body: '',
        steps: [],
        aiPrompt: '',
        aiLoading: false,
        aiError: '',
        aiProvider: '',
        aiUsed: false,
        aiQueued: false,
        aiQueueMsg: '',
        aiPollTimer: null,

        addStep() {
            this.steps.push({ title: '', body: '' });
        },

        async generateAi() {
            if (!this.aiPrompt.trim()) return;
            this.aiLoading = true;
            this.aiError   = '';
            this.aiQueued  = false;
            this.aiQueueMsg = '';
            this.aiProvider = '';

            try {
                const mode = this.hasSteps ? 'steps' : 'full';
                const resp = await fetch('{{ route('admin.site-content.ai-generate') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ type: this.type, prompt: this.aiPrompt, mode }),
                });

                const data = await resp.json();

                // در صف قرار گرفت — worker لوکال پردازش می‌کند
                if (!data.success && data.queued) {
                    this.aiQueued   = true;
                    this.aiQueueMsg = 'در حال پردازش... لطفاً صبر کنید';
                    this.aiLoading  = false;
                    this.pollJobStatus(data.job_id, mode);
                    return;
                }

                if (!data.success) {
                    this.aiError = data.error || 'خطایی رخ داد';
                    return;
                }

                this.applyResult(data, mode);

            } catch (e) {
                this.aiError = 'خطا در اتصال به سرویس AI';
            } finally {
                if (!this.aiQueued) this.aiLoading = false;
            }
        },

        pollJobStatus(jobId, mode) {
            let attempts = 0;
            const maxAttempts = 40; // حداکثر ۲ دقیقه

            this.aiPollTimer = setInterval(async () => {
                attempts++;
                try {
                    const resp = await fetch(`/api/ai-queue/${jobId}/status`, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const data = await resp.json();

                    if (data.status === 'done') {
                        clearInterval(this.aiPollTimer);
                        this.aiQueued  = false;
                        this.aiLoading = false;
                        // data.data = نتیجه از worker
                        const result = typeof data.data === 'string'
                            ? { content: data.data }
                            : data.data;
                        this.applyResult({ success: true, mode, content: result, data: result, provider: 'deepseek' }, mode);

                    } else if (data.status === 'failed') {
                        clearInterval(this.aiPollTimer);
                        this.aiQueued  = false;
                        this.aiLoading = false;
                        this.aiError   = data.error || 'پردازش AI ناموفق بود';

                    } else {
                        const dots = '.'.repeat((attempts % 3) + 1);
                        this.aiQueueMsg = `در حال پردازش${dots} (${attempts * 3}s)`;
                        if (attempts >= maxAttempts) {
                            clearInterval(this.aiPollTimer);
                            this.aiQueued  = false;
                            this.aiLoading = false;
                            this.aiError   = 'زمان انتظار تمام شد. Worker ویندوز را بررسی کنید.';
                        }
                    }
                } catch(e) { /* شبکه قطع — دوباره تلاش می‌کند */ }

            }, 3000); // هر ۳ ثانیه چک کن
        },

        applyResult(data, mode) {
            this.aiUsed     = true;
            this.aiQueued   = false;
            this.aiProvider = data.provider || 'ai';

            if (mode === 'steps') {
                const d = data.data || data.content || {};
                // اگر رشته JSON است parse کن
                let parsed = d;
                if (typeof d === 'string') {
                    try { parsed = JSON.parse(d); } catch(e) { parsed = { title: '', steps: [] }; }
                }
                this.title   = parsed.title   || this.title;
                this.excerpt = parsed.excerpt  || '';
                this.steps   = parsed.steps    || [];
            } else {
                const content = data.content || '';
                this.body = typeof content === 'string' ? content : JSON.stringify(content);
            }
        }
    }
}
</script>
@endpush
@endsection
