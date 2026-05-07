@extends('admin.layout')
@section('title', 'گزارش ارسال فرم‌ها')
@section('header', 'گزارش ارسال فرم‌ها')
@section('content')
<div class="admin-page">
<div class="card admin-sticky-filters admin-hover-lift">
    <div class="card-body">
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
        <div class="admin-page-tools">
            <button type="submit" class="btn btn-primary">فیلتر</button>
            <a href="{{ route('admin.forms.submissions.index') }}" class="btn btn-secondary">پاک کردن</a>
        </div>
        <div class="admin-page-tools mr-auto">
        <button type="submit"
                formaction="{{ route('admin.forms.submissions.export') }}"
                formmethod="GET"
                class="btn btn-secondary">خروجی CSV</button>
        </div>
    </form>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-right">
            <thead>
                <tr class="bg-gray-50 border-b" id="submission-header-row">
                    @foreach($columnOrder as $col)
                        @if($col === 'id' || $col === 'form' || $col === 'date' || $col === 'ip' || in_array($col, $dataKeys, true))
                            <th class="p-3 font-bold border-l border-gray-200 hover:bg-gray-100 admin-draggable-head" data-column-id="{{ $col }}">
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
                        <td colspan="{{ count($columnOrder) }}" class="admin-empty">ارسالی یافت نشد.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@if($submissions->hasPages())
    <div class="admin-pagination">{{ $submissions->links() }}</div>
@endif
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var row = document.getElementById('submission-header-row');
    if (!row) return;

    var dragging = null;
    row.querySelectorAll('th[data-column-id]').forEach(function(th) {
        th.setAttribute('draggable', 'true');
        th.addEventListener('dragstart', function() {
            dragging = th;
            th.classList.add('is-dragging');
        });
        th.addEventListener('dragend', function() {
            th.classList.remove('is-dragging');
            row.querySelectorAll('th[data-column-id]').forEach(function(cell) { cell.classList.remove('is-over'); });
            dragging = null;
        });
        th.addEventListener('dragover', function(e) {
            e.preventDefault();
            if (dragging && dragging !== th) {
                th.classList.add('is-over');
            }
        });
        th.addEventListener('dragleave', function() {
            th.classList.remove('is-over');
        });
        th.addEventListener('drop', function(e) {
            e.preventDefault();
            if (!dragging || dragging === th) return;
            th.classList.remove('is-over');
            var rect = th.getBoundingClientRect();
            var placeAfter = e.clientX < (rect.left + rect.width / 2);
            row.insertBefore(dragging, placeAfter ? th : th.nextSibling);
            var order = [];
            row.querySelectorAll('th[data-column-id]').forEach(function(cell) { order.push(cell.getAttribute('data-column-id')); });
            fetch('{{ route("admin.forms.submissions.column-order") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                body: JSON.stringify({ order: order })
            }).then(function() { window.location.reload(); });
        });
    });
});
</script>
@endpush
@endsection
