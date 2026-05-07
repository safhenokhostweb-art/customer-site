@extends('admin.layout')
@section('title', 'پاسخگویی شبکه‌های اجتماعی')
@section('header', 'پاسخگویی از طریق شبکه‌های اجتماعی')
@section('content')
<div class="admin-page max-w-3xl">
    <div class="card mb-6">
        <div class="card-header">لوگوی ویجت صفحهٔ اصلی</div>
        <div class="card-body">
            <p class="admin-section-lead mb-4">لوگوی دکمهٔ ارتباط در گوشهٔ صفحه. در صورت خالی بودن، آیکون پیش‌فرض نمایش داده می‌شود.</p>
            <form action="{{ route('admin.messaging.widget-logo') }}" method="post">
                @csrf
                @include('components.image-picker', ['name' => 'widget_logo', 'value' => $widgetLogo ?? '', 'context' => 'icon', 'label' => 'لوگوی ویجت'])
                <div class="admin-page-tools mt-2">
                    <button type="submit" class="btn btn-primary">ذخیره لوگو</button>
                </div>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-header">کانال‌های ارتباطی</div>
        <div class="card-body">
            <p class="admin-section-lead mb-6">کاربران با کلیک روی لوگوی ویجت، شبکهٔ مورد نظر (واتساپ، تلگرام، ایتا، بله، سروش، گپ، آی‌گپ، روبیکا یا شبکهٔ سفارشی) را انتخاب و چت را شروع می‌کنند.</p>

            <div class="space-y-4 mb-6">
                @foreach($channels as $ch)
                    <div class="flex flex-wrap items-center justify-between gap-4 p-4 border border-slate-200 rounded-lg">
                        <div class="flex items-center gap-3">
                            <span class="font-medium">{{ $platforms[$ch->platform]['name'] ?? $ch->platform }}</span>
                            @if($ch->platform === 'whatsapp')
                                <span class="text-sm text-slate-500">{{ $ch->phone ?: '—' }}</span>
                            @else
                                <span class="text-sm text-slate-500 truncate max-w-[200px]">{{ $ch->url ?: '—' }}</span>
                            @endif
                            @if($ch->is_active)
                                <span class="badge badge-success">فعال</span>
                            @else
                                <span class="badge badge-danger">غیرفعال</span>
                            @endif
                        </div>
                        <div class="flex gap-2">
                            <a href="{{ route('admin.messaging.edit', $ch) }}" class="btn btn-primary btn-sm">ویرایش</a>
                            <form method="post" action="{{ route('admin.messaging.destroy', $ch) }}" class="inline" onsubmit="return confirm('حذف این کانال؟');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <hr class="my-6">
            <h3 class="font-semibold mb-4">افزودن کانال</h3>
            <form action="{{ route('admin.messaging.store') }}" method="post" class="admin-form-stack">
                @csrf
                <div class="form-group">
                    <label class="form-label">شبکه</label>
                    <select name="platform" id="new-platform" required>
                        <option value="">انتخاب کنید</option>
                        @foreach($platforms as $key => $p)
                            <option value="{{ $key }}">{{ $p['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="whatsapp-fields" class="hidden">
                    <div class="form-group">
                        <label class="form-label">شماره (با کد کشور، بدون +)</label>
                        <input type="text" name="phone" placeholder="989123456789" dir="ltr">
                    </div>
                    <div class="form-group">
                        <label class="form-label">پیام پیش‌فرض</label>
                        <input type="text" name="message" placeholder="سلام">
                    </div>
                </div>
                <div id="url-field" class="form-group hidden">
                    <label class="form-label">لینک یا آیدی</label>
                    <input type="text" name="url" placeholder="https:// یا آیدی کاربر (مثلاً @username برای تلگرام)">
                </div>
                <div id="custom-fields" class="hidden space-y-3">
                    <div class="form-group">
                        <label class="form-label">عنوان نمایشی</label>
                        <input type="text" name="custom_label" placeholder="نام شبکهٔ سفارشی">
                    </div>
                    @include('components.image-picker', ['name' => 'custom_image', 'value' => '', 'context' => 'icon', 'label' => 'لوگو'])
                </div>
                <div class="form-group">
                    <label class="admin-form-inline-check cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" checked>
                        <span>فعال</span>
                    </label>
                </div>
                <div class="admin-page-tools">
                    <button type="submit" class="btn btn-primary">افزودن کانال</button>
                </div>
            </form>
        </div>
    </div>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var platformSelect = document.getElementById('new-platform');
    if (!platformSelect) return;
    function toggleMessagingFields() {
        var v = platformSelect.value;
        document.getElementById('whatsapp-fields').classList.toggle('hidden', v !== 'whatsapp');
        document.getElementById('url-field').classList.toggle('hidden', v === 'whatsapp' || !v);
        document.getElementById('custom-fields').classList.toggle('hidden', v !== 'custom');
    }
    platformSelect.addEventListener('change', toggleMessagingFields);
    toggleMessagingFields();
});
</script>
@endpush
@endsection
