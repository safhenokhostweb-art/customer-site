@extends('themes.default.layout')

@section('title', $content->title)
@section('meta_description', $content->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($content->body ?? ''), 160))

@section('breadcrumb')
    <a href="{{ route('home') }}">خانه</a>
    <span style="margin: 0 0.5rem;">/</span>
    <a href="{{ route('content.index') }}">مرکز محتوا</a>
    <span style="margin: 0 0.5rem;">/</span>
    <span>{{ \Illuminate\Support\Str::limit($content->title, 35) }}</span>
@endsection

@section('content')
<div style="max-width: 72rem; margin: 0 auto; display:flex; gap: 2rem; align-items:flex-start; flex-wrap:wrap;">

    {{-- ستون اصلی --}}
    <article style="flex:1; min-width:0;">

        {{-- نوع + تاریخ --}}
        <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:1rem; flex-wrap:wrap;">
            @php
                $typeColors = [
                    'article'        => ['#dbeafe','#1e40af'],
                    'training'       => ['#d1fae5','#065f46'],
                    'workflow'       => ['#fef3c7','#92400e'],
                    'procedure'      => ['#ede9fe','#5b21b6'],
                    'product_content'=> ['#fce7f3','#9d174d'],
                ];
                [$bgC, $txtC] = $typeColors[$content->type] ?? ['#f3f4f6','#374151'];
            @endphp
            <span style="font-size:0.78rem; font-weight:700; padding:0.25rem 0.7rem;
                         border-radius:9999px; background:{{ $bgC }}; color:{{ $txtC }};">
                {{ $content->typeName() }}
            </span>
            @if($content->ai_generated)
            <span style="font-size:0.75rem; color:#818cf8;">✨ تولید شده با AI</span>
            @endif
            @if($content->published_at)
            <span style="font-size:0.78rem; color:#9ca3af;">
                {{ \App\Helpers\Jalali::formatFa($content->published_at, 'Y/m/d') }}
            </span>
            @endif
            @if($content->category)
            <a href="{{ route('content.index', ['type' => $content->type]) }}"
               style="font-size:0.78rem; color:#4f46e5; text-decoration:none;">
                {{ $content->category->name }}
            </a>
            @endif
        </div>

        {{-- عنوان --}}
        <h1 style="font-size:1.75rem; font-weight:800; color:#111827; line-height:1.4; margin-bottom:1rem;">
            {{ $content->title }}
        </h1>

        {{-- خلاصه --}}
        @if($content->excerpt)
        <p style="font-size:1.05rem; color:#374151; line-height:1.8; margin-bottom:1.5rem;
                  padding:1rem; background:#f8faff; border-right:4px solid #4f46e5; border-radius:0 0.5rem 0.5rem 0;">
            {{ $content->excerpt }}
        </p>
        @endif

        {{-- برچسب‌ها --}}
        @if(!empty($content->tags))
        <div style="display:flex; flex-wrap:wrap; gap:0.35rem; margin-bottom:1.5rem;">
            @foreach($content->tags as $tag)
            <a href="{{ route('content.index', ['q' => $tag]) }}"
               style="font-size:0.75rem; padding:0.2rem 0.55rem; border-radius:9999px;
                      background:#eef2ff; color:#4338ca; text-decoration:none;">
                {{ $tag }}
            </a>
            @endforeach
        </div>
        @endif

        {{-- محتوای اصلی یا مراحل --}}
        @if($content->hasSteps() && $content->steps->isNotEmpty())
            {{-- محتوای مرحله‌ای --}}
            <div style="margin-bottom:2rem;">
                @foreach($content->steps as $step)
                <div style="display:flex; gap:1rem; margin-bottom:1.5rem; align-items:flex-start;">
                    <div style="flex-shrink:0; width:2rem; height:2rem; background:#4f46e5; color:#fff;
                                border-radius:50%; display:flex; align-items:center; justify-content:center;
                                font-weight:700; font-size:0.9rem; margin-top:0.15rem;">
                        {{ $loop->iteration }}
                    </div>
                    <div style="flex:1;">
                        <h3 style="font-weight:700; color:#111827; margin-bottom:0.4rem; font-size:1.05rem;">
                            {{ $step->title }}
                        </h3>
                        @if($step->body)
                        <div style="color:#374151; line-height:1.8; font-size:0.95rem;" class="content-body">
                            {!! \App\Helpers\PostHtml::safe($step->body) !!}
                        </div>
                        @endif
                    </div>
                </div>
                @if(!$loop->last)
                <hr style="border:none; border-top:1px dashed #e5e7eb; margin-bottom:1.5rem;">
                @endif
                @endforeach
            </div>
        @elseif($content->body)
            {{-- محتوای آزاد (HTML) --}}
            <div class="content-body" style="line-height:1.85; color:#1f2937;">
                {!! \App\Helpers\PostHtml::safe($content->body) !!}
            </div>
        @endif

    </article>

    {{-- ستون کناری --}}
    <aside style="width:260px; flex-shrink:0; position:sticky; top:5rem;">

        {{-- اشتراک‌گذاری --}}
        <div style="border:1px solid #e5e7eb; border-radius:0.875rem; padding:1.25rem; margin-bottom:1.25rem; background:#fff;">
            <h3 style="font-size:0.875rem; font-weight:700; color:#374151; margin-bottom:0.75rem;">اشتراک‌گذاری</h3>
            <div style="display:flex; flex-direction:column; gap:0.5rem;">
                <a href="https://telegram.me/share/url?url={{ urlencode(request()->url()) }}&text={{ urlencode($content->title) }}"
                   target="_blank" rel="noopener"
                   style="display:flex; align-items:center; gap:0.5rem; padding:0.5rem 0.75rem;
                          background:#e8f5e9; color:#1b5e20; border-radius:0.5rem; text-decoration:none; font-size:0.82rem; font-weight:600;">
                    <svg style="width:1rem;height:1rem;" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.562 8.247l-2.04 9.614c-.152.67-.547.836-1.108.52l-3.077-2.267-1.484 1.428c-.164.164-.302.302-.62.302l.221-3.143 5.737-5.18c.25-.221-.054-.344-.388-.123L7.147 14.56l-3.043-.95c-.66-.208-.673-.66.138-.977l11.877-4.578c.551-.199 1.032.134.843.192z"/>
                    </svg>
                    تلگرام
                </a>
                <button onclick="navigator.clipboard.writeText(window.location.href).then(function(){this.textContent='✅ کپی شد'}.bind(this))"
                        style="display:flex; align-items:center; gap:0.5rem; padding:0.5rem 0.75rem;
                               background:#f3f4f6; color:#374151; border:none; border-radius:0.5rem;
                               cursor:pointer; font-size:0.82rem; font-weight:600; font-family:inherit; text-align:right;">
                    🔗 کپی لینک
                </button>
            </div>
        </div>

        {{-- محتوای مرتبط --}}
        @if($related->isNotEmpty())
        <div style="border:1px solid #e5e7eb; border-radius:0.875rem; padding:1.25rem; background:#fff;">
            <h3 style="font-size:0.875rem; font-weight:700; color:#374151; margin-bottom:0.75rem;">محتوای مرتبط</h3>
            <div style="display:flex; flex-direction:column; gap:0.65rem;">
                @foreach($related as $rel)
                <a href="{{ route('content.show', $rel->slug) }}"
                   style="display:block; text-decoration:none; color:#111827; font-size:0.82rem;
                          line-height:1.5; padding-bottom:0.65rem; border-bottom:1px solid #f3f4f6;"
                   onmouseover="this.style.color='#4f46e5'" onmouseout="this.style.color='#111827'">
                    {{ \Illuminate\Support\Str::limit($rel->title, 55) }}
                </a>
                @endforeach
            </div>
        </div>
        @endif

    </aside>
</div>

{{-- بازگشت --}}
<div style="max-width:72rem; margin:2rem auto 0;">
    <a href="{{ route('content.index') }}"
       style="color:#4f46e5; font-weight:600; text-decoration:none; font-size:0.9rem;">
        ← بازگشت به مرکز محتوا
    </a>
</div>

<style>
.content-body h2 { font-size:1.2rem; font-weight:700; color:#111827; margin:1.75rem 0 0.6rem; }
.content-body h3 { font-size:1.05rem; font-weight:600; color:#1f2937; margin:1.25rem 0 0.4rem; }
.content-body p  { margin-bottom:1rem; }
.content-body ul, .content-body ol { padding-right:1.5rem; margin-bottom:1rem; }
.content-body li { margin-bottom:0.35rem; line-height:1.7; }
.content-body strong { color:#111827; }
.content-body a { color:#4f46e5; }
</style>
@endsection
