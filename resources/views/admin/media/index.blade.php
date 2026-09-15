@extends('admin.layout')

@section('title', 'کتابخانه رسانه')

@section('content')
<div class="admin-page" id="media-library-root"
    x-data="mediaLibrary({
        endpoints: {
            index: @js(route('admin.media.index')),
            store: @js(route('admin.media.store')),
            show: @js(route('admin.media.show', ['media' => '__UUID__'])),
            update: @js(route('admin.media.update', ['media' => '__UUID__'])),
            destroy: @js(route('admin.media.destroy', ['media' => '__UUID__'])),
            replace: @js(route('admin.media.replace', ['media' => '__UUID__'])),
            editImage: @js(route('admin.media.edit-image', ['media' => '__UUID__'])),
            batchDestroy: @js(route('admin.media.batch-destroy')),
        },
        labels: {
            uploadFailed: 'بارگذاری ناموفق بود.',
            deleteBlocked: 'این رسانه در حال استفاده است و حذف نشد.',
            replaceFailed: 'جایگزینی فایل ناموفق بود.',
            editFailed: 'ویرایش تصویر ناموفق بود.',
            someBlocked: 'برخی موارد در حال استفاده هستند و حذف نشدند.',
        },
    })"
    x-cloak
>
    <div class="admin-page-head">
        <div>
            <h1 class="admin-page-title">کتابخانه رسانه</h1>
            <p style="margin:6px 0 0;color:#6b7280;font-size:13px;">رسانه‌های این سایت؛ مستقل از سایر سایت‌ها</p>
        </div>
    </div>

    @if(session('success'))<div class="card" style="padding:10px 14px;margin-bottom:12px;background:#ecfdf5;color:#047857;border-color:#a7f3d0;">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="card" style="padding:10px 14px;margin-bottom:12px;background:#fef2f2;color:#b91c1c;border-color:#fecaca;">{{ session('error') }}</div>@endif

    {{-- Drop zone / upload --}}
    <div class="card media-dropzone"
        :class="{ 'is-dragover': dragOver }"
        x-on:dragover.prevent="dragOver = true"
        x-on:dragleave.prevent="dragOver = false"
        x-on:drop="onDrop($event)"
    >
        <p style="margin:0 0 8px;font-weight:600;">فایل‌ها را اینجا رها کنید یا انتخاب کنید</p>
        <p style="margin:0 0 12px;color:#6b7280;font-size:12px;">تصویر (JPEG/PNG/GIF/WebP)، ویدئو (MP4/WebM)، صوت (MP3/OGG/WAV)، سند (PDF)</p>
        <label class="btn btn-secondary" style="cursor:pointer;">
            انتخاب فایل‌ها
            <input type="file" name="files" multiple class="sr-only-file-input"
                accept="image/jpeg,image/png,image/gif,image/webp,video/mp4,video/webm,audio/mpeg,audio/ogg,audio/wav,application/pdf"
                x-on:change="onFileInput($event)" aria-label="انتخاب فایل‌های رسانه برای بارگذاری">
        </label>
    </div>

    {{-- Upload queue --}}
    <div class="upload-queue" x-show="uploads.length > 0" x-cloak>
        <template x-for="upload in uploads" :key="upload.id">
            <div class="upload-row">
                <span class="upload-name" x-text="upload.name"></span>
                <div class="upload-progress" role="progressbar" :aria-valuenow="upload.progress" aria-valuemin="0" aria-valuemax="100">
                    <div class="upload-progress-bar" :style="`width:${upload.progress}%`" :class="`status-${upload.status.toLowerCase()}`"></div>
                </div>
                <span class="upload-status" :class="`status-${upload.status.toLowerCase()}`" x-text="{
                    QUEUED: 'در صف',
                    UPLOADING: 'در حال بارگذاری…',
                    PROCESSING: 'در حال پردازش…',
                    SUCCESS: 'موفق',
                    FAILED: upload.message || 'ناموفق',
                }[upload.status]"></span>
                <button type="button" class="upload-dismiss" x-on:click="dismissUpload(upload.id)" aria-label="حذف از فهرست">✕</button>
            </div>
        </template>
    </div>

    {{-- Toolbar --}}
    <div class="card media-toolbar">
        <input type="search" name="q" x-model="q" placeholder="جستجو در عنوان، نام فایل یا توضیح" class="media-search" aria-label="جستجوی رسانه">
        <select name="category" x-model="category" aria-label="فیلتر نوع فایل">
            <option value="">همه انواع</option>
            <option value="image">تصویر</option>
            <option value="video">ویدئو</option>
            <option value="audio">صوت</option>
            <option value="document">سند</option>
        </select>
        <select x-model="usage" aria-label="فیلتر وضعیت استفاده">
            <option value="">همه رسانه‌ها</option>
            <option value="used">در حال استفاده</option>
            <option value="unused">بدون استفاده</option>
        </select>
        <select x-model="sort" aria-label="مرتب‌سازی">
            <option value="newest">جدیدترین</option>
            <option value="oldest">قدیمی‌ترین</option>
            <option value="name">نام</option>
            <option value="size">حجم فایل</option>
        </select>
        <div class="media-view-toggle" role="group" aria-label="نمای نمایش">
            <button type="button" :class="{active: view==='grid'}" x-on:click="setView('grid')" aria-label="نمای شبکه‌ای">▦</button>
            <button type="button" :class="{active: view==='list'}" x-on:click="setView('list')" aria-label="نمای فهرستی">☰</button>
        </div>
        <button type="button" class="btn btn-danger" x-show="selected.length > 0" x-on:click="batchDelete()">
            حذف موارد انتخاب‌شده (<span x-text="selected.length"></span>)
        </button>
    </div>

    <div x-show="loading" class="admin-empty">در حال بارگذاری…</div>

    {{-- Grid view --}}
    <div class="media-grid" x-show="!loading && view==='grid'" x-cloak>
        <template x-for="item in items" :key="item.uuid">
            <article class="media-card" :data-uuid="item.uuid" :class="{selected: isSelected(item.uuid)}">
                <label class="media-select">
                    <input type="checkbox" :checked="isSelected(item.uuid)" x-on:change="toggleSelect(item.uuid)" :aria-label="'انتخاب ' + (item.title || item.original_filename)">
                </label>
                <button type="button" class="media-preview" x-on:click="openDetails(item.uuid)">
                    <template x-if="item.category === 'image'"><img :src="item.preview_url" :alt="item.title || ''" loading="lazy"></template>
                    <template x-if="item.category === 'video'"><span class="media-type-badge">ویدئو</span></template>
                    <template x-if="item.category === 'audio'"><span class="media-type-badge">صوت</span></template>
                    <template x-if="item.category === 'document'"><span class="media-type-badge">PDF</span></template>
                </button>
                <div class="media-card-body">
                    <strong x-text="item.title || item.original_filename" :title="item.title || item.original_filename"></strong>
                    <div class="media-meta">
                        <span x-text="formatSize(item.size_bytes)"></span>
                        <span :class="item.usages_count ? 'usage-used' : 'usage-free'" x-text="item.usages_count ? 'در حال استفاده' : 'بدون استفاده'"></span>
                    </div>
                </div>
            </article>
        </template>
        <div class="card admin-empty" style="grid-column:1/-1;" x-show="items.length === 0">رسانه‌ای یافت نشد.</div>
    </div>

    {{-- List view --}}
    <div class="card media-list-wrap" x-show="!loading && view==='list'" x-cloak>
        <table class="media-list">
            <thead>
                <tr><th></th><th>پیش‌نمایش</th><th>نام</th><th>نوع</th><th>حجم</th><th>ابعاد</th><th>استفاده</th><th>تاریخ</th></tr>
            </thead>
            <tbody>
                <template x-for="item in items" :key="item.uuid">
                    <tr>
                        <td><input type="checkbox" :checked="isSelected(item.uuid)" x-on:change="toggleSelect(item.uuid)" :aria-label="'انتخاب ' + (item.title || item.original_filename)"></td>
                        <td>
                            <button type="button" class="media-list-thumb" x-on:click="openDetails(item.uuid)">
                                <template x-if="item.category === 'image'"><img :src="item.preview_url" :alt="item.title || ''" loading="lazy"></template>
                                <template x-if="item.category !== 'image'"><span x-text="item.category"></span></template>
                            </button>
                        </td>
                        <td><a href="#" x-on:click.prevent="openDetails(item.uuid)" x-text="item.title || item.original_filename"></a></td>
                        <td x-text="item.mime_type"></td>
                        <td x-text="formatSize(item.size_bytes)"></td>
                        <td x-text="item.width ? `${item.width}×${item.height}` : '—'"></td>
                        <td><span :class="item.usages_count ? 'usage-used' : 'usage-free'" x-text="item.usages_count ? 'در حال استفاده' : 'بدون استفاده'"></span></td>
                        <td x-text="new Date(item.created_at).toLocaleDateString('fa-IR')"></td>
                    </tr>
                </template>
                <tr x-show="items.length === 0"><td colspan="8" class="admin-empty">رسانه‌ای یافت نشد.</td></tr>
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="admin-pagination" x-show="pagination.last_page > 1" x-cloak>
        <button type="button" class="btn btn-secondary" :disabled="pagination.current_page <= 1" x-on:click="goToPage(pagination.current_page - 1)">قبلی</button>
        <span x-text="`صفحه ${pagination.current_page} از ${pagination.last_page} (${pagination.total} مورد)`"></span>
        <button type="button" class="btn btn-secondary" :disabled="pagination.current_page >= pagination.last_page" x-on:click="goToPage(pagination.current_page + 1)">بعدی</button>
    </div>

    {{-- Details modal --}}
    <div class="media-modal-backdrop" x-show="detail" x-cloak x-on:keydown.escape.window="closeDetails()">
        <div class="media-modal" role="dialog" aria-modal="true" aria-label="جزئیات رسانه" x-show="detail" @click.outside="closeDetails()">
            <button type="button" class="media-modal-close" x-on:click="closeDetails()" aria-label="بستن">✕</button>
            <h2 class="admin-page-title" style="font-size:16px;margin:0 0 4px;">جزئیات رسانه</h2>
            <template x-if="detail">
                <div class="media-modal-grid">
                    <section class="media-modal-preview">
                        <template x-if="detail.category === 'image' && !imageEditing">
                            <img :src="detail.preview_url" :alt="detail.alt_text || ''" style="max-width:100%;max-height:420px;object-fit:contain;">
                        </template>
                        <template x-if="detail.category === 'image' && imageEditing">
                            <div class="crop-wrap"
                                x-on:mousedown="startCrop($event)"
                                x-on:mousemove="dragCrop($event)"
                                x-on:mouseup="endCrop()"
                                x-on:mouseleave="endCrop()"
                            >
                                <img :src="detail.preview_url" :alt="detail.alt_text || ''" x-ref="cropImg" style="max-width:100%;max-height:420px;display:block;user-select:none;" draggable="false">
                                <div class="crop-box" x-show="cropBox" :style="cropBox ? `left:${cropBox.left}px;top:${cropBox.top}px;width:${cropBox.width}px;height:${cropBox.height}px` : ''"></div>
                            </div>
                        </template>
                        <template x-if="detail.category === 'video'"><video :src="detail.preview_url" controls preload="metadata" style="max-width:100%;max-height:420px;"></video></template>
                        <template x-if="detail.category === 'audio'"><audio :src="detail.preview_url" controls></audio></template>
                        <template x-if="detail.category === 'document'"><a class="btn btn-primary" :href="detail.preview_url" target="_blank" rel="noopener noreferrer">باز کردن PDF</a></template>

                        <dl class="media-detail-facts">
                            <dt>نام فایل</dt><dd x-text="detail.original_filename"></dd>
                            <dt>نوع</dt><dd x-text="detail.mime_type"></dd>
                            <dt>حجم</dt><dd x-text="formatSize(detail.size_bytes)"></dd>
                            <template x-if="detail.width"><dt>ابعاد</dt></template>
                            <template x-if="detail.width"><dd x-text="`${detail.width}×${detail.height}`"></dd></template>
                            <dt>تاریخ بارگذاری</dt><dd x-text="new Date(detail.created_at).toLocaleString('fa-IR')"></dd>
                            <dt>شناسه</dt><dd style="font-family:monospace;font-size:11px;" x-text="detail.uuid"></dd>
                        </dl>

                        <template x-if="detail.category === 'image'">
                            <div class="media-image-tools">
                                <button type="button" class="btn btn-secondary" x-on:click="rotateImage(90)" :disabled="imageEditing">چرخش ۹۰°</button>
                                <button type="button" class="btn btn-secondary" x-on:click="rotateImage(-90)" :disabled="imageEditing">چرخش معکوس</button>
                                <button type="button" class="btn btn-secondary" x-on:click="imageEditing = !imageEditing" x-text="imageEditing ? 'لغو برش' : 'برش تصویر'"></button>
                                <button type="button" class="btn btn-primary" x-show="imageEditing && cropBox" x-on:click="applyCrop($refs.cropImg)">اعمال برش</button>
                            </div>
                        </template>

                        <label class="media-replace-label">
                            جایگزینی فایل
                            <input type="file" class="sr-only-file-input" x-on:change="onReplaceFile($event)" :disabled="replacing">
                        </label>
                        <p style="font-size:12px;color:#b45309;margin:4px 0 0;">توجه: جایگزینی فایل، محتوای منتشرشده‌ای که از این رسانه استفاده می‌کند را نیز تغییر می‌دهد.</p>
                    </section>

                    <section class="media-modal-meta">
                        <form x-on:submit.prevent="saveMetadata()">
                            <label>عنوان<input name="title" x-model="metadataForm.title" maxlength="255"></label>
                            <label>متن جایگزین (alt)<input name="alt_text" x-model="metadataForm.alt_text" maxlength="500"></label>
                            <label>عنوان کوتاه (caption)<textarea name="caption" x-model="metadataForm.caption" rows="2"></textarea></label>
                            <label>توضیح کامل<textarea name="description" x-model="metadataForm.description" rows="4"></textarea></label>
                            <button type="submit" class="btn btn-primary">ذخیره اطلاعات</button>
                        </form>

                        <hr>
                        <h2>وضعیت استفاده</h2>
                        <template x-if="detail.usages && detail.usages.length">
                            <ul class="media-usage-list">
                                <template x-for="usage in detail.usages" :key="usage.reference_path">
                                    <li x-text="`${usage.consumer_type} · ${usage.reference_path}`"></li>
                                </template>
                            </ul>
                        </template>
                        <p x-show="!detail.usages || detail.usages.length === 0" style="color:#047857;font-size:13px;">این رسانه بدون استفاده است و می‌تواند حذف شود.</p>

                        <button type="button" class="btn btn-danger" x-on:click="deleteCurrent()" :disabled="detail.usages && detail.usages.length > 0">حذف رسانه</button>
                    </section>
                </div>
            </template>
        </div>
    </div>
</div>

<style>
[x-cloak]{display:none!important}
.media-dropzone{padding:24px;text-align:center;border:2px dashed #d1d5db;margin-bottom:16px;transition:border-color .15s,background .15s}
.media-dropzone.is-dragover{border-color:#4f46e5;background:#eef2ff}
.sr-only-file-input{position:absolute;width:1px;height:1px;overflow:hidden;opacity:0;clip:rect(0,0,0,0)}
.upload-queue{display:grid;gap:8px;margin-bottom:16px}
.upload-row{display:flex;align-items:center;gap:10px;background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:8px 12px;font-size:13px}
.upload-name{min-width:120px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.upload-progress{flex:1;height:6px;background:#f3f4f6;border-radius:99px;overflow:hidden}
.upload-progress-bar{height:100%;background:#4f46e5;transition:width .2s}
.upload-progress-bar.status-success{background:#059669}
.upload-progress-bar.status-failed{background:#dc2626}
.upload-status{min-width:110px;font-size:12px;color:#6b7280}
.upload-status.status-success{color:#047857}
.upload-status.status-failed{color:#b91c1c}
.upload-dismiss{border:0;background:none;cursor:pointer;color:#9ca3af;font-size:14px}
.media-toolbar{display:flex;gap:10px;align-items:center;flex-wrap:wrap;padding:12px 16px;margin-bottom:16px}
.media-toolbar input,.media-toolbar select{padding:9px;border:1px solid #d1d5db;border-radius:7px}
.media-search{min-width:220px;flex:1}
.media-view-toggle{display:flex;border:1px solid #d1d5db;border-radius:7px;overflow:hidden}
.media-view-toggle button{border:0;background:#fff;padding:8px 12px;cursor:pointer}
.media-view-toggle button.active{background:#4f46e5;color:#fff}
.media-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px}
.media-card{position:relative;background:#fff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;box-shadow:0 1px 2px rgb(0 0 0 / .03)}
.media-card.selected{outline:2px solid #4f46e5}
.media-select{position:absolute;inset-inline-start:8px;top:8px;z-index:2;background:rgba(255,255,255,.85);border-radius:6px;padding:2px 4px}
.media-preview{width:100%;height:140px;background:#f3f4f6;display:flex;align-items:center;justify-content:center;color:#4b5563;overflow:hidden;border:0;padding:0;cursor:pointer}
.media-preview img{width:100%;height:100%;object-fit:cover}
.media-type-badge{font-size:12px;font-weight:600;color:#6b7280}
.media-card-body{padding:10px 12px;display:grid;gap:6px}
.media-card-body strong{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-size:13px}
.media-meta{display:flex;justify-content:space-between;font-size:11px;color:#6b7280}
.usage-used{color:#b45309}
.usage-free{color:#047857}
.media-list-wrap{overflow-x:auto;padding:0}
.media-list{width:100%;border-collapse:collapse;font-size:13px}
.media-list th,.media-list td{padding:10px 12px;border-bottom:1px solid #f3f4f6;text-align:right;white-space:nowrap}
.media-list-thumb{width:44px;height:44px;border:0;background:#f3f4f6;border-radius:6px;overflow:hidden;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:11px;color:#6b7280}
.media-list-thumb img{width:100%;height:100%;object-fit:cover}
.admin-pagination{display:flex;gap:12px;align-items:center;justify-content:center;margin-top:16px;font-size:13px}
.media-modal-backdrop{position:fixed;inset:0;background:rgba(15,23,42,.55);display:flex;align-items:center;justify-content:center;z-index:100;padding:16px}
.media-modal{background:#fff;border-radius:12px;max-width:920px;width:100%;max-height:90vh;overflow-y:auto;position:relative;padding:20px}
.media-modal-close{position:absolute;inset-inline-end:12px;top:12px;border:0;background:#f3f4f6;border-radius:99px;width:32px;height:32px;cursor:pointer;font-size:14px}
.media-modal-grid{display:grid;grid-template-columns:minmax(0,1.2fr) minmax(240px,1fr);gap:20px;margin-top:8px}
.media-detail-facts{display:grid;grid-template-columns:auto 1fr;gap:6px 12px;font-size:12px;margin:14px 0}
.media-image-tools{display:flex;gap:8px;flex-wrap:wrap;margin:10px 0}
.crop-wrap{position:relative;cursor:crosshair;display:inline-block}
.crop-box{position:absolute;border:2px dashed #4f46e5;background:rgba(79,70,229,.15);pointer-events:none}
.media-replace-label{display:inline-block;margin-top:12px;padding:8px 14px;border:1px solid #d1d5db;border-radius:7px;cursor:pointer;font-size:13px}
.media-modal-meta label{display:block;margin-bottom:12px;font-size:13px;font-weight:600}
.media-modal-meta input,.media-modal-meta textarea{width:100%;margin-top:5px;padding:9px;border:1px solid #d1d5db;border-radius:7px;font-weight:400}
.media-usage-list{list-style:none;margin:0;padding:0;font-size:12px}
.media-usage-list li{padding:6px 8px;background:#fffbeb;border-radius:6px;margin-bottom:6px}
@media(max-width:760px){.media-modal-grid{grid-template-columns:1fr}}
@media(max-width:390px){.media-grid{grid-template-columns:1fr}}
</style>
@endsection
