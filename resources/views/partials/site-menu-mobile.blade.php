@php
    $items = $items ?? [];
@endphp
@foreach($items as $item)
    @if(!empty($item['children']))
        <details class="site-menu-mob-item border-b border-slate-100 pb-1 mb-1">
            <summary class="cursor-pointer py-2 font-medium list-none flex justify-between items-center">
                <span>{{ $item['title'] ?? '' }}</span>
                <span class="text-slate-400 text-xs" aria-hidden="true">▼</span>
            </summary>
            <div class="pr-3 space-y-1 border-r-2 border-slate-100 mr-1">
                @include('partials.site-menu-mobile', ['items' => $item['children']])
            </div>
        </details>
    @else
        <a href="{{ $item['url'] ?? '#' }}" target="{{ $item['target'] ?? '_self' }}" rel="{{ trim(($item['rel'] ?? '').' '.(($item['target'] ?? '_self') === '_blank' ? 'noopener noreferrer' : '')) }}" class="block py-2 {{ !empty($item['active_trail']) ? 'font-semibold text-blue-600' : '' }} {{ $item['css_class'] ?? '' }}">{{ $item['title'] ?? '' }}</a>
    @endif
@endforeach
