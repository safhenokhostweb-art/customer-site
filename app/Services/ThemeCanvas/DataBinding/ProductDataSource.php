<?php

namespace App\Services\ThemeCanvas\DataBinding;

use App\Helpers\CurrencyHelper;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class ProductDataSource implements ThemeDataSource
{
    public const SORTS = ['latest', 'catalog_order', 'price_asc', 'price_desc', 'most_viewed', 'new_arrivals', 'manual', 'best_selling_30d'];

    public function __construct(private ThemeBindingMedia $media) {}

    public function source(): string
    {
        return 'products';
    }

    public function resolve(array $query): array
    {
        $sort = (string) ($query['sort'] ?? 'catalog_order');
        $limit = (int) ($query['limit'] ?? 10);
        $selectedIds = array_values(array_unique(array_map('intval', $query['selected_ids'] ?? [])));

        $products = Product::query()
            ->published()
            ->with(['category', 'images', 'variants'])
            ->when(isset($query['category_id']), fn (Builder $builder) => $builder->where('category_id', (int) $query['category_id']))
            ->when(isset($query['collection_slug']), fn (Builder $builder) => $builder->whereHas(
                'category',
                fn (Builder $category) => $category->where('slug', (string) $query['collection_slug'])
            ));

        if ($sort === 'manual') {
            $products->whereIn('id', $selectedIds);
        } elseif ($sort === 'latest') {
            $products->orderByDesc('id');
        } elseif ($sort === 'new_arrivals') {
            $products->where('is_new', true)->orderByDesc('id');
        } elseif ($sort === 'price_asc') {
            $products->orderByRaw('COALESCE(sale_price, price) ASC')->orderBy('id');
        } elseif ($sort === 'price_desc') {
            $products->orderByRaw('COALESCE(sale_price, price) DESC')->orderBy('id');
        } elseif ($sort === 'most_viewed') {
            $products->orderByDesc('views_count')->orderByDesc('id');
        } elseif ($sort === 'best_selling_30d') {
            $sales = DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('orders.status', 'completed')
                ->where('orders.created_at', '>=', now()->subDays(30))
                ->groupBy('order_items.product_id')
                ->selectRaw('order_items.product_id, SUM(order_items.total) AS binding_revenue');
            $products->joinSub($sales, 'binding_sales', fn ($join) => $join->on('products.id', '=', 'binding_sales.product_id'))
                ->select('products.*')
                ->orderByDesc('binding_sales.binding_revenue');
        } else {
            $products->orderBy('order')->orderByDesc('id');
        }

        $resolved = $products->limit($sort === 'manual' ? max(1, count($selectedIds)) : $limit)->get();
        if ($sort === 'manual') {
            $positions = array_flip($selectedIds);
            $resolved = $resolved->sortBy(fn (Product $product) => $positions[$product->id] ?? PHP_INT_MAX)->take($limit)->values();
        }

        $pricingContext = CurrencyHelper::displayContext();

        return [
            'collection_url' => route('shop.index'),
            'items' => $resolved->map(fn (Product $product): array => $this->serialize($product, $pricingContext))->all(),
        ];
    }

    /** @param array{enabled:bool,migrated:bool} $pricingContext @return array<string,mixed> */
    private function serialize(Product $product, array $pricingContext): array
    {
        $regular = (float) $product->price;
        $variantPrices = $product->variants->map(function ($variant) use ($regular): float {
            $variantRegular = $variant->price_override !== null ? (float) $variant->price_override : $regular;
            $variantSale = $variant->sale_price !== null ? (float) $variant->sale_price : null;

            return $variantSale !== null && $variantSale < $variantRegular ? $variantSale : $variantRegular;
        })->filter(fn (float $price) => $price > 0);
        $price = $variantPrices->isNotEmpty() ? (float) $variantPrices->min() : (float) $product->effective_price;
        $discount = $regular > 0 && $price < $regular ? (int) round((($regular - $price) / $regular) * 100) : null;
        $imagePath = $product->images->first()?->path ?: $product->image;

        return [
            'id' => (int) $product->id,
            'title' => (string) $product->title,
            'slug' => (string) $product->slug,
            'url' => route('shop.show', ['slug' => $product->slug]),
            'image_url' => $this->media->url($imagePath),
            'category' => (string) ($product->category?->name ?? ''),
            'price' => $price,
            'regular_price' => $regular,
            'price_html' => CurrencyHelper::formatPriceWithContext($price, $pricingContext).' '.CurrencyHelper::unitLabel(),
            'regular_price_html' => CurrencyHelper::formatPriceWithContext($regular, $pricingContext).' '.CurrencyHelper::unitLabel(),
            'discount_percent' => $discount,
            'in_stock' => $product->isInStock(),
            'requires_variant' => $product->usesVariants(),
            'min_order_qty' => max(1, (int) ($product->min_order_qty ?? 1)),
            'max_order_qty' => $product->max_order_qty !== null ? (int) $product->max_order_qty : null,
            'cart_url' => route('shop.cart.add'),
            'wishlist_url' => route('shop.wishlist.toggle', ['product' => $product->id]),
            'csrf_token' => csrf_token(),
        ];
    }
}

