<?php

namespace App\Services\ThemeCanvas\DataBinding;

use App\Models\Post;
use App\Services\ThemeCanvas\ThemePresentationCapabilityRegistry;

final class BlogPostDataSource implements ThemeDataSource
{
    public const SORTS = ['latest', 'oldest', 'most_viewed', 'manual'];

    public function __construct(private ThemeBindingMedia $media) {}

    public function source(): string
    {
        return 'blog_posts';
    }

    public function resolve(array $query): array
    {
        $sort = (string) ($query['sort'] ?? 'latest');
        $limit = (int) ($query['limit'] ?? 3);
        $selectedIds = array_values(array_unique(array_map('intval', $query['selected_ids'] ?? [])));

        // Tenant isolation: plain model scope on the tenant's default
        // connection (same rule as ProductDataSource — Phase 4B).
        $posts = Post::query()
            ->published()
            ->when(isset($query['category_id']), fn ($builder) => $builder->where('category_id', (int) $query['category_id']));

        if ($sort === 'manual') {
            $posts->whereIn('id', $selectedIds ?: [0]);
        } elseif ($sort === 'oldest') {
            $posts->orderBy('published_at')->orderBy('id');
        } elseif ($sort === 'most_viewed') {
            $posts->orderByDesc('view_count')->orderByDesc('id');
        } else {
            $posts->orderByDesc('published_at')->orderByDesc('id');
        }

        $resolved = $posts->limit($sort === 'manual' ? max(1, count($selectedIds)) : $limit)->get();

        if ($sort === 'manual') {
            $positions = array_flip($selectedIds);
            $resolved = $resolved->sortBy(fn (Post $post) => $positions[$post->id] ?? PHP_INT_MAX)->take($limit)->values();
        }

        return [
            'collection_url' => route('blog.index'),
            'items' => $resolved->map(fn (Post $post): array => [
                'id' => (int) $post->id,
                'title' => (string) $post->title,
                'slug' => (string) $post->slug,
                'url' => route('blog.show', ['slug' => $post->slug]),
                'image_url' => (string) ($this->media->url($post->featured_image) ?? ''),
                'image_alt' => (string) $post->title,
                'date_text' => $post->published_at !== null ? $post->published_at->format('j M Y') : '',
                'category_label' => (string) ($post->category?->name ?? ''),
                'excerpt' => (string) $post->previewText(120),
                'view_count' => (int) $post->view_count,
            ])->all(),
        ];
    }
}
