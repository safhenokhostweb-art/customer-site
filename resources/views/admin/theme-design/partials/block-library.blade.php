{{-- ────────────────────────────────────────────────────────────────────────────
    Block Library palette (Phase 11 follow-up — header-modern adoption).

    Curated, ready-to-insert presets from App\Services\ThemeCanvas\BlockCatalog:
    3 headers, 2 footers, 3 business sections. Every preset targets a REAL
    registered block type (the catalog enforces this — no unrenderable types),
    so insertion stays 100% canonical: the editor's addLibraryBlock() goes
    through makeBlock() + the standard top-level insertion path and merely
    deep-merges the preset's replaceable mock props on top of the type's own
    defaultProps.

    Self-sufficient by design: it pulls its data straight from the catalog
    service so the canvas-editor route/controller contract stays untouched.
    The `fields` map carries the standardized library-level contract
    (text / image_url / link) pointing at the real prop each maps to; chips
    below make that contract visible. Actual editing stays in the inspector —
    this partial never renders its own editing surface.
──────────────────────────────────────────────────────────────────────────── --}}
@php
    $blockLibrary = \App\Services\ThemeCanvas\BlockCatalog::byGroup();
@endphp
@foreach($blockLibrary as $groupKey => $group)
    <div class="border-b border-slate-100" data-testid="tb-library-group-{{ $groupKey }}">
        <div class="flex items-center justify-between px-3 py-2 cursor-pointer select-none hover:bg-slate-50"
             @click="toggleCategory('lib-{{ $groupKey }}')">
            <span class="text-[11px] font-bold uppercase tracking-wide text-slate-500">
                {{ $group['icon'] }} {{ $group['label'] }}
            </span>
            <span class="text-[10px] text-slate-400">{{ count($group['items']) }} مورد</span>
        </div>
        <div x-show="isCategoryVisible('lib-{{ $groupKey }}')" class="px-3 pb-3 grid grid-cols-2 gap-2">
            @foreach($group['items'] as $presetKey => $preset)
                <button type="button"
                        @click="addLibraryBlock(@js($preset))"
                        data-testid="tb-library-add-{{ $presetKey }}"
                        class="flex flex-col items-start gap-1 p-2.5 rounded-lg border border-indigo-100 bg-gradient-to-bl from-white to-indigo-50/40 hover:border-indigo-400 hover:shadow-sm transition text-right"
                        title="{{ $preset['description'] }}"
                >
                    <div class="text-lg leading-none">{{ $preset['icon'] }}</div>
                    <span class="text-[11px] font-semibold text-slate-700 hover:text-indigo-700 leading-tight line-clamp-2">{{ $preset['label'] }}</span>
                    <div class="flex flex-wrap gap-1 mt-0.5">
                        @foreach($preset['fields'] as $field)
                            <span class="text-[9px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-500 font-medium leading-none"
                                  title="فیلد استاندارد: {{ $field['label'] }}">{{ $field['type'] }}</span>
                        @endforeach
                    </div>
                </button>
            @endforeach
        </div>
    </div>
@endforeach
