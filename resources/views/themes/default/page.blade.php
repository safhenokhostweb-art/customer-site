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
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                ویرایش صفحه
            </a>
        </div>
    @endif
@endauth

@if($page->usesBuilder())
    <article class="page-builder-content">
        {!! app(\App\Services\ThemeBuilder\BlockRenderer::class)->setContext(['menu_html' => ''])->renderBlocks($page->getBuilderContentForRender()['sections'] ?? []) !!}
    </article>
@else
<article class="max-w-3xl">
    <h1 class="text-2xl md:text-3xl font-bold mb-6">{{ $page->title }}</h1>
    <div class="prose prose-lg max-w-none">{!! nl2br(e($page->body)) !!}</div>
</article>
@endif
@endsection
