@extends('themes.default.layout')

@section('title', 'ظ…ط±ع©ط² ظ…ط­طھظˆط§')
@section('meta_description', 'ظ…ظ‚ط§ظ„ط§طھطŒ ط¢ظ…ظˆط²ط´â€Œظ‡ط§ ظˆ ظ…ط­طھظˆط§غŒ طھط®طµطµغŒ')

@section('breadcrumb')
    <a href="{{ route('home') }}">ط®ط§ظ†ظ‡</a>
    <span style="margin: 0 0.5rem;">/</span>
    <span>ظ…ط±ع©ط² ظ…ط­طھظˆط§</span>
@endsection

@section('content')
<div style="max-width: 72rem; margin: 0 auto;">

    {{-- ظ‡ط¯ط± --}}
    <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 1.75rem; font-weight: 800; color: #111827; margin-bottom: 0.5rem;">ظ…ط±ع©ط² ظ…ط­طھظˆط§</h1>
        <p style="color: #6b7280; font-size: 1rem;">ظ…ظ‚ط§ظ„ط§طھطŒ ط¢ظ…ظˆط²ط´â€Œظ‡ط§ ظˆ ط±ط§ظ‡ظ†ظ…ط§ظ‡ط§غŒ طھط®طµطµغŒ</p>
    </div>

    {{-- ظپغŒظ„طھط± ظ†ظˆط¹ --}}
    @php
        $typeLabels = \App\Models\SiteContent::$TYPES;
        unset($typeLabels[\App\Models\SiteContent::TYPE_HELP]);
    @endphp
    <div style="display:flex; flex-wrap:wrap; gap:0.5rem; margin-bottom:1.5rem; align-items:center;">
        <a href="{{ route('content.index') }}"
           style="padding:0.35rem 0.85rem; border-radius:9999px; font-size:0.82rem; font-weight:600; text-decoration:none;
                  {{ !$type ? 'background:#4f46e5; color:#fff;' : 'background:#f3f4f6; color:#374151;' }}">
            ظ‡ظ…ظ‡
        </a>
        @foreach($typeLabels as $key => $label)
            @if(($typeCounts[$key] ?? 0) > 0)
            <a href="{{ route('content.index', ['type' => $key]) }}"
               style="padding:0.35rem 0.85rem; border-radius:9999px; font-size:0.82rem; font-weight:600; text-decoration:none;
                      {{ $type === $key ? 'background:#4f46e5; color:#fff;' : 'background:#f3f4f6; color:#374151;' }}">
                {{ $label }}
                <span style="font-size:0.72rem; opacity:0.7;">({{ $typeCounts[$key] }})</span>
            </a>
            @endif
        @endforeach

        {{-- ط¬ط³طھط¬ظˆ --}}
        <form action="{{ route('content.index') }}" method="GET" style="margin-right:auto; display:flex; gap:0.4rem;">
            @if($type)<input type="hidden" name="type" value="{{ $type }}">@endif
            <input type="text" name="q" value="{{ request('q') }}"
                   placeholder="ط¬ط³طھط¬ظˆ ط¯ط± ظ…ط­طھظˆط§..."
                   style="border:1px solid #e5e7eb; border-radius:0.5rem; padding:0.35rem 0.7rem;
                          font-size:0.82rem; outline:none; font-family:inherit; width:180px;">
            <button type="submit"
                    style="padding:0.35rem 0.7rem; background:#f3f4f6; border:1px solid #e5e7eb;
                           border-radius:0.5rem; font-size:0.82rem; cursor:pointer; font-family:inherit;">
                ًں”چ
            </button>
        </form>
    </div>

    {{-- ع©ط§ط±طھâ€Œظ‡ط§غŒ ظ…ط­طھظˆط§ --}}
    @if($contents->isEmpty())
        <div style="text-align:center; padding:4rem 1rem; color:#9ca3af;">
            <x-core-ui::icon name="file-text" style="width:3rem;height:3rem;margin:0 auto 1rem;opacity:.4" />
            <p>ظ…ط­طھظˆط§غŒغŒ ط¨ط±ط§غŒ ظ†ظ…ط§غŒط´ ظˆط¬ظˆط¯ ظ†ط¯ط§ط±ط¯.</p>
        </div>
    @else
        <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:1.25rem; margin-bottom:2rem;">
            @foreach($contents as $item)
            <a href="{{ route('content.show', $item->slug) }}"
               style="display:flex; flex-direction:column; border:1px solid #e5e7eb; border-radius:0.875rem;
                      padding:1.25rem; text-decoration:none; color:inherit; background:#fff;
                      transition:box-shadow .2s, transform .2s;"
               onmouseover="this.style.boxShadow='0 4px 20px rgba(0,0,0,.08)';this.style.transform='translateY(-2px)'"
               onmouseout="this.style.boxShadow='none';this.style.transform='none'">

                {{-- ظ†ظˆط¹ ظ…ط­طھظˆط§ --}}
                <div style="margin-bottom:0.75rem;">
                    @php
                        $typeColors = [
                            'article'       => '#dbeafe:#1e40af',
                            'training'      => '#d1fae5:#065f46',
                            'workflow'      => '#fef3c7:#92400e',
                            'procedure'     => '#ede9fe:#5b21b6',
                            'product_content'=> '#fce7f3:#9d174d',
                        ];
                        [$bgC, $txtC] = explode(':', $typeColors[$item->type] ?? '#f3f4f6:#374151');
                    @endphp
                    <span style="font-size:0.72rem; font-weight:700; padding:0.2rem 0.6rem;
                                 border-radius:9999px; background:{{ $bgC }}; color:{{ $txtC }};">
                        {{ $item->typeName() }}
                    </span>
                    @if($item->ai_generated)
                    <span style="font-size:0.68rem; color:#818cf8; margin-right:0.3rem;">âœ¨ AI</span>
                    @endif
                </div>

                {{-- ط¹ظ†ظˆط§ظ† --}}
                <h2 style="font-size:1rem; font-weight:700; color:#111827; margin-bottom:0.5rem; line-height:1.5;
                           display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
                    {{ $item->title }}
                </h2>

                {{-- ط®ظ„ط§طµظ‡ --}}
                @if($item->excerpt)
                <p style="font-size:0.825rem; color:#6b7280; line-height:1.6; margin-bottom:0.75rem; flex:1;
                          display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden;">
                    {{ $item->excerpt }}
                </p>
                @endif

                {{-- footer --}}
                <div style="display:flex; align-items:center; justify-content:space-between;
                            margin-top:auto; padding-top:0.75rem; border-top:1px solid #f3f4f6;">
                    <span style="font-size:0.75rem; color:#9ca3af;">
                        {{ $item->published_at ? \App\Helpers\Jalali::formatFa($item->published_at, 'Y/m/d') : '' }}
                    </span>
                    <span style="font-size:0.78rem; color:#4f46e5; font-weight:600;">ظ…ط·ط§ظ„ط¹ظ‡ â†گ</span>
                </div>
            </a>
            @endforeach
        </div>

        {{-- طµظپط­ظ‡â€Œط¨ظ†ط¯غŒ --}}
        @if($contents->hasPages())
        <div style="display:flex; justify-content:center; gap:0.4rem; flex-wrap:wrap;">
            @foreach($contents->links()->elements as $element)
                @if(is_string($element))
                    <span style="padding:0.4rem 0.6rem; font-size:0.85rem; color:#9ca3af;">{{ $element }}</span>
                @endif
                @if(is_array($element))
                    @foreach($element as $page => $url)
                        <a href="{{ $url }}"
                           style="padding:0.4rem 0.75rem; border-radius:0.5rem; font-size:0.85rem; text-decoration:none;
                                  {{ $contents->currentPage() == $page
                                     ? 'background:#4f46e5;color:#fff;'
                                     : 'background:#f3f4f6;color:#374151;' }}">
                            {{ $page }}
                        </a>
                    @endforeach
                @endif
            @endforeach
        </div>
        @endif
    @endif

</div>
@endsection


