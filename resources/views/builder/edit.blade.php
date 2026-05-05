<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ویرایش صفحه: {{ $page->title }} — سازنده بصری</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { fontFamily: { vazir: ['Vazirmatn', 'Tahoma', 'sans-serif'] } } } };</script>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    <style>
        body { font-family: Vazirmatn, Tahoma, sans-serif; margin: 0; direction: rtl; overflow: hidden; }
        .builder-panel { overflow-y: auto; }
        .tree-item { padding: 0.35rem 0.5rem; border-radius: 0.375rem; cursor: pointer; font-size: 0.875rem; }
        .tree-item:hover { background: #e2e8f0; }
        .tree-item.selected { background: #c7d2fe; color: #3730a3; }
        .tree-item .tree-children { margin-right: 1rem; border-right: 2px solid #e2e8f0; padding-right: 0.5rem; }
        .preview-frame { border: none; width: 100%; height: 100%; min-height: 50vh; background: #fff; border-radius: 0.5rem; }
        .element-card { padding: 0.5rem 0.75rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; cursor: grab; margin-bottom: 0.35rem; font-size: 0.8rem; display: flex; align-items: center; gap: 0.5rem; user-select: none; }
        .element-card:hover { background: #f1f5f9; border-color: #6366f1; }
        .element-card:active { cursor: grabbing; }
        .drop-zone { min-height: 2.5rem; border: 2px dashed #cbd5e1; border-radius: 0.5rem; transition: background .15s, border-color .15s; }
        .drop-zone.drag-over { background: #e0e7ff; border-color: #6366f1; }
        .drop-zone.drop-zone-sm { min-height: 1.5rem; }
        .btn { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.4rem 0.75rem; border-radius: 0.5rem; font-weight: 500; font-size: 0.875rem; border: none; cursor: pointer; }
        .btn-primary { background: #4f46e5; color: #fff; }
        .btn-primary:hover { background: #4338ca; }
        .btn-secondary { background: #e2e8f0; color: #475569; }
        .btn-secondary:hover { background: #cbd5e1; }
        .btn-success { background: #059669; color: #fff; }
        .btn-success:hover { background: #047857; }
        .btn-danger { background: #dc2626; color: #fff; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-sm { padding: 0.25rem 0.5rem; font-size: 0.8rem; }
        input, textarea, select { width: 100%; padding: 0.4rem 0.6rem; border: 1px solid #cbd5e1; border-radius: 0.375rem; font-size: 0.875rem; }
        input:focus, textarea:focus, select:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 2px rgba(99,102,241,0.2); }
        .form-label { display: block; font-size: 0.8rem; font-weight: 500; color: #475569; margin-bottom: 0.25rem; }
        .form-group { margin-bottom: 0.75rem; }
        .canvas-section { background: #fff; border: 1px solid #e2e8f0; border-radius: 0.5rem; margin-bottom: 0.5rem; overflow: hidden; }
        .canvas-section.selected-wrap { border-color: #6366f1; box-shadow: 0 0 0 2px rgba(99,102,241,0.3); }
    </style>
</head>
<body class="bg-slate-100">
<script>
    window.builderConfig = {!! json_encode([
        'pageId' => $page->id,
        'pageTitle' => $page->title,
        'pageSlug' => $page->slug,
        'initialContent' => $builderContent,
        'elementsConfig' => $elementsConfig,
        'saveUrl' => $page->id ? route('builder.page.update', $page) : null,
        'previewUrl' => route('builder.preview'),
        'exitUrl' => $page->id ? route('page.show', $page->slug) : route('admin.pages.index'),
        'csrf' => csrf_token(),
        'useV2' => isset($useV2) && $useV2,
        'testMode' => isset($testMode) && $testMode,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!};
</script>
<div id="page-builder" class="flex flex-col h-screen"
     x-data="pageBuilder(window.builderConfig)"
     x-init="init()">
    <header class="flex items-center justify-between px-4 py-2 bg-white border-b border-slate-200 shadow-sm shrink-0">
        <div class="flex items-center gap-3">
            <a :href="exitUrl" class="btn btn-secondary btn-sm">← خروج</a>
            <h1 class="text-lg font-bold text-slate-800" x-text="pageTitle"></h1>
        </div>
        <div class="flex items-center gap-2">
            <div class="flex rounded-lg border border-slate-200 overflow-hidden">
                <button type="button" class="px-3 py-1.5 text-sm" :class="device === 'desktop' ? 'bg-indigo-100 text-indigo-700' : 'bg-white text-slate-600'" @click="device = 'desktop'">دسکتاپ</button>
                <button type="button" class="px-3 py-1.5 text-sm" :class="device === 'tablet' ? 'bg-indigo-100 text-indigo-700' : 'bg-white text-slate-600'" @click="device = 'tablet'">تبلت</button>
                <button type="button" class="px-3 py-1.5 text-sm" :class="device === 'mobile' ? 'bg-indigo-100 text-indigo-700' : 'bg-white text-slate-600'" @click="device = 'mobile'">موبایل</button>
            </div>
            <template x-if="saveUrl">
                <button type="button" class="btn btn-success" @click="save()" :disabled="saving">
                    <span x-show="!saving">ذخیره</span>
                    <span x-show="saving">در حال ذخیره...</span>
                </button>
            </template>
            <template x-if="testMode">
                <a :href="exitUrl" class="btn btn-secondary btn-sm">خروج از تست</a>
            </template>
        </div>
    </header>

    <div class="flex flex-1 min-h-0">
        <!-- Left: Component panel (drag sources) -->
        <aside class="w-64 bg-white border-l border-slate-200 builder-panel shrink-0 flex flex-col">
            <div class="p-2 border-b border-slate-200 font-semibold text-slate-700">کتابخانه المان‌ها</div>
            <div class="p-2 flex-1 overflow-y-auto space-y-3">
                <div>
                    <div class="text-xs font-semibold text-slate-500 mb-1">چیدمان</div>
                    <div
                        class="element-card"
                        draggable="true"
                        data-type="section"
                        data-props="{}"
                        @dragstart="onDragStart($event, 'section', getDefaultProps('section'))"
                        @click.prevent="addSection()"
                    >
                        ▣ بخش
                    </div>
                    <div class="element-card" draggable="true" data-type="row" data-props="{}" @dragstart="onDragStart($event, 'row', getDefaultProps('row'))">⇄ ردیف</div>
                    <div class="element-card" draggable="true" data-type="column" data-props="{}" @dragstart="onDragStart($event, 'column', getDefaultProps('column'))">▤ ستون</div>
                </div>
                <template x-for="(cat, catKey) in elementsConfig.byCategory" :key="catKey">
                    <div>
                        <div class="text-xs font-semibold text-slate-500 mb-1" x-text="elementsConfig.categories[catKey] || catKey"></div>
                        <template x-for="el in cat" :key="el.type">
                            <div
                                class="element-card"
                                draggable="true"
                                :data-type="el.type"
                                @dragstart="onDragStart($event, el.type, el.defaultProps || {})"
                                @click.prevent="addElementToSelected(el)"
                            >
                                <span x-text="el.icon"></span>
                                <span x-text="el.name"></span>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </aside>

        <!-- Center: Canvas + Live preview iframe -->
        <main class="flex-1 min-w-0 flex flex-col builder-panel p-4 gap-3"
             @dragover.prevent="dragOverSection = true; $event.dataTransfer.dropEffect = 'copy'"
             @dragleave="dragOverSection = false"
             @drop.prevent="onDropSection($event)">
            <div class="flex-1 min-h-0 flex flex-col gap-2 overflow-hidden" :class="{ 'drag-over': dragOverSection }">
                <div class="text-sm font-semibold text-slate-600">ساختار صفحه — المان را اینجا رها کنید یا از دکمه‌ها استفاده کنید</div>
                <div class="flex-1 overflow-y-auto pr-1 min-h-[200px]">
                    <div class="drop-zone mb-2 p-2 text-center text-slate-400 text-sm"
                         @dragover.prevent="dragOverSection = true; $event.dataTransfer.dropEffect = 'copy'"
                         @dragleave="dragOverSection = false"
                         @drop.prevent="onDropSection($event)"
                         :class="{ 'drag-over': dragOverSection }"
                         x-show="content.sections && content.sections.length === 0"
                         x-transition>
                        بخش جدید را اینجا رها کنید
                    </div>
                    <div id="canvas-sections-list" class="space-y-2">
                    <template x-for="(section, sIdx) in content.sections" :key="section.id">
                        <div class="canvas-section"
                             :class="{ 'selected-wrap': selectedId === section.id }"
                             :data-section-id="section.id">
                            <div class="p-2 flex items-center justify-between bg-slate-50 border-b border-slate-200 cursor-pointer"
                                 @click="select(section.id)">
                                <span class="font-medium text-slate-700">▣ بخش <span x-text="sIdx + 1"></span></span>
                                <div class="flex gap-1">
                                    <button type="button" class="btn btn-secondary btn-sm" @click.stop="addRow(sIdx)">+ ردیف</button>
                                    <button type="button" class="p-0.5 text-slate-500 hover:text-indigo-600" @click.stop="duplicateSection(sIdx)" title="کپی">⎘</button>
                                    <button type="button" class="p-0.5 text-slate-500 hover:text-red-600" @click.stop="removeSection(sIdx)" title="حذف">×</button>
                                </div>
                            </div>
                            <div class="p-2">
                                <template x-for="(row, rIdx) in (section.blocks || [])" :key="row.id">
                                    <div class="mb-2 pl-2 border-r-2 border-slate-200">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-xs text-slate-500">⇄ ردیف</span>
                                            <button type="button" class="btn btn-secondary btn-sm" @click.stop="addColumn(sIdx, rIdx)">+ ستون</button>
                                            <button type="button" class="p-0.5 text-red-500 hover:text-red-700" @click.stop="removeBlock(section.blocks, rIdx)">×</button>
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            <template x-for="(col, cIdx) in (row.blocks || [])" :key="col.id">
                                                <div class="flex-1 min-w-[120px] p-2 bg-slate-50 rounded border border-slate-200">
                                                    <div class="flex justify-between items-center mb-1">
                                                        <span class="text-xs text-slate-500">▤ ستون</span>
                                                        <button type="button" class="p-0.5 text-red-500" @click.stop="removeBlock(row.blocks, cIdx)">×</button>
                                                    </div>
                                                    <div class="drop-zone drop-zone-sm p-1"
                                                         @dragover.prevent="$event.dataTransfer.dropEffect = 'copy'; setDropTarget(section.id, row.id, col.id)"
                                                         @dragleave="clearDropTarget()"
                                                         @drop.prevent="onDropWidget($event, sIdx, rIdx, cIdx)">
                                                        <template x-for="(el, eIdx) in (col.blocks || [])" :key="el.id">
                                                            <div class="tree-item flex justify-between items-center mb-0.5 rounded"
                                                                 :class="{ 'selected': selectedId === el.id }"
                                                                 @click.stop="select(el.id)">
                                                                <span class="text-xs" x-text="getElementName(el.type)"></span>
                                                                <button type="button" class="p-0.5 text-slate-400 hover:text-red-600" @click.stop="removeBlock(col.blocks, eIdx)">×</button>
                                                            </div>
                                                        </template>
                                                        <span class="text-xs text-slate-400" x-show="!(col.blocks && col.blocks.length)">ویجت اینجا رها کنید</span>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                                <button type="button" class="text-xs text-indigo-600 hover:underline" @click="addRow(sIdx)">+ افزودن ردیف</button>
                            </div>
                        </div>
                    </template>
                    </div>
                    <div class="drop-zone mt-2 p-2 text-center text-slate-400 text-sm"
                         @dragover.prevent="dragOverSection = true; $event.dataTransfer.dropEffect = 'copy'"
                         @dragleave="dragOverSection = false"
                         @drop.prevent="onDropSection($event)"
                         :class="{ 'drag-over': dragOverSection }">
                        + بخش جدید را اینجا رها کنید
                    </div>
                </div>
            </div>
            <div class="h-64 shrink-0 rounded-lg overflow-hidden bg-slate-200 flex items-start justify-center" :style="previewWidth">
                <iframe id="builder-preview-iframe" class="preview-frame bg-white shadow-lg w-full h-full" title="پیش‌نمایش"></iframe>
            </div>
        </main>

        <!-- Right: Properties panel -->
        <aside class="w-72 bg-white border-r border-slate-200 builder-panel shrink-0 overflow-y-auto">
            <template x-if="!selectedNode">
                <div class="p-3">
                    <div class="font-semibold text-slate-700 mb-2">تنظیمات المان</div>
                    <p class="text-xs text-slate-500">روی یک بخش، ردیف، ستون یا ویجت کلیک کنید تا تنظیمات آن را ببینید.</p>
                </div>
            </template>
            <template x-if="selectedNode">
                <div class="p-3">
                    <div class="font-semibold text-slate-700 mb-2">تنظیمات المان</div>
                    <template x-if="selectedNode.type === 'section'">
                        <div class="space-y-2">
                            <div class="form-group">
                                <label class="form-label">فاصله داخلی</label>
                                <select x-model="selectedNode.props.padding">
                                    <option value="none">بدون</option>
                                    <option value="sm">کوچک</option>
                                    <option value="md">متوسط</option>
                                    <option value="lg">بزرگ</option>
                                    <option value="xl">بسیار بزرگ</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">پس‌زمینه (کلاس تم)</label>
                                <select x-model="selectedNode.props.background">
                                    <option value="transparent">شفاف</option>
                                    <option value="background">پس‌زمینه</option>
                                    <option value="surface">سطح</option>
                                    <option value="primary">اصلی</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">رنگ پس‌زمینه سفارشی</label>
                                <input type="text" x-model="selectedNode.style.background.color" placeholder="#ffffff یا خالی">
                            </div>
                            <div class="form-group">
                                <label class="form-label">گوشه‌ها (Radius)</label>
                                <select x-model="selectedNode.style.layout.radius">
                                    <option value="">پیش‌فرض</option>
                                    <option value="sm">گرد کوچک</option>
                                    <option value="md">گرد متوسط</option>
                                    <option value="lg">گرد زیاد</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">سایه</label>
                                <select x-model="selectedNode.style.layout.shadow">
                                    <option value="">بدون</option>
                                    <option value="sm">سایه کم</option>
                                    <option value="md">سایه متوسط</option>
                                    <option value="lg">سایه زیاد</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">افکت hover</label>
                                <select x-model="selectedNode.style.effects.hover">
                                    <option value="">غیرفعال</option>
                                    <option value="lift">بالا آمدن کارت</option>
                                </select>
                            </div>
                        </div>
                    </template>
                    <template x-if="selectedNode.type === 'column'">
                        <div class="form-group">
                            <label class="form-label">عرض ستون</label>
                            <select x-model="selectedNode.props.width">
                                <option value="1/1">تمام عرض</option>
                                <option value="1/2">نیم</option>
                                <option value="1/3">یک سوم</option>
                                <option value="2/3">دو سوم</option>
                                <option value="1/4">یک چهارم</option>
                                <option value="3/4">سه چهارم</option>
                            </select>
                        </div>
                    </template>
                    <template x-if="selectedNode.type === 'heading'">
                        <div class="space-y-2">
                            <div class="form-group">
                                <label class="form-label">متن</label>
                                <input type="text" x-model="selectedNode.props.text" placeholder="عنوان">
                            </div>
                            <div class="form-group">
                                <label class="form-label">سطح</label>
                                <select x-model="selectedNode.props.level">
                                    <option value="1">۱</option>
                                    <option value="2">۲</option>
                                    <option value="3">۳</option>
                                    <option value="4">۴</option>
                                    <option value="5">۵</option>
                                    <option value="6">۶</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">تراز متن</label>
                                <select x-model="selectedNode.style.typography.textAlign">
                                    <option value="">پیش‌فرض</option>
                                    <option value="right">راست</option>
                                    <option value="center">وسط</option>
                                    <option value="left">چپ</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">رنگ متن</label>
                                <input type="text" x-model="selectedNode.style.typography.color" placeholder="#111827">
                            </div>
                            <div class="form-group">
                                <label class="form-label">اندازه فونت</label>
                                <select x-model="selectedNode.style.typography.fontSize">
                                    <option value="">پیش‌فرض</option>
                                    <option value="sm">کوچک</option>
                                    <option value="md">متوسط</option>
                                    <option value="lg">بزرگ</option>
                                    <option value="xl">خیلی بزرگ</option>
                                </select>
                            </div>
                        </div>
                    </template>
                    <template x-if="selectedNode.type === 'text'">
                        <div class="space-y-2">
                            <div class="form-group">
                                <label class="form-label">محتوا</label>
                                <textarea x-model="selectedNode.props.content" rows="4" placeholder="متن"></textarea>
                            </div>
                            <div class="form-group">
                                <label class="form-label">تراز متن</label>
                                <select x-model="selectedNode.style.typography.textAlign">
                                    <option value="">پیش‌فرض</option>
                                    <option value="right">راست</option>
                                    <option value="center">وسط</option>
                                    <option value="left">چپ</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">رنگ متن</label>
                                <input type="text" x-model="selectedNode.style.typography.color" placeholder="#4b5563">
                            </div>
                        </div>
                    </template>
                    <template x-if="selectedNode.type === 'button'">
                        <div class="space-y-2">
                            <div class="form-group">
                                <label class="form-label">متن دکمه</label>
                                <input type="text" x-model="selectedNode.props.text">
                            </div>
                            <div class="form-group">
                                <label class="form-label">لینک</label>
                                <input type="text" x-model="selectedNode.props.url" placeholder="#">
                            </div>
                        </div>
                    </template>
                    <template x-if="selectedNode.type === 'hero'">
                        <div class="space-y-2">
                            <div class="form-group">
                                <label class="form-label">عنوان هیرو</label>
                                <input type="text" x-model="selectedNode.props.title">
                            </div>
                            <div class="form-group">
                                <label class="form-label">زیرعنوان</label>
                                <input type="text" x-model="selectedNode.props.subtitle">
                            </div>
                        </div>
                    </template>
                    <template x-if="selectedNode.type === 'image'">
                        <div class="space-y-2">
                            <div class="form-group">
                                <label class="form-label">آدرس تصویر</label>
                                <input type="text" x-model="selectedNode.props.url" placeholder="https://">
                            </div>
                            <div class="form-group">
                                <label class="form-label">متن جایگزین</label>
                                <input type="text" x-model="selectedNode.props.alt" placeholder="alt">
                            </div>
                        </div>
                    </template>
                    <button type="button" class="btn btn-secondary btn-sm mt-2" @click="selectedId = null; selectedNode = null">بستن تنظیمات</button>
                </div>
            </template>
        </aside>
    </div>

    <!-- Phase 8: Debug overlay (test mode only) -->
    <div x-show="testMode" x-cloak class="fixed bottom-4 left-4 right-auto z-50 w-72 max-h-64 overflow-auto bg-slate-900 text-slate-100 text-xs rounded-lg shadow-xl border border-slate-700 p-3 font-mono"
         x-transition>
        <div class="font-semibold text-amber-400 mb-2">حالت تست — دیباگ</div>
        <div class="space-y-1 mb-2">
            <div>تعداد المان‌ها: <span x-text="collectElementIds(content.sections).length"></span></div>
            <div x-show="lastRenderStats">زمان رندر: <span x-text="lastRenderStats ? (lastRenderStats.render_time_ms + ' ms') : '—'"></span></div>
            <div x-show="lastRenderStats">حافظه (پیک): <span x-text="lastRenderStats ? (formatBytes(lastRenderStats.memory_peak_bytes)) : '—'"></span></div>
        </div>
        <div class="text-slate-400 mb-1">شناسه‌ها:</div>
        <div class="max-h-24 overflow-y-auto break-all" x-text="collectElementIds(content.sections).join(', ') || '—'"></div>
    </div>
</div>

<script>
function pageBuilder(config) {
    return {
        pageId: config.pageId,
        pageTitle: config.pageTitle,
        pageSlug: config.pageSlug,
        content: JSON.parse(JSON.stringify(config.initialContent.sections ? config.initialContent : { sections: [] })),
        elementsConfig: config.elementsConfig || { byCategory: {}, categories: {} },
        saveUrl: config.saveUrl || null,
        previewUrl: config.previewUrl,
        exitUrl: config.exitUrl,
        csrf: config.csrf,
        useV2: config.useV2 !== false,
        testMode: config.testMode === true,
        lastRenderStats: null,
        selectedId: null,
        selectedNode: null,
        saving: false,
        device: 'desktop',
        previewDebounce: null,
        dragOverSection: false,
        dragPayload: null,
        dropTarget: null,

        init() {
            if (!this.content.sections) this.content.sections = [];
            this.ensureUniqueIds(this.content.sections);
            this.refreshPreview();
            this.setupSortable();
        },

        ensureUniqueIds(blocks) {
            if (!blocks || !Array.isArray(blocks)) return;
            blocks.forEach(b => {
                if (!b.id) b.id = this.uid();
                this.ensureStyle(b);
                this.ensureUniqueIds(b.blocks);
            });
        },

        uid() {
            return 'el_' + Date.now() + '_' + Math.random().toString(36).slice(2, 11);
        },

        getDefaultProps(type) {
            const flat = this.elementsConfig.flat || [];
            if (flat.indexOf(type) >= 0) {
                for (const cat of Object.values(this.elementsConfig.byCategory || {})) {
                    const el = cat.find(e => e.type === type);
                    if (el && el.defaultProps) return Object.assign({}, el.defaultProps);
                }
            }
            if (type === 'section') return { padding: 'md', background: 'transparent', container: true };
            if (type === 'row') return { gap: 'md' };
            if (type === 'column') return { width: '1/1' };
            return {};
        },

        get contentForSave() {
            return { sections: this.content.sections || [] };
        },

        ensureStyle(node) {
            if (!node) return;
            if (!node.style) node.style = {};
            if (!node.style.layout) node.style.layout = {};
            if (!node.style.typography) node.style.typography = {};
            if (!node.style.background) node.style.background = {};
            if (!node.style.effects) node.style.effects = {};
        },

        onDragStart(ev, type, defaultProps) {
            this.dragPayload = { type, defaultProps: defaultProps || {} };
            const json = JSON.stringify(this.dragPayload);
            ev.dataTransfer.setData('application/json', json);
            ev.dataTransfer.setData('text/plain', json);
            ev.dataTransfer.effectAllowed = 'copy';
        },

        getDropData(ev) {
            let raw = ev.dataTransfer.getData('application/json');
            if (!raw) raw = ev.dataTransfer.getData('text/plain');
            try { return raw ? JSON.parse(raw) : null; } catch (e) { return null; }
        },

        setDropTarget(sectionId, rowId, colId) {
            this.dropTarget = { sectionId, rowId, colId };
        },
        clearDropTarget() {
            this.dropTarget = null;
        },

        onDropSection(ev) {
            this.dragOverSection = false;
            const data = this.dragPayload || this.getDropData(ev);
            if (!data || data.type !== 'section') return;
            this.addSectionFromDrag(data);
        },

        addSectionFromDrag(data) {
            const id = this.uid();
            const section = {
                id,
                type: 'section',
                props: Object.assign({ padding: 'md', background: 'transparent', container: true }, data.defaultProps || {}),
                style: {
                    layout: {},
                    typography: {},
                    background: {},
                    effects: {}
                },
                blocks: [{
                    id: this.uid(),
                    type: 'row',
                    props: { gap: 'md' },
                    blocks: [{ id: this.uid(), type: 'column', props: { width: '1/1' }, blocks: [] }]
                }]
            };
            this.content.sections = this.content.sections || [];
            this.content.sections.push(section);
            this.refreshPreview();
        },

        onDropWidget(ev, sIdx, rIdx, cIdx) {
            const data = this.dragPayload || this.getDropData(ev);
            if (!data || !data.type) return;
            const type = data.type;
            const section = this.content.sections[sIdx];
            if (!section || !section.blocks || !section.blocks[rIdx]) return;
            const row = section.blocks[rIdx];
            const col = (row.blocks || [])[cIdx];
            if (!col) return;
            if (type === 'section') {
                this.addSectionFromDrag(data);
                return;
            }
            if (type === 'row') {
                row.blocks = row.blocks || [];
                row.blocks.push({
                    id: this.uid(),
                    type: 'column',
                    props: { width: '1/2' },
                    blocks: []
                });
                this.refreshPreview();
                return;
            }
            if (type === 'column') {
                row.blocks = row.blocks || [];
                row.blocks.push({
                    id: this.uid(),
                    type: 'column',
                    props: Object.assign({ width: '1/2' }, data.defaultProps || {}),
                    blocks: []
                });
                this.refreshPreview();
                return;
            }
            col.blocks = col.blocks || [];
            col.blocks.push({
                id: this.uid(),
                type,
                props: Object.assign({}, this.getDefaultProps(type), data.defaultProps || {}),
                style: {
                    layout: {},
                    typography: {},
                    background: {},
                    effects: {}
                }
            });
            this.refreshPreview();
        },

        select(id) {
            this.selectedId = id;
            this.selectedNode = this.findNode(this.content.sections, id);
            this.ensureStyle(this.selectedNode);
        },

        findNode(blocks, id) {
            if (!blocks || !id) return null;
            for (const b of blocks) {
                if (b.id === id) return b;
                const found = this.findNode(b.blocks, id);
                if (found) return found;
            }
            return null;
        },

        collectElementIds(blocks) {
            if (!blocks || !Array.isArray(blocks)) return [];
            const ids = [];
            blocks.forEach(b => {
                if (b.id) ids.push(b.id);
                ids.push(...this.collectElementIds(b.blocks));
            });
            return ids;
        },

        formatBytes(n) {
            if (n === undefined || n === null) return '—';
            if (n < 1024) return n + ' B';
            if (n < 1024 * 1024) return (n / 1024).toFixed(1) + ' KB';
            return (n / (1024 * 1024)).toFixed(2) + ' MB';
        },

        getElementName(type) {
            for (const cat of Object.values(this.elementsConfig.byCategory || {})) {
                const el = cat.find(e => e.type === type);
                if (el) return el.name;
            }
            return type;
        },

        /**
         * Click-based insert: add a widget into the currently selected column.
         */
        addElementToSelected(el) {
            if (!el || !el.type) return;
            if (!this.selectedId) {
                alert('برای اضافه کردن ویجت، ابتدا یک ستون را در ساختار صفحه انتخاب کنید.');
                return;
            }
            const node = this.findNode(this.content.sections, this.selectedId);
            if (!node || node.type !== 'column') {
                alert('ویجت‌ها فقط داخل ستون قرار می‌گیرند. لطفاً یک ستون را انتخاب کنید.');
                return;
            }
            node.blocks = node.blocks || [];
            node.blocks.push({
                id: this.uid(),
                type: el.type,
                props: Object.assign({}, this.getDefaultProps(el.type), el.defaultProps || {})
            });
            this.refreshPreview();
        },

        addSection() {
            this.addSectionFromDrag({ type: 'section', defaultProps: {} });
        },

        addRow(sectionIdx) {
            const section = this.content.sections[sectionIdx];
            if (!section) return;
            section.blocks = section.blocks || [];
            section.blocks.push({
                id: this.uid(),
                type: 'row',
                props: { gap: 'md' },
                blocks: [{ id: this.uid(), type: 'column', props: { width: '1/1' }, blocks: [] }]
            });
            this.refreshPreview();
        },

        addColumn(sectionIdx, rowIdx) {
            const section = this.content.sections[sectionIdx];
            if (!section || !section.blocks || !section.blocks[rowIdx]) return;
            const row = section.blocks[rowIdx];
            row.blocks = row.blocks || [];
            row.blocks.push({ id: this.uid(), type: 'column', props: { width: '1/2' }, blocks: [] });
            this.refreshPreview();
        },

        addElementToColumn(sectionIdx, rowIdx, colIdx) {
            const section = this.content.sections[sectionIdx];
            if (!section || !section.blocks || !section.blocks[rowIdx]) return;
            const row = section.blocks[rowIdx];
            const col = (row.blocks || [])[colIdx];
            if (!col) return;
            const def = (this.elementsConfig.byCategory && this.elementsConfig.byCategory.content) ?
                this.elementsConfig.byCategory.content.find(e => e.type === 'heading') : { type: 'heading', defaultProps: { level: 2, text: 'عنوان' } };
            col.blocks = col.blocks || [];
            col.blocks.push({
                id: this.uid(),
                type: (def && def.type) || 'heading',
                props: Object.assign({}, (def && def.defaultProps) || {})
            });
            this.refreshPreview();
        },

        removeSection(idx) {
            this.content.sections.splice(idx, 1);
            this.selectedId = null;
            this.selectedNode = null;
            this.refreshPreview();
        },

        removeBlock(parentBlocks, idx) {
            if (!parentBlocks) return;
            parentBlocks.splice(idx, 1);
            this.selectedId = null;
            this.selectedNode = null;
            this.refreshPreview();
        },

        duplicateSection(idx) {
            const section = JSON.parse(JSON.stringify(this.content.sections[idx]));
            const renew = (b) => { b.id = this.uid(); (b.blocks || []).forEach(renew); };
            renew(section);
            this.content.sections.splice(idx + 1, 0, section);
            this.refreshPreview();
        },

        setupSortable() {
            // از setTimeout استفاده می‌کنیم تا بعد از رندر اولیه DOM اجرا شود
            setTimeout(() => {
                const list = document.getElementById('canvas-sections-list');
                if (!list) return;
                new Sortable(list, {
                    animation: 150,
                    draggable: '.canvas-section',
                    onEnd: (ev) => {
                        const from = ev.oldIndex;
                        const to = ev.newIndex;
                        if (from === to || !this.content.sections) return;
                        const item = this.content.sections.splice(from, 1)[0];
                        this.content.sections.splice(to, 0, item);
                        this.refreshPreview();
                    }
                });
            }, 0);
        },

        refreshPreview() {
            clearTimeout(this.previewDebounce);
            this.previewDebounce = setTimeout(() => {
                const payload = this.useV2
                    ? { builder_content_v2: this.contentForSave }
                    : { builder_content: this.contentForSave };
                if (this.testMode) payload._test_mode = 1;
                fetch(this.previewUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                }).then(r => r.json()).then(data => {
                    if (this.testMode && data.render_time_ms !== undefined) {
                        this.lastRenderStats = {
                            render_time_ms: data.render_time_ms,
                            memory_peak_bytes: data.memory_peak_bytes
                        };
                    }
                    const iframe = document.getElementById('builder-preview-iframe');
                    if (iframe && data.html !== undefined) {
                        const doc = iframe.contentDocument || iframe.contentWindow.document;
                        doc.open();
                        doc.write('<!DOCTYPE html><html dir="rtl" lang="fa"><head><meta charset="utf-8"><link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;600;700&display=swap" rel="stylesheet"><style>body{font-family:Vazirmatn,Tahoma,sans-serif;margin:0;padding:1rem;direction:rtl;background:#f9fafb}.tb-section{padding:1.5rem 0}.tb-container{max-width:80rem;margin:0 auto;padding:0 1rem}.tb-row{display:flex;flex-wrap:wrap;gap:1rem}.tb-column{min-width:0}.tb-col-full{flex:1 1 100%}.tb-col-1-2{flex:1 1 calc(50% - 0.5rem)}.tb-col-1-3{flex:1 1 calc(33.333% - 0.67rem)}.tb-col-2-3{flex:1 1 calc(66.666% - 0.67rem)}.tb-hero{padding:2rem;text-align:center;border-radius:0.5rem;background:#4f46e5;color:#fff}.tb-hero-title{font-size:1.75rem;margin:0 0 0.5rem}.tb-heading{margin:0 0 0.5rem;font-weight:700}.tb-text{line-height:1.7}.tb-image{display:block;max-width:100%;margin:0 auto 1.5rem}.tb-image .tb-img{display:block;max-width:100%;height:auto;margin:0 auto;max-height:480px;object-fit:contain}.tb-btn{display:inline-block;padding:0.5rem 1rem;border-radius:0.5rem;text-decoration:none;font-weight:600}.tb-btn-primary{background:#4338ca;color:#fff}.tb-radius-sm{border-radius:0.5rem}.tb-radius-md{border-radius:0.75rem}.tb-radius-lg{border-radius:1rem}.tb-shadow-sm{box-shadow:0 1px 3px rgba(15,23,42,0.16)}.tb-shadow-md{box-shadow:0 4px 12px rgba(15,23,42,0.18)}.tb-shadow-lg{box-shadow:0 12px 30px rgba(15,23,42,0.22)}.tb-hover-lift{transition:transform .2s,box-shadow .2s}.tb-hover-lift:hover{transform:translateY(-4px);box-shadow:0 10px 25px rgba(15,23,42,0.22)}@media(max-width:767px){.tb-col-1-2,.tb-col-1-3,.tb-col-2-3{flex:1 1 100%}}</style></head><body>' + data.html + '</body></html>');
                        doc.close();
                    }
                }).catch(() => {});
            }, 300);
        },

        get previewWidth() {
            const w = this.device === 'mobile' ? '375px' : this.device === 'tablet' ? '768px' : '100%';
            return { maxWidth: w };
        },

        save() {
            this.saving = true;
            const payload = this.useV2
                ? { builder_content_v2: this.contentForSave }
                : { builder_content: this.contentForSave };
            fetch(this.saveUrl, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrf,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            }).then(r => r.json()).then(() => {
                this.saving = false;
                alert('صفحه ذخیره شد.');
            }).catch(() => {
                this.saving = false;
                alert('خطا در ذخیره.');
            });
        }
    };
}
</script>
</body>
</html>
