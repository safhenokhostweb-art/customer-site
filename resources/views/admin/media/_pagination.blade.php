{{--
    Laravel's default pagination views live in vendor/laravel/framework and
    rely on Tailwind responsive utility classes (sm:flex / sm:hidden) that
    the build's content scanner never sees there (this admin panel is the
    only place in the app that calls ->links(), and vendor paths aren't
    scanned) — both blocks render but stay display:none at every viewport.
    This first-party partial reuses the app's own already-compiled .btn
    classes instead, so it works without depending on scanning vendor code.
--}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="صفحه‌بندی" style="display:flex;gap:12px;align-items:center;justify-content:space-between;flex-wrap:wrap;">
        <span style="font-size:13px;color:#6b7280;">
            نمایش {{ $paginator->firstItem() }} تا {{ $paginator->lastItem() }} از {{ $paginator->total() }}
        </span>
        <div style="display:flex;gap:8px;">
            @if ($paginator->onFirstPage())
                <span class="btn btn-secondary" aria-disabled="true" style="opacity:.5;pointer-events:none;">قبلی</span>
            @else
                <a class="btn btn-secondary" rel="prev" href="{{ $paginator->previousPageUrl() }}">قبلی</a>
            @endif

            @if ($paginator->hasMorePages())
                <a class="btn btn-secondary" rel="next" href="{{ $paginator->nextPageUrl() }}">بعدی</a>
            @else
                <span class="btn btn-secondary" aria-disabled="true" style="opacity:.5;pointer-events:none;">بعدی</span>
            @endif
        </div>
    </nav>
@endif
