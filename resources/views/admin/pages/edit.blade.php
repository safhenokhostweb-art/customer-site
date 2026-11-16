@extends('admin.layout')
@section('title', 'تنظیمات صفحه')
@section('header', 'تنظیمات صفحه')
@section('content')
@php $usesBuilder = $page->usesBuilder(); @endphp
<div class="admin-page">
    <div class="admin-page-head">
        <h1 class="admin-page-title">تنظیمات صفحه: {{ $page->title }}</h1>
        <div class="admin-page-tools">
            <a href="{{ route('admin.theme-design.canvas-edit', ['page' => $page->slug]) }}" class="btn btn-primary btn-sm">ویرایش محتوای صفحه در قالب‌ساز</a>
        </div>
    </div>
    @include('admin.partials.ai-seo-widget', [
        'aiWidgetId'   => 'pageEdit',
        'aiType'       => 'article',
        'aiTitleId'    => 'page-title-edit',
        'aiBodyId'     => 'page-body-editor',
        'aiUseTinymce' => true,
    ])

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.pages.update', $page) }}" method="post">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label class="form-label">عنوان</label>
                    <input type="text" id="page-title-edit" name="title" value="{{ old('title', $page->title) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">نامک</label>
                    <input type="text" name="slug" value="{{ old('slug', $page->slug) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">محتوا</label>
                    @if($usesBuilder)
                        {{-- Builder Commercial Hardening (Phase B): once a page has
                             real content in builder_content_v2, the public renderer
                             (Page::usesBuilder()) ignores `body` permanently — this
                             textarea used to still accept edits here with zero
                             visible effect (a silent no-op control). The one
                             canonical content editor for this page is now the
                             Builder; PageController::update() also refuses to
                             persist `body` for a builder-type page server-side, not
                             just here in the UI. --}}
                        <div class="px-4 py-3 rounded-lg border border-amber-200 bg-amber-50 text-amber-800 text-sm">
                            محتوای این صفحه از طریق
                            <a href="{{ route('admin.theme-design.canvas-edit', ['page' => $page->slug]) }}" class="font-semibold underline">قالب‌ساز بصری Canvas</a>
                            مدیریت می‌شود. این فیلد قدیمی دیگر روی نمایش عمومی صفحه اثری ندارد.
                        </div>
                    @else
                        <textarea id="page-body-editor" name="body" class="w-full min-h-[220px]">{{ old('body', $page->body) }}</textarea>
                        <p class="admin-field-hint">برای طراحی بصری با عناصر (تصویر، ستون، اسلایدر و ...) می‌توانید هر زمان به
                            <a href="{{ route('admin.theme-design.canvas-edit', ['page' => $page->slug]) }}">قالب‌ساز Canvas</a> بروید؛ محتوای فعلی به‌صورت یک بلوک واقعی وارد می‌شود.</p>
                    @endif
                </div>
                <div class="form-group">
                    <label class="form-label">ترتیب</label>
                    <input type="number" name="order" value="{{ old('order', $page->order) }}" min="0">
                </div>
                <div class="form-group"><label><input type="checkbox" name="is_published" value="1" @if(old('is_published', $page->is_published)) checked @endif> منتشر شود</label></div>
                <div class="form-group"><label><input type="checkbox" name="show_in_menu" value="1" @if(old('show_in_menu', $page->show_in_menu)) checked @endif> در منو</label></div>

                <hr style="margin: 1rem 0;">
                <h3 style="font-size: 1rem;">هدر و فوتر این صفحه</h3>
                <p style="font-size: 0.9rem; color: #6b7280; margin-bottom: 0.75rem;">در غیر صورت انتخاب «پیش‌فرض سایت»، از تنظیمات کلی سایت استفاده می‌شود.</p>
                <div class="form-group">
                    <label class="form-label">هدر</label>
                    <select name="builder_header_override">
                        @foreach($headerOptions ?? [] as $value => $label)
                            <option value="{{ $value }}" @if(old('builder_header_override', $page->builder_disable_header ? \App\Services\BuilderLayoutService::PAGE_OVERRIDE_NONE : ($page->builder_header_template_override ?? '')) == $value) selected @endif>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">فوتر</label>
                    <select name="builder_footer_override">
                        @foreach($footerOptions ?? [] as $value => $label)
                            <option value="{{ $value }}" @if(old('builder_footer_override', $page->builder_disable_footer ? \App\Services\BuilderLayoutService::PAGE_OVERRIDE_NONE : ($page->builder_footer_template_override ?? '')) == $value) selected @endif>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="admin-page-tools">
                    <button type="submit" class="btn btn-primary">ذخیره</button>
                    <a href="{{ route('admin.pages.index') }}" class="btn btn-secondary">انصراف</a>
                </div>
            </form>
        </div>
    </div>
</div>
@include('admin.partials.tinymce-field-init', ['selector' => '#page-body-editor', 'uploadContext' => 'featured', 'height' => 420])
@endsection
