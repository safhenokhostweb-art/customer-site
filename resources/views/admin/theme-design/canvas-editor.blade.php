@extends('layouts.canvas-editor')

@section('title', 'ویرایشگر قالب Canvas')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
@endpush

@section('content')
@php
$blockLabels  = collect($registry)->mapWithKeys(fn($d,$t)=>[$t=>$d['name']]);
// Phase 3F-B: is_container is declarative capability metadata from the
// canonical/shared authority (\App\Services\ThemeCanvas\BlockRegistry::
// isContainerType()), not a client-side hardcoded array — see that
// method's docblock for the layered shared+host design.
$blockSchemas = collect($registry)->mapWithKeys(fn($d,$t)=>[$t=>['schema'=>$d['schema']??[],'defaultProps'=>$d['defaultProps']??[],'is_container'=>\App\Services\ThemeCanvas\BlockRegistry::isContainerType($t)]]);
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
    @keydown.escape.window="if (mediaModal.open) closeMediaPicker()"
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
                    :data-testid="'tb-canvas-page-tab-' + key"
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
                data-testid="tb-add-page-open"
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

            {{-- TB6: Template Library --}}
            <button @click="openTemplateLibrary()"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-purple-200 bg-purple-50 text-purple-700 hover:bg-purple-100 text-xs font-medium transition"
                title="کتابخانه قالب‌ها">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm0 8a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zm12 0a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>
                قالب‌ها
            </button>
            <button x-show="selectedBlock" @click="showSaveAsTemplate = true"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100 text-xs font-medium transition"
                title="ذخیره به‌عنوان قالب">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                ذخیره قالب
            </button>

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
                data-testid="tb-canvas-save-button"
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
                data-testid="tb-canvas-publish-button"
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
                <button @click="leftPanelTab='tree'" data-testid="tb-canvas-tree-tab"
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
                            :data-testid="'tb-tree-node-' + node.block.id"
                            :data-block-type="node.block.type"
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
                            {{-- TB5: hide-on-device badges --}}
                            <span x-show="node.block.style?.hideDesktop" class="text-[9px] bg-slate-200 text-slate-500 px-1 rounded shrink-0">D</span>
                            <span x-show="node.block.style?.hideTablet"  class="text-[9px] bg-slate-200 text-slate-500 px-1 rounded shrink-0">T</span>
                            <span x-show="node.block.style?.hideMobile"  class="text-[9px] bg-slate-200 text-slate-500 px-1 rounded shrink-0">M</span>
                            {{-- TB5: responsive override dot --}}
                            <span x-show="node.block.responsive && Object.keys(node.block.responsive).length > 0"
                                class="w-1.5 h-1.5 rounded-full bg-amber-400 shrink-0"></span>
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
                            data-testid="tb-palette-category-{{ $catKey }}"
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
                                        data-testid="tb-palette-add-{{ $blockType }}"
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
                    <div x-show="selectedTab === 'style'" class="flex-1 overflow-y-auto">

                        {{-- TB5: Breakpoint Toggle Bar --}}
                        <div class="flex items-center gap-0.5 bg-slate-50 border-b border-slate-100 p-2 sticky top-0 z-10">
                            <span class="text-[10px] text-slate-400 ml-1">ریسپانسیو:</span>
                            <button @click="activeBreakpoint='desktop'"
                                :class="activeBreakpoint==='desktop' ? 'bg-indigo-600 text-white' : 'bg-white text-slate-500 hover:bg-slate-100 border border-slate-200'"
                                class="flex items-center gap-1 px-2 py-1 rounded-md text-[10px] font-medium transition">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                D
                            </button>
                            <button @click="activeBreakpoint='tablet'"
                                :class="activeBreakpoint==='tablet' ? 'bg-indigo-600 text-white' : 'bg-white text-slate-500 hover:bg-slate-100 border border-slate-200'"
                                class="relative flex items-center gap-1 px-2 py-1 rounded-md text-[10px] font-medium transition">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                T
                                <span x-show="selectedBlock?.responsive?.tablet && Object.keys(selectedBlock.responsive.tablet).length > 0"
                                    class="absolute -top-1 -right-1 w-2 h-2 bg-amber-400 rounded-full"></span>
                            </button>
                            <button @click="activeBreakpoint='mobile'"
                                :class="activeBreakpoint==='mobile' ? 'bg-indigo-600 text-white' : 'bg-white text-slate-500 hover:bg-slate-100 border border-slate-200'"
                                class="relative flex items-center gap-1 px-2 py-1 rounded-md text-[10px] font-medium transition">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V8a2 2 0 00-2-2H8a2 2 0 00-2 2v13a2 2 0 002 2z"/></svg>
                                M
                                <span x-show="selectedBlock?.responsive?.mobile && Object.keys(selectedBlock.responsive.mobile).length > 0"
                                    class="absolute -top-1 -right-1 w-2 h-2 bg-amber-400 rounded-full"></span>
                            </button>
                            <span x-show="activeBreakpoint !== 'desktop'" class="mr-auto text-[9px] text-amber-600 font-medium">
                                override روی <span x-text="activeBreakpoint === 'tablet' ? 'تبلت' : 'موبایل'"></span>
                            </span>
                            <button x-show="activeBreakpoint !== 'desktop'"
                                @click="resetBlockStyle()"
                                class="mr-1 text-[10px] text-red-500 hover:text-red-700 font-medium underline">
                                پاک کردن
                            </button>
                        </div>

                        <div class="px-3 py-3 space-y-2">

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
                                        <label class="text-xs text-slate-500 mb-1 block flex items-center gap-1">اندازه فونت (px)<span x-show="hasResponsiveOverride('fontSize', activeBreakpoint) && activeBreakpoint !== 'desktop'" class="w-1.5 h-1.5 bg-amber-400 rounded-full inline-block"></span></label>
                                        <input type="number" min="8" max="120" :value="getCascadedValue('fontSize', '')" @input="updateStyle('fontSize', $event.target.value===''?'':Number($event.target.value))" placeholder="پیش‌فرض" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                    </div>
                                    <div>
                                        <label class="text-xs text-slate-500 mb-1 block flex items-center gap-1">وزن فونت<span x-show="hasResponsiveOverride('fontWeight', activeBreakpoint) && activeBreakpoint !== 'desktop'" class="w-1.5 h-1.5 bg-amber-400 rounded-full inline-block"></span></label>
                                        <select :value="getCascadedValue('fontWeight', '')" @change="updateStyle('fontWeight', $event.target.value)" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300">
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
                                        <select :value="getCascadedValue('textAlign', '')" @change="updateStyle('textAlign', $event.target.value)" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                            <option value="">پیش‌فرض</option>
                                            <option value="right">راست</option>
                                            <option value="center">وسط</option>
                                            <option value="left">چپ</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-xs text-slate-500 mb-1 block">فاصله خطوط</label>
                                        <input type="number" min="1" max="4" step="0.1" :value="getCascadedValue('lineHeight', '')" @input="updateStyle('lineHeight', $event.target.value===''?'':Number($event.target.value))" placeholder="۱.۵" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300">
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
                                    <label class="text-xs text-slate-500 mb-1 block flex items-center gap-1">رنگ پس‌زمینه<span x-show="hasResponsiveOverride('bgColor', activeBreakpoint) && activeBreakpoint !== 'desktop'" class="w-1.5 h-1.5 bg-amber-400 rounded-full inline-block"></span></label>
                                    <div class="flex items-center gap-2">
                                        <input type="color" :value="getCascadedValue('bgColor', '#ffffff')" @change="updateStyle('bgColor', $event.target.value)" class="w-8 h-8 rounded border border-slate-200 cursor-pointer p-0.5">
                                        <input type="text" :value="getCascadedValue('bgColor', '')" @input="updateStyle('bgColor', $event.target.value)" placeholder="بدون رنگ" class="flex-1 text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                        <button x-show="getCascadedValue('bgColor')" @click="updateStyle('bgColor', '')" class="text-slate-400 hover:text-red-500 text-sm">×</button>
                                    </div>
                                </div>
                                <div>
                                    <label class="text-xs text-slate-500 mb-1 block flex items-center gap-1">رنگ متن<span x-show="hasResponsiveOverride('textColor', activeBreakpoint) && activeBreakpoint !== 'desktop'" class="w-1.5 h-1.5 bg-amber-400 rounded-full inline-block"></span></label>
                                    <div class="flex items-center gap-2">
                                        <input type="color" :value="getCascadedValue('textColor', '#000000')" @change="updateStyle('textColor', $event.target.value)" class="w-8 h-8 rounded border border-slate-200 cursor-pointer p-0.5">
                                        <input type="text" :value="getCascadedValue('textColor', '')" @input="updateStyle('textColor', $event.target.value)" placeholder="بدون رنگ" class="flex-1 text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                        <button x-show="getCascadedValue('textColor')" @click="updateStyle('textColor', '')" class="text-slate-400 hover:text-red-500 text-sm">×</button>
                                    </div>
                                </div>
                                <div x-show="activeBreakpoint === 'desktop'">
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
                                        <input type="number" min="0" max="200" :value="getCascadedValue('paddingTop', '')" @input="updateStyle('paddingTop', $event.target.value===''?'':Number($event.target.value))" placeholder="px" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                    </div>
                                    <div>
                                        <label class="text-xs text-slate-400 mb-1 block">پدینگ پایین</label>
                                        <input type="number" min="0" max="200" :value="getCascadedValue('paddingBottom', '')" @input="updateStyle('paddingBottom', $event.target.value===''?'':Number($event.target.value))" placeholder="px" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                    </div>
                                    <div>
                                        <label class="text-xs text-slate-400 mb-1 block">پدینگ راست</label>
                                        <input type="number" min="0" max="200" :value="getCascadedValue('paddingRight', '')" @input="updateStyle('paddingRight', $event.target.value===''?'':Number($event.target.value))" placeholder="px" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                    </div>
                                    <div>
                                        <label class="text-xs text-slate-400 mb-1 block">پدینگ چپ</label>
                                        <input type="number" min="0" max="200" :value="getCascadedValue('paddingLeft', '')" @input="updateStyle('paddingLeft', $event.target.value===''?'':Number($event.target.value))" placeholder="px" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                    </div>
                                    <div>
                                        <label class="text-xs text-slate-400 mb-1 block">مارجین بالا</label>
                                        <input type="number" min="-100" max="200" :value="getCascadedValue('marginTop', '')" @input="updateStyle('marginTop', $event.target.value===''?'':Number($event.target.value))" placeholder="px" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                    </div>
                                    <div>
                                        <label class="text-xs text-slate-400 mb-1 block">مارجین پایین</label>
                                        <input type="number" min="-100" max="200" :value="getCascadedValue('marginBottom', '')" @input="updateStyle('marginBottom', $event.target.value===''?'':Number($event.target.value))" placeholder="px" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300">
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
                                        <input type="number" min="0" max="20" :value="getCascadedValue('borderWidth', '')" @input="updateStyle('borderWidth', $event.target.value===''?'':Number($event.target.value))" placeholder="۰" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                    </div>
                                    <div>
                                        <label class="text-xs text-slate-400 mb-1 block">سبک</label>
                                        <select :value="getCascadedValue('borderStyle', 'solid')" @change="updateStyle('borderStyle', $event.target.value)" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                            <option value="solid">solid</option>
                                            <option value="dashed">dashed</option>
                                            <option value="dotted">dotted</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-xs text-slate-400 mb-1 block">رنگ حاشیه</label>
                                        <input type="color" :value="getCascadedValue('borderColor', '#e5e7eb')" @change="updateStyle('borderColor', $event.target.value)" class="w-full h-8 rounded border border-slate-200 cursor-pointer p-0.5">
                                    </div>
                                    <div>
                                        <label class="text-xs text-slate-400 mb-1 block">گوشه گرد (px)</label>
                                        <input type="number" min="0" max="100" :value="getCascadedValue('borderRadius', '')" @input="updateStyle('borderRadius', $event.target.value===''?'':Number($event.target.value))" placeholder="۰" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300">
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
                                            :class="(getCascadedValue('shadow', 'none')) === s ? 'ring-2 ring-indigo-500 bg-indigo-50' : 'bg-white hover:bg-slate-50'"
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
                                    <input type="range" min="0.1" max="1" step="0.05" :value="getCascadedValue('opacity', 1)" @input="updateStyle('opacity', Number($event.target.value))" class="flex-1">
                                    <span class="text-xs text-slate-500 w-10 text-left" x-text="Math.round(getCascadedValue('opacity', 1) * 100) + '%'"></span>
                                </div>
                            </div>
                        </div>

                        <div class="pt-1">
                            <button @click="resetBlockStyle()" class="w-full text-xs py-1.5 rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-red-500 transition"
                                x-text="activeBreakpoint === 'desktop' ? 'پاک کردن همه استایل‌ها' : 'پاک کردن override ' + (activeBreakpoint === 'tablet' ? 'تبلت' : 'موبایل')">
                            </button>
                        </div>

                        </div>{{-- end px-3 py-3 --}}
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

                                <template x-if="schemaDef.type === 'string' && !isMediaControl(schemaDef, propKey, selectedBlock?.type) && !isColorControl(schemaDef, propKey, selectedBlock?.type)">
                                    <input
                                        type="text"
                                        x-model="selectedBlock.props[propKey]"
                                        @input="schedulePropUpdate()"
                                        class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 transition"
                                        :placeholder="schemaDef.default ?? ''"
                                        :data-testid="'tb-prop-' + propKey"
                                    >
                                </template>

                                {{-- TASK 3/3 Phase 6.5C (BLD-28) — internal-link-capable adapter
                                     for button/CTA/hero action URL fields. A plain string stays a
                                     raw custom URL (unchanged, backward compatible); an
                                     {type:'internal_page', page_id} value is resolved server-side
                                     (InternalLinkResolver, mirroring CustomerMediaResolver's
                                     {media_id} pattern) to the Page's CURRENT url at render time,
                                     so a later slug rename never breaks it — reuses the exact same
                                     bounded search endpoint Menu's own page picker uses. --}}
                                <template x-if="schemaDef.type === 'link'">
                                    <div class="space-y-2" :data-testid="'tb-link-field-' + propKey">
                                        <div class="flex gap-2 text-xs">
                                            <button type="button"
                                                    @click="setLinkCustomUrl(propKey, isInternalLinkValue(selectedBlock.props[propKey]) ? '' : (selectedBlock.props[propKey] || ''))"
                                                    :class="!isInternalLinkValue(selectedBlock.props[propKey]) ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600'"
                                                    class="px-2 py-1 rounded transition">آدرس سفارشی</button>
                                            <button type="button"
                                                    @click="openLinkPagePicker(propKey)"
                                                    :class="isInternalLinkValue(selectedBlock.props[propKey]) ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600'"
                                                    class="px-2 py-1 rounded transition"
                                                    :data-testid="'tb-link-page-toggle-' + propKey">صفحه داخلی</button>
                                        </div>

                                        <template x-if="!isInternalLinkValue(selectedBlock.props[propKey])">
                                            <input type="text" :value="selectedBlock.props[propKey]"
                                                   @input="setLinkCustomUrl(propKey, $event.target.value)"
                                                   class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300"
                                                   placeholder="https://..." :data-testid="'tb-prop-' + propKey">
                                        </template>

                                        <template x-if="isInternalLinkValue(selectedBlock.props[propKey])">
                                            <div class="flex items-center justify-between gap-2 px-3 py-2 rounded-lg border border-emerald-200 bg-emerald-50 text-xs text-emerald-800"
                                                 :data-testid="'tb-prop-' + propKey">
                                                <span x-text="'لینک به: ' + (selectedBlock.props[propKey].page_title || ('#' + selectedBlock.props[propKey].page_id))"></span>
                                                <button type="button" @click="openLinkPagePicker(propKey)" class="text-indigo-600 underline">تغییر</button>
                                            </div>
                                        </template>

                                        <template x-if="linkPickerOpenFor === propKey">
                                            <div class="border border-slate-200 rounded-lg p-2 space-y-1 bg-white">
                                                <input type="text" x-model="linkPickerQuery" @input.debounce.300ms="searchLinkPages()"
                                                       placeholder="جستجوی صفحه..." class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300"
                                                       :data-testid="'tb-link-page-search-' + propKey">
                                                <template x-for="page in linkPickerResults" :key="page.id">
                                                    <button type="button" @click="selectLinkPage(propKey, page)"
                                                            class="block w-full text-right text-xs px-2 py-1.5 rounded hover:bg-indigo-50"
                                                            :data-testid="'tb-link-page-result-' + page.id"
                                                            x-text="page.title"></button>
                                                </template>
                                                <p x-show="linkPickerQuery && linkPickerResults.length === 0" class="text-xs text-slate-400 px-2 py-1">صفحه‌ای یافت نشد.</p>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                {{-- One media adapter for every semantically-image property.
                                     It delegates to the existing Canvas media library/upload
                                     endpoints; individual blocks never own an uploader. --}}
                                <template x-if="isMediaControl(schemaDef, propKey, selectedBlock?.type)">
                                    <div class="space-y-2">
                                        <template x-if="mediaSrc(selectedBlock.props[propKey])">
                                            <img :src="mediaSrc(selectedBlock.props[propKey])" :data-testid="'tb-media-preview-' + propKey" class="w-full h-28 object-cover rounded-lg border border-slate-200">
                                        </template>
                                        <div class="flex gap-2">
                                            <input type="url" :value="mediaSrc(selectedBlock.props[propKey])" @input="selectedBlock.props[propKey] = $event.target.value; schedulePropUpdate()" :data-testid="'tb-media-value-' + propKey" placeholder="آدرس تصویر (یا انتخاب از کتابخانه)" class="flex-1 text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                            <button @click="openMediaPicker(propKey)" :data-testid="'tb-media-picker-' + propKey" class="px-2 py-1.5 rounded border border-indigo-300 text-indigo-600 hover:bg-indigo-50 text-xs transition">📁</button>
                                        </div>
                                    </div>
                                </template>

                                {{-- Schema-driven structured/repeater adapter. It only
                                     translates the schema's declared storage shape for
                                     authoring; nested image fields reuse the same media
                                     modal and upload endpoints as single-image fields. --}}
                                <template x-if="schemaDef.type === 'repeater'">
                                    <div class="space-y-3" :data-testid="'tb-repeater-' + propKey">
                                        <template x-for="(item, itemIndex) in structuredItems(propKey, schemaDef)" :key="itemIndex">
                                            <fieldset class="rounded-lg border border-slate-200 bg-slate-50 p-3 space-y-3">
                                                <legend class="px-1 text-[11px] font-semibold text-slate-500" x-text="`${schemaDef.label || propKey} ${itemIndex + 1}`"></legend>
                                                <template x-for="(fieldDef, fieldKey) in schemaDef.fields" :key="fieldKey">
                                                    <div class="space-y-1.5">
                                                        <label class="block text-[11px] font-medium text-slate-600" :for="`tb-structured-${propKey}-${itemIndex}-${fieldKey}`" x-text="fieldDef.label || fieldKey"></label>
                                                        <template x-if="fieldDef.type === 'image'">
                                                            <div class="space-y-2">
                                                                <template x-if="mediaSrc(item[fieldKey])">
                                                                    <div>
                                                                        <img :src="mediaSrc(item[fieldKey])" alt="" class="w-full h-24 object-cover rounded-lg border border-slate-200" :data-testid="`tb-structured-preview-${propKey}-${itemIndex}-${fieldKey}`">
                                                                    </div>
                                                                </template>
                                                                <div class="flex gap-2">
                                                                    <button type="button" :id="`tb-structured-${propKey}-${itemIndex}-${fieldKey}`" @click="openMediaPicker(propKey, { index: itemIndex, fieldKey })" :data-testid="`tb-structured-media-${propKey}-${itemIndex}-${fieldKey}`" class="flex-1 px-2 py-2 rounded border border-indigo-300 text-indigo-600 hover:bg-indigo-50 text-xs transition" :aria-label="`انتخاب ${fieldDef.label || fieldKey} برای مورد ${itemIndex + 1}`">انتخاب از رسانه</button>
                                                                    <button type="button" @click="updateStructuredField(propKey, itemIndex, fieldKey, '', schemaDef)" :data-testid="`tb-structured-clear-${propKey}-${itemIndex}-${fieldKey}`" class="px-2 py-2 rounded border border-slate-200 text-slate-600 hover:text-red-600 text-xs" :aria-label="`حذف ${fieldDef.label || fieldKey} از مورد ${itemIndex + 1}`">حذف تصویر</button>
                                                                </div>
                                                            </div>
                                                        </template>
                                                        <template x-if="fieldDef.type === 'repeater'">
                                                            <div class="space-y-2 rounded-lg border border-slate-200 bg-white p-2" :data-testid="`tb-nested-repeater-${propKey}-${itemIndex}-${fieldKey}`">
                                                                <template x-for="(nestedItem, nestedIndex) in nestedItems(propKey, itemIndex, fieldKey)" :key="nestedIndex">
                                                                    <div class="rounded border border-slate-200 bg-slate-50 p-2 space-y-2">
                                                                        <div class="flex items-center justify-between">
                                                                            <span class="text-[10px] font-semibold text-slate-500" x-text="`${fieldDef.label || fieldKey} ${nestedIndex + 1}`"></span>
                                                                            <div class="flex gap-1">
                                                                                <button type="button" @click="moveNestedItem(propKey, itemIndex, fieldKey, nestedIndex, -1, schemaDef)" :disabled="nestedIndex === 0" :data-testid="`tb-nested-up-${propKey}-${itemIndex}-${fieldKey}-${nestedIndex}`" class="px-1.5 py-0.5 rounded border border-slate-200 text-[10px] disabled:opacity-30" :aria-label="`انتقال به بالا`">↑</button>
                                                                                <button type="button" @click="moveNestedItem(propKey, itemIndex, fieldKey, nestedIndex, 1, schemaDef)" :disabled="nestedIndex >= nestedItems(propKey, itemIndex, fieldKey).length - 1" :data-testid="`tb-nested-down-${propKey}-${itemIndex}-${fieldKey}-${nestedIndex}`" class="px-1.5 py-0.5 rounded border border-slate-200 text-[10px] disabled:opacity-30" :aria-label="`انتقال به پایین`">↓</button>
                                                                                <button type="button" @click="removeNestedItem(propKey, itemIndex, fieldKey, nestedIndex, schemaDef)" :data-testid="`tb-nested-remove-${propKey}-${itemIndex}-${fieldKey}-${nestedIndex}`" class="px-1.5 py-0.5 rounded border border-red-200 text-red-600 text-[10px]" :aria-label="`حذف`">حذف</button>
                                                                            </div>
                                                                        </div>
                                                                        <template x-for="(nestedFieldDef, nestedFieldKey) in fieldDef.fields" :key="nestedFieldKey">
                                                                            <div class="space-y-1">
                                                                                <label class="block text-[10px] font-medium text-slate-600" x-text="nestedFieldDef.label || nestedFieldKey"></label>
                                                                                <template x-if="nestedFieldDef.type === 'image'">
                                                                                    <div class="space-y-1">
                                                                                        <template x-if="mediaSrc(nestedItem[nestedFieldKey])">
                                                                                            <img :src="mediaSrc(nestedItem[nestedFieldKey])" alt="" class="w-full h-16 object-cover rounded border border-slate-200" :data-testid="`tb-nested-preview-${propKey}-${itemIndex}-${fieldKey}-${nestedIndex}-${nestedFieldKey}`">
                                                                                        </template>
                                                                                        <div class="flex gap-1">
                                                                                            <button type="button" @click="openMediaPicker(propKey, { index: itemIndex, fieldKey, nestedIndex, nestedFieldKey })" :data-testid="`tb-nested-media-${propKey}-${itemIndex}-${fieldKey}-${nestedIndex}-${nestedFieldKey}`" class="flex-1 px-2 py-1 rounded border border-indigo-300 text-indigo-600 hover:bg-indigo-50 text-[10px]">انتخاب از رسانه</button>
                                                                                            <button type="button" @click="updateNestedField(propKey, itemIndex, fieldKey, nestedIndex, nestedFieldKey, '', schemaDef)" :data-testid="`tb-nested-clear-${propKey}-${itemIndex}-${fieldKey}-${nestedIndex}-${nestedFieldKey}`" class="px-2 py-1 rounded border border-slate-200 text-slate-600 hover:text-red-600 text-[10px]">حذف</button>
                                                                                        </div>
                                                                                    </div>
                                                                                </template>
                                                                                {{-- One further explicit, bounded repeater level (columns → items)
                                                                                     for Mega Menu's real independent column/group structure — see
                                                                                     the deepNestedItems()/*DeepNested* Alpine methods. Still not a
                                                                                     generic recursive mechanism; a deep item's own fields are plain
                                                                                     string/textarea/image only. --}}
                                                                                <template x-if="nestedFieldDef.type === 'repeater'">
                                                                                    <div class="space-y-2 rounded border border-slate-200 bg-white p-2" :data-testid="`tb-deep-repeater-${propKey}-${itemIndex}-${fieldKey}-${nestedIndex}-${nestedFieldKey}`">
                                                                                        <template x-for="(deepItem, deepIndex) in deepNestedItems(propKey, itemIndex, fieldKey, nestedIndex, nestedFieldKey)" :key="deepIndex">
                                                                                            <div class="rounded border border-slate-200 bg-slate-50 p-2 space-y-1">
                                                                                                <div class="flex items-center justify-between">
                                                                                                    <span class="text-[10px] font-semibold text-slate-500" x-text="`${nestedFieldDef.label || nestedFieldKey} ${deepIndex + 1}`"></span>
                                                                                                    <div class="flex gap-1">
                                                                                                        <button type="button" @click="moveDeepNestedItem(propKey, itemIndex, fieldKey, nestedIndex, nestedFieldKey, deepIndex, -1, schemaDef)" :disabled="deepIndex === 0" :data-testid="`tb-deep-up-${propKey}-${itemIndex}-${fieldKey}-${nestedIndex}-${nestedFieldKey}-${deepIndex}`" class="px-1.5 py-0.5 rounded border border-slate-200 text-[10px] disabled:opacity-30">↑</button>
                                                                                                        <button type="button" @click="moveDeepNestedItem(propKey, itemIndex, fieldKey, nestedIndex, nestedFieldKey, deepIndex, 1, schemaDef)" :disabled="deepIndex >= deepNestedItems(propKey, itemIndex, fieldKey, nestedIndex, nestedFieldKey).length - 1" :data-testid="`tb-deep-down-${propKey}-${itemIndex}-${fieldKey}-${nestedIndex}-${nestedFieldKey}-${deepIndex}`" class="px-1.5 py-0.5 rounded border border-slate-200 text-[10px] disabled:opacity-30">↓</button>
                                                                                                        <button type="button" @click="removeDeepNestedItem(propKey, itemIndex, fieldKey, nestedIndex, nestedFieldKey, deepIndex, schemaDef)" :data-testid="`tb-deep-remove-${propKey}-${itemIndex}-${fieldKey}-${nestedIndex}-${nestedFieldKey}-${deepIndex}`" class="px-1.5 py-0.5 rounded border border-red-200 text-red-600 text-[10px]">حذف</button>
                                                                                                    </div>
                                                                                                </div>
                                                                                                <template x-for="(deepFieldDef, deepFieldKey) in nestedFieldDef.fields" :key="deepFieldKey">
                                                                                                    <div class="space-y-1">
                                                                                                        <label class="block text-[10px] font-medium text-slate-600" x-text="deepFieldDef.label || deepFieldKey"></label>
                                                                                                        <template x-if="deepFieldDef.type === 'image'">
                                                                                                            <div class="space-y-1">
                                                                                                                <template x-if="mediaSrc(deepItem[deepFieldKey])">
                                                                                                                    <img :src="mediaSrc(deepItem[deepFieldKey])" alt="" class="w-full h-14 object-cover rounded border border-slate-200">
                                                                                                                </template>
                                                                                                                <div class="flex gap-1">
                                                                                                                    <button type="button" @click="openMediaPicker(propKey, { index: itemIndex, fieldKey, nestedIndex, nestedFieldKey, deepIndex, deepFieldKey })" :data-testid="`tb-deep-media-${propKey}-${itemIndex}-${fieldKey}-${nestedIndex}-${nestedFieldKey}-${deepIndex}-${deepFieldKey}`" class="flex-1 px-2 py-1 rounded border border-indigo-300 text-indigo-600 hover:bg-indigo-50 text-[10px]">انتخاب از رسانه</button>
                                                                                                                    <button type="button" @click="updateDeepNestedField(propKey, itemIndex, fieldKey, nestedIndex, nestedFieldKey, deepIndex, deepFieldKey, '', schemaDef)" class="px-2 py-1 rounded border border-slate-200 text-slate-600 hover:text-red-600 text-[10px]">حذف</button>
                                                                                                                </div>
                                                                                                            </div>
                                                                                                        </template>
                                                                                                        <template x-if="deepFieldDef.type === 'textarea'">
                                                                                                            <textarea :value="deepItem[deepFieldKey] ?? ''" @input="updateDeepNestedField(propKey, itemIndex, fieldKey, nestedIndex, nestedFieldKey, deepIndex, deepFieldKey, $event.target.value, schemaDef)" rows="2" :data-testid="`tb-deep-field-${propKey}-${itemIndex}-${fieldKey}-${nestedIndex}-${nestedFieldKey}-${deepIndex}-${deepFieldKey}`" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 bg-white focus:outline-none focus:ring-1 focus:ring-indigo-300"></textarea>
                                                                                                        </template>
                                                                                                        <template x-if="deepFieldDef.type !== 'image' && deepFieldDef.type !== 'textarea'">
                                                                                                            <input type="text" :value="deepItem[deepFieldKey] ?? ''" @input="updateDeepNestedField(propKey, itemIndex, fieldKey, nestedIndex, nestedFieldKey, deepIndex, deepFieldKey, $event.target.value, schemaDef)" :data-testid="`tb-deep-field-${propKey}-${itemIndex}-${fieldKey}-${nestedIndex}-${nestedFieldKey}-${deepIndex}-${deepFieldKey}`" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 bg-white focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                                                                                        </template>
                                                                                                    </div>
                                                                                                </template>
                                                                                            </div>
                                                                                        </template>
                                                                                        <button type="button" @click="addDeepNestedItem(propKey, itemIndex, fieldKey, nestedIndex, nestedFieldKey, nestedFieldDef, schemaDef)" :data-testid="`tb-deep-add-${propKey}-${itemIndex}-${fieldKey}-${nestedIndex}-${nestedFieldKey}`" class="w-full px-2 py-1 rounded border border-dashed border-indigo-300 text-indigo-600 hover:bg-indigo-50 text-[10px] font-medium">+ افزودن <span x-text="nestedFieldDef.label || nestedFieldKey"></span></button>
                                                                                    </div>
                                                                                </template>
                                                                                <template x-if="nestedFieldDef.type === 'textarea'">
                                                                                    <textarea :value="nestedItem[nestedFieldKey] ?? ''" @input="updateNestedField(propKey, itemIndex, fieldKey, nestedIndex, nestedFieldKey, $event.target.value, schemaDef)" rows="2" :data-testid="`tb-nested-field-${propKey}-${itemIndex}-${fieldKey}-${nestedIndex}-${nestedFieldKey}`" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 bg-white focus:outline-none focus:ring-1 focus:ring-indigo-300"></textarea>
                                                                                </template>
                                                                                <template x-if="nestedFieldDef.type !== 'textarea' && nestedFieldDef.type !== 'image' && nestedFieldDef.type !== 'repeater'">
                                                                                    <input type="text" :value="nestedItem[nestedFieldKey] ?? ''" @input="updateNestedField(propKey, itemIndex, fieldKey, nestedIndex, nestedFieldKey, $event.target.value, schemaDef)" :data-testid="`tb-nested-field-${propKey}-${itemIndex}-${fieldKey}-${nestedIndex}-${nestedFieldKey}`" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 bg-white focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                                                                </template>
                                                                            </div>
                                                                        </template>
                                                                    </div>
                                                                </template>
                                                                <button type="button" @click="addNestedItem(propKey, itemIndex, fieldKey, fieldDef, schemaDef)" :data-testid="`tb-nested-add-${propKey}-${itemIndex}-${fieldKey}`" class="w-full px-2 py-1.5 rounded border border-dashed border-indigo-300 text-indigo-600 hover:bg-indigo-50 text-[11px] font-medium">+ افزودن <span x-text="fieldDef.label || fieldKey"></span></button>
                                                            </div>
                                                        </template>
                                                        <template x-if="fieldDef.type === 'boolean'">
                                                            <label class="flex items-center gap-2 cursor-pointer">
                                                                <input type="checkbox" :checked="!!item[fieldKey]" @change="updateStructuredField(propKey, itemIndex, fieldKey, $event.target.checked, schemaDef)" :data-testid="`tb-structured-field-${propKey}-${itemIndex}-${fieldKey}`">
                                                                <span class="text-[11px] text-slate-600" x-text="item[fieldKey] ? 'فعال' : 'غیرفعال'"></span>
                                                            </label>
                                                        </template>
                                                        <template x-if="fieldDef.type === 'select'">
                                                            <select :value="item[fieldKey] ?? ''" @change="updateStructuredField(propKey, itemIndex, fieldKey, $event.target.value, schemaDef)" :data-testid="`tb-structured-field-${propKey}-${itemIndex}-${fieldKey}`" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 bg-white focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                                                <template x-for="(optLabel, optVal) in fieldDef.options" :key="optVal">
                                                                    <option :value="optVal" x-text="optLabel"></option>
                                                                </template>
                                                            </select>
                                                        </template>
                                                        <template x-if="fieldDef.type !== 'image' && fieldDef.type !== 'repeater' && fieldDef.type !== 'boolean' && fieldDef.type !== 'select'">
                                                            <div>
                                                                <template x-if="fieldDef.type === 'textarea'">
                                                                    <textarea :id="`tb-structured-${propKey}-${itemIndex}-${fieldKey}`" :value="item[fieldKey] ?? ''" @input="updateStructuredField(propKey, itemIndex, fieldKey, $event.target.value, schemaDef)" rows="2" :data-testid="`tb-structured-field-${propKey}-${itemIndex}-${fieldKey}`" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 bg-white focus:outline-none focus:ring-1 focus:ring-indigo-300"></textarea>
                                                                </template>
                                                                <template x-if="fieldDef.type !== 'textarea'">
                                                                    <input type="text" :id="`tb-structured-${propKey}-${itemIndex}-${fieldKey}`" :value="item[fieldKey] ?? ''" @input="updateStructuredField(propKey, itemIndex, fieldKey, $event.target.value, schemaDef)" :data-testid="`tb-structured-field-${propKey}-${itemIndex}-${fieldKey}`" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 bg-white focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                                                </template>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </template>
                                                <div class="flex gap-1 justify-end">
                                                    <button type="button" @click="moveStructuredItem(propKey, itemIndex, -1, schemaDef)" :disabled="itemIndex === 0" :data-testid="`tb-structured-up-${propKey}-${itemIndex}`" class="px-2 py-1 rounded border border-slate-200 text-xs disabled:opacity-30" :aria-label="`انتقال مورد ${itemIndex + 1} به بالا`">↑</button>
                                                    <button type="button" @click="moveStructuredItem(propKey, itemIndex, 1, schemaDef)" :disabled="itemIndex >= structuredItems(propKey, schemaDef).length - 1" :data-testid="`tb-structured-down-${propKey}-${itemIndex}`" class="px-2 py-1 rounded border border-slate-200 text-xs disabled:opacity-30" :aria-label="`انتقال مورد ${itemIndex + 1} به پایین`">↓</button>
                                                    <button type="button" @click="removeStructuredItem(propKey, itemIndex, schemaDef)" :data-testid="`tb-structured-remove-${propKey}-${itemIndex}`" class="px-2 py-1 rounded border border-red-200 text-red-600 text-xs" :aria-label="`حذف مورد ${itemIndex + 1}`">حذف مورد</button>
                                                </div>
                                            </fieldset>
                                        </template>
                                        <button type="button" @click="addStructuredItem(propKey, schemaDef)" :data-testid="'tb-structured-add-' + propKey" class="w-full px-3 py-2 rounded-lg border border-dashed border-indigo-300 text-indigo-600 hover:bg-indigo-50 text-xs font-medium">+ افزودن مورد</button>
                                    </div>
                                </template>

                                <template x-if="schemaDef.type === 'number'">
                                    <input
                                        type="number"
                                        x-model.number="selectedBlock.props[propKey]"
                                        @input="schedulePropUpdate()"
                                        class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 transition"
                                        :min="0"
                                        :data-testid="'tb-prop-' + propKey"
                                    >
                                </template>

                                {{-- Theme Builder Motion Picker: visual selection from this
                                     site's own permitted assets only (fetched via
                                     ROUTES.motionAssets -> the same runtime manifest the
                                     motion runtime component already uses). Never a
                                     free-text asset id field. --}}
                                <template x-if="schemaDef.type === 'motion'">
                                    <div class="space-y-2">
                                        <template x-if="selectedBlock.props[propKey]">
                                            <div class="flex items-center gap-2 border border-slate-200 rounded-lg px-2.5 py-2 bg-slate-50">
                                                <template x-if="motionAssetById(selectedBlock.props[propKey])?.thumbnail">
                                                    <img :src="motionAssetById(selectedBlock.props[propKey]).thumbnail" class="w-8 h-8 rounded object-cover bg-white border border-slate-200">
                                                </template>
                                                <template x-if="!motionAssetById(selectedBlock.props[propKey])?.thumbnail">
                                                    <span class="w-8 h-8 rounded bg-indigo-50 flex items-center justify-center text-indigo-400 text-xs shrink-0">🎬</span>
                                                </template>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-xs font-semibold text-slate-700 truncate" x-text="motionAssetById(selectedBlock.props[propKey])?.name || selectedBlock.props[propKey]"></p>
                                                    <p class="text-[11px] text-slate-400 truncate" x-text="motionAssetById(selectedBlock.props[propKey])?.category || 'در دسترس این سایت'"></p>
                                                </div>
                                                <button @click="clearMotionSelect(propKey)" title="حذف انتخاب" class="text-slate-400 hover:text-red-500 text-sm px-1">×</button>
                                            </div>
                                        </template>
                                        <template x-if="!selectedBlock.props[propKey]">
                                            <p class="text-xs text-slate-400">موشنی انتخاب نشده — این بلوک چیزی نمایش نمی‌دهد.</p>
                                        </template>
                                        <button @click="openMotionPicker(propKey)" class="w-full text-xs px-3 py-2 rounded-lg border border-indigo-300 text-indigo-600 hover:bg-indigo-50 transition font-medium">
                                            🎬 انتخاب موشن از موشن‌های مجاز این سایت
                                        </button>
                                    </div>
                                </template>

                                <template x-if="schemaDef.type === 'boolean'">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <div class="relative">
                                            <input type="checkbox" x-model="selectedBlock.props[propKey]" @change="schedulePropUpdate()" class="sr-only peer" :data-testid="'tb-prop-' + propKey">
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
                                        :data-testid="'tb-prop-' + propKey"
                                    >
                                        <template x-for="(label, val) in schemaDef.options" :key="val">
                                            <option :value="val" x-text="label"></option>
                                        </template>
                                    </select>
                                </template>

                                <template x-if="isColorControl(schemaDef, propKey, selectedBlock?.type)">
                                    <div class="space-y-2">
                                        <div class="flex items-center gap-2">
                                        <input type="color" :value="colorInputValue(selectedBlock.props[propKey])" @input="selectedBlock.props[propKey] = $event.target.value; schedulePropUpdate()" class="w-10 h-10 rounded-lg border border-slate-200 cursor-pointer p-0.5">
                                        <input type="text"  x-model="selectedBlock.props[propKey]" @input="schedulePropUpdate()" class="flex-1 text-sm px-3 py-2 rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300 font-mono" placeholder="#000000" :data-testid="'tb-prop-' + propKey">
                                        <button type="button" @click="clearColor(propKey, schemaDef.default ?? '')" class="px-2 py-1.5 rounded border border-slate-200 text-xs text-slate-600 hover:bg-slate-50">پاک‌کردن</button>
                                        </div>
                                        <div class="flex flex-wrap gap-1.5" aria-label="پالت رنگ قالب">
                                            <template x-for="color in themePalette()" :key="color.token">
                                                <button type="button" @click="selectThemeColor(propKey, color)" :data-testid="'tb-theme-color-' + propKey + '-' + color.token" :title="color.label" class="w-6 h-6 rounded-full border-2 border-white shadow ring-1 ring-slate-200 hover:scale-110 transition" :style="'background:' + color.value"></button>
                                            </template>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="schemaDef.type === 'textarea'">
                                    <textarea
                                        x-model="selectedBlock.props[propKey]"
                                        @input="schedulePropUpdate()"
                                        rows="3"
                                        class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 transition resize-y"
                                        :placeholder="schemaDef.default ?? ''"
                                        :data-testid="'tb-prop-' + propKey"
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
                                    <button x-show="selectedBlock?.type === 'tabs'" @click="addTab()" data-testid="tb-add-tab"
                                        class="text-xs px-2 py-1 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100 font-medium transition flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                        افزودن تب
                                    </button>
                                    <button x-show="selectedBlock?.type !== 'tabs'" @click="showAddChildModal = true" data-testid="tb-open-add-child-modal"
                                        class="text-xs px-2 py-1 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100 font-medium transition flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                        افزودن
                                    </button>
                                </div>
                                <div class="space-y-1 max-h-48 overflow-y-auto">
                                    <template x-for="(child, ci) in (selectedBlock?.children ?? [])" :key="child.id">
                                        <div class="flex items-center gap-1 px-2 py-1.5 rounded-lg border border-slate-200 bg-slate-50 text-xs group">
                                            <span :class="blockCategoryColor(child.type)" class="w-1.5 h-1.5 rounded-full shrink-0"></span>
                                            <span class="flex-1 truncate text-slate-700" x-text="child.props?.label ? (blockLabel(child.type) + ': ' + child.props.label) : blockLabel(child.type)" :data-testid="'tb-child-label-' + child.id"></span>
                                            <div class="flex items-center gap-0.5 opacity-0 group-hover:opacity-100 transition">
                                                <button @click="selectBlock([...selectedPath, ci])" :data-testid="'tb-child-edit-' + child.id"
                                                    class="w-5 h-5 flex items-center justify-center rounded text-indigo-500 hover:bg-indigo-50" title="ویرایش">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                </button>
                                                <button @click="moveChildUp(ci)" :disabled="ci === 0" :data-testid="'tb-child-up-' + child.id"
                                                    class="w-5 h-5 flex items-center justify-center rounded text-slate-400 hover:bg-slate-100 disabled:opacity-30" title="بالاتر">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/></svg>
                                                </button>
                                                <button @click="moveChildDown(ci)" :disabled="ci >= (selectedBlock?.children?.length ?? 0) - 1" :data-testid="'tb-child-down-' + child.id"
                                                    class="w-5 h-5 flex items-center justify-center rounded text-slate-400 hover:bg-slate-100 disabled:opacity-30" title="پایین‌تر">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                                </button>
                                                <button @click="removeChild(ci)" :data-testid="'tb-child-remove-' + child.id"
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
                            data-testid="tb-add-child-{{ $blockType }}"
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

    {{-- TB6: TEMPLATE LIBRARY MODAL --}}
    <div x-show="showTemplateLibrary" x-transition.opacity class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4" style="display:none">
        <div @click.outside="showTemplateLibrary = false" class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl flex flex-col" style="max-height:88vh">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 shrink-0">
                <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm0 8a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zm12 0a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>
                    کتابخانه قالب‌ها
                </h3>
                <button @click="showTemplateLibrary = false" class="text-slate-400 hover:text-slate-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="flex items-center gap-3 px-5 py-3 border-b border-slate-100 shrink-0 bg-slate-50">
                <input type="text" x-model="templateLibrary.search" @input.debounce.400ms="fetchTemplates()"
                    placeholder="جستجو…"
                    class="flex-1 text-sm px-3 py-1.5 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-300">
                <select x-model="templateLibrary.type" @change="fetchTemplates()"
                    class="text-sm px-3 py-1.5 rounded-lg border border-slate-200 focus:outline-none">
                    <option value="">همه انواع</option>
                    <option value="section">بخش</option>
                    <option value="page">صفحه</option>
                    <option value="header">هدر</option>
                    <option value="footer">فوتر</option>
                    <option value="landing_page">لندینگ پیج</option>
                </select>
                <select x-model="templateLibrary.category" @change="fetchTemplates()"
                    class="text-sm px-3 py-1.5 rounded-lg border border-slate-200 focus:outline-none">
                    <option value="">همه دسته‌ها</option>
                    <option value="hero">هیرو</option>
                    <option value="features">ویژگی‌ها</option>
                    <option value="pricing">قیمت‌گذاری</option>
                    <option value="testimonials">نظرات</option>
                    <option value="contact">تماس</option>
                    <option value="gallery">گالری</option>
                    <option value="blog">وبلاگ</option>
                    <option value="product">محصول</option>
                </select>
            </div>
            <div class="flex-1 overflow-y-auto p-5">
                <div x-show="templateLibrary.loading" class="flex items-center justify-center py-16">
                    <svg class="w-8 h-8 text-purple-400 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                </div>
                <div x-show="!templateLibrary.loading && templateLibrary.templates.length === 0" class="text-center py-12 text-slate-400 text-sm">
                    هیچ قالبی یافت نشد. قالب‌ها را از طریق دکمه «ذخیره قالب» اضافه کنید.
                </div>
                <div x-show="!templateLibrary.loading && templateLibrary.templates.length > 0"
                    class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                    <template x-for="tpl in templateLibrary.templates" :key="tpl.id">
                        <div class="border border-slate-200 rounded-xl overflow-hidden hover:shadow-md transition cursor-pointer group flex flex-col"
                            @click="insertTemplate(tpl.id)">
                            <div class="aspect-video bg-gradient-to-br from-slate-100 to-purple-50 flex items-center justify-center relative overflow-hidden">
                                <template x-if="tpl.thumbnail_url">
                                    <img :src="tpl.thumbnail_url" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!tpl.thumbnail_url">
                                    <svg class="w-8 h-8 text-purple-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5z"/></svg>
                                </template>
                                <span class="absolute top-1.5 right-1.5 text-[9px] font-bold px-1.5 py-0.5 rounded-full bg-white/90 text-purple-700" x-text="tpl.type_label"></span>
                                <div class="absolute inset-0 bg-purple-600/0 group-hover:bg-purple-600/10 transition flex items-center justify-center">
                                    <span class="opacity-0 group-hover:opacity-100 bg-purple-600 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition">درج</span>
                                </div>
                            </div>
                            <div class="p-3">
                                <p class="text-xs font-semibold text-slate-800 truncate" x-text="tpl.name"></p>
                                <p class="text-[10px] text-slate-400 mt-0.5" x-text="tpl.category_label"></p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    {{-- TB6: SAVE AS TEMPLATE MODAL --}}
    <div x-show="showSaveAsTemplate" x-transition.opacity class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" style="display:none">
        <div @click.outside="showSaveAsTemplate = false" class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
            <h3 class="text-base font-bold text-slate-800 mb-4">ذخیره به‌عنوان قالب</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">نام قالب *</label>
                    <input type="text" x-model="saveTemplateForm.name" placeholder="نام قالب…"
                        class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-300">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">نوع</label>
                        <select x-model="saveTemplateForm.type" class="w-full text-sm px-2 py-2 rounded-lg border border-slate-200 focus:outline-none">
                            <option value="section">بخش</option>
                            <option value="page">صفحه</option>
                            <option value="header">هدر</option>
                            <option value="footer">فوتر</option>
                            <option value="landing_page">لندینگ پیج</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">دسته</label>
                        <select x-model="saveTemplateForm.category" class="w-full text-sm px-2 py-2 rounded-lg border border-slate-200 focus:outline-none">
                            <option value="hero">هیرو</option>
                            <option value="features">ویژگی‌ها</option>
                            <option value="pricing">قیمت‌گذاری</option>
                            <option value="testimonials">نظرات</option>
                            <option value="contact">تماس</option>
                            <option value="gallery">گالری</option>
                            <option value="team">تیم</option>
                            <option value="blog">وبلاگ</option>
                            <option value="product">محصول</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">محدوده ذخیره</label>
                    <div class="flex gap-2">
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" x-model="saveTemplateForm.scope" value="block" class="text-amber-500">
                            <span class="text-xs text-slate-700">فقط بلوک انتخابی</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" x-model="saveTemplateForm.scope" value="page" class="text-amber-500">
                            <span class="text-xs text-slate-700">کل صفحه</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="flex gap-2 mt-5">
                <button @click="showSaveAsTemplate = false" class="flex-1 py-2 rounded-lg border border-slate-200 text-slate-600 text-sm font-medium hover:bg-slate-50 transition">انصراف</button>
                <button @click="saveAsTemplate()" :disabled="!saveTemplateForm.name || saveTemplateForm.saving"
                    class="flex-1 py-2 rounded-lg bg-amber-500 text-white text-sm font-semibold hover:bg-amber-600 disabled:opacity-50 transition">
                    <span x-show="!saveTemplateForm.saving">ذخیره</span>
                    <span x-show="saveTemplateForm.saving">در حال ذخیره…</span>
                </button>
            </div>
        </div>
    </div>

    {{-- ADD PAGE MODAL --}}
    <div x-show="showAddPage" x-transition.opacity class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div @click.outside="showAddPage = false" class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
            <h3 class="text-base font-bold text-slate-800 mb-4">افزودن صفحه جدید</h3>
            <div class="space-y-3">
                <div>
                    <label class="text-xs font-semibold text-slate-600 mb-1.5 block">عنوان صفحه *</label>
                    <input x-model="newPageTitle" data-testid="tb-add-page-title" type="text" class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-300" placeholder="مثال: درباره ما" @keydown.enter="confirmAddPage()">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600 mb-1.5 block">اسلاگ (URL) <span class="text-slate-400 font-normal">اختیاری</span></label>
                    <input x-model="newPageSlug" data-testid="tb-add-page-slug" type="text" class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-300" placeholder="about-us">
                </div>
            </div>
            <div class="flex gap-2 mt-5">
                <button @click="confirmAddPage()" data-testid="tb-add-page-confirm" :disabled="!newPageTitle" class="flex-1 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 disabled:opacity-50 transition">افزودن</button>
                <button @click="showAddPage = false; newPageTitle=''; newPageSlug=''" class="flex-1 py-2 rounded-lg border border-slate-200 text-slate-600 text-sm font-medium hover:bg-slate-50 transition">انصراف</button>
            </div>
        </div>
    </div>

    {{-- Media Modal --}}
    <div x-show="mediaModal.open" x-transition.opacity class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
        <div @click.outside="closeMediaPicker()" role="dialog" aria-modal="true" aria-labelledby="tb-media-dialog-title" class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl flex flex-col" style="max-height:80vh;">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 shrink-0">
                <h3 id="tb-media-dialog-title" class="font-bold text-slate-800 text-sm">مدیریت رسانه</h3>
                <button type="button" @click="closeMediaPicker()" aria-label="بستن انتخاب رسانه" class="text-slate-400 hover:text-slate-700 text-xl leading-none">×</button>
            </div>
            <div class="flex border-b border-slate-100 shrink-0">
                <button @click="mediaModal.tab = 'library'" :class="mediaModal.tab==='library'?'border-b-2 border-indigo-500 text-indigo-600 font-semibold':'text-slate-500'" class="flex-1 py-2.5 text-xs text-center transition">کتابخانه</button>
                <button @click="mediaModal.tab = 'upload'" data-testid="tb-media-upload-tab" :class="mediaModal.tab==='upload'?'border-b-2 border-indigo-500 text-indigo-600 font-semibold':'text-slate-500'" class="flex-1 py-2.5 text-xs text-center transition">آپلود</button>
            </div>
            {{-- Library — the same canonical Media Library وبسایت → کتابخانه
                 رسانه uses: real site-scoped CustomerMedia records via
                 CustomerMediaService, never a separate theme-media store. --}}
            <div x-show="mediaModal.tab === 'library'" class="flex-1 overflow-y-auto p-4">
                <div class="flex gap-2 mb-3">
                    <input type="search" x-model="mediaModal.q" @input.debounce.400ms="fetchMedia()" data-testid="tb-media-search" placeholder="جستجو در کتابخانه رسانه" class="flex-1 text-xs px-3 py-2 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                    <select x-model="mediaModal.category" @change="fetchMedia()" data-testid="tb-media-category" class="text-xs px-2 py-2 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        <option value="">همه انواع</option>
                        <option value="image">تصویر</option>
                        <option value="video">ویدئو</option>
                        <option value="audio">صوت</option>
                        <option value="document">سند</option>
                    </select>
                </div>
                <div x-show="mediaModal.loading" class="text-center py-10 text-slate-400 text-sm">در حال بارگذاری...</div>
                <div x-show="!mediaModal.loading && mediaModal.images.length === 0" class="text-center py-10 text-slate-400 text-sm">رسانه‌ای یافت نشد</div>
                <div x-show="!mediaModal.loading" class="grid grid-cols-4 gap-3">
                    <template x-for="img in mediaModal.images" :key="img.uuid">
                        <div class="relative group rounded-lg overflow-hidden border-2 cursor-pointer transition"
                             :data-testid="'tb-media-item-' + img.uuid"
                             :class="mediaModal.selected === img.uuid ? 'border-indigo-500' : 'border-transparent hover:border-indigo-300'"
                             @click="selectMedia(img.uuid)"
                             :title="img.title || img.name">
                            <img :src="img.url" class="w-full h-20 object-cover" loading="lazy">
                        </div>
                    </template>
                </div>
            </div>
            {{-- Upload — delegates to CustomerMediaService::upload(), the
                 same sole write authority the main Media Library uses. --}}
            <div x-show="mediaModal.tab === 'upload'" class="flex-1 p-4 flex flex-col items-center justify-center">
                <div class="w-full border-2 border-dashed border-slate-300 rounded-xl p-8 text-center cursor-pointer hover:border-indigo-400 hover:bg-indigo-50 transition"
                     @dragover.prevent @drop.prevent="handleMediaDrop($event)"
                     @click="$refs.mediaFileInput.click()">
                    <div class="text-4xl mb-3">📁</div>
                    <p class="text-sm text-slate-600 font-medium">کلیک کنید یا فایل را بکشید</p>
                    <p class="text-xs text-slate-400 mt-1">JPG, PNG, GIF, WebP — حداکثر ۱۰ مگابایت</p>
                    <input type="file" x-ref="mediaFileInput" data-testid="tb-media-file-input" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden" @change="uploadMediaFile($event.target.files[0])">
                </div>
                <div x-show="mediaModal.uploading" class="mt-4 text-sm text-indigo-600">در حال آپلود...</div>
            </div>
            <div class="flex gap-2 px-4 py-3 border-t border-slate-100 shrink-0">
                <button x-show="mediaModal.selected" @click="confirmMediaSelect()" data-testid="tb-media-confirm" class="flex-1 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">انتخاب تصویر</button>
                <button type="button" @click="closeMediaPicker()" class="flex-1 py-2 rounded-lg border border-slate-200 text-slate-600 text-sm font-medium hover:bg-slate-50 transition">بستن</button>
            </div>
        </div>
    </div>

    {{-- Motion Picker Modal — lists ONLY this site's own permitted Motion
         assets (from ROUTES.motionAssets, backed by the existing runtime
         manifest). Never browses the platform Master Library. --}}
    <div x-show="motionModal.open" x-transition.opacity class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
        <div @click.outside="motionModal.open = false" class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl flex flex-col" style="max-height:80vh;">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 shrink-0">
                <h3 class="font-bold text-slate-800 text-sm">انتخاب موشن گرافیک</h3>
                <button @click="motionModal.open = false" class="text-slate-400 hover:text-slate-700 text-xl leading-none">×</button>
            </div>
            <div class="flex-1 overflow-y-auto p-4">
                <div x-show="motionModal.loading" class="text-center py-10 text-slate-400 text-sm">در حال بارگذاری...</div>
                <div x-show="!motionModal.loading && motionModal.assets.length === 0" class="text-center py-10 text-slate-400 text-sm">
                    هنوز هیچ موشنی برای این سایت فعال نشده است.
                </div>
                <div x-show="!motionModal.loading" class="grid grid-cols-2 gap-3">
                    <template x-for="asset in motionModal.assets" :key="asset.asset_id">
                        <div class="relative group rounded-xl overflow-hidden border-2 cursor-pointer transition p-3 flex items-center gap-3"
                             :class="motionModal.selected === asset.asset_id ? 'border-indigo-500 bg-indigo-50/50' : 'border-slate-200 hover:border-indigo-300'"
                             @click="motionModal.selected = (motionModal.selected === asset.asset_id ? null : asset.asset_id)">
                            <template x-if="asset.thumbnail">
                                <img :src="asset.thumbnail" class="w-12 h-12 rounded-lg object-cover bg-white border border-slate-200 shrink-0">
                            </template>
                            <template x-if="!asset.thumbnail">
                                <span class="w-12 h-12 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-400 text-lg shrink-0">🎬</span>
                            </template>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-800 truncate" x-text="asset.name || asset.asset_id"></p>
                                <p class="text-xs text-slate-400 truncate" x-text="(asset.category ? asset.category + ' · ' : '') + (asset.type || '')"></p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            <div class="flex gap-2 px-4 py-3 border-t border-slate-100 shrink-0">
                <button x-show="motionModal.selected" @click="confirmMotionSelect()" class="flex-1 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">انتخاب این موشن</button>
                <button @click="motionModal.open = false" class="flex-1 py-2 rounded-lg border border-slate-200 text-slate-600 text-sm font-medium hover:bg-slate-50 transition">بستن</button>
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
    motionAssets: '{{ route('admin.theme-design.canvas-motion-assets') }}',
    // TASK 3/3 Phase 6.5C (BLD-28) — reuses the SAME bounded, already-proven
    // search endpoint Menu's own "add existing Page" picker uses
    // (MenuController::searchPages()) — no second Page-search authority.
    linkPageSearch: '{{ route('admin.menus.pages.search') }}',
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
        // TB5: responsive editor
        activeBreakpoint: 'desktop',   // 'desktop' | 'tablet' | 'mobile'
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
        // TB3-07: media modal. `selected`/each image's identity is the
        // canonical CustomerMedia uuid, not a raw URL — MEDIA-4.
        mediaModal: { open: false, tab: 'library', images: [], loading: false, uploading: false, selected: null, propKey: null, target: null, returnFocus: null, q: '', category: '' },
        // TASK 3/3 Phase 6.5C (BLD-28) — internal-link picker state.
        linkPickerOpenFor: null,
        linkPickerQuery: '',
        linkPickerResults: [],
        // MEDIA-4: uuid -> resolved URL, populated as the library loads/
        // uploads, so a persisted {media_id} value can render a live
        // thumbnail in the property panel without a DB round trip per
        // keystroke. Canvas/public rendering resolves independently and
        // authoritatively server-side (CustomerMediaResolver) regardless.
        mediaUrlCache: {},
        motionModal: { open: false, assets: [], loading: false, selected: null, propKey: null, loaded: false },
        // TB6: template library
        showTemplateLibrary: false,
        showSaveAsTemplate: false,
        templateLibrary: { loading: false, templates: [], type: '', category: '', search: '' },
        saveTemplateForm: { name: '', type: 'section', category: 'hero', scope: 'block', saving: false },

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
        // TB5: reactive style object for the active breakpoint
        get currentBpStyle() {
            if (!this.selectedBlock) return {};
            if (this.activeBreakpoint === 'desktop') return this.selectedBlock.style ?? {};
            if (!this.selectedBlock.responsive) return {};
            return this.selectedBlock.responsive[this.activeBreakpoint] ?? {};
        },
        // TB5: get a value with cascade fallback (mobile → tablet → desktop)
        getCascadedValue(key, def = '') {
            if (!this.selectedBlock) return def;
            if (this.activeBreakpoint === 'mobile') {
                const m = this.selectedBlock.responsive?.mobile?.[key];
                if (m !== undefined && m !== '') return m;
                const t = this.selectedBlock.responsive?.tablet?.[key];
                if (t !== undefined && t !== '') return t;
            }
            if (this.activeBreakpoint === 'tablet') {
                const t = this.selectedBlock.responsive?.tablet?.[key];
                if (t !== undefined && t !== '') return t;
            }
            return this.selectedBlock.style?.[key] ?? def;
        },
        // TB5: true when a breakpoint has an override for this key
        hasResponsiveOverride(key, bp) {
            if (!this.selectedBlock?.responsive) return false;
            const v = this.selectedBlock.responsive[bp]?.[key];
            return v !== undefined && v !== '';
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
            // Phase 3D-B: customer-created blocks get a distinct 'cust_'
            // namespace (not 'blk_') so a stable-identity cross-release
            // reconciler (Phase 3D-C/D) can always tell "this block has no
            // master counterpart" without needing to search the master
            // release for it — and so a customer-created id can never be
            // mistaken for a master-origin one even in the (already
            // astronomically unlikely) case of a coincidental value match.
            return {
                id: 'cust_' + Date.now() + '_' + Math.random().toString(36).slice(2, 6),
                type,
                props: JSON.parse(JSON.stringify(BLOCK_SCHEMAS[type]?.defaultProps ?? {})),
                ...(this.isContainerType(type) ? { children: [] } : {})
            };
        },
        isContainerType(type) {
            // Phase 3F-B: declarative metadata from the server (see
            // BLOCK_SCHEMAS construction in this file's @php block), not a
            // hardcoded array — the authoritative source is
            // App\Services\ThemeCanvas\BlockRegistry::isContainerType().
            return !!(BLOCK_SCHEMAS[type]?.is_container);
        },
        structuredItems(propKey, schemaDef) {
            const raw = this.selectedBlock?.props?.[propKey];
            if (Array.isArray(raw)) return raw;
            if (typeof raw !== 'string' || raw.trim() === '') return [];
            try {
                const parsed = JSON.parse(raw);
                return Array.isArray(parsed) ? parsed : [];
            } catch (_) {
                return [];
            }
        },
        persistStructuredItems(propKey, schemaDef, items) {
            if (!this.selectedBlock) return;
            this.selectedBlock.props[propKey] = schemaDef?.storage === 'json'
                ? JSON.stringify(items)
                : items;
            this.schedulePropUpdate();
        },
        addStructuredItem(propKey, schemaDef) {
            const item = {};
            for (const [fieldKey, fieldDef] of Object.entries(schemaDef?.fields ?? {})) {
                item[fieldKey] = fieldDef?.default ?? '';
            }
            this.persistStructuredItems(propKey, schemaDef, [...this.structuredItems(propKey, schemaDef), item]);
        },
        updateStructuredField(propKey, itemIndex, fieldKey, value, schemaDef) {
            const items = this.structuredItems(propKey, schemaDef).map(item => ({ ...item }));
            if (!items[itemIndex]) return;
            // Copying the complete existing object before changing one field
            // deliberately preserves unknown/unmanaged nested keys.
            items[itemIndex][fieldKey] = value;
            this.persistStructuredItems(propKey, schemaDef, items);
        },
        removeStructuredItem(propKey, itemIndex, schemaDef) {
            const items = [...this.structuredItems(propKey, schemaDef)];
            items.splice(itemIndex, 1);
            this.persistStructuredItems(propKey, schemaDef, items);
        },
        moveStructuredItem(propKey, itemIndex, direction, schemaDef) {
            const items = [...this.structuredItems(propKey, schemaDef)];
            const target = itemIndex + direction;
            if (target < 0 || target >= items.length) return;
            [items[itemIndex], items[target]] = [items[target], items[itemIndex]];
            this.persistStructuredItems(propKey, schemaDef, items);
        },
        // BLD-11..13: one explicit, bounded extra repeater level for the one
        // genuinely nested field (nav_footer.columns[].links) — deliberately
        // not a generic recursive mechanism; these operate on
        // structuredItems()[itemIndex][fieldKey] directly.
        nestedItems(propKey, itemIndex, fieldKey) {
            const items = this.structuredItems(propKey);
            const raw = items?.[itemIndex]?.[fieldKey];
            return Array.isArray(raw) ? raw : [];
        },
        persistNestedItems(propKey, itemIndex, fieldKey, nestedItems, schemaDef) {
            const items = this.structuredItems(propKey).map(it => ({ ...it }));
            if (!items[itemIndex]) return;
            items[itemIndex] = { ...items[itemIndex], [fieldKey]: nestedItems };
            this.persistStructuredItems(propKey, schemaDef, items);
        },
        addNestedItem(propKey, itemIndex, fieldKey, nestedSchemaDef, schemaDef) {
            const item = {};
            for (const [k, def] of Object.entries(nestedSchemaDef?.fields ?? {})) {
                item[k] = def?.default ?? '';
            }
            this.persistNestedItems(propKey, itemIndex, fieldKey, [...this.nestedItems(propKey, itemIndex, fieldKey), item], schemaDef);
        },
        updateNestedField(propKey, itemIndex, fieldKey, nestedIndex, nestedFieldKey, value, schemaDef) {
            const nested = this.nestedItems(propKey, itemIndex, fieldKey).map(it => ({ ...it }));
            if (!nested[nestedIndex]) return;
            nested[nestedIndex][nestedFieldKey] = value;
            this.persistNestedItems(propKey, itemIndex, fieldKey, nested, schemaDef);
        },
        removeNestedItem(propKey, itemIndex, fieldKey, nestedIndex, schemaDef) {
            const nested = [...this.nestedItems(propKey, itemIndex, fieldKey)];
            nested.splice(nestedIndex, 1);
            this.persistNestedItems(propKey, itemIndex, fieldKey, nested, schemaDef);
        },
        moveNestedItem(propKey, itemIndex, fieldKey, nestedIndex, direction, schemaDef) {
            const nested = [...this.nestedItems(propKey, itemIndex, fieldKey)];
            const target = nestedIndex + direction;
            if (target < 0 || target >= nested.length) return;
            [nested[nestedIndex], nested[target]] = [nested[target], nested[nestedIndex]];
            this.persistNestedItems(propKey, itemIndex, fieldKey, nested, schemaDef);
        },
        // Commercial Productization fase-2: one further explicit, bounded
        // repeater level (Mega Menu's columns -> items), added the same
        // way nestedItems() itself was added on top of structuredItems() —
        // still not a generic recursive mechanism.
        deepNestedItems(propKey, itemIndex, fieldKey, nestedIndex, deepFieldKey) {
            const nested = this.nestedItems(propKey, itemIndex, fieldKey);
            const raw = nested?.[nestedIndex]?.[deepFieldKey];
            return Array.isArray(raw) ? raw : [];
        },
        persistDeepNestedItems(propKey, itemIndex, fieldKey, nestedIndex, deepFieldKey, deepItems, schemaDef) {
            const nested = this.nestedItems(propKey, itemIndex, fieldKey).map(it => ({ ...it }));
            if (!nested[nestedIndex]) return;
            nested[nestedIndex] = { ...nested[nestedIndex], [deepFieldKey]: deepItems };
            this.persistNestedItems(propKey, itemIndex, fieldKey, nested, schemaDef);
        },
        addDeepNestedItem(propKey, itemIndex, fieldKey, nestedIndex, deepFieldKey, deepSchemaDef, schemaDef) {
            const item = {};
            for (const [k, def] of Object.entries(deepSchemaDef?.fields ?? {})) {
                item[k] = def?.default ?? '';
            }
            this.persistDeepNestedItems(propKey, itemIndex, fieldKey, nestedIndex, deepFieldKey, [...this.deepNestedItems(propKey, itemIndex, fieldKey, nestedIndex, deepFieldKey), item], schemaDef);
        },
        updateDeepNestedField(propKey, itemIndex, fieldKey, nestedIndex, deepFieldKey, deepIndex, deepNestedFieldKey, value, schemaDef) {
            const deep = this.deepNestedItems(propKey, itemIndex, fieldKey, nestedIndex, deepFieldKey).map(it => ({ ...it }));
            if (!deep[deepIndex]) return;
            deep[deepIndex][deepNestedFieldKey] = value;
            this.persistDeepNestedItems(propKey, itemIndex, fieldKey, nestedIndex, deepFieldKey, deep, schemaDef);
        },
        removeDeepNestedItem(propKey, itemIndex, fieldKey, nestedIndex, deepFieldKey, deepIndex, schemaDef) {
            const deep = [...this.deepNestedItems(propKey, itemIndex, fieldKey, nestedIndex, deepFieldKey)];
            deep.splice(deepIndex, 1);
            this.persistDeepNestedItems(propKey, itemIndex, fieldKey, nestedIndex, deepFieldKey, deep, schemaDef);
        },
        moveDeepNestedItem(propKey, itemIndex, fieldKey, nestedIndex, deepFieldKey, deepIndex, direction, schemaDef) {
            const deep = [...this.deepNestedItems(propKey, itemIndex, fieldKey, nestedIndex, deepFieldKey)];
            const target = deepIndex + direction;
            if (target < 0 || target >= deep.length) return;
            [deep[deepIndex], deep[target]] = [deep[target], deep[deepIndex]];
            this.persistDeepNestedItems(propKey, itemIndex, fieldKey, nestedIndex, deepFieldKey, deep, schemaDef);
        },
        isMediaControl(schemaDef, propKey, blockType) {
            // MEDIA-4: every real media-capable prop's canonical schema
            // (BlockRegistry) now declares type:'image' — background_image,
            // image.url, hero_split.image_url, module:motion.fallback_image,
            // and the gallery/team_cards repeater fields all reach this
            // exact same adapter through one honest marker, no per-prop
            // name allowlist needed. nav_header.logo_url is deliberately
            // NOT type:'image': it is the logo's link target, not an image.
            return schemaDef?.type === 'image';
        },
        isColorControl(schemaDef, propKey, blockType) {
            if (schemaDef?.type === 'color') return true;
            // These older shared schemas predate the `color` control marker,
            // but their renderers consume a real CSS color value directly.
            // Named-style fields (hero/section background) and enumerated
            // brand-style selects remain their existing non-color controls.
            return [
                'nav_header:bg_color',
                'nav_header:text_color',
                'nav_footer:bg_color',
                'nav_footer:text_color',
                'contact_info:background',
            ].includes(`${blockType}:${propKey}`);
        },
        themePalette() {
            return [
                { token: 'primary', label: 'رنگ اصلی قالب', value: this.globalStyles.primary_color || '#4f46e5' },
                { token: 'secondary', label: 'رنگ ثانویه قالب', value: this.globalStyles.secondary_color || '#6366f1' },
                { token: 'background', label: 'پس‌زمینه قالب', value: this.globalStyles.background_color || '#f9fafb' },
                { token: 'text', label: 'متن قالب', value: this.globalStyles.text_color || '#111827' },
            ];
        },
        selectThemeColor(propKey, color) {
            if (!this.selectedBlock) return;
            // Persist a normal CSS variable reference: ThemeTokenNormalizer
            // remains the authority that emits each --tb-* token at runtime.
            this.selectedBlock.props[propKey] = `var(--tb-${color.token})`;
            this.schedulePropUpdate();
        },
        clearColor(propKey, defaultValue = '') {
            if (!this.selectedBlock) return;
            this.selectedBlock.props[propKey] = defaultValue;
            this.schedulePropUpdate();
        },
        colorInputValue(value) {
            return /^#[0-9a-f]{6}$/i.test(value || '') ? value : '#000000';
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

        // TASK 2A (tabs container authoring fix): a bounded, tabs-specific
        // shortcut over the exact same canonical mechanism addChildBlock()
        // above already uses (makeBlock('tab_panel') + push into
        // selectedBlock.children) — no second content tree, no new prop
        // shape. It exists only so adding a tab doesn't require opening the
        // full cross-category block palette and finding "Tab Panel" in it;
        // a customer just needs an obvious "Add Tab" action. Each new tab
        // gets a distinct default title (تب ۱, تب ۲, ...) instead of the
        // generic schema default every tab_panel added via the normal
        // palette would otherwise share. Deliberately does NOT move
        // selection onto the new tab_panel the way addChildBlock()/
        // addBlock() do for every other container's child — staying on the
        // `tabs` container itself is what lets "افزودن تب" be clicked
        // repeatedly to add Tab A, Tab B, Tab C in one flow, instead of
        // forcing a reselect of the tabs container between each add. A
        // specific tab is selected for editing separately, via its own
        // row's "ویرایش" button.
        addTab() {
            if (!this.selectedPath || this.selectedBlock?.type !== 'tabs') return;
            this.pushHistory();
            const block = this.makeBlock('tab_panel');
            block.props.title = 'تب ' + ((this.selectedBlock.children?.length ?? 0) + 1);
            if (!this.selectedBlock.children) this.selectedBlock.children = [];
            this.selectedBlock.children.push(block);
            this.isDirty = true;
            this.autoSaveAndReload();
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
            // Phase 3D-B: a duplicate (of a master block or a customer
            // block) is itself customer-created content the moment it's
            // cloned — 'cust_' namespace, same as makeBlock() above.
            block.id = 'cust_' + Date.now() + '_' + Math.random().toString(36).slice(2, 6);
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

        // ── TB3-S2: Per-block style overrides (TB5: breakpoint-aware) ─
        updateStyle(styleKey, value) {
            if (!this.selectedBlock) return;
            const bp = this.activeBreakpoint;
            if (bp === 'desktop') {
                if (!this.selectedBlock.style) this.selectedBlock.style = {};
                if (value === '' || value === null) {
                    delete this.selectedBlock.style[styleKey];
                    if (Object.keys(this.selectedBlock.style).length === 0) delete this.selectedBlock.style;
                } else {
                    this.selectedBlock.style[styleKey] = value;
                }
            } else {
                if (!this.selectedBlock.responsive) this.selectedBlock.responsive = {};
                if (!this.selectedBlock.responsive[bp]) this.selectedBlock.responsive[bp] = {};
                if (value === '' || value === null) {
                    delete this.selectedBlock.responsive[bp][styleKey];
                    if (Object.keys(this.selectedBlock.responsive[bp]).length === 0) delete this.selectedBlock.responsive[bp];
                    if (Object.keys(this.selectedBlock.responsive).length === 0) delete this.selectedBlock.responsive;
                } else {
                    this.selectedBlock.responsive[bp][styleKey] = value;
                }
            }
            this.isDirty = true;
            this.schedulePropUpdate();
        },

        resetBlockStyle() {
            if (!this.selectedBlock) return;
            const bp = this.activeBreakpoint;
            if (bp === 'desktop') {
                delete this.selectedBlock.style;
            } else {
                if (this.selectedBlock.responsive?.[bp]) {
                    delete this.selectedBlock.responsive[bp];
                    if (Object.keys(this.selectedBlock.responsive).length === 0) delete this.selectedBlock.responsive;
                }
            }
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
                // Phase 3C: the live-preview iframe is always same-origin
                // (served from this app's own routes) — targeting window.
                // location.origin instead of '*' means the message is never
                // delivered if the iframe has somehow navigated away to a
                // different origin.
                try { iframe.contentWindow.postMessage(msg, window.location.origin); } catch (_) {}
            }
        },

        onIframeLoad() { setTimeout(() => { this.iframeLoading = false; }, 150); },

        handleIframeMessage(e) {
            // Phase 3C: only ever trust messages from this app's own origin
            // — the live-preview iframe is same-origin by design, so a
            // message from anywhere else is not the preview iframe.
            if (e.origin !== window.location.origin) return;
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

        setDevice(d) { this.device = d; this.activeBreakpoint = d; },

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

        // ── Theme Builder Motion Picker (Phase 3) ───────────────────
        // Lists ONLY this site's own permitted Motion assets — the fetch
        // hits ROUTES.motionAssets, which is backed by the exact same
        // MotionRuntimeManifestReader the public motion runtime component
        // already uses. There is no code path here that can reach the
        // platform Master Library or another site's assets.
        motionAssetById(assetId) {
            return this.motionModal.assets.find(a => a.asset_id === assetId) || null;
        },

        openMotionPicker(propKey) {
            this.motionModal.propKey  = propKey;
            this.motionModal.selected = this.selectedBlock?.props?.[propKey] || null;
            this.motionModal.open     = true;
            if (!this.motionModal.loaded) this.fetchMotionAssets();
        },

        async fetchMotionAssets() {
            this.motionModal.loading = true;
            try {
                const r = await fetch(ROUTES.motionAssets, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN } });
                const d = await r.json();
                this.motionModal.assets = d.assets ?? [];
                this.motionModal.loaded = true;
            } catch (_) { this.motionModal.assets = []; }
            finally { this.motionModal.loading = false; }
        },

        confirmMotionSelect() {
            if (!this.motionModal.propKey) return;
            if (this.selectedBlock) {
                this.selectedBlock.props[this.motionModal.propKey] = this.motionModal.selected || '';
                this.schedulePropUpdate();
            }
            this.motionModal.open = false;
        },

        clearMotionSelect(propKey) {
            if (this.selectedBlock) {
                this.selectedBlock.props[propKey] = '';
                this.schedulePropUpdate();
            }
        },

        /**
         * MEDIA-4: the one client-side resolver for display. A string is a
         * legacy URL/path, returned as-is. A canonical {media_id} value is
         * looked up in mediaUrlCache (populated by fetchMedia()/upload —
         * both real CustomerMedia reads, never guessed). Anything else,
         * including a media_id not yet cached, safely renders as "no image"
         * rather than throwing — the canonical resolved URL always comes
         * from the server (CustomerMediaResolver) at actual render time.
         */
        mediaSrc(value) {
            if (typeof value === 'string') return value;
            if (value && typeof value === 'object' && typeof value.media_id === 'string') {
                const cached = this.mediaUrlCache[value.media_id];
                // A block loaded fresh from the server (a reload, or first
                // selecting it) may reference a media_id the picker was
                // never opened for this session, so nothing populated the
                // cache yet. Resolve it lazily, once, in the background —
                // still through the real site-scoped lookup, not guessed.
                if (cached === undefined) this.ensureMediaCached(value.media_id);
                return cached ?? '';
            }
            return '';
        },

        /** Populates mediaUrlCache for one uuid not yet seen this session. */
        ensureMediaCached(uuid) {
            this._mediaCacheInFlight ??= new Set();
            if (this._mediaCacheInFlight.has(uuid)) return;
            this._mediaCacheInFlight.add(uuid);
            fetch(`${ROUTES.mediaIndex}?uuid=${encodeURIComponent(uuid)}`, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN } })
                .then((r) => r.json())
                .then((d) => {
                    const item = (d.images ?? [])[0];
                    if (item) {
                        // Reassigning the whole object (not a bracket set on
                        // the existing one) guarantees Alpine's reactivity
                        // picks up a brand-new key, not just an update to an
                        // already-tracked one.
                        this.mediaUrlCache = { ...this.mediaUrlCache, [item.uuid]: item.url };
                    }
                })
                .catch(() => {})
                .finally(() => this._mediaCacheInFlight.delete(uuid));
        },

        // ── TASK 3/3 Phase 6.5C (BLD-28): internal-link picker ──────────
        isInternalLinkValue(value) {
            return !!value && typeof value === 'object' && value.type === 'internal_page';
        },

        setLinkCustomUrl(propKey, url) {
            this.selectedBlock.props[propKey] = url;
            this.linkPickerOpenFor = null;
            this.schedulePropUpdate();
        },

        openLinkPagePicker(propKey) {
            this.linkPickerOpenFor = propKey;
            this.linkPickerQuery = '';
            this.linkPickerResults = [];
        },

        async searchLinkPages() {
            try {
                const params = new URLSearchParams();
                if (this.linkPickerQuery) params.set('q', this.linkPickerQuery);
                const r = await fetch(ROUTES.linkPageSearch + '?' + params.toString(), {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                });
                const d = await r.json();
                this.linkPickerResults = d.pages ?? [];
            } catch (e) {
                this.linkPickerResults = [];
            }
        },

        selectLinkPage(propKey, page) {
            this.selectedBlock.props[propKey] = { type: 'internal_page', page_id: page.id, page_title: page.title };
            this.linkPickerOpenFor = null;
            this.schedulePropUpdate();
        },

        openMediaPicker(propKey, target = null) {
            this.mediaModal.propKey  = propKey;
            this.mediaModal.target   = target;
            this.mediaModal.returnFocus = document.activeElement;
            const schemaDef = this.selectedSchema?.[propKey] ?? {};
            let current = this.selectedBlock?.props?.[propKey];
            if (target && target.deepFieldKey !== undefined) {
                current = this.deepNestedItems(propKey, target.index, target.fieldKey, target.nestedIndex, target.nestedFieldKey)?.[target.deepIndex]?.[target.deepFieldKey];
            } else if (target && target.nestedFieldKey !== undefined) {
                current = this.nestedItems(propKey, target.index, target.fieldKey)?.[target.nestedIndex]?.[target.nestedFieldKey];
            } else if (target) {
                current = this.structuredItems(propKey, schemaDef)?.[target.index]?.[target.fieldKey];
            }
            // Only a canonical {media_id} value can be pre-highlighted in
            // the library grid — a legacy raw URL has no library identity.
            this.mediaModal.selected = (current && typeof current === 'object' && typeof current.media_id === 'string')
                ? current.media_id
                : null;
            this.mediaModal.tab      = 'library';
            this.mediaModal.q        = '';
            this.mediaModal.category = '';
            this.mediaModal.open     = true;
            this.fetchMedia();
        },

        closeMediaPicker() {
            const returnFocus = this.mediaModal.returnFocus;
            this.mediaModal.open = false;
            this.mediaModal.target = null;
            this.mediaModal.returnFocus = null;
            this.$nextTick(() => returnFocus?.focus?.());
        },

        async fetchMedia() {
            this.mediaModal.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.mediaModal.q) params.set('q', this.mediaModal.q);
                if (this.mediaModal.category) params.set('category', this.mediaModal.category);
                const qs = params.toString();
                const r = await fetch(ROUTES.mediaIndex + (qs ? `?${qs}` : ''), { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN } });
                const d = await r.json();
                this.mediaModal.images = d.images ?? [];
                for (const img of this.mediaModal.images) {
                    if (img.uuid) this.mediaUrlCache[img.uuid] = img.url;
                }
            } catch (_) { this.mediaModal.images = []; }
            finally { this.mediaModal.loading = false; }
        },

        selectMedia(uuid) {
            this.mediaModal.selected = (this.mediaModal.selected === uuid) ? null : uuid;
        },

        confirmMediaSelect() {
            if (!this.mediaModal.selected || !this.mediaModal.propKey) return;
            const value = { media_id: this.mediaModal.selected };
            if (this.selectedBlock) {
                const t = this.mediaModal.target;
                const schemaDef = this.selectedSchema?.[this.mediaModal.propKey] ?? {};
                if (t && t.deepFieldKey !== undefined) {
                    this.updateDeepNestedField(this.mediaModal.propKey, t.index, t.fieldKey, t.nestedIndex, t.nestedFieldKey, t.deepIndex, t.deepFieldKey, value, schemaDef);
                } else if (t && t.nestedFieldKey !== undefined) {
                    this.updateNestedField(this.mediaModal.propKey, t.index, t.fieldKey, t.nestedIndex, t.nestedFieldKey, value, schemaDef);
                } else if (t) {
                    this.updateStructuredField(this.mediaModal.propKey, t.index, t.fieldKey, value, schemaDef);
                } else {
                    this.selectedBlock.props[this.mediaModal.propKey] = value;
                    this.schedulePropUpdate();
                }
            }
            this.closeMediaPicker();
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
                if (d.uuid) {
                    this.mediaUrlCache[d.uuid] = d.url;
                    this.mediaModal.images.unshift({ uuid: d.uuid, url: d.url, name: d.name, title: null, category: 'image' });
                    this.mediaModal.selected = d.uuid;
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

        // TB6: Template Library
        async openTemplateLibrary() {
            this.showTemplateLibrary = true;
            await this.fetchTemplates();
        },
        async fetchTemplates() {
            this.templateLibrary.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.templateLibrary.type)     params.set('type',     this.templateLibrary.type);
                if (this.templateLibrary.category) params.set('category', this.templateLibrary.category);
                if (this.templateLibrary.search)   params.set('search',   this.templateLibrary.search);

                const res = await fetch(`{{ route('admin.theme-design.canvas.templates') }}?${params}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const json = await res.json();
                this.templateLibrary.templates = json.templates ?? [];
            } catch (e) {
                this.templateLibrary.templates = [];
            } finally {
                this.templateLibrary.loading = false;
            }
        },
        async insertTemplate(id) {
            try {
                const res = await fetch('{{ route('admin.theme-design.canvas.insert-template') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content, 'Accept': 'application/json' },
                    body: JSON.stringify({ template_id: id }),
                });
                const json = await res.json();
                if (json.success && json.blocks) {
                    this.blocks = [...this.blocks, ...json.blocks];
                    this.pushHistory();
                    this.isDirty = true;
                    this.showTemplateLibrary = false;
                    this.showToast('قالب درج شد', 'success');
                }
            } catch (e) {
                this.showToast('خطا در درج قالب', 'error');
            }
        },
        async saveAsTemplate() {
            if (!this.saveTemplateForm.name) return;
            this.saveTemplateForm.saving = true;
            try {
                let blocks = [];
                if (this.saveTemplateForm.scope === 'block' && this.selectedBlock) {
                    blocks = [this.selectedBlock];
                } else {
                    blocks = this.blocks;
                }

                const res = await fetch('{{ route('admin.theme-design.canvas.save-as-template') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content, 'Accept': 'application/json' },
                    body: JSON.stringify({
                        name:     this.saveTemplateForm.name,
                        type:     this.saveTemplateForm.type,
                        category: this.saveTemplateForm.category,
                        scope:    this.saveTemplateForm.scope,
                        blocks:   blocks,
                    }),
                });
                const json = await res.json();
                if (json.success) {
                    this.showSaveAsTemplate = false;
                    this.saveTemplateForm.name = '';
                    this.showToast('قالب «' + json.template.name + '» ذخیره شد', 'success');
                }
            } catch (e) {
                this.showToast('خطا در ذخیره قالب', 'error');
            } finally {
                this.saveTemplateForm.saving = false;
            }
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

            // GAP-M01 — Autosave every 60 seconds when there are unsaved changes
            setInterval(() => {
                if (this.isDirty && !this.quickSaving && !this.saving) {
                    this.autoSave();
                }
            }, 60000);
        }
    };
}
</script>
@endpush
