@extends('admin.layout')
@section('title', 'ظˆغŒط±ط§غŒط´ ظ…ط·ظ„ط¨')
@section('header', 'ظˆغŒط±ط§غŒط´ ظ…ط·ظ„ط¨')
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
        'published' => ['badge-published', 'ظ…ظ†طھط´ط±ط´ط¯ظ‡'],
        'scheduled' => ['badge-scheduled', 'ط²ظ…ط§ظ†â€Œط¨ظ†ط¯غŒâ€Œط´ط¯ظ‡'],
        'pending_review' => ['badge-pending', 'ط¯ط± ط§ظ†طھط¸ط§ط± ط¨ط§ط²ط¨غŒظ†غŒ'],
        'approved' => ['badge-approved', 'طھط§غŒغŒط¯ ط´ط¯ظ‡'],
        'rejected' => ['badge-rejected', 'ط±ط¯ ط´ط¯ظ‡'],
        default => ['badge-draft', 'ظ¾غŒط´â€Œظ†ظˆغŒط³'],
    };
@endphp
<div class="admin-page">
    <div class="admin-page-head">
        <div>
            <h1 class="admin-page-title">ظˆغŒط±ط§غŒط´ ظ…ط·ظ„ط¨</h1>
            <p class="admin-section-lead" style="margin-top:0.35rem">
                ظˆط¶ط¹غŒطھ ظپط¹ظ„غŒ: <span class="badge {{ $statusBadge[0] }}">{{ $statusBadge[1] }}</span>
            </p>
        </div>
        <div class="admin-page-tools">
            <a href="{{ route('admin.posts.preview', $post) }}" target="_blank"
               class="btn btn-secondary"
               style="display:inline-flex;align-items:center;gap:0.4rem;">
                <x-core-ui::icon name="file-text" style="width:.9rem;height:.9rem" />
                ظ¾غŒط´â€Œظ†ظ…ط§غŒط´
            </a>
            <a href="{{ route('admin.posts.revisions', $post) }}" class="btn btn-secondary">طھط§ط±غŒط®ع†ظ‡ ظ†ط³ط®ظ‡â€Œظ‡ط§</a>
        </div>
    </div>
    @if($canMod && $pub === 'pending_review')
    <div class="admin-moderation-bar">
        <span class="text-sm font-medium">ط§غŒظ† ظ…ط·ظ„ط¨ ط¯ط± طµظپ ط¨ط§ط²ط¨غŒظ†غŒ ط§ط³طھ.</span>
        <form method="post" action="{{ route('admin.posts.approve', $post) }}" class="inline">@csrf<button type="submit" class="btn btn-primary btn-sm">طھط§غŒغŒط¯ ظ…ط­طھظˆط§</button></form>
        <form method="post" action="{{ route('admin.posts.reject', $post) }}" class="inline">@csrf<button type="submit" class="btn btn-danger btn-sm">ط±ط¯</button></form>
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
                    <legend class="admin-fieldset__legend">ط¹ظ†ظˆط§ظ†طŒ ط¯ط³طھظ‡ ظˆ ط¨ط±ع†ط³ط¨</legend>
                    <div class="form-group">
                        <label class="form-label" for="post-title">ط¹ظ†ظˆط§ظ†</label>
                        <input id="post-title" type="text" name="title" value="{{ old('title', $post->title) }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="post-slug">ظ†ط§ظ…ع©</label>
                        <input id="post-slug" type="text" name="slug" value="{{ old('slug', $post->slug) }}" autocomplete="off">
                        <span class="admin-field-hint">ظ¾غŒط´ظ†ظ‡ط§ط¯ ظ…غŒâ€Œط´ظˆط¯ ظ¾ط³ ط§ط² ط§ظ†طھط´ط§ط±طŒ ظ†ط§ظ…ع© ط±ط§ ط«ط§ط¨طھ ظ†ع¯ظ‡ ط¯ط§ط±غŒط¯ طھط§ SEO ط¢ط³غŒط¨ ظ†ط¨غŒظ†ط¯.</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="post-category">ط¯ط³طھظ‡â€Œط¨ظ†ط¯غŒ</label>
                        <select id="post-category" name="category_id">
                            <option value="">â€”</option>
                            @foreach($categories as $c)
                                <option value="{{ $c->id }}" @if(old('category_id', $post->category_id) == $c->id) selected @endif>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="post-series">ظ…ط¬ظ…ظˆط¹ظ‡ (ط³ط±غŒ)</label>
                        <select id="post-series" name="series_id">
                            <option value="">â€”</option>
                            @foreach($seriesList as $s)
                                <option value="{{ $s->id }}" @selected(old('series_id', $post->series_id) == $s->id)>{{ $s->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="post-part">ط´ظ…ط§ط±ظ‡ظ” ظ‚ط³ظ…طھ ط¯ط± ط³ط±غŒ</label>
                        <input id="post-part" type="number" name="part_number" min="1" value="{{ old('part_number', $post->part_number) }}" placeholder="ظ…ط«ظ„ط§ظ‹ غ±">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="post-tags">ط¨ط±ع†ط³ط¨â€Œظ‡ط§</label>
                        <input id="post-tags" type="text" name="tags_input" value="{{ old('tags_input', $tagsInput) }}" placeholder="ط¨ط§ ظˆغŒط±ع¯ظˆظ„ غŒط§ طŒ ط¬ط¯ط§ ع©ظ†غŒط¯" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="post-excerpt">ط®ظ„ط§طµظ‡</label>
                        <textarea id="post-excerpt" name="excerpt" rows="3">{{ old('excerpt', $post->excerpt) }}</textarea>
                    </div>
                </fieldset>

                <fieldset class="admin-fieldset">
                    <legend class="admin-fieldset__legend">طھطµظˆغŒط±طŒ ع¯ط§ظ„ط±غŒ ظˆ ظ…ط­طھظˆط§</legend>
                    <x-image-picker name="featured_image" :value="old('featured_image', $post->featured_image)" context="featured" label="طھطµظˆغŒط± ط´ط§ط®طµ" />
                    <div class="form-group">
                        <label class="form-label" for="post-gallery">ع¯ط§ظ„ط±غŒ طھطµط§ظˆغŒط±</label>
                        <textarea id="post-gallery" name="gallery_text" rows="4">{{ old('gallery_text', collect($post->gallery_images ?? [])->implode("\n")) }}</textarea>
                        <span class="admin-field-hint">ظ‡ط± ط®ط· غŒع© ظ…ط³غŒط± ظپط§غŒظ„.</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="post-body-editor">ظ…ط­طھظˆط§</label>
                        <textarea id="post-body-editor" name="body" class="w-full min-h-[200px]">{{ old('body', $post->body) }}</textarea>
                    </div>
                </fieldset>

                <fieldset class="admin-fieldset">
                    <legend class="admin-fieldset__legend">ط³ط¦ظˆ ظˆ ط§ط´طھط±ط§ع©â€Œع¯ط°ط§ط±غŒ</legend>
                    <div class="form-group">
                        <label class="form-label" for="post-meta-title">ط¹ظ†ظˆط§ظ† SEO</label>
                        <input id="post-meta-title" type="text" name="meta_title" value="{{ old('meta_title', $post->meta_title) }}" maxlength="255">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="post-meta-desc">طھظˆط¶غŒط­ط§طھ ظ…طھط§</label>
                        <textarea id="post-meta-desc" name="meta_description" maxlength="500" rows="3">{{ old('meta_description', $post->meta_description) }}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="post-canonical">ط¢ط¯ط±ط³ ع©ط§ظ†ظˆظ†غŒع©ط§ظ„ (ط§ط®طھغŒط§ط±غŒ)</label>
                        <input id="post-canonical" type="text" name="canonical_url" value="{{ old('canonical_url', $post->canonical_url) }}" maxlength="500" dir="ltr">
                    </div>
                    <x-image-picker name="og_image" :value="old('og_image', $post->og_image)" context="featured" label="طھطµظˆغŒط± Open Graph (ط§ط®طھغŒط§ط±غŒ)" />
                </fieldset>

                <fieldset class="admin-fieldset">
                    <legend class="admin-fieldset__legend">ط§ظ†طھط´ط§ط± ظˆ ع¯ط±ط¯ط´ ع©ط§ط±</legend>
                    <div class="form-group">
                        <label class="form-label" for="post-published-at-display">ط²ظ…ط§ظ† ط§ظ†طھط´ط§ط± (ط´ظ…ط³غŒ)</label>
                        <input type="text" id="post-published-at-display" class="form-input w-full max-w-md" value="{{ $publishedAtDisplay }}" placeholder="غ±غ´غ°غ´/غ°غ±/غ°غ± غ±غ´:غ³غ°" autocomplete="off" dir="ltr">
                        <input type="hidden" id="post-published-at" name="published_at" value="{{ $publishedAtIso }}">
                        <span class="admin-field-hint">طھظ‚ظˆغŒظ… ظˆ ط³ط§ط¹طھ ط´ظ…ط³غŒط› ظ…ظ‚ط¯ط§ط± ط§ط±ط³ط§ظ„غŒ ط¨ط±ط§غŒ ط³ط±ظˆط± ظ…غŒظ„ط§ط¯غŒ (ISO) ط§ط³طھ ظˆ ط¯ط± ظپظ‡ط±ط³طھ ط§ط¯ظ…غŒظ† ط¨ظ‡â€Œط´ع©ظ„ ط´ظ…ط³غŒ ط¯غŒط¯ظ‡ ظ…غŒâ€Œط´ظˆط¯.</span>
                        @if($post->published_at && $post->is_published && $post->published_at->isFuture())
                            <div class="text-sm text-slate-600 mt-1"><span class="badge badge-scheduled">ط²ظ…ط§ظ†â€Œط¨ظ†ط¯غŒâ€Œط´ط¯ظ‡</span></div>
                        @endif
                    </div>
                    @if($canMod)
                    <div class="form-group">
                        <label class="admin-form-inline-check" for="post-published-check">
                            <input id="post-published-check" type="checkbox" name="is_published" value="1" @if(old('is_published', $post->is_published)) checked @endif>
                            ط§ظ†طھط´ط§ط± ظ…ط³طھظ‚غŒظ… (ط­ط§ظ„طھ ط³ط§ط¯ظ‡)
                        </label>
                    </div>
                    @endif
                </fieldset>

                <fieldset class="admin-fieldset">
                    <legend class="admin-fieldset__legend">ط±ط¯غŒط§ط¨غŒ</legend>
                    <p class="text-sm text-slate-600 mb-0">
                        طھط§ط±غŒط® ط«ط¨طھ: <strong>{{ \App\Helpers\Jalali::formatFa($post->created_at, 'Y/m/d H:i') }}</strong>
                        @if($post->updated_at && $post->updated_at->ne($post->created_at))
                            â€” ط¢ط®ط±غŒظ† ظˆغŒط±ط§غŒط´ ط±ع©ظˆط±ط¯: <strong>{{ \App\Helpers\Jalali::formatFa($post->updated_at, 'Y/m/d H:i') }}</strong>
                        @endif
                    </p>
                </fieldset>

                <div class="admin-form-actions admin-form-actions--sticky">
                    <button type="submit" name="save_action" value="draft" class="btn btn-secondary">ط°ط®غŒط±ظ‡ ظ¾غŒط´â€Œظ†ظˆغŒط³</button>
                    <button type="submit" name="save_action" value="review" class="btn btn-secondary">ط§ط±ط³ط§ظ„ ط¨ط±ط§غŒ ط¨ط§ط²ط¨غŒظ†غŒ</button>
                    @if($canMod)
                    <button type="submit" name="save_action" value="publish" class="btn btn-primary">ط§ظ†طھط´ط§ط±/ط¨ظ‡â€Œط±ظˆط²ط±ط³ط§ظ†غŒ</button>
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

