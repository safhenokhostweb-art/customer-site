<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ویرایش: {{ $pageTitle }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    {{-- Phase 0.4 (ADR-063): local Vazirmatn, no Google Fonts. --}}
    <style>@include('partials.vazirmatn-font-faces-inline')</style>
    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        *{box-sizing:border-box}
        body{font-family:'Vazirmatn',Tahoma,sans-serif;margin:0;padding:0;overflow:hidden;height:100vh;background:#0f172a;direction:rtl}
        ::-webkit-scrollbar{width:5px;height:5px}::-webkit-scrollbar-track{background:#1e293b}::-webkit-scrollbar-thumb{background:#475569;border-radius:3px}
        [x-cloak]{display:none!important}
        /* Quill */
        .ql-toolbar.ql-snow{border:none;border-bottom:1px solid #334155;background:#1e293b;padding:5px 8px;direction:ltr}
        .ql-container.ql-snow{border:none;font-family:'Vazirmatn',Tahoma,sans-serif;font-size:13px;background:#0f172a}
        .ql-editor{direction:rtl;text-align:right;min-height:90px;max-height:200px;overflow-y:auto;padding:8px 12px;color:#e2e8f0;font-family:'Vazirmatn',Tahoma,sans-serif}
        .ql-editor.ql-blank::before{right:12px;left:auto;font-style:normal;color:#475569;font-size:12px}
        .ql-toolbar .ql-stroke{stroke:#94a3b8}.ql-toolbar .ql-fill{fill:#94a3b8}.ql-toolbar button:hover .ql-stroke{stroke:#fff}.ql-toolbar .ql-picker-label{color:#94a3b8}
        .quill-host{border:1px solid #334155;border-radius:8px;overflow:hidden;background:#0f172a}
        /* Upload spinner */
        .upload-spinner{display:inline-block;width:13px;height:13px;border:2px solid #334155;border-top-color:#6366f1;border-radius:50%;animation:spin .6s linear infinite}
        @keyframes spin{to{transform:rotate(360deg)}}
        /* Repeater */
        .rep-item{background:#1e293b;border:1px solid #334155;border-radius:8px;padding:10px}
        .rep-item:hover{border-color:#4f46e5}
        /* Left panel tab active */
        .ltab-active{background:#1e293b;color:#818cf8!important;border-bottom:2px solid #6366f1}
        /* Right panel tab active */
        .rtab-active{background:#1e293b;color:#818cf8!important;border-bottom:2px solid #6366f1}
        /* Canvas device frames */
        .device-mobile{max-width:390px}
        .device-tablet{max-width:768px}
        /* Page card hover */
        .page-card:hover{background:#1e293b;border-color:#4f46e5}
        /* Custom range */
        input[type=range]{accent-color:#6366f1}
        /* Form inputs dark */
        .inp{width:100%;background:#0f172a;border:1px solid #334155;color:#e2e8f0;border-radius:8px;padding:6px 10px;font-size:12px;font-family:'Vazirmatn',Tahoma,sans-serif;outline:none}
        .inp:focus{border-color:#6366f1;box-shadow:0 0 0 2px rgba(99,102,241,.2)}
        .inp::placeholder{color:#475569}
        select.inp option{background:#1e293b;color:#e2e8f0}
        /* Toggle */
        .tgl-track{width:36px;height:20px;border-radius:10px;transition:.2s;background:#334155;position:relative;cursor:pointer}
        .tgl-track.on{background:#6366f1}
        .tgl-thumb{width:14px;height:14px;border-radius:7px;background:#fff;position:absolute;top:3px;right:3px;transition:.2s;box-shadow:0 1px 3px rgba(0,0,0,.4)}
        .tgl-track.on .tgl-thumb{right:19px}
        /* Live canvas iframe */
        #live-canvas{width:100%;height:100%;border:none;display:block}
        .canvas-loading::after{content:'در حال بارگذاری...';position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#475569;font-size:13px;background:#0f172a}
        /* Undo/redo */
        .btn-icon{width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;transition:.15s;cursor:pointer;font-size:13px}
        .btn-icon:hover{background:#1e293b}
        .btn-icon:disabled{opacity:.3;cursor:not-allowed}
    </style>
</head>
<body>
<div
    x-data="elBuilder()"
    x-init="init()"
    x-cloak
    class="flex flex-col h-screen overflow-hidden"
    style="background:#0f172a"
    @keydown.ctrl.s.window.prevent="saveBlocks()"
    @keydown.ctrl.z.window.prevent="undo()"
    @keydown.ctrl.y.window.prevent="redo()"
>
    {{-- ══ TOP TOOLBAR ════════════════════════════════════════════════════ --}}
    <div class="h-12 shrink-0 flex items-center gap-2 px-3 z-30 border-b"
         style="background:#1e293b;border-color:#334155">

        {{-- Back --}}
        <a href="{{ $exitUrl }}" class="flex items-center gap-1.5 text-slate-400 hover:text-white text-xs font-medium transition shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div class="w-px h-5 shrink-0" style="background:#334155"></div>

        {{-- Logo + Page title --}}
        <div class="flex items-center gap-2 shrink-0">
            <div class="w-6 h-6 rounded-md flex items-center justify-center text-xs" style="background:linear-gradient(135deg,#6366f1,#8b5cf6)">⚡</div>
            <span class="text-xs font-semibold text-white" x-text="pageSettings.title || '{{ $pageTitle }}'"></span>
            <span x-show="!pageSettings.is_published" class="text-[10px] px-1.5 py-0.5 rounded-full font-medium" style="background:#451a03;color:#fb923c">پیش‌نویس</span>
            <span x-show="pageSettings.is_published" class="text-[10px] px-1.5 py-0.5 rounded-full font-medium" style="background:#14532d;color:#86efac">منتشرشده</span>
        </div>

        <div class="flex-1"></div>

        {{-- Device selector --}}
        <div class="flex items-center gap-0.5 p-0.5 rounded-lg shrink-0" style="background:#0f172a">
            <button @click="device='desktop'" :class="device==='desktop'?'bg-indigo-600 text-white':'text-slate-400 hover:text-white'"
                class="px-2.5 py-1 rounded-md text-[10px] font-medium transition flex items-center gap-1" title="دسکتاپ">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2" stroke-width="2"/><path d="M8 21h8M12 17v4" stroke-width="2" stroke-linecap="round"/></svg>
            </button>
            <button @click="device='tablet'" :class="device==='tablet'?'bg-indigo-600 text-white':'text-slate-400 hover:text-white'"
                class="px-2.5 py-1 rounded-md text-[10px] font-medium transition flex items-center gap-1" title="تبلت">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="4" y="2" width="16" height="20" rx="2" stroke-width="2"/><circle cx="12" cy="18" r="1" fill="currentColor"/></svg>
            </button>
            <button @click="device='mobile'" :class="device==='mobile'?'bg-indigo-600 text-white':'text-slate-400 hover:text-white'"
                class="px-2.5 py-1 rounded-md text-[10px] font-medium transition flex items-center gap-1" title="موبایل">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="7" y="2" width="10" height="20" rx="2" stroke-width="2"/><circle cx="12" cy="18" r="1" fill="currentColor"/></svg>
            </button>
        </div>
        <div class="w-px h-5 shrink-0" style="background:#334155"></div>

        {{-- Undo/Redo --}}
        <button @click="undo()" :disabled="historyIdx <= 0" class="btn-icon text-slate-400 hover:text-white" title="بازگشت (Ctrl+Z)">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
        </button>
        <button @click="redo()" :disabled="historyIdx >= history.length - 1" class="btn-icon text-slate-400 hover:text-white" title="انجام دوباره (Ctrl+Y)">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10H11a8 8 0 00-8 8v2M21 10l-6 6m6-6l-6-6"/></svg>
        </button>

        <div class="w-px h-5 shrink-0" style="background:#334155"></div>

        {{-- View live --}}
        <a :href="'{{ $exitUrl }}'" target="_blank"
           class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs text-slate-400 hover:text-white transition shrink-0" style="border:1px solid #334155">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            مشاهده
        </a>

        {{-- Dirty indicator --}}
        <span x-show="isDirty" class="text-amber-400 text-[10px] flex items-center gap-1 shrink-0">
            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse inline-block"></span>
            ذخیره نشده
        </span>

        {{-- Save --}}
        <button @click="saveBlocks()" :disabled="saving" data-testid="tb-save-button"
            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition disabled:opacity-60 shrink-0"
            style="background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff">
            <svg x-show="!saving" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
            <svg x-show="saving" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
            <span x-text="saving ? 'ذخیره...' : 'ذخیره (Ctrl+S)'"></span>
        </button>
    </div>

    {{-- ══ 3-PANEL MAIN ════════════════════════════════════════════════════ --}}
    <div class="flex flex-1 overflow-hidden">

        {{-- ── LEFT PANEL (tabs: Blocks / Pages / Settings) ─────────────── --}}
        <div class="w-64 flex flex-col shrink-0 overflow-hidden z-20" style="background:#1e293b;border-left:1px solid #334155">

            {{-- Left Tab Bar --}}
            <div class="flex border-b shrink-0" style="border-color:#334155">
                <button @click="leftTab='blocks'" :class="leftTab==='blocks'?'ltab-active':'text-slate-500 hover:text-slate-300'"
                    class="flex-1 py-2.5 text-[10px] font-semibold transition flex flex-col items-center gap-0.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="8" height="8" rx="1" stroke-width="2"/><rect x="13" y="3" width="8" height="8" rx="1" stroke-width="2"/><rect x="3" y="13" width="8" height="8" rx="1" stroke-width="2"/><rect x="13" y="13" width="8" height="8" rx="1" stroke-width="2"/></svg>
                    ویجت‌ها
                </button>
                <button @click="leftTab='pages'; loadPages()" :class="leftTab==='pages'?'ltab-active':'text-slate-500 hover:text-slate-300'"
                    class="flex-1 py-2.5 text-[10px] font-semibold transition flex flex-col items-center gap-0.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    صفحات
                </button>
                <button @click="leftTab='settings'" :class="leftTab==='settings'?'ltab-active':'text-slate-500 hover:text-slate-300'"
                    class="flex-1 py-2.5 text-[10px] font-semibold transition flex flex-col items-center gap-0.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3" stroke-width="2"/></svg>
                    صفحه
                </button>
                <button @click="leftTab='history'; loadRevisions()" :class="leftTab==='history'?'ltab-active':'text-slate-500 hover:text-slate-300'"
                    class="flex-1 py-2.5 text-[10px] font-semibold transition flex flex-col items-center gap-0.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    تاریخچه
                </button>
            </div>

            {{-- LEFT TAB: BLOCKS ─────────────────────────────────────────── --}}
            <div x-show="leftTab==='blocks'" class="flex flex-col flex-1 overflow-hidden">
                <div class="px-3 py-2 shrink-0" style="border-bottom:1px solid #334155">
                    <div class="relative">
                        <input x-model="librarySearch" type="text" placeholder="جستجو..." class="inp pr-8">
                        <svg class="w-3.5 h-3.5 absolute top-2 right-2.5 pointer-events-none" style="color:#475569" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                </div>
                <div class="flex-1 overflow-y-auto">
                    @foreach(($orderedCats ?? ['layout','content','marketing','interactive','dynamic','ecommerce','utility']) as $catKey)
                        @php $catDefs=$byCategory[$catKey]??[]; if(empty($catDefs)) continue; $catLabel=$catLabels[$catKey]??$catKey; $catIcon=$catIcons[$catKey]??'▪'; $badgeClass=$catColors[$catKey]??'text-slate-500 bg-slate-800'; @endphp
                        <div x-show="isCatVisible('{{ $catKey }}')" style="border-bottom:1px solid #334155">
                            <button @click="toggleCat('{{ $catKey }}')"
                                class="w-full flex items-center justify-between px-3 py-2 hover:bg-slate-800 transition text-right">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm">{{ $catIcon }}</span>
                                    <span class="text-[11px] font-semibold text-slate-300">{{ $catLabel }}</span>
                                    <span class="text-[10px] text-slate-600">({{ count($catDefs) }})</span>
                                </div>
                                <svg :class="expandedCats['{{ $catKey }}']?'rotate-180':''" class="w-3 h-3 text-slate-500 transition-transform shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="expandedCats['{{ $catKey }}']">
                                <div class="px-2 pb-2 grid grid-cols-2 gap-1.5">
                                    @foreach($catDefs as $blockType => $blockDef)
                                        <button @click="addBlock('{{ $blockType }}')"
                                            data-testid="tb-add-block-{{ $blockType }}"
                                            x-show="isBlockVisible('{{ $blockType }}','{{ addslashes($blockDef['name']) }}')"
                                            class="flex flex-col items-start gap-1 p-2 rounded-lg text-right cursor-pointer group transition"
                                            style="border:1px solid #334155;background:#0f172a"
                                            @mouseenter="$el.style.borderColor='#6366f1';$el.style.background='#1e1b4b'"
                                            @mouseleave="$el.style.borderColor='#334155';$el.style.background='#0f172a'">
                                            <div class="text-base leading-none">{{ $blockDef['icon'] }}</div>
                                            <span class="text-[10px] font-medium text-slate-400 group-hover:text-indigo-300 leading-tight" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">{{ $blockDef['name'] }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- LEFT TAB: PAGES ─────────────────────────────────────────── --}}
            <div x-show="leftTab==='pages'" class="flex flex-col flex-1 overflow-hidden">
                <div class="px-3 py-2 flex gap-2 shrink-0" style="border-bottom:1px solid #334155">
                    <button @click="showCreatePage=true" class="flex-1 flex items-center justify-center gap-1.5 py-1.5 rounded-lg text-[11px] font-semibold text-white transition"
                        style="background:linear-gradient(135deg,#6366f1,#4f46e5)">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        صفحه جدید
                    </button>
                </div>

                {{-- Create page modal inline --}}
                <div x-show="showCreatePage" class="px-3 py-2 shrink-0" style="border-bottom:1px solid #334155;background:#0f172a">
                    <p class="text-[11px] font-semibold text-slate-300 mb-2">ایجاد صفحه جدید</p>
                    <input x-model="newPageTitle" type="text" placeholder="عنوان صفحه..." class="inp mb-2" @keydown.enter="createPage()">
                    <div class="flex gap-2">
                        <button @click="createPage()" class="flex-1 py-1.5 rounded-lg text-[11px] font-semibold text-white" style="background:#6366f1">ایجاد</button>
                        <button @click="showCreatePage=false;newPageTitle=''" class="flex-1 py-1.5 rounded-lg text-[11px] text-slate-400" style="border:1px solid #334155">لغو</button>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto">
                    <div x-show="pagesLoading" class="flex items-center justify-center py-8">
                        <div class="upload-spinner"></div>
                    </div>
                    <div x-show="!pagesLoading && pages.length === 0" class="text-center py-8 text-slate-500 text-xs">هنوز صفحه‌ای وجود ندارد</div>

                    <template x-for="pg in pages" :key="pg.id">
                        <div class="page-card mx-2 my-1 p-2.5 rounded-lg cursor-pointer transition"
                             :class="pg.id === {{ $pageId }} ? 'border-indigo-600 bg-indigo-950' : 'border-transparent'"
                             style="border:1px solid transparent">
                            <div class="flex items-start justify-between gap-1">
                                <div class="flex-1 min-w-0">
                                    <p class="text-[11px] font-semibold text-white truncate" x-text="pg.title"></p>
                                    <p class="text-[10px] text-slate-500 truncate mt-0.5" x-text="'/' + pg.slug"></p>
                                </div>
                                <span :class="pg.is_published?'text-green-400':'text-amber-500'" class="text-[9px] font-semibold shrink-0 mt-0.5" x-text="pg.is_published?'✓منتشر':'پیش‌نویس'"></span>
                            </div>
                            <div class="flex items-center gap-1 mt-2">
                                <a :href="pg.edit_url" class="flex-1 text-center py-1 rounded-md text-[10px] font-medium text-indigo-400 hover:text-white transition" style="border:1px solid #4f46e5">ویرایش در Builder</a>
                                <a :href="pg.admin_url" target="_blank" class="px-2 py-1 rounded-md text-[10px] text-slate-500 hover:text-white transition" title="ویرایش در ادمین" style="border:1px solid #334155">⚙</a>
                                <a :href="pg.view_url" target="_blank" class="px-2 py-1 rounded-md text-[10px] text-slate-500 hover:text-white transition" title="مشاهده" style="border:1px solid #334155">👁</a>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- LEFT TAB: PAGE SETTINGS ─────────────────────────────────── --}}
            <div x-show="leftTab==='settings'" class="flex-1 overflow-y-auto px-3 py-3 space-y-4">
                <div>
                    <label class="block text-[10px] font-semibold text-slate-400 mb-1">عنوان صفحه</label>
                    <input x-model="pageSettings.title" type="text" class="inp" @change="savePageSettings()">
                </div>
                <div>
                    <label class="block text-[10px] font-semibold text-slate-400 mb-1">آدرس (slug)</label>
                    <div class="flex items-center gap-1">
                        <span class="text-[10px] text-slate-600">/</span>
                        <input x-model="pageSettings.slug" type="text" class="inp" @change="savePageSettings()">
                    </div>
                </div>
                <div>
                    <label class="flex items-center justify-between cursor-pointer">
                        <span class="text-[10px] font-semibold text-slate-400">وضعیت انتشار</span>
                        <div class="tgl-track" :class="pageSettings.is_published?'on':''" @click="pageSettings.is_published=!pageSettings.is_published;savePageSettings()">
                            <div class="tgl-thumb"></div>
                        </div>
                    </label>
                </div>
                <div>
                    <label class="flex items-center justify-between cursor-pointer">
                        <span class="text-[10px] font-semibold text-slate-400">نمایش در منو</span>
                        <div class="tgl-track" :class="pageSettings.show_in_menu?'on':''" @click="pageSettings.show_in_menu=!pageSettings.show_in_menu;savePageSettings()">
                            <div class="tgl-thumb"></div>
                        </div>
                    </label>
                </div>
                <div style="border-top:1px solid #334155;padding-top:12px">
                    <p class="text-[10px] font-semibold text-slate-400 mb-2">SEO</p>
                    <div class="space-y-2">
                        <div>
                            <label class="block text-[10px] text-slate-500 mb-1">عنوان متا</label>
                            <input x-model="pageSettings.meta_title" type="text" class="inp" @change="savePageSettings()">
                        </div>
                        <div>
                            <label class="block text-[10px] text-slate-500 mb-1">توضیحات متا</label>
                            <textarea x-model="pageSettings.meta_description" rows="3" class="inp resize-none" @change="savePageSettings()"></textarea>
                        </div>
                    </div>
                </div>
                <div style="border-top:1px solid #334155;padding-top:12px">
                    <label class="block text-[10px] font-semibold text-slate-400 mb-1">ترتیب نمایش در منو</label>
                    <input x-model.number="pageSettings.order" type="number" min="0" class="inp" @change="savePageSettings()">
                </div>
                <div style="border-top:1px solid #334155;padding-top:12px">
                    <p class="text-[10px] font-semibold text-slate-400 mb-2">هدر و فوتر این صفحه</p>
                    <div class="space-y-2">
                        <div>
                            <label class="block text-[10px] text-slate-500 mb-1">هدر</label>
                            <select x-model="pageSettings.builder_header_override" class="inp" @change="savePageSettings()">
                                <template x-for="(label, value) in HEADER_OPTIONS" :key="value">
                                    <option :value="value" x-text="label"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] text-slate-500 mb-1">فوتر</label>
                            <select x-model="pageSettings.builder_footer_override" class="inp" @change="savePageSettings()">
                                <template x-for="(label, value) in FOOTER_OPTIONS" :key="value">
                                    <option :value="value" x-text="label"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- LEFT TAB: REVISION HISTORY ──────────────────────────────── --}}
            <div x-show="leftTab==='history'" class="flex flex-col flex-1 overflow-hidden">
                <div class="px-3 py-2.5 shrink-0 flex items-center justify-between" style="border-bottom:1px solid #334155">
                    <div>
                        <p class="text-xs font-semibold text-white">تاریخچه بازیابی</p>
                        <p class="text-[10px] mt-0.5" style="color:#475569">آخرین ۷ روز ذخیره می‌شود</p>
                    </div>
                    <button @click="loadRevisions()" class="text-[10px] text-indigo-400 hover:text-white transition">↺ بارگذاری</button>
                </div>

                <div class="flex-1 overflow-y-auto">
                    <div x-show="revisionsLoading" class="flex items-center justify-center py-8">
                        <span class="upload-spinner"></span>
                    </div>
                    <div x-show="!revisionsLoading && revisions.length === 0" class="text-center py-8 text-slate-600 text-xs px-4">
                        <div class="text-3xl mb-2 opacity-20">🕐</div>
                        <p>هنوز هیچ نسخه ذخیره‌ای وجود ندارد.</p>
                        <p class="mt-1 text-slate-700">با هر ذخیره، نسخه قبلی اینجا نگه داشته می‌شود.</p>
                    </div>

                    <template x-for="(rev, i) in revisions" :key="rev.id">
                        <div class="mx-2 my-1.5 p-3 rounded-lg" style="border:1px solid #334155;background:#0f172a">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex-1 min-w-0">
                                    <p class="text-[11px] font-semibold text-white" x-text="rev.label || 'نسخه ذخیره'"></p>
                                    <p class="text-[10px] mt-0.5" style="color:#475569" x-text="rev.time_ago"></p>
                                    <p class="text-[10px] font-mono mt-0.5" style="color:#334155" x-text="rev.date"></p>
                                    <p x-show="rev.saved_by" class="text-[10px] mt-0.5" style="color:#475569" x-text="'توسط: '+(rev.saved_by||'')"></p>
                                </div>
                                <span class="text-[10px] text-slate-600">#<span x-text="revisions.length - i"></span></span>
                            </div>
                            <button @click="restoreRevision(rev)"
                                class="mt-2 w-full py-1.5 rounded-md text-[11px] font-medium transition flex items-center justify-center gap-1"
                                style="border:1px solid #4f46e5;color:#818cf8"
                                @mouseenter="$el.style.background='#1e1b4b'"
                                @mouseleave="$el.style.background='transparent'">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                بازیابی این نسخه
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- ── CENTER: LIVE CANVAS ─────────────────────────────────────── --}}
        <div class="flex-1 flex flex-col overflow-hidden relative" style="background:#0f172a">

            {{-- Canvas status bar --}}
            <div class="h-8 flex items-center justify-center gap-3 shrink-0" style="background:#0f172a;border-bottom:1px solid #1e293b">
                <span class="text-[10px] text-slate-600" data-testid="tb-block-count" x-text="blocks.length + ' ویجت'"></span>
                <span class="text-[10px] text-slate-700">•</span>
                <span class="text-[10px] text-slate-600">کلیک روی ویجت برای ویرایش</span>
                <span x-show="canvasLoading" class="flex items-center gap-1 text-[10px] text-indigo-400">
                    <span class="upload-spinner"></span>
                    بارگذاری...
                </span>
            </div>

            {{-- Device wrapper --}}
            <div class="flex-1 flex justify-center overflow-auto py-4 px-4" style="background:#0a0f1a">
                <div class="relative transition-all duration-300 h-full"
                     :style="device==='mobile'?'width:390px':device==='tablet'?'width:768px':'width:100%'"
                     :class="device==='mobile'?'device-mobile':device==='tablet'?'device-tablet':''">

                    {{-- Mobile/Tablet frame --}}
                    <div x-show="device !== 'desktop'" class="absolute inset-0 pointer-events-none z-10 rounded-2xl" style="border:2px solid #334155;border-radius:20px"></div>

                    <iframe
                        id="live-canvas"
                        src="about:blank"
                        class="w-full h-full rounded-lg"
                        style="min-height:600px"
                        @load="onIframeLoad()"
                    ></iframe>
                </div>
            </div>

            {{-- Add first block CTA --}}
            <div x-show="blocks.length === 0 && !canvasLoading"
                 class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                <div class="text-5xl mb-4 opacity-20">🧩</div>
                <p class="text-slate-600 text-sm font-medium">از پنل چپ ویجت انتخاب کنید</p>
            </div>
        </div>

        {{-- ── RIGHT PANEL (Content / Style / Advanced) ───────────────── --}}
        <div class="w-72 flex flex-col shrink-0 overflow-hidden z-20" style="background:#1e293b;border-right:1px solid #334155">

            {{-- Empty state --}}
            <div x-show="selectedIndex < 0" class="flex flex-col items-center justify-center h-full text-center px-6">
                <div class="text-4xl mb-3 opacity-10">✏️</div>
                <p class="text-slate-600 text-xs">روی یک ویجت در Canvas کلیک کنید</p>
            </div>

            <template x-if="selectedIndex >= 0 && selectedBlock">
                <div class="flex flex-col h-full overflow-hidden">

                    {{-- Block header --}}
                    <div class="px-3 py-2 shrink-0 flex items-center justify-between" style="border-bottom:1px solid #334155">
                        <div class="flex items-center gap-2">
                            <span class="text-sm" x-text="BLOCK_EMOJIS[selectedBlock.type]||'🧩'"></span>
                            <div>
                                <span class="text-xs font-bold text-white" x-text="blockLabel(selectedBlock.type)"></span>
                                <p class="text-[10px] font-mono mt-0.5" style="color:#475569" x-text="selectedBlock.type"></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1">
                            <button @click="duplicateBlock(selectedIndex)" class="btn-icon text-slate-500 hover:text-white" title="کپی">⎘</button>
                            <button @click="removeBlock(selectedIndex)" class="btn-icon hover:text-red-400 text-slate-500" title="حذف">×</button>
                        </div>
                    </div>

                    {{-- Right Tab bar --}}
                    <div class="flex shrink-0" style="border-bottom:1px solid #334155">
                        <button @click="rightTab='content'" :class="rightTab==='content'?'rtab-active':'text-slate-500 hover:text-slate-300'"
                            class="flex-1 py-2 text-[10px] font-semibold transition">محتوا</button>
                        <button @click="rightTab='style'" :class="rightTab==='style'?'rtab-active':'text-slate-500 hover:text-slate-300'"
                            class="flex-1 py-2 text-[10px] font-semibold transition">استایل</button>
                        <button @click="rightTab='advanced'" :class="rightTab==='advanced'?'rtab-active':'text-slate-500 hover:text-slate-300'"
                            class="flex-1 py-2 text-[10px] font-semibold transition">پیشرفته</button>
                    </div>

                    {{-- Right content area --}}
                    <div class="flex-1 overflow-y-auto">

                        {{-- ─ TAB: CONTENT (block props) ───────────────── --}}
                        <div x-show="rightTab==='content'" class="px-3 py-3 space-y-4">
                            <template x-for="(schemaDef, propKey) in selectedSchema" :key="propKey">
                                <div :data-testid="'tb-prop-' + propKey">
                                    <label class="block text-[10px] font-semibold mb-1.5" style="color:#94a3b8" x-text="schemaDef.label||propKey"></label>

                                    <template x-if="schemaDef.type==='string'">
                                        <input type="text" x-model="selectedBlock.props[propKey]" @input="markDirty()"
                                            class="inp" :placeholder="schemaDef.default??''">
                                    </template>
                                    <template x-if="schemaDef.type==='number'">
                                        <input type="number" x-model.number="selectedBlock.props[propKey]" @input="markDirty()" class="inp">
                                    </template>
                                    <template x-if="schemaDef.type==='boolean'">
                                        <div class="flex items-center gap-2">
                                            <div class="tgl-track" :class="selectedBlock.props[propKey]?'on':''" @click="selectedBlock.props[propKey]=!selectedBlock.props[propKey];markDirty()">
                                                <div class="tgl-thumb"></div>
                                            </div>
                                            <span class="text-xs" style="color:#94a3b8" x-text="selectedBlock.props[propKey]?'فعال':'غیرفعال'"></span>
                                        </div>
                                    </template>
                                    <template x-if="schemaDef.type==='select'">
                                        <select x-model="selectedBlock.props[propKey]" @change="markDirty()" class="inp">
                                            <template x-for="(lbl,val) in schemaDef.options" :key="val">
                                                <option :value="val" x-text="lbl"></option>
                                            </template>
                                        </select>
                                    </template>
                                    <template x-if="schemaDef.type==='color'">
                                        <div class="flex items-center gap-2">
                                            <input type="color" x-model="selectedBlock.props[propKey]" @input="markDirty()" class="w-9 h-9 rounded-lg border cursor-pointer p-0.5 bg-transparent" style="border-color:#334155">
                                            <input type="text" x-model="selectedBlock.props[propKey]" @input="markDirty()" class="inp font-mono" placeholder="#000000">
                                            <button x-show="selectedBlock.props[propKey]" @click="selectedBlock.props[propKey]='';markDirty()" class="text-slate-600 hover:text-red-400 transition text-base leading-none">×</button>
                                        </div>
                                    </template>
                                    <template x-if="schemaDef.type==='textarea'">
                                        <textarea x-model="selectedBlock.props[propKey]" @input="markDirty()" rows="3"
                                            class="inp resize-y" :placeholder="schemaDef.default??''"></textarea>
                                    </template>
                                    <template x-if="schemaDef.type==='richtext'">
                                        <div class="quill-host" x-init="$nextTick(()=>initQuill($el, propKey))">
                                            <div class="quill-inner"></div>
                                        </div>
                                    </template>
                                    <template x-if="schemaDef.type==='image'">
                                        <div class="space-y-2">
                                            <div x-show="selectedBlock.props[propKey]" class="relative rounded-lg overflow-hidden group" style="border:1px solid #334155">
                                                <img :src="selectedBlock.props[propKey]" class="w-full h-24 object-cover" onerror="this.parentElement.style.display='none'">
                                                <button @click="selectedBlock.props[propKey]='';markDirty()"
                                                    class="absolute top-1 left-1 w-5 h-5 rounded-full bg-red-600 text-white text-xs flex items-center justify-center opacity-0 group-hover:opacity-100 transition">×</button>
                                            </div>
                                            <input type="text" x-model="selectedBlock.props[propKey]" @input="markDirty()" class="inp text-[11px]" placeholder="URL تصویر">
                                            <label class="cursor-pointer w-full flex items-center justify-center gap-2 py-2 rounded-lg text-[11px] font-medium transition"
                                                   style="border:2px dashed #334155;color:#6366f1"
                                                   :class="uploadingProp===propKey?'opacity-50':''">
                                                <span x-show="uploadingProp!==propKey" class="flex items-center gap-1.5">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                    آپلود تصویر
                                                </span>
                                                <span x-show="uploadingProp===propKey" class="flex items-center gap-1.5"><span class="upload-spinner"></span> آپلود...</span>
                                                <input type="file" accept="image/*" class="hidden" @change="uploadImageProp($event,propKey)">
                                            </label>
                                        </div>
                                    </template>
                                    <template x-if="schemaDef.type==='repeater'">
                                        <div class="space-y-2">
                                            <template x-for="(item,itemIdx) in (selectedBlock.props[propKey]||[])" :key="itemIdx">
                                                <div class="rep-item">
                                                    <div class="flex items-center justify-between mb-2">
                                                        <span class="text-[10px] font-semibold" style="color:#94a3b8" x-text="'#'+(itemIdx+1)"></span>
                                                        <div class="flex gap-1">
                                                            <button @click="moveRepItem(propKey,itemIdx,itemIdx-1)" :disabled="itemIdx===0" class="w-4 h-4 flex items-center justify-center rounded text-slate-600 hover:text-white disabled:opacity-30 text-[10px]">▲</button>
                                                            <button @click="moveRepItem(propKey,itemIdx,itemIdx+1)" :disabled="itemIdx>=(selectedBlock.props[propKey]||[]).length-1" class="w-4 h-4 flex items-center justify-center rounded text-slate-600 hover:text-white disabled:opacity-30 text-[10px]">▼</button>
                                                            <button @click="removeRepItem(propKey,itemIdx)" class="w-4 h-4 flex items-center justify-center rounded text-red-500 hover:text-red-400 text-xs leading-none">×</button>
                                                        </div>
                                                    </div>
                                                    <div class="space-y-2">
                                                        <template x-for="(subDef,subKey) in (schemaDef.item_schema||{})" :key="subKey">
                                                            <div>
                                                                <label class="block text-[9px] font-medium mb-0.5" style="color:#64748b" x-text="subDef.label||subKey"></label>
                                                                <template x-if="subDef.type==='string'"><input type="text" :value="item[subKey]??''" @input.debounce.300ms="updateRepItem(propKey,itemIdx,subKey,$event.target.value)" class="inp text-[11px]" :placeholder="subDef.default??''"></template>
                                                                <template x-if="subDef.type==='textarea'"><textarea :value="item[subKey]??''" @input.debounce.300ms="updateRepItem(propKey,itemIdx,subKey,$event.target.value)" rows="2" class="inp text-[11px] resize-y"></textarea></template>
                                                                <template x-if="subDef.type==='boolean'">
                                                                    <div class="flex items-center gap-2">
                                                                        <div class="tgl-track" :class="(item[subKey]??false)?'on':''" @click="updateRepItem(propKey,itemIdx,subKey,!(item[subKey]??false))"><div class="tgl-thumb"></div></div>
                                                                        <span class="text-[10px] text-slate-500" x-text="(item[subKey]??false)?'بله':'خیر'"></span>
                                                                    </div>
                                                                </template>
                                                                <template x-if="subDef.type==='select'"><select :value="item[subKey]??subDef.default??''" @change="updateRepItem(propKey,itemIdx,subKey,$event.target.value)" class="inp text-[11px]"><template x-for="(lbl,val) in (subDef.options||{})" :key="val"><option :value="val" x-text="lbl" :selected="(item[subKey]??subDef.default)==val"></option></template></select></template>
                                                                <template x-if="subDef.type==='color'">
                                                                    <div class="flex gap-2 items-center"><input type="color" :value="item[subKey]||'#6366f1'" @input.debounce.300ms="updateRepItem(propKey,itemIdx,subKey,$event.target.value)" class="w-8 h-8 rounded border bg-transparent cursor-pointer p-0.5" style="border-color:#334155"><input type="text" :value="item[subKey]??''" @input.debounce.300ms="updateRepItem(propKey,itemIdx,subKey,$event.target.value)" class="inp text-[11px] font-mono" placeholder="#000000"></div>
                                                                </template>
                                                                <template x-if="subDef.type==='image'">
                                                                    <div class="space-y-1">
                                                                        <div x-show="item[subKey]" class="relative rounded overflow-hidden group" style="border:1px solid #334155">
                                                                            <img :src="item[subKey]" class="w-full h-14 object-cover" onerror="this.parentElement.style.display='none'">
                                                                            <button @click="updateRepItem(propKey,itemIdx,subKey,'')" class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-red-600 text-white text-xs flex items-center justify-center opacity-0 group-hover:opacity-100 transition">×</button>
                                                                        </div>
                                                                        <div class="flex gap-1.5">
                                                                            <input type="text" :value="item[subKey]??''" @input.debounce.300ms="updateRepItem(propKey,itemIdx,subKey,$event.target.value)" class="inp text-[11px] flex-1" placeholder="URL">
                                                                            <label class="cursor-pointer px-2 py-1 rounded-md text-[10px] font-medium transition flex items-center" style="border:1px solid #4f46e5;color:#818cf8" :class="uploadingRepKey===propKey+'_'+itemIdx+'_'+subKey?'opacity-50':''">
                                                                                <span x-show="uploadingRepKey!==propKey+'_'+itemIdx+'_'+subKey">📷</span>
                                                                                <span x-show="uploadingRepKey===propKey+'_'+itemIdx+'_'+subKey" class="upload-spinner"></span>
                                                                                <input type="file" accept="image/*" class="hidden" @change="uploadRepImage($event,propKey,itemIdx,subKey)">
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                </template>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>
                                            <button @click="addRepItem(propKey,schemaDef)"
                                                class="w-full py-2 rounded-lg text-[11px] font-medium transition flex items-center justify-center gap-1"
                                                style="border:2px dashed #334155;color:#475569"
                                                @mouseenter="$el.style.borderColor='#6366f1';$el.style.color='#818cf8'"
                                                @mouseleave="$el.style.borderColor='#334155';$el.style.color='#475569'">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                                <span x-text="'افزودن '+( schemaDef.label||'مورد')"></span>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </template>
                            <div x-show="Object.keys(selectedSchema).length===0" class="text-center py-8 text-slate-600 text-xs">این ویجت تنظیماتی ندارد</div>
                        </div>

                        {{-- ─ TAB: STYLE ───────────────────────────────── --}}
                        <div x-show="rightTab==='style'" class="px-3 py-3 space-y-4">

                            {{-- Background --}}
                            <div>
                                <p class="text-[10px] font-semibold text-slate-400 mb-2">پس‌زمینه</p>
                                <div class="space-y-2">
                                    <select x-model="selectedBlock._style.bg_type" @change="markDirty()" class="inp">
                                        <option value="">بدون پس‌زمینه</option>
                                        <option value="color">رنگ یکنواخت</option>
                                        <option value="gradient">گرادیانت</option>
                                        <option value="image">تصویر</option>
                                    </select>
                                    <template x-if="selectedBlock._style.bg_type==='color'">
                                        <div class="flex items-center gap-2">
                                            <input type="color" x-model="selectedBlock._style.bg_color" @input="markDirty()" class="w-9 h-9 rounded-lg border p-0.5 cursor-pointer bg-transparent" style="border-color:#334155">
                                            <input type="text" x-model="selectedBlock._style.bg_color" @input="markDirty()" class="inp font-mono" placeholder="#ffffff">
                                        </div>
                                    </template>
                                    <template x-if="selectedBlock._style.bg_type==='gradient'">
                                        <div class="space-y-2">
                                            <div class="flex items-center gap-2">
                                                <span class="text-[10px] text-slate-500 shrink-0">رنگ ۱</span>
                                                <input type="color" x-model="selectedBlock._style.grad_from" @input="markDirty()" class="w-9 h-9 rounded-lg border p-0.5 cursor-pointer bg-transparent" style="border-color:#334155">
                                                <span class="text-[10px] text-slate-500 shrink-0">رنگ ۲</span>
                                                <input type="color" x-model="selectedBlock._style.grad_to" @input="markDirty()" class="w-9 h-9 rounded-lg border p-0.5 cursor-pointer bg-transparent" style="border-color:#334155">
                                            </div>
                                            <select x-model="selectedBlock._style.grad_dir" @change="markDirty()" class="inp">
                                                <option value="to right">راست به چپ</option>
                                                <option value="to bottom right">ضلع پایین-راست</option>
                                                <option value="to bottom">بالا به پایین</option>
                                                <option value="to bottom left">ضلع پایین-چپ</option>
                                            </select>
                                        </div>
                                    </template>
                                    <template x-if="selectedBlock._style.bg_type==='image'">
                                        <div class="space-y-2">
                                            <div x-show="selectedBlock._style.bg_image" class="relative rounded-lg overflow-hidden group" style="border:1px solid #334155">
                                                <img :src="selectedBlock._style.bg_image" class="w-full h-16 object-cover" onerror="this.parentElement.style.display='none'">
                                                <button @click="selectedBlock._style.bg_image='';markDirty()" class="absolute top-1 left-1 w-5 h-5 rounded-full bg-red-600 text-white text-xs flex items-center justify-center opacity-0 group-hover:opacity-100 transition">×</button>
                                            </div>
                                            <input type="text" x-model="selectedBlock._style.bg_image" @input="markDirty()" class="inp text-[11px]" placeholder="URL تصویر">
                                            <label class="cursor-pointer w-full flex items-center justify-center gap-1.5 py-1.5 rounded-lg text-[11px] font-medium transition" style="border:2px dashed #334155;color:#6366f1" :class="uploadingProp==='_bg_img'?'opacity-50':''">
                                                <span x-show="uploadingProp!=='_bg_img'">📷 آپلود تصویر پس‌زمینه</span>
                                                <span x-show="uploadingProp==='_bg_img'" class="flex items-center gap-1"><span class="upload-spinner"></span> آپلود...</span>
                                                <input type="file" accept="image/*" class="hidden" @change="uploadBgImage($event)">
                                            </label>
                                            <select x-model="selectedBlock._style.bg_size" @change="markDirty()" class="inp">
                                                <option value="cover">cover — پوشش کامل</option>
                                                <option value="contain">contain — درون کادر</option>
                                                <option value="auto">auto</option>
                                            </select>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Padding --}}
                            <div>
                                <p class="text-[10px] font-semibold text-slate-400 mb-2">فاصله داخلی (Padding)</p>
                                <select x-model="selectedBlock._style.padding" @change="markDirty()" class="inp mb-2">
                                    <option value="">پیش‌فرض</option>
                                    <option value="p-0">بدون فاصله</option>
                                    <option value="p-2">خیلی کوچک (8px)</option>
                                    <option value="p-4">کوچک (16px)</option>
                                    <option value="p-6">متوسط (24px)</option>
                                    <option value="p-8">بزرگ (32px)</option>
                                    <option value="p-12">خیلی بزرگ (48px)</option>
                                    <option value="p-16">عظیم (64px)</option>
                                    <option value="custom">سفارشی...</option>
                                </select>
                                <template x-if="selectedBlock._style.padding==='custom'">
                                    <div class="grid grid-cols-2 gap-2">
                                        <div><label class="text-[10px] text-slate-600">بالا (px)</label><input type="number" x-model.number="selectedBlock._style.pt" @input="markDirty()" class="inp text-[11px]" placeholder="0"></div>
                                        <div><label class="text-[10px] text-slate-600">پایین (px)</label><input type="number" x-model.number="selectedBlock._style.pb" @input="markDirty()" class="inp text-[11px]" placeholder="0"></div>
                                        <div><label class="text-[10px] text-slate-600">راست (px)</label><input type="number" x-model.number="selectedBlock._style.pr" @input="markDirty()" class="inp text-[11px]" placeholder="0"></div>
                                        <div><label class="text-[10px] text-slate-600">چپ (px)</label><input type="number" x-model.number="selectedBlock._style.pl" @input="markDirty()" class="inp text-[11px]" placeholder="0"></div>
                                    </div>
                                </template>
                            </div>

                            {{-- Margin --}}
                            <div>
                                <p class="text-[10px] font-semibold text-slate-400 mb-2">فاصله خارجی (Margin)</p>
                                <select x-model="selectedBlock._style.margin" @change="markDirty()" class="inp">
                                    <option value="">پیش‌فرض</option>
                                    <option value="m-0">بدون فاصله</option>
                                    <option value="my-2">کوچک عمودی</option>
                                    <option value="my-4">متوسط عمودی</option>
                                    <option value="my-8">بزرگ عمودی</option>
                                    <option value="my-12">خیلی بزرگ عمودی</option>
                                </select>
                            </div>

                            {{-- Border radius --}}
                            <div>
                                <p class="text-[10px] font-semibold text-slate-400 mb-2">گوشه‌ها</p>
                                <select x-model="selectedBlock._style.radius" @change="markDirty()" class="inp">
                                    <option value="">پیش‌فرض</option>
                                    <option value="rounded-none">بدون گوشه</option>
                                    <option value="rounded">کوچک</option>
                                    <option value="rounded-md">متوسط</option>
                                    <option value="rounded-xl">بزرگ</option>
                                    <option value="rounded-2xl">خیلی بزرگ</option>
                                    <option value="rounded-full">کاملاً گرد</option>
                                </select>
                            </div>

                            {{-- Shadow --}}
                            <div>
                                <p class="text-[10px] font-semibold text-slate-400 mb-2">سایه</p>
                                <select x-model="selectedBlock._style.shadow" @change="markDirty()" class="inp">
                                    <option value="">بدون سایه</option>
                                    <option value="shadow-sm">کوچک</option>
                                    <option value="shadow">متوسط</option>
                                    <option value="shadow-md">قوی</option>
                                    <option value="shadow-lg">خیلی قوی</option>
                                    <option value="shadow-xl">بزرگ</option>
                                    <option value="shadow-2xl">عظیم</option>
                                </select>
                            </div>

                            {{-- Text color --}}
                            <div>
                                <p class="text-[10px] font-semibold text-slate-400 mb-2">رنگ متن</p>
                                <div class="flex items-center gap-2">
                                    <input type="color" x-model="selectedBlock._style.text_color" @input="markDirty()" class="w-9 h-9 rounded-lg border p-0.5 cursor-pointer bg-transparent" style="border-color:#334155">
                                    <input type="text" x-model="selectedBlock._style.text_color" @input="markDirty()" class="inp font-mono" placeholder="ارث‌بری">
                                    <button x-show="selectedBlock._style.text_color" @click="selectedBlock._style.text_color='';markDirty()" class="text-slate-600 hover:text-red-400 transition">×</button>
                                </div>
                            </div>

                            {{-- Opacity --}}
                            <div>
                                <p class="text-[10px] font-semibold text-slate-400 mb-2">شفافیت: <span class="text-indigo-400" x-text="Math.round((selectedBlock._style.opacity??1)*100)+'%'"></span></p>
                                <input type="range" min="0.1" max="1" step="0.05"
                                    x-model.number="selectedBlock._style.opacity" @input="markDirty()"
                                    class="w-full h-1.5 appearance-none rounded-full cursor-pointer"
                                    style="background:#334155">
                            </div>

                            {{-- Border --}}
                            <div>
                                <p class="text-[10px] font-semibold text-slate-400 mb-2">کادر (Border)</p>
                                <div class="space-y-2">
                                    <div class="flex gap-2">
                                        <input type="color" x-model="selectedBlock._style.border_color" @input="markDirty()" class="w-9 h-9 rounded-lg border p-0.5 cursor-pointer bg-transparent shrink-0" style="border-color:#334155">
                                        <input type="number" x-model.number="selectedBlock._style.border_width" @input="markDirty()" class="inp" placeholder="0 (px)">
                                        <select x-model="selectedBlock._style.border_style" @change="markDirty()" class="inp">
                                            <option value="solid">solid</option>
                                            <option value="dashed">dashed</option>
                                            <option value="dotted">dotted</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ─ TAB: ADVANCED ─────────────────────────────── --}}
                        <div x-show="rightTab==='advanced'" class="px-3 py-3 space-y-4">
                            <div>
                                <p class="text-[10px] font-semibold text-slate-400 mb-2">نمایش و پنهان کردن</p>
                                <div class="space-y-3">
                                    <label class="flex items-center justify-between cursor-pointer">
                                        <span class="text-[10px] text-slate-400">پنهان در موبایل</span>
                                        <div class="tgl-track" :class="selectedBlock._style.hide_mobile?'on':''" @click="selectedBlock._style.hide_mobile=!selectedBlock._style.hide_mobile;markDirty()"><div class="tgl-thumb"></div></div>
                                    </label>
                                    <label class="flex items-center justify-between cursor-pointer">
                                        <span class="text-[10px] text-slate-400">پنهان در دسکتاپ</span>
                                        <div class="tgl-track" :class="selectedBlock._style.hide_desktop?'on':''" @click="selectedBlock._style.hide_desktop=!selectedBlock._style.hide_desktop;markDirty()"><div class="tgl-thumb"></div></div>
                                    </label>
                                </div>
                            </div>
                            <div>
                                <p class="text-[10px] font-semibold text-slate-400 mb-2">انیمیشن ورودی</p>
                                <select x-model="selectedBlock._style.animation" @change="markDirty()" class="inp">
                                    <option value="">بدون انیمیشن</option>
                                    <option value="fade-in">محو شدن (Fade In)</option>
                                    <option value="slide-up">لغزش از پایین</option>
                                    <option value="slide-down">لغزش از بالا</option>
                                    <option value="slide-right">لغزش از راست</option>
                                    <option value="slide-left">لغزش از چپ</option>
                                    <option value="zoom-in">بزرگ شدن (Zoom In)</option>
                                    <option value="bounce">پرشی (Bounce)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-400 mb-1">تاخیر انیمیشن (ms)</label>
                                <input type="number" x-model.number="selectedBlock._style.anim_delay" @input="markDirty()" class="inp" placeholder="0" min="0" step="100">
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-400 mb-1">کلاس CSS سفارشی</label>
                                <input type="text" x-model="selectedBlock._style.css_class" @input="markDirty()" class="inp font-mono text-[11px]" placeholder="my-class another-class">
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-400 mb-1">شناسه HTML (ID)</label>
                                <input type="text" x-model="selectedBlock._style.html_id" @input="markDirty()" class="inp font-mono text-[11px]" placeholder="section-id">
                                <p class="text-[10px] mt-1" style="color:#475569">برای لینک‌های لنگر (anchor): #section-id</p>
                            </div>
                            <div style="border-top:1px solid #334155;padding-top:12px" class="flex gap-2">
                                <button @click="duplicateBlock(selectedIndex)" class="flex-1 py-2 rounded-lg text-[11px] font-medium text-indigo-400 hover:text-white transition" style="border:1px solid #4f46e5">
                                    ⎘ کپی ویجت
                                </button>
                                <button @click="removeBlock(selectedIndex)" class="flex-1 py-2 rounded-lg text-[11px] font-medium text-red-400 hover:text-white transition" style="border:1px solid #7f1d1d">
                                    × حذف
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Toast --}}
    <div x-show="toast.show" x-transition.opacity
         :class="toast.type==='success'?'bg-green-700':'bg-red-700'"
         class="fixed bottom-5 left-1/2 -translate-x-1/2 text-white text-xs font-medium px-4 py-2.5 rounded-xl shadow-2xl z-50 flex items-center gap-2">
        <span x-text="toast.type==='success'?'✓':'✗'"></span>
        <span x-text="toast.msg"></span>
    </div>
</div>

<script>
const CSRF_TOKEN   = '{{ csrf_token() }}';
const BLOCK_LABELS = @json($blockLabels);
const BLOCK_SCHEMAS= @json($blockSchemas);
const INITIAL_BLOCKS= @json($initialBlocks);
const ROUTES = {
    save:         '{{ $saveUrl }}',
    preview:      '{{ $previewUrl }}',
    listPages:    '{{ $listPagesUrl }}',
    createPage:   '{{ $createPageUrl }}',
    pageSettings: '{{ $pageSettingsUrl }}',
    uploadImage:  '{{ $uploadUrl }}',
    revisionBase: '{{ url("/builder/api/revision") }}',
};
const PAGE_META = {
    id:           {{ $pageId }},
    title:        @json($pageTitle),
    slug:         @json($pageSlug),
    is_published: {{ $pagePublished ? 'true' : 'false' }},
    show_in_menu: {{ $pageShowInMenu ? 'true' : 'false' }},
    meta_title:   @json($pageMetaTitle),
    meta_description: @json($pageMetaDesc),
    order:        {{ (int) ($pageOrder ?? 0) }},
    builder_header_override: @json($pageHeaderOverride ?? ''),
    builder_footer_override: @json($pageFooterOverride ?? ''),
};
// UI-C5: header/footer template choices — same options admin.pages.edit
// (the legacy settings form) used, now also available from the one
// canonical Page Builder settings tab.
const HEADER_OPTIONS = @json($headerOptions ?? []);
const FOOTER_OPTIONS = @json($footerOptions ?? []);
const BLOCK_EMOJIS = {
    "hero":"🌟","section":"📦","row":"⇄","column":"▤","grid":"🔲","columns":"▥",
    "tabs":"📑","accordion":"🪗","slider":"🎠","heading":"🔠","text":"📄",
    "image":"🖼️","button":"🔘","icon":"⭐","divider":"➖","spacer":"↕️","video":"🎬",
    "gallery":"🗂️","cta":"📢","testimonials":"⭐","blog_posts":"📰","product_grid":"🛒",
    "faq":"❓","countdown":"⏱️","social_icons":"🔗","promo_banner":"📣","custom_html":"⌨️",
    "card":"🃏","features_grid":"✨","counter_grid":"🔢","pricing_card":"💳",
    "team_grid":"👥","testimonial_card":"💬","alert_box":"⚠️","map_embed":"🗺️",
    "contact_form":"📬","timeline":"📅","newsletter":"📧"
};
const QUILL_TOOLBAR = [
    [{ header: [1,2,3,false] }],
    ['bold','italic','underline','strike'],
    [{ list:'ordered'},{list:'bullet'}],
    ['link'],
    [{ align:[] }],
    ['clean']
];
@php $__orderedCats = $orderedCats ?? ['layout','content','marketing','interactive','dynamic','ecommerce','utility']; @endphp
const ORDEREDCATS = @json($__orderedCats);

// ── Default _style object ─────────────────────────────────────────────────────
function defaultStyle() {
    return {
        bg_type:'', bg_color:'', bg_image:'', bg_size:'cover',
        grad_from:'#6366f1', grad_to:'#8b5cf6', grad_dir:'to right',
        padding:'', pt:0, pb:0, pr:0, pl:0,
        margin:'', radius:'', shadow:'',
        text_color:'', opacity:1,
        border_color:'', border_width:0, border_style:'solid',
        hide_mobile:false, hide_desktop:false,
        animation:'', anim_delay:0,
        css_class:'', html_id:'',
    };
}

function elBuilder() {
    return {
        // blocks
        blocks:        JSON.parse(JSON.stringify(INITIAL_BLOCKS || [])),
        selectedIndex: -1,
        isDirty:       false,
        saving:        false,
        canvasLoading: false,
        canvasTimer:   null,
        quillInstances:{},
        uploadingProp: null,
        uploadingRepKey: null,
        // UI state
        leftTab:  'blocks',
        rightTab: 'content',
        device:   'desktop',
        expandedCats: { layout:true, content:true, marketing:false, interactive:false, dynamic:false, ecommerce:false, utility:false },
        librarySearch: '',
        // pages
        pages:         [],
        pagesLoading:  false,
        showCreatePage:false,
        newPageTitle:  '',
        // revisions
        revisions:       [],
        revisionsLoading:false,
        insertAfterIdx:  -1,
        // page settings
        pageSettings: { ...PAGE_META },
        pageSettingsSaving: false,
        // undo/redo
        history: [],
        historyIdx: -1,
        // toast
        toast: { show:false, msg:'', type:'success', _timer:null },

        // ── Computed ──────────────────────────────────────────────────────────
        get selectedBlock() {
            if (this.selectedIndex < 0 || this.selectedIndex >= this.blocks.length) return null;
            return this.blocks[this.selectedIndex];
        },
        get selectedSchema() {
            if (!this.selectedBlock) return {};
            return BLOCK_SCHEMAS[this.selectedBlock.type]?.schema ?? {};
        },

        // ── Library ───────────────────────────────────────────────────────────
        toggleCat(key)     { this.expandedCats[key] = !this.expandedCats[key]; },
        isCatVisible(key)  {
            if (!this.librarySearch) return true;
            const s = this.librarySearch.toLowerCase();
            return Object.entries(BLOCK_LABELS).some(([t, lbl]) =>
                t.includes(s) || (lbl+'').toLowerCase().includes(s)
            );
        },
        isBlockVisible(type, name) {
            if (!this.librarySearch) return true;
            const s = this.librarySearch.toLowerCase();
            return type.toLowerCase().includes(s) || name.toLowerCase().includes(s);
        },

        // ── Blocks CRUD ───────────────────────────────────────────────────────
        addBlock(type) {
            const defs  = JSON.parse(JSON.stringify(BLOCK_SCHEMAS[type]?.defaultProps ?? {}));
            const block = { id:'blk_'+Date.now()+'_'+Math.random().toString(36).slice(2,6), type, props: defs, _style: defaultStyle() };
            this.pushHistory();
            this.blocks = [...this.blocks, block];
            this.selectBlock(this.blocks.length - 1);
            this.isDirty = true;
            this.refreshCanvas();
        },

        removeBlock(idx) {
            this._destroyQuills();
            this.pushHistory();
            this.blocks.splice(idx, 1);
            this.blocks = [...this.blocks];
            if (this.selectedIndex >= this.blocks.length) this.selectedIndex = this.blocks.length - 1;
            else if (this.selectedIndex === idx) this.selectedIndex = -1;
            this.isDirty = true;
            this.refreshCanvas();
        },

        duplicateBlock(idx) {
            const clone = JSON.parse(JSON.stringify(this.blocks[idx]));
            clone.id = 'blk_'+Date.now()+'_'+Math.random().toString(36).slice(2,6);
            this.pushHistory();
            this.blocks.splice(idx + 1, 0, clone);
            this.blocks = [...this.blocks];
            this.selectBlock(idx + 1);
            this.isDirty = true;
            this.refreshCanvas();
        },

        selectBlock(idx) {
            this._destroyQuills();
            this.selectedIndex = idx;
            // Ensure _style exists on block
            if (idx >= 0 && this.blocks[idx] && !this.blocks[idx]._style) {
                this.blocks[idx]._style = defaultStyle();
            }
            this.rightTab = 'content';
            // Highlight in iframe
            const iframe = document.getElementById('live-canvas');
            iframe?.contentWindow?.postMessage({ type:'highlight', idx }, '*');
        },

        moveBlock(from, to) {
            if (to < 0 || to >= this.blocks.length) return;
            const arr = [...this.blocks];
            const [item] = arr.splice(from, 1);
            arr.splice(to, 0, item);
            this.pushHistory();
            this.blocks = arr;
            this.selectedIndex = to;
            this.isDirty = true;
            this.refreshCanvas();
        },

        // ── Undo / Redo ───────────────────────────────────────────────────────
        pushHistory() {
            this.history = this.history.slice(0, this.historyIdx + 1);
            this.history.push(JSON.stringify(this.blocks));
            if (this.history.length > 50) { this.history.shift(); }
            else { this.historyIdx++; }
        },
        undo() {
            if (this.historyIdx <= 0) return;
            this.historyIdx--;
            this._destroyQuills();
            this.blocks = JSON.parse(this.history[this.historyIdx]);
            this.selectedIndex = -1;
            this.isDirty = true;
            this.refreshCanvas();
        },
        redo() {
            if (this.historyIdx >= this.history.length - 1) return;
            this.historyIdx++;
            this._destroyQuills();
            this.blocks = JSON.parse(this.history[this.historyIdx]);
            this.selectedIndex = -1;
            this.isDirty = true;
            this.refreshCanvas();
        },

        // ── Canvas ────────────────────────────────────────────────────────────
        markDirty() {
            this.isDirty = true;
            this.debouncedCanvas();
        },

        debouncedCanvas() {
            clearTimeout(this.canvasTimer);
            this.canvasTimer = setTimeout(() => this.refreshCanvas(), 350);
        },

        async refreshCanvas() {
            if (!this.blocks.length) {
                const iframe = document.getElementById('live-canvas');
                if (iframe) {
                    const doc = iframe.contentDocument || iframe.contentWindow.document;
                    doc.open(); doc.write(this.buildIframeHtml([])); doc.close();
                }
                return;
            }
            this.canvasLoading = true;
            try {
                const r = await fetch(ROUTES.preview + '?mode=builder', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                    body: JSON.stringify({ blocks: this.blocks })
                });
                const d = await r.json();
                const blockHtmls = d.block_htmls ?? [];
                const iframe = document.getElementById('live-canvas');
                if (iframe) {
                    const doc = iframe.contentDocument || iframe.contentWindow.document;
                    doc.open();
                    doc.write(this.buildIframeHtml(blockHtmls));
                    doc.close();
                }
            } catch (e) { console.error('Canvas refresh error', e); }
            finally { this.canvasLoading = false; }
        },

        buildIframeHtml(blockHtmls) {
            const labels = BLOCK_LABELS;
            const wrappedBlocks = blockHtmls.map((html, idx) => {
                const block  = this.blocks[idx];
                const s      = block?._style ?? {};
                const lbl    = labels[block?.type] ?? block?.type ?? '';
                const isSelected = idx === this.selectedIndex;

                // Build inline style from _style
                let inlineStyle = '';
                if (s.opacity != null && s.opacity !== 1) inlineStyle += `opacity:${s.opacity};`;
                if (s.text_color) inlineStyle += `color:${s.text_color};`;
                if (s.border_width) inlineStyle += `border:${s.border_width}px ${s.border_style||'solid'} ${s.border_color||'#000'};`;
                // Background
                if (s.bg_type === 'color' && s.bg_color) inlineStyle += `background-color:${s.bg_color};`;
                if (s.bg_type === 'gradient' && s.grad_from && s.grad_to) inlineStyle += `background:linear-gradient(${s.grad_dir||'to right'},${s.grad_from},${s.grad_to});`;
                if (s.bg_type === 'image' && s.bg_image) inlineStyle += `background-image:url('${s.bg_image}');background-size:${s.bg_size||'cover'};background-position:center;`;

                // CSS classes
                const classes = [];
                if (s.padding && s.padding !== 'custom') classes.push(s.padding);
                if (s.padding === 'custom') inlineStyle += `padding:${s.pt||0}px ${s.pr||0}px ${s.pb||0}px ${s.pl||0}px;`;
                if (s.margin) classes.push(s.margin);
                if (s.radius) classes.push(s.radius);
                if (s.shadow) classes.push(s.shadow);
                if (s.css_class) classes.push(s.css_class);
                const hideClass = s.hide_mobile ? ' md:block hidden' : (s.hide_desktop ? ' block md:hidden' : '');

                const idAttr = s.html_id ? ` id="${s.html_id}"` : '';
                const selClass = isSelected ? 'bldr-selected' : '';

                return `<div class="bldr-blk-wrap${hideClass}">
  <div class="bldr-blk ${classes.join(' ')} ${selClass}"
       data-idx="${idx}"
       style="${inlineStyle}"
       ${idAttr}
       onclick="bld_select(${idx})"
       onmouseenter="this.classList.add('bldr-hover')"
       onmouseleave="this.classList.remove('bldr-hover')">
    <div class="bldr-toolbar" onclick="event.stopPropagation()">
      <button class="btb" onclick="bld_action('up',${idx})" title="بالا">▲</button>
      <button class="btb" onclick="bld_action('down',${idx})" title="پایین">▼</button>
      <button class="btb" onclick="bld_action('dup',${idx})" title="کپی">⎘</button>
      <button class="btb btb-del" onclick="bld_action('del',${idx})" title="حذف">×</button>
      <span class="bldr-label">${lbl}</span>
    </div>
    <div class="bldr-content">${html}</div>
  </div>
  <button class="bldr-add-between" onclick="bld_addafter(${idx})" title="افزودن ویجت">
    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>
    افزودن ویجت
  </button>
</div>`;
            }).join('');

            return `<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<script src="https://cdn.tailwindcss.com"><\/script>
<style>@include('partials.vazirmatn-font-faces-inline')</style>
<style>
  body{font-family:'Vazirmatn',Tahoma,sans-serif;margin:0;padding:0;direction:rtl;background:#fff;min-height:100vh}
  img{max-width:100%;height:auto}
  /* Builder overlay */
  .bldr-blk-wrap{position:relative}
  .bldr-blk{position:relative;transition:outline .1s}
  .bldr-blk.bldr-hover{outline:2px dashed rgba(99,102,241,.5);outline-offset:-1px}
  .bldr-blk.bldr-selected{outline:2px solid #6366f1!important;outline-offset:-1px}
  .bldr-toolbar{display:none;position:absolute;top:-36px;right:0;z-index:9999;background:#6366f1;border-radius:8px 8px 0 0;padding:3px 6px;gap:2px;flex-direction:row;align-items:center;font-family:Tahoma,sans-serif}
  .bldr-blk.bldr-hover .bldr-toolbar,.bldr-blk.bldr-selected .bldr-toolbar{display:flex}
  .btb{background:none;border:none;color:rgba(255,255,255,.85);cursor:pointer;padding:2px 6px;font-size:12px;border-radius:4px;line-height:1.4}
  .btb:hover{background:rgba(255,255,255,.2);color:#fff}
  .btb-del:hover{background:rgba(239,68,68,.5);color:#fff}
  .bldr-label{color:rgba(255,255,255,.6);font-size:10px;padding:2px 6px;border-right:1px solid rgba(255,255,255,.2);margin-right:2px;white-space:nowrap}
  .bldr-add-between{display:flex;align-items:center;justify-content:center;gap:4px;width:100%;padding:6px;color:#6366f1;font-size:11px;background:transparent;border:none;cursor:pointer;opacity:0;transition:.2s;font-family:'Vazirmatn',Tahoma,sans-serif}
  .bldr-blk-wrap:hover .bldr-add-between{opacity:.7}
  .bldr-add-between:hover{opacity:1!important;background:rgba(99,102,241,.05)}
  /* Block content pointer-events fix */
  .bldr-content a,.bldr-content button,.bldr-content input{pointer-events:none}
  /* Responsive hide */
  @media(max-width:768px){.md\\:hidden{display:none!important}}
  @media(min-width:769px){.md\\:block.hidden{display:block!important}.block.md\\:hidden{display:none!important}}
</style>
</head>
<body>
${wrappedBlocks || '<div style="height:100px;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:13px;font-family:Vazirmatn,sans-serif">از پنل چپ ویجت اضافه کنید</div>'}
<script>
function bld_select(idx) {
  document.querySelectorAll('.bldr-blk').forEach(b=>b.classList.remove('bldr-selected'));
  const el=document.querySelector('.bldr-blk[data-idx="'+idx+'"]');
  if(el){el.classList.add('bldr-selected');el.scrollIntoView({behavior:'smooth',block:'nearest'})}
  window.parent.postMessage({type:'select',idx},'*');
}
function bld_action(action,idx) {
  window.parent.postMessage({type:'action',action,idx},'*');
}
function bld_addafter(idx) {
  window.parent.postMessage({type:'addafter',idx},'*');
}
window.addEventListener('message',function(e){
  if(e.data?.type==='highlight'){
    document.querySelectorAll('.bldr-blk').forEach(b=>b.classList.remove('bldr-selected'));
    const el=document.querySelector('.bldr-blk[data-idx="'+e.data.idx+'"]');
    if(el){el.classList.add('bldr-selected');el.scrollIntoView({behavior:'smooth',block:'nearest'})}
  }
});
<\/script>
</body>
</html>`;
        },

        onIframeLoad() {
            // iframe loaded — nothing to do
        },

        // ── Revision History ──────────────────────────────────────────────────
        async loadRevisions() {
            @if(($pageId ?? 0) > 0)
            this.revisionsLoading = true;
            try {
                const r = await fetch('{{ route("builder.api.page.revisions", $pageId ?? 0) }}', {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }
                });
                const d = await r.json();
                this.revisions = d.revisions ?? [];
            } catch(e) { this.showToast('خطا در بارگذاری تاریخچه', 'error'); }
            finally { this.revisionsLoading = false; }
            @else
            this.revisions = [];
            @endif
        },

        async restoreRevision(rev) {
            if (!confirm('آیا مطمئن هستید؟ محتوای فعلی قبل از بازیابی ذخیره خواهد شد.')) return;
            try {
                const r = await fetch('{{ url("/builder/api/revision") }}/' + rev.id + '/restore', {
                    method: 'POST',
                    headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':CSRF_TOKEN, 'Accept':'application/json' },
                    body: JSON.stringify({})
                });
                const d = await r.json();
                if (d.success) {
                    this._destroyQuills();
                    this.blocks = (d.blocks ?? []).map(b => ({
                        ...b,
                        props: b.props ?? b.data ?? {},
                        _style: b._style ?? defaultStyle(),
                    }));
                    this.selectedIndex = -1;
                    this.isDirty = false;
                    this.pushHistory();
                    this.refreshCanvas();
                    this.showToast('نسخه قبلی بازیابی شد ✓', 'success');
                    this.loadRevisions();
                } else { this.showToast('خطا در بازیابی', 'error'); }
            } catch(e) { this.showToast('خطای شبکه', 'error'); }
        },

        // ── Pages management ──────────────────────────────────────────────────
        async loadPages() {
            this.pagesLoading = true;
            try {
                const r = await fetch(ROUTES.listPages, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN } });
                const d = await r.json();
                this.pages = d.pages ?? [];
            } catch(e) { this.showToast('خطا در بارگذاری صفحات', 'error'); }
            finally { this.pagesLoading = false; }
        },

        async createPage() {
            if (!this.newPageTitle.trim()) { this.showToast('عنوان صفحه الزامی است', 'error'); return; }
            try {
                const r = await fetch(ROUTES.createPage, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                    body: JSON.stringify({ title: this.newPageTitle })
                });
                const d = await r.json();
                if (d.success) {
                    this.showToast('صفحه ایجاد شد', 'success');
                    this.showCreatePage = false;
                    this.newPageTitle = '';
                    this.loadPages();
                    // Navigate to new page in builder after 1s
                    setTimeout(() => { window.location.href = d.page.edit_url; }, 800);
                } else { this.showToast(d.message||'خطا', 'error'); }
            } catch(e) { this.showToast('خطای شبکه', 'error'); }
        },

        async savePageSettings() {
            if (this.pageSettingsSaving) return;
            this.pageSettingsSaving = true;
            try {
                const r = await fetch(ROUTES.pageSettings, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                    body: JSON.stringify(this.pageSettings)
                });
                const d = await r.json();
                if (d.success) {
                    this.pageSettings = { ...this.pageSettings, ...d.page };
                    this.showToast('تنظیمات صفحه ذخیره شد ✓', 'success');
                } else { this.showToast(d.message||'خطا در ذخیره', 'error'); }
            } catch(e) { this.showToast('خطای شبکه', 'error'); }
            finally { this.pageSettingsSaving = false; }
        },

        // ── Quill WYSIWYG ──────────────────────────────────────────────────────
        initQuill(wrapperEl, propKey) {
            if (wrapperEl.dataset.qi === '1') return;
            wrapperEl.dataset.qi = '1';
            const inner = wrapperEl.querySelector('.quill-inner');
            if (!inner || typeof Quill === 'undefined') return;
            const q = new Quill(inner, { theme: 'snow', modules: { toolbar: QUILL_TOOLBAR }, placeholder: 'متن خود را بنویسید...' });
            const existing = this.selectedBlock?.props[propKey] || '';
            if (existing) q.root.innerHTML = existing;
            q.root.dir = 'rtl';
            q.on('text-change', () => {
                if (this.selectedBlock) { this.selectedBlock.props[propKey] = q.root.innerHTML; this.markDirty(); }
            });
            this.quillInstances[this.selectedIndex + '_' + propKey] = q;
        },

        _destroyQuills() {
            Object.values(this.quillInstances).forEach(q => { try { q.off('text-change'); } catch(e){} });
            this.quillInstances = {};
        },

        // ── Image Upload ──────────────────────────────────────────────────────
        async uploadImageProp(event, propKey) {
            const file = event.target.files?.[0]; if (!file) return;
            this.uploadingProp = propKey;
            await this._doUpload(file, url => { this.selectedBlock.props[propKey] = url; this.markDirty(); });
            this.uploadingProp = null; event.target.value = '';
        },
        async uploadBgImage(event) {
            const file = event.target.files?.[0]; if (!file) return;
            this.uploadingProp = '_bg_img';
            await this._doUpload(file, url => { this.selectedBlock._style.bg_image = url; this.markDirty(); });
            this.uploadingProp = null; event.target.value = '';
        },
        async uploadRepImage(event, propKey, itemIdx, subKey) {
            const file = event.target.files?.[0]; if (!file) return;
            this.uploadingRepKey = `${propKey}_${itemIdx}_${subKey}`;
            await this._doUpload(file, url => { this.updateRepItem(propKey, itemIdx, subKey, url); });
            this.uploadingRepKey = null; event.target.value = '';
        },
        async _doUpload(file, callback) {
            const form = new FormData(); form.append('file', file); form.append('context', 'featured');
            try {
                const r = await fetch(ROUTES.uploadImage, { method:'POST', headers:{'X-CSRF-TOKEN':CSRF_TOKEN,'Accept':'application/json'}, body: form });
                const d = await r.json();
                if (d.url) { callback(d.url); this.showToast('تصویر آپلود شد ✓', 'success'); }
                else { this.showToast(d.message||'خطا در آپلود', 'error'); }
            } catch { this.showToast('خطای شبکه', 'error'); }
        },

        // ── Repeater ──────────────────────────────────────────────────────────
        addRepItem(propKey, schemaDef) {
            if (!Array.isArray(this.selectedBlock.props[propKey])) this.selectedBlock.props[propKey] = [];
            const newItem = {};
            Object.entries(schemaDef.item_schema || {}).forEach(([k,def]) => { newItem[k] = def.default !== undefined ? def.default : ''; });
            this.selectedBlock.props[propKey] = [...this.selectedBlock.props[propKey], newItem];
            this.markDirty();
        },
        removeRepItem(propKey, idx) {
            this.selectedBlock.props[propKey] = (this.selectedBlock.props[propKey]||[]).filter((_,i)=>i!==idx);
            this.markDirty();
        },
        updateRepItem(propKey, idx, subKey, value) {
            const items = [...(this.selectedBlock.props[propKey]||[])];
            items[idx] = {...items[idx], [subKey]: value};
            this.selectedBlock.props[propKey] = items;
            this.markDirty();
        },
        moveRepItem(propKey, from, to) {
            const arr = [...(this.selectedBlock.props[propKey]||[])];
            if (to < 0 || to >= arr.length) return;
            const [item] = arr.splice(from, 1);
            arr.splice(to, 0, item);
            this.selectedBlock.props[propKey] = arr;
            this.markDirty();
        },

        // ── Save ──────────────────────────────────────────────────────────────
        async saveBlocks() {
            if (this.saving) return; this.saving = true;
            try {
                const r = await fetch(ROUTES.save, {
                    method:'PUT',
                    headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF_TOKEN,'Accept':'application/json'},
                    body: JSON.stringify({ blocks: this.blocks })
                });
                const d = await r.json();
                if (d.success) { this.isDirty=false; this.showToast('ذخیره شد ✓','success'); }
                else { this.showToast(d.error??'خطا در ذخیره','error'); }
            } catch { this.showToast('خطای شبکه','error'); }
            finally { this.saving=false; }
        },

        // ── Helpers ───────────────────────────────────────────────────────────
        blockLabel(type)  { return BLOCK_LABELS[type] ?? type; },
        blockPreview(block) {
            const d = block?.props ?? {};
            const t = d.title||d.text||d.name||d.content||'';
            return (typeof t === 'string' ? t.replace(/<[^>]+>/g,'') : '').slice(0, 50);
        },

        showToast(msg, type='success') {
            clearTimeout(this.toast._timer);
            this.toast = { show:true, msg, type, _timer:setTimeout(()=>this.toast.show=false, 3500) };
        },

        // ── Init ──────────────────────────────────────────────────────────────
        init() {
            // Normalize blocks
            this.blocks.forEach(b => {
                if (!b.props && b.data) b.props = b.data;
                if (!b.props) b.props = {};
                if (!b._style) b._style = defaultStyle();
            });

            // Push initial history state
            this.pushHistory();

            // Initial canvas render
            this.$nextTick(() => { this.refreshCanvas(); });

            // Listen for postMessage from iframe
            window.addEventListener('message', (e) => {
                if (!e.data?.type) return;
                const d = e.data;
                if (d.type === 'select') { this.selectBlock(d.idx); }
                else if (d.type === 'action') {
                    if (d.action === 'up')   this.moveBlock(d.idx, d.idx - 1);
                    else if (d.action === 'down') this.moveBlock(d.idx, d.idx + 1);
                    else if (d.action === 'dup')  this.duplicateBlock(d.idx);
                    else if (d.action === 'del')  this.removeBlock(d.idx);
                }
                else if (d.type === 'addafter') {
                    // Open block library pointing to insertion after d.idx
                    this.leftTab = 'blocks';
                    this.insertAfterIdx = d.idx;
                    this.showToast('ویجت مورد نظر را از کتابخانه انتخاب کنید', 'success');
                }
            });

            // Keyboard shortcuts
            window.addEventListener('beforeunload', e => {
                if (this.isDirty) { e.preventDefault(); e.returnValue=''; }
            });
        }
    };
}
</script>
</body>
</html>
