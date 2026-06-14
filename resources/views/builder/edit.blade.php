<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ویرایش: {{ $page->title }} — سازنده بصری</title>
    <link rel="preload" as="font" href="{{ asset('assets/fonts/vazirmatn/Vazirmatn-Regular.woff2') }}" type="font/woff2" crossorigin>
    <link rel="preload" as="font" href="{{ asset('assets/fonts/vazirmatn/Vazirmatn-Bold.woff2') }}" type="font/woff2" crossorigin>
    <style>@include('partials.vazirmatn-font-faces-inline')</style>
    <script defer src="{{ asset('vendor/alpinejs/alpinejs.min.js') }}"></script>
    <script defer src="{{ asset('vendor/sortablejs/Sortable.min.js') }}"></script>
<style>
/* ── Reset & Base ─────────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; }
html, body { margin: 0; padding: 0; height: 100%; overflow: hidden; }
body {
    font-family: Vazirmatn, Tahoma, sans-serif;
    direction: rtl;
    background: #0f172a;
    color: #1e293b;
    font-size: 13px;
}
[x-cloak] { display: none !important; }

/* ── Layout Shell ──────────────────────────────────────────────── */
#builder-app { display: flex; flex-direction: column; height: 100vh; }

/* ── Header / Chrome ───────────────────────────────────────────── */
.b-chrome {
    flex-shrink: 0;
    height: 52px;
    background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 55%, #312e81 100%);
    border-bottom: 1px solid rgba(255,255,255,0.06);
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 0 14px;
    z-index: 50;
}
.b-chrome-back {
    display: inline-flex; align-items: center; gap: 5px;
    color: #94a3b8; font-size: 12px; font-weight: 600;
    text-decoration: none; white-space: nowrap;
    padding: 5px 10px; border-radius: 8px;
    border: 1px solid rgba(255,255,255,0.1);
    background: rgba(255,255,255,0.05);
    transition: all .15s;
}
.b-chrome-back:hover { color: #fff; background: rgba(255,255,255,0.12); }
.b-chrome-divider { width: 1px; height: 24px; background: rgba(255,255,255,0.1); flex-shrink: 0; }
.b-chrome-logo {
    width: 30px; height: 30px; border-radius: 8px; flex-shrink: 0;
    background: linear-gradient(135deg, #6366f1, #818cf8);
    display: flex; align-items: center; justify-content: center;
    font-size: 14px;
}
.b-chrome-title {
    display: flex; flex-direction: column; gap: 1px; min-width: 0;
}
.b-chrome-title-label { font-size: 10px; color: #64748b; letter-spacing: .05em; text-transform: uppercase; white-space: nowrap; }
.b-chrome-title-name { font-size: 13px; font-weight: 700; color: #f8fafc; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 240px; }
.b-chrome-spacer { flex: 1; }
.b-device-group {
    display: flex; border-radius: 8px; overflow: hidden;
    border: 1px solid rgba(255,255,255,0.12); background: rgba(0,0,0,0.25);
    flex-shrink: 0;
}
.b-device-btn {
    padding: 5px 12px; font-size: 11px; font-weight: 600; border: none; cursor: pointer;
    background: transparent; color: #64748b; display: flex; align-items: center; gap: 4px;
    transition: all .15s;
}
.b-device-btn.active { background: rgba(255,255,255,0.15); color: #fff; }
.b-save-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 16px; border-radius: 8px; border: none; cursor: pointer;
    background: linear-gradient(135deg, #6366f1, #4f46e5);
    color: #fff; font-weight: 700; font-size: 12px;
    font-family: Vazirmatn, Tahoma, sans-serif;
    transition: all .15s; flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(99,102,241,0.4);
}
.b-save-btn:hover { background: linear-gradient(135deg, #4f46e5, #4338ca); transform: translateY(-1px); }
.b-save-btn:disabled { opacity: .65; transform: none; cursor: not-allowed; }

/* ── Three Panels ──────────────────────────────────────────────── */
.b-body { display: flex; flex: 1; min-height: 0; overflow: hidden; }

/* ── Right Panel — Element Library ────────────────────────────── */
.b-panel-right {
    width: 256px; flex-shrink: 0;
    background: #ffffff; border-left: 1px solid #e2e8f0;
    display: flex; flex-direction: column; overflow: hidden;
}
.b-panel-tabs {
    display: flex; border-bottom: 1px solid #e2e8f0;
    background: #f8fafc; padding: 4px;
    gap: 3px; flex-shrink: 0;
}
.b-panel-tab {
    flex: 1; padding: 6px 4px; border: none; border-radius: 6px;
    font-size: 11px; font-weight: 600; cursor: pointer; background: transparent;
    color: #64748b; font-family: Vazirmatn, Tahoma, sans-serif;
    transition: all .15s;
}
.b-panel-tab.active { background: #fff; color: #4f46e5; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
.b-search-box { padding: 8px; border-bottom: 1px solid #f1f5f9; flex-shrink: 0; }
.b-search-input {
    width: 100%; padding: 7px 10px; border: 1px solid #e2e8f0; border-radius: 8px;
    font-size: 12px; font-family: Vazirmatn, Tahoma, sans-serif;
    background: #f8fafc; color: #334155; outline: none;
    transition: border-color .15s, box-shadow .15s;
}
.b-search-input:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.1); background: #fff; }
.b-elements-scroll { flex: 1; overflow-y: auto; padding: 8px; }
.b-cat-label {
    font-size: 10px; font-weight: 700; color: #94a3b8; letter-spacing: .06em;
    text-transform: uppercase; padding: 6px 4px 4px; margin-top: 6px;
    display: flex; align-items: center; gap: 6px;
}
.b-cat-label:first-child { margin-top: 0; }
.b-cat-pill {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 7px; border-radius: 99px; font-size: 9.5px; font-weight: 700;
    letter-spacing: .03em;
}
.b-block-card {
    display: flex; align-items: center; gap: 8px;
    padding: 7px 9px; border-radius: 8px; margin-bottom: 2px;
    border: 1px solid #f1f5f9; background: #fafafa;
    cursor: grab; user-select: none;
    transition: all .15s;
}
.b-block-card:hover { background: #f0f4ff; border-color: #c7d2fe; transform: translateX(-2px); }
.b-block-card:active { cursor: grabbing; }
.b-block-icon {
    width: 30px; height: 30px; border-radius: 7px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px;
}
.b-block-name { font-size: 12px; font-weight: 600; color: #334155; }

/* Kit Cards */
.b-kit-card {
    border-radius: 10px; border: 1px solid #e2e8f0;
    padding: 12px; margin-bottom: 8px;
    background: linear-gradient(180deg, #fff, #f8fafc);
    transition: all .15s;
}
.b-kit-card:hover { border-color: #a5b4fc; box-shadow: 0 4px 16px rgba(79,70,229,.1); }
.b-kit-name { font-weight: 700; font-size: 13px; color: #0f172a; margin: 0 0 4px; }
.b-kit-desc { font-size: 11px; color: #64748b; margin: 0 0 10px; line-height: 1.5; }
.b-kit-apply {
    width: 100%; padding: 7px; border-radius: 7px; border: none; cursor: pointer;
    background: #6366f1; color: #fff; font-weight: 700; font-size: 12px;
    font-family: Vazirmatn, Tahoma, sans-serif; transition: background .15s;
}
.b-kit-apply:hover { background: #4f46e5; }

/* ── Center Panel ──────────────────────────────────────────────── */
.b-center {
    flex: 1; min-width: 0; display: flex; flex-direction: column;
    background: #f1f5f9; overflow: hidden;
}
.b-preview-bar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 8px 14px; background: #fff; border-bottom: 1px solid #e2e8f0;
    flex-shrink: 0;
}
.b-preview-label { font-size: 11px; font-weight: 700; color: #475569; display: flex; align-items: center; gap: 6px; }
.b-preview-hint {
    font-size: 10.5px; color: #6366f1; background: #eef2ff;
    border: 1px solid #c7d2fe; padding: 3px 9px; border-radius: 99px; font-weight: 600;
}
.b-preview-wrap {
    flex: 0 0 auto; padding: 10px 14px 6px;
    display: flex; justify-content: center;
}
.b-preview-inner {
    background: #fff; border-radius: 10px; overflow: hidden;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 24px rgba(0,0,0,.08);
    transition: max-width .25s ease;
    width: 100%;
}
.b-preview-iframe {
    display: block; border: none; width: 100%;
    min-height: 340px; height: 340px; background: #fff;
}
/* ── Structure Tree ────────────────────────────────────────────── */
.b-struct {
    flex: 1; min-height: 0; display: flex; flex-direction: column;
    margin: 0 14px 10px; background: #fff; border-radius: 10px;
    border: 1px solid #e2e8f0; overflow: hidden;
}
.b-struct-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 14px 8px; border-bottom: 1px solid #f1f5f9; flex-shrink: 0;
}
.b-struct-title { font-size: 12px; font-weight: 700; color: #475569; display: flex; align-items: center; gap: 6px; }
.b-struct-body { flex: 1; overflow-y: auto; padding: 8px 10px; }
.b-drop-zone {
    min-height: 44px; border: 2px dashed #cbd5e1; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; color: #94a3b8; transition: all .15s; margin-bottom: 6px;
    cursor: default;
}
.b-drop-zone.drag-over { background: #eef2ff; border-color: #6366f1; color: #6366f1; }
.b-section-block {
    border: 1px solid #e2e8f0; border-radius: 8px;
    margin-bottom: 6px; overflow: hidden;
    transition: border-color .15s, box-shadow .15s;
}
.b-section-block.selected-wrap { border-color: #6366f1; box-shadow: 0 0 0 2px rgba(99,102,241,.2); }
.b-section-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 7px 10px; background: #f8fafc; cursor: pointer;
    border-bottom: 1px solid #e2e8f0;
}
.b-section-head:hover { background: #f0f4ff; }
.b-section-label { font-weight: 700; font-size: 12px; color: #334155; display: flex; align-items: center; gap: 6px; }
.b-section-actions { display: flex; gap: 3px; align-items: center; }
.b-act-btn {
    width: 24px; height: 24px; border-radius: 5px; border: none; cursor: pointer;
    background: transparent; color: #64748b; font-size: 12px;
    display: flex; align-items: center; justify-content: center;
    transition: all .15s;
}
.b-act-btn:hover { background: #e2e8f0; }
.b-act-btn.danger:hover { background: #fee2e2; color: #dc2626; }
.b-add-row-btn {
    font-size: 11px; color: #6366f1; background: transparent; border: none; cursor: pointer;
    padding: 3px 6px; font-weight: 600; font-family: Vazirmatn, Tahoma, sans-serif;
}
.b-add-row-btn:hover { text-decoration: underline; }
.b-row-wrap { padding: 6px 8px; border-bottom: 1px solid #f1f5f9; }
.b-row-wrap:last-child { border-bottom: none; }
.b-row-label { font-size: 10.5px; color: #94a3b8; font-weight: 600; display: flex; justify-between; align-items: center; margin-bottom: 4px; gap: 4px; }
.b-cols-row { display: flex; flex-wrap: wrap; gap: 4px; }
.b-col-box {
    flex: 1; min-width: 80px; background: #f8fafc; border: 1px solid #e2e8f0;
    border-radius: 6px; padding: 4px 6px;
}
.b-col-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 3px; }
.b-col-lbl { font-size: 10px; color: #94a3b8; font-weight: 600; }
.b-widget-drop {
    min-height: 28px; border: 1px dashed #e2e8f0; border-radius: 5px;
    padding: 3px; transition: all .15s;
}
.b-widget-drop.drag-over { background: #eef2ff; border-color: #6366f1; }
.b-widget-item {
    display: flex; align-items: center; justify-content: space-between;
    padding: 3px 6px; border-radius: 5px; cursor: pointer;
    font-size: 11px; color: #475569; gap: 4px; margin-bottom: 2px;
    transition: all .12s;
}
.b-widget-item:hover { background: #eef2ff; color: #4338ca; }
.b-widget-item.selected { background: #c7d2fe; color: #3730a3; font-weight: 700; }
.b-widget-empty { font-size: 10px; color: #cbd5e1; text-align: center; padding: 4px 0; }

/* ── Left Panel — Settings ─────────────────────────────────────── */
.b-panel-left {
    width: 256px; flex-shrink: 0;
    background: #fff; border-right: 1px solid #e2e8f0;
    display: flex; flex-direction: column; overflow: hidden;
}
.b-settings-head {
    padding: 14px 14px 10px; border-bottom: 1px solid #f1f5f9; flex-shrink: 0;
}
.b-settings-title { font-size: 12px; font-weight: 700; color: #334155; display: flex; align-items: center; gap: 6px; }
.b-settings-body { flex: 1; overflow-y: auto; padding: 12px; }
.b-settings-empty { color: #94a3b8; font-size: 12px; line-height: 1.6; text-align: center; padding: 20px 10px; }
.b-settings-empty svg { margin: 0 auto 8px; display: block; opacity: .4; }
.b-type-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 8px; border-radius: 99px; font-size: 10px; font-weight: 700;
    background: #eef2ff; color: #4f46e5; margin-bottom: 12px;
}
.b-form-group { margin-bottom: 10px; }
.b-form-label { display: block; font-size: 11px; font-weight: 600; color: #475569; margin-bottom: 4px; }
.b-form-input, .b-form-select, .b-form-textarea {
    width: 100%; padding: 7px 9px; border: 1px solid #e2e8f0; border-radius: 7px;
    font-size: 12px; font-family: Vazirmatn, Tahoma, sans-serif; color: #1e293b;
    background: #f8fafc; outline: none; transition: all .15s;
}
.b-form-input:focus, .b-form-select:focus, .b-form-textarea:focus {
    border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.08); background: #fff;
}
.b-form-textarea { resize: vertical; min-height: 70px; }
.b-form-section { font-size: 10px; font-weight: 700; color: #cbd5e1; letter-spacing: .07em; text-transform: uppercase; margin: 12px 0 6px; padding-top: 10px; border-top: 1px solid #f1f5f9; }
.b-form-section:first-child { margin-top: 0; border-top: none; padding-top: 0; }
.b-close-btn {
    width: 100%; margin-top: 12px; padding: 7px; border-radius: 7px; border: 1px solid #e2e8f0;
    background: #f8fafc; color: #64748b; font-weight: 600; font-size: 12px; cursor: pointer;
    font-family: Vazirmatn, Tahoma, sans-serif; transition: all .15s;
}
.b-close-btn:hover { background: #fee2e2; border-color: #fca5a5; color: #dc2626; }

/* ── Debug Overlay ─────────────────────────────────────────────── */
.b-debug {
    position: fixed; bottom: 14px; left: 14px; z-index: 99;
    width: 270px; max-height: 220px; overflow: auto;
    background: #0f172a; color: #94a3b8; font-size: 11px;
    border-radius: 10px; border: 1px solid #1e293b;
    box-shadow: 0 8px 30px rgba(0,0,0,.5); padding: 12px;
    font-family: 'Courier New', monospace; direction: ltr;
}

/* ── Scrollbar ─────────────────────────────────────────────────── */
::-webkit-scrollbar { width: 5px; height: 5px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 99px; }
::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>
</head>
<body>
@php
$pageKitsForJs = $pageKits ?? config('builder-page-kits.kits', []);
// Block icon map
$blockIcons = [
    'section'=>'📦','row'=>'⇄','column'=>'▤','heading'=>'🔠','text'=>'📄',
    'image'=>'🖼️','button'=>'🔘','icon'=>'⭐','divider'=>'➖','spacer'=>'↕️',
    'video'=>'🎬','gallery'=>'🗂️','hero'=>'🌟','cta'=>'📢','countdown'=>'⏱️',
    'testimonials'=>'💬','map'=>'🗺️','social'=>'📡','html'=>'</>', 'form'=>'📋',
    'accordion'=>'🪗','slider'=>'🎠','blog_posts'=>'📰','product_grid'=>'🛍️',
];
// Category color pairs [bg, text]
$catColors = [
    'layout'    => ['bg-blue-50',   'text-blue-700'],
    'content'   => ['bg-green-50',  'text-green-700'],
    'media'     => ['bg-purple-50', 'text-purple-700'],
    'marketing' => ['bg-orange-50', 'text-orange-700'],
    'dynamic'   => ['bg-pink-50',   'text-pink-700'],
    'utility'   => ['bg-slate-100', 'text-slate-600'],
    'ecommerce' => ['bg-emerald-50','text-emerald-700'],
];
$catLabels = [
    'layout'=>'چیدمان','content'=>'محتوا','media'=>'رسانه',
    'marketing'=>'بازاریابی','dynamic'=>'پویا','utility'=>'ابزار',
    'ecommerce'=>'فروشگاه',
];
@endphp
<script>
window.builderConfig = {!! json_encode([
    'pageId'       => $page->id,
    'pageTitle'    => $page->title,
    'pageSlug'     => $page->slug,
    'initialContent' => $builderContent,
    'elementsConfig' => $elementsConfig,
    'pageKits'     => $pageKitsForJs,
    'saveUrl'      => $page->id ? route('builder.page.update', $page) : null,
    'previewUrl'   => route('builder.preview'),
    'exitUrl'      => $page->id ? route('page.show', $page->slug) : route('admin.pages.index'),
    'csrf'         => csrf_token(),
    'useV2'        => isset($useV2) && $useV2,
    'testMode'     => isset($testMode) && $testMode,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!};
window.blockIconMap = {!! json_encode($blockIcons) !!};
</script>

<div id="builder-app" x-data="pageBuilder(window.builderConfig)" x-init="init()">

    {{-- ══ HEADER ══════════════════════════════════════════════════════ --}}
    <header class="b-chrome">
        <a :href="exitUrl" class="b-chrome-back">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
            </svg>
            خروج
        </a>
        <div class="b-chrome-divider"></div>
        <div class="b-chrome-logo">✏️</div>
        <div class="b-chrome-title">
            <span class="b-chrome-title-label">سازنده بصری صفحه</span>
            <span class="b-chrome-title-name" x-text="pageTitle"></span>
        </div>
        <div class="b-chrome-spacer"></div>

        {{-- Device Toggles --}}
        <div class="b-device-group">
            <button type="button" class="b-device-btn" :class="device==='desktop'?'active':''" @click="device='desktop'" title="دسکتاپ">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2" stroke-width="2"/><path d="M8 21h8M12 17v4" stroke-width="2" stroke-linecap="round"/></svg>
                دسکتاپ
            </button>
            <button type="button" class="b-device-btn" :class="device==='tablet'?'active':''" @click="device='tablet'" title="تبلت">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="4" y="2" width="16" height="20" rx="2" stroke-width="2"/><circle cx="12" cy="18" r="1" fill="currentColor"/></svg>
                تبلت
            </button>
            <button type="button" class="b-device-btn" :class="device==='mobile'?'active':''" @click="device='mobile'" title="موبایل">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="7" y="2" width="10" height="20" rx="2" stroke-width="2"/><circle cx="12" cy="18" r="1" fill="currentColor"/></svg>
                موبایل
            </button>
        </div>

        <div class="b-chrome-divider"></div>

        {{-- Save --}}
        <template x-if="saveUrl">
            <button type="button" class="b-save-btn" @click="save()" :disabled="saving">
                <template x-if="!saving">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                </template>
                <template x-if="saving">
                    <svg width="14" height="14" class="animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </template>
                <span x-text="saving ? 'در حال ذخیره...' : 'ذخیره صفحه'"></span>
            </button>
        </template>
    </header>

    {{-- ══ BODY ═════════════════════════════════════════════════════════ --}}
    <div class="b-body">

        {{-- ── RIGHT PANEL — Elements Library ─────────────────────── --}}
        <aside class="b-panel-right">
            <div class="b-panel-tabs">
                <button type="button" class="b-panel-tab" :class="leftTab==='widgets'?'active':''" @click="leftTab='widgets'">
                    🧩 المان‌ها
                </button>
                <button type="button" class="b-panel-tab" :class="leftTab==='kits'?'active':''" @click="leftTab='kits'">
                    🎨 قالب‌ها
                </button>
            </div>

            {{-- Elements Tab --}}
            <div x-show="leftTab==='widgets'" style="display:flex;flex-direction:column;flex:1;min-height:0;overflow:hidden;">
                <div class="b-search-box">
                    <input type="search" class="b-search-input" placeholder="🔍 جستجو در المان‌ها..." x-model="libraryFilter">
                </div>
                <div class="b-elements-scroll">
                    {{-- Layout: Section / Row / Column --}}
                    <div x-show="!libraryFilter">
                        <div class="b-cat-label">
                            <span class="b-cat-pill" style="background:#dbeafe;color:#1d4ed8;">چیدمان</span>
                        </div>
                        <div class="b-block-card" draggable="true" data-type="section"
                             @dragstart="onDragStart($event,'section',getDefaultProps('section'))"
                             @click.prevent="addSection()">
                            <div class="b-block-icon" style="background:#dbeafe;">📦</div>
                            <span class="b-block-name">بخش</span>
                        </div>
                        <div class="b-block-card" draggable="true" data-type="row"
                             @dragstart="onDragStart($event,'row',getDefaultProps('row'))">
                            <div class="b-block-icon" style="background:#e0e7ff;">⇄</div>
                            <span class="b-block-name">ردیف</span>
                        </div>
                        <div class="b-block-card" draggable="true" data-type="column"
                             @dragstart="onDragStart($event,'column',getDefaultProps('column'))">
                            <div class="b-block-icon" style="background:#ede9fe;">▤</div>
                            <span class="b-block-name">ستون</span>
                        </div>
                    </div>

                    {{-- Dynamic Categories --}}
                    <template x-for="(cat, catKey) in elementsConfig.byCategory" :key="catKey">
                        <div x-show="!libraryFilter || categoryMatches(catKey, cat)">
                            <div class="b-cat-label" x-show="!libraryFilter">
                                <span class="b-cat-pill" style="background:#dcfce7;color:#166534;"
                                      x-text="elementsConfig.categories[catKey] || catKey"></span>
                            </div>
                            <template x-for="el in cat" :key="el.type">
                                <div x-show="!libraryFilter || elementMatches(el)"
                                     class="b-block-card" draggable="true"
                                     :data-type="el.type"
                                     @dragstart="onDragStart($event, el.type, el.defaultProps || {})"
                                     @click.prevent="addElementToSelected(el)">
                                    <div class="b-block-icon" style="background:#f0fdf4;">
                                        <span x-text="getBlockIcon(el.type)"></span>
                                    </div>
                                    <span class="b-block-name" x-text="el.name"></span>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Kits Tab --}}
            <div x-show="leftTab==='kits'" x-cloak style="flex:1;overflow-y:auto;padding:10px;">
                <p style="font-size:11px;color:#64748b;line-height:1.6;margin:0 0 10px;">
                    قالب‌های آماده حرفه‌ای — با اعمال، <strong>تمام صفحه</strong> جایگزین می‌شود.
                </p>
                <template x-for="kit in kitsList" :key="kit.id">
                    <div class="b-kit-card" :style="'border-top:3px solid '+(kit.accent||'#6366f1')">
                        <p class="b-kit-name" x-text="kit.label"></p>
                        <p class="b-kit-desc" x-text="kit.description"></p>
                        <button type="button" class="b-kit-apply" @click="applyFullPageKit(kit)">اعمال قالب</button>
                    </div>
                </template>
                <template x-if="!kitsList || kitsList.length===0">
                    <p style="text-align:center;color:#94a3b8;font-size:12px;padding:20px 0;">قالبی یافت نشد</p>
                </template>
            </div>
        </aside>

        {{-- ── CENTER — Preview + Structure ───────────────────────── --}}
        <main class="b-center"
              @dragover.prevent="dragOverSection=true;$event.dataTransfer.dropEffect='copy'"
              @dragleave="dragOverSection=false"
              @drop.prevent="onDropSection($event)">

            {{-- Preview Bar --}}
            <div class="b-preview-bar">
                <span class="b-preview-label">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:#6366f1"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    پیش‌نمایش زنده
                </span>
                <span class="b-preview-hint">کلیک روی بلوک = انتخاب</span>
            </div>

            {{-- Iframe --}}
            <div class="b-preview-wrap" style="flex-shrink:0;">
                <div class="b-preview-inner" :style="previewContainerStyle">
                    <iframe id="builder-preview-iframe" class="b-preview-iframe"
                            title="پیش‌نمایش زنده"></iframe>
                </div>
            </div>

            {{-- Structure Tree --}}
            <div class="b-struct" :class="{'drag-over':dragOverSection}">
                <div class="b-struct-head">
                    <span class="b-struct-title">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:#6366f1"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h8M4 18h8"/></svg>
                        ساختار صفحه
                    </span>
                    <button type="button"
                            style="font-size:11px;color:#6366f1;background:#eef2ff;border:1px solid #c7d2fe;border-radius:6px;padding:4px 9px;cursor:pointer;font-family:Vazirmatn,Tahoma,sans-serif;font-weight:700;"
                            @click="addSection()">
                        + بخش جدید
                    </button>
                </div>
                <div class="b-struct-body">
                    <div class="b-drop-zone"
                         @dragover.prevent="dragOverSection=true;$event.dataTransfer.dropEffect='copy'"
                         @dragleave="dragOverSection=false"
                         @drop.prevent="onDropSection($event)"
                         :class="{'drag-over':dragOverSection}"
                         x-show="content.sections && content.sections.length===0"
                         x-transition>
                        📦 بخش جدید را اینجا رها کنید
                    </div>
                    <div id="canvas-sections-list">
                        <template x-for="(section, sIdx) in content.sections" :key="section.id">
                            <div class="b-section-block" :class="{'selected-wrap':selectedId===section.id}" :data-section-id="section.id">
                                <div class="b-section-head" @click="select(section.id)">
                                    <span class="b-section-label">
                                        <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20" style="color:#6366f1"><path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 6a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2zm0 6a1 1 0 011-1h6a1 1 0 010 2H4a1 1 0 01-1-1z"/></svg>
                                        بخش <span x-text="sIdx+1"></span>
                                    </span>
                                    <div class="b-section-actions">
                                        <button type="button" class="b-act-btn" @click.stop="addRow(sIdx)" title="افزودن ردیف"
                                                style="font-size:10px;font-weight:700;color:#6366f1;background:#eef2ff;">+ردیف</button>
                                        <button type="button" class="b-act-btn" @click.stop="duplicateSection(sIdx)" title="کپی">
                                            <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        </button>
                                        <button type="button" class="b-act-btn danger" @click.stop="removeSection(sIdx)" title="حذف">
                                            <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </div>
                                {{-- Rows --}}
                                <div style="padding:6px 8px;">
                                    <template x-for="(row, rIdx) in (section.blocks||[])" :key="row.id">
                                        <div class="b-row-wrap">
                                            <div class="b-row-label" style="display:flex;justify-content:space-between;align-items:center;">
                                                <span>⇄ ردیف <span x-text="rIdx+1"></span></span>
                                                <div style="display:flex;gap:3px;">
                                                    <button type="button" class="b-act-btn" @click.stop="addColumn(sIdx,rIdx)"
                                                            style="font-size:10px;font-weight:700;color:#059669;background:#dcfce7;">+ستون</button>
                                                    <button type="button" class="b-act-btn danger" @click.stop="removeBlock(section.blocks,rIdx)">
                                                        <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="b-cols-row">
                                                <template x-for="(col, cIdx) in (row.blocks||[])" :key="col.id">
                                                    <div class="b-col-box">
                                                        <div class="b-col-head">
                                                            <span class="b-col-lbl">▤ ستون</span>
                                                            <button type="button" class="b-act-btn danger" @click.stop="removeBlock(row.blocks,cIdx)" style="width:18px;height:18px;">
                                                                <svg width="9" height="9" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                                                            </button>
                                                        </div>
                                                        <div class="b-widget-drop"
                                                             @dragover.prevent="$event.dataTransfer.dropEffect='copy';setDropTarget(section.id,row.id,col.id)"
                                                             @dragleave="clearDropTarget()"
                                                             @drop.prevent="onDropWidget($event,sIdx,rIdx,cIdx)"
                                                             :class="dropTarget&&dropTarget.colId===col.id?'drag-over':''">
                                                            <template x-for="(el,eIdx) in (col.blocks||[])" :key="el.id">
                                                                <div class="b-widget-item" :class="{'selected':selectedId===el.id}" @click.stop="select(el.id)">
                                                                    <span style="font-size:11px;" x-text="getBlockIcon(el.type)+' '+getElementName(el.type)"></span>
                                                                    <button type="button" class="b-act-btn danger" @click.stop="removeBlock(col.blocks,eIdx)" style="width:16px;height:16px;flex-shrink:0;">
                                                                        <svg width="9" height="9" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                                                                    </button>
                                                                </div>
                                                            </template>
                                                            <div class="b-widget-empty" x-show="!(col.blocks&&col.blocks.length)">+ رها کنید</div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                    <button type="button" class="b-add-row-btn" @click="addRow(sIdx)">+ ردیف جدید</button>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div class="b-drop-zone" style="margin-top:4px;"
                         @dragover.prevent="dragOverSection=true;$event.dataTransfer.dropEffect='copy'"
                         @dragleave="dragOverSection=false"
                         @drop.prevent="onDropSection($event)"
                         :class="{'drag-over':dragOverSection}">
                        + بخش جدید اینجا رها کنید
                    </div>
                </div>
            </div>
        </main>

        {{-- ── LEFT PANEL — Settings ────────────────────────────────── --}}
        <aside class="b-panel-left">
            <div class="b-settings-head">
                <div class="b-settings-title">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:#6366f1"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                    تنظیمات المان
                </div>
            </div>
            <div class="b-settings-body">
                <template x-if="!selectedNode">
                    <div class="b-settings-empty">
                        <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/></svg>
                        روی یک بخش، ردیف، ستون یا ویجت کلیک کنید تا تنظیمات آن را ببینید.
                    </div>
                </template>
                <template x-if="selectedNode">
                    <div>
                        <div class="b-type-badge">
                            <span x-text="getBlockIcon(selectedNode.type)"></span>
                            <span x-text="getElementName(selectedNode.type)"></span>
                        </div>

                        {{-- Section Settings --}}
                        <template x-if="selectedNode.type==='section'">
                            <div>
                                <div class="b-form-section">چیدمان</div>
                                <div class="b-form-group">
                                    <label class="b-form-label">فاصله داخلی (Padding)</label>
                                    <select class="b-form-select" x-model="selectedNode.props.padding" @change="refreshPreview()">
                                        <option value="none">بدون فاصله</option>
                                        <option value="sm">کوچک</option>
                                        <option value="md">متوسط</option>
                                        <option value="lg">بزرگ</option>
                                        <option value="xl">بسیار بزرگ</option>
                                    </select>
                                </div>
                                <div class="b-form-group">
                                    <label class="b-form-label">گوشه‌ها</label>
                                    <select class="b-form-select" x-model="selectedNode.style.layout.radius" @change="refreshPreview()">
                                        <option value="">پیش‌فرض</option>
                                        <option value="sm">گرد کم</option>
                                        <option value="md">گرد متوسط</option>
                                        <option value="lg">گرد زیاد</option>
                                    </select>
                                </div>
                                <div class="b-form-group">
                                    <label class="b-form-label">سایه</label>
                                    <select class="b-form-select" x-model="selectedNode.style.layout.shadow" @change="refreshPreview()">
                                        <option value="">بدون سایه</option>
                                        <option value="sm">سایه کم</option>
                                        <option value="md">سایه متوسط</option>
                                        <option value="lg">سایه زیاد</option>
                                    </select>
                                </div>
                                <div class="b-form-section">پس‌زمینه</div>
                                <div class="b-form-group">
                                    <label class="b-form-label">نوع پس‌زمینه</label>
                                    <select class="b-form-select" x-model="selectedNode.props.background" @change="refreshPreview()">
                                        <option value="transparent">شفاف</option>
                                        <option value="background">پس‌زمینه سایت</option>
                                        <option value="surface">سطح سفید</option>
                                        <option value="primary">رنگ اصلی</option>
                                    </select>
                                </div>
                                <div class="b-form-group">
                                    <label class="b-form-label">رنگ سفارشی (hex)</label>
                                    <input type="text" class="b-form-input" x-model="selectedNode.style.background.color" placeholder="#ffffff" @input="refreshPreview()">
                                </div>
                                <div class="b-form-section">افکت</div>
                                <div class="b-form-group">
                                    <label class="b-form-label">Hover</label>
                                    <select class="b-form-select" x-model="selectedNode.style.effects.hover" @change="refreshPreview()">
                                        <option value="">غیرفعال</option>
                                        <option value="lift">بالا آمدن</option>
                                    </select>
                                </div>
                            </div>
                        </template>

                        {{-- Column Settings --}}
                        <template x-if="selectedNode.type==='column'">
                            <div>
                                <div class="b-form-section">اندازه</div>
                                <div class="b-form-group">
                                    <label class="b-form-label">عرض ستون</label>
                                    <select class="b-form-select" x-model="selectedNode.props.width" @change="refreshPreview()">
                                        <option value="1/1">تمام عرض</option>
                                        <option value="1/2">نصف (۵۰٪)</option>
                                        <option value="1/3">یک سوم (۳۳٪)</option>
                                        <option value="2/3">دو سوم (۶۶٪)</option>
                                        <option value="1/4">یک چهارم (۲۵٪)</option>
                                        <option value="3/4">سه چهارم (۷۵٪)</option>
                                    </select>
                                </div>
                            </div>
                        </template>

                        {{-- Heading Settings --}}
                        <template x-if="selectedNode.type==='heading'">
                            <div>
                                <div class="b-form-section">محتوا</div>
                                <div class="b-form-group">
                                    <label class="b-form-label">متن عنوان</label>
                                    <input type="text" class="b-form-input" x-model="selectedNode.props.text" @input="refreshPreview()" placeholder="عنوان را بنویسید...">
                                </div>
                                <div class="b-form-group">
                                    <label class="b-form-label">سطح (H1–H6)</label>
                                    <select class="b-form-select" x-model="selectedNode.props.level" @change="refreshPreview()">
                                        <option value="1">H1 — بزرگ‌ترین</option>
                                        <option value="2">H2</option>
                                        <option value="3">H3</option>
                                        <option value="4">H4</option>
                                        <option value="5">H5</option>
                                        <option value="6">H6 — کوچک‌ترین</option>
                                    </select>
                                </div>
                                <div class="b-form-section">استایل</div>
                                <div class="b-form-group">
                                    <label class="b-form-label">تراز متن</label>
                                    <select class="b-form-select" x-model="selectedNode.style.typography.textAlign" @change="refreshPreview()">
                                        <option value="">پیش‌فرض (راست)</option>
                                        <option value="right">راست</option>
                                        <option value="center">وسط</option>
                                        <option value="left">چپ</option>
                                    </select>
                                </div>
                                <div class="b-form-group">
                                    <label class="b-form-label">رنگ متن</label>
                                    <input type="text" class="b-form-input" x-model="selectedNode.style.typography.color" @input="refreshPreview()" placeholder="#111827">
                                </div>
                                <div class="b-form-group">
                                    <label class="b-form-label">اندازه فونت</label>
                                    <select class="b-form-select" x-model="selectedNode.style.typography.fontSize" @change="refreshPreview()">
                                        <option value="">پیش‌فرض</option>
                                        <option value="sm">کوچک</option>
                                        <option value="md">متوسط</option>
                                        <option value="lg">بزرگ</option>
                                        <option value="xl">خیلی بزرگ</option>
                                    </select>
                                </div>
                            </div>
                        </template>

                        {{-- Text Settings --}}
                        <template x-if="selectedNode.type==='text'">
                            <div>
                                <div class="b-form-section">محتوا</div>
                                <div class="b-form-group">
                                    <label class="b-form-label">متن</label>
                                    <textarea class="b-form-textarea" x-model="selectedNode.props.content" @input="refreshPreview()" placeholder="متن خود را بنویسید..."></textarea>
                                </div>
                                <div class="b-form-section">استایل</div>
                                <div class="b-form-group">
                                    <label class="b-form-label">تراز</label>
                                    <select class="b-form-select" x-model="selectedNode.style.typography.textAlign" @change="refreshPreview()">
                                        <option value="">پیش‌فرض</option>
                                        <option value="right">راست</option>
                                        <option value="center">وسط</option>
                                        <option value="left">چپ</option>
                                    </select>
                                </div>
                                <div class="b-form-group">
                                    <label class="b-form-label">رنگ متن</label>
                                    <input type="text" class="b-form-input" x-model="selectedNode.style.typography.color" @input="refreshPreview()" placeholder="#4b5563">
                                </div>
                            </div>
                        </template>

                        {{-- Button Settings --}}
                        <template x-if="selectedNode.type==='button'">
                            <div>
                                <div class="b-form-section">محتوا</div>
                                <div class="b-form-group">
                                    <label class="b-form-label">متن دکمه</label>
                                    <input type="text" class="b-form-input" x-model="selectedNode.props.text" @input="refreshPreview()">
                                </div>
                                <div class="b-form-group">
                                    <label class="b-form-label">لینک (URL)</label>
                                    <input type="text" class="b-form-input" x-model="selectedNode.props.url" @input="refreshPreview()" placeholder="https://...">
                                </div>
                            </div>
                        </template>

                        {{-- Hero Settings --}}
                        <template x-if="selectedNode.type==='hero'">
                            <div>
                                <div class="b-form-section">محتوا</div>
                                <div class="b-form-group">
                                    <label class="b-form-label">عنوان</label>
                                    <input type="text" class="b-form-input" x-model="selectedNode.props.title" @input="refreshPreview()">
                                </div>
                                <div class="b-form-group">
                                    <label class="b-form-label">زیرعنوان</label>
                                    <input type="text" class="b-form-input" x-model="selectedNode.props.subtitle" @input="refreshPreview()">
                                </div>
                            </div>
                        </template>

                        {{-- Image Settings --}}
                        <template x-if="selectedNode.type==='image'">
                            <div>
                                <div class="b-form-section">تصویر</div>
                                <div class="b-form-group">
                                    <label class="b-form-label">آدرس تصویر (URL)</label>
                                    <input type="text" class="b-form-input" x-model="selectedNode.props.url" @input="refreshPreview()" placeholder="https://...">
                                </div>
                                <div class="b-form-group">
                                    <label class="b-form-label">متن جایگزین (alt)</label>
                                    <input type="text" class="b-form-input" x-model="selectedNode.props.alt" @input="refreshPreview()">
                                </div>
                            </div>
                        </template>

                        <button type="button" class="b-close-btn" @click="clearSelection()">
                            بستن تنظیمات
                        </button>
                    </div>
                </template>
            </div>
        </aside>
    </div>

    {{-- Debug Overlay (test mode) --}}
    <div class="b-debug" x-show="testMode" x-cloak x-transition>
        <div style="color:#fbbf24;font-weight:700;margin-bottom:6px;">⚙ حالت تست</div>
        <div>المان‌ها: <span x-text="collectElementIds(content.sections).length"></span></div>
        <div x-show="lastRenderStats">زمان رندر: <span x-text="lastRenderStats?.render_time_ms+'ms'"></span></div>
        <div x-show="lastRenderStats">حافظه: <span x-text="lastRenderStats?formatBytes(lastRenderStats.memory_peak_bytes):'—'"></span></div>
    </div>
</div>

<script>
function pageBuilder(config) {
    return {
        pageId: config.pageId,
        pageTitle: config.pageTitle,
        pageSlug: config.pageSlug,
        content: JSON.parse(JSON.stringify(config.initialContent?.sections ? config.initialContent : { sections: [] })),
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
        leftTab: 'widgets',
        libraryFilter: '',
        pageKits: config.pageKits || {},

        get kitsList() {
            const p = this.pageKits || {};
            return Object.keys(p).map(k => p[k]);
        },

        get previewContainerStyle() {
            const w = this.device === 'mobile' ? '375px' : this.device === 'tablet' ? '768px' : '100%';
            return { maxWidth: w, width: '100%', margin: '0 auto' };
        },

        getBlockIcon(type) {
            return (window.blockIconMap || {})[type] || '▪';
        },

        categoryMatches(catKey, cat) {
            const f = (this.libraryFilter || '').trim().toLowerCase();
            if (!f) return true;
            const label = String(this.elementsConfig.categories[catKey] || catKey).toLowerCase();
            if (label.includes(f)) return true;
            return cat.some(el => this.elementMatches(el));
        },

        elementMatches(el) {
            const f = (this.libraryFilter || '').trim().toLowerCase();
            if (!f) return true;
            return String(el.name || '').toLowerCase().includes(f) || String(el.type || '').toLowerCase().includes(f);
        },

        applyFullPageKit(kit) {
            if (!kit?.sections) return;
            if ((this.content.sections||[]).length > 0 && !confirm('تمام بخش‌های فعلی جایگزین شود؟')) return;
            const clone = JSON.parse(JSON.stringify(kit.sections));
            this.renewIdsTree(clone);
            this.content.sections = clone;
            this.selectedId = null;
            this.selectedNode = null;
            this.refreshPreview();
            this.setupSortable();
        },

        renewIdsTree(blocks) {
            if (!Array.isArray(blocks)) return;
            blocks.forEach(b => { b.id = this.uid(); this.ensureStyle(b); this.renewIdsTree(b.blocks); });
        },

        init() {
            if (!this.content.sections) this.content.sections = [];
            this.ensureUniqueIds(this.content.sections);
            window.addEventListener('message', this.onPreviewPostMessage.bind(this));
            this.refreshPreview();
            this.setupSortable();
        },

        ensureUniqueIds(blocks) {
            if (!Array.isArray(blocks)) return;
            blocks.forEach(b => { if (!b.id) b.id = this.uid(); this.ensureStyle(b); this.ensureUniqueIds(b.blocks); });
        },

        uid() { return 'el_' + Date.now() + '_' + Math.random().toString(36).slice(2, 11); },

        ensureStyle(node) {
            if (!node) return;
            if (!node.style) node.style = {};
            if (!node.style.layout) node.style.layout = {};
            if (!node.style.typography) node.style.typography = {};
            if (!node.style.background) node.style.background = {};
            if (!node.style.effects) node.style.effects = {};
        },

        getDefaultProps(type) {
            for (const cat of Object.values(this.elementsConfig.byCategory || {})) {
                const el = cat.find(e => e.type === type);
                if (el?.defaultProps) return { ...el.defaultProps };
            }
            if (type === 'section') return { padding: 'md', background: 'transparent', container: true };
            if (type === 'row') return { gap: 'md' };
            if (type === 'column') return { width: '1/1' };
            return {};
        },

        get contentForSave() { return { sections: this.content.sections || [] }; },

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
            try { return raw ? JSON.parse(raw) : null; } catch { return null; }
        },

        setDropTarget(sectionId, rowId, colId) { this.dropTarget = { sectionId, rowId, colId }; },
        clearDropTarget() { this.dropTarget = null; },

        onDropSection(ev) {
            this.dragOverSection = false;
            const data = this.dragPayload || this.getDropData(ev);
            if (!data || data.type !== 'section') return;
            this.addSectionFromDrag(data);
        },

        addSectionFromDrag(data) {
            const section = {
                id: this.uid(), type: 'section',
                props: { padding: 'md', background: 'transparent', container: true, ...data.defaultProps },
                style: { layout: {}, typography: {}, background: {}, effects: {} },
                blocks: [{ id: this.uid(), type: 'row', props: { gap: 'md' },
                           blocks: [{ id: this.uid(), type: 'column', props: { width: '1/1' }, blocks: [] }] }]
            };
            this.content.sections = this.content.sections || [];
            this.content.sections.push(section);
            this.refreshPreview();
        },

        onDropWidget(ev, sIdx, rIdx, cIdx) {
            const data = this.dragPayload || this.getDropData(ev);
            if (!data?.type) return;
            const { type } = data;
            const section = this.content.sections[sIdx];
            if (!section?.blocks?.[rIdx]) return;
            const row = section.blocks[rIdx];
            const col = (row.blocks || [])[cIdx];
            if (!col) return;
            if (type === 'section') { this.addSectionFromDrag(data); return; }
            if (type === 'row') { row.blocks = row.blocks||[]; row.blocks.push({ id:this.uid(), type:'column', props:{width:'1/2'}, blocks:[] }); this.refreshPreview(); return; }
            if (type === 'column') { row.blocks = row.blocks||[]; row.blocks.push({ id:this.uid(), type:'column', props:{width:'1/2',...data.defaultProps}, blocks:[] }); this.refreshPreview(); return; }
            col.blocks = col.blocks || [];
            col.blocks.push({ id:this.uid(), type, props:{...this.getDefaultProps(type),...data.defaultProps}, style:{layout:{},typography:{},background:{},effects:{}} });
            this.refreshPreview();
        },

        select(id) {
            this.selectedId = id;
            this.selectedNode = this.findNode(this.content.sections, id);
            if (this.selectedNode) this.ensureStyle(this.selectedNode);
            this.syncIframeSelection(id);
        },

        syncIframeSelection(elementId) {
            const iframe = document.getElementById('builder-preview-iframe');
            try { if (iframe?.contentWindow) iframe.contentWindow.postMessage({ source:'page-builder-parent', type:'applySelection', elementId:elementId||'' }, window.location.origin||'*'); } catch {}
        },

        onPreviewPostMessage(ev) {
            try {
                if (ev.origin && ev.origin !== window.location.origin) return;
                const d = ev.data;
                if (!d || d.source !== 'page-builder-preview' || d.type !== 'select') return;
                if (typeof d.elementId !== 'string') return;
                if (!this.findNode(this.content.sections, d.elementId)) return;
                this.selectedId = d.elementId;
                this.selectedNode = this.findNode(this.content.sections, d.elementId);
                if (this.selectedNode) this.ensureStyle(this.selectedNode);
                this.syncIframeSelection(d.elementId);
            } catch {}
        },

        clearSelection() { this.selectedId = null; this.selectedNode = null; this.syncIframeSelection(''); },

        findNode(blocks, id) {
            if (!blocks || !id) return null;
            for (const b of blocks) { if (b.id === id) return b; const f = this.findNode(b.blocks, id); if (f) return f; }
            return null;
        },

        getElementName(type) {
            for (const cat of Object.values(this.elementsConfig.byCategory || {})) { const el = cat.find(e => e.type === type); if (el) return el.name; }
            const names = { section:'بخش', row:'ردیف', column:'ستون', heading:'عنوان', text:'متن', image:'تصویر', button:'دکمه', icon:'آیکون', divider:'جداکننده', spacer:'فاصله', video:'ویدیو', gallery:'گالری', hero:'هیرو', cta:'دکمه اقدام', countdown:'شمارنده معکوس', html:'HTML' };
            return names[type] || type;
        },

        collectElementIds(blocks) { if (!blocks) return []; const ids=[]; blocks.forEach(b=>{if(b.id)ids.push(b.id);ids.push(...this.collectElementIds(b.blocks));}); return ids; },
        formatBytes(n) { if(!n)return'—'; if(n<1024)return n+'B'; if(n<1048576)return(n/1024).toFixed(1)+'KB'; return(n/1048576).toFixed(2)+'MB'; },

        addSection() { this.addSectionFromDrag({ type:'section', defaultProps:{} }); },
        addRow(sIdx) {
            const s = this.content.sections[sIdx];
            if (!s) return;
            s.blocks = s.blocks||[];
            s.blocks.push({ id:this.uid(), type:'row', props:{gap:'md'}, blocks:[{id:this.uid(),type:'column',props:{width:'1/1'},blocks:[]}] });
            this.refreshPreview();
        },
        addColumn(sIdx, rIdx) {
            const s = this.content.sections[sIdx];
            if (!s?.blocks?.[rIdx]) return;
            const row = s.blocks[rIdx];
            row.blocks = row.blocks||[];
            row.blocks.push({ id:this.uid(), type:'column', props:{width:'1/2'}, blocks:[] });
            this.refreshPreview();
        },
        addElementToSelected(el) {
            if (!el?.type) return;
            if (!this.selectedId) { alert('ابتدا یک ستون را انتخاب کنید.'); return; }
            const node = this.findNode(this.content.sections, this.selectedId);
            if (!node || node.type !== 'column') { alert('ویجت‌ها فقط داخل ستون قرار می‌گیرند.'); return; }
            node.blocks = node.blocks||[];
            node.blocks.push({ id:this.uid(), type:el.type, props:{...this.getDefaultProps(el.type),...el.defaultProps||{}}, style:{layout:{},typography:{},background:{},effects:{}} });
            this.refreshPreview();
        },
        removeSection(idx) { this.content.sections.splice(idx,1); this.selectedId=null; this.selectedNode=null; this.refreshPreview(); },
        removeBlock(parentBlocks, idx) { if(!parentBlocks)return; parentBlocks.splice(idx,1); this.selectedId=null; this.selectedNode=null; this.refreshPreview(); },
        duplicateSection(idx) {
            const s = JSON.parse(JSON.stringify(this.content.sections[idx]));
            const renew = b => { b.id=this.uid(); (b.blocks||[]).forEach(renew); };
            renew(s);
            this.content.sections.splice(idx+1,0,s);
            this.refreshPreview();
        },

        setupSortable() {
            setTimeout(() => {
                const list = document.getElementById('canvas-sections-list');
                if (!list || typeof Sortable === 'undefined') return;
                new Sortable(list, { animation:150, draggable:'.b-section-block', onEnd:(ev)=>{
                    const {oldIndex:from,newIndex:to} = ev;
                    if (from===to||!this.content.sections) return;
                    const item=this.content.sections.splice(from,1)[0];
                    this.content.sections.splice(to,0,item);
                    this.refreshPreview();
                }});
            }, 0);
        },

        refreshPreview() {
            clearTimeout(this.previewDebounce);
            this.previewDebounce = setTimeout(() => {
                const payload = this.useV2 ? { builder_content_v2: this.contentForSave } : { builder_content: this.contentForSave };
                if (this.testMode) payload._test_mode = 1;
                fetch(this.previewUrl, {
                    method:'POST',
                    headers:{'Content-Type':'application/json','X-CSRF-TOKEN':this.csrf,'Accept':'application/json'},
                    body:JSON.stringify(payload)
                }).then(r=>r.json()).then(data=>{
                    if (this.testMode && data.render_time_ms !== undefined) this.lastRenderStats = { render_time_ms:data.render_time_ms, memory_peak_bytes:data.memory_peak_bytes };
                    const iframe = document.getElementById('builder-preview-iframe');
                    if (iframe && data.html !== undefined) {
                        const doc = iframe.contentDocument || iframe.contentWindow.document;
                        doc.open();
                        doc.write('<!DOCTYPE html><html dir="rtl" lang="fa"><head><meta charset="utf-8"><style>@font-face{font-family:Vazirmatn;src:url(/assets/fonts/vazirmatn/Vazirmatn-Regular.woff2) format("woff2");font-weight:400;font-display:swap}@font-face{font-family:Vazirmatn;src:url(/assets/fonts/vazirmatn/Vazirmatn-Bold.woff2) format("woff2");font-weight:700;font-display:swap}*{box-sizing:border-box}body{font-family:Vazirmatn,Tahoma,sans-serif;margin:0;padding:1.25rem;direction:rtl;background:#f8fafc;min-height:100px}.tb-section{box-sizing:border-box}.tb-padding-none{padding-top:0;padding-bottom:0}.tb-padding-sm{padding-top:1.25rem;padding-bottom:1.25rem}.tb-padding-md{padding-top:2.5rem;padding-bottom:2.5rem}.tb-padding-lg{padding-top:3.5rem;padding-bottom:3.5rem}.tb-padding-xl{padding-top:4.5rem;padding-bottom:4.5rem}.tb-bg-background{background:#f9fafb}.tb-bg-surface{background:#fff}.tb-bg-primary{background:#4f46e5;color:#fff}.tb-container{max-width:80rem;margin:0 auto;padding:0 1rem}.tb-row{display:flex;flex-wrap:wrap;gap:1rem}.tb-column{min-width:0}.tb-col-full{flex:1 1 100%}.tb-col-1-2{flex:1 1 calc(50% - .5rem)}.tb-col-1-3{flex:1 1 calc(33.333% - .67rem)}.tb-col-2-3{flex:1 1 calc(66.666% - .67rem)}.tb-col-1-4{flex:1 1 calc(25% - .75rem)}.tb-col-3-4{flex:1 1 calc(75% - .75rem)}.tb-hero{padding:2rem;text-align:center;border-radius:.5rem;background:#4f46e5;color:#fff}.tb-heading{margin:0 0 .5rem;font-weight:700}.tb-text{line-height:1.7}.tb-image{display:block;max-width:100%;margin:0 auto 1.5rem}.tb-image .tb-img{display:block;max-width:100%;height:auto;margin:0 auto;max-height:480px;object-fit:contain}.tb-btn{display:inline-block;padding:.5rem 1rem;border-radius:.5rem;text-decoration:none;font-weight:600}.tb-btn-primary{background:#4338ca;color:#fff}.tb-radius-sm{border-radius:.5rem}.tb-radius-md{border-radius:.75rem}.tb-radius-lg{border-radius:1rem}.tb-shadow-sm{box-shadow:0 1px 3px rgba(15,23,42,.16)}.tb-shadow-md{box-shadow:0 4px 12px rgba(15,23,42,.18)}.tb-shadow-lg{box-shadow:0 12px 30px rgba(15,23,42,.22)}.tb-hover-lift{transition:transform .2s,box-shadow .2s}.tb-hover-lift:hover{transform:translateY(-4px);box-shadow:0 10px 25px rgba(15,23,42,.22)}@media(max-width:767px){.tb-col-1-2,.tb-col-1-3,.tb-col-2-3{flex:1 1 100%}}</style></head><body>' + data.html + '</body></html>');
                        doc.close();
                        try {
                            var prevSt=doc.querySelector('style[data-blv="1"]'); if(prevSt)prevSt.remove();
                            var prevSc=doc.querySelector('script[data-blv="1"]'); if(prevSc)prevSc.remove();
                            var liveSt=doc.createElement('style'); liveSt.setAttribute('data-blv','1');
                            liveSt.textContent='[id^="tb-"]{cursor:pointer;transition:outline .12s}[id^="tb-"]:hover{outline:2px solid rgba(99,102,241,.5);outline-offset:2px}.tb-builder-selected{outline:3px solid #6366f1!important;outline-offset:2px;box-shadow:0 0 0 4px rgba(99,102,241,.15)!important;position:relative;z-index:2}';
                            doc.head.appendChild(liveSt);
                            var liveSc=doc.createElement('script'); liveSc.setAttribute('data-blv','1');
                            liveSc.textContent='(function(){var PW=window.parent;if(!PW)return;function cs(){document.querySelectorAll(".tb-builder-selected").forEach(n=>n.classList.remove("tb-builder-selected"));}function as(eid){cs();if(!eid)return;var el=document.getElementById("tb-"+eid);if(!el)return;el.classList.add("tb-builder-selected");try{el.scrollIntoView({behavior:"smooth",block:"center"});}catch{}}document.addEventListener("click",e=>{var t=e.target.closest(\'[id^="tb-"]\');if(!t||t.id.indexOf("tb-")!==0)return;e.preventDefault();e.stopPropagation();var raw=t.id.slice(3);cs();t.classList.add("tb-builder-selected");PW.postMessage({source:"page-builder-preview",type:"select",elementId:raw},"*");},true);window.addEventListener("message",ev=>{if(ev.source!==PW)return;var d=ev.data;if(!d||d.source!=="page-builder-parent"||d.type!=="applySelection")return;as(d.elementId||"");});})()';
                            doc.body.appendChild(liveSc);
                        } catch {}
                        requestAnimationFrame(()=>this.syncIframeSelection(this.selectedId));
                    }
                }).catch(()=>{});
            }, 280);
        },

        save() {
            this.saving = true;
            const payload = this.useV2 ? { builder_content_v2: this.contentForSave } : { builder_content: this.contentForSave };
            fetch(this.saveUrl, {
                method:'PUT',
                headers:{'Content-Type':'application/json','X-CSRF-TOKEN':this.csrf,'Accept':'application/json'},
                body:JSON.stringify(payload)
            }).then(r=>r.json()).then(()=>{
                this.saving=false;
                const btn = document.querySelector('.b-save-btn');
                if(btn){ btn.style.background='linear-gradient(135deg,#059669,#047857)'; setTimeout(()=>btn.style.background='',2000); }
            }).catch(()=>{ this.saving=false; alert('خطا در ذخیره.'); });
        }
    };
}
</script>
</body>
</html>
