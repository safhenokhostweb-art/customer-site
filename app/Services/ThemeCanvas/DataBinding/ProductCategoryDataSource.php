<?php

namespace App\Services\ThemeCanvas\DataBinding;

use App\Models\ProductCategory;

final class ProductCategoryDataSource implements ThemeDataSource
{
    public const SORTS = ['catalog_order', 'name', 'manual'];

    public function __construct(private ThemeBindingMedia $media) {}

    public function source(): string
    {
        return 'product_categories';
    }

    public function resolve(array $query): array
    {
        $sort = (string) ($query['sort'] ?? 'catalog_order');
        $limit = (int) ($query['limit'] ?? 12);
        $selectedIds = array_values(array_unique(array_map('intval', $query['selected_ids'] ?? [])));

        $categories = ProductCategory::query()
            ->withCount(['products' => fn ($builder) => $builder->published()])
            ->with(['products' => fn ($builder) => $builder->published()->with('images')->orderBy('order')->orderByDesc('id')->limit(1)]);

        if ($sort === 'manual') {
            $categories->whereIn('id', $selectedIds);
        } elseif ($sort === 'name') {
            $categories->orderBy('name');
        } else {
            $categories->orderBy('order')->orderBy('id');
        }

        $resolved = $categories->limit($sort === 'manual' ? max(1, count($selectedIds)) : $limit)->get();
        if ($sort === 'manual') {
            $positions = array_flip($selectedIds);
            $resolved = $resolved->sortBy(fn (ProductCategory $category) => $positions[$category->id] ?? PHP_INT_MAX)->take($limit)->values();
        }

        return [
            'collection_url' => route('shop.index'),
            'items' => $resolved->map(function (ProductCategory $category): array {
                $representative = $category->products->first();
                $imagePath = $representative?->images->first()?->path ?: $representative?->image;

                return [
                    'id' => (int) $category->id,
                    'title' => (string) $category->name,
                    'slug' => (string) $category->slug,
                    'url' => route('shop.index', ['category' => $category->slug]),
                    'image_url' => $this->media->url($imagePath),
                    'product_count' => (int) $category->products_count,
                ];
            })->all(),
        ];
    }
}

