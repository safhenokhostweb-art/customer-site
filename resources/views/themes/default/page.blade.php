@extends('themes.default.layout')

@section('title', \App\Helpers\Seo::title($page))
@section('meta_description', \App\Helpers\Seo::description($page))
@section('canonical', route('page.show', $page->slug))
@push('json-ld')
@php
    $jsonLdWebPage = \App\Helpers\JsonLd::webPage($page->title, route('page.show', $page->slug), \App\Helpers\Seo::description($page));
@endphp
<script type="application/ld+json">@json($jsonLdWebPage)</script>
@endpush

@section('breadcrumb')
    <a href="{{ route('home') }}">خانه</a>
    <span class="mx-2">/</span>
    <span>{{ $page->title }}</span>
@endsection

@section('content')
@auth
    @if(auth()->user()->isTenantAdmin())
        <div class="mb-4 flex justify-end">
            <a href="{{ route('builder.edit', $page) }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg font-medium text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm">
                <x-core-ui::icon name="edit" style="width:20px;height:20px" />
                ویرایش صفحه
            </a>
        </div>
    @endif
@endauth

@if($page->usesBuilder())
    <article class="page-builder-content">
        {!! app(\App\Services\ThemeCanvas\ThemeCanvasRenderer::class)->setContext(['menu_html' => ''])->renderBlocks($page->getBuilderContentForRender()['sections'] ?? []) !!}
    </article>
@else
<article style="max-width:48rem;">
    <h1 style="font-size:1.75rem;font-weight:800;color:#111827;line-height:1.4;margin-bottom:1.5rem;">
        {{ $page->title }}
    </h1>
    <div class="content-body" style="line-height:1.85;color:#1f2937;font-size:1rem;">
        @php
            $bodyContent = $page->body ?? '';
            $isHtml = preg_match('/<(p|h[1-6]|ul|ol|div|strong|em|br)\b/i', $bodyContent);
        @endphp
        @if($isHtml)
            {!! \App\Helpers\PostHtml::safe($bodyContent) !!}
        @else
            {!! nl2br(e($bodyContent)) !!}
        @endif
    </div>
</article>
<style>
.content-body h2{font-size:1.3rem;font-weight:700;color:#111827;margin:1.75rem 0 0.6rem;}
.content-body h3{font-size:1.1rem;font-weight:600;color:#1f2937;margin:1.25rem 0 0.4rem;}
.content-body p{margin-bottom:1rem;}
.content-body ul,.content-body ol{padding-right:1.5rem;margin-bottom:1rem;}
.content-body li{margin-bottom:0.35rem;line-height:1.7;}
.content-body strong{color:#111827;font-weight:600;}
.content-body a{color:#4f46e5;text-decoration:underline;}
</style>
@endif
@endsection


