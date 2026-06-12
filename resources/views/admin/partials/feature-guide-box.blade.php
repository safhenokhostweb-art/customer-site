<div class="card bg-slate-50 border border-slate-200 text-sm text-slate-700">
    <details class="group" @if(!isset($open) || $open) open @endif>
        <summary class="flex items-center justify-between cursor-pointer list-none px-4 py-3">
            <span class="font-semibold text-slate-800">
                {{ $title ?? 'راهنمای قابلیت' }}
            </span>
            <x-core-ui::icon name="chevron-down" class="w-4 h-4 text-slate-500 transition-transform duration-200 group-open:rotate-180" />
        </summary>
        <div class="px-4 pb-4 pt-1 leading-relaxed">
            {{ $content ?? '' }}
        </div>
    </details>
</div>

