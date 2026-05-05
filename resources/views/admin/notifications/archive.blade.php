@extends('admin.layout')
@section('title', 'آرشیو اعلان‌ها')
@section('header', 'آرشیو اعلان‌ها')

@section('content')
<div class="mb-6">
    <form method="get" action="{{ route('admin.notifications.archive') }}" class="flex flex-wrap items-end gap-3">
        <div class="form-group mb-0">
            <label class="form-label">جستجو (عنوان یا متن)</label>
            <input type="text" name="q" value="{{ request('q') }}" class="form-control w-56" placeholder="کاراکتر یا کلمه...">
        </div>
        <div class="form-group mb-0">
            <label class="form-label">از تاریخ (شمسی)</label>
            <x-persian-date-input name="from" :value="request('from')" placeholder="۱۴۰۳/۰۱/۰۱" class="form-control w-40" />
        </div>
        <div class="form-group mb-0">
            <label class="form-label">تا تاریخ (شمسی)</label>
            <x-persian-date-input name="to" :value="request('to')" placeholder="۱۴۰۳/۱۲/۲۹" class="form-control w-40" />
        </div>
        <button type="submit" class="btn btn-primary">فیلتر</button>
        <a href="{{ route('admin.notifications.archive') }}" class="btn btn-secondary">پاک کردن</a>
    </form>
</div>

<div class="card">
    <div class="card-header flex justify-between items-center">
        <span>لیست ارسال‌ها</span>
        <a href="{{ route('admin.notifications.send-users.form') }}" class="btn btn-primary btn-sm">ارسال اعلان جدید</a>
    </div>
    <div class="table-responsive">
        <table class="w-full">
            <thead>
                <tr>
                    <th class="text-right p-3">عنوان</th>
                    <th class="text-right p-3 hidden md:table-cell">نوع</th>
                    <th class="text-right p-3">تعداد مخاطب</th>
                    <th class="text-right p-3">تاریخ ارسال</th>
                </tr>
            </thead>
            <tbody>
                @forelse($batches as $b)
                <tr>
                    <td class="p-3 font-medium">{{ $b->title }}</td>
                    <td class="p-3 hidden md:table-cell">
                        @php $types = ['info'=>'اطلاع','success'=>'موفق','warning'=>'هشدار','danger'=>'مهم','promo'=>'تبلیغاتی','ad'=>'تبلیغات']; @endphp
                        {{ $types[$b->type] ?? $b->type }}
                    </td>
                    <td class="p-3">{{ number_format($b->recipient_count) }}</td>
                    <td class="p-3 text-slate-600">{{ \App\Helpers\Jalali::formatFa($b->sent_at, 'Y/m/d H:i') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="p-8 text-center text-slate-500">اعلانی در آرشیو نیست.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@if($batches->hasPages())
<div class="mt-4">{{ $batches->withQueryString()->links() }}</div>
@endif

@push('head')
<link rel="stylesheet" href="https://unpkg.com/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">
@endpush
@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://unpkg.com/persian-date@1.1.0/dist/persian-date.min.js"></script>
<script src="https://unpkg.com/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script>
<script>
if (document.querySelector('.persian-date-input') && typeof jQuery !== 'undefined' && jQuery.fn.persianDatepicker) {
    jQuery('.persian-date-input').persianDatepicker({ format: 'YYYY/MM/DD', persianDigit: true, initialValueType: 'persian' });
}
</script>
@endpush
@endsection
