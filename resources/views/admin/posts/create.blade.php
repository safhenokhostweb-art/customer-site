@extends('admin.layout')
@section('title', 'مطلب جدید')
@section('header', 'مطلب جدید')
@section('content')
<div class="admin-page">
    <div class="admin-page-head">
        <h1 class="admin-page-title">مطلب جدید</h1>
    </div>
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.posts.store') }}" method="post">
                @csrf
                <div class="form-group">
                    <label class="form-label">عنوان</label>
                    <input type="text" name="title" value="{{ old('title') }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">نامک (اختیاری)</label>
                    <input type="text" name="slug" value="{{ old('slug') }}">
                    <p class="text-xs text-slate-500 mt-1">فقط حروف، عدد و خط تیره. در صورت خالی بودن، خودکار از عنوان ساخته می‌شود.</p>
                </div>
                <div class="form-group">
                    <label class="form-label">دسته‌بندی</label>
                    <select name="category_id">
                        <option value="">—</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}" {{ old('category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">مجموعه (سری)</label>
                    <select name="series_id">
                        <option value="">—</option>
                        @foreach($seriesList as $s)
                            <option value="{{ $s->id }}" @selected(old('series_id') == $s->id)>{{ $s->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">شمارهٔ قسمت در سری</label>
                    <input type="number" name="part_number" min="1" value="{{ old('part_number') }}" placeholder="مثلاً ۱">
                </div>
                <div class="form-group">
                    <label class="form-label">برچسب‌ها</label>
                    <input type="text" name="tags_input" value="{{ old('tags_input') }}" placeholder="با ویرگول یا ، جدا کنید">
                </div>
                <div class="form-group">
                    <label class="form-label">خلاصه</label>
                    <textarea name="excerpt">{{ old('excerpt') }}</textarea>
                    <p class="text-xs text-slate-500 mt-1">اگر خالی بماند، از متن محتوا خلاصه استاندارد ساخته می‌شود.</p>
                </div>
                <x-image-picker name="featured_image" :value="old('featured_image')" context="featured" label="تصویر شاخص" />
                <div class="form-group">
                    <label class="form-label">گالری تصاویر</label>
                    <textarea name="gallery_text" rows="4" placeholder="هر خط یک مسیر فایل (پس از آپلود در شاخص یا رسانه)">{{ old('gallery_text') }}</textarea>
                    <p class="text-xs text-slate-500 mt-1">مسیر هر تصویر را در یک خط جداگانه وارد کنید.</p>
                </div>
                <div class="form-group">
                    <label class="form-label">محتوا</label>
                    <textarea id="post-body-editor" name="body" class="w-full min-h-[200px]">{{ old('body') }}</textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">عنوان SEO</label>
                    <input type="text" name="meta_title" value="{{ old('meta_title') }}" maxlength="255">
                </div>
                <div class="form-group">
                    <label class="form-label">توضیحات متا</label>
                    <textarea name="meta_description" maxlength="500">{{ old('meta_description') }}</textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">آدرس کانونیکال (اختیاری)</label>
                    <input type="text" name="canonical_url" value="{{ old('canonical_url') }}" maxlength="500" placeholder="https://... یا /blog/...">
                </div>
                <x-image-picker name="og_image" :value="old('og_image')" context="featured" label="تصویر Open Graph (اختیاری)" />
                <div class="form-group">
                    <label class="form-label">زمان انتشار (اختیاری)</label>
                    <input type="datetime-local" name="published_at" value="{{ old('published_at') }}">
                    <p class="text-xs text-slate-500 mt-1">برای زمان‌بندی انتشار از این فیلد به همراه دکمه «زمان‌بندی انتشار» استفاده کنید.</p>
                </div>
                @if(auth()->user()->hasPermission('posts_moderate'))
                <div class="form-group"><label><input type="checkbox" name="is_published" value="1" {{ old('is_published') ? 'checked' : '' }}> انتشار مستقیم (حالت ساده)</label></div>
                @endif
                <div class="admin-page-tools flex flex-wrap gap-2">
                    <button type="submit" name="save_action" value="draft" class="btn btn-secondary">ذخیره پیش‌نویس</button>
                    <button type="submit" name="save_action" value="review" class="btn btn-secondary">ارسال برای بازبینی</button>
                    @if(auth()->user()->hasPermission('posts_moderate'))
                    <button type="submit" name="save_action" value="publish" class="btn btn-primary">انتشار فوری</button>
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
