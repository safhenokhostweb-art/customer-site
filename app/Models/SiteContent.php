<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SiteContent extends Model
{
    use SoftDeletes;

    public const TYPE_ARTICLE       = 'article';
    public const TYPE_TRAINING      = 'training';
    public const TYPE_WORKFLOW      = 'workflow';
    public const TYPE_PROCEDURE     = 'procedure';
    public const TYPE_PRODUCT       = 'product_content';
    public const TYPE_HELP          = 'help';

    public const STATUS_DRAFT       = 'draft';
    public const STATUS_PUBLISHED   = 'published';
    public const STATUS_ARCHIVED    = 'archived';

    public static array $TYPES = [
        self::TYPE_ARTICLE    => 'مقاله',
        self::TYPE_TRAINING   => 'آموزش',
        self::TYPE_WORKFLOW   => 'فرآیند کاری',
        self::TYPE_PROCEDURE  => 'روال و مراحل',
        self::TYPE_PRODUCT    => 'محتوای محصول',
        self::TYPE_HELP       => 'راهنمای پنل',
    ];

    protected $fillable = [
        'type', 'title', 'slug', 'excerpt', 'body', 'status',
        'ai_generated', 'ai_prompt', 'category_id', 'author_id',
        'tags', 'meta', 'panel_section', 'published_at',
    ];

    protected $casts = [
        'tags'          => 'array',
        'meta'          => 'array',
        'ai_generated'  => 'boolean',
        'published_at'  => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->title) . '-' . Str::random(5);
            }
        });
    }

    public function steps(): HasMany
    {
        return $this->hasMany(SiteContentStep::class, 'content_id')->orderBy('sort_order');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(SiteContentCategory::class, 'category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function typeName(): string
    {
        return self::$TYPES[$this->type] ?? $this->type;
    }

    public function hasSteps(): bool
    {
        return in_array($this->type, [self::TYPE_TRAINING, self::TYPE_WORKFLOW, self::TYPE_PROCEDURE]);
    }

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeForSection($query, string $section)
    {
        return $query->where('panel_section', $section)->where('type', self::TYPE_HELP);
    }
}
