@extends('admin.layout')
@section('title', 'پایپ‌لاین فروش CRM')

@push('head')
<style>
.pipeline-col{min-width:220px;max-width:280px;flex:1 0 220px}
.deal-card{cursor:grab;transition:box-shadow .2s,transform .2s}
.deal-card:active{cursor:grabbing;transform:scale(1.02)}
.drag-over{background:rgba(99,102,241,0.06);border-color:#a5b4fc!important}
</style>
@endpush

@section('content')
<div class="px-4 py-6">

    <div class="flex items-center justify-between mb-6 max-w-full">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">پایپ‌لاین فروش</h1>
            <p class="text-slate-500 text-sm">کارت‌ها را بین مراحل جابجا کنید</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.crm.index') }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-white border border-slate-200 text-slate-700 text-sm font-medium hover:bg-slate-50 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                داشبورد CRM
            </a>
        </div>
    </div>

    <div class="flex gap-4 overflow-x-auto pb-4">
        @php
        $stageColors = [
            'lead' => 'slate',
            'contacted' => 'blue',
            'proposal' => 'violet',
            'negotiation' => 'amber',
            'won' => 'emerald',
            'lost' => 'red',
        ];
        @endphp

        @foreach($stages as $stageKey => $stageLabel)
        @php
            $deals = $pipeline[$stageKey] ?? collect();
            $color = $stageColors[$stageKey] ?? 'slate';
            $total = $deals->sum('value');
        @endphp
        <div class="pipeline-col bg-slate-50 rounded-xl border border-slate-200 p-3"
             data-stage="{{ $stageKey }}"
             ondragover="event.preventDefault();this.classList.add('drag-over')"
             ondragleave="this.classList.remove('drag-over')"
             ondrop="handleDrop(event,this)">

            {{-- Column Header --}}
            <div class="flex items-center justify-between mb-3 px-1">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-{{ $color }}-500"></span>
                    <span class="text-sm font-semibold text-slate-700">{{ $stageLabel }}</span>
                    <span class="text-xs bg-slate-200 text-slate-600 px-1.5 py-0.5 rounded-full font-medium">{{ $deals->count() }}</span>
                </div>
                @if($total > 0)
                <span class="text-xs text-slate-500">{{ number_format($total) }} ت</span>
                @endif
            </div>

            {{-- Deal Cards --}}
            <div class="space-y-2 min-h-16" id="col-{{ $stageKey }}">
                @foreach($deals as $deal)
                <div class="deal-card bg-white rounded-lg border border-slate-200 p-3 shadow-sm"
                     draggable="true"
                     data-deal-id="{{ $deal->id }}"
                     ondragstart="handleDragStart(event,{{ $deal->id }})">
                    <p class="text-sm font-medium text-slate-800 mb-1 line-clamp-2">{{ $deal->title }}</p>
                    @if($deal->contact)
                    <p class="text-xs text-slate-500 mb-2">{{ $deal->contact->name }}</p>
                    @endif
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-{{ $color }}-700">{{ number_format($deal->value) }} ت</span>
                        @if($deal->expected_close_date)
                        <span class="text-xs text-slate-400">{{ $deal->expected_close_date->format('Y/m/d') }}</span>
                        @endif
                    </div>
                    <div class="mt-2 flex items-center gap-1">
                        <div class="flex-1 bg-slate-100 rounded-full h-1">
                            <div class="h-1 rounded-full bg-{{ $color }}-400" style="width: {{ $deal->probability }}%"></div>
                        </div>
                        <span class="text-xs text-slate-400">{{ $deal->probability }}%</span>
                    </div>
                    @if($deal->contact)
                    <a href="{{ route('admin.crm.contacts.show', $deal->contact_id) }}" class="mt-2 block text-xs text-indigo-500 hover:underline">مشاهده مخاطب ←</a>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</div>

@push('scripts')
<script>
let draggedDealId = null;

function handleDragStart(ev, dealId) {
    draggedDealId = dealId;
    ev.dataTransfer.effectAllowed = 'move';
}

async function handleDrop(ev, col) {
    ev.preventDefault();
    col.classList.remove('drag-over');
    if (!draggedDealId) return;

    const newStage = col.dataset.stage;

    try {
        const res = await fetch(`/admin/crm/deals/${draggedDealId}/move`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ stage: newStage }),
        });
        if (res.ok) {
            location.reload();
        }
    } catch (e) {
        alert('خطا در جابجایی کارت');
    }
    draggedDealId = null;
}
</script>
@endpush
@endsection
