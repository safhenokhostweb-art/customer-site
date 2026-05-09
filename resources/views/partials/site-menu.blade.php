@php
    $menu = $menu ?? null;
    $variant = $variant ?? 'nav';
    $items = $items ?? [];
    $items = \App\Helpers\MenuActiveTrail::decorate($items, url()->current());
@endphp
@if($menu && !empty($items))
    @foreach($items as $item)
        @if($variant === 'footer')
            <a href="{{ $item['url'] ?? '#' }}" target="{{ $item['target'] ?? '_self' }}" rel="{{ trim(($item['rel'] ?? '').' '.(($item['target'] ?? '_self') === '_blank' ? 'noopener noreferrer' : '')) }}" class="hover:underline {{ $item['css_class'] ?? '' }} {{ !empty($item['active_trail']) ? 'font-semibold' : '' }}">
                @if(!empty($item['icon']))<span class="{{ $item['icon'] }}" aria-hidden="true"></span> @endif
                {{ $item['title'] ?? '' }}
                @if(!empty($item['badge']))<span class="text-xs opacity-80">({{ $item['badge'] }})</span>@endif
            </a>
            @if(!empty($item['children']))
                @foreach($item['children'] as $child)
                    <a href="{{ $child['url'] ?? '#' }}" target="{{ $child['target'] ?? '_self' }}" rel="{{ trim(($child['rel'] ?? '').' '.(($child['target'] ?? '_self') === '_blank' ? 'noopener noreferrer' : '')) }}" class="hover:underline {{ $child['css_class'] ?? '' }} {{ !empty($child['active_trail']) ? 'font-semibold' : '' }}">{{ $child['title'] ?? '' }}</a>
                @endforeach
            @endif
        @else
            @if(!empty($item['children']))
                <div class="relative group">
                    <a href="{{ $item['url'] ?? '#' }}" target="{{ $item['target'] ?? '_self' }}" rel="{{ trim(($item['rel'] ?? '').' '.(($item['target'] ?? '_self') === '_blank' ? 'noopener noreferrer' : '')) }}" class="nav-link {{ !empty($item['active_trail']) ? 'font-semibold' : '' }} {{ $item['css_class'] ?? '' }}">
                        @if(!empty($item['icon']))<span class="{{ $item['icon'] }}" aria-hidden="true"></span> @endif
                        {{ $item['title'] ?? '' }}
                        @if(!empty($item['badge']))<span class="text-xs rounded bg-slate-100 px-1">{{ $item['badge'] }}</span>@endif
                    </a>
                    @if(!empty($item['featured_html']))
                        <div class="hidden group-hover:block absolute top-full right-0 z-50 mt-1 p-2 bg-white border rounded text-sm max-w-xs">{!! $item['featured_html'] !!}</div>
                    @endif
                    @php
                        $cols = $item['mega_columns'] ?? [];
                        $useMega = is_array($cols) && count($cols) > 1;
                    @endphp
                    <div class="absolute top-full right-0 mt-1 py-2 min-w-[200px] bg-white rounded-lg shadow-lg border border-gray-100 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-50 {{ $useMega ? 'flex gap-4 min-w-[480px]' : '' }}" style="color: #1f2937;">
                        @if($useMega)
                            @foreach($cols as $col)
                                <div class="menu-mega-col px-2 border-l border-gray-100 first:border-l-0">
                                    @foreach($col['items'] ?? [] as $child)
                                        <a href="{{ $child['url'] ?? '#' }}" target="{{ $child['target'] ?? '_self' }}" rel="{{ trim(($child['rel'] ?? '').' '.(($child['target'] ?? '_self') === '_blank' ? 'noopener noreferrer' : '')) }}" class="block px-4 py-2 hover:bg-gray-50 {{ !empty($child['active_trail']) ? 'bg-blue-50 font-medium' : '' }} {{ $child['css_class'] ?? '' }}">{{ $child['title'] ?? '' }}</a>
                                    @endforeach
                                </div>
                            @endforeach
                        @else
                            @foreach($item['children'] as $child)
                                <a href="{{ $child['url'] ?? '#' }}" target="{{ $child['target'] ?? '_self' }}" rel="{{ trim(($child['rel'] ?? '').' '.(($child['target'] ?? '_self') === '_blank' ? 'noopener noreferrer' : '')) }}" class="block px-4 py-2 hover:bg-gray-50 {{ !empty($child['active_trail']) ? 'bg-blue-50 font-medium' : '' }} {{ $child['css_class'] ?? '' }}">{{ $child['title'] ?? '' }}</a>
                            @endforeach
                        @endif
                    </div>
                </div>
            @else
                <a href="{{ $item['url'] ?? '#' }}" target="{{ $item['target'] ?? '_self' }}" rel="{{ trim(($item['rel'] ?? '').' '.(($item['target'] ?? '_self') === '_blank' ? 'noopener noreferrer' : '')) }}" class="nav-link {{ !empty($item['active_trail']) ? 'font-semibold' : '' }} {{ $item['css_class'] ?? '' }}">{{ $item['title'] ?? '' }}</a>
            @endif
        @endif
    @endforeach
@endif
