@extends('admin.layout')
@section('title', 'حرکات کالا')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">حرکات کالا</h1>
            <p class="text-slate-500 text-sm">تاریخچه کامل ورود و خروج کالا از انبارها</p>
        </div>
        <a href="{{ route('admin.inventory.index') }}" class="text-sm text-indigo-600 hover:underline">← داشبورد</a>
    </div>

    {{-- Filters --}}
    <form method="GET" class="flex flex-wrap gap-3 mb-5">
        <select name="warehouse_id" class="px-3 py-2 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none">
            <option value="">همه انبارها</option>
            @foreach($warehouses as $wh)
            <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
            @endforeach
        </select>
        <select name="type" class="px-3 py-2 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-300 outline-none">
            <option value="">همه نوع‌ها</option>
            <option value="in" {{ request('type') === 'in' ? 'selected' : '' }}>ورود کالا</option>
            <option value="out" {{ request('type') === 'out' ? 'selected' : '' }}>خروج کالا</option>
            <option value="adjustment" {{ request('type') === 'adjustment' ? 'selected' : '' }}>تعدیل</option>
            <option value="return" {{ request('type') === 'return' ? 'selected' : '' }}>مرجوعی</option>
        </select>
        <button type="submit" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">فیلتر</button>
    </form>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3 text-right font-medium text-slate-600">تاریخ</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-600">محصول</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-600 hidden md:table-cell">انبار</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-600">نوع</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-600">مقدار</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-600 hidden sm:table-cell">قبل / بعد</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-600 hidden lg:table-cell">یادداشت</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($movements as $mv)
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-4 py-3 text-slate-500 text-xs whitespace-nowrap">{{ \App\Helpers\Jalali::formatFa($mv->moved_at, 'Y/m/d H:i') }}</td>
                    <td class="px-4 py-3 font-medium text-slate-800">{{ $mv->product?->title ?? '—' }}</td>
                    <td class="px-4 py-3 text-slate-600 hidden md:table-cell">{{ $mv->warehouse?->name ?? '—' }}</td>
                    <td class="px-4 py-3">
                        @php $typeColors = ['in'=>'emerald','out'=>'red','adjustment'=>'blue','transfer'=>'violet','return'=>'amber']; $c = $typeColors[$mv->type] ?? 'slate'; @endphp
                        <span class="text-xs px-2 py-0.5 rounded-full bg-{{ $c }}-50 text-{{ $c }}-700 border border-{{ $c }}-200">
                            {{ \App\Models\StockMovement::typeLabel($mv->type) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 font-bold {{ $mv->quantity_change > 0 ? 'text-emerald-600' : 'text-red-600' }}">
                        {{ $mv->quantity_change > 0 ? '+' : '' }}@numFa($mv->quantity_change)
                    </td>
                    <td class="px-4 py-3 text-slate-500 text-xs hidden sm:table-cell">@numFa($mv->quantity_before) → @numFa($mv->quantity_after)</td>
                    <td class="px-4 py-3 text-slate-500 text-xs hidden lg:table-cell">{{ $mv->notes ?? '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-10 text-center text-slate-400">هنوز حرکتی ثبت نشده است</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($movements->hasPages())
    <div class="mt-4">{{ $movements->links() }}</div>
    @endif

</div>
@endsection
