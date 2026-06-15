@extends('admin.layout')
@section('title', 'هشدارهای موجودی')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">هشدارهای موجودی</h1>
            <p class="text-slate-500 text-sm">محصولاتی که موجودی آن‌ها به حد اخطار رسیده یا تمام شده</p>
        </div>
        <a href="{{ route('admin.inventory.index') }}" class="text-sm text-indigo-600 hover:underline">← داشبورد</a>
    </div>

    @if($alerts->isEmpty())
    <div class="bg-white rounded-xl border border-emerald-200 shadow-sm p-12 text-center">
        <svg class="w-14 h-14 mx-auto mb-4 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <p class="text-emerald-600 font-semibold text-lg">همه محصولات موجودی کافی دارند</p>
        <p class="text-slate-500 text-sm mt-1">در حال حاضر هیچ هشداری وجود ندارد.</p>
    </div>
    @else
    <div class="space-y-3">
        @foreach($alerts as $item)
        <div class="bg-white rounded-xl border {{ $item->isOutOfStock() ? 'border-red-200' : 'border-amber-200' }} shadow-sm p-4 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 {{ $item->isOutOfStock() ? 'bg-red-50' : 'bg-amber-50' }}">
                <svg class="w-6 h-6 {{ $item->isOutOfStock() ? 'text-red-500' : 'text-amber-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-semibold text-slate-800">{{ $item->product?->title ?? 'محصول نامشخص' }}</p>
                <p class="text-sm text-slate-500">انبار: {{ $item->warehouse?->name ?? '—' }} · حد اخطار: {{ $item->low_stock_threshold }}</p>
            </div>
            <div class="flex items-center gap-4 shrink-0">
                <div class="text-center">
                    <p class="text-2xl font-bold {{ $item->isOutOfStock() ? 'text-red-600' : 'text-amber-600' }}">{{ $item->quantity }}</p>
                    <p class="text-xs text-slate-400">موجودی فعلی</p>
                </div>
                <a href="{{ route('admin.inventory.items.index', ['warehouse_id' => $item->warehouse_id]) }}" class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-xs font-medium hover:bg-indigo-700 transition whitespace-nowrap">
                    تنظیم موجودی
                </a>
            </div>
        </div>
        @endforeach
    </div>
    @endif

</div>
@endsection
