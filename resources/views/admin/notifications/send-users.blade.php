@extends('admin.layout')
@section('title', 'ارسال اعلان به کاربران')
@section('header', 'ارسال اعلان به کاربران')

@section('content')
<div class="admin-page">
    <div class="admin-page-head">
        <p class="text-slate-600 text-sm">اعلان در صندوق ورودی «حساب من» برای کاربران نمایش داده می‌شود.</p>
        <div class="admin-page-tools">
            <a href="{{ route('admin.notifications.archive') }}" class="btn btn-secondary btn-sm">آرشیو اعلان‌ها</a>
        </div>
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

                    <div id="recipient-selected" class="form-group {{ old('recipient_type') === 'selected' ? '' : 'hidden' }}" @if(old('recipient_type') !== 'selected') hidden @endif>
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

                    <div id="recipient-date-range" class="grid grid-cols-1 md:grid-cols-2 gap-4 form-group {{ old('recipient_type') === 'date_range' ? '' : 'hidden' }}" @if(old('recipient_type') !== 'date_range') hidden @endif>
                        <div>
                            <label class="form-label">ثبت‌نام از تاریخ (شمسی)</label>
                            <x-persian-date-input :defer-global-init="true" name="registered_from" :value="old('registered_from')" placeholder="۱۴۰۳/۰۱/۰۱" class="form-control" />
                        </div>
                        <div>
                            <label class="form-label">ثبت‌نام تا تاریخ (شمسی)</label>
                            <x-persian-date-input :defer-global-init="true" name="registered_to" :value="old('registered_to')" placeholder="۱۴۰۳/۱۲/۲۹" class="form-control" />
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
                    @if(\App\Helpers\SiteHelper::moduleEnabled('sms'))
                    <div class="form-group">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="hidden" name="send_sms" value="0">
                            <input type="checkbox" name="send_sms" value="1" class="rounded" {{ old('send_sms') ? 'checked' : '' }}>
                            <span>ارسال خلاصهٔ متن به موبایل کاربران (نیازمند تنظیم درگاه پیامک در بخش پیامک)</span>
                        </label>
                        <p class="text-slate-500 text-xs mt-1">فقط برای کاربرانی که موبایل معتبر دارند؛ متن کوتاه از عنوان و بدنه ساخته می‌شود.</p>
                    </div>
                    @endif
                    <div class="admin-page-tools mt-6">
                        <button type="submit" class="btn btn-primary">ارسال اعلان</button>
                        <a href="{{ route('admin.notifications.archive') }}" class="btn btn-secondary">آرشیو</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var sel = document.getElementById('recipient_type');
    var divSelected = document.getElementById('recipient-selected');
    var divDateRange = document.getElementById('recipient-date-range');
    function toggle() {
        var v = sel.value;
        var showSel = v === 'selected';
        var showDates = v === 'date_range';
        divSelected.classList.toggle('hidden', !showSel);
        divSelected.hidden = !showSel;
        divDateRange.classList.toggle('hidden', !showDates);
        divDateRange.hidden = !showDates;
        if (showDates) {
            if (typeof window.promoteDeferredPersianInputs === 'function') {
                window.promoteDeferredPersianInputs(divDateRange);
            }
            if (typeof window.initPersianDateInputs === 'function') {
                window.initPersianDateInputs(divDateRange);
            }
        }
    }
    if (sel) {
        sel.addEventListener('change', toggle);
        toggle();
    }

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
});
</script>
@endpush
@endsection
