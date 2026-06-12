@extends('admin.layout')
@section('title', 'ظ…ط·ظ„ط¨ ط¬ط¯غŒط¯')
@section('header', 'ظ…ط·ظ„ط¨ ط¬ط¯غŒط¯')
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
        <h1 class="admin-page-title">ظ…ط·ظ„ط¨ ط¬ط¯غŒط¯</h1>
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
                    <legend class="admin-fieldset__legend">ط¹ظ†ظˆط§ظ†طŒ ط¯ط³طھظ‡ ظˆ ط¨ط±ع†ط³ط¨</legend>
                    <div class="form-group">
                        <label class="form-label" for="post-title">ط¹ظ†ظˆط§ظ†</label>
                        <input id="post-title" type="text" name="title" value="{{ old('title') }}" required aria-required="true">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="post-slug">ظ†ط§ظ…ع© (ط§ط®طھغŒط§ط±غŒ)</label>
                        <input id="post-slug" type="text" name="slug" value="{{ old('slug') }}" autocomplete="off">
                        <span class="admin-field-hint">ظپظ‚ط· ط­ط±ظˆظپطŒ ط¹ط¯ط¯ ظˆ ط®ط· طھغŒط±ظ‡. ط¯ط± طµظˆط±طھ ط®ط§ظ„غŒ ط¨ظˆط¯ظ†طŒ ط®ظˆط¯ع©ط§ط± ط§ط² ط¹ظ†ظˆط§ظ† ط³ط§ط®طھظ‡ ظ…غŒâ€Œط´ظˆط¯.</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="post-category">ط¯ط³طھظ‡â€Œط¨ظ†ط¯غŒ</label>
                        <select id="post-category" name="category_id">
                            <option value="">â€”</option>
                            @foreach($categories as $c)
                                <option value="{{ $c->id }}" {{ old('category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="post-series">ظ…ط¬ظ…ظˆط¹ظ‡ (ط³ط±غŒ)</label>
                        <select id="post-series" name="series_id">
                            <option value="">â€”</option>
                            @foreach($seriesList as $s)
                                <option value="{{ $s->id }}" @selected(old('series_id') == $s->id)>{{ $s->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="post-part">ط´ظ…ط§ط±ظ‡ظ” ظ‚ط³ظ…طھ ط¯ط± ط³ط±غŒ</label>
                        <input id="post-part" type="number" name="part_number" min="1" value="{{ old('part_number') }}" placeholder="ظ…ط«ظ„ط§ظ‹ غ±">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="post-tags">ط¨ط±ع†ط³ط¨â€Œظ‡ط§</label>
                        <input id="post-tags" type="text" name="tags_input" value="{{ old('tags_input') }}" placeholder="ط¨ط§ ظˆغŒط±ع¯ظˆظ„ غŒط§ طŒ ط¬ط¯ط§ ع©ظ†غŒط¯" autocomplete="off">
                        <span class="admin-field-hint">ط¨ط±ع†ط³ط¨â€Œظ‡ط§ ط¯ط± ظپظ‡ط±ط³طھ ظ…ط·ظ„ط¨ ظˆ ط¨ط±ط§غŒ ظپغŒظ„طھط± ظ†ظ…ط§غŒط´ ط¯ط§ط¯ظ‡ ظ…غŒâ€Œط´ظˆظ†ط¯.</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="post-excerpt">ط®ظ„ط§طµظ‡</label>
                        <textarea id="post-excerpt" name="excerpt" rows="3">{{ old('excerpt') }}</textarea>
                        <span class="admin-field-hint">ط§ع¯ط± ط®ط§ظ„غŒ ط¨ظ…ط§ظ†ط¯طŒ ط§ط² ظ…طھظ† ظ…ط­طھظˆط§ ط®ظ„ط§طµظ‡ ط§ط³طھط§ظ†ط¯ط§ط±ط¯ ط³ط§ط®طھظ‡ ظ…غŒâ€Œط´ظˆط¯.</span>
                    </div>
                </fieldset>

                <fieldset class="admin-fieldset">
                    <legend class="admin-fieldset__legend">طھطµظˆغŒط±طŒ ع¯ط§ظ„ط±غŒ ظˆ ظ…ط­طھظˆط§</legend>
                    <x-image-picker name="featured_image" :value="old('featured_image')" context="featured" label="طھطµظˆغŒط± ط´ط§ط®طµ" />
                    <div class="form-group">
                        <label class="form-label" for="post-gallery">ع¯ط§ظ„ط±غŒ طھطµط§ظˆغŒط±</label>
                        <textarea id="post-gallery" name="gallery_text" rows="4" placeholder="ظ‡ط± ط®ط· غŒع© ظ…ط³غŒط± ظپط§غŒظ„ (ظ¾ط³ ط§ط² ط¢ظ¾ظ„ظˆط¯ ط¯ط± ط´ط§ط®طµ غŒط§ ط±ط³ط§ظ†ظ‡)">{{ old('gallery_text') }}</textarea>
                        <span class="admin-field-hint">ظ…ط³غŒط± ظ‡ط± طھطµظˆغŒط± ط±ط§ ط¯ط± غŒع© ط®ط· ط¬ط¯ط§ع¯ط§ظ†ظ‡ ظˆط§ط±ط¯ ع©ظ†غŒط¯.</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="post-body-editor">ظ…ط­طھظˆط§</label>
                        <textarea id="post-body-editor" name="body" class="w-full min-h-[200px]">{{ old('body') }}</textarea>
                    </div>
                </fieldset>

                <fieldset class="admin-fieldset">
                    <legend class="admin-fieldset__legend">ط³ط¦ظˆ ظˆ ط§ط´طھط±ط§ع©â€Œع¯ط°ط§ط±غŒ</legend>
                    <div class="form-group">
                        <label class="form-label" for="post-meta-title">ط¹ظ†ظˆط§ظ† SEO</label>
                        <input id="post-meta-title" type="text" name="meta_title" value="{{ old('meta_title') }}" maxlength="255">
                        <span class="admin-field-hint">ط¨ط±ط§غŒ ظ†طھط§غŒط¬ ط¬ط³طھط¬ظˆط› ط¨ظ‡طھط± ط§ط³طھ ظ…ظ†ط­طµط± ط¨ظ‡ ظپط±ط¯ ظˆ ظ†ط²ط¯غŒع© ط¨ظ‡ ط¹ظ†ظˆط§ظ† ظ…ط·ظ„ط¨ ط¨ط§ط´ط¯.</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="post-meta-desc">طھظˆط¶غŒط­ط§طھ ظ…طھط§</label>
                        <textarea id="post-meta-desc" name="meta_description" maxlength="500" rows="3">{{ old('meta_description') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="post-canonical">ط¢ط¯ط±ط³ ع©ط§ظ†ظˆظ†غŒع©ط§ظ„ (ط§ط®طھغŒط§ط±غŒ)</label>
                        <input id="post-canonical" type="text" name="canonical_url" value="{{ old('canonical_url') }}" maxlength="500" placeholder="https://... غŒط§ /blog/..." dir="ltr">
                    </div>
                    <x-image-picker name="og_image" :value="old('og_image')" context="featured" label="طھطµظˆغŒط± Open Graph (ط§ط®طھغŒط§ط±غŒ)" />
                </fieldset>

                <fieldset class="admin-fieldset">
                    <legend class="admin-fieldset__legend">ط§ظ†طھط´ط§ط± ظˆ ع¯ط±ط¯ط´ ع©ط§ط±</legend>
                    <div class="form-group">
                        <label class="form-label" for="post-published-at-display">ط²ظ…ط§ظ† ط§ظ†طھط´ط§ط± (ط´ظ…ط³غŒطŒ ط§ط®طھغŒط§ط±غŒ)</label>
                        <input type="text" id="post-published-at-display" class="form-input w-full max-w-md" value="{{ $publishedAtDisplay }}" placeholder="غ±غ´غ°غ´/غ°غ±/غ°غ± غ±غ´:غ³غ°" autocomplete="off" dir="ltr">
                        <input type="hidden" id="post-published-at" name="published_at" value="{{ $publishedAtIso }}">
                        <span class="admin-field-hint">طھظ‚ظˆغŒظ… ظˆ ط³ط§ط¹طھ ط´ظ…ط³غŒط› ط¨ط±ط§غŒ ط²ظ…ط§ظ†â€Œط¨ظ†ط¯غŒ ط§ط² ط§غŒظ† ظپغŒظ„ط¯ ظ‡ظ…ط±ط§ظ‡ ط¨ط§ ط¯ع©ظ…ظ‡ آ«ط²ظ…ط§ظ†â€Œط¨ظ†ط¯غŒ ط§ظ†طھط´ط§ط±آ» ط§ط³طھظپط§ط¯ظ‡ ع©ظ†غŒط¯. ظ…ظ‚ط¯ط§ط± ط§ط±ط³ط§ظ„غŒ ط¨ط±ط§غŒ ط³ط±ظˆط± ط¨ظ‡â€Œطµظˆط±طھ ظ…غŒظ„ط§ط¯غŒ (ISO) ط°ط®غŒط±ظ‡ ظ…غŒâ€Œط´ظˆط¯.</span>
                    </div>
                    @if(auth()->user()->hasPermission('posts_moderate'))
                    <div class="form-group">
                        <label class="admin-form-inline-check" for="post-published-check">
                            <input id="post-published-check" type="checkbox" name="is_published" value="1" {{ old('is_published') ? 'checked' : '' }}>
                            ط§ظ†طھط´ط§ط± ظ…ط³طھظ‚غŒظ… (ط­ط§ظ„طھ ط³ط§ط¯ظ‡)
                        </label>
                    </div>
                    @endif
                </fieldset>

                <input type="hidden" name="preview_after_save" id="preview_after_save_flag" value="0">
                <div class="admin-form-actions admin-form-actions--sticky">
                    <button type="submit" name="save_action" value="draft" class="btn btn-secondary">ط°ط®غŒط±ظ‡ ظ¾غŒط´â€Œظ†ظˆغŒط³</button>
                    <button type="submit" name="save_action" value="draft" class="btn btn-secondary"
                            onclick="document.getElementById('preview_after_save_flag').value='1'"
                            style="display:inline-flex;align-items:center;gap:0.4rem;">
                        <x-core-ui::icon name="file-text" style="width:.9rem;height:.9rem" />
                        ط°ط®غŒط±ظ‡ ظˆ ظ¾غŒط´â€Œظ†ظ…ط§غŒط´
                    </button>
                    <button type="submit" name="save_action" value="review" class="btn btn-secondary">ط§ط±ط³ط§ظ„ ط¨ط±ط§غŒ ط¨ط§ط²ط¨غŒظ†غŒ</button>
                    @if(auth()->user()->hasPermission('posts_moderate'))
                    <button type="submit" name="save_action" value="publish" class="btn btn-primary">ط§ظ†طھط´ط§ط± ظپظˆط±غŒ</button>
                    <button type="submit" name="save_action" value="schedule" class="btn btn-secondary">ط²ظ…ط§ظ†â€Œط¨ظ†ط¯غŒ ط§ظ†طھط´ط§ط±</button>
                    @endif
                    <a href="{{ route('admin.posts.index') }}" class="btn btn-outline-secondary">ط§ظ†طµط±ط§ظپ</a>
                </div>
            </form>
        </div>
    </div>
</div>
@include('admin.posts._editor_scripts')
@endsection

