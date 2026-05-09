@php
    $menus = \App\Helpers\PlatformUx::menus();
    $location = $location ?? 'header';
    $variant = $variant ?? 'nav';
    $menuData = $menus[$location] ?? null;
    $items = $menuData['items'] ?? [];
    $items = \App\Helpers\MenuActiveTrail::decorate($items, url()->current());
@endphp
@if(!empty($items))
    @foreach($items as $item)
        @if($variant === 'footer')
            <a href="{{ $item['url'] ?? '#' }}" target="{{ $item['target'] ?? '_self' }}" rel="{{ trim(($item['rel'] ?? '').' '.(($item['target'] ?? '_self') === '_blank' ? 'noopener noreferrer' : '')) }}" class="hover:underline {{ !empty($item['active_trail']) ? 'font-semibold' : '' }}">{{ $item['title'] }}</a>
            @if(!empty($item['children']))
                @foreach($item['children'] as $child)
                    <a href="{{ $child['url'] ?? '#' }}" target="{{ $child['target'] ?? '_self' }}" rel="{{ trim(($child['rel'] ?? '').' '.(($child['target'] ?? '_self') === '_blank' ? 'noopener noreferrer' : '')) }}" class="hover:underline {{ !empty($child['active_trail']) ? 'font-semibold' : '' }}">{{ $child['title'] }}</a>
                @endforeach
            @endif
        @else
            @if(!empty($item['children']))
                <div class="relative group">
                    <a href="{{ $item['url'] ?? '#' }}" target="{{ $item['target'] ?? '_self' }}" rel="{{ trim(($item['rel'] ?? '').' '.(($item['target'] ?? '_self') === '_blank' ? 'noopener noreferrer' : '')) }}" class="nav-link {{ !empty($item['active_trail']) ? 'font-semibold' : '' }}">{{ $item['title'] }}</a>
                    <div class="absolute top-full right-0 mt-1 py-2 min-w-[180px] bg-white rounded-lg shadow-lg border border-gray-100 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-50" style="color: #1f2937;">
                        @foreach($item['children'] as $child)
                            <a href="{{ $child['url'] ?? '#' }}" target="{{ $child['target'] ?? '_self' }}" rel="{{ trim(($child['rel'] ?? '').' '.(($child['target'] ?? '_self') === '_blank' ? 'noopener noreferrer' : '')) }}" class="block px-4 py-2 hover:bg-gray-50 {{ !empty($child['active_trail']) ? 'bg-blue-50 font-medium' : '' }}">{{ $child['title'] }}</a>
                        @endforeach
                    </div>
                </div>
            @else
                <a href="{{ $item['url'] ?? '#' }}" target="{{ $item['target'] ?? '_self' }}" rel="{{ trim(($item['rel'] ?? '').' '.(($item['target'] ?? '_self') === '_blank' ? 'noopener noreferrer' : '')) }}" class="nav-link {{ !empty($item['active_trail']) ? 'font-semibold' : '' }}">{{ $item['title'] }}</a>
            @endif
        @endif
    @endforeach
@endif
