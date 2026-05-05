@props([
    'name' => 'image',
    'value' => '',
    'context' => 'thumbnail',
    'label' => 'تصویر',
])
@php
    $contextSizes = [
        'avatar' => '100×100',
        'thumbnail' => '200×200',
        'featured' => '800×600',
        'product' => '400×400',
        'course' => '400×300',
        'icon' => '64×64',
        'messaging' => '128×128',
        'advertisement' => '728×90',
    ];
    $hint = $contextSizes[$context] ?? '200×200';
@endphp
<div class="form-group image-picker-wrap" id="picker-{{ $name }}" data-context="{{ $context }}" data-name="{{ $name }}">
    <label class="form-label">{{ $label }} ({{ $hint }} پیکسل)</label>
    <div class="flex flex-wrap items-center gap-4">
        <div class="image-preview border border-slate-200 rounded-lg overflow-hidden bg-slate-50" style="width:120px;height:120px;display:flex;align-items:center;justify-content:center;">
            <span class="text-slate-400 text-sm {{ $value ? 'hidden' : '' }}" id="preview-placeholder-{{ $name }}">بدون تصویر</span>
            <img src="{{ $value ? \App\Helpers\ImageHelper::url($value) : '' }}" alt="" class="w-full h-full object-cover {{ $value ? '' : 'hidden' }}" id="preview-{{ $name }}">
        </div>
        <div>
            <input type="hidden" name="{{ $name }}" id="input-{{ $name }}" value="{{ $value }}">
            <input type="file" accept="image/jpeg,image/png,image/gif,image/webp" id="file-{{ $name }}" class="hidden">
            <button type="button" class="btn btn-secondary btn-sm image-picker-browse">انتخاب از کامپیوتر</button>
        </div>
    </div>
</div>
<script>
(function(){
    var wrap = document.getElementById('picker-{{ $name }}');
    if (!wrap) return;
    var name = '{{ $name }}';
    var input = document.getElementById('input-' + name);
    var fileInput = document.getElementById('file-' + name);
    var preview = document.getElementById('preview-' + name);
    var placeholder = document.getElementById('preview-placeholder-' + name);
    var browseBtn = wrap.querySelector('.image-picker-browse');
    var context = '{{ $context }}';
    if (!browseBtn || !fileInput) return;
    browseBtn.addEventListener('click', function() { fileInput.click(); });
    fileInput.addEventListener('change', function() {
        if (!this.files || !this.files[0]) return;
        var fd = new FormData();
        fd.append('file', this.files[0]);
        fd.append('context', context);
        fd.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
        browseBtn.disabled = true;
        browseBtn.textContent = 'در حال آپلود...';
        fetch('{{ route("admin.image.upload") }}', {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (data.path) {
                input.value = data.path;
                if (preview) {
                    preview.src = data.url;
                    preview.classList.remove('hidden');
                    if (placeholder) placeholder.classList.add('hidden');
                }
                var msg = document.createElement('span');
                msg.className = 'text-green-600 text-sm font-medium';
                msg.textContent = 'ذخیره موفقیت‌آمیز';
                var btnParent = browseBtn.parentElement;
                if (btnParent) btnParent.appendChild(msg);
                else wrap.appendChild(msg);
                setTimeout(function(){ msg.remove(); }, 2500);
            } else {
                alert(data.message || 'خطا در آپلود');
            }
        }).catch(function() { alert('خطا در آپلود'); }).finally(function() {
            browseBtn.disabled = false;
            browseBtn.textContent = 'انتخاب از کامپیوتر';
            fileInput.value = '';
        });
    });
})();
</script>
