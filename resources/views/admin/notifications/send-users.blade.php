@extends('admin.layout')
@section('title', 'ارسال اعلان به کاربران')
@section('header', 'ارسال اعلان به کاربران')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <p class="text-slate-600 text-sm">اعلان در صندوق ورودی «حساب من» برای کاربران نمایش داده می‌شود.</p>
    <a href="{{ route('admin.notifications.archive') }}" class="btn btn-secondary btn-sm">آرشیو اعلان‌ها</a>
</div>

<div class="max-w-3xl">
    <div class="card">
        <div class="card-header">مخاطبان</div>
        <div class="card-body">
            <form action="{{ route('admin.notifications.send-users') }}" method="post" enctype="multipart/form-data" id="notification-send-form">
                @csrf
                <div class="form-group">
                    <label class="form-label">ارسال به *</label>
                    <select name="recipient_type" id="recipient_type" class="form-control" required>
                        <option value="all" {{ old('recipient_type', 'all') === 'all' ? 'selected' : '' }}>همهٔ کاربران ({{ number_format($userCount) }})</option>
                        <option value="selected" {{ old('recipient_type') === 'selected' ? 'selected' : '' }}>کاربران انتخاب‌شده</option>
                        <option value="date_range" {{ old('recipient_type') === 'date_range' ? 'selected' : '' }}>کاربران بر اساس تاریخ ثبت‌نام</option>
                    </select>
                </div>

                <div id="recipient-selected" class="form-group hidden">
                    <label class="form-label">جستجو و انتخاب کاربران</label>
                    <input type="text" id="user-search" class="form-control mb-2" placeholder="نام، ایمیل یا موبایل...">
                    <div class="border border-slate-200 rounded-lg max-h-48 overflow-y-auto p-2">
                        @foreach($users as $u)
                        <label class="flex items-center gap-2 py-1.5 px-2 hover:bg-slate-50 rounded user-row" data-name="{{ str_replace('"', '', $u->name ?? '') }}" data-email="{{ str_replace('"', '', $u->email ?? '') }}" data-mobile="{{ str_replace('"', '', $u->mobile ?? '') }}">
                            <input type="checkbox" name="user_ids[]" value="{{ $u->id }}" {{ in_array($u->id, old('user_ids', [])) ? 'checked' : '' }}>
                            <span>{{ $u->name }} — {{ $u->email ?? $u->mobile ?? '-' }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                <div id="recipient-date-range" class="grid grid-cols-1 md:grid-cols-2 gap-4 form-group hidden">
                    <div>
                        <label class="form-label">ثبت‌نام از تاریخ (شمسی)</label>
                        <x-persian-date-input name="registered_from" :value="old('registered_from')" placeholder="۱۴۰۳/۰۱/۰۱" class="form-control" />
                    </div>
                    <div>
                        <label class="form-label">ثبت‌نام تا تاریخ (شمسی)</label>
                        <x-persian-date-input name="registered_to" :value="old('registered_to')" placeholder="۱۴۰۳/۱۲/۲۹" class="form-control" />
                    </div>
                </div>

                <hr class="my-6">
                <div class="form-group">
                    <label class="form-label">عنوان *</label>
                    <input type="text" name="title" value="{{ old('title') }}" required class="form-control" placeholder="مثلاً تخفیف ویژه">
                </div>
                <div class="form-group">
                    <label class="form-label">متن اعلان</label>
                    <textarea name="body" rows="4" class="form-control" placeholder="متن کامل...">{{ old('body') }}</textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">نوع اعلان</label>
                    <select name="type" class="form-control">
                        <option value="info" {{ old('type', 'info') === 'info' ? 'selected' : '' }}>اطلاع‌رسانی</option>
                        <option value="success" {{ old('type') === 'success' ? 'selected' : '' }}>موفقیت</option>
                        <option value="warning" {{ old('type') === 'warning' ? 'selected' : '' }}>هشدار</option>
                        <option value="danger" {{ old('type') === 'danger' ? 'selected' : '' }}>مهم</option>
                        <option value="promo" {{ old('type') === 'promo' ? 'selected' : '' }}>تبلیغاتی</option>
                        <option value="ad" {{ old('type') === 'ad' ? 'selected' : '' }}>تبلیغات</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">تصویر (اختیاری)</label>
                    <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp" class="form-control">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">لینک (اختیاری)</label>
                        <input type="url" name="link" value="{{ old('link') }}" class="form-control" placeholder="https://">
                    </div>
                    <div class="form-group">
                        <label class="form-label">متن دکمهٔ فراخوان</label>
                        <input type="text" name="cta_text" value="{{ old('cta_text', 'مشاهده') }}" class="form-control">
                    </div>
                </div>
                <div class="flex gap-2 mt-6">
                    <button type="submit" class="btn btn-primary">ارسال اعلان</button>
                    <a href="{{ route('admin.notifications.archive') }}" class="btn btn-secondary">آرشیو</a>
                </div>
            </form>
        </div>
    </div>
</div>

@push('head')
<link rel="stylesheet" href="https://unpkg.com/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">
@endpush
@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://unpkg.com/persian-date@1.1.0/dist/persian-date.min.js"></script>
<script src="https://unpkg.com/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var sel = document.getElementById('recipient_type');
    var divSelected = document.getElementById('recipient-selected');
    var divDateRange = document.getElementById('recipient-date-range');
    function toggle() {
        var v = sel.value;
        divSelected.classList.toggle('hidden', v !== 'selected');
        divDateRange.classList.toggle('hidden', v !== 'date_range');
    }
    sel.addEventListener('change', toggle);
    toggle();

    var search = document.getElementById('user-search');
    var rows = document.querySelectorAll('.user-row');
    if (search) {
        search.addEventListener('input', function() {
            var q = this.value.trim().toLowerCase();
            rows.forEach(function(row) {
                var name = (row.getAttribute('data-name') || '').toLowerCase();
                var email = (row.getAttribute('data-email') || '').toLowerCase();
                var mobile = (row.getAttribute('data-mobile') || '').toLowerCase();
                var show = !q || name.includes(q) || email.includes(q) || mobile.includes(q);
                row.style.display = show ? '' : 'none';
            });
        });
    }
    if (document.querySelector('.persian-date-input')) {
        jQuery('.persian-date-input').persianDatepicker({ format: 'YYYY/MM/DD', persianDigit: true, initialValueType: 'persian' });
    }
});
</script>
@endpush
@endsection
