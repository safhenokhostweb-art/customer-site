@extends('admin.layout')
@section('title', 'ایجاد فرم جدید')
@section('header', 'ایجاد فرم جدید')
@section('content')

<div class="admin-page">
    <div class="admin-page-head">
        <h1 class="admin-page-title">ایجاد فرم جدید</h1>
        <div class="admin-page-tools">
            <a href="{{ route('admin.forms.index') }}" class="btn btn-outline-secondary btn-sm">بازگشت</a>
        </div>
    </div>

    <div class="card max-w-3xl">
        <div class="card-body">
            @if ($errors->any())
                <div class="bg-red-50 text-red-800 border border-red-200 p-3 rounded-lg mb-4 text-sm">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('admin.forms.store') }}" class="admin-form-stack">
                @csrf

                <fieldset class="admin-fieldset">
                    <legend class="admin-fieldset__legend">اطلاعات اصلی</legend>
                    <div class="form-group">
                        <label class="form-label" for="form-title">عنوان فرم</label>
                        <input id="form-title" type="text" name="title" class="w-full border rounded-lg px-3 py-2" value="{{ old('title') }}" required placeholder="مثلاً درخواست همکاری">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="form-slug">نامک URL (اختیاری)</label>
                        <input id="form-slug" type="text" name="slug" class="w-full border rounded-lg px-3 py-2 font-mono text-sm" dir="ltr" value="{{ old('slug') }}" placeholder="همکاری — در صورت خالی، خودکار ساخته می‌شود">
                        <span class="admin-field-hint">فقط حروف، عدد و خط تیره؛ برای آدرس عمومی <code dir="ltr">/forms/نامک</code></span>
                    </div>
                    <label class="admin-form-inline-check">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                        فرم در سایت عمومی فعال باشد
                    </label>
                </fieldset>

                <fieldset class="admin-fieldset">
                    <legend class="admin-fieldset__legend">متن بالای فرم (اختیاری)</legend>
                    <div class="form-group">
                        <label class="form-label" for="form-intro">راهنما / توضیح کوتاه</label>
                        <textarea id="form-intro" name="intro_text" class="w-full border rounded-lg px-3 py-2 min-h-[200px]" placeholder="مثلاً مدارک لازم، زمان پاسخ‌گویی...">{{ old('intro_text') }}</textarea>
                    </div>
                </fieldset>

                <fieldset class="admin-fieldset">
                    <legend class="admin-fieldset__legend">سئو (اختیاری)</legend>
                    <div class="form-group">
                        <label class="form-label" for="form-meta-title">عنوان متا</label>
                        <input id="form-meta-title" type="text" name="meta_title" class="w-full border rounded-lg px-3 py-2" value="{{ old('meta_title') }}" maxlength="255">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="form-meta-desc">توضیح متا</label>
                        <textarea id="form-meta-desc" name="meta_description" rows="2" class="w-full border rounded-lg px-3 py-2" maxlength="500">{{ old('meta_description') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="form-canonical">Canonical (اختیاری)</label>
                        <input id="form-canonical" type="text" name="canonical_url" class="w-full border rounded-lg px-3 py-2 font-mono text-sm" dir="ltr" value="{{ old('canonical_url') }}" maxlength="500">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="form-robots">Robots (اختیاری)</label>
                        <input id="form-robots" type="text" name="meta_robots" class="w-full border rounded-lg px-3 py-2 font-mono text-sm" dir="ltr" value="{{ old('meta_robots') }}" maxlength="80" placeholder="index,follow یا noindex,nofollow">
                    </div>
                </fieldset>

                <div class="admin-form-actions">
                    <button type="submit" class="btn btn-primary">ذخیره و رفتن به فیلدها</button>
                    <a href="{{ route('admin.forms.index') }}" class="btn btn-outline-secondary">انصراف</a>
                </div>
            </form>
        </div>
    </div>
</div>
@include('admin.partials.tinymce-field-init', ['selector' => '#form-intro', 'uploadContext' => 'featured', 'height' => 320])
@endsection
