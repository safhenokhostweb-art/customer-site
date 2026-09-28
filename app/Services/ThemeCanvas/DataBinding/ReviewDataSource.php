<?php

namespace App\Services\ThemeCanvas\DataBinding;

use App\Helpers\SiteHelper;
use App\Models\Review;

/** Tenant-database review source. Disabled modules resolve to an empty set. */
final class ReviewDataSource implements ThemeDataSource
{
    public const SORTS = ['latest', 'highest_rating', 'manual'];

    public function source(): string
    {
        return 'reviews';
    }

    public function resolve(array $query): array
    {
        if (! SiteHelper::moduleEnabled('reviews')) {
            return ['items' => []];
        }

        $sort = (string) ($query['sort'] ?? 'latest');
        $limit = (int) ($query['limit'] ?? 3);
        $selectedIds = array_values(array_unique(array_map('intval', $query['selected_ids'] ?? [])));
        $reviews = Review::query()->with('user:id,name')->where('is_approved', true);

        if ($sort === 'manual') {
            $reviews->whereIn('id', $selectedIds ?: [0]);
        } elseif ($sort === 'highest_rating') {
            $reviews->orderByDesc('rating')->orderByDesc('id');
        } else {
            $reviews->orderByDesc('id');
        }

        $resolved = $reviews->limit($sort === 'manual' ? max(1, count($selectedIds)) : $limit)->get();
        if ($sort === 'manual') {
            $positions = array_flip($selectedIds);
            $resolved = $resolved->sortBy(fn (Review $review) => $positions[$review->id] ?? PHP_INT_MAX)->take($limit)->values();
        }

        return ['items' => $resolved->map(fn (Review $review): array => [
            'id' => (int) $review->id,
            'author' => (string) ($review->author_name ?: $review->user?->name ?: 'مشتری'),
            'role' => 'مشتری تأییدشده',
            'rating' => (string) $review->rating,
            'body' => (string) $review->body,
            'avatar_url' => '',
            'avatar_alt' => (string) ($review->author_name ?: 'مشتری'),
        ])->all()];
    }
}
