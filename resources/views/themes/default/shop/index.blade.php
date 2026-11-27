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
.module-shop-page { width: 100%; max-width: 100%; overflow-x: hidden; }
.module-shop-page .module-toolbar { display: flex; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1.25rem; align-items: center; }
.module-shop-page .module-filter-card { border: 1px solid #e5e7eb; border-radius: 1rem; padding: 1.25rem; margin-bottom: 1.5rem; background: #fff; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05); }
.module-shop-page .module-filter-grid { display: grid; gap: 0.75rem; grid-template-columns: minmax(0, 2fr) minmax(0, 1fr) minmax(0, 1fr) minmax(0, 1.2fr) auto; align-items: center; }
.module-shop-page .module-filter-grid input,
.module-shop-page .module-filter-grid select { width: 100%; min-width: 0; box-sizing: border-box; padding: 0.625rem 0.875rem; border: 1px solid #d1d5db; border-radius: 0.625rem; font-size: 0.875rem; background: #fff; }
.module-shop-page .module-filter-grid button { padding: 0.625rem 1.5rem; border-radius: 0.625rem; font-weight: 600; font-size: 0.875rem; background: var(--platform-primary, #4f46e5); color: #fff; border: none; cursor: pointer; white-space: nowrap; transition: opacity 0.2s; }
.module-shop-page .module-filter-grid button:hover { opacity: 0.9; }
.module-shop-page .attr-filters-bar { display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; margin-bottom: 1.25rem; }
.module-shop-page .attr-filter-item { display: inline-flex; align-items: center; gap: 0.35rem; }
.module-shop-page a:focus-visible, .module-shop-page button:focus-visible, .module-shop-page input:focus-visible, .module-shop-page select:focus-visible { outline: 2px solid var(--platform-primary, #4f46e5); outline-offset: 2px; }
@media (max-width: 1024px) {
    .module-shop-page .module-filter-grid { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); }
    .module-shop-page .module-filter-grid button { grid-column: 1 / -1; width: 100%; }
}
@media (max-width: 640px) {
    .module-shop-page .module-filter-grid { grid-template-columns: 1fr; }
}
</style>
@endpush
<div class="module-shop-page">
<div class="flex items-center justify-between flex-wrap gap-4 mb-4">
    <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0;">فروشگاه</h1>
    <div style="display: flex; gap: 1rem; align-items: center;">
        <a href="{{ route('shop.wishlist') }}" style="font-weight: 600; color: var(--platform-link-color, #4f46e5); text-decoration: none; font-size: 0.875rem; display: inline-flex; align-items: center; gap: 0.25rem;">
            <span>♥</span>
            <span>علاقه‌مندی‌ها</span>
        </a>
        <a href="{{ route('shop.compare') }}" style="font-weight: 600; color: var(--platform-link-color, #4f46e5); text-decoration: none; font-size: 0.875rem; display: inline-flex; align-items: center; gap: 0.25rem;">
            <span>⇄</span>
            <span>مقایسه</span>
        </a>
    </div>
</div>

@if(isset($categories) && $categories->isNotEmpty())
<div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1.25rem;">
    <a href="{{ route('shop.index', array_filter(['q' => request('q'), 'tag' => request('tag'), 'min_price' => request('min_price'), 'max_price' => request('max_price'), 'sort' => request('sort'), 'attr' => request('attr')])) }}" style="padding: 0.45rem 1rem; border-radius: 9999px; font-size: 0.875rem; text-decoration: none; transition: all 0.2s; {{ !request('category') ? 'background: #1f2937; color: white;' : 'background: #f3f4f6; color: #374151;' }}">همه دسته‌ها</a>
    @foreach($categories as $c)
        <a href="{{ route('shop.index', array_merge(array_filter(['q' => request('q'), 'tag' => request('tag'), 'min_price' => request('min_price'), 'max_price' => request('max_price'), 'sort' => request('sort'), 'attr' => request('attr')]), ['category' => $c->slug])) }}" style="padding: 0.45rem 1rem; border-radius: 9999px; font-size: 0.875rem; text-decoration: none; transition: all 0.2s; {{ request('category') === $c->slug ? 'background: #1f2937; color: white;' : 'background: #f3f4f6; color: #374151;' }}">{{ $c->name }}</a>
    @endforeach
</div>
@endif

@if(isset($tags) && $tags->isNotEmpty())
<div style="display: flex; flex-wrap: wrap; gap: 0.35rem; margin-bottom: 1.25rem; align-items: center;">
    <span style="font-size: 0.85rem; color: #6b7280; margin-left: 0.25rem;">برچسب:</span>
    <a href="{{ route('shop.index', array_filter(['category' => request('category'), 'q' => request('q'), 'min_price' => request('min_price'), 'max_price' => request('max_price'), 'sort' => request('sort'), 'attr' => request('attr')])) }}" style="font-size: 0.8rem; padding: 0.25rem 0.6rem; border-radius: 9999px; background: {{ !request('tag') ? '#1f2937' : '#e5e7eb' }}; color: {{ !request('tag') ? '#fff' : '#374151' }}; text-decoration: none;">همه</a>
    @foreach($tags as $tg)
        <a href="{{ route('shop.index', array_merge(array_filter(['category' => request('category'), 'q' => request('q'), 'min_price' => request('min_price'), 'max_price' => request('max_price'), 'sort' => request('sort'), 'attr' => request('attr')]), ['tag' => $tg->slug])) }}" style="font-size: 0.8rem; padding: 0.25rem 0.6rem; border-radius: 9999px; background: {{ request('tag') === $tg->slug ? '#1f2937' : '#e5e7eb' }}; color: {{ request('tag') === $tg->slug ? '#fff' : '#374151' }}; text-decoration: none;">{{ $tg->name }}</a>
    @endforeach
</div>
@endif

<form method="get" action="{{ route('shop.index') }}" class="module-filter-card" aria-label="فیلتر محصولات">
    <input type="hidden" name="category" value="{{ request('category') }}">
    <input type="hidden" name="tag" value="{{ request('tag') }}">
    @foreach(request('attr', []) as $ak => $av)
        <input type="hidden" name="attr[{{ $ak }}]" value="{{ $av }}">
    @endforeach
    <div class="module-filter-grid">
        <input id="shop-filter-q" type="text" name="q" value="{{ request('q') }}" placeholder="جستجو در محصولات..." aria-label="جستجو در محصولات">
        <input type="number" name="min_price" value="{{ request('min_price') }}" placeholder="حداقل قیمت (ریال)">
        <input type="number" name="max_price" value="{{ request('max_price') }}" placeholder="حداکثر قیمت (ریال)">
        <select name="sort">
            <option value="recommended" {{ ($sort ?? request('sort', 'recommended')) === 'recommended' ? 'selected' : '' }}>مرتب‌سازی پیشنهادی</option>
            <option value="newest" {{ ($sort ?? request('sort')) === 'newest' ? 'selected' : '' }}>جدیدترین</option>
            <option value="price_asc" {{ ($sort ?? request('sort')) === 'price_asc' ? 'selected' : '' }}>ارزان‌ترین</option>
            <option value="price_desc" {{ ($sort ?? request('sort')) === 'price_desc' ? 'selected' : '' }}>گران‌ترین</option>
        </select>
        <button type="submit">اعمال فیلتر</button>
    </div>
</form>

@if(isset($attributeFilters) && $attributeFilters->isNotEmpty())
<div class="attr-filters-bar">
@foreach($attributeFilters as $attr)
<form method="get" action="{{ route('shop.index') }}" class="attr-filter-item">
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
    <label style="font-size: 0.85rem; color: #374151; white-space: nowrap;">{{ $attr->name }}:</label>
    <select name="attr[{{ $attr->slug }}]" onchange="this.form.submit()" style="padding: 0.35rem 0.6rem; border-radius: 0.5rem; border: 1px solid #d1d5db; font-size: 0.85rem;">
        <option value="">— همه —</option>
        @foreach($attr->values as $val)
            <option value="{{ $val->slug ?: $val->value }}" @selected(request()->input('attr.'.$attr->slug) == ($val->slug ?: $val->value))>{{ $val->value }}</option>
        @endforeach
    </select>
</form>
@endforeach
</div>
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
                        <x-core-ui::icon name="star" class="" />
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
