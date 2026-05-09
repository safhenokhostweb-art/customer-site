@foreach($items as $row)
    <li>
        <strong>{{ $row['title'] ?? '' }}</strong>
        <span class="text-slate-500 text-xs">({{ $row['url'] ?? '#' }})</span>
        @if(!empty($row['children']))
            <ul class="list-circle pr-6 mt-1 space-y-1">
                @include('admin.menus.partials.preview-tree', ['items' => $row['children']])
            </ul>
        @endif
    </li>
@endforeach
