{{-- نیاز به متغیرهای $goLiveIso و $goLiveDisplay در والد --}}
<div class="form-group">
    <label class="form-label" for="menu-go-live-display">زمان انتشار (شمسی، اختیاری)</label>
    <input type="text" id="menu-go-live-display" class="form-input w-full max-w-md" value="{{ $goLiveDisplay }}" placeholder="۱۴۰۴/۰۶/۱۲ ۱۰:۳۰" autocomplete="off" dir="ltr">
    <input type="hidden" id="menu-go-live-hidden" name="go_live_at" value="{{ $goLiveIso }}">
    <span class="admin-field-hint">برای زمان‌بندی انتشار منو؛ خالی یعنی بدون زمان مشخص. مقدار سرور به‌صورت میلادی ذخیره می‌شود.</span>
</div>
