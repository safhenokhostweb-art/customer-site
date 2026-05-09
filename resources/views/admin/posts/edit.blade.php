@extends('admin.layout')
@section('title', 'ویرایش مطلب')
@section('header', 'ویرایش مطلب')
@section('content')
@php
    $canMod = auth()->user()->hasPermission('posts_moderate');
    $pub = $post->publicationStatus();
@endphp
<div class="admin-page">
    <div class="admin-page-head">
        <h1 class="admin-page-title">ویرایش مطلب</h1>
        <div class="admin-page-tools">
            <a href="{{ route('admin.posts.revisions', $post) }}" class="btn btn-secondary">تاریخچه نسخه‌ها</a>
        </div>
    </div>
    @if($canMod && $pub === 'pending_review')
    <div class="card mb-4">
        <div class="card-body flex flex-wrap gap-2 items-center">
            <span class="text-sm font-medium">این مطلب در صف بازبینی است.</span>
            <form method="post" action="{{ route('admin.posts.approve', $post) }}" class="inline">@csrf<button type="submit" class="btn btn-primary btn-sm">تایید محتوا</button></form>
            <form method="post" action="{{ route('admin.posts.reject', $post) }}" class="inline">@csrf<button type="submit" class="btn btn-danger btn-sm">رد</button></form>
        </div>
    </div>
    @endif
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.posts.update', $post) }}" method="post">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label class="form-label">عنوان</label>
                    <input type="text" name="title" value="{{ old('title', $post->title) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">نامک</label>
                    <input type="text" name="slug" value="{{ old('slug', $post->slug) }}">
                    <p class="text-xs text-slate-500 mt-1">پیشنهاد می‌شود پس از انتشار، نامک را ثابت نگه دارید تا SEO آسیب نبیند.</p>
                </div>
                <div class="form-group">
                    <label class="form-label">دسته‌بندی</label>
                    <select name="category_id">
                        <option value="">—</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}" @if(old('category_id', $post->category_id) == $c->id) selected @endif>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">مجموعه (سری)</label>
                    <select name="series_id">
                        <option value="">—</option>
                        @foreach($seriesList as $s)
                            <option value="{{ $s->id }}" @selected(old('series_id', $post->series_id) == $s->id)>{{ $s->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">شمارهٔ قسمت در سری</label>
                    <input type="number" name="part_number" min="1" value="{{ old('part_number', $post->part_number) }}" placeholder="مثلاً ۱">
                </div>
                <div class="form-group">
                    <label class="form-label">برچسب‌ها</label>
                    <input type="text" name="tags_input" value="{{ old('tags_input', $tagsInput) }}" placeholder="با ویرگول یا ، جدا کنید">
                </div>
                <div class="form-group">
                    <label class="form-label">خلاصه</label>
                    <textarea name="excerpt">{{ old('excerpt', $post->excerpt) }}</textarea>
                </div>
                <x-image-picker name="featured_image" :value="old('featured_image', $post->featured_image)" context="featured" label="تصویر شاخص" />
                <div class="form-group">
                    <label class="form-label">گالری تصاویر</label>
                    <textarea name="gallery_text" rows="4">{{ old('gallery_text', collect($post->gallery_images ?? [])->implode("\n")) }}</textarea>
                    <p class="text-xs text-slate-500 mt-1">هر خط یک مسیر فایل.</p>
                </div>
                <div class="form-group">
                    <label class="form-label">محتوا</label>
                    <textarea id="post-body-editor" name="body" class="w-full min-h-[200px]">{{ old('body', $post->body) }}</textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">عنوان SEO</label>
                    <input type="text" name="meta_title" value="{{ old('meta_title', $post->meta_title) }}" maxlength="255">
                </div>
                <div class="form-group">
                    <label class="form-label">توضیحات متا</label>
                    <textarea name="meta_description" maxlength="500">{{ old('meta_description', $post->meta_description) }}</textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">آدرس کانونیکال (اختیاری)</label>
                    <input type="text" name="canonical_url" value="{{ old('canonical_url', $post->canonical_url) }}" maxlength="500">
                </div>
                <x-image-picker name="og_image" :value="old('og_image', $post->og_image)" context="featured" label="تصویر Open Graph (اختیاری)" />
                <div class="form-group">
                    <label class="form-label">زمان انتشار</label>
                    <input type="datetime-local" name="published_at" value="{{ old('published_at', optional($post->published_at)->format('Y-m-d\TH:i')) }}">
                </div>
                @if($canMod)
                <div class="form-group"><label><input type="checkbox" name="is_published" value="1" @if(old('is_published', $post->is_published)) checked @endif> انتشار مستقیم (حالت ساده)</label></div>
                @endif
                <div class="admin-page-tools flex flex-wrap gap-2">
                    <button type="submit" name="save_action" value="draft" class="btn btn-secondary">ذخیره پیش‌نویس</button>
                    <button type="submit" name="save_action" value="review" class="btn btn-secondary">ارسال برای بازبینی</button>
                    @if($canMod)
                    <button type="submit" name="save_action" value="publish" class="btn btn-primary">انتشار/به‌روزرسانی</button>
                    <button type="submit" name="save_action" value="schedule" class="btn btn-secondary">زمان‌بندی انتشار</button>
                    @endif
                    <a href="{{ route('admin.posts.index') }}" class="btn btn-secondary">انصراف</a>
                </div>
            </form>
        </div>
    </div>
</div>
@include('admin.posts._editor_scripts')
@endsection
