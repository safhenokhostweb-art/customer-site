@props([
    'name'    => 'image',
    'value'   => '',
    'context' => 'thumbnail',
    'label'   => 'تصویر',
    'help'    => null,
    'required'=> false,
])
@php
    use App\Services\SmartImageUploadService;
    $hint       = SmartImageUploadService::contextHint($context);
    $uploadUrl  = route('admin.image.upload');
    $previewSrc = $value ? \App\Helpers\ImageHelper::url($value) : '';
    $uid        = 'sip_' . str_replace(['.', '-'], '_', $name) . '_' . uniqid();
@endphp

<div class="smart-image-picker" id="{{ $uid }}"
     x-data="smartImagePicker('{{ $uid }}', '{{ $uploadUrl }}', '{{ $context }}')"
     x-on:dragover.prevent="dragActive = true"
     x-on:dragleave.prevent="dragActive = false"
     x-on:drop.prevent="handleDrop($event)">

    @if($label)
    <label class="form-label mb-2 block">
        {{ $label }}
        @if($required)<span class="text-red-500 mr-1">*</span>@endif
        <span class="text-xs font-normal text-slate-400 mr-1">({{ $hint }} پیکسل)</span>
    </label>
    @endif

    {{-- Hidden input (submitted with form) --}}
    <input type="hidden" name="{{ $name }}" id="{{ $uid }}_val" value="{{ $value }}">
    <input type="file"   id="{{ $uid }}_file" accept="image/jpeg,image/png,image/gif,image/webp"
           class="hidden" x-ref="fileInput">

    {{-- Drop zone --}}
    <div class="relative rounded-2xl border-2 transition-colors duration-200 overflow-hidden"
         :class="dragActive
            ? 'border-blue-400 bg-blue-50'
            : (currentSrc ? 'border-slate-200 bg-white' : 'border-dashed border-slate-300 bg-slate-50 hover:border-blue-300 hover:bg-blue-50/30')"
         style="min-height:160px;">

        {{-- Preview image --}}
        <template x-if="currentSrc">
            <div class="relative group">
                <img :src="currentSrc" alt="{{ $label }}" loading="lazy"
                     class="w-full object-cover rounded-2xl"
                     style="max-height:280px; object-position:center;">
                {{-- Hover overlay --}}
                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition-all duration-200 rounded-2xl flex items-center justify-center opacity-0 group-hover:opacity-100">
                    <button type="button"
                            @click="$refs.fileInput.click()"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-white text-slate-800 rounded-xl text-sm font-medium shadow-lg hover:bg-blue-50 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        تغییر تصویر
                    </button>
                    <button type="button"
                            @click="clearImage()"
                            class="mr-2 inline-flex items-center gap-1 px-3 py-2 bg-red-50 text-red-600 rounded-xl text-sm font-medium shadow-lg hover:bg-red-100 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        حذف
                    </button>
                </div>
            </div>
        </template>

        {{-- Empty state --}}
        <template x-if="!currentSrc">
            <div class="flex flex-col items-center justify-center py-10 px-4 cursor-pointer"
                 @click="$refs.fileInput.click()">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-4"
                     style="background:linear-gradient(135deg,#e0f2fe,#dbeafe);">
                    <svg class="w-7 h-7 text-blue-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-slate-700 mb-1">بکشید و رها کنید یا کلیک کنید</p>
                <p class="text-xs text-slate-400">JPEG · PNG · GIF · WebP &nbsp;·&nbsp; حداکثر ۱۰ مگابایت</p>
            </div>
        </template>

        {{-- Upload progress overlay --}}
        <template x-if="uploading">
            <div class="absolute inset-0 bg-white/90 backdrop-blur-sm flex flex-col items-center justify-center rounded-2xl z-10">
                <div class="w-40 mb-4">
                    <div class="h-2 bg-slate-200 rounded-full overflow-hidden">
                        <div class="h-full bg-blue-500 rounded-full transition-all duration-300"
                             :style="'width:' + progress + '%'"></div>
                    </div>
                    <p class="text-center text-xs text-slate-500 mt-2" x-text="progress + '%'"></p>
                </div>
                <p class="text-sm text-slate-600">
                    <span x-show="aiProcessing">هوش مصنوعی در حال بهینه‌سازی...</span>
                    <span x-show="!aiProcessing">در حال آپلود...</span>
                </p>
            </div>
        </template>
    </div>

    {{-- Metadata strip --}}
    <template x-if="meta">
        <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500">
            <span class="flex items-center gap-1">
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                <span x-text="meta.width + '×' + meta.height"></span>
            </span>
            <span class="flex items-center gap-1">
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span x-text="formatBytes(meta.final_size)"></span>
                <span x-show="meta.size_reduction !== '0%'" class="text-green-600 font-medium">
                    (-<span x-text="meta.size_reduction"></span>)
                </span>
            </span>
            <span x-show="meta.format" class="uppercase font-semibold px-1.5 py-0.5 rounded text-white text-[10px]"
                  :class="meta.format === 'webp' ? 'bg-blue-500' : 'bg-slate-400'"
                  x-text="meta.format"></span>
            <span x-show="meta.ai_enhanced" class="flex items-center gap-1 text-violet-600 font-medium">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                بهینه‌شده با AI
            </span>
        </div>
    </template>

    {{-- Error --}}
    <template x-if="errorMsg">
        <p class="mt-2 text-sm text-red-600 flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <span x-text="errorMsg"></span>
        </p>
    </template>

    @if($help)
    <p class="mt-1.5 text-xs text-slate-400">{{ $help }}</p>
    @endif
</div>

@once
@push('scripts')
<script>
function smartImagePicker(uid, uploadUrl, context) {
    return {
        currentSrc:  document.getElementById(uid + '_val')?.value
                     ? (@json($previewSrc || ''))
                     : '',
        uploading:   false,
        progress:    0,
        aiProcessing:false,
        meta:        null,
        errorMsg:    null,
        dragActive:  false,

        handleDrop(e) {
            this.dragActive = false;
            const files = e.dataTransfer?.files;
            if (files && files[0]) this.uploadFile(files[0]);
        },

        clearImage() {
            this.currentSrc = '';
            this.meta       = null;
            this.errorMsg   = null;
            const input = document.getElementById(uid + '_val');
            if (input) input.value = '';
            const fileEl = document.getElementById(uid + '_file');
            if (fileEl) fileEl.value = '';
        },

        uploadFile(file) {
            if (!file) return;
            if (!file.type.startsWith('image/')) {
                this.errorMsg = 'فایل انتخاب‌شده تصویر نیست.';
                return;
            }
            if (file.size > 10 * 1024 * 1024) {
                this.errorMsg = 'حجم فایل بیشتر از ۱۰ مگابایت است.';
                return;
            }

            this.uploading    = true;
            this.progress     = 5;
            this.errorMsg     = null;
            this.meta         = null;
            this.aiProcessing = false;

            const fd = new FormData();
            fd.append('file',    file);
            fd.append('context', context);
            fd.append('_token',  document.querySelector('meta[name="csrf-token"]')?.content || '');

            const xhr = new XMLHttpRequest();
            xhr.open('POST', uploadUrl);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('Accept', 'application/json');

            xhr.upload.onprogress = (e) => {
                if (e.lengthComputable) {
                    this.progress = Math.min(85, Math.round(e.loaded / e.total * 85));
                    if (this.progress > 70) this.aiProcessing = true;
                }
            };

            xhr.onload = () => {
                this.progress = 100;
                try {
                    const data = JSON.parse(xhr.responseText);
                    if (xhr.status >= 200 && xhr.status < 300 && data.path) {
                        const input = document.getElementById(uid + '_val');
                        if (input) input.value = data.path;
                        this.currentSrc = data.url;
                        this.meta       = data;
                    } else {
                        this.errorMsg = data.message || 'خطا در آپلود تصویر.';
                    }
                } catch {
                    this.errorMsg = 'پاسخ سرور نامعتبر است.';
                }
                this.uploading    = false;
                this.aiProcessing = false;
            };

            xhr.onerror = () => {
                this.errorMsg  = 'خطا در ارتباط با سرور.';
                this.uploading = false;
            };

            xhr.send(fd);
        },

        formatBytes(bytes) {
            if (!bytes || bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        },

        init() {
            const fileEl = document.getElementById(uid + '_file');
            if (fileEl) {
                fileEl.addEventListener('change', () => {
                    if (fileEl.files && fileEl.files[0]) {
                        this.uploadFile(fileEl.files[0]);
                        fileEl.value = '';
                    }
                });
            }
        }
    };
}
</script>
@endpush
@endonce
