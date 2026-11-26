@extends('layouts.canvas-editor')

@section('title', 'ویرایشگر قالب Canvas')

@push('head')
<script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
@endpush

@section('content')
@php
$blockLabels  = collect($registry)->mapWithKeys(fn($d,$t)=>[$t=>$d['name']]);
// Phase 3F-B: is_container is declarative capability metadata from the
// canonical/shared authority (\App\Services\ThemeCanvas\BlockRegistry::
// isContainerType()), not a client-side hardcoded array — see that
// method's docblock for the layered shared+host design.
$blockSchemas = collect($registry)->mapWithKeys(function ($d, $t) {
    $definition = \SaaSPlatform\ThemeRuntime\Inspector\InspectorFieldRegistry::normalizeDefinition($d);
    return [$t => [
        'schema' => $definition['schema'],
        'defaultProps' => $definition['defaultProps'] ?? [],
        'editor' => $definition['editor'],
        'dataBinding' => $definition['dataBinding'] ?? null,
        'modulePresentation' => $definition['modulePresentation'] ?? null,
        'liveStructureUpdate' => $definition['liveStructureUpdate'] ?? null,
        'is_container' => \App\Services\ThemeCanvas\BlockRegistry::isContainerType($t),
    ]];
});
$orderedCats  = ['layout','content','dynamic','ecommerce','marketing','utility','module'];
$catColors    = ['layout'=>'text-blue-600 bg-blue-50','content'=>'text-green-700 bg-green-50','dynamic'=>'text-purple-600 bg-purple-50','ecommerce'=>'text-emerald-700 bg-emerald-50','marketing'=>'text-orange-600 bg-orange-50','utility'=>'text-slate-600 bg-slate-100','module'=>'text-indigo-700 bg-indigo-50'];
$blockEmoji   = ['scroll_to_top'=>'🔝','hero'=>'🌟','section'=>'📦','grid'=>'🔲','columns'=>'⬜','column'=>'▤','accordion'=>'🪗','slider'=>'🎠','heading'=>'🔠','text'=>'📄','image'=>'🖼️','gallery'=>'🗂️','video'=>'🎬','button'=>'🔘','divider'=>'➖','counter'=>'🔢','blog_posts'=>'📰','testimonials'=>'⭐','product_grid'=>'🛍️','product_slider'=>'🎠','cta'=>'📢','countdown'=>'⏱️','promo_banner'=>'📯','search_bar'=>'🔍','nav_menu'=>'🗺️','social_icons'=>'📡','login_register'=>'👤','module:blog_posts'=>'📰','module:shop_product_grid'=>'🛍️','module:courses_grid'=>'🎓','module:pricing_table'=>'💰','module:contact_quick'=>'📞','module:faq_dynamic'=>'❓','module:support_button'=>'💬','module:gallery_widget'=>'🖼️','module:reviews_grid'=>'⭐','module:referral_widget'=>'🎁','module:social_links_bar'=>'📡','module:notifications_bar'=>'🔔','module:whatsapp_widget'=>'📲','module:form_embed'=>'📋','module:plan_cta'=>'👑','module:course_enroll_cta'=>'🎓','module:my_learning_link'=>'📚'];
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

        {{-- Sidebar visibility toggles. Ctrl+B: block library, Ctrl+Alt+B:
             properties panel, Ctrl+\ or F: full-width focus mode (both). --}}
        <div class="flex items-center gap-0.5 bg-slate-100 rounded-lg p-0.5 shrink-0">
            <button type="button" @click="toggleSidebar('right')"
                data-testid="tb-toggle-right-sidebar"
                :aria-pressed="showRightSidebar"
                :class="showRightSidebar ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-400 hover:text-slate-700'"
                title="کتابخانه بلوک‌ها (Ctrl+B)"
                class="p-1.5 rounded-md transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <rect x="3" y="4" width="18" height="16" rx="2" stroke-width="1.8"/>
                    <path d="M15 4h4a2 2 0 012 2v12a2 2 0 01-2 2h-4z" fill="currentColor" opacity=".25" stroke="none"/>
                    <path d="M15 4v16" stroke-width="1.8"/>
                </svg>
            </button>
            <button type="button" @click="toggleSidebar('left')"
                data-testid="tb-toggle-left-sidebar"
                :aria-pressed="showLeftSidebar"
                :class="showLeftSidebar ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-400 hover:text-slate-700'"
                title="پنل تنظیمات (Ctrl+Alt+B)"
                class="p-1.5 rounded-md transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <rect x="3" y="4" width="18" height="16" rx="2" stroke-width="1.8"/>
                    <path d="M9 4H5a2 2 0 00-2 2v12a2 2 0 002 2h4z" fill="currentColor" opacity=".25" stroke="none"/>
                    <path d="M9 4v16" stroke-width="1.8"/>
                </svg>
            </button>
            <button type="button" @click="toggleFocusMode()"
                data-testid="tb-toggle-focus-mode"
                :aria-pressed="!showRightSidebar && !showLeftSidebar"
                :class="!showRightSidebar && !showLeftSidebar ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-400 hover:text-slate-700'"
                title="پیش‌نمایش تمام‌عرض (F یا Ctrl+\)"
                class="p-1.5 rounded-md transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 9V5a1 1 0 011-1h4M20 9V5a1 1 0 00-1-1h-4M4 15v4a1 1 0 001 1h4M20 15v4a1 1 0 01-1 1h-4"/>
                </svg>
            </button>
        </div>
        <div class="h-6 w-px bg-slate-200 shrink-0"></div>

        {{-- Page Tabs. ADR-076: the tabs scroll inside their own strip
             (theme-runtime::canvas.tab-strip: chevrons, wheel, active tab kept
             in view), so adding pages never needs a browser zoom-out. --}}
        <div class="tbts-group" data-testid="tb-page-tabs-group">
            {{-- Searchable Page Dropdown Menu --}}
            <div class="relative shrink-0" x-data="{ pageSearchOpen: false, pageSearchFilter: '' }" @click.outside="pageSearchOpen = false">
                <button
                    type="button"
                    @click="pageSearchOpen = !pageSearchOpen; if (pageSearchOpen) $nextTick(() => $refs.pageSearchInput?.focus())"
                    class="flex items-center gap-1.5 h-7 px-2.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-medium shadow-sm transition"
                    title="جستجو و تغییر سریع صفحه، هدر، فوتر، پست یا محصول"
                    data-testid="tb-page-search-dropdown-btn"
                >
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <span class="max-w-[130px] truncate" x-text="pageDisplayName(currentPage, pages[currentPage]?.title)"></span>
                    <svg class="w-3 h-3 text-slate-400 transition-transform" :class="pageSearchOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div
                    x-show="pageSearchOpen"
                    x-cloak
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    class="absolute right-0 top-full mt-1.5 w-64 bg-white rounded-xl shadow-xl border border-slate-200 py-2 z-50 overflow-hidden text-right"
                    data-testid="tb-page-search-dropdown-menu"
                >
                    <div class="px-2.5 pb-2 border-b border-slate-100">
                        <div class="relative">
                            <input
                                x-ref="pageSearchInput"
                                type="text"
                                x-model="pageSearchFilter"
                                placeholder="جستجوی برگه، پست، محصول..."
                                class="w-full text-xs pr-7 pl-2 py-1.5 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500"
                            >
                            <svg class="w-3.5 h-3.5 text-slate-400 absolute right-2 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                    </div>

                    <div class="max-h-64 overflow-y-auto py-1 divide-y divide-slate-50 text-xs">
                        {{-- Headers / Footers Section --}}
                        <div class="py-1">
                            <div class="px-3 py-1 text-[10px] font-semibold text-slate-400 uppercase tracking-wider">ساختار قالب</div>
                            <template x-for="(pg, k) in pages" :key="k">
                                <div x-show="k.startsWith('__') && (!pageSearchFilter || pageDisplayName(k, pg.title).toLowerCase().includes(pageSearchFilter.toLowerCase()))">
                                    <button
                                        type="button"
                                        @click="if (k === '__header__') { selectHeaderNode(); } else if (k === '__footer__') { selectFooterNode(); } else { switchPage(k); } pageSearchOpen = false;"
                                        :class="currentPage === k ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-slate-700 hover:bg-slate-50'"
                                        class="w-full text-right px-3 py-1.5 flex items-center justify-between gap-2 transition"
                                    >
                                        <div class="flex items-center gap-2 truncate">
                                            <span x-text="k === '__header__' ? '🔝' : (k === '__footer__' ? '🔚' : '📄')"></span>
                                            <span class="truncate" x-text="pageDisplayName(k, pg.title)"></span>
                                        </div>
                                        <span class="text-[9px] px-1.5 py-0.5 rounded font-mono" :class="k === '__header__' ? 'bg-teal-100 text-teal-700' : 'bg-purple-100 text-purple-700'" x-text="k === '__header__' ? 'Header' : 'Footer'"></span>
                                    </button>
                                </div>
                            </template>
                        </div>

                        {{-- Regular Pages Section --}}
                        <div class="py-1">
                            <div class="px-3 py-1 text-[10px] font-semibold text-slate-400 uppercase tracking-wider">صفحات سایت</div>
                            <template x-for="(pg, k) in pages" :key="k">
                                <div x-show="!k.startsWith('__') && !k.startsWith('post_') && !k.startsWith('product_') && (!pageSearchFilter || pageDisplayName(k, pg.title).toLowerCase().includes(pageSearchFilter.toLowerCase()) || k.toLowerCase().includes(pageSearchFilter.toLowerCase()))">
                                    <button
                                        type="button"
                                        @click="switchPage(k); pageSearchOpen = false;"
                                        :class="currentPage === k ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-slate-700 hover:bg-slate-50'"
                                        class="w-full text-right px-3 py-1.5 flex items-center justify-between gap-2 transition"
                                    >
                                        <div class="flex items-center gap-2 truncate">
                                            <span x-text="pg.is_home || k === 'home' ? '🏠' : '📄'"></span>
                                            <span class="truncate" x-text="pageDisplayName(k, pg.title)"></span>
                                        </div>
                                        <span class="text-[9px] text-slate-400 font-mono" x-text="k"></span>
                                    </button>
                                </div>
                            </template>
                        </div>

                        {{-- Posts Section --}}
                        <div class="py-1">
                            <div class="px-3 py-1 text-[10px] font-semibold text-slate-400 uppercase tracking-wider">مطالب وبلاگ</div>
                            <template x-for="(pg, k) in pages" :key="k">
                                <div x-show="k.startsWith('post_') && (!pageSearchFilter || pageDisplayName(k, pg.title).toLowerCase().includes(pageSearchFilter.toLowerCase()))">
                                    <button
                                        type="button"
                                        @click="switchPage(k); pageSearchOpen = false;"
                                        :class="currentPage === k ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-slate-700 hover:bg-slate-50'"
                                        class="w-full text-right px-3 py-1.5 flex items-center justify-between gap-2 transition"
                                    >
                                        <div class="flex items-center gap-2 truncate">
                                            <span>📰</span>
                                            <span class="truncate" x-text="pageDisplayName(k, pg.title)"></span>
                                        </div>
                                        <span class="text-[9px] text-slate-400 font-mono" x-text="k"></span>
                                    </button>
                                </div>
                            </template>
                        </div>

                        {{-- Products Section --}}
                        <div class="py-1">
                            <div class="px-3 py-1 text-[10px] font-semibold text-slate-400 uppercase tracking-wider">محصولات فروشگاه</div>
                            <template x-for="(pg, k) in pages" :key="k">
                                <div x-show="k.startsWith('product_') && (!pageSearchFilter || pageDisplayName(k, pg.title).toLowerCase().includes(pageSearchFilter.toLowerCase()))">
                                    <button
                                        type="button"
                                        @click="switchPage(k); pageSearchOpen = false;"
                                        :class="currentPage === k ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-slate-700 hover:bg-slate-50'"
                                        class="w-full text-right px-3 py-1.5 flex items-center justify-between gap-2 transition"
                                    >
                                        <div class="flex items-center gap-2 truncate">
                                            <span>🛍️</span>
                                            <span class="truncate" x-text="pageDisplayName(k, pg.title)"></span>
                                        </div>
                                        <span class="text-[9px] text-slate-400 font-mono" x-text="k"></span>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <div class="h-5 w-px bg-slate-200 shrink-0"></div>

            <div class="tbts" data-tb-tabstrip data-testid="tb-page-tabs">
                @include('theme-runtime::canvas.tab-strip-nav', ['dir' => 'start'])
                <div class="tbts-viewport" data-tbts-viewport role="group" aria-label="صفحات قالب">
                    <template x-for="(page, key) in pages" :key="key">
                        <button
                            @click="switchPage(key)"
                            :data-testid="'tb-canvas-page-tab-' + key"
                            :data-canvas-page-tab="key"
                            :aria-current="currentPage === key ? 'page' : null"
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
                </div>
                @include('theme-runtime::canvas.tab-strip-nav', ['dir' => 'end'])
            </div>
            <button
                @click="showAddPage = true"
                data-testid="tb-add-page-open"
                class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-medium text-slate-500 hover:bg-slate-100 hover:text-indigo-600 transition whitespace-nowrap shrink-0"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                صفحه جدید
            </button>
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-2 shrink-0">
            {{-- WU-1: Inspect Mode (theme-runtime::canvas.inspect) — outlines,
                 widget ids and the copy action bar inside the preview. --}}
            <button type="button" @click="toggleInspect()" data-tb-inspect-toggle
                :aria-pressed="inspectOn ? 'true' : 'false'"
                title="حالت بازرسی (Ctrl+I)"
                class="flex items-center gap-1 h-7 px-2 rounded-lg border text-xs font-medium transition"
                :class="inspectOn ? 'border-indigo-400 bg-indigo-50 text-indigo-700' : 'border-slate-200 text-slate-500 hover:text-indigo-600 hover:border-indigo-300'">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V5a1 1 0 011-1h3m8 0h3a1 1 0 011 1v3m0 8v3a1 1 0 01-1 1h-3m-8 0H5a1 1 0 01-1-1v-3M9.5 9.5l5 5m0-5l-5 5"/></svg>
                بازرسی
            </button>
            {{-- WU-2: Design HUD summary from the preview (TbAudit, Sentinel codes). --}}
            <span x-show="inspectOn && auditSummary" x-cloak data-tb-audit-chip
                :data-tone="auditSummary?.tone"
                role="status" aria-live="polite"
                :title="auditSummary ? `سلامت بوم در عرض ${auditSummary.width}px` : ''"
                class="flex items-center gap-1.5 h-7 px-2 rounded-lg border text-xs font-medium"
                :class="{ 'border-rose-200 bg-rose-50 text-rose-700': auditSummary?.tone === 'error', 'border-amber-200 bg-amber-50 text-amber-800': auditSummary?.tone === 'warn', 'border-emerald-200 bg-emerald-50 text-emerald-700': auditSummary?.tone === 'ok' }">
                <span class="w-2 h-2 rounded-full" aria-hidden="true"
                    :class="{ 'bg-rose-500': auditSummary?.tone === 'error', 'bg-amber-500': auditSummary?.tone === 'warn', 'bg-emerald-500': auditSummary?.tone === 'ok' }"></span>
                <span x-text="!auditSummary ? '' : (auditSummary.tone === 'ok' ? 'سالم' : [auditSummary.counts.blocker ? `${auditSummary.counts.blocker} خطا` : '', auditSummary.counts.warning ? `${auditSummary.counts.warning} هشدار` : ''].filter(Boolean).join(' · '))"></span>
            </span>
            {{-- AI Dev HUD (?ai_dev=1 or Ctrl+Shift+D): chip + floating bar over the selected block. --}}
            @include('admin.theme-design.partials.ai-dev-hud', ['part' => 'toolbar'])

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
            <button type="button" data-testid="tb-open-templates" @click="openTemplateLibrary()"
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
            <a :href="@js(url('/canvas-preview?token=' . $previewToken)) + '&page=' + encodeURIComponent(currentPage)" target="_blank" data-testid="tb-full-preview-link"
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
            @if(($canvasMode ?? 'theme') === 'maintenance')
                <button type="button"
                        @click="resetMaintenanceTemplate()"
                        data-testid="tb-canvas-reset-maintenance-button"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-amber-300 bg-amber-50 text-amber-800 hover:bg-amber-100 text-xs font-medium transition">
                    بازنشانی به قالب حرفه‌ای تعمیرات
                </button>
            @else
            <form method="POST" action="{{ route('admin.theme-design.canvas-discard-draft') }}" onsubmit="return confirm('تغییرات پیش‌نویس رد شود؟')">
                @csrf
                <button type="submit" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 text-xs font-medium transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    رد تغییرات
                </button>
            </form>
            @endif

            {{-- Canvas Assets Marketplace Shortcut --}}
            <a href="{{ route('admin.canvas-marketplace.index') }}" target="_blank"
               title="دریافت و خرید دارایی‌های جدید Canvas"
               class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-indigo-200 bg-indigo-50/60 hover:bg-indigo-100 text-indigo-700 text-xs font-medium transition">
                <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                <span>مارکت‌پلیس دارایی‌ها</span>
            </a>

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
        <div class="border-r border-violet-200 pr-3 mr-1 min-w-[220px]">
            <div class="flex items-center justify-between mb-1">
                <label class="text-[11px] text-slate-500 font-medium">استایل سراسری دکمه‌ها</label>
                <a href="{{ route('admin.canvas-marketplace.index', ['tab' => 'buttons']) }}" target="_blank"
                   class="text-[10px] text-indigo-600 hover:text-indigo-800 transition">
                    مارکت‌پلیس ↗
                </a>
            </div>
            <div class="flex items-center gap-1.5">
                <select
                    :value="(globalStyles.active_button_pack && globalStyles.active_button_preset) ? (globalStyles.active_button_pack + '|' + globalStyles.active_button_preset) : ''"
                    @change="onGlobalButtonPresetChange($event.target.value)"
                    class="w-full text-xs px-2 py-1.5 rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-1 focus:ring-violet-300">
                    <option value="">پیش‌فرض قالب سایت (کلاسیک)</option>
                    @foreach($grantedButtonPacks ?? [] as $bp)
                        <optgroup label="{{ $bp['title'] }}">
                            @foreach($bp['presets'] ?? [] as $pr)
                                <option value="{{ $bp['slug'] }}|{{ $pr['slug'] }}">
                                    {{ $pr['title'] ?? $pr['slug'] }} ({{ $bp['title'] }})
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                <button
                    type="button"
                    x-show="globalStyles.active_button_pack"
                    @click="rollbackGlobalButtonPreset()"
                    class="shrink-0 text-xs px-2 py-1.5 rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 transition"
                    title="بازگشت به استایل کلاسیک قالب">
                    بازنشانی
                </button>
            </div>
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
    <div class="relative flex flex-1 overflow-hidden">

        {{-- Floating edge handles. They live outside the panels so a
             collapsed sidebar always keeps a visible re-open affordance. --}}
        <button type="button" @click="toggleSidebar('right')"
            data-testid="tb-collapse-right-sidebar"
            :aria-expanded="showRightSidebar"
            :aria-label="showRightSidebar ? 'جمع کردن کتابخانه بلوک‌ها' : 'باز کردن کتابخانه بلوک‌ها'"
            :title="(showRightSidebar ? 'جمع کردن' : 'باز کردن') + ' کتابخانه بلوک‌ها (Ctrl+B)'"
            class="absolute top-1/2 right-0 -translate-y-1/2 z-40 flex h-12 w-5 items-center justify-center rounded-s-lg border border-slate-200 bg-white text-slate-500 shadow-md transition-colors hover:bg-indigo-50 hover:text-indigo-600">
            <svg class="h-3 w-3 transition-transform duration-200" :class="showRightSidebar ? '' : 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
        <button type="button" @click="toggleSidebar('left')"
            data-testid="tb-collapse-left-sidebar"
            :aria-expanded="showLeftSidebar"
            :aria-label="showLeftSidebar ? 'جمع کردن پنل تنظیمات' : 'باز کردن پنل تنظیمات'"
            :title="(showLeftSidebar ? 'جمع کردن' : 'باز کردن') + ' پنل تنظیمات (Ctrl+Alt+B)'"
            class="absolute top-1/2 left-0 -translate-y-1/2 z-40 flex h-12 w-5 items-center justify-center rounded-e-lg border border-slate-200 bg-white text-slate-500 shadow-md transition-colors hover:bg-indigo-50 hover:text-indigo-600">
            <svg class="h-3 w-3 transition-transform duration-200" :class="showLeftSidebar ? '' : 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
            </svg>
        </button>

        {{-- ── PANEL A: Block Library (RTL = right side) ─────────── --}}
        <div :style="sidebarPanelStyle('right')"
             :aria-hidden="!showRightSidebar"
             data-testid="tb-right-sidebar-panel"
             class="bg-white border-l border-slate-200 flex flex-col overflow-hidden shrink-0 z-20">

            {{-- TB4: Tab toggle: بلوک‌ها / ساختار --}}
            <div class="flex border-b border-slate-100 shrink-0">
                <button @click="leftPanelTab='blocks'"
                    :class="leftPanelTab==='blocks' ? 'border-b-2 border-indigo-500 text-indigo-600 font-semibold' : 'text-slate-500 hover:text-slate-700'"
                    class="flex-1 py-2 text-xs text-center transition">بلوک‌ها</button>
                <button @click="leftPanelTab='tree'" data-testid="tb-canvas-tree-tab"
                    :class="leftPanelTab==='tree' ? 'border-b-2 border-indigo-500 text-indigo-600 font-semibold' : 'text-slate-500 hover:text-slate-700'"
                    class="flex-1 py-2 text-xs text-center transition">ساختار</button>
                <button @click="leftPanelTab='health'" data-testid="tb-canvas-health-tab"
                    :class="leftPanelTab==='health' ? 'border-b-2 border-indigo-500 text-indigo-600 font-semibold' : 'text-slate-500 hover:text-slate-700'"
                    class="flex-1 py-2 text-xs text-center transition">سلامت ویجت</button>
            </div>

            {{-- WU-3: «سلامت ویجت» — the Design HUD as a per-widget list (theme-runtime::canvas.health). --}}
            <div x-show="leftPanelTab==='health'" x-cloak class="flex-1 overflow-y-auto">
                @include('admin.theme-design.partials.ai-dev-hud', ['part' => 'health'])
                @include('theme-runtime::canvas.health', ['active' => "leftPanelTab === 'health'"])
            </div>

{{-- TB4: Structure Tree Tab --}}
            <div x-show="leftPanelTab==='tree'" class="flex-1 overflow-y-auto">
                <div class="py-2">
                    {{-- Persistent Header Structural Node --}}
                    <div
                        @click="selectHeaderNode()"
                        data-testid="tb-tree-node-header"
                        :class="currentPage === '__header__' ? 'bg-teal-50 border-r-2 border-teal-500 text-teal-800 font-semibold' : 'hover:bg-slate-50 text-slate-700'"
                        class="flex items-center gap-2 px-3 py-2 cursor-pointer transition text-xs border-b border-slate-100"
                    >
                        <span class="w-2.5 h-2.5 rounded-full bg-teal-500 shrink-0"></span>
                        <span class="text-sm">🔝</span>
                        <span class="flex-1 truncate font-medium" x-text="pageDisplayName('__header__', pages['__header__']?.title)"></span>
                        <span class="text-[9px] bg-teal-100 text-teal-700 px-1.5 py-0.5 rounded font-mono shrink-0">Header</span>
                    </div>

                    {{-- Section Divider for Page Content --}}
                    <div class="px-3 py-1.5 bg-slate-50/70 border-b border-slate-100 flex items-center justify-between text-[11px] text-slate-400">
                        <span x-text="'محتوای صفحه (' + pageDisplayName(currentPage, pages[currentPage]?.title) + ')'"></span>
                        <span class="font-mono text-[10px]" x-text="flattenTree().length + ' بلوک'"></span>
                    </div>

                    <div x-show="flattenTree().length === 0" class="flex flex-col items-center justify-center py-6 text-center text-slate-400">
                        <svg class="w-8 h-8 mb-2 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                        <p class="text-xs">هنوز بلوکی در محتوای این صفحه نیست</p>
                    </div>
                    <template x-for="node in flattenTree()" :key="node.block.id + node.depth">
                        <div
                            @click="selectBlock(node.path)"
                            @dragstart="startTreeDrag(node.block.id, node.path); $event.dataTransfer && ($event.dataTransfer.effectAllowed = 'move', $event.dataTransfer.setData('text/plain', String(node.block.id)))"
                            @dragover.prevent="onTreeDragOver(node.block.id, node.path, computeTreeDropPosition($event, isContainerType(node.block.type)), $event)"
                            @dragleave="onTreeDragLeave($event)"
                            @drop.prevent="onTreeDrop(node.block.id, node.path)"
                            @dragend="endTreeDrag()"
                            draggable="true"
                            :data-testid="'tb-tree-node-' + node.block.id"
                            :data-block-type="node.block.type"
                            :style="'padding-right:' + (12 + node.depth * 14) + 'px'"
                            :class="[
                                isSelectedPath(node.path) ? 'bg-indigo-50 border-r-2 border-indigo-500' : 'hover:bg-slate-50',
                                treeDragState.dragOverBlockId === node.block.id && treeDragState.dropPosition === 'before' ? 'border-t-2 border-indigo-600 bg-indigo-50/40' : '',
                                treeDragState.dragOverBlockId === node.block.id && treeDragState.dropPosition === 'after' ? 'border-b-2 border-indigo-600 bg-indigo-50/40' : '',
                                treeDragState.dragOverBlockId === node.block.id && treeDragState.dropPosition === 'inside' ? 'bg-indigo-100 ring-2 ring-indigo-500 ring-inset' : '',
                            ]"
                            class="relative flex items-center gap-2 px-3 py-1.5 cursor-pointer transition text-xs">
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
                            {{-- Drag handle --}}
                            <svg class="w-3.5 h-3.5 text-slate-300 hover:text-slate-500 cursor-grab active:cursor-grabbing shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/></svg>
                            <span x-show="treeDragState.dragOverBlockId === node.block.id && treeDragState.dropPosition"
                                  class="text-[9px] font-semibold px-1.5 py-0.5 rounded bg-indigo-600 text-white shrink-0"
                                  x-text="treeDragState.dropPosition === 'inside' ? 'زیرمجموعه داخل این ویجت' : (treeDragState.dropPosition === 'before' ? 'قبل از این ویجت' : 'بعد از این ویجت')"></span>
                        </div>
                    </template>

                    {{-- Persistent Footer Structural Node --}}
                    <div
                        @click="selectFooterNode()"
                        data-testid="tb-tree-node-footer"
                        :class="currentPage === '__footer__' ? 'bg-purple-50 border-r-2 border-purple-500 text-purple-800 font-semibold' : 'hover:bg-slate-50 text-slate-700'"
                        class="flex items-center gap-2 px-3 py-2 cursor-pointer transition text-xs border-t border-slate-100 mt-2"
                    >
                        <span class="w-2.5 h-2.5 rounded-full bg-purple-500 shrink-0"></span>
                        <span class="text-sm">🔚</span>
                        <span class="flex-1 truncate font-medium" x-text="pageDisplayName('__footer__', pages['__footer__']?.title)"></span>
                        <span class="text-[9px] bg-purple-100 text-purple-700 px-1.5 py-0.5 rounded font-mono shrink-0">Footer</span>
                    </div>
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

            @if(!empty($modulePresentations))
            <div x-show="leftPanelTab==='blocks'" class="px-3 py-3 border-b border-indigo-100 bg-indigo-50/60" data-testid="tb-module-capability-palette">
                <p class="text-[11px] font-bold text-indigo-800 mb-2">Add Element / Module</p>
                <div class="grid grid-cols-2 gap-2">
                    @foreach($modulePresentations as $capability => $capabilityDef)
                    <button type="button" @click="openModuleCapability(@js($capability))"
                        data-testid="tb-module-capability-{{ str_replace('.', '-', $capability) }}"
                        class="rounded-lg border border-indigo-200 bg-white px-2 py-2 text-[11px] font-semibold text-indigo-700 hover:border-indigo-400 hover:bg-indigo-50 text-right">
                        {{ $capabilityDef['label'] }}
                    </button>
                    @endforeach
                </div>
            </div>
            @endif

            <div x-show="leftPanelTab==='blocks'" class="flex-1 overflow-y-auto">
                @include('admin.theme-design.partials.block-library')
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

                        <div x-show="expandedCats['{{ $catKey }}'] || !!librarySearch" x-collapse>
                            <div class="px-2 pb-2 grid grid-cols-2 gap-1.5">
                                @foreach($catBlocks as $blockType => $blockDef)
                                    @php $blockAccess = ($assetAccess ?? [])[$blockType] ?? null; @endphp
                                    @if(!empty($blockAccess['locked']))
                                        {{-- ADR-087: a locked widget is advertised (upgrade path), never draggable. --}}
                                        <div role="button" tabindex="0" aria-disabled="true"
                                             data-testid="tb-palette-locked-{{ $blockType }}"
                                             x-show="isBlockVisible('{{ $blockType }}', '{{ addslashes($blockDef['name']) }}')"
                                             @click="showToast(@js($blockAccess['message'] ?? $blockAccess['badge']), 'error')"
                                             @keydown.enter.prevent="showToast(@js($blockAccess['message'] ?? $blockAccess['badge']), 'error')"
                                             class="flex flex-col items-start gap-1 p-2.5 rounded-lg border border-dashed border-slate-300 bg-slate-50 text-right"
                                             style="cursor:not-allowed"
                                             title="{{ $blockAccess['message'] ?? $blockDef['name'] }}">
                                            <div class="text-lg leading-none" style="filter:grayscale(1);opacity:.6">{{ $blockEmoji[$blockType] ?? '🧩' }}</div>
                                            <span class="text-[11px] font-medium text-slate-500 leading-tight line-clamp-2">{{ $blockDef['name'] }}</span>
                                            @include('theme-runtime::canvas.asset-access-badge', ['access' => $blockAccess])
                                        </div>
                                        @continue
                                    @endif
                                    <button
                                        draggable="true"
                                        @click="addBlock('{{ $blockType }}')"
                                        @dragstart="startDragBlock($event, '{{ $blockType }}')"
                                        @dragend="endDragBlock()"
                                        x-show="isBlockVisible('{{ $blockType }}', '{{ addslashes($blockDef['name']) }}', '{{ addslashes($blockDef['description'] ?? '') }}')"
                                        data-testid="tb-palette-add-{{ $blockType }}"
                                        class="flex flex-col items-start gap-1 p-2.5 rounded-lg border border-slate-200 bg-white hover:border-indigo-400 hover:bg-indigo-50 hover:shadow-sm transition text-right cursor-grab active:cursor-grabbing group"
                                        title="{{ $blockDef['description'] ?? $blockDef['name'] }}"
                                    >
                                        <div class="text-lg leading-none">{{ $blockEmoji[$blockType] ?? '🧩' }}</div>
                                        <span class="text-[11px] font-medium text-slate-700 group-hover:text-indigo-700 leading-tight line-clamp-2">{{ $blockDef['name'] }}</span>
                                        @if(!empty($blockDef['description']))
                                            <span class="text-[9px] text-slate-400 leading-tight line-clamp-1">{{ $blockDef['description'] }}</span>
                                        @endif
                                        @if(!empty($blockDef['requires']))
                                            <span class="text-[9px] px-1.5 py-0.5 rounded {{ $badgeClass }} font-medium leading-none mt-0.5">{{ $blockDef['requires'] }}</span>
                                        @endif
                                        @include('theme-runtime::canvas.asset-access-badge', ['access' => $blockAccess])
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

        {{-- Resizer: left edge of the right sidebar (toward the canvas). --}}
        <div @pointerdown.prevent="startSidebarDrag('right', $event)"
             :style="sidebarResizerStyle('right')"
             data-testid="tb-resize-right-sidebar"
             title="برای تغییر عرض بکشید"
             class="group relative w-1.5 shrink-0 cursor-col-resize touch-none bg-transparent hover:bg-indigo-400/40 transition-colors z-30">
            <span class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 h-10 w-1 rounded-full bg-slate-300 group-hover:bg-indigo-500 transition-colors pointer-events-none"></span>
        </div>

        {{-- ── PANEL B: Live Canvas (center) ──────────────────────── --}}
        <div data-testid="tb-canvas-panel" class="flex-1 w-full min-w-0 flex flex-col overflow-hidden bg-slate-200">

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

        {{-- Resizer: right edge of the left sidebar (toward the canvas). --}}
        <div @pointerdown.prevent="startSidebarDrag('left', $event)"
             :style="sidebarResizerStyle('left')"
             data-testid="tb-resize-left-sidebar"
             title="برای تغییر عرض بکشید"
             class="group relative w-1.5 shrink-0 cursor-col-resize touch-none bg-transparent hover:bg-indigo-400/40 transition-colors z-30">
            <span class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 h-10 w-1 rounded-full bg-slate-300 group-hover:bg-indigo-500 transition-colors pointer-events-none"></span>
        </div>

        {{-- ── PANEL C: Props Panel (LTR = left side) ─────────────── --}}
        <div :style="sidebarPanelStyle('left')"
             :aria-hidden="!showLeftSidebar"
             data-testid="tb-left-sidebar-panel"
             class="bg-white border-r border-slate-200 flex flex-col overflow-hidden shrink-0 z-20">

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

{{-- Block Actions (Move / Duplicate / Delete / Copy/Paste Style) — TB4: path-aware --}}
                    <div class="flex items-center gap-1.5 px-3 py-2 border-b border-slate-100 shrink-0">
                        <button @click="moveSelectedUp()" :disabled="selectedEditorCapabilities.lockedStructure || !canMoveUp()"
                            title="بالاتر"
                            class="flex-1 flex items-center justify-center gap-1 py-1.5 rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-slate-50 disabled:opacity-30 transition">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/></svg>
                        </button>
                        <button @click="moveSelectedDown()" :disabled="selectedEditorCapabilities.lockedStructure || !canMoveDown()"
                            title="پایین‌تر"
                            class="flex-1 flex items-center justify-center gap-1 py-1.5 rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-slate-50 disabled:opacity-30 transition">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <button @click="duplicateSelected()" :disabled="selectedEditorCapabilities.lockedStructure"
                            title="کپی بلوک"
                            class="flex-1 flex items-center justify-center py-1.5 rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-600 transition">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        </button>
                        <button @click="removeSelected()" :disabled="selectedEditorCapabilities.lockedStructure"
                            title="حذف بلوک"
                            class="flex-1 flex items-center justify-center py-1.5 rounded-lg border border-red-200 text-xs font-medium text-red-500 hover:bg-red-50 hover:border-red-400 transition">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                        {{-- Copy/Paste Style --}}
                        <button @click="copyBlockStyle()"
                            title="کپی استایل"
                            class="flex-1 flex items-center justify-center py-1.5 rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-violet-50 hover:border-violet-300 hover:text-violet-600 transition">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                        </button>
                        <button @click="pasteBlockStyle()" :disabled="!canPasteStyle()"
                            title="چسباندن استایل"
                            class="flex-1 flex items-center justify-center py-1.5 rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-violet-50 hover:border-violet-300 hover:text-violet-600 disabled:opacity-30 transition">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 01-2 2v12a2 2 0 012 2h10a2 2 0 012-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
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

                    {{-- ADR-082 (saas-platform): one scroll area for the three tabs.
                         The schema field loop (below the generic panes) serves every
                         tab: it lists the active tab's own fields (`panel`) and moves
                         above the generic sections on Style / Advanced (order-first). --}}
                    <div class="flex-1 overflow-y-auto flex flex-col" data-testid="tb-inspector-scroll">
                    {{-- Style Tab --}}
                    <div x-show="selectedTab === 'style'">

                        {{-- TB5: Breakpoint Toggle Bar — for the generic wrapper
                             sections below; a widget that hides them all (ADR-082)
                             switches devices beside each responsive field instead. --}}
                        <div class="flex items-center gap-0.5 bg-slate-50 border-b border-slate-100 p-2 sticky top-0 z-10"
                            x-show="CanvasControls.hiddenStyleSections(selectedEditorCapabilities).length < Object.keys(CanvasControls.GENERIC_STYLE_SECTIONS).length"
                            data-testid="tb-style-breakpoints">
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
                            <button x-show="activeBreakpoint !== 'desktop' && CanvasControls.hiddenStyleSections(selectedEditorCapabilities).length === 0"
                                @click="resetBlockStyle()"
                                class="mr-1 text-[10px] text-red-500 hover:text-red-700 font-medium underline">
                                پاک کردن
                            </button>
                        </div>

                        {{-- ADR-082: a widget hiding the generic sections whose block
                             still holds values saved in them before. --}}
                        <template x-if="hasHiddenGenericStyle()">
                            <div class="tbc-notice" data-testid="tb-hidden-generic-style">
                                این ویجت استایل‌های خودش را دارد و بخش‌های عمومی استایل برایش پنهان است، اما این بلوک هنوز مقدارهایی از آن بخش‌ها دارد که روی ظاهرش اثر می‌گذارند.
                                <button type="button" class="tbc-reset" @click="clearHiddenGenericStyle()" data-testid="tb-clear-hidden-generic-style">حذف این مقدارها</button>
                            </div>
                        </template>

                        <div class="px-3 py-3 space-y-2" x-show="CanvasControls.hiddenStyleSections(selectedEditorCapabilities).length < Object.keys(CanvasControls.GENERIC_STYLE_SECTIONS).length">

                        {{-- Typography --}}
                        <div class="border border-slate-100 rounded-lg overflow-hidden" x-show="genericStyleVisible('typography')">
                            <button @click="styleSection.typography = !styleSection.typography"
                                class="w-full flex items-center justify-between px-3 py-2 bg-slate-50 text-xs font-semibold text-slate-700 hover:bg-slate-100 transition">
                                <span>تایپوگرافی</span>
                                <svg class="w-3.5 h-3.5 transition-transform" :class="styleSection.typography ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="styleSection.typography" class="p-3 space-y-3">
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="text-xs text-slate-500 mb-1 block flex items-center gap-1">اندازه فونت (px)<span x-show="hasResponsiveOverride('fontSize', activeBreakpoint) && activeBreakpoint !== 'desktop'" class="w-1.5 h-1.5 bg-amber-400 rounded-full inline-block"></span></label>
                                        <input type="number" min="8" max="120" :value="getCascadedValue('fontSize', '')" @input.debounce.300ms="updateStyle('fontSize', $event.target.value===''?'':Number($event.target.value))" placeholder="پیش‌فرض" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300">
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
                                        <input type="number" min="1" max="4" step="0.1" :value="getCascadedValue('lineHeight', '')" @input.debounce.300ms="updateStyle('lineHeight', $event.target.value===''?'':Number($event.target.value))" placeholder="۱.۵" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Colors & Gradient --}}
                        <div class="border border-slate-100 rounded-lg overflow-hidden" x-show="genericStyleVisible('colors')">
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
                                        <input type="text" :value="getCascadedValue('bgColor', '')" @input.debounce.300ms="updateStyle('bgColor', $event.target.value)" placeholder="بدون رنگ" class="flex-1 text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                        <button x-show="getCascadedValue('bgColor')" @click="updateStyle('bgColor', '')" class="text-slate-400 hover:text-red-500 text-sm">×</button>
                                    </div>
                                </div>
                                <div>
                                    <label class="text-xs text-slate-500 mb-1 block flex items-center gap-1">رنگ متن<span x-show="hasResponsiveOverride('textColor', activeBreakpoint) && activeBreakpoint !== 'desktop'" class="w-1.5 h-1.5 bg-amber-400 rounded-full inline-block"></span></label>
                                    <div class="flex items-center gap-2">
                                        <input type="color" :value="getCascadedValue('textColor', '#000000')" @change="updateStyle('textColor', $event.target.value)" class="w-8 h-8 rounded border border-slate-200 cursor-pointer p-0.5">
                                        <input type="text" :value="getCascadedValue('textColor', '')" @input.debounce.300ms="updateStyle('textColor', $event.target.value)" placeholder="بدون رنگ" class="flex-1 text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300">
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
                        <div class="border border-slate-100 rounded-lg overflow-hidden" x-show="genericStyleVisible('spacing')">
                            <button @click="styleSection.spacing = !styleSection.spacing"
                                class="w-full flex items-center justify-between px-3 py-2 bg-slate-50 text-xs font-semibold text-slate-700 hover:bg-slate-100 transition">
                                <span>فاصله‌گذاری</span>
                                <svg class="w-3.5 h-3.5 transition-transform" :class="styleSection.spacing ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="styleSection.spacing" class="p-3 space-y-2">
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="text-xs text-slate-400 mb-1 block">پدینگ بالا</label>
                                        <input type="number" min="0" max="200" :value="getCascadedValue('paddingTop', '')" @input.debounce.300ms="updateStyle('paddingTop', $event.target.value===''?'':Number($event.target.value))" placeholder="px" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                    </div>
                                    <div>
                                        <label class="text-xs text-slate-400 mb-1 block">پدینگ پایین</label>
                                        <input type="number" min="0" max="200" :value="getCascadedValue('paddingBottom', '')" @input.debounce.300ms="updateStyle('paddingBottom', $event.target.value===''?'':Number($event.target.value))" placeholder="px" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                    </div>
                                    <div>
                                        <label class="text-xs text-slate-400 mb-1 block">پدینگ راست</label>
                                        <input type="number" min="0" max="200" :value="getCascadedValue('paddingRight', '')" @input.debounce.300ms="updateStyle('paddingRight', $event.target.value===''?'':Number($event.target.value))" placeholder="px" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                    </div>
                                    <div>
                                        <label class="text-xs text-slate-400 mb-1 block">پدینگ چپ</label>
                                        <input type="number" min="0" max="200" :value="getCascadedValue('paddingLeft', '')" @input.debounce.300ms="updateStyle('paddingLeft', $event.target.value===''?'':Number($event.target.value))" placeholder="px" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                    </div>
                                    <div>
                                        <label class="text-xs text-slate-400 mb-1 block">مارجین بالا</label>
                                        <input type="number" min="-100" max="200" :value="getCascadedValue('marginTop', '')" @input.debounce.300ms="updateStyle('marginTop', $event.target.value===''?'':Number($event.target.value))" placeholder="px" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                    </div>
                                    <div>
                                        <label class="text-xs text-slate-400 mb-1 block">مارجین پایین</label>
                                        <input type="number" min="-100" max="200" :value="getCascadedValue('marginBottom', '')" @input.debounce.300ms="updateStyle('marginBottom', $event.target.value===''?'':Number($event.target.value))" placeholder="px" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Border --}}
                        <div class="border border-slate-100 rounded-lg overflow-hidden" x-show="genericStyleVisible('border')">
                            <button @click="styleSection.border = !styleSection.border"
                                class="w-full flex items-center justify-between px-3 py-2 bg-slate-50 text-xs font-semibold text-slate-700 hover:bg-slate-100 transition">
                                <span>حاشیه</span>
                                <svg class="w-3.5 h-3.5 transition-transform" :class="styleSection.border ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="styleSection.border" class="p-3 space-y-2">
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="text-xs text-slate-400 mb-1 block">ضخامت (px)</label>
                                        <input type="number" min="0" max="20" :value="getCascadedValue('borderWidth', '')" @input.debounce.300ms="updateStyle('borderWidth', $event.target.value===''?'':Number($event.target.value))" placeholder="۰" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300">
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
                                        <input type="number" min="0" max="100" :value="getCascadedValue('borderRadius', '')" @input.debounce.300ms="updateStyle('borderRadius', $event.target.value===''?'':Number($event.target.value))" placeholder="۰" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Shadow --}}
                        <div class="border border-slate-100 rounded-lg overflow-hidden" x-show="genericStyleVisible('shadow')">
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
                        <div class="border border-slate-100 rounded-lg overflow-hidden" x-show="genericStyleVisible('opacity')">
                            <button @click="styleSection.opacity = !styleSection.opacity"
                                class="w-full flex items-center justify-between px-3 py-2 bg-slate-50 text-xs font-semibold text-slate-700 hover:bg-slate-100 transition">
                                <span>شفافیت</span>
                                <svg class="w-3.5 h-3.5 transition-transform" :class="styleSection.opacity ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="styleSection.opacity" class="p-3">
                                <div class="flex items-center gap-2">
                                    <input type="range" min="0.1" max="1" step="0.05" :value="getCascadedValue('opacity', 1)" @input.debounce.300ms="updateStyle('opacity', Number($event.target.value))" class="flex-1">
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
                    <div x-show="selectedTab === 'advanced'" class="px-4 py-4 space-y-4">
                        <div x-show="selectedEditorCapabilities.allowCustomCss">
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">کلاس CSS سفارشی</label>
                            <input type="text" :value="selectedBlock.style?.customClass ?? ''" @input.debounce.300ms="updateStyle('customClass', $event.target.value)"
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
                            <input type="number" min="0.2" max="3" step="0.1" :value="selectedBlock.style?.animationDuration ?? 0.6" @input.debounce.300ms="updateStyle('animationDuration', Number($event.target.value))"
                                class="w-full text-xs px-3 py-2 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        </div>
                    </div>

                    {{-- Content (Props) Form --}}
                    {{-- ADR-082 (saas-platform): this loop renders the active tab's
                         own schema fields (CanvasControls.visibleFields: panel,
                         condition, state), so on Style / Advanced it shows above the
                         generic sections and only when the widget has fields there. --}}
                    <div x-show="selectedTab === 'content' || panelHasFields(selectedTab)"
                        :class="selectedTab === 'content' ? '' : 'order-first border-b border-slate-100'"
                        class="px-4 py-4 space-y-4" :data-testid="'tb-panel-fields-' + selectedTab">
                        <template x-for="(schemaDef, propKey) in panelFields(selectedTab)" :key="propKey">
                            <div :data-testid="'tb-field-' + propKey">
                                {{-- ADR-076: a field's schema `group` opens a sidebar section
                                     where it first appears (same contract as the platform Canvas). --}}
                                <template x-if="fieldGroupStart(propKey)">
                                    <p class="mt-2 mb-3 pb-1 border-b border-slate-100 text-xs font-bold tracking-wide text-indigo-700" x-text="schemaDef.group" :data-testid="'tb-field-group-' + propKey"></p>
                                </template>
                                {{-- ADR-082: Normal / Hover / Active above a state_group --}}
                                <template x-if="fieldStateStart(propKey)">
                                    @include('theme-runtime::canvas.control-states')
                                </template>
                                <div class="flex items-center mb-1.5">
                                    <label class="block text-xs font-semibold text-slate-600" :for="'ctrl-' + propKey" x-text="schemaDef.label || propKey"></label>
                                    @include('theme-runtime::canvas.control-devices')
                                </div>

                                {{-- ADR-082: range, responsive number/select, spacing,
                                     typography and shadow — the shared controls. --}}
                                @include('theme-runtime::canvas.control-field')

                                <template x-if="schemaDef.type === 'string' && !['pack_slug', 'preset_slug', 'asset_version', 'preset_snapshot', 'icon_slug', 'svg_snapshot'].includes(propKey) && !isMediaControl(schemaDef, propKey, selectedBlock?.type) && !isColorControl(schemaDef, propKey, selectedBlock?.type)">
                                    <input
                                        type="text"
                                        x-model="selectedBlock.props[propKey]"
                                        @input="schedulePropUpdate()"
                                        class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 transition"
                                        :placeholder="schemaDef.default ?? ''"
                                        :data-testid="'tb-prop-' + propKey"
                                    >
                                </template>

                                {{-- Phase 0.6 (ADR-065): the shared searchable SVG icon picker. --}}
                                <template x-if="schemaDef.type === 'icon'">
                                    @include('theme-runtime::canvas.icon-picker', [
                                        'value' => "selectedBlock.props[propKey] ?? schemaDef.default ?? ''",
                                        'set' => "onIconSelected(propKey, __icon)",
                                        'id' => 'propKey',
                                        'label' => "schemaDef.label || propKey",
                                    ])
                                </template>

                                {{-- Canvas overhaul Unit 4: one link control (custom URL or any page,
                                     product, category, post or course via the shared entity picker).
                                     Picked entities are stored as references and resolved to the live
                                     permalink at render time (EntityLinkCatalog). --}}
                                <template x-if="schemaDef.type === 'link'">
                                    <div :data-testid="'tb-link-field-' + propKey">
                                        <x-canvas-link-field value="selectedBlock.props[propKey]"
                                                             set="v => { selectedBlock.props[propKey] = v; schedulePropUpdate(); }"
                                                             testid="propKey">
                                            <input type="text" dir="ltr" inputmode="url" :value="linkFieldUrl(selectedBlock.props[propKey])"
                                                   @input="selectedBlock.props[propKey] = linkFieldValue(selectedBlock.props[propKey], $event.target.value); schedulePropUpdate()"
                                                   class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300"
                                                   placeholder="/about یا https://…" :data-testid="'tb-prop-' + propKey">
                                        </x-canvas-link-field>
                                    </div>
                                </template>

                                {{-- One media adapter for every semantically-image property.
                                     It delegates to the existing Canvas media library/upload
                                     endpoints; individual blocks never own an uploader. --}}
                                <template x-if="isMediaControl(schemaDef, propKey, selectedBlock?.type)">
                                    <div class="space-y-2">
                                        <template x-if="mediaSrc(selectedBlock.props[propKey]) && schemaDef.media_kind !== 'video'">
                                            <img :src="mediaSrc(selectedBlock.props[propKey])" :data-testid="'tb-media-preview-' + propKey" class="w-full h-28 object-cover rounded-lg border border-slate-200">
                                        </template>
                                        <div class="flex gap-2">
                                            <input type="url" :value="mediaSrc(selectedBlock.props[propKey])" @input="selectedBlock.props[propKey] = $event.target.value; schedulePropUpdate()" :data-testid="'tb-media-value-' + propKey" :placeholder="schemaDef.media_kind === 'video' ? 'آدرس ویدیو (یا انتخاب از کتابخانه)' : 'آدرس تصویر (یا انتخاب از کتابخانه)'" class="flex-1 text-xs px-2 py-1.5 rounded border border-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-300">
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
                                        <template x-for="(item, itemIndex) in structuredItems(propKey, schemaDef)" :key="repeaterItemKey(item, itemIndex)">
                                            <fieldset class="rounded-lg border bg-slate-50 p-3 space-y-3" :class="selectedLogicalItem?.group === propKey && selectedLogicalItem?.index === itemIndex ? 'border-indigo-500 ring-2 ring-indigo-100' : 'border-slate-200'" :data-testid="`tb-repeater-item-${propKey}-${itemIndex}`">
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
                                                                <template x-for="(nestedItem, nestedIndex) in nestedItems(propKey, itemIndex, fieldKey)" :key="repeaterItemKey(nestedItem, nestedIndex)">
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
                                                                                        <template x-for="(deepItem, deepIndex) in deepNestedItems(propKey, itemIndex, fieldKey, nestedIndex, nestedFieldKey)" :key="repeaterItemKey(deepItem, deepIndex)">
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
                                                                                                            <textarea :value="deepItem[deepFieldKey] ?? ''" @input.debounce.300ms="updateDeepNestedField(propKey, itemIndex, fieldKey, nestedIndex, nestedFieldKey, deepIndex, deepFieldKey, $event.target.value, schemaDef)" rows="2" :data-testid="`tb-deep-field-${propKey}-${itemIndex}-${fieldKey}-${nestedIndex}-${nestedFieldKey}-${deepIndex}-${deepFieldKey}`" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 bg-white focus:outline-none focus:ring-1 focus:ring-indigo-300"></textarea>
                                                                                                        </template>
                                                                                                        <template x-if="deepFieldDef.type === 'link'">
                                                                                                            <x-canvas-link-field value="deepItem[deepFieldKey]" set="v => updateDeepNestedField(propKey, itemIndex, fieldKey, nestedIndex, nestedFieldKey, deepIndex, deepFieldKey, v, schemaDef)" testid="`deep-${propKey}-${itemIndex}-${fieldKey}-${nestedIndex}-${nestedFieldKey}-${deepIndex}-${deepFieldKey}`">
                                                                                                                <input type="text" dir="ltr" inputmode="url" placeholder="/products یا https://…" :value="linkFieldUrl(deepItem[deepFieldKey])" @input.debounce.300ms="updateDeepNestedField(propKey, itemIndex, fieldKey, nestedIndex, nestedFieldKey, deepIndex, deepFieldKey, linkFieldValue(deepItem[deepFieldKey], $event.target.value), schemaDef)" :data-testid="`tb-deep-field-${propKey}-${itemIndex}-${fieldKey}-${nestedIndex}-${nestedFieldKey}-${deepIndex}-${deepFieldKey}`" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 bg-white focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                                                                                            </x-canvas-link-field>
                                                                                                        </template>
                                                                                                        <template x-if="deepFieldDef.type === 'boolean'">
                                                                                                            <label class="flex items-center gap-2 cursor-pointer">
                                                                                                                <input type="checkbox" role="switch" :checked="isTruthyField(deepItem[deepFieldKey])" @change="updateDeepNestedField(propKey, itemIndex, fieldKey, nestedIndex, nestedFieldKey, deepIndex, deepFieldKey, $event.target.checked, schemaDef)" :data-testid="`tb-deep-field-${propKey}-${itemIndex}-${fieldKey}-${nestedIndex}-${nestedFieldKey}-${deepIndex}-${deepFieldKey}`">
                                                                                                                <span class="text-[10px] text-slate-600" x-text="isTruthyField(deepItem[deepFieldKey]) ? 'فعال' : 'غیرفعال'"></span>
                                                                                                            </label>
                                                                                                        </template>
                                                                                                        <template x-if="deepFieldDef.type === 'icon'">
                                                                                                            @include('theme-runtime::canvas.icon-picker', [
                                                                                                                'value' => "deepItem[deepFieldKey] ?? ''",
                                                                                                                'set' => "updateDeepNestedField(propKey, itemIndex, fieldKey, nestedIndex, nestedFieldKey, deepIndex, deepFieldKey, __icon, schemaDef)",
                                                                                                                'id' => '`${propKey}-${itemIndex}-${fieldKey}-${nestedIndex}-${nestedFieldKey}-${deepIndex}-${deepFieldKey}`',
                                                                                                                'label' => "deepFieldDef.label || deepFieldKey",
                                                                                                            ])
                                                                                                        </template>
                                                                                                        <template x-if="deepFieldDef.type !== 'image' && deepFieldDef.type !== 'textarea' && deepFieldDef.type !== 'link' && deepFieldDef.type !== 'boolean' && deepFieldDef.type !== 'icon'">
                                                                                                            <input type="text" :value="deepItem[deepFieldKey] ?? ''" @input.debounce.300ms="updateDeepNestedField(propKey, itemIndex, fieldKey, nestedIndex, nestedFieldKey, deepIndex, deepFieldKey, $event.target.value, schemaDef)" :data-testid="`tb-deep-field-${propKey}-${itemIndex}-${fieldKey}-${nestedIndex}-${nestedFieldKey}-${deepIndex}-${deepFieldKey}`" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 bg-white focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                                                                                        </template>
                                                                                                    </div>
                                                                                                </template>
                                                                                            </div>
                                                                                        </template>
                                                                                        <button type="button" @click="addDeepNestedItem(propKey, itemIndex, fieldKey, nestedIndex, nestedFieldKey, nestedFieldDef, schemaDef)" :data-testid="`tb-deep-add-${propKey}-${itemIndex}-${fieldKey}-${nestedIndex}-${nestedFieldKey}`" class="w-full px-2 py-1 rounded border border-dashed border-indigo-300 text-indigo-600 hover:bg-indigo-50 text-[10px] font-medium">+ افزودن <span x-text="nestedFieldDef.label || nestedFieldKey"></span></button>
                                                                                    </div>
                                                                                </template>
                                                                                <template x-if="nestedFieldDef.type === 'textarea'">
                                                                                    <textarea :value="nestedItem[nestedFieldKey] ?? ''" @input.debounce.300ms="updateNestedField(propKey, itemIndex, fieldKey, nestedIndex, nestedFieldKey, $event.target.value, schemaDef)" rows="2" :data-testid="`tb-nested-field-${propKey}-${itemIndex}-${fieldKey}-${nestedIndex}-${nestedFieldKey}`" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 bg-white focus:outline-none focus:ring-1 focus:ring-indigo-300"></textarea>
                                                                                </template>
                                                                                <template x-if="nestedFieldDef.type === 'link'">
                                                                                    <x-canvas-link-field value="nestedItem[nestedFieldKey]" set="v => updateNestedField(propKey, itemIndex, fieldKey, nestedIndex, nestedFieldKey, v, schemaDef)" testid="`nested-${propKey}-${itemIndex}-${fieldKey}-${nestedIndex}-${nestedFieldKey}`">
                                                                                        <input type="text" dir="ltr" inputmode="url" placeholder="/products یا https://…" :value="linkFieldUrl(nestedItem[nestedFieldKey])" @input.debounce.300ms="updateNestedField(propKey, itemIndex, fieldKey, nestedIndex, nestedFieldKey, linkFieldValue(nestedItem[nestedFieldKey], $event.target.value), schemaDef)" :data-testid="`tb-nested-field-${propKey}-${itemIndex}-${fieldKey}-${nestedIndex}-${nestedFieldKey}`" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 bg-white focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                                                                    </x-canvas-link-field>
                                                                                </template>
                                                                                <template x-if="nestedFieldDef.type === 'boolean'">
                                                                                    <label class="flex items-center gap-2 cursor-pointer">
                                                                                        <input type="checkbox" role="switch" :checked="isTruthyField(nestedItem[nestedFieldKey])" @change="updateNestedField(propKey, itemIndex, fieldKey, nestedIndex, nestedFieldKey, $event.target.checked, schemaDef)" :data-testid="`tb-nested-field-${propKey}-${itemIndex}-${fieldKey}-${nestedIndex}-${nestedFieldKey}`">
                                                                                        <span class="text-[10px] text-slate-600" x-text="isTruthyField(nestedItem[nestedFieldKey]) ? 'فعال' : 'غیرفعال'"></span>
                                                                                    </label>
                                                                                </template>
                                                                                <template x-if="nestedFieldDef.type === 'icon'">
                                                                                    @include('theme-runtime::canvas.icon-picker', [
                                                                                        'value' => "nestedItem[nestedFieldKey] ?? ''",
                                                                                        'set' => "updateNestedField(propKey, itemIndex, fieldKey, nestedIndex, nestedFieldKey, __icon, schemaDef)",
                                                                                        'id' => '`${propKey}-${itemIndex}-${fieldKey}-${nestedIndex}-${nestedFieldKey}`',
                                                                                        'label' => "nestedFieldDef.label || nestedFieldKey",
                                                                                    ])
                                                                                </template>
                                                                                <template x-if="nestedFieldDef.type !== 'textarea' && nestedFieldDef.type !== 'image' && nestedFieldDef.type !== 'repeater' && nestedFieldDef.type !== 'link' && nestedFieldDef.type !== 'boolean' && nestedFieldDef.type !== 'icon'">
                                                                                    <input type="text" :value="nestedItem[nestedFieldKey] ?? ''" @input.debounce.300ms="updateNestedField(propKey, itemIndex, fieldKey, nestedIndex, nestedFieldKey, $event.target.value, schemaDef)" :data-testid="`tb-nested-field-${propKey}-${itemIndex}-${fieldKey}-${nestedIndex}-${nestedFieldKey}`" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 bg-white focus:outline-none focus:ring-1 focus:ring-indigo-300">
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
                                                                <input type="checkbox" role="switch" :id="`tb-structured-${propKey}-${itemIndex}-${fieldKey}`" :checked="isTruthyField(item[fieldKey])" @change="updateStructuredField(propKey, itemIndex, fieldKey, $event.target.checked, schemaDef)" :data-testid="`tb-structured-field-${propKey}-${itemIndex}-${fieldKey}`">
                                                                <span class="text-[11px] text-slate-600" x-text="isTruthyField(item[fieldKey]) ? 'فعال' : 'غیرفعال'"></span>
                                                            </label>
                                                        </template>
                                                        <template x-if="fieldDef.type === 'link'">
                                                            <x-canvas-link-field value="item[fieldKey]" set="v => updateStructuredField(propKey, itemIndex, fieldKey, v, schemaDef)" testid="`item-${propKey}-${itemIndex}-${fieldKey}`">
                                                                <input type="text" dir="ltr" inputmode="url" placeholder="/products یا https://…" :id="`tb-structured-${propKey}-${itemIndex}-${fieldKey}`" :value="linkFieldUrl(item[fieldKey])" @input.debounce.300ms="updateStructuredField(propKey, itemIndex, fieldKey, linkFieldValue(item[fieldKey], $event.target.value), schemaDef)" :data-testid="`tb-structured-field-${propKey}-${itemIndex}-${fieldKey}`" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 bg-white focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                                            </x-canvas-link-field>
                                                        </template>
                                                        <template x-if="fieldDef.type === 'select'">
                                                            <select :value="item[fieldKey] ?? ''" @change="updateStructuredField(propKey, itemIndex, fieldKey, $event.target.value, schemaDef)" :data-testid="`tb-structured-field-${propKey}-${itemIndex}-${fieldKey}`" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 bg-white focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                                                <template x-for="(optLabel, optVal) in fieldDef.options" :key="optVal">
                                                                    <option :value="optVal" x-text="optLabel"></option>
                                                                </template>
                                                            </select>
                                                        </template>
                                                        <template x-if="fieldDef.type === 'icon'">
                                                            @include('theme-runtime::canvas.icon-picker', [
                                                                'value' => "item[fieldKey] ?? ''",
                                                                'set' => "updateStructuredField(propKey, itemIndex, fieldKey, __icon, schemaDef)",
                                                                'id' => '`${propKey}-${itemIndex}-${fieldKey}`',
                                                                'label' => "fieldDef.label || fieldKey",
                                                            ])
                                                        </template>
                                                        <template x-if="fieldDef.type !== 'image' && fieldDef.type !== 'repeater' && fieldDef.type !== 'boolean' && fieldDef.type !== 'select' && fieldDef.type !== 'link' && fieldDef.type !== 'icon'">
                                                            <div>
                                                                <template x-if="fieldDef.type === 'textarea'">
                                                                    <textarea :id="`tb-structured-${propKey}-${itemIndex}-${fieldKey}`" :value="item[fieldKey] ?? ''" @input.debounce.300ms="updateStructuredField(propKey, itemIndex, fieldKey, $event.target.value, schemaDef)" rows="2" :data-testid="`tb-structured-field-${propKey}-${itemIndex}-${fieldKey}`" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 bg-white focus:outline-none focus:ring-1 focus:ring-indigo-300"></textarea>
                                                                </template>
                                                                <template x-if="fieldDef.type !== 'textarea'">
                                                                    <input type="text" :id="`tb-structured-${propKey}-${itemIndex}-${fieldKey}`" :value="item[fieldKey] ?? ''" @input.debounce.300ms="updateStructuredField(propKey, itemIndex, fieldKey, $event.target.value, schemaDef)" :data-testid="`tb-structured-field-${propKey}-${itemIndex}-${fieldKey}`" class="w-full text-xs px-2 py-1.5 rounded border border-slate-200 bg-white focus:outline-none focus:ring-1 focus:ring-indigo-300">
                                                                </template>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </template>
                                                <div class="flex gap-1 justify-end">
                                                    <button type="button" @click="moveStructuredItem(propKey, itemIndex, -1, schemaDef)" :disabled="itemIndex === 0" :data-testid="`tb-structured-up-${propKey}-${itemIndex}`" class="px-2 py-1 rounded border border-slate-200 text-xs disabled:opacity-30" :aria-label="`انتقال مورد ${itemIndex + 1} به بالا`">↑</button>
                                                    <button type="button" @click="moveStructuredItem(propKey, itemIndex, 1, schemaDef)" :disabled="itemIndex >= structuredItems(propKey, schemaDef).length - 1" :data-testid="`tb-structured-down-${propKey}-${itemIndex}`" class="px-2 py-1 rounded border border-slate-200 text-xs disabled:opacity-30" :aria-label="`انتقال مورد ${itemIndex + 1} به پایین`">↓</button>
                                                    <button type="button" @click="duplicateStructuredItem(propKey, itemIndex, schemaDef)" :data-testid="`tb-structured-duplicate-${propKey}-${itemIndex}`" class="px-2 py-1 rounded border border-slate-200 text-xs" :aria-label="`تکثیر مورد ${itemIndex + 1}`">تکثیر</button>
                                                    <button type="button" @click="removeStructuredItem(propKey, itemIndex, schemaDef)" :data-testid="`tb-structured-remove-${propKey}-${itemIndex}`" class="px-2 py-1 rounded border border-red-200 text-red-600 text-xs" :aria-label="`حذف مورد ${itemIndex + 1}`">حذف مورد</button>
                                                </div>
                                            </fieldset>
                                        </template>
                                        <button type="button" @click="addStructuredItem(propKey, schemaDef)" :data-testid="'tb-structured-add-' + propKey" class="w-full px-3 py-2 rounded-lg border border-dashed border-indigo-300 text-indigo-600 hover:bg-indigo-50 text-xs font-medium">+ افزودن مورد</button>
                                    </div>
                                </template>

                                <template x-if="schemaDef.type === 'number' && !ctlIsResponsive(propKey)">
                                    <input
                                        type="number"
                                        x-model.number="selectedBlock.props[propKey]"
                                        @input="schedulePropUpdate()"
                                        class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 transition"
                                        :min="schemaDef.min ?? 0" :max="schemaDef.max ?? null" :step="schemaDef.step ?? 'any'"
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
                                                    <p class="text-[11px] truncate" :class="motionAvailability(selectedBlock.props[propKey]) === 'available' ? 'text-slate-400' : 'text-amber-600'"
                                                       :data-testid="'tb-motion-status-' + propKey"
                                                       x-text="motionAvailabilityNotice(selectedBlock.props[propKey]) || motionAssetById(selectedBlock.props[propKey])?.category || 'در دسترس این سایت'"></p>
                                                </div>
                                                <button @click="clearMotionSelect(propKey)" title="حذف انتخاب" class="text-slate-400 hover:text-red-500 text-sm px-1">×</button>
                                            </div>
                                        </template>
                                        <template x-if="!selectedBlock.props[propKey]">
                                            <p class="text-xs text-slate-400">موشنی انتخاب نشده — این بلوک چیزی نمایش نمی‌دهد.</p>
                                        </template>
                                        <button @click="openMotionPicker(propKey)" :data-testid="'tb-motion-pick-' + propKey" class="w-full text-xs px-3 py-2 rounded-lg border border-indigo-300 text-indigo-600 hover:bg-indigo-50 transition font-medium">
                                            🎬 انتخاب موشن از موشن‌های مجاز این سایت
                                        </button>
                                    </div>
                                </template>

                                {{-- ADR-079/080 (saas-platform): the selected motion's own runtime
                                     parameters — texts, colours, colour scheme, section
                                     switches, animate/loop/speed — built
                                     from its manifest `parameters`. Stored as {key: value}
                                     overrides; an untouched field keeps the asset default. --}}
                                <template x-if="schemaDef.type === 'motion_params'">
                                    <div class="space-y-3" x-init="ensureMotionAssets()" data-testid="tb-motion-params">
                                        <template x-if="!motionParamDefs().length">
                                            <p class="text-xs" data-testid="tb-motion-params-notice"
                                               :class="['premium', 'unavailable'].includes(motionAvailability(selectedBlock.props.motion_asset_id)) ? 'text-amber-600' : 'text-slate-400'"
                                               x-text="motionParamsNotice()"></p>
                                        </template>
                                        <template x-for="group in motionParamGroups()" :key="group.key">
                                            <fieldset class="space-y-2.5 border border-slate-200 rounded-lg p-2.5">
                                                <legend class="text-[11px] font-semibold text-slate-500 px-1" x-text="group.label"></legend>
                                                <template x-for="p in group.items" :key="p.key">
                                                    <div class="space-y-1">
                                                        <label class="block text-xs text-slate-600" :for="'tb-mp-' + p.key" x-text="p.label"></label>
                                                        <template x-if="p.type === 'text'">
                                                            <input type="text" :id="'tb-mp-' + p.key" :maxlength="p.max" :value="motionParamValue(propKey, p)" :placeholder="p.default"
                                                                @input="setMotionParam(propKey, p, $event.target.value)" :data-testid="'tb-mp-' + p.key"
                                                                class="w-full text-xs px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300">
                                                        </template>
                                                        <template x-if="p.type === 'color'">
                                                            <div class="flex items-center gap-2">
                                                                <input type="color" :id="'tb-mp-' + p.key" :value="motionParamValue(propKey, p)" @input="setMotionParam(propKey, p, $event.target.value)" :data-testid="'tb-mp-' + p.key" class="w-9 h-8 rounded border border-slate-200 bg-white cursor-pointer">
                                                                <input type="text" dir="ltr" maxlength="7" :value="motionParamValue(propKey, p)" @change="/^#[0-9a-fA-F]{6}$/.test($event.target.value) && setMotionParam(propKey, p, $event.target.value.toLowerCase())" class="flex-1 text-xs px-2 py-1.5 rounded-lg border border-slate-200 bg-white font-mono" :aria-label="p.label + ' (کد رنگ)'">
                                                            </div>
                                                        </template>
                                                        <template x-if="p.type === 'number'">
                                                            <div class="flex items-center gap-2">
                                                                <input type="range" :id="'tb-mp-' + p.key" :min="p.min" :max="p.max" :step="p.step" :value="motionParamValue(propKey, p)" @input="setMotionParam(propKey, p, $event.target.value)" :data-testid="'tb-mp-' + p.key" class="flex-1 accent-indigo-500">
                                                                <span class="text-xs text-slate-600 w-10 text-left" x-text="motionParamValue(propKey, p) + (p.format === 'percent' ? '٪' : '')"></span>
                                                            </div>
                                                        </template>
                                                        <template x-if="p.type === 'boolean'">
                                                            <label class="flex items-center gap-2 cursor-pointer">
                                                                <div class="relative">
                                                                    <input type="checkbox" :id="'tb-mp-' + p.key" :checked="motionParamValue(propKey, p) === true" @change="setMotionParam(propKey, p, $event.target.checked)" class="sr-only peer" :data-testid="'tb-mp-' + p.key">
                                                                    <div class="w-9 h-5 bg-slate-200 peer-checked:bg-indigo-500 rounded-full transition"></div>
                                                                    <div class="absolute top-0.5 right-0.5 w-4 h-4 bg-white rounded-full shadow peer-checked:translate-x-full rtl:peer-checked:-translate-x-full transition-transform"></div>
                                                                </div>
                                                                <span class="text-xs text-slate-600" x-text="p.group === 'sections' ? (motionParamValue(propKey, p) ? 'نمایش' : 'پنهان') : (motionParamValue(propKey, p) ? 'فعال' : 'غیرفعال')"></span>
                                                            </label>
                                                        </template>
                                                        <template x-if="p.type === 'select'">
                                                            <select :id="'tb-mp-' + p.key" :value="motionParamValue(propKey, p)" @change="setMotionParam(propKey, p, $event.target.value)" :data-testid="'tb-mp-' + p.key" class="w-full text-xs px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white">
                                                                <template x-for="(optLabel, optVal) in p.options" :key="optVal">
                                                                    <option :value="optVal" x-text="optLabel" :selected="optVal === motionParamValue(propKey, p)"></option>
                                                                </template>
                                                            </select>
                                                        </template>
                                                    </div>
                                                </template>
                                            </fieldset>
                                        </template>
                                        <button type="button" x-show="hasMotionParams(propKey)" @click="resetMotionParams(propKey)" data-testid="tb-motion-params-reset"
                                            class="w-full text-xs px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition">بازگشت به مقادیر پیش‌فرض موشن</button>
                                    </div>
                                </template>

                                <template x-if="schemaDef.type === 'boolean'">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <div class="relative">
                                            <input type="checkbox" :checked="ctlChecked(propKey)" @change="ctlSetProp(propKey, $event.target.checked)" class="sr-only peer" :data-testid="'tb-prop-' + propKey">
                                            <div class="w-9 h-5 bg-slate-200 peer-checked:bg-indigo-500 rounded-full transition"></div>
                                            <div class="absolute top-0.5 right-0.5 w-4 h-4 bg-white rounded-full shadow peer-checked:translate-x-full rtl:peer-checked:-translate-x-full transition-transform"></div>
                                        </div>
                                        <span class="text-sm text-slate-700" x-text="ctlChecked(propKey) ? 'فعال' : 'غیرفعال'"></span>
                                    </label>
                                </template>

                                {{-- ADR-076: segmented control for a select whose schema declares
                                     ui: 'segmented' — one button per schema option. --}}
                                <template x-if="schemaDef.type === 'select' && schemaDef.ui === 'segmented' && !ctlIsResponsive(propKey)">
                                    <div class="flex gap-1 p-1 rounded-lg bg-slate-100" role="radiogroup" :aria-label="schemaDef.label || propKey" :data-testid="'tb-segmented-' + propKey">
                                        <template x-for="(label, val) in schemaDef.options" :key="val">
                                            <button type="button" role="radio"
                                                :aria-checked="String(selectedBlock.props[propKey] ?? schemaDef.default ?? '') === String(val) ? 'true' : 'false'"
                                                @click="selectedBlock.props[propKey] = val; schedulePropUpdate()"
                                                :class="String(selectedBlock.props[propKey] ?? schemaDef.default ?? '') === String(val) ? 'bg-white text-indigo-700 shadow-sm font-semibold' : 'text-slate-600 hover:text-slate-800'"
                                                class="flex-1 px-2 py-1.5 rounded-md text-xs leading-tight transition focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                                                x-text="label" :data-testid="'tb-segmented-' + propKey + '-' + val"></button>
                                        </template>
                                    </div>
                                </template>

                                <template x-if="schemaDef.type === 'select' && schemaDef.ui !== 'segmented' && !ctlIsResponsive(propKey)">
                                    <select
                                        x-model="selectedBlock.props[propKey]"
                                        @change="schedulePropUpdate()"
                                        class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 transition"
                                        :data-testid="'tb-prop-' + propKey"
                                    >
                                        {{-- Phase 0.2c: each option states its own selected state —
                                             x-model alone set the value before x-for had rendered the
                                             options, so the select could show the first option instead
                                             of the stored value. --}}
                                        <template x-for="(label, val) in schemaDef.options" :key="val">
                                            <option :value="val" :selected="String(selectedBlock.props[propKey] ?? schemaDef.default ?? '') === String(val)" x-text="label"></option>
                                        </template>
                                    </select>
                                </template>

                                <template x-if="selectedBlock?.type === 'button' && propKey === 'style_mode' && selectedBlock.props?.style_mode === 'custom'">
                                    <div class="mt-2.5 p-3 rounded-xl bg-violet-50/70 border border-violet-100 space-y-2" data-testid="tb-button-custom-preset-box">
                                        <div class="flex items-center justify-between">
                                            <label class="block text-xs font-semibold text-violet-800">استایل اختصاصی این دکمه:</label>
                                            <a href="{{ route('admin.canvas-marketplace.index', ['tab' => 'buttons']) }}" target="_blank"
                                               class="text-[10px] text-violet-600 hover:text-violet-800 font-medium">کتابخانه دکمه‌ها ↗</a>
                                        </div>
                                        <select
                                            :value="(selectedBlock.props.pack_slug && selectedBlock.props.preset_slug) ? (selectedBlock.props.pack_slug + '|' + selectedBlock.props.preset_slug) : ''"
                                            @change="onCustomButtonPresetChange($event.target.value)"
                                            class="w-full text-xs px-2.5 py-2 rounded-lg border border-violet-200 bg-white focus:outline-none focus:ring-1 focus:ring-violet-400">
                                            <option value="">-- انتخاب پریست استایل --</option>
                                            @foreach($grantedButtonPacks ?? [] as $bp)
                                                <optgroup label="{{ $bp['title'] }}">
                                                    @foreach($bp['presets'] ?? [] as $pr)
                                                        <option value="{{ $bp['slug'] }}|{{ $pr['slug'] }}">
                                                            {{ $pr['title'] ?? $pr['slug'] }} ({{ $bp['title'] }})
                                                        </option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </select>
                                        <p class="text-[11px] text-violet-600 leading-4">این استایل به صورت کاملاً ایزوله روی همین دکمه ذخیره و اعمال می‌شود و طراحی سایر بخش‌ها را دستکاری نمی‌کند.</p>
                                    </div>
                                </template>

                                {{-- Phase 0.5 (ADR-064): the same colour control as the platform
                                     Canvas — theme swatches stored as var(--tb-…) so the block follows
                                     the theme, opacity (rgba() or a color-mix() of the swatch) and
                                     explicit transparent, on the shared theme-runtime CanvasColor. --}}
                                <template x-if="isColorControl(schemaDef, propKey, selectedBlock?.type)">
                                    <div class="space-y-2" :data-testid="'tb-color-' + propKey">
                                        <div class="flex items-center gap-2">
                                            <span class="relative w-10 h-10 rounded-lg border border-slate-200 overflow-hidden shrink-0" style="background-image:conic-gradient(#e2e8f0 25%,#fff 0 50%,#e2e8f0 0 75%,#fff 0);background-size:10px 10px;" aria-hidden="true">
                                                <span class="absolute inset-0" :style="'background:' + colorPreview(selectedBlock.props[propKey])" :data-testid="'tb-color-preview-' + propKey"></span>
                                            </span>
                                            <input type="color" :value="colorInputValue(selectedBlock.props[propKey])" @input="setColorBase(propKey, $event.target.value)" class="w-10 h-10 rounded-lg border border-slate-200 cursor-pointer p-0.5" aria-label="انتخاب رنگ دلخواه">
                                            <input type="text" dir="ltr" x-model="selectedBlock.props[propKey]" @input="schedulePropUpdate()" class="flex-1 min-w-0 text-xs px-2.5 py-2 rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300 font-mono" placeholder="#000000" aria-label="مقدار رنگ" :data-testid="'tb-prop-' + propKey">
                                            <button type="button" @click="clearColor(propKey, schemaDef.default ?? '')" class="px-2 py-1.5 rounded border border-slate-200 text-xs text-slate-600 hover:bg-slate-50">پاک‌کردن</button>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-1.5" role="group" aria-label="رنگ‌های قالب">
                                            <template x-for="color in themePalette()" :key="color.token">
                                                <button type="button" @click="selectThemeColor(propKey, color)" :data-testid="'tb-theme-color-' + propKey + '-' + color.token" :title="color.label" :aria-label="color.label"
                                                    :aria-pressed="colorParse(selectedBlock.props[propKey]).token === color.token ? 'true' : 'false'"
                                                    :class="colorParse(selectedBlock.props[propKey]).token === color.token ? 'ring-2 ring-indigo-500 ring-offset-1' : 'ring-1 ring-slate-200'"
                                                    class="w-6 h-6 rounded-full border-2 border-white shadow hover:scale-110 transition" :style="'background:' + color.value"></button>
                                            </template>
                                            <button type="button" @click="selectedBlock.props[propKey] = 'transparent'; schedulePropUpdate()" :aria-pressed="colorParse(selectedBlock.props[propKey]).kind === 'transparent' ? 'true' : 'false'"
                                                :class="colorParse(selectedBlock.props[propKey]).kind === 'transparent' ? 'ring-2 ring-indigo-500' : 'ring-1 ring-slate-200'"
                                                class="px-2 h-6 rounded-full text-[11px] text-slate-600 bg-white" :data-testid="'tb-color-transparent-' + propKey">بی‌رنگ</button>
                                        </div>
                                        <label class="flex items-center gap-2 text-[11px] text-slate-600">
                                            <span class="shrink-0">شفافیت</span>
                                            <input type="range" min="0" max="100" step="5" class="flex-1 accent-indigo-600" :value="colorParse(selectedBlock.props[propKey]).alpha" @input="setColorAlpha(propKey, Number($event.target.value))"
                                                :disabled="!['token', 'hex', 'rgba'].includes(colorParse(selectedBlock.props[propKey]).kind)" :data-testid="'tb-color-alpha-' + propKey" aria-label="میزان شفافیت رنگ">
                                            <span class="w-9 text-left tabular-nums" x-text="(colorParse(selectedBlock.props[propKey]).kind === 'transparent' ? 0 : colorParse(selectedBlock.props[propKey]).alpha) + '٪'"></span>
                                        </label>
                                    </div>
                                </template>

                                {{-- header_order is an ordered list of header elements, not a
                                     raw list value: author it by dragging, and persist the
                                     resulting key order through schedulePropUpdate(). --}}
                                <template x-if="schemaDef.type === 'array' && propKey === 'header_order'">
                                    <div dir="rtl" data-testid="tb-header-order">
                                        <p class="text-[10px] text-slate-400 mb-1.5">برای تغییر ترتیب، هر آیتم را بکشید و رها کنید.</p>
                                        <ul class="space-y-1.5">
                                            <template x-for="item in headerOrderItems(propKey)" :key="item.key">
                                                <li draggable="true"
                                                    :data-testid="'tb-header-order-item-' + item.key"
                                                    class="flex items-center gap-2 px-2.5 py-2 rounded-lg border bg-white text-xs cursor-move select-none transition"
                                                    :class="headerDragOverKey === item.key ? 'border-indigo-400 ring-1 ring-indigo-200 opacity-80' : 'border-slate-200'"
                                                    @dragstart="headerDragKey = item.key; headerDragOverKey = item.key; $event.dataTransfer.effectAllowed = 'move'; $event.dataTransfer.setData('text/plain', item.key);"
                                                    @dragover.prevent="headerDragOverKey = item.key; if ($event.dataTransfer) $event.dataTransfer.dropEffect = 'move';"
                                                    @drop.prevent="
                                                        const from = headerDragKey ?? $event.dataTransfer.getData('text/plain');
                                                        const to = item.key;
                                                        headerDragKey = null; headerDragOverKey = null;
                                                        if (!from || from === to) return;
                                                        const keys = headerOrderItems(propKey).map(i => i.key);
                                                        const fromIndex = keys.indexOf(from);
                                                        const toIndex = keys.indexOf(to);
                                                        if (fromIndex === -1 || toIndex === -1) return;
                                                        keys.splice(toIndex, 0, keys.splice(fromIndex, 1)[0]);
                                                        schedulePropUpdate('header_order', keys);
                                                    "
                                                    @dragend="headerDragKey = null; headerDragOverKey = null;"
                                                >
                                                    <span class="text-slate-300" aria-hidden="true">⠿</span>
                                                    <span class="flex-1 text-slate-700" x-text="item.label"></span>
                                                    <span class="font-mono text-[10px] text-slate-400" x-text="item.key"></span>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </template>

                                {{-- Phase 0.2c: schema-less list props (type: array, no
                                     `fields` — accordion items, manual id lists), the same
                                     guarded JSON editor as the platform Canvas. An invalid
                                     draft is never written to the block. --}}
                                <template x-if="schemaDef.type === 'array' && propKey !== 'header_order'">
                                    <div x-data="{ jsonDraft: null, jsonDraftFor: null, jsonError: '' }">
                                        <textarea dir="ltr" rows="5" spellcheck="false" :data-testid="'tb-prop-' + propKey"
                                            :value="(jsonDraftFor === selectedBlock.id && jsonDraft !== null) ? jsonDraft : JSON.stringify(selectedBlock.props[propKey] ?? schemaDef.default ?? [], null, 2)"
                                            @input="
                                                jsonDraft = $event.target.value; jsonDraftFor = selectedBlock.id;
                                                try {
                                                    const parsed = JSON.parse(jsonDraft);
                                                    if (!Array.isArray(parsed)) throw new Error('not a list');
                                                    jsonError = '';
                                                    selectedBlock.props[propKey] = parsed;
                                                    schedulePropUpdate();
                                                } catch (_) { jsonError = 'JSON نامعتبر است؛ تا اصلاح نشود ذخیره نمی‌شود.'; }
                                            "
                                            @blur="if (!jsonError) { jsonDraft = null; jsonDraftFor = null; }"
                                            class="w-full text-[11px] leading-5 px-2.5 py-2 rounded-lg border bg-white font-mono resize-y focus:outline-none focus:ring-2"
                                            :class="jsonError ? 'border-rose-300 focus:ring-rose-200' : 'border-slate-200 focus:ring-indigo-300'"></textarea>
                                        <p class="text-[10px] mt-1" :class="jsonError ? 'text-rose-600' : 'text-slate-400'" x-text="jsonError || 'ویرایش پیشرفته (فهرست JSON)'"></p>
                                    </div>
                                </template>

                                {{-- also multi-line `text` and `rich_text` (sanitized at render
                                     time), which had no control at all --}}
                                <template x-if="schemaDef.type === 'textarea' || schemaDef.type === 'text' || schemaDef.type === 'rich_text'">
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

                        {{-- Phase 4B.6 — generic Manual/Dynamic content-source
                             selector. Rendered for any block whose registered
                             type declares a manual_prop; no FoodMart branch. --}}
                        <template x-if="selectedTab === 'content' && selectedManualPropKey">
                            <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-3 space-y-2" data-testid="tb-content-source">
                                <label class="block text-xs font-semibold text-slate-700 mb-1">منبع محتوا (Content source)</label>
                                <div class="flex gap-2">
                                    <button type="button" @click="setContentSource('manual')"
                                        class="flex-1 px-2 py-1.5 rounded-lg text-xs font-medium border"
                                        :class="!bindingEnabled ? 'bg-amber-500 text-white border-amber-500' : 'bg-white text-slate-600 border-slate-200'"
                                        data-testid="tb-source-manual">دستی (Manual)</button>
                                    <button type="button" @click="setContentSource('dynamic')"
                                        class="flex-1 px-2 py-1.5 rounded-lg text-xs font-medium border"
                                        :class="bindingEnabled ? 'bg-indigo-500 text-white border-indigo-500' : 'bg-white text-slate-600 border-slate-200'"
                                        data-testid="tb-source-dynamic">پویا (Dynamic)</button>
                                </div>
                                <p class="text-[11px] text-slate-500" x-show="!bindingEnabled">محتوای دستی شما در همین بخش ویرایش می‌شود و با تغییر منبع حذف نمی‌شود.</p>
                                <p class="text-[11px] text-slate-500" x-show="bindingEnabled">محتوا از داده‌های واقعی ماژول خوانده می‌شود؛ محتوای دستی حفظ می‌ماند.</p>
                            </div>
                        </template>

                        <template x-if="selectedTab === 'content' && selectedBindingDef">
                            <div class="rounded-xl border border-indigo-200 bg-indigo-50/60 p-3 space-y-3" data-testid="tb-data-binding" x-show="bindingEnabled">
                                <label class="flex items-center justify-between gap-2 text-xs font-semibold text-slate-700">
                                    <span>Dynamic data</span>
                                    <input type="checkbox" :checked="bindingEnabled" @change="toggleBinding($event.target.checked)" data-testid="tb-binding-enabled">
                                </label>
                                <template x-if="bindingEnabled">
                                    <div class="space-y-3">
                                        <p class="text-[11px] text-slate-500">Source: <span class="font-mono" x-text="selectedBindingDef.source"></span></p>
                                        <div>
                                            <label class="block text-xs font-medium text-slate-600 mb-1">Sort / selection</label>
                                            <select @change="updateBindingQuery('sort', $event.target.value)" class="w-full text-xs px-2 py-2 rounded border border-slate-200 bg-white" data-testid="tb-binding-sort">
                                                <template x-for="sort in bindingSortOptions" :key="sort"><option :value="sort" :selected="sort === selectedBlock.binding.query.sort" x-text="sort"></option></template>
                                            </select>
                                        </div>
                                        <div x-show="selectedBindingDef.source === 'products'">
                                            <label class="block text-xs font-medium text-slate-600 mb-1">Category ID (optional)</label>
                                            <input type="number" min="1" :value="selectedBlock.binding.query.category_id ?? ''" @input.debounce.300ms="updateBindingQuery('category_id', $event.target.value ? Number($event.target.value) : null)" class="w-full text-xs px-2 py-2 rounded border border-slate-200 bg-white" data-testid="tb-binding-category">
                                        </div>                                            <div x-show="selectedBlock.binding.query.sort === 'manual'" x-effect="bindingEntitiesActive">
                                            <label class="block text-xs font-medium text-slate-600 mb-1">انتخاب موارد</label>
                                            <div x-show="bindingEntitiesLoading" class="text-[11px] text-slate-400 py-1">در حال بارگذاری موارد…</div>
                                            <div x-show="!bindingEntitiesLoading && bindingEntities.length === 0" class="text-[11px] text-slate-400 py-1">موردی موجود نیست</div>
                                            <template x-if="!bindingEntitiesLoading && bindingEntities.length > 0">
                                                <div class="max-h-40 overflow-y-auto rounded border border-slate-200 bg-white divide-y divide-slate-100" data-testid="tb-binding-entity-options">
                                                    <template x-for="entity in bindingEntities" :key="entity.id">
                                                        <label class="flex items-center gap-2 px-2 py-1.5 text-xs cursor-pointer hover:bg-slate-50">
                                                            <input type="checkbox" class="rounded" :value="entity.id"
                                                                :checked="(selectedBlock.binding.query.selected_ids ?? []).includes(entity.id)"
                                                                @change="toggleBindingEntity(entity.id, $event.target.checked)" :data-testid="'tb-binding-entity-' + entity.id">
                                                            <span class="truncate" x-text="entity.label"></span>
                                                        </label>
                                                    </template>
                                                </div>
                                            </template>
                                            <p class="text-[10px] text-slate-400 mt-1" x-show="bindingSelectedLabels">انتخاب‌شده: <span x-text="bindingSelectedLabels"></span></p>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-slate-600 mb-1">Item limit</label>
                                            <input type="number" min="1" max="24" :value="selectedBlock.binding.query.limit" @input.debounce.300ms="updateBindingQuery('limit', Number($event.target.value))" class="w-full text-xs px-2 py-2 rounded border border-slate-200 bg-white" data-testid="tb-binding-limit">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-slate-600 mb-1">When no data exists</label>
                                            <select x-model="selectedBlock.binding.empty" @change="schedulePropUpdate()" class="w-full text-xs px-2 py-2 rounded border border-slate-200 bg-white" data-testid="tb-binding-empty">
                                                <option value="manual">نمایش محتوای دستی</option><option value="hide">پنهان‌کردن بخش</option><option value="empty_state">نمایش وضعیت خالی</option>
                                            </select>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>

                        {{-- Column widths — the Inspector half of the Canvas resizer.
                             The values are exactly props.columns[i].width (percent) a
                             drag writes, so a numeric edit and a drag cannot drift. --}}
                        <template x-if="selectedTab === 'content' && selectedBlock?.type === 'columns' && columnWidthCount() >= 2">
                            <div class="mt-2 pt-3 border-t border-slate-100" data-testid="tb-column-widths">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-semibold text-slate-700">عرض ستون‌ها</span>
                                    <span class="text-[10px] text-slate-400">مجموع <span x-text="faDigits(Math.round(columnWidthTotal()))"></span>٪</span>
                                </div>
                                <div class="space-y-2.5">
                                    <template x-for="(width, index) in columnWidths()" :key="index">
                                        <div class="flex items-center gap-2">
                                            <span class="w-11 shrink-0 text-[10px] text-slate-500" x-text="'ستون ' + faDigits(index + 1)"></span>
                                            <input type="range" min="10" max="90" step="1" dir="ltr"
                                                :value="Math.round(width)"
                                                @focus="beginColumnWidthEdit()"
                                                @input="setColumnWidth(index, $event.target.value)"
                                                :aria-label="'عرض ستون ' + faDigits(index + 1) + ' درصد'"
                                                :data-testid="'tb-col-width-range-' + index"
                                                class="flex-1 accent-indigo-600">
                                            <input type="number" min="10" max="90" step="0.1" dir="ltr"
                                                :value="columnWidthInput(width)"
                                                @focus="beginColumnWidthEdit()"
                                                @input="queueColumnWidthInput(index, $event.target.value)"
                                                :aria-label="'درصد عرض ستون ' + faDigits(index + 1)"
                                                :data-testid="'tb-col-width-input-' + index"
                                                class="w-16 text-xs text-center tabular-nums px-1.5 py-1 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                                            <span class="text-[10px] text-slate-400">٪</span>
                                        </div>
                                    </template>
                                </div>
                                <div class="flex gap-1.5 mt-2.5">
                                    <button type="button" @click="distributeColumnsEvenly()"
                                        :disabled="selectedEditorCapabilities.lockedStructure"
                                        data-testid="tb-col-widths-even"
                                        class="flex-1 text-[11px] px-2 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 disabled:opacity-40 transition">توزیع مساوی</button>
                                    <button type="button" @click="resetColumnWidths()"
                                        :disabled="selectedEditorCapabilities.lockedStructure"
                                        data-testid="tb-col-widths-reset"
                                        class="flex-1 text-[11px] px-2 py-1.5 rounded-lg border border-indigo-200 text-indigo-600 hover:bg-indigo-50 disabled:opacity-40 transition">بازگشت به نسبت</button>
                                </div>
                                <p x-show="selectedBlock?.props?.columns?.length" class="text-[10px] text-amber-600 mt-1.5" data-testid="tb-col-widths-custom">عرض دستی فعال است؛ انتخاب نسبت جدید آن را بازنشانی می‌کند.</p>
                            </div>
                        </template>

                        <div x-show="selectedTab === 'content' && Object.keys(selectedSchema).length === 0 && !selectedBindingDef" class="text-center py-6">
                            <p class="text-xs text-slate-400">این ویجت تنظیماتی ندارد</p>
                        </div>

                        {{-- TB4: Container Children Panel --}}
                        <template x-if="selectedTab === 'content' && isContainerType(selectedBlock?.type)">
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
                    </div>{{-- /tb-inspector-scroll --}}
                </div>
            </template>
        </div>
    </div>

    {{-- TB4: Add Child Block Modal — with a live search over title, type, tags and category. --}}
    @php
        // One normalized haystack per type; the query is normalized the same way (childSearchNorm).
        $childSearchIndex = [];
        foreach ($orderedCats as $catKey) {
            foreach ($byCategory[$catKey] ?? [] as $blockType => $blockDef) {
                if (!empty((($assetAccess ?? [])[$blockType] ?? [])['locked'])) continue; // ADR-087
                $haystack = implode(' ', [$blockDef['name'] ?? '', $blockType, str_replace(['_', ':'], ' ', $blockType), $catKey, $catLabels[$catKey] ?? '', implode(' ', (array) ($blockDef['tags'] ?? []))]);
                $childSearchIndex[$blockType] = trim(preg_replace('/\s+/u', ' ', str_replace(['ي', 'ك', "\u{200C}"], ['ی', 'ک', ' '], mb_strtolower($haystack))));
            }
        }
    @endphp
    <div x-show="showAddChildModal" x-transition.opacity class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
        x-data="{
            childQuery: '',
            childIndex: @js($childSearchIndex),
            childSearchNorm(s) { return String(s || '').toLowerCase().replace(/ي/g, 'ی').replace(/ك/g, 'ک').replace(/‌/g, ' ').replace(/\s+/g, ' ').trim(); },
            childMatches(type) {
                const terms = this.childSearchNorm(this.childQuery).split(' ').filter(Boolean);
                const hay = this.childIndex[type] || '';
                return terms.every(t => hay.includes(t));
            },
            get childMatchList() { return Object.keys(this.childIndex).filter(t => this.childMatches(t)); },
        }"
        x-effect="if (showAddChildModal) { childQuery = ''; $nextTick(() => $refs.childSearch?.focus()); }"
        @keydown.escape.window="if (showAddChildModal) showAddChildModal = false"
        data-testid="tb-add-child-dialog">
        <div @click.outside="showAddChildModal = false" class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-5" role="dialog" aria-modal="true" aria-label="افزودن بلوک فرزند">
            <h3 class="text-sm font-bold text-slate-800 mb-3 flex items-center gap-2">
                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                افزودن بلوک فرزند به <span class="text-indigo-600" x-text="blockLabel(selectedBlock?.type)"></span>
            </h3>
            <div class="relative mb-2">
                <svg class="absolute right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
                <input type="search" x-ref="childSearch" x-model="childQuery" dir="rtl" autocomplete="off" enterkeyhint="go"
                    placeholder="جستجوی ویجت (عنوان، نوع یا دسته)…" aria-label="جستجوی ویجت فرزند"
                    @keydown.enter.prevent="const first = childMatchList[0]; if (first) { addChildBlock(first); showAddChildModal = false; }"
                    class="tb-child-search w-full h-9 pr-8 pl-8 rounded-lg border border-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-400"
                    data-testid="tb-add-child-search">
                <button type="button" x-show="childQuery" x-cloak @click="childQuery = ''; $refs.childSearch.focus()"
                    class="absolute left-1.5 top-1/2 -translate-y-1/2 w-6 h-6 flex items-center justify-center rounded text-slate-400 hover:text-slate-600 hover:bg-slate-100"
                    aria-label="پاک کردن جستجو" data-testid="tb-add-child-search-clear">×</button>
            </div>
            <style>.tb-child-search::-webkit-search-cancel-button { -webkit-appearance: none; appearance: none; }</style>
            <p class="sr-only" aria-live="polite" x-text="childQuery ? `${childMatchList.length} ویجت` : ''"></p>
            <p x-show="childQuery && !childMatchList.length" x-cloak class="py-6 text-center text-xs text-slate-500" data-testid="tb-add-child-empty">ویجتی با این عنوان یافت نشد</p>
            <div class="grid grid-cols-2 gap-2 max-h-64 overflow-y-auto" x-show="!childQuery || childMatchList.length">
                @foreach($orderedCats as $catKey)
                    @if(!isset($byCategory[$catKey])) @continue @endif
                    @foreach($byCategory[$catKey] as $blockType => $blockDef)
                        @if(!empty((($assetAccess ?? [])[$blockType] ?? [])['locked'])) @continue @endif
                        <button
                            x-show="childMatches('{{ $blockType }}')"
                            @click="addChildBlock('{{ $blockType }}'); showAddChildModal = false"
                            data-testid="tb-add-child-{{ $blockType }}"
                            class="flex items-center gap-2 px-2 py-2 rounded-lg border border-slate-200 hover:border-indigo-400 hover:bg-indigo-50 text-xs text-right transition">
                            <span class="text-base leading-none shrink-0">
                                @switch($blockType)
                                    @case('hero') 🌟 @break @case('section') 📦 @break
                                    @case('flex_container') 📐 @break @case('grid') 🔲 @break
                                    @case('columns') ⬜ @break @case('column') ▤ @break
                                    @case('heading') 🔠 @break
                                    @case('text') 📄 @break @case('image') 🖼️ @break
                                    @case('button') 🔘 @break @case('cta') 📢 @break
                                    @case('scroll_to_top') 🔝 @break
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
                    <option value="navigation">ناوبری</option>
                    <option value="hero">هیرو</option>
                    <option value="features">ویژگی‌ها و بنتو</option>
                    <option value="product">محصول</option>
                    <option value="store">فروشگاه</option>
                    <option value="blog">وبلاگ</option>
                    <option value="testimonials">اعتماد و آمار</option>
                    <option value="pricing">قیمت‌گذاری</option>
                    <option value="faq">سؤالات متداول</option>
                    <option value="corporate">شرکتی</option>
                    <option value="contact">تماس</option>
                    <option value="gallery">گالری</option>
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
                        <button type="button" class="text-start border border-slate-200 rounded-xl overflow-hidden hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-purple-400 transition cursor-pointer group flex flex-col disabled:opacity-60 disabled:cursor-wait"
                            :data-testid="'tpl-card-' + tpl.id" :disabled="templateLibrary.inserting"
                            :aria-disabled="tpl.access && tpl.access.locked ? 'true' : 'false'"
                            :title="tpl.access && tpl.access.locked ? tpl.access.message : tpl.name"
                            @click="tpl.access && tpl.access.locked ? showToast(tpl.access.message || tpl.access.badge, 'error') : insertTemplate(tpl.id)">
                            <div class="aspect-video w-full bg-gradient-to-br from-slate-100 to-purple-50 flex items-center justify-center relative overflow-hidden">
                                <template x-if="tpl.thumbnail_url">
                                    <img :src="tpl.thumbnail_url" :alt="tpl.name" loading="lazy" class="w-full h-full object-cover transition duration-300 group-hover:scale-105">
                                </template>
                                <template x-if="!tpl.thumbnail_url">
                                    <svg class="w-8 h-8 text-purple-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5z"/></svg>
                                </template>
                                <span class="absolute top-1.5 right-1.5 text-[9px] font-bold px-1.5 py-0.5 rounded-full bg-white/90 text-purple-700" x-text="tpl.type_label"></span>
                                <span x-show="tpl.source === 'library'" class="absolute top-1.5 left-1.5 text-[9px] font-bold px-1.5 py-0.5 rounded-full bg-purple-600 text-white">آماده</span>
                                @include('theme-runtime::canvas.asset-access-template-card')
                                <div class="absolute inset-0 bg-purple-600/0 group-hover:bg-purple-600/10 transition flex items-center justify-center">
                                    <span class="opacity-0 group-hover:opacity-100 group-focus-visible:opacity-100 bg-purple-600 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition" x-text="templateLibrary.inserting ? 'در حال درج…' : 'درج'"></span>
                                </div>
                            </div>
                            <div class="p-3 w-full">
                                <p class="text-xs font-semibold text-slate-800 truncate" x-text="tpl.name"></p>
                                <p class="text-[10px] text-slate-400 mt-0.5" x-text="tpl.category_label"></p>
                                <p x-show="tpl.description" class="text-[10px] leading-relaxed text-slate-500 mt-1 line-clamp-2" x-text="tpl.description"></p>
                            </div>
                        </button>
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

    {{-- Canvas overhaul Unit 4: unified entity link picker. One modal for every
         link field; results come live from the database (EntityLinkCatalog),
         each with the permalink it resolves to right now. --}}
    <div x-show="entityPicker.open" x-cloak x-transition.opacity
         class="fixed inset-0 bg-black/50 z-50 flex items-start justify-center p-4 pt-[10vh]"
         @keydown.escape.window="entityPicker.open && closeEntityPicker()"
         @click.self="closeEntityPicker()" data-testid="tb-link-picker">
        <div class="w-full max-w-xl rounded-2xl bg-white shadow-2xl overflow-hidden" role="dialog" aria-modal="true" aria-label="انتخاب لینک">
            <div class="p-4 border-b border-slate-100 space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-800">انتخاب مقصد لینک</h3>
                    <button type="button" class="text-slate-400 hover:text-slate-700 text-lg leading-none" aria-label="بستن" @click="closeEntityPicker()">×</button>
                </div>
                <input type="search" x-model="entityPicker.query" @input.debounce.250ms="searchEntities()"
                       placeholder="جستجوی برگه، محصول، دسته، مقاله یا دوره…"
                       class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-300"
                       data-testid="tb-link-picker-search">
                <div class="flex flex-wrap gap-1.5 text-xs">
                    <button type="button" @click="entityPicker.entity = ''; searchEntities()"
                            :class="entityPicker.entity === '' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            class="px-2.5 py-1 rounded-full transition">همه</button>
                    <template x-for="(label, key) in entityPicker.entities" :key="key">
                        <button type="button" @click="entityPicker.entity = key; searchEntities()"
                                :class="entityPicker.entity === key ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                class="px-2.5 py-1 rounded-full transition" :data-testid="'tb-link-picker-tab-' + key" x-text="label"></button>
                    </template>
                </div>
            </div>
            <div class="max-h-[50vh] overflow-y-auto p-2" :aria-busy="entityPicker.loading ? 'true' : 'false'">
                <template x-for="result in entityPicker.results" :key="result.entity + ':' + result.id">
                    <button type="button" @click="pickEntity(result)"
                            class="w-full flex items-center gap-3 text-right px-3 py-2 rounded-lg hover:bg-indigo-50 focus:bg-indigo-50 focus:outline-none"
                            :data-testid="'tb-link-picker-result-' + result.entity + '-' + result.id">
                        <span class="shrink-0 w-24 text-[10px] font-bold text-indigo-700 bg-indigo-50 rounded px-1.5 py-0.5 text-center" x-text="result.entity_label"></span>
                        <span class="flex-1 min-w-0">
                            <span class="block truncate text-sm text-slate-800" x-text="result.title"></span>
                            <span class="block truncate text-[11px] text-slate-400" dir="ltr" x-text="result.url"></span>
                        </span>
                    </button>
                </template>
                <p x-show="entityPicker.loading" class="px-3 py-6 text-center text-xs text-slate-400">در حال جستجو…</p>
                <p x-show="!entityPicker.loading && entityPicker.results.length === 0" class="px-3 py-6 text-center text-xs text-slate-400">موردی یافت نشد. برگه یا محصول را در پیشخوان بسازید؛ اینجا بلافاصله نمایش داده می‌شود.</p>
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
                             @click="motionModal.selected = (motionModal.selected === asset.asset_id ? null : asset.asset_id)" :data-testid="'tb-motion-asset-' + asset.asset_id">
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
                <button x-show="motionModal.selected" @click="confirmMotionSelect()" data-testid="tb-motion-confirm" class="flex-1 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">انتخاب این موشن</button>
                <button @click="motionModal.open = false" class="flex-1 py-2 rounded-lg border border-slate-200 text-slate-600 text-sm font-medium hover:bg-slate-50 transition">بستن</button>
            </div>
        </div>
    </div>

    <div x-show="moduleChooser.open" x-transition.opacity class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" data-testid="tb-module-presentation-modal">
        <div @click.outside="moduleChooser.open=false" class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-bold text-slate-800" x-text="moduleChooser.definition?.label || 'Module'"></h3>
                    <p class="text-xs text-slate-500 mt-1">Choose a presentation supplied by the active theme.</p>
                </div>
                <button type="button" @click="moduleChooser.open=false" class="text-slate-400 text-xl">&times;</button>
            </div>
            <div class="space-y-2">
                <template x-for="presentation in (moduleChooser.definition?.presentations ?? [])" :key="presentation.type">
                    <div class="rounded-xl border border-slate-200 p-3" :data-testid="'tb-module-presentation-' + presentation.type">
                        <p class="text-sm font-semibold text-slate-800" x-text="presentation.label"></p>
                        <p class="text-[10px] text-slate-400 font-mono mt-1" x-text="presentation.type"></p>
                        <div class="flex gap-2 mt-3">
                            <button type="button" @click="insertModulePresentation(presentation, 'before')" class="flex-1 rounded-lg border border-indigo-200 px-3 py-2 text-xs text-indigo-700 hover:bg-indigo-50" data-testid="tb-module-insert-before">Insert before selection</button>
                            <button type="button" @click="insertModulePresentation(presentation, 'after')" class="flex-1 rounded-lg bg-indigo-600 px-3 py-2 text-xs text-white hover:bg-indigo-700" data-testid="tb-module-insert-after">Insert after selection</button>
                        </div>
                    </div>
                </template>
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
window.__GRANTED_ICON_PACKS__ = @json($grantedIconPacks ?? []);
</script>
{{-- Phase 6.1-B: one include = runtime -> identity (version-gated) -> interaction.
     No host structural services on this surface yet ([]), kept in sync with
     the platform's own editor pages which use the same bootstrap partial. --}}
@include('theme-runtime::canvas.color')
@include('theme-runtime::canvas.controls')
@include('theme-runtime::canvas.icons')
@include('theme-runtime::canvas.tab-strip')
@include('theme-runtime::canvas.inspect')
@include('theme-runtime::canvas.editor-bootstrap', ['canvasStructuralServices' => []])
<script>
const CSRF_TOKEN    = document.querySelector('meta[name="csrf-token"]').content;
const BLOCK_LABELS  = @json($blockLabels);
const BLOCK_SCHEMAS = @json($blockSchemas);
const MODULE_PRESENTATIONS = @json($modulePresentations);
const BINDING_CAPABILITIES = @json(\App\Services\ThemeCanvas\DataBinding\ThemeDataBindingRegistry::capabilities());
const INITIAL_PAGES = @json($pages);
const GLOBAL_STYLES = @json($globalStyles);
const GRANTED_BUTTON_PACKS = @json($grantedButtonPacks ?? []);
const GRANTED_ICON_PACKS = @json($grantedIconPacks ?? []);
if (window.CanvasIcons && Array.isArray(GRANTED_ICON_PACKS)) {
    window.CanvasIcons.loadCustomPacks(GRANTED_ICON_PACKS);
}
// Header element order controlled by the header block's `header_order` prop.
// Kept here so the drag list always offers every known element, even when the
// stored order is partial or empty.
const HEADER_ORDER_ITEMS = [
    { key: 'logo',    label: 'لوگو / برند' },
    { key: 'search',  label: 'جستجو' },
    { key: 'social',  label: 'شبکه‌های اجتماعی' },
    { key: 'phone',   label: 'شماره تماس' },
    { key: 'cta',     label: 'دکمه اقدام (CTA)' },
    { key: 'cart',    label: 'سبد خرید' },
    { key: 'account', label: 'ورود / حساب کاربری' },
    { key: 'menu',    label: 'منوی ناوبری' },
    { key: 'burger',  label: 'منوی همبرگری (موبایل)' },
    { key: 'dark_toggle', label: 'دکمه تغییر حالت تاریک' },
];
@php $modeQuery = ($canvasMode ?? 'theme') === 'maintenance' ? ['mode' => 'maintenance'] : []; @endphp
const ROUTES = {
    saveBlocks:   '{{ route('admin.theme-design.canvas-save-blocks', $modeQuery) }}',
    addPage:      '{{ route('admin.theme-design.canvas-add-page') }}',
    deletePage:   '{{ url('admin/theme-design/canvas/pages') }}',
    globalStyles: '{{ route('admin.theme-design.canvas-global-styles', $modeQuery) }}',
    publish:      '{{ route('admin.theme-design.canvas-publish', $modeQuery) }}',
    livePreview:  '{{ route('admin.theme-design.canvas-live-preview', $modeQuery) }}',
    renderBlock:  '{{ route('admin.theme-design.canvas-render-block', $modeQuery) }}',
    resetMaintenance: '{{ route('admin.theme-design.canvas-reset-maintenance') }}',
    mediaIndex:   '{{ route('admin.theme-design.media-index') }}',
    mediaUpload:  '{{ route('admin.theme-design.media-upload') }}',
    mediaDestroy: '{{ route('admin.theme-design.media-destroy') }}',
    motionAssets: '{{ route('admin.theme-design.canvas-motion-assets') }}',
    bindingEntities: '{{ route('admin.theme-design.canvas-binding-entities', ['source' => ':source']) }}',
    // TASK 3/3 Phase 6.5C (BLD-28) — reuses the SAME bounded, already-proven
    // search endpoint Menu's own "add existing Page" picker uses
    // (MenuController::searchPages()) — no second Page-search authority.
    linkEntities: '{{ route('admin.theme-design.canvas-link-entities') }}',
};

// Phase 4B.5 — block props arriving from storage as a JSON empty array
// ([]) decode to a JS Array, and x-model assignments on an Array become
// expando properties that JSON.stringify silently drops — so the first
// prop edit on such a block would never reach the renderer. Normalizing
// every props container to a plain object at state init makes the panel's
// draft mutation lossless for all block types, generically.
function normalizePageProps(pages) {
    const walk = (blocks) => {
        if (!Array.isArray(blocks)) return;
        for (const block of blocks) {
            if (!block || typeof block !== 'object') continue;
            if (!Array.isArray(block.children ?? null)) {
                if (!block.props || typeof block.props !== 'object' || Array.isArray(block.props)) block.props = {};
            }
            if (Array.isArray(block.children)) {
                walk(block.children);
                if (!block.props || typeof block.props !== 'object' || Array.isArray(block.props)) block.props = {};
            }
        }
    };
    for (const page of Object.values(pages ?? {})) {
        walk(page?.blocks);
    }
    return pages;
}

if (typeof window.CanvasControls === 'undefined' || !window.CanvasControls.editorMixin) {
    window.CanvasControls = {
        GENERIC_STYLE_SECTIONS: { typography: 1, spacing: 1, background: 1, border: 1, shadow: 1, layout: 1 },
        hiddenStyleSections: function() { return []; },
        visibleFields: function() { return []; },
        editorMixin: function() {
            return {
                ctlChecked: function() { return false; },
                panelHasFields: function() { return false; },
                genericStyleVisible: function() { return true; }
            };
        }
    };
}

function canvasEditor() {
    return {
        // ADR-082 (saas-platform): tabs, conditions, per-device values and
        // states of the Inspector (theme-runtime canvas-controls.js; ctlSetProp below).
        ...window.CanvasControls.editorMixin(),
        // ── State ──────────────────────────────────────────────
        pages:         normalizePageProps(JSON.parse(JSON.stringify(INITIAL_PAGES))),
        currentPage:   (new URLSearchParams(window.location.search).get('page') && INITIAL_PAGES[new URLSearchParams(window.location.search).get('page')])
            ? new URLSearchParams(window.location.search).get('page')
            : (Object.keys(INITIAL_PAGES)[0] ?? '__header__'),
        selectedPath:  null,          // null | [0] | [0, 1] | ...
        selectedLogicalItem: null,
        leftPanelTab:  'blocks',      // 'blocks' | 'tree' | 'health' (WU-3)
        treeExpanded:  {},
        showAddChildModal: false,
        moduleChooser: { open: false, capability: null, definition: null },
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
        // Sidebar layout: visibility, persisted widths (px) and active drag.
        showRightSidebar: true,
        showLeftSidebar:  true,
        rightSidebarWidth: 320,
        leftSidebarWidth:  360,
        sidebarDrag:       null,
        sidebarShortcutBound: false,
        globalStyles:  JSON.parse(JSON.stringify(GLOBAL_STYLES)),
        gsIsDirty:     false,
        gsSaving:      false,
        toast:         { show: false, msg: '', type: 'success', _timer: null },
        // TB3-02: iframe
        device:        'desktop',
        iframeTimestamp: Date.now(),
        inspectOn:     false,
        inspectHost:   null,
        auditSummary:  null,
        iframeLoading:  true,
        iframeReady:    false,
        propUpdateTimer: null,
        pendingPropUpdate: false,
        // Per-block render request sequence: an HTML reply that started
        // before a newer render must never replace the newer DOM.
        propRenderSeq: {},
        pendingColumnWidthsMessage: null,
        columnWidthInputTimer: null,
        columnWidthHistoryTimer: null,
        columnWidthHistoryPending: false,
        // header_order drag & drop (content tab)
        headerDragKey:     null,
        headerDragOverKey: null,
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
        entityPicker: { open: false, query: '', entity: '', results: [], entities: {}, loading: false, set: null },
        // MEDIA-4: uuid -> resolved URL, populated as the library loads/
        // uploads, so a persisted {media_id} value can render a live
        // thumbnail in the property panel without a DB round trip per
        // keystroke. Canvas/public rendering resolves independently and
        // authoritatively server-side (CustomerMediaResolver) regardless.
        mediaUrlCache: {},
        motionModal: { open: false, assets: [], locked: [], loading: false, selected: null, propKey: null, loaded: false },
        // TB6: template library
        showTemplateLibrary: false,
        showSaveAsTemplate: false,
        templateLibrary: { loading: false, inserting: false, templates: [], type: '', category: '', search: '' },
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
            // A `blocks` field is the container's child list, authored in
            // the children panel — it has no content-tab control.
            const schema = BLOCK_SCHEMAS[this.selectedBlock.type]?.schema ?? {};
            return Object.fromEntries(Object.entries(schema).filter(([, def]) => def?.type !== 'blocks'));
        },
        // header_order — the element order as a stable, complete list: the
        // stored order first (unknown keys preserved), then every known header
        // element the stored value does not mention yet. An empty stored value
        // shows the schema default, which is also what the renderer falls back
        // to, so the list always matches the rendered header.
        headerOrderItems(propKey) {
            let stored = Array.isArray(this.selectedBlock?.props?.[propKey]) ? this.selectedBlock.props[propKey] : [];
            if (stored.length === 0) {
                const fallback = BLOCK_SCHEMAS[this.selectedBlock?.type]?.schema?.[propKey]?.default;
                stored = Array.isArray(fallback) ? fallback : [];
            }
            const labels = Object.fromEntries(HEADER_ORDER_ITEMS.map(i => [i.key, i.label]));
            const items = [];
            const seen = new Set();
            for (const raw of stored) {
                const key = String(raw);
                if (key === '' || seen.has(key)) continue;
                seen.add(key);
                items.push({ key, label: labels[key] ?? key });
            }
            for (const item of HEADER_ORDER_ITEMS) {
                if (!seen.has(item.key)) items.push({ ...item });
            }
            return items;
        },

        // ADR-076 section headers (fieldGroupStart) come from the
        // CanvasControls mixin, computed over the active tab's visible fields.
        // ADR-082: the mixin's prop writer, the same path as the x-model
        // controls (props write + live preview); `undefined` drops a device
        // value back to inheritance.
        ctlSetProp(key, value) {
            if (!this.selectedBlock) return;
            if (!this.selectedBlock.props || typeof this.selectedBlock.props !== 'object' || Array.isArray(this.selectedBlock.props)) {
                this.selectedBlock.props = {};
            }
            if (value === undefined) delete this.selectedBlock.props[key];
            else this.selectedBlock.props[key] = value;
            this.schedulePropUpdate();
        },

        // ── Column widths: Inspector ⇄ Canvas ─────────────────
        // Both surfaces read and write props.columns[i].width (percent). A
        // drag lands here through the `columnResize` message; a number or
        // slider edit goes out as `setColumnWidths` so the preview follows
        // before the debounced block re-render.
        columnWidthCount(block = this.selectedBlock) {
            if (!block) return 0;
            const children = Array.isArray(block.children) ? block.children : [];
            if (children.length === 0) {
                const ratios = String(block.props?.ratios ?? '').trim();
                return ratios ? Math.max(1, ratios.split(',').length) : 2;
            }
            // Only DIRECT children of the row are columns. A widget dropped
            // inside a `column` is that column's child, never a new track.
            // Rows authored before multi-child columns are still flat (their
            // direct children are the cells), so they keep one track each.
            const wrapped = children.filter(child => child?.type === 'column').length;
            if (wrapped > 0 && wrapped === children.length) return wrapped;
            return children.length;
        },
        columnRatioWidths(block = this.selectedBlock) {
            const count = this.columnWidthCount(block);
            if (count < 1) return [];
            if (count === 1) return [100];
            const parts = String(block?.props?.ratios ?? '').split(',').map(part => part.trim()).filter(Boolean);
            const fractions = [];
            for (const part of parts) {
                if (part === '1') { fractions.push(1); continue; }
                const match = part.match(/^(\d+(?:\.\d+)?)\/(\d+(?:\.\d+)?)$/);
                if (!match) return new Array(count).fill(100 / count);
                const numerator = Number(match[1]);
                const denominator = Number(match[2]);
                if (!(denominator > 0) || !(numerator > 0) || numerator > denominator) {
                    return new Array(count).fill(100 / count);
                }
                fractions.push(numerator / denominator);
            }
            if (fractions.length !== count) return new Array(count).fill(100 / count);
            const total = fractions.reduce((sum, value) => sum + value, 0);
            const widths = fractions.map(value => Math.round((value / total) * 1000) / 10);
            const drift = Math.round((100 - widths.reduce((sum, value) => sum + value, 0)) * 10) / 10;
            widths[widths.length - 1] = Math.round((widths[widths.length - 1] + drift) * 10) / 10;
            return widths;
        },
        columnWidths() {
            const count = this.columnWidthCount();
            if (count < 1) return [];
            const stored = this.selectedBlock?.props?.columns;
            if (Array.isArray(stored) && stored.length === count) {
                const widths = stored.map(column => Number(column && column.width));
                if (widths.every(width => Number.isFinite(width) && width > 0)) {
                    return this.clampColumnWidths(widths);
                }
            }
            return this.columnRatioWidths();
        },
        clampColumnWidths(widths) {
            if (widths.length <= 1) return [100];
            const minimum = 10;
            const maximum = 100 - minimum * (widths.length - 1);
            const next = widths.map(width => Math.round(Math.min(maximum, Math.max(minimum, width)) * 10) / 10);
            const drift = Math.round((100 - next.reduce((sum, width) => sum + width, 0)) * 10) / 10;
            const fixIndex = next.length - 1;
            next[fixIndex] = Math.round((next[fixIndex] + drift) * 10) / 10;
            if (next[fixIndex] < minimum) {
                const deficit = minimum - next[fixIndex];
                next[fixIndex] = minimum;
                let largest = 0;
                next.forEach((width, index) => { if (width > next[largest]) largest = index; });
                next[largest] = Math.round((next[largest] - deficit) * 10) / 10;
            }
            return next;
        },
        columnWidthTotal() {
            return Math.round(this.columnWidths().reduce((sum, width) => sum + width, 0) * 10) / 10;
        },
        columnWidthInput(width) {
            return Math.round(Number(width) * 10) / 10;
        },
        faDigits(value) {
            return String(value).replace(/[0-9]/g, digit => '۰۱۲۳۴۵۶۷۸۹'[Number(digit)]);
        },
        // Typing is debounced so a partial number never reflows the row.
        queueColumnWidthInput(index, value) {
            if (value === '' || value === null) return;
            clearTimeout(this.columnWidthInputTimer);
            const blockId = this.selectedBlock?.id;
            this.columnWidthInputTimer = setTimeout(() => {
                if (!this.selectedBlock || this.selectedBlock.id !== blockId) return;
                this.setColumnWidth(index, value);
            }, 200);
        },
        cancelColumnWidthInput() {
            clearTimeout(this.columnWidthInputTimer);
            this.columnWidthInputTimer = null;
        },
        setColumnWidth(index, value) {
            const widths = this.columnWidths();
            const count = widths.length;
            if (count < 2 || index < 0 || index >= count) return;
            if (value === '' || value === null) return;
            const parsed = Number(value);
            if (!Number.isFinite(parsed)) return;
            const minimum = 10;
            const target = Math.round(
                Math.min(100 - minimum * (count - 1), Math.max(minimum, parsed)) * 10
            ) / 10;
            const others = widths.map((width, position) => position === index ? 0 : Math.max(minimum, width));
            const othersTotal = others.reduce((sum, width) => sum + width, 0);
            const remaining = 100 - target;
            const next = others.map((width, position) => {
                if (position === index) return target;
                return othersTotal > 0 ? (width / othersTotal) * remaining : remaining / (count - 1);
            });
            this.commitColumnWidths(this.clampColumnWidths(next));
        },
        commitColumnWidths(widths) {
            if (!this.selectedBlock) return;
            // One undo entry per editing burst: a slider fires many input
            // events, but Ctrl+Z should return to the layout before the
            // gesture started, not replay every intermediate step.
            if (!this.columnWidthHistoryPending) {
                this.pushHistory();
                this.columnWidthHistoryPending = true;
            }
            clearTimeout(this.columnWidthHistoryTimer);
            this.columnWidthHistoryTimer = setTimeout(() => { this.columnWidthHistoryPending = false; }, 1200);
            this.pushColumnWidths(widths);
            this.ctlSetProp('columns', widths.map(width => ({ width })));
            clearTimeout(this.inlineAutoSaveTimer);
            this.inlineAutoSaveTimer = setTimeout(() => this.autoSave(), 1500);
        },
        distributeColumnsEvenly() {
            const count = this.columnWidthCount();
            if (count < 2) return;
            this.cancelColumnWidthInput();
            this.commitColumnWidths(new Array(count).fill(100 / count));
        },
        resetColumnWidths() {
            if (!this.selectedBlock) return;
            this.cancelColumnWidthInput();
            this.pushHistory();
            this.pushColumnWidths(this.columnRatioWidths());
            this.ctlSetProp('columns', undefined);
            clearTimeout(this.inlineAutoSaveTimer);
            this.inlineAutoSaveTimer = setTimeout(() => this.autoSave(), 1500);
        },
        beginColumnWidthEdit() {
            this.columnWidthHistoryPending = false;
        },
        columnWidthMessage(blockId, widths) {
            return {
                action: 'setColumnWidths',
                blockId,
                columns: widths.map(width => ({ width: Math.round(Number(width) * 10) / 10 })),
            };
        },
        pushColumnWidths(widths) {
            if (!this.selectedBlock) return;
            const message = this.columnWidthMessage(this.selectedBlock.id, widths);
            if (!this.iframeReady) { this.pendingColumnWidthsMessage = message; return; }
            this.sendToIframe(message);
        },

        get selectedEditorCapabilities() {
            return this.selectedBlock
                ? (BLOCK_SCHEMAS[this.selectedBlock.type]?.editor ?? { allowCustomCss: true, lockedStructure: false, uiDirection: null })
                : { allowCustomCss: true, lockedStructure: false, uiDirection: null };
        },
        // Phase 4B.6 — generic manual/dynamic content-source contract.
        // A block supports manual mode when its registered type declares
        // dataBinding.manual_prop naming the manual-items prop. Switching
        // source never destroys the other side's data (manual props and
        // binding live in separate keys).
        get selectedManualPropKey() {
            const type = this.selectedBlock?.type;
            if (!type || !this.selectedBlock) return null;
            const manual = BLOCK_SCHEMAS[type]?.dataBinding?.manual_prop;
            return typeof manual === 'string' && manual !== '' ? manual : null;
        },
        setContentSource(mode) {
            if (!this.selectedBlock || !this.selectedManualPropKey) return;
            if (mode === 'dynamic') {
                if (!this.selectedBlock.binding) this.toggleBinding(true);
            } else {
                if (this.selectedBlock.binding) this.toggleBinding(false);
            }
        },
        get selectedBindingDef() {
            if (!this.selectedBlock) return null;
            return BLOCK_SCHEMAS[this.selectedBlock.type]?.dataBinding ?? null;
        },
        get bindingEnabled() { return !!this.selectedBlock?.binding; },
        get bindingSortOptions() {
            return BINDING_CAPABILITIES[this.selectedBindingDef?.source]?.sorts ?? [];
        },
        get bindingSelectedIds() { return (this.selectedBlock?.binding?.query?.selected_ids ?? []).join(','); },
        bindingEntities: [],
        bindingEntitiesLoading: false,
        bindingEntitiesSource: null,
        // Phase 4B.5 — lazy-load entity options once per source, when a
        // block with a manual-selection-capable binding gets selected.
        get bindingEntitiesActive() {
            const source = this.selectedBindingDef?.source;
            if (source && this.bindingEntitiesSource !== source) this.fetchBindingEntities(source);
            return source ?? null;
        },
        toggleBinding(enabled) {
            if (!this.selectedBlock || !this.selectedBindingDef) return;
            if (!enabled) {
                delete this.selectedBlock.binding;
            } else {
                this.selectedBlock.binding = {
                    source: this.selectedBindingDef.source,
                    query: { sort: this.selectedBindingDef.default_sort ?? 'catalog_order', limit: BINDING_CAPABILITIES[this.selectedBindingDef.source]?.default_limit ?? 10 },
                    empty: 'hide'
                };
            }
            this.schedulePropUpdate();
        },
        updateBindingQuery(key, value) {
            if (!this.selectedBlock?.binding?.query) return;
            if (value === null || value === '') delete this.selectedBlock.binding.query[key];
            else this.selectedBlock.binding.query[key] = value;
            this.schedulePropUpdate();
        },
        updateBindingSelectedIds(value) {
            const ids = String(value).split(',').map(id => Number(id.trim())).filter(id => Number.isInteger(id) && id > 0).slice(0, 24);
            this.updateBindingQuery('selected_ids', ids);
        },
        // Phase 4B.5 — generic tenant-safe entity picker for binding manual
        // selection. Options come from the server's allowlisted read-only
        // endpoint (same published/tenant rules the public resolver uses),
        // so users pick human-readable names instead of raw numeric IDs.
        get bindingSelectedLabels() {
            const ids = this.selectedBlock?.binding?.query?.selected_ids ?? [];
            return ids.map((id) => this.bindingEntities.find((e) => e.id === id)?.label ?? ('#' + id)).join('، ');
        },
        toggleBindingEntity(id, checked) {
            if (!this.selectedBlock?.binding?.query) return;
            const ids = [...(this.selectedBlock.binding.query.selected_ids ?? [])];
            const index = ids.indexOf(id);
            if (checked && index === -1) ids.push(id);
            if (!checked && index !== -1) ids.splice(index, 1);
            this.updateBindingQuery('selected_ids', ids.slice(0, 24));
        },
        async fetchBindingEntities(source) {
            if (!source || this.bindingEntitiesSource === source) return;
            this.bindingEntitiesSource = source;
            this.bindingEntitiesLoading = true;
            try {
                const r = await fetch(ROUTES.bindingEntities.replace(':source', encodeURIComponent(source)),
                    { headers: { 'Accept': 'application/json' } });
                const d = await r.json();
                this.bindingEntities = Array.isArray(d.entities) ? d.entities : [];
            } catch (_) {
                this.bindingEntities = [];
            } finally {
                this.bindingEntitiesLoading = false;
            }
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
        isBlockVisible(type, name, desc = '') {
            if (!this.librarySearch) return true;
            const s = this.librarySearch.toLowerCase();
            return type.toLowerCase().includes(s) || name.toLowerCase().includes(s) || (desc && desc.toLowerCase().includes(s));
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
        openModuleCapability(capability) {
            const definition = MODULE_PRESENTATIONS[capability];
            if (!definition || !Array.isArray(definition.presentations) || definition.presentations.length === 0) return;
            this.moduleChooser = { open: true, capability, definition };
        },
        insertModulePresentation(presentation, position) {
            if (!presentation?.type || !BLOCK_SCHEMAS[presentation.type]) return;
            this.pushHistory();
            const block = this.makeBlock(presentation.type);
            const capability = BINDING_CAPABILITIES[presentation.source] ?? {};
            const allowedSorts = capability.sorts ?? [];
            const requestedSort = presentation.default_sort || 'catalog_order';
            block.binding = {
                source: presentation.source,
                query: {
                    sort: allowedSorts.includes(requestedSort) ? requestedSort : (allowedSorts[0] || 'catalog_order'),
                    limit: capability.default_limit ?? 10,
                },
                empty: 'manual',
            };

            const blocks = [...this.blocks];
            const selectedTopIndex = this.selectedPath?.[0];
            let index = Number.isInteger(selectedTopIndex)
                ? selectedTopIndex + (position === 'before' ? 0 : 1)
                : blocks.length;
            index = Math.max(0, Math.min(index, blocks.length));
            blocks.splice(index, 0, block);
            this.pages[this.currentPage].blocks = blocks;
            this.selectedPath = [index];
            this.moduleChooser = { open: false, capability: null, definition: null };
            this.isDirty = true;
            this.autoSaveAndReload();
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
        persistStructuredItems(propKey, schemaDef, items, structuralChange = false) {
            if (!this.selectedBlock) return;
            this.selectedBlock.props[propKey] = schemaDef?.storage === 'json'
                ? JSON.stringify(items)
                : items;
            if (structuralChange && BLOCK_SCHEMAS[this.selectedBlock.type]?.liveStructureUpdate === 'document') {
                this.isDirty = true;
                this.autoSaveAndReload();
            } else {
                this.schedulePropUpdate();
            }
        },
        newRepeaterId() {
            return 'item_' + (globalThis.crypto?.randomUUID?.().replaceAll('-', '') ?? (Date.now().toString(36) + Math.random().toString(36).slice(2)));
        },
        newRepeaterItem(fields) {
            const item = { _id: this.newRepeaterId() };
            for (const [fieldKey, fieldDef] of Object.entries(fields ?? {})) item[fieldKey] = fieldDef?.default ?? '';
            return item;
        },
        repeaterItemKey(item, index) {
            if (typeof item?._id === 'string' && /^[A-Za-z0-9_-]{1,80}$/.test(item._id)) return item._id;
            const source = `${index}|${JSON.stringify(item ?? {})}`;
            let hash = 2166136261;
            for (let i = 0; i < source.length; i++) hash = Math.imul(hash ^ source.charCodeAt(i), 16777619);
            return `legacy_${(hash >>> 0).toString(16)}`;
        },
        addStructuredItem(propKey, schemaDef) {
            const item = this.newRepeaterItem(schemaDef?.fields);
            this.persistStructuredItems(propKey, schemaDef, [...this.structuredItems(propKey, schemaDef), item], true);
        },
        duplicateStructuredItem(propKey, itemIndex, schemaDef) {
            const items = this.structuredItems(propKey, schemaDef).map(item => ({ ...item }));
            if (!items[itemIndex]) return;
            const duplicate = JSON.parse(JSON.stringify(items[itemIndex]));
            duplicate._id = this.newRepeaterId();
            items.splice(itemIndex + 1, 0, duplicate);
            this.persistStructuredItems(propKey, schemaDef, items, true);
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
            this.persistStructuredItems(propKey, schemaDef, items, true);
        },
        moveStructuredItem(propKey, itemIndex, direction, schemaDef) {
            const items = [...this.structuredItems(propKey, schemaDef)];
            const target = itemIndex + direction;
            if (target < 0 || target >= items.length) return;
            [items[itemIndex], items[target]] = [items[target], items[itemIndex]];
            this.persistStructuredItems(propKey, schemaDef, items, true);
        },
        // BLD-11..13: one explicit, bounded extra repeater level for the one
        // genuinely nested field (nav_footer.columns[].links) — deliberately
        // not a generic recursive mechanism; these operate on
        // structuredItems()[itemIndex][fieldKey] directly.
        // Phase 0.2b (ADR-061): a nested list may still be a legacy JSON
        // string, and older content stores scalar items (["a", "b"] for
        // pricing features). Both are presented in the declared shape —
        // scalars as {firstField: value}, which every renderer accepts — so
        // copying an item before an edit never spreads a string into
        // {0: "a", 1: "b", …}.
        listItems(raw, def) {
            let items = raw;
            if (typeof items === 'string' && items.trim() !== '') {
                try { items = JSON.parse(items); } catch (_) { items = []; }
            }
            if (!Array.isArray(items)) return [];
            const key = Object.keys(def?.fields ?? {})[0];
            return key ? items.map(it => (typeof it === 'string' || typeof it === 'number') ? { [key]: it } : it) : items;
        },
        nestedSchema(propKey, fieldKey, deepFieldKey = null) {
            const def = this.selectedSchema?.[propKey]?.fields?.[fieldKey];
            return deepFieldKey === null ? def : def?.fields?.[deepFieldKey];
        },
        nestedItems(propKey, itemIndex, fieldKey) {
            const items = this.structuredItems(propKey);
            return this.listItems(items?.[itemIndex]?.[fieldKey], this.nestedSchema(propKey, fieldKey));
        },
        persistNestedItems(propKey, itemIndex, fieldKey, nestedItems, schemaDef) {
            const items = this.structuredItems(propKey).map(it => ({ ...it }));
            if (!items[itemIndex]) return;
            items[itemIndex] = { ...items[itemIndex], [fieldKey]: nestedItems };
            this.persistStructuredItems(propKey, schemaDef, items);
        },
        addNestedItem(propKey, itemIndex, fieldKey, nestedSchemaDef, schemaDef) {
            const item = this.newRepeaterItem(nestedSchemaDef?.fields);
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
            return this.listItems(nested?.[nestedIndex]?.[deepFieldKey], this.nestedSchema(propKey, fieldKey, deepFieldKey));
        },
        // A link field's editable URL: plain strings as-is, structured
        // {url, …} links by their url; edits keep whichever shape exists.
        linkFieldUrl(value) {
            return (value && typeof value === 'object') ? (value.url ?? '') : (value ?? '');
        },
        linkFieldValue(current, url) {
            return (current && typeof current === 'object' && !Array.isArray(current)) ? { ...current, url } : url;
        },
        isTruthyField(value) {
            return value === true || value === 1 || value === '1' || value === 'true';
        },
        persistDeepNestedItems(propKey, itemIndex, fieldKey, nestedIndex, deepFieldKey, deepItems, schemaDef) {
            const nested = this.nestedItems(propKey, itemIndex, fieldKey).map(it => ({ ...it }));
            if (!nested[nestedIndex]) return;
            nested[nestedIndex] = { ...nested[nestedIndex], [deepFieldKey]: deepItems };
            this.persistNestedItems(propKey, itemIndex, fieldKey, nested, schemaDef);
        },
        addDeepNestedItem(propKey, itemIndex, fieldKey, nestedIndex, deepFieldKey, deepSchemaDef, schemaDef) {
            const item = this.newRepeaterItem(deepSchemaDef?.fields);
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
        // Phase 0.5 (ADR-064): the swatches and the colour value model are the
        // shared theme-runtime CanvasColor (canvas-color.js), same as the
        // platform Canvas.
        themePalette() {
            return window.CanvasColor.palette(this.globalStyles);
        },
        colorParse(value) { return window.CanvasColor.parse(value, this.themePalette()); },
        colorPreview(value) { return window.CanvasColor.previewCss(value, this.themePalette()); },
        selectThemeColor(propKey, color) {
            if (!this.selectedBlock) return;
            // A token reference with the default-palette fallback:
            // ThemeTokenNormalizer remains the authority that emits each
            // --tb-* token, and a page without it still gets a colour.
            const current = this.colorParse(this.selectedBlock.props[propKey]);
            const alpha = ['token', 'hex', 'rgba'].includes(current.kind) ? current.alpha : 100;
            this.selectedBlock.props[propKey] = window.CanvasColor.swatch(color.token, alpha);
            this.schedulePropUpdate();
        },
        setColorBase(propKey, hex) {
            if (!this.selectedBlock) return;
            const current = this.colorParse(this.selectedBlock.props[propKey]);
            const alpha = ['token', 'hex', 'rgba'].includes(current.kind) ? current.alpha : 100;
            this.selectedBlock.props[propKey] = window.CanvasColor.fixed(hex, alpha);
            this.schedulePropUpdate();
        },
        setColorAlpha(propKey, alpha) {
            if (!this.selectedBlock) return;
            this.selectedBlock.props[propKey] = window.CanvasColor.withAlpha(this.selectedBlock.props[propKey] ?? '', alpha, this.themePalette());
            this.schedulePropUpdate();
        },
        clearColor(propKey, defaultValue = '') {
            if (!this.selectedBlock) return;
            this.selectedBlock.props[propKey] = defaultValue;
            this.schedulePropUpdate();
        },
        colorInputValue(value) {
            // A swatch or rgba() value shows its base colour in the native picker.
            return window.CanvasColor.inputHex(value, this.themePalette());
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
                const inserted = this.wrapForColumnRow(container, block);
                container.children.push(inserted);
                const childIndex = container.children.length - 1;
                // A wrapped widget is selected itself, not its new column.
                this.selectedPath = inserted === block
                    ? [...parentPath, childIndex]
                    : [...parentPath, childIndex, 0];
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

        // A `columns` row's direct children ARE its tracks, so every new
        // insertion becomes a `column` wrapper: the track/width-slider count
        // then only changes when a column is actually added or removed, and
        // several widgets can stack inside that column. Rows authored before
        // wrapper support stay flat (their direct children are the cells) so
        // they keep rendering and counting exactly as stored.
        wrapForColumnRow(container, block) {
            if (container?.type !== 'columns' || block?.type === 'column') return block;
            const siblings = Array.isArray(container.children) ? container.children : [];
            if (siblings.length > 0 && !siblings.every(child => child?.type === 'column')) return block;
            const column = this.makeBlock('column');
            column.children = [block];
            return column;
        },

        // Block Library (Phase 11 follow-up): canonical insertion for catalog
        // presets — makeBlock() supplies the real type's identity, schema
        // defaults, and container semantics, then the preset's replaceable
        // mock props are merged on top (preset values win; unspecified keys
        // keep the type's own defaults). No second content tree, no fake
        // types — the preset's block_type must exist in BLOCK_SCHEMAS exactly
        // like every palette button. Same pushHistory/select/save contract
        // as addBlock() above.
        addLibraryBlock(preset) {
            if (!preset?.block_type || !BLOCK_SCHEMAS[preset.block_type]) return;
            this.pushHistory();
            const block = this.makeBlock(preset.block_type);
            block.props = { ...block.props, ...(preset.props ?? {}) };
            const arr = [...this.blocks];
            arr.push(block);
            this.pages[this.currentPage].blocks = arr;
            this.selectedPath = [arr.length - 1];
            this.isDirty = true;
            this.autoSaveAndReload();
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
            if (!this.selectedPath || this.selectedEditorCapabilities.lockedStructure) return;
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
            if (!this.selectedPath || this.selectedEditorCapabilities.lockedStructure) return;
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
            if (path && path.length > 0) this.showLeftSidebar = true;
            if (this.selectedBlock) this.sendToIframe({ action: 'select', blockId: this.selectedBlock.id });
        },

        moveSelectedUp() {
            if (!this.selectedPath || this.selectedEditorCapabilities.lockedStructure) return;
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
            if (!this.selectedPath || this.selectedEditorCapabilities.lockedStructure) return;
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

        schedulePropUpdate(propKey = null, value = null) {
            // Optional explicit prop write (used by reordering controls such as
            // header_order) so a whole replacement array is assigned through one
            // reactive update; every existing zero-argument call is unchanged.
            if (propKey && this.selectedBlock) {
                this.selectedBlock.props[propKey] = value;
            }
            this.isDirty = true;
            clearTimeout(this.propUpdateTimer);
            this.propUpdateTimer = setTimeout(() => this.pushPropUpdate(), 350);
        },

        async pushPropUpdate() {
            if (!this.selectedBlock) return;
            // Phase 4B.6 — the live preview of a content-rich page takes
            // seconds to boot, and 'ready' arrives after @load. A prop edit
            // made in that window used to be SILENTLY DROPPED here (the
            // early return), so the user's first edit never appeared until
            // some later reload re-read the draft. Queue it and flush on
            // ready instead of discarding it.
            if (!this.iframeReady) {
                this.pendingPropUpdate = true;
                return;
            }
            const blockId = this.selectedBlock.id;
            const requestId = (this.propRenderSeq[blockId] ?? 0) + 1;
            this.propRenderSeq[blockId] = requestId;
            const snapshot = JSON.stringify(this.selectedBlock);
            try {
                const blockPayload = JSON.parse(JSON.stringify(this.selectedBlock)); // includes .style if set
                const r = await fetch(ROUTES.renderBlock, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                    body: JSON.stringify({ block: blockPayload })
                });
                const d = await r.json();
                // A slow reply for an older value must not overwrite a newer
                // edit (column widths made this visible as a 50/50 flash
                // after restoring 60/40).
                if (this.propRenderSeq[blockId] !== requestId) return;
                const path = this.findBlockPath(blockId);
                const current = path ? this.getBlockByPath(path) : null;
                if (!current || JSON.stringify(current) !== snapshot) return;
                if (d.html) this.sendToIframe({ action: 'updateBlock', blockId, html: d.html });
            } catch (_) {}
        },

        // ── Page Management ────────────────────────────────────
        pageDisplayName(key, title) {
            const rawKey = String(key || '').trim();
            const MAP = {
                '__header__': 'هدر سایت',
                '__footer__': 'فوتر سایت',
                'header': 'هدر سایت',
                'footer': 'فوتر سایت',
                'home': 'صفحه اصلی',
                'login': 'ورود به حساب',
                'register': 'ثبت‌نام',
            };
            if (MAP[rawKey]) return MAP[rawKey];
            if (title && String(title).trim() !== '' && !String(title).startsWith('__')) return String(title).trim();
            if (rawKey.startsWith('post_')) return 'مطلب: ' + rawKey.replace('post_', '');
            if (rawKey.startsWith('product_')) return 'محصول: ' + rawKey.replace('product_', '');
            if (rawKey.startsWith('page_')) return 'برگه: ' + rawKey.replace('page_', '');
            return rawKey || 'صفحه بدون نام';
        },

        async switchPage(key) {
            if (this.currentPage === key) return;
            if (this.isDirty) await this.saveCurrentPage();
            this.currentPage  = key;
            try {
                const u = new URL(window.location.href);
                u.searchParams.set('page', key);
                window.history.replaceState({}, '', u.toString());
            } catch (_) {}
            this.selectedPath = null;
            this.selectedTab  = 'content';
            // Reset history for new page
            this.history      = [JSON.parse(JSON.stringify(this.pages[key]?.blocks ?? []))];
            this.historyIndex = 0;
            this.reloadIframe();
            if (key === '__header__' || key === '__footer__') {
                this.showLeftSidebar = true;
                if (!this.pages[key]) {
                    this.pages[key] = { title: key === '__header__' ? 'هدر سایت' : 'فوتر سایت', slug: key, blocks: [] };
                }
                if (Array.isArray(this.pages[key].blocks) && this.pages[key].blocks.length > 0) {
                    this.selectedPath = [0];
                    this.$nextTick(() => {
                        this.selectedPath = [0];
                        this.selectedTab = 'content';
                        if (this.selectedBlock) {
                            this.sendToIframe({ action: 'select', blockId: this.selectedBlock.id });
                        }
                    });
                }
            }
        },

        async selectHeaderNode() {
            this.showLeftSidebar = true;
            if (this.currentPage !== '__header__') {
                await this.switchPage('__header__');
            }
            if (!this.pages['__header__']) {
                this.pages['__header__'] = { title: 'هدر سایت', slug: '__header__', blocks: [] };
            }
            if (Array.isArray(this.pages['__header__'].blocks) && this.pages['__header__'].blocks.length > 0) {
                this.selectedPath = [0];
                this.selectedTab = 'content';
                this.$nextTick(() => {
                    this.selectedPath = [0];
                    this.selectedTab = 'content';
                    if (this.selectedBlock) {
                        this.sendToIframe({ action: 'select', blockId: this.selectedBlock.id });
                    }
                });
            }
        },

        async selectFooterNode() {
            this.showLeftSidebar = true;
            if (this.currentPage !== '__footer__') {
                await this.switchPage('__footer__');
            }
            if (!this.pages['__footer__']) {
                this.pages['__footer__'] = { title: 'فوتر سایت', slug: '__footer__', blocks: [] };
            }
            if (Array.isArray(this.pages['__footer__'].blocks) && this.pages['__footer__'].blocks.length > 0) {
                this.selectedPath = [0];
                this.selectedTab = 'content';
                this.$nextTick(() => {
                    this.selectedPath = [0];
                    this.selectedTab = 'content';
                    if (this.selectedBlock) {
                        this.sendToIframe({ action: 'select', blockId: this.selectedBlock.id });
                    }
                });
            }
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
                const r = await fetch(ROUTES.saveBlocks, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                    body: JSON.stringify({ page_key: this.currentPage, blocks: this.blocks })
                });
                // A refused save (locked structure, ADR-087 locked widget)
                // stays dirty and says why, instead of silently dropping.
                if (r.ok) {
                    this.isDirty = false;
                } else {
                    const d = await r.json().catch(() => ({}));
                    this.showToast(d.error ?? 'ذخیرهٔ خودکار انجام نشد.', 'error');
                }
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

        async resetMaintenanceTemplate() {
            if (!confirm('صفحه تعمیرات به قالب حرفه‌ای پیش‌فرض بازنشانی شود؟')) return;
            try {
                const r = await fetch(ROUTES.resetMaintenance, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                });
                const d = await r.json();
                if (d.success && d.blocks) {
                    this.pages.maintenance = { ...(this.pages.maintenance || {}), blocks: d.blocks };
                    this.currentPage = 'maintenance';
                    this.isDirty = false;
                    this.reloadIframe();
                    this.showToast('قالب حرفه‌ای تعمیرات بارگذاری شد ✓', 'success');
                }
            } catch {
                this.showToast('خطا در بازنشانی قالب تعمیرات', 'error');
            }
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
                if (d.success) { this.gsIsDirty = false; this.showToast('استایل‌های سراسری ذخیره شد ✓', 'success'); this.reloadIframe(); }
                else this.showToast(d.error ?? 'خطا', 'error');
            } catch { this.showToast('خطای شبکه', 'error'); }
            finally { this.gsSaving = false; }
        },

        onGlobalButtonPresetChange(val) {
            this.gsIsDirty = true;
            if (!val) {
                this.globalStyles.active_button_pack = null;
                this.globalStyles.active_button_preset = null;
                this.globalStyles.active_button_version = null;
                this.globalStyles.active_button_tokens = null;
                this.applyLiveGlobalButtonTokens(null);
                return;
            }
            const [packSlug, presetSlug] = val.split('|');
            const pack = (GRANTED_BUTTON_PACKS || []).find(p => p.slug === packSlug);
            const preset = pack ? (pack.presets || []).find(pr => pr.slug === presetSlug) : null;
            if (pack && preset) {
                this.globalStyles.active_button_pack = pack.slug;
                this.globalStyles.active_button_preset = preset.slug;
                this.globalStyles.active_button_version = pack.current_version || '1.0.0';
                this.globalStyles.active_button_tokens = preset.css_tokens || {};
                this.applyLiveGlobalButtonTokens(preset.css_tokens);
            }
        },

        rollbackGlobalButtonPreset() {
            this.onGlobalButtonPresetChange('');
        },

        applyLiveGlobalButtonTokens(tokens) {
            const iframe = document.getElementById('previewIframe');
            if (!iframe || !iframe.contentDocument) return;
            const doc = iframe.contentDocument;
            const root = doc.documentElement;
            const vars = [
                '--tb-btn-global-padding', '--tb-btn-global-radius', '--tb-btn-global-font-weight',
                '--tb-btn-global-font-size', '--tb-btn-global-border', '--tb-btn-global-shadow',
                '--tb-btn-global-bg', '--tb-btn-global-text', '--tb-btn-global-hover-bg', '--tb-btn-global-hover-text'
            ];
            if (!tokens) {
                vars.forEach(v => root.style.removeProperty(v));
                return;
            }
            if (tokens.padding) root.style.setProperty('--tb-btn-global-padding', tokens.padding);
            if (tokens.border_radius) root.style.setProperty('--tb-btn-global-radius', tokens.border_radius);
            if (tokens.font_weight) root.style.setProperty('--tb-btn-global-font-weight', tokens.font_weight);
            if (tokens.font_size) root.style.setProperty('--tb-btn-global-font-size', tokens.font_size);
            if (tokens.border) root.style.setProperty('--tb-btn-global-border', tokens.border);
            if (tokens.shadow) root.style.setProperty('--tb-btn-global-shadow', tokens.shadow);
            if (tokens.background) root.style.setProperty('--tb-btn-global-bg', tokens.background);
            if (tokens.text_color) root.style.setProperty('--tb-btn-global-text', tokens.text_color);
            if (tokens.hover_styles?.background) root.style.setProperty('--tb-btn-global-hover-bg', tokens.hover_styles.background);
            if (tokens.hover_styles?.text_color) root.style.setProperty('--tb-btn-global-hover-text', tokens.hover_styles.text_color);
        },

        onCustomButtonPresetChange(val) {
            if (!val) {
                this.selectedBlock.props.pack_slug = '';
                this.selectedBlock.props.preset_slug = '';
                this.selectedBlock.props.asset_version = '1.0.0';
                this.selectedBlock.props.preset_snapshot = null;
                this.schedulePropUpdate();
                return;
            }
            const [packSlug, presetSlug] = val.split('|');
            const pack = (GRANTED_BUTTON_PACKS || []).find(p => p.slug === packSlug);
            const preset = pack ? (pack.presets || []).find(pr => pr.slug === presetSlug) : null;
            if (pack && preset) {
                this.selectedBlock.props.pack_slug = pack.slug;
                this.selectedBlock.props.preset_slug = preset.slug;
                this.selectedBlock.props.asset_version = pack.current_version || '1.0.0';
                this.selectedBlock.props.preset_snapshot = preset.css_tokens || {};
                this.schedulePropUpdate();
            }
        },

        onIconSelected(propKey, __icon) {
            this.selectedBlock.props[propKey] = __icon;
            if (window.CanvasIcons && window.CanvasIcons.getCustomItem) {
                const item = window.CanvasIcons.getCustomItem(__icon);
                if (item) {
                    this.selectedBlock.props.pack_slug = item.pack_slug || '';
                    this.selectedBlock.props.icon_slug = item.item_slug || '';
                    this.selectedBlock.props.asset_version = item.version || '1.0.0';
                    this.selectedBlock.props.svg_snapshot = item.body || '';
                } else {
                    this.selectedBlock.props.pack_slug = '';
                    this.selectedBlock.props.icon_slug = '';
                    this.selectedBlock.props.svg_snapshot = '';
                }
            }
            this.schedulePropUpdate();
        },

        // ── TB3-02: Live Iframe ───────────────────────────────
        livePreviewUrl() {
            const sep = ROUTES.livePreview.includes('?') ? '&' : '?';
            return ROUTES.livePreview + sep + 'page=' + encodeURIComponent(this.currentPage) + '&t=' + this.iframeTimestamp;
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
                try { ThemeCanvasRuntime.post(iframe.contentWindow, msg.action, Object.fromEntries(Object.entries(msg).filter(([key]) => key !== 'action'))); } catch (_) {}
            }
        },

        onIframeLoad() { setTimeout(() => { this.iframeLoading = false; }, 150); },

        async handleIframeMessage(e) {
            // Phase 3C: only ever trust messages from this app's own origin
            // — the live-preview iframe is same-origin by design, so a
            // message from anywhere else is not the preview iframe.
            if (!ThemeCanvasRuntime.sameOrigin(e.origin) || e.source !== this.getIframe()?.contentWindow) return;
            const d = e.data;
            if (!ThemeCanvasRuntime.valid(d, 'to-editor')) return;
            switch (d.action) {
                case 'ready':
                    this.iframeReady   = true;
                    this.iframeLoading = false;
                    if (this.selectedBlock) this.sendToIframe({ action: 'select', blockId: this.selectedBlock.id });
                    // A width edit made while the preview was booting is
                    // applied before the first render request so the row
                    // never flashes back to its stored layout.
                    if (this.pendingColumnWidthsMessage) {
                        this.sendToIframe(this.pendingColumnWidthsMessage);
                        this.pendingColumnWidthsMessage = null;
                    }
                    // Phase 4B.6 — flush a prop edit queued while the iframe
                    // was still booting (see pushPropUpdate).
                    if (this.pendingPropUpdate) {
                        this.pendingPropUpdate = false;
                        this.pushPropUpdate();
                    }
                    break;
                case 'iframeHeight':
                    const iframe = this.getIframe();
                    if (iframe) iframe.style.height = Math.max(400, d.height || 400) + 'px';
                    break;
                case 'blockSelected': {
                    let path = this.findBlockPath(d.blockId);
                    if (!path && this.pages['__header__']?.blocks?.length) {
                        const hdrFound = this.findBlockPath(d.blockId, this.pages['__header__'].blocks);
                        if (hdrFound || d.blockId === 'header' || this.pages['__header__'].blocks.some(b => b.id === d.blockId)) {
                            if (this.currentPage !== '__header__') {
                                await this.switchPage('__header__');
                            }
                            path = this.findBlockPath(d.blockId) || [0];
                        }
                    }
                    if (!path && this.pages['__footer__']?.blocks?.length) {
                        const ftrFound = this.findBlockPath(d.blockId, this.pages['__footer__'].blocks);
                        if (ftrFound || d.blockId === 'footer' || this.pages['__footer__'].blocks.some(b => b.id === d.blockId)) {
                            if (this.currentPage !== '__footer__') {
                                await this.switchPage('__footer__');
                            }
                            path = this.findBlockPath(d.blockId) || [0];
                        }
                    }
                    if (path) {
                        this.showLeftSidebar = true;
                        this.selectedPath = path;
                        this.selectedTab = 'content';
                        this.selectedLogicalItem = d.logicalGroup !== null && Number.isInteger(d.logicalIndex)
                            ? { group: d.logicalGroup, index: d.logicalIndex }
                            : null;
                        if (this.selectedLogicalItem) {
                            this.$nextTick(() => document.querySelector(`[data-testid="tb-repeater-item-${d.logicalGroup}-${d.logicalIndex}"]`)?.scrollIntoView({ behavior: 'smooth', block: 'center' }));
                        }
                    }
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
                // A completed column drag. The iframe measured the real
                // tracks; here they become persisted author data
                // (props.columns[i].width, percent) and the block is
                // re-rendered so the saved DOM and the preview agree.
                case 'columnResize': {
                    const path = this.findBlockPath(d.blockId);
                    const block = path ? this.getBlockByPath(path) : null;
                    const incoming = Array.isArray(d.columns) ? d.columns : [];
                    const columns = incoming
                        .map(column => ({ width: Math.round(Number(column?.width ?? 0) * 10) / 10 }))
                        .filter(column => Number.isFinite(column.width) && column.width >= 10 && column.width <= 90);
                    if (!block || columns.length < 2) break;
                    if (path.join(',') !== (this.selectedPath ?? []).join(',')) this.selectBlock(path);
                    this.columnWidthHistoryPending = false;
                    this.pushHistory();
                    if (!block.props || typeof block.props !== 'object' || Array.isArray(block.props)) block.props = {};
                    block.props.columns = columns;
                    this.isDirty = true;
                    this.schedulePropUpdate();
                    clearTimeout(this.inlineAutoSaveTimer);
                    this.inlineAutoSaveTimer = setTimeout(() => this.autoSave(), 1500);
                    break;
                }
            }
        },

        setDevice(d) { this.device = d; this.activeBreakpoint = d; },
        toggleInspect() { this.inspectHost?.toggle(); },

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
            if (['section','grid','columns','column','slider','accordion'].includes(type)) return 'bg-blue-400';
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
                this.motionModal.locked = d.locked ?? [];
                this.motionModal.loaded = true;
            } catch (_) { this.motionModal.assets = []; this.motionModal.locked = []; }
            finally { this.motionModal.loading = false; }
        },

confirmMotionSelect() {
            if (!this.motionModal.propKey) return;
            if (this.selectedBlock) {
                const next = this.motionModal.selected || '';
                // ADR-079: overrides belong to one asset — a different motion
                // starts from its own defaults.
                if (this.selectedBlock.props[this.motionModal.propKey] !== next && 'motion_params' in this.selectedBlock.props) {
                    this.selectedBlock.props.motion_params = {};
                }
                this.selectedBlock.props[this.motionModal.propKey] = next;
                this.schedulePropUpdate();
            }
            this.motionModal.open = false;
        },

        clearMotionSelect(propKey) {
            if (this.selectedBlock) {
                this.selectedBlock.props[propKey] = '';
                if ('motion_params' in this.selectedBlock.props) this.selectedBlock.props.motion_params = {};
                this.schedulePropUpdate();
            }
        },

        // ── Motion runtime parameters (ADR-079) ─────────────────────
        // Controls come from the selected asset's manifest `parameters`
        // (ROUTES.motionAssets). Values are stored sparsely: only keys that
        // differ from the asset default; the server re-validates them all.
        ensureMotionAssets() {
            if (!this.motionModal.loaded && !this.motionModal.loading) this.fetchMotionAssets();
        },

        // Why a placed motion has no controls (and renders nothing). The
        // platform withholds a package item the site is not licensed for and
        // lists it under `locked` with a fixed reason (saas-platform
        // CustomerRuntimeManifest::LOCK_REASONS). An id in neither list is
        // simply not available to this site (archived, or not in its package).
        motionAvailability(assetId) {
            if (!assetId) return 'none';
            if (!this.motionModal.loaded) return 'loading';
            if (this.motionAssetById(assetId)) return 'available';
            const lock = this.motionModal.locked.find(l => l.asset_id === assetId);
            return lock?.reason === 'premium_license_required' ? 'premium' : 'unavailable';
        },

        motionAvailabilityNotice(assetId) {
            return {
                premium: 'این موشن نیازمند لایسنس پرمیوم است.',
                unavailable: 'این موشن برای این سایت در دسترس نیست.',
            }[this.motionAvailability(assetId)] || '';
        },

        motionParamsNotice() {
            const assetId = this.selectedBlock?.props?.motion_asset_id;
            const state = this.motionAvailability(assetId);
            if (state === 'none') return 'ابتدا یک موشن انتخاب کنید.';
            if (state === 'loading') return 'در حال بارگذاری تنظیمات موشن…';
            return this.motionAvailabilityNotice(assetId) || 'این موشن تنظیمات اختصاصی ندارد.';
        },

        motionParamDefs() {
            const asset = this.motionAssetById(this.selectedBlock?.props?.motion_asset_id);
            return Array.isArray(asset?.parameters) ? asset.parameters : [];
        },

        motionParamGroups() {
            // ADR-080 (saas-platform): `sections` = show/hide switches per part.
            const labels = { content: 'متن‌ها و ارقام', style: 'رنگ‌ها و حالت رنگی', sections: 'بخش‌های موشن', motion: 'انیمیشن' };
            return ['content', 'style', 'sections', 'motion']
                .map(key => ({ key, label: labels[key], items: this.motionParamDefs().filter(p => (p.group || 'content') === key) }))
                .filter(group => group.items.length > 0);
        },

        motionParamStore(propKey) {
            const v = this.selectedBlock?.props?.[propKey];
            return v && typeof v === 'object' && !Array.isArray(v) ? v : {};
        },

        motionParamValue(propKey, p) {
            const store = this.motionParamStore(propKey);
            return Object.prototype.hasOwnProperty.call(store, p.key) ? store[p.key] : p.default;
        },

        // Stores on every input event: schedulePropUpdate() is already
        // debounced, and a per-field debounce would read the input after a
        // re-render had reset it to the stored value (losing typed text when
        // another control changed first). An emptied text field is kept as ''
        // — the server treats it as "use the default" — so the field does not
        // snap back to the default text while the user is retyping.
        setMotionParam(propKey, p, raw) {
            if (!this.selectedBlock) return;
            const store = { ...this.motionParamStore(propKey) };
            let value = raw;
            if (p.type === 'number') value = Number(raw);
            if (p.type === 'text') value = String(raw).slice(0, p.max || 80);
            if (value === p.default || (p.type === 'number' && Number.isNaN(value))) {
                delete store[p.key];
            } else {
                store[p.key] = value;
            }
            this.selectedBlock.props[propKey] = store;
            this.schedulePropUpdate();
        },

        hasMotionParams(propKey) {
            return Object.keys(this.motionParamStore(propKey)).length > 0;
        },

        resetMotionParams(propKey) {
            if (!this.selectedBlock) return;
            this.selectedBlock.props[propKey] = {};
            this.schedulePropUpdate();
        },

        // ── Tree-based Drag & Drop with Re-parenting ──────────────────
        // Drag state for tree-based reordering and re-parenting
        treeDragState: {
            isDragging: false,
            draggedBlockId: null,
            draggedBlockPath: null,
            dragOverBlockId: null,
            dragOverPath: null,
            dropPosition: null, // 'before', 'after', 'inside'
        },

        // Check if target is a descendant of source (circular drop prevention)
        isDescendantOf(draggedPath, targetPath) {
            if (!draggedPath || !targetPath) return false;
            if (targetPath.length <= draggedPath.length) return false;
            for (let i = 0; i < draggedPath.length; i++) {
                if (draggedPath[i] !== targetPath[i]) return false;
            }
            return true;
        },

        computeTreeDropPosition(event, isContainer) {
            const el = event?.currentTarget;
            if (!el) return 'after';
            const rect = el.getBoundingClientRect();
            const ratio = (event.clientY - rect.top) / Math.max(rect.height, 1);
            if (isContainer) {
                if (ratio < 0.25) return 'before';
                if (ratio > 0.75) return 'after';
                return 'inside';
            }
            return ratio < 0.5 ? 'before' : 'after';
        },

        // Handle drag start on a block in the tree
        startTreeDrag(blockId, path) {
            this.treeDragState = {
                isDragging: true,
                draggedBlockId: blockId,
                draggedBlockPath: path,
                dragOverBlockId: null,
                dragOverPath: null,
                dropPosition: null,
            };
            this.sendToIframe({ action: 'showDropZones', visible: true });
        },

        // Handle drag over a block in the tree
        onTreeDragOver(blockId, path, position, event) {
            if (!this.treeDragState.isDragging) return;

            // Prevent dropping into self or descendants
            if (blockId === this.treeDragState.draggedBlockId) {
                this.treeDragState.dropPosition = null;
                this.treeDragState.dragOverBlockId = null;
                this.treeDragState.dragOverPath = null;
                return;
            }

            if (this.isDescendantOf(this.treeDragState.draggedBlockPath, path)) {
                this.treeDragState.dropPosition = null;
                this.treeDragState.dragOverBlockId = null;
                this.treeDragState.dragOverPath = null;
                return;
            }

            this.treeDragState.dragOverBlockId = blockId;
            this.treeDragState.dragOverPath = path;
            this.treeDragState.dropPosition = position; // 'before', 'after', 'inside'

            // Visual feedback via iframe
            this.sendToIframe({
                action: 'setDropTarget',
                blockId: blockId,
                position: position
            });
        },

        // Handle drag leave
        onTreeDragLeave(event) {
            if (!this.treeDragState.isDragging) return;
            if (event && event.currentTarget && event.relatedTarget && event.currentTarget.contains(event.relatedTarget)) return;
            this.treeDragState.dragOverBlockId = null;
            this.treeDragState.dragOverPath = null;
            this.treeDragState.dropPosition = null;
            this.sendToIframe({ action: 'setDropTarget', blockId: null });
        },

        // Handle drop on tree
        onTreeDrop(blockId, path) {
            if (!this.treeDragState.isDragging) return;

            const { draggedBlockPath, dropPosition, dragOverPath } = this.treeDragState;
            if (!draggedBlockPath || !dragOverPath) {
                this.endTreeDrag();
                return;
            }

            // Prevent circular drops
            if (this.isDescendantOf(draggedBlockPath, dragOverPath)) {
                this.endTreeDrag();
                return;
            }

            this.pushHistory();

            // Get source and target arrays
            const sourceArray = this.getSiblingArray(draggedBlockPath);
            const sourceIndex = draggedBlockPath[draggedBlockPath.length - 1];
            const [draggedBlock] = sourceArray.splice(sourceIndex, 1);

            // Determine target array and index
            let targetArray;
            let targetIndex;

            if (dropPosition === 'inside') {
                // Re-parent: add as child of target
                const targetBlock = this.getBlockByPath(dragOverPath);
                if (!targetBlock) { this.endTreeDrag(); return; }
                if (!this.isContainerType(targetBlock.type)) { this.endTreeDrag(); return; }
                if (!targetBlock.children) targetBlock.children = [];
                targetArray = targetBlock.children;
                targetIndex = targetArray.length; // append to end
            } else {
                // Re-order: insert before/after target in its parent array
                const targetParentPath = dragOverPath.slice(0, -1);
                const targetIndexInParent = dragOverPath[dragOverPath.length - 1];
                targetArray = this.getSiblingArray(dragOverPath);
                targetIndex = dropPosition === 'before' ? targetIndexInParent : targetIndexInParent + 1;
            }

            // Adjust index if moving within same array
            if (sourceArray === targetArray && sourceIndex < targetIndex) {
                targetIndex--;
            }

            targetArray.splice(targetIndex, 0, draggedBlock);
            this.isDirty = true;
            this.autoSaveAndReload();
            this.endTreeDrag();
        },

        endTreeDrag() {
            this.treeDragState = {
                isDragging: false,
                draggedBlockId: null,
                draggedBlockPath: null,
                dragOverBlockId: null,
                dragOverPath: null,
                dropPosition: null,
            };
            this.sendToIframe({ action: 'showDropZones', visible: false });
            this.sendToIframe({ action: 'setDropTarget', blockId: null });
        },

        // ── Copy/Paste Style ──────────────────────────────────────────
        copiedStyle: null,
        copiedStyleType: null,

        copyBlockStyle() {
            if (!this.selectedBlock) return;
            // Only copy style-related properties
            const styleKeys = ['style', 'responsive', 'props'];
            const style = {};
            for (const key of styleKeys) {
                if (this.selectedBlock[key]) {
                    style[key] = JSON.parse(JSON.stringify(this.selectedBlock[key]));
                }
            }
            this.copiedStyle = style;
            this.copiedStyleType = this.selectedBlock.type;
            this.showToast('استایل کپی شد', 'success');
        },

        pasteBlockStyle() {
            if (!this.selectedBlock || !this.copiedStyle || this.copiedStyleType !== this.selectedBlock.type) {
                this.showToast('استایل کپی شده برای این نوع بلوک معتبر نیست', 'error');
                return;
            }
            this.pushHistory();

            // Paste style and responsive
            if (this.copiedStyle.style) {
                this.selectedBlock.style = JSON.parse(JSON.stringify(this.copiedStyle.style));
            }
            if (this.copiedStyle.responsive) {
                this.selectedBlock.responsive = JSON.parse(JSON.stringify(this.copiedStyle.responsive));
            }
            // Optionally paste relevant props (colors, spacing, etc.)
            if (this.copiedStyle.props) {
                const stylePropKeys = ['bgColor', 'textColor', 'fontSize', 'fontWeight', 'textAlign', 'lineHeight',
                    'paddingTop', 'paddingBottom', 'paddingRight', 'paddingLeft',
                    'marginTop', 'marginBottom', 'marginRight', 'marginLeft',
                    'borderWidth', 'borderStyle', 'borderColor', 'borderRadius',
                    'boxShadow', 'opacity', 'bgGradientFrom', 'bgGradientTo', 'bgGradientDir'];
                for (const key of stylePropKeys) {
                    if (Object.prototype.hasOwnProperty.call(this.copiedStyle.props, key)) {
                        this.selectedBlock.props[key] = this.copiedStyle.props[key];
                    }
                }
            }

            this.isDirty = true;
            this.autoSaveAndReload();
            this.showToast('استایل چسبانده شد', 'success');
        },

        canPasteStyle() {
            return this.copiedStyle && this.selectedBlock && this.copiedStyleType === this.selectedBlock.type;
        },

        // ── Drop Indicator Line (handled via iframe communication) ──────
        // The iframe handles the visual drop indicator line; we just send position updates

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

        // ── Canvas overhaul Unit 4: unified entity link picker ─────────────
        // One modal for every link field at every depth. `set` is the field's
        // own writer, so nested repeater items persist through their usual
        // update path. A pick stores {type:'entity', entity, id, label}; the
        // label is only for this chip, the server resolves the live URL.
        isEntityLinkValue(value) {
            return !!value && typeof value === 'object' && (value.type === 'entity' || value.type === 'internal_page');
        },
        entityLinkLabel(value) {
            if (!this.isEntityLinkValue(value)) return '';
            return value.label || value.page_title || ('#' + (value.id ?? value.page_id));
        },
        entityLinkKind(value) {
            if (!this.isEntityLinkValue(value)) return '';
            if (value.type === 'internal_page') return 'برگه';
            return this.entityPicker.entities[value.entity] || ({ route: 'صفحه اصلی', page: 'برگه', product: 'محصول', product_category: 'دسته محصول', post: 'مقاله', blog_category: 'دسته وبلاگ', course: 'دوره' })[value.entity] || value.entity;
        },
        openEntityPicker(set) {
            this.entityPicker = { ...this.entityPicker, open: true, query: '', entity: '', results: [], set };
            this.searchEntities();
            this.$nextTick(() => document.querySelector('[data-testid="tb-link-picker-search"]')?.focus());
        },
        closeEntityPicker() {
            this.entityPicker.open = false;
            this.entityPicker.set = null;
        },
        async searchEntities() {
            const picker = this.entityPicker;
            picker.loading = true;
            try {
                const params = new URLSearchParams();
                if (picker.query) params.set('q', picker.query);
                if (picker.entity) params.set('entity', picker.entity);
                const r = await fetch(ROUTES.linkEntities + '?' + params.toString(), { headers: { 'Accept': 'application/json' } });
                const d = await r.json();
                picker.entities = d.entities ?? picker.entities;
                picker.results = d.results ?? [];
            } catch (e) {
                picker.results = [];
            } finally {
                picker.loading = false;
            }
        },
        pickEntity(result) {
            const set = this.entityPicker.set;
            if (typeof set === 'function') set({ type: 'entity', entity: result.entity, id: result.id, label: result.title });
            this.closeEntityPicker();
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
        // Canvas overhaul Unit 5 (ADR-075): a template lands in its own zone
        // (header/footer templates on the virtual __header__/__footer__
        // pages, never in a page body), a header/footer template replaces
        // the current one after confirmation, and the canvas re-renders at
        // once through the same autoSaveAndReload() every other insert uses.
        // History is pushed BEFORE the change, as addBlock() does, so one
        // undo restores the pre-insert tree.
        async insertTemplate(id) {
            if (this.templateLibrary.inserting) return;
            this.templateLibrary.inserting = true;
            try {
                const res = await fetch('{{ route('admin.theme-design.canvas.insert-template') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content, 'Accept': 'application/json' },
                    body: JSON.stringify({ template_id: id }),
                });
                const json = await res.json().catch(() => ({}));
                if (!res.ok || !json.success || !Array.isArray(json.blocks)) {
                    this.showToast(json.message || 'درج قالب ممکن نشد.', 'error');
                    return;
                }
                const zonePage = { header: '__header__', footer: '__footer__' }[json.zone] ?? null;
                if (zonePage && this.currentPage !== zonePage && this.pages[zonePage]) {
                    await this.switchPage(zonePage);
                } else if (!zonePage && this.currentPage.startsWith('__')) {
                    await this.switchPage(Object.keys(this.pages).find(k => !k.startsWith('__')) ?? 'home');
                }
                const replace = !!zonePage && this.blocks.length > 0;
                if (replace && !confirm(`${json.zone === 'header' ? 'هدر' : 'فوتر'} فعلی با این قالب جایگزین شود؟ (با «بازگردانی» قابل برگشت است)`)) return;

                this.pushHistory();
                const inserted = json.blocks.map(raw => this.normalizeBlock(JSON.parse(JSON.stringify(raw))));
                const start = replace ? 0 : this.blocks.length;
                this.pages[this.currentPage].blocks = replace ? inserted : [...this.blocks, ...inserted];
                this.selectedPath = inserted.length ? [start] : null;
                this.showTemplateLibrary = false;
                this.isDirty = true;
                await this.autoSaveAndReload();
                this.showToast('قالب درج شد', 'success');
            } catch (e) {
                console.error('[template-insert]', e);
                this.showToast('خطا در درج قالب', 'error');
            } finally {
                this.templateLibrary.inserting = false;
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

        // ── Canvas sidebar layout ─────────────────────────────
        sidebarWidthBounds(width) {
            return Math.min(600, Math.max(260, Math.round(width)));
        },

        loadSidebarLayout() {
            try {
                const right = parseInt(localStorage.getItem('tb-canvas-right-sidebar-width'), 10);
                const left  = parseInt(localStorage.getItem('tb-canvas-left-sidebar-width'), 10);
                if (Number.isFinite(right)) this.rightSidebarWidth = this.sidebarWidthBounds(right);
                if (Number.isFinite(left))  this.leftSidebarWidth  = this.sidebarWidthBounds(left);

                const rightOpen = localStorage.getItem('tb-canvas-right-sidebar-open');
                const leftOpen  = localStorage.getItem('tb-canvas-left-sidebar-open');
                if (rightOpen !== null) this.showRightSidebar = rightOpen === '1';
                if (leftOpen !== null)  this.showLeftSidebar  = leftOpen === '1';
            } catch (_) { /* storage unavailable (private mode) — keep defaults */ }
        },

        persistSidebarWidths() {
            try {
                localStorage.setItem('tb-canvas-right-sidebar-width', String(this.rightSidebarWidth));
                localStorage.setItem('tb-canvas-left-sidebar-width', String(this.leftSidebarWidth));
            } catch (_) { /* storage unavailable — widths still apply for this session */ }
        },

        persistSidebarLayout() {
            try {
                localStorage.setItem('tb-canvas-right-sidebar-open', this.showRightSidebar ? '1' : '0');
                localStorage.setItem('tb-canvas-left-sidebar-open', this.showLeftSidebar ? '1' : '0');
            } catch (_) { /* storage unavailable — layout still applies for this session */ }
        },

        // Width (not display) drives the collapse so the browser can animate
        // it. Hidden panels keep their last resized width, so re-opening
        // restores exactly the size the operator dragged to.
        sidebarPanelStyle(side) {
            const open  = side === 'right' ? this.showRightSidebar : this.showLeftSidebar;
            const width = side === 'right' ? this.rightSidebarWidth : this.leftSidebarWidth;
            const edge  = side === 'right' ? 'border-left-width' : 'border-right-width';
            return 'width:' + (open ? width : 0) + 'px;'
                + edge + ':' + (open ? 1 : 0) + 'px;'
                + 'opacity:' + (open ? 1 : 0) + ';'
                + 'visibility:' + (open ? 'visible' : 'hidden') + ';'
                // A live splitter must track the pointer 1:1; the easing is
                // only for the collapse/expand transition.
                + (this.sidebarDrag ? 'transition:none;' : 'transition:all .25s ease;');
        },

        sidebarResizerStyle(side) {
            const open = side === 'right' ? this.showRightSidebar : this.showLeftSidebar;
            return 'width:' + (open ? 6 : 0) + 'px;'
                + 'opacity:' + (open ? 1 : 0) + ';'
                + 'pointer-events:' + (open ? 'auto' : 'none') + ';'
                + 'transition:all .25s ease;';
        },

        toggleSidebar(side) {
            if (side === 'right') this.showRightSidebar = !this.showRightSidebar;
            else this.showLeftSidebar = !this.showLeftSidebar;
            this.persistSidebarLayout();
        },

        // Focus mode: both sidebars go away (or both come back if either was
        // already hidden, so the shortcut never leaves the editor unlabelled).
        toggleFocusMode() {
            const visible = this.showRightSidebar || this.showLeftSidebar;
            this.showRightSidebar = !visible;
            this.showLeftSidebar  = !visible;
            this.persistSidebarLayout();
        },

        startSidebarDrag(side, event) {
            if (event.button !== 0) return;
            const startX = event.clientX;
            const startWidth = side === 'right' ? this.rightSidebarWidth : this.leftSidebarWidth;
            this.sidebarDrag = { side, startX, startWidth };

            // The preview iframe would otherwise swallow pointermove as soon
            // as the pointer crosses into it, freezing the splitter.
            const iframe = this.getIframe();
            if (iframe) iframe.classList.add('pointer-events-none');
            document.body.style.cursor = 'col-resize';
            document.body.style.userSelect = 'none';

            const onMove = (e) => {
                const delta = side === 'right'
                    ? startX - e.clientX  // right sidebar grows as its left edge moves left
                    : e.clientX - startX; // left sidebar grows as its right edge moves right
                const width = this.sidebarWidthBounds(startWidth + delta);
                if (side === 'right') this.rightSidebarWidth = width;
                else this.leftSidebarWidth = width;
            };

            const onEnd = () => {
                this.sidebarDrag = null;
                if (iframe) iframe.classList.remove('pointer-events-none');
                document.body.style.cursor = '';
                document.body.style.userSelect = '';
                this.persistSidebarWidths();
                window.removeEventListener('pointermove', onMove);
                window.removeEventListener('pointerup', onEnd);
                window.removeEventListener('pointercancel', onEnd);
            };

            window.addEventListener('pointermove', onMove);
            window.addEventListener('pointerup', onEnd);
            window.addEventListener('pointercancel', onEnd);
        },

        init() {
            // TB3-01 + TB4: Normalize block.data → block.props, migrate legacy children locations
            Object.values(this.pages).forEach(page => {
                if (Array.isArray(page.blocks)) page.blocks = page.blocks.map(b => this.normalizeBlock(b));
            });

            // TB3-S2: Initialize undo history with current state
            this.history = [JSON.parse(JSON.stringify(this.blocks))];
            this.historyIndex = 0;

            if (this.currentPage === '__header__' || this.currentPage === '__footer__') {
                this.showLeftSidebar = true;
                if (Array.isArray(this.pages[this.currentPage]?.blocks) && this.pages[this.currentPage].blocks.length > 0) {
                    this.selectedPath = [0];
                    this.selectedTab = 'content';
                }
            }

            // TB3-02: postMessage bridge
            window.addEventListener('message', (e) => this.handleIframeMessage(e));

            // WU-1: Inspect Mode. The preview finds window.TbInspectHost on
            // this window (same origin) and re-applies the state after reloads.
            // attachHost() is idempotent: init() runs twice (x-init + Alpine).
            this.inspectHost = window.TbInspect?.attachHost({
                frame: () => this.getIframe(),
                describe: (type) => ({ label: BLOCK_LABELS[type] || '', schema: BLOCK_SCHEMAS[type]?.schema || null, defaults: BLOCK_SCHEMAS[type]?.defaultProps || null }),
                onChange: (on) => { this.inspectOn = on; if (!on) this.auditSummary = null; },
                onAudit: (summary) => { this.auditSummary = this.inspectOn ? summary : null; },
                // WU-3: a «سلامت ویجت» row selects its block.
                select: (blockId) => { const path = this.findBlockPath(blockId); if (path) this.selectBlock(path); },
            }) ?? null;

            // AI Dev HUD (partials/ai-dev-hud): read-only, absent when the site turns it off.
            window.TbDevHud?.attach(this, { frame: () => this.getIframe() });

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

            // Sidebar layout: restore persisted widths + open/closed state,
            // then bind the shortcuts. init() runs twice (x-init + Alpine),
            // so the listener must be registered exactly once.
            this.loadSidebarLayout();
            if (!this.sidebarShortcutBound) {
                this.sidebarShortcutBound = true;
                window.addEventListener('keydown', (e) => {
                    const el = document.activeElement;
                    const tag = el?.tagName;
                    if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || el?.isContentEditable) return;

                    const mod = e.ctrlKey || e.metaKey;
                    const key = (e.key || '').toLowerCase();

                    // Ctrl+\ or plain F: full-width preview (both sidebars).
                    if ((mod && !e.altKey && !e.shiftKey && (key === '\\' || e.code === 'Backslash'))
                        || (!mod && !e.altKey && !e.shiftKey && key === 'f')) {
                        e.preventDefault();
                        this.toggleFocusMode();
                        return;
                    }

                    // Ctrl+B: block library, Ctrl+Alt+B: properties panel.
                    if (mod && !e.shiftKey && key === 'b') {
                        e.preventDefault();
                        e.altKey ? this.toggleSidebar('left') : this.toggleSidebar('right');
                    }
                });
            }

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
