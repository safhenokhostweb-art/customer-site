<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Post extends Model
{
    public const EDITORIAL_DRAFT = 'draft';

    public const EDITORIAL_PENDING_REVIEW = 'pending_review';

    public const EDITORIAL_APPROVED = 'approved';

    public const EDITORIAL_REJECTED = 'rejected';

    public const EDITORIAL_PUBLISHED = 'published';

    protected $fillable = [
        'title', 'slug', 'body', 'excerpt', 'featured_image', 'gallery_images',
        'meta_title', 'meta_description', 'canonical_url', 'og_image',
        'category_id', 'series_id', 'part_number', 'user_id',
        'published_at', 'is_published', 'editorial_status', 'view_count', 'likes_count',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_published' => 'boolean',
            'gallery_images' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(PostSeries::class, 'series_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'post_tag');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(PostRevision::class)->orderByDesc('id');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->where('editorial_status', self::EDITORIAL_PUBLISHED)
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    /**
     * خلاصهٔ نمایش‌پذیر پست (excerpt یا تکه‌ای از body).
     */
    public function previewText(int $limit = 140): string
    {
        $text = trim((string) ($this->excerpt ?: strip_tags((string) $this->body)));

        return Str::limit($text, $limit);
    }

    /**
     * برآورد مدت زمان مطالعه بر حسب دقیقه.
     */
    public function estimatedReadingMinutes(int $wpm = 220): int
    {
        $plain = trim(strip_tags((string) $this->body));
        if ($plain === '') {
            return 1;
        }

        $words = str_word_count($plain);
        if ($words <= 0) {
            return 1;
        }

        return max(1, (int) ceil($words / max(120, $wpm)));
    }

    /**
     * وضعیت نمایشی در ادمین (پیش‌نویس، بازبینی، تایید، رد، زمان‌بندی، منتشر).
     */
    public function publicationStatus(): string
    {
        $es = (string) ($this->editorial_status ?? self::EDITORIAL_DRAFT);

        if ($es === self::EDITORIAL_PENDING_REVIEW && ! $this->is_published) {
            return 'pending_review';
        }
        if ($es === self::EDITORIAL_REJECTED && ! $this->is_published) {
            return 'rejected';
        }
        if ($es === self::EDITORIAL_APPROVED && ! $this->is_published) {
            return 'approved';
        }

        if ($this->is_published && $this->published_at && $this->published_at->isFuture()) {
            return 'scheduled';
        }

        if ($this->is_published && $es === self::EDITORIAL_PUBLISHED) {
            return 'published';
        }

        if (! $this->is_published) {
            return 'draft';
        }

        if ($this->is_published) {
            return 'published';
        }

        return 'draft';
    }

    public function seoTitle(): string
    {
        $metaTitle = trim((string) ($this->meta_title ?? ''));

        return $metaTitle !== '' ? $metaTitle : (string) $this->title;
    }

    public function seoDescription(int $limit = 160): string
    {
        $metaDescription = trim((string) ($this->meta_description ?? ''));
        if ($metaDescription !== '') {
            return Str::limit($metaDescription, $limit);
        }

        return Str::limit(trim((string) $this->previewText(220)), $limit);
    }

    /**
     * Canonical مطلق یا نسبی برای متا تگ.
     */
    public function effectiveCanonicalUrl(): string
    {
        $c = trim((string) ($this->canonical_url ?? ''));
        if ($c === '') {
            return route('blog.show', $this->slug);
        }
        if (str_starts_with($c, 'http://') || str_starts_with($c, 'https://')) {
            return $c;
        }

        return url($c);
    }
}
