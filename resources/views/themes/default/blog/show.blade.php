@extends('themes.default.layout')

@section('title', \App\Helpers\Seo::title($post))
@section('meta_description', $post->seoDescription())
@section('meta_image', \App\Helpers\Seo::imageUrl($post))
@section('canonical', $post->effectiveCanonicalUrl())
@section('og_type', 'article')
@push('json-ld')
<script type="application/ld+json">@json(\App\Helpers\JsonLd::article($post))</script>
@endpush

@section('breadcrumb')
    <a href="{{ route('home') }}">خانه</a>
    <span style="margin: 0 0.5rem;">/</span>
    <a href="{{ route('blog.index') }}">وبلاگ</a>
    <span style="margin: 0 0.5rem;">/</span>
    <span>{{ \Illuminate\Support\Str::limit($post->title, 30) }}</span>
@endsection

@section('content')
@if($isPreview ?? false)
<div style="position:sticky;top:0;z-index:999;background:#f59e0b;color:#1c1917;
            padding:0.6rem 1.25rem;display:flex;align-items:center;justify-content:space-between;
            flex-wrap:wrap;gap:0.5rem;font-size:0.875rem;font-weight:600;box-shadow:0 2px 8px rgba(0,0,0,.15);">
    <div style="display:flex;align-items:center;gap:0.5rem;">
        <x-core-ui::icon name="eye" style="width:1.1rem;height:1.1rem;flex-shrink:0" style="width:2.5px;height:px" />
        حالت پیش‌نمایش — این صفحه برای بازدیدکنندگان نمایش داده نمی‌شود
        @php $pubStatus = $post->publicationStatus(); @endphp
        <span style="padding:.15rem .55rem;border-radius:9999px;font-size:.72rem;
                     background:{{ $pubStatus==='published'?'#16a34a':'#374151' }};color:#fff;">
            {{ match($pubStatus) {
                'published'=>'منتشرشده','scheduled'=>'زمان‌بندی‌شده',
                'pending_review'=>'در انتظار بازبینی','draft'=>'پیش‌نویس',default=>$pubStatus
            } }}
        </span>
    </div>
    <div style="display:flex;align-items:center;gap:0.75rem;">
        <a href="{{ route('admin.posts.edit', $post) }}"
           style="background:#1c1917;color:#fef3c7;padding:.3rem .85rem;border-radius:.4rem;
                  text-decoration:none;font-size:.82rem;font-weight:600;">
            ✏️ ویرایش مطلب
        </a>
        <a href="{{ route('admin.posts.index') }}"
           style="color:#1c1917;text-decoration:none;font-size:.82rem;">
            ← بازگشت به لیست
        </a>
    </div>
</div>
@endif

<article style="max-width: 48rem;">
    @if($post->featured_image)
    <div style="aspect-ratio: 16/9; background: #f3f4f6; border-radius: 0.75rem; overflow: hidden; margin-bottom: 1.5rem;">
        <img src="{{ \App\Helpers\ImageHelper::url($post->featured_image) }}" alt="{{ $post->title }}" style="width: 100%; height: 100%; object-fit: cover;">
    </div>
    @endif
    <h1 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 1rem;">{{ $post->title }}</h1>
    @if($post->excerpt)
        <p style="font-size: 1rem; color:#374151; margin-bottom:1rem; line-height:1.8;">{{ $post->excerpt }}</p>
    @endif
    <p style="color: #6b7280; margin-bottom: 1rem;">
        {{ $post->published_at ? \App\Helpers\Jalali::formatFa($post->published_at, 'Y/m/d') : '' }}
        @if($post->category)
            <span>• {{ $post->category->name }}</span>
        @endif
        <span>• حدود {{ $post->estimatedReadingMinutes() }} دقیقه مطالعه</span>
        <span>• @numFa($post->view_count) بازدید</span>
    </p>
    @if($post->tags->isNotEmpty())
        <div style="display:flex; flex-wrap:wrap; gap:0.35rem; margin-bottom:1rem;">
            @foreach($post->tags as $tag)
                <a href="{{ route('blog.index', ['tag' => $tag->slug]) }}" style="font-size:0.75rem; padding:0.2rem 0.55rem; border-radius:9999px; background:var(--platform-primary-light,#eef2ff); color:var(--platform-primary,#4338ca); text-decoration:none;">{{ $tag->name }}</a>
            @endforeach
        </div>
    @endif
    @if($post->series)
        <p style="font-size:0.9rem; margin-bottom:1rem;">
            <a href="{{ route('blog.series', $post->series->slug) }}" style="color: var(--platform-link-color, #4f46e5); font-weight:600;">مجموعه: {{ $post->series->title }}</a>
            @if($post->part_number)
                <span style="color:#6b7280;"> — قسمت {{ $post->part_number }}</span>
            @endif
        </p>
    @endif
    @if(isset($seriesSiblings) && $seriesSiblings->count() > 1)
        <nav style="margin-bottom:1.25rem; padding:0.75rem; border:1px solid #e5e7eb; border-radius:0.5rem; font-size:0.85rem;">
            <div style="font-weight:600; margin-bottom:0.35rem;">سایر قسمت‌ها</div>
            <ul style="margin:0; padding-inline-start:1.1rem;">
                @foreach($seriesSiblings as $sib)
                    <li style="margin:0.2rem 0;">
                        @if($sib->id === $post->id)
                            <span style="font-weight:600;">قسمت {{ $sib->part_number ?? '؟' }}: {{ \Illuminate\Support\Str::limit($sib->title, 48) }}</span>
                        @else
                            <a href="{{ route('blog.show', $sib->slug) }}">قسمت {{ $sib->part_number ?? '؟' }}: {{ \Illuminate\Support\Str::limit($sib->title, 48) }}</a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </nav>
    @endif
    <div style="line-height: 1.75;" class="post-body">{!! \App\Helpers\PostHtml::safe($post->body) !!}</div>
    @php $gallery = array_filter($post->gallery_images ?? []); @endphp
    @if(count($gallery) > 0)
        <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap:0.5rem; margin-top:1.25rem;">
            @foreach($gallery as $imgPath)
                <a href="{{ \App\Helpers\ImageHelper::url($imgPath) }}" target="_blank" rel="noopener noreferrer" style="display:block; border-radius:0.5rem; overflow:hidden; border:1px solid #e5e7eb;">
                    <img src="{{ \App\Helpers\ImageHelper::url($imgPath) }}" alt="" style="width:100%; height:100px; object-fit:cover;">
                </a>
            @endforeach
        </div>
    @endif
    <div style="margin-top:1rem; display:flex; align-items:center; gap:0.75rem;">
        <button type="button" id="blog-like-btn" data-url="{{ route('blog.like', $post->slug) }}" style="padding:0.4rem 0.85rem; border-radius:0.5rem; border:1px solid #d1d5db; background:#fff; cursor:pointer; font-size:0.85rem;">
            پسندیدم (<span id="blog-like-count">{{ num_fa($post->likes_count) }}</span>)
        </button>
    </div>
</article>
@if(isset($relatedPosts) && $relatedPosts->isNotEmpty())
    <section style="margin-top: 2rem; max-width: 48rem;">
        <h2 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 0.8rem;">مطالب مرتبط</h2>
        <div style="display:grid; gap: 0.5rem;">
            @foreach($relatedPosts as $relatedPost)
                <a href="{{ route('blog.show', $relatedPost->slug) }}" style="display:block; border:1px solid #e5e7eb; border-radius:0.5rem; padding:0.65rem; text-decoration:none; color:inherit;">
                    <div style="font-weight:600;">{{ $relatedPost->title }}</div>
                    <div style="font-size:0.8rem; color:#6b7280;">
                        {{ $relatedPost->published_at ? \App\Helpers\Jalali::formatFa($relatedPost->published_at, 'Y/m/d') : '' }}
                    </div>
                </a>
            @endforeach
        </div>
    </section>
@endif
<p class="mt-8">
    <a href="{{ route('blog.index') }}" class="font-semibold" style="color: var(--platform-link-color, #4f46e5);">← بازگشت به وبلاگ</a>
</p>
@push('scripts')
<script>
(function(){
    var btn = document.getElementById('blog-like-btn');
    if (!btn) return;
    var token = document.querySelector('meta[name="csrf-token"]');
    btn.addEventListener('click', function() {
        fetch(btn.getAttribute('data-url'), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token ? token.content : '',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        }).then(function() {
            var n = document.getElementById('blog-like-count');
            if (n) {
                var v = parseInt(n.textContent.replace(/,/g, ''), 10) || 0;
                n.textContent = (v + 1).toLocaleString('fa-IR');
            }
            btn.disabled = true;
        }).catch(function(){});
    });
})();
</script>
@endpush
@endsection


