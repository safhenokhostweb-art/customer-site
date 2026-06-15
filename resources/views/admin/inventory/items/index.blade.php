@extends('admin.layout')
@section('title', 'موجودی انبارها')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">موجودی کالاها</h1>
            <p class="text-slate-500 text-sm">{{ number_format($items->total()) }} آیتم</p>
        </div>
        <a href="{{ route('admin.inventory.index') }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-white border border-slate-200 text-slate-700 text-sm font-medium hover:bg-slate-50 transition">
            ← داشبورد انبار
        </a>
    </div>

    @if(session('success'))
    <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-emerald-700 text-sm">{{ session('success') }}</div>
    @endif

    {{-- Filters --}}
    <form method="GET" class="flex flex-wrap gap-3 mb-5">
        <input name="q" value="{{ request('q') }}" placeholder="جستجو نام محصول..." class="flex-1 min-w-40 px-3 py-2 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none">
        <select name="warehouse_id" class="px-3 py-2 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none">
            <option value="">همه انبارها</option>
            @foreach($warehouses as $wh)
            <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
            @endforeach
        </select>
        <label class="flex items-center gap-2 px-3 py-2 rounded-lg border border-slate-200 text-sm cursor-pointer hover:bg-slate-50">
            <input type="checkbox" name="low_stock" value="1" {{ request('low_stock') ? 'checked' : '' }}>
            فقط موجودی کم
        </label>
        <button type="submit" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">فیلتر</button>
    </form>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3 text-right font-medium text-slate-600">محصول</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-600 hidden md:table-cell">انبار</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-600">موجودی</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-600 hidden sm:table-cell">در دسترس</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-600 hidden lg:table-cell">محدوده اخطار</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-600">تنظیم موجودی</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($items as $item)
                <tr class="hover:bg-slate-50 transition {{ $item->isLowStock() ? 'bg-amber-50/40' : '' }}">
                    <td class="px-4 py-3">
                        <p class="font-medium text-slate-800">{{ $item->product?->title ?? '—' }}</p>
                        @if($item->location)
                        <p class="text-xs text-slate-400">محل: {{ $item->location }}</p>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-slate-600 hidden md:table-cell">{{ $item->warehouse?->name ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <span class="font-bold text-lg {{ $item->isOutOfStock() ? 'text-red-600' : ($item->isLowStock() ? 'text-amber-600' : 'text-slate-800') }}">
                            {{ $item->quantity }}
                        </span>
                        @if($item->isOutOfStock())
                        <span class="mr-1 text-xs bg-red-100 text-red-700 px-1 py-0.5 rounded">اتمام</span>
                        @elseif($item->isLowStock())
                        <span class="mr-1 text-xs bg-amber-100 text-amber-700 px-1 py-0.5 rounded">کم</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-slate-600 hidden sm:table-cell">{{ $item->availableQuantity() }}</td>
                    <td class="px-4 py-3 text-slate-500 text-xs hidden lg:table-cell">اخطار ≤ {{ $item->low_stock_threshold }}</td>
                    <td class="px-4 py-3">
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 text-xs hover:bg-slate-50 transition">
                                تنظیم ↓
                            </button>
                            <div x-show="open" @click.away="open=false" class="absolute left-0 top-full mt-1 w-64 bg-white border border-slate-200 rounded-xl shadow-lg p-4 z-10">
                                <form method="POST" action="{{ route('admin.inventory.items.adjust', $item) }}" class="space-y-2">
                                    @csrf
                                    <select name="type" class="w-full px-2 py-1.5 rounded border border-slate-200 text-xs">
                                        <option value="in">ورود کالا</option>
                                        <option value="out">خروج کالا</option>
                                        <option value="adjustment">تعدیل موجودی</option>
                                        <option value="return">مرجوعی</option>
                                    </select>
                                    <input name="quantity_change" type="number" placeholder="مقدار تغییر (+ یا -)" required class="w-full px-2 py-1.5 rounded border border-slate-200 text-xs">
                                    <input name="notes" placeholder="یادداشت..." class="w-full px-2 py-1.5 rounded border border-slate-200 text-xs">
                                    <button type="submit" class="w-full py-1.5 rounded bg-indigo-600 text-white text-xs font-medium hover:bg-indigo-700">اعمال</button>
                                </form>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-12 text-center text-slate-400">
                        <svg class="w-10 h-10 mx-auto mb-2 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        هنوز آیتمی در انبار ثبت نشده است
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($items->hasPages())
    <div class="mt-4">{{ $items->links() }}</div>
    @endif

</div>
@endsection
