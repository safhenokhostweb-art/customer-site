@extends('themes.default.layout')

@section('title', isset($category) ? $category->name : 'وبلاگ')
@section('meta_description', isset($category) ? ('مطالب دسته ' . $category->name) : 'آخرین مقالات و آموزش‌ها')
@section('canonical', isset($category) ? route('blog.category', $category->slug) : route('blog.index'))

@section('breadcrumb')
    <a href="{{ route('home') }}">خانه</a>
    <span>/</span>
    <span>{{ isset($category) ? $category->name : 'وبلاگ' }}</span>
@endsection

@section('content')
@push('head')
<style>
.module-blog-page .module-filter-card { border:1px solid #e5e7eb; border-radius:.75rem; padding:.85rem; margin-bottom:1rem; background:#fff; }
.module-blog-page a:focus-visible, .module-blog-page button:focus-visible, .module-blog-page input:focus-visible { outline:2px solid var(--platform-primary,#4f46e5); outline-offset:2px; }
@media (max-width: 768px){ .module-blog-page .module-search-row { flex-direction:column; } }
</style>
@endpush
<div class="module-blog-page">
<h1 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem;">{{ isset($category) ? $category->name : 'وبلاگ' }}</h1>

<form method="get" action="{{ isset($category) ? route('blog.category', $category->slug) : route('blog.index') }}" class="module-filter-card" aria-label="جستجو در وبلاگ">
    <div class="module-search-row" style="display: flex; gap: 0.5rem;">
        <input id="blog-filter-q" type="text" name="q" value="{{ request('q') }}" placeholder="جستجو در مطالب..." style="flex: 1; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem;">
        <button type="submit" style="padding: 0.5rem 1rem; border-radius: 0.5rem; font-weight: 600; background: var(--platform-primary, #4f46e5); color: white; border: none; cursor: pointer;">جستجو</button>
    </div>
</form>

@if(isset($categories) && $categories->isNotEmpty())
<div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1.5rem;">
    <a href="{{ route('blog.index') }}" style="padding: 0.5rem 1rem; border-radius: 0.5rem; text-decoration: none; {{ !isset($category) ? 'background: #1f2937; color: white;' : 'background: #f3f4f6; color: #374151;' }}">همه</a>
    @foreach($categories as $c)
        <a href="{{ route('blog.category', $c->slug) }}" style="padding: 0.5rem 1rem; border-radius: 0.5rem; text-decoration: none; {{ isset($category) && $category->id === $c->id ? 'background: #1f2937; color: white;' : 'background: #f3f4f6; color: #374151;' }}">{{ $c->name }} <span style="font-size: 0.72rem;">({{ $c->published_posts_count ?? 0 }})</span></a>
    @endforeach
</div>
@endif

@if(isset($popularTags) && $popularTags->isNotEmpty() && !isset($category))
<div style="margin-bottom: 1.25rem;">
    <div style="font-size: 0.85rem; font-weight: 600; margin-bottom: 0.35rem;">برچسب‌های پرکاربرد</div>
    <div style="display: flex; flex-wrap: wrap; gap: 0.35rem;">
        @foreach($popularTags as $tg)
            <a href="{{ route('blog.index', array_filter(['tag' => $tg->slug, 'q' => request('q')])) }}" style="font-size: 0.78rem; padding: 0.25rem 0.6rem; border-radius: 9999px; text-decoration: none; background: {{ request('tag') === $tg->slug ? '#1f2937' : '#f3f4f6' }}; color: {{ request('tag') === $tg->slug ? '#fff' : '#374151' }};">{{ $tg->name }} ({{ $tg->published_posts_count }})</a>
        @endforeach
    </div>
</div>
@endif

@if(isset($featuredPosts) && request('q') === null && !isset($category) && $featuredPosts->isNotEmpty())
<section class="card" style="margin-bottom: 1.5rem;">
    <h2 style="font-weight:700; margin-bottom:1rem;">آخرین مطالب منتخب</h2>
    <div class="card-grid card-grid-3">
        @foreach($featuredPosts as $featured)
            <a href="{{ route('blog.show', $featured->slug) }}" style="text-decoration:none; color:inherit; border:1px solid #e5e7eb; border-radius:0.75rem; padding:0.9rem;">
                <div style="font-weight:700; margin-bottom:0.35rem;">{{ $featured->title }}</div>
                <div style="font-size:0.78rem; color:#6b7280;">{{ $featured->published_at ? \App\Helpers\Jalali::formatFa($featured->published_at, 'Y/m/d') : '' }} • {{ $featured->estimatedReadingMinutes() }} دقیقه</div>
            </a>
        @endforeach
    </div>
</section>
@endif

<div style="display: grid; gap: 1.25rem; grid-template-columns: minmax(0, 1fr);">
    <div class="card-grid card-grid-3">
        @forelse($posts as $post)
            <article class="card" style="display: flex; flex-direction: column; overflow: hidden;">
                <a href="{{ route('blog.show', $post->slug) }}" style="flex: 1; text-decoration: none; color: inherit;">
                    <div style="aspect-ratio: 16/9; background: #f3f4f6; overflow: hidden; margin: -1.5rem -1.5rem 1rem -1.5rem;">
                        @if($post->featured_image)
                            <img src="{{ \App\Helpers\ImageHelper::url($post->featured_image) }}" alt="{{ $post->title }}" style="width: 100%; height: 100%; object-fit: cover;">
                        @else
                            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 2.5rem;">📄</div>
                        @endif
                    </div>
                    <h2 style="font-weight: 700; font-size: 1.125rem; margin-bottom: 0.5rem;">{{ $post->title }}</h2>
                    <p style="color: #6b7280; font-size: 0.875rem; margin-bottom: 0.75rem;">
                        {{ $post->published_at ? \App\Helpers\Jalali::formatFa($post->published_at, 'Y/m/d') : '' }}
                        @if($post->category)
                            <span>• {{ $post->category->name }}</span>
                        @endif
                        <span>• {{ $post->estimatedReadingMinutes() }} دقیقه مطالعه</span>
                    </p>
                    <p style="color: #374151; font-size: 0.9rem; line-height: 1.7;">{{ $post->previewText() }}</p>
                </a>
            </article>
        @empty
            <div class="card" style="grid-column: 1 / -1; text-align: center; padding: 3rem;">
                <p style="font-size: 1.125rem; color: #6b7280; margin-bottom: 1.5rem;">مطلبی یافت نشد.</p>
                <a href="{{ route('blog.index') }}" style="font-weight: 600; color: var(--platform-link-color, #4f46e5);">بازگشت به وبلاگ</a>
            </div>
        @endforelse
    </div>

    @if(isset($popularPosts) && $popularPosts->isNotEmpty())
        <aside class="card">
            <h3 style="font-weight:700; margin-bottom: 0.75rem;">پربازدیدترین مطالب</h3>
            <div style="display: grid; gap: 0.5rem;">
                @foreach($popularPosts as $popularPost)
                    <a href="{{ route('blog.show', $popularPost->slug) }}" style="display:block; text-decoration:none; color: inherit; padding:0.5rem; border:1px solid #e5e7eb; border-radius:0.5rem;">
                        <div style="font-weight:600; font-size: 0.9rem;">{{ $popularPost->title }}</div>
                        <div style="font-size: 0.75rem; color:#6b7280;">{{ $popularPost->view_count ?? 0 }} بازدید</div>
                    </a>
                @endforeach
            </div>
        </aside>
    @endif
</div>

{{ $posts->links() }}
</div>
@endsection
