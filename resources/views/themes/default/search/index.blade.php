@extends('themes.default.layout')

@section('title', $q ? 'جستجو: ' . e($q) : 'جستجوی پیشرفته')

@section('breadcrumb')
    <a href="{{ route('home') }}">خانه</a>
    <span class="mx-2">/</span>
    <span>جستجو</span>
    @if($q)
        <span class="mx-2">/</span>
        <span>{{ e($q) }}</span>
    @endif
@endsection

@section('content')
<div class="search-page" style="max-width: 72rem; margin: 0 auto;">
    {{-- جستجوی اصلی — مشابه دیجی‌کالا --}}
    <div class="search-hero" style="background: linear-gradient(135deg, var(--platform-primary, #4f46e5) 0%, #6366f1 100%); border-radius: 1rem; padding: 2rem; margin-bottom: 2rem; color: white;">
        <h1 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 1rem; text-align: center;">جستجوی پیشرفته</h1>
        <div class="search-box-wrap" style="position: relative; max-width: 36rem; margin: 0 auto;">
            <form method="get" action="{{ route('search.index') }}" id="search-form" role="search">
                <input type="hidden" name="type" value="{{ $type }}">
                <input type="hidden" name="category" value="{{ $category ?? '' }}">
                <input type="hidden" name="min_price" value="{{ $minPrice ?? '' }}">
                <input type="hidden" name="max_price" value="{{ $maxPrice ?? '' }}">
                <input type="hidden" name="sort" value="{{ $sort }}">
                <div style="display: flex; gap: 0.5rem; background: white; border-radius: 0.75rem; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.2);">
                    <input type="search" name="q" id="search-input" value="{{ e($q) }}" placeholder="نام محصول، مطلب یا کد را جستجو کنید..."
                        autocomplete="off"
                        style="flex: 1; padding: 1rem 1.25rem; border: none; font-size: 1rem; color: #1f2937;">
                    <button type="submit" style="padding: 1rem 1.5rem; background: var(--platform-primary, #4f46e5); color: white; border: none; cursor: pointer; font-weight: 600;">
                        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </button>
                </div>
            </form>
            <div id="search-suggest" style="display: none; position: absolute; top: 100%; right: 0; left: 0; margin-top: 0.25rem; background: white; border-radius: 0.5rem; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.15); z-index: 50; max-height: 24rem; overflow-y: auto;"></div>
        </div>
    </div>

    {{-- فیلترها و مرتب‌سازی --}}
    <form method="get" action="{{ route('search.index') }}" id="filter-form" style="margin-bottom: 1.5rem;">
        <input type="hidden" name="q" value="{{ e($q) }}">
        <div style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: center; background: white; padding: 1rem; border-radius: 0.75rem; border: 1px solid #e5e7eb;">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <label style="font-weight: 600; color: #374151;">نوع:</label>
                <select name="type" class="form-select" style="width: auto; padding: 0.5rem 2rem 0.5rem 0.75rem;" onchange="this.form.submit()">
                    <option value="all" {{ $type === 'all' ? 'selected' : '' }}>همه</option>
                    @if(\App\Helpers\SiteHelper::shopEnabled())
                        <option value="products" {{ $type === 'products' ? 'selected' : '' }}>محصولات</option>
                    @endif
                    @if(\App\Helpers\SiteHelper::blogEnabled())
                        <option value="posts" {{ $type === 'posts' ? 'selected' : '' }}>مطالب</option>
                    @endif
                </select>
            </div>
            @if(isset($categories['product']) && $categories['product']->isNotEmpty() && in_array($type, ['all', 'products']))
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <label style="font-weight: 600; color: #374151;">دسته محصول:</label>
                    <select name="category" class="form-select" style="width: auto; min-width: 10rem;" onchange="this.form.submit()">
                        <option value="">همه</option>
                        @foreach($categories['product'] as $c)
                            <option value="{{ $c->slug }}" {{ ($category ?? '') === $c->slug ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            @if(isset($categories['post']) && $categories['post']->isNotEmpty() && $type === 'posts')
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <label style="font-weight: 600; color: #374151;">دسته مطلب:</label>
                    <select name="category" class="form-select" style="width: auto; min-width: 10rem;" onchange="this.form.submit()">
                        <option value="">همه</option>
                        @foreach($categories['post'] as $c)
                            <option value="{{ $c->slug }}" {{ ($category ?? '') === $c->slug ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            @if(\App\Helpers\SiteHelper::shopEnabled() && in_array($type, ['all', 'products']))
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <label style="font-weight: 600; color: #374151;">قیمت:</label>
                    <input type="number" name="min_price" value="{{ $minPrice ?? '' }}" placeholder="از" class="form-input" style="width: 7rem;" onchange="this.form.submit()">
                    <span>تا</span>
                    <input type="number" name="max_price" value="{{ $maxPrice ?? '' }}" placeholder="تا" class="form-input" style="width: 7rem;" onchange="this.form.submit()">
                    <span style="font-size: 0.875rem; color: #6b7280;">تومان</span>
                </div>
            @endif
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-right: auto;">
                <label style="font-weight: 600; color: #374151;">مرتب‌سازی:</label>
                <select name="sort" class="form-select" style="width: auto; min-width: 10rem;" onchange="this.form.submit()">
                    <option value="relevance" {{ $sort === 'relevance' ? 'selected' : '' }}>مرتبط‌ترین</option>
                    @if(\App\Helpers\SiteHelper::shopEnabled())
                        <option value="price_asc" {{ $sort === 'price_asc' ? 'selected' : '' }}>ارزان‌ترین</option>
                        <option value="price_desc" {{ $sort === 'price_desc' ? 'selected' : '' }}>گران‌ترین</option>
                    @endif
                    <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>جدیدترین</option>
                </select>
            </div>
        </div>
    </form>

    {{-- برچسب فیلترهای فعال --}}
    @if($q || $category || $minPrice || $maxPrice)
        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem;">
            @if($q)
                <span class="filter-chip" style="display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.35rem 0.75rem; background: #e0e7ff; color: #3730a3; border-radius: 9999px; font-size: 0.875rem;">جستجو: {{ e($q) }}</span>
            @endif
            @if($category)
                @php
                    $catName = $category;
                    if (isset($categories['product'])) { $c = $categories['product']->firstWhere('slug', $category); if ($c) $catName = $c->name; }
                    if (isset($categories['post'])) { $c = $categories['post']->firstWhere('slug', $category); if ($c) $catName = $c->name; }
                @endphp
                <span class="filter-chip" style="display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.35rem 0.75rem; background: #d1fae5; color: #065f46; border-radius: 9999px; font-size: 0.875rem;">دسته: {{ $catName }}</span>
            @endif
            @if($minPrice || $maxPrice)
                <span class="filter-chip" style="display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.35rem 0.75rem; background: #fef3c7; color: #92400e; border-radius: 9999px; font-size: 0.875rem;">
                    قیمت: {{ $minPrice ? number_format($minPrice) : '۰' }} – {{ $maxPrice ? number_format($maxPrice) : '∞' }} تومان
                </span>
            @endif
            <a href="{{ route('search.index') }}" style="font-size: 0.875rem; color: #6b7280; text-decoration: none;">پاک کردن همه</a>
        </div>
    @endif

    {{-- نتایج --}}
    <div id="search-results">
        @php
            $products = $results['products'] ?? collect();
            $posts = $results['posts'] ?? collect();
            $total = $results['total'] ?? 0;
        @endphp
        <p style="margin-bottom: 1rem; color: #6b7280; font-size: 0.875rem;">
            @if($total > 0)
                حدود {{ $total }} نتیجه
            @elseif($q || $category || $minPrice || $maxPrice)
                نتیجه‌ای یافت نشد. فیلترها را تغییر دهید.
            @else
                عبارت جستجو را وارد کنید یا فیلترها را تنظیم کنید.
            @endif
        </p>

        <div class="card-grid card-grid-3" id="results-grid">
            @foreach($products as $product)
                <article class="card" style="display: flex; flex-direction: column; overflow: hidden;">
                    <a href="{{ route('shop.show', $product->slug) }}" style="flex: 1; text-decoration: none; color: inherit;">
                        <div style="aspect-ratio: 1; background: #f3f4f6; overflow: hidden; margin: -1.5rem -1.5rem 1rem -1.5rem;">
                            @if($product->image)
                                <img src="{{ \App\Helpers\ImageHelper::url($product->image) }}" alt="{{ $product->title }}" loading="lazy" style="width: 100%; height: 100%; object-fit: cover;">
                            @else
                                <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 3rem;">📦</div>
                            @endif
                        </div>
                        <h2 style="font-weight: 700; font-size: 1.125rem; margin-bottom: 0.5rem;">{{ $product->title }}</h2>
                        <p style="font-weight: 600; margin-bottom: 1rem; color: var(--platform-primary, #4f46e5);">{!! \App\Helpers\CurrencyHelper::formatPrice($product->effective_price) !!} {{ \App\Helpers\CurrencyHelper::unitLabel() }}</p>
                    </a>
                    <form method="post" action="{{ route('shop.cart.add') }}" style="margin-top: auto;">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <button type="submit" style="width: 100%; padding: 0.5rem 1rem; border-radius: 0.5rem; font-weight: 600; color: white; border: none; cursor: pointer; background: var(--platform-button-primary-bg, #4f46e5);">افزودن به سبد</button>
                    </form>
                </article>
            @endforeach
            @foreach($posts as $post)
                <article class="card" style="display: flex; flex-direction: column; overflow: hidden;">
                    <a href="{{ route('blog.show', $post->slug) }}" style="flex: 1; text-decoration: none; color: inherit;">
                        <div style="aspect-ratio: 16/9; background: #f3f4f6; overflow: hidden; margin: -1.5rem -1.5rem 1rem -1.5rem;">
                            @if($post->featured_image)
                                <img src="{{ \App\Helpers\ImageHelper::url($post->featured_image) }}" alt="{{ $post->title }}" loading="lazy" style="width: 100%; height: 100%; object-fit: cover;">
                            @else
                                <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 2rem;">📝</div>
                            @endif
                        </div>
                        <span style="font-size: 0.75rem; color: #6b7280;">مطلب</span>
                        <h2 style="font-weight: 700; font-size: 1.125rem; margin-bottom: 0.5rem;">{{ $post->title }}</h2>
                        @if($post->excerpt)
                            <p style="font-size: 0.875rem; color: #6b7280; margin-bottom: 0.5rem;">{{ Str::limit(strip_tags($post->excerpt), 80) }}</p>
                        @endif
                    </a>
                    <a href="{{ route('blog.show', $post->slug) }}" style="margin-top: auto; padding: 0.5rem 1rem; border-radius: 0.5rem; font-weight: 600; text-align: center; color: white; text-decoration: none; background: var(--platform-primary, #4f46e5);">مشاهده</a>
                </article>
            @endforeach
        </div>

        @if($products->isEmpty() && $posts->isEmpty() && ($q || $category || $minPrice || $maxPrice))
            <div class="card" style="text-align: center; padding: 3rem;">
                <p style="font-size: 3rem; margin-bottom: 1rem;">🔍</p>
                <p style="color: #6b7280; margin-bottom: 1rem;">نتیجه‌ای یافت نشد.</p>
                <a href="{{ route('search.index') }}" style="font-weight: 600; color: var(--platform-link-color, #4f46e5);">جستجوی جدید</a>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
(function() {
    var input = document.getElementById('search-input');
    var suggest = document.getElementById('search-suggest');
    var debounceTimer;
    var lastQ = '';

    function fetchSuggest(q) {
        if (q.length < 2) {
            suggest.style.display = 'none';
            suggest.innerHTML = '';
            return;
        }
        fetch('{{ route("search.suggest") }}?q=' + encodeURIComponent(q))
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var items = data.items || [];
                if (items.length === 0) {
                    suggest.style.display = 'none';
                    suggest.innerHTML = '';
                    return;
                }
                suggest.innerHTML = items.map(function(it) {
                    var img = it.image ? '<img src="' + it.image + '" alt="" style="width: 3rem; height: 3rem; object-fit: cover; border-radius: 0.375rem;">' : '<div style="width: 3rem; height: 3rem; background: #e5e7eb; border-radius: 0.375rem;"></div>';
                    var price = it.price ? '<span style="font-weight: 600; color: #4f46e5;">' + new Intl.NumberFormat('fa-IR').format(it.price) + ' تومان</span>' : '';
                    return '<a href="' + it.url + '" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; text-decoration: none; color: #1f2937; border-bottom: 1px solid #f3f4f6;">' + img + '<div style="flex: 1;"><div style="font-weight: 500;">' + it.title + '</div>' + price + '</div></a>';
                }).join('');
                suggest.style.display = 'block';
            })
            .catch(function() {
                suggest.style.display = 'none';
            });
    }

    input.addEventListener('input', function() {
        var q = this.value.trim();
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function() { fetchSuggest(q); }, 300);
    });
    input.addEventListener('focus', function() {
        if (this.value.trim().length >= 2) fetchSuggest(this.value.trim());
    });
    input.addEventListener('blur', function() {
        setTimeout(function() { suggest.style.display = 'none'; }, 200);
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') suggest.style.display = 'none';
    });
})();
</script>
@endpush
@endsection
