{{--
    Canvas overhaul Unit 4: one link control for every `link` field, at the
    top level and at every repeater depth (nav links, footer columns, card
    links). It shows the picked entity as a chip, or the field's own URL
    input plus a button that opens the shared entity picker
    (canvas-editor.blade.php, `entityPicker`).

    Props are Alpine expressions evaluated in the caller's scope:
      value  — the field's current value
      set    — a function taking the new value (a string or an entity ref)
      testid — the data-testid for the chip and picker button
    The slot is the caller's existing URL <input>.
--}}
@props(['value', 'set', 'testid'])
<div class="space-y-1">
    <template x-if="isEntityLinkValue({{ $value }})">
        <div class="flex items-center gap-2 px-2 py-1.5 rounded-lg border border-emerald-200 bg-emerald-50 text-xs text-emerald-900"
             :data-testid="'link-chip-' + {{ $testid }}">
            <span class="shrink-0 rounded bg-emerald-600/10 px-1.5 py-0.5 text-[10px] font-bold" x-text="entityLinkKind({{ $value }})"></span>
            <span class="truncate flex-1" x-text="entityLinkLabel({{ $value }})"></span>
            <button type="button" class="shrink-0 text-indigo-600 hover:underline" @click="openEntityPicker({{ $set }})">تغییر</button>
            <button type="button" class="shrink-0 px-1 text-slate-500 hover:text-red-600" aria-label="حذف لینک" @click="({{ $set }})('')">×</button>
        </div>
    </template>
    <template x-if="!isEntityLinkValue({{ $value }})">
        <div class="flex items-stretch gap-1">
            <div class="flex-1 min-w-0">{{ $slot }}</div>
            <button type="button" title="انتخاب از صفحات، محصولات، دسته‌ها و مقالات"
                    class="shrink-0 rounded border border-slate-200 bg-white px-2 text-xs font-bold text-indigo-600 hover:bg-indigo-50"
                    :data-testid="'link-pick-' + {{ $testid }}"
                    @click="openEntityPicker({{ $set }})">انتخاب</button>
        </div>
    </template>
</div>
