@extends('admin.layout')
@section('title', 'انبارها')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">انبارها</h1>
            <p class="text-slate-500 text-sm">{{ $warehouses->count() }} انبار ثبت شده</p>
        </div>
        <a href="{{ route('admin.inventory.warehouses.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            انبار جدید
        </a>
    </div>

    @if($warehouses->isEmpty())
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-12 text-center">
        <svg class="w-14 h-14 mx-auto mb-4 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        <p class="text-slate-500 font-medium">هنوز انباری ثبت نشده است</p>
        <a href="{{ route('admin.inventory.warehouses.create') }}" class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">اولین انبار را بسازید</a>
    </div>
    @else
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($warehouses as $wh)
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 {{ $wh->is_default ? 'border-indigo-300 ring-1 ring-indigo-100' : '' }}">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="font-semibold text-slate-800">{{ $wh->name }}</h3>
                        @if($wh->is_default)
                        <span class="text-xs px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-700 font-medium">پیش‌فرض</span>
                        @endif
                        @if(!$wh->is_active)
                        <span class="text-xs px-1.5 py-0.5 rounded bg-slate-100 text-slate-500">غیرفعال</span>
                        @endif
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">کد: {{ $wh->code }}</p>
                </div>
                <div class="flex items-center gap-1">
                    <a href="{{ route('admin.inventory.warehouses.edit', $wh) }}" class="p-1.5 rounded hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </a>
                </div>
            </div>
            @if($wh->city || $wh->address)
            <p class="text-xs text-slate-500 mb-2">{{ $wh->city }}{{ $wh->city && $wh->address ? ' · ' : '' }}{{ $wh->address }}</p>
            @endif
            @if($wh->manager_name)
            <p class="text-xs text-slate-500 mb-3">مسئول: {{ $wh->manager_name }}{{ $wh->manager_mobile ? ' (' . $wh->manager_mobile . ')' : '' }}</p>
            @endif
            <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                <span class="text-sm font-semibold text-slate-700">@numFa($wh->inventory_items_count) آیتم</span>
                <a href="{{ route('admin.inventory.items.index', ['warehouse_id' => $wh->id]) }}" class="text-xs text-indigo-500 hover:underline">مشاهده موجودی ←</a>
            </div>
        </div>
        @endforeach
    </div>
    @endif

</div>
@endsection
