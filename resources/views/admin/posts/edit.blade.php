@extends('admin.layout')
@section('title', 'ویرایش مطلب')
@section('header', 'ویرایش مطلب')
@php
    $publishedAtIso = old('published_at');
    if ($publishedAtIso === null && $post->published_at) {
        $publishedAtIso = $post->published_at->format('Y-m-d H:i:s');
    }
    $publishedAtIso = $publishedAtIso ?? '';
    $publishedAtDisplay = '';
    if ($publishedAtIso !== '') {
        try {
            $publishedAtDisplay = \App\Helpers\Jalali::formatFa(\Carbon\Carbon::parse($publishedAtIso), 'Y/m/d H:i');
        } catch (\Throwable $e) {
        }
    }
@endphp
@section('content')
@php
    $canMod = auth()->user()->hasPermission('posts_moderate');
    $pub = $post->publicationStatus();
    $statusBadge = match ($pub) {
        'published' => ['badge-published', 'منتشرشده'],
        'scheduled' => ['badge-scheduled', 'زمان‌بندی‌شده'],
        'pending_review' => ['badge-pending', 'در انتظار بازبینی'],
        'approved' => ['badge-approved', 'تایید شده'],
        'rejected' => ['badge-rejected', 'رد شده'],
        default => ['badge-draft', 'پیش‌نویس'],
    };
@endphp
<div class="admin-page">
    <div class="admin-page-head">
        <div>
            <h1 class="admin-page-title">ویرایش مطلب</h1>
            <p class="admin-section-lead" style="margin-top:0.35rem">
                وضعیت فعلی: <span class="badge {{ $statusBadge[0] }}">{{ $statusBadge[1] }}</span>
            </p>
        </div>
        <div class="admin-page-tools">
            <a href="{{ route('admin.posts.preview', $post) }}" target="_blank"
               class="btn btn-secondary"
               style="display:inline-flex;align-items:center;gap:0.4rem;">
                <x-core-ui::icon name="file-text" style="width:.9rem;height:.9rem" />
                پیش‌نمایش
            </a>
            <a href="{{ route('admin.posts.revisions', $post) }}" class="btn btn-secondary">تاریخچه نسخه‌ها</a>
        </div>
    </div>
    @if($canMod && $pub === 'pending_review')
    <div class="admin-moderation-bar">
        <span class="text-sm font-medium">این مطلب در صف بازبینی است.</span>
        <form method="post" action="{{ route('admin.posts.approve', $post) }}" class="inline">@csrf<button type="submit" class="btn btn-primary btn-sm">تایید محتوا</button></form>
        <form method="post" action="{{ route('admin.posts.reject', $post) }}" class="inline">@csrf<button type="submit" class="btn btn-danger btn-sm">رد</button></form>
    </div>
    @endif
    @include('admin.partials.ai-seo-widget', [
        'aiWidgetId'     => 'post-edit',
        'aiType'         => 'article',
        'aiTitleId'      => 'post-title',
        'aiBodyId'       => 'post-body-editor',
        'aiExcerptId'    => 'post-excerpt',
        'aiMetaTitleId'  => 'post-meta-title',
        'aiMetaDescId'   => 'post-meta-desc',
        'aiTagsId'       => 'post-tags',
        'aiUseTinymce'   => true,
    ])

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.posts.update', $post) }}" method="post" class="admin-form-long">
                @csrf
                @method('PUT')

                <fieldset class="admin-fieldset">
                    <legend class="admin-fieldset__legend">عنوان، دسته و برچسب</legend>
                    <div class="form-group">
                        <label class="form-label" for="post-title">عنوان</label>
                        <input id="post-title" type="text" name="title" value="{{ old('title', $post->title) }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="post-slug">نامک</label>
                        <input id="post-slug" type="text" name="slug" value="{{ old('slug', $post->slug) }}" autocomplete="off">
                        <span class="admin-field-hint">پیشنهاد می‌شود پس از انتشار، نامک را ثابت نگه دارید تا SEO آسیب نبیند.</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="post-category">دسته‌بندی</label>
                        <select id="post-category" name="category_id">
                            <option value="">—</option>
                            @foreach($categories as $c)
                                <option value="{{ $c->id }}" @if(old('category_id', $post->category_id) == $c->id) selected @endif>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="post-series">مجموعه (سری)</label>
                        <select id="post-series" name="series_id">
                            <option value="">—</option>
                            @foreach($seriesList as $s)
                                <option value="{{ $s->id }}" @selected(old('series_id', $post->series_id) == $s->id)>{{ $s->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="post-part">شمارهٔ قسمت در سری</label>
                        <input id="post-part" type="number" name="part_number" min="1" value="{{ old('part_number', $post->part_number) }}" placeholder="مثلاً ۱">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="post-tags">برچسب‌ها</label>
                        <input id="post-tags" type="text" name="tags_input" value="{{ old('tags_input', $tagsInput) }}" placeholder="با ویرگول یا ، جدا کنید" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="post-excerpt">خلاصه</label>
                        <textarea id="post-excerpt" name="excerpt" rows="3">{{ old('excerpt', $post->excerpt) }}</textarea>
                    </div>
                </fieldset>

                <fieldset class="admin-fieldset">
                    <legend class="admin-fieldset__legend">تصویر، گالری و محتوا</legend>
                    <x-image-picker name="featured_image" :value="old('featured_image', $post->featured_image)" context="featured" label="تصویر شاخص" />
                    <div class="form-group">
                        <label class="form-label" for="post-gallery">گالری تصاویر</label>
                        <textarea id="post-gallery" name="gallery_text" rows="4">{{ old('gallery_text', collect($post->gallery_images ?? [])->implode("\n")) }}</textarea>
                        <span class="admin-field-hint">هر خط یک مسیر فایل.</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="post-body-editor">محتوا</label>
                        <textarea id="post-body-editor" name="body" class="w-full min-h-[200px]">{{ old('body', $post->body) }}</textarea>
                    </div>
                </fieldset>

                <fieldset class="admin-fieldset">
                    <legend class="admin-fieldset__legend">سئو و اشتراک‌گذاری</legend>
                    <div class="form-group">
                        <label class="form-label" for="post-meta-title">عنوان SEO</label>
                        <input id="post-meta-title" type="text" name="meta_title" value="{{ old('meta_title', $post->meta_title) }}" maxlength="255">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="post-meta-desc">توضیحات متا</label>
                        <textarea id="post-meta-desc" name="meta_description" maxlength="500" rows="3">{{ old('meta_description', $post->meta_description) }}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="post-canonical">آدرس کانونیکال (اختیاری)</label>
                        <input id="post-canonical" type="text" name="canonical_url" value="{{ old('canonical_url', $post->canonical_url) }}" maxlength="500" dir="ltr">
                    </div>
                    <x-image-picker name="og_image" :value="old('og_image', $post->og_image)" context="featured" label="تصویر Open Graph (اختیاری)" />
                </fieldset>

                <fieldset class="admin-fieldset">
                    <legend class="admin-fieldset__legend">انتشار و گردش کار</legend>
                    <div class="form-group">
                        <label class="form-label" for="post-published-at-display">زمان انتشار (شمسی)</label>
                        <input type="text" id="post-published-at-display" class="form-input w-full max-w-md" value="{{ $publishedAtDisplay }}" placeholder="۱۴۰۴/۰۱/۰۱ ۱۴:۳۰" autocomplete="off" dir="ltr">
                        <input type="hidden" id="post-published-at" name="published_at" value="{{ $publishedAtIso }}">
                        <span class="admin-field-hint">تقویم و ساعت شمسی؛ مقدار ارسالی برای سرور میلادی (ISO) است و در فهرست ادمین به‌شکل شمسی دیده می‌شود.</span>
                        @if($post->published_at && $post->is_published && $post->published_at->isFuture())
                            <div class="text-sm text-slate-600 mt-1"><span class="badge badge-scheduled">زمان‌بندی‌شده</span></div>
                        @endif
                    </div>
                    @if($canMod)
                    <div class="form-group">
                        <label class="admin-form-inline-check" for="post-published-check">
                            <input id="post-published-check" type="checkbox" name="is_published" value="1" @if(old('is_published', $post->is_published)) checked @endif>
                            انتشار مستقیم (حالت ساده)
                        </label>
                    </div>
                    @endif
                </fieldset>

                <fieldset class="admin-fieldset">
                    <legend class="admin-fieldset__legend">ردیابی</legend>
                    <p class="text-sm text-slate-600 mb-0">
                        تاریخ ثبت: <strong>{{ \App\Helpers\Jalali::formatFa($post->created_at, 'Y/m/d H:i') }}</strong>
                        @if($post->updated_at && $post->updated_at->ne($post->created_at))
                            — آخرین ویرایش رکورد: <strong>{{ \App\Helpers\Jalali::formatFa($post->updated_at, 'Y/m/d H:i') }}</strong>
                        @endif
                    </p>
                </fieldset>

                <div class="admin-form-actions admin-form-actions--sticky">
                    <button type="submit" name="save_action" value="draft" class="btn btn-secondary">ذخیره پیش‌نویس</button>
                    <button type="submit" name="save_action" value="review" class="btn btn-secondary">ارسال برای بازبینی</button>
                    @if($canMod)
                    <button type="submit" name="save_action" value="publish" class="btn btn-primary">انتشار/به‌روزرسانی</button>
                    <button type="submit" name="save_action" value="schedule" class="btn btn-secondary">زمان‌بندی انتشار</button>
                    @endif
                    <a href="{{ route('admin.posts.index') }}" class="btn btn-outline-secondary">انصراف</a>
                </div>
            </form>
        </div>
    </div>
</div>
@include('admin.posts._editor_scripts')
@endsection

