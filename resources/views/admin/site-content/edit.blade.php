@extends('admin.layout')
@section('title', 'ویرایش: ' . $siteContent->title)

@section('content')
@php
$types = \App\Models\SiteContent::$TYPES;
$type = $siteContent->type;
$hasSteps = $siteContent->hasSteps();
@endphp

<div class="p-6" dir="rtl" x-data="contentEditor('{{ $type }}', {{ json_encode($hasSteps) }}, @json($siteContent), @json($siteContent->steps))">

    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.site-content.index') }}" class="text-gray-400 hover:text-gray-600">
            <x-core-ui::icon name="edit" class="w-5 h-5" />
        </a>
        <h1 class="text-xl font-bold text-gray-900">ویرایش — <span class="text-indigo-600">{{ $types[$type] ?? $type }}</span></h1>
        @if($siteContent->ai_generated)
        <span class="px-2 py-1 bg-purple-50 text-purple-700 text-xs rounded-full">✨ تولیدشده با AI</span>
        @endif
    </div>

    {{-- AI Assistant --}}
    <div class="bg-gradient-to-l from-purple-50 to-indigo-50 border border-indigo-100 rounded-2xl p-5 mb-6">
        <div class="flex items-center gap-2 mb-3">
            <span class="text-xl">✨</span>
            <span class="font-semibold text-indigo-800">بازنویسی با AI</span>
        </div>
        <div class="flex gap-3">
            <input x-model="aiPrompt" type="text"
                placeholder="درخواست جدید برای بازنویسی یا تکمیل محتوا..."
                class="flex-1 border border-indigo-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-indigo-400 bg-white"
                @keydown.enter.prevent="generateAi()">
            <button @click="generateAi()" :disabled="aiLoading"
                class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-xl text-sm font-medium transition">
                <span x-show="!aiLoading">بازنویسی</span>
                <span x-show="aiLoading">در حال تولید...</span>
            </button>
        </div>
        <div x-show="aiError" x-text="aiError" class="mt-2 text-sm text-red-600"></div>
    </div>

    @if(session('success'))
    <div class="mb-4 p-3 bg-green-50 text-green-700 rounded-xl text-sm">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.site-content.update', $siteContent) }}">
        @csrf @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-5">
                <div class="bg-white rounded-2xl border border-gray-100 p-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">عنوان</label>
                        <input type="text" name="title" x-model="title" required
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-indigo-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">خلاصه</label>
                        <textarea name="excerpt" x-model="excerpt" rows="2"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-indigo-400 resize-none"></textarea>
                    </div>
                    @if(!$hasSteps)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">متن اصلی</label>
                        <textarea name="body" x-model="body" rows="15"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-indigo-400 font-mono"></textarea>
                    </div>
                    @endif
                </div>

                @if($hasSteps)
                <div class="bg-white rounded-2xl border border-gray-100 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-800">مراحل</h3>
                        <button type="button" @click="addStep()" class="text-sm text-indigo-600 hover:text-indigo-800">+ افزودن مرحله</button>
                    </div>
                    <div class="space-y-3">
                        <template x-for="(step, i) in steps" :key="i">
                            <div class="border border-gray-100 rounded-xl p-4 bg-gray-50">
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="w-6 h-6 bg-indigo-600 text-white text-xs rounded-full flex items-center justify-center font-bold" x-text="i+1"></span>
                                    <input type="text" x-bind:name="'steps['+i+'][title]'" x-model="step.title"
                                        class="flex-1 border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-indigo-400">
                                    <button type="button" @click="steps.splice(i,1)" class="text-red-400 hover:text-red-600">✕</button>
                                </div>
                                <textarea x-bind:name="'steps['+i+'][body]'" x-model="step.body" rows="2"
                                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-400 resize-none"></textarea>
                            </div>
                        </template>
                    </div>
                </div>
                @endif
            </div>

            <div class="space-y-4">
                <div class="bg-white rounded-2xl border border-gray-100 p-5 space-y-3">
                    <h3 class="font-semibold text-gray-800">انتشار</h3>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">وضعیت</label>
                        <select name="status" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-400">
                            <option value="draft" @selected($siteContent->status === 'draft')>پیش‌نویس</option>
                            <option value="published" @selected($siteContent->status === 'published')>منتشرشده</option>
                            <option value="archived" @selected($siteContent->status === 'archived')>آرشیو</option>
                        </select>
                    </div>
                    <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium">ذخیره تغییرات</button>
                </div>

                <div class="bg-white rounded-2xl border border-gray-100 p-5 space-y-3">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">دسته‌بندی</label>
                        <select name="category_id" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-400">
                            <option value="">بدون دسته</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" @selected($siteContent->category_id == $cat->id)>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">برچسب‌ها</label>
                        <input type="text" name="tags" value="{{ is_array($siteContent->tags) ? implode(', ', $siteContent->tags) : '' }}"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-400">
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function contentEditor(type, hasSteps, content, initialSteps) {
    return {
        type, hasSteps,
        title: content.title || '',
        excerpt: content.excerpt || '',
        body: content.body || '',
        steps: initialSteps.map(s => ({ title: s.title, body: s.body || '' })),
        aiPrompt: '',
        aiLoading: false,
        aiError: '',

        addStep() { this.steps.push({ title: '', body: '' }); },

        async generateAi() {
            if (!this.aiPrompt.trim()) return;
            this.aiLoading = true;
            this.aiError = '';
            try {
                const mode = this.hasSteps ? 'steps' : 'full';
                const resp = await fetch('{{ route('admin.site-content.ai-generate') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ type: this.type, prompt: this.aiPrompt, mode }),
                });
                const data = await resp.json();
                if (!data.success) { this.aiError = data.error; return; }
                if (data.mode === 'steps' && data.data) {
                    this.title = data.data.title || this.title;
                    this.excerpt = data.data.excerpt || this.excerpt;
                    this.steps = data.data.steps || [];
                } else {
                    this.body = data.content || '';
                }
            } catch(e) { this.aiError = 'خطا در اتصال'; } finally { this.aiLoading = false; }
        }
    }
}
</script>
@endpush
@endsection

