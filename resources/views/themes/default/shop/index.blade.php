@extends('themes.default.layout')

@section('title', 'فروشگاه')

@section('breadcrumb')
    <a href="{{ route('home') }}">خانه</a>
    <span class="mx-2">/</span>
    <span>فروشگاه</span>
@endsection

@section('content')
@push('head')
<style>
.module-shop-page .module-toolbar { display:flex; flex-wrap:wrap; gap:.5rem; margin-bottom:1rem; }
.module-shop-page .module-filter-card { border:1px solid #e5e7eb; border-radius:.75rem; padding:.85rem; margin-bottom:1rem; background:#fff; }
.module-shop-page a:focus-visible, .module-shop-page button:focus-visible, .module-shop-page input:focus-visible, .module-shop-page select:focus-visible { outline:2px solid #4f46e5; outline-offset:2px; }
@media (max-width: 768px){ .module-shop-page .module-filter-grid { grid-template-columns: 1fr !important; } }
</style>
@endpush
<div class="module-shop-page">
<h1 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 1rem;">فروشگاه</h1>
<p style="margin-bottom: 1rem;">
    <a href="{{ route('shop.wishlist') }}" style="font-weight: 600; color: var(--platform-link-color, #4f46e5); text-decoration: none; margin-left: 1rem;">علاقه‌مندی‌ها</a>
    <a href="{{ route('shop.compare') }}" style="font-weight: 600; color: var(--platform-link-color, #4f46e5); text-decoration: none;">مقایسه</a>
</p>

@if(isset($categories) && $categories->isNotEmpty())
<div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem;">
    <a href="{{ route('shop.index', array_filter(['q' => request('q'), 'tag' => request('tag'), 'min_price' => request('min_price'), 'max_price' => request('max_price'), 'sort' => request('sort'), 'attr' => request('attr')])) }}" style="padding: 0.5rem 1rem; border-radius: 0.5rem; text-decoration: none; {{ !request('category') ? 'background: #1f2937; color: white;' : 'background: #f3f4f6; color: #374151;' }}">همه</a>
    @foreach($categories as $c)
        <a href="{{ route('shop.index', array_merge(array_filter(['q' => request('q'), 'tag' => request('tag'), 'min_price' => request('min_price'), 'max_price' => request('max_price'), 'sort' => request('sort'), 'attr' => request('attr')]), ['category' => $c->slug])) }}" style="padding: 0.5rem 1rem; border-radius: 0.5rem; text-decoration: none; {{ request('category') === $c->slug ? 'background: #1f2937; color: white;' : 'background: #f3f4f6; color: #374151;' }}">{{ $c->name }}</a>
    @endforeach
</div>
@endif

@if(isset($tags) && $tags->isNotEmpty())
<div style="display: flex; flex-wrap: wrap; gap: 0.35rem; margin-bottom: 1rem; align-items: center;">
    <span style="font-size: 0.85rem; color: #6b7280;">برچسب:</span>
    <a href="{{ route('shop.index', array_filter(['category' => request('category'), 'q' => request('q'), 'min_price' => request('min_price'), 'max_price' => request('max_price'), 'sort' => request('sort'), 'attr' => request('attr')])) }}" style="font-size: 0.85rem; padding: 0.25rem 0.6rem; border-radius: 999px; background: {{ !request('tag') ? '#1f2937' : '#e5e7eb' }}; color: {{ !request('tag') ? '#fff' : '#374151' }}; text-decoration: none;">همه</a>
    @foreach($tags as $tg)
        <a href="{{ route('shop.index', array_merge(array_filter(['category' => request('category'), 'q' => request('q'), 'min_price' => request('min_price'), 'max_price' => request('max_price'), 'sort' => request('sort'), 'attr' => request('attr')]), ['tag' => $tg->slug])) }}" style="font-size: 0.85rem; padding: 0.25rem 0.6rem; border-radius: 999px; background: {{ request('tag') === $tg->slug ? '#1f2937' : '#e5e7eb' }}; color: {{ request('tag') === $tg->slug ? '#fff' : '#374151' }}; text-decoration: none;">{{ $tg->name }}</a>
    @endforeach
</div>
@endif

<form method="get" action="{{ route('shop.index') }}" class="module-filter-card" aria-label="فیلتر محصولات">
    <input type="hidden" name="category" value="{{ request('category') }}">
    <input type="hidden" name="tag" value="{{ request('tag') }}">
    @foreach(request('attr', []) as $ak => $av)
        <input type="hidden" name="attr[{{ $ak }}]" value="{{ $av }}">
    @endforeach
    <div class="module-filter-grid" style="display: grid; gap: 0.5rem; grid-template-columns: 2fr 1fr 1fr 1fr auto;">
        <input id="shop-filter-q" type="text" name="q" value="{{ request('q') }}" placeholder="جستجو در محصولات..." style="flex: 1; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem;" aria-label="جستجو در محصولات">
        <input type="number" name="min_price" value="{{ request('min_price') }}" placeholder="حداقل قیمت" style="padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem;">
        <input type="number" name="max_price" value="{{ request('max_price') }}" placeholder="حداکثر قیمت" style="padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem;">
        <select name="sort" style="padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem;">
            <option value="recommended" {{ ($sort ?? request('sort', 'recommended')) === 'recommended' ? 'selected' : '' }}>مرتب‌سازی پیشنهادی</option>
            <option value="newest" {{ ($sort ?? request('sort')) === 'newest' ? 'selected' : '' }}>جدیدترین</option>
            <option value="price_asc" {{ ($sort ?? request('sort')) === 'price_asc' ? 'selected' : '' }}>ارزان‌ترین</option>
            <option value="price_desc" {{ ($sort ?? request('sort')) === 'price_desc' ? 'selected' : '' }}>گران‌ترین</option>
        </select>
        <button type="submit" style="padding: 0.5rem 1rem; border-radius: 0.5rem; font-weight: 600; background: var(--platform-primary, #4f46e5); color: white; border: none; cursor: pointer;">جستجو</button>
    </div>
</form>

@if(isset($attributeFilters) && $attributeFilters->isNotEmpty())
@foreach($attributeFilters as $attr)
<form method="get" action="{{ route('shop.index') }}" style="display: inline-block; margin-left: 0.5rem; margin-bottom: 0.5rem;">
    <input type="hidden" name="category" value="{{ request('category') }}">
    <input type="hidden" name="tag" value="{{ request('tag') }}">
    <input type="hidden" name="q" value="{{ request('q') }}">
    <input type="hidden" name="min_price" value="{{ request('min_price') }}">
    <input type="hidden" name="max_price" value="{{ request('max_price') }}">
    <input type="hidden" name="sort" value="{{ request('sort') }}">
    @foreach(request('attr', []) as $ak => $av)
        @if($ak !== $attr->slug)
            <input type="hidden" name="attr[{{ $ak }}]" value="{{ $av }}">
        @endif
    @endforeach
    <label style="font-size: 0.85rem; color: #374151;">{{ $attr->name }}:</label>
    <select name="attr[{{ $attr->slug }}]" onchange="this.form.submit()" style="padding: 0.35rem 0.5rem; border-radius: 0.375rem; border: 1px solid #d1d5db;">
        <option value="">— همه —</option>
        @foreach($attr->values as $val)
            <option value="{{ $val->slug ?: $val->value }}" @selected(request()->input('attr.'.$attr->slug) == ($val->slug ?: $val->value))>{{ $val->value }}</option>
        @endforeach
    </select>
</form>
@endforeach
@endif

<div class="card-grid card-grid-3">
    @forelse($products as $product)
        <article class="card" style="display: flex; flex-direction: column; overflow: hidden;">
            <a href="{{ route('shop.show', $product->slug) }}" style="flex: 1; text-decoration: none; color: inherit;">
                <div style="aspect-ratio: 1; background: #f3f4f6; overflow: hidden; margin: -1.5rem -1.5rem 1rem -1.5rem;">
                    @php $img = $product->primaryImagePath(); @endphp
                    @if($img)
                        <img src="{{ \App\Helpers\ImageHelper::url($img) }}" alt="{{ $product->title }}" style="width: 100%; height: 100%; object-fit: cover;">
                    @else
                        <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 3rem;">📦</div>
                    @endif
                </div>
                <h2 style="font-weight: 700; font-size: 1.125rem; margin-bottom: 0.35rem;">{{ $product->title }}</h2>
                @if($product->early_access)
                <div style="margin-bottom:0.4rem">
                    <span style="display:inline-flex;align-items:center;gap:.25rem;font-size:.72rem;font-weight:700;padding:.15rem .5rem;border-radius:9999px;background:#fef3c7;color:#b45309;white-space:nowrap">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="11" height="11"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                        دسترسی زودهنگام
                    </span>
                </div>
                @endif
                @if($product->tags->isNotEmpty())
                    <p style="font-size: 0.75rem; color: #6b7280; margin-bottom: 0.35rem;">{{ $product->tags->pluck('name')->implode('، ') }}</p>
                @endif
                @if($product->category)
                    <p style="font-size: 0.8rem; color: #6b7280; margin-bottom: 0.4rem;">دسته: {{ $product->category->name }}</p>
                @endif
                <p style="font-weight: 600; margin-bottom: 1rem; color: var(--platform-primary, #4f46e5);">
                    @if($product->usesVariants())
                        از {!! \App\Helpers\CurrencyHelper::formatPrice($product->display_price) !!}
                    @else
                        {!! \App\Helpers\CurrencyHelper::formatPrice($product->display_price) !!}
                    @endif
                    {{ \App\Helpers\CurrencyHelper::unitLabel() }}
                </p>
                @if(!$product->usesVariants() && $product->stock !== null)
                    <p style="font-size: 0.8rem; color: {{ (int)$product->stock > 0 ? '#166534' : '#b91c1c' }}; margin-bottom: 0.75rem;">
                        {{ (int)$product->stock > 0 ? 'موجودی: '.$product->stock : 'ناموجود' }}
                    </p>
                @endif
            </a>
            <div style="display: flex; flex-wrap: wrap; gap: 0.35rem; margin-top: auto;">
                <form method="post" action="{{ route('shop.wishlist.toggle', $product) }}" style="flex: 1; min-width: 6rem;">
                    @csrf
                    <button type="submit" style="width: 100%; padding: 0.45rem; border-radius: 0.5rem; font-weight: 600; border: 1px solid #e5e7eb; background: #fff; cursor: pointer;">{{ $wishlist->has($product->id) ? '♥' : '♡' }}</button>
                </form>
                <form method="post" action="{{ route('shop.compare.toggle', $product) }}" style="flex: 1; min-width: 6rem;">
                    @csrf
                    <button type="submit" style="width: 100%; padding: 0.45rem; border-radius: 0.5rem; font-weight: 600; border: 1px solid #e5e7eb; background: #fff; cursor: pointer;">مقایسه</button>
                </form>
            </div>
            @if($product->usesVariants())
                <a href="{{ route('shop.show', $product->slug) }}" style="display: block; text-align: center; margin-top: 0.5rem; padding: 0.5rem 1rem; border-radius: 0.5rem; font-weight: 600; color: white; text-decoration: none; background: var(--platform-button-primary-bg, #4f46e5);">انتخاب تنوع</a>
            @else
                <form method="post" action="{{ route('shop.cart.add') }}" style="margin-top: 0.5rem;">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <button type="submit" style="width: 100%; padding: 0.5rem 1rem; border-radius: 0.5rem; font-weight: 600; color: white; border: none; cursor: pointer; background: var(--platform-button-primary-bg, #4f46e5);" {{ (!$product->allow_backorder && $product->stock !== null && (int)$product->stock < 1) ? 'disabled' : '' }}>
                        {{ (!$product->allow_backorder && $product->stock !== null && (int)$product->stock < 1) ? 'ناموجود' : 'افزودن به سبد' }}
                    </button>
                </form>
            @endif
        </article>
    @empty
        <div class="card" style="grid-column: 1 / -1; text-align: center; padding: 3rem;">
            <p style="color: #6b7280; margin-bottom: 1rem;">محصولی یافت نشد.</p>
            <a href="{{ route('home') }}" style="font-weight: 600; color: var(--platform-link-color, #4f46e5);">بازگشت به خانه</a>
        </div>
    @endforelse
</div>

{{ $products->links() }}

<div style="margin-top: 1.5rem;">
    <a href="{{ route('shop.cart') }}" style="display: inline-block; padding: 0.75rem 1.5rem; border-radius: 0.5rem; font-weight: 600; color: white; text-decoration: none; background: var(--platform-button-primary-bg, #4f46e5);">مشاهده سبد خرید</a>
</div>
</div>
@endsection
