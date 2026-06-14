<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ویرایش: {{ $pageTitle }}</title>
    {{-- Tailwind CDN Play — handles all utility classes without a build step --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: {} },
            plugins: [],
        }
    </script>
    {{-- Vazirmatn font --}}
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    {{-- SortableJS --}}
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    {{-- AlpineJS --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Vazirmatn', Tahoma, sans-serif; margin: 0; padding: 0; overflow: hidden; height: 100vh; background: #f1f5f9; direction: rtl; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        [x-cloak] { display: none !important; }
        /* x-collapse polyfill via max-height */
        [x-show][style*="display: none"] { max-height: 0; overflow: hidden; }
    </style>
</head>
<body>
@php
$orderedCats = ['layout','content','marketing','dynamic','ecommerce','utility'];
@endphp

<div
    x-data="pageContentBuilder()"
    x-init="init()"
    x-cloak
    class="flex flex-col h-screen bg-slate-100 overflow-hidden select-none"
    @keydown.ctrl.s.window.prevent="saveBlocks()"
>
    {{-- ══ TOOLBAR ══════════════════════════════════════════════════════════ --}}
    <div class="h-14 bg-white border-b border-slate-200 flex items-center px-4 gap-3 shrink-0 shadow-sm z-30">

        <a href="{{ $exitUrl }}"
           class="flex items-center gap-1.5 text-slate-500 hover:text-slate-800 text-sm font-medium transition ml-1 shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            بازگشت
        </a>
        <div class="h-6 w-px bg-slate-200 shrink-0"></div>

        <div class="flex items-center gap-2 shrink-0">
            <div class="w-7 h-7 rounded-lg bg-indigo-600 flex items-center justify-center text-white text-xs">✏️</div>
            <div>
                <p class="text-[10px] text-slate-400 leading-none mb-0.5">سازنده بصری</p>
                <span class="text-sm font-semibold text-slate-800">{{ $pageTitle }}</span>
            </div>
        </div>
        <div class="h-6 w-px bg-slate-200 shrink-0"></div>

        @isset($contentType)
        @php $ctLabels = ['post'=>['📰','مطلب','blue'],'product'=>['🛍️','محصول','emerald'],'article'=>['🎓','مقاله','violet'],'page'=>['📄','صفحه','indigo']]; $ctData = $ctLabels[$contentType] ?? ['📄','صفحه','indigo']; @endphp
        <span class="text-[10px] px-2 py-1 rounded-full font-semibold border bg-{{ $ctData[2] }}-50 border-{{ $ctData[2] }}-200 text-{{ $ctData[2] }}-700 shrink-0">
            {{ $ctData[0] }} {{ $ctData[1] }}
        </span>
        @endisset

        <div class="flex-1"></div>

        {{-- Device toggles --}}
        <div class="flex items-center gap-1 bg-slate-100 rounded-lg p-1 shrink-0">
            <button @click="previewDevice='desktop'" :class="previewDevice==='desktop'?'bg-white shadow text-slate-700':'text-slate-400 hover:text-slate-600'"
                class="px-2.5 py-1 rounded-md text-xs font-medium transition flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2" stroke-width="2"/><path d="M8 21h8M12 17v4" stroke-width="2" stroke-linecap="round"/></svg>
                دسکتاپ
            </button>
            <button @click="previewDevice='mobile'" :class="previewDevice==='mobile'?'bg-white shadow text-slate-700':'text-slate-400 hover:text-slate-600'"
                class="px-2.5 py-1 rounded-md text-xs font-medium transition flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="7" y="2" width="10" height="20" rx="2" stroke-width="2"/><circle cx="12" cy="18" r="1" fill="currentColor"/></svg>
                موبایل
            </button>
        </div>
        <div class="h-6 w-px bg-slate-200 shrink-0"></div>

        <button @click="showPreview=!showPreview; if(showPreview) refreshPreview()"
            :class="showPreview?'bg-teal-50 border-teal-300 text-teal-700':'border-slate-200 text-slate-600 hover:border-teal-300 hover:text-teal-700'"
            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-medium transition shrink-0">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            پیش‌نمایش
        </button>

        <span x-show="isDirty" class="text-amber-500 text-xs font-medium flex items-center gap-1 shrink-0">
            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse inline-block"></span>
            ذخیره نشده
        </span>

        <button
            @click="saveBlocks()"
            :disabled="saving"
            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-indigo-300 text-indigo-700 hover:bg-indigo-50 text-xs font-medium transition disabled:opacity-60 shrink-0"
        >
            <svg x-show="!saving" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
            <svg x-show="saving" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
            <span x-text="saving ? 'ذخیره...' : 'ذخیره'"></span>
        </button>
    </div>

    {{-- ══ 3-PANEL LAYOUT ═══════════════════════════════════════════════════ --}}
    <div class="flex flex-1 overflow-hidden">

        {{-- ── PANEL A: Block Library (right in RTL) ───────────────────── --}}
        <div class="w-72 bg-white border-l border-slate-200 flex flex-col overflow-hidden shrink-0 z-20">
            <div class="px-4 py-3 border-b border-slate-100 shrink-0">
                <div class="relative">
                    <input x-model="librarySearch" type="text" placeholder="جستجوی ویجت..."
                        class="w-full text-sm pr-8 pl-3 py-2 rounded-lg border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 placeholder-slate-400">
                    <svg class="w-4 h-4 text-slate-400 absolute top-2.5 right-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto">
                @foreach($orderedCats as $catKey)
                    @php
                        $catDefs    = $byCategory[$catKey] ?? [];
                        if (empty($catDefs)) continue;
                        $catLabel   = $catLabels[$catKey] ?? $catKey;
                        $catIcon    = $catIcons[$catKey]  ?? '▪';
                        $badgeClass = $catColors[$catKey] ?? 'text-slate-600 bg-slate-100';
                    @endphp
                    <div x-show="isCategoryVisible('{{ $catKey }}')" class="border-b border-slate-100">
                        <button @click="toggleCategory('{{ $catKey }}')"
                            class="w-full flex items-center justify-between px-4 py-2.5 hover:bg-slate-50 transition text-right">
                            <div class="flex items-center gap-2">
                                <span class="text-sm">{{ $catIcon }}</span>
                                <span class="text-xs font-semibold text-slate-700">{{ $catLabel }}</span>
                                <span class="text-xs text-slate-400 font-normal">({{ count($catDefs) }})</span>
                            </div>
                            <svg :class="expandedCats['{{ $catKey }}'] ? 'rotate-180' : ''" class="w-3.5 h-3.5 text-slate-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="expandedCats['{{ $catKey }}']">
                            <div class="px-2 pb-2 grid grid-cols-2 gap-1.5">
                                @foreach($catDefs as $blockType => $blockDef)
                                    <button
                                        @click="addBlock('{{ $blockType }}')"
                                        x-show="isBlockVisible('{{ $blockType }}', '{{ addslashes($blockDef['name']) }}')"
                                        class="flex flex-col items-start gap-1 p-2.5 rounded-lg border border-slate-200 bg-white hover:border-indigo-400 hover:bg-indigo-50 hover:shadow-sm transition text-right cursor-pointer group"
                                        title="{{ $blockDef['name'] }}"
                                    >
                                        <div class="text-lg leading-none">{{ $blockDef['icon'] }}</div>
                                        <span class="text-[11px] font-medium text-slate-700 group-hover:text-indigo-700 leading-tight" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">{{ $blockDef['name'] }}</span>
                                        @if(!empty($blockDef['required_module']))
                                            <span class="text-[9px] px-1.5 py-0.5 rounded {{ $badgeClass }} font-medium leading-none mt-0.5">{{ $blockDef['required_module'] }}</span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ── PANEL B: Canvas (center) ────────────────────────────────── --}}
        <div class="flex-1 flex flex-col overflow-hidden">

            {{-- Inline Preview --}}
            <div x-show="showPreview" class="bg-slate-200 border-b border-slate-300 shrink-0" style="transition:all .2s">
                <div class="flex justify-center py-2 px-4">
                    <div :style="previewDevice==='mobile'?'max-width:375px;width:100%':'width:100%'"
                         class="bg-white rounded-lg shadow-md overflow-hidden border border-slate-300" style="transition:max-width .3s">
                        <iframe id="builder-preview-iframe" style="width:100%;height:300px;border:none;display:block;" title="پیش‌نمایش"></iframe>
                    </div>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto flex flex-col items-center px-6 py-6 gap-4">
                <div class="w-full max-w-2xl flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-indigo-500"></div>
                        <span class="text-xs font-semibold text-slate-600">{{ $pageTitle }}</span>
                        <span class="text-xs text-slate-400" x-text="'(' + blocks.length + ' ویجت)'"></span>
                    </div>
                    <span class="text-[10px] text-slate-400">Ctrl+S برای ذخیره</span>
                </div>

                <div id="canvas-blocks" class="w-full max-w-2xl space-y-2" style="min-height:12rem">
                    <template x-for="(block, index) in blocks" :key="block.id">
                        <div
                            @click="selectBlock(index)"
                            :class="selectedIndex === index ? 'border-indigo-500 shadow-md bg-white ring-2 ring-indigo-200' : 'border-slate-200 bg-white hover:border-indigo-300 hover:shadow-sm'"
                            class="rounded-xl border-2 cursor-pointer transition group relative"
                        >
                            <div class="flex items-center gap-3 p-3">
                                <span class="drag-handle cursor-grab text-slate-300 hover:text-slate-500 text-lg leading-none select-none px-1 shrink-0" title="کشیدن برای جابجایی">⠿</span>
                                <div :class="blockCategoryColor(block.type)" class="w-1 self-stretch rounded-full shrink-0"></div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm" x-text="BLOCK_EMOJIS[block.type] || '🧩'"></span>
                                        <span class="text-sm font-semibold text-slate-800" x-text="blockLabel(block.type)"></span>
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
                                <button @click.stop="removeBlock(index)"
                                    class="shrink-0 w-7 h-7 flex items-center justify-center rounded-lg text-slate-300 hover:text-red-500 hover:bg-red-50 transition opacity-0 group-hover:opacity-100 text-sm" title="حذف ویجت">×</button>
                            </div>
                        </div>
                    </template>

                    <div x-show="blocks.length === 0" class="flex flex-col items-center justify-center py-16 text-center">
                        <div class="text-5xl mb-4 opacity-30">🧩</div>
                        <p class="text-slate-400 text-sm font-medium">هنوز ویجتی اضافه نشده</p>
                        <p class="text-slate-300 text-xs mt-1">از کتابخانه سمت راست ویجت انتخاب کنید</p>
                    </div>
                </div>

                <div x-show="blocks.length > 0" class="w-full max-w-2xl">
                    <div class="w-full py-3 rounded-xl border-2 border-dashed border-slate-200 text-slate-400 text-sm font-medium flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        از کتابخانه سمت راست ویجت اضافه کنید
                    </div>
                </div>
            </div>
        </div>

        {{-- ── PANEL C: Props Panel (left in RTL) ──────────────────────── --}}
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

                                <template x-if="schemaDef.type === 'string'">
                                    <input type="text" x-model="selectedBlock.props[propKey]" @input="isDirty=true;debouncedPreview()"
                                        class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 transition"
                                        :placeholder="schemaDef.default ?? ''">
                                </template>
                                <template x-if="schemaDef.type === 'number'">
                                    <input type="number" x-model.number="selectedBlock.props[propKey]" @input="isDirty=true;debouncedPreview()"
                                        class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 transition" :min="0">
                                </template>
                                <template x-if="schemaDef.type === 'boolean'">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <div class="relative">
                                            <input type="checkbox" x-model="selectedBlock.props[propKey]" @change="isDirty=true;debouncedPreview()" class="sr-only peer">
                                            <div class="w-9 h-5 bg-slate-200 peer-checked:bg-indigo-500 rounded-full transition"></div>
                                            <div class="absolute top-0.5 right-0.5 w-4 h-4 bg-white rounded-full shadow peer-checked:translate-x-full rtl:peer-checked:-translate-x-full transition-transform"></div>
                                        </div>
                                        <span class="text-sm text-slate-700" x-text="selectedBlock.props[propKey] ? 'فعال' : 'غیرفعال'"></span>
                                    </label>
                                </template>
                                <template x-if="schemaDef.type === 'select'">
                                    <select x-model="selectedBlock.props[propKey]" @change="isDirty=true;debouncedPreview()"
                                        class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 transition">
                                        <template x-for="(lbl, val) in schemaDef.options" :key="val">
                                            <option :value="val" x-text="lbl"></option>
                                        </template>
                                    </select>
                                </template>
                                <template x-if="schemaDef.type === 'color'">
                                    <div class="flex items-center gap-2">
                                        <input type="color" x-model="selectedBlock.props[propKey]" @input="isDirty=true;debouncedPreview()" class="w-10 h-10 rounded-lg border border-slate-200 cursor-pointer p-0.5">
                                        <input type="text" x-model="selectedBlock.props[propKey]" @input="isDirty=true;debouncedPreview()" class="flex-1 text-sm px-3 py-2 rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300 font-mono" placeholder="#000000">
                                    </div>
                                </template>
                                <template x-if="schemaDef.type === 'textarea'">
                                    <textarea x-model="selectedBlock.props[propKey]" @input="isDirty=true;debouncedPreview()" rows="3"
                                        class="w-full text-sm px-3 py-2 rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 transition resize-y"
                                        :placeholder="schemaDef.default ?? ''"></textarea>
                                </template>
                            </div>
                        </template>
                        <div x-show="Object.keys(selectedSchema).length === 0" class="text-center py-6">
                            <p class="text-xs text-slate-400">این ویجت تنظیماتی ندارد</p>
                        </div>
                    </div>

                    <div class="px-4 py-3 border-t border-slate-100 shrink-0">
                        <button @click="removeBlock(selectedIndex)"
                            class="w-full flex items-center justify-center gap-2 py-2 rounded-lg border border-red-200 text-red-500 hover:bg-red-50 text-xs font-medium transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            حذف این ویجت
                        </button>
                    </div>
                </div>
            </template>
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

<script>
const CSRF_TOKEN     = '{{ csrf_token() }}';
const BLOCK_LABELS   = @json($blockLabels);
const BLOCK_SCHEMAS  = @json($blockSchemas);
const INITIAL_BLOCKS = @json($initialBlocks);
const BLOCK_EMOJIS   = {"hero":"🌟","section":"📦","row":"⇄","column":"▤","grid":"🔲","columns":"▥","tabs":"📑","accordion":"🪗","slider":"🎠","heading":"🔠","text":"📄","image":"🖼️","button":"🔘","icon":"⭐","divider":"➖","spacer":"↕️","video":"🎬","gallery":"🗂️","cta":"📢","testimonials":"⭐","blog_posts":"📰","product_grid":"🛒","faq":"❓","countdown":"⏱️","social_icons":"🔗","promo_banner":"📣","custom_html":"⌨️"};
const ROUTES         = { save: '{{ $saveUrl }}', preview: '{{ $previewUrl }}' };

function pageContentBuilder() {
    return {
        blocks:        JSON.parse(JSON.stringify(INITIAL_BLOCKS || [])),
        selectedIndex: -1,
        isDirty:       false,
        saving:        false,
        librarySearch: '',
        expandedCats:  { layout:true, content:true, marketing:false, dynamic:false, ecommerce:false, utility:false },
        showPreview:   false,
        previewDevice: 'desktop',
        previewTimer:  null,
        toast: { show:false, msg:'', type:'success', _timer:null },

        get selectedBlock() {
            if (this.selectedIndex < 0 || this.selectedIndex >= this.blocks.length) return null;
            return this.blocks[this.selectedIndex];
        },
        get selectedSchema() {
            if (!this.selectedBlock) return {};
            return BLOCK_SCHEMAS[this.selectedBlock.type]?.schema ?? {};
        },

        toggleCategory(key)    { this.expandedCats[key] = !this.expandedCats[key]; },
        isCategoryVisible(key) {
            if (!this.librarySearch) return true;
            const s = this.librarySearch.toLowerCase();
            return Object.keys(BLOCK_LABELS).some(t => {
                const lbl = (BLOCK_LABELS[t] ?? '').toLowerCase();
                return t.includes(s) || lbl.includes(s);
            });
        },
        isBlockVisible(type, name) {
            if (!this.librarySearch) return true;
            const s = this.librarySearch.toLowerCase();
            return type.toLowerCase().includes(s) || name.toLowerCase().includes(s);
        },

        addBlock(type) {
            const defs = JSON.parse(JSON.stringify(BLOCK_SCHEMAS[type]?.defaultProps ?? {}));
            const block = { id:'blk_'+Date.now()+'_'+Math.random().toString(36).slice(2,6), type, props:defs };
            this.blocks = [...this.blocks, block];
            this.selectedIndex = this.blocks.length - 1;
            this.isDirty = true;
            this.$nextTick(() => {
                document.getElementById('canvas-blocks')?.lastElementChild?.scrollIntoView({behavior:'smooth',block:'nearest'});
                if (this.showPreview) this.refreshPreview();
            });
        },
        removeBlock(index) {
            this.blocks.splice(index, 1);
            if (this.selectedIndex >= this.blocks.length) this.selectedIndex = this.blocks.length - 1;
            this.isDirty = true;
            if (this.showPreview) this.refreshPreview();
        },
        selectBlock(index) { this.selectedIndex = index; },
        moveBlock(from, to) {
            if (to < 0 || to >= this.blocks.length) return;
            const arr = [...this.blocks];
            const [item] = arr.splice(from, 1);
            arr.splice(to, 0, item);
            this.blocks = arr;
            this.selectedIndex = to;
            this.isDirty = true;
        },

        debouncedPreview() {
            if (!this.showPreview) return;
            clearTimeout(this.previewTimer);
            this.previewTimer = setTimeout(() => this.refreshPreview(), 350);
        },
        refreshPreview() {
            fetch(ROUTES.preview, {
                method:'POST',
                headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF_TOKEN,'Accept':'application/json'},
                body:JSON.stringify({ blocks: this.blocks })
            }).then(r=>r.json()).then(d=>{
                const iframe = document.getElementById('builder-preview-iframe');
                if (iframe && d.html !== undefined) {
                    const doc = iframe.contentDocument || iframe.contentWindow.document;
                    doc.open();
                    doc.write('<!DOCTYPE html><html dir="rtl" lang="fa"><head><meta charset="utf-8"><style>body{font-family:Vazirmatn,Tahoma,sans-serif;margin:0;padding:1rem;direction:rtl;background:#fff;font-size:14px}</style></head><body>'+d.html+'</body></html>');
                    doc.close();
                }
            }).catch(()=>{});
        },

        async saveBlocks() {
            if (this.saving) return;
            this.saving = true;
            try {
                const r = await fetch(ROUTES.save, {
                    method:'PUT',
                    headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF_TOKEN,'Accept':'application/json'},
                    body:JSON.stringify({ blocks: this.blocks })
                });
                const d = await r.json();
                if (d.success) { this.isDirty=false; this.showToast('ذخیره شد ✓','success'); }
                else this.showToast(d.error ?? 'خطا در ذخیره','error');
            } catch { this.showToast('خطای شبکه','error'); }
            finally { this.saving = false; }
        },

        blockLabel(type)  { return BLOCK_LABELS[type] ?? type; },
        blockPreview(block) {
            const d = block?.props ?? {};
            return d.title || d.text || d.content || d.name || d.subtitle || d.label || '';
        },
        blockCategoryColor(type) {
            if (!type) return 'bg-slate-300';
            if (['hero','section','row','column','grid','columns','tabs','accordion','slider'].includes(type)) return 'bg-blue-400';
            if (['heading','text','image','button','icon','divider','spacer','video','gallery'].includes(type)) return 'bg-green-500';
            if (['cta','countdown','promo_banner'].includes(type)) return 'bg-orange-400';
            if (['testimonials','blog_posts','faq'].includes(type)) return 'bg-purple-400';
            if (['product_grid'].includes(type)) return 'bg-emerald-500';
            return 'bg-slate-400';
        },

        showToast(msg, type='success') {
            clearTimeout(this.toast._timer);
            this.toast = { show:true, msg, type, _timer:setTimeout(()=>this.toast.show=false, 3200) };
        },

        init() {
            this.blocks.forEach(b => { if (!b.props && b.data) b.props=b.data; if (!b.props) b.props={}; });
            this.$nextTick(() => {
                const el = document.getElementById('canvas-blocks');
                if (el && typeof Sortable !== 'undefined') {
                    Sortable.create(el, {
                        animation:180, handle:'.drag-handle', ghostClass:'opacity-40', chosenClass:'ring-2 ring-indigo-400',
                        onEnd: (evt) => {
                            if (evt.oldIndex === evt.newIndex) return;
                            const arr = [...this.blocks];
                            const [item] = arr.splice(evt.oldIndex, 1);
                            arr.splice(evt.newIndex, 0, item);
                            this.blocks = arr;
                            this.selectedIndex = evt.newIndex;
                            this.isDirty = true;
                        }
                    });
                }
            });
            window.addEventListener('beforeunload', e => { if (this.isDirty) { e.preventDefault(); e.returnValue=''; } });
        }
    };
}
</script>
</body>
</html>
