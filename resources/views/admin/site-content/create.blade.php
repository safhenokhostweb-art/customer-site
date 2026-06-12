@extends('admin.layout')
@section('title', 'ظ…ط­طھظˆط§غŒ ط¬ط¯غŒط¯')

@section('content')
@php
$types = \App\Models\SiteContent::$TYPES;
$hasSteps = in_array($type, ['training','workflow','procedure']);
@endphp

<div class="p-6" dir="rtl" x-data="contentEditor('{{ $type }}', {{ json_encode($hasSteps) }})">

    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.site-content.index') }}" class="text-gray-400 hover:text-gray-600">
            <x-core-ui::icon name="image" class="w-5 h-5" />
        </a>
        <h1 class="text-xl font-bold text-gray-900">ظ…ط­طھظˆط§غŒ ط¬ط¯غŒط¯ â€” <span class="text-indigo-600">{{ $types[$type] ?? $type }}</span></h1>
    </div>

    {{-- AI Assistant Panel --}}
    <div class="bg-gradient-to-l from-purple-50 to-indigo-50 border border-indigo-100 rounded-2xl p-5 mb-6">
        <div class="flex items-center gap-2 mb-3">
            <span class="text-xl">âœ¨</span>
            <span class="font-semibold text-indigo-800">ط¯ط³طھغŒط§ط± ظ‡ظˆط´ظ…ظ†ط¯ AI</span>
            <span class="text-xs text-indigo-500">(GapGPT + DeepSeek)</span>
        </div>
        <div class="flex gap-3">
            <input x-model="aiPrompt" type="text"
                placeholder="ظ…ظˆط¶ظˆط¹ غŒط§ ط¯ط±ط®ظˆط§ط³طھ ط®ظˆط¯ ط±ط§ ط¨ظ†ظˆغŒط³غŒط¯... ظ…ط«ظ„ط§ظ‹: ط¢ظ…ظˆط²ط´ ط«ط¨طھ ط³ظپط§ط±ط´ ط¨ط±ط§غŒ ظ…ط´طھط±غŒط§ظ†"
                class="flex-1 border border-indigo-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-indigo-400 bg-white"
                @keydown.enter.prevent="generateAi()">
            <button @click="generateAi()" :disabled="aiLoading"
                class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-xl text-sm font-medium flex items-center gap-2 transition">
                <x-core-ui::icon name="image" class="w-4 h-4 animate-spin" />
                <span x-show="!aiLoading">طھظˆظ„غŒط¯ ظ…ط­طھظˆط§</span>
                <span x-show="aiLoading">ط¯ط± ط­ط§ظ„ طھظˆظ„غŒط¯...</span>
            </button>
        </div>
        <div x-show="aiError" x-text="aiError" class="mt-2 text-sm text-red-600"></div>

        {{-- ظ†ظ…ط§غŒط´ ظˆط¶ط¹غŒطھ طµظپ --}}
        <div x-show="aiQueued" class="mt-2 flex items-center gap-2 text-sm text-amber-600">
            <x-core-ui::icon name="image" class="w-4 h-4 animate-spin shrink-0" />
            <span x-text="aiQueueMsg"></span>
        </div>

        <div x-show="aiProvider" class="mt-2 text-xs text-indigo-400">طھظˆظ„غŒط¯ط´ط¯ظ‡ ط¨ط§: <span x-text="aiProvider"></span></div>
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
                        <label class="block text-sm font-medium text-gray-700 mb-1">ط¹ظ†ظˆط§ظ† <span class="text-red-500">*</span></label>
                        <input type="text" name="title" x-model="title" required
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-indigo-400"
                            placeholder="ط¹ظ†ظˆط§ظ† ظ…ط­طھظˆط§...">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ط®ظ„ط§طµظ‡</label>
                        <textarea name="excerpt" x-model="excerpt" rows="2"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-indigo-400 resize-none"
                            placeholder="ط®ظ„ط§طµظ‡ ع©ظˆطھط§ظ‡..."></textarea>
                    </div>

                    @if(!$hasSteps)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ظ…طھظ† ط§طµظ„غŒ</label>
                        <textarea name="body" x-model="body" rows="15"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-indigo-400 font-mono"
                            placeholder="ظ…طھظ† ظ…ط­طھظˆط§ ط±ط§ ط§غŒظ†ط¬ط§ ط¨ظ†ظˆغŒط³غŒط¯ غŒط§ ط§ط² ط¯ط³طھغŒط§ط± AI ط§ط³طھظپط§ط¯ظ‡ ع©ظ†غŒط¯..."></textarea>
                    </div>
                    @endif
                </div>

                {{-- Steps (for training/workflow/procedure) --}}
                @if($hasSteps)
                <div class="bg-white rounded-2xl border border-gray-100 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-800">ظ…ط±ط§ط­ظ„</h3>
                        <button type="button" @click="addStep()"
                            class="text-sm text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
                            <x-core-ui::icon name="image" class="w-4 h-4" />
                            ط§ظپط²ظˆط¯ظ† ظ…ط±ط­ظ„ظ‡
                        </button>
                    </div>

                    <div class="space-y-3">
                        <template x-for="(step, i) in steps" :key="i">
                            <div class="border border-gray-100 rounded-xl p-4 bg-gray-50">
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="w-6 h-6 bg-indigo-600 text-white text-xs rounded-full flex items-center justify-center font-bold" x-text="i+1"></span>
                                    <input type="text" x-bind:name="'steps['+i+'][title]'" x-model="step.title"
                                        class="flex-1 border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-indigo-400"
                                        placeholder="ط¹ظ†ظˆط§ظ† ظ…ط±ط­ظ„ظ‡...">
                                    <button type="button" @click="steps.splice(i,1)" class="text-red-400 hover:text-red-600">
                                        <x-core-ui::icon name="image" class="w-4 h-4" />
                                    </button>
                                </div>
                                <textarea x-bind:name="'steps['+i+'][body]'" x-model="step.body" rows="2"
                                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-400 resize-none"
                                    placeholder="طھظˆط¶غŒط­ ظ…ط±ط­ظ„ظ‡..."></textarea>
                            </div>
                        </template>
                        <template x-if="steps.length === 0">
                            <div class="text-center py-8 text-gray-400 text-sm">
                                ظ…ط±ط­ظ„ظ‡â€Œط§غŒ ط§ط¶ط§ظپظ‡ ع©ظ†غŒط¯ غŒط§ ط§ط² ط¯ط³طھغŒط§ط± AI ط§ط³طھظپط§ط¯ظ‡ ع©ظ†غŒط¯
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
                    <h3 class="font-semibold text-gray-800">ط§ظ†طھط´ط§ط±</h3>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">ظˆط¶ط¹غŒطھ</label>
                        <select name="status" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-400">
                            <option value="draft">ظ¾غŒط´â€Œظ†ظˆغŒط³</option>
                            <option value="published">ظ…ظ†طھط´ط±ط´ط¯ظ‡</option>
                        </select>
                    </div>
                    <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium transition">
                        ط°ط®غŒط±ظ‡ ظ…ط­طھظˆط§
                    </button>
                    <a href="{{ route('admin.site-content.index') }}" class="block text-center text-sm text-gray-400 hover:text-gray-600">ط§ظ†طµط±ط§ظپ</a>
                </div>

                {{-- Category & Tags --}}
                <div class="bg-white rounded-2xl border border-gray-100 p-5 space-y-3">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">ط¯ط³طھظ‡â€Œط¨ظ†ط¯غŒ</label>
                        <select name="category_id" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-400">
                            <option value="">ط¨ط¯ظˆظ† ط¯ط³طھظ‡</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">ط¨ط±ع†ط³ط¨â€Œظ‡ط§ (ط¨ط§ ع©ط§ظ…ط§ ط¬ط¯ط§ ع©ظ†غŒط¯)</label>
                        <input type="text" name="tags" placeholder="ظ…ط«ظ„ط§ظ‹: ط¢ظ…ظˆط²ط´طŒ ط±ط§ظ‡ظ†ظ…ط§طŒ ظپط±ظˆط´ع¯ط§ظ‡"
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

                // ط¯ط± طµظپ ظ‚ط±ط§ط± ع¯ط±ظپطھ â€” worker ظ„ظˆع©ط§ظ„ ظ¾ط±ط¯ط§ط²ط´ ظ…غŒâ€Œع©ظ†ط¯
                if (!data.success && data.queued) {
                    this.aiQueued   = true;
                    this.aiQueueMsg = 'ط¯ط± ط­ط§ظ„ ظ¾ط±ط¯ط§ط²ط´... ظ„ط·ظپط§ظ‹ طµط¨ط± ع©ظ†غŒط¯';
                    this.aiLoading  = false;
                    this.pollJobStatus(data.job_id, mode);
                    return;
                }

                if (!data.success) {
                    this.aiError = data.error || 'ط®ط·ط§غŒغŒ ط±ط® ط¯ط§ط¯';
                    return;
                }

                this.applyResult(data, mode);

            } catch (e) {
                this.aiError = 'ط®ط·ط§ ط¯ط± ط§طھطµط§ظ„ ط¨ظ‡ ط³ط±ظˆغŒط³ AI';
            } finally {
                if (!this.aiQueued) this.aiLoading = false;
            }
        },

        pollJobStatus(jobId, mode) {
            let attempts = 0;
            const maxAttempts = 80; // ط­ط¯ط§ع©ط«ط± غ´ ط¯ظ‚غŒظ‚ظ‡

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
                        // data.data ظ…ظ…ع©ظ† ط§ط³طھ string غŒط§ object ط¨ط§ط´ط¯
                        let rawData = data.data;
                        // ط§ع¯ط± string ط§ط³طھطŒ parse ع©ظ†
                        if (typeof rawData === 'string') {
                            try { rawData = JSON.parse(rawData); } catch(e) {}
                        }
                        // ط§ع¯ط± ظ‡ظ†ظˆط² string ط§ط³طھ (ظ…ظ‚ط§ظ„ظ‡ ط³ط§ط¯ظ‡)طŒ ط¨ظ‡ ط¹ظ†ظˆط§ظ† content ط§ط³طھظپط§ط¯ظ‡ ع©ظ†
                        const isString = typeof rawData === 'string';
                        this.applyResult({
                            success: true, mode,
                            content: isString ? rawData : rawData,
                            data: rawData,
                            provider: 'deepseek'
                        }, mode);

                    } else if (data.status === 'failed') {
                        clearInterval(this.aiPollTimer);
                        this.aiQueued  = false;
                        this.aiLoading = false;
                        this.aiError   = data.error || 'ظ¾ط±ط¯ط§ط²ط´ AI ظ†ط§ظ…ظˆظپظ‚ ط¨ظˆط¯';

                    } else {
                        const dots = '.'.repeat((attempts % 3) + 1);
                        this.aiQueueMsg = `ط¯ط± ط­ط§ظ„ ظ¾ط±ط¯ط§ط²ط´${dots} (${attempts * 3}s)`;
                        if (attempts >= maxAttempts) {
                            clearInterval(this.aiPollTimer);
                            this.aiQueued  = false;
                            this.aiLoading = false;
                            this.aiError   = 'ظ¾ط§ط³ط® AI ط¯ط±غŒط§ظپطھ ظ†ط´ط¯. ظ„ط·ظپط§ظ‹ ai-worker.ps1 ط±ط§ ط±ظˆغŒ ظˆغŒظ†ط¯ظˆط² ط§ط¬ط±ط§ ع©ظ†غŒط¯ غŒط§ DEEPSEEK_API_KEY ط±ط§ ط¯ط± .env ط³ط±ظˆط± طھظ†ط¸غŒظ… ع©ظ†غŒط¯.';
                        }
                    }
                } catch(e) { /* ط´ط¨ع©ظ‡ ظ‚ط·ط¹ â€” ط¯ظˆط¨ط§ط±ظ‡ طھظ„ط§ط´ ظ…غŒâ€Œع©ظ†ط¯ */ }

            }, 3000); // ظ‡ط± غ³ ط«ط§ظ†غŒظ‡ ع†ع© ع©ظ†
        },

        applyResult(data, mode) {
            this.aiUsed     = true;
            this.aiQueued   = false;
            this.aiProvider = data.provider || 'ai';

            if (mode === 'steps') {
                const d = data.data || data.content || {};
                // ط§ع¯ط± ط±ط´طھظ‡ JSON ط§ط³طھ parse ع©ظ†
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

