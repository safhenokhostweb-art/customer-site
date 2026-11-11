@push('head')
{{-- No <title> here. layouts/canvas-theme.blade.php renders its own
     <title> at line 73 AND renders @stack('head') at line 166, inside the
     same <head>. A pushed <title> therefore produced a SECOND <title> on
     every live-preview document — verified: 2 titles rendered before this
     change. The layout's title is the correct one. --}}
<style>
*, *::before, *::after { box-sizing: border-box; }
html { scroll-behavior: smooth; }
{{-- Canvas fidelity fix (2026-09-20): this iframe is what the Theme
     Design Canvas editor's WYSIWYG surface actually shows to the site
     owner — it used to carry its own tiny, hand-rolled CSS subset (no
     theme-runtime::css.contract, no nav_header/nav_footer, hero_split/
     hero_gradient, gallery, promo_banner/countdown, wishlist/installment
     CSS, and only 4 raw --tb-* custom properties instead of the full
     ThemeTokenNormalizer set), which had silently drifted from
     layouts/canvas-theme.blade.php — the SAME renderer output (both go
     through ThemeCanvasRenderer) rendered with materially different CSS.
     Now shares the exact same partial as the real public page: a single,
     un-driftable source for both. Do not re-add any of these rules
     locally; edit the partial. --}}

/* ── Editor canvas overlay ── */
[data-tb-block-id] {
    cursor: pointer;
    outline: 2px solid transparent;
    outline-offset: 0;
    transition: outline-color 0.12s ease;
}
[data-tb-block-id]:hover { outline: 2px solid rgba(99,102,241,0.55); }
[data-tb-block-id].tb-selected {
    outline: 2px solid #4f46e5;
    box-shadow: 0 0 0 4px rgba(79,70,229,0.12);
}
[data-tb-block-id]::before {
    content: attr(data-tb-block-type);
    display: none;
    position: absolute;
    top: 0; right: 0;
    z-index: 200;
    background: #4f46e5; color: #fff;
    font-size: 10px; font-family: 'Vazirmatn', sans-serif; font-weight: 600;
    padding: 2px 7px;
    border-radius: 0 0 0 5px;
    pointer-events: none; white-space: nowrap; line-height: 1.6;
}

/* ── Drop zones (TB3-03) ── */
.tb-drop-zone {
    height: 0; margin: 0;
    transition: height 0.15s ease, opacity 0.15s ease;
    position: relative; overflow: visible; opacity: 0;
}
body.tb-drag-active .tb-drop-zone {
    height: 10px; opacity: 1;
    background: rgba(99,102,241,0.08); border-radius: 5px;
}
.tb-drop-zone.tb-drop-active {
    height: 10px !important; opacity: 1 !important;
    background: #4f46e5 !important; border-radius: 5px;
}
.tb-drop-zone.tb-drop-active::before {
    content: '';
    position: absolute; left: 50%; top: 50%;
    transform: translate(-50%, -50%);
    width: 14px; height: 14px;
    background: #4f46e5; border-radius: 50%;
    border: 3px solid #fff; box-shadow: 0 0 0 2px #4f46e5;
}

/* Module placeholder */
.tb-module-placeholder {
    background: linear-gradient(135deg, #f0f4ff 0%, #e8effe 100%);
    border: 2px dashed #a5b4fc; border-radius: 0.75rem;
    padding: 2.5rem 2rem; text-align: center;
    color: #6366f1; font-size: 0.95rem; font-weight: 600;
}
.tb-module-placeholder .tb-module-icon { font-size: 2rem; margin-bottom: 0.5rem; }

/* Visibility helpers */
@media (min-width: 1024px) { .tb-hide-desktop { display: none !important; } }
@media (min-width: 768px) and (max-width: 1023px) { .tb-hide-tablet { display: none !important; } }
@media (max-width: 767px) { .tb-hide-mobile { display: none !important; } }

/* Animation keyframes */
@keyframes tb-fade-in { from { opacity: 0; } to { opacity: 1; } }
@keyframes tb-slide-up { from { opacity: 0; transform: translateY(24px); } to { opacity: 1; transform: translateY(0); } }
@keyframes tb-slide-right { from { opacity: 0; transform: translateX(-24px); } to { opacity: 1; transform: translateX(0); } }
@keyframes tb-zoom-in { from { opacity: 0; transform: scale(0.92); } to { opacity: 1; transform: scale(1); } }
@keyframes tb-bounce-in { 0% { opacity:0; transform: scale(0.7); } 60% { transform: scale(1.06); } 100% { opacity:1; transform: scale(1); } }
.tb-anim-fade-in  { animation: tb-fade-in   var(--tb-anim-duration,0.6s) both; }
.tb-anim-slide-up { animation: tb-slide-up  var(--tb-anim-duration,0.6s) both; }
.tb-anim-slide-right { animation: tb-slide-right var(--tb-anim-duration,0.6s) both; }
.tb-anim-zoom-in  { animation: tb-zoom-in   var(--tb-anim-duration,0.6s) both; }
.tb-anim-bounce-in { animation: tb-bounce-in var(--tb-anim-duration,0.7s) both; }

/* Inline editing */
[data-tb-editable]:focus {
    outline: 2px dashed #6366f1 !important;
    outline-offset: 2px;
    border-radius: 3px;
    min-width: 30px;
    min-height: 1em;
}

/* ── TB4: Flex Container ── */
.tb-flex-container { width: 100%; }
.tb-fc-empty {
    display: flex; align-items: center; justify-content: center;
    min-height: 80px; border: 2px dashed #c7d2fe; border-radius: 8px;
    color: #818cf8; font-size: 0.8rem; font-weight: 500;
    background: rgba(99,102,241,0.04); width: 100%;
}
[data-tb-block-id][data-tb-container="1"].tb-selected {
    outline: 2px solid #10b981;
    box-shadow: 0 0 0 4px rgba(16,185,129,0.12);
}
[data-tb-block-id][data-tb-container="1"].tb-container-drag-over {
    outline: 2px dashed #6366f1 !important;
    background: rgba(99,102,241,0.04) !important;
}
</style>
@endpush
@push('scripts')
@if(false)

@if(empty($blocks))
<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:80vh;text-align:center;color:#9ca3af;padding:2rem;">
    <div style="font-size:3.5rem;margin-bottom:1rem;opacity:0.4;">🧩</div>
    <p style="font-size:1rem;font-weight:600;margin:0 0 0.5rem;">این صفحه هنوز بلوکی ندارد</p>
    <p style="font-size:0.85rem;margin:0;">از کتابخانه بکشید یا روی بلوک کلیک کنید</p>
</div>
@else

{{-- First drop zone (before first block) --}}
<div class="tb-drop-zone"
     data-before="{{ $blocks[0]['id'] ?? '__end__' }}"
     data-index="0"></div>

@foreach($blocks as $index => $block)
@php
    $bid       = $block['id'] ?? '';
    $nextBlock = $blocks[$index + 1] ?? null;
@endphp
{!! $renderedBlocks[$bid] ?? '' !!}
<div class="tb-drop-zone"
     data-before="{{ $nextBlock ? $nextBlock['id'] : '__end__' }}"
     data-index="{{ $index + 1 }}"></div>
@endforeach

@endif

@endif
{{-- Phase 6.1-B: one include = runtime -> identity (version-gated) -> interaction.
     Render/inline-edit surface: no structural services ([]), same bootstrap
     as the customer editor and the platform's live preview. --}}
@include('theme-runtime::canvas.editor-bootstrap', ['canvasStructuralServices' => []])
@include('theme-runtime::canvas.inspect')
<script>
(function () {
    'use strict';

    var state = { selectedBlockId: null };
    var inlineState = { active: false, el: null, propKey: null, inlineType: 'text', timer: null };

    function notifyParent(msg) {
        // Phase 3C: this preview is only ever embedded same-origin (by the
        // canvas editor, served from this same app) — targeting window.
        // location.origin instead of '*' means nothing is delivered if this
        // frame is ever loaded under an unexpected parent.
        try { ThemeCanvasRuntime.post(parent, msg.action, Object.fromEntries(Object.entries(msg).filter(([key]) => key !== 'action'))); } catch (_) {}
    }

    /* ── Inline editing (TB3-04) ─────────────────────────────── */
    function enableInlineEditing(el) {
        if (inlineState.active && inlineState.el !== el) disableInlineEditing();
        inlineState.active    = true;
        inlineState.el        = el;
        inlineState.propKey   = el.getAttribute('data-tb-editable');
        inlineState.inlineType = el.getAttribute('data-tb-inline-type') || 'text';
        el.contentEditable = 'true';
        el.focus();
        var range = document.createRange();
        range.selectNodeContents(el);
        var sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(range);
    }

    function disableInlineEditing() {
        if (!inlineState.active || !inlineState.el) return;
        var el = inlineState.el;
        el.contentEditable = 'false';
        sendInlineChange(true);
        inlineState.active = false;
        inlineState.el = null;
    }

    function sendInlineChange(isFinal) {
        if (!inlineState.el || !inlineState.propKey) return;
        var el = inlineState.el;
        var value = inlineState.inlineType === 'html' ? el.innerHTML : el.innerText;
        var blockEl = el.closest('[data-tb-block-id]');
        var blockId = blockEl ? blockEl.getAttribute('data-tb-block-id') : null;
        if (!blockId) return;
        notifyParent({ action: 'inlinePropChange', blockId: blockId, propKey: inlineState.propKey, value: value, final: !!isFinal });
    }

    document.addEventListener('dblclick', function (e) {
        var editableEl = e.target.closest('[data-tb-editable]');
        if (editableEl) {
            e.preventDefault();
            e.stopPropagation();
            enableInlineEditing(editableEl);
            return;
        }
        disableInlineEditing();
    }, true);

    document.addEventListener('blur', function (e) {
        if (inlineState.active && inlineState.el && !inlineState.el.contains(e.relatedTarget)) {
            disableInlineEditing();
        }
    }, true);

    document.addEventListener('input', function (e) {
        if (!inlineState.active || e.target !== inlineState.el) return;
        clearTimeout(inlineState.timer);
        inlineState.timer = setTimeout(function () { sendInlineChange(false); }, 400);
    }, true);

    document.addEventListener('keydown', function (e) {
        if (!inlineState.active) return;
        if (e.key === 'Escape') { disableInlineEditing(); }
        if (e.key === 'Enter' && inlineState.inlineType !== 'html') { e.preventDefault(); disableInlineEditing(); }
    }, true);

    document.addEventListener('paste', function (e) {
        if (!inlineState.active) return;
        if (inlineState.inlineType !== 'html') {
            e.preventDefault();
            var text = (e.clipboardData || window.clipboardData).getData('text/plain');
            document.execCommand('insertText', false, text);
        }
    }, true);

    /* Block click → select */
    document.addEventListener('click', function (e) {
        var blockEl = e.target.closest('[data-tb-block-id]');
        if (blockEl) {
            var logicalItem = e.target.closest('[data-tb-logical-group][data-tb-logical-index]');
            var interactive = e.target.closest('a, button, input, select, textarea, form, [role="button"]');
            if (interactive && interactive.closest('a[href]')) e.preventDefault();
            if (!interactive) e.stopPropagation();
            selectBlock(blockEl.getAttribute('data-tb-block-id'));
            notifyParent({
                action: 'blockSelected',
                blockId: state.selectedBlockId,
                logicalGroup: logicalItem ? logicalItem.getAttribute('data-tb-logical-group') : null,
                logicalIndex: logicalItem ? Number(logicalItem.getAttribute('data-tb-logical-index')) : null
            });
        }
    }, true);

    function selectBlock(blockId) {
        var prev = document.querySelector('[data-tb-block-id].tb-selected');
        if (prev) prev.classList.remove('tb-selected');
        state.selectedBlockId = blockId;
        if (blockId) {
            var el = document.querySelector('[data-tb-block-id="' + blockId + '"]');
            if (el) {
                el.classList.add('tb-selected');
                el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }
    }

    function updateBlockHtml(blockId, html) {
        disableInlineEditing();
        ThemeCanvasRuntime.replaceNode(blockId, html);
        syncColumnResizers();
    }

    function setDropTarget(beforeId) {
        document.querySelectorAll('#canvas-main > [data-tb-block-id]').forEach(function (el) {
            el.classList.toggle('tb-drop-active', el.getAttribute('data-tb-block-id') === beforeId);
        });
    }

    // TB4: container drag highlight
    var currentContainerTarget = null;
    function setContainerTarget(containerId) {
        if (currentContainerTarget) { currentContainerTarget.classList.remove('tb-container-drag-over'); currentContainerTarget = null; }
        if (containerId) {
            var el = document.querySelector('[data-tb-block-id="' + containerId + '"][data-tb-container="1"]');
            if (el) { el.classList.add('tb-container-drag-over'); currentContainerTarget = el; }
        }
    }

    function getDropIndexAtY(clientY) {
        // Only top-level blocks
        var blocks = Array.from(document.querySelectorAll('#canvas-main > [data-tb-block-id]'));
        var result = { index: blocks.length, beforeId: '__end__' };
        for (var i = 0; i < blocks.length; i++) {
            var rect = blocks[i].getBoundingClientRect();
            if (clientY < rect.top + rect.height / 2) {
                result = { index: i, beforeId: blocks[i].getAttribute('data-tb-block-id') };
                break;
            }
        }
        return result;
    }

    function getContainerAtPoint(x, y) {
        var els = document.elementsFromPoint(x, y);
        for (var i = 0; i < els.length; i++) {
            var c = els[i].closest('[data-tb-container="1"]');
            if (c) return c.getAttribute('data-tb-block-id');
        }
        return null;
    }

    function sendHeight() {
        notifyParent({ action: 'iframeHeight', height: Math.max(200, document.body.scrollHeight) });
    }

    /* ── Column resizers ─────────────────────────────────────
       The row is a CSS grid. Each handle sits on a track boundary and, while
       dragged, rewrites the row's inline grid-template-columns so the preview
       follows the pointer without a server round-trip. Releasing posts the
       final percentages to the editor, which persists them as
       props.columns[i].width. Disabled below 768px (the row stacks). */
    var COLUMN_MIN_PX = 100;
    var COLUMN_MIN_FRACTION = 0.1;
    var columnResize = null;

    function columnCells(colsEl) {
        return Array.prototype.filter.call(colsEl.children, function (el) {
            return el.nodeType === 1 && !el.matches('style, link, script, .tb-col-splitters');
        });
    }

    function columnsBlockId(colsEl) {
        var block = colsEl.closest('[data-tb-block-id]');
        return block ? block.getAttribute('data-tb-block-id') : null;
    }

    function applyColumnWidths(colsEl, widths) {
        var total = widths.reduce(function (sum, width) { return sum + width; }, 0);
        if (!total) return;
        var count = widths.length;
        colsEl.style.gridTemplateColumns = widths.map(function (width) {
            var weight = Math.round((width / total) * count * 1000) / 1000;
            return 'minmax(0,' + weight + 'fr)';
        }).join(' ');
    }

    // Inspector → Canvas: a number/slider edit applies the same live track
    // rewrite a drag does, without waiting for the debounced re-render.
    function setBlockColumnWidths(blockId, columns) {
        var colsEl = document.getElementById('tb-' + blockId);
        if (!colsEl || !colsEl.classList.contains('tb-columns')) {
            var block = document.querySelector('[data-tb-block-id="' + blockId.replace(/"/g, '\\"') + '"]');
            colsEl = block ? block.querySelector('.tb-columns') : null;
        }
        if (!colsEl) return;
        var cells = columnCells(colsEl);
        if (!cells.length) return;
        var widths = columns.map(function (column) { return Number(column && column.width); })
            .filter(function (width) { return Number.isFinite(width) && width > 0; });
        // A count mismatch means the two documents disagree; the next block
        // re-render is authoritative, so leave this frame alone.
        if (widths.length !== cells.length) return;
        applyColumnWidths(colsEl, widths);
        syncColumnResizers();
    }

    function roundedColumnPercents(widths) {
        var total = widths.reduce(function (sum, width) { return sum + width; }, 0);
        if (!total) return [];
        var percents = widths.map(function (width) {
            return Math.round((width / total) * 1000) / 10;
        });
        var drift = Math.round((100 - percents.reduce(function (sum, value) { return sum + value; }, 0)) * 10) / 10;
        percents[percents.length - 1] = Math.round((percents[percents.length - 1] + drift) * 10) / 10;
        return percents;
    }

    // Handles are re-measured from the live DOM, so they stay correct after
    // block replacement, image loading, font swap and window resizes.
    function syncColumnResizers() {
        document.querySelectorAll('.tb-columns[data-tb-resizable="1"]').forEach(function (colsEl) {
            var cells = columnCells(colsEl);
            var overlay = colsEl.querySelector(':scope > .tb-col-splitters');
            if (cells.length < 2) {
                if (overlay) overlay.remove();
                return;
            }
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.className = 'tb-col-splitters';
                overlay.setAttribute('aria-hidden', 'true');
                colsEl.appendChild(overlay);
            }
            var wanted = cells.length - 1;
            while (overlay.children.length > wanted) overlay.removeChild(overlay.lastChild);
            while (overlay.children.length < wanted) {
                var handle = document.createElement('button');
                handle.type = 'button';
                handle.className = 'tb-col-resizer';
                handle.setAttribute('role', 'separator');
                handle.setAttribute('aria-orientation', 'vertical');
                handle.setAttribute('aria-label', 'تغییر عرض ستون');
                handle.title = 'بکشید تا عرض ستون تغییر کند · دوبار کلیک برای عرض مساوی';
                overlay.appendChild(handle);
            }

            var box = colsEl.getBoundingClientRect();
            var rtl = (document.documentElement.dir || '').toLowerCase() === 'rtl';
            for (var index = 1; index < cells.length; index++) {
                var previous = cells[index - 1].getBoundingClientRect();
                var current = cells[index].getBoundingClientRect();
                var boundary = rtl
                    ? (previous.left + current.right) / 2
                    : (previous.right + current.left) / 2;
                overlay.children[index - 1].style.left = (boundary - box.left) + 'px';
            }
        });
    }

    function finishColumnResize(event, cancelled) {
        if (!columnResize || event.pointerId !== columnResize.pointerId) return;
        var active = columnResize;
        columnResize = null;
        document.body.classList.remove('tb-col-resizing');
        if (cancelled) {
            active.colsEl.style.gridTemplateColumns = '';
            syncColumnResizers();
            return;
        }
        commitColumnWidths(active.colsEl, active.widths);
    }

    function commitColumnWidths(colsEl, widths) {
        var blockId = columnsBlockId(colsEl);
        var percents = roundedColumnPercents(widths);
        if (!blockId || percents.length < 2) {
            colsEl.style.gridTemplateColumns = '';
            syncColumnResizers();
            return;
        }
        applyColumnWidths(colsEl, widths);
        syncColumnResizers();
        notifyParent({
            action: 'columnResize',
            blockId: blockId,
            columns: percents.map(function (width) { return { width: width }; })
        });
    }

    document.addEventListener('pointerdown', function (event) {
        var handle = event.target.closest('.tb-col-resizer');
        if (!handle || event.button !== 0) return;
        if (window.innerWidth <= 768) return;
        var colsEl = handle.closest('.tb-columns');
        if (!colsEl) return;
        var cells = columnCells(colsEl);
        if (cells.length < 2) return;

        event.preventDefault();
        event.stopPropagation();
        handle.setPointerCapture?.(event.pointerId);

        var rtl = (document.documentElement.dir || '').toLowerCase() === 'rtl';
        var index = Array.prototype.indexOf.call(handle.parentElement.children, handle);
        var rects = cells.map(function (cell) {
            return cell.getBoundingClientRect().width;
        });
        columnResize = {
            pointerId: event.pointerId,
            colsEl: colsEl,
            startX: event.clientX,
            rowWidth: colsEl.getBoundingClientRect().width,
            widths: rects,
            leftIndex: rtl ? index + 1 : index,
            rightIndex: rtl ? index : index + 1
        };
        document.body.classList.add('tb-col-resizing');
    }, true);

    document.addEventListener('pointermove', function (event) {
        if (!columnResize || event.pointerId !== columnResize.pointerId) return;
        event.preventDefault();
        var delta = event.clientX - columnResize.startX;
        var pair = columnResize.widths[columnResize.leftIndex] + columnResize.widths[columnResize.rightIndex];
        var minimum = Math.max(COLUMN_MIN_PX, columnResize.rowWidth * COLUMN_MIN_FRACTION);
        minimum = Math.min(minimum, pair / 2);
        var left = columnResize.widths[columnResize.leftIndex] + delta;
        var right = columnResize.widths[columnResize.rightIndex] - delta;
        left = Math.max(minimum, Math.min(pair - minimum, left));
        right = pair - left;
        var widths = columnResize.widths.slice();
        widths[columnResize.leftIndex] = left;
        widths[columnResize.rightIndex] = right;
        columnResize.liveWidths = widths;
        applyColumnWidths(columnResize.colsEl, widths);
        syncColumnResizers();
    }, true);

    document.addEventListener('pointerup', function (event) {
        if (!columnResize || event.pointerId !== columnResize.pointerId) return;
        if (columnResize.liveWidths) columnResize.widths = columnResize.liveWidths;
        finishColumnResize(event, false);
    }, true);
    document.addEventListener('pointercancel', function (event) { finishColumnResize(event, true); }, true);

    // Keyboard: arrows resize by 2% of the row; Home/Enter/Space distribute;
    // double-click also distributes. Keyboard distribution is its own committed
    // edit so Ctrl+Z returns to the layout before the shortcut.
    document.addEventListener('keydown', function (event) {
        var handle = event.target.closest && event.target.closest('.tb-col-resizer');
        if (!handle) return;
        var colsEl = handle.closest('.tb-columns');
        if (!colsEl) return;
        var cells = columnCells(colsEl);
        if (cells.length < 2) return;
        if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
            event.preventDefault();
            var rtl = (document.documentElement.dir || '').toLowerCase() === 'rtl';
            var index = Array.prototype.indexOf.call(handle.parentElement.children, handle);
            var rects = cells.map(function (cell) { return cell.getBoundingClientRect().width; });
            var pair = rects[rtl ? index + 1 : index] + rects[rtl ? index : index + 1];
            var rowRect = colsEl.getBoundingClientRect();
            var minimum = Math.min(Math.max(COLUMN_MIN_PX, rowRect.width * COLUMN_MIN_FRACTION), pair / 2);
            var minimumPercent = pair > 0 ? (minimum / pair) * 100 : 5;
            var currentIndex = rtl ? index + 1 : index;
            var currentPercent = pair > 0 ? (rects[currentIndex] / pair) * 100 : 50;
            var step = 2;
            var delta = event.key === 'ArrowRight' ? step : -step;
            if (rtl) delta = -delta;
            var newPercent = Math.max(minimumPercent, Math.min(100 - minimumPercent, currentPercent + delta));
            var widths = rects.slice();
            widths[currentIndex] = pair * (newPercent / 100);
            widths[rtl ? index : index + 1] = pair - widths[currentIndex];
            commitColumnWidths(colsEl, widths);
        } else if (event.key === 'Home' || event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            var count = cells.length;
            commitColumnWidths(colsEl, new Array(count).fill(100 / count));
        }
    }, true);

    document.addEventListener('dblclick', function (event) {
        var handle = event.target.closest && event.target.closest('.tb-col-resizer');
        if (!handle) return;
        var colsEl = handle.closest('.tb-columns');
        if (!colsEl) return;
        var count = columnCells(colsEl).length;
        if (count < 2) return;
        event.preventDefault();
        event.stopPropagation();
        commitColumnWidths(colsEl, new Array(count).fill(100 / count));
    }, true);

    /* postMessage bridge: parent → iframe */
    window.addEventListener('message', function (e) {
        // Phase 3C: only ever trust messages from this app's own origin —
        // this frame is only ever embedded same-origin by the canvas editor.
        if (!ThemeCanvasRuntime.sameOrigin(e.origin) || e.source !== parent) return;
        var d = e.data;
        if (!ThemeCanvasRuntime.valid(d, 'to-preview')) return;
        switch (d.action) {
            case 'select':
                selectBlock(d.blockId);
                break;
            case 'updateBlock':
                updateBlockHtml(d.blockId, d.html || '');
                sendHeight();
                break;
            case 'showDropZones':
                document.body.classList.toggle('tb-drag-active', !!d.visible);
                if (!d.visible) { setDropTarget(null); setContainerTarget(null); }
                break;
            case 'setDropTarget':
                setDropTarget(d.beforeId || null);
                break;
            case 'setColumnWidths':
                setBlockColumnWidths(d.blockId, Array.isArray(d.columns) ? d.columns : []);
                break;
            case 'setContainerTarget':
                setContainerTarget(d.containerId || null);
                break;
            case 'getDropIndex':
                var cY = d.clientY || 0; var cX = d.clientX || 0;
                var res = getDropIndexAtY(cY);
                var cid = getContainerAtPoint(cX, cY);
                notifyParent({ action: 'dropIndexResult', index: res.index, beforeId: res.beforeId, containerId: cid });
                break;
            case 'inlinePropChange':
                /* no-op in iframe — parent sent this; it was already handled above */
                break;
        }
    });

    notifyParent({ action: 'ready' });
    if (document.readyState === 'complete') { sendHeight(); syncColumnResizers(); }
    else window.addEventListener('load', function () { sendHeight(); syncColumnResizers(); });
    window.addEventListener('resize', syncColumnResizers);
    if (typeof ResizeObserver !== 'undefined') {
        new ResizeObserver(function () { sendHeight(); syncColumnResizers(); }).observe(document.body);
    }
}());
</script>
@endpush
{{-- This is the editor's live-preview iframe, i.e. a PREVIEW_EDITOR surface,
     even though it renders through the PUBLIC canvas layout. Pin the directive
     to noindex instead of inheriting the tenant's indexable default, so the
     preview document is never indexable. layouts/canvas-theme.blade.php reads
     $seoMeta and @include shares this scope. --}}
@php($seoMeta = ['robots' => 'noindex, nofollow'])
@include('layouts.canvas-theme')
