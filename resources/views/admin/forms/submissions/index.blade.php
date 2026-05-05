@extends('admin.layout')
@section('title', 'گزارش ارسال فرم‌ها')
@section('header', 'گزارش ارسال فرم‌ها')
@section('content')

<div class="mb-6 flex flex-wrap items-end gap-3">
    <form method="GET" action="{{ route('admin.forms.submissions.index') }}" class="flex flex-wrap items-end gap-3">
        <div>
            <label class="form-label text-xs">جستجو</label>
            <input type="text" name="q" class="form-input w-40" placeholder="فرم، IP" value="{{ request('q') }}">
        </div>
        <div>
            <label class="form-label text-xs">فرم</label>
            <select name="form_id" class="form-input w-48">
                <option value="">همه</option>
                @foreach($forms as $f)
                    <option value="{{ $f->id }}" {{ request('form_id') == $f->id ? 'selected' : '' }}>{{ $f->title }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label text-xs">از تاریخ (شمسی)</label>
            <x-persian-date-input name="from" placeholder="۱۴۰۳/۰۱/۰۱" :value="request('from')" class="form-input w-44" />
        </div>
        <div>
            <label class="form-label text-xs">تا تاریخ (شمسی)</label>
            <x-persian-date-input name="to" placeholder="۱۴۰۳/۱۲/۲۹" :value="request('to')" class="form-input w-44" />
        </div>
        <button type="submit" class="btn btn-primary">فیلتر</button>
        <a href="{{ route('admin.forms.submissions.index') }}" class="btn btn-secondary">پاک کردن</a>
    </form>
    <form method="GET" action="{{ route('admin.forms.submissions.export') }}" class="mr-auto flex items-end gap-2">
        <input type="hidden" name="form_id" value="{{ request('form_id') }}">
        <input type="hidden" name="from" value="{{ request('from') }}">
        <input type="hidden" name="to" value="{{ request('to') }}">
        <input type="hidden" name="q" value="{{ request('q') }}">
        <button type="submit" class="btn btn-secondary">خروجی CSV</button>
    </form>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-right">
            <thead>
                <tr class="bg-gray-50 border-b" id="submission-header-row">
                    @foreach($columnOrder as $col)
                        @if($col === 'id' || $col === 'form' || $col === 'date' || $col === 'ip' || in_array($col, $dataKeys, true))
                            <th class="p-3 font-bold cursor-grab active:cursor-grabbing border-l border-gray-200 select-none hover:bg-gray-100" data-column-id="{{ $col }}">
                                @if($col === 'id') # @elseif($col === 'form') فرم @elseif($col === 'date') تاریخ @elseif($col === 'ip') IP @else {{ $col }} @endif
                            </th>
                        @endif
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($submissions as $item)
                    <tr class="border-b hover:bg-gray-50">
                        @foreach($columnOrder as $col)
                            @if($col === 'id')
                                <td class="p-3">{{ $item->id }}</td>
                            @elseif($col === 'form')
                                <td class="p-3">{{ $item->form?->title ?? '—' }}</td>
                            @elseif($col === 'date')
                                <td class="p-3 text-sm">{{ \App\Helpers\Jalali::formatFa($item->created_at, 'Y/m/d H:i') }}</td>
                            @elseif($col === 'ip')
                                <td class="p-3">{{ $item->ip ?? '—' }}</td>
                            @elseif(in_array($col, $dataKeys, true))
                                <td class="p-3 text-sm">{{ is_array($item->data[$col] ?? null) ? json_encode($item->data[$col]) : ($item->data[$col] ?? '—') }}</td>
                            @endif
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columnOrder) }}" class="p-6 text-center text-gray-500">ارسالی یافت نشد.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-3 border-t">
        {{ $submissions->links() }}
    </div>
</div>

@push('head')
<link rel="stylesheet" href="https://unpkg.com/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">
@endpush
@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://unpkg.com/persian-date@1.1.0/dist/persian-date.min.js"></script>
<script src="https://unpkg.com/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof jQuery !== 'undefined' && jQuery.fn.persianDatepicker) {
        jQuery('.persian-date-input').persianDatepicker({ format: 'YYYY/MM/DD', persianDigit: true, initialValueType: 'persian' });
    }
    var row = document.getElementById('submission-header-row');
    if (row && typeof Sortable !== 'undefined') {
        new Sortable(row, {
            animation: 150,
            handle: 'th',
            onEnd: function() {
                var order = [];
                row.querySelectorAll('th[data-column-id]').forEach(function(th) { order.push(th.getAttribute('data-column-id')); });
                fetch('{{ route("admin.forms.submissions.column-order") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ order: order })
                }).then(function() { window.location.reload(); });
            }
        });
    }
});
</script>
@endpush
@endsection
