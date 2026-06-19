@extends('layouts.canvas-editor')

@section('title', 'ویرایشگر قالب Canvas')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
@endpush

@section('content')
@php
$blockLabels  = collect($registry)->mapWithKeys(fn($d,$t)=>[$t=>$d['name']]);
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

            {{-- Undo / Redo --}}
            <button @click="undo()" :disabled="historyIndex <= 0"
                title="واگرد (Ctrl+Z)"
                class="flex items-center justify-center w-7 h-7 rounded-lg border border-slate-200 text-slate-500 hover:text-indigo-600 hover:border-indigo-300 disabled:opacity-30 transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6M3 10l6-6"/></svg>
            </button>
            <button @click="redo()" :disabled="historyIndex >= history.length - 1"
                title="از نو (Ctrl+Y)"
                class="flex items-center justify-center w-7 h-7 rounded-lg border border-slate-200 text-slate-500 hover:text-indigo-600 hover:border-indigo-300 disabled:opacity-30 transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10H11a8 8 0 00-8 8v2M21 10l-6 6M21 10l-6-6"/></svg>
            </button>
            <div class="h-6 w-px bg-slate-200 shrink-0"></div>

            {{-- Draft badge --}}
            <span class="flex items-center gap-1 text-[10px] px-2 py-1 rounded-full bg-amber-50 border border-amber-200 text-amber-700 font-semibold">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse inline-block"></span>
                پیش‌نویس
            </span>

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

            {{-- Preview draft --}}
            <a href="{{ url('/canvas-preview?token=' . $previewToken) }}" target="_blank"
               class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:border-teal-300 hover:text-teal-700 text-xs font-medium transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                پیش‌نمایش
            </a>

            {{-- Save draft --}}
            <button
                @click="saveCurrentPage()"
                :disabled="saving"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-indigo-300 text-indigo-700 hover:bg-indigo-50 text-xs font-medium transition disabled:opacity-60"
            >
                <svg x-show="!saving" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                <svg x-show="saving" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                <span x-text="saving ? 'ذخیره...' : 'ذخیره پیش‌نویس'"></span>
            </button>

            {{-- Discard draft --}}
            <form method="POST" action="{{ route('admin.theme-design.canvas-discard-draft') }}" onsubmit="return confirm('تغییرات پیش‌نویس رد شود؟')">
                @csrf
                <button type="submit" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 text-xs font-medium transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    رد تغییرات
                </button>
            </form>

            {{-- Publish --}}
            <button
                @click="publishDraft()"
                :disabled="publishing"
                class="flex items-center gap-1.5 px-4 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold transition disabled:opacity-60"
            >
                <svg x-show="!publishing" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <svg x-show="publishing" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                <span x-text="publishing ? 'انتشار...' : 'انتشار'"></span>
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
            @click="saveGlobalStylesAndReload()"
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

            {{-- TB4: Tab toggle: بلوک‌ها / ساختار --}}
            <div class="flex border-b border-slate-100 shrink-0">
                <button @click="leftPanelTab='blocks'"
                    :class="leftPanelTab==='blocks' ? 'border-b-2 border-indigo-500 text-indigo-600 font-semibold' : 'text-slate-500 hover:text-slate-700'"
                    class="flex-1 py-2 text-xs text-center transition">بلوک‌ها</button>
                <button @click="leftPanelTab='tree'"
                    :class="leftPanelTab==='tree' ? 'border-b-2 border-indigo-500 text-indigo-600 font-semibold' : 'text-slate-500 hover:text-slate-700'"
                    class="flex-1 py-2 text-xs text-center transition">ساختار</button>
            </div>

            {{-- TB4: Structure Tree Tab --}}
            <div x-show="leftPanelTab==='tree'" class="flex-1 overflow-y-auto">
                <div x-show="flattenTree().length === 0" class="flex flex-col items-center justify-center py-10 text-center text-slate-400">
                    <svg class="w-8 h-8 mb-2 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    <p class="text-xs">هنوز بلوکی اضافه نشده</p>
                </div>
                <div class="py-2">
                    <template x-for="node in flattenTree()" :key="node.block.id + node.depth">
                        <div
                            @click="selectBlock(node.path)"
                            :style="'padding-right:' + (12 + node.depth * 14) + 'px'"
                            :class="isSelectedPath(node.path) ? 'bg-indigo-50 border-r-2 border-indigo-500' : 'hover:bg-slate-50'"
                            class="flex items-center gap-2 px-3 py-1.5 cursor-pointer transition text-xs">
                            <span :class="blockCategoryColor(node.block.type)" class="w-2 h-2 rounded-full shrink-0"></span>
                            <template x-if="isContainerType(node.block.type)">
                                <svg @click.stop="toggleTreeNode(node.block.id)"
                                    class="w-3 h-3 text-slate-400 shrink-0 transition-transform"
                                    :class="treeExpanded[node.block.id] === false ? '-rotate-90' : ''"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </template>
                            <template x-if="!isContainerType(node.block.type)">
                                <span class="w-3 h-3 shrink-0"></span>
                            </template>
                            <span class="flex-1 truncate" :class="isSelectedPath(node.path) ? 'text-indigo-700 font-medium' : 'text-slate-700'"
                                  x-text="node.block.props?.label ? (blockLabel(node.block.type) + ': ' + node.block.props.label) : blockLabel(node.block.type)"></span>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Blocks Library Search (blocks tab only) --}}
            <div x-show="leftPanelTab==='blocks'" class="px-4 py-3 border-b border-slate-100 shrink-0">
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

            <div x-show="leftPanelTab==='blocks'" class="flex-1 overflow-y-auto">
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
                                        draggable="true"
                                        @click="addBlock('{{ $blockType }}')"
                                        @dragstart="startDragBlock($event, '{{ $blockType }}')"
                                        @dragend="endDragBlock()"
                                        x-show="isBlockVisible('{{ $blockType }}', '{{ addslashes($blockDef['name']) }}')"
                                        class="flex flex-col items-start gap-1 p-2.5 rounded-lg border border-slate-200 bg-white hover:border-indigo-400 hover:bg-indigo-50 hover:shadow-sm transition text-right cursor-grab active:cursor-grabbing group"
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

            <div x-show="leftPanelTab==='blocks'" class="px-3 py-2 border-t border-slate-100 shrink-0 bg-slate-50">
                <p class="text-[10px] text-slate-400 text-center">کلیک یا بکشید برای افزودن</p>
            </div>
        </div>

        {{-- ── PANEL B: Live Canvas (center) ──────────────────────── --}}
        <div class="flex-1 flex flex-col overflow-hidden bg-slate-200">

            {{-- Canvas Toolbar --}}
            <div class="h-10 bg-white border-b border-slate-200 flex items-center px-4 gap-3 shrink-0 z-10">
                {{-- Device Toggle --}}
                <div class="flex items-center gap-0.5 bg-slate-100 rounded-lg p-0.5">
                    <button @click="setDevice('desktop')"
                        :class="device==='desktop' ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-500 hover:text-slate-700'"
                        class="flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        دسکتاپ
                    </button>
                    <button @click="setDevice('tablet')"
                        :class="device==='tablet' ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-500 hover:text-slate-700'"
                        class="flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        تبلت
                    </button>
                    <button @click="setDevice('mobile')"
                        :class="device==='mobile' ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-500 hover:text-slate-700'"
                        class="flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        موبایل
                    </button>
                </div>
                <div class="h-4 w-px bg-slate-200 shrink-0"></div>
                <span class="text-xs text-slate-500" x-text="blocks.length + ' بلوک — ' + (pages[currentPage]?.title ?? currentPage)"></span>
                <span x-show="quickSaving" class="text-xs text-indigo-500 flex items-center gap-1 mr-auto">
                    <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                    ذخیره...
                </span>
            </div>

            {{-- Canvas Area --}}
            <div class="flex-1 overflow-auto py-4 flex justify-center">
                <div
                    class="relative bg-white shadow-xl transition-all duration-300 mx-auto"
                    :style="device==='desktop' ? 'width:100%;max-width:none' : device==='tablet' ? 'width:768px;max-width:100%' : 'width:390px;max-width:100%'"
                    style="min-height:500px;"
                >
                    {{-- Live Preview Iframe --}}
                    <iframe
                        id="tb-live-iframe"
                        :src="livePreviewUrl()"
                        class="w-full border-0 block"
                        style="min-height:500px;"
                        @load="onIframeLoad()"
                        title="پیش‌نمایش زنده"
                    ></iframe>

                    {{-- Drag overlay indicator (TB3-03) --}}
                    <div
                        x-show="isDraggingBlock"
                        class="absolute inset-0 z-20 flex items-start justify-center pt-24 pointer-events-none"
                        style="background:rgba(99,102,241,0.06);border:3px dashed #6366f1;"
                    >
                        <div class="bg-white rounded-2xl shadow-xl px-8 py-5 text-center pointer-events-none">
                            <div class="text-3xl mb-2">🎯</div>
                            <p class="text-sm font-bold text-indigo-700">رها کنید تا بلوک اضافه شود</p>
                            <p class="text-xs text-slate-400 mt-1" x-text="'نوع: ' + (draggedBlockLabel || draggedBlockType)"></p>
                        </div>
                    </div>

                    {{-- Drop receiver (pointer-events on) --}}
                    <div
                        x-show="isDraggingBlock"
                        class="absolute inset-0 z-10"
                        @dragover.prevent="onDragOverCanvas($event)"
                        @dragleave="onDragLeaveCanvas()"
                        @drop.prevent="onDropToCanvas($event)"
                    ></div>

                    {{-- Loading overlay --}}
                    <div x-show="iframeLoading" class="absolute inset-0 z-30 bg-white flex items-center justify-center" style="min-height:200px;">
                        <div class="flex items-center gap-3 text-slate-400">
                            <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                            <span class="text-sm">در حال بارگذاری...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── PANEL C: Props Panel (LTR = left side) ─────────────── --}}
        <div class="w-80 bg-white border-r border-slate-200 flex flex-col overflow-hidden shrink-0 z-20">

            <div x-show="!selectedPath" class="flex flex-col items-center justify-center h-full text-center px-6">
                <div class="text-4xl mb-3 opacity-20">✏️</div>
                <p class="text-slate-400 text-sm">یک بلوک از Canvas کلیک کنید</p>
                <p class="text-slate-300 text-xs mt-1">برای ویرایش تنظیمات</p>
            </div>

            <template x-if="selectedPath && selectedBlock">
                <div class="flex flex-col h-full overflow-hidden">

                    <div class="px-4 py-3 border-b border-slate-100 shrink-0">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div :class="blockCategoryColor(selectedBlock.type)" class="w-2 h-2 rounded-full"></div>
                                <span class="text-sm font-bold text-slate-800" x-text="blockLabel(selectedBlock.type)"></span>
                            </div>
                            <button @click="selectedPath = null; sendToIframe({action:'select', blockId:null})" class="text-slate-400 hover:text-slate-600 text-lg leading-none">×</button>
                        </div>
                        {{-- Breadcrumb for nested selection --}}
                        <div x-show="selectedPath.length > 1" class="flex items-center gap-1 mt-1 flex-wrap">
                            <template x-for="(pidx, d) in selectedPath.slice(0, -1)" :key="d">
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-600 cursor-pointer hover:bg-indigo-100 transition"
                                      @click="selectBlock(selectedPath.slice(0, d+1))"
                                      x-text="blockLabel(getBlockByPath(selectedPath.slice(0,d+1))?.type ?? '')"></span>
                                <span class="text-slate-300 text-[10px]">›</span>
                            </template>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1 font-mono" x-text="selectedBlock.type"></p>
                    </div>

                    {{-- Block Actions (Move / Duplicate / Delete) — TB4: path-aware --}}
                    <div class="flex items-center gap-1.5 px-3 py-2 border-b border-slate-100 shrink-0">
                        <button @click="moveSelectedUp()" :disabled="!canMoveUp()"
                            title="بالاتر"
                            class="flex-1 flex items-center justify-center gap-1 py-1.5 rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-slate-50 disabled:opacity-30 transition">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/></svg>
                        </button>
                        <button @click="moveSelectedDown()" :disabled="!canMoveDown()"
                            title="پایین‌تر"
                            class="flex-1 flex items-center justify-center gap-1 py-1.5 rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-slate-50 disabled:opacity-30 transition">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <button @click="duplicateSelected()"
                            title="کپی بلوک"
                            class="flex-1 flex items-center justify-center py-1.5 rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-600 transition">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        </button>
                        <button @click="removeSelected()"
                            title="حذف بلوک"
                            class="flex-1 flex items-center justify-center py-1.5 rounded-lg border border-red-200 text-xs font-medium text-red-500 hover:bg-red-50 hover:border-red-400 transition">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>

                    {{-- Tabs: Content / Style / Advanced --}}
                    <div class="flex border-b border-slate-100 shrink-0">
                        <button
                            @click="selectedTab = 'content'"
                            :class="selectedTab === 'content' ? 'border-b-2 border-indigo-500 text-indigo-600 font-semibold' : 'text-slate-500 hover:text-slate-700'"
                            class="flex-1 py-2 text-xs text-center transition">
                            محتوا
                        </button>
                        <button
                            @click="selectedTab = 'style'"
                            :class="selectedTab === 'style' ? 'border-b-2 border-indigo-500 text-indigo-600 font-semibold' : 'text-slate-500 hover:text-slate-700'"
                            class="flex-1 py-2 text-xs text-center transition">
                            استایل
                        </button>
                        <button
                            @click="selectedTab = 'advanced'"
                            :class="selectedTab === 'advanced' ? 'border-b-2 border-indigo-500 text-indigo-600 font-semibold' : 'text-slate-500 hover:text-slate-700'"
                            class="flex-1 py-2 text-xs text-center transition">
                            پیشرفته
                        </button>
                    </div>

                    {{-- Style Tab --}}
                    <div x-show="selectedTab === 'style'" class="flex-1 overflow-y-auto px-3 py-3 space-y-2">

                        {{-- Typography --}}
                        <div class="border border-slate-100 rounded-lg overflow-hidden">
                            <button @click="styleSection.typography = !styleSection.typography"
                                class="w-full flex items-center justify-between px-3 py-2 bg-slate-50 text-xs font-semibold text-slate-700 hover:bg-slate-100 transition">
                                <span>تایپوگرافی</span>
                                <svg class="w-3.5 h-3.5 transition-transform" :class="styleSection.typography ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="styleSection.typography" class="p-3 space-y-3">
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="text-xs text-slate-500 mb-1 block">اندازه فونت (px)</label>
                                        <input type="number" min="8" max="120" :value="selectedBlock.style?.fontSize ?? ''" @input="updateStyle('fontSize', $event.target.value===''?'':Number($event.target.value))" placeholder="پیش‌فرض" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                    </div>
                                    <div>
                                        <label class="text-xs text-slate-500 mb-1 block">وزن فونت</label>
                                        <select :value="selectedBlock.style?.fontWeight ?? ''" @change="updateStyle('fontWeight', $event.target.value)" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                            <option value="">پیش‌فرض</option>
                                            <option value="300">Light</option>
                                            <option value="400">Normal</option>
                                            <option value="500">Medium</option>
                                            <option value="600">SemiBold</option>
                                            <option value="700">Bold</option>
                                            <option value="800">ExtraBold</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-xs text-slate-500 mb-1 block">تراز متن</label>
                                        <select :value="selectedBlock.style?.textAlign ?? ''" @change="updateStyle('textAlign', $event.target.value)" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                            <option value="">پیش‌فرض</option>
                                            <option value="right">راست</option>
                                            <option value="center">وسط</option>
                                            <option value="left">چپ</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-xs text-slate-500 mb-1 block">فاصله خطوط</label>
                                        <input type="number" min="1" max="4" step="0.1" :value="selectedBlock.style?.lineHeight ?? ''" @input="updateStyle('lineHeight', $event.target.value===''?'':Number($event.target.value))" placeholder="۱.۵" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Colors & Gradient --}}
                        <div class="border border-slate-100 rounded-lg overflow-hidden">
                            <button @click="styleSection.colors = !styleSection.colors"
                                class="w-full flex items-center justify-between px-3 py-2 bg-slate-50 text-xs font-semibold text-slate-700 hover:bg-slate-100 transition">
                                <span>رنگ‌ها و گرادیان</span>
                                <svg class="w-3.5 h-3.5 transition-transform" :class="styleSection.colors ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="styleSection.colors" class="p-3 space-y-3">
                                <div>
                                    <label class="text-xs text-slate-500 mb-1 block">رنگ پس‌زمینه</label>
                                    <div class="flex items-center gap-2">
                                        <input type="color" :value="selectedBlock.style?.bgColor || '#ffffff'" @change="updateStyle('bgColor', $event.target.value)" class="w-8 h-8 rounded border border-slate-200 cursor-pointer p-0.5">
                                        <input type="text" :value="selectedBlock.style?.bgColor || ''" @input="updateStyle('bgColor', $event.target.value)" placeholder="بدون رنگ" class="flex-1 text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                        <button x-show="selectedBlock.style?.bgColor" @click="updateStyle('bgColor', '')" class="text-slate-400 hover:text-red-500 text-sm">×</button>
                                    </div>
                                </div>
                                <div>
                                    <label class="text-xs text-slate-500 mb-1 block">رنگ متن</label>
                                    <div class="flex items-center gap-2">
                                        <input type="color" :value="selectedBlock.style?.textColor || '#000000'" @change="updateStyle('textColor', $event.target.value)" class="w-8 h-8 rounded border border-slate-200 cursor-pointer p-0.5">
                                        <input type="text" :value="selectedBlock.style?.textColor || ''" @input="updateStyle('textColor', $event.target.value)" placeholder="بدون رنگ" class="flex-1 text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                        <button x-show="selectedBlock.style?.textColor" @click="updateStyle('textColor', '')" class="text-slate-400 hover:text-red-500 text-sm">×</button>
                                    </div>
                                </div>
                                <div>
                                    <label class="flex items-center gap-2 text-xs text-slate-500 mb-2 cursor-pointer">
                                        <input type="checkbox" :checked="!!(selectedBlock.style?.bgGradientFrom)" @change="toggleGradient($event.target.checked)" class="rounded">
                                        گرادیان پس‌زمینه
                                    </label>
                                    <template x-if="selectedBlock.style?.bgGradientFrom">
                                        <div class="space-y-2">
                                            <div class="grid grid-cols-2 gap-2">
                                                <div>
                                                    <label class="text-xs text-slate-400 mb-1 block">رنگ شروع</label>
                                                    <input type="color" :value="selectedBlock.style?.bgGradientFrom || '#4f46e5'" @change="updateStyle('bgGradientFrom', $event.target.value)" class="w-full h-8 rounded border border-slate-200 cursor-pointer p-0.5">
                                                </div>
                                                <div>
                                                    <label class="text-xs text-slate-400 mb-1 block">رنگ پایان</label>
                                                    <input type="color" :value="selectedBlock.style?.bgGradientTo || '#7c3aed'" @change="updateStyle('bgGradientTo', $event.target.value)" class="w-full h-8 rounded border border-slate-200 cursor-pointer p-0.5">
                                                </div>
                                            </div>
                                            <select :value="selectedBlock.style?.bgGradientDir ?? '135deg'" @change="updateStyle('bgGradientDir', $event.target.value)" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                                <option value="135deg">قطری (↘)</option>
                                                <option value="to bottom">عمودی (↓)</option>
                                                <option value="to right">افقی (→)</option>
                                                <option value="45deg">قطری (↗)</option>
                                            </select>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        {{-- Spacing --}}
                        <div class="border border-slate-100 rounded-lg overflow-hidden">
                            <button @click="styleSection.spacing = !styleSection.spacing"
                                class="w-full flex items-center justify-between px-3 py-2 bg-slate-50 text-xs font-semibold text-slate-700 hover:bg-slate-100 transition">
                                <span>فاصله‌گذاری</span>
                                <svg class="w-3.5 h-3.5 transition-transform" :class="styleSection.spacing ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="styleSection.spacing" class="p-3 space-y-2">
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="text-xs text-slate-400 mb-1 block">پدینگ بالا</label>
                                        <input type="number" min="0" max="200" :value="selectedBlock.style?.paddingTop ?? ''" @input="updateStyle('paddingTop', $event.target.value===''?'':Number($event.target.value))" placeholder="px" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                    </div>
                                    <div>
                                        <label class="text-xs text-slate-400 mb-1 block">پدینگ پایین</label>
                                        <input type="number" min="0" max="200" :value="selectedBlock.style?.paddingBottom ?? ''" @input="updateStyle('paddingBottom', $event.target.value===''?'':Number($event.target.value))" placeholder="px" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                    </div>
                                    <div>
                                        <label class="text-xs text-slate-400 mb-1 block">پدینگ راست</label>
                                        <input type="number" min="0" max="200" :value="selectedBlock.style?.paddingRight ?? ''" @input="updateStyle('paddingRight', $event.target.value===''?'':Number($event.target.value))" placeholder="px" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                    </div>
                                    <div>
                                        <label class="text-xs text-slate-400 mb-1 block">پدینگ چپ</label>
                                        <input type="number" min="0" max="200" :value="selectedBlock.style?.paddingLeft ?? ''" @input="updateStyle('paddingLeft', $event.target.value===''?'':Number($event.target.value))" placeholder="px" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                    </div>
                                    <div>
                                        <label class="text-xs text-slate-400 mb-1 block">مارجین بالا</label>
                                        <input type="number" min="-100" max="200" :value="selectedBlock.style?.marginTop ?? ''" @input="updateStyle('marginTop', $event.target.value===''?'':Number($event.target.value))" placeholder="px" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                    </div>
                                    <div>
                                        <label class="text-xs text-slate-400 mb-1 block">مارجین پایین</label>
                                        <input type="number" min="-100" max="200" :value="selectedBlock.style?.marginBottom ?? ''" @input="updateStyle('marginBottom', $event.target.value===''?'':Number($event.target.value))" placeholder="px" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Border --}}
                        <div class="border border-slate-100 rounded-lg overflow-hidden">
                            <button @click="styleSection.border = !styleSection.border"
                                class="w-full flex items-center justify-between px-3 py-2 bg-slate-50 text-xs font-semibold text-slate-700 hover:bg-slate-100 transition">
                                <span>حاشیه</span>
                                <svg class="w-3.5 h-3.5 transition-transform" :class="styleSection.border ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="styleSection.border" class="p-3 space-y-2">
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="text-xs text-slate-400 mb-1 block">ضخامت (px)</label>
                                        <input type="number" min="0" max="20" :value="selectedBlock.style?.borderWidth ?? ''" @input="updateStyle('borderWidth', $event.target.value===''?'':Number($event.target.value))" placeholder="۰" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                    </div>
                                    <div>
                                        <label class="text-xs text-slate-400 mb-1 block">سبک</label>
                                        <select :value="selectedBlock.style?.borderStyle ?? 'solid'" @change="updateStyle('borderStyle', $event.target.value)" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                            <option value="solid">solid</option>
                                            <option value="dashed">dashed</option>
                                            <option value="dotted">dotted</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-xs text-slate-400 mb-1 block">رنگ حاشیه</label>
                                        <input type="color" :value="selectedBlock.style?.borderColor || '#e5e7eb'" @change="updateStyle('borderColor', $event.target.value)" class="w-full h-8 rounded border border-slate-200 cursor-pointer p-0.5">
                                    </div>
                                    <div>
                                        <label class="text-xs text-slate-400 mb-1 block">گوشه گرد (px)</label>
                                        <input type="number" min="0" max="100" :value="selectedBlock.style?.borderRadius ?? ''" @input="updateStyle('borderRadius', $event.target.value===''?'':Number($event.target.value))" placeholder="۰" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Shadow --}}
                        <div class="border border-slate-100 rounded-lg overflow-hidden">
                            <button @click="styleSection.shadow = !styleSection.shadow"
                                class="w-full flex items-center justify-between px-3 py-2 bg-slate-50 text-xs font-semibold text-slate-700 hover:bg-slate-100 transition">
                                <span>سایه</span>
                                <svg class="w-3.5 h-3.5 transition-transform" :class="styleSection.shadow ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="styleSection.shadow" class="p-3">
                                <div class="grid grid-cols-3 gap-1.5">
                                    <template x-for="s in ['none','sm','md','lg','xl']" :key="s">
                                        <button @click="updateStyle('shadow', s)"
                                            :class="(selectedBlock.style?.shadow ?? 'none') === s ? 'ring-2 ring-indigo-500 bg-indigo-50' : 'bg-white hover:bg-slate-50'"
                                            class="py-1.5 text-xs rounded border border-slate-200 transition font-medium" x-text="s"></button>
                                    </template>
                                </div>
                            </div>
                        </div>

                        {{-- Opacity --}}
                        <div class="border border-slate-100 rounded-lg overflow-hidden">
                            <button @click="styleSection.opacity = !styleSection.opacity"
                                class="w-full flex items-center justify-between px-3 py-2 bg-slate-50 text-xs font-semibold text-slate-700 hover:bg-slate-100 transition">
                                <span>شفافیت</span>
                                <svg class="w-3.5 h-3.5 transition-transform" :class="styleSection.opacity ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="styleSection.opacity" class="p-3">
                                <div class="flex items-center gap-2">
                                    <input type="range" min="0.1" max="1" step="0.05" :value="selectedBlock.style?.opacity ?? 1" @input="updateStyle('opacity', Number($event.target.value))" class="flex-1">
                                    <span class="text-xs text-slate-500 w-10 text-left" x-text="Math.round((selectedBlock.style?.opacity ?? 1) * 100) + '%'"></span>
                                </div>
                            </div>
                        </div>

                        <div class="pt-1">
                            <button @click="resetBlockStyle()" class="w-full text-xs py-1.5 rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-red-500 transition">
                                پاک کردن همه استایل‌ها
                            </button>
                        </div>
                    </div>

                    {{-- Advanced Tab --}}
                    <div x-show="selectedTab === 'advanced'" class="flex-1 overflow-y-auto px-4 py-4 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">کلاس CSS سفارشی</label>
                            <input type="text" :value="selectedBlock.style?.customClass ?? ''" @input="updateStyle('customClass', $event.target.value)"
                                placeholder="my-class another-class" class="w-full text-xs px-3 py-2 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-300 font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-2">نمایش در دستگاه‌ها</label>
                            <div class="space-y-2">
                                <label class="flex items-center gap-2 text-xs text-slate-600 cursor-pointer">
                                    <input type="checkbox" :checked="!!selectedBlock.style?.hideDesktop" @change="updateStyle('hideDesktop', $event.target.checked || '')" class="rounded">
                                    مخفی در دسکتاپ
                                </label>
                                <label class="flex items-center gap-2 text-xs text-slate-600 cursor-pointer">
                                    <input type="checkbox" :checked="!!selectedBlock.style?.hideTablet" @change="updateStyle('hideTablet', $event.target.checked || '')" class="rounded">
                                    مخفی در تبلت
                                </label>
                                <label class="flex items-center gap-2 text-xs text-slate-600 cursor-pointer">
                                    <input type="checkbox" :checked="!!selectedBlock.style?.hideMobile" @change="updateStyle('hideMobile', $event.target.checked || '')" class="rounded">
                                    مخفی در موبایل
                                </label>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">انیمیشن ورود</label>
                            <select :value="selectedBlock.style?.animation ?? ''" @change="updateStyle('animation', $event.target.value)" class="w-full text-xs px-3 py-2 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                                <option value="">بدون انیمیشن</option>
                                <option value="fade-in">Fade In</option>
                                <option value="slide-up">Slide Up</option>
                                <option value="slide-right">Slide Right</option>
                                <option value="zoom-in">Zoom In</option>
                                <option value="bounce-in">Bounce In</option>
                            </select>
                        </div>
                        <div x-show="selectedBlock.style?.animation && selectedBlock.style.animation !== ''">
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">مدت زمان (ثانیه)</label>
                            <input type="number" min="0.2" max="3" step="0.1" :value="selectedBlock.style?.animationDuration ?? 0.6" @input="updateStyle('animationDuration', Number($event.target.value))"
                                class="w-full text-xs px-3 py-2 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        </div>
                    </div>

                    {{-- Content (Props) Form --}}
                    <div x-show="selectedTab === 'content'" class="flex-1 overflow-y-auto px-4 py-4 space-y-4">
                        <template x-for="(schemaDef, propKey) in selectedSchema" :key="propKey">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5" x-text="schemaDef.label || propKey"></label>

                                <template x-if="schemaDef.type === 'string'">
                                    <input
                                        type="text"
                                        x-model="selectedBlock.props[propKey]"
                                        @input="schedulePropUpdate()"
                                        class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 transition"
                                        :placeholder="schemaDef.default ?? ''"
                                    >
                                </template>

                                <template x-if="schemaDef.type === 'image'">
                                    <div class="space-y-2">
                                        <template x-if="selectedBlock.props[propKey]">
                                            <img :src="selectedBlock.props[propKey]" class="w-full h-28 object-cover rounded-lg border border-slate-200">
                                        </template>
                                        <div class="flex gap-2">
                                            <input type="url" x-model="selectedBlock.props[propKey]" @input="schedulePropUpdate()" placeholder="آدرس تصویر" class="flex-1 text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                            <button @click="openMediaPicker(propKey)" class="px-2 py-1.5 rounded border border-indigo-300 text-indigo-600 hover:bg-indigo-50 text-xs transition">📁</button>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="schemaDef.type === 'number'">
                                    <input
                                        type="number"
                                        x-model.number="selectedBlock.props[propKey]"
                                        @input="schedulePropUpdate()"
                                        class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 transition"
                                        :min="0"
                                    >
                                </template>

                                <template x-if="schemaDef.type === 'boolean'">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <div class="relative">
                                            <input type="checkbox" x-model="selectedBlock.props[propKey]" @change="schedulePropUpdate()" class="sr-only peer">
                                            <div class="w-9 h-5 bg-slate-200 peer-checked:bg-indigo-500 rounded-full transition"></div>
                                            <div class="absolute top-0.5 right-0.5 w-4 h-4 bg-white rounded-full shadow peer-checked:translate-x-full rtl:peer-checked:-translate-x-full transition-transform"></div>
                                        </div>
                                        <span class="text-sm text-slate-700" x-text="selectedBlock.props[propKey] ? 'فعال' : 'غیرفعال'"></span>
                                    </label>
                                </template>

                                <template x-if="schemaDef.type === 'select'">
                                    <select
                                        x-model="selectedBlock.props[propKey]"
                                        @change="schedulePropUpdate()"
                                        class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 transition"
                                    >
                                        <template x-for="(label, val) in schemaDef.options" :key="val">
                                            <option :value="val" x-text="label"></option>
                                        </template>
                                    </select>
                                </template>

                                <template x-if="schemaDef.type === 'color'">
                                    <div class="flex items-center gap-2">
                                        <input type="color" x-model="selectedBlock.props[propKey]" @input="schedulePropUpdate()" class="w-10 h-10 rounded-lg border border-slate-200 cursor-pointer p-0.5">
                                        <input type="text"  x-model="selectedBlock.props[propKey]" @input="schedulePropUpdate()" class="flex-1 text-sm px-3 py-2 rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300 font-mono" placeholder="#000000">
                                    </div>
                                </template>

                                <template x-if="schemaDef.type === 'textarea'">
                                    <textarea
                                        x-model="selectedBlock.props[propKey]"
                                        @input="schedulePropUpdate()"
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

                        {{-- TB4: Container Children Panel --}}
                        <template x-if="isContainerType(selectedBlock?.type)">
                            <div class="mt-4 pt-4 border-t border-slate-200">
                                <div class="flex items-center justify-between px-1 mb-2">
                                    <span class="text-xs font-semibold text-slate-700 flex items-center gap-1.5">
                                        <svg class="w-3 h-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                                        فرزندان (<span x-text="selectedBlock?.children?.length ?? 0"></span>)
                                    </span>
                                    <button @click="showAddChildModal = true"
                                        class="text-xs px-2 py-1 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100 font-medium transition flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                        افزودن
                                    </button>
                                </div>
                                <div class="space-y-1 max-h-48 overflow-y-auto">
                                    <template x-for="(child, ci) in (selectedBlock?.children ?? [])" :key="child.id">
                                        <div class="flex items-center gap-1 px-2 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-xs group">
                                            <span :class="blockCategoryColor(child.type)" class="w-1.5 h-1.5 rounded-full shrink-0"></span>
                                            <span class="flex-1 truncate text-slate-700" x-text="child.props?.label ? (blockLabel(child.type) + ': ' + child.props.label) : blockLabel(child.type)"></span>
                                            <div class="flex items-center gap-0.5 opacity-0 group-hover:opacity-100 transition">
                                                <button @click="selectBlock([...selectedPath, ci])"
                                                    class="w-5 h-5 flex items-center justify-center rounded text-indigo-500 hover:bg-indigo-50" title="ویرایش">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                </button>
                                                <button @click="moveChildUp(ci)" :disabled="ci === 0"
                                                    class="w-5 h-5 flex items-center justify-center rounded text-slate-400 hover:bg-slate-100 disabled:opacity-30" title="بالاتر">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/></svg>
                                                </button>
                                                <button @click="moveChildDown(ci)" :disabled="ci >= (selectedBlock?.children?.length ?? 0) - 1"
                                                    class="w-5 h-5 flex items-center justify-center rounded text-slate-400 hover:bg-slate-100 disabled:opacity-30" title="پایین‌تر">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                                </button>
                                                <button @click="removeChild(ci)"
                                                    class="w-5 h-5 flex items-center justify-center rounded text-red-400 hover:bg-red-50" title="حذف">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                    <div x-show="!(selectedBlock?.children?.length)" class="text-center py-4 text-slate-400 text-xs">
                                        هیچ بلوک فرزندی وجود ندارد
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- TB4: Add Child Block Modal --}}
    <div x-show="showAddChildModal" x-transition.opacity class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div @click.outside="showAddChildModal = false" class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-5">
            <h3 class="text-sm font-bold text-slate-800 mb-3 flex items-center gap-2">
                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                افزودن بلوک فرزند به <span class="text-indigo-600" x-text="blockLabel(selectedBlock?.type)"></span>
            </h3>
            <div class="grid grid-cols-2 gap-2 max-h-64 overflow-y-auto">
                @foreach($orderedCats as $catKey)
                    @if(!isset($byCategory[$catKey])) @continue @endif
                    @foreach($byCategory[$catKey] as $blockType => $blockDef)
                        <button
                            @click="addChildBlock('{{ $blockType }}'); showAddChildModal = false"
                            class="flex items-center gap-2 px-2 py-2 rounded-lg border border-slate-200 hover:border-indigo-400 hover:bg-indigo-50 text-xs text-right transition">
                            <span class="text-base leading-none shrink-0">
                                @switch($blockType)
                                    @case('hero') 🌟 @break @case('section') 📦 @break
                                    @case('flex_container') 📐 @break @case('grid') 🔲 @break
                                    @case('columns') ⬜ @break @case('heading') 🔠 @break
                                    @case('text') 📄 @break @case('image') 🖼️ @break
                                    @case('button') 🔘 @break @case('cta') 📢 @break
                                    @default 🧩
                                @endswitch
                            </span>
                            <span class="font-medium text-slate-700 leading-tight">{{ $blockDef['name'] }}</span>
                        </button>
                    @endforeach
                @endforeach
            </div>
            <button @click="showAddChildModal = false" class="w-full mt-3 text-xs py-1.5 rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 transition">انصراف</button>
        </div>
    </div>

    {{-- ADD PAGE MODAL --}}
    <div x-show="showAddPage" x-transition.opacity class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div @click.outside="showAddPage = false" class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
            <h3 class="text-base font-bold text-slate-800 mb-4">افزودن صفحه جدید</h3>
            <div class="space-y-3">
                <div>
                    <label class="text-xs font-semibold text-slate-600 mb-1.5 block">عنوان صفحه *</label>
                    <input x-model="newPageTitle" type="text" class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-300" placeholder="مثال: درباره ما" @keydown.enter="confirmAddPage()">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 mb-1.5 block">اسلاگ (URL) <span class="text-slate-400 font-normal">اختیاری</span></label>
                    <input x-model="newPageSlug" type="text" class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-300" placeholder="about-us">
                </div>
            </div>
            <div class="flex gap-2 mt-5">
                <button @click="confirmAddPage()" :disabled="!newPageTitle" class="flex-1 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 disabled:opacity-50 transition">افزودن</button>
                <button @click="showAddPage = false; newPageTitle=''; newPageSlug=''" class="flex-1 py-2 rounded-lg border border-slate-200 text-slate-600 text-sm font-medium hover:bg-slate-50 transition">انصراف</button>
            </div>
        </div>
    </div>

    {{-- Media Modal --}}
    <div x-show="mediaModal.open" x-transition.opacity class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
        <div @click.outside="mediaModal.open = false" class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl flex flex-col" style="max-height:80vh;">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 shrink-0">
                <h3 class="font-bold text-slate-800 text-sm">مدیریت رسانه</h3>
                <button @click="mediaModal.open = false" class="text-slate-400 hover:text-slate-700 text-xl leading-none">×</button>
            </div>
            <div class="flex border-b border-slate-100 shrink-0">
                <button @click="mediaModal.tab = 'library'" :class="mediaModal.tab==='library'?'border-b-2 border-indigo-500 text-indigo-600 font-semibold':'text-slate-500'" class="flex-1 py-2.5 text-xs text-center transition">کتابخانه</button>
                <button @click="mediaModal.tab = 'upload'" :class="mediaModal.tab==='upload'?'border-b-2 border-indigo-500 text-indigo-600 font-semibold':'text-slate-500'" class="flex-1 py-2.5 text-xs text-center transition">آپلود</button>
            </div>
            {{-- Library --}}
            <div x-show="mediaModal.tab === 'library'" class="flex-1 overflow-y-auto p-4">
                <div x-show="mediaModal.loading" class="text-center py-10 text-slate-400 text-sm">در حال بارگذاری...</div>
                <div x-show="!mediaModal.loading && mediaModal.images.length === 0" class="text-center py-10 text-slate-400 text-sm">هنوز تصویری آپلود نشده</div>
                <div x-show="!mediaModal.loading" class="grid grid-cols-4 gap-3">
                    <template x-for="img in mediaModal.images" :key="img.url">
                        <div class="relative group rounded-lg overflow-hidden border-2 cursor-pointer transition"
                             :class="mediaModal.selected === img.url ? 'border-indigo-500' : 'border-transparent hover:border-indigo-300'"
                             @click="selectMedia(img.url)">
                            <img :src="img.url" class="w-full h-20 object-cover">
                            <button @click.stop="deleteMedia(img.url)" class="absolute top-1 left-1 w-6 h-6 bg-red-500 text-white rounded-full text-xs opacity-0 group-hover:opacity-100 transition flex items-center justify-center">×</button>
                        </div>
                    </template>
                </div>
            </div>
            {{-- Upload --}}
            <div x-show="mediaModal.tab === 'upload'" class="flex-1 p-4 flex flex-col items-center justify-center">
                <div class="w-full border-2 border-dashed border-slate-300 rounded-xl p-8 text-center cursor-pointer hover:border-indigo-400 hover:bg-indigo-50 transition"
                     @dragover.prevent @drop.prevent="handleMediaDrop($event)"
                     @click="$refs.mediaFileInput.click()">
                    <div class="text-4xl mb-3">📁</div>
                    <p class="text-sm text-slate-600 font-medium">کلیک کنید یا فایل را بکشید</p>
                    <p class="text-xs text-slate-400 mt-1">JPG, PNG, GIF, WebP, SVG — حداکثر ۵ مگابایت</p>
                    <input type="file" x-ref="mediaFileInput" accept="image/*" class="hidden" @change="uploadMediaFile($event.target.files[0])">
                </div>
                <div x-show="mediaModal.uploading" class="mt-4 text-sm text-indigo-600">در حال آپلود...</div>
            </div>
            <div class="flex gap-2 px-4 py-3 border-t border-slate-100 shrink-0">
                <button x-show="mediaModal.selected" @click="confirmMediaSelect()" class="flex-1 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">انتخاب تصویر</button>
                <button @click="mediaModal.open = false" class="flex-1 py-2 rounded-lg border border-slate-200 text-slate-600 text-sm font-medium hover:bg-slate-50 transition">بستن</button>
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
    saveBlocks:   '{{ route('admin.theme-design.canvas-save-blocks') }}',
    addPage:      '{{ route('admin.theme-design.canvas-add-page') }}',
    deletePage:   '{{ url('admin/theme-design/canvas/pages') }}',
    globalStyles: '{{ route('admin.theme-design.canvas-global-styles') }}',
    publish:      '{{ route('admin.theme-design.canvas-publish') }}',
    livePreview:  '{{ route('admin.theme-design.canvas-live-preview') }}',
    renderBlock:  '{{ route('admin.theme-design.canvas-render-block') }}',
    mediaIndex:   '{{ route('admin.theme-design.media-index') }}',
    mediaUpload:  '{{ route('admin.theme-design.media-upload') }}',
    mediaDestroy: '{{ route('admin.theme-design.media-destroy') }}',
};

function canvasEditor() {
    return {
        // ── State ──────────────────────────────────────────────
        pages:         JSON.parse(JSON.stringify(INITIAL_PAGES)),
        currentPage:   Object.keys(INITIAL_PAGES)[0] ?? '__header__',
        selectedPath:  null,          // null | [0] | [0, 1] | ...
        leftPanelTab:  'blocks',      // 'blocks' | 'tree'
        treeExpanded:  {},
        showAddChildModal: false,
        selectedTab:   'content',
        isDirty:       false,
        saving:        false,
        quickSaving:   false,
        publishing:    false,
        showAddPage:   false,
        newPageTitle:  '',
        newPageSlug:   '',
        librarySearch: '',
        expandedCats:  { layout:true, content:true, module:true, dynamic:false, ecommerce:false, marketing:false, utility:false },
        showGlobalStyles: false,
        globalStyles:  JSON.parse(JSON.stringify(GLOBAL_STYLES)),
        gsIsDirty:     false,
        gsSaving:      false,
        toast:         { show: false, msg: '', type: 'success', _timer: null },
        // TB3-02: iframe
        device:        'desktop',
        iframeTimestamp: Date.now(),
        iframeLoading:  true,
        iframeReady:    false,
        propUpdateTimer: null,
        // TB3-03: drag
        isDraggingBlock:      false,
        draggedBlockType:     null,
        draggedBlockLabel:    null,
        dropTargetIndex:      -1,
        dropTargetBeforeId:   null,
        dragTargetContainerId: null,
        // TB3-S2: undo/redo history
        history:       [],
        historyIndex:  -1,
        maxHistory:    25,
        // TB3-05: style sections
        styleSection: { typography: true, colors: true, spacing: false, border: false, shadow: false, opacity: false },
        inlineAutoSaveTimer: null,
        // TB3-07: media modal
        mediaModal: { open: false, tab: 'library', images: [], loading: false, uploading: false, selected: null, propKey: null },

        // ── Computed ──────────────────────────────────────────
        get blocks() { return this.pages[this.currentPage]?.blocks ?? []; },
        set blocks(val) { if (this.pages[this.currentPage]) this.pages[this.currentPage].blocks = val; },
        get selectedBlock() {
            if (!this.selectedPath || this.selectedPath.length === 0) return null;
            return this.getBlockByPath(this.selectedPath);
        },
        get selectedIndex() {
            return this.selectedPath?.length > 0 ? (this.selectedPath[0] ?? -1) : -1;
        },
        get selectedSchema() {
            if (!this.selectedBlock) return {};
            return BLOCK_SCHEMAS[this.selectedBlock.type]?.schema ?? {};
        },

        // ── Library ──────────────────────────────────────────
        toggleCategory(key)    { this.expandedCats[key] = !this.expandedCats[key]; },
        isCategoryVisible(key) {
            if (!this.librarySearch) return true;
            const s = this.librarySearch.toLowerCase();
            return Object.keys(BLOCK_LABELS).some(t => t.includes(s) || (BLOCK_LABELS[t]??'').toLowerCase().includes(s));
        },
        isBlockVisible(type, name) {
            if (!this.librarySearch) return true;
            const s = this.librarySearch.toLowerCase();
            return type.toLowerCase().includes(s) || name.toLowerCase().includes(s);
        },

        // ── TB4: Block Tree Helpers ───────────────────────────
        makeBlock(type) {
            return {
                id: 'blk_' + Date.now() + '_' + Math.random().toString(36).slice(2, 6),
                type,
                props: JSON.parse(JSON.stringify(BLOCK_SCHEMAS[type]?.defaultProps ?? {})),
                ...(this.isContainerType(type) ? { children: [] } : {})
            };
        },
        isContainerType(type) {
            return ['section', 'container', 'grid', 'columns', 'row', 'column', 'flex_container'].includes(type);
        },
        getBlockByPath(path, arr) {
            arr = arr ?? this.blocks;
            if (!path || path.length === 0) return null;
            let block = arr[path[0]];
            for (let i = 1; i < path.length; i++) {
                if (!block) return null;
                block = (block.children ?? [])[path[i]];
            }
            return block ?? null;
        },
        getSiblingArray(path) {
            if (!path || path.length === 0) return null;
            if (path.length === 1) return this.blocks;
            const parent = this.getBlockByPath(path.slice(0, -1));
            if (!parent) return null;
            if (!parent.children) parent.children = [];
            return parent.children;
        },
        findBlockPath(blockId, arr, prefix) {
            arr = arr ?? this.blocks;
            prefix = prefix ?? [];
            for (let i = 0; i < arr.length; i++) {
                if (arr[i].id === blockId) return [...prefix, i];
                if (arr[i].children?.length) {
                    const found = this.findBlockPath(blockId, arr[i].children, [...prefix, i]);
                    if (found) return found;
                }
            }
            return null;
        },
        flattenTree(arr, depth, prefix) {
            arr    = arr    ?? this.blocks;
            depth  = depth  ?? 0;
            prefix = prefix ?? [];
            const result = [];
            for (let i = 0; i < arr.length; i++) {
                const block = arr[i];
                const path  = [...prefix, i];
                result.push({ block, depth, path });
                if (this.isContainerType(block.type) && block.children?.length && this.treeExpanded[block.id] !== false) {
                    result.push(...this.flattenTree(block.children, depth + 1, path));
                }
            }
            return result;
        },
        isSelectedPath(path) {
            if (!this.selectedPath || !path) return false;
            if (this.selectedPath.length !== path.length) return false;
            return this.selectedPath.every((v, i) => v === path[i]);
        },
        toggleTreeNode(blockId) {
            this.treeExpanded[blockId] = this.treeExpanded[blockId] !== false ? false : true;
        },

        // ── Canvas Actions ────────────────────────────────────
        addBlock(type, atIndex = -1, parentPath = null) {
            this.pushHistory();
            const block = this.makeBlock(type);
            if (parentPath !== null) {
                const container = this.getBlockByPath(parentPath);
                if (!container) return;
                if (!container.children) container.children = [];
                container.children.push(block);
                this.selectedPath = [...parentPath, container.children.length - 1];
            } else {
                const arr = [...this.blocks];
                const idx = (atIndex >= 0 && atIndex <= arr.length) ? atIndex : arr.length;
                arr.splice(idx, 0, block);
                this.pages[this.currentPage].blocks = arr;
                this.selectedPath = [idx];
            }
            this.isDirty = true;
            this.autoSaveAndReload();
        },

        addChildBlock(type) {
            if (!this.selectedPath || !this.isContainerType(this.selectedBlock?.type)) return;
            this.addBlock(type, -1, this.selectedPath);
        },

        removeSelected() {
            if (!this.selectedPath) return;
            this.pushHistory();
            const siblings = this.getSiblingArray(this.selectedPath);
            if (!siblings) return;
            const idx = this.selectedPath[this.selectedPath.length - 1];
            siblings.splice(idx, 1);
            this.selectedPath = null;
            this.isDirty = true;
            this.autoSaveAndReload();
        },

        duplicateSelected() {
            if (!this.selectedPath) return;
            this.pushHistory();
            const siblings = this.getSiblingArray(this.selectedPath);
            if (!siblings) return;
            const idx = this.selectedPath[this.selectedPath.length - 1];
            const clone = JSON.parse(JSON.stringify(siblings[idx]));
            this._regenIds(clone);
            siblings.splice(idx + 1, 0, clone);
            this.selectedPath = [...this.selectedPath.slice(0, -1), idx + 1];
            this.isDirty = true;
            this.autoSaveAndReload();
        },

        _regenIds(block) {
            block.id = 'blk_' + Date.now() + '_' + Math.random().toString(36).slice(2, 6);
            (block.children ?? []).forEach(c => this._regenIds(c));
        },

        selectBlock(pathOrIndex) {
            const path = Array.isArray(pathOrIndex) ? pathOrIndex : (pathOrIndex >= 0 ? [pathOrIndex] : null);
            this.selectedPath = path;
            this.selectedTab = 'content';
            if (this.selectedBlock) this.sendToIframe({ action: 'select', blockId: this.selectedBlock.id });
        },

        moveSelectedUp() {
            if (!this.selectedPath) return;
            const siblings = this.getSiblingArray(this.selectedPath);
            const idx = this.selectedPath[this.selectedPath.length - 1];
            if (!siblings || idx <= 0) return;
            this.pushHistory();
            [siblings[idx - 1], siblings[idx]] = [siblings[idx], siblings[idx - 1]];
            this.selectedPath = [...this.selectedPath.slice(0, -1), idx - 1];
            this.isDirty = true;
            this.autoSaveAndReload();
        },

        moveSelectedDown() {
            if (!this.selectedPath) return;
            const siblings = this.getSiblingArray(this.selectedPath);
            const idx = this.selectedPath[this.selectedPath.length - 1];
            if (!siblings || idx >= siblings.length - 1) return;
            this.pushHistory();
            [siblings[idx], siblings[idx + 1]] = [siblings[idx + 1], siblings[idx]];
            this.selectedPath = [...this.selectedPath.slice(0, -1), idx + 1];
            this.isDirty = true;
            this.autoSaveAndReload();
        },

        canMoveUp()   { if (!this.selectedPath) return false; return (this.selectedPath[this.selectedPath.length - 1] ?? 0) > 0; },
        canMoveDown() {
            if (!this.selectedPath) return false;
            const siblings = this.getSiblingArray(this.selectedPath);
            const idx = this.selectedPath[this.selectedPath.length - 1];
            return siblings && idx < siblings.length - 1;
        },

        moveChildUp(ci) {
            if (!this.selectedBlock?.children || ci <= 0) return;
            this.pushHistory();
            const ch = this.selectedBlock.children;
            [ch[ci - 1], ch[ci]] = [ch[ci], ch[ci - 1]];
            this.isDirty = true;
            this.autoSaveAndReload();
        },
        moveChildDown(ci) {
            const ch = this.selectedBlock?.children ?? [];
            if (ci >= ch.length - 1) return;
            this.pushHistory();
            [ch[ci], ch[ci + 1]] = [ch[ci + 1], ch[ci]];
            this.isDirty = true;
            this.autoSaveAndReload();
        },
        removeChild(ci) {
            if (!this.selectedBlock?.children) return;
            this.pushHistory();
            this.selectedBlock.children.splice(ci, 1);
            this.isDirty = true;
            this.autoSaveAndReload();
        },

        moveBlock(from, to) {
            if (to < 0 || to >= this.blocks.length) return;
            this.pushHistory();
            const arr = [...this.blocks];
            const [item] = arr.splice(from, 1);
            arr.splice(to, 0, item);
            this.pages[this.currentPage].blocks = arr;
            this.selectedPath = [to];
            this.isDirty = true;
            this.autoSaveAndReload();
        },

        // ── TB3-S2: Undo / Redo ──────────────────────────────
        pushHistory() {
            if (this.historyIndex < this.history.length - 1) {
                this.history = this.history.slice(0, this.historyIndex + 1);
            }
            this.history.push(JSON.parse(JSON.stringify(this.blocks)));
            if (this.history.length > this.maxHistory) {
                this.history.shift();
            } else {
                this.historyIndex++;
            }
        },

        undo() {
            if (this.historyIndex <= 0) return;
            this.historyIndex--;
            this.pages[this.currentPage].blocks = JSON.parse(JSON.stringify(this.history[this.historyIndex]));
            this.selectedPath = null;
            this.isDirty = true;
            this.autoSaveAndReload();
        },

        redo() {
            if (this.historyIndex >= this.history.length - 1) return;
            this.historyIndex++;
            this.pages[this.currentPage].blocks = JSON.parse(JSON.stringify(this.history[this.historyIndex]));
            this.selectedPath = null;
            this.isDirty = true;
            this.autoSaveAndReload();
        },

        // ── TB3-S2: Per-block style overrides ────────────────
        updateStyle(styleKey, value) {
            if (!this.selectedBlock) return;
            if (!this.selectedBlock.style) this.selectedBlock.style = {};
            if (value === '' || value === null) {
                delete this.selectedBlock.style[styleKey];
                if (Object.keys(this.selectedBlock.style).length === 0) {
                    delete this.selectedBlock.style;
                }
            } else {
                this.selectedBlock.style[styleKey] = value;
            }
            this.isDirty = true;
            this.schedulePropUpdate();
        },

        resetBlockStyle() {
            if (!this.selectedBlock) return;
            delete this.selectedBlock.style;
            this.isDirty = true;
            this.autoSaveAndReload();
        },

        schedulePropUpdate() {
            this.isDirty = true;
            clearTimeout(this.propUpdateTimer);
            this.propUpdateTimer = setTimeout(() => this.pushPropUpdate(), 350);
        },

        async pushPropUpdate() {
            if (!this.selectedBlock || !this.iframeReady) return;
            try {
                const blockPayload = JSON.parse(JSON.stringify(this.selectedBlock)); // includes .style if set
                const r = await fetch(ROUTES.renderBlock, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                    body: JSON.stringify({ block: blockPayload })
                });
                const d = await r.json();
                if (d.html) this.sendToIframe({ action: 'updateBlock', blockId: this.selectedBlock.id, html: d.html });
            } catch (_) {}
        },

        // ── Page Management ────────────────────────────────────
        switchPage(key) {
            if (this.isDirty) this.saveCurrentPage();
            this.currentPage  = key;
            this.selectedPath = null;
            this.selectedTab  = 'content';
            // Reset history for new page
            this.history      = [JSON.parse(JSON.stringify(this.pages[key]?.blocks ?? []))];
            this.historyIndex = 0;
            this.reloadIframe();
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
                    if (this.currentPage === key) { this.currentPage = Object.keys(this.pages)[0]; this.reloadIframe(); }
                    this.showToast('صفحه حذف شد.', 'success');
                } else this.showToast(d.error ?? 'خطا', 'error');
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
                    this.newPageTitle = ''; this.newPageSlug = '';
                    this.switchPage(d.key);
                    this.showToast('صفحه افزوده شد.', 'success');
                } else this.showToast(d.error ?? 'خطا', 'error');
            }).catch(() => this.showToast('خطای شبکه', 'error'));
        },

        // ── Save / Publish ────────────────────────────────────
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
                else this.showToast(d.error ?? 'خطا', 'error');
            } catch { this.showToast('خطای شبکه', 'error'); }
            finally { this.saving = false; }
        },

        async autoSave() {
            if (this.quickSaving) return;
            this.quickSaving = true;
            try {
                await fetch(ROUTES.saveBlocks, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                    body: JSON.stringify({ page_key: this.currentPage, blocks: this.blocks })
                });
                this.isDirty = false;
            } catch (_) {}
            finally { this.quickSaving = false; }
        },

        async autoSaveAndReload() { await this.autoSave(); this.reloadIframe(); },

        async publishDraft() {
            if (this.publishing) return;
            if (this.isDirty) await this.saveCurrentPage();
            this.publishing = true;
            try {
                const r = await fetch(ROUTES.publish, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                });
                const d = await r.json();
                if (d.success) { this.showToast('قالب منتشر شد ✓', 'success'); setTimeout(() => window.location.reload(), 800); }
                else this.showToast(d.error ?? 'خطا', 'error');
            } catch { this.showToast('خطای شبکه', 'error'); }
            finally { this.publishing = false; }
        },

        async saveGlobalStylesAndReload() {
            if (this.gsSaving) return;
            this.gsSaving = true;
            try {
                const r = await fetch(ROUTES.globalStyles, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                    body: JSON.stringify(this.globalStyles)
                });
                const d = await r.json();
                if (d.success) { this.gsIsDirty = false; this.showToast('رنگ‌ها ذخیره شد ✓', 'success'); this.reloadIframe(); }
                else this.showToast(d.error ?? 'خطا', 'error');
            } catch { this.showToast('خطای شبکه', 'error'); }
            finally { this.gsSaving = false; }
        },

        // ── TB3-02: Live Iframe ───────────────────────────────
        livePreviewUrl() {
            return ROUTES.livePreview + '?page=' + encodeURIComponent(this.currentPage) + '&t=' + this.iframeTimestamp;
        },

        reloadIframe() {
            this.iframeLoading  = true;
            this.iframeReady    = false;
            this.iframeTimestamp = Date.now();
        },

        getIframe() { return document.getElementById('tb-live-iframe'); },

        sendToIframe(msg) {
            const iframe = this.getIframe();
            if (iframe && iframe.contentWindow) {
                try { iframe.contentWindow.postMessage(msg, '*'); } catch (_) {}
            }
        },

        onIframeLoad() { setTimeout(() => { this.iframeLoading = false; }, 150); },

        handleIframeMessage(e) {
            const d = e.data;
            if (!d || !d.action) return;
            switch (d.action) {
                case 'ready':
                    this.iframeReady   = true;
                    this.iframeLoading = false;
                    if (this.selectedBlock) this.sendToIframe({ action: 'select', blockId: this.selectedBlock.id });
                    break;
                case 'iframeHeight':
                    const iframe = this.getIframe();
                    if (iframe) iframe.style.height = Math.max(400, d.height || 400) + 'px';
                    break;
                case 'blockSelected': {
                    const path = this.findBlockPath(d.blockId);
                    if (path) { this.selectedPath = path; this.selectedTab = 'content'; }
                    break;
                }
                case 'dropIndexResult':
                    this.dropTargetIndex       = d.index ?? this.blocks.length;
                    this.dropTargetBeforeId    = d.beforeId ?? '__end__';
                    this.dragTargetContainerId = d.containerId ?? null;
                    this.sendToIframe({ action: 'setDropTarget', beforeId: this.dropTargetBeforeId });
                    if (this.dragTargetContainerId) {
                        this.sendToIframe({ action: 'setContainerTarget', containerId: this.dragTargetContainerId });
                    }
                    break;
                case 'inlinePropChange':
                    const ib = this.blocks.find(b => b.id === d.blockId);
                    if (ib && d.propKey) {
                        if (!ib.props) ib.props = {};
                        ib.props[d.propKey] = d.value;
                        this.isDirty = true;
                        if (d.final) {
                            clearTimeout(this.inlineAutoSaveTimer);
                            this.inlineAutoSaveTimer = setTimeout(() => this.autoSave(), 800);
                        }
                    }
                    break;
            }
        },

        setDevice(d) { this.device = d; },

        // ── TB3-03: Drag from Library ─────────────────────────
        startDragBlock(event, blockType) {
            this.draggedBlockType  = blockType;
            this.draggedBlockLabel = BLOCK_LABELS[blockType] ?? blockType;
            this.isDraggingBlock   = true;
            event.dataTransfer.effectAllowed = 'copy';
            event.dataTransfer.setData('text/plain', blockType);
            this.sendToIframe({ action: 'showDropZones', visible: true });
        },

        endDragBlock() {
            this.isDraggingBlock       = false;
            this.draggedBlockType      = null;
            this.draggedBlockLabel     = null;
            this.dropTargetIndex       = -1;
            this.dropTargetBeforeId    = null;
            this.dragTargetContainerId = null;
            this.sendToIframe({ action: 'showDropZones', visible: false });
            this.sendToIframe({ action: 'setContainerTarget', containerId: null });
        },

        onDragOverCanvas(event) {
            if (!this.isDraggingBlock) return;
            const iframe = this.getIframe();
            if (iframe) {
                const rect = iframe.getBoundingClientRect();
                const scrollY = iframe.contentWindow?.scrollY ?? 0;
                const relY = event.clientY - rect.top + scrollY;
                const relX = event.clientX - rect.left;
                this.sendToIframe({ action: 'getDropIndex', clientY: relY, clientX: relX });
            }
        },

        onDragLeaveCanvas() { this.sendToIframe({ action: 'setDropTarget', beforeId: null }); },

        onDropToCanvas(event) {
            event.preventDefault();
            const type = this.draggedBlockType || event.dataTransfer.getData('text/plain');
            if (!type) { this.endDragBlock(); return; }
            if (this.dragTargetContainerId) {
                const containerPath = this.findBlockPath(this.dragTargetContainerId);
                if (containerPath) {
                    this.addBlock(type, -1, containerPath);
                    this.endDragBlock();
                    return;
                }
            }
            const idx = this.dropTargetIndex >= 0 ? this.dropTargetIndex : this.blocks.length;
            this.addBlock(type, idx);
            this.endDragBlock();
        },

        // ── Helpers ──────────────────────────────────────────
        blockLabel(type)    { return BLOCK_LABELS[type] ?? type; },
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
            if (type.startsWith('module:pricing') || type.startsWith('module:plan')) return 'bg-pink-500';
            if (type.startsWith('module:'))       return 'bg-indigo-500';
            if (type === 'hero' || type === 'cta') return 'bg-purple-500';
            if (['section','grid','columns','slider','accordion'].includes(type)) return 'bg-blue-400';
            if (['heading','text','image','gallery','video','button','divider','counter'].includes(type)) return 'bg-green-500';
            if (['countdown','promo_banner'].includes(type)) return 'bg-orange-400';
            return 'bg-slate-400';
        },

        toggleGradient(enable) {
            if (enable) {
                this.updateStyle('bgGradientFrom', '#4f46e5');
                this.updateStyle('bgGradientTo', '#7c3aed');
                this.updateStyle('bgGradientDir', '135deg');
            } else {
                this.updateStyle('bgGradientFrom', '');
                this.updateStyle('bgGradientTo', '');
                this.updateStyle('bgGradientDir', '');
            }
        },

        openMediaPicker(propKey) {
            this.mediaModal.propKey  = propKey;
            this.mediaModal.selected = this.selectedBlock?.props?.[propKey] || null;
            this.mediaModal.tab      = 'library';
            this.mediaModal.open     = true;
            this.fetchMedia();
        },

        async fetchMedia() {
            this.mediaModal.loading = true;
            try {
                const r = await fetch(ROUTES.mediaIndex, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN } });
                const d = await r.json();
                this.mediaModal.images = d.images ?? [];
            } catch (_) { this.mediaModal.images = []; }
            finally { this.mediaModal.loading = false; }
        },

        selectMedia(url) {
            this.mediaModal.selected = (this.mediaModal.selected === url) ? null : url;
        },

        confirmMediaSelect() {
            if (!this.mediaModal.selected || !this.mediaModal.propKey) return;
            if (this.selectedBlock) {
                this.selectedBlock.props[this.mediaModal.propKey] = this.mediaModal.selected;
                this.schedulePropUpdate();
            }
            this.mediaModal.open = false;
        },

        async deleteMedia(url) {
            if (!confirm('این تصویر حذف شود؟')) return;
            try {
                await fetch(ROUTES.mediaDestroy, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                    body: JSON.stringify({ url })
                });
                this.mediaModal.images = this.mediaModal.images.filter(i => i.url !== url);
                if (this.mediaModal.selected === url) this.mediaModal.selected = null;
            } catch (_) { this.showToast('خطا در حذف', 'error'); }
        },

        handleMediaDrop(e) {
            const file = e.dataTransfer?.files?.[0];
            if (file) this.uploadMediaFile(file);
        },

        async uploadMediaFile(file) {
            if (!file) return;
            this.mediaModal.uploading = true;
            const fd = new FormData();
            fd.append('file', file);
            try {
                const r = await fetch(ROUTES.mediaUpload, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' }, body: fd });
                const d = await r.json();
                if (d.url) {
                    this.mediaModal.images.unshift({ url: d.url, name: d.name, size: 0 });
                    this.mediaModal.selected = d.url;
                    this.mediaModal.tab = 'library';
                    this.showToast('تصویر آپلود شد ✓', 'success');
                } else this.showToast(d.error ?? 'خطا در آپلود', 'error');
            } catch (_) { this.showToast('خطای شبکه', 'error'); }
            finally { this.mediaModal.uploading = false; }
        },

        showToast(msg, type = 'success') {
            clearTimeout(this.toast._timer);
            this.toast = { show: true, msg, type, _timer: setTimeout(() => this.toast.show = false, 3200) };
        },

        // TB3-01 + TB4: Normalize blocks recursively
        normalizeBlock(block) {
            if (block.data !== undefined && block.props === undefined) { block.props = block.data; delete block.data; }
            if (!block.props) block.props = {};
            if (!block.children) {
                if (Array.isArray(block.blocks)) { block.children = block.blocks; delete block.blocks; }
                else if (Array.isArray(block.props?.blocks)) { block.children = block.props.blocks; delete block.props.blocks; }
            }
            if (Array.isArray(block.children)) block.children = block.children.map(c => this.normalizeBlock(c));
            return block;
        },

        init() {
            // TB3-01 + TB4: Normalize block.data → block.props, migrate legacy children locations
            Object.values(this.pages).forEach(page => {
                if (Array.isArray(page.blocks)) page.blocks = page.blocks.map(b => this.normalizeBlock(b));
            });

            // TB3-S2: Initialize undo history with current state
            this.history = [JSON.parse(JSON.stringify(this.blocks))];
            this.historyIndex = 0;

            // TB3-02: postMessage bridge
            window.addEventListener('message', (e) => this.handleIframeMessage(e));

            // TB3-S2: Keyboard shortcuts
            window.addEventListener('keydown', (e) => {
                const tag = document.activeElement?.tagName;
                if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return;
                if ((e.ctrlKey || e.metaKey) && e.key === 'z' && !e.shiftKey) {
                    e.preventDefault();
                    this.undo();
                } else if ((e.ctrlKey || e.metaKey) && (e.key === 'y' || (e.key === 'z' && e.shiftKey))) {
                    e.preventDefault();
                    this.redo();
                }
            });

            window.addEventListener('beforeunload', (e) => {
                if (this.isDirty) { e.preventDefault(); e.returnValue = ''; }
            });

            this.iframeLoading = true;
        }
    };
}
</script>
@endpush
