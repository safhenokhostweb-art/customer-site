{{-- AI Dev HUD: a client-side, read-only diagnostic bar for the Canvas
     editor. It reads the editor's own state (selected block, its parent,
     props) and the preview DOM (schema/render placeholders, Design HUD
     checks), and copies a structured AI prompt. It never sends a request.

     Gate: the site setting `enable_ai_dev_hud` switches it off entirely when
     it is set and false (nothing below is rendered). Otherwise it turns on
     with ?ai_dev=1 or Ctrl+Shift+D.

     Parts (one include per spot in canvas-editor.blade.php):
       'toolbar' the «Dev HUD» chip and the floating bar over the selected block
                 (position: fixed), plus the shared script (once);
       'health'  the diagnostics card at the top of the «سلامت ویجت» tab.
     The editor's init() calls window.TbDevHud?.attach(this, {...}). --}}
@php
    // No such setting: allowed (?ai_dev=1 / Ctrl+Shift+D still required). Set: its truth value decides.
    $aiDevFlag = \App\Models\Setting::get('enable_ai_dev_hud');
    $aiDevAllowed = $aiDevFlag === null || filter_var($aiDevFlag, FILTER_VALIDATE_BOOL);
    $aiDevPart = $part ?? 'toolbar';
@endphp
@if ($aiDevAllowed)
@once
@php
    $aiDevMethods = static function (string $class, string $prefix, bool $declaredOnly = false): array {
        $out = [];
        foreach ((new \ReflectionClass($class))->getMethods() as $method) {
            if (str_starts_with($method->getName(), $prefix) && (! $declaredOnly || $method->getDeclaringClass()->getName() === $class)) {
                $out[] = $method->getName();
            }
        }
        sort($out);

        return array_values(array_unique($out));
    };
    $aiDevRenderers = [
        'package' => $aiDevMethods(\SaaSPlatform\ThemeRuntime\BlockRenderer::class, 'render'),
        'host' => $aiDevMethods(\App\Services\ThemeBuilder\BlockRenderer::class, 'render', true),
        'module' => $aiDevMethods(\App\Services\ThemeCanvas\ThemeCanvasRenderer::class, 'renderModule'),
    ];
    // Children policy, from the same authorities the server uses: the
    // editor's container flag (insertion), the save validator's `children`
    // rule, the layout kind and the declared structural capabilities.
    $aiDevSchemas = app(\App\Services\ThemeBuilder\ElementSchemaRegistry::class);
    $aiDevTypes = [];
    foreach (\App\Services\ThemeCanvas\BlockRegistry::all() as $aiDevType => $aiDevDef) {
        $aiDevEditor = \SaaSPlatform\ThemeRuntime\Inspector\InspectorFieldRegistry::normalizeDefinition($aiDevDef)['editor'];
        $aiDevTypes[$aiDevType] = array_filter([
            'k' => \SaaSPlatform\ThemeRuntime\Layout\LayoutContract::kindForType($aiDevType),
            'c' => \App\Services\ThemeCanvas\BlockRegistry::isContainerType($aiDevType) ? 1 : 0,
            'r' => ($aiDevSchemas->getSchema($aiDevType)['children'] ?? 'none') === 'blocks' ? 1 : 0,
            'ck' => $aiDevEditor['allowedChildKinds'],
            'pk' => $aiDevEditor['allowedParentKinds'],
            'm' => $aiDevEditor['maxChildren'],
        ], static fn ($v) => $v !== null);
    }
    $aiDevPolicy = ['types' => $aiDevTypes, 'maxDepth' => \SaaSPlatform\ThemeRuntime\Layout\DocumentValidator::MAX_DEPTH];
    // Social network names as the site shows them (bale → بله).
    $aiDevNetworks = [];
    foreach (\SaaSPlatform\ThemeRuntime\Support\SocialNetworks::keys() as $aiDevNetwork) {
        $aiDevNetworks[$aiDevNetwork] = \SaaSPlatform\ThemeRuntime\Support\SocialNetworks::label($aiDevNetwork);
    }
@endphp
<style>
.tbdh-bar { position: fixed; z-index: 60; display: flex; align-items: center; gap: .375rem; max-width: min(34rem, calc(100vw - 1rem)); padding: .3rem .375rem; border: 1px solid #c7d2fe; border-radius: .625rem; background: #fff; box-shadow: 0 8px 24px rgb(15 23 42 / 16%); font-size: .75rem; color: #1e293b; }
.tbdh-dot { flex: none; width: .5rem; height: .5rem; border-radius: 999px; background: #10b981; }
.tbdh-dot[data-tone="warn"] { background: #f59e0b; }
.tbdh-dot[data-tone="error"] { background: #f43f5e; }
.tbdh-type { flex: none; max-width: 9rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .6875rem; font-weight: 700; }
.tbdh-state { flex: none; font-weight: 600; color: #047857; }
.tbdh-state[data-tone="warn"] { color: #b45309; }
.tbdh-state[data-tone="error"] { color: #be123c; }
.tbdh-input { flex: 1 1 10rem; min-width: 6rem; height: 2rem; padding: 0 .5rem; border: 1px solid #e2e8f0; border-radius: .5rem; font: inherit; color: inherit; }
.tbdh-input:focus { outline: 2px solid #6366f1; outline-offset: 0; border-color: transparent; }
.tbdh-copy { flex: none; height: 2rem; padding: 0 .625rem; border: 0; border-radius: .5rem; background: #4f46e5; color: #fff; font: inherit; font-weight: 600; cursor: pointer; white-space: nowrap; }
.tbdh-copy:hover { background: #4338ca; }
.tbdh-copy:focus-visible, .tbdh-close:focus-visible, .tbdh-chip:focus-visible { outline: 2px solid #6366f1; outline-offset: 1px; }
.tbdh-close { flex: none; width: 1.75rem; height: 2rem; border: 0; background: none; color: #94a3b8; font-size: 1rem; line-height: 1; cursor: pointer; }
.tbdh-close:hover { color: #475569; }
.tbdh-file { display: block; }
.tbdh-chip { flex: none; display: inline-flex; align-items: center; gap: .3rem; height: 1.75rem; padding: 0 .4rem; border: 1px solid #c7d2fe; border-radius: .5rem; background: #eef2ff; color: #4338ca; font-size: .75rem; font-weight: 600; cursor: pointer; white-space: nowrap; }
.tbdh-card { margin: .75rem .75rem 0; padding: .625rem .75rem; border: 1px solid #c7d2fe; border-radius: .625rem; background: #f8faff; font-size: .75rem; color: #334155; }
.tbdh-card h3 { display: flex; align-items: center; gap: .375rem; margin: 0 0 .5rem; font-size: .75rem; font-weight: 700; color: #3730a3; }
.tbdh-card dl { display: grid; grid-template-columns: auto minmax(0, 1fr); gap: .25rem .5rem; margin: 0; }
.tbdh-card dt { color: #64748b; }
.tbdh-card dd { margin: 0; min-width: 0; overflow-wrap: anywhere; }
.tbdh-card code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .6875rem; }
.tbdh-card ul { margin: .5rem 0 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: .25rem; }
.tbdh-card li { padding: .25rem .375rem; border-radius: .375rem; background: #fff1f2; color: #9f1239; overflow-wrap: anywhere; }
.tbdh-card li[data-kind="warning"] { background: #fffbeb; color: #92400e; }
.tbdh-card .tbdh-copy { width: 100%; margin-top: .5rem; }
.tbdh-muted { margin: 0; color: #64748b; }
.tbdh-badge { flex: none; padding: .125rem .375rem; border-radius: .375rem; background: #fff1f2; color: #be123c; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .625rem; font-weight: 700; }
.tbdh-slots { margin: .5rem 0 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: .25rem; }
.tbdh-slots li { display: flex; flex-direction: column; gap: .125rem; padding: .3rem .4rem; border-radius: .375rem; background: #f0fdf4; color: #334155; }
.tbdh-slots li[data-tone="error"] { background: #fff1f2; }
.tbdh-slot-head { display: flex; align-items: baseline; gap: .375rem; min-width: 0; overflow-wrap: anywhere; }
.tbdh-slot-error { color: #9f1239; overflow-wrap: anywhere; }
.tbdh-ok { color: #047857; font-weight: 600; }
.tbdh-group { margin: .625rem 0 0; font-size: .6875rem; font-weight: 700; color: #475569; }
.tbdh-card li[data-kind="content"] { display: flex; flex-direction: column; align-items: flex-start; gap: .25rem; background: #fffbeb; color: #92400e; }
.tbdh-path { font-size: .625rem; color: #78350f; }
.tbdh-fix { flex: none; height: 1.75rem; padding: 0 .5rem; border: 1px solid #f59e0b; border-radius: .5rem; background: #fffbeb; color: #92400e; font: inherit; font-size: .6875rem; font-weight: 700; cursor: pointer; white-space: nowrap; }
.tbdh-fix:hover { background: #fef3c7; }
.tbdh-fix:focus-visible { outline: 2px solid #f59e0b; outline-offset: 1px; }
/* The Inspector field a deep-link landed on. */
.tbdh-target { outline: 2px solid #f59e0b !important; outline-offset: 2px; box-shadow: 0 0 0 6px rgb(245 158 11 / 25%) !important; transition: box-shadow .3s; }
@media (prefers-reduced-motion: reduce) { .tbdh-target { transition: none; } }
/* The toolbar has no room below 1366 px (the page-tab strip already overlaps there when unsaved): the bar and the health card show the state. */
@media (max-width: 1365.98px) { .tbdh-chip { display: none; } }
@media (prefers-reduced-motion: reduce) { .tbdh-copy { transition: none; } }
</style>
<script>
(function (root) {
    'use strict';
    if (root.TbDevHud) return;

    var RENDERERS = @json($aiDevRenderers);
    // type → { k: layout kind, c: editor container, r: save validator checks children,
    //          ck/pk: allowed child/parent kinds, m: maxChildren }; maxDepth from DocumentValidator.
    var POLICY = @json($aiDevPolicy);
    var NETWORKS = @json($aiDevNetworks);
    var FILES = {
        packageRenderer: 'packages/saas-platform/modules/theme-runtime/src/BlockRenderer.php',
        packageRegistry: 'packages/saas-platform/modules/theme-runtime/src/BlockRegistry.php',
        css: 'packages/saas-platform/modules/theme-runtime/resources/css/theme-runtime-blocks.css',
        hostRenderer: 'app/Services/ThemeBuilder/BlockRenderer.php',
        hostRegistry: 'app/Services/ThemeCanvas/BlockRegistry.php',
        moduleRenderer: 'app/Services/ThemeCanvas/ThemeCanvasRenderer.php',
    };
    var ZONES = { __header__: 'header', __footer__: 'footer' };

    /* ── Pure helpers (exercised by tests/browser/canvas-ai-dev-hud.spec.js) ── */

    function isShortcut(e) {
        return !!e && !!(e.ctrlKey || e.metaKey) && !!e.shiftKey && !e.altKey && (e.code === 'KeyD' || e.key === 'd' || e.key === 'D');
    }

    function enabledByUrl(search) {
        try { return new URLSearchParams(search || '').get('ai_dev') === '1'; } catch (_) { return false; }
    }

    function studly(s) {
        return String(s).split(/[_\-\s:]+/).filter(Boolean).map(function (p) { return p.charAt(0).toUpperCase() + p.slice(1); }).join('');
    }

    /** The files (and method) that render a type, from the renderer method lists the server sent. */
    function viewHint(type, renderers) {
        renderers = renderers || RENDERERS;
        type = String(type || '');
        if (type.indexOf('module:') === 0) {
            var m = 'renderModule' + studly(type.slice(7));
            return [FILES.moduleRenderer + ' → ' + (renderers.module.indexOf(m) !== -1 ? m : 'renderModuleBlock') + '()', FILES.hostRegistry];
        }
        if (type.indexOf('ext:') === 0) return ['Extension SDK element (' + type + ')', FILES.hostRegistry];
        var method = 'render' + studly(type);
        var out = [];
        if (renderers.host.indexOf(method) !== -1) out.push(FILES.hostRenderer + ' → ' + method + '()');
        out.push(FILES.packageRenderer + ' → ' + (renderers.package.indexOf(method) !== -1 ? method : 'renderFallback') + '()');
        out.push(FILES.packageRegistry, FILES.css);
        return out;
    }

    /** The block's own schema/render placeholder in the preview → error lines. */
    function placeholderErrors(doc, id) {
        if (!doc || !id) return [];
        var el = doc.getElementById('tb-' + id);
        if (!el || !el.classList.contains('tb-placeholder')) return [];
        var reason = el.getAttribute('data-reason') || 'render_error';
        var code = el.querySelector('.tb-placeholder-detail code');
        var detail = code ? code.textContent.trim() : '';
        var parts = detail ? detail.split(' · ') : [reason];
        return parts.map(function (p) { return { kind: reason === 'invalid_schema' ? 'schema' : 'render', message: p.trim() }; });
    }

    function childrenOf(block) {
        var c = block ? (block.children !== undefined ? block.children : block.blocks) : undefined;
        return c === undefined || c === null ? [] : c;
    }

    function subtreeDepth(node) {
        var max = 0;
        var children = childrenOf(node);
        if (Array.isArray(children)) children.forEach(function (c) { if (c && typeof c === 'object') max = Math.max(max, 1 + subtreeDepth(c)); });
        return max;
    }

    /** The server's own "[child N] …" messages → { slots: {N: [msg]}, rest: [issue] } (first level; deeper ones keep their prefix). */
    function splitSlotErrors(issues) {
        var slots = {}, rest = [];
        (issues || []).forEach(function (i) {
            var m = /^\[child (\d+)\]\s*(.*)$/.exec(i.message);
            if (m && i.kind === 'schema') (slots[m[1]] = slots[m[1]] || []).push(m[2]);
            else rest.push(i);
        });
        return { slots: slots, rest: rest };
    }

    /**
     * Children policy for one block, the server's rules on the client:
     * only containers take children (a leaf's children are silently never
     * rendered), every child is a known block with a unique id, declared
     * allowedChildKinds / allowedParentKinds / maxChildren hold, nesting stays
     * within maxDepth; plus the save validator's own per-slot errors.
     * depth = the block's own depth (top-level block = 1).
     */
    function childrenPolicy(block, depth, serverSlots, policy) {
        policy = policy || POLICY;
        serverSlots = serverSlots || {};
        var def = policy.types[block.type] || null;
        var kind = def ? def.k : 'widget';
        var res = { type: block.type, id: block.id || '', kind: kind, accepts: !!(def && def.c), validated: !!(def && def.r), max: def && def.m ? def.m : null, slots: [], issues: [] };
        var children = childrenOf(block);
        var where = '«' + block.type + '»';
        if (!Array.isArray(children)) {
            res.issues.push({ kind: 'slot-error', message: where + ': children must be a list of blocks' });
            return res;
        }
        var seen = {};
        children.forEach(function (c, i) {
            var errs = [];
            var isBlock = c && typeof c === 'object' && typeof c.type === 'string' && c.type !== '';
            if (!isBlock) {
                errs.push(['slot-error', 'not a block (no type)']);
            } else {
                var cd = policy.types[c.type];
                if (!cd) errs.push(['slot-error', 'unknown block type «' + c.type + '»']);
                if (!res.accepts) {
                    errs.push(['children-policy', where + ' accepts no children (kind ' + kind + '): this child is never rendered']);
                } else {
                    var ck = cd ? cd.k : 'widget';
                    if (def.ck && def.ck.indexOf(ck) === -1) errs.push(['children-policy', where + ' accepts kinds ' + def.ck.join('/') + ', not «' + ck + '»']);
                    if (cd && cd.pk && cd.pk.indexOf(kind) === -1) errs.push(['children-policy', '«' + c.type + '» belongs in kinds ' + cd.pk.join('/') + ', not «' + kind + '»']);
                    if (def.m && i >= def.m) errs.push(['children-policy', 'beyond maxChildren (' + def.m + ')']);
                }
                if (!c.id) errs.push(['slot-error', 'no id']);
                else if (seen[c.id] !== undefined) errs.push(['slot-error', 'duplicate id «' + c.id + '» (slot ' + seen[c.id] + ')']);
                else seen[c.id] = i;
                if (depth + 1 + subtreeDepth(c) > policy.maxDepth) errs.push(['children-policy', 'nesting deeper than ' + policy.maxDepth + ' levels']);
            }
            // The server's message is from the last render: keep it only while
            // the block type it names is still the one in this slot.
            (serverSlots[i] || []).forEach(function (m) {
                var named = /^Block "([^"]+)"/.exec(m) || /^Unknown element type: (\S+?)\.?$/.exec(m);
                if (!named || (isBlock && named[1] === c.type)) errs.push(['slot-error', m]);
            });
            var slot = { index: i, type: isBlock ? c.type : '?', id: isBlock && c.id ? c.id : '', errors: errs.map(function (e) { return { kind: e[0], message: e[1] }; }) };
            res.slots.push(slot);
            slot.errors.forEach(function (e) {
                res.issues.push({ kind: e.kind, message: 'slot ' + i + ' ' + slot.type + (slot.id ? '#' + slot.id : '') + ': ' + e.message });
            });
        });
        if (res.accepts && !res.validated && children.length) {
            res.issues.push({ kind: 'warning', message: 'children-policy: the save validator does not check the children of ' + where + ' (schema children: none)' });
        }
        return res;
    }

    function childrenLines(ch) {
        var yes = function (b) { return b ? 'بله' : 'خیر'; };
        var lines = ['  * والد: `' + ch.type + ' (ID: ' + ch.id + ')` | kind: `' + ch.kind + '` | پذیرش فرزند: ' + yes(ch.accepts)
            + ' | اعتبارسنجی فرزندان هنگام ذخیره: ' + yes(ch.validated) + ' | سقف فرزندان: ' + (ch.max || 'نامحدود')];
        if (!ch.slots.length) lines.push('  * ' + (ch.accepts ? 'بدون فرزند' : 'این المان فرزند نمی‌پذیرد'));
        ch.slots.forEach(function (s) {
            lines.push('  * [' + s.index + '] `' + s.type + '` (ID: ' + (s.id || '—') + '): '
                + (s.errors.length ? s.errors.map(function (e) { return '`[' + e.kind + '] ' + e.message + '`'; }).join('، ') : 'سالم'));
        });
        return lines;
    }

    /* ── Content issues: what the site's admin fixes in the Inspector, not in code ── */

    // Primary copy fields: emptied after the widget shipped a default, the widget shows a hole.
    var PRIMARY_TEXT = ['text', 'title', 'heading', 'label', 'button_text'];

    var PRIMARY_LINK = ['url', 'href', 'link'];

    /** cta_url → cta_text / cta_title / cta_label set: the link is meant to be used. */
    function hasCompanionText(bag, linkKey) {
        var base = linkKey.replace(/_(url|link|href)$/, '');
        if (base === linkKey) return false;
        return ['_text', '_title', '_label'].some(function (s) { var v = bag[base + s]; return typeof v === 'string' && v.trim() !== ''; });
    }

    function linkValue(v) { return (v && typeof v === 'object' && !Array.isArray(v)) ? String(v.url || '') : String(v == null ? '' : v); }
    function linkMissing(v) { var s = linkValue(v).trim(); return s === '' || s === '#'; }
    function fieldPath(prop, index, key) { return index === null ? prop : prop + '[' + index + '].' + key; }

    /** A visible repeater item's own name: its network, else its first non-empty text sub-field. */
    function itemName(item, fields) {
        if (item.network && item.network !== 'auto') return NETWORKS[item.network] || String(item.network);
        for (var k in fields) {
            if (fields[k].type !== 'link' && typeof item[k] === 'string' && item[k].trim() !== '') return item[k].trim();
        }
        return '';
    }

    /**
     * Content issues of one block from its props and schema alone: links an
     * author left empty or "#", primary texts emptied, plus the preview's dead
     * links (anchors with href="#") mapped back to the field that holds them.
     * Each issue carries field = { path, prop, index, key, label } for the deep-link.
     * deadLinks = [{ text, network }] read from the preview.
     */
    function contentIssues(block, schema, deadLinks) {
        var props = (block && block.props) || {};
        var out = [], byPath = {};
        function add(code, message, prop, index, key, label, seen) {
            var path = fieldPath(prop, index, key);
            if (byPath[path]) { byPath[path].seenInPreview = byPath[path].seenInPreview || seen; return byPath[path]; }
            var issue = { kind: 'content', code: code, message: message, seenInPreview: !!seen, field: { path: path, prop: prop, index: index, key: index === null ? null : key, label: label } };
            byPath[path] = issue; out.push(issue);
            return issue;
        }
        var repeaters = [], links = [];
        Object.keys(schema || {}).forEach(function (prop) {
            var def = schema[prop] || {};
            if (def.type === 'repeater' && def.fields) {
                var lk = Object.keys(def.fields).filter(function (k) { return def.fields[k].type === 'link'; });
                if (lk.length) repeaters.push({ prop: prop, def: def, linkKeys: lk });
            } else if (def.type === 'link') {
                links.push({ prop: prop, def: def });
            } else if ((def.type === 'string' || def.type === 'textarea') && PRIMARY_TEXT.indexOf(prop) !== -1
                && typeof def.default === 'string' && def.default.trim() !== '' && typeof props[prop] === 'string' && props[prop].trim() === '') {
                add('EMPTY_TEXT', 'متن «' + (def.label || prop) + '» خالی است.', prop, null, null, def.label || prop, false);
            }
        });
        // Links in repeater items that are visible (named) but go nowhere: the
        // item's primary link (url/href/link), or another link whose own
        // companion text is set (mega_banner_url ↔ mega_banner_title); an
        // optional link of an unused feature is not a gap.
        repeaters.forEach(function (r) {
            var items = Array.isArray(props[r.prop]) ? props[r.prop] : [];
            items.forEach(function (item, i) {
                if (!item || typeof item !== 'object') return;
                var name = itemName(item, r.def.fields);
                r.linkKeys.forEach(function (k) {
                    if (PRIMARY_LINK.indexOf(k) === -1 && !hasCompanionText(item, k)) return;
                    if (name && linkMissing(item[k])) {
                        add('DEAD_LINK', 'لینک «' + name + '» مقصد ندارد' + (linkValue(item[k]).trim() === '' ? ' و در سایت نمایش داده نمی‌شود.' : '.'),
                            r.prop, i, k, (r.def.fields[k].label || k) + ' — ' + name, false);
                    }
                });
            });
        });
        // Top-level links whose companion text is set (cta_text → cta_url).
        links.forEach(function (l) {
            var text = props[l.prop.replace(/_(url|link|href)$/, '_text')];
            if (text !== undefined && String(text).trim() !== '' && linkMissing(props[l.prop])) {
                add('DEAD_LINK', 'لینک «' + String(text).trim() + '» مقصد ندارد.', l.prop, null, null, l.def.label || l.prop, false);
            }
        });
        // The preview's dead anchors: mark the matching issue as seen, or map a new one.
        (deadLinks || []).forEach(function (a) {
            var hit = null;
            repeaters.some(function (r) {
                var items = Array.isArray(props[r.prop]) ? props[r.prop] : [];
                return items.some(function (item, i) {
                    if (!item || typeof item !== 'object') return false;
                    return r.linkKeys.some(function (k) {
                        if (!linkMissing(item[k])) return false;
                        var textHit = Object.keys(r.def.fields).some(function (f) { return r.def.fields[f].type !== 'link' && typeof item[f] === 'string' && item[f].trim() !== '' && item[f].trim() === a.text; });
                        if ((a.network && item.network === a.network) || textHit) {
                            hit = add('DEAD_LINK', 'لینک «' + (itemName(item, r.def.fields) || a.text || a.network) + '» مقصد ندارد.', r.prop, i, k, (r.def.fields[k].label || k) + ' — ' + (itemName(item, r.def.fields) || a.text), true);
                            return true;
                        }
                        return false;
                    });
                });
            });
            if (hit) return;
            links.some(function (l) {
                var text = props[l.prop.replace(/_(url|link|href)$/, '_text')];
                if (linkMissing(props[l.prop]) && text !== undefined && String(text).trim() === a.text) {
                    hit = add('DEAD_LINK', 'لینک «' + a.text + '» مقصد ندارد.', l.prop, null, null, l.def.label || l.prop, true);
                    return true;
                }
                return false;
            });
            if (hit) return;
            // The anchor belongs to a field that now has a link: the preview is
            // older than the props (it re-renders on the next reload) — not a gap.
            var known = repeaters.some(function (r) {
                return (Array.isArray(props[r.prop]) ? props[r.prop] : []).some(function (item) {
                    if (!item || typeof item !== 'object') return false;
                    if (a.network && item.network === a.network) return true;
                    return Object.keys(r.def.fields).some(function (f) { return r.def.fields[f].type !== 'link' && typeof item[f] === 'string' && item[f].trim() !== '' && item[f].trim() === a.text; });
                });
            }) || links.some(function (l) { var t = props[l.prop.replace(/_(url|link|href)$/, '_text')]; return t !== undefined && String(t).trim() === a.text; });
            if (!known) out.push({ kind: 'content', code: 'DEAD_LINK', message: 'لینک «' + (a.text || NETWORKS[a.network] || a.network || '؟') + '» مقصد ندارد (فیلدش پیدا نشد).', seenInPreview: true, field: null });
        });
        return out;
    }

    /** The preview's anchors to "#" inside a widget (the Design HUD's DEAD_LINK), as plain data. */
    function deadLinksIn(el) {
        if (!el) return [];
        return Array.prototype.slice.call(el.querySelectorAll('a[href="#"]')).filter(function (a) {
            var r = a.getBoundingClientRect();
            return r.width > 0 && r.height > 0;
        }).map(function (a) {
            var m = /\btb-social-icon--([a-z0-9_-]+)\b/.exec(a.className || '');
            return { text: (a.textContent || a.getAttribute('aria-label') || '').trim(), network: m && m[1] !== 'text' ? m[1] : '' };
        });
    }

    /** A server message "required prop "x" is missing" is the admin's to fill, not code. */
    function requiredPropIssue(issue, schema) {
        var m = /required prop "([^"]+)" is missing/.exec(issue.message || '');
        if (!m) return null;
        var def = (schema || {})[m[1]] || {};
        return { kind: 'content', code: 'REQUIRED_EMPTY', message: 'فیلد الزامی «' + (def.label || m[1]) + '» خالی است.', seenInPreview: true, field: { path: m[1], prop: m[1], index: null, key: null, label: def.label || m[1] } };
    }

    function buildPrompt(meta, instruction, issues, children) {
        var content = (issues || []).filter(function (i) { return i.kind === 'content'; });
        var core = (issues || []).filter(function (i) { return i.kind !== 'content'; });
        var lines = [
            '### دستور عیب‌یابی / توسعه ویجت کانواس (Dev HUD)',
            '',
            '- **دستور توسعه‌دهنده:** ' + (String(instruction || '').trim() || '(بدون دستور؛ وضعیت این المان را بررسی و ایرادهایش را رفع کن)'),
            '- **مشخصات فنی المان:**',
            '  * نوع المان (Type): `' + meta.type + '`',
            '  * شناسه (ID/UUID): `' + meta.id + '`',
            '  * والد (Parent): `' + meta.parent.type + ' (ID: ' + meta.parent.id + ')`',
            '  * مسیر احتمالی فایل‌ها: `' + meta.view.join(' | ') + '`',
            '- **نواقص محتوایی قابل تنظیم توسط مدیر:**',
        ];
        if (content.length) {
            lines.push('  > این‌ها مقدار محتوایی‌اند و مدیر سایت آن‌ها را در پنل ادیتور پر می‌کند. برایشان سورس PHP/CSS را تغییر نده، مگر اینکه اسکیما یا بایندینگ همان فیلد خراب باشد.');
            content.forEach(function (i) {
                lines.push('  * `' + i.code + '`' + (i.field ? ' — فیلد `' + i.field.path + '`' : '') + ': ' + i.message);
            });
        } else {
            lines.push('  * موردی نیست.');
        }
        lines.push('- **خطاهای ثبت‌شده در ادیتور:**');
        if (core.length) {
            core.forEach(function (i) { lines.push('  `[' + i.kind + '] ' + i.message + '`'); });
        } else {
            lines.push('  `هیچ خطایی ثبت نشده است.`');
        }
        if (children) {
            lines.push('- **ساختار فرزندان (children_structure):**');
            lines = lines.concat(childrenLines(children));
        }
        lines.push('- **تنظیمات و ویژگی‌های فعلی (Current Props):**', '  ```json');
        JSON.stringify(meta.props || {}, null, 2).split('\n').forEach(function (l) { lines.push('  ' + l); });
        lines.push('  ```', '- **اقدام مورد نیاز:** لطفاً بر اساس داده‌های ساختاری فوق، فایل‌های مربوطه را بررسی کرده و تغییرات لازم را در کد پیاده کن.');
        return lines.join('\n');
    }

    function fallbackCopy(text) {
        var ta = document.createElement('textarea');
        ta.value = text; ta.setAttribute('readonly', ''); ta.style.cssText = 'position:fixed;top:-1000px;opacity:0';
        document.body.appendChild(ta); ta.select();
        var ok = false;
        try { ok = document.execCommand('copy'); } catch (_) { ok = false; }
        ta.remove();
        return ok;
    }

    function copy(text) {
        if (root.TbInspect && typeof root.TbInspect.copy === 'function') return Promise.resolve(root.TbInspect.copy(text));
        if (root.navigator.clipboard && root.isSecureContext) return root.navigator.clipboard.writeText(text).then(function () { return true; }, function () { return fallbackCopy(text); });
        return Promise.resolve(fallbackCopy(text));
    }

    /* ── Shared state (Alpine store) ── */

    var store = {
        active: false,
        instruction: '',
        domIssues: [],
        domContent: [],
        deadLinks: [],
        serverSlots: {},
        parentServerSlots: {},
        pos: null,
        editor: null,

        /** The selected block's schema (the editor's BLOCK_SCHEMAS). */
        get schema() {
            var b = this.editor && this.editor.selectedBlock;
            var all = typeof BLOCK_SCHEMAS !== 'undefined' ? BLOCK_SCHEMAS : {};
            return (b && all[b.type] && all[b.type].schema) || {};
        },
        /** User Content Action: live from the props, plus the preview's dead links and the server's "required" messages. */
        get contentIssues() {
            var b = this.editor && this.editor.selectedBlock;
            if (!b) return [];
            var out = contentIssues(b, this.schema, this.deadLinks);
            var seen = {};
            out.forEach(function (i) { if (i.field) seen[i.field.path] = true; });
            this.domContent.forEach(function (i) { if (!i.field || !seen[i.field.path]) out.push(i); });
            return out;
        },
        /** Core Code Issue: render, schema, structure, layout. */
        get coreIssues() { return this.issues.filter(function (i) { return i.kind !== 'content'; }); },
        get firstFixable() { return this.contentIssues.find(function (i) { return !!i.field; }) || null; },

        get path() { return (this.editor && this.editor.selectedPath) || []; },
        get parentBlock() {
            var e = this.editor, path = this.path;
            return path.length > 1 && e && typeof e.getBlockByPath === 'function' ? e.getBlockByPath(path.slice(0, -1)) : null;
        },
        get meta() {
            var e = this.editor;
            var b = e && e.selectedBlock;
            if (!b) return null;
            var parent = this.parentBlock;
            return {
                type: b.type,
                id: b.id,
                props: b.props || {},
                parent: parent ? { type: parent.type, id: parent.id } : { type: ZONES[e.currentPage] || 'page', id: e.currentPage },
                view: viewHint(b.type),
            };
        },
        /** Children Integrity of the selected block (live from the editor state). */
        get children() {
            var b = this.editor && this.editor.selectedBlock;
            return b ? childrenPolicy(b, this.path.length, this.serverSlots) : null;
        },
        /** The selected block's own slot, judged by its parent's policy. */
        get ownSlot() {
            var p = this.parentBlock;
            if (!p) return null;
            return childrenPolicy(p, this.path.length - 1, this.parentServerSlots).slots[this.path[this.path.length - 1]] || null;
        },
        get issues() {
            var out = this.domIssues.slice();
            var own = this.ownSlot, parent = this.parentBlock;
            if (own) own.errors.forEach(function (e) { out.push({ kind: e.kind, message: 'in parent ' + parent.type + '#' + parent.id + ' slot ' + own.index + ': ' + e.message }); });
            if (this.children) out = out.concat(this.children.issues);
            return out.concat(this.contentIssues);
        },
        get policyBadge() {
            var kinds = this.issues.map(function (i) { return i.kind; });
            if (kinds.indexOf('slot-error') !== -1) return '[slot-error]';
            return kinds.indexOf('children-policy') !== -1 ? '[children-policy]' : '';
        },
        get tone() {
            // A content gap is amber: the admin fills it; red is reserved for code.
            if (this.issues.some(function (i) { return i.kind !== 'warning' && i.kind !== 'content'; })) return 'error';
            return this.issues.length ? 'warn' : 'ok';
        },
        get toneLabel() {
            var kinds = this.issues.map(function (i) { return i.kind; });
            if (kinds.indexOf('schema') !== -1) return 'خطای اسکیما';
            if (kinds.indexOf('slot-error') !== -1 || kinds.indexOf('children-policy') !== -1) return 'خطای ساختار فرزندان';
            if (this.tone === 'warn' && kinds.every(function (k) { return k === 'content'; })) return 'نقص محتوایی';
            return { error: 'خطای رندر', warn: 'هشدار', ok: 'معتبر' }[this.tone];
        },

        /**
         * Deep-link: open the block's Inspector on the field that holds the
         * gap and focus it (a repeater item is selected and highlighted first).
         */
        focusField(field) {
            var e = this.editor, meta = this.meta;
            if (!e || !meta || !field) return;
            if (!e.selectedBlock || e.selectedBlock.id !== meta.id) e.selectBlock(e.findBlockPath(meta.id));
            e.selectedTab = 'content';
            e.selectedLogicalItem = field.index === null ? null : { group: field.prop, index: field.index };
            var selector = field.index === null
                ? '[data-testid="tb-prop-' + field.prop + '"]'
                : '#' + (root.CSS && CSS.escape ? CSS.escape('tb-structured-' + field.prop + '-' + field.index + '-' + field.key) : 'tb-structured-' + field.prop + '-' + field.index + '-' + field.key);
            var started = Date.now();
            (function seek() {
                var el = document.querySelector(selector);
                if (!el && Date.now() - started < 1500) { root.requestAnimationFrame(seek); return; }
                if (!el) { if (e.showToast) e.showToast.call(e, 'فیلد «' + field.label + '» در پنل پیدا نشد', 'error'); return; }
                el.scrollIntoView({ block: 'center', behavior: 'smooth' });
                el.focus({ preventScroll: true });
                if (typeof el.select === 'function') el.select();
                el.classList.add('tbdh-target');
                root.setTimeout(function () { el.classList.remove('tbdh-target'); }, 2000);
            }());
        },

        toggle(on) {
            this.active = on === undefined ? !this.active : !!on;
            if (this.active) this.refresh();
        },

        frame() { return this.editor && this.editor.__devHudFrame ? this.editor.__devHudFrame() : null; },
        frameDoc() { try { var f = this.frame(); return f && f.contentDocument; } catch (_) { return null; } },

        /** The block's element in the preview, else its nearest rendered ancestor (an invalid child renders inside its parent's placeholder). */
        elementFor(doc) {
            var e = this.editor, path = this.path;
            var esc = function (id) { return root.CSS && CSS.escape ? CSS.escape(id) : id; };
            for (var n = path.length; n >= 1; n--) {
                var b = e.getBlockByPath ? e.getBlockByPath(path.slice(0, n)) : (n === path.length ? e.selectedBlock : null);
                if (!b || !b.id) continue;
                var el = doc.querySelector('[data-tb-block-id="' + esc(b.id) + '"][data-widget-type]') || doc.getElementById('tb-' + b.id);
                if (el) return { el: el, own: n === path.length };
            }
            return { el: null, own: false };
        },

        refresh() {
            var meta = this.active ? this.meta : null;
            var doc = this.frameDoc();
            if (!meta || !doc) { this.domIssues = []; this.domContent = []; this.deadLinks = []; this.serverSlots = {}; this.parentServerSlots = {}; this.pos = null; return; }
            var split = splitSlotErrors(placeholderErrors(doc, meta.id));
            var parent = this.parentBlock;
            var schema = this.schema;
            this.serverSlots = split.slots;
            this.parentServerSlots = parent ? splitSlotErrors(placeholderErrors(doc, parent.id)).slots : {};
            var issues = [], content = [];
            split.rest.forEach(function (i) { var c = requiredPropIssue(i, schema); if (c) content.push(c); else issues.push(i); });
            var found = this.elementFor(doc);
            var el = found.own ? found.el : null;
            var audit = this.frame().contentWindow.TbAudit;
            if (el && audit && typeof audit.auditNow === 'function') {
                try {
                    audit.auditNow(el).filter(function (f) { return f.severity !== 'info' && f.code !== 'DEAD_LINK'; }).forEach(function (f) {
                        issues.push({ kind: f.severity === 'blocker' ? 'render' : 'warning', message: f.code + ': ' + f.message });
                    });
                } catch (_) { /* the Design HUD is optional */ }
            }
            this.domIssues = issues;
            this.domContent = content;
            this.deadLinks = deadLinksIn(el); // DEAD_LINK is content: mapped to its field in contentIssues
            this.place(found.el);
        },

        /** Above the selected block, kept inside the preview frame; docked to its top edge when the block is off screen. */
        place(el) {
            var f = this.frame();
            if (!f || !el) { this.pos = null; return; }
            var fr = f.getBoundingClientRect();
            var r = el.getBoundingClientRect();
            var barH = 40, gap = 6, width = Math.min(544, fr.width - 16);
            var visible = r.bottom > 0 && r.top < fr.height;
            var top = visible ? fr.top + r.top - barH - gap : fr.top + gap;
            if (visible && r.top < barH + gap) top = fr.top + Math.max(r.top, 0) + gap;
            var right = visible ? Math.min(r.right, fr.width) : fr.width - 8;
            var left = Math.max(fr.left + 8, fr.left + right - width);
            this.pos = { top: Math.round(Math.max(top, fr.top + gap)), left: Math.round(left), width: Math.round(width) };
        },

        copyPrompt() {
            var meta = this.meta;
            if (!meta) return;
            var self = this;
            this.refresh();
            copy(buildPrompt(meta, this.instruction, this.issues, this.children)).then(function (ok) {
                var toast = self.editor && self.editor.showToast;
                if (toast) toast.call(self.editor, ok ? 'کپی شد ✓' : 'کپی نشد؛ مرورگر اجازه دسترسی به کلیپ‌بورد نداد', ok ? 'success' : 'error');
            });
        },
    };

    document.addEventListener('alpine:init', function () { root.Alpine.store('devHud', store); });

    /* ── Editor wiring (called once from the editor's init()) ── */

    var attached = false;
    function attach(editor, options) {
        if (attached) return root.Alpine.store('devHud');
        attached = true;
        var s = root.Alpine.store('devHud');
        s.editor = editor;
        editor.__devHudFrame = options && options.frame ? options.frame : function () { return null; };
        var schedule = (function () {
            var t = 0;
            return function () { root.clearTimeout(t); t = root.setTimeout(function () { if (s.active) s.refresh(); }, 60); };
        }());
        function onKey(e) { if (isShortcut(e)) { e.preventDefault(); s.toggle(); } }
        root.addEventListener('keydown', onKey);
        root.addEventListener('resize', schedule, { passive: true });
        // The preview: its own shortcut, scroll and re-render events. Iframe
        // load does not bubble, so it is caught in the capture phase.
        document.addEventListener('load', function (e) {
            if (e.target !== s.frame()) return;
            try {
                var w = e.target.contentWindow;
                w.addEventListener('keydown', onKey);
                w.addEventListener('scroll', schedule, { passive: true });
                w.addEventListener('resize', schedule, { passive: true });
                w.document.addEventListener('theme:canvas-lifecycle', schedule);
            } catch (_) { /* cross-origin preview: shortcut from the editor only */ }
            schedule();
        }, true);
        // Selection, page and device changes.
        root.Alpine.effect(function () {
            void (editor.selectedBlock && editor.selectedBlock.id);
            void editor.currentPage; void editor.device;
            schedule();
        });
        if (enabledByUrl(root.location.search)) s.toggle(true);
        return s;
    }

    root.TbDevHud = Object.freeze({ version: 1, attach: attach, isShortcut: isShortcut, enabledByUrl: enabledByUrl, viewHint: viewHint, placeholderErrors: placeholderErrors, splitSlotErrors: splitSlotErrors, childrenPolicy: childrenPolicy, contentIssues: contentIssues, deadLinksIn: deadLinksIn, requiredPropIssue: requiredPropIssue, buildPrompt: buildPrompt });
}(window));
</script>
@endonce
@switch($aiDevPart)
@case('toolbar')
<button type="button" class="tbdh-chip" x-cloak x-show="$store.devHud?.active" @click="$store.devHud.toggle(false)"
    title="AI Dev HUD فعال است — بستن (Ctrl+Shift+D)" aria-label="AI Dev HUD فعال است؛ بستن" data-ai-dev-chip>
    <span class="tbdh-dot" :data-tone="$store.devHud?.tone" aria-hidden="true"></span>
    Dev
</button>
<div class="tbdh-bar" dir="rtl" role="toolbar" aria-label="AI Dev HUD" data-ai-dev-hud x-data x-cloak
    x-show="$store.devHud.active && $store.devHud.meta && $store.devHud.pos"
    :style="$store.devHud.pos ? `top:${$store.devHud.pos.top}px;left:${$store.devHud.pos.left}px;width:${$store.devHud.pos.width}px` : ''">
    <span class="tbdh-dot" :data-tone="$store.devHud.tone" aria-hidden="true"></span>
    <span class="tbdh-type" dir="ltr" x-text="$store.devHud.meta?.type" data-ai-dev-type></span>
    <span class="tbdh-state" :data-tone="$store.devHud.tone" x-text="$store.devHud.toneLabel" data-ai-dev-state></span>
    <span class="tbdh-badge" dir="ltr" x-show="$store.devHud.policyBadge" x-text="$store.devHud.policyBadge" data-ai-dev-policy-badge></span>
    <button type="button" class="tbdh-fix" x-show="$store.devHud.firstFixable" @click="$store.devHud.focusField($store.devHud.firstFixable.field)"
        :title="$store.devHud.firstFixable ? `${$store.devHud.firstFixable.message} — ${$store.devHud.firstFixable.field.path}` : ''" data-ai-dev-fix-field>تنظیم فیلد در پنل ادیتور</button>
    <input type="text" class="tbdh-input" x-model="$store.devHud.instruction" @keydown.enter.prevent="$store.devHud.copyPrompt()"
        placeholder="دستور رفع باگ یا افزودن امکان جدید..." aria-label="دستور توسعه‌دهنده" data-ai-dev-instruction>
    <button type="button" class="tbdh-copy" @click="$store.devHud.copyPrompt()" data-ai-dev-copy>کپی پرامپت هوش مصنوعی (AI Prompt)</button>
    <button type="button" class="tbdh-close" @click="$store.devHud.toggle(false)" aria-label="بستن Dev HUD" title="بستن (Ctrl+Shift+D)">×</button>
</div>
@break
@case('health')
<section class="tbdh-card" dir="rtl" x-cloak x-show="$store.devHud?.active" data-ai-dev-health aria-label="Dev HUD">
    <h3><span class="tbdh-dot" :data-tone="$store.devHud?.tone" aria-hidden="true"></span>Dev HUD — المان انتخاب‌شده</h3>
    <p class="tbdh-muted" x-show="!$store.devHud?.meta">یک المان را در بوم انتخاب کنید.</p>
    <template x-if="$store.devHud?.meta">
        <div>
            <dl>
                <dt>نوع</dt><dd><code dir="ltr" x-text="$store.devHud.meta.type"></code></dd>
                <dt>شناسه</dt><dd><code dir="ltr" x-text="$store.devHud.meta.id"></code></dd>
                <dt>والد</dt><dd><code dir="ltr" x-text="`${$store.devHud.meta.parent.type} (ID: ${$store.devHud.meta.parent.id})`"></code></dd>
                <dt>وضعیت</dt><dd x-text="$store.devHud.toneLabel" data-ai-dev-health-state></dd>
                <dt>فایل‌ها</dt><dd><template x-for="f in $store.devHud.meta.view" :key="f"><code dir="ltr" class="tbdh-file" :title="f" x-text="f.replace(/^.*\//, '')"></code></template></dd>
            </dl>
            {{-- User Content Action: the admin fills these in the Inspector. --}}
            <div x-show="$store.devHud.contentIssues.length" data-ai-dev-content>
                <h4 class="tbdh-group">نواقص محتوایی (قابل تنظیم در ادیتور)</h4>
                <ul>
                    <template x-for="(i, n) in $store.devHud.contentIssues" :key="n">
                        <li data-kind="content" :data-ai-dev-content-item="i.field ? i.field.path : ''">
                            <span x-text="i.message"></span>
                            <code dir="ltr" class="tbdh-path" x-show="i.field" x-text="i.field ? i.field.path : ''"></code>
                            <button type="button" class="tbdh-fix" x-show="i.field" @click="$store.devHud.focusField(i.field)" data-ai-dev-fix>تنظیم فیلد در پنل ادیتور</button>
                        </li>
                    </template>
                </ul>
            </div>
            {{-- Core Code Issue: render, schema, structure and layout. --}}
            <div x-show="$store.devHud.coreIssues.length" data-ai-dev-core>
                <h4 class="tbdh-group">ایرادات کد (Core Code Issue)</h4>
                <ul>
                    <template x-for="(i, n) in $store.devHud.coreIssues" :key="n">
                        <li :data-kind="i.kind" dir="ltr"><code x-text="`[${i.kind}] ${i.message}`"></code></li>
                    </template>
                </ul>
            </div>
            <button type="button" class="tbdh-copy" @click="$store.devHud.copyPrompt()" data-ai-dev-health-copy>کپی پرامپت هوش مصنوعی (AI Prompt)</button>
        </div>
    </template>
</section>
{{-- Children Integrity: the selected block's slots under the children policy. --}}
<section class="tbdh-card" dir="rtl" x-cloak x-show="$store.devHud?.active && $store.devHud?.children" data-ai-dev-children aria-label="سلامت فرزندان">
    <template x-if="$store.devHud?.children">
        <div>
            <h3>
                <span class="tbdh-dot" :data-tone="$store.devHud.children.slots.some(s => s.errors.length) ? 'error' : ($store.devHud.children.issues.length ? 'warn' : 'ok')" aria-hidden="true"></span>
                سلامت فرزندان (Children Integrity)
            </h3>
            <dl>
                <dt>پذیرش فرزند</dt><dd x-text="$store.devHud.children.accepts ? `بله (kind: ${$store.devHud.children.kind})` : `خیر (kind: ${$store.devHud.children.kind})`" data-ai-dev-children-accepts></dd>
                <dt>اعتبارسنجی ذخیره</dt><dd x-text="$store.devHud.children.validated ? 'فرزندان بررسی می‌شوند' : 'فرزندان بررسی نمی‌شوند'"></dd>
                <dt>سقف</dt><dd x-text="$store.devHud.children.max || 'نامحدود'"></dd>
            </dl>
            <p class="tbdh-muted" x-show="!$store.devHud.children.slots.length" x-text="$store.devHud.children.accepts ? 'این المان هنوز فرزندی ندارد.' : 'این المان فرزند نمی‌پذیرد.'"></p>
            <ol class="tbdh-slots" x-show="$store.devHud.children.slots.length">
                <template x-for="s in $store.devHud.children.slots" :key="s.index">
                    <li :data-ai-dev-slot="s.index" :data-tone="s.errors.length ? 'error' : 'ok'">
                        <span class="tbdh-slot-head" dir="ltr"><b x-text="`[${s.index}]`"></b> <code x-text="s.type"></code> <span class="tbdh-muted" x-text="s.id"></span></span>
                        <span x-show="!s.errors.length" class="tbdh-ok">سالم</span>
                        <template x-for="(e, n) in s.errors" :key="n"><code class="tbdh-slot-error" dir="ltr" x-text="`[${e.kind}] ${e.message}`"></code></template>
                    </li>
                </template>
            </ol>
        </div>
    </template>
</section>
@break
@endswitch
@endif
