<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostRevision extends Model
{
    protected $fillable = ['post_id', 'user_id', 'snapshot'];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function snapshotFromPost(Post $post): array
    {
        return [
            'title' => $post->title,
            'slug' => $post->slug,
            'body' => $post->body,
            'excerpt' => $post->excerpt,
            'featured_image' => $post->featured_image,
            'gallery_images' => $post->gallery_images,
            'meta_title' => $post->meta_title,
            'meta_description' => $post->meta_description,
            'canonical_url' => $post->canonical_url,
            'og_image' => $post->og_image,
            'category_id' => $post->category_id,
            'series_id' => $post->series_id,
            'part_number' => $post->part_number,
            'tag_ids' => $post->tags()->pluck('tags.id')->values()->all(),
            'is_published' => $post->is_published,
            'published_at' => $post->published_at?->toIso8601String(),
            'editorial_status' => $post->editorial_status,
        ];
    }
}
