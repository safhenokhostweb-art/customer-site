@extends('admin.layout')
@section('title', 'داشبورد انبار و موجودی')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">انبار و موجودی</h1>
            <p class="text-slate-500 text-sm mt-0.5">مدیریت موجودی کالا، انبارها، و گزارش‌های حرکت کالا</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('admin.inventory.alerts.index') }}" class="relative inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-white border border-slate-200 text-slate-700 text-sm font-medium hover:bg-slate-50 transition">
                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                هشدارهای موجودی
                @if($lowStockItems > 0)
                <span class="absolute -top-1 -left-1 w-5 h-5 rounded-full bg-amber-500 text-white text-xs flex items-center justify-center font-bold">@numFa($lowStockItems)</span>
                @endif
            </a>
            <a href="{{ route('admin.inventory.warehouses.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                انبار جدید
            </a>
        </div>
    </div>

    {{-- KPI --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center mb-3">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            </div>
            <div class="text-2xl font-bold text-slate-800">@numFa($totalWarehouses)</div>
            <div class="text-xs text-slate-500 mt-0.5">انبار فعال</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <div class="w-10 h-10 rounded-xl bg-violet-50 flex items-center justify-center mb-3">
                <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
            <div class="text-2xl font-bold text-slate-800">@numFa($totalItems)</div>
            <div class="text-xs text-slate-500 mt-0.5">آیتم انبار</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center mb-3">
                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div class="text-2xl font-bold text-amber-600">@numFa($lowStockItems)</div>
            <div class="text-xs text-slate-500 mt-0.5">موجودی کم</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center mb-3">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </div>
            <div class="text-2xl font-bold text-red-600">@numFa($outOfStockItems)</div>
            <div class="text-xs text-slate-500 mt-0.5">اتمام موجودی</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Low Stock Alerts --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-semibold text-slate-800 flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    هشدار موجودی کم
                </h2>
                <a href="{{ route('admin.inventory.alerts.index') }}" class="text-sm text-indigo-600 hover:underline">همه ←</a>
            </div>
            @forelse($lowStockList as $item)
            <div class="flex items-center justify-between py-2.5 border-b border-slate-100 last:border-0">
                <div class="min-w-0">
                    <p class="text-sm font-medium text-slate-800 truncate">{{ $item->product?->title ?? 'محصول نامشخص' }}</p>
                    <p class="text-xs text-slate-500">انبار: {{ $item->warehouse?->name ?? '—' }}</p>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <span class="text-sm font-bold {{ $item->quantity === 0 ? 'text-red-600' : 'text-amber-600' }}">@numFa($item->quantity)</span>
                    <a href="{{ route('admin.inventory.items.index', ['warehouse_id' => $item->warehouse_id]) }}" class="text-xs text-indigo-500 hover:underline">تنظیم</a>
                </div>
            </div>
            @empty
            <p class="text-slate-400 text-sm text-center py-6">همه محصولات موجودی کافی دارند ✓</p>
            @endforelse
        </div>

        {{-- Recent Movements --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-semibold text-slate-800">آخرین حرکات کالا</h2>
                <a href="{{ route('admin.inventory.movements.index') }}" class="text-sm text-indigo-600 hover:underline">همه ←</a>
            </div>
            @forelse($recentMovements as $mv)
            <div class="flex items-center justify-between py-2.5 border-b border-slate-100 last:border-0">
                <div class="min-w-0">
                    <p class="text-sm text-slate-700 truncate">{{ $mv->product?->title ?? '—' }}</p>
                    <p class="text-xs text-slate-500">{{ \App\Models\StockMovement::typeLabel($mv->type) }} · {{ $mv->warehouse?->name ?? '—' }}</p>
                </div>
                <span class="text-sm font-semibold {{ $mv->quantity_change >= 0 ? 'text-emerald-600' : 'text-red-600' }} shrink-0">
                    {{ $mv->quantity_change > 0 ? '+' : '' }}@numFa($mv->quantity_change)
                </span>
            </div>
            @empty
            <p class="text-slate-400 text-sm text-center py-6">هنوز حرکتی ثبت نشده است.</p>
            @endforelse
        </div>
    </div>

    {{-- Quick Links --}}
    <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
        <a href="{{ route('admin.inventory.warehouses.index') }}" class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm hover:border-indigo-300 transition flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            </div>
            <span class="text-sm font-medium text-slate-700">انبارها</span>
        </a>
        <a href="{{ route('admin.inventory.items.index') }}" class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm hover:border-indigo-300 transition flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-violet-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
            <span class="text-sm font-medium text-slate-700">مدیریت موجودی</span>
        </a>
        <a href="{{ route('admin.inventory.movements.index') }}" class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm hover:border-indigo-300 transition flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
            </div>
            <span class="text-sm font-medium text-slate-700">حرکات کالا</span>
        </a>
        <a href="{{ route('admin.inventory.alerts.index') }}" class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm hover:border-amber-300 transition flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            </div>
            <span class="text-sm font-medium text-slate-700">هشدارها</span>
        </a>
    </div>

</div>
@endsection
