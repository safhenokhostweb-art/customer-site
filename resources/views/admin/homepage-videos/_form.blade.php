@php
    $v = $video ?? null;
    $isEdit = isset($isEdit) && $isEdit;
@endphp

<fieldset class="admin-fieldset">
    <legend class="admin-fieldset__legend">اطلاعات ویدیو</legend>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="form-group">
            <label class="form-label">عنوان (اختیاری)</label>
            <input type="text" name="title" value="{{ old('title', $v->title ?? '') }}" maxlength="255" placeholder="مثلاً: ویدیوی معرفی محصول">
        </div>
        <div class="form-group">
            <label class="form-label">زیرعنوان (اختیاری)</label>
            <input type="text" name="subtitle" value="{{ old('subtitle', $v->subtitle ?? '') }}" maxlength="500">
        </div>
    </div>

    <div class="form-group">
        <label class="form-label">نوع ویدیو *</label>
        <select name="video_type" id="video_type_sel" onchange="updateUrlHint()">
            <option value="youtube"    {{ old('video_type', $v->video_type ?? 'youtube') === 'youtube'    ? 'selected' : '' }}>YouTube</option>
            <option value="mp4"        {{ old('video_type', $v->video_type ?? '') === 'mp4'        ? 'selected' : '' }}>فایل مستقیم (MP4/WebM)</option>
            <option value="cloudflare" {{ old('video_type', $v->video_type ?? '') === 'cloudflare' ? 'selected' : '' }}>Cloudflare Stream</option>
        </select>
    </div>

    <div class="form-group">
        <label class="form-label">آدرس ویدیو *</label>
        <input type="text" name="video_url" dir="ltr" required
               value="{{ old('video_url', $v->video_url ?? '') }}"
               id="video_url_input" placeholder="https://www.youtube.com/watch?v=...">
        <span class="admin-field-hint" id="video_url_hint">لینک کامل ویدیوی YouTube را وارد کنید.</span>
    </div>
</fieldset>

<fieldset class="admin-fieldset">
    <legend class="admin-fieldset__legend">تصویر پیش‌نمایش و پوشش</legend>

    <x-smart-image-picker
        name="thumbnail_url"
        :value="old('thumbnail_url', $v->thumbnail_url ?? '')"
        context="video_thumb"
        label="تصویر Thumbnail (اختیاری)"
        help="اگر خالی باشد، YouTube thumbnail خودکار استفاده می‌شود." />

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
        <div class="form-group">
            <label class="form-label">متن روی ویدیو (اختیاری)</label>
            <input type="text" name="overlay_text" value="{{ old('overlay_text', $v->overlay_text ?? '') }}" maxlength="255">
        </div>
        <div class="form-group">
            <label class="form-label">شفافیت پوشش (۰–۱۰۰)</label>
            <input type="number" name="overlay_opacity" value="{{ old('overlay_opacity', $v->overlay_opacity ?? 30) }}" min="0" max="100">
        </div>
    </div>
</fieldset>

<fieldset class="admin-fieldset">
    <legend class="admin-fieldset__legend">دکمه فراخوانی (CTA)</legend>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="form-group">
            <label class="form-label">متن دکمه</label>
            <input type="text" name="cta_text" value="{{ old('cta_text', $v->cta_text ?? '') }}" maxlength="100" placeholder="بیشتر بدانید">
        </div>
        <div class="form-group">
            <label class="form-label">لینک دکمه</label>
            <input type="text" name="cta_url" dir="ltr" value="{{ old('cta_url', $v->cta_url ?? '') }}" maxlength="500" placeholder="/shop">
        </div>
    </div>
</fieldset>

<fieldset class="admin-fieldset">
    <legend class="admin-fieldset__legend">تنظیمات پخش</legend>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="hidden" name="autoplay" value="0">
            <input type="checkbox" name="autoplay" value="1" class="checkbox"
                   {{ old('autoplay', $v->autoplay ?? false) ? 'checked' : '' }}>
            <span>اتوپلی</span>
        </label>
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="hidden" name="loop" value="0">
            <input type="checkbox" name="loop" value="1" class="checkbox"
                   {{ old('loop', $v->loop ?? false) ? 'checked' : '' }}>
            <span>پخش حلقه</span>
        </label>
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="hidden" name="muted" value="0">
            <input type="checkbox" name="muted" value="1" class="checkbox"
                   {{ old('muted', $v->muted ?? true) ? 'checked' : '' }}>
            <span>بی‌صدا</span>
        </label>
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="hidden" name="show_controls" value="0">
            <input type="checkbox" name="show_controls" value="1" class="checkbox"
                   {{ old('show_controls', $v->show_controls ?? true) ? 'checked' : '' }}>
            <span>نوار کنترل</span>
        </label>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
        <div class="form-group">
            <label class="form-label">ترتیب نمایش</label>
            <input type="number" name="sort_order" value="{{ old('sort_order', $v->sort_order ?? 0) }}" min="0">
        </div>
        <div class="form-group">
            <label class="form-label">وضعیت</label>
            <select name="is_active">
                <option value="1" {{ old('is_active', $v->is_active ?? true) ? 'selected' : '' }}>فعال</option>
                <option value="0" {{ !old('is_active', $v->is_active ?? true) ? 'selected' : '' }}>غیرفعال</option>
            </select>
        </div>
    </div>
</fieldset>

@push('scripts')
<script>
function updateUrlHint() {
    var sel = document.getElementById('video_type_sel');
    var hint = document.getElementById('video_url_hint');
    var input = document.getElementById('video_url_input');
    if (!sel || !hint) return;
    var hints = {
        youtube:    'لینک کامل ویدیوی YouTube را وارد کنید. مثال: https://youtu.be/abc123',
        mp4:        'آدرس کامل فایل MP4 یا WebM را وارد کنید. مثال: https://cdn.example.com/video.mp4',
        cloudflare: 'آدرس Cloudflare Stream (iframe URL). مثال: https://customer-xx.cloudflarestream.com/abc/iframe',
    };
    hint.textContent = hints[sel.value] || '';
    if (input) input.placeholder = hints[sel.value]?.split('مثال: ')[1]?.split(' ')[0] || '';
}
document.addEventListener('DOMContentLoaded', updateUrlHint);
</script>
@endpush
