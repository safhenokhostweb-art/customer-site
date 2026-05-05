@extends('themes.default.layout')

@section('title', \App\Helpers\Seo::title($product))
@section('meta_description', \App\Helpers\Seo::description($product))
@section('meta_image', \App\Helpers\Seo::imageUrl($product))
@section('canonical', route('shop.show', $product->slug))
@section('og_type', 'product')
@push('json-ld')
<script type="application/ld+json">@json(\App\Helpers\JsonLd::product($product))</script>
@endpush

@section('breadcrumb')
    <a href="{{ route('home') }}">خانه</a>
    <span style="margin: 0 0.5rem;">/</span>
    <a href="{{ route('shop.index') }}">فروشگاه</a>
    <span style="margin: 0 0.5rem;">/</span>
    <span>{{ Str::limit($product->title, 30) }}</span>
@endsection

@section('content')
<div style="max-width: 72rem; margin: 0 auto;">
    <div style="display: flex; flex-wrap: wrap; gap: 2rem; align-items: flex-start; margin-bottom: 2.5rem;">
        <div style="flex: 1 1 260px; max-width: 360px;">
            @if($product->image)
                <div style="aspect-ratio: 1; background: #f3f4f6; border-radius: 0.75rem; overflow: hidden; border: 1px solid #e5e7eb;">
                    <img src="{{ \App\Helpers\ImageHelper::url($product->image) }}" alt="{{ $product->title }}" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
            @else
                <div style="aspect-ratio: 1; background: #f3f4f6; border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 4rem; border: 1px solid #e5e7eb;">
                    📦
                </div>
            @endif
        </div>

        <div style="flex: 1 1 320px; min-width: 0;">
            <div class="card" style="padding: 1.75rem; border-radius: 0.75rem; border: 1px solid #e5e7eb; background: #ffffff;">
                <h1 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.75rem; color: var(--platform-title-color, #111827);">
                    {{ $product->title }}
                </h1>

                <p style="color: #6b7280; margin-bottom: 1rem; font-size: 0.9rem;">
                    کد محصول: <span style="font-weight: 600;">{{ $product->sku ?? '-' }}</span>
                </p>

                <div style="margin-bottom: 1.25rem;">
                    <span style="font-size: 1.4rem; font-weight: 700; color: var(--platform-primary, #4f46e5);">
                        {!! \App\Helpers\CurrencyHelper::formatPrice($product->effective_price) !!} {{ \App\Helpers\CurrencyHelper::unitLabel() }}
                    </span>
                    @if($product->sale_price && $product->sale_price < $product->price)
                        <span style="margin-right: 0.5rem; color: #9ca3af; text-decoration: line-through; font-size: 0.95rem;">
                            {!! \App\Helpers\CurrencyHelper::formatPrice($product->price) !!}
                        </span>
                    @endif
                </div>

                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1.25rem; font-size: 0.85rem; color: #4b5563;">
                    <span style="padding: 0.35rem 0.75rem; border-radius: 999px; background: #ecfdf5; color: #15803d;">
                        ارسال سریع
                    </span>
                    <span style="padding: 0.35rem 0.75rem; border-radius: 999px; background: #eff6ff; color: #1d4ed8;">
                        پرداخت امن
                    </span>
                    <span style="padding: 0.35rem 0.75rem; border-radius: 999px; background: #fef3c7; color: #92400e;">
                        ضمانت بازگشت وجه
                    </span>
                </div>

                <form method="post" action="{{ route('shop.cart.add') }}" class="flex flex-wrap items-center gap-4" style="margin-bottom: 0.5rem;">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <label class="flex items-center gap-2">
                        <span class="form-label mb-0">تعداد:</span>
                        <input type="number" name="quantity" value="1" min="1" class="form-input w-24">
                    </label>
                    <button type="submit" class="px-6 py-3 font-semibold rounded-lg text-white transition" style="background: var(--platform-button-primary-bg, #4f46e5); border: none; cursor: pointer;">
                        افزودن به سبد
                    </button>
                </form>

                <p style="margin-top: 0.75rem; font-size: 0.9rem;">
                    <a href="{{ route('shop.index') }}" style="font-weight: 600; color: var(--platform-link-color, #4f46e5); text-decoration: none;">
                        ← بازگشت به فروشگاه
                    </a>
                </p>
            </div>
        </div>
    </div>

    @if($product->description)
        <section class="card" style="margin-bottom: 2.5rem; padding: 1.75rem; border-radius: 0.75rem; border: 1px solid #e5e7eb; background: #ffffff;">
            <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 1rem;">توضیحات محصول</h2>
            <div style="line-height: 1.8; color: #4b5563;">
                {!! nl2br(e($product->description)) !!}
            </div>
        </section>
    @endif

    @if(isset($related) && $related->isNotEmpty())
        <section style="margin-bottom: 2.5rem;">
            <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 1.5rem;">محصولات مرتبط</h2>
            <div class="card-grid card-grid-4">
                @foreach($related as $rel)
                    <article class="card" style="display: flex; flex-direction: column; overflow: hidden;">
                        <a href="{{ route('shop.show', $rel->slug) }}" style="flex: 1; text-decoration: none; color: inherit;">
                            <div style="aspect-ratio: 1; background: #f3f4f6; overflow: hidden; margin: -1.5rem -1.5rem 1rem -1.5rem;">
                                @if($rel->image)
                                    <img src="{{ \App\Helpers\ImageHelper::url($rel->image) }}" alt="{{ $rel->title }}" style="width: 100%; height: 100%; object-fit: cover;">
                                @else
                                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 2.5rem;">📦</div>
                                @endif
                            </div>
                            <h3 style="font-weight: 700; font-size: 1rem; margin-bottom: 0.5rem;">{{ $rel->title }}</h3>
                            <p style="font-weight: 600; margin-bottom: 0.25rem; color: var(--platform-primary, #4f46e5);">
                                {!! \App\Helpers\CurrencyHelper::formatPrice($rel->effective_price) !!} {{ \App\Helpers\CurrencyHelper::unitLabel() }}
                            </p>
                        </a>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
