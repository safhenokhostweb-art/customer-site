{{-- Wave 7.0c — Reusable admin breadcrumb partial.
     Accepts a $crumbs array of items shaped like:
         ['label' => 'متن', 'url' => 'مسیر یا null']
     The last item is always rendered as plain text (current page).
     Pages that opt-in include this above their `<div class="admin-page">`. --}}
@php
    $crumbs = $crumbs ?? [];
@endphp
@if(!empty($crumbs))
<nav class="admin-breadcrumb" aria-label="مسیر صفحه">
    <ol class="admin-breadcrumb-list">
        @foreach($crumbs as $i => $crumb)
            @php
                $label = $crumb['label'] ?? '';
                $url = $crumb['url'] ?? null;
                $isLast = $i === array_key_last($crumbs);
            @endphp
            <li class="admin-breadcrumb-item">
                @if($isLast || empty($url))
                    <span class="admin-breadcrumb-current" aria-current="page">{{ $label }}</span>
                @else
                    <a href="{{ $url }}" class="admin-breadcrumb-link">{{ $label }}</a>
                @endif
                @unless($isLast)
                    <span class="admin-breadcrumb-sep" aria-hidden="true">›</span>
                @endunless
            </li>
        @endforeach
    </ol>
</nav>
<style>
    .admin-breadcrumb { margin: 0 0 0.85rem; font-size: 0.78rem; color: #64748b; }
    .admin-breadcrumb-list { list-style: none; padding: 0; margin: 0; display: flex; flex-wrap: wrap; align-items: center; gap: 0.35rem; }
    .admin-breadcrumb-item { display: inline-flex; align-items: center; gap: 0.35rem; }
    .admin-breadcrumb-link { color: #4f46e5; text-decoration: none; }
    .admin-breadcrumb-link:hover { text-decoration: underline; }
    .admin-breadcrumb-current { color: #334155; font-weight: 600; }
    .admin-breadcrumb-sep { color: #94a3b8; }
</style>
@endif
