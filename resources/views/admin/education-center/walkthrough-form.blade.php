@extends('admin.layout')

@section('title', isset($walkthrough) ? 'ویرایش راهنما' : 'راهنمای جدید')

@section('content')
<div dir="rtl" class="p-6 max-w-4xl mx-auto" x-data="walkthroughForm()">

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.education-center.manage', ['tab' => 'walkthroughs']) }}"
           class="text-gray-400 hover:text-gray-600 p-1 rounded hover:bg-gray-100 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <h1 class="text-xl font-bold text-gray-800">
            {{ isset($walkthrough) ? 'ویرایش راهنما' : 'راهنمای جدید' }}
        </h1>
    </div>

    @if($errors->any())
    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 rounded-lg px-4 py-3 text-sm">
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form method="POST"
          action="{{ isset($walkthrough) ? route('admin.education-center.walkthroughs.update', $walkthrough['id']) : route('admin.education-center.walkthroughs.store') }}"
          class="space-y-5">
        @csrf
        @if(isset($walkthrough)) @method('PUT') @endif

        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-5">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">عنوان <span class="text-red-500">*</span></label>
                    <input type="text" name="title" value="{{ old('title', $walkthrough['title'] ?? '') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-400"
                           required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ماژول <span class="text-red-500">*</span></label>
                    <input type="text" name="module_slug" value="{{ old('module_slug', $walkthrough['module_slug'] ?? '') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-400"
                           placeholder="مثال: shop, support" required>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">مسیر راه‌انداز (Route)</label>
                    <input type="text" name="trigger_route" value="{{ old('trigger_route', $walkthrough['trigger_route'] ?? '') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-400"
                           placeholder="مثال: admin.shop.index">
                </div>
                <div class="flex items-end pb-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1"
                               {{ old('is_active', $walkthrough['is_active'] ?? true) ? 'checked' : '' }}
                               class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                        <span class="text-sm font-medium text-gray-700">فعال</span>
                    </label>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">توضیحات</label>
                <textarea name="description" rows="2"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-400 resize-none">{{ old('description', $walkthrough['description'] ?? '') }}</textarea>
            </div>
        </div>

        {{-- مراحل -- فقط برای ایجاد --}}
        @if(!isset($walkthrough))
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold text-gray-700 text-sm flex items-center gap-2">
                    <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                    </svg>
                    مراحل راهنما
                </h2>
                <button type="button" x-on:click="addStep()"
                        class="text-blue-600 hover:text-blue-800 text-sm flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    افزودن مرحله
                </button>
            </div>

            <div class="space-y-3" x-ref="stepsContainer">
                <template x-for="(step, index) in steps" :key="index">
                    <div class="border border-gray-200 rounded-lg p-3 bg-gray-50">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-semibold text-gray-600" x-text="'مرحله ' + (index + 1)"></span>
                            <button type="button" x-on:click="removeStep(index)"
                                    class="text-red-400 hover:text-red-600 text-xs">حذف</button>
                        </div>
                        <div class="space-y-2">
                            <input type="text" :name="'steps['+index+'][title]'" x-model="step.title"
                                   class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:border-blue-400"
                                   placeholder="عنوان مرحله">
                            <textarea :name="'steps['+index+'][body]'" x-model="step.body" rows="2"
                                      class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:border-blue-400 resize-none"
                                      placeholder="توضیحات مرحله"></textarea>
                            <input type="text" :name="'steps['+index+'][target_selector]'" x-model="step.target_selector"
                                   class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:border-blue-400"
                                   placeholder="CSS Selector (اختیاری)">
                        </div>
                    </div>
                </template>

                <div x-show="steps.length === 0" class="text-center py-6 text-gray-400 text-sm">
                    هنوز مرحله‌ای اضافه نشده است.
                </div>
            </div>
        </div>
        @endif

        <div class="flex items-center gap-3 justify-end">
            <a href="{{ route('admin.education-center.manage', ['tab' => 'walkthroughs']) }}"
               class="px-4 py-2 rounded-lg border border-gray-300 text-sm text-gray-600 hover:bg-gray-50 transition">
                انصراف
            </a>
            <button type="submit"
                    class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                {{ isset($walkthrough) ? 'ذخیره تغییرات' : 'ایجاد راهنما' }}
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function walkthroughForm() {
    return {
        steps: @json(isset($walkthrough) ? ($walkthrough['steps'] ?? []) : []),
        addStep() {
            this.steps.push({ title: '', body: '', target_selector: '' });
        },
        removeStep(index) {
            this.steps.splice(index, 1);
        }
    };
}
</script>
@endpush
@endsection
