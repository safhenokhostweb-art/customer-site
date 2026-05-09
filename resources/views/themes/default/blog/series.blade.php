@extends('themes.default.layout')

@section('title', $series->title)
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags((string) ($series->description ?? '')), 160) ?: 'مطالب مجموعه '.$series->title)
@section('canonical', route('blog.series', $series->slug))

@section('breadcrumb')
    <a href="{{ route('home') }}">خانه</a>
    <span style="margin: 0 0.5rem;">/</span>
    <a href="{{ route('blog.index') }}">وبلاگ</a>
    <span style="margin: 0 0.5rem;">/</span>
    <span>{{ \Illuminate\Support\Str::limit($series->title, 40) }}</span>
@endsection

@section('content')
<h1 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem;">{{ $series->title }}</h1>
@if($series->description)
    <p style="color:#4b5563; margin-bottom:1.5rem; line-height:1.8;">{{ $series->description }}</p>
@endif

<div class="card-grid card-grid-3" style="display:grid; gap:1rem;">
    @forelse($posts as $post)
        <article class="card">
            <a href="{{ route('blog.show', $post->slug) }}" style="text-decoration:none; color:inherit;">
                <div style="font-weight:700;">@if($post->part_number) قسمت {{ $post->part_number }}: @endif{{ $post->title }}</div>
                <div style="font-size:0.8rem; color:#6b7280; margin-top:0.35rem;">{{ $post->published_at ? \App\Helpers\Jalali::formatFa($post->published_at, 'Y/m/d') : '' }}</div>
            </a>
        </article>
    @empty
        <p>مطلبی در این مجموعه نیست.</p>
    @endforelse
</div>

@if(method_exists($posts, 'links'))
    <div style="margin-top:1.5rem;">{{ $posts->links() }}</div>
@endif

<p class="mt-8">
    <a href="{{ route('blog.index') }}" style="font-weight: 600; color: var(--platform-link-color, #4f46e5);">← بازگشت به وبلاگ</a>
</p>
@endsection
