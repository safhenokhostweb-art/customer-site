@extends('layouts.canvas-editor')

@section('title', 'ویرایشگر قالب Canvas')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
@endpush

@section('content')
@php
$blockLabels = collect($registry)->mapWithKeys(fn($d,$t)=>[$t=>$d['name']]);
$blockSchemas = collect($registry)->mapWithKeys(fn($d,$t)=>[$t=>['schema'=>$d['schema']??[],'defaultProps'=>$d['defaultProps']??[]]]);
$orderedCats  = ['layout','content','dynamic','ecommerce','marketing','utility','module'];
$catColors    = ['layout'=>'text-blue-600 bg-blue-50','content'=>'text-green-700 bg-green-50','dynamic'=>'text-purple-600 bg-purple-50','ecommerce'=>'text-emerald-700 bg-emerald-50','marketing'=>'text-orange-600 bg-orange-50','utility'=>'text-slate-600 bg-slate-100','module'=>'text-indigo-700 bg-indigo-50'];
$blockEmoji   = ['hero'=>'🌟','section'=>'📦','grid'=>'🔲','columns'=>'⬜','accordion'=>'🪗','slider'=>'🎠','heading'=>'🔠','text'=>'📄','image'=>'🖼️','gallery'=>'🗂️','video'=>'🎬','button'=>'🔘','divider'=>'➖','counter'=>'🔢','blog_posts'=>'📰','testimonials'=>'⭐','product_grid'=>'🛍️','product_slider'=>'🎠','cta'=>'📢','countdown'=>'⏱️','promo_banner'=>'📯','search_bar'=>'🔍','nav_menu'=>'🗺️','social_icons'=>'📡','login_register'=>'👤','module:blog_posts'=>'📰','module:shop_product_grid'=>'🛍️','module:courses_grid'=>'🎓','module:pricing_table'=>'💰','module:contact_quick'=>'📞','module:faq_dynamic'=>'❓','module:support_button'=>'💬','module:gallery_widget'=>'🖼️','module:reviews_grid'=>'⭐','module:referral_widget'=>'🎁','module:social_links_bar'=>'📡','module:notifications_bar'=>'🔔','module:whatsapp_widget'=>'📲','module:form_embed'=>'📋','module:plan_cta'=>'👑','module:course_enroll_cta'=>'🎓','module:my_learning_link'=>'📚'];
@endphp

<div
    x-data="canvasEditor()"
    x-init="init()"
    x-cloak
    class="flex flex-col h-screen bg-slate-100 overflow-hidden select-none"
    @keydown.ctrl.s.window.prevent="saveCurrentPage()"
>
    {{-- ══════════════════════════════════════════════════════════
         TOOLBAR
    ══════════════════════════════════════════════════════════ --}}
    <div class="h-14 bg-white border-b border-slate-200 flex items-center px-4 gap-3 shrink-0 shadow-sm z-30">

        {{-- Back --}}
        <a href="{{ route('admin.theme-design.canvas') }}"
           class="flex items-center gap-1.5 text-slate-500 hover:text-slate-800 text-sm font-medium transition ml-1 shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            بازگشت
        </a>
        <div class="h-6 w-px bg-slate-200 shrink-0"></div>

        {{-- Title --}}
        <div class="flex items-center gap-2 shrink-0">
            <div class="w-7 h-7 rounded-lg bg-indigo-600 flex items-center justify-center text-white text-xs font-bold">✏️</div>
            <span class="text-sm font-semibold text-slate-800">ویرایشگر Canvas</span>
        </div>
        <div class="h-6 w-px bg-slate-200 shrink-0"></div>

        {{-- Page Tabs --}}
        <div class="flex items-center gap-1.5 overflow-x-auto flex-1 min-w-0" style="scrollbar-width:none;">
            <template x-for="(page, key) in pages" :key="key">
                <button
                    @click="switchPage(key)"
                    :class="key === '__header__'
                        ? (currentPage === key ? 'bg-teal-600 text-white shadow-sm' : 'bg-teal-50 text-teal-700 hover:bg-teal-100 border border-teal-200')
                        : key === '__footer__'
                            ? (currentPage === key ? 'bg-purple-600 text-white shadow-sm' : 'bg-purple-50 text-purple-700 hover:bg-purple-100 border border-purple-200')
                            : (currentPage === key ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200')"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium transition whitespace-nowrap flex items-center gap-1.5 group relative"
                >
                    <span x-show="key === '__header__'" class="text-sm leading-none">🔝</span>
                    <span x-show="key === '__footer__'" class="text-sm leading-none">🔚</span>
                    <span x-text="page.title ?? key"></span>
                    <span
                        x-show="!page.is_home && key !== 'home' && !key.startsWith('__')"
                        @click.stop="deletePage(key)"
                        :class="currentPage === key ? 'text-indigo-200 hover:text-white' : 'text-slate-400 hover:text-red-500'"
                        class="text-xs leading-none transition hidden group-hover:inline"
                        title="حذف صفحه"
                    >×</span>
                </button>
            </template>
            <button
                @click="showAddPage = true"
                class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-medium text-slate-500 hover:bg-slate-100 hover:text-indigo-600 transition whitespace-nowrap"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                صفحه جدید
            </button>
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-2 shrink-0">

            {{-- Dirty indicator --}}
            <span x-show="isDirty" class="text-amber-500 text-xs font-medium flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse inline-block"></span>
                ذخیره نشده
            </span>

            {{-- Global Styles --}}
            <button
                @click="showGlobalStyles = !showGlobalStyles"
                :class="showGlobalStyles ? 'bg-violet-50 border-violet-300 text-violet-700' : 'border-slate-200 text-slate-600 hover:border-violet-300 hover:text-violet-600'"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-medium transition"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
                رنگ‌ها
            </button>

            {{-- Preview --}}
            <a href="{{ url('/') }}" target="_blank"
               class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:border-indigo-300 hover:text-indigo-600 text-xs font-medium transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                پیش‌نمایش
            </a>

            {{-- Save --}}
            <button
                @click="saveCurrentPage()"
                :disabled="saving"
                class="flex items-center gap-1.5 px-4 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium transition disabled:opacity-60"
            >
                <svg x-show="!saving" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                <svg x-show="saving" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                <span x-text="saving ? 'ذخیره...' : 'ذخیره'"></span>
            </button>
        </div>
    </div>

    {{-- Global Styles Drawer --}}
    <div
        x-show="showGlobalStyles"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        class="bg-white border-b border-violet-200 px-6 py-4 flex flex-wrap items-end gap-4 z-20 shrink-0 shadow-sm"
    >
        <div class="text-xs font-bold text-violet-700 flex items-center gap-1.5 self-center ml-2">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
            استایل‌های سراسری
        </div>
        <div>
            <label class="text-[11px] text-slate-500 block mb-1">رنگ اصلی</label>
            <div class="flex items-center gap-1.5">
                <input type="color" x-model="globalStyles.primary_color" @input="gsIsDirty=true" class="w-8 h-8 rounded-lg border border-slate-200 cursor-pointer p-0.5">
                <input type="text"  x-model="globalStyles.primary_color" @input="gsIsDirty=true" class="w-24 text-xs px-2 py-1.5 rounded-lg border border-slate-200 font-mono focus:outline-none focus:ring-1 focus:ring-violet-300">
            </div>
        </div>
        <div>
            <label class="text-[11px] text-slate-500 block mb-1">رنگ ثانویه</label>
            <div class="flex items-center gap-1.5">
                <input type="color" x-model="globalStyles.secondary_color" @input="gsIsDirty=true" class="w-8 h-8 rounded-lg border border-slate-200 cursor-pointer p-0.5">
                <input type="text"  x-model="globalStyles.secondary_color" @input="gsIsDirty=true" class="w-24 text-xs px-2 py-1.5 rounded-lg border border-slate-200 font-mono focus:outline-none focus:ring-1 focus:ring-violet-300">
            </div>
        </div>
        <div>
            <label class="text-[11px] text-slate-500 block mb-1">رنگ پس‌زمینه</label>
            <div class="flex items-center gap-1.5">
                <input type="color" x-model="globalStyles.background_color" @input="gsIsDirty=true" class="w-8 h-8 rounded-lg border border-slate-200 cursor-pointer p-0.5">
                <input type="text"  x-model="globalStyles.background_color" @input="gsIsDirty=true" class="w-24 text-xs px-2 py-1.5 rounded-lg border border-slate-200 font-mono focus:outline-none focus:ring-1 focus:ring-violet-300">
            </div>
        </div>
        <div>
            <label class="text-[11px] text-slate-500 block mb-1">فونت</label>
            <input type="text" x-model="globalStyles.font_family" @input="gsIsDirty=true" class="w-40 text-xs px-2 py-1.5 rounded-lg border border-slate-200 font-mono focus:outline-none focus:ring-1 focus:ring-violet-300" placeholder="Vazirmatn">
        </div>
        <button
            @click="saveGlobalStyles()"
            :disabled="!gsIsDirty || gsSaving"
            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-violet-600 hover:bg-violet-700 text-white text-xs font-medium transition disabled:opacity-50"
        >
            <svg x-show="!gsSaving" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <svg x-show="gsSaving" class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
            <span x-text="gsSaving ? 'ذخیره...' : 'اعمال رنگ‌ها'"></span>
        </button>
        <button @click="showGlobalStyles=false" class="mr-auto text-slate-400 hover:text-slate-600 text-lg leading-none">×</button>
    </div>

    {{-- ══════════════════════════════════════════════════════════
         3-PANEL MAIN LAYOUT
    ══════════════════════════════════════════════════════════ --}}
    <div class="flex flex-1 overflow-hidden">

        {{-- ── PANEL A: Block Library (RTL = right side) ─────────── --}}
        <div class="w-72 bg-white border-l border-slate-200 flex flex-col overflow-hidden shrink-0 z-20">

            <div class="px-4 py-3 border-b border-slate-100 shrink-0">
                <div class="relative">
                    <input
                        x-model="librarySearch"
                        type="text"
                        placeholder="جستجوی ویجت..."
                        class="w-full text-sm pr-8 pl-3 py-2 rounded-lg border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 placeholder-slate-400"
                    >
                    <svg class="w-4 h-4 text-slate-400 absolute top-2.5 right-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto">
                @foreach($orderedCats as $catKey)
                    @if(!isset($byCategory[$catKey])) @continue @endif
                    @php
                        $catLabel  = $catLabels[$catKey] ?? $catKey;
                        $catIcon   = $catIcons[$catKey] ?? '▪';
                        $catBlocks = $byCategory[$catKey];
                        $badgeClass = $catColors[$catKey] ?? 'text-slate-600 bg-slate-100';
                    @endphp
                    <div x-show="isCategoryVisible('{{ $catKey }}')" class="border-b border-slate-100">
                        <button
                            @click="toggleCategory('{{ $catKey }}')"
                            class="w-full flex items-center justify-between px-4 py-2.5 hover:bg-slate-50 transition text-right"
                        >
                            <div class="flex items-center gap-2">
                                <span class="text-sm">{{ $catIcon }}</span>
                                <span class="text-xs font-semibold text-slate-700">{{ $catLabel }}</span>
                                <span class="text-xs text-slate-400 font-normal">({{ count($catBlocks) }})</span>
                            </div>
                            <svg :class="expandedCats['{{ $catKey }}'] ? 'rotate-180' : ''" class="w-3.5 h-3.5 text-slate-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>

                        <div x-show="expandedCats['{{ $catKey }}']" x-collapse>
                            <div class="px-2 pb-2 grid grid-cols-2 gap-1.5">
                                @foreach($catBlocks as $blockType => $blockDef)
                                    <button
                                        @click="addBlock('{{ $blockType }}')"
                                        x-show="isBlockVisible('{{ $blockType }}', '{{ addslashes($blockDef['name']) }}')"
                                        class="flex flex-col items-start gap-1 p-2.5 rounded-lg border border-slate-200 bg-white hover:border-indigo-400 hover:bg-indigo-50 hover:shadow-sm transition text-right cursor-pointer group"
                                        title="{{ $blockDef['name'] }}"
                                    >
                                        <div class="text-lg leading-none">{{ $blockEmoji[$blockType] ?? '🧩' }}</div>
                                        <span class="text-[11px] font-medium text-slate-700 group-hover:text-indigo-700 leading-tight line-clamp-2">{{ $blockDef['name'] }}</span>
                                        @if(!empty($blockDef['requires']))
                                            <span class="text-[9px] px-1.5 py-0.5 rounded {{ $badgeClass }} font-medium leading-none mt-0.5">{{ $blockDef['requires'] }}</span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ── PANEL B: Canvas (center) ──────────────────────────── --}}
        <div class="flex-1 overflow-y-auto flex flex-col items-center px-6 py-6 gap-4">

            <div class="w-full max-w-2xl flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div :class="currentPage === '__header__' ? 'bg-teal-500' : currentPage === '__footer__' ? 'bg-purple-500' : 'bg-indigo-500'" class="w-2 h-2 rounded-full"></div>
                    <span class="text-xs font-semibold text-slate-600" x-text="pages[currentPage]?.title ?? currentPage"></span>
                    <span x-show="currentPage === '__header__'" class="text-[10px] px-1.5 py-0.5 rounded bg-teal-50 text-teal-700 border border-teal-200 font-medium">بلاک‌های هدر سایت</span>
                    <span x-show="currentPage === '__footer__'" class="text-[10px] px-1.5 py-0.5 rounded bg-purple-50 text-purple-700 border border-purple-200 font-medium">بلاک‌های فوتر سایت</span>
                    <span class="text-xs text-slate-400" x-text="'(' + blocks.length + ' ویجت)'"></span>
                </div>
                <span class="text-[10px] text-slate-400">Ctrl+S برای ذخیره</span>
            </div>

            <div id="canvas-blocks" class="w-full max-w-2xl space-y-2 min-h-48">
                <template x-for="(block, index) in blocks" :key="block.id">
                    <div
                        @click="selectBlock(index)"
                        :class="selectedIndex === index
                            ? 'border-indigo-500 shadow-md bg-white ring-2 ring-indigo-200'
                            : 'border-slate-200 bg-white hover:border-indigo-300 hover:shadow-sm'"
                        class="rounded-xl border-2 cursor-pointer transition group relative"
                    >
                        <div class="flex items-center gap-3 p-3">
                            <span class="drag-handle cursor-grab text-slate-300 hover:text-slate-500 text-lg leading-none select-none px-1 shrink-0" title="کشیدن برای جابجایی">⠿</span>

                            <div :class="blockCategoryColor(block.type)" class="w-1 self-stretch rounded-full shrink-0"></div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-slate-800" x-text="blockLabel(block.type)"></span>
                                    <span x-show="block.type.startsWith('module:')" class="text-[10px] px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200 font-medium">ماژول</span>
                                </div>
                                <div class="text-xs text-slate-400 truncate mt-0.5" x-text="blockPreview(block)"></div>
                            </div>

                            <span class="text-[10px] text-slate-300 font-mono shrink-0" x-text="index + 1"></span>

                            <div class="flex flex-col gap-0.5 shrink-0 opacity-0 group-hover:opacity-100 transition">
                                <button @click.stop="moveBlock(index, index - 1)" :disabled="index === 0"
                                    class="w-5 h-5 flex items-center justify-center rounded text-slate-400 hover:text-slate-700 hover:bg-slate-100 disabled:opacity-30 transition text-xs">▲</button>
                                <button @click.stop="moveBlock(index, index + 1)" :disabled="index === blocks.length - 1"
                                    class="w-5 h-5 flex items-center justify-center rounded text-slate-400 hover:text-slate-700 hover:bg-slate-100 disabled:opacity-30 transition text-xs">▼</button>
                            </div>

                            <button
                                @click.stop="removeBlock(index)"
                                class="shrink-0 w-7 h-7 flex items-center justify-center rounded-lg text-slate-300 hover:text-red-500 hover:bg-red-50 transition opacity-0 group-hover:opacity-100 text-sm"
                                title="حذف ویجت"
                            >×</button>
                        </div>
                    </div>
                </template>

                <div x-show="blocks.length === 0" class="flex flex-col items-center justify-center py-16 text-center">
                    <div class="text-5xl mb-4 opacity-30">🧩</div>
                    <p class="text-slate-400 text-sm font-medium">این صفحه هنوز ویجتی ندارد</p>
                    <p class="text-slate-300 text-xs mt-1">از کتابخانه سمت راست ویجت اضافه کنید</p>
                </div>
            </div>

            <div x-show="blocks.length > 0" class="w-full max-w-2xl">
                <button
                    @click="showLibraryHighlight = true"
                    class="w-full py-3 rounded-xl border-2 border-dashed border-slate-200 text-slate-400 hover:border-indigo-300 hover:text-indigo-500 hover:bg-indigo-50/50 transition text-sm font-medium flex items-center justify-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    افزودن ویجت جدید
                </button>
            </div>
        </div>

        {{-- ── PANEL C: Props Panel (LTR = left side) ─────────────── --}}
        <div class="w-80 bg-white border-r border-slate-200 flex flex-col overflow-hidden shrink-0 z-20">

            <div x-show="selectedIndex < 0" class="flex flex-col items-center justify-center h-full text-center px-6">
                <div class="text-4xl mb-3 opacity-20">✏️</div>
                <p class="text-slate-400 text-sm">یک ویجت از Canvas انتخاب کنید</p>
                <p class="text-slate-300 text-xs mt-1">برای ویرایش تنظیمات</p>
            </div>

            <template x-if="selectedIndex >= 0 && selectedBlock">
                <div class="flex flex-col h-full overflow-hidden">

                    <div class="px-4 py-3 border-b border-slate-100 shrink-0">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div :class="blockCategoryColor(selectedBlock.type)" class="w-2 h-2 rounded-full"></div>
                                <span class="text-sm font-bold text-slate-800" x-text="blockLabel(selectedBlock.type)"></span>
                            </div>
                            <button @click="selectedIndex = -1" class="text-slate-400 hover:text-slate-600 text-lg leading-none">×</button>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1 font-mono" x-text="selectedBlock.type"></p>
                    </div>

                    <div class="flex-1 overflow-y-auto px-4 py-4 space-y-4">
                        <template x-for="(schemaDef, propKey) in selectedSchema" :key="propKey">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5" x-text="schemaDef.label || propKey"></label>

                                {{-- string --}}
                                <template x-if="schemaDef.type === 'string'">
                                    <input
                                        type="text"
                                        x-model="selectedBlock.props[propKey]"
                                        @input="isDirty = true"
                                        class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 transition"
                                        :placeholder="schemaDef.default ?? ''"
                                    >
                                </template>

                                {{-- number --}}
                                <template x-if="schemaDef.type === 'number'">
                                    <input
                                        type="number"
                                        x-model.number="selectedBlock.props[propKey]"
                                        @input="isDirty = true"
                                        class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 transition"
                                        :min="0"
                                    >
                                </template>

                                {{-- boolean --}}
                                <template x-if="schemaDef.type === 'boolean'">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <div class="relative">
                                            <input type="checkbox" x-model="selectedBlock.props[propKey]" @change="isDirty = true" class="sr-only peer">
                                            <div class="w-9 h-5 bg-slate-200 peer-checked:bg-indigo-500 rounded-full transition"></div>
                                            <div class="absolute top-0.5 right-0.5 w-4 h-4 bg-white rounded-full shadow peer-checked:translate-x-full rtl:peer-checked:-translate-x-full transition-transform"></div>
                                        </div>
                                        <span class="text-sm text-slate-700" x-text="selectedBlock.props[propKey] ? 'فعال' : 'غیرفعال'"></span>
                                    </label>
                                </template>

                                {{-- select --}}
                                <template x-if="schemaDef.type === 'select'">
                                    <select
                                        x-model="selectedBlock.props[propKey]"
                                        @change="isDirty = true"
                                        class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 transition"
                                    >
                                        <template x-for="(label, val) in schemaDef.options" :key="val">
                                            <option :value="val" x-text="label"></option>
                                        </template>
                                    </select>
                                </template>

                                {{-- color --}}
                                <template x-if="schemaDef.type === 'color'">
                                    <div class="flex items-center gap-2">
                                        <input type="color" x-model="selectedBlock.props[propKey]" @input="isDirty = true" class="w-10 h-10 rounded-lg border border-slate-200 cursor-pointer p-0.5">
                                        <input type="text"  x-model="selectedBlock.props[propKey]" @input="isDirty = true" class="flex-1 text-sm px-3 py-2 rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300 font-mono" placeholder="#000000">
                                    </div>
                                </template>

                                {{-- textarea --}}
                                <template x-if="schemaDef.type === 'textarea'">
                                    <textarea
                                        x-model="selectedBlock.props[propKey]"
                                        @input="isDirty = true"
                                        rows="3"
                                        class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 transition resize-y"
                                        :placeholder="schemaDef.default ?? ''"
                                    ></textarea>
                                </template>
                            </div>
                        </template>

                        <div x-show="Object.keys(selectedSchema).length === 0" class="text-center py-6">
                            <p class="text-xs text-slate-400">این ویجت تنظیماتی ندارد</p>
                        </div>
                    </div>

                    <div class="px-4 py-3 border-t border-slate-100 shrink-0">
                        <button
                            @click="removeBlock(selectedIndex)"
                            class="w-full flex items-center justify-center gap-2 py-2 rounded-lg border border-red-200 text-red-500 hover:bg-red-50 text-xs font-medium transition"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            حذف این ویجت
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- ══════════════════ ADD PAGE MODAL ══════════════════ --}}
    <div x-show="showAddPage" x-transition.opacity class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div @click.outside="showAddPage = false" class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
            <h3 class="text-base font-bold text-slate-800 mb-4">افزودن صفحه جدید</h3>
            <div class="space-y-3">
                <div>
                    <label class="text-xs font-semibold text-slate-600 mb-1.5 block">عنوان صفحه *</label>
                    <input x-model="newPageTitle" type="text" class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-300" placeholder="مثال: درباره ما" @keydown.enter="confirmAddPage()">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 mb-1.5 block">اسلاگ (URL) <span class="text-slate-400 font-normal">اختیاری — فقط a-z و -</span></label>
                    <input x-model="newPageSlug" type="text" class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-300" placeholder="about-us">
                </div>
            </div>
            <div class="flex gap-2 mt-5">
                <button @click="confirmAddPage()" :disabled="!newPageTitle" class="flex-1 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 disabled:opacity-50 transition">افزودن</button>
                <button @click="showAddPage = false; newPageTitle=''; newPageSlug=''" class="flex-1 py-2 rounded-lg border border-slate-200 text-slate-600 text-sm font-medium hover:bg-slate-50 transition">انصراف</button>
            </div>
        </div>
    </div>

    {{-- Toast --}}
    <div x-show="toast.show" x-transition.opacity
         :class="toast.type === 'success' ? 'bg-green-600' : 'bg-red-600'"
         class="fixed bottom-6 left-1/2 -translate-x-1/2 text-white text-sm font-medium px-4 py-2.5 rounded-xl shadow-lg z-50 flex items-center gap-2">
        <span x-text="toast.type === 'success' ? '✓' : '✗'"></span>
        <span x-text="toast.msg"></span>
    </div>
</div>
@endsection

@push('scripts')
<script>
const CSRF_TOKEN    = document.querySelector('meta[name="csrf-token"]').content;
const BLOCK_LABELS  = @json($blockLabels);
const BLOCK_SCHEMAS = @json($blockSchemas);
const INITIAL_PAGES = @json($pages);
const GLOBAL_STYLES = @json($globalStyles);
const ROUTES = {
    saveBlocks:    '{{ route('admin.theme-design.canvas-save-blocks') }}',
    addPage:       '{{ route('admin.theme-design.canvas-add-page') }}',
    deletePage:    '{{ url('admin/theme-design/canvas/pages') }}',
    globalStyles:  '{{ route('admin.theme-design.canvas-global-styles') }}',
};

function canvasEditor() {
    return {
        // State
        pages:         JSON.parse(JSON.stringify(INITIAL_PAGES)),
        currentPage:   Object.keys(INITIAL_PAGES)[0] ?? 'home',
        selectedIndex: -1,
        isDirty:       false,
        saving:        false,
        showAddPage:   false,
        newPageTitle:  '',
        newPageSlug:   '',
        librarySearch: '',
        expandedCats:  { layout:true, content:true, module:true, dynamic:false, ecommerce:false, marketing:false, utility:false },
        showLibraryHighlight: false,
        showGlobalStyles: false,
        globalStyles:  JSON.parse(JSON.stringify(GLOBAL_STYLES)),
        gsIsDirty:     false,
        gsSaving:      false,
        toast: { show: false, msg: '', type: 'success', _timer: null },

        // Computed
        get blocks() {
            return this.pages[this.currentPage]?.blocks ?? [];
        },
        set blocks(val) {
            if (this.pages[this.currentPage]) this.pages[this.currentPage].blocks = val;
        },
        get selectedBlock() {
            if (this.selectedIndex < 0 || this.selectedIndex >= this.blocks.length) return null;
            return this.blocks[this.selectedIndex];
        },
        get selectedSchema() {
            if (!this.selectedBlock) return {};
            return BLOCK_SCHEMAS[this.selectedBlock.type]?.schema ?? {};
        },

        // Block library
        toggleCategory(key)   { this.expandedCats[key] = !this.expandedCats[key]; },
        isCategoryVisible(key) {
            if (!this.librarySearch) return true;
            const s = this.librarySearch.toLowerCase();
            return Object.keys(BLOCK_LABELS).some(t => {
                const label = (BLOCK_LABELS[t] ?? '').toLowerCase();
                return t.includes(s) || label.includes(s);
            });
        },
        isBlockVisible(type, name) {
            if (!this.librarySearch) return true;
            const s = this.librarySearch.toLowerCase();
            return type.toLowerCase().includes(s) || name.toLowerCase().includes(s);
        },

        // Canvas actions
        addBlock(type) {
            const defaults = JSON.parse(JSON.stringify(BLOCK_SCHEMAS[type]?.defaultProps ?? {}));
            const block = { id: 'blk_' + Date.now() + '_' + Math.random().toString(36).slice(2,6), type, props: defaults };
            const newBlocks = [...this.blocks, block];
            this.pages[this.currentPage].blocks = newBlocks;
            this.selectedIndex = newBlocks.length - 1;
            this.isDirty = true;
            this.$nextTick(() => {
                const el = document.getElementById('canvas-blocks');
                el && el.lastElementChild && el.lastElementChild.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            });
        },
        removeBlock(index) {
            const arr = [...this.blocks];
            arr.splice(index, 1);
            this.pages[this.currentPage].blocks = arr;
            if (this.selectedIndex >= arr.length) this.selectedIndex = arr.length - 1;
            this.isDirty = true;
        },
        selectBlock(index) { this.selectedIndex = index; },
        moveBlock(from, to) {
            if (to < 0 || to >= this.blocks.length) return;
            const arr = [...this.blocks];
            const [item] = arr.splice(from, 1);
            arr.splice(to, 0, item);
            this.pages[this.currentPage].blocks = arr;
            this.selectedIndex = to;
            this.isDirty = true;
        },

        // Page management
        switchPage(key) {
            if (this.isDirty) this.saveCurrentPage();
            this.currentPage   = key;
            this.selectedIndex = -1;
        },
        deletePage(key) {
            if (key === 'home' || key.startsWith('__')) return;
            if (!confirm('این صفحه حذف شود؟')) return;
            fetch(ROUTES.deletePage + '/' + key, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' }
            }).then(r => r.json()).then(d => {
                if (d.success) {
                    delete this.pages[key];
                    if (this.currentPage === key) this.currentPage = Object.keys(this.pages)[0];
                    this.showToast('صفحه حذف شد.', 'success');
                } else {
                    this.showToast(d.error ?? 'خطا در حذف صفحه', 'error');
                }
            }).catch(() => this.showToast('خطای شبکه', 'error'));
        },
        confirmAddPage() {
            if (!this.newPageTitle.trim()) return;
            fetch(ROUTES.addPage, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                body: JSON.stringify({ title: this.newPageTitle, slug: this.newPageSlug })
            }).then(r => r.json()).then(d => {
                if (d.success) {
                    this.pages[d.key] = d.page;
                    this.showAddPage  = false;
                    this.newPageTitle = '';
                    this.newPageSlug  = '';
                    this.switchPage(d.key);
                    this.showToast('صفحه افزوده شد.', 'success');
                } else {
                    this.showToast(d.error ?? 'خطا در افزودن صفحه', 'error');
                }
            }).catch(() => this.showToast('خطای شبکه', 'error'));
        },

        // Save
        async saveCurrentPage() {
            if (this.saving) return;
            this.saving = true;
            try {
                const r = await fetch(ROUTES.saveBlocks, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                    body: JSON.stringify({ page_key: this.currentPage, blocks: this.blocks })
                });
                const d = await r.json();
                if (d.success) { this.isDirty = false; this.showToast('ذخیره شد ✓', 'success'); }
                else this.showToast(d.error ?? 'خطا در ذخیره', 'error');
            } catch { this.showToast('خطای شبکه', 'error'); }
            finally { this.saving = false; }
        },

        async saveGlobalStyles() {
            if (this.gsSaving) return;
            this.gsSaving = true;
            try {
                const r = await fetch(ROUTES.globalStyles, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                    body: JSON.stringify(this.globalStyles)
                });
                const d = await r.json();
                if (d.success) { this.gsIsDirty = false; this.showToast('رنگ‌ها ذخیره شد ✓', 'success'); }
                else this.showToast(d.error ?? 'خطا', 'error');
            } catch { this.showToast('خطای شبکه', 'error'); }
            finally { this.gsSaving = false; }
        },

        // Helpers
        blockLabel(type)   { return BLOCK_LABELS[type] ?? type; },
        blockPreview(block) {
            const d = block?.props ?? block?.data ?? {};
            return d.title || d.text || d.content || d.name || d.subtitle || d.label || '';
        },
        blockCategoryColor(type) {
            if (!type) return 'bg-slate-300';
            if (type.startsWith('module:blog'))   return 'bg-blue-500';
            if (type.startsWith('module:shop'))   return 'bg-emerald-500';
            if (type.startsWith('module:course')) return 'bg-violet-500';
            if (type.startsWith('module:form'))   return 'bg-amber-500';
            if (type.startsWith('module:pricing') || type.startsWith('module:plan') || type.startsWith('module:subscriber')) return 'bg-pink-500';
            if (type.startsWith('module:'))       return 'bg-indigo-500';
            if (type === 'hero' || type === 'cta') return 'bg-purple-500';
            if (['section','grid','columns','slider','accordion'].includes(type)) return 'bg-blue-400';
            if (['heading','text','image','gallery','video','button','divider','counter'].includes(type)) return 'bg-green-500';
            if (['blog_posts','testimonials','product_grid','product_slider'].includes(type)) return 'bg-purple-400';
            if (['countdown','promo_banner'].includes(type)) return 'bg-orange-400';
            return 'bg-slate-400';
        },

        showToast(msg, type = 'success') {
            clearTimeout(this.toast._timer);
            this.toast = { show: true, msg, type, _timer: setTimeout(() => this.toast.show = false, 3200) };
        },

        init() {
            // Normalize legacy blocks: block.data → block.props
            Object.values(this.pages).forEach(page => {
                (page.blocks ?? []).forEach(block => {
                    if (!block.props && block.data) block.props = block.data;
                    if (!block.props) block.props = {};
                });
            });

            this.$nextTick(() => {
                const el = document.getElementById('canvas-blocks');
                if (el && typeof Sortable !== 'undefined') {
                    Sortable.create(el, {
                        animation: 180,
                        handle: '.drag-handle',
                        ghostClass: 'opacity-40',
                        chosenClass: 'ring-2 ring-indigo-400',
                        onEnd: (evt) => {
                            if (evt.oldIndex === evt.newIndex) return;
                            const arr = [...this.blocks];
                            const [item] = arr.splice(evt.oldIndex, 1);
                            arr.splice(evt.newIndex, 0, item);
                            this.pages[this.currentPage].blocks = arr;
                            this.selectedIndex = evt.newIndex;
                            this.isDirty = true;
                        }
                    });
                }
            });

            window.addEventListener('beforeunload', (e) => {
                if (this.isDirty) { e.preventDefault(); e.returnValue = ''; }
            });
        }
    };
}
</script>
@endpush
