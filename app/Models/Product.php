<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasTranslations;

    protected array $translatable = ['title', 'description', 'short_description'];
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    protected static function booted(): void
    {
        static::saving(function (Product $product): void {
            $product->is_active = $product->status === self::STATUS_PUBLISHED;
        });
    }

    protected $fillable = [
        'title', 'slug', 'description', 'image', 'sku', 'category_id',
        'price', 'sale_price', 'stock', 'is_active', 'order',
        'meta_title', 'meta_description', 'status', 'canonical_url', 'og_image', 'video_url',
        'shipping_text', 'returns_text', 'faq', 'size_chart_url', 'size_chart_html',
        'is_new', 'sale_ends_at', 'low_stock_threshold', 'warranty_text', 'guarantee_text',
        'allow_backorder', 'min_order_qty', 'max_order_qty', 'views_count',
        'is_bundle', 'bundle_pricing', 'bundle_fixed_price', 'bundle_discount_percent',
        'early_access', 'builder_content',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'is_active' => 'boolean',
            'faq' => 'array',
            'is_new' => 'boolean',
            'sale_ends_at' => 'datetime',
            'allow_backorder' => 'boolean',
            'early_access'   => 'boolean',
            'is_bundle' => 'boolean',
            'bundle_fixed_price'      => 'decimal:2',
            'bundle_discount_percent' => 'decimal:2',
            'builder_content'         => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(ProductAttributeValue::class, 'product_product_attribute_value')
            ->withTimestamps();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ProductTag::class, 'product_product_tag')->withTimestamps();
    }

    public function bundleLines(): HasMany
    {
        return $this->hasMany(ProductBundle::class, 'parent_product_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function relatedLinked(string $type): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'product_related',
            'product_id',
            'related_product_id'
        )->withPivot('relation_type')->wherePivot('relation_type', $type);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function getEffectivePriceAttribute(): float
    {
        return $this->sale_price !== null && $this->sale_price < $this->price
            ? (float) $this->sale_price
            : (float) $this->price;
    }

    /**
     * قیمت نمایشی در لیست (کمترین واریانت در صورت وجود).
     */
    public function getDisplayPriceAttribute(): float
    {
        if ($this->relationLoaded('variants')) {
            $vars = $this->variants->filter(fn ($v) => $v->effective_price > 0);
            if ($vars->isNotEmpty()) {
                return (float) $vars->min('effective_price');
            }
        }

        return $this->effective_price;
    }

    public function usesVariants(): bool
    {
        if ($this->relationLoaded('variants')) {
            return $this->variants->isNotEmpty();
        }

        return $this->variants()->exists();
    }

    public function primaryImagePath(): ?string
    {
        if ($this->relationLoaded('images') && $this->images->isNotEmpty()) {
            return $this->images->first()->path;
        }
        $first = $this->images()->orderBy('sort_order')->value('path');

        return $first ?: $this->image;
    }

    public function isInStock(int $qty = 1, ?ProductVariant $variant = null): bool
    {
        if ($this->allow_backorder) {
            return true;
        }
        if ($variant) {
            return $variant->isInStock($qty);
        }
        if ($this->usesVariants()) {
            return $this->variants->contains(fn (ProductVariant $v) => $v->isInStock($qty));
        }
        if ($this->stock === null) {
            return true;
        }

        return (int) $this->stock >= $qty;
    }

    public function stockRemaining(?ProductVariant $variant = null): ?int
    {
        if ($variant) {
            return $variant->stock;
        }
        if ($this->usesVariants()) {
            return null;
        }

        return $this->stock !== null ? (int) $this->stock : null;
    }

    public function isLowStock(?ProductVariant $variant = null): bool
    {
        $threshold = $this->low_stock_threshold;
        if ($threshold === null || $threshold < 1) {
            return false;
        }
        if ($variant && $variant->stock !== null) {
            return (int) $variant->stock > 0 && (int) $variant->stock <= $threshold;
        }
        if (!$this->usesVariants() && $this->stock !== null) {
            return (int) $this->stock > 0 && (int) $this->stock <= $threshold;
        }

        return false;
    }
}
