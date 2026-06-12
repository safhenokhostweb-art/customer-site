@extends('admin.layout')
@section('title', 'مطلب جدید')
@section('header', 'مطلب جدید')
@php
    $publishedAtIso = old('published_at') ?? '';
    $publishedAtDisplay = '';
    if ($publishedAtIso !== '') {
        try {
            $publishedAtDisplay = \App\Helpers\Jalali::formatFa(\Carbon\Carbon::parse($publishedAtIso), 'Y/m/d H:i');
        } catch (\Throwable $e) {
        }
    }
@endphp
@section('content')
<div class="admin-page">
    <div class="admin-page-head">
        <h1 class="admin-page-title">مطلب جدید</h1>
    </div>

    @include('admin.partials.ai-seo-widget', [
        'aiWidgetId'     => 'post-create',
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
            <form action="{{ route('admin.posts.store') }}" method="post" class="admin-form-long">
                @csrf
                <fieldset class="admin-fieldset">
                    <legend class="admin-fieldset__legend">عنوان، دسته و برچسب</legend>
                    <div class="form-group">
                        <label class="form-label" for="post-title">عنوان</label>
                        <input id="post-title" type="text" name="title" value="{{ old('title') }}" required aria-required="true">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="post-slug">نامک (اختیاری)</label>
                        <input id="post-slug" type="text" name="slug" value="{{ old('slug') }}" autocomplete="off">
                        <span class="admin-field-hint">فقط حروف، عدد و خط تیره. در صورت خالی بودن، خودکار از عنوان ساخته می‌شود.</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="post-category">دسته‌بندی</label>
                        <select id="post-category" name="category_id">
                            <option value="">—</option>
                            @foreach($categories as $c)
                                <option value="{{ $c->id }}" {{ old('category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="post-series">مجموعه (سری)</label>
                        <select id="post-series" name="series_id">
                            <option value="">—</option>
                            @foreach($seriesList as $s)
                                <option value="{{ $s->id }}" @selected(old('series_id') == $s->id)>{{ $s->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="post-part">شمارهٔ قسمت در سری</label>
                        <input id="post-part" type="number" name="part_number" min="1" value="{{ old('part_number') }}" placeholder="مثلاً ۱">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="post-tags">برچسب‌ها</label>
                        <input id="post-tags" type="text" name="tags_input" value="{{ old('tags_input') }}" placeholder="با ویرگول یا ، جدا کنید" autocomplete="off">
                        <span class="admin-field-hint">برچسب‌ها در فهرست مطلب و برای فیلتر نمایش داده می‌شوند.</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="post-excerpt">خلاصه</label>
                        <textarea id="post-excerpt" name="excerpt" rows="3">{{ old('excerpt') }}</textarea>
                        <span class="admin-field-hint">اگر خالی بماند، از متن محتوا خلاصه استاندارد ساخته می‌شود.</span>
                    </div>
                </fieldset>

                <fieldset class="admin-fieldset">
                    <legend class="admin-fieldset__legend">تصویر، گالری و محتوا</legend>
                    <x-image-picker name="featured_image" :value="old('featured_image')" context="featured" label="تصویر شاخص" />
                    <div class="form-group">
                        <label class="form-label" for="post-gallery">گالری تصاویر</label>
                        <textarea id="post-gallery" name="gallery_text" rows="4" placeholder="هر خط یک مسیر فایل (پس از آپلود در شاخص یا رسانه)">{{ old('gallery_text') }}</textarea>
                        <span class="admin-field-hint">مسیر هر تصویر را در یک خط جداگانه وارد کنید.</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="post-body-editor">محتوا</label>
                        <textarea id="post-body-editor" name="body" class="w-full min-h-[200px]">{{ old('body') }}</textarea>
                    </div>
                </fieldset>

                <fieldset class="admin-fieldset">
                    <legend class="admin-fieldset__legend">سئو و اشتراک‌گذاری</legend>
                    <div class="form-group">
                        <label class="form-label" for="post-meta-title">عنوان SEO</label>
                        <input id="post-meta-title" type="text" name="meta_title" value="{{ old('meta_title') }}" maxlength="255">
                        <span class="admin-field-hint">برای نتایج جستجو؛ بهتر است منحصر به فرد و نزدیک به عنوان مطلب باشد.</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="post-meta-desc">توضیحات متا</label>
                        <textarea id="post-meta-desc" name="meta_description" maxlength="500" rows="3">{{ old('meta_description') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="post-canonical">آدرس کانونیکال (اختیاری)</label>
                        <input id="post-canonical" type="text" name="canonical_url" value="{{ old('canonical_url') }}" maxlength="500" placeholder="https://... یا /blog/..." dir="ltr">
                    </div>
                    <x-image-picker name="og_image" :value="old('og_image')" context="featured" label="تصویر Open Graph (اختیاری)" />
                </fieldset>

                <fieldset class="admin-fieldset">
                    <legend class="admin-fieldset__legend">انتشار و گردش کار</legend>
                    <div class="form-group">
                        <label class="form-label" for="post-published-at-display">زمان انتشار (شمسی، اختیاری)</label>
                        <input type="text" id="post-published-at-display" class="form-input w-full max-w-md" value="{{ $publishedAtDisplay }}" placeholder="۱۴۰۴/۰۱/۰۱ ۱۴:۳۰" autocomplete="off" dir="ltr">
                        <input type="hidden" id="post-published-at" name="published_at" value="{{ $publishedAtIso }}">
                        <span class="admin-field-hint">تقویم و ساعت شمسی؛ برای زمان‌بندی از این فیلد همراه با دکمه «زمان‌بندی انتشار» استفاده کنید. مقدار ارسالی برای سرور به‌صورت میلادی (ISO) ذخیره می‌شود.</span>
                    </div>
                    @if(auth()->user()->hasPermission('posts_moderate'))
                    <div class="form-group">
                        <label class="admin-form-inline-check" for="post-published-check">
                            <input id="post-published-check" type="checkbox" name="is_published" value="1" {{ old('is_published') ? 'checked' : '' }}>
                            انتشار مستقیم (حالت ساده)
                        </label>
                    </div>
                    @endif
                </fieldset>

                <input type="hidden" name="preview_after_save" id="preview_after_save_flag" value="0">
                <div class="admin-form-actions admin-form-actions--sticky">
                    <button type="submit" name="save_action" value="draft" class="btn btn-secondary">ذخیره پیش‌نویس</button>
                    <button type="submit" name="save_action" value="draft" class="btn btn-secondary"
                            onclick="document.getElementById('preview_after_save_flag').value='1'"
                            style="display:inline-flex;align-items:center;gap:0.4rem;">
                        <x-core-ui::icon name="file-text" style="width:.9rem;height:.9rem" />
                        ذخیره و پیش‌نمایش
                    </button>
                    <button type="submit" name="save_action" value="review" class="btn btn-secondary">ارسال برای بازبینی</button>
                    @if(auth()->user()->hasPermission('posts_moderate'))
                    <button type="submit" name="save_action" value="publish" class="btn btn-primary">انتشار فوری</button>
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

